<?php
/*
Plugin Name: WooCommerce Payment Recovery
Description: Recuperación de pagos fallidos o pendientes.
Author: JuanmderosaDeveloper
Version: 1.0.0
Requires at least: 5.0
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WCPR_PATH', plugin_dir_path(__FILE__));
define('WCPR_VERSION', '1.0.0');

// SIEMPRE cargar los archivos básicos
require_once WCPR_PATH . 'includes/validations.php';
require_once WCPR_PATH . 'includes/diagnostics.php';

// Cargar verificación temprana
require_once WCPR_PATH . 'includes/verify.php';

// Cargar flow logger para diagnosticar el flujo
require_once WCPR_PATH . 'includes/flow-logger.php';

// CARGAR DEPENDENCIAS TEMPRANO - Antes de registrar los hooks
// Estos son necesarios para que wcpr_schedule_recovery() funcione
require_once WCPR_PATH . 'includes/settings.php';
require_once WCPR_PATH . 'includes/scheduler.php';
require_once WCPR_PATH . 'includes/hooks.php';
require_once WCPR_PATH . 'includes/cancel-orders.php';

// Inicializar opciones por defecto al activar el plugin
if (file_exists(WCPR_PATH . 'includes/settings.php')) {
    register_activation_hook(__FILE__, 'wcpr_set_default_options');
}

// FUNCIONES DE HOOKS DE ÓRDENES - Definidas temprano en el plugin
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

// REGISTRAR EL FILTRO DIRECTAMENTE - SIN HOOKS
// Esto asegura que se registre antes de que WooCommerce cargue
add_filter('woocommerce_email_classes', 'wcpr_register_emails_direct', 0);

// REGISTRAR HOOKS DE ÓRDENES DIRECTAMENTE - MUY TEMPRANO
// Esto asegura que se registren antes del checkout
add_action('woocommerce_checkout_order_processed', 'wcpr_schedule_recovery', 10, 1);
add_action('woocommerce_order_status_pending', 'wcpr_schedule_recovery_on_status_change', 10, 2);
add_action('woocommerce_order_status_failed', 'wcpr_schedule_recovery_on_status_change', 10, 2);

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

// Cargar admin SIEMPRE si estamos en admin (no esperar a que WooCommerce se cargue)
if (is_admin()) {
    require_once WCPR_PATH . 'includes/settings.php';
    require_once WCPR_PATH . 'includes/admin.php';
    require_once WCPR_PATH . 'includes/diagnostics-advanced.php';
}

// Cargar el plugin cuando WooCommerce esté disponible
add_action('after_setup_theme', 'wcpr_init_plugin', 999);

function wcpr_init_plugin()
{
    // Verificar si las dependencias críticas están disponibles
    if (!function_exists('wc_get_order')) {
        return;
    }

    if (!function_exists('as_schedule_single_action')) {
        return;
    }

    if (!class_exists('WC_Email')) {
        return;
    }

    // Cargar configuración (de nuevo si no está en admin)
    if (!is_admin()) {
        require_once WCPR_PATH . 'includes/settings.php';
    }

    // Cargar debug
    require_once WCPR_PATH . 'includes/debug.php';

    // Resto del plugin (ya está cargado en el archivo raíz)
    // do_action('wcpr_loaded');
}

/**
 * INTEGRACIÓN CON TERCEROS:
 * Prevenir envíos duplicados con el plugin "Cart Abandonment Recovery for WooCommerce" (CartFlows).
 * El plugin tercero no detecta pedidos "pendientes", "fallidos" o "cancelados" como compras exitosas,
 * por lo tanto seguiría enviando sus propios correos de carrito abandonado.
 * Vamos a interceptar su filtro antes del envío y cancelar el suyo si nuestro plugin ya está al mando.
 */
add_filter('wcf_ca_should_send_email', 'wcpr_prevent_duplicate_cartflows_emails', 10, 2);

function wcpr_prevent_duplicate_cartflows_emails($should_send, $email_data)
{
    if (!$should_send) {
        return $should_send;
    }

    $user_email = isset($email_data->user_email) ? $email_data->user_email : '';
    
    if (empty($user_email)) {
        return $should_send;
    }

    // Buscar si hay algún pedido reciente (últimos 3 días) para este email
    // que nuestro plugin ya esté gestionando.
    $args = array(
        'billing_email' => $user_email,
        'date_created' => '>' . (time() - DAY_IN_SECONDS * 3), // dentro de los últimos 3 días
        'status' => array('pending', 'failed'),
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $orders = wc_get_orders($args);

    if (!empty($orders)) {
        $order = current($orders);
        // Si la orden ya tiene nuestro sello de que la estamos procesando
        if ($order->get_meta('_wcpr_recovery_scheduled')) {
            wcpr_log('🚫 Bloqueando email del plugin Cart Abandonment Recovery. WCPR ya está gestionando la recuperación.', ['email' => $user_email, 'order_id' => $order->get_id()]);
            // Devolver false detiene el envío de ese plugin
            return false;
        }
    }

    return $should_send;
}
