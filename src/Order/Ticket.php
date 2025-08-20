<?php

namespace DL\TicketManager\Order;

class Ticket
{

    /**
     * Método para crear un ticket
     * @param mixed $data
     * @return int|\WP_Error
     * @author Daniel Lucia
     */
    public function create($data)
    {

        $ticket_data = [
            'post_title'   => $data['name'],
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'dl-ticket',
            'meta_input'   => $data,
        ];

        $ticket_data = apply_filters('dl_ticket_manager_create_ticket_data', $ticket_data);
        $post_id = wp_insert_post($ticket_data);
        $event_id = $this->createEvent($data['event']);

        if ($event_id) {
            wp_set_object_terms($post_id, $event_id, 'dl-event');
        }

        do_action('dl_ticket_manager_ticket_created', [
            'ticket_id' => $post_id,
            'data'      => $data,
        ]);

        return $post_id;
    }

    /**
     * Método para cambiar el estado de un ticket
     * @param int $ticket_id
     * @param string $new_status
     * @return void
     * @author Daniel Lucia
     */
    public function changeStatus(int $ticket_id, string $new_status): void
    {
        if (!in_array($new_status, (new TicketStatus())->getAllStatuses(), true)) {
            return;
        }

        update_post_meta($ticket_id, 'status', $new_status);

        do_action('dl_ticket_manager_ticket_status_changed', [
            'ticket_id' => $ticket_id,
            'new_status' => $new_status,
        ]);
    }

    /**
     * Método para crear un evento como taxonomia
     * @param string $eventName
     * @return int
     * @author Daniel Lucia
     */
    public function createEvent(string $eventName): int
    {
        $term = term_exists($eventName, 'dl-event');

        if ($term === 0 || $term === null) {
            $newTerm = wp_insert_term($eventName, 'dl-event');

            if (is_wp_error($newTerm)) {
                return 0;
            }

            return (int) $newTerm['term_id'];
        }

        return (int) (is_array($term) ? $term['term_id'] : $term);
    }

    /**
     * Método para obtener los tickets de un pedido
     * @param int $order_id
     * @return array{code: mixed, event: mixed, id: bool|int, name: mixed, status: mixed[]}
     * @author Daniel Lucia
     */
    public function getFromOrderId(int $order_id)
    {
        $query = new \WP_Query([
            'post_type'  => 'dl-ticket',
            'meta_query' => [
                [
                    'key'   => 'order_id',
                    'value' => $order_id,
                ],
            ],
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $tickets[] = apply_filters(
                    'dl_ticket_manager_get_ticket_data',
                    [
                        'id'   => get_the_ID(),
                        'code' => get_post_meta(get_the_ID(), 'code', true),
                        'name' => get_post_meta(get_the_ID(), 'name', true),
                        'event' => get_post_meta(get_the_ID(), 'event', true),
                        'status' => get_post_meta(get_the_ID(), 'status', true),
                    ]
                );
            }
            wp_reset_postdata();
        }

        return $tickets;
    }

    public function getDataFromCode(string $code): array
    {
        $query = new \WP_Query([
            'post_type'  => 'dl-ticket',
            'meta_query' => [
                [
                    'key'   => 'code',
                    'value' => $code,
                ],
            ],
        ]);

        if (!$query->have_posts()) {
            return [];
        }

        $ticket = [];
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_post_meta(get_the_ID(), 'product_id', true);
            $product = wc_get_product($product_id);

            $ticket = apply_filters('dl_ticket_manager_get_ticket_data', [
                'id'      => get_the_ID(),
                'code'    => get_post_meta(get_the_ID(), 'code', true),
                'name'    => get_post_meta(get_the_ID(), 'name', true),
                'event'   => get_post_meta(get_the_ID(), 'event', true),
                'status'  => get_post_meta(get_the_ID(), 'status', true),
                'order_id' => get_post_meta(get_the_ID(), 'order_id', true),
                'product_id' => $product_id,
                'thumbnail_url' => $product ? wp_get_attachment_url($product->get_image_id()) : '',
                'description' => $product ? wpautop($product->get_description()) : '',
                'date' => get_post_meta($product_id, '_event_date', true),
                'time' => get_post_meta($product_id, '_event_time', true),
                'address' => get_post_meta($product_id, '_event_address', true),
                'city' => get_post_meta($product_id, '_event_city', true),
                'state' => get_post_meta($product_id, '_event_state', true),
                'venue' => get_post_meta($product_id, '_event_venue', true),
            ]);
        }
        
        wp_reset_postdata();

        return $ticket;
    }
}
