<?php
class SmsModel extends Model
{
    public function sendSms($data = array(), $send_time = '', $charset = 'utf-8', $id_code = '')
    {
        Sms::sendSms($data, $send_time, $charset, $id_code);
    }
}
