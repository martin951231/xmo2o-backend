<?php

class DeviceCustomize
{
    protected $appid='';
    protected $appSecret='';
    public function __construct(){

        $this->appid='2L0J23130p630g8U1i7w455K1g8p';
        $this->appSecret= '5b01d2d54d9911f928ff42eb3c14f6ea';

    }

    public function curlPost($url,$params,$token='',$method='POST'){
        $head=array(
            'Content-Type: application/json'
        );
        if(!empty($token)){
            $head[]='token:'.$token;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS =>json_encode($params,JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER =>$head,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response ? json_decode($response,true) : false;
    }

    /**
     * 获取token
     * @author: liukezhu
     * @date : 2021/6/9
     */
    public function getToken(){
        $db_access_token_common_expires = M('Access_token_common_expires');
        $where_access_token = [
            'type' =>'dzbb_token',
            'access_id' =>$this->appid.'_'.$this->appSecret,
        ];
        $access_token_info = $db_access_token_common_expires->where($where_access_token)->find();
        $time = time();
        if ($access_token_info && intval($access_token_info['access_token_expire']) > $time) {
            return array('error' => true, 'data' =>$access_token_info['access_token']);
        }
        else{
            list($msec, $sec) = explode(' ', microtime());
            $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            $sign=md5($this->appid.$msectime.$this->appSecret);
            $arr=array(
                'appId'=>$this->appid,
                'timestamp'=>$msectime,
                'sign'=>$sign
            );
            $url='http://demo.iot.lanniuyun.com:9000/v1/auth/token';
            $rel=$this->curlPost($url,$arr);
            if($rel['result'] != 1){
                return array('error' => false, 'data' =>'token获取失败');
            }
            $set = array(
                'access_token' => $rel['data'],
                'access_token_expire' => $time + 82800,  //有效期23小时
            );
            if ($access_token_info) {
                $db_access_token_common_expires->where($where_access_token)->data($set)->save();
            } else {
                $set['type'] = $where_access_token['type'];
                $set['access_id'] = $where_access_token['access_id'];
                $db_access_token_common_expires->data($set)->add();
            }
            return array('error' => true, 'data' =>$set['access_token']);
        }
    }


    /**
     *新增设备
     * @author: liukezhu
     * @date : 2021/7/8
     * @param $devicesn
     * @param $name
     * @return array
     */
    public function addDevice($devicesn,$name){
        $param=array(
            'devicesn'=>$devicesn,
            'name'=>$name,
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/device/addDev';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$name,$rel],'d4/addDevice',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     * 查询设备
     * @author: liukezhu
     * @date : 2021/7/8
     * @param $devicesn
     */
    public function queryDevice($devicesn){
        $param=array(
            'devicesn'=>$devicesn,
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/device/queryDevice';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$rel],'d4/queryDevice',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     * 删除设备
     * @author: liukezhu
     * @date : 2021/7/8
     * @param $devicesn
     */
    public function delDevice($devicesn){
        $param=array(
            'devicesn'=>$devicesn,
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/device/delDev';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$rel],'d4/delDevice',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     * 卡号新增接口
     * @author: liukezhu
     * @date : 2021/7/8
     * @param $devicesn 设备号
     * @param $cardNo   卡号
     * @param $endTime  失效时间 2020-12-12 12:12:12
     * @param int $type 1白名单,2黑名单
     */
    public function addCard($devicesn,$cardNo,$endTime,$type='1'){
        $param=array(
            'devicesns'=>$devicesn,
            'data'=>array(
                array(
                    'cardNo'=>$cardNo,
                    'endTime'=>$endTime,
                    'type'=>$type
                )
            )
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/card/addCard';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$cardNo,$endTime,$rel],'d4/addCard',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     * 删除卡号
     * @author: liukezhu
     * @date : 2021/7/8
     * @param $devicesn  设备号
     * @param $cardNo 卡号
     * @return array
     */
    public function delCard($devicesn,$cardNo){
        $param=array(
            'devicesns'=>$devicesn,
            'cardNo'=>$cardNo,
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/person/delCard';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$cardNo,$rel],'d4/delCard',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     * 添加/编辑 人员头像
     * @author: liukezhu
     * @date : 2021/7/9
     * @param $devicesn 设备号
     * @param array $data guid 业务平台人员id url头像 endTime 到期时间 //2020:11:11 12:12:12
     * @return array
     */
    public function addPerson($devicesn,$data=[]){
        $param=array(
            'devicesns'=>$devicesn,
            'data'=>$data
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/face/addPersonAndFace';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$data,$rel],'d4/addPerson',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     *删除人脸
     * @author: liukezhu
     * @date : 2021/7/9
     * @param $devicesn 设备号
     * @param $guid array    业务平台人员id
     * @return array
     */
    public function delFace($devicesn,$guid=''){
        $param=array(
            'devicesns'=>$devicesn,
            'personGuids'=>$guid
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/face/delface';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$guid,$rel],'d4/delFace',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     *重启设备
     * @author: liukezhu
     * @date : 2021/7/9
     */
    public function restartDev($devicesn){
        $param=array(
            'devicesn'=>$devicesn
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/device/restartDev';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$rel],'d4/restartDev',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

    /**
     *远程开门
     * @author: liukezhu
     * @date : 2021/7/9
     * @param $devicesn
     */
    public function openDevice($devicesn){
        $param=array(
            'devicesn'=>$devicesn
        );
        $token=$this->getToken();
        if(!$token['error']){
            return ['error'=>false,'data'=>$token['data']];
        }
        $url='http://demo.iot.lanniuyun.com:9000/v1/device/open';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([$devicesn,$rel],'d4/openDevice',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }

}


