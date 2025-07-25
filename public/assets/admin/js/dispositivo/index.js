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
            "data": "status"
        }
    ],
    'columnDefs': [
        {
            targets: [2],
            className: 'dt-body-center'
        }
    ],
    'rowCallback': function (row, data, index) {
        let btn = 'success';
        if (data['display_status'] == "Desconectado") {
            btn = "danger";
        }
        $('td:eq(1)', row).html('<button class="btn btn-' + btn + '">' + data['display_status'] + '</button>');
        $('td:eq(2)', row).html(
            '<a href="#" class="btn btn-sm btn-danger delete" data-bs-toggle="modal" data-bs-target="#modalDelete" onclick="configModalDelete(' + data["id"] + ')"><i class="far fa-trash-alt"></i></a>'
        );


    },
});

function configModalDelete(id) {
    $('#id_device').val(id);
}