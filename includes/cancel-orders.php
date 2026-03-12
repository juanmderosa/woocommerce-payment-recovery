<?php

add_action('wcpr_cancel_order', 'wcpr_cancel_unpaid_order');

// Registrar el hook de cancelación para enviar el email automáticamente
// Usar prioridad 20 para asegurar que se ejecuta después de WooCommerce (que usa 10)
add_action('woocommerce_order_status_cancelled', 'wcpr_handle_order_cancelled', 20, 2);

function wcpr_handle_order_cancelled($order_id, $order)
{
    wcpr_log('🔵 Hook: woocommerce_order_status_cancelled disparado', ['order_id' => $order_id]);

    // Verificar si esta orden fue cancelada por WCPR
    $wcpr_cancelled = $order->get_meta('_wcpr_cancelled');
    $cancel_note = $order->get_customer_note();

    wcpr_log('📊 Verificando si es cancelación por WCPR', [
        'order_id' => $order_id,
        'meta_wcpr_cancelled' => $wcpr_cancelled,
        'has_pago_note' => strpos($cancel_note, 'falta de pago') !== false
    ]);

    // Si tiene el meta data o tiene el texto en la nota, es de WCPR
    if ($wcpr_cancelled === '1' || strpos($cancel_note, 'falta de pago') !== false) {
        wcpr_log('✅ Orden cancelada por WCPR - enviando email de cancelación', ['order_id' => $order_id]);
        wcpr_send_cancel_email($order_id);
    } else {
        wcpr_log('⚠️ Orden cancelada manualmente (no por WCPR) - no enviando email', ['order_id' => $order_id]);
    }
}

function wcpr_cancel_unpaid_order($order_id)
{
    wcpr_log('Ejecutando: wcpr_cancel_order', ['order_id' => $order_id]);

    if (!wcpr_is_valid_order($order_id)) {
        wcpr_log('ERROR: Orden no válida en wcpr_cancel_order', ['order_id' => $order_id]);
        return;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        wcpr_log('ERROR: No se pudo obtener la orden en wcpr_cancel_order', ['order_id' => $order_id]);
        return;
    }

    $status = $order->get_status();
    wcpr_log('Estado actual de la orden', ['order_id' => $order_id, 'status' => $status]);

    if ($status === 'pending' || $status === 'failed') {
        try {
            // Marcar que esta cancelación es por WCPR
            $order->update_meta_data('_wcpr_cancelled', '1');
            $order->save();

            $order->update_status(
                'cancelled',
                'Orden cancelada automáticamente por falta de pago luego de 36 horas.'
            );
            wcpr_log('Orden cancelada exitosamente', ['order_id' => $order_id, 'previous_status' => $status]);

            // El email se envía a través del hook woocommerce_order_status_cancelled
        } catch (Exception $e) {
            wcpr_log('ERROR al cancelar orden: ' . $e->getMessage(), ['order_id' => $order_id]);
        }
    } else {
        wcpr_log('Orden con estado que no es pendiente o fallida - no se cancela', ['order_id' => $order_id, 'status' => $status]);
    }
}

function wcpr_send_cancel_email($order_id)
{
    wcpr_log('📧 Intentando enviar email de cancelación', ['order_id' => $order_id]);

    if (!function_exists('WC')) {
        wcpr_log('❌ WooCommerce no está disponible', ['order_id' => $order_id]);
        return;
    }

    try {
        // Obtener el mailer de WooCommerce
        $mailer = WC()->mailer();
        if (!$mailer) {
            wcpr_log('❌ No se pudo obtener el mailer de WooCommerce', ['order_id' => $order_id]);
            return;
        }

        // Obtener los emails registrados
        $emails = $mailer->get_emails();
        if (!$emails) {
            wcpr_log('❌ No hay emails registrados', ['order_id' => $order_id]);
            return;
        }

        wcpr_log('📨 Emails disponibles', ['count' => count($emails), 'keys' => array_keys($emails)]);

        // Buscar el email de cancelación
        if (!isset($emails['WC_Email_Payment_Recovery_Cancel'])) {
            wcpr_log('❌ WC_Email_Payment_Recovery_Cancel no encontrado en emails registrados', ['available' => array_keys($emails)]);
            return;
        }

        $cancel_email = $emails['WC_Email_Payment_Recovery_Cancel'];
        wcpr_log('✅ Email de cancelación encontrado', ['class' => get_class($cancel_email)]);

        // Disparar el email
        $cancel_email->trigger($order_id);
        wcpr_log('✅ Trigger de email de cancelación ejecutado', ['order_id' => $order_id]);
    } catch (Exception $e) {
        wcpr_log('❌ Excepción al enviar email de cancelación: ' . $e->getMessage(), ['order_id' => $order_id, 'trace' => $e->getTraceAsString()]);
    }
}
