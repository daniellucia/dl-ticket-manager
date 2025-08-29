<?php defined('ABSPATH') || exit; ?>
<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
    <thead>
        <tr>
            <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?= __('Event', 'dl-ticket-manager') ?></span></th>
            <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr"><?= __('Date', 'dl-ticket-manager') ?></span></th>
            <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr"><?= __('Name', 'dl-ticket-manager') ?></span></th>
            <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr"><?= __('Identifier', 'dl-ticket-manager') ?></span></th>
            <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span class="nobr"><?= __('Download ticket', 'dl-ticket-manager') ?></span></th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($tickets as $ticket): ?>
            <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
                <th class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" scope="row">
                    <?= $this->e($ticket['event']) ?>
                </th>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date">
                    <?= $this->e(date_i18n(get_option('date_format'), timestamp_with_offset: strtotime($ticket['date']))) ?>
                </td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status" dta-title="Estado">
                    <?= $this->e($ticket['name']) ?>
                </td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total">
                    <?= $this->e($ticket['identifier']) ?>
                </td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
                    <a href="<?= $pdf->url($ticket['code']); ?>" class="woocommerce-button wp-element-button button view"><?= __('Download ticket', 'dl-ticket-manager') ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>