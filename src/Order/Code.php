<?php

namespace DL\TicketManager\Order;

class Code
{
    private $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Genera un código único para el ticket.
     * @return string
     */
    public function generate(int $length = 8, int $product_id): string
    {
        $characters_length = strlen($this->characters);
        $unique = false;
        $code = '';

        while (!$unique) {

            $code = $product_id;

            for ($i = 0; $i < $length; $i++) {
                $code .= $this->characters[random_int(0, $characters_length - 1)];
            }

            $query = new \WP_Query([
                'post_type'  => 'dl-ticket',
                'meta_query' => [
                    [
                        'key'   => 'code',
                        'value' => $code,
                    ]
                ],
                'fields'     => 'ids',
                'posts_per_page' => 1,
            ]);

            if ($query->found_posts === 0) {
                $unique = true;
            }
        }

        return apply_filters('dl_ticket_manager_generate_code', $code);
    }
}
