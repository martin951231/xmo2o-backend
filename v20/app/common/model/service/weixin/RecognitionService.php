<?php
/**
 * 微信授权相关
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/7 11:26
 */

namespace app\common\model\service\weixin;
use app\common\model\db\Recognition as RecognitionModel;
use net\Http as Http;
use think\Exception;

class RecognitionService {
    public $recognitionModel = null;

    //微信生成二维码前缀
    const TICKET_PREFIX = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=';

    public function __construct()
    {
        $this->recognitionModel = new RecognitionModel();
    }

    /**
     * 生成登录用的临时二维码
     * @param $id
     * @return array
     */

    public function getLoginQrcode($param=[]){
        $appid     = cfg('wechat_appid');
        $appsecret = cfg('wechat_appsecret');

        if(empty($appid) || empty($appsecret)){
            throw new \think\Exception("请联系管理员配置【AppId】【 AppSecret】");
        }
        $qrcodeType = $param['qrcode_type'] ?? '';
        // 当前时间
        $nowTime = time();

        $loginQrcodeService = new LoginQrcodeService();

        $where = [
            'add_time','<' ,$nowTime-604800
        ];
        $loginQrcodeService->del($where);

        $data = [];
        $data['uid'] = 0;
        $data['ticket'] = '';
        $data['add_time'] = $nowTime;
        if($qrcodeType){
            $data['type'] = $qrcodeType;
        }

        $qrcodeId = $loginQrcodeService->save($data);
        if(empty($qrcodeId)){
            throw new \think\Exception(L_('获取二维码错误！无法写入数据到数据库。请重试。'));
        }

        $httpObj = new Http();

        //微信授权获得access_token
        $accessTokenArray = (new \app\common\model\service\weixin\AccessTokenExpiresService)->getAccessToken();
        if ($accessTokenArray['errcode']) {
            throw new \think\Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] .',微信返回错误信息：' . $accessTokenArray['errmsg']);
        }

        $accessToken = $accessTokenArray['access_token'];

        $qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$accessToken;
        $post_data['expire_seconds'] = 2592000;
        $post_data['action_name'] = 'QR_SCENE';
        $post_data['action_info']['scene']['scene_id'] = $qrcodeId;

        $json = $httpObj->curlPost($qrcode_url,json_encode($post_data));
        if (!$json['errcode']){
            $condition['id'] = $qrcodeId;
            $data['ticket'] = $json['ticket'];
            if($loginQrcodeService->updateThis($condition,$data)){
                $returnArr = [
                    'qrcode_id'=>$qrcodeId,
                    'qrcode'=>self::TICKET_PREFIX.urlencode($json['ticket'])
                ];
                return $returnArr;
            }else{
                $loginQrcodeService->del($condition);
                throw new \think\Exception(L_('获取二维码错误！保存二维码失败。请重试。'));
            }
        }else{
            $condition['id'] = $qrcodeId;
            $loginQrcodeService->del($condition);
            throw new \think\Exception(L_('发生错误：错误代码 ').$json['errcode'].L_('，微信返回错误信息：').$json['errmsg']);
        }

    }

    //生成登录用的临时二维码
    public function getAdminQrcode(){
        $appid     = cfg('wechat_appid');
        $appsecret = cfg('wechat_appsecret');

        if(empty($appid) || empty($appsecret)){
            throw new \think\Exception("请联系管理员配置【AppId】【 AppSecret】");
        }

        // 当前时间
        $now_time = time();

        // 二维码记录表
        $adminQrcodeService = new AdminQrcodeService();

        // 删除记录
        $where[] = ['add_time','<',$now_time-604800];
        $adminQrcodeService->del($where);

        // 添加自增属性
        $adminQrcodeService->setAutoIncrement();

        // 添加记录
        $saveData['add_time'] = $now_time;
        $qrcodeId = $adminQrcodeService->save($saveData);
        if(empty($qrcodeId)){
            throw new \think\Exception("获取二维码错误！无法写入数据到数据库。请重试。");
        }

        $httpObj = new Http();

        //微信授权获得access_token
        $accessTokenArray = (new \app\common\model\service\weixin\AccessTokenExpiresService)->getAccessToken();
        if ($accessTokenArray['errcode']) {
            throw new \think\Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] .',微信返回错误信息：' . $accessTokenArray['errmsg']);
        }

        $accessToken = $accessTokenArray['access_token'];

        $qrcodeUrl='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$accessToken;
        $postData['expire_seconds'] = 2592000;
        $postData['action_name'] = 'QR_SCENE';
        $postData['action_info']['scene']['scene_id'] = $qrcodeId;

        $json = $httpObj->curlPost($qrcodeUrl,json_encode($postData));

        if (!$json['errcode']){
            $dataLoginQrcode['ticket'] = $json['ticket'];

            if($adminQrcodeService->updateById($qrcodeId,$dataLoginQrcode)){
                return ['id'=>$qrcodeId,'qrcode_url'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($json['ticket'])];

            }else{
                $conditionLoginQrcode['id'] = $qrcodeId;
                $adminQrcodeService->del($conditionLoginQrcode);
                throw new \think\Exception('获取二维码错误！保存二维码失败。请重试。');
            }
        }else{
            $conditionLoginQrcode['id'] = $qrcodeId;
            $adminQrcodeService->del($conditionLoginQrcode);
            throw new \think\Exception('发生错误：错误代码 '.$json['errcode'].'，微信返回错误信息：'.$json['errmsg']);
        }
    }


    /**
     * 生成永久二维码
     * @param $qrcodeId 二维码id
     * @author 张涛
     * @date 2020/07/03
     */
    public function seeQrcode($qrcodeId = 0, $thirdType = '', $thirdId = 0)
    {
        $rs = ['error_code' => true, 'qrcode_id' => $qrcodeId, 'qrcode' => ''];
        if ($qrcodeId > 0) {
            //获取二维码
            $qrcode = $this->recognitionModel->getOne(['id'=>$qrcodeId]);
            if (empty($qrcode)) {
                throw new Exception('二维码不存在');
            }
            $qrcode['ticket'] && $rs['qrcode'] = self::TICKET_PREFIX . urlencode($qrcode['ticket']);
        }
        if (empty($rs['qrcode'])) {
            //生成新二维码
            $accessTokenArray = (new AccessTokenExpiresService())->getAccessToken();
            if ($accessTokenArray['errcode']) {
                throw new Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] . ',微信返回错误信息：' . $accessTokenArray['errmsg']);
            }

            $qrcodeId = $this->recognitionModel->addNewQrcodeRow($thirdType, $thirdId);
            $access_token = $accessTokenArray['access_token'];
            $qrcode_url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
            $post_data['action_name'] = 'QR_LIMIT_SCENE';
            $post_data['action_info']['scene']['scene_id'] = $qrcodeId;

            $json = Http::curlPost($qrcode_url, json_encode($post_data));
            if ($json['errcode']) {
                throw new Exception('发生错误：错误代码 ' . $json['errcode'] . '，微信返回错误信息：' . $json['errmsg']);
            }
            $rs['qrcode'] = self::TICKET_PREFIX . urlencode($json['ticket']);
            $rs['qrcode_id'] = $qrcodeId;
        }
        return $rs;
    }

    /**
     * 获得二维码
     * @param $type string 第三方类型
     * @param $id integer 第三方的id
     * @author 衡婷妹
     * @date 2020/10/22
     */
    public function getQrcodeByThirdId($type, $id){
        $where['third_type'] = $type;
        $where['third_id'] = $id;
        $recognition = $this->getOne($where);
        if(empty($recognition)){
            return(array('error_code'=>true,'msg'=>L_('二维码不存在')));
        }else{
            return(array('error_code'=>false,'qrcode_id'=>$recognition['id'],'qrcode'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($recognition['ticket'])));
        }
    }

    /**
     * 获得新的二维码
     * @param $third_type string 第三方类型
     * @param $third_id integer 第三方的id
     * @author 衡婷妹
     * @date 2020/10/22
     */
    public function getNewQrcode($third_type,$third_id){
        $appid     = cfg('wechat_appid');
        $appsecret = cfg('wechat_appsecret');

        if(empty($appid) || empty($appsecret)){
            return(array('error_code'=>true,'msg'=>L_('请联系管理员配置【AppId】【 AppSecret】')));
        }

        $qrcode_return = $this->addNewQrcodeRow($third_type,$third_id);
        if(isset($qrcode_return['error_code']) && $qrcode_return['error_code']){
            return $qrcode_return;
        }

        $http = new Http();

        //微信授权获得access_token
        $access_token_array = (new AccessTokenExpiresService())->getAccessToken();
        if (isset($access_token_array['errcode']) && $access_token_array['errcode']) {
            $this->updateThis(array('id' => $qrcode_return['qrcode_id']),array('status' => 0));

            return(array('error_code'=>true,'msg'=>'获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']));
        }
        $access_token = $access_token_array['access_token'];

        $qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
        $post_data['action_name']='QR_LIMIT_SCENE';
        $post_data['action_info']['scene']['scene_id'] = $qrcode_return['qrcode_id'];

        $json = $http->curlPost($qrcode_url,json_encode($post_data));
        if (!isset($json['errcode']) || !$json['errcode']){
            $qrcode_save_return = $this->saveQrcode($qrcode_return['qrcode_id'],$json['ticket'],$third_type,$third_id);
            return $qrcode_save_return;
        }else {
            $this->updateThis(array('id' => $qrcode_return['qrcode_id']),array('status' => 0));
            return(array('error_code'=>true,'msg'=>L_('发生错误：错误代码').$json['errcode'].',微信返回错误信息：'.$json['errmsg']));
        }
    }

    /**
     * 返回现存的二维码
     * @param $qrcode_id 二维码id
     * @author 衡婷妹
     * @date 2020/10/22
     */
    public function getQrcode($qrcode_id){
        $condition_recognition['id'] = $qrcode_id;
        $recognition = $this->getOne($condition_recognition);
        if(empty($recognition)){
            return(array('error_code'=>true,'msg'=>L_('二维码不存在')));
        }else{
            return(array('error_code'=>false,'qrcode_id'=>$recognition['id'],'qrcode'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($recognition['ticket'])));
        }
    }

    /**
     * @param $third_type string 第三方类型
     * @param $third_id integer 第三方的id
     * @author 衡婷妹
     * @date 2020/10/22
     */
    public function addNewQrcodeRow($third_type,$third_id){
        $data_new_recognition['third_type'] = $third_type;
        $data_new_recognition['third_id'] = $third_id;
        $data_new_recognition['status'] = 1;
        $data_new_recognition['add_time'] = $_SERVER['REQUEST_TIME'];

        //首先查取有没有status = 0的，优先替换
        $condition_recognition['status'] = 0;
        $recognition = $this->getOne($condition_recognition);
        if(empty($recognition)){
            $data_new_recognition['ticket'] ="";
            $qrcode_id = $this->add($data_new_recognition);
            if($qrcode_id){
                return(array('error_code'=>false,'qrcode_id'=>$qrcode_id));
            }else{
                return(array('error_code'=>true,'msg'=>L_('获取失败！请重试。')));
            }
        }else{
            $condition_new_recognition['id'] = $recognition['id'];
            if($this->updateThis($condition_new_recognition,$data_new_recognition)){
                return(array('error_code'=>false,'qrcode_id'=>$recognition['id']));
            }else{
                return(array('error_code'=>true,'msg'=>L_('获取失败！请重试。')));
            }
        }
    }

    /**
     * 保存二维码的ticket
     * @param $third_type 第三方类型
     * @param $third_id 第三方的id
     * @author 衡婷妹
     * @date 2020/10/22
     */
    public function saveQrcode($qrcode_id,$ticket,$third_type,$third_id){
        $condition_recognition['id'] = $qrcode_id;
        $data_recognition['status'] = 1;
        $data_recognition['add_time'] = $_SERVER['REQUEST_TIME'];
        $data_recognition['ticket'] = $ticket;
        if($this->updateThis($condition_recognition,$data_recognition)){
//            $save_return = $this->saveAppQrcode($qrcode_id,$third_type,$third_id);
//            if(isset($save_return['error_code'])&&$save_return['error_code']){
//                return $save_return;
//            }
            return(array('error_code'=>false,'qrcode_id'=>$qrcode_id,'qrcode'=>'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket)));
        }else{
            return(array('error_code'=>true,'msg'=>L_('二维码保存失败！请重试。')));
        }
    }

    /**
     * 保存qrcode_id到应用
     * @param $qrcode_id 二维码id
     * @param $third_type 第三方类型
     * @param $third_id 第三方的id
     * @author 衡婷妹
     * @date 2020/10/22
     */
    public function saveAppQrcode($qrcode_id,$third_type,$third_id){
        if($third_type == 'merchantstore'){
            $save_return = array('error_code'=>false);
        }else if($third_type == 'shop' || $third_type == 'mallstore'){
            $save_return=invoke_cms_model('Merchant_store_shop/save_qrcode',[$third_id,$qrcode_id,$third_type]);
		}
        return $save_return;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->recognitionModel->where($where)->find();
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->recognitionModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->recognitionModel->updateThis($where,$data);

        return $result;

    }
}
