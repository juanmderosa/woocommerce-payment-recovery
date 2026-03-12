<?php

function wcpr_schedule_emails($order_id)
{
    if (!wcpr_can_schedule_actions()) {
        wcpr_log('ERROR: ActionScheduler no disponible');
        return;
    }

    if (!wcpr_is_valid_order($order_id)) {
        wcpr_log('ERROR: Orden no válida en schedule_emails', ['order_id' => $order_id]);
        return;
    }

    $settings = wcpr_get_email_settings();
    wcpr_log('Configuración de emails cargada', $settings);

    // Email 1
    if ($settings['email_1_enabled'] === 'yes') {
        $timestamp_1 = time() + ($settings['email_1_delay'] * 60);
        as_schedule_single_action(
            $timestamp_1,
            'wcpr_send_email_1',
            array($order_id),
            'wc-payment-recovery'
        );
        wcpr_log('Email 1 programado', ['order_id' => $order_id, 'delay_minutes' => $settings['email_1_delay'], 'timestamp' => $timestamp_1]);
    }

    // Email 2
    if ($settings['email_2_enabled'] === 'yes') {
        $timestamp_2 = time() + ($settings['email_2_delay'] * 60);
        as_schedule_single_action(
            $timestamp_2,
            'wcpr_send_email_2',
            array($order_id),
            'wc-payment-recovery'
        );
        wcpr_log('Email 2 programado', ['order_id' => $order_id, 'delay_minutes' => $settings['email_2_delay'], 'timestamp' => $timestamp_2]);
    }

    // Email 3
    if ($settings['email_3_enabled'] === 'yes') {
        $timestamp_3 = time() + ($settings['email_3_delay'] * 60);
        as_schedule_single_action(
            $timestamp_3,
            'wcpr_send_email_3',
            array($order_id),
            'wc-payment-recovery'
        );
        wcpr_log('Email 3 programado', ['order_id' => $order_id, 'delay_minutes' => $settings['email_3_delay'], 'timestamp' => $timestamp_3]);
    }

    // Cancelación automática
    if ($settings['cancel_enabled'] === 'yes') {
        $timestamp_cancel = time() + ($settings['cancel_delay'] * 60);
        as_schedule_single_action(
            $timestamp_cancel,
            'wcpr_cancel_order',
            array($order_id),
            'wc-payment-recovery'
        );
        wcpr_log('Cancelación automática programada', ['order_id' => $order_id, 'delay_minutes' => $settings['cancel_delay'], 'timestamp' => $timestamp_cancel]);
    }
}
