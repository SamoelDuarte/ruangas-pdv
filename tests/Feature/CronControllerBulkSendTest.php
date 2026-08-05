<?php

namespace Tests\Feature;

use App\Http\Controllers\CronController;
use Tests\TestCase;

class CronControllerBulkSendTest extends TestCase
{
    public function test_resolve_media_metadata_uses_actual_extension_for_image_media(): void
    {
        $controller = new CronController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('resolveMediaMetadata');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'https://example.com/logo.png');

        $this->assertSame('image/png', $result['mimetype']);
        $this->assertSame('logo.png', $result['fileName']);
    }

    public function test_normalize_phone_number_removes_non_digits_and_country_code(): void
    {
        $controller = new CronController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('normalizePhoneNumber');
        $method->setAccessible(true);

        $this->assertSame('11999999999', $method->invoke($controller, '(11) 99999-9999'));
        $this->assertSame('11999999999', $method->invoke($controller, '5511999999999'));
        $this->assertSame(null, $method->invoke($controller, 'abc'));
    }

    public function test_detects_invalid_whatsapp_number_response_from_evolution(): void
    {
        $controller = new CronController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('evolutionNumberDoesNotExist');
        $method->setAccessible(true);

        $response = [
            'status' => 400,
            'error' => 'Bad Request',
            'response' => [
                'message' => [
                    ['jid' => '5511960319050@s.whatsapp.net', 'exists' => false, 'number' => '5511960319050'],
                ],
            ],
        ];

        $this->assertTrue($method->invoke($controller, $response));
    }
}
