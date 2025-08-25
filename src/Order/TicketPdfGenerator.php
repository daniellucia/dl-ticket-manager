<?php

namespace DL\TicketManager\Order;

use Dompdf\Dompdf;
use Dompdf\Options;

class TicketPdfGenerator
{

    /**
     * Genera la URL de descarga del ticket PDF a partir del código.
     * @param string $code
     * @return string
     */
    public function url(string $code): string
    {
        $url = add_query_arg([
            'action' => 'download-ticket',
            'code' => urlencode($code),
            '_wpnonce' => wp_create_nonce("download-ticket-{$code}")
        ], home_url('/'));

        return $url;
    }

    /**
     * Revisa si existe el parámetro GET "code-ticket" y genera el PDF para descargar.
     * @return void
     * @author Daniel Lúcia
     */
    public function maybeDownloadTicket(): void
    {
        if (!isset($_GET['action']) || $_GET['action'] !== 'download-ticket' || !isset($_GET['code']) || empty($_GET['code'])) {
            return;
        }

        $code = sanitize_text_field($_GET['code']);
        if (!wp_verify_nonce($_GET['_wpnonce'], "download-ticket-{$code}")) {
            wp_die('Token inválido');
        }

        if (isset($_GET['code']) && !empty($_GET['code'])) {
            $this->createPdf($code);
        }
    }

    /**
     * Genera un PDF a partir del código del ticket.
     * @param string $code
     * @param bool $download
     * @return void
     * @author Daniel Lúcia
     */
    public function createPdf(string $code, bool $download = true): void
    {
        $ticket_generator = new TicketGenerator();
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options = apply_filters('dl_ticket_manager_dompdf_options', $options);

        $dompdf = new Dompdf($options);
        $ticket = new Ticket();

        $ticket_data = $ticket->getDataFromCode($code);
        $order_id = $ticket_data['order_id'] ?? 0;

        if ($order_id == 0) {
            wp_die(__('The order associated with this ticket has not been found.', 'dl-ticket-manager'));
        }

        $html = $this->renderTemplate(
            get_option('dl_ticket_manager_template', plugin_dir_path(__FILE__) . '../Pdf/Templates/Default.html'),
            [
                'QR_IMAGE_SRC' => $ticket_generator->getQrImage($order_id, $code),
                'TICKET_CODE' => $ticket_data['code'] ?? '',
                'EVENT_TITLE' => $ticket_data['event'] ?? '',
                'ATTENDEE_NAME' => $ticket_data['name'] ?? '',
                'POSTER_IMAGE_SRC' => $ticket_data['thumbnail_url'] ?? '',
                'EVENT_DESCRIPTION' => $ticket_data['description'] ?? '',
                'EVENT_DATE' => $ticket_data['date'] ?? '',
                'EVENT_TIME' => $ticket_data['time'] ?? '',
                'VENUE_ADDRESS' => $ticket_data['address'] ?? '',
                'VENUE_CITY' => $ticket_data['city'] ?? '',
                'EVENT_STATE' => $ticket_data['state'] ?? '',
                'VENUE_NAME' => $ticket_data['venue'] ?? '',
                'ORDER_NUMBER' => $ticket_data['order_id'] ?? '',
                'CONDITIONS_TEXT' => wpautop(get_option('dl_ticket_manager_conditions_text', '')),
                'LEGAL_TEXT' => wpautop(get_option('dl_ticket_manager_legal_text', '')),
            ]
        );

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("ticket-{$code}.pdf", ["Attachment" => $download]);
    }

    /**
     * Renderiza una plantilla HTML simple sustituyendo variables por su valor.
     * @param string $template_path Ruta absoluta al archivo de plantilla.
     * @param array $vars Array asociativo ['variable' => 'valor']
     * @return string
     */
    public function renderTemplate(string $template_path, array $vars): string
    {
        if (!file_exists($template_path)) {
            return '';
        }

        $html = file_get_contents($template_path);

        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        return $html;
    }
}
