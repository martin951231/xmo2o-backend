<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      移动管理端 共用的 监控 控制器
 */
namespace app\community\controller\manage_api\common;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\Device\Hik6000CCameraService;
use app\community\model\service\Device\HikGateWayDeviceService;
use app\community\model\service\Device\HikGateWayEventService;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageService;
use app\consts\DeviceConst;

class CameraDeviceController extends BaseController
{
    /**
     * 移动管理端公共获取监控 没有控制
     * @return \json
     */
    public function cameraDeviceLinks() {
        $service_house_face_device = new HouseFaceDeviceService();
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if (isset($this->login_info['village_id'])) {
            $village_id = $this->login_info['village_id'];
        } else {
            $village_id = 0;
        }
        if (isset($this->login_info['property_id'])) {
            $property_id = $this->login_info['property_id'];
        } else {
            $property_id = 0;
        }
        $page  = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        if (!$page || $page <=0) {
            $page  = 0;
            $limit = 0;
        }
        $res = [];
        if (!$village_id && !$property_id) {
            $res['list']         = [];
            $res['count']        = 0;
            $res['limit']        = $limit;
        }
        if (!$village_id || $village_id <= 0) {
            $whereVillageColumn = [];
            $whereVillageColumn[] = ['property_id', '=', $property_id];
            $whereVillageColumn[] = ['status', '=', 1];
            $village_id_arr = (new HouseVillageService())->getVillageColumn($whereVillageColumn, 'village_id');
        }
        $name = $this->request->post('name','','trim');
        $where = [];
        $whereChannel=array();
        if ($name) {
            $where[] = ['camera_name','like',"%$name%"];
            $whereChannel[] = ['a.camera_name', 'like', '%'.$name.'%'];
        }
        $where[] = ['camera_status', 'in', [0,1]];
        $whereChannel[] = ['a.camera_status', 'in', [0,1]];
        if(isset($village_id_arr)) {
            $where[] = ['village_id', 'in', $village_id_arr];
            $whereChannel[] = ['a.village_id', 'in', $village_id_arr];
        } elseif ($village_id > 0) {
            $where[] = ['village_id', '=', $village_id];
            $whereChannel[] = ['a.village_id', '=', $village_id];
        }
        $where[] = ['look_url', '<>', ''];
        $order = 'sort DESC,camera_id DESC';
        $param = [
            'isFlv' => 1
        ];
        try{
            $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($village_id);
            if ($deviceQrCodeJudge) {
                $field1 = "a.*,b.channel_id,b.channelName,b.channelId,b.channelNo,b.channelNo,b.hls as channelHls, b.hlsHd as channelHlsHd,b.rtmp as channelRtmp,b.rtmpHd as channelRtmpHd,b.flv as channelFlv,b.flvHD as channelFlvHD,b.rtsp as channelRtsp,b.rtspHd as channelRtspHd";
                $order1 = "a.sort DESC,a.camera_id DESC";
                $data = $service_house_face_device->getListLeftJoinChannel($whereChannel,$field1,$page,$limit,$order1);
                $count = $service_house_face_device->getListLeftJoinChannelCount($whereChannel);
            } else {
                $field = 'camera_id,camera_name,camera_sn,village_id,floor_id,look_url,lookUrlType,thirdProtocol,camera_status';
                $data = $service_house_face_device->cameraLinkList($where, $field, $page, $limit, $order, $param);
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

    /**
     * 获取监控地址
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLiveAddress() {
        $arr = $this->getLoginInfo();
        if (isset($arr['status']) && $arr['status']!=1000) {
            return api_output($arr['status'],[],$arr['msg']);
        }
        if (isset($this->login_info['village_id'])) {
            $village_id = $this->login_info['village_id'];
        } else {
            $village_id = 0;
        }
        $current_village_id = $this->request->post('village_id', 0, 'intval');
        if($current_village_id>0){
            $village_id=$current_village_id;
        }
        $serviceHouseFaceDevice = new HouseFaceDeviceService();
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $camera_id = $this->request->post('camera_id',0);
        $channelId = $this->request->post('channelId',0);
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
}