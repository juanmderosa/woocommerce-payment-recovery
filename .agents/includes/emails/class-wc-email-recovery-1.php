<?php

if (! defined('ABSPATH')) exit;

class WC_Email_Payment_Recovery_1 extends WC_Email_Payment_Recovery_Base
{

    public function __construct()
    {

        $this->id = 'payment_recovery_1';
        $this->title = 'Payment Recovery 1';
        $this->description = 'Primer Email enviado después del abandono del pago.';

        $this->heading = 'Tu pedido te está esperando';
        $this->subject = 'Tu pedido te está esperando';

        parent::__construct();
    }

    protected function get_message()
    {

        return "Notamos que tu pago todavía no se completó, pero tu pedido sigue reservado para vos.

Las prendas que elegiste siguen esperándote para que puedas finalizar tu compra cuando quieras";
    }
}
