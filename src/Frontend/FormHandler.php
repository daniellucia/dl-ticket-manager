<?php

namespace DL\TicketManager\Frontend;

class FormHandler
{
    public function register(): void
    {
        add_action('woocommerce_after_add_to_cart_button', [$this, 'outputForm']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'addCartItemData'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'displayItemData'], 10, 2);
        add_filter('woocommerce_is_sold_individually', [$this, 'isSoldIndividually'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'addOrderLineItemMeta'], 10, 4);
    }

    public function isSoldIndividually(bool $sold_individually, $product): bool
    {
        if ($product->get_type() === 'ticket') {
            return false;
        }
        return $sold_individually;
    }

    public function outputForm(): void
    {
        global $product;

        if ($product->get_type() !== 'ticket') {
            return;
        }

        $is_nominative = get_post_meta($product->get_id(), '_is_nominative', true);
 
        echo '<div id="ticket-names-wrapper" ' . ($is_nominative ? 'style="display:block;"' : 'style="display:none;"') . '>';
        echo '<label>' . __('Name(s) for the ticket:', 'dl-ticket-manager') . '</label>';
        echo '<div class="ticket-name"><input type="text" name="ticket_names[]" required ></div>';
        echo '</div>';
        ?>

        <script>
            jQuery(function($) {
                var qtyInput = $('input.qty');
                qtyInput.on('change', function() {
                    var count = parseInt($(this).val());
                    var wrapper = $('#ticket-names-wrapper');
                    wrapper.find('.ticket-name').remove();
                    for (let i = 0; i < count; i++) {
                        wrapper.append('<div class="ticket-name"><input type="text" name="ticket_names[]" required placeholder="<?php esc_attr_e('Nombre del titular del ticket', 'dl-ticket-manager'); ?>"></div>');
                    }
                }).trigger('change');
            });
        </script>

        <?php
    }

    public function addCartItemData($cart_item_data, $product_id, $variation_id): array
    {

        if (isset($_POST['ticket_names'])) {
            $cart_item_data['ticket_names'] = array_map('sanitize_text_field', $_POST['ticket_names']);
        }

        return $cart_item_data;
    }

    public function displayItemData($item_data, $cart_item): array
    {
        if (isset($cart_item['ticket_names'])) {
            foreach ($cart_item['ticket_names'] as $index => $name) {
                $item_data[] = [
                    'key'   => __('#', 'dl-ticket-manager') . ' ' . ($index + 1),
                    'value' => $name,
                ];
            }
        }
        return $item_data;
    }

    public function addOrderLineItemMeta($item, $cart_item_key, $values, $order)
    {
        if (isset($values['ticket_names']) && is_array($values['ticket_names'])) {
            $item->add_meta_data('ticket_names', $values['ticket_names']);
        }
    }
}
