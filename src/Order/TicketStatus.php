<?php

namespace DL\TicketManager\Order;

class TicketStatus
{
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Método para obtener la etiqueta de un estado en formato html
     * @param mixed $status
     * @return string
     * @author Daniel Lucia
     */
    public function getLabel($status)
    {
        switch ($status) {
            case self::STATUS_PENDING:
                $label = __('Pending', 'dl-ticket-manager');
                $color = '#f1c40f';
                $bg    = 'rgba(241,196,15,0.15)';
                break;
            case self::STATUS_CONFIRMED:
                $label = __('Confirmed', 'dl-ticket-manager');
                $color = '#27ae60';
                $bg    = 'rgba(39,174,96,0.15)';
                break;
            case self::STATUS_CANCELLED:
                $label = __('Cancelled', 'dl-ticket-manager');
                $color = '#e74c3c';
                $bg    = 'rgba(231,76,60,0.15)';
                break;
            default:
                $label = __('Unknown', 'dl-ticket-manager');
                $color = '#7f8c8d';
                $bg    = 'rgba(127,140,141,0.15)';
                break;
        }

        return '<span style="font-size: 12px;color:' . esc_attr($color) . ';background:' . esc_attr($bg) . ';border:1px solid ' . esc_attr($color) . ';padding:1px 8px;border-radius:4px;font-weight:normal;display:inline-block;font-family: monospace;">' . esc_html($label) . '</span>';
    }

    /**
     * Método para obtener todos los estados de los tickets
     * @return string[]
     * @author Daniel Lucia
     */
    public function getAllStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Método para obtener el estado por defecto de los tickets
     * @return string
     * @author Daniel Lucia
     */
    public function getDefaultStatus(): string
    {
        return self::STATUS_PENDING;
    }
}
