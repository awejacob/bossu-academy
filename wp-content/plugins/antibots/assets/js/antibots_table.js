jQuery(document).ready(function () {



    // Cria um elemento <style>
    var styleElement = jQuery('<style>');

    // Define as regras de estilo
    var customCSS = '.antibots-custom-loading-message { background: yellow; color: blue; font-size: 18px; padding:20px; height:100px !important; /* outros estilos desejados */ }';

    // Adiciona as regras de estilo ao elemento <style>
    styleElement.text(customCSS);

    // Insere o elemento <style> no <head> do documento
    jQuery('head').append(styleElement);

    //var table99 = jQuery('#dataTableBots').DataTable({ 
    var table9999 = jQuery('#dataTableBots').DataTable({
        processing: true,
        /*     //  "language": {processing: '<i style="margin-top:-40px;" class="fa fa-spinner fa-spin fa-2x fa-fw"></i>'}, */
        "language": { processing: '<span class="antibots-custom-loading-message">Please wait...</span>' },

        "serverSide": true,
        "order": [[0, "desc"]],
        "columnDefs": [
            {
                "targets": 0, // -1
                "data": null,
                "defaultContent": "<button>Whitelist</button>"
            },
            {
                "targets": 2,
                "createdCell": function (td, cellData, rowData, row, col) {
                    if (cellData == 'OK') {
                        jQuery(td).css("background-color", "#A9DFBF");
                    }
                    if (cellData == 'Denied') {
                        jQuery(td).css("background-color", "#F5B7B1 ");
                    }
                    if (cellData == 'Masked') {
                        jQuery(td).css("background-color", "#FFFF00");
                    }
                },
            }],
        "ajax": {
            "url": datatablesajax.url + '?action=antibots_get_ajax_data',
            error: function (jqXHR, textStatus, errorThrown) {
                alert("Unexpected error. Please, try again later.");
            }
        },
        dataType: "json",
        contentType: "application/json",
    });



    jQuery("#dataTableBots tbody").on('click', 'tr', function () {
        var $row = table9999.row(jQuery(this).closest('tr')); // .data();
        var rowIdx = table9999.row(jQuery(this).closest('tr')).index();
        $ip = $row.cell(rowIdx, 3).data();


        jQuery("#dialog-confirm").dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
                "Add to Whitelist": function () {
                    // console.log($ip);
                    jQuery.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            'action': 'antibots_add_whitelist',
                            'ip': $ip,
                            'nonce': antibots_ajax.nonce // Attach the nonce here
                        },
                        success: function (response) {
                            // Visual feedback for the user
                            alert(response); // Or display in a div element
                            console.log('Success:', response);
                            // Optional: reload table or remove row
                        },
                        error: function (xhr, status, error) {
                            // Handle errors
                            alert('Error: ' + xhr.responseText);
                            console.error('Error:', xhr.responseText);
                        }
                    });
                    jQuery(this).dialog("close");
                },
                Cancel: function () {
                    jQuery(this).dialog("close");
                }
            }
        });
        jQuery("#modal-body").html('Add IP: ' + $ip + ' to whitelist?');
    });
});