<?php

/**
 * Quick Test - WCPR
 * 
 * Uso:
 * 1. Desde WordPress CLI: wp eval-file plugins/wc-payment-recovery/includes/quick-test.php
 * 2. O accede a: https://tu-sitio.com/wp-content/plugins/wc-payment-recovery/includes/quick-test.php?test=1
 */

// Sólo funciona si pasas test=1
if (empty($_GET['test'])) {
    die('No se permite acceso directo. Usa: wp eval-file ...');
}

echo "=== WCPR QUICK TEST ===\n\n";

// TEST 1: ¿El plugin se cargó?
echo "1. ¿El plugin se cargó?\n";
if (function_exists('wcpr_log')) {
    echo "   ✓ SÍ - wcpr_log disponible\n";
} else {
    echo "   ✗ NO - wspr_log NO disponible\n";
    die();
}

// TEST 2: ¿Los emails se registraron?
echo "\n2. ¿Los emails se registraron en WooCommerce?\n";

$mailer = WC()->mailer();
$emails = $mailer->get_emails();

$our_emails = [
    'WC_Email_Payment_Recovery_1' => 'Email 1 (30 min)',
    'WC_Email_Payment_Recovery_2' => 'Email 2 (6 horas)',
    'WC_Email_Payment_Recovery_3' => 'Email 3 (24 horas)',
];

$found_count = 0;
foreach ($our_emails as $class_name => $description) {
    if (isset($emails[$class_name])) {
        echo "   ✓ $class_name - $description\n";
        $found_count++;
    } else {
        echo "   ✗ $class_name - NO registrado\n";
    }
}

if ($found_count === 0) {
    echo "\n   ⚠️  PROBLEMA: Ninguno de nuestros emails está registrado\n";
    echo "   Posibles causas:\n";
    echo "   - El filter no se ejecutó\n";
    echo "   - Las clases no se cargaron\n";
    echo "   - Error al instanciar\n";
    die();
} elseif ($found_count < 3) {
    echo "\n   ⚠️  PROBLEMA: Solo " . $found_count . " de 3 emails registrados\n";
    die();
}

echo "\n   ✓ Todos los 3 emails están registrados correctamente\n";

// TEST 3: ¿Hay órdenes pending?
echo "\n3. Buscando órdenes pending/failed...\n";

$orders = wc_get_orders([
    'status' => ['pending', 'failed'],
    'limit' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
]);

if (empty($orders)) {
    echo "   ⚠️  No hay órdenes pending/failed\n";
    echo "   Crea una orden de prueba\n";
} else {
    $order = $orders[0];

    echo "   ✓ Orden encontrada: #" . $order->get_id() . "\n";
    echo "     Estado: " . $order->get_status() . "\n";
    echo "     Email: " . $order->get_billing_email() . "\n";

    // TEST 4: ¿Hay acciones programadas?
    echo "\n4. ¿Hay acciones programadas para esta orden?\n";

    $scheduled = as_get_scheduled_actions([
        'group' => 'wc-payment-recovery',
        'args' => [$order->get_id()],
    ]);

    if (empty($scheduled)) {
        echo "   ✗ NO hay acciones programadas\n";
        echo "\n   Intentando programar manualmente...\n";

        // Intenta programar
        wcpr_schedule_emails($order->get_id());

        // Verifica si se programaron
        $scheduled = as_get_scheduled_actions([
            'group' => 'wc-payment-recovery',
            'args' => [$order->get_id()],
        ]);

        if (empty($scheduled)) {
            echo "   ✗ ERROR: No se programaron acciones\n";
            echo "   Revisa los logs para ver el error\n";
        } else {
            echo "   ✓ " . count($scheduled) . " acciones programadas correctamente\n";
        }
    } else {
        echo "   ✓ " . count($scheduled) . " acciones ya programadas:\n";
        foreach ($scheduled as $action) {
            try {
                $time = 'No disponible';
                $schedule = $action->get_schedule();

                if ($schedule) {
                    if (method_exists($schedule, 'getTimestamp')) {
                        $timestamp = $schedule->getTimestamp();
                        if ($timestamp) {
                            $time = wp_date('Y-m-d H:i:s', $timestamp);
                        }
                    } elseif (method_exists($action, 'get_date')) {
                        $timestamp = $action->get_date();
                        if ($timestamp) {
                            $time = wp_date('Y-m-d H:i:s', $timestamp);
                        }
                    }
                }
            } catch (Exception $e) {
                $time = 'Error: ' . $e->getMessage();
            }
            echo "     - " . $action->get_hook() . " @ " . $time . "\n";
        }
    }
}

echo "\n=== TEST COMPLETADO ===\n";
