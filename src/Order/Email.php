<?php

namespace DL\TicketManager\Order;

use DL\TicketManager\Order\TicketPdfGenerator;

defined('ABSPATH') || exit;

class Email
{

    public function __construct()
    {
        add_action('woocommerce_email_order_details', [$this, 'add_ticket_message'], 20, 4);
        //add_filter('woocommerce_email_order_address', [$this, 'maybe_remove_billing_address'], 10, 5);
        add_filter('woocommerce_email_attachments', [$this, 'attach_ticket_pdf'], 10, 3);
    }

    /**
     * Adjunta el PDF del ticket al correo de WooCommerce
     * @param array $attachments
     * @param string $email_id
     * @param WC_Order $order
     * @return array
     */
    public function attach_ticket_pdf($attachments, $email_id, $order)
    {

        if ($this->order_has_ticket($order) && ($email_id === 'customer_completed_order' || $email_id === 'customer_processing_order')) {
            if ($order && $order instanceof \WC_Order) {
                $order_id = $order->get_id();

                $tickets = (new Ticket())->getFromOrderId($order_id);
                if (!empty($tickets)) {
                    foreach ($tickets as $ticket) {
                        $code = $ticket['code'];
                        $pdf_path = (new TicketPdfGenerator())->createPdf($code, false);
                        if (is_string($pdf_path) && file_exists($pdf_path)) {
                            $attachments[] = $pdf_path;
                        }
                    }
                }
            }
        }

        return $attachments;
    }

    /**
     * Añade un mensaje al correo de confirmación si el pedido contiene tickets
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     * @param WC_Email $email
     */
    public function add_ticket_message($order, $sent_to_admin, $plain_text, $email): void
    {
        if ($this->order_has_ticket($order)) {
            if ($plain_text) {
                echo "\n" . __('You have purchased tickets for an event. You will receive your tickets with QR code attached to this email. Present them at the entrance to validate them.', 'dl-ticket-manager') . "\n";
            } else {

                $order_id = $order->get_id();
                $ticket_generator = new TicketGenerator();

                echo '<p style="margin-top:20px; font-weight:bold; color:#2d2d2d;">' .
                    __('You have purchased tickets for an event. You will receive your tickets with QR code attached to this email. Present them at the entrance to validate them.', 'dl-ticket-manager') .
                    '</p>';

                $tickets = (new Ticket())->getFromOrderId($order_id);
                if (!empty($tickets)) {
                    echo '<table style="margin-bottom: 40px; width: 100%; border-collapse: collapse;" cellpadding="0" cellspacing="0">';
                    foreach ($tickets as $ticket) {
                        $code = $ticket['code'];
                        echo '<tr>';

                            echo '
                            <td style="padding: 0; text-align: left;width: 170px;padding-right: 10px;">
                                <img src="' . $ticket_generator->getQrImage($order_id, $code). '" style="width:170px; height:auto; margin: 0;" />
                            </td>';

                            echo '<td style="padding: 0;">';

                                do_action('dl_ticket_manager_email_ticket_details_before', $ticket, $order);

                                echo '<p style="margin: 0;"><strong>' . $ticket['name'] . '</strong></p>';
                                if ($ticket['identifier']) {
                                    echo '<p style="margin: 0;"><strong>' . $ticket['identifier'] . '</strong></p>';
                                }
                                echo '<p style="margin: 0;">' . $ticket['event'] . '</p>';

                                do_action('dl_ticket_manager_email_ticket_details_after', $ticket, $order);

                            echo '</td>';
                            
                        echo '</tr>';
                    }
                    echo '</table>';
                }
                
            }
        }
    }

    /**
     * Elimina los campos de dirección de facturación si el pedido tiene tickets
     * @param mixed $fields
     * @param mixed $order
     * @param mixed $sent_to_admin
     * @param mixed $plain_text
     * @param mixed $email
     * @author Daniel Lucia
     */
    public function maybe_remove_billing_address($fields, $order, $sent_to_admin, $plain_text, $email)
    {
        if ($this->order_has_ticket($order)) {
            $fields = [];
        }
        return $fields;
    }

    /**
     * Comprueba si el pedido tiene productos de tipo ticket
     * @param WC_Order $order
     * @return bool
     */
    private function order_has_ticket($order): bool
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->is_type('ticket')) {
                return true;
            }
        }

        return false;
    }
}
