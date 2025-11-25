<?php

namespace app\deliver\model\service;

use app\deliver\model\db\DeliverUserRegister;
use think\Exception;

/**
 * 兼职配送员申请服务
 * @author: 张涛
 * @date: 2020/12/1
 * @package app\deliver\model\service
 */
class DeliverUserRegisterService
{

    public $registerMod = null;

    public function __construct()
    {
        $this->registerMod = new DeliverUserRegister();
    }

    /**
     * 注册
     * @author: 张涛
     * @date: 2020/12/1
     */
    public function register($params)
    {
        $phone = $params['phone'] ?? '';
        $truename = $params['truename'] ?? '';
        $cardNumber = $params['card_number'] ?? '';
        $code = $params['code'] ?? '';
        $face_img = $params['face_img'] ?? '';
        $provinceId = $params['province_id'] ?? 0;
        $cityId = $params['city_id'] ?? 0;
        $lng = $params['lng'] ?? '';
        $lat = $params['lat'] ?? '';
        $adress = $params['adress'] ?? '';
        // $fields = $params['fields'] ?? '';
        if (empty($provinceId) || empty($cityId)) {
            throw new Exception(L_('请选择意向工作城市'));
        }
        if (empty($adress)) {
            throw new Exception(L_('请填写意向工作地址'));
        }
        if (empty($truename)) {
            throw new Exception(L_('真实姓名不能为空'));
        }
        if (empty($phone) || !preg_match('/^\d+$/', $phone)) {
            throw new Exception(L_('手机号码格式不正确'));
        }
        if (empty($truename)) {
            throw new Exception(L_('真实姓名不能为空'));
        }
        if (empty($cardNumber)) {
            throw new Exception(L_('身份证号不能为空'));
        }
        if (empty($code)) {
            throw new Exception(L_('验证码不能为空'));
        }
        if ($this->checkPhoneInRegisterApply($phone)) {
            throw new Exception(L_('该手机号已提交申请，系统正在审核中，请耐心等待'));
        }
        if ($this->checkCardNumberInRegisterApply($cardNumber)) {
            throw new Exception(L_('该身份证号已提交申请，系统正在审核中，请耐心等待'));
        }
        if ($this->checkCardNumberInRegisterApply($cardNumber)) {
            throw new Exception(L_('该身份证号已提交申请，系统正在审核中，请耐心等待'));
        }
        if ((new \app\deliver\model\service\DeliverUserService())->checkCardNumberInUse($cardNumber)) {
            throw new Exception(L_('该身份证号已被使用'));
        }

        $smsWhere = [
            ['phone', '=', $phone],
            ['type', '=', 34],
            ['status', '=', 0],
            ['extra', '=', $code],
            ['expire_time', '>', time()],
        ];
        $smsRecord = \think\facade\Db::name('app_sms_record')->where($smsWhere)->find();
        if (empty($smsRecord)) {
           throw new Exception(L_('验证码错误'));
        }
        \think\facade\Db::name('app_sms_record')->where('pigcms_id', $smsRecord['pigcms_id'])->update(['status' => 1]);

        $data = [
            'phone' => $phone,
            'truename' => $truename,
            'card_number' => $cardNumber,
            // 'user_field' => $fields,
            'ip' => request()->ip(),
            'apply_time' => time(),
            'status' => 0,
            'face_recognition_images' => $face_img,
            'province_id'=>$provinceId,
            'city_id'=>$cityId,
            'lng' => $lng,
            'lat' => $lat,
            'adress' => $adress,
        ];
        $this->registerMod->insert($data);
    }

    /**
     * 检查手机号是否在注册申请未审核记录中
     * @param $phone
     * @author: 张涛
     * @date: 2020/12/11
     */
    public function checkPhoneInRegisterApply($phone)
    {
        $id = $this->registerMod->where(['status' => 0, 'phone' => $phone])->value('id');
        return $id > 0 ? true : false;
    }

    /**
     * 检查身份证号是否在注册申请未审核记录中
     * @param $cardNumber
     * @author: 张涛
     * @date: 2020/12/11
     */
    public function checkCardNumberInRegisterApply($cardNumber)
    {
        $id = $this->registerMod->where(['status' => 0, 'card_number' => $cardNumber])->value('id');
        return $id > 0 ? true : false;
    }

    /**
     * 获得总数
     * @param $where
     * @date: 2022/01/04
     */
    public function getCount($where)
    {
        $id = $this->registerMod->getCount($where);
        return $id > 0 ? $id : 0;
    }

}