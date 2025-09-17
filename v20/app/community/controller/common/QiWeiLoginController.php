<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信第登录
 */

namespace app\community\controller\common;

use app\common\model\service\send_message\SmsService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\workweixin\WorkWeiXinSuiteService;
use app\consts\WorkWeiXinConst;

class QiWeiLoginController  extends CommunityBaseController
{
    /**
     * 企业微信移动管理端登录
     * @return \json
     */
    public function manageLogin() {
        try{
            $sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
            $param = [];
            $url = $sWorkWeiXinSuiteService->getSuiteConnectOauth2AuthorizeUrl($param);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        fdump_api(['param' => $_REQUEST, 'url' => $url], 'workWeiXin/00manageLogin', 1);
        header("Location: $url");
        die;
    }

    /**
     * 授权后获取访问用户身份对应跳转登录
     * @return \json
     */
    public function oauth2() {
        $code        = $this->request->param('code','','trim');
        $property_id = $this->request->param('property_id','','int');
        try{
            $sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
            $param = [
                'property_id' => $property_id
            ];
            $back_url = $sWorkWeiXinSuiteService->getQiWeiLoginUserInfo($code, $param);
            fdump_api(['back_url' => $back_url,'param'=>$param], 'workWeiXin/getQiWeiLoginUserInfo', 1);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        header("Location: $back_url");
        die;
    }

    /**
     * 获取到绑定的相关信息
     * @return \json
     */
    public function getAuthBindInfo() {
        $bindCode = $this->request->param('bindCode','','trim');
        $type     = $this->request->param('type','','trim');
        try{
            $sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
            $arr = $sWorkWeiXinSuiteService->getAuthBindInfo($bindCode, $type);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $arr);
    }

    /**
     * 发送验证码
     * @return \json
     */
    public function sendCode()
    {
        $phone    = $this->request->param('phone', '', 'trim');
        $bindCode = $this->request->param('bindCode','','trim');
        $deviceId = $this->request->param('Device-Id', '', 'trim');
        $type_arr = [
            WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE => WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE
        ];
        $type = $this->request->param('type', '', 'trim');
        
        $phoneCountryType = $this->request->param('phone_country_type', '', 'trim');


        //检验手机号是否合法 国内,11位,海外，数字组合
        if ($phoneCountryType == '' || $phoneCountryType == '86') {
            $regx = '/^1\d{10}$/';
        } else {
            $regx = '/^\d+$/';
        }
        if (!preg_match($regx, $phone)) {
            return api_output(1001, [], L_('手机号格式有误'));
        }
        if (!isset($type_arr[$type])) {
            return api_output(1001, [], L_('非法请求来源'));
        }
        try {
            $param = [
                'bindCode' => $bindCode,
            ];
            (new SmsService())->sendCode($type, $phone, $phoneCountryType, $deviceId,  $param);
            return api_output(0, [], L_('发送成功'));
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * 绑定手机号
     * @return \json
     */
    public function bindPhone() {
        $bindCode = $this->request->param('bindCode','','trim');
        $type     = $this->request->param('type','','trim');
        $phone    = $this->request->param('phone','','trim');
        $code     = $this->request->param('code','','trim');
        try{
            $check = (new SmsService())->checkCode($phone, $code, WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE, WorkWeiXinConst::COMMUNITY_MANAGE_LOGIN_BIND_PHONE_CODE_TYPE);
            if (!$check) {
                return api_output(1001, [], L_('验证码错误'));
            }
            $sWorkWeiXinSuiteService = new WorkWeiXinSuiteService();
            $arr = $sWorkWeiXinSuiteService->bindAuthUserPhone($bindCode, $type, $phone);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0, $arr);
    }
}