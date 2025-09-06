<?php

namespace DL\TicketManager\Frontend;

use DL\TicketManager\Order\Ticket;
use DL\TicketManager\Order\TicketStatus;

defined('ABSPATH') || exit;

class Validation
{
    public function __construct()
    {
        add_shortcode('ticket_validator', [$this, 'renderValidator']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('rest_api_init', [$this, 'registerEndpoint']);
    }

    /**
     * Mostramos el validador de tickets
     * @return bool|string
     * @author Daniel Lucia
     */
    public function renderValidator(): string
    {

        /**
         * @var mixed $show Opción para mostrar el validador solo a administradores
         */
        $show = get_option('dl_ticket_manager_show_validator_admin', 'no');
        if ($show === 'no' && !current_user_can('manage_options')) {
            return '';
        }
        
        ob_start();
        ?>
        <div id="ticket-validator">
            <h3>Validador de Tickets</h3>
            <video id="qr-video"></video>
            <p id="qr-result">Esperando lectura...</p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Encolamos los scripts y estilos necesarios para el validador de tickets
     * @return void
     * @author Daniel Lucia
     */
    public function enqueueAssets(): void
    {
        global $post;
        if (!is_a($post, 'WP_Post')) {
            return;
        }

        if (has_shortcode($post->post_content, 'ticket_validator')) {

            wp_enqueue_script(
                'qr-scanner',
                plugin_dir_url(DL_TICKET_MANAGER_FILE) . 'assets/js/qr-scanner.umd.min.js',
                [],
                DL_TICKET_MANAGER_VERSION,
                true
            );

            wp_enqueue_script(
                'ticket-validator-js',
                plugin_dir_url(DL_TICKET_MANAGER_FILE) . 'assets/js/ticket-validator.js',
                ['qr-scanner', 'jquery'],
                DL_TICKET_MANAGER_VERSION,
                true
            );

            wp_localize_script('ticket-validator-js', 'ticketValidator', [
                'endpoint' => esc_url(rest_url('tickets/v1/validate')),
                'nonce'    => wp_create_nonce('wp_rest')
            ]);

            wp_enqueue_style(
                'ticket-validator-css',
                plugin_dir_url(DL_TICKET_MANAGER_FILE) . 'assets/css/ticket-validator.css',
                [],
                DL_TICKET_MANAGER_VERSION
            );
        }
    }

    /**
     * Registramos el endpoint para la validación de tickets
     * @return void
     * @author Daniel Lucia
     */
    public function registerEndpoint(): void
    {
        register_rest_route('tickets/v1', '/validate', [
            'methods'  => 'POST',
            'callback' => [$this, 'validateTicket'],
            'permission_callback' => '__return_true',
        ]);
    }


    /**
     * Validamos el ticket
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     * @author Daniel Lucia
     */
    public function validateTicket(\WP_REST_Request $request): \WP_REST_Response
    {
        $code = sanitize_text_field($request->get_param('code'));
        $data = json_decode($code, true);

        $order_id = $data['order_id'] ?? null;
        $security = $data['security'] ?? null;

        if ($order_id === null) {

            $response = [
                'status'  => 'error',
                'message' => __('Invalid ticket code.', 'dl-ticket-manager')
            ];

            do_action('dl_validation_event', 'invalid_ticket_code', $data, 0, [], $response);

            return new \WP_REST_Response($response, 400);
        }

        //Obtenemos el pedido y comprobamos si existe
        $order = wc_get_order((int)$order_id);
        if (!$order) {

            $response = [
                'status'  => 'error',
                'message' => __('The order is not valid.', 'dl-ticket-manager')
            ];

            do_action('dl_validation_event', 'invalid_order', $data, 0, [], $response);

            return new \WP_REST_Response($response, 400);
        }

        //Obtenemos el pedido y comprobamos que es valido y tiene el estado procesando o completado
        if (!in_array($order->get_status(), ['processing', 'completed'])) {

            $response = [
                'status'  => 'error',
                'message' => __('The order is not in processing/completed status.', 'dl-ticket-manager')
            ];

            do_action('dl_validation_event', 'invalid_order_status', $data, $order_id, [], $response);

            return new \WP_REST_Response($response, 400);
        }

        //Comprobamos seguridad
        if ($security !== get_post_meta($order_id, 'security', true)) {

            $response = [
                'status'  => 'error',
                'message' => __('Invalid security code.', 'dl-ticket-manager')
            ];

            do_action('dl_validation_event', 'invalid_security_code', $data, $order_id, [], $response);

            return new \WP_REST_Response($response, 400);
        }

        //Comprobamos estado del ticket
        $ticket = new Ticket();
        $ticket_data = $ticket->getDataFromCode($data['code']);
        $ticket_status = $ticket_data['status'] ?? null;

        if ($ticket_status !== 'pending') {

            $response = [
                'status'  => 'error',
                'message' => __('The ticket has already been used or cancelled.', 'dl-ticket-manager')
            ];

            do_action('dl_validation_event', 'invalid_ticket_status', $data, $order_id, $ticket_data, $response);

            return new \WP_REST_Response($response, 400);
        }

        /**
         * Permite a plugins externos validar el ticket antes de continuar.
         * Si el filtro retorna un WP_Error, se devuelve como error.
         */
        $external_validation = apply_filters('dl_validate_ticket_data',  $ticket_data, $order_id, $data);

        if (is_wp_error($external_validation)) {

            $response = [
                'status'  => 'error',
                'message' => $external_validation->get_error_message(),
            ];

            do_action('dl_validation_event', 'external_ticket_validation_failed', $data, $order_id, $ticket_data, $response);

            return new \WP_REST_Response($response, 400);

        } elseif ($external_validation === false) {

            $response = [
                'status'  => 'error',
                'message' => __('Ticket validation failed by external plugin.', 'dl-ticket-manager'),
            ];

            do_action('dl_validation_event', 'external_ticket_validation_failed', $data, $order_id, $ticket_data, $response);
            
            return new \WP_REST_Response($response, 400);
            
        }

        //Confirmamos ticket
        $ticket->changeStatus($ticket_data['id'], TicketStatus::STATUS_CONFIRMED);

        $response = [
            'status'  => 'success',
            'message' => "
            <strong>{$ticket_data['event']}</strong>
            <span>{$ticket_data['name']}</span>
            <span>{$ticket_data['time']}, {$ticket_data['date']}</span>
            ",
        ];

        do_action('dl_validation_event', 'ticket_confirmed', $data, $order_id, $ticket_data);

        return new \WP_REST_Response($response);
    }
}
