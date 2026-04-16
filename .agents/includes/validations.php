<?php

/**
 * Función de logging simple
 */
if (!function_exists('wcpr_log')) {
    function wcpr_log($message, $data = null)
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            $log_message = '[WCPR] ' . $message;

            if ($data !== null) {
                $log_message .= ' | ' . print_r($data, true);
            }

            error_log($log_message);
        }
    }
}

/**
 * Funciones de validación - Sin logging en esta sección
 */

function wcpr_is_valid_order($order_id)
{
    if (!is_numeric($order_id) || $order_id <= 0) {
        return false;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        return false;
    }

    return true;
}

function wcpr_can_schedule_actions()
{
    if (!function_exists('as_schedule_single_action')) {
        return false;
    }

    return true;
}
