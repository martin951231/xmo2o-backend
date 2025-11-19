<?php
/**
 * 物业引导
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/5/14 16:08
 */

namespace app\community\controller\login;
use app\community\controller\CommunityBaseController;
use app\common\model\service\send_message\SmsService;
use app\community\model\service\HouseInformationClueCollectionService;

class PropertyGuideController extends CommunityBaseController
{
    /**
     * Notes:对应手机号发送验证码
     * @return \json
     * @author: wanzy
     * @date_time: 2021/5/14 17:24
     */
    public function sendCode() {
        // 预约注册物业
        $from = 'property_guide';
        $param  = $this->request->param();
        try {
            $result = (new SmsService())->sendLoginSms($param,$from);
            return api_output(0, $result, '发送成功');
        } catch (\Throwable $th) {
            return api_output(1003, [], $th->getMessage());
        }
    }

    /**
     * Notes: 添加-智慧社区收集客户线索信息
     * @return \json
     * @author: wanzy
     * @date_time: 2021/5/14 17:23
     */
    public function addInformation() {
        $phone = $this->request->param('phone', '', 'trim');
        $phoneCountryType = $this->request->param('phone_country_type', '', 'trim');
        $name = $this->request->param('name', '', 'trim');
        $company = $this->request->param('company', '', 'trim');
        $code = $this->request->param('code', '', 'trim');
        $type = $this->request->param('type', '', 'trim');
        if (!$name) {
            return api_output(1001, [], '请输入姓名');
        }
        if (!$phone) {
            return api_output(1001, [], '请输入手机号');
        }
        if (!$type || $type!='mobile') {
            if (!$code) {
                return api_output(1001, [], '请输入验证码');
            }
        }
        //检验手机号是否合法 国内,11位,海外，数字组合
        if ($phoneCountryType == '' || $phoneCountryType == '86') {
            $regx = '/^1\d{10}$/';
        } else {
            $regx = '/^\d+$/';
        }
        if (!preg_match($regx, $phone)) {
            return api_output(1001, [], '手机号格式有误');
        }
        if (!$company) {
            return api_output(1001, [], '请输入公司名称');
        }
        $add_ip = request()->ip();
        //重置密码
        try {
            $data = [
                'name' => $name,
                'phoneCountryType' => $phoneCountryType,
                'phone' => $phone,
                'company' => $company,
                'code' => $code,
                'add_ip' => $add_ip,
                'type' => $type,
            ];
            $collection_id = (new HouseInformationClueCollectionService())->addInformation($data);
            $arr = [];
            $arr['collection_id'] = $collection_id;
            $arr['collection_img'] = cfg('site_url') .'/static/images/house/qywx/qykf.png';
            return api_output(0, $arr, '预约成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * Notes: 引导页内容
     * @return \json
     * @author: wanzy
     * @date_time: 2021/5/15 9:55
     */
    public function propertyGuide() {
        $property_id = $this->adminUser['property_id'];
        if (!isset($property_id)) {
            return api_output(1002, [], "登录失效了");
        }
        $returnArr = (new HouseInformationClueCollectionService())->propertyGuide($property_id);
        return api_output(0, $returnArr);
    }

    /**
     * Notes: 完成引导内容
     * @return \json
     * @author: wanzy
     * @date_time: 2021/5/15 10:23
     */
    public function completePropertyGuide() {
        // 用户id
        $login_userId = $this->_uid;
        // 用户角色
        $login_role = $this->login_role;
        $property_id = $this->adminUser['property_id'];
        if (!isset($property_id)) {
            return api_output(1002, [], "登录失效了");
        }
        try {
            $data = [
                'login_role' => $login_role,
                'login_userId' => $login_userId,
            ];
            $arr = (new HouseInformationClueCollectionService())->completePropertyGuide($property_id,$data);
            return api_output(0, $arr, '操作成功');
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }
}