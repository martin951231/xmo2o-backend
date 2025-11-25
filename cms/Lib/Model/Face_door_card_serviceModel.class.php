<?php
//远程开门
class Face_door_card_serviceModel extends Model {
//    private $url = 'https://iot.hutuiot.com:8018';
//    private $port = '8018';
    //远程开门
    public function openDoor($device_id,$now_village_bind)
    {
        $device_info = D("House_face_device")->where(['device_id'=>$device_id])
            ->field("device_sn,village_id,floor_id,public_area_id,device_domain")->find();
        if(!$device_info){
            return false;
        }
        $identity = $device_info['device_sn'];
        $url = $device_info['device_domain'];
        //检查设备是否在线
        $certification = $this->certification($identity,$url);
        if($certification['success'] == true){
            if($certification['status'][0] == 1){
                $device_status = 1;
            }else{
                $device_status = 2;
            }
            D('House_face_device')->where(['device_id'=>$device_id])->data(['device_status'=>$device_status])->save();
            //设备在线 就去开门
            if($certification['status'][0] == 1){
                $res = $this->remoteOpenDoor($identity,$url);
                if($res['success'] == true){
                    $openInfo = [
                        'identity'=>$identity,
                        'log_status'=>0,
                    ];
                    $this->openDoorLog($openInfo, $now_village_bind);
                    return true;
                }
            }else{
                //开门失败
                $openInfo = [
                    'identity'=>$identity,
                    'log_status'=>1,
                ];
                $this->openDoorLog($openInfo, $now_village_bind);
                return false;
            }
        }else{
            //开门失败
            $openInfo = [
                'identity'=>$identity,
                'log_status'=>1,
            ];
            $this->openDoorLog($openInfo, $now_village_bind);
            return false;
        }
    }
    //处理开门记录
    public function openDoorLog($data,$user_bind=[])
    {
        if($data) {
            $log_status = $data['log_status'];//开门成功
            $log_time = time();
            $log_from = 2;
            $device_info = D('House_face_device')->where(['device_sn' => $data['identity']])->field('device_id,village_id,floor_id,public_area_id')->find();
            $device_id = $device_info['device_id'];
            $map['village_id'] = $device_info['village_id'];
            if ($device_info['floor_id'] && $device_info['floor_id'] <> '-1') {
                $foor_id = explode(',', $device_info['floor_id']);
                $map['foor_id'] = ['in', $foor_id];
            }
            if ($device_info['floor_id'] == '-1') {
                $log_name = D('House_village_public_area')->where(['public_area_id' => $device_info['public_area_id']])->getField('public_area_name');
            } else {
                $floor_data = D('House_village_floor')->where(['floor_id' => $device_info['floor_id']])->field('floor_name,single_id')->find();
                if ($floor_data) {
                    $single_name = D('house_village_single')->where(['id' => $floor_data['single_id']])->getField('single_name');
                    if (preg_match('/[\x7f-\xff]/', $single_name . $floor_data['floor_name'])) {
                        $log_name = $single_name . $floor_data['floor_name'];
                    } else {
                        $log_name = $single_name . '号楼-' . $floor_data['floor_name'] . '单元';
                    }
                } else {
                    $log_name = '--';
                }
            }
            $user_data = [
                'uid' => isset($user_bind['uid']) ? $user_bind['uid'] : 0,
                'log_bind_id' => isset($user_bind['pigcms_id']) ? $user_bind['pigcms_id'] : 0,
                'log_name' => $log_name,
                'log_from' => $log_from,
                'log_business_id' => $device_info['village_id'] ? $device_info['village_id'] : 0,
                'log_more_id' => $user_bind['floor_id'] ? $user_bind['floor_id'] : 0,
                'device_id' => $device_id,
                'log_status' => $log_status,
                'log_detail' => serialize($data),
                'log_time' => $log_time,
            ];
            $user_data['add_time']=time();
            $res = D('House_user_log')->add($user_data);
        }
        return true;
    }
    //-----------------------------------------------以下是平台发给设备------------------------------------------//
    //远程开门
    public function remoteOpenDoor($identity,$urls)
    {
        $url = $urls.'/dp/ly/remoteOpenDoor.ext';
        if(!$identity){
            return false;
        }
        $data = json_encode([
            'identity'=>$identity,
            'open'=>1,//开门
        ]);
        $res = $this->curlPost($url,$data);
        return $res;
    }
    //查看设备是否在线
    public function certification($identity,$urls)
    {
        $url = $urls.'/dp/dev/pullDevstatus.ext';
        //identity 设备序列号;
        $data = json_encode([
            "identity"=>[$identity],
        ]);
        $res = $this->curlPost($url,$data);
        return $res;
    }
    /**
     * Notes:查询设备开门记录
     * @param $identity
     * @param $starttime
     * @param $endtime
     * @param string $cardno
     * @param int $start
     * @param int $limit
     * { "identity":"对讲机序列号"
     * "starttime":"开始时间，格式 yyyyMMddHHmmss"
     * "endtime":"结束时间，格式 yyyyMMddHHmmss"
     * "cardno": "卡号",//卡号,为空则所有卡号的
     * "start":"第几条开始",
     * "limit":"获取条数" }
     * @return bool|mixed|string
     * @datetime: 2021/1/7 10:05
     */
    public function lookOpenLog($identity,$urls,$starttime,$endtime,$cardno='',$start=0,$limit=100)
    {
        $url = $urls.'/dp/mj/queryOpenRecords.ext';
        //identity 设备序列号;
        $data = json_encode([
            "identity"=>$identity,
            "starttime"=>$starttime,
            "endtime"=>$endtime,
            "cardno"=> $cardno,//卡号,为空则所有卡号的
            "start"=>$start,
            "limit"=>$limit,
        ]);
        $res = $this->curlPost($url,$data);
        return $res;
    }
    public function curlPost($url,$data,$type=0,$timeout=10){
        $ch = curl_init();
        if($type == 0){
            $headers[] = "Content-Type: text/plain;charset=utf8";
        }else{
            $headers[] = "Content-Type: application/json;charset=utf8";
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }
    function curlGet($url){
        $ch = curl_init();
        $headers[] = "Content-Type: text/plain;charset=utf8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($temp, true);
        return $result;
    }
}
