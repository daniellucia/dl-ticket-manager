<?php

namespace DL\TicketManager\Order;

use Dompdf\Dompdf;
use Dompdf\Options;
use League\Plates\Engine;

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
    public function createPdf(string $code, bool $download = true)
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

        $logo_id = get_option('dl_ticket_manager_pdf_logo', '');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

        $template_folder = get_option('dl_ticket_manager_template', plugin_dir_path(__FILE__) . '../Pdf/Templates/Default.php');
        $template_folder = dirname($template_folder);

        $template_file = basename(get_option('dl_ticket_manager_template', plugin_dir_path(__FILE__) . '../Pdf/Templates/Default.php'));
        $template_file = pathinfo($template_file, PATHINFO_FILENAME);

        $template = new Engine($template_folder);

        $html = $template->render(
            $template_file,
            apply_filters('dl_ticket_manager_pdf_template_vars', [
                'LOGO' => $logo_url,
                'QR_IMAGE_SRC' => $ticket_generator->getQrImage($order_id, $code),
                'TICKET_CODE' => $ticket_data['code'] ?? '',
                'EVENT_TITLE' => $ticket_data['event'] ?? '',
                'ATTENDEE_NAME' => $ticket_data['name'] ?? '',
                'POSTER_IMAGE_SRC' => $ticket_data['thumbnail_url'] ?? '',
                'EVENT_DESCRIPTION' => $ticket_data['description'] ?? '',
                'EVENT_DATE' => $this->format_date($ticket_data['date'] ?? ''),
                'EVENT_TIME' => $ticket_data['time'] ?? '',
                'VENUE_ADDRESS' => $ticket_data['address'] ?? '',
                'VENUE_CITY' => $ticket_data['city'] ?? '',
                'EVENT_STATE' => $ticket_data['state'] ?? '',
                'VENUE_NAME' => $ticket_data['venue'] ?? '',
                'ORDER_NUMBER' => $ticket_data['order_id'] ?? '',
                'CONDITIONS_TEXT' => wpautop(get_option('dl_ticket_manager_conditions_text', '')),
                'LEGAL_TEXT' => wpautop(get_option('dl_ticket_manager_legal_text', '')),
                'ISSUER_NAME' => get_option('dl_ticket_manager_issuer_name', ''),
                'ISSUER_WEBSITE' => get_option('dl_ticket_manager_issuer_website', ''),
                'SUPPORT_EMAIL' => get_option('dl_ticket_manager_support_email', ''),
            ])
        );
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf = apply_filters('dl_ticket_manager_dompdf', $dompdf);

        $dompdf->render();

        if (!$download) {

            $pdf_path = plugin_dir_path(DL_TICKET_MANAGER_FILE) . "temp/{$code}.pdf";
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
            }

            file_put_contents($pdf_path, $dompdf->output());
            return $pdf_path;
        } else {

            $dompdf->stream("{$code}.pdf", ["Attachment" => true]);
        }

        return null;
    }

    /**
     * Formatea una fecha en formato Y-m-d a un formato legible.
     * @param string $string_date
     * @return string
     * @author Daniel Lucia
     */
    private function format_date(string $string_date): string
    {

        if (trim($string_date) === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $string_date);

        if (!$date) {
            return $string_date;
        }

        $months = [
            1 => __('january', 'dl-ticket-manager'),
            2 => __('february', 'dl-ticket-manager'),
            3 => __('march', 'dl-ticket-manager'),
            4 => __('april', 'dl-ticket-manager'),
            5 => __('may', 'dl-ticket-manager'),
            6 => __('june', 'dl-ticket-manager'),
            7 => __('july', 'dl-ticket-manager'),
            8 => __('august', 'dl-ticket-manager'),
            9 => __('september', 'dl-ticket-manager'),
            10 => __('october', 'dl-ticket-manager'),
            11 => __('november', 'dl-ticket-manager'),
            12 => __('december', 'dl-ticket-manager')
        ];

        $day   = $date->format('j');
        $month   = (int) $date->format('n');
        $year   = $date->format('Y');

        return sprintf(__('%s of %s of %s', 'dl-ticket-manager'), $day, $months[$month], $year);
    }

}
