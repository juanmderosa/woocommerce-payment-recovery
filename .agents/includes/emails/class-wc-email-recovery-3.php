<?php

if (! defined('ABSPATH')) exit;

class WC_Email_Payment_Recovery_3 extends WC_Email_Payment_Recovery_Base
{

    public function __construct()
    {

        $this->id = 'payment_recovery_3';
        $this->title = 'Payment Recovery 3';
        $this->description = 'Último recordatorio antes de cancelar el pedido. (tercer e-mail)';

        $this->heading = 'Último recordatorio';
        $this->subject = 'Últimas horas para confirmar tu pedido ';

        parent::__construct();
    }

    protected function get_message()
    {

        return "Tus prendas siguen reservadas, pero queda poco tiempo para completar tu pago.

Si todavía querés ese look, este es el momento de confirmarlo antes de que el stock vuelva a estar disponible.

Muchos de nuestros productos tienen stock limitado, por lo que pueden agotarse rápidamente.";
    }
}
