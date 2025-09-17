<?php

class AppPushModel extends Model
{
    public function send($order, $order_type) {
        import('@.ORG.Apppush');
        $apppush = new Apppush();
        fdump_api(['order' => $order, 'order_type' => $order_type], 'appPushModelLog', 1);
        return $apppush->send($order, $order_type);
    }
}