<?php
/*
 * 智能垃圾回收
 * 
 */
namespace garbage_collection;
final class GarbageCollection
{
    public $garbage_collection_url;//请求地址
    public $garbage_collection_user_name;//用户名
    public $garbage_collection_password;//密码
    public $accessToken;//接入 Token

    public function __construct()
    {
        $this->garbage_collection_url = cfg('garbage_collection_url');
        $this->garbage_collection_user_name = cfg('garbage_collection_user_name');
        $this->garbage_collection_password = cfg('garbage_collection_password');
        $this->getAccessToken();
    }
    
    private  function getAccessToken(){
        $url = $this->garbage_collection_url.'/auth/agent/login';
        $postParams = [
            'username'=>$this->garbage_collection_user_name,
            'password'=>$this->garbage_collection_password
        ];
        $result = http_request($url, 'POST',$postParams);
        $res = json_decode($result[1],true);
        if($res && isset($res['accessToken'])) {
            $this->accessToken = $res['accessToken'];
        }else{
            fdump_api($result,'garbage_collection/getAccessTokenErr');
        }
        return true;
    }
    
    /**
     * 拉取设备列表
     */
    public function pullMachine($page=0,$size=10)
    {
        if(!$this->accessToken || !$this->garbage_collection_url){
            return false;
        }
        $url = $this->garbage_collection_url.'/api/kitchen-refuse-api/open/machine-list?page='.$page.'&size='.$size;
        $postParams = [
            'username'=>$this->garbage_collection_user_name,
            'password'=>$this->garbage_collection_password
        ];
        $headers = [
            "Access-Token:".$this->accessToken
        ];
        $result = http_request($url, 'GET',json_encode($postParams),$headers);
        $res = json_decode($result[1],true);
        fdump_api($result,'garbage_collection/pullMachine',1);
        if($res || $res===[]) {
            return $res;
        }
        return false;
    }
    
    /**
     * 新增用户
     */
    public function saveUser($param)
    {
        if(!$this->accessToken || !$this->garbage_collection_url){
            return false;
        }
        $url = $this->garbage_collection_url.'/api/common-api/open/user-save';
        $postParams = [
            'type'=>$param['type'],//用户账号类型1:手机号2:卡号3:二维码4:RFID
            'account'=>$param['account'],//用户账号
            'communityId'=>$param['communityId'],//设备小区主键
            'name'=>$param['name'],//姓名
            'phone'=>$param['phone'],//联系方式
            'building'=>$param['building'],//楼栋号
            'unit'=>$param['unit'],//unit
            'room'=>$param['room'],//room
            'points'=>$param['points'],//积分，保留1位小数，默认0
            'faceUrl'=>$param['faceUrl'],//人脸照片url
        ];
        $headers = [
            "Access-Token:".$this->accessToken
        ];
        $result = http_request($url, 'POST',$postParams,$headers);
        $res = json_decode($result[1],true);
        fdump_api([$postParams,$result[1]],'garbage_collection/saveUser',1);
        if(!isset($res['code']) || $res['code']!=0) {
            fdump_api([$postParams,$result[1]],'garbage_collection/saveUserErr',1);
        }
        return $res;
    }
    
    /**
     * 编辑用户
     */
    public function editUser($param)
    {
        if(!$this->accessToken || !$this->garbage_collection_url){
            return false;
        }
        $url = $this->garbage_collection_url.'/api/common-api/open/user-edit';
        $postParams = [
            'type'=>$param['type'],//用户账号类型1:手机号2:卡号3:二维码4:RFID
            'account'=>$param['account'],//用户账号
            'name'=>$param['name'],//姓名
            'phone'=>$param['phone'],//联系方式
            'building'=>$param['building'],//楼栋号
            'unit'=>$param['unit'],//unit
            'room'=>$param['room'],//room
            'faceUrl'=>$param['faceUrl'],//人脸照片url
        ];
        $headers = [
            "Access-Token:".$this->accessToken
        ];
        $result = http_request($url, 'POST',$postParams,$headers);
        $res = json_decode($result[1],true);
//        $res = [
//            'code'=>0,
//            'data'=>12,
//        ];
        fdump_api([$postParams,$result[1]],'garbage_collection/editUser',1);
        if(!isset($res['code']) || $res['code']!=0) {
            fdump_api([$postParams,$result[1]],'garbage_collection/editUserErr',1);
        }
        return $res;
    }
    /**
     * 拉取垃圾类型列表
     */
    public function pullGarbageCategory($page=0,$size=10)
    {
        if(!$this->accessToken || !$this->garbage_collection_url){
            return false;
        }
        $url = $this->garbage_collection_url.'/api/kitchen-refuse-api/open/garbage-category';
        $postParams = [
            'username'=>$this->garbage_collection_user_name,
            'password'=>$this->garbage_collection_password
        ];
        $headers = [
            "Access-Token:".$this->accessToken
        ];
        $result = http_request($url, 'GET',json_encode($postParams),$headers);
        $res = json_decode($result[1],true);
        fdump_api($result,'garbage_collection/pullGarbageCategory',1);
        if($res || $res===[]) {
            return $res;
        }
        return false;
    }
}
?>