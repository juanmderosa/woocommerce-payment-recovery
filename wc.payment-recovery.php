<?php
/*
Plugin Name: WooCommerce Payment Recovery
Description: Recuperación de pagos fallidos o pendientes.
Author: JuanmderosaDeveloper
Version: 1.1.0
Requires at least: 5.0
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WCPR_PATH', plugin_dir_path(__FILE__));
define('WCPR_VERSION', '1.1.0');

// SIEMPRE cargar los archivos básicos independientes
require_once WCPR_PATH . 'includes/validations.php';
require_once WCPR_PATH . 'includes/diagnostics.php';
require_once WCPR_PATH . 'includes/verify.php';
require_once WCPR_PATH . 'includes/flow-logger.php';
require_once WCPR_PATH . 'includes/settings.php'; // Settings es seguro de cargar temprano

// Inicializar opciones por defecto al activar el plugin
register_activation_hook(__FILE__, 'wcpr_set_default_options');

// Cargar admin SIEMPRE si estamos en admin
if (is_admin()) {
    require_once WCPR_PATH . 'includes/admin.php';
    require_once WCPR_PATH . 'includes/diagnostics-advanced.php';
}

// Cargar las dependencias y registrar hooks de WooCommerce cuando WP termine de cargar los plugins
add_action('plugins_loaded', 'wcpr_bootstrap_plugin');

function wcpr_bootstrap_plugin()
{
    // Verificar si WooCommerce está activo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // CARGAR DEPENDENCIAS (que asumen que WC existe)
    require_once WCPR_PATH . 'includes/scheduler.php';
    require_once WCPR_PATH . 'includes/hooks.php';
    require_once WCPR_PATH . 'includes/cancel-orders.php';
    require_once WCPR_PATH . 'includes/debug.php';

    // REGISTRAR LOS HOOKS DE ÓRDENES Y EMAILS
    add_filter('woocommerce_email_classes', 'wcpr_register_emails_direct', 0);
    add_action('woocommerce_checkout_order_processed', 'wcpr_schedule_recovery', 10, 1);
    add_action('woocommerce_order_status_pending', 'wcpr_schedule_recovery_on_status_change', 10, 2);
    add_action('woocommerce_order_status_failed', 'wcpr_schedule_recovery_on_status_change', 10, 2);
    add_action('woocommerce_order_status_processing', 'wcpr_mark_as_recovered_on_status_change', 10, 2);
    add_action('woocommerce_order_status_completed', 'wcpr_mark_as_recovered_on_status_change', 10, 2);
}

// FUNCIONES DE HOOKS DE ÓRDENES
function wcpr_mark_as_recovered_on_status_change($order_id, $order)
{
    // Solo nos importa si había sido programada para recuperación
    if (!$order->get_meta('_wcpr_recovery_scheduled')) {
        return;
    }

    // Si ya está marcada como recuperada, no hacer nada
    if ($order->get_meta('_wcpr_recovered')) {
        return;
    }

    wcpr_log('💰 Orden recuperada exitosamente', ['order_id' => $order_id, 'status' => $order->get_status()]);
    $order->update_meta_data('_wcpr_recovered', '1');
    $order->save();

    // Cancelar acciones programadas para esta orden
    if (function_exists('as_unschedule_all_actions')) {
        as_unschedule_all_actions('wcpr_send_email_1', array($order_id), 'wc-payment-recovery');
        as_unschedule_all_actions('wcpr_send_email_2', array($order_id), 'wc-payment-recovery');
        as_unschedule_all_actions('wcpr_send_email_3', array($order_id), 'wc-payment-recovery');
        as_unschedule_all_actions('wcpr_cancel_order', array($order_id), 'wc-payment-recovery');
        wcpr_log('🚫 Acciones de recuperación canceladas para la orden', ['order_id' => $order_id]);
    }
}

function wcpr_schedule_recovery_on_status_change($order_id, $order)
{
    wcpr_log('📍 Hook: Cambio de estado de orden', ['order_id' => $order_id, 'status' => $order->get_status()]);

    if ($order->get_meta('_wcpr_recovery_scheduled')) {
        wcpr_log('⏭️  Ya se programaron las acciones de recuperación para esta orden anteriormente', ['order_id' => $order_id]);
        return;
    }

    wcpr_schedule_recovery($order_id);
}

function wcpr_schedule_recovery($order_id)
{
    wcpr_log('✓ FUNCIÓN wcpr_schedule_recovery ejecutada', ['order_id' => $order_id]);

    if (!wcpr_is_valid_order($order_id)) {
        wcpr_log('✗ Orden no válida', ['order_id' => $order_id]);
        return;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        wcpr_log('✗ No se pudo obtener la orden', ['order_id' => $order_id]);
        return;
    }

    $status = $order->get_status();
    wcpr_log('📊 Estado de orden', ['order_id' => $order_id, 'status' => $status]);

    // Verificar que ActionScheduler esté disponible
    if (!function_exists('as_schedule_single_action')) {
        wcpr_log('✗ ActionScheduler no disponible', []);
        return;
    }

    if ($order->get_meta('_wcpr_recovery_scheduled')) {
        wcpr_log('⏭️  Ya se programaron las acciones de recuperación para esta orden anteriormente', ['order_id' => $order_id]);
        return;
    }

    wcpr_log('⏰ Programando emails para orden', ['order_id' => $order_id, 'status' => $status]);
    wcpr_schedule_emails($order_id);

    $order->update_meta_data('_wcpr_recovery_scheduled', '1');
    $order->save();

    wcpr_log('✓ Emails programados exitosamente', ['order_id' => $order_id]);
}

function wcpr_register_emails_direct($emails)
{
    wcpr_log('✓ FILTRO EJECUTADO: woocommerce_email_classes', ['emails_actuales' => count($emails)]);

    try {
        if (!class_exists('WC_Email')) {
            wcpr_log('⚠️ WC_Email no existe aún', []);
            return $emails;
        }

        // Cargar archivos de email
        require_once WCPR_PATH . 'includes/emails/class-wc-email-recovery-base.php';
        require_once WCPR_PATH . 'includes/emails/class-wc-email-recovery-1.php';
        require_once WCPR_PATH . 'includes/emails/class-wc-email-recovery-2.php';
        require_once WCPR_PATH . 'includes/emails/class-wc-email-recovery-3.php';
        require_once WCPR_PATH . 'includes/emails/class-wc-email-recovery-cancel.php';

        if (!class_exists('WC_Email_Payment_Recovery_1')) {
            wcpr_log('✗ No se pudo cargar WC_Email_Payment_Recovery_1', []);
            return $emails;
        }

        $emails['WC_Email_Payment_Recovery_1'] = new WC_Email_Payment_Recovery_1();
        $emails['WC_Email_Payment_Recovery_2'] = new WC_Email_Payment_Recovery_2();
        $emails['WC_Email_Payment_Recovery_3'] = new WC_Email_Payment_Recovery_3();
        $emails['WC_Email_Payment_Recovery_Cancel'] = new WC_Email_Payment_Recovery_Cancel();

        wcpr_log('✓ 4 emails de WCPR registrados correctamente', ['total_emails' => count($emails), 'wcpr_emails' => ['Email 1', 'Email 2', 'Email 3', 'Cancelación']]);

        return $emails;
    } catch (Exception $e) {
        wcpr_log('✗ ERROR registrando emails: ' . $e->getMessage(), ['linea' => $e->getLine()]);
        return $emails;
    }
}
