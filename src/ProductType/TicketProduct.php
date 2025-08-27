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

        add_filter('woocommerce_cart_item_quantity', [$this, 'lockTicketQuantity'], 10, 3);

        add_filter( 'woocommerce_store_api_product_quantity_editable', [$this, 'disable_change_quantity_cart'], 10, 2 );

    }

    /**
     * Deshabilita la edición de la cantidad de productos en el carrito para los tickets
     * @param mixed $is_editable
     * @param mixed $cart_item
     * @author Daniel Lucia
     */
    public function disable_change_quantity_cart($is_editable, $cart_item) {

        if ( $cart_item && $cart_item->get_type() === 'ticket' ) {
            return false;
        }

        return $is_editable;

    }

    /**
     * Bloqueamos la cantidad de tickets en el carrito
     * @param mixed $product_quantity
     * @param mixed $cart_item_key
     * @param mixed $cart_item
     * @author Daniel Lucia
     */
    public function lockTicketQuantity($product_quantity, $cart_item_key, $cart_item)
    {
        $product = $cart_item['data'];

        if ($product && $product->is_type('ticket')) {
            return sprintf('%d', $cart_item['quantity']);
        }

        return $product_quantity;
    }

    /**
     * Mostramos la plantilla para el ticket
     * @return void
     * @author Daniel Lucia
     */
    public function singleProductSummary()
    {
        global $product;

        if ($product && $product->get_type() === 'ticket') {
            wc_get_template('single-product/add-to-cart/ticket.php');
        }
    }

    /**
     * Modificamos la plantilla del carrito para los tickets
     * @param mixed $template
     * @param mixed $template_name
     * @param mixed $template_path
     * @return string
     * @author Daniel Lucia
     */
    public function addCartTemplate($template, $template_name, $template_path): string
    {
        if ($template_name === 'single-product/add-to-cart/ticket.php') {
            $template = wc_locate_template('single-product/add-to-cart/simple.php');
        }
        return $template;
    }

    /**
     * Añadimos el tipo de producto "ticket"
     * @param array $types
     * @return array
     * @author Daniel Lucia
     */
    public function addType(array $types): array
    {
        $types['ticket'] = __('Ticket', 'dl-ticket-manager');
        return $types;
    }

    /**
     * Modificamos la clase del producto según su tipo
     * @param string $classname
     * @param string $product_type
     * @return string
     * @author Daniel Lucia
     */
    public function mapProductClass(string $classname, string $product_type): string
    {
        return $product_type === 'ticket' ? WC_Product_Ticket::class : $classname;
    }

    /**
     * Modificamos las pestañas de datos del producto
     * @param array $tabs
     * @return array
     * @author Daniel Lucia
     */
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

    /**
     * Mostramos el panel de datos del producto
     * @return void
     * @author Daniel Lucia
     */
    public function productDataPanelContent()
    {
        echo '<div id="event_product_data" class="panel woocommerce_options_panel">';

        do_action('dl_ticket_event_fields_before');

        echo '<div class="options_group">';
            woocommerce_wp_checkbox([
                'id' => '_is_nominative',
                'label' => __('Nominative ticket', 'dl-ticket-manager'),
                'description' => __('Does this ticket require the name of the attendees?', 'dl-ticket-manager'),
            ]);

            // Mostrar DNI
            $is_nominative = get_post_meta(get_the_ID(), '_is_nominative', true);
            $show_id_style = ($is_nominative === 'yes') ? '' : 'display:none;';
            ?>
            <div id="show-id-checkbox" style="<?php echo $show_id_style; ?>">
                <?php
                woocommerce_wp_checkbox([
                    'id' => '_is_show_id',
                    'label' => __('Request DNI', 'dl-ticket-manager'),
                    'description' => __('Check to request the attendee\'s DNI on the ticket.', 'dl-ticket-manager'),
                ]);
                ?>
            </div>
            <script>
            jQuery(document).ready(function($){
                $('#_is_nominative').on('change', function(){
                    if ($(this).is(':checked')) {
                        $('#show-id-checkbox').show();
                    } else {
                        $('#show-id-checkbox').hide();
                        $('#_is_show_id').prop('checked', false);
                    }
                }).trigger('change');
            });
            </script>
            <?php
        echo '</div>';

        echo '<div class="options_group">';

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

        echo '</div>';
        echo '<div class="options_group">';

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
            
        echo '</div>';
        echo '<div class="options_group">';

            woocommerce_wp_text_input([
                'id'  => '_end_date',
                'label'  => __('End sale date', 'dl-ticket-manager'),
                'placeholder' => 'YYYY-MM-DD',
                'desc_tip' => true,
                'description' => __('Enter the end sale date for the ticket.', 'dl-ticket-manager'),
                'type'  => 'date',
            ]);

        echo '</div>';
        echo '<div class="options_group">';

            $image_id = get_post_meta(get_the_ID(), '_ticket_image', true);
            $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
            ?>
            
            <p class="form-field" style="display: flex;gap: 10px;align-items: flex-start;">
                <label><?php esc_html_e('Image for ticket', 'dl-ticket-manager'); ?></label>
                <input type="hidden" id="dl-ticket-image" name="_ticket_image" value="<?php echo esc_attr($image_id); ?>" />
                <button type="button" class="button" id="dl-ticket-select-image"><?php esc_html_e('Select image for ticket', 'dl-ticket-manager'); ?></button>
                <button type="button" class="button" id="dl-ticket-remove-image" <?php echo $image_url ? '' : 'style="display:none;"'; ?>><?php esc_html_e('Remove image', 'dl-ticket-manager'); ?></button>
                <img id="dl-ticket-image-preview" src="<?php echo esc_url($image_url); ?>" style="max-width: 208px;max-height:80px;<?php echo $image_url ? '' : 'display:none;'; ?>" />
            </p>

            <script>
            jQuery(document).ready(function($){
                var frame;
                $('#dl-ticket-select-image').on('click', function(e){
                    e.preventDefault();
                    if (frame) {
                        frame.open();
                        return;
                    }
                    frame = wp.media({
                        title: '<?php esc_html_e('Select image for ticket', 'dl-ticket-manager'); ?>',
                        button: { text: '<?php esc_html_e('Use this image', 'dl-ticket-manager'); ?>' },
                        multiple: false
                    });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#dl-ticket-image').val(attachment.id);
                        $('#dl-ticket-image-preview').attr('src', attachment.url).show();
                        $('#dl-ticket-remove-image').show();
                    });
                    frame.open();
                });
                $('#dl-ticket-remove-image').on('click', function(e){
                    e.preventDefault();
                    $('#dl-ticket-image').val('');
                    $('#dl-ticket-image-preview').hide();
                    $(this).hide();
                });
            });
            </script>
            <?php
        echo '</div>';
        do_action('dl_ticket_event_fields_after');

        echo '</div>';
    }

    /**
     * Guardamos los campos personalizados del producto
     * @param int $post_id
     * @return void
     * @author Daniel Lucia
     */
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

        $is_show_id = isset($_POST['_is_show_id']) ? 'yes' : 'no';
        update_post_meta($post_id, '_is_show_id', $is_show_id);

        $ticket_image = $_POST['_ticket_image'] ?? '';
        update_post_meta($post_id, '_ticket_image', sanitize_text_field($ticket_image));

        do_action('dl_ticket_save_event_fields', $post_id);
    }

    /**
     * Verificamos si el ticket se puede comprar revisando la fecha de finalización
     * @param bool $purchasable
     * @param \WC_Product $product
     * @return bool
     * @author Daniel Lucia
     */
    public function isTicketPurchasable(bool $purchasable, \WC_Product $product): bool
    {
        if ($product->get_type() === 'ticket') {

            //Verificamos fecha
            $end_date = $product->get_meta('_end_date');

            if ($end_date && strtotime($end_date) < strtotime('today')) {
                $purchasable = false;
            }

            $purchasable = apply_filters('dl_ticket_purchasable', $purchasable, $product);

        }
        
        return $purchasable;
    }

    /**
     * Mostramos un mensaje si la venta ha terminado
     * @return void
     * @author Daniel Lucia
     */
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

    /**
     * Cambiamos el texto del botón "Añadir al carrito"
     * @param string $text
     * @param \WC_Product $product
     * @return string
     * @author Daniel Lucia
     */
    public function changeAddToCartText(string $text, \WC_Product $product): string
    {
        if ($product->get_type() === 'ticket' && !$product->is_purchasable()) {
            return __('Sale completed', 'dl-ticket-manager');
        }
        return $text;
    }

    /**
     * Encolamos los scripts y estilos necesarios para el admin
     * @param mixed $hook
     * @return void
     * @author Daniel Lucia
     */
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
