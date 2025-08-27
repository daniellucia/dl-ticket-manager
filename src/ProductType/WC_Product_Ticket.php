<?php

namespace DL\TicketManager\ProductType;

class WC_Product_Ticket extends \WC_Product_Simple
{

    public function get_type(): string
    {
        return 'ticket';
    }

    public function is_sold_individually(): bool
    {
        return false;
    }
}
