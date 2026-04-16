<?php

/**
 * Sistema de diagnóstico - Se ejecuta si el plugin no se carga
 */

// Esto corre SIN verificaciones, para diagnosticar qué está mal
add_action('admin_notices', 'wcpr_diagnose_issues', 999);

function wcpr_diagnose_issues()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Verificar si el plugin se cargó
    if (function_exists('wcpr_get_email_settings')) {
        // Plugin se cargó bien, salir
        return;
    }

    // El plugin NO se cargó, mostrar diagnóstico
    $checks = [];

    // Check 1: WooCommerce
    $checks['WooCommerce'] = [
        'status' => class_exists('WooCommerce'),
        'required' => true
    ];

    // Check 2: WC_Email
    $checks['Clases WooCommerce'] = [
        'status' => class_exists('WC_Email'),
        'required' => true
    ];

    // Check 3: ActionScheduler
    $checks['ActionScheduler'] = [
        'status' => function_exists('as_schedule_single_action'),
        'required' => true
    ];

    // Check 4: Funciones WC
    $checks['Funciones WC'] = [
        'status' => function_exists('wc_get_order'),
        'required' => true
    ];

    // Contar problemas
    $problems = 0;
    foreach ($checks as $check) {
        if ($check['required'] && !$check['status']) {
            $problems++;
        }
    }

    if ($problems === 0) {
        // Todo está disponible pero el plugin aún no se cargó
        // Esto podría ser un problema de timing
        return;
    }

    // Mostrar información de diagnóstico
?>
    <div class="notice notice-error is-dismissible" style="background: #fff3cd; border-left: 4px solid #ff9800;">
        <p>
            <strong style="color: #ff9800;">
                ⚠️ WooCommerce Payment Recovery - No se pudo cargar
            </strong>
        </p>
        <p>El plugin requiere las siguientes dependencias:</p>
        <ul style="margin: 10px 0 10px 20px; list-style: disc inside;">
            <?php
            foreach ($checks as $name => $check) {
                if ($check['required']) {
                    $icon = $check['status'] ? '✓' : '✗';
                    $style = $check['status'] ? 'color: green;' : 'color: red; font-weight: bold;';
                    echo '<li style="' . $style . '">' . $icon . ' ' . esc_html($name) . '</li>';
                }
            }
            ?>
        </ul>

        <p style="margin-top: 15px; font-size: 12px; color: #666;">
            <strong>Solución:</strong><br>
            1. Asegúrate de que WooCommerce 7.0+ esté instalado y activo<br>
            2. Verifica en <strong>Herramientas > Estado del sitio</strong> que ActionScheduler esté disponible<br>
            3. Desactiva y reactiva el plugin<br>
            4. Si el problema persiste, contacta a tu proveedor de hosting
        </p>
    </div>
<?php
}
