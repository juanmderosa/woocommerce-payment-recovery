<?php

/**
 * Diagnóstico avanzado del plugin
 * Este archivo se carga solo en admin para verificar problemas específicos
 */

add_action('wp_ajax_wcpr_test_flow', 'wcpr_test_flow_ajax');
add_action('wp_ajax_nopriv_wcpr_test_flow', 'wcpr_test_flow_ajax');

function wcpr_test_flow_ajax()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sin permisos');
    }

    $result = [
        'plugin_loaded' => function_exists('wcpr_get_email_settings'),
        'woocommerce_active' => class_exists('WooCommerce'),
        'action_scheduler_available' => function_exists('as_schedule_single_action'),
        'emails_registered' => wcpr_verify_emails_registered(),
        'settings' => wcpr_get_email_settings(),
        'pending_actions' => [],
    ];

    // Obtener acciones pendientes
    if (function_exists('as_get_scheduled_actions')) {
        $pending = as_get_scheduled_actions([
            'group' => 'wc-payment-recovery',
            'status' => 'pending',
        ]);

        foreach ($pending as $action) {
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
                $scheduled_time = 'Error al obtener';
            }

            $result['pending_actions'][] = [
                'hook' => $action->get_hook(),
                'args' => $action->get_args(),
                'scheduled' => $scheduled_time,
            ];
        }
    }

    wp_send_json_success($result);
}

/**
 * Panel de diagnóstico mejorado
 */
add_action('admin_notices', 'wcpr_show_diagnostic_panel');

function wcpr_show_diagnostic_panel()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['wcpr_diagnostic'])) {
        return;
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wcpr_diagnostic')) {
        return;
    }

    // Obtener la última orden pending o failed
    $args = [
        'status' => ['pending', 'failed'],
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    $orders = wc_get_orders($args);

    if (empty($orders)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>No hay órdenes con estado pending o failed.</p>';
        echo '</div>';
        return;
    }

    $order = $orders[0];
    $order_id = $order->get_id();

    // Obtener acciones programadas para esta orden
    $actions = [];
    if (function_exists('as_get_scheduled_actions')) {
        $actions = as_get_scheduled_actions([
            'group' => 'wc-payment-recovery',
            'args' => [$order_id],
        ]);
    }

    echo '<div class="notice notice-info is-dismissible" style="background: #e7f3ff; border-left: 4px solid #0073aa;">';
    echo '<h3 style="margin-top: 0;">WCPR - Panel de Diagnóstico</h3>';

    echo '<div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd;">';
    echo '<h4>Última orden: #' . $order_id . '</h4>';
    echo '<ul>';
    echo '<li><strong>Estado:</strong> ' . $order->get_status() . '</li>';
    echo '<li><strong>Email:</strong> ' . $order->get_billing_email() . '</li>';
    echo '<li><strong>Total:</strong> ' . $order->get_formatted_order_total() . '</li>';
    echo '<li><strong>Fecha:</strong> ' . $order->get_date_created()->format('Y-m-d H:i:s') . '</li>';
    echo '</ul>';
    echo '</div>';

    echo '<div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd;">';
    echo '<h4>Acciones programadas para orden #' . $order_id . ':</h4>';
    if (empty($actions)) {
        echo '<p style="color: #d32f2f;"><strong>⚠️ No hay acciones programadas</strong></p>';
    } else {
        echo '<ul>';
        foreach ($actions as $action) {
            try {
                $scheduled_time = 'No disponible';

                // Intentar obtener el timestamp de varias formas
                $schedule = $action->get_schedule();

                if ($schedule) {
                    // Método 1: getTimestamp()
                    if (method_exists($schedule, 'getTimestamp')) {
                        $timestamp = $schedule->getTimestamp();
                        if ($timestamp) {
                            $scheduled_time = wp_date('Y-m-d H:i:s', $timestamp);
                        }
                    }
                    // Método 2: get_date() o similar
                    elseif (method_exists($action, 'get_date')) {
                        $timestamp = $action->get_date();
                        if ($timestamp) {
                            $scheduled_time = wp_date('Y-m-d H:i:s', $timestamp);
                        }
                    }
                    // Método 3: Acceder a propiedad pública
                    elseif (isset($schedule->date)) {
                        $scheduled_time = wp_date('Y-m-d H:i:s', strtotime($schedule->date));
                    }
                }
            } catch (Exception $e) {
                $scheduled_time = 'Error al obtener';
            }
            $now = wp_date('Y-m-d H:i:s');
            echo '<li>' . esc_html($action->get_hook()) . ' - Planificado: ' . esc_html($scheduled_time) . ' (Ahora: ' . esc_html($now) . ')</li>';
        }
        echo '</ul>';
    }
    echo '</div>';

    echo '<div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd;">';
    echo '<h4>Logs recientes:</h4>';
    echo '<p><code style="background: #f5f5f5; padding: 10px; display: block; overflow: auto;">';
    echo 'Ver en: /wp-content/debug.log';
    echo '</code></p>';
    echo '</div>';

    // Botón para programar manualmente
    echo '<div style="background: white; padding: 15px; margin: 10px 0; border-radius: 4px; border: 1px solid #ddd;">';
    echo '<form method="post" style="display: inline;">';
    wp_nonce_field('wcpr_test_order_action');
    echo '<input type="hidden" name="wcpr_test_order_id" value="' . $order_id . '">';
    echo '<button type="submit" name="wcpr_test_action" value="schedule" class="button button-primary">Programar emails manualmente</button>';
    echo '</form>';
    echo '</div>';

    echo '</div>';

    // Procesar acción manual
    if (isset($_POST['wcpr_test_action']) && isset($_POST['wcpr_test_order_id'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wcpr_test_order_action')) {
            return;
        }

        $test_order_id = intval($_POST['wcpr_test_order_id']);

        if ($_POST['wcpr_test_action'] === 'schedule') {
            wcpr_log('TEST MANUAL: Programando emails para orden ' . $test_order_id);
            wcpr_schedule_emails($test_order_id);
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✓ Acciones programadas manualmente. Revisa los logs.</p>';
            echo '</div>';
        }
    }
}

/**
 * Añadir enlace en el admin para acceder al panel de diagnóstico
 */
add_action('admin_notices', 'wcpr_add_diagnostic_link');

function wcpr_add_diagnostic_link()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Solo mostrar si hay órdenes pending/failed
    $args = [
        'status' => ['pending', 'failed'],
        'limit' => 1,
    ];

    $orders = wc_get_orders($args);

    if (empty($orders)) {
        return;
    }

    $url = add_query_arg([
        'wcpr_diagnostic' => '1',
        '_wpnonce' => wp_create_nonce('wcpr_diagnostic'),
    ]);

    echo '<div class="notice notice-info" style="margin-top: 20px;">';
    echo '<p>';
    echo '<strong>WCPR - Payment Recovery:</strong> ';
    echo '<a href="' . esc_url($url) . '" class="button">Abrir diagnóstico</a>';
    echo '</p>';
    echo '</div>';
}
