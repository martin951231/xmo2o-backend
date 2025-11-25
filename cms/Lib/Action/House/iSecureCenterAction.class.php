<?php

/**
 * 海康开放平台-综合安防管理平台（iSecure Center）
 * Class iSecureCenter
 */
class iSecureCenterAction extends BaseAction{


    protected function _initialize(){
        $this->houseLoginWhite = ['iSecureCenter'];
        parent::_initialize();
    }

    public function videoPreview() {
        $camera_id = I('camera_id');
        $village_id = $this->house_session['village_id'];
        $whereVideoPreview = [
            'camera_sn' => $camera_id,
            'village_id' => $village_id,
        ];
        $camera_device = M('House_camera_device')->where($whereVideoPreview)->find();
        if (empty($camera_device)) {
            $this->error('对应设备不存在');
            exit;
        }
        $this->assign('camera_device',$camera_device);
        $streamType = 1;// 码流类型，0:主码流 1:子码流 2:第三码流 参数不填，默认为主码流
        $model = '\app\community\model\service\HouseFaceDeviceService/videoV2CamerasPreviewURLs';
        $info = invoke_v20_service($model,[$camera_device['camera_id'], $village_id, $streamType]);
        if (isset($info['data']['retval'])&&$info['data']['retval']['look_url']) {
            $this->assign('videoH5Player',$info['data']['retval']);
            $this->display('videoH5Player');
        } else {
            $iSecureCenterConfig = [
                'ip' => $this->config['iSecureCenterIp'],
                'port' => $this->config['iSecureCenterPreUrlPort'],
                'appkey' => $this->config['iSecureCenterAppKey'],
                'appsecret' => $this->config['iSecureCenterAppSecret'],
            ];
            $this->assign('iSecureCenterConfig',$iSecureCenterConfig);
            $this->display();
        }
    }
    public function videoPreviewFullScreen() {
        $camera_id = I('camera_id');
        $village_id = I('village_id');
        $bindUserId = I('bindUserId');
        if ($bindUserId) {
            $whereBind = [
                'pigcms_id' => $bindUserId
            ];
            $bindUserInfo = M('House_village_user_bind')->where($whereBind)->field('village_id,uid,type,floor_id')->find();
            if (!$village_id&&$bindUserInfo['village_id']) {
                $village_id = $bindUserInfo['village_id'];
            }
        }
        if (!$village_id) {
            $village_id = $this->house_session['village_id'];
        }
        $whereVideoPreview = [
            'camera_sn' => $camera_id,
            'village_id' => $village_id,
        ];
        $camera_device = M('House_camera_device')->where($whereVideoPreview)->find();
        if (empty($camera_device)) {
            $this->error('对应设备不存在');
            exit;
        }
        $this->assign('camera_device',$camera_device);

        $streamType = 1;// 码流类型，0:主码流 1:子码流 2:第三码流 参数不填，默认为主码流
        $model = '\app\community\model\service\HouseFaceDeviceService/videoV2CamerasPreviewURLs';
        $info = invoke_v20_service($model,[$camera_device['camera_id'], $village_id, $streamType]);
        $this->assign('videoH5Player',$info['data']['retval']);
        $this->display('videoH5PlayerFullScreen');
//        if (isset($info['data']['retval'])&&$info['data']['retval']['look_url']) {
//            $this->assign('videoH5Player',$info['data']['retval']);
//            $this->display('videoH5PlayerFullScreen');
//        } else {
//            $iSecureCenterConfig = [
//                'ip' => $this->config['iSecureCenterIp'],
//                'port' => $this->config['iSecureCenterPreUrlPort'],
//                'appkey' => $this->config['iSecureCenterAppKey'],
//                'appsecret' => $this->config['iSecureCenterAppSecret'],
//            ];
//            $this->assign('iSecureCenterConfig',$iSecureCenterConfig);
//            $this->display();
//        }
    }

    public function videoH5FlvFullScreen() {
        $camera_id = I('camera_id');
        $village_id = I('village_id');
        $bindUserId = I('bindUserId');
        if ($bindUserId) {
            $whereBind = [
                'pigcms_id' => $bindUserId
            ];
            $bindUserInfo = M('House_village_user_bind')->where($whereBind)->field('village_id,uid,type,floor_id')->find();
            if (!$village_id&&$bindUserInfo['village_id']) {
                $village_id = $bindUserInfo['village_id'];
            }
        }
        if (!$village_id) {
            $village_id = $this->house_session['village_id'];
        }
        $whereVideoPreview = [
            'camera_sn' => $camera_id,
            'village_id' => $village_id,
        ];
        $camera_device = M('House_camera_device')->where($whereVideoPreview)->find();
        if (empty($camera_device)) {
            $this->error('对应设备不存在');
            exit;
        }
        $baseUrl = $this->config['site_url'];
        if (isset($camera_device['look_url']) && $camera_device['look_url'] && strpos($baseUrl, 'https') !== false) {
            $camera_device['look_url'] = str_replace('http://', 'https://', $camera_device['look_url']);
        }
        
        $this->assign('camera_device',$camera_device);
        $this->display();
    }
}