<?php

/**
 * Panel de administración del plugin
 */

add_action('admin_menu', 'wcpr_admin_menu');

function wcpr_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'Payment Recovery',
        'Payment Recovery',
        'manage_options',
        'wcpr-settings',
        'wcpr_settings_page'
    );
}

function wcpr_settings_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method="post">
            <?php settings_fields('wcpr_settings_group'); ?>
            <?php do_settings_sections('wcpr_settings'); ?>
            <?php submit_button('Guardar configuración'); ?>
        </form>
    </div>
<?php
}

// Registrar settings
add_action('admin_init', 'wcpr_register_settings');

function wcpr_register_settings()
{
    register_setting('wcpr_settings_group', 'wcpr_email_1_enabled');
    register_setting('wcpr_settings_group', 'wcpr_email_1_delay');
    register_setting('wcpr_settings_group', 'wcpr_email_2_enabled');
    register_setting('wcpr_settings_group', 'wcpr_email_2_delay');
    register_setting('wcpr_settings_group', 'wcpr_email_3_enabled');
    register_setting('wcpr_settings_group', 'wcpr_email_3_delay');
    register_setting('wcpr_settings_group', 'wcpr_cancel_enabled');
    register_setting('wcpr_settings_group', 'wcpr_cancel_delay');

    // Email 1 - 30 minutos
    add_settings_section(
        'wcpr_email_1',
        'Primer correo',
        'wcpr_email_1_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_email_1_enabled',
        'Habilitado',
        'wcpr_email_1_enabled_callback',
        'wcpr_settings',
        'wcpr_email_1'
    );

    add_settings_field(
        'wcpr_email_1_delay',
        'Retraso (minutos)',
        'wcpr_email_1_delay_callback',
        'wcpr_settings',
        'wcpr_email_1'
    );

    // Email 2 - 6 horas
    add_settings_section(
        'wcpr_email_2',
        'Segundo correo',
        'wcpr_email_2_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_email_2_enabled',
        'Habilitado',
        'wcpr_email_2_enabled_callback',
        'wcpr_settings',
        'wcpr_email_2'
    );

    add_settings_field(
        'wcpr_email_2_delay',
        'Retraso (minutos)',
        'wcpr_email_2_delay_callback',
        'wcpr_settings',
        'wcpr_email_2'
    );

    // Email 3 - 24 horas
    add_settings_section(
        'wcpr_email_3',
        'Tercer correo',
        'wcpr_email_3_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_email_3_enabled',
        'Habilitado',
        'wcpr_email_3_enabled_callback',
        'wcpr_settings',
        'wcpr_email_3'
    );

    add_settings_field(
        'wcpr_email_3_delay',
        'Retraso (minutos)',
        'wcpr_email_3_delay_callback',
        'wcpr_settings',
        'wcpr_email_3'
    );

    // Cancelación automática
    add_settings_section(
        'wcpr_cancel',
        'Cancelación automática',
        'wcpr_cancel_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_cancel_enabled',
        'Habilitado',
        'wcpr_cancel_enabled_callback',
        'wcpr_settings',
        'wcpr_cancel'
    );

    add_settings_field(
        'wcpr_cancel_delay',
        'Retraso (minutos)',
        'wcpr_cancel_delay_callback',
        'wcpr_settings',
        'wcpr_cancel'
    );
}

// Callbacks para Email 1
function wcpr_email_1_section_callback()
{
    echo 'Configurar el primer correo de recuperación después del pago fallido.';
}

function wcpr_email_1_enabled_callback()
{
    $value = get_option('wcpr_email_1_enabled');
?>
    <input type="checkbox" name="wcpr_email_1_enabled" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_email_1_delay_callback()
{
    $value = get_option('wcpr_email_1_delay', 30);
?>
    <input type="number" name="wcpr_email_1_delay" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

// Callbacks para Email 2
function wcpr_email_2_section_callback()
{
    echo 'Configurar el segundo correo de recuperación después del pago fallido.';
}

function wcpr_email_2_enabled_callback()
{
    $value = get_option('wcpr_email_2_enabled');
?>
    <input type="checkbox" name="wcpr_email_2_enabled" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_email_2_delay_callback()
{
    $value = get_option('wcpr_email_2_delay', 360);
?>
    <input type="number" name="wcpr_email_2_delay" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

// Callbacks para Email 3
function wcpr_email_3_section_callback()
{
    echo 'Configurar el tercer correo de recuperación después del pago fallido.';
}

function wcpr_email_3_enabled_callback()
{
    $value = get_option('wcpr_email_3_enabled');
?>
    <input type="checkbox" name="wcpr_email_3_enabled" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_email_3_delay_callback()
{
    $value = get_option('wcpr_email_3_delay', 1440);
?>
    <input type="number" name="wcpr_email_3_delay" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

// Callbacks para Cancelación
function wcpr_cancel_section_callback()
{
    echo 'Cancelar automáticamente las órdenes no pagadas.';
}

function wcpr_cancel_enabled_callback()
{
    $value = get_option('wcpr_cancel_enabled');
?>
    <input type="checkbox" name="wcpr_cancel_enabled" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_cancel_delay_callback()
{
    $value = get_option('wcpr_cancel_delay', 2160);
?>
    <input type="number" name="wcpr_cancel_delay" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}
