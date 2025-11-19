<?php
/**
 * 智慧社区管理平台
 * 版本信息16.020220902
 */

namespace face\hikvision;

use app\community\model\db\AccessTokenCommonExpires;

class infoVisionICommunity
{
    public $hostname = '';
    public $port = '';
    public $content_type = 'application/json'; // 类型
    public $accept = "*/*";
    public $date = "";
    public $app_key = "";
    public $app_secret = '';
    public $base_url = '';
    public $tag_id='frs';

    public function __construct()
    {
        $config = $this->getConfig();
        $this->hostname = $config['hostname'];
        $this->port = $config['port'];
        $this->app_key = $config['app_key'];
        $this->app_secret = $config['app_secret'];
        $this->date = date('Y-m-d H:i:s');
        $this->base_url = "https://" . $this->hostname . ":" . $this->port;
    }

    public function getConfig()
    {
        $hostname = cfg('hik_info_vision_i_community_hostname');
        $port = cfg('hik_info_vision_i_community_port');
        $app_key = cfg('hik_info_vision_i_community_app_key');
        $app_secret = cfg('hik_info_vision_i_community_app_secret');
        !$hostname && $hostname = $this->hostname;
        !$port && $port = $this->port;
        !$app_key && $app_key = $this->app_key;
        !$app_secret && $app_secret = $this->app_secret;
        return [
            'hostname' => $hostname,
            'port' => $port,
            'app_key' => $app_key,
            'app_secret' => $app_secret,
        ];
    }

    // 1.获取根区域信息
    public $v1RegionsRoot = "/api/resource/v1/regions/root";

    public function getRegionsOrg()
    {
        $postData = [
            'treeCode' => 0
        ];
        $result = $this->artemisCommon($this->v1RegionsRoot, $postData);
        return $result;
    }

    // 5.1.5.1.4根据父组织编号获取下级组织列表
    public $v1OrgParentOrgIndexCodeSubOrgList = "/api/resource/v1/org/parentOrgIndexCode/subOrgList";

    public function getParentOrgIndexCodeSubOrgList($parentOrgIndexCode, $pageNo = 1, $pageSize = 100)
    {
        $postData = [
            'parentOrgIndexCode' => $parentOrgIndexCode,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];
        $result = $this->artemisCommon($this->v1OrgParentOrgIndexCodeSubOrgList, $postData);
        return $result;
    }

    // 根据组织编号获取组织详细信息
    public $v1OrgInfoUrl = "/api/resource/v1/org/orgIndexCodes/orgInfo";

    public function getOrgIndexCodesInfo($orgIndexCodes)
    {
        $postData = [
            'orgIndexCodes' => $orgIndexCodes,
        ];
        $result = $this->artemisCommon($this->v1OrgInfoUrl, $postData);
        return $result;
    }

    // 根据查询条件查询区域列表信息，主要用于区域信息查询过滤。 相对V1接口，支持级联场景的区域查询
    public $v2RegionNodesByParams = "/api/irds/v2/region/nodesByParams";

    public function getRegionNodesByParams($parentIndexCodes = [], $resourceType = 'region', $pageNo = 1, $pageSize = 100, $isSubRegion = false)
    {
        $postData = [
            'resourceType' => $resourceType,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];
        if (!empty($parentIndexCodes)) {
            $postData['parentIndexCodes'] = $parentIndexCodes;
        }
        if ($isSubRegion) {
            $postData['isSubRegion'] = true;
        }
        $result = $this->artemisCommon($this->v2RegionNodesByParams, $postData);
        return $result;
    }

    // 5.1.5.1.1获取根组织
    public $v1RegionsHouses = "/api/cpams/v1/regions/houses";

    public function getRegionsHouses($pageNo = 1, $pageSize = 100)
    {
        $postData = [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];
        $result = $this->artemisCommon($this->v1RegionsHouses, $postData);
        return $result;
    }

    // 查询门禁点列表v2
    public $v2DoorSearchUrl = "/api/resource/v2/door/search";
    public function getDoorSearch($pageNo = 1, $pageSize = 100, $parentIndexCode = '', $expressions = [])
    {
        $postData = [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];
        if ($parentIndexCode) {
            $expressions[] = [
                "key" => "parentIndexCode",
                "operator" => 0,
                "values" => [
                    $parentIndexCode
                ]
            ];
        }
        if (! empty($expressions)) {
            $postData['expressions'] = $expressions;
        }
        $result = $this->artemisCommon($this->v2DoorSearchUrl, $postData);
        return $result;
    }
    
    // 查询门禁设备列表v2
    public $v2AcsDeviceSearchUrl = "/api/resource/v2/acsDevice/search";
    public function getAcsDoorSearch($pageNo = 1, $pageSize = 100)
    {
        $postData = [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'orderBy' => 'sort',
            'orderType' => 'asc',
        ];
        $result = $this->artemisCommon($this->v2AcsDeviceSearchUrl, $postData);
        return $result;
    }

    /**
     * 查询门禁点状态.
     */
    public $v1DoorStates = "/api/acs/v1/door/states";
    public function getDoorStates($doorIndexCode = '', $doorIndexCodes = [])
    {
        if (! $doorIndexCode && empty($doorIndexCodes)) {
            return false;
        }
        if ($doorIndexCode) {
            $doorIndexCodes[] = $doorIndexCode;
        }
        $postData = [
            'doorIndexCodes' => $doorIndexCodes,
        ];
        $result = $this->artemisCommon($this->v1DoorStates, $postData);
        return $result;
    }
    
    /**
     * 门禁点反控
     * @param $controlType 0-常开 1-门闭 2-门开 3-常闭
     */
    public $v1DoorDoControl = "/api/acs/v1/door/doControl";
    public function doorDoControl($doorIndexCode = '', $doorIndexCodes = [], $controlType = 2)
    {
        if (! $doorIndexCode && empty($doorIndexCodes)) {
            return false;
        }
        if ($doorIndexCode) {
            $doorIndexCodes[] = $doorIndexCode;
        }
        $postData = [
            'doorIndexCodes' => $doorIndexCodes,
            'controlType' => $controlType,
        ];
        $result = $this->artemisCommon($this->v1DoorDoControl, $postData);
        return $result;
    }

    /**
     * 查询门禁点事件v2.
     */
    public $v2DoorEvents = "/api/acs/v2/door/events";
    public function getDoorEvents($pageNo = 1, $pageSize = 100, $startTime = '', $endTime = '')
    {
        $postData = [
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'sort' => 'receiveTime',
            'order' => 'asc',
        ];
        if ($startTime) {
            $postData['startTime'] = $startTime;
        }
        if ($endTime) {
            $postData['endTime'] = $endTime;
        }
        $result = $this->artemisCommon($this->v2DoorEvents, $postData);
        return $result;
    }

    // 1.添加人员v2
    public $v2AddResourcePersonUrl = "/api/resource/v2/person/single/add";

    public function addPersonResource($postData = array())
    {
        $result = $this->artemisCommon($this->v2AddResourcePersonUrl, $postData);
        return $result;
    }
    // 5.新增入住信息
    public $v2AddHousesPersonUrl = "/api/cpams/personHouseService/v1/person/houses";

    public function addHousesPerson($postData = array())
    {
        $result = $this->artemisCommon($this->v2AddHousesPersonUrl, $postData);
        return $result;
    }
    // 6.更新入住信息
    public $v2UpdateHousesPersonUrl = "/api/cpams/personHouseService/v1/person/houses/update";

    public function updateHousesPerson($postData = array())
    {
        $result = $this->artemisCommon($this->v2UpdateHousesPersonUrl, $postData);
        return $result;
    }
    //2.修改人员
    public $v2UpdateResourcePersonUrl = "/api/resource/v1/person/single/update";

    public function updatePersonResource($postData = array())
    {
        $result = $this->artemisCommon($this->v2UpdateResourcePersonUrl, $postData);
        return $result;
    }
    
    //5.查询人员列表v2
    /**
     * 根据 personIds等条件来查人员信息 多个值使用英文逗号分隔，不超过1000个
     * *
     **/
    public $v2GetPersonInfoUrl = "/api/resource/v2/person/advance/personList";

    public function getPersonInfoByCondition($postData = array())
    {
        $result = $this->artemisCommon($this->v2GetPersonInfoUrl, $postData);
        return $result;
    }
    
    //6.批量删除人员
    /**
     * 根据 personIds删除人员信息 personIds传数组
     **/
    public $v2DeletePersonInfoUrl = "/api/resource/v1/person/batch/delete";

    public function deletePersonInfoByCondition($postData = array())
    {
        $result = $this->artemisCommon($this->v2DeletePersonInfoUrl, $postData);
        return $result;
    }

    //7.修改人脸
    public $v2UpdatePersonFaceUrl = "/api/resource/v1/face/single/update";

    public function updatePersonFaceInfo($postData = array())
    {
        $result = $this->artemisCommon($this->v2UpdatePersonFaceUrl, $postData);
        return $result;
    }

    //8.添加人脸
    public $v2AddPersonFaceUrl = "/api/resource/v1/face/single/add";

    public function addPersonFaceInfo($postData = array())
    {
        $result = $this->artemisCommon($this->v2AddPersonFaceUrl, $postData);
        return $result;
    }

    //9.删除人脸
    public $v2DeletePersonFaceUrl = "/api/resource/v1/face/single/delete";

    public function deletePersonFaceInfo($postData = array())
    {
        $result = $this->artemisCommon($this->v2DeletePersonFaceUrl, $postData);
        return $result;
    }

    //10.批量添加人员
    public $v2AddBatchPersonInfoUrl = "/api/resource/v1/person/batch/add";

    /***
     * 批量时不支持传人脸照片数据
     * 可传 clientId 作为每条数据的标识，结果中原样返回 只支持数字
     **/
    public function addBatchPersonInfo($postData = array())
    {
        $result = $this->artemisCommon($this->v2AddBatchPersonInfoUrl, $postData);
        return $result;
    }

    //1.批量开卡 人员卡片接口
    public $v2AddOpenCardUrl = "/api/cis/v1/card/bindings";

    public function addOpenCard($postData = array())
    {
        $result = $this->artemisCommon($this->v2AddOpenCardUrl, $postData);
        return $result;
    }

    //3.获取单个卡片信息
    public $v2GetCardInfoUrl = "/api/irds/v1/card/cardInfo";

    public function getCardInfo($postData = array())
    {
        $result = $this->artemisCommon($this->v2GetCardInfoUrl, $postData);
        return $result;
    }
    //4.查询卡片列表
    public $v2GetCardListUrl = "/api/irds/v1/card/advance/cardList";

    public function GetCardListByCondition($postData = array())
    {
        $result = $this->artemisCommon($this->v2GetCardListUrl, $postData);
        return $result;
    }
    //5.卡片退卡 删除卡片
    public $v2DeletCardInfoUrl = "/api/cis/v1/card/deletion";

    public function deletCardInfo($postData = array())
    {
        $result = $this->artemisCommon($this->v2DeletCardInfoUrl, $postData);
        return $result;
    }
    //9.生成卡片二维码
    public $v2GetbarCodeUrl = "/api/cis/v1/card/barCode";

    public function GetDoorbarCodeStr($postData = array())
    {
        $result = $this->artemisCommon($this->v2GetbarCodeUrl, $postData);
        return $result;
    }
    
    // 获取API网关接口调用token，无入参。
    public $v1TokenUrl = "/api/v1/oauth/token";
    public $tokenType = 'hik_info_vision_i_community_common_token';

    public function getToken($getNew = false)
    {
        $commonType = $this->tokenType;
        $commonAccessId = $this->app_key . '_' . $this->app_secret;
        $whereCommon = [];
        $whereCommon[] = ['type', '=', $commonType];
        $whereCommon[] = ['access_id', '=', $commonAccessId];
        $dbAccessTokenCommonExpires = new AccessTokenCommonExpires();
        $tokenInfo = $dbAccessTokenCommonExpires->getOne($whereCommon, true, 'id DESC');
        if ($tokenInfo && !is_array($tokenInfo)) {
            $tokenInfo = $tokenInfo->toArray();
        }
        $now_time = time();
        $now_time = $now_time + 1800; // 提前半小时过期 避免
        if (!$getNew && isset($tokenInfo['access_token_expire']) && intval($tokenInfo['access_token_expire']) > intval($now_time)) {
            $access_token = $tokenInfo['access_token'];
            return ['code' => 0, 'access_token' => $access_token, 'msg' => 'success'];
        }
        $result = $this->artemisCommon($this->v1TokenUrl, [], 'POST', false);
        $access_token = isset($result['data']['access_token']) && trim($result['data']['access_token']) ? trim($result['data']['access_token']) : '';
        if (!$access_token) {
            return $result;
        }
        if (isset($result['data']['expires_in']) && intval($result['data']['expires_in']) > 1) {
            $access_token_expire = $now_time + intval($result['data']['expires_in']);
            $tokenParam = array(
                'access_token' => $access_token,
                'access_token_expire' => $access_token_expire,
            );
            if ($tokenInfo && isset($tokenInfo['id'])) {
                $dbAccessTokenCommonExpires->saveOne($whereCommon, $tokenParam);
            } else {
                $tokenParam['type'] = $commonType;
                $tokenParam['access_id'] = $commonAccessId;
                $dbAccessTokenCommonExpires->addOne($tokenParam);
            }
        }
        return ['code' => 0, 'access_token' => $access_token, 'msg' => 'success'];
    }

    // 公共请求方法
    public function artemisCommon($url, $postData = [], $httpMethod = 'POST', $getToken = true)
    {
        $url = '/artemis' . $url;
        if ($getToken) {
            $tokenInfo = $this->getToken();
            $access_token = isset($tokenInfo['access_token']) && trim($tokenInfo['access_token']) ? trim($tokenInfo['access_token']) : '';
        } else {
            $access_token = '';
        }
        $contentMd5 = '';
        $sign = $this->getSign($url, $httpMethod, $contentMd5);
        $options = $this->getOptions($sign, $access_token, $contentMd5);
        $postUrl = $this->base_url . $url;
        $result = $this->curlPost($postUrl, json_encode($postData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $options);
        $result = json_decode($result, true);
        return $result;
    }

    // 公共请求接口
    public function curlPost($url = '', $postData = '', $options = array(), $timeOut = 30)
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut); //设置curl允许执行最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        // https请求 不验证整数和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        $error = curl_errno($ch);
        fdump_api(['$error' => $error, '$data' => $data, '$postData' => $postData, '$options' => $options, '$url' => $url], 'infoVisionICommunity/curlPostLog', 1);
        curl_close($ch);
        return $data;
    }

    // 参数统一处理
    public function getOptions($sign, $access_token = '', $contentMd5 = '')
    {
        $headerArr = [];
        $headerArr[] = "Accept:" . $this->accept;
        if ($contentMd5) {
            $headerArr[] = "Content-MD5:" . $contentMd5;
        }
        $headerArr[] = "Content-Type:" . $this->content_type;
        $headerArr[] = "Date:" . $this->date;
        $headerArr[] = "X-Ca-Key:" . $this->app_key;
        $headerArr[] = "X-Ca-Signature:" . $sign;
        $headerArr[] = "X-Ca-Signature-Headers:" . "x-ca-key";
        if ($access_token) {
            $headerArr[] = "access_token:" . $access_token;
        }
        $options = array(
            CURLOPT_HTTPHEADER => $headerArr
        );
        return $options;
    }

    /**
     * 以app_secret为秘钥,使用 HmacSHA256
     * @param $postData
     * @param $url
     * @param string $httpMethod
     * @return string
     */
    public function getSign($url, $httpMethod = 'POST', $contentMd5 = '')
    {
        $httpMethod = strtoupper($httpMethod); // 请求方法统一转大写
        $sign_str = $this->getSignStr($url, $httpMethod, $contentMd5);// 签名字符串
        $priKey = $this->app_secret;
        $sign = hash_hmac('sha256', $sign_str, $priKey, true);// 生成信息摘要
        $result = base64_encode($sign);
        return $result;
    }

    public function getSignStr($url, $httpMethod, $contentMd5 = '')
    {
        $next = "\n";
        $str = $httpMethod . $next . $this->accept . $next;
        if ($contentMd5) {
            $str .= $contentMd5 . $next;
        }
        $str .= $this->content_type . $next . $this->date . $next;
        $str .= "x-ca-key:" . $this->app_key . $next;
        $str .= $url;
        return $str;
    }

    public function getContentMd5($postData)
    {
        $postDataJson = json_encode($postData, JSON_UNESCAPED_UNICODE);
        return base64_encode(md5($postDataJson));
    }

    /**
     * 获取人脸识别成功相关事件码
     * @return int[]
     */
    public function getFaceDoorSuccess()
    {
        // 人脸+指纹认证通过	196888
        // 人脸+密码认证通过	196889
        // 人脸+刷卡认证通过	196890
        // 人脸+密码+指纹认证通过	196891
        // 人脸+刷卡+指纹认证通过	196892
        // 人脸认证通过	196893
        // 人证比对通过	197162
        return [196888, 196889, 196890, 196891, 196892, 196893, 197162, 983309];
    }

    /**
     * 获取人脸识别失败相关事件码
     * @return int[]
     */
    public function getFaceDoorErr()
    {
        // 人脸+指纹认证失败	197141
        // 人脸+指纹认证超时	197142
        // 人脸+密码认证失败	197143
        // 人脸+密码认证超时	197144
        // 人脸+刷卡认证失败	197145
        // 人脸+刷卡认证超时	197146
        // 人脸+密码+指纹认证失败	197147
        // 人脸+密码+指纹认证超时	197148
        // 人脸+刷卡+指纹认证失败	197149
        // 人脸+刷卡+指纹认证超时	197150
        // 人脸认证失败	197151
        // 人脸识别失败	197160
        // 真人检测失败	197161
        // 人证比对失败	197163
        return [197141, 197142, 197143, 197144, 197145, 197146, 197147, 197148, 197149, 197150, 197151, 197160, 197161, 197163];
    }



    /**
     * 获取[IC卡开门]成功相关事件码
     * @return int[]
     */
    public function getUserIcCardDoorSuccess()
    {
        // 合法卡比对通过	198914
        // 刷卡+指纹认证通过	196885
        // 刷卡+密码认证通过	198915
        // 刷卡+指纹+密码通过	196886
        // 巡查卡比对通过	198918
        // 超级卡比对通过	198921
        return [198914, 196885, 198915, 196886, 198918];
    }

    /**
     * 获取[IC卡开门]失败相关事件码
     * @return int[]
     */
    public function getUserIcCardDoorErr()
    {
        // 无此卡号 197634
        // 卡号过期 197633
        // 卡不属于多重认证群组 197391
        // 输入卡号错误 197122
        // 卡号长度错误 197124
        // 卡号数字检查错误 197125
        // 刷卡+密码不开门	198145
        // 卡未分配权限	197635
        // 刷卡+指纹+密码失败	197136
        // 刷卡+指纹+密码超时	197137
        return [197634, 197633, 197391, 197122, 197124, 197125, 198145, 197635, 197136, 197137];
    }
}