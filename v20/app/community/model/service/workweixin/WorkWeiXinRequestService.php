<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信统一接口
 */


namespace app\community\model\service\workweixin;


class WorkWeiXinRequestService
{
    public $qyapiUrl = 'https://qyapi.weixin.qq.com';
    public $openUrl  = 'https://open.weixin.qq.com';

    /**
     * 获取第三方应用凭证
     * 该API用于获取第三方应用凭证（suite_access_token）。
     * @param $param
     * @return array|mixed
     */
    public function cgiBinServiceGetSuiteToken($param)
    {
        $url = $this->qyapiUrl . '/cgi-bin/service/get_suite_token';
        $service_suite_token_result = http_request($url, 'POST', json_encode($param));
        if ($service_suite_token_result[0] == 200) {
            $service_suite_token_result = json_decode($service_suite_token_result[1], true);
        } else {
            $service_suite_token_result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $service_suite_token_result], 'workWeiXin/cgiBinServiceGetSuiteToken',1);
        return $service_suite_token_result;
    }

    /**
     * 获取预授权码
     * 该API用于获取预授权码。预授权码用于企业授权时的第三方服务商安全验证。
     * @param $suite_access_token
     * @return array|mixed
     */
    public function cgiBinServiceGetPreAuthCode($suite_access_token)
    {
        $url = $this->qyapiUrl . "/cgi-bin/service/get_pre_auth_code?suite_access_token={$suite_access_token}";
        $pre_auth_code_result = http_request($url, 'GET');
        if ($pre_auth_code_result[0] == 200) {
            $pre_auth_code_result = json_decode($pre_auth_code_result[1], true);
        } else {
            $pre_auth_code_result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token, 'url' => $url, 'result' => $pre_auth_code_result], 'workWeiXin/cgiBinServiceGetPreAuthCode',1);
        return $pre_auth_code_result;
    }

    /**
     * 设置授权配置
     * 该接口可对某次授权进行配置。可支持测试模式（应用未发布时）。
     * @param $suite_access_token
     * @param $param
     * @return array|mixed
     */
    public function cgiBinServiceSetSessionInfo($suite_access_token, $param)
    {
        $url = $this->qyapiUrl . "/cgi-bin/service/set_session_info?suite_access_token={$suite_access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinServiceSetSessionInfo',1);
        return $result;
    }

    /**
     * 引导用户进入授权页 第三方服务商在自己的网站中放置“企业微信应用授权”的入口，引导企业微信管理员进入应用授权页。授权页网址为:
     * @param $suite_id
     * @param $pre_auth_code
     * @param $redirect_uri
     * @param $state
     * @return string
     */
    public function get3rdappInstallUrl($suite_id, $pre_auth_code, $redirect_uri, $state)
    {
        return $this->qyapiUrl . "/3rdapp/install?suite_id={$suite_id}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_uri}&state={$state}";
    }

    /**
     * 获取企业永久授权码
     * 该API用于使用临时授权码换取授权方的永久授权码，并换取授权信息、企业access_token，临时授权码一次有效。
     * @param $auth_code
     * @param $suite_access_token
     * @return array|mixed
     */
    public function cgiBinServiceGetPermanentCode($auth_code, $suite_access_token)
    {
        $url = $this->qyapiUrl . "/cgi-bin/service/get_permanent_code?suite_access_token={$suite_access_token}";
        $param = [
            'auth_code' => $auth_code
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinServiceGetPermanentCode',1);
        return $result;
    }

    /**
     * 获取企业授权信息
     * 该API用于通过永久授权码换取企业微信的授权信息。 永久code的获取，是通过临时授权码使用get_permanent_code 接口获取到的permanent_code。
     * @param $auth_corpid
     * @param $permanent_code
     * @param $suite_access_token
     * @return array|mixed
     */
    public function cgiBinServiceGetAuthInfo($auth_corpid, $permanent_code, $suite_access_token)
    {
        $url = $this->qyapiUrl . "/cgi-bin/service/get_auth_info?suite_access_token={$suite_access_token}";
        $param = [
            'auth_corpid'    => $auth_corpid,
            'permanent_code' => $permanent_code,
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinServiceGetAuthInfo',1);
        return $result;
    }

    /**
     * 获取access_token
     * 获取access_token是调用企业微信API接口的第一步，相当于创建了一个登录凭证，其它的业务API接口，都需要依赖于access_token来鉴权调用者身份。
     * 因此开发者，在使用业务接口前，要明确access_token的颁发来源，使用正确的access_token。
     * @param $corpid
     * @param $corpsecret
     * @return array|mixed
     */
    public function cgiBinGetToken($corpid, $corpsecret) {
        $url = $this->qyapiUrl . "/cgi-bin/gettoken?corpid={$corpid}&corpsecret=$corpsecret";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['corpid' => $corpid,'corpsecret' => $corpsecret, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinGetToken',1);
        return $result;
    }
    
    public function cgiBinServiceGetCorpToken($auth_corpid, $permanent_code, $suite_access_token) {
        $url = $this->qyapiUrl . "/cgi-bin/service/get_corp_token?suite_access_token=$suite_access_token";
        $param = [
            'auth_corpid'    => $auth_corpid,
            'permanent_code' => $permanent_code,
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinServiceGetCorpToken',1);
        return $result;
    }

    /**
     * 获取部门列表
     * 企业通讯录安全特别重要，企业微信将持续升级加固通讯录接口的安全机制，以下是关键的变更点：
     * 【重要】从2022年8月15日10点开始，“企业管理后台 - 管理工具 - 通讯录同步”的新增IP将不能再调用此接口，企业可通过「获取部门ID列表」接口获取部门ID列表。查看调整详情。
     * @param string $access_token 调用接口凭证
     * @param string $id 部门id。获取指定部门及其下的子部门（以及子部门的子部门等等，递归）。 如果不填，默认获取全量组织架构
     * @return array|mixed
     */
    public function cgiBinDepartmentList($access_token, $id = '') {
        $url = $this->qyapiUrl . "/cgi-bin/department/simplelist?access_token={$access_token}";
        if ($id) {
            $url .= "&id={$id}";
        }
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'id' => $id, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinDepartmentList',1);
        return $result;
    }

    /**
     * 获取单个部门详情
     * @param $access_token
     * @param string $id
     * @return array|mixed
     */
    public function cgiBinDepartmentGet($access_token, $id = '') {
        $url = $this->qyapiUrl . "/cgi-bin/department/get?access_token={$access_token}";
        if ($id) {
            $url .= "&id={$id}";
        }
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'id' => $id, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinDepartmentGet',1);
        return $result;
    }

    /**
     * 创建部门
     * @param $access_token
     * @param $param
     * @return array|mixed
     */
    public function cgiBinDepartmentCreate($access_token, $param) {
        $url = $this->qyapiUrl . "/cgi-bin/department/create?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinDepartmentCreate',1);
        return $result;
    }

    /**
     * 更新部门
     * @param $access_token
     * @param $param
     * @return array|mixed
     */
    public function cgiBinDepartmentUpdate($access_token, $param) {
        $url = $this->qyapiUrl . "/cgi-bin/department/update?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinDepartmentUpdate',1);
        return $result;
    }

    /**
     * 删除部门
     * @param $access_token
     * @param $id
     * @return array|mixed
     */
    public function cgiBinDepartmentDelete($access_token, $id) {
        return ['errcode' => 0];
//        $url = $this->qyapiUrl . "/cgi-bin/department/delete?access_token={$access_token}&id={$id}";
//        $result = http_request($url, 'GET');
//        if ($result[0] == 200) {
//            $result = json_decode($result[1], true);
//        } else {
//            $result = [];
//        }
//        fdump_api(['access_token' => $access_token,'id' => $id, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinDepartmentDelete',1);
//        return $result;
    }

    /**
     * @param $access_token
     * @param $param
     * [
     *     'cursor': '', // 用于分页查询的游标，字符串类型，由上一次调用返回，首次调用不填
     *     'limit':  '', // 分页，预期请求的数据量，取值范围 1 ~ 10000
     * ]
     * @return array|mixed
     */
    public function cgiBinUserListId($access_token, $param) {
        $url = $this->qyapiUrl . "/cgi-bin/user/list_id?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserListId',1);
        return $result;
    }
    
    /**
     * 读取成员
     * 应用只能获取可见范围内的成员信息，且每种应用获取的字段有所不同，在返回结果说明中会逐个说明。
     * @param $access_token
     * @param $userid
     * @return array|mixed
     */
    public function cgiBinUserGet($access_token, $userid) {
        $url = $this->qyapiUrl . "/cgi-bin/user/get?access_token={$access_token}&userid=$userid";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'userid' => $userid, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserGet',1);
        return $result;
    }


    /**
     * 手机号获取userid
     * 通过手机号获取其所对应的userid。
     * @param $access_token
     * @param $mobile
     * @return array|mixed
     */
    public function cgiBinUserGetUserId($access_token, $mobile) {
        $url = $this->qyapiUrl . "/cgi-bin/user/getuserid?access_token={$access_token}";
        $param = [
            'mobile' => $mobile
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserGetUserId',1);
        return $result;
    }

    /**
     * 添加成员
     * @param $access_token
     * @param $param
     * @return array|mixed
     */
    public function cgiBinUserCreate($access_token, $param) {
        $url = $this->qyapiUrl . "/cgi-bin/user/create?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserCreate',1);
        return $result;
    }

    /**
     * 更新成员
     * @param $access_token
     * @param $param
     * @return array|mixed
     */
    public function cgiBinUserUpdate($access_token, $param) {
        $url = $this->qyapiUrl . "/cgi-bin/user/update?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserUpdate',1);
        return $result;
    }

    /**
     * 删除成员
     * @param $access_token
     * @param $userid
     * @return array|mixed
     */
    public function cgiBinUserDelete($access_token, $userid) {
        return ['errcode' => 0];
//        $url = $this->qyapiUrl . "/cgi-bin/user/delete?access_token={$access_token}&userid={$userid}";
//        $result = http_request($url, 'GET');
//        if ($result[0] == 200) {
//            $result = json_decode($result[1], true);
//        } else {
//            $result = [];
//        }
//        fdump_api(['access_token' => $access_token, 'userid' => $userid, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserDelete',1);
//        return $result;
    }

    /**
     * 获取标签列表
     * @param $access_token
     * @return array|mixed
     */
    public function cgiBinTagList($access_token) {
        $url = $this->qyapiUrl . "/cgi-bin/tag/list?access_token={$access_token}";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagList',1);
        return $result;
    }

    /**
     * 创建标签
     * @param $access_token
     * @param $tagname
     * @param $tagid
     * @return array|mixed
     */
    public function cgiBinTagCreate($access_token, $tagname, $tagid = '') {
        $param = [
            'tagname' => $tagname,
        ];
        if ($tagid) {
            $param['tagid'] = $tagid;
        }
        $url = $this->qyapiUrl . "/cgi-bin/tag/create?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagCreate',1);
        return $result;
    }

    /**
     * 更新标签名字
     * @param $access_token
     * @param $tagname
     * @param $tagid
     * @return array|mixed
     */
    public function cgiBinTagUpdate($access_token, $tagname, $tagid = '') {
        $param = [
            'tagname' => $tagname,
        ];
        if ($tagid) {
            $param['tagid'] = $tagid;
        }
        $url = $this->qyapiUrl . "/cgi-bin/tag/update?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagUpdate',1);
        return $result;
    }

    /**
     * 删除标签
     * @param $access_token
     * @param $param
     * @return array|mixed
     */
    public function cgiBinTagDelete($access_token, $tagid) {
        $url = $this->qyapiUrl . "/cgi-bin/tag/delete?access_token={$access_token}&tagid={$tagid}";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'tagid' => $tagid, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagDelete',1);
        return $result;
    }


    /**
     * 获取标签成员
     * @param $access_token
     * @param $tagid
     * @return array|mixed
     */
    public function cgiBinTagGetUser($access_token, $tagid) {
        $url = $this->qyapiUrl . "/cgi-bin/tag/get?access_token={$access_token}&tagid={$tagid}";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'tagid' => $tagid, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagGetUser',1);
        return $result;
    }

    /**
     * 增加标签成员
     * @param $access_token
     * @param $tagid
     * @param array $userlist
     * @param array $partylist
     * @return array|mixed
     * errcode	     返回码
     * errmsg	    对返回码的文本描述内容
     * invalidlist	非法的成员帐号列表
     * invalidparty	非法的部门id列表
     * a)正确时返回
     * {
     *    "errcode": 0,
     *    "errmsg": "ok"
     * }
     * b)若部分userid、partylist非法，则返回
     * {
     *    "errcode": 0,
     *    "errmsg": "ok",
     *    "invalidlist"："usr1|usr2|usr",
     *    "invalidparty"：[2,4],
     * }
     * c)当包含userid、partylist全部非法时返回
     * {
     *    "errcode": 40070,
     *    "errmsg": "all list invalid"
     * }
     */
    public function cgiBinTagAddTagUsers($access_token, $tagid, $userlist = [], $partylist = []) {
        $param = [
            'tagid' => $tagid,
        ];
        if ($userlist) {
            $param['userlist'] = $userlist;
        }
        if ($partylist) {
            $param['partylist'] = $partylist;
        }
        $url = $this->qyapiUrl . "/cgi-bin/tag/addtagusers?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagAddTagUsers',1);
        return $result;
    }


    /**
     * 增加标签成员
     * @param $access_token
     * @param $tagid
     * @param array $userlist
     * @param array $partylist
     * @return array|mixed
     * errcode	     返回码
     * errmsg	    对返回码的文本描述内容
     * invalidlist	非法的成员帐号列表
     * invalidparty	非法的部门id列表
     * a)正确时返回
     * {
     *    "errcode": 0,
     *    "errmsg": "deleted"
     * }
     * b)若部分userid、partylist非法，则返回
     * {
     *    "errcode": 0,
     *    "errmsg": "deleted",
     *    "invalidlist"："usr1|usr2|usr",
     *    "invalidparty"：[2,4],
     * }
     * c)当包含userid、partylist全部非法时返回
     * {
     *    "errcode": 40070,
     *    "errmsg": "all list invalid"
     * }
     */
    public function cgiBinTagDelTagUsers($access_token, $tagid, $userlist = [], $partylist = []) {
        $param = [
            'tagid' => $tagid,
        ];
        if ($userlist) {
            $param['userlist'] = $userlist;
        }
        if ($partylist) {
            $param['partylist'] = $partylist;
        }
        $url = $this->qyapiUrl . "/cgi-bin/tag/deltagusers?access_token={$access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinTagDelTagUsers',1);
        return $result;
    }

    /**
     * 构造第三方应用oauth2链接
     * 如果第三方应用需要在打开的网页里面携带用户的身份信息，第一步需要构造如下的链接来获取code：
     * @param string $appid          第三方应用id（即ww或wx开头的suite_id）。注意与企业的网页授权登录不同
     * @param string $redirect_uri   授权后重定向的回调链接地址，请使用urlencode对链接进行处理 ，注意域名需要设置为第三方应用的可信域名
     * @param string $response_type  返回类型，此时固定为：code
     * @param string $scope          应用授权作用域。
     *                                  snsapi_base：静默授权，可获取成员的基础信息（UserId与DeviceId）；
     *                                  snsapi_privateinfo：手动授权，可获取成员的详细信息，包含头像、二维码等敏感信息。
     * @param string $state          重定向后会带上state参数，企业可以填写a-zA-Z0-9的参数值，长度不可超过128个字节
     * @param string $endString      固定内容 #wechat_redirect
     * @return string
     */
    public function connectOauth2Authorize($appid, $redirect_uri, $response_type = 'code', $scope = 'snsapi_privateinfo', $state = '',$endString = '#wechat_redirect') {
        if ($state) {
            $url = "/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type={$response_type}&scope={$scope}&state={$state}{$endString}";
        } else {
            $url = "/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type={$response_type}&scope={$scope}{$endString}";
        }
        $url = $this->openUrl . $url;
        fdump_api(['appid' => $appid, 'redirect_uri' => $redirect_uri, 'url' => $url, 'state' => $state], 'workWeiXin/connectOauth2Authorize',1);
        return $url;
    }

    /**
     * 获取访问用户身份
     * @param string $suite_access_token 第三方应用的suite_access_token
     * @param string $code 通过成员授权获取到的code，最大为512字节。每次成员授权带上的code将不一样，code只能使用一次，5分钟未被使用自动过期。
     * @return array|mixed
     */
    public function cgiBinServiceAuthGetUserInfo3rd($suite_access_token, $code) {
        $url = $this->qyapiUrl . "/cgi-bin/service/auth/getuserinfo3rd?suite_access_token={$suite_access_token}&code={$code}";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token, 'code' => $code, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinServiceAuthGetUserInfo3rd',1);
        return $result;
    }

    /**
     * 获取访问用户敏感信息
     * @param string $suite_access_token 第三方应用的suite_access_token
     * @param string $user_ticket 	     成员票据
     * @return array|mixed
     */
    public function  cgiBinServiceAuthGetUserDetail3rd($suite_access_token, $user_ticket) {
        $param = [
            'user_ticket' => $user_ticket,
        ];
        $url = $this->qyapiUrl . "/cgi-bin/service/auth/getuserdetail3rd?suite_access_token={$suite_access_token}";
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['suite_access_token' => $suite_access_token, 'user_ticket' => $user_ticket, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinServiceAuthGetUserDetail3rd',1);
        return $result;
    }


    /**
     * userid与openid互换
     * @param $userid
     * @param $access_token
     * @return array|mixed
     */
    public function cgiBinUserConvertToOpenid($userid, $access_token)
    {
        $url = $this->qyapiUrl . '/cgi-bin/user/convert_to_openid?access_token='.$access_token;
        $param = [
            'userid' => $userid
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserConvertToOpenid',1);
        return $result;
    }

    /**
     * openid转userid
     * @param $openid
     * @param $access_token
     * @return array|mixed
     */
    public function cgiBinUserConvertToUserid($openid, $access_token)
    {
        $url = $this->qyapiUrl . '/cgi-bin/user/convert_to_userid?access_token='.$access_token;
        $param = [
            'openid' => $openid
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinUserConvertToOpenid',1);
        return $result;
    }

    /**
     * 将企业主体下的明文userid转换为服务商主体下的密文userid。
     */
    public function cgiBinBatchUseridToOpenuserid($userid_list, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/batch/userid_to_openuserid?access_token='.$access_token;
        $param = [
            'userid_list' => $userid_list
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinBatchUseridToOpenuserid',1);
        return $result;
    }

    /**
     * 服务商上传附件资源[第三方应用开发>服务端API>客户联系>上传附件资源].
     */
    public function cgiBinMediaUploadAttachment($param, $access_token, $media_type = 'image', $attachment_type = 2) {
        $url = $this->qyapiUrl . '/cgi-bin/media/upload_attachment?access_token='.$access_token.'media_type='.$media_type.'&attachment_type='.$attachment_type;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinMediaUploadAttachmentLog',1);
        return $result;
    }

    /**
     * 服务商上传附件资源[第三方应用开发>服务端API>素材管理>上传临时素材].
     * type 媒体文件类型，分别有图片（image）、语音（voice）、视频（video），普通文件（file）
     */
    public function cgiBinMediaUpload($param, $access_token, $type = 'image') {
        $url = $this->qyapiUrl . '/cgi-bin/media/upload?type='.$type.'&access_token='.$access_token;
        $result = $this->curlQyWxPost($url, $param);
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinMediaUploadLog',1);
        return $result;
    }

    public function curlQyWxPost($url,$data,$timeout=15, $type = null){
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";
        if($type == 'json'){
            $headers[] = "Content-type: application/json;charset=utf-8";
        }
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
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        fdump_api(['result' => $result, 'errno' => $errno, 'error' => $error], 'workWeiXin/cgiBinMediaUploadLog',1);

        //关闭curl
        curl_close($ch);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 发送新客户欢迎语.
     */
    public function cgiBinExternalContactSendWelcomeMsg($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/send_welcome_msg?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactSendWelcomeMsgLog',1);
        return $result;
    }

    /**
     * 获取企业标签库.
     */
    public function cgiBinExternalContactGetCorpTagList($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/get_corp_tag_list?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactGetCorpTagListLog',1);
        return $result;
    }

    /**
     * 添加企业客户标签.
     */
    public function cgiBinExternalContactAddCorpTag($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/add_corp_tag?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactAddCorpTagLog',1);
        return $result;
    }

    /**
     * 编辑客户企业标签.
     */
    public function cgiBinExternalContactMarkTag($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/mark_tag?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactMarkTagLog',1);
        return $result;
    }

    /**
     * 第三方主体unionid转换为第三方external_userid.
     */
    public function cgiBinExternalContactUnionIdToExternalUserId3rd($param, $suite_access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/service/externalcontact/unionid_to_external_userid_3rd?suite_access_token='.$suite_access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactUnionIdToExternalUserId3rdLog',1);
        return $result;
    }

    /**
     * 企业和第三方应用可通过此接口获取企业与成员的群发记录。
     */
    public function cgiBinExternalContactGetGroupMsgResult($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/get_group_msg_result?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactUnionIdToExternalUserId3rdLog',1);
        return $result;
    }

    /**
     * 获取群发成员发送任务列表.
     */
    public function cgiBinExternalContactGetGroupMsgTask($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/get_groupmsg_task?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactGetGroupMsgTaskLog',1);
        return $result;
    }

    /**
     * 获取群发成员发送任务列表.
     */
    public function cgiBinExternalContactGetGroupMsgSendResult($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/get_groupmsg_send_result?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactGetGroupMsgSendResultLog',1);
        return $result;
    }

    /**
     * 创建企业群发.
     */
    public function cgiBinExternalContactAddMsgTemplate($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/add_msg_template?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactAddMsgTemplateLog',1);
        return $result;
    }

    /**
     * 发送应用消息.
     * 应用支持推送文本、图片、视频、文件、图文等类型。
     */
    public function cgiBinMessageSend($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/message/send?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinMessageSendLog',1);
        return $result;
    }

    /**
     * 提醒成员群发.
     * 企业和第三方应用可调用此接口，重新触发群发通知，提醒成员完成群发任务，24小时内每个群发最多触发三次提醒。
     */
    public function cgiBinExternalContactRemindGroupMsgSend($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/remind_groupmsg_send?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactRemindGroupMsgSendLog',1);
        return $result;
    }

    /**
     * 将代开发应用或第三方应用获取的密文open_userid转换为明文userid。
     */
    public function cgiBinBatchOpenUserIdToUserId($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/batch/openuserid_to_userid?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinBatchOpenUserIdToUserIdLog',1);
        return $result;
    }

    /**
     * external_userid的转换.
     * 将企业主体下的external_userid转换为服务商主体下的external_userid.
     */
    public function cgiBinExternalContactGetNewExternalUserId($param, $access_token) {
        $url = $this->qyapiUrl . '/cgi-bin/externalcontact/get_new_external_userid?access_token='.$access_token;
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactGetNewExternalUserIdLog',1);
        return $result;
    }

    /**
     * 获取客户详情.
     * 企业可通过此接口，根据外部联系人的userid，拉取客户详情。
     */
    public function cgiBinExternalContactGet($external_userid, $access_token) {
        $url = $this->qyapiUrl . "/cgi-bin/externalcontact/get?access_token={$access_token}&external_userid={$external_userid}";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token,'external_userid' => $external_userid, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactGetLog',1);
        return $result;
    }

    /**
     * 批量获取客户详情.
     * 企业/第三方可通过此接口获取指定成员添加的客户信息列表.
     */
    public function cgiBinExternalContactBatchGetByUser($userid_list, $access_token) {
        $url = $this->qyapiUrl . "/cgi-bin/externalcontact/batch/get_by_user?access_token={$access_token}";
        $param = [
            'userid_list' => $userid_list,
            'limit' => 100
        ];
        $result = http_request($url, 'POST', json_encode($param));
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['param' => $param, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactBatchGetByUserLog',1);
        return $result;
    }
    
    public function cgiBinExternalContactGetFollowUserList($access_token)
    {
        $url = $this->qyapiUrl . "/cgi-bin/externalcontact/get_follow_user_list?access_token={$access_token}";
        $result = http_request($url, 'GET');
        if ($result[0] == 200) {
            $result = json_decode($result[1], true);
        } else {
            $result = [];
        }
        fdump_api(['access_token' => $access_token, 'url' => $url, 'result' => $result], 'workWeiXin/cgiBinExternalContactGetFollowUserListLog',1);
        return $result;
    }
}