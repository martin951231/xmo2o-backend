<?php

/*
 * 硬件对应前端接口
 *
 */

class House_hardwareAction extends BaseAction
{
    /**
     * 业主摄像头
     *
     * array (
     * 'camera_info' =>
     *            array (
     *                  0 =>
     *                      array (
     *                          'camera_id' => '1', // 摄像头id
     *                          'camera_name' => '摄像头1号', // 摄像头名称
     *                          'camera_sn' => '4F027D5PAGBD904', // 摄像头设备编号
     *                          'village_id' => '42',// 归属小区id
     *                          'lng' => '117.2115400',// 经纬度
     *                          'lat' => '31.7914320',// 经纬度
     *                          'camera_status' => '0',// 状态 0 正常
     *                          'add_time' => '1568271557', // 添加时间
     *                          'last_time' => '1587459738', // 最后修改时间
     *                          'floor_id' => '-1', // 单元id -1 为大门或者公共区域
     *                          'public_area_id' => '3', // 公共区id
     *                         'is_support_look' => '1', // 是否支持监控视频 下面的是对应视频流地址
     *                         'look_url' => 'https://cmgw-vpc.lechange.com:8890/LCO/4F027D5PAGBD904/0/0/20200416T034002/dev_4F027D5PAGBD904_20200416T034002.m3u8?proto=https',
     *                         'address_txt' => '小区大门（东门）',// 摄像头所在地址
     *                        ),
     *                  ),
     * )
     */
    public function user_camera_look() {
        // 查询用户业主相关信息
        $village_id = I('village_id');
        if (!$village_id) {
            $this->returnCode('30000001');
        }
        $pigcms_id = I('pigcms_id');
        if (empty($pigcms_id)) {
            $this->returnCode('20120004');
        }
        $name      = I('name');

        $db_user_bind = M('House_village_user_bind');
        $where_bind = array(
            'village_id' => $village_id,
            'pigcms_id'  => $pigcms_id,
        );
        $user_bind_info = $db_user_bind->where($where_bind)->find();

        $db_house_camera_device = D('House_camera_device');

        $whereChannel = [
            'a.village_id' => $village_id,
            'a.is_support_look' => 1,
            'a.camera_status' => 0,
        ];
        $camera_where = array(
            'village_id'      => $village_id,
            'is_support_look' => 1,
            'camera_status'   => 0
        );
        $camera_where['_string'] = "(floor_id>0 && floor_id={$user_bind_info['floor_id']}) OR (floor_id<=0)";
        $whereChannel['_string'] = "(a.floor_id>0 && a.floor_id={$user_bind_info['floor_id']}) OR (a.floor_id<=0)";
        if ($name) {
            $camera_where['_string'] .= ' AND camera_name like "%'.$name.'%"';
            $whereChannel['_string'] .= ' AND a.camera_name like "%'.$name.'%"';
        }

        $deviceQrCodeJudge = D('Config_customization')->deviceQrCodeJudge($village_id);
        if ($deviceQrCodeJudge) {
            $field1 = "a.*,b.channel_id,b.channelName,b.channelId,b.channelNo,b.channelNo,b.hls as channelHls, b.hlsHd as channelHlsHd,b.rtmp as channelRtmp,b.rtmpHd as channelRtmpHd,b.flv as channelFlv,b.flvHD as channelFlvHD,b.rtsp as channelRtsp,b.rtspHd as channelRtspHd";
            $camera_info = $db_house_camera_device
                ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_device_channel as b ON a.camera_id = b.device_id AND b.device_type='house_camera_device' AND b.isDel=0")
                ->where($whereChannel)->order('a.sort DESC, a.camera_id DESC')->field($field1)->select();
        } else {
            $camera_info = $db_house_camera_device->where($camera_where)->order('sort DESC, camera_id DESC')->select();
        }

        // 全部公共区域信息
        $public_area = M('House_village_public_area')->where(array('village_id'=>$village_id))->getField('public_area_id,public_area_id,public_area_name');
        // 单元
        $floor_info = M('House_village_floor')->where(array('floor_id'=>$user_bind_info['floor_id']))->getField('floor_id,floor_name,floor_layer');
        // 楼栋
        $single_info = M('House_village_single')->where(array('id'=>$user_bind_info['single_id']))->getField('single_id,single_id,single_name');

        $baseUrl = $this->config['site_url'];
        if ($camera_info) {
            $site_url = $this->config['site_url'];
            $httpSiteUrl = str_replace('https', 'http', $site_url);
            foreach($camera_info as &$val) {
                if (isset($val['channelName']) && trim($val['channelName'])) {
                    $val['camera_name'] .= ' ' . $val['channelName'];
                }
                if ($val['floor_id']==-1 && $val['public_area_id']) {
                    $txt = $public_area[$val['public_area_id']] ? $public_area[$val['public_area_id']]['public_area_name'] : '';
                    $val['address_txt'] = $this->config['house_name'] . $txt;
                } elseif ($val['floor_id']==-1) {
                    $val['address_txt'] = $this->config['house_name'] . '大门';
                } else {
                    $txt = $single_info[$val['single_id']] ? $single_info[$val['single_id']]['single_name'] : '';
                    $floor_txt = $floor_info[$val['floor_id']] ? $floor_info[$val['floor_id']]['floor_name'] : '';
                    if (!$txt && $floor_info[$val['floor_id']]) {
                        $txt = $floor_info[$val['floor_id']]['floor_layer'];
                    }
                    $txt_info = $txt . '_' . $floor_txt;
                    $val['address_txt'] = $txt_info;
                    $val['open_url'] = "";
                }
                if ($val['device_brand']=='iSecureCenter' && $val['camera_sn']) {
                    $val['open_url'] = $httpSiteUrl.'/shequ.php?g=House&c=iSecureCenter&a=videoPreviewFullScreen&from=user&camera_id='.$val['camera_sn']."&bindUserId={$pigcms_id}&village_id={$village_id}";
                    $val['videoPreviewUrl'] = $site_url.'/shequ.php?g=House&c=iSecureCenter&a=videoPreviewFullScreen&camera_id='.$val['camera_sn']."&bindUserId={$pigcms_id}&village_id={$village_id}";
                    $val['address_txt'] = $val['camera_name'];
                } else {
                    $val['videoPreviewUrl'] = '';
                }
                if ($val['device_brand']=='iSecureCenter' && $val['camera_sn']) {
                    $reply = $db_house_camera_device->getCameraReplyInfo($village_id,$val['camera_id'],$pigcms_id);
                    if($reply['status'] != 2){
                        $val['videoPreviewUrl'] = '';
                        $val['look_url'] = '';
                        $val['open_url'] = '';
                    } elseif($val['videoPreviewUrl']) {
                        $val['address_txt'] .= '(可看)';
                    }
                    $val['tip'] = isset($reply['tip'])?$reply['tip']:'';
                    $val['tipStatus'] = isset($reply['status'])?$reply['status']:2;
                    $val['tipBtn'] = isset($reply['btn'])?$reply['btn']:'好的';
                    $val['btnUrl'] = isset($reply['btnUrl'])?$reply['btnUrl']:'';
                } elseif ($val['device_brand']!='iSecureCenter' && $val['brand_id']!='iSecureCenter' && $val['look_url'] && strpos($val['look_url'], 'm3u8') !== false) {
                    $val['videoPreviewUrl'] = $site_url . "/shequ.php?g=House&c=iSecureCenter&a=videoH5FlvFullScreen&camera_id={$val['camera_sn']}&bindUserId={$pigcms_id}&from=user";
                    $val['open_url'] = $site_url . "/shequ.php?g=House&c=iSecureCenter&a=videoH5FlvFullScreen&camera_id={$val['camera_sn']}&bindUserId={$pigcms_id}&from=user";
                }
                if ($val['look_url'] && ($val['lookUrlType'] == 'flv' || $val['lookUrlType'] == 'hls')) {
                    // 新开页面看flv
                    $val['videoPreviewUrl'] = '';
                    $val['open_url'] = $baseUrl."/wap.php?g=Wap&c=DeviceMonitor&a=lookVideo&camera_id={$val['camera_id']}&isFlv=1";
                }
                if ($val['address_txt'] == '_') {
                    $val['address_txt'] = $val['camera_name'];
                }
            }
        } else {
            $camera_info = [];
        }
        $where['village_id'] = $village_id;
        $monitor_log_list = D('House_village_monitor_log')->getChannelList($where,1,200);
        if (!empty($monitor_log_list['list'])) {
            foreach ($monitor_log_list['list'] as $item) {
                $camera_info[] = [
                    'camera_type' => 'dahua',
                    'camera_id' => $item['camera_id'],
                    'open_time' => 0,
                    'address_txt' => $item['channelName'],
                    'is_support_look' => 1,
                    'village_id' => $village_id,
                    'look_url' => $item['videoTxt'],
                    'open_url' => $baseUrl."/wap.php?g=Wap&c=DeviceMonitor&a=lookVideo&camera_id={$item['camera_id']}",
                ];
            }
        }

        $res['camera_info'] = $camera_info;

        $res['title'] = $this->config['hawkEyeName']?$this->config['hawkEyeName']:'鹰眼服务';

        $this->returnCode(0,$res);
    }

}
