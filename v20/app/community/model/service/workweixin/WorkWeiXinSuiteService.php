<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信第三方应用业务处理
 */

namespace app\community\model\service\workweixin;

use app\common\model\service\ConfigService;
use app\community\model\db\AccessTokenCommonExpires;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\workweixin\WorkWeixinDepartment;
use app\community\model\db\workweixin\WorkWeixinUser;
use app\community\model\service\CommunityLoginService;
use app\consts\WorkWeiXinConst;
use app\traits\house\WorkWeiXinTraits;
use app\traits\WorkWeiXinToJobTraits;

class WorkWeiXinSuiteService
{
    use WorkWeiXinTraits;
    use WorkWeiXinToJobTraits;
    
    public $config;
    public $now_time;
    public $cloud = NULL;
    public function __construct()
    {
        $this->now_time = time();
    }
    
    /**
     * 判断企业微信服务商配置没有
     * @return bool
     */
    public function judgeConfig() 
    {
        if (!$this->config) {
            $field = 'name,value';
            $where = [['name','in','enterprise_wx_provider_suiteid,enterprise_wx_provider_secret,enterprise_wx_corpid,enterprise_book_suiteid,enterprise_book_secret']];
            $workWeiXinConfig = array_column((new ConfigService())->getConfigList($where,$field),'value', 'name');
            $suite_id = isset($workWeiXinConfig['enterprise_wx_provider_suiteid']) && $workWeiXinConfig['enterprise_wx_provider_suiteid'] ? $workWeiXinConfig['enterprise_wx_provider_suiteid'] : cfg('enterprise_wx_provider_suiteid');
            $suite_secret = isset($workWeiXinConfig['enterprise_wx_provider_secret']) && $workWeiXinConfig['enterprise_wx_provider_secret'] ? $workWeiXinConfig['enterprise_wx_provider_secret'] : cfg('enterprise_wx_provider_secret');
            $suite_corpid = isset($workWeiXinConfig['enterprise_wx_corpid']) && $workWeiXinConfig['enterprise_wx_corpid'] ? $workWeiXinConfig['enterprise_wx_corpid'] : cfg('enterprise_wx_corpid');
            $book_suite_id = isset($workWeiXinConfig['enterprise_book_suiteid']) && $workWeiXinConfig['enterprise_book_suiteid'] ? $workWeiXinConfig['enterprise_book_suiteid'] : cfg('enterprise_book_suiteid');
            $book_secret = isset($workWeiXinConfig['enterprise_book_secret']) && $workWeiXinConfig['enterprise_book_secret'] ? $workWeiXinConfig['enterprise_book_secret'] : cfg('enterprise_book_secret');
            $this->config = [
                'suite_id'      => $suite_id,
                'suite_secret'  => $suite_secret,
                'suite_corpid'  => $suite_corpid,
                'book_suite_id' => $book_suite_id,
                'book_secret'   => $book_secret,
            ];
        }
        if (!isset($this->config['suite_id']) || !isset($this->config['suite_secret']) || !$this->config['suite_id'] || !$this->config['suite_secret']) {
            return false;
        } else {
            return true;
        }
    }
    
    public function setConfig($config) 
    {
        $this->config = $config;
        return $this->config;
    }

    /**
     * 获取第三方应用凭证
     * 用于获取第三方应用凭证（suite_access_token）
     * @param false $new
     * @return array
     */
    public function getSuiteAccessToken($new = false, $config = []) 
    {
        if (!$this->judgeConfig() && empty($config)) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        if (! empty($config)) {
            $suite_id      = $config['suite_id'];
            $suite_secret  = $config['suite_secret'];
            if (isset($config['suite_ticket']) && $config['suite_ticket']) {
                $suite_ticket = $config['suite_ticket'];
            } else {
                $suite_ticket  = (new ConfigService())->getOneField( 'enterprise_book_suite_ticket');
            }
        } else {
            $suite_id      = $this->config['suite_id'];
            $suite_secret  = $this->config['suite_secret'];
            if (isset($this->config['suite_ticket']) && $this->config['suite_ticket']) {
                $suite_ticket = $this->config['suite_ticket'];
            } else {
                $suite_ticket  = (new ConfigService())->getOneField( 'enterprise_wx_suite_ticket');
            }
        }
        // todo 临时测试写死
        if (!$suite_ticket) {
            return [
                'code'    => 1001,
                'message' => '企业微信未推送suite_ticket，请确认是否正确配置了回调路径。',
            ];
        }
        $type          = 'suite_access_token';
        $access_id     = $suite_id.'_suiteid_'.$suite_secret.'_suite_secret_'.$suite_ticket;
        $where_suite_token = [];
        $where_suite_token[] = ['type',      '=', $type];
        $where_suite_token[] = ['access_id', '=', $access_id];
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $suite_info = $db_access_token_common_expires->getOne($where_suite_token, true, 'id DESC');
        if ($suite_info && !is_array($suite_info)) {
            $suite_info = $suite_info->toArray();
        }
        $now_time           = $this->now_time + 1800; // 提前半小时过期 避免
        $suite_access_token = '';
        if (!$new && isset($suite_info['access_token_expire']) && intval($suite_info['access_token_expire']) > intval($now_time)) {
            $suite_access_token = $suite_info['access_token'];
        }else {
            $param = [
                'suite_id'     => $suite_id,
                'suite_secret' => $suite_secret,
                'suite_ticket' => $suite_ticket,
            ];
            fdump_api(['msg' => '获取suite_access_token', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            $service_suite_token_result = (new WorkWeiXinRequestService())->cgiBinServiceGetSuiteToken($param);
            fdump_api(['msg' => '获取suite_access_token-结果', 'line' => __LINE__, 'service_suite_token_result' => $service_suite_token_result], 'qiYeWeiXin/businessSynQwLog', 1);
            if (isset($service_suite_token_result['suite_access_token']) && $service_suite_token_result['suite_access_token']) {
                $suite_access_token = $service_suite_token_result['suite_access_token'];
            } elseif (isset($service_suite_token_result['errcode']) && !empty($service_suite_token_result['errcode'])) {
                return ['code' => $service_suite_token_result['errcode'], 'errmsg' => $service_suite_token_result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($service_suite_token_result['errcode'])];
            }
            if ($suite_access_token && isset($service_suite_token_result['expires_in']) && intval($service_suite_token_result['expires_in']) > 1) {
                $access_token_expire = $this->now_time + intval($service_suite_token_result['expires_in']);
                $tokenParam = array(
                    'access_token'        => $suite_access_token,
                    'access_token_expire' => $access_token_expire,
                );
                if ($suite_info && isset($suite_info['id'])) {
                    $db_access_token_common_expires->saveOne($where_suite_token, $tokenParam);
                } else {
                    $tokenParam['type']      = $type;
                    $tokenParam['access_id'] = $access_id;
                    $db_access_token_common_expires->addOne($tokenParam);
                }
            }
        }
        return ['code' => 0, 'access_token' =>$suite_access_token];
    }
    
    /**
     * 获取第三方登录应用凭证
     * 用于获取第三方登录应用凭证（suite_access_token）
     * @param false $new
     * @return array
     */
    public function getLoginSuiteAccessToken($new = false, $config = [])
    {
        if (!$this->judgeConfig() && empty($config)) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        if (! empty($config)) {
            $suite_id      = $config['suite_id'];
            $suite_secret  = $config['suite_secret'];
            if (isset($config['suite_ticket']) && $config['suite_ticket']) {
                $suite_ticket = $config['suite_ticket'];
            } else {
                $suite_ticket  = (new ConfigService())->getOneField( 'enterprise_login_suite_ticket');
            }
        } else {
            $suite_id      = (new ConfigService())->getOneField( 'enterprise_login_suiteId');
            $suite_secret  = (new ConfigService())->getOneField( 'enterprise_login_secret');
            $suite_ticket  = (new ConfigService())->getOneField( 'enterprise_login_suite_ticket');
        }
        // todo 临时测试写死
        if (!$suite_ticket) {
            return [
                'code'    => 1001,
                'message' => '企业微信未推送suite_ticket，请确认是否正确配置了回调路径。',
            ];
        }
        $type          = 'login_suite_access_token';
        $access_id     = $suite_id.'_suiteid_'.$suite_secret.'_suite_secret_'.$suite_ticket;
        $where_suite_token = [];
        $where_suite_token[] = ['type',      '=', $type];
        $where_suite_token[] = ['access_id', '=', $access_id];
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $suite_info = $db_access_token_common_expires->getOne($where_suite_token, true, 'id DESC');
        if ($suite_info && !is_array($suite_info)) {
            $suite_info = $suite_info->toArray();
        }
        $now_time           = $this->now_time + 1800; // 提前半小时过期 避免
        $suite_access_token = '';
        if (!$new && isset($suite_info['access_token_expire']) && intval($suite_info['access_token_expire']) > intval($now_time)) {
            $suite_access_token = $suite_info['access_token'];
        }else {
            $param = [
                'suite_id'     => $suite_id,
                'suite_secret' => $suite_secret,
                'suite_ticket' => $suite_ticket,
            ];
            fdump_api(['msg' => '获取suite_access_token', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/getLoginSuiteAccessTokenLog', 1);
            $service_suite_token_result = (new WorkWeiXinRequestService())->cgiBinServiceGetSuiteToken($param);
            fdump_api(['msg' => '获取suite_access_token-结果', 'line' => __LINE__, 'service_suite_token_result' => $service_suite_token_result], 'qiYeWeiXin/getLoginSuiteAccessTokenLog', 1);
            if (isset($service_suite_token_result['suite_access_token']) && $service_suite_token_result['suite_access_token']) {
                $suite_access_token = $service_suite_token_result['suite_access_token'];
            } elseif (isset($service_suite_token_result['errcode']) && !empty($service_suite_token_result['errcode'])) {
                return ['code' => $service_suite_token_result['errcode'], 'errmsg' => $service_suite_token_result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($service_suite_token_result['errcode'])];
            }
            if ($suite_access_token && isset($service_suite_token_result['expires_in']) && intval($service_suite_token_result['expires_in']) > 1) {
                $access_token_expire = $this->now_time + intval($service_suite_token_result['expires_in']);
                $tokenParam = array(
                    'access_token'        => $suite_access_token,
                    'access_token_expire' => $access_token_expire,
                );
                if ($suite_info && isset($suite_info['id'])) {
                    $db_access_token_common_expires->saveOne($where_suite_token, $tokenParam);
                } else {
                    $tokenParam['type']      = $type;
                    $tokenParam['access_id'] = $access_id;
                    $db_access_token_common_expires->addOne($tokenParam);
                }
            }
        }
        return ['code' => 0, 'access_token' =>$suite_access_token];
    }
    
    protected $code, $message, $errmsg, $suite_access_token;

    /**
     * 过滤获取 第三方应用的suite_access_token
     * @return false
     */
    protected function getSuiteAccessTokenFilter($isBookConfig = false, $new = false) {
        $this->code    = '';
        $this->message = '';
        $this->errmsg  = '';
        $config = [];
        if ($isBookConfig) {
            $config = [
                'suite_id' => $this->config['book_suite_id'],
                'suite_secret' => $this->config['book_secret'],
            ];
        }
        if (!$this->judgeConfig() && empty($config)) {
            $this->code    = 1001;
            $this->message = WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP;
            return false;
        }
        $result_token = $this->getSuiteAccessToken($new, $config);
        if (isset($result_token['access_token']) && $result_token['access_token']) {
            $this->suite_access_token = $result_token['access_token'];
            return true;
        } elseif (isset($result_token['code']) && !empty($result_token['code'])) {
            $this->code    = $result_token['code'];
            $this->message = $this->traitWorkWeiXinErrorCode($result_token['errcode']);
            $this->errmsg  = $result_token['errmsg'];
            return false;
        } else {
            $this->suite_access_token = '';
            $this->code    = 1001;
            $this->message = '缺少suite_access_token';
            return false;
        }
    }
    
    /**
     * 获取预授权码
     * 用于获取预授权码。预授权码用于企业授权时的第三方服务商安全验证。
     * @param string $suite_access_token
     * @return array
     */
    public function getPreAuthCode($suite_access_token = '') 
    {
        $this->suite_access_token = $suite_access_token;
        $this->getSuiteAccessTokenFilter();
        if ($this->code) {
            return [
                'code'    => $this->code,
                'errmsg'  => $this->errmsg,
                'message' => $this->message,
            ];
        }
        $pre_auth_code = '';
        $pre_auth_code_result = (new WorkWeiXinRequestService())->cgiBinServiceGetPreAuthCode($this->suite_access_token);
        if (isset($pre_auth_code_result['pre_auth_code']) && $pre_auth_code_result['pre_auth_code']) {
            $pre_auth_code = $pre_auth_code_result['pre_auth_code'];
        } elseif (isset($pre_auth_code_result['errcode']) && !empty($pre_auth_code_result['errcode'])) {
            return ['code' => $pre_auth_code_result['errcode'], 'errmsg' => $pre_auth_code_result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($pre_auth_code_result['errcode'])];
        }
        return ['code' => 0, 'pre_auth_code' =>$pre_auth_code, 'suite_access_token' =>$this->suite_access_token];
    }

    /**
     * 设置授权配置
     * 可对某次授权进行配置。可支持测试模式（应用未发布时）。
     * @param string $suite_access_token
     * @param string $pre_auth_code
     * @return array
     */
    public function setSessionInfo($suite_access_token = '', $pre_auth_code = '') 
    {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        if (!$pre_auth_code) {
            $pre_auth_code_arr = $this->getPreAuthCode();
            if (isset($pre_auth_code_arr['pre_auth_code']) && $pre_auth_code_arr['pre_auth_code']) {
                $pre_auth_code      = $pre_auth_code_arr['pre_auth_code'];
                $suite_access_token = $pre_auth_code_arr['suite_access_token'];
            } elseif (isset($pre_auth_code_arr['code']) && !empty($pre_auth_code_arr['code'])) {
                return ['code' => $pre_auth_code_arr['code'], 'message' => $pre_auth_code_arr['message']];
            }
        }
        if (!$pre_auth_code) {
            return [
                'code'    => 1001,
                'message' => '缺少预授权码pre_auth_code。',
            ];
        }
        if (!$suite_access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少suite_access_token。',
            ];
        }
        $enterprise_wx_is_online = cfg('enterprise_wx_is_online');
        // auth_type 授权类型：0 正式授权， 1 测试授权。 默认值为0。注意，请确保应用在正式发布后的授权类型为“正式授权”
        if ($enterprise_wx_is_online && 1 == $enterprise_wx_is_online) {
            $auth_type = 0;
        } else {
            $auth_type = 1;
        }
        $param = array(
            'pre_auth_code' => $pre_auth_code,
            'session_info' => (object)array(
                'auth_type' => $auth_type,
            ),
        );
        $result = (new WorkWeiXinRequestService())->cgiBinServiceSetSessionInfo($suite_access_token, $param);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'],'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        return ['code' => 0, 'pre_auth_code' =>$pre_auth_code, 'suite_access_token' =>$suite_access_token];
    }

    /**
     * 引导用户进入授权页
     * 第三方服务商在自己的网站中放置“企业微信应用授权”的入口，引导企业微信管理员进入应用授权页。授权页网址
     * @param string $pre_auth_code
     * @param string $redirect_uri
     * @param string $state
     * @return array|string
     */
    public function get3rdappInstallUrl($pre_auth_code = '', $redirect_uri = '', $state = '') 
    {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $suite_id      = $this->config['suite_id'];
        if (!$pre_auth_code) {
            $result = $this->setSessionInfo();
            if (isset($result['pre_auth_code']) && $result['pre_auth_code']) {
                $pre_auth_code      = $result['pre_auth_code'];
            } elseif (isset($result['code']) && !empty($result['code'])) {
                return ['code' => $result['code'], 'message' => $result['message']];
            }
        }
        return (new WorkWeiXinRequestService())->get3rdappInstallUrl($suite_id, $pre_auth_code, $redirect_uri, $state);
    }

    /**
     * @param $auth_code
     * @param string $suite_access_token
     * @return array
     */
    public function getPermanentCode($auth_code, $suite_access_token = '') 
    {
        $this->suite_access_token = $suite_access_token;
        $this->getSuiteAccessTokenFilter();
        if ($this->code) {
            return [
                'code'    => $this->code,
                'errmsg'  => $this->errmsg,
                'message' => $this->message,
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinServiceGetPermanentCode($auth_code, $this->suite_access_token);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        return ['code' => 0, 'result' => $result, 'suite_access_token' => $this->suite_access_token];
    }

    /**
     * 获取企业授权信息
     *  用于通过永久授权码换取企业微信的授权信息。 永久code的获取，是通过临时授权码使用get_permanent_code 接口获取到的permanent_code。
     * @param $auth_corpid
     * @param $permanent_code
     * @param string $suite_access_token
     * @return array
     */
    public function getAuthInfo($auth_corpid, $permanent_code, $suite_access_token = '') 
    {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        if (!$auth_corpid) {
            return [
                'code'    => 1001,
                'message' => '缺少授权方corpid。',
            ];
        }
        if (!$permanent_code) {
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $whereWorkWx = [];
            $whereWorkWx[] = ['corpid', '=', $auth_corpid];
            $infoHouseEnterpriseWxBind = $dbHouseEnterpriseWxBind->getOne($whereWorkWx);
            if ($infoHouseEnterpriseWxBind && !is_array($infoHouseEnterpriseWxBind)) {
                $infoHouseEnterpriseWxBind = $infoHouseEnterpriseWxBind->toArray();
            }
            if ($infoHouseEnterpriseWxBind && isset($infoHouseEnterpriseWxBind['permanent_code'])) {
                $permanent_code = $infoHouseEnterpriseWxBind['permanent_code'];
            }
        }
        if (!$permanent_code) {
            return [
                'code'    => 1001,
                'message' => '缺少永久授权码。',
            ];
        }
        $this->suite_access_token = $suite_access_token;
        $this->getSuiteAccessTokenFilter();
        if ($this->code) {
            return [
                'code'    => $this->code,
                'errmsg'  => $this->errmsg,
                'message' => $this->message,
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinServiceGetAuthInfo($auth_corpid,$permanent_code, $this->suite_access_token);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        return ['code' => 0, 'result' => $result, 'permanent_code' => $permanent_code, 'suite_access_token' => $this->suite_access_token];
    }

    /**
     * 获取access_token
     * 获取access_token是调用企业微信API接口的第一步，相当于创建了一个登录凭证，其它的业务API接口，都需要依赖于access_token来鉴权调用者身份。
     * 因此开发者，在使用业务接口前，要明确access_token的颁发来源，使用正确的access_token。
     * @param int $property_id
     * @param string $corpid
     * @param string $corpsecret
     * @param bool $new
     * @return array
     */
    public function getToken($property_id = 0, $corpid = '', $corpsecret = '', $new = false) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        fdump_api(['msg' => '获取token', 'line' => __LINE__, 'property_id' => $property_id, 'corpid' => $corpid, 'new' => $new], 'qiYeWeiXin/businessSynQwLog', 1);
        $where[] = ['bind_type', '=', 0];
        $where[] = ['bind_id', '=', $property_id];
        $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
        $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid, permanent_code, book_permanent_code','pigcms_id DESC');
        $auth_corpid = isset($bindInfo['corpid']) && $bindInfo['corpid'] ? $bindInfo['corpid'] : '';
        $permanent_code = isset($bindInfo['book_permanent_code']) && $bindInfo['book_permanent_code'] ? $bindInfo['book_permanent_code'] : '';
        
        $type          = 'book_permanent_code_access_token_permanent_code_access_token';
        $access_id     = $auth_corpid.'_token_'.$permanent_code;
        $where_access_token = [];
        $where_access_token[] = ['type',      '=', $type];
        $where_access_token[] = ['access_id', '=', $access_id];
        $db_access_token_common_expires = new AccessTokenCommonExpires();
        $access_token_info = $db_access_token_common_expires->getOne($where_access_token, true, 'id DESC');
        if ($access_token_info && !is_array($access_token_info)) {
            $access_token_info = $access_token_info->toArray();
        }
        $now_time           = $this->now_time + 1800; // 提前半小时过期 避免
        $access_token = '';
        if (!$new && isset($access_token_info['access_token_expire']) && intval($access_token_info['access_token_expire']) > intval($now_time)) {
            $access_token = $access_token_info['access_token'];
            fdump_api(['msg' => '获取token', 'line' => __LINE__, 'type' => $type, 'access_id' => $access_id, 'access_token_info' => $access_token_info], 'qiYeWeiXin/businessSynQwLog', 1);
        }else {
            $this->getSuiteAccessTokenFilter(true);
            $result = (new WorkWeiXinRequestService())->cgiBinServiceGetCorpToken($auth_corpid,$permanent_code, $this->suite_access_token);
            fdump_api(['msg' => '获取token', 'line' => __LINE__, 'result' => $result, 'auth_corpid' => $auth_corpid, 'bindInfo' => $bindInfo, 'permanent_code' => $permanent_code, 'suite_access_token' => $this->suite_access_token, 'corpid' => $corpid, 'corpsecret' => $corpsecret], 'qiYeWeiXin/businessSynQwLog', 1);
            if (isset($result['errcode']) && !empty($result['errcode'])) {
                return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
            } elseif (isset($result['access_token']) && $result['access_token']) {
                $access_token = $result['access_token'];
            }
            if ($access_token && isset($result['expires_in']) && intval($result['expires_in']) > 1) {
                $access_token_expire = $this->now_time + intval($result['expires_in']);
                $tokenParam = array(
                    'access_token'        => $access_token,
                    'access_token_expire' => $access_token_expire,
                );
                if ($access_token_info && isset($access_token_info['id'])) {
                    $db_access_token_common_expires->saveOne($where_access_token, $tokenParam);
                } else {
                    $tokenParam['type']      = $type;
                    $tokenParam['access_id'] = $access_id;
                    $db_access_token_common_expires->addOne($tokenParam);
                }
            }
        }
        return ['code' => 0, 'access_token' => $access_token, 'corpid' => $corpid, 'corpsecret' => $corpsecret];
    }

    /**
     * 获取子部门ID列表
     * @param $param
     * @return array|mixed
     */
    public function getDepartmentSimpleList($param) {
        if (!$this->judgeConfig()) {
            fdump_api(['msg' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP, 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        fdump_api(['msg' => '获取子部门ID列表-获取token', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->departmentCommonFilter($param);
        if (!$this->property_id) {
            fdump_api(['msg' => '缺少物业id', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '缺少物业id',
            ];
        }
        if (!$this->access_token) {
            fdump_api(['msg' => '缺少调用凭证', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinDepartmentList($this->access_token, $this->id);
        fdump_api(['msg' => '获取子部门ID列表', 'result' => $result, 'line' => __LINE__, 'access_token' => $this->access_token, 'id' => $this->id], 'qiYeWeiXin/businessSynQwLog', 1);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        $getDepartmentDetail = isset($param['getDepartmentDetail']) && $param['getDepartmentDetail'] ? intval($param['getDepartmentDetail']) : 0;
        if ($getDepartmentDetail === 1 && isset($result['department_id']) && $result['department_id']) {
            $param['access_token'] = $this->access_token;
            $workWeiXinDepartmentData = [
                'from'    => WorkWeiXinConst::FROM_PROPERTY,
                'from_id' => $this->property_id,
                'corpid'  => $this->corpid,
            ];
            $whereUnique = [
                'from'    => WorkWeiXinConst::FROM_PROPERTY,
                'from_id' => $this->property_id,
                'corpid'  => $this->corpid,
            ];
            $dbWorkWeixinDepartment = new WorkWeixinDepartment();
            foreach ($result['department_id'] as $item) {
                $departmentDetail = $this->getDepartmentDetail($item['id'], $param);
                fdump_api(['msg' => '获取单个部门详情', 'item' => $item, 'id' => $item['id'], 'param' => $param, 'line' => __LINE__, 'departmentDetail' => $departmentDetail], 'qiYeWeiXin/businessSynQwLog', 1);
                if (isset($departmentDetail['department']) && $departmentDetail['department'] && isset($departmentDetail['department']) && $departmentDetail['department']) {
                    $department = $departmentDetail['department'];
                    $department_id       = $department['id'];
                    $department_name     = isset($department['name'])              && $department['name']              ? $department['name']              : '';
                    $department_parentid = isset($department['parentid'])          && $department['parentid']          ? $department['parentid']          : '';
                    $department_order    = isset($department['order'])             && $department['order']             ? $department['order']             : '';
                    $department_leader   = isset($department['department_leader']) && $department['department_leader'] ? $department['department_leader'] : '';
                    $whereUnique['department_id'] = $department_id;
                    $recordDepartment = $dbWorkWeixinDepartment->getOne($whereUnique);
                    if ($recordDepartment && !is_array($recordDepartment)) {
                        $recordDepartment = $recordDepartment->toArray();
                    }
                    $workWeiXinDepartmentData['department_id']       = $department_id;
                    $workWeiXinDepartmentData['department_name']     = $department_name;
                    $workWeiXinDepartmentData['department_parentid'] = $department_parentid;
                    $workWeiXinDepartmentData['department_order']    = $department_order;
                    $workWeiXinDepartmentData['department_leader']    = $department_leader ?  json_encode($department_leader,JSON_UNESCAPED_UNICODE) : '';
                    if ($recordDepartment && isset($recordDepartment['department_id'])) {
                        $workWeiXinDepartmentData['update_time'] = time();
                        fdump_api(['msg' => '更新单个部门', 'whereUnique' => $whereUnique, 'param' => $param, 'line' => __LINE__, 'workWeiXinDepartmentData' => $workWeiXinDepartmentData], 'qiYeWeiXin/businessSynQwLog', 1);
                        $dbWorkWeixinDepartment->updateThis($whereUnique, $workWeiXinDepartmentData);
                    } else {
                        $workWeiXinDepartmentData['add_time'] = time();
                        fdump_api(['msg' => '新增单个部门', 'param' => $param, 'line' => __LINE__, 'workWeiXinDepartmentData' => $workWeiXinDepartmentData], 'qiYeWeiXin/businessSynQwLog', 1);
                        $dbWorkWeixinDepartment->add($workWeiXinDepartmentData);
                    }
                }
                usleep(50000);//休眠50毫秒
            }
        }
        return $result;
    }

    protected $access_token, $property_id, $corpid, $corpsecret, $id, $name, $parentid, $order, $operation_type;

    /**
     * token获取统一过滤
     * @param $param
     */
    protected function tokenCommonFilter($param) {
        $this->access_token   = isset($param['access_token'])   && $param['access_token']   ? trim($param['access_token'])     : '';
        $this->property_id    = isset($param['property_id'])    && $param['property_id']    ? trim($param['property_id'])      : '';
        $this->corpid         = isset($param['corpid'])         && $param['corpid']         ? trim($param['corpid'])           : '';
        $this->corpsecret     = isset($param['corpsecret'])     && $param['corpsecret']     ? trim($param['corpsecret'])       : '';

        if (!$this->corpid && $this->property_id) {
            $where = [];
            $where[] = ['bind_type', '=', 0];
            $where[] = ['bind_id', '=', $this->property_id];
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid','pigcms_id DESC');
            if ($bindInfo && !is_array($bindInfo)) {
                $bindInfo = $bindInfo->toArray();
            }
            // todo 如果授权企业微信和服务商相同 可以使用 企业微信自带的 通讯录获取
            if (isset($bindInfo['corpid']) && $bindInfo['corpid']) {
                $this->corpid = $bindInfo['corpid'];
            }
        }
        $new = isset($param['new']) && $param['new'] ? $param['new'] : '';

        fdump_api(['msg' => 'token获取统一过滤', 'line' => __LINE__, 'param' => $param, 'suite_corpid' => $this->config['suite_corpid'], 'corpid' => $this->corpid, 'new' => $new], 'qiYeWeiXin/businessSynQwLog', 1);
        
        if (0 && $this->config['suite_corpid'] == $this->corpid && ($this->property_id || $this->corpid)) {
            $access_token_info  = $this->getToken($this->property_id, $this->corpid, $this->corpsecret, $new);
            fdump_api([
                'msg' => '授权企业id和服务商同一个id获取token', 'line' => __LINE__, 'access_token_info' => $access_token_info,
                'property_id' => $this->property_id, 'corpid' => $this->corpid, 'corpsecret' => $this->corpsecret, 'new' => $new,
            ], 'qiYeWeiXin/businessSynQwLog',1);
            $this->access_token = isset($access_token_info['access_token']) && $access_token_info['access_token'] ? trim($access_token_info['access_token']) : '';
            if (isset($access_token_info['corpid']) && $access_token_info['corpid']) {
                $this->corpid = $access_token_info['corpid'];
            }
            if (isset($access_token_info['corpsecret']) && $access_token_info['corpsecret']) {
                $this->corpsecret = $access_token_info['corpsecret'];
            }
        } elseif ($this->config['book_suite_id'] && $this->config['book_secret']) {
            $access_token_info  = $this->getToken($this->property_id, $this->config['book_suite_id'], $this->config['book_secret'], $new);
            fdump_api([
                'msg' => '通讯录授权参数获取token', 'line' => __LINE__, 'access_token_info' => $access_token_info, 'property_id' => $this->property_id,
                'book_suite_id' => $this->config['book_suite_id'], 'book_secret' => $this->config['book_secret'], 'new' => $new,
            ], 'qiYeWeiXin/businessSynQwLog',1);
            $this->access_token = isset($access_token_info['access_token']) && $access_token_info['access_token'] ? trim($access_token_info['access_token']) : '';
        } else {
            $result_token = $this->getSuiteAccessToken($new);
            fdump_api([
                'msg' => '用于获取第三方应用凭证（旧版）', 'line' => __LINE__, 'new' => $new,
            ], 'qiYeWeiXin/businessSynQwLog',1);
            if (isset($result_token['access_token']) && $result_token['access_token']) {
                $this->access_token = $result_token['access_token'];
            }
            return true;
        }
    }
    
    /**
     * 过滤企业微信部门相关数据
     * @param $param
     */
    public function departmentCommonFilter($param) {
        $this->tokenCommonFilter($param);
        $this->id             = isset($param['id'])             && $param['id']             ? trim($param['id'])               : '';
        $this->name           = isset($param['name'])           && $param['name']           ? $this->checkStr($param['name']) : '';
        $this->parentid       = isset($param['parentid'])       && $param['parentid']       ? trim($param['parentid'])         : '';
        $this->order          = isset($param['order'])          && $param['order']          ? trim($param['order'])            : '';
        $this->operation_type = isset($param['operation_type']) && $param['operation_type'] ? trim($param['operation_type'])   : 'create';
    }
    
    /**
     * 获取单个部门详情
     * @param $id
     * @param $param
     * @return array|mixed
     */
    public function getDepartmentDetail($id, $param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        fdump_api(['msg' => '获取单个部门详情-获取token', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        $this->departmentCommonFilter($param);
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinDepartmentGet($this->access_token, $id);
        fdump_api(['msg' => '获取单个部门详情', 'line' => __LINE__, 'result' => $result, 'access_token' => $this->access_token, 'id' => $id, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        return $result;
    }
    

    /**
     * 创建/更新/删除 部门
     * @param $param
     * [
     *      operation_type: '', 操作类型  create 添加  update 更新  delete 删除
     * ]
     * @return array|mixed
     */
    public function departmentOperation($param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->departmentCommonFilter($param);
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        if ($this->operation_type != 'delete' && !$this->name) {
            return [
                'code'    => 1001,
                'message' => '缺少正确部门名称，不能包含:*?"<>｜',
            ];
        }
        if ($this->operation_type != 'delete' && !$this->parentid) {
            return [
                'code'    => 1001,
                'message' => '缺少父级id',
            ];
        }
        $postParam = [
            'name'     => $this->name,
            'parentid' => $this->parentid,
        ];
        if ($this->order) {
            $postParam['order'] = $this->order;
        }
        if ($this->id) {
            $postParam['id']    = $this->id;
        }
        fdump_api(['msg' => '创建/更新/删除部门-参数', 'operation_type' => $this->operation_type, 'access_token' => $this->access_token, 'id' => $this->id, 'postParam' => $postParam, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
        if ($this->operation_type == 'delete' && 0) {
            $result = (new WorkWeiXinRequestService())->cgiBinDepartmentDelete($this->access_token, $this->id);
        } elseif ($this->operation_type == 'update') {
            $result = (new WorkWeiXinRequestService())->cgiBinDepartmentUpdate($this->access_token, $postParam);
        } else {
            $result = (new WorkWeiXinRequestService())->cgiBinDepartmentCreate($this->access_token, $postParam);
        }
        fdump_api(['msg' => '创建/更新/删除部门-结果', 'result' => $result, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
        if (!isset($param['new']) && isset($result['errcode']) && $result['errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            fdump_api(['msg' => 'token失效重新获取调用下', 'result' => $result, 'param' => $param, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
            return $this->departmentOperation($param);
        }
        if ($this->operation_type == 'delete' && $result['errcode'] == 60123) {
            fdump_api(['msg' => '无效组织id', 'result' => $result, 'param' => $param, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                "id"      => $this->id,
                "errmsg"  => 'invalid party id',
                "errcode" => 0,
            ];
        }
        if ($this->operation_type == 'update' && $result['errcode'] == 60003) {
            $param['operation_type'] = 'create';
            $param['access_token'] = $this->access_token;
            fdump_api(['msg' => '更新失败【不存在】走添加', 'result' => $result, 'param' => $param, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
            return $this->departmentOperation($param);
        }
        // todo处理重复问题 department existed
        if ($this->id && $result['errcode'] == 60008) {
            $param['access_token'] = $this->access_token;
            fdump_api(['msg' => '添加失败已存在查询下', 'id' => $this->id, 'result' => $result, 'param' => $param, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
            $departmentDetail = $this->getDepartmentDetail($this->id, $param);
            if (isset($departmentDetail['department']) && $departmentDetail['department']) {
                $department = $departmentDetail['department'];
            }
            fdump_api(['msg' => '添加失败已存在查询-结果', 'departmentDetail' => $departmentDetail, 'line' => __LINE__,], 'qiYeWeiXin/businessSynQwLog', 1);
            if (isset($department['id']) && $department['id'] && $department['parentid'] == $this->parentid) {
                return [
                    "id"      => $department['id'],
                    "name"    => $department['name'],
                    "errmsg"  => 'department existed',
                    "errcode" => 0,
                ];
            } else {
                // todo 名称如果重复考虑 名称+id进行同步
            }
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        if ($this->name) {
            $result['name'] = $this->name;
        }
        return $result;
    }

    protected $cursor, $limit, $userid, $department, $isRecord, $mobile;
    protected $dbWorkWeixinUser;
    
    /**
     * 过滤企业微信部门相关数据
     * @param $param
     */
    public function userCommonFilter($param) {
        $this->tokenCommonFilter($param);
        $this->cursor     = isset($param['cursor'])     && $param['cursor']     ? trim($param['cursor'])     : '';
        $this->limit      = isset($param['limit'])      && $param['limit']      ? trim($param['limit'])      : '';
        $this->userid     = isset($param['userid'])     && $param['userid']     ? trim($param['userid'])     : '';
        $this->department = isset($param['department']) && $param['department'] ? trim($param['department']) : '';
        $this->isRecord   = isset($param['isRecord'])   && $param['isRecord']   ? trim($param['isRecord'])   : '';
        $this->mobile     = isset($param['mobile'])     && $param['mobile']     ? trim($param['mobile'])     : '';
    }

    /** @var 具体参考文档链接[企业内部开发>服务端API>通讯录管理>成员管理>创建成员]https://developer.work.weixin.qq.com/document/path/90195 */
    protected $alias, $add_department, $delete_department, $position, $gender, $email, $biz_mail, $telephone, $to_invite, $address, $enable;
    /**
     * 企业成员 添加编辑删除等操作 过滤
     * @param $param
     */
    public function userOperationFilter($param) {
        $this->tokenCommonFilter($param);
        $this->userid            = isset($param['userid'])            && $param['userid']            ? trim($param['userid'])            : '';
        $this->mobile            = isset($param['mobile'])            && $param['mobile']            ? trim($param['mobile'])            : '';
        $this->name              = isset($param['name'])              && $param['name']              ? $this->checkStr($param['name'])  : '';
        $this->operation_type    = isset($param['operation_type'])    && $param['operation_type']    ? trim($param['operation_type'])    : 'create';
        /** @var string alias 成员别名。长度1~64个utf8字符*/                                                   
        $this->alias             = isset($param['alias'])             && $param['alias']             ? $this->checkStr($param['alias']) : '';
        /** @var array department 成员所属部门id列表，不超过100个 类似 [1, 2] */                                    
        $this->department        = isset($param['department'])        && $param['department']        ? $param['department']              : [];
        /** @var string add_department 新增的部门id       3  即将成员加入此部门*/                                  
        $this->add_department    = isset($param['add_department'])    && $param['add_department']    ? $param['add_department']          : '';
        /** @var string delete_department 删除部门身份id  4 即将对应成员从这个部门移除 */
        $this->delete_department = isset($param['delete_department']) && $param['delete_department'] ? $param['delete_department']       : '';
        /** @var string position 职务信息。长度为0~128个字符 */
        $this->position          = isset($param['position'])          && $param['position']          ? trim($param['position'])          : '';
        /** @var string gender 性别。1表示男性，2表示女性 */                      
        $this->gender            = isset($param['gender'])            && $param['gender']            ? trim($param['gender'])            : '';
        /** @var string email 邮箱。长度6~64个字节，且为有效的email格式。企业内必须唯一，mobile/email二者不能同时为空 */
        $this->email             = isset($param['email'])             && $param['email']             ? trim($param['email'])             : '';
        /**
         * @var string biz_mail 企业邮箱。
         * 仅对开通企业邮箱的企业有效。长度6~64个字节，且为有效的企业邮箱格式。企业内必须唯一。
         * 未填写则系统会为用户生成默认企业邮箱（由系统生成的邮箱可修改一次，2022年4月25日之后创建的成员需通过企业管理后台-协作-邮件-邮箱管理-成员邮箱修改） 
         */
        $this->biz_mail          = isset($param['biz_mail'])          && $param['biz_mail']          ? trim($param['biz_mail'])          : '';
        /** @var string telephone 座机。32字节以内，由纯数字、“-”、“+”或“,”组成。*/
        $this->telephone         = isset($param['telephone'])         && $param['telephone']         ? trim($param['telephone'])         : '';
        /** @var string enable 启用/禁用成员。1表示启用成员，0表示禁用成员*/
        $this->enable            = isset($param['enable'])            && $param['enable']            ? intval($param['enable'])          : 1;
        /** @var string to_invite 是否邀请该成员使用企业微信（将通过微信服务通知或短信或邮件下发邀请，每天自动下发一次，最多持续3个工作日），默认值为true。 */
        $this->to_invite         = isset($param['to_invite'])         && $param['to_invite']         ? $param['to_invite']               : true;
        /** @var string address 地址。长度最大128个字符 */
        $this->address           = isset($param['address'])           && $param['address']           ? trim($param['address'])           : '';
        if (!$this->department) {
            $this->department = [];
        }
    }

    /**
     * 获取成员ID列表
     * @param $param
     * @return array|mixed
     */
    public function getUserListId($param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userCommonFilter($param);
        if (!$this->property_id) {
            return [
                'code'    => 1001,
                'message' => '缺少物业id',
            ];
        }
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        $postParam = [];
        if ($this->cursor) {
            $postParam['cursor'] = $this->cursor;
        }
        if ($this->limit) {
            $postParam['limit']  = $this->limit;
        }
        $result = (new WorkWeiXinRequestService())->cgiBinUserListId($this->access_token, $postParam);
        if (!isset($param['new']) && isset($result['errcode']) && $result['errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            return $this->getUserListId($param);
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        if (isset($result['dept_user']) && $result['dept_user']) {
            $param['access_token'] = $this->access_token;
            $param['corpid']       = $this->corpid;
            $param['corpsecret']   = $this->corpsecret;
            $param['isRecord']     = 1;
            $getUserIdArr = [];
            $this->dbWorkWeixinUser = new WorkWeixinUser();
            foreach ($result['dept_user'] as &$item) {
                if (isset($item['userid']) && $item['userid'] && !isset($getUserIdArr[$item['userid']])) {
                    $param['userid']     = $item['userid'];
                    $param['department'] = isset($item['department']) ? $item['department'] : '';
                    $user = $this->getUserGetDetail($param);
                    if (!isset($user['code']) || !$user['code']) {
                        $getUserIdArr[$item['userid']] = $item['userid'];
                    }
                    $item['user'] = $user;
                }
            }
        }
        return $result;
    }
    
    /**
     * 获取成员详情
     * @param $param
     * @return array|mixed
     */
    public function getUserGetDetail($param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userCommonFilter($param);
        if (!$this->property_id) {
            return [
                'code'    => 1001,
                'message' => '缺少物业id',
            ];
        }
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        if (!$this->userid) {
            return [
                'code'    => 1001,
                'message' => '缺少成员UserID',
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinUserGet($this->access_token, $this->userid);
        if (!isset($param['new']) && isset($result['errcode']) && $result['errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            return $this->getUserGetDetail($param);
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        if ($this->isRecord && isset($result['userid']) && $result['userid']) {
            if (!$this->dbWorkWeixinUser) {
                $this->dbWorkWeixinUser = new WorkWeixinUser();
            }
            // todo 要保存相关数据
            $whereUnique = [
                'from'        => WorkWeiXinConst::FROM_PROPERTY,
                'from_id'     => $this->property_id,
                'auth_corpid' => $this->corpid,
                'user_id'     => $result['userid'],
                'delete_time' => 0,
            ];
            $workWeiXinUserData = [
                'from'        => WorkWeiXinConst::FROM_PROPERTY,
                'from_id'     => $this->property_id,
                'auth_corpid' => $this->corpid,
                'info_type'   => WorkWeiXinConst::INFO_TYPE_WORK_WEI_XIN_USER_GET,
                'time_stamp'  => $this->now_time,
                'change_type' => WorkWeiXinConst::CHANGE_TYPE_WORK_WEI_XIN_GET_USER,
                'user_id'     => $result['userid'],
            ];
            if (isset($result['name']) && $result['name'] && !$this->name) {
                $workWeiXinUserData['name'] = $result['name'];
                $this->name = $result['name'];
            }
            if (isset($result['department']) && $result['department']) {
                $workWeiXinUserData['department'] = json_encode($result['department'],JSON_UNESCAPED_UNICODE);
                $this->department = $result['department'];
            }
            if (isset($result['position']) && $result['position']) {
                $workWeiXinUserData['position'] = $result['position'];
                $this->position = $result['position'];
            }
            if (isset($result['mobile']) && $result['mobile']) {
                $workWeiXinUserData['mobile'] = $result['mobile'];
                $this->mobile = $result['mobile'];
            }
            if (isset($result['gender']) && $result['gender']) {
                $workWeiXinUserData['gender'] = $result['gender'];
                $this->gender = $result['gender'];
            }
            if (isset($result['email']) && $result['email']) {
                $workWeiXinUserData['email'] = $result['email'];
                $this->email = $result['email'];
            }
            if (isset($result['avatar']) && $result['avatar']) {
                $workWeiXinUserData['avatar'] = $result['avatar'];
                $this->avatar = $result['avatar'];
            }
            if (isset($result['status']) && $result['status']) {
                $workWeiXinUserData['status'] = $result['status'];
            }
            if (isset($result['extattr']) && $result['extattr']) {
                $workWeiXinUserData['ext_attr'] = json_encode($result['extattr'],JSON_UNESCAPED_UNICODE);
            }
            if (isset($result['order']) && $result['order']) {
                $workWeiXinUserData['order'] = json_encode($result['order'],JSON_UNESCAPED_UNICODE);
            }
            if (isset($result['main_department']) && $result['main_department']) {
                $workWeiXinUserData['main_department'] = $result['main_department'];
            }
            if (isset($result['main_department']) && $result['main_department']) {
                $workWeiXinUserData['main_department'] = $result['main_department'];
            }
            if (isset($result['is_leader_in_dept']) && $result['is_leader_in_dept']) {
                $workWeiXinUserData['is_leader_in_dept'] = json_encode($result['is_leader_in_dept'],JSON_UNESCAPED_UNICODE);
            }
            if (isset($result['thumb_avatar']) && $result['thumb_avatar']) {
                $workWeiXinUserData['thumb_avatar'] = $result['thumb_avatar'];
            }
            if (isset($result['direct_leader']) && $result['direct_leader']) {
                $workWeiXinUserData['direct_leader'] = json_encode($result['direct_leader'],JSON_UNESCAPED_UNICODE);
            }
            if (isset($result['biz_mail']) && $result['biz_mail']) {
                $workWeiXinUserData['biz_mail'] = $result['biz_mail'];
            }
            if (!isset($workWeiXinUserData['suite_corpid']) && isset($param['suite_corpid']) && $param['suite_corpid']) {
                $workWeiXinUserData['suite_corpid'] = $param['suite_corpid'];
            }
            if (!isset($workWeiXinUserData['suite_id']) && isset($param['suite_id']) && $param['suite_id']) {
                $workWeiXinUserData['suite_id'] = $param['suite_id'];
            }
            if (!isset($workWeiXinUserData['open_user_id']) && isset($param['open_user_id']) && $param['open_user_id']) {
                $workWeiXinUserData['open_user_id'] = $param['open_user_id'];
            }
            $userInfo = $this->dbWorkWeixinUser->getOne($whereUnique, 'id');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            if ($userInfo && isset($userInfo['id'])) {
                $workWeiXinUserData['update_time'] = $this->now_time;
                $this->dbWorkWeixinUser->updateThis($whereUnique, $workWeiXinUserData);
            } else {
                $workWeiXinUserData['add_time'] = $this->now_time;
                $this->dbWorkWeixinUser->add($workWeiXinUserData);
            }
        }
        return $result;
    }

    /**
     * 手机号获取userid
     * 通过手机号获取其所对应的userid。
     * @param $param
     * @return array|mixed
     */
    public function mobileGetUserId($param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userCommonFilter($param);
        if (!$this->property_id) {
            return [
                'code'    => 1001,
                'message' => '缺少物业id',
            ];
        }
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        if (!$this->mobile) {
            return [
                'code'    => 1001,
                'message' => '缺少手机号',
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinUserGetUserId($this->access_token, $this->mobile);
        if (!isset($param['new']) && isset($result['errcode']) && $result['errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            return $this->mobileGetUserId($param);
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        if (isset($result['userid']) && $result['userid']) {
            $param['access_token'] = $this->access_token;
            $param['corpid']       = $this->corpid;
            $param['corpsecret']   = $this->corpsecret;
            $param['isRecord']     = 1;
            $param['userid']       = $result['userid'];
            $result['user'] = $this->getUserGetDetail($param);
        }
        return $result;
    }

    /**
     * 创建/更新/删除 成员
     * @param $param
     * [
     *      operation_type    : '', 操作类型  create 添加  update 更新  delete 删除
     *      delete_department : 20, delete 删除的时候携带上删除的所处部门 避免角色直接删除 保证只是去除对应部门角色
     *      add_department    : 20, create 添加的时候携带上添加的所处部门
     * ]
     * @return array|mixed
     */
    public function userOperation($param, $whereWork = []) {
        fdump_api(['msg' => '创建/更新/删除-成员', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userOperationFilter($param);
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        if ($this->operation_type != 'delete') {
            if (!$this->mobile) {
                return [
                    'code'    => 1001,
                    'message' => '缺少手机号',
                ];
            }
        }
        $noUser = false;
        // 以手机号查询下企微相关信息
        if ($this->mobile) {
            $param['access_token'] = $this->access_token;
            fdump_api(['msg' => '手机号查询用户id', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            $userDetail = $this->mobileGetUserId($param);
            fdump_api(['msg' => '手机号查询用户id-结果', 'line' => __LINE__, 'userDetail' => $userDetail], 'qiYeWeiXin/businessSynQwLog', 1);
            if (isset($userDetail['code']) && $userDetail['code'] == 46004) {
                $noUser = true;
            } elseif (isset($userDetail['user']) && $userDetail['user']) {
                // todo 查询到了 企业微信那边身份 以这个身份相关信息为准 处理相关替换
                $userWork = $userDetail['user'];
            }
            if (isset($userWork) && isset($userWork['userid'])) {
                $this->userid = $userWork['userid'];
            }
            if (isset($userWork) && isset($userWork['department'])) {
                $this->department = $userWork['department'];
            }
        }
        if (!$this->department) {
            $this->department = [];
        }
        
        $postParam = [];
        if (isset($userWork) && $userWork && $this->operation_type == 'create') {
            $this->operation_type = 'update';
        }
        if (!$this->userid) {
            fdump_api(['msg' => '缺少操作对象', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                'code'    => 1001,
                'message' => '缺少操作对象',
            ];
        }
        if ($this->operation_type == 'delete'){
            if ($noUser) {
                // 如果企微那边已经删除了 这边直接返回成功
                fdump_api(['msg' => 'user no exist', 'line' => __LINE__, 'operation_type' => $this->operation_type, 'noUser' => $noUser], 'qiYeWeiXin/businessSynQwLog', 1);
                return [
                    'errcode' => 0,
                    'errmsg'  => 'user no exist',
                    'userid'  => $this->userid,
                ];
            }
            if ($this->delete_department && in_array($this->delete_department, $this->department) && count($this->department) > 1) {
                // 确认删除是单纯移除单个部门下身份 去除对应部门 走编辑逻辑
                $delete_arr_key = array_search($this->delete_department,$this->department,true);
                unset($this->department[$delete_arr_key]);
                $this->operation_type = 'update';
            }
        } else {
            if (!$this->name) {
                fdump_api(['msg' => '缺少正确成员名称，不能包含:*?"<>｜', 'line' => __LINE__, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
                return [
                    'code'    => 1001,
                    'message' => '缺少正确成员名称，不能包含:*?"<>｜',
                ];
            }
            if ($this->add_department && !in_array($this->add_department, $this->department)) {
                $this->department[] = $this->add_department;
            }
            if (!$this->department) {
                fdump_api(['msg' => '缺少归属部门', 'line' => __LINE__, 'department' => $this->department, 'add_department' => $this->add_department, 'param' => $param], 'qiYeWeiXin/businessSynQwLog', 1);
                return [
                    'code'    => 1001,
                    'message' => '缺少归属部门',
                ];
            }
            $postParam['to_invite']  = $this->to_invite;
        }
        $postParam['enable']         = $this->enable;
        if ($this->userid) {
            $postParam['userid']     = $this->userid;
        }
        if ($this->name) {
            $postParam['name']       = $this->name;
        }
        if ($this->mobile) {
            $postParam['mobile']     = $this->mobile;
        }
        if ($this->department) {
            $postParam['department'] = $this->department;
        }
        if ($this->position) {
            $postParam['position']   = $this->position;
        }
        if ($this->gender) {
            $postParam['gender']     = $this->gender;
        }
        if ($this->email) {
            $postParam['email']      = $this->email;
        }
        if ($this->biz_mail) {
            $postParam['biz_mail']   = $this->biz_mail;
        }
        if ($this->telephone) {
            $postParam['telephone']  = $this->telephone;
        }
        if ($this->address) {
            $postParam['address']    = $this->address;
        }
        $qyUniqueJsonArr = $postParam;
        $qyUniqueJsonArr['corpid'] = isset($param['corpid']) && $param['corpid'] ? $param['corpid'] : '';
        $qy_unique_json = md5(json_encode($qyUniqueJsonArr,JSON_UNESCAPED_UNICODE));
        if ($this->operation_type == 'delete' && 0) {
            fdump_api(['msg' => '删除-处理', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'access_token' => $this->access_token, 'userid' => $this->userid, 'postParam' => $postParam], 'qiYeWeiXin/businessSynQwLog', 1);
            $result = (new WorkWeiXinRequestService())->cgiBinUserDelete($this->access_token, $this->userid);
        } elseif ($this->operation_type == 'update') {
            fdump_api(['msg' => '更新-处理', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'access_token' => $this->access_token, 'userid' => $this->userid, 'postParam' => $postParam], 'qiYeWeiXin/businessSynQwLog', 1);
            if ($whereWork) {
                if (!$this->dbHouseWorker) {
                    $this->dbHouseWorker = new HouseWorker();
                }
                $workInfo = $this->dbHouseWorker->getOne($whereWork, 'wid, qy_open_userid, qy_unique_json');
                $qy_open_userid = isset($workInfo['qy_open_userid']) && $workInfo['qy_open_userid'] ? $workInfo['qy_open_userid'] : '';
//                if ($qy_unique_json && isset($workInfo['qy_unique_json']) && $qy_unique_json == $workInfo['qy_unique_json']) {
//                    return [
//                        "userid"  => $this->userid,
//                        "errmsg"  => 'data not change',
//                        "errcode" => 0,
//                    ];
//                }
            }
            if (! isset($qy_open_userid) || ! $qy_open_userid) {
                $toResult = (new WorkWeiXinRequestService())->cgiBinBatchUseridToOpenuserid([$this->userid], $this->access_token);
                fdump_api(['msg' => '转换-处理', 'line' => __LINE__, 'userid' =>  $this->userid, 'access_token' => $this->access_token, 'toResult' => $toResult], 'qiYeWeiXin/businessSynQwLog', 1);
                if (isset($toResult['open_userid_list']) && !empty($toResult['open_userid_list'])) {
                    foreach ($toResult['open_userid_list'] as $open_userids) {
                        if ($open_userids['userid'] == $this->userid) {
                            $qy_open_userid = $open_userids['open_userid'];
                            $this->dbHouseWorker->editData($whereWork, ['qy_open_userid' => $qy_open_userid]);
                        }
                    }
                }
            }
            $qy_open_userid && $postParam['userid'] = $qy_open_userid;
            if (! $qy_open_userid) {
                fdump_api(['msg' => '无法更新-走先删除后添加处理', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'access_token' => $this->access_token], 'qiYeWeiXin/businessSynQwLog', 1);
                $result = (new WorkWeiXinRequestService())->cgiBinUserDelete($this->access_token, $this->userid);
                fdump_api(['msg' => '无法更新-走先删除结果', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'access_token' => $this->access_token, 'result' => $result], 'qiYeWeiXin/businessSynQwLog', 1);
                if (! $result['errcode'] || $result['errcode'] == 60111) {
                    // 删除成功或者已经不存在了 走添加
                    $param['operation_type'] = 'create';
                    $param['access_token'] = $this->access_token;
                    fdump_api(['msg' => '无法更新-删除后添加-更新不存在走下添加', 'line' => __LINE__, 'param' =>  $param], 'qiYeWeiXin/businessSynQwLog', 1);
                    return $this->userOperation($param);
                }
            } else {
                fdump_api(['msg' => '更新-处理', 'line' => __LINE__, 'access_token' =>  $this->access_token, 'postParam' =>  $postParam], 'qiYeWeiXin/businessSynQwLog', 1);
                $result = (new WorkWeiXinRequestService())->cgiBinUserUpdate($this->access_token, $postParam);
            }
        } else {
            fdump_api(['msg' => '创建-处理', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'access_token' => $this->access_token, 'userid' => $this->userid, 'postParam' => $postParam], 'qiYeWeiXin/businessSynQwLog', 1);
            $result = (new WorkWeiXinRequestService())->cgiBinUserCreate($this->access_token, $postParam);
            fdump_api(['msg' => '创建-处理-结果', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'access_token' => $this->access_token, 'userid' => $this->userid, 'postParam' => $postParam], 'qiYeWeiXin/businessSynQwLog', 1);
            if (!isset($param['new']) && isset($result['errcode']) && $result[ 'errcode'] == 60104) {
//                fdump_api(['msg' => '无法创建【已有手机号】-走先删除后添加处理', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'postParam' => $postParam], 'qiYeWeiXin/businessSynQwLog', 1);
//                $result = (new WorkWeiXinRequestService())->cgiBinUserDelete($this->access_token, $this->userid);
//                fdump_api(['msg' => '无法创建【已有手机号】-走先删除结果', 'line' => __LINE__, 'operation_type' =>  $this->operation_type, 'result' => $result], 'qiYeWeiXin/businessSynQwLog', 1);
//                if (! $result['errcode'] || $result['errcode'] == 60111) {
//                    $param['new'] = true;
//                    $param['operation_type'] = 'create';
//                    $param['access_token'] = $this->access_token;
//                    return $this->userOperation($param);
//                }
            }
        }
        if (!isset($param['new']) && isset($result['errcode']) && $result[ 'errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            fdump_api(['msg' => '创建/更新/删除-token失效重新获取调用下', 'line' => __LINE__, 'param' =>  $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return $this->userOperation($param);
        }
        if ($this->operation_type == 'delete' && $result['errcode'] == 60111) {
            fdump_api(['msg' => 'userid not found', 'line' => __LINE__, 'result' =>  $result], 'qiYeWeiXin/businessSynQwLog', 1);
            return [
                "userid"  => $this->userid,
                "errmsg"  => 'userid not found',
                "errcode" => 0,
            ];
        }
        if ($this->operation_type == 'update' && $result['errcode'] == 60003) {
            $param['operation_type'] = 'create';
            $param['access_token'] = $this->access_token;
            fdump_api(['msg' => '创建/更新/删除-更新部门不存在走下添加', 'line' => __LINE__, 'param' =>  $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return $this->departmentOperation($param);
        }
        if ($this->operation_type == 'update' && $result['errcode'] == 60111) {
            $param['operation_type'] = 'create';
            $param['access_token'] = $this->access_token;
            fdump_api(['msg' => '创建/更新/删除-更新不存在走下添加', 'line' => __LINE__, 'param' =>  $param], 'qiYeWeiXin/businessSynQwLog', 1);
            return $this->userOperation($param);
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        if ($this->name) {
            $result['name'] = $this->name;
        }
        if (!isset($result['userid']) && $this->userid) {
            $result['userid'] = $this->userid;
        }
        $result['qy_unique_json'] = $qy_unique_json;
        return $result;
    }

    /**
     * 将企业主体下的明文userid转换为服务商主体下的密文userid。
     */
    public function toOpenuserid($userIds, $param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userOperationFilter($param);
        $result = (new WorkWeiXinRequestService())->cgiBinBatchUseridToOpenuserid($userIds, $this->access_token);
        return $result;
    }
    
    protected $tagname, $tagid;
    /**
     * 过滤企业微信部门相关数据
     * @param $param
     */
    public function tagCommonFilter($param) {
        $this->tokenCommonFilter($param);
        $this->operation_type = isset($param['operation_type']) && $param['operation_type'] ? trim($param['operation_type'])      : 'create';
        /** @var string tagname 标签名称，长度限制为32个字以内（汉字或英文字母），标签名不可与其他标签重名。 */
        $this->tagname        = isset($param['tagname'])        && $param['tagname']        ? $this->checkStr($param['tagname']) : '';
        /** @var int tagid 标签id，非负整型，指定此参数时新增的标签会生成对应的标签id，不指定时则以目前最大的id自增。 */
        $this->tagid          = isset($param['tagid'])          && $param['tagid']          ? trim($param['tagid'])               : '';
    }

    /**
     * 获取标签列表
     * @param $param
     * @return array|mixed
     */
    public function getTagList($param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->tagCommonFilter($param);
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinTagList($this->access_token);
        if (!isset($param['new']) && isset($result['errcode']) && $result['errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            return $this->getTagList($param);
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        return $result;
    }

    /**
     * 创建/更新/删除 标签
     * @param $param
     * [
     *      operation_type    : '', 操作类型  create 添加  update 更新  delete 删除
     * ]
     * @return array|mixed
     */
    public function tagOperation($param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->tagCommonFilter($param);
        if (!$this->access_token) {
            return [
                'code'    => 1001,
                'message' => '缺少调用凭证',
            ];
        }
        if ($this->operation_type != 'delete' && !$this->tagname) {
            return [
                'code'    => 1001,
                'message' => '缺少正确标签名称，不能包含:*?"<>｜',
            ];
        } elseif ($this->operation_type == 'delete' && !$this->tagid) {
            return [
                'code'    => 1001,
                'message' => '缺少标签id',
            ];
        }
        if ($this->operation_type == 'delete') {
            $result = (new WorkWeiXinRequestService())->cgiBinTagDelete($this->access_token, $this->tagid);
        } elseif ($this->operation_type == 'update') {
            $result = (new WorkWeiXinRequestService())->cgiBinTagUpdate($this->access_token, $this->tagname, $this->tagid);
        } else {
            $result = (new WorkWeiXinRequestService())->cgiBinTagCreate($this->access_token, $this->tagname, $this->tagid);
        }
        if (!isset($param['new']) && isset($result['errcode']) && $result['errcode'] == 40014) {
            // token失效重新获取调用下
            $param['new'] = true;
            return $this->tagOperation($param);
        }
        if ($this->operation_type == 'delete' && $result['errcode'] == 40068) {
            return [
                "tagid"   => $this->tagid,
                "errmsg"  => 'invalid tagid',
                "errcode" => 0,
            ];
        }
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        if ($this->name) {
            $result['name'] = $this->name;
        }
        return $result;
    }

    /**
     * 构造第三方应用oauth2链接
     * 如果第三方应用需要在打开的网页里面携带用户的身份信息，第一步需要构造如下的链接来获取code：
     * @param $param
     * [
     *      property_id  : 1, // 物业id 选传
     *      redirect_url : '', // 授权后重定向的回调链接地址 直接传链接无需处理 
     * ]
     * @param string $scope
     * @return array|string
     */
    public function  getSuiteConnectOauth2AuthorizeUrl($param, $scope = 'snsapi_base') {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->property_id    = isset($param['property_id'])  && $param['property_id']  ? intval($param['property_id'])  : '';
        if (cfg('enterprise_help_application_check_switch') == 1) {
            // 服务商应用辅助审核开关打开了 没有匹配到手机号 就使用服务商应用辅助审核手机号
            $mobile = cfg('enterprise_help_application_check_phone');
            if ($mobile) {
                $property_id = $this->property_id ?? 0;
                $qyLoginByMobileInfo = (new  CommunityLoginService())->qyLoginByMobile($mobile, $property_id);
                if (isset($qyLoginByMobileInfo) && isset($qyLoginByMobileInfo['ticket']) && $qyLoginByMobileInfo['ticket']) {
                    $back_url = cfg('site_url') . "/packapp/community/pages/Property_menu/PropertyIndex?ticket=".$qyLoginByMobileInfo['ticket'];
                    return $back_url;
                }
            }
        }
        $redirect_url         = isset($param['redirect_url']) && $param['redirect_url'] ? trim($param['redirect_url']) : '';
        if (!$redirect_url) {
            $redirect_url  = cfg('site_url') . '/v20/public/index.php/community/common.QiWeiLogin/oauth2';
        }
        
        $appid         = $this->config['suite_id'];
        if ($this->property_id) {
            $state     = $this->property_id;
            $redirect_url .= '&property_id=' . $this->property_id;
        } else {
            $state     = '';
        }
        $redirect_uri  = urlencode($redirect_url);
        $response_type = 'code';
        $url = (new WorkWeiXinRequestService())->connectOauth2Authorize($appid, $redirect_uri, $response_type, $scope, $state);
        return $url;
    }
    
    /**
     * 获取访问用户身份
     * @param string $code               通过成员授权获取到的code
     * @param string $suite_access_token 第三方应用的suite_access_token
     * @return array|string
     */
    public function getServiceAuthGetUserInfo3rd($code, $suite_access_token = '') {
        fdump_api(['code' => $code, 'suite_access_token' => $suite_access_token], '$getServiceAuthGetUserInfo3rd', 1);
        $this->suite_access_token = $suite_access_token;
        $this->getSuiteAccessTokenFilter();
        if ($this->code) {
            return [
                'code'    => $this->code,
                'errmsg'  => $this->errmsg,
                'message' => $this->message,
            ];
        }
        if (!$code) {
            return [
                'code'    => 1001,
                'message' => '缺少成员授权获取到的code。',
            ];
        }
        fdump_api(['code' => $code, 'suite_access_token' => $this->suite_access_token], '$getServiceAuthGetUserInfo3rd', 1);
        $result = (new WorkWeiXinRequestService())->cgiBinServiceAuthGetUserInfo3rd($this->suite_access_token, $code);
        fdump_api(['$result' => $result], '$getServiceAuthGetUserInfo3rd', 1);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        $result['suite_access_token'] = $this->suite_access_token;
        return $result;
    }

    /**
     * 获取访问用户敏感信息
     * @param string $user_ticket 	      成员票据
     * @param string $suite_access_token  第三方应用的suite_access_token
     * @return array|mixed
     */
    public function getServiceAuthGetUserDetail3rd($user_ticket, $suite_access_token = '') {
        $this->suite_access_token = $suite_access_token;
        $this->getSuiteAccessTokenFilter();
        if ($this->code) {
            return [
                'code'    => $this->code,
                'errmsg'  => $this->errmsg,
                'message' => $this->message,
            ];
        }
        if (!$user_ticket) {
            return [
                'code'    => 1001,
                'message' => '缺少成员票据。',
            ];
        }
        $result = (new WorkWeiXinRequestService())->cgiBinServiceAuthGetUserDetail3rd($this->suite_access_token, $user_ticket);
        if (isset($result['errcode']) && !empty($result['errcode'])) {
            return ['code' => $result['errcode'], 'errmsg' => $result['errmsg'], 'message' => $this->traitWorkWeiXinErrorCode($result['errcode'])];
        }
        return $result;
    }
    
    protected $open_userid, $user_ticket, $avatar, $qr_code;
    public function getQiWeiLoginUserInfo($code, $param = []) {
        if ($code) {
            try {
                $result = $this->getServiceAuthGetUserInfo3rd($code);
            }catch (\Exception $e){
                $result = [];
                fdump_api(['line' => $e->getLine(),'file' => $e->getFile(),'property_id' => $e->getMessage(),'code' => $e->getCode()], 'workWeiXin/getQiWeiLoginUserInfo', true);
            }
            fdump_api(['code' => $code, 'result' => $result], 'workWeiXin/getQiWeiLoginUserInfo', 1);
            if (isset($result['corpid']) && $result['corpid']) {
                $this->corpid = $result['corpid'];
            }
            if (isset($result['userid']) && $result['userid']) {
                $this->userid = $result['userid'];
            }
            if (isset($result['open_userid']) && $result['open_userid']) {
                $this->open_userid = $result['open_userid'];
            }
            if (isset($result['user_ticket']) && $result['user_ticket']) {
                $this->user_ticket = $result['user_ticket'];
            }
            if (isset($result['suite_access_token']) && $result['suite_access_token']) {
                $this->suite_access_token = $result['suite_access_token'];
            }
        }
        if ($this->user_ticket) {
            $result1 = $this->getServiceAuthGetUserDetail3rd($this->user_ticket, $this->suite_access_token);
            fdump_api(['user_ticket' => $this->user_ticket, 'result1' => $result1], 'workWeiXin/getQiWeiLoginUserInfo', 1);
            if (isset($result1['corpid']) && $result1['corpid']) {
                $this->corpid = $result1['corpid'];
            }
            if (isset($result1['userid']) && $result1['userid']) {
                $this->userid = $result1['userid'];
            }
            if (isset($result1['name']) && $result1['name']) {
                $this->name = $result1['name'];
            }
            if (isset($result1['gender']) && $result1['gender']) {
                $this->gender = $result1['gender'];
            }
            if (isset($result1['avatar']) && $result1['avatar']) {
                $this->avatar = $result1['avatar'];
            }
            if (isset($result1['qr_code']) && $result1['qr_code']) {
                $this->qr_code = $result1['qr_code'];
            }
        }
        
        if ($this->corpid && (!isset($param['property_id']) || !$param['property_id'])) {
            $where = [];
            $where[] = ['bind_type', '=', 0];
            $where[] = ['corpid', '=', $this->corpid];
            $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
            $bindInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corpid,bind_id, qy_address_book_secret','pigcms_id DESC');
            if ($bindInfo && !is_array($bindInfo)) {
                $bindInfo = $bindInfo->toArray();
            }
            $param['property_id'] = isset($bindInfo['bind_id']) && $bindInfo['bind_id'] ? intval($bindInfo['bind_id']) : 0;
        }
        
        $mobile       = '';
        $suite_corpid = cfg('enterprise_wx_corpid');
        $suite_id     = $this->config['suite_id'];
        if (isset($param['property_id']) && $param['property_id'] && $this->userid) {
            $param['userid']       = $this->userid;
            $param['isRecord']     = 1;
            $param['suite_corpid'] = $suite_corpid;
            $param['suite_id']     = $suite_id;
            $param['open_user_id'] = $this->open_userid;
            $user = $this->getUserGetDetail($param);
            if (isset($user['mobile']) && $user['mobile']) {
                $mobile = $user['mobile'];
            }
        } elseif ($this->corpid && $this->userid) {
            $param['corpid']       = $this->corpid;
            $param['userid']       = $this->userid;
            $param['suite_corpid'] = $suite_corpid;
            $param['suite_id']     = $suite_id;
            $param['isRecord']     = 1;
            $param['open_user_id'] = $this->open_userid;
            $user = $this->getUserGetDetail($param);
            if (isset($user['mobile']) && $user['mobile']) {
                $mobile = $user['mobile'];
            }
        }
        if (!$mobile && $this->userid && $this->corpid) {
            if (!$this->dbWorkWeixinUser) {
                $this->dbWorkWeixinUser = new WorkWeixinUser();
            }
            $whereUnique = [
                'from'        => WorkWeiXinConst::FROM_PROPERTY,
                'auth_corpid' => $this->corpid,
                'user_id'     => $this->userid,
                'delete_time' => 0,
            ];
            $userInfo = $this->dbWorkWeixinUser->getOne($whereUnique, 'id, mobile, from, from_id');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            if (isset($userInfo['mobile']) && $userInfo['mobile']) {
                $mobile = $userInfo['mobile'];
            }
            if (isset($userInfo['mobile']) && $userInfo['mobile']) {
                $mobile = $userInfo['mobile'];
            }
            if (!$this->property_id && isset($userInfo['from']) && $userInfo['from'] == 'property' && $userInfo['from_id']) {
                $this->property_id = $userInfo['from_id'];
            }
        }

        if (!$mobile && cfg('enterprise_help_application_check_switch') == 1) {
            // 服务商应用辅助审核开关打开了 没有匹配到手机号 就使用服务商应用辅助审核手机号
            $mobile = cfg('enterprise_help_application_check_phone');
        }
        // todo 获得了手机号进行手机号处理
        fdump_api(['mobile'=>$mobile,'userid'=>$this->userid], 'workWeiXin/getQiWeiLoginUserInfo',1);
        $qyLoginByMobileInfo=array();
        if ($mobile) {
            $qyLoginByMobileInfo = (new  CommunityLoginService())->qyLoginByMobile($mobile, $this->property_id);
        } elseif ($this->userid) {
            // todo 没有手机号码 处理记录相关信息进行绑定操作
            if (!$this->dbWorkWeixinUser) {
                $this->dbWorkWeixinUser = new WorkWeixinUser();
            }
            $whereUnique = [
                'from'        => WorkWeiXinConst::FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN,
                'from_id'     => $this->userid,
                'auth_corpid' => $this->corpid,
                'user_id'     => $this->userid,
                'delete_time' => 0,
            ];
            $userInfo = $this->dbWorkWeixinUser->getOne($whereUnique, 'id, mobile, from, from_id');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            if (isset($userInfo['mobile']) && $userInfo['mobile']) {
                $mobile = $userInfo['mobile'];
                $qyLoginByMobileInfo = (new  CommunityLoginService())->qyLoginByMobile($mobile, $this->property_id);
            } else {
                // todo 查询了相关信息后还是没有手机号码 执行跳转绑定手机号页面
                $md5_ticket_arr = [];
                $dataParam = [
                    'from'        => WorkWeiXinConst::FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN,
                    'from_id'     => $this->userid,
                    'auth_corpid' => $this->corpid,
                    'user_id'     => $this->userid,
                    'info_type'   => WorkWeiXinConst::INFO_TYPE_WORK_WEI_XIN_AUTH_USER_INFO,
                    'time_stamp'  => $this->now_time,
                    'change_type' => WorkWeiXinConst::CHANGE_TYPE_WORK_WEI_XIN_GET_AUTH_INFO,
                ];
                $md5_ticket_arr['from']       = WorkWeiXinConst::FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN;
                $md5_ticket_arr['user_id']    = $this->userid;
                $md5_ticket_arr['time_stamp'] = $this->now_time;
                if ($this->open_userid) {
                    $dataParam['open_user_id'] = $this->open_userid;
                    $md5_ticket_arr['open_user_id'] = $this->open_userid;
                }
                if ($suite_corpid) {
                    $dataParam['suite_corpid'] = $suite_corpid;
                    $md5_ticket_arr['suite_corpid'] = $suite_corpid;
                }
                if ($suite_id) {
                    $dataParam['suite_id']     = $suite_id;
                    $md5_ticket_arr['suite_id'] = $suite_id;
                }
                $md5_ticket = md5(json_encode($md5_ticket_arr, JSON_UNESCAPED_UNICODE));
                $dataParam['md5_ticket']       = $md5_ticket;
                if ($userInfo && isset($userInfo['id'])) {
                    $dataParam['update_time'] = $this->now_time;
                    $this->dbWorkWeixinUser->updateThis($whereUnique, $dataParam);
                } else {
                    $dataParam['add_time'] = $this->now_time;
                    $this->dbWorkWeixinUser->add($dataParam);
                }
                // todo 跳转绑定链接
                $type = WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE;
                $back_url = cfg('site_url') . "/packapp/community/pages/Community/bindPhone/index?type={$type}&bindCode=".$md5_ticket;
            }
        }
        if (isset($qyLoginByMobileInfo) && isset($qyLoginByMobileInfo['ticket']) && $qyLoginByMobileInfo['ticket']) {
            //$back_url = cfg('site_url') . "/packapp/community?loginTicket=".$qyLoginByMobileInfo['md5Ticket'];
            if($qyLoginByMobileInfo['login_role']==5){
                $back_url = cfg('site_url') . "/packapp/community/pages/Community_menu/index?ticket=".$qyLoginByMobileInfo['ticket'];
            }elseif ($qyLoginByMobileInfo['login_role']==6){
                $back_url = cfg('site_url') . "/packapp/community/pages/PropertyWorker_menu/PropertyWorkerIndex?ticket=".$qyLoginByMobileInfo['ticket'];
            }elseif ($qyLoginByMobileInfo['login_role']==4 || $qyLoginByMobileInfo['login_role']==3){
                $back_url =cfg('site_url') . "/packapp/community/pages/Property_menu/PropertyIndex?ticket=".$qyLoginByMobileInfo['ticket'];
            }else{
                $back_url = cfg('site_url') . "/packapp/community/";
            }
        } elseif (!isset($back_url)) {
            $back_url = cfg('site_url') . "/packapp/community/";
        }
//        $data = [
//            'userid'      => $this->userid,
//            'name'        => $this->name,
//            'corpid'      => $this->corpid,
//            'open_userid' => $this->open_userid,
//            'gender'      => $this->gender,
//            'avatar'      => $this->avatar,
//            'qr_code'     => $this->qr_code,
//            'mobile'      => $this->mobile,
//        ];
        return $back_url;
    }

    /**
     * 获取到绑定的相关信息
     * @param $bindCode
     * @param $type
     * @param $check
     * @return array
     */
    public function getAuthBindInfo($bindCode, $type, $phone = '', $check = false) {
        $arr = [];
        if ($type == WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE) {
            if (!$this->dbWorkWeixinUser) {
                $this->dbWorkWeixinUser = new WorkWeixinUser();
            }
            $whereUnique = [
                'from'        => WorkWeiXinConst::FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN,
                'md5_ticket'  => $bindCode,
                'delete_time' => 0,
            ];
            $userInfo = $this->dbWorkWeixinUser->getOne($whereUnique, 'id,user_id, auth_corpid');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            if ($check && $phone) {
                $arr = [];
                $checkExistUser = false;
                if (isset($userInfo['auth_corpid']) && $userInfo['auth_corpid']) {
                    $whereUniquePhone = [];
                    $whereUniquePhone[] = ['from',        '=', WorkWeiXinConst::FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN];
                    $whereUniquePhone[] = ['auth_corpid', '=', $userInfo['auth_corpid']];
                    $whereUniquePhone[] = ['user_id',     '<>', $userInfo['user_id']];
                    $whereUniquePhone[] = ['mobile',      '=', $phone];
                    $whereUniquePhone[] = ['delete_time', '=', 0];
                    $checkUserInfo = $this->dbWorkWeixinUser->getOne($whereUniquePhone, 'id');
                    if ($checkUserInfo && !is_array($checkUserInfo)) {
                        $checkUserInfo = $checkUserInfo->toArray();
                    }
                    if (isset($checkUserInfo['id'])) {
                        $checkExistUser = true;
                    }
                }
                if (!isset($userInfo['id'])) {
                    $checkExist     = false;
                } else {
                    $checkExist     = true;
                }
                $arr['checkExist']     = $checkExist;
                $arr['checkExistUser'] = $checkExistUser;
                $arr['userInfo']       = $userInfo;
                return $arr;
            }
            if (isset($userInfo['user_id']) && $userInfo['user_id']) {
                $arr['user_id'] = $userInfo['user_id'];
            }
            if (isset($userInfo['auth_corpid']) && $userInfo['auth_corpid']) {
                $dbHouseEnterpriseWxBind = new HouseEnterpriseWxBind();
                $where = [];
                $where[] = ['corpid|PlaintextCorpId', '=', $userInfo['auth_corpid']];
                $corpInfo = $dbHouseEnterpriseWxBind->getOne($where, 'corp_name, corp_full_name');
                if ($corpInfo && !is_array($corpInfo)) {
                    $corpInfo = $corpInfo->toArray();
                }
            }
            if (isset($corpInfo) && isset($corpInfo['corp_full_name']) && $corpInfo['corp_full_name']) {
                $arr['corp_name'] = $corpInfo['corp_full_name'];
            } elseif (isset($corpInfo) && isset($corpInfo['corp_name'])) {
                $arr['corp_name'] = $corpInfo['corp_name'];
            }
        }
        return $arr;
    }

    /**
     * 绑定手机号
     * @param $bindCode
     * @param $type
     * @param $phone
     * @param array $param
     * @return array
     * @throws \think\Exception
     */
    public function bindAuthUserPhone($bindCode, $type, $phone, $param = []) {
        if ($type == WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE) {
            if (!$this->dbWorkWeixinUser) {
                $this->dbWorkWeixinUser = new WorkWeixinUser();
            }
            $whereUnique = [
                'from'        => WorkWeiXinConst::FROM_COMMUNITY_MANAGE_WORK_WEI_XIN_LOGIN,
                'md5_ticket'  => $bindCode,
                'delete_time' => 0,
            ];
            $userInfo = $this->dbWorkWeixinUser->getOne($whereUnique, 'id,user_id, auth_corpid');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            if (!isset($userInfo['id'])) {
                throw new \think\Exception('绑定对象不存在');
            }
            $saveData = [
                'mobile'      => $phone,
                'update_time' => time(),
            ];
            $save = $this->dbWorkWeixinUser->updateThis($whereUnique, $saveData);
            if ($save === false) {
                throw new \think\Exception('绑定失败,请重试~');
            }
            $qyLoginByMobileInfo = (new  CommunityLoginService())->qyLoginByMobile($phone, $this->property_id);
            if (isset($qyLoginByMobileInfo) && isset($qyLoginByMobileInfo['md5Ticket']) && $qyLoginByMobileInfo['md5Ticket']) {
                $back_url = cfg('site_url') . "/packapp/community?loginTicket=".$qyLoginByMobileInfo['md5Ticket'];
            } elseif (!isset($back_url)) {
                $back_url = cfg('site_url') . "/packapp/community";
            }
        } else {
            throw new \think\Exception('绑定对象不存在');
        }
        return [
            'bindCode' => $bindCode,
            'type'     => $type,
            'phone'    => $phone,
            'back_url' => $back_url,
        ];
    }

    /**
     * 获取客户详情.
     * 企业可通过此接口，根据外部联系人的userid，拉取客户详情。
     */
    public function getExternalContactDetail($external_userid, $param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userOperationFilter($param);
        $result = (new WorkWeiXinRequestService())->cgiBinExternalContactGet($external_userid, $this->access_token);
        return $result;
    }

    /**
     * 批量获取客户详情.
     * 企业/第三方可通过此接口获取指定成员添加的客户信息列表.
     */
    public function getExternalContactBatchByUser($userid_list, $param) {
        if (!$this->judgeConfig()) {
            return [
                'code'    => 1001,
                'message' => WorkWeiXinConst::WORK_WEI_XIN_SUITE_CONFIG_ERR_TIP,
            ];
        }
        $this->userOperationFilter($param);
        $result = (new WorkWeiXinRequestService())->cgiBinExternalContactBatchGetByUser($userid_list, $this->access_token);
        return $result;
    }
}