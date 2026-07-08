# Instrucao rapida - Rastreador (MQTT, TCP e Webhook)

## 1) O que ja esta pronto

1. Webhook aberto para debug:
   - `https://2.25.205.114/webhook/rasteramento`
2. Listener MQTT no Laravel para receber e mostrar na tela.
3. Listener TCP no Laravel para receber e mostrar na tela.

## 1.1) Sobre IMEI no .env

Nao coloque IMEI no `.env`.

- O `.env` guarda configuracao geral (host, porta, usuario).
- O IMEI vem dentro da mensagem de cada rastreador.
- Assim um unico servidor atende varios carros ao mesmo tempo.

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
MQTT_TOPIC="tracker/#"
MQTT_QOS=1
MQTT_TLS=false
```

Para listener TCP, adicione tambem:

```env
TRACKER_TCP_HOST=0.0.0.0
TRACKER_TCP_PORT=5001
TRACKER_TCP_CLIENT_TIMEOUT=30
TRACKER_TCP_MAX_BYTES_PER_READ=4096
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

Se no seu servidor der erro de usuario com espaco, confira se `MQTT_USERNAME` e `MQTT_PASSWORD`
estao realmente vazios (sem espaco) e rode:

```bash
php artisan config:clear
```

Esse comando mostra na tela tudo que chegar via MQTT.

Neste momento ele esta em modo monitoramento, sem salvar mensagens.

Para testar TCP (sem salvar, so monitorar):

```powershell
php artisan tracker:tcp-listen
```

Se quiser outra porta no momento do teste:

```powershell
php artisan tracker:tcp-listen --port=5001
```

No rastreador TCP, configure:

1. Host/IP do servidor
2. Porta `5001` (ou a que voce definir)
3. Protocolo TCP

## 5) Teste rapido do webhook HTTP

```powershell
Invoke-RestMethod -Method Post -Uri "https://2.25.205.114/webhook/rasteramento" -ContentType "application/json" -Body '{"imei":"123456789012345","lat":-23.55,"lng":-46.63,"speed":52}'
```

## 6) Portas para liberar

1. `443/tcp` (HTTPS webhook)
2. `1883/tcp` (MQTT sem TLS)
3. `8883/tcp` (MQTT com TLS)
4. `5001/tcp` (TCP rastreador)

## 7) Proximo passo

Depois de confirmar que os dados estao chegando, fazemos:

1. salvar em banco
2. painel simples com ultima posicao
3. envio de comandos e confirmacao (ACK)




comando pra startar php artisan tracker:tcp-listen --port=5001
