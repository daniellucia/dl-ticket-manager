<?php

namespace DL\TicketManager\Frontend;

defined('ABSPATH') || exit;

class Assets
{
    public function __construct()
    {
        wp_enqueue_style(
            'tickets-css',
            plugin_dir_url(DL_TICKET_MANAGER_FILE) . 'assets/css/tickets.css',
            [],
            DL_TICKET_MANAGER_VERSION
        );
    }
}
