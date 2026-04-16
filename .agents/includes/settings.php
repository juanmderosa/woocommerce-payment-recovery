<?php

/**
 * Settings y opciones del plugin (un único option array: wcpr_settings)
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

function wcpr_get_settings()
{
    $defaults = wcpr_get_defaults();
    $opts = get_option('wcpr_settings', []);
    if (!is_array($opts)) {
        $opts = [];
    }

    return array_merge($defaults, $opts);
}

function wcpr_update_settings($data)
{
    $san = wcpr_sanitize_settings($data);
    return update_option('wcpr_settings', $san);
}

function wcpr_sanitize_settings($input)
{
    $defaults = wcpr_get_defaults();
    $out = [];

    foreach ($defaults as $key => $default) {
        if (isset($input[$key])) {
            // booleans stored as 'yes' / 'no' for checkboxes
            if (strpos($key, '_enabled') !== false) {
                $out[$key] = $input[$key] === 'yes' ? 'yes' : 'no';
            } else {
                $out[$key] = intval($input[$key]);
            }
        } else {
            $out[$key] = $default;
        }
    }

    return $out;
}

/**
 * Inicializar opciones por defecto en la activación del plugin
 */
function wcpr_set_default_options()
{
    $defaults = wcpr_get_defaults();
    if (false === get_option('wcpr_settings')) {
        add_option('wcpr_settings', $defaults);
    } else {
        $current = get_option('wcpr_settings', []);
        if (!is_array($current) || empty($current)) {
            update_option('wcpr_settings', $defaults);
        }
    }
}

/**
 * Compat wrapper para código existente que usa wcpr_get_email_settings()
 */
function wcpr_get_email_settings()
{
    return wcpr_get_settings();
}

