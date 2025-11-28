<?php


namespace app\community\controller\village_api\User;


use app\common\model\service\config\ConfigCustomizationService;
use app\community\controller\UserBaseController;
use app\community\model\service\Device\HouseDeviceQrcodeService;
use app\community\model\service\Device\HouseVillageDeviceCmdService;
use app\community\model\service\Device\XiHanEventService;
use app\community\model\service\FaceDeviceService;
use app\community\model\service\Device\FaceDHYunRuiCloudDeviceService;
use  app\community\model\service\Device\HikInfoVisionICommunityService;
use app\community\model\service\HouseFaceDeviceService;
use app\consts\Cloud260Const;
use app\consts\DeviceConst;

class FaceDeviceController extends UserBaseController
{
    /**
     * 获取设置乐橙服务地址, 开发者默认填写openapi.lechange.cn:443即可
     * @return \json
     */
    public function getDhYunRuiLcOpenSDKApi() {
        if (!$this->_uid || !isset($this->userInfo['uid'])){
            return api_output_error(1002, "用户不存在或未登录");
        }
        $result = (new FaceDHYunRuiCloudDeviceService())->getLcOpenSDKApi($this->userInfo);
        return api_output(0,$result);
    }

    /**
     *  获取接下来云睿相关请求所需携带的token
     * @return \json
     */
    public function getDhYunRuiCloudAccessToken() {
        if (!$this->_uid || !isset($this->userInfo['uid'])){
            return api_output_error(1002, "用户不存在或未登录");
        }
        $result = (new FaceDHYunRuiCloudDeviceService())->getCloudAccessToken();
        return api_output(0,$result);
    }

    /**
     *  获取乐橙播放token
     * @return \json
     */
    public function getDhYunRuiLeChengUserToken() {
        if (!$this->_uid || !isset($this->userInfo['uid'])){
            return api_output_error(1002, "用户不存在或未登录");
        }
        $result = (new FaceDHYunRuiCloudDeviceService())->getLeChengUserToken();
        return api_output(0,$result);
    }

    /**
     *  纯云app注册sip
     * @return \json
     */
    public function registerDhYunRuiChunYunSip() {

        if (!$this->_uid || !isset($this->userInfo['uid'])){
            return api_output_error(1002, "用户不存在或未登录");
        }
        $phone = $this->request->post('phone');
        if (!$phone && isset($this->userInfo['phone']) && $this->userInfo['phone']) {
            $phone = $this->userInfo['phone'];
        }
        if (!$phone) {
            return api_output_error(1001, "缺少手机号");
        }
        $result = (new FaceDHYunRuiCloudDeviceService())->registerChunYunSip($phone);
        return api_output(0,$result);
    }

    /**
     *  获取云睿平台配置
     * @return \json
     */
    public function getDhYunLechangeConfig() {

        if (!$this->_uid || !isset($this->userInfo['uid'])){
            return api_output_error(1002, "用户不存在或未登录");
        }
        $result = (new FaceDHYunRuiCloudDeviceService())->getLechangeConfig();
        return api_output(0,$result);
    }


    /**
     *  获取开门身份id
     * @return \json
     */
    public function getDhYunPersonFile() {
        if (!$this->_uid || !isset($this->userInfo['uid'])){
            return api_output_error(1002, "用户不存在或未登录");
        }
        $result = (new FaceDeviceService())->filterUserToDataFromUid($this->userInfo['uid']);
        $arr = [];
        if (isset($result['person_id']) && $result['person_id']) {
            $arr['type']         = 1;
            $arr['personFileId'] = $result['person_id'];
        } else {
            $arr['type']         = -1;
            $arr['personFileId'] = "";
        }
        return api_output(0,$arr);
    }

    public function getDeviceOpenQrCode()
    {
        if (!$this->_uid || !isset($this->userInfo['uid'])) {
            return api_output_error(1002, "用户不存在或未登录");
        }
        $village_id = $this->request->post('village_id');
        $pigcms_id = $this->request->post('pigcms_id');
        $params = [
            'uid' => $this->_uid,
            'village_id' => $village_id,
            'pigcms_id' => $pigcms_id,
        ];
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $app_secret = cfg('hik_info_vision_i_community_app_secret');
        try {
            // todo 获取设备二维码 注意有测试条件需要删除
            $configCustomizationService =  new ConfigCustomizationService();
                
            $getFaceCloud260Judge = $configCustomizationService->getFaceCloud260Judge();
            $sHouseFaceDeviceService = new HouseFaceDeviceService();
            $hasHikInfoVisionICommunity = 0;
            if (!empty($app_key) && !empty($app_secret)) {
                $whereFace = [];
                $whereFace[] = ['village_id', '=', $village_id];
                $whereFace[] = ['device_type', '=', DeviceConst::HIK_DEVICE_TYPE];
                $whereFace[] = ['thirdProtocol', '=', DeviceConst::HIK_INFO_VISION_I_COMMUNITY];
                $whereFace[] = ['is_del', '=', 0];
                $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace);
                if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $hasHikInfoVisionICommunity = 1;
                }
            }
            if ($hasHikInfoVisionICommunity) {
                $hikInfoVisionICommunityService = new HikInfoVisionICommunityService();
                $result = $hikInfoVisionICommunityService->getDoorbarCodeStr($village_id, $pigcms_id, $this->_uid);
                if ($result['err_code'] == 1) {
                    return api_output_error(1001, $result['err_msg']);
                }
                return api_output(0, $result);
            }
            $hasFaceCloud260 = 0;
            if ($getFaceCloud260Judge) {
                $device_type = Cloud260Const::CLOUD_260_DEVICE_TYPE;
                $whereFace = [];
                $whereFace[] = ['village_id', '=', $village_id];
                $whereFace[] = ['device_type', '=', $device_type];
                $whereFace[] = ['is_del', '=', 0];
                $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace);
                if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $hasFaceCloud260 = 1;
                }
            }
            if ($hasFaceCloud260 == 1) {
                $result = (new HouseVillageDeviceCmdService())->getUserOpenCode($params);
                return api_output(0, $result);
            }
            $hasXiHan = 0;
            $getXiHanJudge = $configCustomizationService->getFaceXiHanDoorSwitch();
            if ($getXiHanJudge) {
                $whereFace = [];
                $whereFace[] = ['village_id', '=', $village_id];
                $whereFace[] = ['device_type', '=', DeviceConst::XI_HAN_DEVICE_TYPE];
                $whereFace[] = ['is_del', '=', 0];
                $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace);
                if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $hasXiHan = 1;
                }
            }
            if ($hasXiHan == 1) {
                $params['hasXiHan'] = $hasXiHan;
                $result = (new XiHanEventService())->getUserOpenCode($params);
                return api_output(0, $result);
            }
            
            $is_func_cloudmrsqfw_special = $configCustomizationService->getFuncCloudmrsqfwSpecial();
            if($is_func_cloudmrsqfw_special){
                $whereFace = [];
                $whereFace[] = ['village_id', '=', $village_id];
                $whereFace[] = ['third_dev_source', '=', 'lilin_pf'];
                $whereFace[] = ['third_dev_id', '<>', ''];
                $whereFace[] = ['is_del', '<', 4];
                $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace);
                if (isset($deviceInfo['device_id']) && $deviceInfo['device_id']) {
                    $liLinDevAccessApiService= new \app\community\model\service\Device\LiLinDevAccessApiService();
                    $result=$liLinDevAccessApiService->getUserOpenCode($pigcms_id,$village_id);
                    if ($result['err_code'] == 1) {
                        return api_output_error(1001, $result['err_msg']);
                    }
                    return api_output(0, $result);
                }
            }
            
            fdump_api(['hasXiHan' => $hasXiHan, 'hasHikInfoVisionICommunity' => $hasHikInfoVisionICommunity, 'hasFaceCloud260' => $hasFaceCloud260], 'addDeviceQrCode');
            $result = (new HouseDeviceQrcodeService())->addDeviceQrCode($pigcms_id);
        } catch (\Exception $e) {
            return api_output_error(-1, [$e->getMessage()]);
        }
        return api_output(0, $result);
    }
}