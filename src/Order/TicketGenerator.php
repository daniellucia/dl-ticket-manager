<?php

namespace DL\TicketManager\Order;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use chillerlan\QRCode\QRCode;

class TicketGenerator
{
    private $ticket;

    public function register(): void
    {
        add_action('woocommerce_order_status_processing', [$this, 'generateTickets']);
        add_action('woocommerce_order_status_completed', [$this, 'generateTickets']);
        add_action('woocommerce_order_status_cancelled', [$this, 'cancelTickets']);
        add_action('add_meta_boxes', [$this, 'addTicketNamesMetabox']);
        add_action('woocommerce_new_order', [$this, 'addOrderSecurityField']);
        add_action('before_delete_post', [$this, 'deleteTicketsWithOrder']);

        $this->ticket = new Ticket();
    }

    /**
     * Borra los tickets asociados cuando se elimina un pedido
     * @param mixed $order_id
     * @return void
     * @author Daniel Lúcia
     */
    public function deleteTicketsWithOrder($order_id)
    {
        $post = get_post($order_id);

        if ($post && $post->post_type === 'shop_order') {
            $tickets = $this->ticket->getFromOrderId($order_id);
            if (!empty($tickets)) {
                foreach ($tickets as $ticket) {
                    wp_delete_post($ticket['id'], false);
                }
            }
        }

    }

    /**
     * Método para agregar el metabox de nombres de tickets
     * @return void
     * @author Daniel Lúcia
     */
    public function addTicketNamesMetabox(): void
    {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'dl_ticket_names',
            __('Asistentes', 'dl-ticket-manager'),
            [$this, 'renderTicketNamesMetabox'],
            $screen,
            'normal'
        );
    }

    /**
     * Método para renderizar el metabox de nombres de tickets
     * @param mixed $order
     * @return void
     * @author Daniel Lúcia
     */
    public function renderTicketNamesMetabox($order): void
    {
        if (!$order) {
            echo __('No order data.', 'dl-ticket-manager');
            return;
        }

        $codes = $this->ticket->getFromOrderId($order->get_id());

        $security = get_post_meta($order->get_id(), 'security', true);
        echo '<p>' . __('Security code', 'dl-ticket-manager') . ': ' . esc_html($security) . '</p>';

        if (!empty($codes)) {

            echo '<h3>' . __('Valid attendees:', 'dl-ticket-manager') . '</h3>';
            echo '<ul>';
            $pdf = new TicketPdfGenerator;
            foreach ($codes as $code) {


                echo '<li style="overflow:hidden;">' .
                    '<img src="' . $this->getQrImage($order->get_id(), $code['code']) . '" style="max-width:60px; height:auto; margin: 0 20px 0 0; float:left;">' .
                    '<strong>' . __('Name', 'dl-ticket-manager') . '</strong>: ' . esc_html($code['name']) . '<br>' .
                    '<strong>' . __('Code', 'dl-ticket-manager') . '</strong>: ' . esc_html($code['code']) . '<br>' .
                    '<a href="' . esc_url($pdf->url($code['code'])) . '">' . __('Download ticket', 'dl-ticket-manager') . '</a>' .
                    '</li>';
            }
            echo '</ul>';
        }
    }

    public function getDownloadUrl($code) {}
    /**
     * Método que se dispara cuando se cancela un pedido.
     * Con esto, marcamos los tickets como cancelados.
     * @param int $order_id
     * @return void
     * @author Daniel Lúcia
     */
    public function cancelTickets(int $order_id): void
    {
        $tickets = $this->ticket->getFromOrderId($order_id);
        if (empty($tickets)) {
            return;
        }

        foreach ($tickets as $ticket) {
            $this->ticket->changeStatus($ticket['id'], TicketStatus::STATUS_CANCELLED);
        }
    }

    /**
     * Método para generar tickets para un pedido
     * @param int $order_id
     * @return void
     * @author Daniel Lúcia
     */
    public function generateTickets(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if ($this->eventHasTickets($order_id)) {

            $tickets = $this->ticket->getFromOrderId($order_id);

            if (empty($tickets)) {
                return;
            }

            foreach ($tickets as $ticket) {
                $this->ticket->changeStatus($ticket['id'], TicketStatus::STATUS_PENDING);
            }


            return;
        }

        foreach ($order->get_items() as $item_id => $item) {

            $product = $item->get_product();
            $product_name = $product->get_name();

            if (!$product || $product->get_type() !== 'ticket') {
                continue;
            }

            $names = $item->get_meta('ticket_names', true);
            if (!$names || !is_array($names)) {
                continue;
            }

            $ids = $item->get_meta('ticket_ids', true);

            foreach ($names as $index => $name) {
                $code = (new Code())->generate(8, $product->get_id());

                $this->ticket->create([
                    'order_id' => $order_id,
                    'product_id' => $product->get_id(),
                    'code'     => $code,
                    'name'     => $name,
                    'event'   => $product_name,
                    'status'  => (new TicketStatus())->getDefaultStatus(),
                    'identifier' => isset($ids[$index]) ? $ids[$index] : '',
                ]);
            }
        }
    }

    /**
     * Método para verificar si un evento tiene tickets
     * @param int $order_id
     * @return bool
     * @author Daniel Lúcia
     */
    private function eventHasTickets(int $order_id)
    {

        $query = new \WP_Query([
            'post_type'  => 'dl-ticket',
            'meta_query' => [
                [
                    'key'   => 'order_id',
                    'value' => $order_id,
                ],
            ],
        ]);

        return $query->have_posts();
    }

    /**
     * Método para agregar el campo de seguridad a un pedido
     * @param mixed $order_id
     * @return void
     * @author Daniel Lúcia
     */
    public function addOrderSecurityField($order_id)
    {
        $security = get_post_meta($order_id, 'security', true);

        if ($security) {
            return;
        }

        $security = wp_generate_uuid4();
        update_post_meta($order_id, 'security', $security);
    }

    /**
     * Método para obtener la imagen QR de un ticket
     * @param mixed $order_id
     * @param mixed $code
     * @author Daniel Lúcia
     */
    public function getQrImage(int $order_id, string $code)
    {

        $data = json_encode(
            apply_filters(
                'dl_ticket_manager_qr_data',
                [
                    'order_id' => $order_id,
                    'security' => get_post_meta($order_id, 'security', true),
                    'code' => $code,
                ]
            )
        );
        return (new QRCode)->render($data);
    }

    
    /**
     * Limpia la carpeta temporal de archivos PDF.
     * @return void
     * @author Daniel Lucia
     */
    public function maybeCleanTempFolder()
    {
        // Solo ejecuta una vez al día
        if (get_transient('dl_ticket_manager_temp_cleaned')) {
            return;
        }

        $temp_dir = plugin_dir_path(DL_TICKET_MANAGER_FILE) . 'temp/';
        if (is_dir($temp_dir)) {
            foreach (glob($temp_dir . '*') as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        // Marca que ya se ha limpiado hoy
        set_transient('dl_ticket_manager_temp_cleaned', true, DAY_IN_SECONDS);
    }
}
