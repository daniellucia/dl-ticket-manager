<?php

namespace DL\TicketManager\Frontend;

use DL\TicketManager\Order\Ticket;
use DL\TicketManager\Order\TicketPdfGenerator;
use DL\TicketManager\Order\TicketStatus;

class Cpt
{
    public function __construct()
    {
        add_filter('manage_dl-ticket_posts_columns', [$this, 'addCustomColumns']);
        add_action('manage_dl-ticket_posts_custom_column', [$this, 'renderCustomColumns'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'addEventsFilterDropdown']);
        add_filter('parse_query', [$this, 'filterTicketsByEvent']);
        add_filter('post_row_actions', [$this, 'removeActions'], 10, 2);
        add_action('template_redirect', [$this, 'redirectSingular']);
        add_action('admin_head', [$this, 'customColumnStyles']);
        add_action('wp_ajax_change_ticket_status', [$this, 'ajaxChangeTicketStatus']);
        add_action('admin_footer', [$this, 'adminAjaxScript']);
        add_action('woocommerce_loop_add_to_cart_link', [$this, 'hideAddToCartForTickets'], 20, 2);
        add_action('pre_get_posts', [$this, 'filterSearchQuery']);
        add_action('pre_get_posts', function ($query) {
            if (is_admin() && $query->get('post_type') === 'dl-ticket' && !current_user_can('manage_options')) {
                $query->set('post_type', 'none');
            }
        });
        
    }

    /**
     * Quitamos el boton de añadir al carrito del listado si el producto es de tipo ticket
     * @param mixed $button
     * @param mixed $product
     * @author Daniel Lucia
     */
    public function hideAddToCartForTickets($button, $product)
    {
        if ($product->is_type('ticket')) {
            return '';
        }
        return $button;
    }

    /**
     * Agregamos estilos para las columnas del listado de tickets
     * @return void
     * @author Daniel Lucia
     */
    public function customColumnStyles()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'dl-ticket') {
            echo '<style>
                .wp-list-table th.column-order_id { width: 80px; }
                .wp-list-table th.column-code { width: 120px; }
                .wp-list-table th.column-download { width: 120px; }
                .wp-list-table th.column-status { width: 100px; }
                .wp-list-table th.column-change-status { width: 90px; }
                .wp-list-table th.column-identifier { width: 110px; }
            </style>';
        }
    }

    /**
     * Redireccionamos si se intenta entrar directamente a un ticket
     * @return void
     * @author Daniel Lucia
     */
    public function redirectSingular()
    {
        if (is_singular('dl-ticket')) {
            wp_redirect(home_url());
            exit;
        }
    }

    /**
     * Quitamos acciones del admin para los tickets
     * @param mixed $actions
     * @param mixed $post
     * @author Daniel Lucia
     */
    public function removeActions($actions, $post)
    {

        if ($post->post_type === 'dl-ticket') {
            unset($actions['edit']);
            unset($actions['inline hide-if-no-js']);
            unset($actions['view']);
        }

        return $actions;
    }

    /**
     * Registramos el Custom Post Type para los tickets
     * @return void
     * @author Daniel Lucia
     */
    public function registerCpt()
    {
        $labels = [
            'name'               => __('Tickets', 'dl-ticket-manager'),
            'singular_name'      => __('Ticket', 'dl-ticket-manager'),
            'add_new'            => __('Add new', 'dl-ticket-manager'),
            'add_new_item'       => __('Add new ticket', 'dl-ticket-manager'),
            'edit_item'          => __('Edit ticket', 'dl-ticket-manager'),
            'new_item'           => __('New ticket', 'dl-ticket-manager'),
            'view_item'          => __('View ticket', 'dl-ticket-manager'),
            'search_items'       => __('Search tickets', 'dl-ticket-manager'),
            'not_found'          => __('No tickets found', 'dl-ticket-manager'),
            'not_found_in_trash' => __('No tickets found in trash', 'dl-ticket-manager'),
            'menu_name'          => __('Tickets', 'dl-ticket-manager'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'has_archive'        => false,
            'rewrite'            => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-tickets-alt',
            'supports'           => ['title', 'editor', 'custom-fields'],
            'capabilities' => [
                'edit_post'          => 'do_not_allow',
                'delete_post'        => 'do_not_allow',
                'create_posts'       => 'do_not_allow',
            ],
            'show_in_rest'       => false
        ];

        register_post_type('dl-ticket', $args);
    }

    /**
     * Registramos la taxonomía para los tickets para que se pueda filtrar por eventos
     * @return void
     * @author Daniel Lucia
     */
    public function registerTaxonomy(): void
    {
        $labels = [
            'name'              => __('Events', 'dl-ticket-manager'),
            'singular_name'     => __('Event', 'dl-ticket-manager'),
            'search_items'      => __('Search events', 'dl-ticket-manager'),
            'all_items'         => __('All events', 'dl-ticket-manager'),
            'parent_item'       => __('Parent event', 'dl-ticket-manager'),
            'parent_item_colon' => __('Parent event:', 'dl-ticket-manager'),
            'edit_item'         => __('Edit event', 'dl-ticket-manager'),
            'update_item'       => __('Update event', 'dl-ticket-manager'),
            'add_new_item'      => __('Add new event', 'dl-ticket-manager'),
            'new_item_name'     => __('New event name', 'dl-ticket-manager'),
            'menu_name'         => __('Events', 'dl-ticket-manager'),
        ];

        register_taxonomy(
            'dl-event',
            ['dl-ticket'],
            [
                'hierarchical'      => true,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => ['slug' => 'evento'],
                'show_in_rest'      => true,
            ]
        );
    }

    /**
     * Agregamos columnas personalizadas a la lista de tickets
     * @param mixed $columns
     * @author Daniel Lucia
     */
    public function addCustomColumns($columns)
    {
        if (isset($columns['date'])) {
            $date = $columns['date'];
            unset($columns['date']);
        }

        //$columns['event'] = __('Event', 'dl-ticket-manager');
        $columns['order_id'] = __('Order', 'dl-ticket-manager');
        $columns['identifier'] = __('Identifier', 'dl-ticket-manager');
        $columns['code'] = __('Code', 'dl-ticket-manager');
        $columns['download'] = __('Download', 'dl-ticket-manager');
        $columns['status'] = __('Status', 'dl-ticket-manager');
        $columns['change-status'] = __('Change Status', 'dl-ticket-manager');

        return $columns;
    }

    /**
     * Renderizamos las columnas personalizadas en la lista de tickets
     * @param mixed $column
     * @param mixed $post_id
     * @return void
     * @author Daniel Lucia
     */
    public function renderCustomColumns($column, $post_id)
    {
        switch ($column) {
            case 'download':
                $pdf = new TicketPdfGenerator;
                echo '<a href="' . esc_url($pdf->url(esc_html(get_post_meta($post_id, 'code', true)))) . '" target="_blank">' . __('Download ticket', 'dl-ticket-manager') . '</a>';
                break;
            case 'event':
                echo '<strong>' . esc_html(get_post_meta($post_id, 'event', true)) . '</strong>';
                break;
            case 'order_id':
                $order_id = esc_html(get_post_meta($post_id, 'order_id', true));
                echo '<a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '">';
                echo $order_id;
                echo '</a>';
                break;
            case 'identifier':
                echo '<pre style="margin: 0;">';
                $identifier = esc_html(get_post_meta($post_id, 'identifier', true));
                if (!$identifier) {
                    $identifier = 'n/a';
                }
                echo $identifier;
                echo '</pre>';
                break;
            case 'code':
                echo '<pre style="margin: 0;">';
                echo esc_html(get_post_meta($post_id, 'code', true));
                echo '</pre>';
                break;
            case 'status':

                $status = esc_html(get_post_meta($post_id, 'status', true));
                echo (new TicketStatus())->getLabel($status);

                break;
            case 'change-status':
                $status = esc_html(get_post_meta($post_id, 'status', true));
                $nonce = wp_create_nonce('dl_ticket_manager_status_nonce');
                echo '<input type="hidden" class="dl-ticket-nonce" value="' . esc_attr($nonce) . '">';
                echo '<select class="dl-change-status" data-ticket-id="' . esc_attr($post_id) . '" style="font-size: 11px; width: 100%;">';
                foreach ((new TicketStatus())->getAllStatuses() as $value => $label) {
                    echo '<option value="' . esc_attr($label) . '"' . selected($label, $status, false) . '>' . ucfirst(esc_html($label)) . '</option>';
                }
                echo '</select>';
                break;
        }
    }

    /**
     * Agregamos un filtro por eventos en la lista de tickets
     * @return void
     * @author Daniel Lucia
     */
    public function addEventsFilterDropdown(): void
    {
        global $typenow;

        if ($typenow === 'dl-ticket') {
            $taxonomy  = 'dl-event';
            $selected  = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
            $infoTaxonomy = get_taxonomy($taxonomy);

            wp_dropdown_categories([
                'show_option_all' => sprintf(__('Todos los %s', 'dl-ticket-manager'), $infoTaxonomy->label),
                'taxonomy'        => $taxonomy,
                'name'            => $taxonomy,
                'orderby'         => 'name',
                'selected'        => $selected,
                'hierarchical'    => true,
                'depth'           => 0,
                'show_count'      => true,
                'hide_empty'      => false,
            ]);
        }
    }

    /**
     * Filtramos los tickets por evento
     * @param mixed $query
     * @return void
     * @author Daniel Lucia
     */
    public function filterTicketsByEvent($query)
    {
        global $pagenow;
        $taxonomy = 'dl-event';
        $q_vars   = &$query->query_vars;

        if (
            $pagenow === 'edit.php'
            && isset($q_vars['post_type'])
            && $q_vars['post_type'] === 'dl-ticket'
            && isset($_GET[$taxonomy])
            && is_numeric($_GET[$taxonomy])
            && (int) $_GET[$taxonomy] !== 0
        ) {
            $term = get_term_by('id', (int) $_GET[$taxonomy], $taxonomy);
            if ($term) {
                $q_vars[$taxonomy] = $term->slug;
            }
        }
    }

    /**
     * Cambiamos el estado de un ticket mediante AJAX
     * @return void
     * @author Daniel Lucia
     */
    public function ajaxChangeTicketStatus()
    {
        check_ajax_referer('dl_ticket_manager_status_nonce', 'nonce');

        if (
            isset($_POST['ticket_id']) &&
            isset($_POST['status']) &&
            current_user_can('edit_posts')
        ) {
            $ticket_id = intval($_POST['ticket_id']);
            $new_status = sanitize_text_field($_POST['status']);

            $ticket = new Ticket();
            $ticket->changeStatus($ticket_id, $new_status);

            wp_send_json_success([
                'message' => __('Status updated', 'dl-ticket-manager'),
                'label' => (new TicketStatus())->getLabel($new_status)
            ]);
        }

        wp_send_json_error(['message' => __('Error updating status', 'dl-ticket-manager')]);
    }

    /**
     * Agregamos el script AJAX para cambiar el estado del ticket
     * @return void
     * @author Daniel Lucia
     */
    public function adminAjaxScript()
    {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'dl-ticket') {
        ?>
            <script>
                jQuery(document).ready(function($) {
                    $('.dl-change-status').on('change', function() {
                        var select = $(this);
                        var ticket_id = select.data('ticket-id');
                        var status = select.val();
                        var nonce = select.closest('tr').find('.dl-ticket-nonce').val();

                        $.post(ajaxurl, {
                            action: 'change_ticket_status',
                            ticket_id: ticket_id,
                            status: status,
                            nonce: nonce
                        }, function(response) {

                            console.log(response)
                            if (response.success) {
                                select.parents('tr').find('.column-status').html(response.data.label);
                            } else {
                                alert(response.data.message);
                            }

                        });
                    });
                });
            </script>
        <?php
        }
    }

    /**
     * Filtra la consulta de búsqueda en el listado
     * @param mixed $query
     * @return void
     * @author Daniel Lucia
     */
    public function filterSearchQuery($query)
    {
        if (
            is_admin() &&
            $query->is_main_query() &&
            $query->get('post_type') === 'dl-ticket' &&
            !empty($query->get('s'))
        ) {
            $search = $query->get('s');
            $meta_query = [
                'relation' => 'OR',
                [
                    'key'     => 'code',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'identifier',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
                [
                    'key'     => 'order_id',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];
            $query->set('meta_query', $meta_query);
            $query->set('s', ''); // Evita que WP busque por título
        }
    }
}
