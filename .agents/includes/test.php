<?php

/**
 * Test manual del plugin WCPR
 * 
 * Uso en WordPress admin:
 * Agrega ?wcpr_test=1 a la URL del dashboard
 * 
 * Uso en terminal (desde la raíz de WordPress):
 * wp eval-file plugins/wc-payment-recovery/includes/test.php
 */

// Para CLI de WordPress
if (class_exists('WP_CLI')) {
    echo "=== WCPR TEST ===\n";
}

// Verificar que el plugin se haya cargado
echo "1. Verificando plugin...\n";

if (function_exists('wcpr_log')) {
    echo "   ✓ wcpr_log disponible\n";
} else {
    echo "   ✗ wcpr_log NO disponible\n";
    return;
}

if (function_exists('wcpr_get_email_settings')) {
    echo "   ✓ wcpr_get_email_settings disponible\n";
} else {
    echo "   ✗ wcpr_get_email_settings NO disponible\n";
}

// Verificar ActionScheduler
echo "\n2. Verificando ActionScheduler...\n";

if (function_exists('as_schedule_single_action')) {
    echo "   ✓ as_schedule_single_action disponible\n";
} else {
    echo "   ✗ as_schedule_single_action NO disponible\n";
}

if (function_exists('as_get_scheduled_actions')) {
    echo "   ✓ as_get_scheduled_actions disponible\n";
} else {
    echo "   ✗ as_get_scheduled_actions NO disponible\n";
}

// Verificar WooCommerce
echo "\n3. Verificando WooCommerce...\n";

if (class_exists('WooCommerce')) {
    echo "   ✓ WooCommerce activo\n";
} else {
    echo "   ✗ WooCommerce NO activo\n";
    return;
}

if (function_exists('wc_get_order')) {
    echo "   ✓ wc_get_order disponible\n";
} else {
    echo "   ✗ wc_get_order NO disponible\n";
}

// Verificar emails registrados
echo "\n4. Verificando emails registrados...\n";

$emails = WC()->mailer()->get_emails();
$our_emails = [
    'WC_Email_Payment_Recovery_1',
    'WC_Email_Payment_Recovery_2',
    'WC_Email_Payment_Recovery_3',
];

foreach ($our_emails as $email_class) {
    if (isset($emails[$email_class])) {
        echo "   ✓ {$email_class} registrado\n";
    } else {
        echo "   ✗ {$email_class} NO registrado\n";
    }
}

// Buscar órdenes para test
echo "\n5. Buscando orden para test...\n";

$orders = wc_get_orders([
    'status' => ['pending', 'failed'],
    'limit' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
]);

if (empty($orders)) {
    echo "   ⚠️  No hay órdenes pending/failed. Crea una para probar.\n";
    return;
}

$order = $orders[0];
$order_id = $order->get_id();

echo "   ✓ Orden encontrada: #{$order_id}\n";
echo "   - Estado: " . $order->get_status() . "\n";
echo "   - Email: " . $order->get_billing_email() . "\n";

// Verificar acciones programadas para esta orden
echo "\n6. Verificando acciones programadas...\n";

$scheduled = as_get_scheduled_actions([
    'group' => 'wc-payment-recovery',
    'args' => [$order_id],
]);

if (empty($scheduled)) {
    echo "   ⚠️  No hay acciones programadas para esta orden\n";
    echo "   Programando manualmente...\n";

    wcpr_schedule_emails($order_id);

    $scheduled = as_get_scheduled_actions([
        'group' => 'wc-payment-recovery',
        'args' => [$order_id],
    ]);

    if (empty($scheduled)) {
        echo "   ✗ Error: No se programaron acciones\n";
    } else {
        echo "   ✓ " . count($scheduled) . " acciones programadas\n";
    }
} else {
    echo "   ✓ " . count($scheduled) . " acciones programadas:\n";
    foreach ($scheduled as $action) {
        try {
            $scheduled_time = 'No disponible';
            $schedule = $action->get_schedule();

            if ($schedule) {
                if (method_exists($schedule, 'getTimestamp')) {
                    $timestamp = $schedule->getTimestamp();
                    if ($timestamp) {
                        $scheduled_time = wp_date('Y-m-d H:i:s', $timestamp);
                    }
                } elseif (method_exists($action, 'get_date')) {
                    $timestamp = $action->get_date();
                    if ($timestamp) {
                        $scheduled_time = wp_date('Y-m-d H:i:s', $timestamp);
                    }
                }
            }
        } catch (Exception $e) {
            $scheduled_time = 'Error: ' . $e->getMessage();
        }
        echo "     - " . $action->get_hook() . " @ " . $scheduled_time . "\n";
    }
}

// Configuración actual
echo "\n7. Configuración actual...\n";

$settings = wcpr_get_email_settings();
echo "   Email 1: " . ($settings['email_1_enabled'] === 'yes' ? '✓' : '✗') . " - " . $settings['email_1_delay'] . " min\n";
echo "   Email 2: " . ($settings['email_2_enabled'] === 'yes' ? '✓' : '✗') . " - " . $settings['email_2_delay'] . " min\n";
echo "   Email 3: " . ($settings['email_3_enabled'] === 'yes' ? '✓' : '✗') . " - " . $settings['email_3_delay'] . " min\n";
echo "   Cancelación: " . ($settings['cancel_enabled'] === 'yes' ? '✓' : '✗') . " - " . $settings['cancel_delay'] . " min\n";

echo "\n=== TEST COMPLETADO ===\n";
