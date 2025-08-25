<?php

namespace DL\TicketManager\Frontend;

class Assets
{
    public function register()
    {
        wp_enqueue_style(
            'tickets-css',
            plugin_dir_url(DL_TICKET_MANAGER_FILE) . 'assets/css/tickets.css',
            [],
            DL_TICKET_MANAGER_VERSION
        );
    }
}
