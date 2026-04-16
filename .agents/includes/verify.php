<?php

/**
 * Verificación inicial del plugin
 * Este archivo se carga PRIMERO para asegurar que el plugin esté funcionando
 */

// Registro muy temprano para verificar que el plugin se carga
wcpr_log('=== WCPR PLUGIN INICIADO ===', ['timestamp' => current_time('mysql')]);

// Verificar cuando se procesa una orden
add_action('woocommerce_checkout_order_processed', 'wcpr_verify_checkout_hook', 1, 1);

function wcpr_verify_checkout_hook($order_id)
{
    wcpr_log('✓ HOOK CHECKOUT DISPARADO', ['order_id' => $order_id, 'time' => current_time('mysql')]);
}

// Añadimos verificación para cambios de estado que es cuando realmente se crea la orden
add_action('woocommerce_order_status_pending', 'wcpr_log_order_pending', 1, 2);
add_action('woocommerce_order_status_failed', 'wcpr_log_order_failed', 1, 2);

function wcpr_log_order_pending($order_id, $order)
{
    wcpr_log('✓ ORDEN CAMBIÓ A PENDING', ['order_id' => $order_id, 'time' => current_time('mysql')]);
}

function wcpr_log_order_failed($order_id, $order)
{
    wcpr_log('✓ ORDEN CAMBIÓ A FAILED', ['order_id' => $order_id, 'time' => current_time('mysql')]);
}

// Verificar cambios de estado generales
add_action('woocommerce_order_status_changed', 'wcpr_verify_status_change', 1, 3);

function wcpr_verify_status_change($order_id, $old_status, $new_status)
{
    wcpr_log('✓ CAMBIO DE ESTADO', ['order_id' => $order_id, 'old' => $old_status, 'new' => $new_status]);
}

// Verificar si WooCommerce está disponible
add_action('woocommerce_init', function () {
    wcpr_log('✓ WooCommerce inicializado');
});

// Verificar si ActionScheduler está disponible
add_action('action_scheduler_init', function () {
    wcpr_log('✓ ActionScheduler inicializado');
});

// Hook personalizado para ver cuándo se cargan los emails
add_filter('woocommerce_email_classes', 'wcpr_verify_emails_registered_filter');

function wcpr_verify_emails_registered_filter($emails)
{
    wcpr_log('Emails registrados en WooCommerce:', array_keys($emails));
    return $emails;
}
