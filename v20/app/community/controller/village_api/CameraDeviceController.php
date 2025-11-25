<?php


namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\Device\DeviceHkNeiBuHandleService;
use app\community\model\service\Device\FaceDHYunRuiCloudDeviceService;
use app\community\model\service\Device\FaceHikCloudNeiBuDeviceService;
use app\community\model\service\Device\Hik6000CCameraService;
use app\community\model\service\Device\HikGateWayDeviceService;
use app\community\model\service\Device\HikGateWayEventService;
use app\community\model\service\Device\SynOverviewService;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\iSecureCameraService;
use app\community\model\service\FaceDeviceService;
use app\community\model\service\HouseCameraDeviceVtypeService;
use app\consts\DeviceConst;
use app\consts\EventAlarmConst;
use app\consts\SynOverviewConst;
use Fastwhal\HikDeviceGateway\BuiltInConstants;
use think\Exception;
use app\traits\FaceDeviceHikCloudTraits;

class CameraDeviceController extends CommunityBaseController
{
    use FaceDeviceHikCloudTraits;
    
    /**
     * 视频监控列表
     * @author lijie
     * @date_time 2022/01/12
     * @return \json
     */
    public function getCameraList()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        $device_type = $this->request->post('device_type',0);
        $is_online = $this->request->post('is_online',0);
        $is_support_look = $this->request->post('is_support_look',0);
        $camera_sn = $this->request->post('camera_sn','');
        $camera_name = $this->request->post('camera_name','');
        $thirdProtocol = $this->request->post('thirdProtocol',0);
        
        $whereChannel = [['brand_id','>',0]];
        if($device_type) {
            $where['device_type'] = $device_type;
            $whereChannel[] = ['a.device_type', '=', $device_type];
        }
        $whereRaw = '';
        if($camera_name) {
            $whereRaw ='camera_name like "%'.$camera_name.'%"';
            $whereChannel[] = ['a.camera_name', 'like', '%'.$camera_name.'%'];
        }
        if($camera_sn) {
            if ($whereRaw) {
                $whereRaw .= ' AND camera_sn like "%'.$camera_sn.'%"';
            } else {
                $whereRaw = 'camera_sn like "%'.$camera_sn.'%"';
            }
            $whereChannel[] = ['a.camera_sn', 'like', '%'.$camera_sn.'%'];
        }
        if ($is_online&&$is_online==1) {
            $where['camera_status'] = 0;
            $whereChannel[] = ['a.camera_status', '=', 0];
        } elseif ($is_online&&$is_online==2) {
            $where['camera_status'] = 1;
            $whereChannel[] = ['a.camera_status', '=', 1];
        } else {
            $where['camera_status'] = [0,1];
            $whereChannel[] = ['a.camera_status', 'in', [0,1]];
        }
        if ($is_support_look&&$is_support_look==1) {
            $where['is_support_look'] = 1;
            $whereChannel[] = ['a.is_support_look', '=', 1];
        } elseif ($is_support_look&&$is_support_look==2) {
            $where['is_support_look'] = 0;
            $whereChannel[] = ['a.is_support_look', '=', 0];
        } else {
            $where['is_support_look'] = [0,1];
            $whereChannel[] = ['a.is_support_look', 'in', [0,1]];
        }
        if (intval($thirdProtocol) > 0) {
            $where['thirdProtocol'] = $thirdProtocol;
            $whereChannel[] = ['a.thirdProtocol', '=', $thirdProtocol];
        }
        $where['village_id'] = $village_id;
        $whereChannel[] = ['a.village_id', '=', $village_id];
//        $where['device_brand'] = 'iSecureCenter';
        $field = true;
        $order = 'sort DESC,camera_id DESC';
        try{
            
            $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($village_id);
            if ($deviceQrCodeJudge) {
                $field1 = "a.*,b.channel_id,b.channelName,b.channelId,b.channelNo,b.channelNo,b.hls as channelHls, b.hlsHd as channelHlsHd,b.rtmp as channelRtmp,b.rtmpHd as channelRtmpHd,b.flv as channelFlv,b.flvHD as channelFlvHD,b.rtsp as channelRtsp,b.rtspHd as channelRtspHd";
                $order1 = "a.sort DESC,a.camera_id DESC";
                $data = $service_house_face_device->getListLeftJoinChannel($whereChannel,$field1,$page,$limit,$order1);
                $count = $service_house_face_device->getListLeftJoinChannelCount($whereChannel);
            } else {
                $data = $service_house_face_device->getCameraList($where,$field,$page,$limit,$order,$whereRaw);
                $count = $service_house_face_device->getCameraCount($where,$whereRaw);
            }
            fdump_api(['data' => $data, 'count' => $count], '$data');
            
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list']         = $data;
        $res['count']        = $count;
        $res['total_limit']  = $limit;
        $hikCloudInfo = (new Hik6000CCameraService())->getVillageJudgeConfig($village_id);
        $res['hikCloudInfo'] = $hikCloudInfo;
        return api_output(0,$res);
    }
    public function getCameraChannelList()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        $village_id=$village_id>0 ? $village_id:0;
        $whereChannel = [];
        $whereChannel[] = ['a.thirdProtocol', '=', 16];
        $whereChannel[] = ['a.village_id', '=', $village_id];
        $whereChannel[] = ['a.camera_status', 'in', [0,1]];
        $whereChannel[] = ['b.isDel', '<', 1];
        $order = 'a.camera_id DESC,a.camera_status ASC, b.channel_id asc';
        try{
            $field1 = "a.camera_id,a.camera_name,a.camera_sn,a.village_id,a.cloud_device_name,b.channel_id,b.channelName,b.channelId,b.channelNo";
                $data = $service_house_face_device->getListLeftJoinAllChannel($whereChannel,$field1,$order);
                $count = $service_house_face_device->getListLeftJoinChannelCount($whereChannel);
                
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list']         = $data;
        $res['count']        = $count;
        return api_output(0,$res);
    }
    public function cameraDeviceLinks() {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page  = $this->request->post('page',1);
        $limit = $this->request->post('limit',9);
        $name  = $this->request->post('name','','trim');
        $whereChannel = [];
        $where = [];
        if ($name) {
            $where[] = ['camera_name','like',"%$name%"];
            $whereChannel[] = ['a.camera_name', 'like', '%'.$name.'%'];
        }
        if ($page <= 0) {
            $page  = 0;
            $limit = 0;
        }
        $where[] = ['camera_status', 'in', [0,1]];
        $where[] = ['village_id', '=', $village_id];
        $where[] = ['look_url', '<>', ''];

        $whereChannel[] = ['a.camera_status', 'in', [0,1]];
        $whereChannel[] = ['a.village_id', '=', $village_id];
        $whereChannel[] = ['a.look_url', '<>', ''];
        $order = 'sort DESC,camera_id DESC';
        try{
            $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($village_id);
            if ($deviceQrCodeJudge) {
                $field1 = "a.*,b.channelName,b.channelId,b.channelNo,b.channelNo,b.hls as channelHls, b.hlsHd as channelHlsHd,b.rtmp as channelRtmp,b.rtmpHd as channelRtmpHd,b.flv as channelFlv,b.flvHD as channelFlvHD,b.rtsp as channelRtsp,b.rtspHd as channelRtspHd";
                $order1 = "a.sort DESC,a.camera_id DESC";
                $data = $service_house_face_device->getListLeftJoinChannel($whereChannel,$field1,$page,$limit,$order1, ['gainLookUrl' => 1]);
                $count = $service_house_face_device->getListLeftJoinChannelCount($whereChannel);
            } else {
                $field = 'camera_id,camera_name,camera_sn,village_id,floor_id,look_url,lookUrlType,thirdProtocol,camera_status,cloud_device_id,gatewayinfo';
                $data = $service_house_face_device->cameraLinkList($where, $field, $page, $limit, $order);
                $count = $service_house_face_device->getCameraCount($where);
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list']         = $data;
        $res['count']        = $count;
        $res['limit']        = $limit;
        return api_output(0,$res);
    }

    public function videoV2CamerasPreviewURLs() {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $camera_id = $this->request->post('camera_id',0);
        if(!$camera_id){
            return api_output_error(1001,'缺少必传参数');
        }
        try{
            $data = $service_house_face_device->videoV2CamerasPreviewURLs($camera_id,$village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 删除视频监控
     * @author lijie
     * @date_time 2022/01/12
     */
    public function delCamera()
    {
        $camera_id = $this->request->post('camera_id',0);
        if(!$camera_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $where['camera_id'] = $camera_id;
        try{
            $data = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id]);
            if ($data && !is_array($data)) {
                $data = $data->toArray();
            }
            $cloud_device_id = isset($data['cloud_device_id']) && $data['cloud_device_id'] ? trim($data['cloud_device_id']) : '';
            $thirdProtocol = isset($data['thirdProtocol']) && $data['thirdProtocol'] ? intval($data['thirdProtocol']) : 0;
            if ($cloud_device_id && $thirdProtocol == DeviceConst::HIK_YUNMO_NEIBU_SHEQU) {
                // todo 海康内部协议 同步删除云端设备
                $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
                $param = [
                    'village_id'      => $data['village_id'],
                    'device_id'       => $data['camera_id'],
                    'operation'       => 'deleteDevice',
                    'device_sn'       => $data['camera_sn'],
                    'orderGroupId'    => $orderGroupId,
                    'cloud_device_id' => $cloud_device_id,
                    'thirdProtocol'   => $thirdProtocol,
                    'deviceType'      => DeviceConst::DEVICE_TYPE_CAMERA,
                ];
                $delInfo = (new FaceDeviceService())->deleteDevice($param);
                if (isset($delInfo['code']) && $delInfo['code'] > 0) {
                    return api_output_error(1001,$delInfo['msg']);
                }
            }
            if ($thirdProtocol == DeviceConst::HIK_YUNMO_NEIBU_6000C) {
                // todo 只删除本地不同步删除云端
            }
            if ($cloud_device_id && $thirdProtocol == DeviceConst::DH_YUNRUI) {
                // todo 大华云睿 同步删除云端设备
                $orderGroupId = md5(uniqid().$_SERVER['REQUEST_TIME']);
                $param = [
                    'village_id'      => $data['village_id'],
                    'device_id'       => $data['camera_id'],
                    'operation'       => 'deleteDevice',
                    'device_sn'       => $data['camera_sn'],
                    'orderGroupId'    => $orderGroupId,
                    'cloud_device_id' => $cloud_device_id,
                    'thirdProtocol'   => $thirdProtocol,
                    'deviceType'      => DeviceConst::DEVICE_TYPE_CAMERA,
                ];
                $delInfo = (new FaceDeviceService())->deleteDevice($param);
                if (isset($delInfo['code']) && $delInfo['code'] > 0) {
                     return api_output_error(1001,$delInfo['msg']);
                }
            }
            if ($cloud_device_id && $thirdProtocol && in_array($thirdProtocol,[DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM])) {
                // 海康网关
                $delInfo = (new HikGateWayDeviceService())->deleteDeviceBox($data);
                if (isset($delInfo['code']) && $delInfo['code'] > 0 && (!isset($delInfo['subStatusCode']) || $delInfo['subStatusCode']!='badParameters')) {
                    return api_output_error(1001,$delInfo['message']);
                }
            }
            $res = $service_house_face_device->saveCamera(['camera_id'=>$camera_id],['camera_status'=>4]);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res) {
            return api_output(0,'');
        }
        return api_output_error(1001,'服务异常');
    }

    /**
     * 预览视频监控
     * @author lijie
     * @date_time 2022/01/12
     * @return \json
     */
    public function previewURLs()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $camera_id = $this->request->post('camera_id',0);
        if(!$village_id || !$camera_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $service_i_secure_camera = new iSecureCameraService();
        $where['camera_id'] = $camera_id;
        $field = 'camera_sn';
        try{
            $camera_info = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id],$field);
            if(empty($camera_info)){
                return api_output_error(1001,'参数错误');
            }
            $data = $service_i_secure_camera->previewURLs($camera_info['camera_sn']);
            if(!$data){
                return api_output_error(1001,'视频获取异常');
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 回放视频监控
     * @author lijie
     * @date_time 2022/01/12
     * @return \json
     */
    public function playbackURLs()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $village_id = $this->request->post('village_id',0);
        $camera_id = $this->request->post('camera_id',0);
        $start_time = '';
        $end_time = '';
        if(!$village_id || !$camera_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $service_i_secure_camera = new iSecureCameraService();
        $where['camera_id'] = $camera_id;
        $field = 'camera_sn';
        try{
            $camera_info = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id],$field);
            if(empty($camera_info)){
                return api_output_error(1001,'参数错误');
            }
            $data = $service_i_secure_camera->playbackURLs($camera_info['camera_sn'],$start_time,$end_time);
            if(!$data){
                return api_output_error(1001,'视频获取异常');
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 设备品牌
     * @author lijie
     * @date_time 2022/01/13
     * @return \json
     */
    public function getBrandList()
    {
        $service_house_face_device = new HouseFaceDeviceService();
        try{
            $data = $service_house_face_device->getBrandList();
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    public function getThirdProtocol() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $brand_id =  $this->request->post('brand_id');
        $channelId = $this->request->post('channelId',0);
        $cameraId = $this->request->post('cameraId',0);
        $brand_key =  $this->request->post('brand_key');
        $service_face_device = new FaceDeviceService();
        $data = [];
        try{

            $villageGateway = (new HikGateWayDeviceService())->getHouseDeviceGatewayInfo(['village_id'=>$village_id,'is_open'=>1]);
            
            
            if ($brand_id) {
                $brand_list = (new HouseFaceDeviceService())->getBrandList();
                if ($brand_list) {
                    foreach ($brand_list as $item) {
                        if (isset($item['brand_id']) && $brand_id == $item['brand_id']) {
                            $brand_key = $item['brand_key'];
                            break;
                        }
                    }
                }
            }
            if (isset($service_face_device->thirdProtocolArr[$brand_key])) {
                $data['thirdProtocol'] = $service_face_device->thirdProtocolArr[$brand_key];
                if (isset($data['thirdProtocol'][DeviceConst::HIK_ISC_V151])) {
                    /** todo 目前海康综合安防平台 监控部分不支持手动添加 只能获取海康安防平台的设备数据 所以添加的时候去除这个协议 */
                    unset($data['thirdProtocol'][DeviceConst::HIK_ISC_V151]);
                }
                //过滤没有启用配置的海康网关
                if (empty($villageGateway)){
                    unset($data['thirdProtocol'][DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]);
                }
                
                if (empty(cfg('hikGatewayHost')) || empty(cfg('hikGatewayProtocol')) || empty(cfg('hikGatewayPort'))|| empty(cfg('hikGatewayUserName'))|| empty(cfg('hikGatewayPassword'))){
                    unset($data['thirdProtocol'][DeviceConst::HIK_DEVICE_GATEWAY]);
                }
                
            } elseif (!$brand_key) {
                $data['thirdProtocol'] = [];
            }
            $data['tips'] = (new HouseFaceDeviceService())->getBrandHaikangCodeTip($brand_key, $channelId);
            $data['brand_key'] = $brand_key;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 设备类型
     * @return \json
     */
    public function getDeviceTypeList()
    {
        $service_house_face_device = new HouseFaceDeviceService();
        try{
            $village_id = $this->adminUser['village_id'];
            $data = $service_house_face_device->getDeviceTypeList($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    
    public function getVideoGatewayList()
    {
        $protocol =  (int)$this->request->post('protocol');
        $channelId = $this->request->post('channelId',0);
        $cameraId = $this->request->post('cameraId',0);


        $whereChannel = [];
        $whereChannel[] = ['device_type', '=', 'house_camera_device'];
        $whereChannel[] = ['device_id', '=', $cameraId];
        $whereChannel[] = ['channelId', '=', $channelId];
        $whereChannel[] = ['isDel', '=', 0];
        $channelInfo = (new FaceDeviceService())->getHouseDeviceChannelInfo($whereChannel);
        if ($channelInfo && !is_array($channelInfo)) {
            $channelInfo = $channelInfo->toArray();
        }
        $channelNo = isset($channelInfo['channelNo']) ? trim($channelInfo['channelNo']) : '';
        $codeTipParams = ['channelNo' => $channelNo];
        if (in_array($protocol,[DeviceConst::HIK_DEVICE_GATEWAY,DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM])){
            $service_face_device = new FaceDeviceService();
            $service_house_face_device = new HouseFaceDeviceService();
            
            $formParams = $service_house_face_device->getBrandHaikangCodeTip('', $channelId, $codeTipParams);
            unset($formParams['product_model']);
            unset($formParams['open_time']);
            unset($formParams['param']);
            unset($formParams['thirdLoginName']);
            $formParams['thirdLoginPassword']['lable'] = '登录密钥';
            $formParams['thirdLoginPassword']['title'] = 'ip访问设备登录的密码(当协议类是 ISUP5.0 必填 )';
            
            $radioList = $service_face_device->hikDeviceGatewayVideoProtocolType();
            $data = [
                'hasRadio' => !empty($radioList) ? 1 : 0,
                'radioList' => $radioList,
                'formParams'=>$formParams
            ];
            $site_url = cfg('site_url');
            $hostName = str_replace('http://', '', $site_url);
            $hostName = str_replace('https://', '', $hostName);
            $data['radioIconImg'] = $site_url . "/static/file/village/faceDevice/image_explain.png";
            $data['radioExplainImg'] = $site_url . "/static/file/village/faceDevice/camera_isup_version_explain.png?t=10001";
            $data['httpIconImg'] = $site_url . "/static/file/village/faceDevice/image_explain.png";
            $data['httpExplainImg'] = $site_url . "/static/file/village/faceDevice/camera_http_explain.png";
            $data['applicableModelExplainImg'] = $site_url . "/static/file/village/faceDevice/camera_applicable_model_explain.png";
            $data['deviceModeInfoExplainImg'] = $site_url . "/static/file/village/faceDevice/device_mode_info_explain.png";
            
            $data['httpListenArr'] = [
                ['title' => "目的IP或域名", 'value' => $hostName],
                ['title' => "URL", 'value' => '/v20/public/index.php/community/common.HikGateWay/eventNotification'],
                ['title' => "端口", 'value' => 80],
            ];
            return api_output(0,$data);
        } else {
            $service_house_face_device = new HouseFaceDeviceService();

            $formParams = $service_house_face_device->getBrandHaikangCodeTip('', $channelId, $codeTipParams);
            $data = [
                'hasRadio' => 0,
                'radioList' => [],
                'formParams'=>$formParams
            ];
            return api_output(0,$data);
        }
        return api_output(0,[]);
    }
    
    /**
     * 添加/编辑视频监控
     * @author lijie
     * @date_time 2022/01/13
     */
    public function addCameraDevice()
    {
        $village_id      = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $camera_name        = $this->request->post('camera_name',0);
        $camera_sn          = $this->request->post('camera_sn',0);
        $open_time          = $this->request->post('open_time','');
        $floor_id           = $this->request->post('floor_id',0);
        $public_area_id     = $this->request->post('public_area_id',0);
        $is_support_look    = $this->request->post('is_support_look',0);
        $brand_id           = $this->request->post('brand_id',0);
        $product_model      = $this->request->post('product_model','');
        $device_type        = $this->request->post('device_type',1);
        $param              = $this->request->post('param','');
        $remark             = $this->request->post('remark','');
        $camera_id          = $this->request->post('camera_id',0);
        $thirdProtocol      = $this->request->post('thirdProtocol',0);
        $device_code        = $this->request->post('device_code','');
        $thirdLoginName     = $this->request->post('thirdLoginName','');
        $thirdLoginPassword = $this->request->post('thirdLoginPassword','');
        $sort               = $this->request->post('sort',0);
        $isup_version       = $this->request->post('isup_version','');
        if (empty($camera_sn) || empty($camera_name) || !$device_type || !$brand_id) {
            return api_output_error(1001, '缺少必传参数');
        }
        if (in_array($thirdProtocol, [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]) && !$isup_version) {
            return api_output_error(1001, '请选择协议类型');
        }
        if (!$thirdLoginPassword && $isup_version && $isup_version == BuiltInConstants::PROTOCOL_TYPE_ISUPV5) {
            return api_output_error(1001, '请填写登录密钥');
        }
        $service_house_face_device = new HouseFaceDeviceService();
        $brand_name = $service_house_face_device->getBrandName($brand_id);
        $paramPosts['brand_id']            = $brand_id;
        $paramPosts['village_id']          = $village_id;
        $paramPosts['camera_name']         = $camera_name;
        $paramPosts['camera_sn']           = $camera_sn;
        $paramPosts['camera_status']       = 0;
        $paramPosts['add_time']            = time();
        $paramPosts['last_time']           = time();
        $paramPosts['open_time']           = !empty($open_time) ? strtotime($open_time) : 0;
        $paramPosts['public_area_id']      = !$public_area_id   ? $public_area_id       : 0;
        $paramPosts['floor_id']            = !$floor_id         ? $floor_id             : 0;
        $paramPosts['is_support_look']     = $is_support_look;
        $paramPosts['brand_name']          = $brand_name;
        $paramPosts['product_model']       = $product_model;
        $paramPosts['device_type']         = $device_type;
        $paramPosts['param']               = !empty($param)  ? $param  : '';
        $paramPosts['remark']              = !empty($remark) ? $remark : '';
        $paramPosts['sort']               = (int)$sort;
        if ($thirdProtocol) {
            $paramPosts['thirdProtocol']       = $thirdProtocol;
        }
        $paramPosts['device_code']         = $device_code;
        $faceDeviceService = new FaceDeviceService();
        if ($thirdProtocol == DeviceConst::DH_YUNRUI && (!$thirdLoginName || !$thirdLoginPassword)) {
            $thirdLoginName     = 'admin';
            $thirdLoginPassword = 'admin123';
        }
        $paramPosts['thirdLoginName']      = $thirdLoginName;
        $paramPosts['thirdLoginPassword']  = $thirdLoginPassword;
        try{
            if($camera_id){
                if ($isup_version) {
                    $deviceInfo = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id], 'camera_id, gatewayinfo');
                    $gatewayinfo = isset($deviceInfo['gatewayinfo']) && $deviceInfo['gatewayinfo'] ? json_decode($deviceInfo['gatewayinfo'], 1) : [];
                    $gatewayinfo['isup_version'] = $isup_version;
                    $paramPosts['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                }
                unset($paramPosts['add_time']);
                $operation = 'updateDevice';
                $res = $service_house_face_device->saveCamera(['camera_id'=>$camera_id],$paramPosts);
            }else{
                $whereCamera    = [];
                $whereCamera[]  = ['camera_status', '<>', 4];
                $whereCamera[]  = ['camera_sn', '=', $camera_sn];
                if ($thirdProtocol) {
                    $whereCamera[] = ['thirdProtocol', '=', $thirdProtocol];
                }
                $camera_info = $service_house_face_device->getCameraInfo($whereCamera,'camera_id');
                if ($camera_info && isset($camera_info['camera_id'])) {
                    return api_output_error(1001,'该设备已经添加');
                } else {
                    if ($isup_version) {
                        $gatewayinfo = [];
                        $gatewayinfo['isup_version'] = $isup_version;
                        $paramPosts['gatewayinfo'] = json_encode($gatewayinfo, JSON_UNESCAPED_UNICODE);
                    }
                    $res = $service_house_face_device->addCamera($paramPosts);
                    if ($res) {
                        $camera_id = $res;
                        $operation = 'addDevice';
                    }
                }
            }
            if ($camera_id && isset($paramPosts['thirdProtocol']) && $paramPosts['thirdProtocol']) {
                $param = [
                    'village_id'    => $village_id,
                    'device_id'     => $camera_id,
                    'operation'     => $operation,
                    'deviceType'    => DeviceConst::DEVICE_TYPE_CAMERA,
                    'thirdProtocol' => $paramPosts['thirdProtocol'],
                ];
                $handleDevice = $faceDeviceService->handleDevice($camera_sn, $param);
                if (isset($handleDevice['code']) && $handleDevice['code']>0) {
                    return api_output_error(-1, $handleDevice['msg']);
                }
                fdump_api($handleDevice,'$handleDevice');
            }

            $channel_id = $this->request->post('channel_id', 0, 'intval');
            $channelId = $this->request->post('channelId', 0);
            $channelName = $this->request->post('channelName', '', 'trim');
            if (($channel_id > 0 || $channelId) && $channelName) {
                $whereChannel = [];
                $whereChannel[] = ['device_type', '=', 'house_camera_device'];
                $whereChannel[] = ['device_id', '=', $camera_id];
                if ($channel_id > 0) {
                    $whereChannel[] = ['channel_id', '=', $channel_id];
                } else {
                    $whereChannel[] = ['channelId', '=', $channelId];
                }
                $whereChannel[] = ['isDel', '=', 0];

                $updateData = ['channelName' => $channelName];
                (new FaceDeviceService())->updateHouseDeviceChannelInfo($whereChannel, $updateData);
            }
            
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        if($res)
            return api_output(0,[]);
        return api_output_error(1001,'服务异常');
    }

    /**
     * 视频监控详情
     * @author lijie
     * @date_time 2022/01/13
     * @return \json
     */
    public function getCameraInfo()
    {
        $camera_id = $this->request->post('camera_id',0);
        $channelId = $this->request->post('channelId',0);
        $service_house_face_device = new HouseFaceDeviceService();
        try{
            $data = $service_house_face_device->getCameraInfo(['camera_id'=>$camera_id]);
            if ($data && !is_array($data)) {
                $data = $data->toArray();
            }
            if ($channelId) {
                $whereChannel = [];
                $whereChannel[] = ['device_type', '=', 'house_camera_device'];
                $whereChannel[] = ['device_id', '=', $camera_id];
                $whereChannel[] = ['channelId', '=', $channelId];
                $whereChannel[] = ['isDel', '=', 0];
                $channelInfo = (new FaceDeviceService())->getHouseDeviceChannelInfo($whereChannel);
                if ($channelInfo && !is_array($channelInfo)) {
                    $channelInfo = $channelInfo->toArray();
                }
                fdump_api($channelInfo, '$channelInfo');
                if (!empty($channelInfo)) {
                    $data['channel_id'] = $channelInfo['channel_id'];
                    $data['channelId'] = $channelInfo['channelId'];
                    $data['channelName'] = $channelInfo['channelName'];
                }
            }
            
            if (isset($data['sort']) && !$data['sort']) {
                $data['sort'] = 0;
            }
            $gatewayinfo = isset($data['gatewayinfo']) && $data['gatewayinfo'] ? json_decode($data['gatewayinfo'], 1) : [];
            $data['gatewayinfo'] = $gatewayinfo;
            $data['isup_version'] = isset($gatewayinfo['isup_version']) && $gatewayinfo['isup_version'] ? $gatewayinfo['isup_version'] : '';
            $data['brand_id'] = trim($data['brand_id']);


            $syn_type = SynOverviewConst::SYN_CAMERA_DEVICE;
            $getParams = [
                'syn_type' => $syn_type, 'syn_id' => $camera_id, 'to_id' => 0,
            ];
            $synOverView = (new SynOverviewService())->getSynOverview($getParams);
            if ($synOverView && !is_array($synOverView)) {
                $synOverView = $synOverView->toArray();
            }
            if (!empty($synOverView)) {
                if (isset($synOverView['syn_time']) && intval($synOverView['syn_time']) > 0) {
                    $synOverView['syn_time_text'] = date('Y-m-d H:i:s', $synOverView['syn_time']);
                }
                $data['synOverView'] = $synOverView;
            } else {
                // 查询下状态
                $getParams = [
                    'deviceType' => DeviceConst::DEVICE_TYPE_CAMERA, 'device_id' => $camera_id, 'thirdProtocol' => $data['thirdProtocol'],
                ];
                $synOverViewInfo = (new SynOverviewService())->getDeviceBindInfo($getParams);
                if ($synOverViewInfo && !is_array($synOverViewInfo)) {
                    $synOverViewInfo = $synOverViewInfo->toArray();
                }
                if (!empty($synOverViewInfo)) {
                    if (isset($synOverViewInfo['update_time']) && intval($synOverViewInfo['update_time']) > 0) {
                        $synOverViewInfo['syn_time_text'] = date('Y-m-d H:i:s', $synOverViewInfo['update_time']);
                    } elseif (isset($synOverViewInfo['add_time']) && intval($synOverViewInfo['add_time']) > 0) {
                        $synOverViewInfo['add_time'] = date('Y-m-d H:i:s', $synOverViewInfo['add_time']);
                    }
                    if (isset($synOverViewInfo['err_reason']) && $synOverViewInfo['err_reason']) {
                        $synOverViewInfo['syn_msg'] = trim($synOverViewInfo['err_reason']);
                    } elseif (isset($synOverViewInfo['syn_status_txt']) && $synOverViewInfo['syn_status_txt']) {
                        $synOverViewInfo['syn_msg'] = trim($synOverViewInfo['syn_status_txt']);
                    }
                    $synOverViewInfo = unsetParam($synOverViewInfo, ['update_time', 'add_time', 'err_reason', 'syn_status_txt']);
                    $data['synOverView'] = $synOverViewInfo;
                }
                
            }
            
            /**
             * 去除前缀$edit_ 就是对应字段对应是否可编辑权限
             */
            $is_edit = false;
            $edit_brand_id           = $is_edit;
            $edit_thirdProtocol      = $is_edit;
            $edit_device_type        = $is_edit;
            $edit_camera_name        = $is_edit;
            $edit_camera_sn          = $is_edit;
            $edit_device_code        = $is_edit;
            $edit_product_model      = $is_edit;
            $edit_thirdLoginName     = $is_edit;
            $edit_thirdLoginPassword = $is_edit;
            $edit_open_time          = $is_edit;
            $edit_remark             = $is_edit;
            $edit_edit_param         = $is_edit;
            $edit_is_support_look    = $is_edit;
            if (isset($data['thirdProtocol']) && $data['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_6000C) {
                $no_edit = true;
                $edit_brand_id           = $no_edit;
                $edit_thirdProtocol      = $no_edit;
                $edit_device_type        = $no_edit;
                $edit_camera_sn          = $no_edit;
                $edit_device_code        = $no_edit;
                $edit_product_model      = $no_edit;
                $edit_thirdLoginName     = $no_edit;
                $edit_thirdLoginPassword = $no_edit;
                $edit_open_time          = $no_edit;
                $edit_remark             = $no_edit;
                $edit_edit_param         = $no_edit;
            }
            if (isset($data['thirdProtocol'])) {
                switch ($data['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        $brand_key = DeviceConst::DH_BRAND_KEY;
                        break;
                    case DeviceConst::HIK_YUNMO_WAIBU:
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    case DeviceConst::HIK_YUNMO_NEIBU_6000C:
                        $brand_key = DeviceConst::HIK_BRAND_KEY;
                        break;
                }
                $serviceFaceDevice = new FaceDeviceService();
                if (isset($brand_key) && isset($serviceFaceDevice->thirdProtocolShowArr[$brand_key])) {
                    $thirdProtocol = $serviceFaceDevice->thirdProtocolShowArr[$brand_key];
                    $thirdProtocolInfo  = $thirdProtocol[DeviceConst::HIK_YUNMO_NEIBU_6000C];
                    $protocolTitle      = $thirdProtocolInfo['thirdTitle'];
                    $data['protocolTitle'] = $protocolTitle;
                } else {
                    $data['protocolTitle'] = '无';
                }
            }
            
            // 控制编辑权限
            $editAuth = [
                'edit_brand_id'           => $edit_brand_id,
                'edit_thirdProtocol'      => $edit_thirdProtocol,
                'edit_device_type'        => $edit_device_type,
                'edit_camera_name'        => $edit_camera_name,
                'edit_camera_sn'          => $edit_camera_sn,
                'edit_device_code'        => $edit_device_code,
                'edit_product_model'      => $edit_product_model,
                'edit_thirdLoginName'     => $edit_thirdLoginName,
                'edit_thirdLoginPassword' => $edit_thirdLoginPassword,
                'edit_open_time'          => $edit_open_time,
                'edit_remark'             => $edit_remark,
                'edit_edit_param'         => $edit_edit_param,
                'edit_is_support_look'    => $edit_is_support_look,
            ];
            $data['editAuth'] = $editAuth;
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 查询公共区域和楼栋列表
     * @return \json
     */
    public function getHousePosition()
    {
        $village_id = 50;
        if(!$village_id) {
            return api_output_error(1001,'必传参数缺失');
        }
        // 查询楼栋
        $village_service = new HouseVillageService();
        try {
            $list = $village_service->getHousePosition($village_id);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 视频权限申请列表
     * @author lijie
     * @date_time 2022/01/14
     * @return \json
     */
    public function getReplyList()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        $date = $this->request->post('date');
        $start_time = $this->request->post('start_time',0);
        $end_time = $this->request->post('end_time',0);
        $camera_sn = $this->request->post('camera_sn','');
        $camera_name = $this->request->post('camera_name','');
        if ($date&&isset($date[0])&&$date[0]&&isset($date[1])&&$date[1]) {
            $start_time = strtotime($date[0]);
            $end_time = strtotime($date[1] . ' 23:59:59');
        }
        $reply_status = $this->request->post('reply_status','');
        $name = $this->request->post('name','');
        $phone = $this->request->post('phone','');
        if($reply_status){
            $where[] = ['reply_status','=',$reply_status];
        }
        if($name) {
            $where[] = ['name','=',$name];
        }
        if($phone) {
            $where[] = ['phone','=',$phone];
        }
        if($start_time) {
            $where[] = ['start_time','>=',$start_time];
        }
        if($end_time) {
            $where[] = ['end_time','<=',$end_time];
        }
        $where[] = ['village_id','=',$village_id];
        $field = '*';
        $order = 'id DESC';
        $param = [
            'camera_sn' => $camera_sn,
            'camera_name' => $camera_name,
        ];
        try{
            $data = $service_house_face_device->getCameraReplyList($where,$field,$page,$limit,$order,$param);
            $count = $service_house_face_device->getCameraReplyCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['list'] = $data;
        $res['count'] = $count;
        $res['total_limit'] = 15;
        return api_output(0,$res);
    }
	
    //获取视频类型
    public function getVmonitorTypeList(){
        $village_id = $this->adminUser['village_id'];
        $page = $this->request->post('page',0,'int');
        $page = $page>0 ? $page:0;
        $limit = $this->request->post('limit',20,'int');
        $limit = $limit>0 ? $limit:20;
        try{
            $houseCameraDeviceVtypeService=new HouseCameraDeviceVtypeService();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('xtype','in',array(1,2));
            $vtypeTmp=$houseCameraDeviceVtypeService->getOneData($whereArr);
            if(empty($vtypeTmp)){
                $insertData=array('village_id'=>$village_id);
                $insertData['vname']='普通视频监控';
                $insertData['xsort']=100;
                $insertData['status']=1;
                $insertData['xtype']=1;
                $insertData['add_time']=time();
                $houseCameraDeviceVtypeService->addOneData($insertData);
                $insertData['xtype']=2;
                $insertData['vname']='高空抛物视频监控';
                $houseCameraDeviceVtypeService->addOneData($insertData);
            }
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('del_time','=',0);
            $data = $houseCameraDeviceVtypeService->getDataLists($whereArr,'*','xsort desc,id desc',$page,$limit);
            return api_output(0,$data);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
    }

    //添加一个视频类型
    public function addVmonitorType(){
        $village_id = $this->adminUser['village_id'];
        $vname = $this->request->post('vname',0,'trim');
        $vname= !empty($vname) ? htmlspecialchars($vname,ENT_QUOTES) :'';
        $xsort = $this->request->post('xsort',0,'int');
        $xsort = $xsort>0 ? $xsort:0;
        $status = $this->request->post('status',0,'int');
        $status = $status>0 ? $status:0;
        $id = $this->request->post('id',0,'int');
        $id = $id>0 ? $id:0;
        try{
            $houseCameraDeviceVtypeService=new HouseCameraDeviceVtypeService();
            if($id>0){
                $whereArr=array();
                $whereArr[]=array('village_id','=',$village_id);
                $whereArr[]=array('id','=',$id);
                $saveData=array();
                $saveData['vname']=$vname;
                $saveData['xsort']=$xsort;
                $saveData['status']=$status;
                $saveData['update_time']=time();
                $ret=$houseCameraDeviceVtypeService->updateOneData($whereArr,$saveData);
                return api_output(0,$id);
            }else{
                $insertData=array('village_id'=>$village_id);
                $insertData['vname']=$vname;
                $insertData['xsort']=$xsort;
                $insertData['status']=$status;
                $insertData['xtype']=0;
                $insertData['add_time']=time();
                $insert_id=$houseCameraDeviceVtypeService->addOneData($insertData);
                return api_output(0,$insert_id);
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }

    }

    public function delVmonitorType()
    {
        $village_id = $this->adminUser['village_id'];
        $idd = $this->request->post('idd', 0, 'int');
        $idd = $idd > 0 ? $idd : 0;
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $village_id);
        $whereArr[] = array('id', '=', $idd);
        try {
            $houseCameraDeviceVtypeService = new HouseCameraDeviceVtypeService();
            $vtypeTmp = $houseCameraDeviceVtypeService->getOneData($whereArr);
            if (empty($vtypeTmp)) {
                return api_output(1002, [], '删除的数据不存在！');
            }
            $ret = $houseCameraDeviceVtypeService->updateOneData($whereArr, ['del_time' => time()]);
            return api_output(0, $idd);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    /**
     * 视频权限申请列表
     * @author lijie
     * @date_time 2022/01/14
     * @return \json
     */
    public function getReplyInfo()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        $id = $this->request->post('id',0);
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $field = true;
        try{
            $houseVillageService=new HouseVillageService();
            $data = $service_house_face_device->getCameraReplyDetail($where,$field);
            if($data && isset($data['phone']) && !empty($data['phone'])){
                $data['phone']= $houseVillageService->phoneDesensitizationV($data['phone'],$village_id);
            }
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['info'] = $data;
        return api_output(0,$res);
    }


    /**
     * 视频权限申请列表
     * @author lijie
     * @date_time 2022/01/14
     * @return \json
     */
    public function checkReplyInfo()
    {
        $village_id = $this->adminUser['village_id'];
        $service_house_face_device = new HouseFaceDeviceService();
        $id = $this->request->post('id',0);
        $reply_status = $this->request->post('reply_status',0);
        $reason = $this->request->post('reason');
        $reason = trim($reason);
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        if (!$reply_status){
            return api_output(1002, [], '缺少审核状态！');
        }
        if($reply_status==3&&empty($reason)) {
            return api_output(1002, [], '审核拒绝请填写理由！');
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $param = [
            'reply_status' => $reply_status,
            'reason' => $reason?$reason:'',
            'reply_time' => time(),
        ];
        try{
            $data = $service_house_face_device->saveCameraReply($where,$param);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res['info'] = $data;
        return api_output(0,$res);
    }


    /**
     * 查询视频类型
     * @author: liukezhu
     * @date : 2022/7/23
     * @return \json
     */
    public function getCameraType(){
        $village_id = $this->adminUser['village_id'];
        try{
            $data = (new HouseFaceDeviceService())->getCameraDeviceType($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,$data);
    }
    
    public function getCameraThirdProtocols() {
        $village_id = $this->adminUser['village_id'];
        try{
            $data = (new HouseFaceDeviceService())->getCameraThirdProtocols($village_id);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        return api_output(0,['list' => $data]);
    }

	/**
	 * 获取监控设备的信息（如果有云，则获取云）
	 * @return \json
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 */
	public function getDeviceInfo()
	{
		$village_id = $this->adminUser['village_id'];
		$serviceHouseFaceDevice = new HouseFaceDeviceService();
		if (empty($village_id)){
			return api_output(1002, [], '请先登录到小区后台！');
		}
		$camera_id = $this->request->post('camera_id',0);
		if(!$camera_id){
			return api_output_error(1001,'缺少必传参数');
		}
        $channelId = $this->request->post('channelId','','trim');
        $channel_id = $this->request->post('channel_id',0,'intval');
		$field = 'camera_id,camera_name,camera_sn,lng,lat,add_time,last_time,village_id,thirdProtocol,cloud_is_encrypt,cloud_device_id,gatewayinfo';
		$cameraInfo = $serviceHouseFaceDevice->getCameraInfo(['camera_id'=>$camera_id,'village_id'=>$village_id],$field);
	 
		$cameraDeviceInfo = [];
		$protocol         = ''; //协议，目前仅支持大华云睿 、 海康云牟内部协议
		$protocolTitle    = ''; //协议，目前仅支持大华云睿 、 海康云牟内部协议
        $cameraVideo      = [];
        $networkInfo      = [];
		if ($cameraInfo){
			$serviceFaceDevice = new FaceDeviceService();
			$cameraInfo = is_array($cameraInfo) ? $cameraInfo : $cameraInfo->toArray();
			
			
			if ($cameraInfo['thirdProtocol'] == DeviceConst::DH_YUNRUI){ //大华云睿
				$brand_key = DeviceConst::DH_BRAND_KEY;
				if (isset($serviceFaceDevice->thirdProtocolShowArr[$brand_key])) {
					$thirdProtocol = $serviceFaceDevice->thirdProtocolShowArr[$brand_key];
					$thirdProtocolInfo  = $thirdProtocol[DeviceConst::DH_YUNRUI];
					$protocol           = $thirdProtocolInfo['thirdProtocol'];
					$protocolTitle      = $thirdProtocolInfo['thirdTitle'];;
				}else{
					$protocol      = DeviceConst::DH_YUNRUI;
					$protocolTitle = DeviceConst::DH_YUNRUI_TITLE;
				}
				try {
					$deviceInfo = (new FaceDHYunRuiCloudDeviceService())->getDeviceInfo($cameraInfo['camera_sn']);
					$cameraVideo = $serviceFaceDevice->getCameraUrl($cameraInfo['camera_id']);
					if ($deviceInfo['code'] == 0 && $deviceInfo['success']){
						$cameraDeviceInfo = $deviceInfo['data'];
					}else{
						return api_output_error(1001,'云睿反馈：'.$deviceInfo['message']);
					}
				}catch (\RuntimeException $e){
					return api_output_error(1001,$e->getMessage());
				}
			}else if($cameraInfo['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_SHEQU) { //海康云牟内部社区协议
				$brand_key = DeviceConst::HIK_BRAND_KEY;
				if (isset($serviceFaceDevice->thirdProtocolShowArr[$brand_key])) {
					$thirdProtocol = $serviceFaceDevice->thirdProtocolShowArr[$brand_key];
					$thirdProtocolInfo  = $thirdProtocol[DeviceConst::HIK_YUNMO_NEIBU_SHEQU];
					$protocol           = $thirdProtocolInfo['thirdProtocol'];
					$protocolTitle      = $thirdProtocolInfo['thirdTitle'];;
				}else{
					$protocol      = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
					$protocolTitle = DeviceConst::HIK_YUNMO_NEIBU_SHEQU_TITLE;
				}
				try {
					$deviceInfo = (new FaceHikCloudNeiBuDeviceService())->getDeviceDetail($cameraInfo['cloud_device_id']);
					$cameraVideo = $serviceFaceDevice->getCameraUrl($cameraInfo['camera_id']);
					if ($deviceInfo['code'] == 200){
						$cameraDeviceInfo = $deviceInfo['data'];
					}else{
						return api_output_error(1001,'海康云牟反馈：'.$deviceInfo['message']);
					}
				}catch (\RuntimeException $e){
					return api_output_error(1001,$e->getMessage());
				}
			}else if($cameraInfo['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_6000C) { //6000C社区边缘
                $brand_key = DeviceConst::HIK_BRAND_KEY;
                if (isset($serviceFaceDevice->thirdProtocolShowArr[$brand_key])) {
                    $thirdProtocol = $serviceFaceDevice->thirdProtocolShowArr[$brand_key];
                    $thirdProtocolInfo  = $thirdProtocol[DeviceConst::HIK_YUNMO_NEIBU_6000C];
                    $protocol           = $thirdProtocolInfo['thirdProtocol'];
                    $protocolTitle      = $thirdProtocolInfo['thirdTitle'];;
                }else{
                    $protocol      = DeviceConst::HIK_YUNMO_NEIBU_6000C;
                    $protocolTitle = DeviceConst::HIK_YUNMO_NEIBU_6000C_TITLE;
                }
                try {
                    $deviceInfo = (new FaceHikCloudNeiBuDeviceService())->getDeviceDetail($cameraInfo['cloud_device_id']);
                    $cameraVideo = (new Hik6000CCameraService())->getDeviceDetail($cameraInfo['camera_id']);
                    if ($deviceInfo['code'] == 200){
                        $cameraDeviceInfo = $deviceInfo['data'];
                    }else{
                        return api_output_error(1001,'海康云牟反馈：'.$deviceInfo['message']);
                    }
                }catch (\RuntimeException $e){
                    return api_output_error(1001,$e->getMessage());
                }
            }else if(in_array($cameraInfo['thirdProtocol'], [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM])) { 
                //Hik Device Gateway 海康网关   
                $brand_key = DeviceConst::HIK_BRAND_KEY;
                if (isset($serviceFaceDevice->thirdProtocolShowArr[$brand_key])) {
                    $thirdProtocol = $serviceFaceDevice->thirdProtocolShowArr[$brand_key];
                    $thirdProtocolInfo  = $thirdProtocol[$cameraInfo['thirdProtocol']];
                    $protocol           = $thirdProtocolInfo['thirdProtocol'];
                    $protocolTitle      = $thirdProtocolInfo['thirdTitle'];;
                }
                if (!$cameraInfo['cloud_device_id']) {
                    return api_output_error(1001,'设备还未同步至[Hik Device Gateway 海康网关]');
                }
                try {
                    $params = [
                        'device_equipment_type' => DeviceConst::DEVICE_TYPE_CAMERA, 'village_id' => $village_id, 'device_sn' => $cameraInfo['camera_sn'], 
                        'device_id' => $camera_id, 'thirdProtocol' => $cameraInfo['thirdProtocol'],
                    ];
                    $deviceInfo = (new HikGateWayDeviceService())->getHikGatewayDeviceInfoBox($cameraInfo, $params);
                    $cameraVideo = [];
                    $cameraVideo['liveResult'] = (new HikGateWayDeviceService())->getChannelsBox($cameraInfo, $params);
                    $networkInfo = (new HikGateWayDeviceService())->getDeviceNetworkBox($cameraInfo, $params);
                    
//                    $infoHttpHosts = (new HikGateWayDeviceService())->setSingleSubscribeDeviceMgmt($cameraInfo, $params);
                    $infoHttpHosts = (new HikGateWayDeviceService())->getHttpHosts($cameraInfo, $params);
                    fdump_api($infoHttpHosts, '$infoHttpHosts');
                    if ($deviceInfo['code'] == 200){
                        $cameraDeviceInfo = $deviceInfo['DeviceInfo'];
                        $cameraDeviceInfo['cloud_device_id'] = $cameraInfo['cloud_device_id'];
                        if (isset($cameraDeviceInfo['deviceType']) && $cameraDeviceInfo['deviceType']) {
                            $cameraDeviceInfo['deviceTypeText'] = (new HikGateWayDeviceService())->getDeviceTypeName($cameraDeviceInfo['deviceType']);
                        }
                    }else{
                        return api_output_error(1001,'海康网关反馈：'.$deviceInfo['message']);
                    }
                }catch (\RuntimeException $e){
                    return api_output_error(1001,$e->getMessage());
                }
            }
            if($cameraVideo && !isset($cameraVideo['look_url'])){
                $cameraVideo['look_url']='';
                $cameraVideo['lookUrlType']='';
                $cameraVideo['liveResult']=array('desc'=>'未获取到信息！');
            }
            if($cameraDeviceInfo && isset($cameraDeviceInfo['deviceStatus'])){
                $camera_status=$cameraDeviceInfo['deviceStatus']==1 ? 0:1;
                $serviceHouseFaceDevice->saveCamera(array('camera_id'=>$cameraInfo['camera_id']),array('camera_status'=>$camera_status));
            }
		}
		
		$data = [
			'protocol'          => $protocol,
			'protocolTitle'     => $protocolTitle,
			'cameraDeviceInfo'  => $cameraDeviceInfo,
            'cameraVideo'       => $cameraVideo,
            'networkInfo'       => $networkInfo,
		];
		return api_output(0,$data);
	}

    /**
     * 获取监控地址
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
	public function getLiveAddress() {
        $village_id = $this->adminUser['village_id'];
        $serviceHouseFaceDevice = new HouseFaceDeviceService();
        $deviceHkNeiBuHandleService=new DeviceHkNeiBuHandleService();
        $current_village_id = $this->request->post('current_village_id', 0, 'intval');
        if($current_village_id){
            $village_id= $current_village_id;
        }
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $camera_id = $this->request->post('camera_id',0);
        $channelId = $this->request->post('channelId','','trim');
        $channel_id = $this->request->post('channel_id',0,'intval');
        if(!$camera_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $look_url    = '';
        $lookUrlType = '';
        $field = 'camera_id,camera_name,camera_sn,brand_name,lng,lat,add_time,last_time,thirdProtocol,cloud_is_encrypt,cloud_device_id,look_url,lookUrlType';
        $cameraInfo = $serviceHouseFaceDevice->getCameraInfo(['camera_id'=>$camera_id,'village_id'=>$village_id],$field);
        $gatewayinfo = [];
        if ($cameraInfo){
            $cameraInfo = is_array($cameraInfo) ? $cameraInfo : $cameraInfo->toArray();
            if($cameraInfo['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_6000C) {
                try {
                    $cameraVideo = (new Hik6000CCameraService())->getDeviceDetail($cameraInfo['camera_id']);
                    if (isset($cameraVideo['liveResult']['flv']) && $cameraVideo['liveResult']['flv']) {
                        $look_url    = $cameraVideo['liveResult']['flv'];
                        $lookUrlType = 'flv';
                    }
                }catch (\RuntimeException $e){
                    return api_output_error(1001,$e->getMessage());
                }
            }

            if(in_array($cameraInfo['thirdProtocol'], [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM])) {
                // 海康网关
                $params = [
                    'device_equipment_type' => DeviceConst::DEVICE_TYPE_CAMERA, 'village_id' => $village_id, 'channelId' => $channelId,
                    'device_sn' => $cameraInfo['camera_sn'], 'device_id' => $camera_id, 'thirdProtocol' => $cameraInfo['thirdProtocol'],
                ];
                $liveResult = (new HikGateWayDeviceService())->getChannelsBox($cameraInfo, $params);
                if ((!isset($liveResult['code']) || !$liveResult['code']) && !empty($liveResult) && isset($liveResult[0]) && !empty($liveResult[0])) {
                    $preview = isset($liveResult[0]['preview']) ? $liveResult[0]['preview'] : [];
                    if ($channelId) {
                        foreach ($liveResult as $resultItem) {
                            if (isset($resultItem['preview']['channelId']) && trim($resultItem['preview']['channelId']) == trim($channelId)) {
                                $preview = $resultItem['preview'];
                                break;
                            }
                        }
                    }
                    $look_url = isset($preview['look_url']) && $preview['look_url'] ? trim($preview['look_url']) : '';
                    $lookUrlType = isset($preview['lookUrlType']) && $preview['lookUrlType'] ? trim($preview['lookUrlType']) : '';
                    $gatewayinfo = isset($preview['config']) ? $preview['config'] : [];
                }
            }
            if($channel_id>0){
                $whereArr=array('channel_id'=>$channel_id,'device_id'=>$camera_id,'village_id'=>$village_id,'isDel'=>'0');
                $deviceChannelInfo=$deviceHkNeiBuHandleService->getOneHouseDeviceChannel($whereArr,'*');
                if($deviceChannelInfo && isset($deviceChannelInfo['hlsHd']) && $deviceChannelInfo['hlsHd']){
                    $look_url=$deviceChannelInfo['hlsHd'];
                    $lookUrlType='hls';
                }elseif ($deviceChannelInfo && isset($deviceChannelInfo['hls']) && $deviceChannelInfo['hls']){
                    $look_url=$deviceChannelInfo['hls'];
                    $lookUrlType='hls';
                }
            }
        }
        if (!$look_url || !$lookUrlType) {
            $look_url = isset($cameraInfo['look_url']) && trim($cameraInfo['look_url']) ? trim($cameraInfo['look_url']) : '';
            $lookUrlType = isset($cameraInfo['lookUrlType']) && trim($cameraInfo['lookUrlType']) ? trim($cameraInfo['lookUrlType']) : '';
        }
        $camera_name = isset($cameraInfo['camera_name']) ? trim($cameraInfo['camera_name']) : '';
        $brand_name = isset($cameraInfo['brand_name']) ? trim($cameraInfo['brand_name']) : '';
        $data = [
            'look_url' => $look_url,
            'lookUrlType' => $lookUrlType,
            'gatewayinfo' => $gatewayinfo,
            'camera_name' => $camera_name,
            'brand_name' => $brand_name,
        ];
        if (!empty($gatewayinfo)) {
            $networkHost = isset($gatewayinfo['networkHost']) && $gatewayinfo['networkHost'] ? $gatewayinfo['networkHost'] : '';
            $rtspPort = isset($gatewayinfo['rtspPort']) && $gatewayinfo['rtspPort'] ? $gatewayinfo['rtspPort'] : '';
            $httpPort = isset($gatewayinfo['httpPort']) && $gatewayinfo['httpPort'] ? $gatewayinfo['httpPort'] : '';
            $lookUrlArr = explode('/dac/', $look_url);
            if ($networkHost && $rtspPort && isset($lookUrlArr[0]) && isset($lookUrlArr[1]) && $lookUrlArr[0] && strpos($lookUrlArr[0], 'rtsp://') !== false) {
                $replaceStr = "rtsp://{$networkHost}:{$rtspPort}";
                $look_url = str_replace($lookUrlArr[0], $replaceStr, $look_url);
                $data['look_url'] = $look_url;
            }
            if ($networkHost) {
                $gatewayinfo['host'] = $networkHost;
            }
            if ($httpPort) {
                $gatewayinfo['port'] = $httpPort;
            }
            $data['gatewayinfo'] = $gatewayinfo;
            $data['gatewayPluginDownload'] = "https://o2o-demo-img.pigcms.com/static/HCWebSDKPlugin.exe";
        }
        return api_output(0,$data);
    }

    /**
     * 查询社区下的设备列表
     * @return \json
     * @throws Exception
     */
    public function getDeviceByCommunityId() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        try {
            $queueData = [
                'jobType'       => 'hik6000CGetDeviceByCommunityId',
                'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_6000C,
                'village_id'    => $village_id,
            ];
            $job_id = $this->traitCommonHikCloud($queueData);
            $data = [
                'job_id' => $job_id
            ];
//            $data = (new Hik6000CCameraService())->getDeviceByCommunityId($village_id);
        }catch (\RuntimeException $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * 获取对应设备告警事件.
     * @return \think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeviceAlarmEventList() {
        $village_id = $this->adminUser['village_id'];
        $serviceHouseFaceDevice = new HouseFaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $camera_id = $this->request->post('camera_id',0);
        $page = $this->request->post('page',1);
        $pageSize = $this->request->post('pageSize',15);
        if(!$camera_id){
            return api_output_error(1001,'缺少必传参数');
        }
        $field = 'camera_id,camera_name,camera_sn,lng,lat,add_time,last_time,thirdProtocol,cloud_is_encrypt,cloud_device_id';
        $cameraInfo = $serviceHouseFaceDevice->getCameraInfo(['camera_id'=>$camera_id,'village_id'=>$village_id],$field);
        if ($cameraInfo && !is_array($cameraInfo)) {
            $cameraInfo = $cameraInfo->toArray();
        }
        if(empty($cameraInfo)){
            return api_output_error(1001,'对应监控不存在');
        }
        $thirdProtocolArr = [DeviceConst::HIK_YUNMO_NEIBU_SHEQU, DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]; // 支持协议类型
        $alarm_list = [];
        $alarm_count = 0;
        if (!empty($cameraInfo)) {
            $thirdProtocol = isset($cameraInfo['thirdProtocol']) ? intval($cameraInfo['thirdProtocol']) : 0;
            if (!in_array($thirdProtocol, $thirdProtocolArr)){
                return api_output_error(1001,'当前类型设备不支持查看告警消息');
            }
            // todo 注意适配了大华其他的 这里要改
            $cloud_device_id = isset($cameraInfo['cloud_device_id']) ? trim($cameraInfo['cloud_device_id']) : 0;
            if (!$cloud_device_id){
                return api_output_error(1001,'设备未同步添加至对应平台无法获取告警信息');
            }
            $business_type = EventAlarmConst::HIK_NEI_BU_EVENT_ALARM;
            if ($thirdProtocol == DeviceConst::HIK_YUNMO_NEIBU_SHEQU) {
                $business_type = EventAlarmConst::HIK_NEI_BU_EVENT_ALARM;
            }
            if (in_array($thirdProtocol, [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM])) {
                $business_type = EventAlarmConst::HIK_DEVICE_GATEWAY_EVENT_ALARM;
            }
            $channelId = $this->request->post('channelId',0);
            $whereAlarm = [];
            $whereAlarm[] = ['business_type', '=', $business_type];
            $whereAlarm[] = ['device_id', '=', $cloud_device_id];
            if ($channelId) {
                $whereAlarm[] = ['channel_id', '=', $channelId];
            }
            // todo 目前仅支持高空抛物
            $alarm_list = (new DeviceHkNeiBuHandleService())->getCameraAlarmEventList($whereAlarm, true, 'event_time DESC, id DESC', $page, $pageSize);
            if ($alarm_list && !is_array($alarm_list)) {
                $alarm_list = $alarm_list->toArray();
            }
            fdump_api(['$whereAlarm' => $whereAlarm, '$alarm_list' => $alarm_list], '$alarm_list');
            foreach ($alarm_list as &$alarm) {
                $local_url = isset($alarm['local_url']) ? trim($alarm['local_url']) : '';
                $local_url && $local_url = replace_file_domain($local_url);
                $alarm['local_url'] = $local_url;
                $local_url && $alarm['picture_url'] = $local_url;
                if (isset($alarm['event_time']) && intval($alarm['event_time']) > 1) {
                    $alarm['event_time_txt'] = date('Y-m-d', $alarm['event_time']);
                }
            }
            $alarm_count = (new DeviceHkNeiBuHandleService())->getCameraAlarmEventCount($whereAlarm);
        }
        $data = [
            'alarm_list' => $alarm_list,
            'count' => $alarm_count,
            'cameraInfo' => $cameraInfo,
        ];
        return api_output(0,$data);
    }

    /**
     * 获取对应设备告警事件详情.
     */
    public function getDeviceAlarmInfo() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $id = $this->request->post('id',0);
        if(!$id){
            return api_output_error(1001,'缺少必传参数');
        }
        $whereAlarm = [];
        $whereAlarm[] = ['id', '=', $id];
        // todo 目前仅支持高空抛物
//        $whereAlarm[] = ['event_code', '=', 10213];
        $alarm = (new DeviceHkNeiBuHandleService())->getCameraAlarmEventInfo($whereAlarm);
        if ($alarm && !is_array($alarm)) {
            $alarm = $alarm->toArray();
        }
        $local_url = isset($alarm['local_url']) ? trim($alarm['local_url']) : '';
        $local_url && $local_url = replace_file_domain($local_url);
        $alarm['local_url'] = $local_url;
        if (isset($alarm['event_time']) && intval($alarm['event_time']) > 1) {
            $alarm['event_time_txt'] = date('Y-m-d', $alarm['event_time']);
        }
        $alarmShow = [];
        if (isset($alarm['event_id']) && $alarm['event_id']) {
            $alarmShow[] = [
                'label' => '事件ID',
                'type' => 'text',
                'value' => $alarm['event_id'],
            ];
        }
        if (isset($alarm['event_time_txt']) && $alarm['event_time_txt']) {
            $alarmShow[] = [
                'label' => '事件时间',
                'type' => 'text',
                'value' => $alarm['event_time_txt'],
            ];
        } elseif (isset($alarm['date_time']) && $alarm['date_time']) {
            $alarmShow[] = [
                'label' => '事件时间（UTC+08:00）',
                'type' => 'text',
                'value' => $alarm['date_time'],
            ];
        }
        if (isset($alarm['device_id']) && $alarm['device_id']) {
            $alarmShow[] = [
                'label' => '设备ID',
                'type' => 'text',
                'value' => $alarm['device_id'],
            ];
        }
        if (isset($alarm['device_name']) && $alarm['device_name']) {
            $alarmShow[] = [
                'label' => '设备名称',
                'type' => 'text',
                'value' => $alarm['device_name'],
            ];
        }
        if (isset($alarm['channel_id']) && $alarm['channel_id']) {
            $alarmShow[] = [
                'label' => '触发报警的设备通道号',
                'type' => 'text',
                'value' => $alarm['channel_id'],
            ];
        }
        if (isset($alarm['channel_name']) && $alarm['channel_name']) {
            $alarmShow[] = [
                'label' => '触发报警的设备通道名称',
                'type' => 'text',
                'value' => $alarm['channel_name'],
            ];
        }
        if (isset($alarm['device_model']) && $alarm['device_model']) {
            $alarmShow[] = [
                'label' => '设备型号',
                'type' => 'text',
                'value' => $alarm['device_model'],
            ];
        }
        if (isset($alarm['device_type']) && $alarm['device_type']) {
            $alarmShow[] = [
                'label' => '设备类型',
                'type' => 'text',
                'value' => $alarm['device_type'],
            ];
        }
        if (isset($alarm['event_description']) && $alarm['event_description']) {
            $alarmShow[] = [
                'label' => '事件描述',
                'type' => 'text',
                'value' => $alarm['event_description'],
            ];
        }
        if ($local_url) {
            $alarmShow[] = [
                'label' => '图片URL',
                'type' => 'image',
                'value' => $local_url,
            ];
        } elseif (isset($alarm['picture_url']) && $alarm['picture_url']) {
            $alarmShow[] = [
                'label' => '图片URL',
                'type' => 'image',
                'value' => $alarm['picture_url'],
            ];
        }
        if (isset($alarm['event_remark']) && $alarm['event_remark']) {
            $alarmShow[] = [
                'label' => '备注',
                'type' => 'text',
                'value' => $alarm['event_remark'],
            ];
        }
        $data = [
            'alarm' => $alarm,
            'alarmShow' => $alarmShow,
        ];
        return api_output(0,$data);
    }
}