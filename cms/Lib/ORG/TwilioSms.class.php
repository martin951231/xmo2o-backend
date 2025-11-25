<?php
/**
 * Created by Pigcms.
 * User: JJC.
 * Date: 2019/3/29.
 * Time: 14:43.
 */
require_once __DIR__ . '/twilio_sms/Twilio/autoload.php';
use Twilio\Rest\Client;

class TwilioSms
{

    private $config = array(
        'account_sid' => '',
        'auth_token' => '',
        'from' => '',
    );

    /**
     * TwilioSms constructor.
     *
     */
    public function __construct()
    {
        $this->config['account_sid']  =  C('config.twilio_account_sid');
        $this->config['auth_token']   =  C('config.twilio_auth_token');
        $this->config['from']         =  C('config.twilio_number');
        $this->twilio = new Client($this->config['account_sid'], $this->config['auth_token']);
    }

    /**
     * @desc 判断短信是否为中文
     * @param $str
     * @return bool
     */
    private function isChinese($str){
        if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $str)>0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @desc Unicode中文转码
     * @param $str
     * @return string
     */
    private function UnicodeEncode($str){
        //split word
        preg_match_all('/./u',$str,$matches);

        $unicodeStr = "";
        foreach($matches[0] as $m){
            //拼接
            $unicodeStr .= "&#".base_convert(bin2hex(iconv('UTF-8',"UCS-4",$m)),16,10);
        }
        return $unicodeStr;
    }

    /**
     * @desc 发送短信
     * @param  $to:短信接收人手机号,指定国家码，如：+8613367891234
     * @param  $content:短信内容,必须是英文或者数字，否则无法发送，但接口返回状态成功
     * @return bool|string
     */
    public function send($data){
        $sms_sign=C('config.sms_sign');
        if (!empty($data['o2o_village_id'])){
            $arr = D('House_village')->checkVillageSms($data['o2o_village_id'], 1, true);
            if (! $arr) {
                TwilioSms::add_sms_record($data,-2);
                return false;
            }
            if (!empty($arr['sms_sign'])) {
                $sms_sign = $arr['sms_sign'];
            }
        }
        $Content = '['.trim($sms_sign).']'.$data['content'];
        $tel = '+'.($data['nationCode'] ? $data['nationCode'] : C('config.twilio_sms_default_country')).$data['mobile'];

        try{
            $content = $this->twilio->messages->create($tel,array("from" => $this->config['from'], "body" => $Content));
            $sms_status = 1;
        }catch(Exception $e){
			fdump($e,'e',true);
            $sms_status = 0;
            $content = $e->getMessage();
        }
        TwilioSms::add_sms_record($data,$sms_status);

        fdump_sql($content,'twillio_sms_error_log');

        return $content;
    }
    //增加到本地数据库记录
    public function add_sms_record($data,$status){
        $row = array(
            'mer_id'     =>  isset($data['o2o_mer_id']) ? intval($data['o2o_mer_id']) : 0,
            'uid'        =>  isset($data['o2o_uid']) ? intval($data['o2o_uid']) : 0,
            'store_id'   =>  isset($data['o2o_store_id']) ? intval($data['o2o_store_id']) : 0,
            'time'       =>  time(),
            'phone'      =>  isset($data['mobile']) ? $data['mobile'] : '',
            'text'       =>  isset($data['content']) ? $data['content'] : '',
            'status'     =>  $status,
            'type'       =>  isset($data['o2o_type']) ? $data['o2o_type'] : '',
            'sendto'     =>  isset($data['o2o_sendto']) ? $data['o2o_sendto'] : '',
            'village_id' =>  isset($data['o2o_village_id']) ? $data['o2o_village_id'] : 0,
            'village_send_id' => isset($data['village_send_id']) ? intval($data['village_send_id']) : 0
        );
        $add = D('Sms_record')->add($row);
        if (! $add) {
            fdump_sql(['TwilioSms', $row],'sms_error_log');
        }
        // 如果是社区群发处理社区群发
        if (0 == $status && intval($row['village_send_id']) > 0 && $data['o2o_type'] == 'house_group_send') {
            // 正常发送添加成功发送数量
            M('House_sms_group_send')->where(array('send_id' => $data['village_send_id']))->setInc('success_send_num', 1);
        }
        $village_id = $row['o2o_village_id'];
        if (intval($village_id) > 0) {
            // 发送不论是否成功更正下社区拥有短信数量
            D('House_village')->correctVillageSmsNumber($data['village_send_id']);
        }
        return true;
    }
}






