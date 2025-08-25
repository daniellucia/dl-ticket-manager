<?php

/**
 * Plugin Name: Ticket Manager for WooCommerce
 * Description: Plugin para venta de tickets con WooCommerce.
 * Version: 0.0.8
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

use DL\TicketManager\Plugin;

define('DL_TICKET_MANAGER_VERSION', '0.0.8');
define('DL_TICKET_MANAGER_FILE', __FILE__);

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $plugin = new Plugin();
    $plugin->init();
});
