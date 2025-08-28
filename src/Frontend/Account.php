<?php

namespace DL\TicketManager\Frontend;

use DL\TicketManager\Order\Ticket;
use DL\TicketManager\Order\TicketPdfGenerator;
use League\Plates\Engine;

if (! defined('ABSPATH')) {
    exit;
}

class Account
{
    public function __construct()
    {
        // Hooks
        add_action('init', [$this, 'add_endpoint']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_link_my_account']);
        add_action('woocommerce_account_my-tickets_endpoint', [$this, 'endpoint_content']);

        // Activación / Desactivación
        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
    }

    /**
     * Registramos el endpoint "my-tickets"
     * @return void
     * @author Daniel Lucia
     */
    public function add_endpoint()
    {
        add_rewrite_endpoint('my-tickets', EP_ROOT | EP_PAGES);
    }


    /**
     * Añadimos enlace al menú de "Mi Cuenta"
     * @param mixed $items
     * @author Daniel Lucia
     */
    public function add_link_my_account($items)
    {
        // Añadimos el enlace antes de logout
        $new_items = [];
        foreach ($items as $key => $value) {
            if ($key === 'customer-logout') {
                $new_items['my-tickets'] = __('My tickets', 'dl-ticket-manager');
            }
            $new_items[$key] = $value;
        }

        // Si no existe logout,lo añadimos al final
        if (!isset($new_items['my-tickets'])) {
            $new_items['my-tickets'] = __('My tickets', 'dl-ticket-manager');
        }
        return $new_items;
    }

    /**
     * Contenido de la página "Mis Entradas"
     * @return void
     * @author Daniel Lucia
     */
    public function endpoint_content()
    {

        $tickets = (new Ticket())->getFromUserId(get_current_user_id());

        if (!empty($tickets)) {
            $pdf = new TicketPdfGenerator;

            $template_folder = plugin_dir_path(DL_TICKET_MANAGER_FILE) . 'src/Views/';
            $template = new Engine($template_folder);
            echo $template->render('Account', [
                'tickets' => $tickets,
                'pdf' => $pdf
            ]);

        } else {
            echo '<p>' . __('You have no tickets purchased.', 'dl-ticket-manager') . '</p>';
        }
    }

    /**
     * Flush rewrite rules al activar
     * @return void
     * @author Daniel Lucia
     */
    public static function activate()
    {
        add_rewrite_endpoint('mis-entradas', EP_ROOT | EP_PAGES);
        flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules al desactivar
     * @return void
     * @author Daniel Lucia
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
