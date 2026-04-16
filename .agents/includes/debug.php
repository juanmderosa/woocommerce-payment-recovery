<?php

/**
 * Debugging y logs del plugin
 */

/**
 * Función de logging (por si no existe)
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
 * Verificar que los correos estén registrados correctamente
 */
function wcpr_verify_emails_registered()
{
    if (!function_exists('WC') || !is_callable('WC')) {
        return false;
    }

    $mailer = WC()->mailer();
    if (!$mailer) {
        return false;
    }

    $emails = $mailer->get_emails();
    if (!$emails) {
        return false;
    }

    $all_registered = isset($emails['WC_Email_Payment_Recovery_1']) &&
        isset($emails['WC_Email_Payment_Recovery_2']) &&
        isset($emails['WC_Email_Payment_Recovery_3']);

    if ($all_registered) {
        wcpr_log('INFO: Todos los correos registrados correctamente');
    } else {
        wcpr_log('INFO: Algunos correos no se registraron');
    }

    return $all_registered;
}

/**
 * Dashboard widget para debugging
 */
add_action('wp_dashboard_setup', 'wcpr_add_dashboard_widget');

function wcpr_add_dashboard_widget()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    wp_add_dashboard_widget(
        'wcpr_debug_widget',
        'WC Payment Recovery - Debug',
        'wcpr_dashboard_widget_callback'
    );
}

function wcpr_dashboard_widget_callback()
{
    // Verificar si el plugin se cargó correctamente
    if (!has_action('wcpr_loaded')) {
        echo '<div style="padding: 10px; background: #fee; border: 1px solid #f99; border-radius: 4px;">';
        echo '<p><strong style="color: #c00;">⚠️ El plugin no se cargó correctamente</strong></p>';
        echo '<p>Las dependencias de WooCommerce no están disponibles. Verifica que WooCommerce esté activo.</p>';
        echo '</div>';
        return;
    }
?>
    <div style="padding: 10px;">
        <h4>Estado del sistema</h4>

        <?php
        // ActionScheduler
        if (function_exists('as_schedule_single_action')) {
            echo '<p><strong>✓ ActionScheduler:</strong> Disponible</p>';
        } else {
            echo '<p><strong>✗ ActionScheduler:</strong> NO disponible</p>';
        }

        // WooCommerce
        if (class_exists('WooCommerce')) {
            echo '<p><strong>✓ WooCommerce:</strong> Activo</p>';
        } else {
            echo '<p><strong>✗ WooCommerce:</strong> NO activo</p>';
        }

        // Correos registrados
        $emails_ok = wcpr_verify_emails_registered();
        if ($emails_ok) {
            echo '<p><strong>✓ Correos:</strong> Registrados</p>';
        } else {
            echo '<p><strong>✗ Correos:</strong> No registrados</p>';
        }

        // Configuración
        if (function_exists('wcpr_get_email_settings')) {
            $settings = wcpr_get_email_settings();
            echo '<h4>Configuración actual</h4>';
            echo '<ul style="font-size: 12px; margin: 10px 0;">';
            echo '<li>Email 1: ' . ($settings['email_1_enabled'] === 'yes' ? '✓' : '✗') . ' - ' . $settings['email_1_delay'] . ' min</li>';
            echo '<li>Email 2: ' . ($settings['email_2_enabled'] === 'yes' ? '✓' : '✗') . ' - ' . $settings['email_2_delay'] . ' min</li>';
            echo '<li>Email 3: ' . ($settings['email_3_enabled'] === 'yes' ? '✓' : '✗') . ' - ' . $settings['email_3_delay'] . ' min</li>';
            echo '<li>Cancelación: ' . ($settings['cancel_enabled'] === 'yes' ? '✓' : '✗') . ' - ' . $settings['cancel_delay'] . ' min</li>';
            echo '</ul>';
        }

        // Acciones programadas pendientes
        if (function_exists('as_get_scheduled_actions')) {
            echo '<h4>Acciones programadas (WCPR)</h4>';
            $pending_actions = as_get_scheduled_actions([
                'group' => 'wc-payment-recovery',
                'status' => 'pending',
            ]);

            if (empty($pending_actions)) {
                echo '<p style="font-size: 12px; color: #666;">No hay acciones programadas</p>';
            } else {
                echo '<ul style="font-size: 12px; margin: 10px 0;">';
                foreach ($pending_actions as $action) {
                    try {
                        $scheduled_date = 'No disponible';
                        $schedule = $action->get_schedule();

                        if ($schedule) {
                            if (method_exists($schedule, 'getTimestamp')) {
                                $timestamp = $schedule->getTimestamp();
                                if ($timestamp) {
                                    $scheduled_date = wp_date('Y-m-d H:i:s', $timestamp);
                                }
                            } elseif (method_exists($action, 'get_date')) {
                                $timestamp = $action->get_date();
                                if ($timestamp) {
                                    $scheduled_date = wp_date('Y-m-d H:i:s', $timestamp);
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $scheduled_date = 'Error al obtener';
                    }
                    echo '<li>' . $action->get_hook() . ' - ' . $scheduled_date . '</li>';
                }
                echo '</ul>';
            }
        }
        ?>
    </div>
<?php
}

/**
 * Verificar integridad del plugin al cargar
 */
add_action('wcpr_loaded', function () {
    wcpr_log('Plugin cargado completamente y funcionando');
    if (!defined('WCPR_LOADED')) {
        define('WCPR_LOADED', true);
    }
});
