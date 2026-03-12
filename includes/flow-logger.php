<?php

/**
 * Verificación específica del flujo de checkout
 * Monitorea cada paso para diagnosticar fallos
 */

// PASO 1: ¿Se registran los emails?
add_action('woocommerce_init', function () {
    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();

    $wcpr_emails = array_filter(array_keys($emails), function ($key) {
        return strpos($key, 'Payment_Recovery') !== false;
    });

    if (empty($wcpr_emails)) {
        wcpr_log('⚠️ ADVERTENCIA: Los emails de WCPR no se registraron', [
            'total_emails' => count($emails),
            'wcpr_emails' => $wcpr_emails,
        ]);
    } else {
        wcpr_log('✓ Emails de WCPR detectados en woocommerce_init', $wcpr_emails);
    }
}, 999);

// PASO 2: Cuando se procesa el checkout
add_action('woocommerce_checkout_order_processed', function ($order_id, $posted_data = null) {
    wcpr_log('📍 CHECKOUT PROCESADO', ['order_id' => $order_id]);

    // ¿Existe la orden?
    $order = wc_get_order($order_id);
    if (!$order) {
        wcpr_log('✗ Orden #' . $order_id . ' NO existe');
        return;
    }

    wcpr_log('✓ Orden #' . $order_id . ' existe', [
        'status' => $order->get_status(),
        'email' => $order->get_billing_email(),
    ]);

    // ¿Se puede programar?
    if (!wcpr_can_schedule_actions()) {
        wcpr_log('✗ ActionScheduler no funciona');
        return;
    }

    wcpr_log('✓ ActionScheduler disponible');

    // ¿Se intenta programar?
}, 1, 2);

// PASO 3: Justo antes de que se intente enviar un email (si llegamos tan lejos)
add_action('woocommerce_order_status_pending', function ($order_id, $order) {
    wcpr_log('📍 ORDEN PENDING', ['order_id' => $order_id, 'status' => $order->get_status()]);

    // Si no hay acciones programadas, ¿por qué?
    $scheduled = as_get_scheduled_actions([
        'group' => 'wc-payment-recovery',
        'args' => [$order_id],
    ]);

    if (empty($scheduled)) {
        wcpr_log('⚠️ No hay acciones programadas para orden #' . $order_id);
    } else {
        wcpr_log('✓ Hay ' . count($scheduled) . ' acciones programadas para orden #' . $order_id);
    }
}, 1, 2);

// PASO 4: Verificar que el programa de acciones funciona
add_action('woocommerce_order_status_failed', function ($order_id, $order) {
    wcpr_log('📍 ORDEN FAILED', ['order_id' => $order_id]);
}, 1, 2);

// LOG: Cada vez que se intenta disparar un email de WCPR
add_action('wcpr_send_email_1', function ($order_id) {
    wcpr_log('🔴 wcpr_send_email_1 DISPARADO', ['order_id' => $order_id]);
}, 0);

// LOG: Cada vez que se intenta disparar el segundo email de WCPR
add_action('wcpr_send_email_2', function ($order_id) {
    wcpr_log('🟠 wcpr_send_email_2 DISPARADO', ['order_id' => $order_id]);
}, 0);

// LOG: Cada vez que se intenta disparar el tercer email de WCPR
add_action('wcpr_send_email_3', function ($order_id) {
    wcpr_log('🟡 wcpr_send_email_3 DISPARADO', ['order_id' => $order_id]);
}, 0);

// LOG: Cada vez que se intenta cancelar una orden
add_action('wcpr_cancel_order', function ($order_id) {
    wcpr_log('🔵 wcpr_cancel_order DISPARADO', ['order_id' => $order_id]);
}, 0);

// LOG: Envío de email de cancelación
add_action('woocommerce_order_status_cancelled', function ($order_id) {
    wcpr_log('📧 Orden cancelada - Email de cancelación debería haber sido enviado', ['order_id' => $order_id]);
}, 0);
