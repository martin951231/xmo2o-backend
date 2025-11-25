<?php
/**
 * 监控或者摄像方法
 * Class iSecureCenter
 */

class CameraDeviceAction extends BaseAction
{
    protected function _initialize(){
        $this->houseLoginWhite = ['CameraDevice'];
        parent::_initialize();
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
        ];
        if($village_id>0){
            $whereVideoPreview['village_id']=$village_id;
        }
        $camera_device = M('House_camera_device')->where($whereVideoPreview)->order('camera_id desc')->find();
        if (empty($camera_device)) {
            $this->error('对应设备不存在');
            exit;
        }
        $extType='';
        $look_url=$camera_device['look_url'] ? strtolower($camera_device['look_url']):'';
        if($look_url && (strpos($look_url,'.hd.m3u8')||strpos($look_url,'.m3u8'))){
            $extType='hls';
        }else if($look_url && strpos($look_url,'.flv')){
            $extType='flv';
        }
        $this->assign('extType',$extType);
        $this->assign('camera_device',$camera_device);
        $this->display();
    }
}