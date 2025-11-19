<?php

/**
 * 大华摄像机
 * 目前实际调试设备类型 DH-SD4423-D-I 网络球型摄像机
 * Class DeviceMonitorAction
 */
class DeviceMonitorAction extends BaseAction{

    public function lookVideo() {
        $camera_id = I('camera_id');
        $isFlv     = I('isFlv');
        if ($camera_id && !$isFlv) {
            $where = [];
            $where['camera_id'] = $camera_id;
            $monitor = D('House_village_monitor')->where($where)->find();
            $changeIpUrl = C('config.changeIpUrl')?C('config.changeIpUrl'):'http://192.168.0.241:8808';
            $changeIpPort = C('config.cameraChangeIpPort')?C('config.cameraChangeIpPort'):'1935';
            if (isset($monitor['channelNum'])&&$monitor['channelNum']) {
                $monitor['videoTxt'] = "{$changeIpUrl}/live?port={$changeIpPort}&app=myapp&stream=mystream_{$monitor['channelNum']}";
            }
        } else {
            $where = [];
            $where['camera_id'] = $camera_id;
            $monitor = D('House_camera_device')->where($where)->find();
            $isVideo = 'flv';
            $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
            if(strpos($agent,'iphone') !== false || strpos($agent,'ipad') !== false || strpos($agent,'ipod') !== false){
                // todo 苹果换种视频流
                $whereChannel = [
                    'device_type' => 'house_camera_device',
                    'village_id'  => $monitor['village_id'],
                    'device_id'   => $camera_id,
                ];
                $channelInfo = M('House_device_channel')->where($whereChannel)->order('add_time DESC')->find();
                if (!$channelInfo['hls']) {
                    if ($monitor['brand_id'] == 1) {
                        if ($this->config['HikCloud6000CSwitch'] == 1) {
                            $params = [
                                'channelId'  => $channelInfo['channelId'],
                                'channel_id' => $channelInfo['channel_id'],
                                'param'      => ['protocol' => 4]
                            ];
                            $live = invoke_v20_service('\app\community\model\service\Device\Hik6000CCameraService/getLiveAddressNew',$params);
                        } else {
                            $params = [
                                'deviceId'      => $monitor['camera_sn'],
                                'thirdProtocol' => $monitor['thirdProtocol'],
                                'device_sn'     => $monitor['camera_sn'],
                                'village_id'    => $monitor['village_id'],
                                'device_id'     => $monitor['camera_id'],
                                'hkDeviceId'    => $monitor['cloud_device_id'],
                                'validateCode'    => $monitor['device_code'],
                            ];
                            $live = invoke_v20_service('\app\community\model\service\Device\FaceDeviceService/createDeviceLive',$params);
                        }
                    } else {
                        $channelId = isset($channelInfo['channelId']) && $channelInfo['channelId'] ? $channelInfo['channelId'] : '0';
                        $params = [
                            'deviceId' => $monitor['camera_sn'],
                            'channelId' => $channelId,
                            'param' => ['LiveType' => 'hls']
                        ];
                        $live = invoke_v20_service('\app\community\model\service\Device\FaceDHYunRuiCloudDeviceService/createDeviceLive',$params);
                    }
                    $live = $live['data']['retval'];
                    if (isset($live['hls']) && $live['hls']) {
                        $monitor['lookUrlType'] = 'hls';
                        $monitor['look_url'] = $live['hls'];
                    } elseif($live['liveUrl']) {
                        $monitor['lookUrlType'] = 'hls';
                        $monitor['look_url'] = $live['liveUrl'];
                    }
                    $isVideo = 'hls';
                } elseif (isset($monitor['lookUrlType']) && $monitor['lookUrlType'] == 'hls' && isset($monitor['look_url']) && $monitor['look_url']) {
                    $isVideo = 'hls';
                } else {
                    $monitor['lookUrlType'] = 'hls';
                    $monitor['look_url'] = $channelInfo['hls'];
                    $isVideo = 'hls';
                }
                
            }
            $baseUrl = $this->config['site_url'];
            if (isset($monitor['look_url']) && $monitor['look_url'] && strpos($baseUrl, 'https') !== false) {
                $monitor['look_url'] = str_replace('http://', 'https://', $monitor['look_url']);
            }
            if (isset($monitor['look_url']) && $monitor['look_url'] && $monitor['lookUrlType'] == 'flv') {
                $isVideo = 'flv';
                $monitor['videoTxt']    = $monitor['look_url'];
                $monitor['channelName'] = $monitor['camera_name'];
            } elseif (isset($monitor['look_url']) && $monitor['look_url'] && $monitor['lookUrlType'] == 'hls') {
                $isVideo = 'hls';
                $monitor['videoTxt']    = $monitor['look_url'];
                $monitor['channelName'] = $monitor['camera_name'];
            } else {
                $monitor = [];
            }
            fdump_api(['isVideo' => $isVideo, 'monitor' => $monitor], '1wap_monitor');
            if ($isVideo == 'flv') {
                // cdn flv资源地址
                $flvMinJs = D('House_village_monitor_log')->flvMinJs;
                $this->assign('monitor',$monitor);
                $this->assign('flvMinJs',$flvMinJs);
                $this->display();
            } elseif($isVideo == 'hls') {
                $videoMinJs = C('config.site_url') . '\tpl\Wap\pure\static\mall\js\video.min.js';
                $videoMinCss = C('config.site_url') . '\tpl\Wap\pure\static\mall\css\video-js.css';
                $this->assign('videoMinJs',$videoMinJs);
                $this->assign('videoMinCss',$videoMinCss);
                $this->assign('monitor',$monitor);
                
                $this->display('lookVideoHls');
            }
        }
    }
}