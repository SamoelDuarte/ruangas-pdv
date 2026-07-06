# Instrucao rapida - Rastreador (MQTT + Webhook)

## 1) O que ja esta pronto

1. Webhook aberto para debug:
   - `https://2.25.205.114/webhook/rasteramento`
2. Listener MQTT no Laravel para receber e mostrar na tela.

## 2) IP e porta (resposta direta)

1. HTTPS: pode usar so o IP com `https://`.
   - Porta padrao: `443` (nao precisa escrever `:443`).
2. MQTT: precisa informar porta sim.
   - Mais comum: `1883` (sem TLS) ou `8883` (com TLS).

## 3) Configurar no .env

Copie estes campos para o seu `.env`:

```env
MQTT_HOST=2.25.205.114
MQTT_PORT=1883
MQTT_USERNAME=
MQTT_PASSWORD=
MQTT_CLIENT_ID=ruangas-listener
MQTT_TOPIC=tracker/#
MQTT_QOS=1
MQTT_TLS=false
```

Se for usar TLS no broker:

```env
MQTT_PORT=8883
MQTT_TLS=true
```

## 4) Rodar e ver o que chega

No PowerShell, dentro do projeto:

```powershell
php artisan tracker:mqtt-listen
```

Esse comando mostra na tela tudo que chegar via MQTT.

Neste momento ele esta em modo monitoramento, sem salvar mensagens.

## 5) Teste rapido do webhook HTTP

```powershell
Invoke-RestMethod -Method Post -Uri "https://2.25.205.114/webhook/rasteramento" -ContentType "application/json" -Body '{"imei":"123456789012345","lat":-23.55,"lng":-46.63,"speed":52}'
```

## 6) Portas para liberar

1. `443/tcp` (HTTPS webhook)
2. `1883/tcp` (MQTT sem TLS)
3. `8883/tcp` (MQTT com TLS)

## 7) Proximo passo

Depois de confirmar que os dados estao chegando, fazemos:

1. salvar em banco
2. painel simples com ultima posicao
3. envio de comandos e confirmacao (ACK)
