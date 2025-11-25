<?php
/*
 * 平台配送管理
 *
 */

class Platform_deliveryAction extends BaseAction{
    public function index(){
        $mer_id = $this->merchant_session['mer_id'];
        $merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
        if (empty($merchant['appid']) || empty($merchant['key'])) {
            // 生成appid 和 key
            $info = $this->create_info();
            $save = M('Merchant')->where(array('mer_id' => $mer_id))->save($info);
        } else {
            $info = array(
                'appid' => $merchant['appid'],
                'key' => $merchant['key']
            );
        }
        $this->assign('info',$info);

		$this->display();
    }

    private function create_info() {
        // 生成appid 和 key  appid  16位  key 32位
        $key_time = date("ymdHis");
        $time = time();
        $time = sprintf("%010d",$time);
        $key_id = $key_time.$time.rand(10,99).sprintf("%08d",$this->merchant_session['mer_id']);
        $key = MD5($key_id);
        $appid_time = time();
        $appid_time = sprintf("%010d",$appid_time);
        $a_time = date("ymd");
        $appid = $a_time . $appid_time;
        $info = array(
            'appid' => $appid,
            'key' => $key
        );
        return $info;
    }

}