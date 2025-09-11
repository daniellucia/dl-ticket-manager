<?php

/**
 * Plugin Name: Ticket Manager for WooCommerce
 * Description: Plugin for ticket sales with WooCommerce.
 * Version: 1.0.0
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

use DL\TicketManager\Plugin;

define('DL_TICKET_MANAGER_VERSION', '1.0.0');
define('DL_TICKET_MANAGER_FILE', __FILE__);

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $plugin = new Plugin();
    $plugin->init();
});
