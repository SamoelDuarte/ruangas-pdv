<?php

// Função para verificar se o número já foi escolhido
if (!function_exists('isEscolhido')) {
    function isEscolhido($numero, $numerosEscolhidos)
    {
        return in_array($numero, $numerosEscolhidos);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorteio Ruan Gas</title>

    <!-- Link para Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Reset e estilo base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Raleway', sans-serif;
            background-image: url('/public/assets/imagens/fundo.jpg');
            /* Substitua com o caminho da sua imagem */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
            flex-direction: column;
        }

        .container {
            background: linear-gradient(135deg, #6bff8c, #126a6e);
            border-radius: 15px;
            padding: 20px;
            max-width: 551px;
            width: 100%;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
            color: white;
            animation: fadeIn 1s ease-in-out;
        }

        h1 {
            font-size: 30px;
            margin-bottom: 20px;
        }

        input[type="text"] {
            width: 80%;
            padding: 12px;
            font-size: 18px;
            margin: 10px 0;
            border: 2px solid #fff;
            border-radius: 5px;
            outline: none;
            background-color: #fff;
            color: #333;
            transition: border-color 0.3s;
            text-align: center;
        }

        input[type="text"]:focus {
            border-color: #f06595;
        }

        button {
            background-color: #fff;
            color: #f06595;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            width: 80%;
        }

        button:hover {
            background-color: #f06595;
            color: white;
        }

        /* Estilo da barra de rolagem */
        .numero-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1px;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
            border-radius: 10px;
            /* Arredondamento */
            background: linear-gradient(135deg, #6bff8c, #126a6e);
        }

        /* Estilo da barra de rolagem */
        .numero-container::-webkit-scrollbar {
            width: 10px;
            /* Largura da barra */
        }

        .numero-container::-webkit-scrollbar-track {
            background: linear-gradient(135deg, #dcdcdc, #f1f1f1);
            /* Degradê no track */
            border-radius: 10px;
        }

        .numero-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #6bff8c, #126a6e);
            /* Degradê no thumb */
            border-radius: 10px;
            /* Arredondado */
        }

        .numero-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4ca472, #0f595c);
            /* Escurece no hover */
        }

        .numero-btn {
            background-color: #fff;
            color: #126a6e;
            border: 2px solid #fff;
            padding: 15px;
            font-size: 14px;
            text-align: center;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .numero-btn:hover {
            background-color: #126a6e;
            color: white;
        }

        .numero-btn:active {
            background-color: #f06595;
            color: white;
        }

        .disabled-btn {
            background-color: #dcdcdc;
            color: #888;
            cursor: not-allowed;
            position: relative;
        }

        .disabled-btn::after {
            content: "X";
            color: red;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
        }
    </style>
    <style>
        /* Estilo padrão para desktop */
        .imagem-sorteio {
            width: 153px;
        }

        /* Estilo para a versão mobile */
        @media (max-width: 768px) {
            .imagem-sorteio {
                width: 86px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div style="position: relative; text-align: center;">
            <div style="display: inline-block; position: relative; z-index: 2;">
                <h2><?php echo $cliente->sorteio->nome; ?> </h2>
                <h1>Ruan Gás</h1>
            </div>
            <img src="/public/assets/imagens/icone.png" alt="Imagem de fundo"
                style="position: absolute; top: 50%; right: 0; transform: translateY(-50%);  z-index: 1;"
                class="imagem-sorteio">
        </div>

        <!-- Informações do Sorteio -->
        <div class="info-sorteio">
            <p><strong>Número do Sorteio:</strong> <?php echo $cliente->sorteio->numero_sorteio; ?></p>
            <p><strong>Data de Início:</strong> <?php echo $cliente->sorteio->data_inicio_formatada; ?></p>
            <p><strong>Data de Término:</strong> <?php echo $cliente->sorteio->data_termino_formatada; ?></p>
        </div>
        <!-- Inputs Dinâmicos para Escolher os Números -->
        <form action="{{ route('sorteio.salvarNumeroSorte', ['id' => $cliente->id]) }}" method="POST"
            onsubmit="return validarFormulario();">
            @csrf <!-- Proteção CSRF -->
            <p>Você pode escolher até <strong>{{ $quantidadeNumeros }}</strong> números da sorte.</p>
            <!-- Inputs para escolher números da sorte -->
            <div id="inputsNumeros" style="margin: 10px 0;">
                @for ($i = 0; $i < $quantidadeNumeros; $i++)
                    <input type="text" required class="form-control numero-input" id="numeroInput{{ $i }}"
                        name="numeros_sorte[]" placeholder="Escolha o número {{ $i + 1 }}" maxlength="4">
                @endfor
            </div>

            <button type="submit" class="btn btn-primary">Salvar Escolhas</button>
        </form>

    </div>

    <div class="numero-container" id="numeroContainer">
        <?php
        for ($i = $cliente->sorteio->numero_min; $i <= $cliente->sorteio->numero_max; $i++) {
            $disabledClass = in_array($i, $numerosEscolhidos) ? 'disabled-btn' : '';
            $onclick = in_array($i, $numerosEscolhidos) ? '' : 'onclick="selecionarNumero(' . $i . ')"';
            echo '<div class="numero-btn ' . $disabledClass . '" ' . $onclick . '>' . $i . '</div>';
        }
        ?>
    </div>

    <script>
        let numerosEscolhidosCliente = []; // Armazena os números escolhidos
        const numerosEscolhidos = <?php echo json_encode($numerosEscolhidos); ?>;
        const maxNumeros = {{ $quantidadeNumeros }}; // Quantidade máxima de números permitidos
        const numeroMin = {{ $cliente->sorteio->numero_min }}; // Número mínimo permitido
        const numeroMax = {{ $cliente->sorteio->numero_max }}; // Número máximo permitido

        function validarNumero(numero, nome) {
            // Verifica se o número digitado é válido (não vazio e numérico)
            if (numero.trim() === '') return;

            // Verifica se o número está dentro do intervalo permitido
            if (numero < numeroMin || numero > numeroMax) {
                alert(`O número deve estar entre ${numeroMin} e ${numeroMax}.`);
                document.getElementById(nome).value = ""; // Limpa o campo de input
                return;
            }

            // Verifica se o número já foi escolhido
            if (numerosEscolhidos.includes(numero)) {
                alert("Número indisponível! Por favor, escolha outro número.");
                document.getElementById(nome).value = ""; // Limpa o campo de input
            }
        }

        // Função para selecionar um número
        function selecionarNumero(numero) {
            if (numerosEscolhidosCliente.length >= maxNumeros) {
                alert("Você já selecionou todos os números permitidos!");
                return;
            }

            // Verifica se o número está dentro do intervalo permitido
            if (numero < numeroMin || numero > numeroMax) {
                alert(`O número deve estar entre ${numeroMin} e ${numeroMax}.`);
                return;
            }

            if (numerosEscolhidosCliente.includes(numero)) {
                alert("Número já selecionado. Escolha outro.");
                return;
            }

            // Adiciona o número escolhido no array
            numerosEscolhidosCliente.push(numero);

            // Preenche o próximo input disponível
            const index = numerosEscolhidosCliente.length - 1;
            document.getElementById(`numeroInput${index}`).value = numero;

            // Desativa o botão do número escolhido
            const btn = document.querySelector(`.numero-btn:contains("${numero}")`);
            if (btn) btn.classList.add('disabled-btn');
        }

        // Função para validar o formulário antes do envio
        function validarFormulario() {
            // Coleta todos os valores dos campos de entrada
            const numerosInputs = document.querySelectorAll('input[name="numeros_sorte[]"]');
            let numerosDigitados = [];

            for (let input of numerosInputs) {
                let numero = input.value.trim();

                if (numero === "") continue; // Ignora campos vazios

                // Verifica se o número já foi escolhido
                if (numerosEscolhidos.includes(numero)) {
                    alert(`O número ${numero} já foi escolhido! Por favor, escolha outro número.`);
                    input.value = ""; // Limpa o campo
                    return false; // Impede o envio do formulário
                }

                // Verifica se o número já foi digitado
                if (numerosDigitados.includes(numero)) {
                    alert(`O número ${numero} foi selecionado mais de uma vez. Por favor, escolha números únicos.`);
                    return false; // Impede o envio do formulário
                }

                // Verifica se o número está dentro do intervalo permitido
                if (numero < numeroMin || numero > numeroMax) {
                    alert(`O número ${numero} deve estar entre ${numeroMin} e ${numeroMax}.`);
                    return false; // Impede o envio do formulário
                }

                numerosDigitados.push(numero);
            }

            return true; // Permite o envio do formulário se estiver tudo correto
        }
    </script>


</body>

</html>
