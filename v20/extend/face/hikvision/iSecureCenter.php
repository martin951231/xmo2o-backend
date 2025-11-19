<?php
/**
 * 海康威视-综合安防管理平台
 */

namespace  face\hikvision;

class iSecureCenter
{
    public $pre_url = "";
    public $app_key = '';
    public $app_secret = '';

    public $time;//时间戳
    public $content_type='application/json';// 类型
    public $accept = "*/*";
    public $charset;//格式

    public $resourceV2EncodeDeviceSearch = '/artemis/api/resource/v2/encodeDevice/search';// 查询编码设备列表v2
    public $resourceV1Cameras = '/artemis/api/resource/v1/cameras';// 查询监控点列表v2
    public $videoV2CamerasPreviewURLs = '/artemis/api/video/v2/cameras/previewURLs';// 获取监控点预览取流URLv2
    public $resourceV1AlarmOutSearch = '/artemis/api/resource/v1/alarmOut/search';// 查询报警输出列表
    public $videoV1AlarmOutStatusGet = '/artemis/api/video/v1/alarmOut/status/get';// 获取报警输出通道状态
    public $resourceV1CameraTimeRange = '/artemis/api/resource/v1/camera/timeRange';// 增量获取监控点数据
    public $eventServiceV1EventSubscriptionByEventTypes = '/artemis/api/eventService/v1/eventSubscriptionByEventTypes';// 按事件类型订阅事件
    // 视频监控=>视频丢失 131329;视频遮挡 131330;移动侦测 131331;虚焦 131613;报警输入 589825;可视域事件 196355;GPS采集 851969;
    //         区域入侵 131588;越界侦测 131585;进入区域 131586;离开区域 131587;徘徊侦测 131590;人员聚集 131593;快速移动 131592;
    //         停车侦测 131591;物品遗留 131594;物品拿取 131595;人数异常 131664;间距异常 131665;剧烈运动 131596;离岗 131603;
    //         倒地 131605;攀高 131597;重点人员起身 131610;如厕超时 131608;人员站立 131666;静坐 131667;防风场滞留 131609;
    //         起身 131598;人靠近ATM 131599;操作超时 131600;贴纸条 131601;安装读卡器 131602;尾随 131604;声强突变 131606;
    //         折线攀高 131607;折线警戒面 131611;温差报警 192518;温度报警 192517;船只检测 192516;火点检测 192515;烟火检测 192514;烟雾检测 192513;
    //         高空抛物 930335
    // 视频网管=>监控点离线 889196545;
    public $eventTypesArr = [
        131329,131330,131331,131613,589825,196355,851969,
        131588,131585,131586,131587,131590,131593,131592,
        131591,131594,131595,131664,131665,131596,131603,
        131605,131597,131610,131608,131666,131667,131609,
        131598,131599,131600,131601,131602,131604,131606,
        131607,131611,192518,192517,192516,192515,192514,192513,
        930335,
        889196545,
    ];

    public function __construct($app_key='',$app_secret=''){
        $this->pre_url = 'https://' . cfg('iSecureCenterIp') . ':' .cfg('iSecureCenterPreUrlPort');
        if ($app_key) {
            $this->app_key = $app_key;
        } else {
            $this->app_key = cfg('iSecureCenterAppKey');
        }
        if ($app_secret) {
            $this->app_secret = $app_secret;
        } else {
            $this->app_secret = cfg('iSecureCenterAppSecret');
        }
        $this->charset = 'utf-8';
        list($msec,$sec) = explode(' ',microtime());
        $this->time = (float)sprintf('%.0f',(floatval($msec)+floatval($sec))*1000);
        $this->eventDest = cfg('site_url') .'/appapi.php?g=Appapi&c=CameraDevice&a=iSecureCenterEventRcv';
//        $this->eventDest = 'https://pmwlc.chinarg.cn/appapi.php?g=Appapi&c=CameraDevice&a=iSecureCenterEventRcv';
    }
    // 参数统一处理
    public function getOptions($sign) {
        $options = array(
            CURLOPT_HTTPHEADER => array(
                "Accept:".$this->accept,
                "Content-Type:".$this->content_type,
                "X-Ca-Key:".$this->app_key,
                "X-Ca-Signature:".$sign,
                "X-Ca-Timestamp:".$this->time,
                "X-Ca-Signature-Headers:"."x-ca-key,x-ca-timestamp",
            )
        );
        return $options;
    }
    // 查询编码设备列表v2
    public function resourceV2EncodeDeviceSearch($response) {
        // 请求参数
        $postData = [];
        $postData['pageNo'] = isset($response['pageNo'])?intval($response['pageNo']):1;
        $postData['pageSize'] = isset($response['pageSize'])?intval($response['pageSize']):1000;
        $sign = $this->getSign($postData,$this->resourceV2EncodeDeviceSearch);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->resourceV2EncodeDeviceSearch,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->resourceV2EncodeDeviceSearch, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/resourceV2EncodeDeviceSearchLog',1);
        return json_decode($result,true);
    }
    // 查询监控点列表v2
    public function resourceV1Cameras($response) {
        // 请求参数
        $postData = [];
        $postData['pageNo'] = isset($response['pageNo'])?intval($response['pageNo']):1;
        $postData['pageSize'] = isset($response['pageSize'])?intval($response['pageSize']):1000;
        $sign = $this->getSign($postData,$this->resourceV1Cameras);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->resourceV1Cameras,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->resourceV1Cameras, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/resourceV1CamerasLog',1);
        return json_decode($result,true);
    }
    // 增量获取监控点数据
    public function resourceV1CameraTimeRange($response) {
        // 请求参数
        $postData = [];
        // 统一传时间戳 这边统一处理
        $startTime = isset($response['startTime'])?intval($response['startTime']):'';
        $endTime = isset($response['endTime'])?intval($response['endTime']):'';
        // 支持查询范围 48*3600=172800
        if (!$endTime&&$startTime) {
            $endTime = $startTime + 3600;
        } elseif (!$endTime&&!$startTime) {
            $nowTime = time();
            $endTime = $nowTime;
            $startTime = $nowTime - 3600;
        } elseif (!$startTime&&$endTime) {
            $startTime = $endTime - 3600;
        }
        $postData['startTime'] = date('c',$startTime);
        $postData['endTime'] = date('c',$endTime);
        $postData['startTime'] = strval($postData['startTime']);
        $postData['endTime'] = strval($postData['endTime']);
        $postData['pageNo'] = isset($response['pageNo'])?intval($response['pageNo']):1;
        $postData['pageSize'] = isset($response['pageSize'])?intval($response['pageSize']):1000;
        $sign = $this->getSign($postData,$this->resourceV1CameraTimeRange);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->resourceV1CameraTimeRange,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->resourceV1CameraTimeRange, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/resourceV1CameraTimeRangeLog',1);
        return json_decode($result,true);
    }
    // 获取监控点预览取流URLv2
    public function videoV2CamerasPreviewURLs($response) {
        // 请求参数
        $postData = [];
        $postData['cameraIndexCode'] = isset($response['cameraIndexCode'])?trim($response['cameraIndexCode']):'';
        $postData['streamType'] = isset($response['streamType'])?intval($response['streamType']):0;
        $postData['protocol'] = isset($response['protocol'])?trim($response['protocol']):'ws';
        $postData['transmode'] = isset($response['transmode'])?intval($response['transmode']):1;
        $postData['expand'] = isset($response['expand'])?trim($response['expand']):'';
//        $postData['expand'] = isset($response['expand'])?trim($response['expand']):'transcode=1&videype=h264';
        $sign = $this->getSign($postData,$this->videoV2CamerasPreviewURLs);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->videoV2CamerasPreviewURLs,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->videoV2CamerasPreviewURLs, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/videoV2CamerasPreviewURLsLog',1);
        return json_decode($result,true);
    }
    // 查询报警输出列表
    public function resourceV1AlarmOutSearch($response) {
        // 请求参数
        $postData = [];
        $postData['pageNo'] = isset($response['pageNo'])?intval($response['pageNo']):'1';
        $postData['pageSize'] = isset($response['pageSize'])?intval($response['pageSize']):'1000';
        $sign = $this->getSign($postData,$this->resourceV1AlarmOutSearch);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->resourceV1AlarmOutSearch,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->resourceV1AlarmOutSearch, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/resourceV1AlarmOutSearchLog',1);
        return json_decode($result,true);
    }
    // 获取报警输出通道状态
    public function videoV1AlarmOutStatusGet($response) {
        // 请求参数
        $postData = [];
        $postData['channelIndexCode'] = isset($response['channelIndexCode'])?trim($response['channelIndexCode']):'';
        $sign = $this->getSign($postData,$this->eventServiceV1EventSubscriptionByEventTypes);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->eventServiceV1EventSubscriptionByEventTypes,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->eventServiceV1EventSubscriptionByEventTypes, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/eventServiceV1EventSubscriptionByEventTypesLog',1);
        return json_decode($result,true);
    }
    //按事件类型订阅事件
    public function eventServiceV1EventSubscriptionByEventTypes($response) {
        // 请求参数
        $postData = [];
        $eventTypes = $this->eventTypesArr;
        $postData['eventTypes'] = isset($response['eventTypes'])?$response['eventTypes']:$eventTypes;
        // 指定事件接收的地址
        $postData['eventDest'] = isset($response['eventDest'])?$response['eventDest']:$this->eventDest;
        //订阅类型，0-订阅原始事件，1-联动事件，2-原始事件和联动事件，不填使用默认值0
        $postData['subType'] = isset($response['subType'])?intval($response['subType']):0;
        // 事件等级，0-未配置，1-低，2-中，3-高
        //此处事件等级是指在事件联动中配置的等级
        //订阅类型为0时，此参数无效，使用默认值0
        //在订阅类型为1时，不填使用默认值[1,2,3]
        //在订阅类型为2时，不填使用默认值[0,1,2,3]
        //数组大小不超过32，事件等级大小不超过31
        $postData['eventLvl'] = isset($response['eventLvl'])?$response['eventLvl']:[];
        $sign = $this->getSign($postData,$this->eventServiceV1EventSubscriptionByEventTypes);
        $options = $this->getOptions($sign);
        $result = $this->curlPost($this->pre_url.$this->eventServiceV1EventSubscriptionByEventTypes,json_encode($postData),$options);
        fdump_api(['app_key' => $this->app_key, 'url' => $this->pre_url.$this->eventServiceV1EventSubscriptionByEventTypes, 'postData' => $postData, 'options' => $options, 'result' => $result],'iSecureCenter/eventServiceV1EventSubscriptionByEventTypesLog',1);
        return json_decode($result,true);
    }


    /**
     * 以app_secret为秘钥,使用 HmacSHA256
     * @param $postData
     * @param $url
     * @param string $httpMethod
     * @return string
     */
    public function getSign($postData,$url,$httpMethod='POST') {
        $sign_str = $this->getSignStr($postData,$url,$httpMethod);// 签名字符串
        $priKey = $this->app_secret;
        $sign = hash_hmac('sha256',$sign_str,$priKey,true);// 生成信息摘要
        $result = base64_encode($sign);
        return $result;
    }

    public function getSignStr($postData,$url,$httpMethod) {
        $next = "\n";
        $str = $httpMethod.$next.$this->accept.$next.$this->content_type.$next;
        $str .= "x-ca-key:".$this->app_key.$next;
        $str .= "x-ca-timestamp:".$this->time.$next;
        $str .= $url;
        return $str;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = '';
        $i = 0;$len=count($params);
        foreach ($params as $k=>$v) {
            if (false === $this->checkEmpty($v)&&"@"!=substr($v,0,1)) {
                // 转换成目前字符集
                $v = $this->characet($v,$this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "?{$k}=".$v;
                } else {
                    $stringToBeSigned .= "&{$k}=".$v;
                }
                $i++;
            }
        }
        unset($k,$v);
        return $stringToBeSigned;
    }

    public function get_message($param) {
        $str = str_replace(array('{','}','"'),'',json_encode($param));
        return $str;
    }

    protected function checkEmpty($value) {
        if (!isset($value)) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (trim($value) === "") {
            return true;
        }
        return false;
    }

    public function characet($data,$targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset)!=0) {
                $data = mb_convert_encoding($data,$targetCharset,$fileType);
            }
        }
        return $data;
    }

    public function curlPost($url='',$postData='',$options = array(),$timeOut = 30) {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeOut); //设置curl允许执行最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch,$options);
        }
        // https请求 不验证整数和host
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        $data = curl_exec($ch);
        $error = curl_errno($ch);
        fdump_api(['$error' => $error, '$data' => $data],'iSecureCenter/curlPostLog',1);

        curl_close($ch);
        return $data;
    }
}