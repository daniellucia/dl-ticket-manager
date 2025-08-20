jQuery(document).ready(function ($) {

    if ($("#qr-video").length) {

        const qrScanner = new QrScanner(
            document.getElementById("qr-video"),
            result => onScanSuccess(result),
            {
                returnDetailedScanResult: true
            }
        );

        qrScanner.start().catch(e => {
            $("#qr-result").text("No se pudo acceder a la cámara: " + e.message);
        });

        function onScanSuccess(result) {

            qrScanner.stop();
            //$("#qr-result").text("Código leído: " + result.data);
            $("#qr-result").text("Comprobando...");

            $.post(ticketValidator.endpoint, {
                code: result.data,
                _wpnonce: ticketValidator.nonce
            }).done(function (res) {
                if (res.status === 'success') {
                    $("#qr-result").addClass('success');
                } else {
                    $("#qr-result").addClass('failed');
                }

                $("#qr-result").html(res.message);

            }).fail(function (xhr) {

                $("#qr-result").removeClass('failed');
                $("#qr-result").text(xhr.responseJSON.message);
            });
        }

        $("#qr-result").after('<button id="restart-scan" type="button">Escanear otro código</button>');
        $("#restart-scan").on('click', function () {
            $("#qr-result").text('Esperando lectura…');
            qrScanner.start();
        });
    }
});
