<?php
// Los hooks se registran ahora en wc.payment-recovery.php para asegurar que se registren temprano
// Estas son funciones helper para el carrito

add_action('init', 'wcpr_restore_cart');

function wcpr_restore_cart()
{

    if (!isset($_GET['recover_cart'])) return;

    $data = base64_decode($_GET['recover_cart']);

    $items = json_decode($data, true);

    if (!is_array($items)) {
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }

    WC()->cart->empty_cart();

    foreach ($items as $item) {

        $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
        $qty = isset($item['qty']) ? intval($item['qty']) : 1;
        $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
        $variation = isset($item['variation']) ? (array) $item['variation'] : array();

        if ($product_id > 0) {
            WC()->cart->add_to_cart($product_id, $qty, $variation_id, $variation);
        }
    }

    wp_safe_redirect(wc_get_cart_url());
    exit;
}

function wcpr_generate_cart_restore_link($order)
{

    $items = [];

    foreach ($order->get_items() as $item) {

        $product_id = $item->get_product_id();
        $qty = $item->get_quantity();
        $variation_id = $item->get_variation_id();
        $variation = array();

        // Capturar los atributos de la variación
        if ($variation_id > 0) {
            foreach ($item->get_meta_data() as $meta) {

                $key = $meta->key;

                // Filtrar solo los atributos (comienzan con "pa_")
                if (strpos($key, 'pa_') === 0) {

                    $variation[$key] = $meta->value;
                }
            }
        }

        $items[] = array(
            'product_id' => $product_id,
            'qty' => $qty,
            'variation_id' => $variation_id,
            'variation' => $variation
        );
    }

    $encoded = base64_encode(json_encode($items));

    return site_url('/?recover_cart=' . $encoded);
}


function wcpr_order_still_unpaid($order_id)
{

    $order = wc_get_order($order_id);

    if (!$order) return false;

    $status = $order->get_status();

    if ($status === 'pending' || $status === 'failed') {
        return true;
    }

    return false;
}


/**
 * Hooks de acción para enviar emails programados
 */
add_action('wcpr_send_email_1', function ($order_id) {
    wcpr_log('Ejecutando: wcpr_send_email_1', ['order_id' => $order_id]);
    try {
        $emails = WC()->mailer()->get_emails();
        if (!isset($emails['WC_Email_Payment_Recovery_1'])) {
            wcpr_log('ERROR: WC_Email_Payment_Recovery_1 no encontrado en emails registrados');
            return;
        }
        $emails['WC_Email_Payment_Recovery_1']->trigger($order_id);
        wcpr_log('Email 1 enviado exitosamente', ['order_id' => $order_id]);
    } catch (Exception $e) {
        wcpr_log('ERROR al enviar Email 1: ' . $e->getMessage(), ['order_id' => $order_id]);
    }
});

add_action('wcpr_send_email_2', function ($order_id) {
    wcpr_log('Ejecutando: wcpr_send_email_2', ['order_id' => $order_id]);
    try {
        $emails = WC()->mailer()->get_emails();
        if (!isset($emails['WC_Email_Payment_Recovery_2'])) {
            wcpr_log('ERROR: WC_Email_Payment_Recovery_2 no encontrado en emails registrados');
            return;
        }
        $emails['WC_Email_Payment_Recovery_2']->trigger($order_id);
        wcpr_log('Email 2 enviado exitosamente', ['order_id' => $order_id]);
    } catch (Exception $e) {
        wcpr_log('ERROR al enviar Email 2: ' . $e->getMessage(), ['order_id' => $order_id]);
    }
});

add_action('wcpr_send_email_3', function ($order_id) {
    wcpr_log('Ejecutando: wcpr_send_email_3', ['order_id' => $order_id]);
    try {
        $emails = WC()->mailer()->get_emails();
        if (!isset($emails['WC_Email_Payment_Recovery_3'])) {
            wcpr_log('ERROR: WC_Email_Payment_Recovery_3 no encontrado en emails registrados');
            return;
        }
        $emails['WC_Email_Payment_Recovery_3']->trigger($order_id);
        wcpr_log('Email 3 enviado exitosamente', ['order_id' => $order_id]);
    } catch (Exception $e) {
        wcpr_log('ERROR al enviar Email 3: ' . $e->getMessage(), ['order_id' => $order_id]);
    }
});
