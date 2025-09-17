<?php

namespace wechatLive;

use file_handle\FileHandle;
use net\Http as Http;
use think\Exception;
use think\Image;

class MiniLive
{
    private $addProductApi = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/add?access_token=';
    private $reAuditApi = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/audit?access_token=';
    private $delProductApi = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/delete?access_token=';
    private $getProductStateApi = 'https://api.weixin.qq.com/wxa/business/getgoodswarehouse?access_token=';
    private $createLiveApi = 'https://api.weixin.qq.com/wxaapi/broadcast/room/create?access_token=';
    private $getReplayApi = 'https://api.weixin.qq.com/wxa/business/getliveinfo?access_token=';
    private $addLiveGoodsApi = 'https://api.weixin.qq.com/wxaapi/broadcast/room/addgoods?access_token=';
    private $getLiveListApi = 'https://api.weixin.qq.com/wxa/business/getliveinfo?access_token=';
    private $updateGoodsinfoApi = 'https://api.weixin.qq.com/wxaapi/broadcast/goods/update?access_token=';

    //主播
    private $addRole = 'https://api.weixin.qq.com/wxaapi/broadcast/role/addrole?access_token=';
    private $deleteRole = 'https://api.weixin.qq.com/wxaapi/broadcast/role/deleterole?access_token=';
    private $getRole = 'https://api.weixin.qq.com/wxaapi/broadcast/role/getrolelist?access_token=';

    //直播间
    private $getSharedCode = 'https://api.weixin.qq.com/wxaapi/broadcast/room/getsharedcode?access_token=';
    private $deleteRoom = 'https://api.weixin.qq.com/wxaapi/broadcast/room/deleteroom?access_token=';


    private $errData;
    private $liveErrData;

    public function __construct()
    {
        $this->errData = [
            '-1'     => '系统错误',
            '1003'   => '商品id不存在',
            '47001'  => '入参格式不符合规范',
            '200002' => '入参错误',
            '300001' => '禁止创建/更新商品（如:商品创建功能被封禁）',
            '300002' => '名称长度不符合规则',
            '300003' => '价格输入不合规（如:现价比原价大、传入价格非数字等）',
            '300004' => '商品名称存在违规违法内容',
            '300005' => '商品图片存在违规违法内容',
            '300006' => '图片上传失败（如:mediaID过期）',
            '300007' => '线上小程序版本不存在该链接',
            '300008' => '添加商品失败',
            '300009' => '商品审核撤回失败',
            '300010' => '商品审核状态不对（如:商品审核中）',
            '300011' => '操作非法（API不允许操作非API创建的商品）',
            '300012' => '没有提审额度（每天500次提审额度）',
            '300013' => '提审失败',
            '300014' => '审核中，无法删除（非零代表失败）',
            '300015' => '微信直播商品库不存在当前商品',
            '300017' => '商品未提审',
            '300018' => '商品图片尺寸过大',
            '300021' => '商品添加成功，审核失败',

            //成员管理错误码
            '400001'=> '微信号不合规',
            '400002'=> '微信号需要实名认证，仅设置主播角色时可能出现',
            '400003'=> '添加角色达到上限（管理员10个，运营者500个，主播500个）',
            '400004'=> '重复添加角色',
            '400005'=> '主播角色删除失败，该主播存在未开播的直播间',
        ];

        $this->liveErrData = [
            '-1'      => '系统错误',
            '1'       => '未创建直播间',
            '1003'    => '商品id不存在',
            '47001'   => '入参格式不符合规范',
            '200002'  => '入参错误',
            '300001'  => '禁止创建/更新商品 或 禁止编辑&更新房间',
            '300002'  => '名称长度不符合规则',
            '300003' => '价格输入不合规（如:现价比原价大、传入价格非数字等）',
            '300006'  => '图片上传失败（如:mediaID过期）',
            '300022'  => '此房间号不存在',
            '300023'  => '房间状态 拦截（当前房间状态不允许此操作）',
            '300024'  => '商品不存在',
            '300025'  => '商品审核未通过',
            '300026'  => '房间商品数量已经满额',
            '300027'  => '导入商品失败',
            '300028'  => '房间名称违规',
            '300029'  => '主播昵称违规',
            '300030'  => '主播微信号不合法',
            '300031'  => '直播间封面图不合规',
            '300032'  => '直播间分享图违规',
            '300033'  => '添加商品超过直播间上限',
            '300034'  => '主播微信昵称长度不符合要求',
            '300035'  => '主播微信号不存在',
            '300036'  => '主播微信号未实名认证',
            '9410000' => '直播间列表为空',
            '9410001' => '获取房间失败',
            '9410002' => '获取商品失败',
            '9410003' => '获取回放失败',
        ];
    }

    //添加直播商品
    public function addGoods($img, $goods_name, $price1, $price2, $url,$priceType)
    {
        if (empty($img) || empty($goods_name) || empty($price1) || empty($url)) {
            throw new Exception("添加直播商品时，必填参数未填");
        }
        $json = [
            'coverImgUrl' => $this->getMediaID($img, 300, 300),
            'name'        => $goods_name,
            'price'       => $price1,
            'url'         => $url,
            'priceType' => $priceType
        ];
        if ($price2 > 0) {
            $json['price2']    = $price2;
        } 
        $response = $this->postJson($this->addProductApi, ['goodsInfo' => $json], 15, true);
        return [
            'goodsId' => $response['goodsId'],
            'auditId' => $response['auditId']
        ];
    }

    public function updateGoods($goodsId, $img, $goods_name, $price1, $price2, $url)
    {
        if (empty($img) || empty($goods_name) || empty($price1) || empty($url)) {
            throw new Exception("添加直播商品时，必填参数未填");
        }
        $json = [
            'coverImgUrl' => $this->getMediaID($img, 300, 300),
            'name'        => $goods_name,
            'price'       => $price1,
            'url'         => $url,
            'goodsId'     => $goodsId
        ];
        if (!$price2) {
            $json['priceType'] = 1;
        } else {
            $json['priceType'] = 2;
            $json['price2']    = $price2;
        }
        $response = $this->postJson($this->updateGoodsinfoApi, ['goodsInfo' => $json], 15, true);
        return $response;
    }

    //重新提交审核
    //tx_goods_id = 腾讯那边的goods_id
    public function reAuditGoods($tx_goods_id)
    {
        if (empty($tx_goods_id)) {
            throw new Exception("重新提交审核时，必填参数未填");
        }
        $json     = ['goodsId' => $tx_goods_id];
        $response = $this->postJson($this->reAuditApi, $json, 15, true);
        return [
            'auditId' => $response['auditId']
        ];
    }

    //删除商品
    //tx_goods_id = 腾讯那边的goods_id
    public function delGoods($tx_goods_id)
    {
        if (empty($tx_goods_id)) {
            throw new Exception("删除商品时，必填参数未填");
        }
        $json     = ['goodsId' => $tx_goods_id];
        $response = $this->postJson($this->delProductApi, $json, 15, true);
        return true;
    }

    //获取商品审核状态
    //tx_goods_ids = 腾讯那边的goods_ids数组
    public function getGoodsState(array $tx_goods_ids)
    {
        if (empty($tx_goods_ids)) {
            throw new Exception("商品必填参数未填!");
        }
        $json     = ['goods_ids' => $tx_goods_ids];
        $response = $this->postJson($this->getProductStateApi, $json, 15, true);
        return array_column($response['goods'], 'audit_status', 'goods_id');
    }

    /**
     * 创建直播间
     * @param  [type] $name         [直播间名字，最短3个汉字，最长17个汉字，1个汉字相当于2个字符]
     * @param  [type] $bgimg        [背景图，填入mediaID（mediaID获取后，三天内有效）图片规则：建议像素1080*1920，大小不超过2M]
     * @param  [type] $startTime    [直播计划开始时间（开播时间需要在当前时间的10分钟后 并且 开始时间不能在 6 个月后）]
     * @param  [type] $endTime      [直播计划结束时间（开播时间和结束时间间隔不得短于30分钟，不得超过24小时）]
     * @param  [type] $anchorName   [主播昵称，最短2个汉字，最长15个汉字，1个汉字相当于2个字符]
     * @param  [type] $anchorWechat [主播微信号，如果未实名认证，需要先前往“小程序直播”小程序进行实名验证]
     * @param  [type] $shareImg     [分享图，填入mediaID（mediaID获取后，三天内有效）直播间分享图，图片规则：建议像素800*640，大小不超过1M]
     * @return [roomId]               [房间ID]
     */
    public function createLive($name, $bgimg, $startTime, $endTime, $anchorName, $anchorWechat, $shareImg)
    {
        if (empty($name)) {
            throw new Exception("直播间名字，最短3个汉字，最长17个汉字，1个汉字相当于2个字符");
        }
        if (empty($anchorWechat)) {
            throw new Exception("主播微信号，如果未实名认证，需要先前往“小程序直播”小程序进行实名验证");
        }
        $json     = [
            'name'         => $name,
            'coverImg'     => $this->getMediaID($bgimg, 1080, 1920),
            'startTime'    => strtotime($startTime),
            'endTime'      => strtotime($endTime),
            'anchorName'   => $anchorName,
            'anchorWechat' => $anchorWechat,
            'shareImg'     => $this->getMediaID($shareImg, 800, 640),
            'type'         => 0, //直播间类型 【1: 推流，0：手机直播】
            'screenType'   => 0, //横屏、竖屏 【1：横屏，0：竖屏】（横屏：视频宽高比为16:9、4:3、1.85:1 ；竖屏：视频宽高比为9:16、2:3）
            'closeLike'    => 0, //是否关闭点赞 【0：开启，1：关闭】（若关闭，直播开始后不允许开启）
            'closeGoods'   => 0, //是否关闭货架 【0：开启，1：关闭】（若关闭，直播开始后不允许开启）
            'closeComment' => 0, //是否关闭评论 【0：开启，1：关闭】（若关闭，直播开始后不允许开启）
        ];
        $response = $this->postJson($this->createLiveApi, $json, 15, false, true);
        return ['roomId' => $response['roomId']];
    }

    //为直播间导入商品
    //需要在创建直播间后紧接着执行
    //room_id  直播间ID
    //goods_ids 需要导入的商品ID数组
    public function importGoods($room_id, $goods_ids)
    {
        if (empty($room_id) || empty($goods_ids)) {
            throw new Exception("导入直播间商品时，必填参数未填");
        }
        $json     = [
            'ids'    => $goods_ids,
            'roomId' => $room_id
        ];
        $response = $this->postJson($this->addLiveGoodsApi, $json, 15, false, true);
        return true;
    }

    //获取直播间列表
    public function getShowList($page = 1, $limit = 10)
    {
        $json = [
            'start' => $limit * ($page - 1),
            'limit' => $limit
        ];

        return $this->postJson($this->getLiveListApi, $json, 15, false, true);
    }

    //获取直播间回放视频(会有多个视频)
    //room_id = 腾讯那边的房间ID
    public function getReplay($room_id)
    {
        if (empty($room_id)) {
            throw new Exception("获取直播间回放视频时，必填参数未填");
        }
        $json     = [
            'action'  => 'get_replay',
            'room_id' => $room_id,
            'start'   => 0,
            'limit'   => 99
        ];
        $response = $this->postJson($this->getReplayApi, $json, 15, false, true);
        return array_column($response['live_replay'], 'media_url');
    }

    private function getToken()
    {

        $data  = invoke_cms_model('Access_token_wxapp_expires/get_access_token');
        $token = $data['retval'];

        if ($token['errcode'] == '0') {
            return $token['access_token'];
        } else {
            throw new Exception("获取access token失败");
        }
    }

    private function getMediaID($img, $width = 300, $height = 300)
    {
        $imgUrl = dispose_url($img);
        if (cfg('static_oss_switch') == 1) {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'] . str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_oss_access_domain_names'), '', $imgUrl);
        } else if (cfg('static_obs_switch') == 1) {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'] . str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_obs_access_domain_names'), '', $imgUrl);
        } else if (cfg('static_cos_switch') && cfg('static_cos_region')) {
            $file_handle = new FileHandle();
            $file_handle->download($imgUrl);
            $imgUrl = $_SERVER['DOCUMENT_ROOT'] . str_replace($_SERVER['REQUEST_SCHEME'] . '://' . cfg('static_cos_access_domain_names'), '', $imgUrl);
        } else {
            $imgUrl = $_SERVER['DOCUMENT_ROOT'] . str_replace(cfg('site_url'), '', $imgUrl);
        }
        $this->thumb($imgUrl, $width, $height);

        $access_token = $this->getToken();
        empty($access_token) && throw_exception('token获取失败！');
        empty($imgUrl) && throw_exception('图片地址异常！');

        $file     = $imgUrl;
        $fileInfo = pathinfo($file);
        $basename = '';
        if ($fileInfo['basename']) {
            $basename = $fileInfo['basename'];
        }
        if ($fileInfo['extension']) {
            $extension = $fileInfo['extension'];
        } else {
            $extension = '';
        }
        if (class_exists('\CURLFile')) {
            $data['media'] = new \CURLFile(realpath($file), $extension, $basename);
        } else {
            $data['media'] = '@' . realpath($file);
        }
        $httpObj = new Http();
        $return  = $httpObj->curlQyWxPost('https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $access_token . '&type=image', $data);
        if (!is_array($return)) {
            $return = json_decode($return, true);
        }

        if (!isset($return['media_id'])) {
            throw new Exception("上传图片异常，没有获得Media id，可能是因为没有找到服务器上的那张图，" . $img);

        }
        return $return['media_id'];
    }

    //裁剪图片
    private function thumb($img, $width, $height)
    {
        $image = Image::open($img);
        $image->thumb($width, $height)->save($img);
    }

    private function postJson($url, $data, $timeout = 15, $returnErr = false, $returnLiveErr = false)
    {
        $url       = $url . $this->getToken();
        $data      = json_encode($data);
        $ch        = curl_init();
        $headers[] = "Content-type: application/json;charset=utf-8";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec($ch);

        //关闭curl
        curl_close($ch);
        $result = json_decode($result, true);
        fdump_api([$data, $url, $result], 'wechatLive/live', 1);
        if (isset($result['errmsg']) && !$returnErr && ($result['errcode'] < 0 || $result['errcode'] > 0)) {
            if ($returnLiveErr) {
                throw new Exception($result['errcode'] . " " . $this->liveErrData[$result['errcode']]);
            } else {
                throw new Exception($result['errmsg']);
            }
        } elseif ($returnErr && $result['errcode'] != '0') {
            throw new Exception($result['errcode'] . " " . $this->errData[$result['errcode']]);
        } elseif (empty($result)) {
            throw new Exception('调用腾讯接口异常！');
        }
        return $result;
    }


    private function doPost($url, $data)
    {
        $url       = $url . $this->getToken();
        $data      = json_encode($data);
        $ch        = curl_init();
        $headers[] = "Content-type: application/json;charset=utf-8";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);

        //关闭curl
        curl_close($ch);
        $result = json_decode($result, true);
        fdump_api([$data, $url, $result], 'wechatLive/live', 1);
        if ($result['errcode'] != 0) {
            throw new Exception('调用腾讯接口异常！错误码：' . $result['errcode']);
        }
        return $result;
    }


    private function doGet($url, $data)
    {
        $url = $url . $this->getToken();
        if ($data) {
            $url .= '&' . http_build_query($data);
        }
        $result = \net\Http::curlGet($url);
        $result = json_decode($result, true);
        fdump_api([$data, $url, $result], 'wechatLive/live', 1);
        if ($result['errcode'] != 0) {
            throw new Exception('调用腾讯接口异常！错误码：' . $result['errcode']);
        }
        return $result;
    }

    public function doAction($isPost, $action, $data)
    {
        $url = $this->$action;
        if ($isPost) {
            //post请求
            return $this->doPost($url, $data);
        } else {
            //get请求
            return $this->doGet($url, $data);
        }
    }
}