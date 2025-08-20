<?php

namespace DL\TicketManager\ProductType;


class TicketProduct
{

    public function register(): void
    {
        add_filter('product_type_selector', [$this, 'addType']);
        add_filter('woocommerce_product_class', [$this, 'mapProductClass'], 10, 2);

        add_filter('woocommerce_product_data_tabs', [$this, 'productDataTabs']);
        add_action('woocommerce_product_data_panels', [$this, 'productDataPanelContent']);

        add_action('woocommerce_process_product_meta_ticket', [$this, 'saveProductDataFields']);

        add_filter('woocommerce_is_purchasable', [$this, 'isTicketPurchasable'], 10, 2);
        add_action('woocommerce_single_product_summary', [$this, 'showSaleEndedMessage'], 31);
        add_filter('woocommerce_product_single_add_to_cart_text', [$this, 'changeAddToCartText'], 10, 2);

        add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 600);

        add_filter('woocommerce_product_type_selector', [$this, 'addType']);
        add_filter('woocommerce_locate_template', [$this, 'addCartTemplate'], 10, 3);
        add_filter('woocommerce_single_product_summary', [$this, 'singleProductSummary'], 31);
    }

    public function singleProductSummary()
    {
        global $product;

        if ($product && $product->get_type() === 'ticket') {
            wc_get_template('single-product/add-to-cart/ticket.php');
        }
    }

    public function addCartTemplate($template, $template_name, $template_path): string
    {
        if ($template_name === 'single-product/add-to-cart/ticket.php') {
            $template = wc_locate_template('single-product/add-to-cart/simple.php');
        }
        return $template;
    }

    public function addType(array $types): array
    {
        $types['ticket'] = __('Ticket', 'dl-ticket-manager');
        return $types;
    }

    public function mapProductClass(string $classname, string $product_type): string
    {
        return $product_type === 'ticket' ? WC_Product_Ticket::class : $classname;
    }

    public function productDataTabs(array $tabs): array
    {
        $tabs['event'] = [
            'label' => __('Event', 'dl-ticket-manager'),
            'target' => 'event_product_data', 
            'class' => ['show_if_ticket'], 
            'priority' => 21, 
        ];

        return $tabs;
    }

    public function productDataPanelContent()
    {
        echo '<div id="event_product_data" class="panel woocommerce_options_panel">';

        woocommerce_wp_checkbox([
            'id' => '_is_nominative',
            'label' => __('Nominative ticket', 'dl-ticket-manager'),
            'description' => __('Does this ticket require the name of the attendees?', 'dl-ticket-manager'),
        ]);

        woocommerce_wp_text_input([
            'id'  => '_event_date',
            'label'  => __('Event date', 'dl-ticket-manager'),
            'placeholder' => 'YYYY-MM-DD',
            'desc_tip' => true,
            'description' => __('Introduce la fecha de inicio del evento.', 'dl-ticket-manager'),
            'type'  => 'date',
        ]);

        woocommerce_wp_text_input([
            'id'  => '_event_time',
            'label'  => __('Event time', 'dl-ticket-manager'),
            'placeholder' => 'HH:MM',
            'desc_tip' => true,
            'description' => __('Enter the start time of the event.', 'dl-ticket-manager'),
            'type'  => 'time',
        ]);

        // Nuevo campo: recinto
        woocommerce_wp_text_input([
            'id'  => '_event_venue',
            'label'  => __('Event venue', 'dl-ticket-manager'),
            'placeholder' => __('Event venue', 'dl-ticket-manager'),
            'desc_tip' => true,
            'description' => __('Enter the venue where the event will take place.', 'dl-ticket-manager'),
        ]);

        woocommerce_wp_text_input([
            'id'  => '_event_address',
            'label'  => __('Address', 'dl-ticket-manager'),
            'placeholder' => __('Event address', 'dl-ticket-manager'),
            'desc_tip' => true,
            'description' => __('Enter the address where the event will take place.', 'dl-ticket-manager'),
        ]);

        woocommerce_wp_text_input([
            'id'  => '_event_city',
            'label'  => __('City', 'dl-ticket-manager'),
            'placeholder' => __('City', 'dl-ticket-manager'),
            'desc_tip' => true,
            'description' => __('Enter the city where the event will take place.', 'dl-ticket-manager'),
        ]);

        woocommerce_wp_text_input([
            'id'  => '_event_state',
            'label'  => __('State', 'dl-ticket-manager'),
            'placeholder' => __('State', 'dl-ticket-manager'),
            'desc_tip' => true,
            'description' => __('Enter the state where the event will take place.', 'dl-ticket-manager'),
        ]);

        woocommerce_wp_text_input([
            'id'  => '_end_date',
            'label'  => __('End sale date', 'dl-ticket-manager'),
            'placeholder' => 'YYYY-MM-DD',
            'desc_tip' => true,
            'description' => __('Enter the end sale date for the ticket.', 'dl-ticket-manager'),
            'type'  => 'date',
        ]);

        echo '</div>';
    }

    public function saveProductDataFields(int $post_id): void
    {
        $event_date = $_POST['_event_date'] ?? '';
        update_post_meta($post_id, '_event_date', sanitize_text_field($event_date));

        $event_time = $_POST['_event_time'] ?? '';
        update_post_meta($post_id, '_event_time', sanitize_text_field($event_time));

        // Guardar el nuevo campo recinto
        $event_venue = $_POST['_event_venue'] ?? '';
        update_post_meta($post_id, '_event_venue', sanitize_text_field($event_venue));

        $event_address = $_POST['_event_address'] ?? '';
        update_post_meta($post_id, '_event_address', sanitize_text_field($event_address));

        $event_city = $_POST['_event_city'] ?? '';
        update_post_meta($post_id, '_event_city', sanitize_text_field($event_city));

        $event_province = $_POST['_event_state'] ?? '';
        update_post_meta($post_id, '_event_state', sanitize_text_field($event_province));

        $end_date = $_POST['_end_date'] ?? '';
        update_post_meta($post_id, '_end_date', sanitize_text_field($end_date));

        $is_nominative = isset($_POST['_is_nominative']) ? 'yes' : 'no';
        update_post_meta($post_id, '_is_nominative', $is_nominative);
    }

    public function isTicketPurchasable(bool $purchasable, \WC_Product $product): bool
    {
        if ($product->get_type() === 'ticket') {
            $end_date = $product->get_meta('_end_date');

            if ($end_date && strtotime($end_date) < strtotime('today')) {
                return false;
            }
        }
        return $purchasable;
    }


    public function showSaleEndedMessage(): void
    {
        global $product;
        
        // Nos aseguramos de que es un objeto de producto válido y no se puede comprar
        if ($product instanceof \WC_Product && $product->get_type() === 'ticket' && !$product->is_purchasable()) {
            $end_date = $product->get_meta('_end_date');
            if ($end_date && strtotime($end_date) < strtotime('today')) {
                echo '<div class="woocommerce-error" role="alert">' . __('The ticket sales for this event have ended.', 'dl-ticket-manager') . '</div>';
            }
        }
    }

    public function changeAddToCartText(string $text, \WC_Product $product): string
    {
        if ($product->get_type() === 'ticket' && !$product->is_purchasable()) {
            return __('Sale completed', 'dl-ticket-manager');
        }
        return $text;
    }

    public function admin_scripts($hook): void
    {
        global $post_type;

        if ($hook == 'post.php' || $hook == 'post-new.php') {
            if ($post_type == 'product') {
                ?>
                <script type="text/javascript">
                    addEventListener("DOMContentLoaded", function() {
                        jQuery(document).ready(function($) {

                            $('select#product-type').change(function() {
                                var product_type = $(this).val();

                                setTimeout(function() {
                                    if (product_type == 'ticket') {
                                        $('.show_if_ticket').show();
                                        $('.hide_if_ticket').hide();

                                        $('.show_if_simple, .general_options').show();
                                        $('.shipping_options, .attribute_options').hide();

                                    } else {
                                        $('.show_if_ticket').hide();
                                    }
                                }, 50);

                            }).change();
                        });
                    });
                </script>
                <?php
            }
        }
    }
}
