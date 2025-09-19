<?php

namespace DL\TicketManager\Frontend;

defined('ABSPATH') || exit;

class Assets
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_style']);
    }

    /**
     * Encola los estilos necesarios para el frontend
     * @return void
     * @author Daniel Lucia
     */
    public function enqueue_style()
    {
        wp_enqueue_style(
            'tickets-css',
            plugin_dir_url(DL_TICKET_MANAGER_FILE) . 'assets/css/tickets.css',
            [],
            DL_TICKET_MANAGER_VERSION
        );
    }
}
