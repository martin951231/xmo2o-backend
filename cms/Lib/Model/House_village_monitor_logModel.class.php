<?php
/**
 * 大华摄像机  小区监控设备（硬盘录像机）
 * 目前实际调试设备类型 DH-SD4423-D-I 网络球型摄像机
 */
class House_village_monitor_logModel extends Model{

    //码流类型，主码流0，辅码流1
    public $subTypeArr = [
        0 => '辅码流',
        1 => '主码流',
        2 => '辅码流',
    ];
    // cdn fly格式视频播放资源地址
    public $flvMinJs = "https://cdn.bootcdn.net/ajax/libs/flv.js/1.6.2/flv.min.js";
    // cameraChangeIp 转换flv 服务ip 客户的 124.223.196.232  本地的 192.168.0.241
    // changeIpUrl 播放flv地址 客户的 124.223.196.232:待定  本地 http://192.168.0.241:8808
    // cameraChangeIpPort 播放flv地址的监听端口 客户的 待定  本地 1935
    // cameraChangeUrl 最终播放视频路径转换请求 客户的 待定 本地 http://192.168.0.241
    //

    public function getList($where,$page,$page_count)
    {
        if (!$where) {
            return array();
        }
        $count_arr = $this->where($where)->count();
        if (!$count_arr) {
            return array();
        }
        $sort = '`logId` ASC';
        $list = $this->field(true)->page($page, $page_count)->where($where)->order($sort)->select();
        if (!empty($list)) {
            foreach ($list as &$item) {
                if (isset($item['subType'])&&isset($this->subTypeArr[$item['subType']])) {
                    $item['subTypeTxt'] = $this->subTypeArr[$item['subType']];
                } else {
                    $item['subTypeTxt'] = '';
                }
                if (isset($item['addTime'])&&$item['addTime']) {
                    $item['addTimeTxt'] = date('Y-m-d H:i:s', $item['addTime']);
                }
                if (isset($item['updateTime'])&&$item['updateTime']) {
                    $item['updateTimeTxt'] = date('Y-m-d H:i:s', $item['updateTime']);
                }
            }
        }

        import('@.ORG.merchant_page');
        $p = new Page($count_arr,$page_count,'page');
        $arr = array(
            'list'	=>	isset($list)?$list:array(),
            'pagebar'	=>	$p->show(),
        );
        return	$arr;
    }


    public function getChannelList($where,$page,$page_count)
    {
        if (!$where) {
            return array();
        }
        if (!isset($where['status'])) {
            $where['status'] = ['neq', 4];
        }
        $count_arr = D('house_village_monitor')->where($where)->count();
        if (!$count_arr) {
            return array();
        }
        $sort = '`logId` ASC, `camera_id` ASC';
        $list = D('house_village_monitor')->field(true)->page($page, $page_count)->where($where)->order($sort)->select();
        if (!empty($list)) {
            $changeIpUrl = C('config.changeIpUrl')?C('config.changeIpUrl'):'http://192.168.0.241:8808';
            $changeIpPort = C('config.cameraChangeIpPort')?C('config.cameraChangeIpPort'):'1935';
            foreach ($list as &$item) {
                if (isset($item['subType'])&&isset($this->subTypeArr[$item['subType']])) {
                    $item['subTypeTxt'] = $this->subTypeArr[$item['subType']];
                } else {
                    $item['subTypeTxt'] = '';
                }
                if (isset($item['addTime'])&&$item['addTime']) {
                    $item['addTimeTxt'] = date('Y-m-d H:i:s', $item['addTime']);
                }
                if (isset($item['updateTime'])&&$item['updateTime']) {
                    $item['updateTimeTxt'] = date('Y-m-d H:i:s', $item['updateTime']);
                }
                if (isset($item['channelNum'])&&$item['channelNum']) {
                    $item['videoTxt'] = "{$changeIpUrl}/live?port={$changeIpPort}&app=myapp&stream=mystream_{$item['channelNum']}";
                }
            }
        }

        import('@.ORG.merchant_page');
        $p = new Page($count_arr,$page_count,'page');
        $arr = array(
            'list'	=>	isset($list)?$list:array(),
            'pagebar'	=>	$p->show(),
        );
        return	$arr;
    }

    public function videoRecorderAdd($data) {
        $logId = isset($data['logId'])&&$data['logId']?$data['logId']:0;
        $village_id = isset($data['village_id'])&&$data['village_id']?$data['village_id']:0;
        $property_id = isset($data['property_id'])&&$data['property_id']?$data['property_id']:0;
        if ($logId) {
            $where = [];
            $where['logId'] = $logId;
            $monitorLog = $this->where($where)->find();
        } else {
            $monitorLog = [];
        }
        $videoRecorderName = isset($data['videoRecorderName'])&&trim($data['videoRecorderName'])?trim($data['videoRecorderName']):'';
        $videoRecorderIp = isset($data['videoRecorderIp'])&&trim($data['videoRecorderIp'])?trim($data['videoRecorderIp']):'';
        $videoRecorderPort = isset($data['videoRecorderPort'])&&trim($data['videoRecorderPort'])?trim($data['videoRecorderPort']):'';
        $ipAddress = isset($data['ipAddress'])&&trim($data['ipAddress'])?trim($data['ipAddress']):'';
        $ipPort = isset($data['ipPort'])&&trim($data['ipPort'])?trim($data['ipPort']):'';
        $userName = isset($data['userName'])&&trim($data['userName'])?trim($data['userName']):'';
        $password = isset($data['password'])&&trim($data['password'])?trim($data['password']):'';
        $subType = isset($data['subType'])&&intval($data['subType'])?intval($data['subType']):2;
        $channelStart = isset($data['channelStart'])&&intval($data['channelStart'])?intval($data['channelStart']):1;
        $channelEnd = isset($data['channelEnd'])&&intval($data['channelEnd'])?intval($data['channelEnd']):1;

        if (!$videoRecorderName || !$videoRecorderIp || !$videoRecorderPort || !$ipAddress || !$ipPort || !$userName || !$password || !$channelStart || !$channelEnd) {
            throw new \Exception('必填项缺失');
        }
        if (!in_array($subType,[1,2])) {
            throw new \Exception('码流类型参数错误');
        }
        if ($monitorLog&&$channelEnd<$monitorLog['channelEnd']) {
            throw new \Exception("通道编号数不能小于{$monitorLog['channelEnd']}");
        }
        if ($channelEnd<$channelStart) {
            throw new \Exception("通道编号数不能小于{$channelStart}");
        }

        // ip去重
        $whereRepeat = [
            'videoRecorderIp' => $videoRecorderIp,
            'videoRecorderPort' => $videoRecorderPort,
        ];
        if ($village_id) {
            $whereRepeat['village_id'] = $village_id;
        }
        if ($logId) {
            $whereRepeat['logId'] = ['neq',$logId];
        }
        $repeatLogInfo = D('House_village_monitor_log')->where($whereRepeat)->field('logId,videoRecorderName')->find();
        if ($repeatLogInfo) {
            throw new \Exception("对应ip【{$videoRecorderIp}】和端口【{$videoRecorderPort}】，【{$repeatLogInfo['videoRecorderName']}，ID：{$repeatLogInfo['logId']}】已经存在了");
        }
        // 映射ip去重
        $whereRepeat = [
            'ipAddress' => $ipAddress,
            'ipPort' => $ipPort,
        ];
        if ($village_id) {
            $whereRepeat['village_id'] = $village_id;
        }
        if ($logId) {
            $whereRepeat['logId'] = ['neq',$logId];
        }
        $repeatLogInfo = D('House_village_monitor_log')->where($whereRepeat)->field('logId,videoRecorderName')->find();
        if ($repeatLogInfo) {
            throw new \Exception("对应映射ip【{$ipAddress}】和映射端口【{$ipPort}】，【{$repeatLogInfo['videoRecorderName']}，ID：{$repeatLogInfo['logId']}】已经存在了");
        }
        // 名称去重
        $whereRepeat = [
            'videoRecorderName' => $videoRecorderName,
        ];
        if ($village_id) {
            $whereRepeat['village_id'] = $village_id;
        }
        if ($logId) {
            $whereRepeat['logId'] = ['neq',$logId];
        }
        $repeatLogInfo = D('House_village_monitor_log')->where($whereRepeat)->field('logId,videoRecorderName')->find();
        if ($repeatLogInfo) {
            throw new \Exception("对应名称，【{$repeatLogInfo['videoRecorderName']}，ID：{$repeatLogInfo['logId']}】已经存在了");
        }
        $saveData = [
            'village_id' => $village_id,
            'property_id' => $property_id,
            'videoRecorderName' => $videoRecorderName,
            'videoRecorderIp' => $videoRecorderIp,
            'videoRecorderPort' => $videoRecorderPort,
            'ipAddress' => $ipAddress,
            'ipPort' => $ipPort,
            'userName' => $userName,
            'password' => $password,
            'subType' => $subType,
            'channelStart' => $channelStart,
            'channelEnd' => $channelEnd,
        ];
        if (empty($monitorLog)) {
            $saveData['addTime'] = time();
            $saveData['rtmpStatus'] = 0;
            $logId = D('House_village_monitor_log')->add($saveData);
            if (!$logId) {
                throw new \Exception("添加失败");
            }
            $this->batchToMonitor($logId, $saveData);
        } else {
            if (isset($monitorLog['village_id'])&&!$monitorLog['village_id']&&$village_id) {
                $saveData['village_id'] = $village_id;
            }
            if (isset($monitorLog['property_id'])&&!$monitorLog['property_id']&&$property_id) {
                $saveData['property_id'] = $property_id;
            }
            $where = [];
            $where['logId'] = $logId;
            $saveData['updateTime'] = time();
            $saveLogId = D('House_village_monitor_log')->where($where)->save($saveData);
            if ($saveLogId==false) {
                throw new \Exception("更新失败");
            }
            $this->batchToMonitor($logId, $saveData);
        }
        $param = [];
        $now_time = time();
        $param['type'] = 'daHuaRtmpChangeAll';
        $unique_id = 'daHuaRtmpChangeAll';
        if ($property_id) {
            $param['property_id'] = $property_id;
            $unique_id .='_'.$property_id;
        }
        if ($village_id) {
            $param['village_id'] = $village_id;
            $unique_id .='_'.$village_id;
        }
        $param['unique_id'] = $unique_id;
        $param['time'] = $now_time;
        $arr['param'] = serialize($param);
        $arr['plan_time'] = 0;
        $arr['space_time'] = 1800;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_monitor_change_video';
        $arr['time_type'] = 0;
        $arr['unique_id'] = $unique_id;
        $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $add_id = M('process_sub_plan')->data($arr)->add();
        if (!$add_id) {
            fdump_api(['大华摄像头添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'plan_sub/sub_monitor_change_video',1);
        }
        return $saveData;
    }

    public function batchToMonitor($logId,$logParam=[]) {
        if ($logId && empty($logInfo)) {
            $where = [];
            $where['logId'] = $logId;
            $logParam = $this->where($where)->find();
        }
        $channelStart = isset($logParam['channelStart'])&&$logParam['channelStart']?intval($logParam['channelStart']):1;
        $channelEnd = isset($logParam['channelEnd'])&&$logParam['channelEnd']?intval($logParam['channelEnd']):1;
        $userName = $logParam['userName'];
        $password = $logParam['password'];
        $ipAddress = $logParam['ipAddress'];
        $ipPort = $logParam['ipPort'];
        $subType = $logParam['subType'];
        $village_id = $logParam['village_id'];
        if (!$logId || !$channelStart || !$channelEnd || !$userName || !$password || !$ipAddress || !$ipPort || !$subType || !$village_id) {
            throw new \Exception("生成通道失败");
        }
        $subType -= 1;
        $data = [
            'camera_type' => isset($logParam['camera_type'])&&$logParam['camera_type']?intval($logParam['camera_type']):'dahua',
            'village_id' => $village_id,
            'property_id' => isset($logParam['property_id'])&&$logParam['property_id']?intval($logParam['property_id']):0,
            'userName' => $userName,
            'password' => $password,
            'ipAddress' => $ipAddress,
            'ipPort' => $ipPort,
            'subType' => $subType,
            'logId' => $logId,
            'status' => 1,
            'rtmpStatus' => 0,
        ];
        $whereRepeat = [
            'camera_type' => $data['camera_type'],
            'village_id' => $village_id,
            'property_id' => $data['property_id'],
            'ipAddress' => $data['ipAddress'],
            'ipPort' => $data['ipPort'],
            'subType' => $data['subType'],
            'status' => ['neq', 4],
        ];
        $db_house_village_monitor = D('House_village_monitor');
        $addTime = time();
        $changeIp = C('config.cameraChangeIp')?C('config.cameraChangeIp'):'192.168.0.241';
        $cameraArr = [];
        $whereDel = [];
        $whereDel['logId'] = $logId;
        $whereDel['rtmpStatus'] = 0;
        $dataDel = [
            'status' => 4,
            'updateTime' => $addTime,
        ];
        $db_house_village_monitor->where($whereDel)->save($dataDel);
        for ($i = $channelStart; $i <= $channelEnd; $i++) {
            $channelNum = $i;
            $channelName = '通道'.$i;
            $rtspUrl = "rtsp://{$userName}:{$password}@{$ipAddress}:{$ipPort}/cam/realmonitor?channel={$channelNum}&subtype={$subType}";
            $rtmpUrl = 'rtmp://'.$changeIp.'/myapp/mystream_'.$logId.'_'.$i;
            $whereRepeat['channelNum'] = $channelNum;
            $repeatInfo = $db_house_village_monitor->where($whereRepeat)->find();
            $data['channelName'] = $channelName;
            $data['channelNum'] = $channelNum;
            $data['rtspUrl'] = $rtspUrl;
            $data['rtmpUrl'] = $rtmpUrl;
            $data['ffmpegOder'] = 'ffmpeg -rtsp_transport tcp -i "'.$rtspUrl.'" -c:v libx264 -f flv '.$rtmpUrl;
            if (empty($repeatInfo)||!isset($repeatInfo['camera_id'])||!$repeatInfo['camera_id']) {
                $data['addTime'] = $addTime;
                $camera_id = $db_house_village_monitor->add($data);
                $cameraArr[] = $camera_id;
            }
        }
        if (!empty($cameraArr)) {
            $config_data = D('Config_data')->get_config_data();
            if (!isset($config_data['daHuaRtmpChange'])||!$config_data['daHuaRtmpChange']) {
                // 一旦有添加 记录下来执行计划任务
                D('Config_data')->save_config_data('daHuaRtmpChange',$addTime);
            }
        }
        return $cameraArr;
    }

    public function videoEdit($data) {
        $camera_id = isset($data['camera_id'])&&$data['camera_id']?$data['camera_id']:0;
        $village_id = isset($data['village_id'])&&$data['village_id']?$data['village_id']:0;
        $property_id = isset($data['property_id'])&&$data['property_id']?$data['property_id']:0;
        $house_village_monitor = D('House_village_monitor');
        if ($camera_id) {
            $where = [];
            $where['camera_id'] = $camera_id;
            $monitor = $house_village_monitor->where($where)->find();
            if (empty($monitor)) {
                throw new \Exception("操作对象不存在");
            }
        } else {
            // 目前不支持添加
            throw new \Exception("必传参数缺失");
        }
        $channelName = isset($data['channelName'])&&trim($data['channelName'])?trim($data['channelName']):'';
        $channelIp = isset($data['channelIp'])&&trim($data['channelIp'])?trim($data['channelIp']):'';
        $status = isset($data['status'])&&intval($data['status'])?intval($data['status']):'';
        if (!$channelName && !$channelIp && !$status) {
            return $data;
        }
        $saveData = [];
        if ($channelName) {
            $saveData['channelName'] = $channelName;
        }
        if ($channelIp) {
            $saveData['channelIp'] = $channelIp;
        }
        if ($status) {
            $saveData['status'] = $status;
        }
        if (isset($monitor['property_id'])&&!$monitor['village_id']&&$village_id) {
            $saveData['village_id'] = $village_id;
        }
        if (isset($monitor['property_id'])&&!$monitor['property_id']&&$property_id) {
            $saveData['property_id'] = $property_id;
        }
        $where = [];
        $where['camera_id'] = $camera_id;
        $saveData['updateTime'] = time();
        $saves = $house_village_monitor->where($where)->save($saveData);
        if ($saves==false) {
            throw new \Exception("更新失败");
        }
        return $data;
    }
}