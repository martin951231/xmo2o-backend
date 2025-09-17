<?php


namespace app\community\model\service;


class iSecureCameraService
{
    public $iscUrl = 'http://60.173.195.178:9500';

    public function __construct()
    {
        $this->iscUrl = cfg('isc_url');
    }

    /**
     * 获取监控点预览取流URLv2
     * @author lijie
     * @date_time 2021/01/08
     * @param string $cameraIndexCode 监控点唯一标识
     * @param int $streamType 码流类型，0:主码流 1:子码流 2:第三码流 参数不填，默认为主码流
     * @param string $protocol 取流协议（应用层协议），
    “hik”:HIK私有协议，使用视频SDK进行播放时，传入此类型；
    “rtsp”:RTSP协议；
    “rtmp”:RTMP协议（RTMP协议只支持海康SDK协议、EHOME协议、ONVIF协议接入的设备；只支持H264视频编码和AAC音频编码）；
    “hls”:HLS协议（HLS协议只支持海康SDK协议、EHOME协议、ONVIF协议接入的设备；只支持H264视频编码和AAC音频编码）；
    “ws”:Websocket协议（一般用于H5视频播放器取流播放）。
    参数不填，默认为HIK协议
     * @param int $transmode 传输协议（传输层协议），0:UDP 1:TCP 默认是TCP
     * @param string $expand 标识扩展内容，格式：key=value，
    调用方根据其播放控件支持的解码格式选择相应的封装类型；
    多个扩展时，以“&”隔开；
     * @param string $streamform 输出码流转封装格式，“ps”:PS封装格式、“rtp”:RTP封装协议。当protocol=rtsp时生效，且不传值时默认为RTP封装协议。
     * @return array|bool|mixed|string
     */
    public function previewURLs($cameraIndexCode='',$streamType=0,$protocol='ws',$transmode=1,$expand='transcode=0',$streamform='ps')
    {
        if(!$cameraIndexCode)
            return false;
        $url = $this->iscUrl."/api/video/v2/cameras/previewURLs";
        $postParams['cameraIndexCode'] = $cameraIndexCode;
        $postParams['streamType'] = $streamType;
        $postParams['protocol'] = $protocol;
        $postParams['transmode'] = $transmode;
        $postParams['expand'] = $expand;
        $postParams['streamform'] = $streamform;
        $res = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($res)){
            $res = json_decode($res,true);
        }
        if($res['code'] == 0){
            return ['url'=>$res['data']['url'],'status'=>1];
        }
        return ['status'=>0];
    }

    /**
     * 获取监控点回放取流URLv2
     * @author lijie
     * @date_time 2021/01/08
     * @param string $cameraIndexCode 监控点唯一标识
     * @param int $recordLocation 存储类型,0：中心存储 1：设备存储 默认为中心存储
     * @param string $protocol 取流协议（应用层协议)，
    “hik”:HIK私有协议，使用视频SDK进行播放时，传入此类型；
    “rtsp”:RTSP协议；
    “rtmp”:RTMP协议（RTMP协议只支持海康SDK协议、EHOME协议、ONVIF协议接入的设备；只支持H264视频编码和AAC音频编码；RTMP回放要求录像片段连续，需要在URL后自行拼接beginTime=20190902T100303&endTime=20190902T100400，其中20190902T100303至20190902T100400为查询出有连续录像的时间段。对于不连续的录像，需要分段查询分段播放）；
    “hls”:HLS协议（HLS协议只支持海康SDK协议、EHOME协议、ONVIF协议接入的设备；只支持H264视频编码和AAC音频编码；hls协议只支持云存储，不支持设备存储，云存储版本要求v2.2.4及以上的2.x版本，或v3.0.5及以上的3.x版本；ISC版本要求v1.2.0版本及以上，需在运管中心-视频联网共享中切换成启动平台外置VOD）,
    “ws”:Websocket协议（一般用于H5视频播放器取流播放）。
    参数不填，默认为HIK协议
     * @param int $transmode 传输协议（传输层协议）0:UDP
    1:TCP
    默认为TCP，在protocol设置为rtsp或者rtmp时有效
    注：EHOME设备回放只支持TCP传输
     * @param string $beginTime 开始查询时间
     * @param string $endTime 结束查询时间，开始时间和结束时间相差不超过三天
     * @param string $uuid 分页查询id，上一次查询返回的uuid，用于继续查询剩余片段，默认为空字符串。当存储类型为设备存储时，该字段生效，中心存储会一次性返回全部片段。
     * @param string $expand 扩展内容，格式：key=value，
    调用方根据其播放控件支持的解码格式选择相应的封装类型；
    多个扩展时，以“&”隔开；
     * @param string $streamform 输出码流转封装格式，“ps”:PS封装格式、“rtp”:RTP封装协议。当protocol=rtsp时生效，且不传值时默认为RTP封装协议
     * @param int $lockType 查询录像的锁定类型，0-查询全部录像；1-查询未锁定录像；2-查询已锁定录像，不传默认值为0。通过录像锁定与解锁接口来进行录像锁定与解锁。
     * @return array|bool|mixed|string
     * @throws \think\Exception
     */
    public function playbackURLs($cameraIndexCode='',$beginTime='',$endTime='',$recordLocation=0,$protocol='ws',$transmode=1,$uuid='',$expand='transcode=0',$streamform='ps',$lockType=0)
    {
        if(!$cameraIndexCode)
            throw new \think\Exception("监控点唯一标识不存在！");
        if(empty($beginTime) || empty($endTime))
            throw new \think\Exception("查询开始时间和结束时间不能为空！");
        if(strtotime($endTime) <= strtotime($beginTime))
            throw new \think\Exception("查询开始时间需要大于结束时间！");
        if(strtotime($endTime)-strtotime($beginTime) > 3*86400)
            throw new \think\Exception("开始时间和结束时间相差不能超过三天！");
        $url = $this->iscUrl."/api/video/v2/cameras/playbackURLs";
        $postParams['cameraIndexCode'] = $cameraIndexCode;
        $postParams['recordLocation'] = $recordLocation;
        $postParams['protocol'] = $protocol;
        $postParams['transmode'] = $transmode;
        $postParams['beginTime'] = $beginTime;
        $postParams['endTime'] = $endTime;
        $postParams['uuid'] = $uuid;
        $postParams['expand'] = $expand;
        $postParams['streamform'] = $streamform;
        $postParams['lockType'] = $lockType;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 订阅事件
     * @author lijie
     * @date_time 2022/01/11
     * @param array $eventTypes 事件类型
     * @param string $eventDest 指定事件接收的地址，采用restful回调模式，支持http和https
     * @param int $subType 订阅类型，0-订阅原始事件，1-联动事件，2-原始事件和联动事件，不填使用默认值0
     * @param array $eventLvl 事件等级，0-未配置，1-低，2-中，3-高
    此处事件等级是指在事件联动中配置的等级
    订阅类型为0时，此参数无效，使用默认值0
    在订阅类型为1时，不填使用默认值[1,2,3]
    在订阅类型为2时，不填使用默认值[0,1,2,3]
    数组大小不超过32，事件等级大小不超过31
     * @return array|bool|mixed|string
     * @throws \think\Exception
     */
    public function eventSubscriptionByEventTypes($eventTypes=[],$eventDest='',$subType=0,$eventLvl=[])
    {
        if(empty($eventTypes))
            throw new \think\Exception("事件类型不能为空！");
        if(empty($eventDest))
            throw new \think\Exception("事件接收的地址不能为空！");
        $url = $this->iscUrl."/api/eventService/v1/eventSubscriptionByEventTypes";
        $postParams['eventTypes'] = $eventTypes;
        $postParams['eventDest'] = $eventDest;
        $postParams['subType'] = $subType;
        $postParams['eventLvl'] = $eventLvl;
        $data = curlPost($url,json_encode($postParams),45,"Content-type: application/json;charset=utf-8");
        if(!is_array($data)){
            $data = json_decode($data,true);
        }
        return $data;
    }
}