const inputNome = document.getElementById('device_name');
const btnGerar = document.getElementById('createDeviceBtn');
const qrCard = document.getElementById('qrCard');
const qrExpired = document.getElementById("qr-expired");

let session = '';
let id_device = '';
let conectado = false;

// Habilita botão se nome for válido
inputNome.addEventListener('input', () => {
    const nome = inputNome.value.trim();
    btnGerar.disabled = nome.length === 0 || nome.toLowerCase() === 'zaxio';
});

btnGerar.addEventListener('click', function () {
    const nome = inputNome.value.trim();
    qrExpired.style.display = "none";
    if (nome === '' || nome.toLowerCase() === 'zaxio') return;

    // Desabilita campo e esconde botão
    inputNome.disabled = true;
    btnGerar.style.display = 'none';

    // Requisição AJAX
    $.ajax({
        url: "/dispositivo/criar",
        type: "POST",
        data: {
            nome: nome,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            session = data.session;
            id_device = data.id;
            $('#session_device').val(session);
            $('#id_device').val(id_device);

            const qrImg = document.getElementById('qrcode-img');
            qrImg.src = data.qrcode;
            qrImg.style.display = 'block';

            document.getElementById('qr-timer').style.display = 'block';
            qrCard.style.display = 'block';

            startCountdown();
            startVerificacao();
        }
    });
});

function startCountdown() {
    let countdown = 10;
    const countdownSpan = document.getElementById("countdown");
    const qrTimer = document.getElementById("qr-timer");
   
    const qrcodeImg = document.getElementById("qrcode-img");

    // Garante que o texto comece em 10
    countdownSpan.innerText = countdown;

    const interval = setInterval(() => {
        if (conectado) return clearInterval(interval);
        countdown--;
        countdownSpan.innerText = countdown;

        if (countdown <= 0) {
            clearInterval(interval);
            qrTimer.style.display = "none";
            qrcodeImg.style.display = "none";
            qrExpired.style.display = "block";
        
            if (!conectado) {
                inputNome.disabled = false;
                btnGerar.style.display = 'inline-block';
            }
        }
        
    }, 1000);
}


// Verificação status
function startVerificacao() {
    const footerQrCode = document.getElementById("footer-qr-code");

    const intervalId = setInterval(() => {
        if (conectado) return clearInterval(intervalId);

        $.ajax({
            url: "/dispositivo/getStatus",
            type: "GET",
            data: { sessionId: session },
            success: function (response) {
                let res = JSON.parse(response);
                if (res.status === 'AUTHENTICATED') {
                    conectado = true;
                    $('#qrcode-img').hide();
                    $('#qr-timer').hide();
                    $('#footer-qr-code').show();

                    // Atualiza com nome também
                    $.ajax({
                        url: "updateStatus",
                        method: "POST",
                        data: JSON.stringify({
                            id: id_device,
                            status: res.status,
                            jid: res.me.jid,
                            picture: res.me.picture,
                            nome: inputNome.value
                        }),
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: "Conectado com sucesso.",
                        showConfirmButton: false,
                        timer: 5000,
                    });
                }
            }
        });
    }, 1000);
}

// var newSession = {
//     "url": "http://localhost:3333/sessions/add",
//     "method": "POST",
//     "timeout": 0,
//     "headers": {
//         "secret": "$2a$12$VruN7Mf0FsXW2mR8WV0gTO134CQ54AmeCR.ml3wgc9guPSyKtHMgC",
//         "Content-Type": "application/json"
//     },
//     "data": JSON.stringify({
//         "sessionId": session
//     }),
// };

// $.ajax(newSession).done(function (response) {

//     qrcodeImg.src = response['qr'];
// });



// Função de verificação que será executada a cada 5 segundos
// function verificarCondicaos() {


//     var settings = {
//         "url": "http://localhost:3333/sessions/" + session + "/status",
//         "method": "GET",
//         "timeout": 0,
//         "headers": {
//             "secret": "$2a$12$VruN7Mf0FsXW2mR8WV0gTO134CQ54AmeCR.ml3wgc9guPSyKtHMgC"
//         },
//     };

//     $.ajax(settings).done(function (response) {
        
//     });
// }

// Definir o intervalo de verificação a cada 5 segundos se foi authicado


// function updateName() {
//     var name_device = $("#name_device").val();
//     var updateName = {
//         "url": "updateName",
//         "method": "POST",
//         "timeout": 0,
//         "data": JSON.stringify({
//             "name": name_device,
//             "id": id_device,

//         }),
//         "headers": {
//             "Content-Type": "application/json",
//             "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
//         }
//     };

//     $.ajax(updateName).done(function (response) {

//         const Toast = Swal.mixin({
//             toast: true,
//             position: 'top-end',
//             showConfirmButton: false,
//             timer: 5000,
//             timerProgressBar: true,
//             didOpen: (toast) => {
//                 toast.addEventListener('mouseenter', Swal.stopTimer)
//                 toast.addEventListener('mouseleave', Swal.resumeTimer)
//             }
//         })

//         Toast.fire({
//             icon: 'success',
//             title: "Nome Atualizado  com sucesso.",
//         })

//     });



// }