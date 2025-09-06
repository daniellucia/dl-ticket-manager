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
            $("#qr-result").text(ticketValidator.text_not_camera + " " + e.message);
        });

        function onScanSuccess(result) {

            qrScanner.stop();
            
            $("#qr-result").text(ticketValidator.text_checking);

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

        $("#qr-result").after('<button id="restart-scan" type="button">' + ticketValidator.button_scan_text + '</button>');
        $("#restart-scan").on('click', function () {
            $("#qr-result").text(ticketValidator.text_awaiting);
            qrScanner.start();
        });
    }
});
