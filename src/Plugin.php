<?php

namespace DL\TicketManager;

use DL\TicketManager\ProductType\TicketProduct;
use DL\TicketManager\Frontend\FormHandler;
use DL\TicketManager\Frontend\Cpt;
use DL\TicketManager\Frontend\Validation;
use DL\TicketManager\Order\TicketGenerator;
use DL\TicketManager\Order\TicketPdfGenerator;
use DL\TicketManager\Config\Page;

class Plugin
{
    private TicketProduct $product_type;
    private FormHandler $form_handler;
    private TicketGenerator $ticket_generator;
    private Cpt $cpt;
    private TicketPdfGenerator $pdf;
    private Page $config_page;
    private Validation $validation;

    public function __construct()
    {
        $this->product_type = new TicketProduct();
        $this->form_handler = new FormHandler();
        $this->ticket_generator = new TicketGenerator();
        $this->cpt = new Cpt();
        $this->pdf = new TicketPdfGenerator();
        $this->config_page = new Page();
        $this->validation = new Validation();
    }

    public function init(): void
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'showWooCommerceNotice']);
            return;
        }

        add_action('init', [$this, 'loadComponents']);
    }

    public function showWooCommerceNotice(): void
    {
        echo '<div class="error"><p><strong>Ticket Manager</strong> requiere WooCommerce activo.</p></div>';
    }

    /**
     * Cargamos los componentes del plugin
     * @return void
     * @author Daniel Lucia
     */
    public function loadComponents(): void
    {
        $this->product_type->register();
        $this->form_handler->register();
        $this->ticket_generator->register();
        $this->cpt->registerCpt();
        $this->cpt->registerTaxonomy();
        $this->cpt->register();
        $this->pdf->maybeDownloadTicket();
        $this->config_page->register();
        $this->validation->register();
    }
}
