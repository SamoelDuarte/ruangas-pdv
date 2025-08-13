var url = window.location.origin;
$('#table-device').DataTable({
    processing: true,
    serverSide: true,
    "ajax": {
        "url": url + "/dispositivo/getDevices",
        "type": "GET"
    },
    "columns": [
        {
            "data": "name"
        },
        {
            "data": "display_status"
        },
        {
            "data": null,
            "render": function(data, type, row) {
                return row.start_minutes + 'min ' + row.start_seconds + 'seg';
            }
        },
        {
            "data": null,
            "render": function(data, type, row) {
                return row.end_minutes + 'min ' + row.end_seconds + 'seg';
            }
        },
        {
            "data": "status"
        }
    ],
    'columnDefs': [
        {
            targets: [2, 3, 4],
            className: 'dt-body-center'
        }
    ],
    'rowCallback': function (row, data, index) {
        let btn = 'success';
        if (data['display_status'] == "Desconectado") {
            btn = "danger";
        }
        $('td:eq(1)', row).html('<button class="btn btn-' + btn + '">' + data['display_status'] + '</button>');
        $('td:eq(4)', row).html(
            '<div class="btn-group">' +
            '<a href="#" class="btn btn-sm btn-info edit" data-bs-toggle="modal" data-bs-target="#modalEdit" onclick="editDevice(' + data["id"] + 
            ')"><i class="fas fa-edit"></i></a> ' +
            '<a href="#" class="btn btn-sm btn-danger delete" data-bs-toggle="modal" data-bs-target="#modalDelete" onclick="configModalDelete(' + 
            data["id"] + ')"><i class="far fa-trash-alt"></i></a>' +
            '</div>'
        );


    },
});

function configModalDelete(id) {
    $('#id_device').val(id);
}

function editDevice(id) {
    // Buscar dados do dispositivo
    $.ajax({
        url: "/dispositivo/" + id + "/get",
        type: "GET",
        success: function(data) {
            $('#edit_device_id').val(data.id);
            $('#edit_device_name').val(data.name);
            $('#edit_start_minutes').val(data.start_minutes);
            $('#edit_start_seconds').val(data.start_seconds);
            $('#edit_end_minutes').val(data.end_minutes);
            $('#edit_end_seconds').val(data.end_seconds);
            $('#modalEdit').modal('show');
        }
    });
}

// Função para validar os intervalos de tempo na edição
function validateEditTimeIntervals() {
    const startMinutes = parseInt($('#edit_start_minutes').val()) || 0;
    const startSeconds = parseInt($('#edit_start_seconds').val()) || 0;
    const endMinutes = parseInt($('#edit_end_minutes').val()) || 0;
    const endSeconds = parseInt($('#edit_end_seconds').val()) || 0;

    const startTotal = (startMinutes * 60) + startSeconds;
    const endTotal = (endMinutes * 60) + endSeconds;

    return {
        isValid: startTotal < endTotal,
        startTotal,
        endTotal
    };
}

// Validar campos do modal de edição
$('#modalEdit input').on('input', function() {
    const timeValidation = validateEditTimeIntervals();
    if (!timeValidation.isValid) {
        $('#edit_end_minutes').get(0).setCustomValidity('O intervalo final deve ser maior que o inicial');
        $('#edit_end_seconds').get(0).setCustomValidity('O intervalo final deve ser maior que o inicial');
    } else {
        $('#edit_end_minutes').get(0).setCustomValidity('');
        $('#edit_end_seconds').get(0).setCustomValidity('');
    }
});

// Salvar edição
$('#btnSaveEdit').click(function() {
    const timeValidation = validateEditTimeIntervals();
    if (!timeValidation.isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: 'O intervalo final deve ser maior que o inicial'
        });
        return;
    }

    const data = {
        id: $('#edit_device_id').val(),
        name: $('#edit_device_name').val(),
        start_minutes: $('#edit_start_minutes').val(),
        start_seconds: $('#edit_start_seconds').val(),
        end_minutes: $('#edit_end_minutes').val(),
        end_seconds: $('#edit_end_seconds').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    $.ajax({
        url: "/dispositivo/update",
        type: "POST",
        data: data,
        success: function(response) {
            $('#modalEdit').modal('hide');
            $('#table-device').DataTable().ajax.reload();
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: 'Dispositivo atualizado com sucesso'
            });
        },
        error: function(response) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response.responseJSON.message || 'Erro ao atualizar dispositivo'
            });
        }
    });
});