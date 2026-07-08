<?php

namespace App\Services\Tracker;

use App\Models\Carro;
use App\Models\TrackerAddressStay;
use App\Models\TrackerPing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrackerTcpMessageIngestor
{
    public function ingest(string $rawMessage, ?string $peer = null): ?TrackerPing
    {
        $parsed = $this->parseMessage($rawMessage);

        if (!$parsed || empty($parsed['imei'])) {
            return null;
        }

        $carro = Carro::where('imei_rastreador', $parsed['imei'])->first();

        $addressLine = null;
        $geocodeSource = null;
        if ($parsed['latitude'] !== null && $parsed['longitude'] !== null) {
            [$addressLine, $geocodeSource] = $this->reverseGeocode((float) $parsed['latitude'], (float) $parsed['longitude']);
        }

        $eventTime = $parsed['gps_at'] ?? Carbon::now();
        $stay = null;
        if (!empty($addressLine)) {
            $stay = $this->upsertAddressStay(
                $parsed['imei'],
                $carro?->id,
                $addressLine,
                $parsed['latitude'],
                $parsed['longitude'],
                $eventTime
            );
        }

        return TrackerPing::create([
            'carro_id' => $carro?->id,
            'tracker_address_stay_id' => $stay?->id,
            'imei' => $parsed['imei'],
            'packet_type' => $parsed['packet_type'],
            'packet_origin' => $parsed['packet_origin'],
            'protocol' => $parsed['protocol'],
            'device_name' => $parsed['device_name'],
            'raw_message' => $parsed['raw_message'],
            'latitude' => $parsed['latitude'],
            'longitude' => $parsed['longitude'],
            'altitude' => $parsed['altitude'],
            'speed' => $parsed['speed'],
            'ignition' => $parsed['ignition'],
            'in_motion' => $parsed['in_motion'],
            'address_line' => $addressLine,
            'geocode_source' => $geocodeSource,
            'gps_at' => $parsed['gps_at'],
            'received_at' => Carbon::now(),
            'metadata' => [
                'peer' => $peer,
                'part_count' => $parsed['part_count'],
                'battery' => $parsed['battery'],
            ],
        ]);
    }

    private function parseMessage(string $rawMessage): ?array
    {
        $trimmed = trim($rawMessage);
        if ($trimmed === '') {
            return null;
        }

        if (str_ends_with($trimmed, '$')) {
            $trimmed = substr($trimmed, 0, -1);
        }

        $parts = explode(',', $trimmed);
        if (count($parts) < 3) {
            return null;
        }

        $header = $parts[0];
        $packetOrigin = null;
        $packetType = null;

        if (str_contains($header, ':')) {
            [$packetOrigin, $packetType] = explode(':', $header, 2);
        } else {
            $packetType = $header;
        }

        $protocol = $parts[1] ?? null;
        $imei = isset($parts[2]) ? preg_replace('/\D+/', '', (string) $parts[2]) : null;
        $deviceName = $parts[3] ?? null;

        $latitude = null;
        $longitude = null;
        $altitude = null;
        $speed = null;
        $gpsAt = null;
        $battery = null;
        $ignition = $this->guessIgnition($packetType, $parts);
        $inMotion = $this->guessMotion($packetType, $parts);

        if (in_array($packetType, ['GTFRI', 'GTERI'], true)) {
            $speed = $this->toFloat($parts[8] ?? null);
            $altitude = $this->toFloat($parts[10] ?? null);
            $longitude = $this->toFloat($parts[11] ?? null);
            $latitude = $this->toFloat($parts[12] ?? null);
            $gpsAt = $this->parseTrackerDate($parts[13] ?? null);
            $battery = $this->toFloat($parts[20] ?? null);
        } elseif ($packetType === 'GTINF') {
            $battery = $this->toFloat($parts[12] ?? null);
            $gpsAt = $this->parseTrackerDate($parts[17] ?? null) ?: $this->parseTrackerDate($parts[27] ?? null);
        } else {
            [$longitude, $latitude] = $this->findCoordinates($parts);
            $gpsAt = $this->findDateInParts($parts);
        }

        if ($inMotion === null && $speed !== null) {
            $inMotion = $speed > 3;
        }

        return [
            'packet_type' => $packetType,
            'packet_origin' => $packetOrigin,
            'protocol' => $protocol,
            'imei' => $imei,
            'device_name' => $deviceName,
            'raw_message' => trim($rawMessage),
            'latitude' => $this->isValidLatitude($latitude) ? $latitude : null,
            'longitude' => $this->isValidLongitude($longitude) ? $longitude : null,
            'altitude' => $altitude,
            'speed' => $speed,
            'gps_at' => $gpsAt,
            'ignition' => $ignition,
            'in_motion' => $inMotion,
            'battery' => $battery,
            'part_count' => count($parts),
        ];
    }

    private function reverseGeocode(float $latitude, float $longitude): array
    {
        if (!config('tracker_tcp.reverse_geocode', true)) {
            return [null, null];
        }

        $lat = round($latitude, 5);
        $lng = round($longitude, 5);
        $cacheKey = "tracker_geocode_v2_{$lat}_{$lng}";

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return [$cached, 'cache'];
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => (string) config('app.name', 'Laravel') . '-tracker/1.0',
            ])
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'jsonv2',
                'addressdetails' => 1,
            ]);

        if (!$response->successful()) {
            return [null, null];
        }

        $shortAddress = $this->extractStreetAndNumber($response->json());
        if ($shortAddress === null) {
            return [null, null];
        }

        Cache::put($cacheKey, $shortAddress, now()->addDays(30));

        return [$shortAddress, 'nominatim'];
    }

    private function extractStreetAndNumber(array $json): ?string
    {
        $address = data_get($json, 'address', []);
        if (!is_array($address)) {
            return null;
        }

        $street = $this->firstNonEmptyString([
            data_get($address, 'road'),
            data_get($address, 'pedestrian'),
            data_get($address, 'residential'),
            data_get($address, 'path'),
            data_get($address, 'footway'),
        ]);

        if ($street === null) {
            return null;
        }

        $houseNumber = $this->firstNonEmptyString([
            data_get($address, 'house_number'),
            data_get($address, 'housenumber'),
        ]);

        return $houseNumber !== null ? "{$street}, {$houseNumber}" : $street;
    }

    private function firstNonEmptyString(array $values): ?string
    {
        foreach ($values as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function upsertAddressStay(
        string $imei,
        ?int $carroId,
        string $addressLine,
        ?float $latitude,
        ?float $longitude,
        Carbon $eventTime
    ): TrackerAddressStay {
        $lastStay = TrackerAddressStay::where('imei', $imei)->latest('id')->first();

        if ($lastStay && $this->normalizeAddress($lastStay->address_line) === $this->normalizeAddress($addressLine)) {
            $arrived = $lastStay->arrived_at ?? $eventTime;
            $lastStay->update([
                'carro_id' => $carroId ?? $lastStay->carro_id,
                'left_at' => $eventTime,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'permanence_seconds' => max(0, $arrived->diffInSeconds($eventTime)),
            ]);

            return $lastStay->fresh();
        }

        return TrackerAddressStay::create([
            'carro_id' => $carroId,
            'imei' => $imei,
            'address_line' => $addressLine,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'arrived_at' => $eventTime,
            'left_at' => $eventTime,
            'permanence_seconds' => 0,
        ]);
    }

    private function findCoordinates(array $parts): array
    {
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $first = $this->toFloat($parts[$i] ?? null);
            $second = $this->toFloat($parts[$i + 1] ?? null);

            if ($this->isValidLongitude($first) && $this->isValidLatitude($second)) {
                return [$first, $second];
            }
        }

        return [null, null];
    }

    private function findDateInParts(array $parts): ?Carbon
    {
        foreach ($parts as $part) {
            $date = $this->parseTrackerDate($part);
            if ($date !== null) {
                return $date;
            }
        }

        return null;
    }

    private function parseTrackerDate(?string $value): ?Carbon
    {
        $normalized = trim((string) $value);
        if (!preg_match('/^\d{14}$/', $normalized)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('YmdHis', $normalized, config('app.timezone', 'UTC'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function guessIgnition(?string $packetType, array $parts): ?bool
    {
        if ($packetType === 'GTIGN' || $packetType === 'GTIGL') {
            return true;
        }

        if ($packetType === 'GTIGF') {
            return false;
        }

        if (in_array($packetType, ['GTFRI', 'GTERI'], true)) {
            $candidate = $parts[7] ?? null;
            if ($candidate === '1') {
                return true;
            }
            if ($candidate === '0') {
                return false;
            }
        }

        return null;
    }

    private function guessMotion(?string $packetType, array $parts): ?bool
    {
        if ($packetType === 'GTMPN') {
            return true;
        }

        if ($packetType === 'GTMPF') {
            return false;
        }

        if (in_array($packetType, ['GTFRI', 'GTERI'], true)) {
            $speed = $this->toFloat($parts[8] ?? null);
            if ($speed !== null) {
                return $speed > 3;
            }
        }

        return null;
    }

    private function normalizeAddress(string $address): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $address)));
    }

    private function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function isValidLatitude(?float $value): bool
    {
        return $value !== null && $value >= -90 && $value <= 90;
    }

    private function isValidLongitude(?float $value): bool
    {
        return $value !== null && $value >= -180 && $value <= 180;
    }
}
