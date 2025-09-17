<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      D4设备应用
 */

namespace app\community\model\service\Device;


use app\community\model\db\AccessTokenCommonExpires;

class D4FaceDeviceService
{
    protected $appid='';
    protected $appSecret='';
    protected $domain='';
    public function __construct(){
        $this->appid='2f0d2Q220y1U0h5p1n9c4t9Y4j3z';
        $this->appSecret= 'c30b6eea884bb24c0049263f2b8226ec';
        $this->domain= 'http://api.iot.lanniuyun.com:9000';
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
     */
    public function getToken(){
        $db_access_token_common_expires = (new AccessTokenCommonExpires());
        $where_access_token = [
            'type' =>'d4_device_token',
            'access_id' =>$this->appid.'_'.$this->appSecret,
        ];
        $access_token_info = $db_access_token_common_expires->where($where_access_token)->find();
        $time = time();
        if ($access_token_info && intval($access_token_info['access_token_expire']) > $time) {
            return array('error' => true, 'data' =>$access_token_info['access_token']);
        }
        else{
            list($msec, $sec) = explode(' ', microtime());
            $msectime =(string) intval((floatval($msec) + floatval($sec)) * 1000);
            $sign=md5($this->appid.$msectime.$this->appSecret);
            $arr=array(
                'appId'=>$this->appid,
                'timestamp'=>$msectime,
                'sign'=>$sign
            );
            $url=$this->domain.'/v1/auth/token';
            $rel=$this->curlPost($url,$arr,'','GET');
            if($rel['result'] != 1){
                fdump_api([__LINE__,json_encode($arr,JSON_UNESCAPED_UNICODE),$url,$rel],'d4/getToken',1);
                return array('error' => false, 'data' =>'token获取失败');
            }
            $set = array(
                'access_token' => $rel['data'],
                'access_token_expire' => $time + 82800,  //有效期23小时
            );
            if ($access_token_info) {
                $db_access_token_common_expires->where($where_access_token)->update($set);
            } else {
                $set['type'] = $where_access_token['type'];
                $set['access_id'] = $where_access_token['access_id'];
                $db_access_token_common_expires->insert($set);
            }
            return array('error' => true, 'data' =>$set['access_token']);
        }
    }

    /**
     * 添加/编辑 人员头像
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
        $url=$this->domain.'/v1/face/addPersonAndFace';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([__LINE__,$devicesn,$data,$rel],'d4/addPerson',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg'],'ret'=>$rel];
        }
        return ['error'=>true,'data'=>$rel,'ret'=>$rel];
    }

    /**
     * 删除卡号
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
        $url=$this->domain.'/v1/person/delCard';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([__LINE__,$devicesn,$cardNo,$rel],'d4/delCard',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }


    /**
     * 卡号新增接口
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
        $url=$this->domain.'/v1/card/addCard';
        $rel=$this->curlPost($url,$param,$token['data']);
        fdump_api([__LINE__,$devicesn,$cardNo,$endTime,$rel],'d4/addCard',1);
        if($rel['result'] != 1){
            return ['error'=>false,'data'=>$rel['msg']];
        }
        return ['error'=>true,'data'=>$rel['data']];
    }
}