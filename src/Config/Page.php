<?php

namespace DL\TicketManager\Config;

class Page
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Agregamos la página de configuración del plugin
     * @return void
     * @author Daniel Lúcia
     */
    public function addSettingsPage(): void
    {
        add_options_page(
            __('Ticket Manager Configuración', 'dl-ticket-manager'),
            __('Ticket Manager', 'dl-ticket-manager'),
            'manage_options',
            'dl-ticket-manager-settings',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Registra los ajustes del plugin
     * @return void
     * @author Daniel Lucia
     */
    public function registerSettings(): void
    {
        register_setting('dl_ticket_manager_settings', 'dl_ticket_manager_legal_text');
        register_setting('dl_ticket_manager_settings', 'dl_ticket_manager_conditions_text');
        register_setting('dl_ticket_manager_settings', 'dl_ticket_manager_template');
        register_setting('dl_ticket_manager_settings', 'dl_ticket_manager_issuer_name');
        register_setting('dl_ticket_manager_settings', 'dl_ticket_manager_issuer_website');
        register_setting('dl_ticket_manager_settings', 'dl_ticket_manager_support_email');

        add_settings_section(
            'dl_ticket_manager_section',
            __('General settings', 'dl-ticket-manager'),
            function () {
                echo __('Configure the legal texts and conditions of the plugin.', 'dl-ticket-manager');
            },
            'dl-ticket-manager-settings'
        );

        add_settings_field(
            'dl_ticket_manager_legal_text',
            __('Legal text', 'dl-ticket-manager'),
            [$this, 'renderLegalTextField'],
            'dl-ticket-manager-settings',
            'dl_ticket_manager_section'
        );

        add_settings_field(
            'dl_ticket_manager_conditions_text',
            __('General conditions of entry', 'dl-ticket-manager'),
            [$this, 'renderConditionsTextField'],
            'dl-ticket-manager-settings',
            'dl_ticket_manager_section'
        );

        add_settings_field(
            'dl_ticket_manager_template',
            __('Ticket template', 'dl-ticket-manager'),
            [$this, 'renderTemplateSelectField'],
            'dl-ticket-manager-settings',
            'dl_ticket_manager_section'
        );

        add_settings_field(
            'dl_ticket_manager_issuer_name',
            __('Issuer name', 'dl-ticket-manager'),
            [$this, 'renderIssuerNameField'],
            'dl-ticket-manager-settings',
            'dl_ticket_manager_section'
        );

        add_settings_field(
            'dl_ticket_manager_issuer_website',
            __('Issuer website', 'dl-ticket-manager'),
            [$this, 'renderIssuerWebsiteField'],
            'dl-ticket-manager-settings',
            'dl_ticket_manager_section'
        );

        add_settings_field(
            'dl_ticket_manager_support_email',
            __('Issuer email', 'dl-ticket-manager'),
            [$this, 'renderIssuerEmailField'],
            'dl-ticket-manager-settings',
            'dl_ticket_manager_section'
        );
    }

    public function renderSettingsPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ticket Manager Configuration', 'dl-ticket-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('dl_ticket_manager_settings');
                do_settings_sections('dl-ticket-manager-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Mostramos campo para texto legal
     * @return void
     * @author Daniel Lucia
     */
    public function renderLegalTextField(): void
    {
        $value = get_option('dl_ticket_manager_legal_text', '');
        echo '<textarea name="dl_ticket_manager_legal_text" rows="6" cols="60" style="width: 100%;">' . esc_textarea($value) . '</textarea>';
    }

    /**
     * Mostramos campo para condiciones generales
     * @return void
     * @author Daniel Lucia
     */
    public function renderConditionsTextField(): void
    {
        $value = get_option('dl_ticket_manager_conditions_text', '');
        echo '<textarea name="dl_ticket_manager_conditions_text" rows="6" cols="60" style="width: 100%;">' . esc_textarea($value) . '</textarea>';
    }

    /**
     * Mostramos campo para plantilla de ticket
     * @return void
     * @author Daniel Lucia
     */
    public function renderTemplateSelectField(): void
    {
        $templates = apply_filters('dl_ticket_manager_templates', [
            plugin_dir_path(__FILE__) . '../Pdf/Templates/Default.php' => __('Default template', 'dl-ticket-manager'),
        ]);

        $selected = get_option('dl_ticket_manager_template', 'default');
        echo '<select name="dl_ticket_manager_template" style="width: 100%;max-width: 100%;">';
        foreach ($templates as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($selected, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function renderIssuerNameField(): void
    {
        $value = get_option('dl_ticket_manager_issuer_name', '');
        echo '<input type="text" name="dl_ticket_manager_issuer_name" value="' . esc_attr($value) . '" style="width: 100%;" />';
    }

    public function renderIssuerWebsiteField(): void
    {
        $value = get_option('dl_ticket_manager_issuer_website', '');
        echo '<input type="text" name="dl_ticket_manager_issuer_website" value="' . esc_attr($value) . '" style="width: 100%;" />';
    }

    public function renderIssuerEmailField(): void
    {
        $value = get_option('dl_ticket_manager_support_email', '');
        echo '<input type="email" name="dl_ticket_manager_support_email" value="' . esc_attr($value) . '" style="width: 100%;" />';
    }
}
