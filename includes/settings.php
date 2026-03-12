<?php

/**
 * Settigns y opciones del plugin
 */

function wcpr_get_option($key, $default = '')
{
    $option = get_option('wcpr_' . $key, $default);
    return $option;
}

function wcpr_update_option($key, $value)
{
    return update_option('wcpr_' . $key, $value);
}

function wcpr_delete_option($key)
{
    return delete_option('wcpr_' . $key);
}

/**
 * Obtener configuración de correos
 */
function wcpr_get_email_settings()
{
    return [
        'email_1_enabled' => wcpr_get_option('email_1_enabled', 'yes'),
        'email_1_delay' => wcpr_get_option('email_1_delay', 30),
        'email_2_enabled' => wcpr_get_option('email_2_enabled', 'yes'),
        'email_2_delay' => wcpr_get_option('email_2_delay', 360),
        'email_3_enabled' => wcpr_get_option('email_3_enabled', 'yes'),
        'email_3_delay' => wcpr_get_option('email_3_delay', 1440),
        'cancel_enabled' => wcpr_get_option('cancel_enabled', 'yes'),
        'cancel_delay' => wcpr_get_option('cancel_delay', 2160),
    ];
}

/**
 * Valores por defecto
 */
function wcpr_get_defaults()
{
    return [
        'email_1_enabled' => 'yes',
        'email_1_delay' => 30,         // minutos
        'email_2_enabled' => 'yes',
        'email_2_delay' => 360,        // minutos (6 horas)
        'email_3_enabled' => 'yes',
        'email_3_delay' => 1440,       // minutos (24 horas)
        'cancel_enabled' => 'yes',
        'cancel_delay' => 2160,        // minutos (36 horas)
    ];
}

/**
 * Inicializar opciones por defecto
 */
function wcpr_maybe_init_options()
{
    if (!get_option('wcpr_initialized')) {
        $defaults = wcpr_get_defaults();

        foreach ($defaults as $key => $value) {
            wcpr_update_option($key, $value);
        }

        update_option('wcpr_initialized', 'yes');
    }
}

add_action('plugins_loaded', 'wcpr_maybe_init_options');
