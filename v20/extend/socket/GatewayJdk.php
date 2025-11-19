<?php
/**
 * @author : liukezhu
 * @date : 2022/3/14
 */

namespace socket;
require_once __DIR__.'/Gateway.php';
Gateway::$registerAddress = '127.0.0.1:1236';
class GatewayJdk
{
    /**
     * 发送消息
     * @author: liukezhu
     * @date : 2022/3/14
     * @param $client_id
     * @param $data
     * @param int $type
     * @return array
     * @throws \Exception
     */
    public static function send($client_id,$data,$type=0){
        $data=json_encode($data, JSON_UNESCAPED_UNICODE);
        $checkOnline=self::checkOnline($client_id);
        if(!$checkOnline['error']){
            return $checkOnline;
        }
        if($type == 1){
            Gateway::sendToAll($data);
        }else{
            Gateway::sendToClient($client_id,$data);
        }
        return ['error'=>true,'msg'=>'发送成功'];
    }

    /**
     * 校验$client_id是否在线
     * @author: liukezhu
     * @date : 2022/3/14
     * @param $client_id
     * @return array
     */
    public static function checkOnline($client_id){
        try{
            $result=Gateway::isOnline($client_id);
            if($result == 1){
                return ['status'=>true,'msg'=>'设备在线'];
            }else{
                return ['status'=>false,'msg'=>'设备不在线'];
            }
        }catch (\Exception $e){
            return ['status'=>false,'msg'=>'设备不在线'];
        }
    }

    /**
     * 获取所有在线client_id列表
     * @author: liukezhu
     * @date : 2022/3/15
     * @return array
     */
    public function getAllClientIdList(){
        return GateWay::getAllClientIdList();
    }

    /**
     * 获取绑定$client_id
     * @author: liukezhu
     * @date : 2022/3/21
     * @param $client_id
     * @return mixed
     */
    public function getBindClientId($client_id){
      return Gateway::getUidByClientId ($client_id);
    }

}