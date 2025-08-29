<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= __('Ticket', 'dl-ticket-manager') ?> - <?= $this->e($EVENT_TITLE) ?></title>
    <style>
        @page {
            margin: 16mm;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .border {
            border: 1px solid #222;
        }

        .btop {
            border-top: 1px solid #222;
        }

        .bbtm {
            border-bottom: 1px solid #222;
        }

        .pad {
            padding: 8px;
        }

        .small {
            font-size: 9px;
            line-height: 1.25;
        }

        .muted {
            color: #555;
        }

        .title {
            font-size: 30px;
            font-weight: 700;
            letter-spacing: .3px;
            margin: 0 0 2px 0;
        }

        .subtitle {
            font-size: 17px;
            letter-spacing: .3px;
        }

        .label {
            font-size: 12px;
            color: #666;
            padding: 3px 0;
        }

        .val {
            font-size: 12px;
            font-weight: 600;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .w-100 {
            width: 100%;
        }

        .w-60 {
            width: 60%;
        }

        .w-50 {
            width: 50%;
        }

        .w-40 {
            width: 40%;
        }

        .qr {
            width: 200px;
            height: 200px;
            object-fit: contain;
        }

        .poster {
            width: 100%;
            height: auto;
        }

        /*.code {
            font-family: "Courier New", monospace;
            font-size: 12px;
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
        }*/

        .divider {
            height: 8px;
        }
    </style>
</head>

<body>

    <table class="w-100" cellspacing="0" cellpadding="0">
        <tr>
            <td class="pad">
                <div class="title"><?= $this->e($EVENT_TITLE) ?></div>
                <div class="subtitle"><?= $this->e($EVENT_DATE) ?></div>
                <div class="subtitle"><?= $this->e($ATTENDEE_NAME) ?></div>
            </td>
            <td style="text-align: right;">
                <img src="<?= $LOGO ?>" style="max-width:200px;max-height:110px;margin: 0;" />
            </td>
        </tr>

        <tr>
            <td class="bbtm" style="vertical-align: top; padding-top: 10px;" colspan="2">
                <img class="poster" src="<?= $this->e($TICKET_IMAGE) ?>" alt="<?= $this->e($EVENT_TITLE) ?>">
                <div class="divider"></div>
            </td>
        </tr>
        <tr>
            <td class="pad bbtm w-50" style="vertical-align: top;">
                <table class="w-100" cellspacing="0" cellpadding="0">

                    <tr>
                        <td>
                            <table class="w-100" cellspacing="0" cellpadding="2">
                                <tr>
                                    <td class="label"><?= __('Date', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($EVENT_DATE) ?></td>
                                </tr>
                                <tr>
                                    <td class="label"><?= __('Time', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($EVENT_TIME) ?></td>
                                </tr>
                                <tr>
                                    <td class="label"><?= __('Venue', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($VENUE_NAME) ?></td>
                                </tr>
                                <tr>
                                    <td class="label"><?= __('Address', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($VENUE_ADDRESS) ?></td>
                                </tr>
                                <tr>
                                    <td class="label"><?= __('City', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($VENUE_CITY) ?></td>
                                </tr>
                                <!--<tr>
                                    <td class="label">Zona/Asiento</td>
                                    <td class="right">{{SEAT_LABEL}}</td>
                                </tr>-->
                                <tr>
                                    <td class="label"><?= __('Ticket Holder', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($ATTENDEE_NAME) ?></td>
                                </tr>
                                <tr>
                                    <td class="label"><?= __('Code', 'dl-ticket-manager') ?></td>
                                    <td class="right"><span class="code"><?= $this->e($TICKET_CODE) ?></span></td>
                                </tr>
                                <tr>
                                    <td class="label"><?= __('Order', 'dl-ticket-manager') ?></td>
                                    <td class="right"><?= $this->e($ORDER_NUMBER) ?></td>
                                </tr>
                            </table>

                            <!--<table class="w-100" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="pad" style="padding-left:0;">
                                        {{EVENT_DESCRIPTION}}
                                    </td>
                                </tr>
                            </table>-->
                        </td>
                    </tr>

                </table>
            </td>

            <td class="pad bbtm" style="text-align: right;">
                <img class="qr" src="<?= $this->e($QR_IMAGE_SRC) ?>" alt="Código QR">
            </td>
        </tr>

        <?php if ($CONDITIONS_TEXT) : ?>
            <tr>
                <td class="pad" colspan="2">
                    <div class="label"><?= __('General conditions of ticket', 'dl-ticket-manager') ?></div>
                    <div class="small">
                        <?= $CONDITIONS_TEXT ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($LEGAL_TEXT) : ?>
            <tr>
                <td class="pad" colspan="2">
                    <div class="label"><?= __('Terms and conditions', 'dl-ticket-manager') ?></div>
                    <div class="small">
                        <?= $LEGAL_TEXT ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td class="pad btop small muted" colspan="2" style="text-align:center;">
                <?= __('Ticket issued by', 'dl-ticket-manager') ?> <?= $this->e($ISSUER_NAME) ?> &middot; <?= $this->e($ISSUER_WEBSITE) ?> &middot; <?= $this->e($SUPPORT_EMAIL) ?>
            </td>
        </tr>
    </table>

</body>

</html>