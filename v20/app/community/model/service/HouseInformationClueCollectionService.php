<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/5/14 17:01
 */

namespace app\community\model\service;

use think\Exception;

use app\community\model\db\HouseInformationClueCollection;
use app\community\model\db\HousePropertyGuide;

class HouseInformationClueCollectionService
{
    /**
     * Notes: 添加客户信息
     * @param $data
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/5/15 10:37
     */
    public function addInformation($data) {
        if (isset($data['phoneCountryType'])) {
            $phoneCountryType = $data['phoneCountryType'];
        } else {
            $phoneCountryType = '';
        }
        if (isset($data['phone'])) {
            $phone = $data['phone'];
        } else {
            throw new Exception('请输入手机号');
        }
        $phone = phone_format($phoneCountryType, $phone);
        if (!isset($data['type']) || !$data['type'] || $data['type']!='mobile') {
            if (isset($data['code'])) {
                $code = $data['code'];
            } else {
                throw new Exception('请输入验证码');
            }
            $smsWhere = [
                ['phone', '=', $phone],
                ['type', '=', 51],
                ['status', '=', 0],
                ['extra', '=', $code],
                ['expire_time', '>', time()],
            ];
            $smsRecord = \think\facade\Db::name('app_sms_record')->where($smsWhere)->find();
            if (empty($smsRecord)) {
                throw new Exception('验证码错误');
            }
        }
        if (isset($data['name']) && $data['name']) {
            $name = $data['name'];
        } else {
            $name = '';
        }
        if (isset($data['company']) && $data['company']) {
            $company = $data['company'];
        } else {
            $company = '';
        }
        if (isset($data['add_ip']) && $data['add_ip']) {
            $add_ip = $data['add_ip'];
        } else {
            $add_ip = \think\Facade\Request::ip();
        }
        $now_time = time();
        $where_repeat = [];
        $where_repeat[] = ['phoneCountryType', '=', $phoneCountryType];
        $where_repeat[] = ['phone', '=', $phone];
        // 防止一天内重复提交
        $where_repeat[] = ['add_time', '>', $now_time-86400];
        $repeat_info = (new HouseInformationClueCollection())->getOne($where_repeat);
        if (!empty($repeat_info) && isset($repeat_info['collection_id'])) {
            throw new Exception('很抱歉，一天内只能提交一次哦');
        }
        $data = [
            'name' => $name,
            'from' => 1,
            'phoneCountryType' => $phoneCountryType,
            'phone' => $phone,
            'company' => $company,
            'add_ip' => $add_ip,
            'add_time' => $now_time,
        ];
        $collection_id = (new HouseInformationClueCollection())->add($data);
        if ($collection_id) {
            (new propertyDemoService())->qywxSend($data);
            return $collection_id;
        } else {
            throw new Exception('添加失败！请重试~');
        }
    }

    /**
     * Notes: 物业引导
     * @param $property_id
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/5/15 10:37
     */
    public function propertyGuide($property_id) {
        $site_url = cfg('site_url');
        if (cfg('site_logo')) {
            $system_admin_logo = cfg('site_logo');
            $system_admin_logo = replace_file_domain($system_admin_logo);
        } else {
            $system_admin_logo = $site_url . "/tpl/System/Static/images/pigcms_logo.png";
        }
        $returnArr['system_admin_logo'] = $system_admin_logo;
        $returnArr['title'] = '初次使用';
        $returnArr['des'] = '很高兴认识您，先跟着我们完善一下基础配置吧';
        $block = [];
        $block[] = [
            'img' => $site_url .'/static/images/house/property/img_1_1.png',
            'num_img' => $site_url .'/static/images/house/property/01.png',
            'title' => '完善物业信息',
            'des' => '填写物业基本信息',
            'btn' => '去完善',
            'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_config',
            'v20_path' =>'/property/property.iframe/property_config',
        ];
        $block[] = [
            'img' => $site_url .'/static/images/house/property/img_1_2.png',
            'num_img' => $site_url .'/static/images/house/property/02.png',
            'title' => '创建小区',
            'des' => '系统支持多小区管理模式，您至少需要创建一个小区',
            'btn' => '去创建',
            'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_village_list',
            'v20_path' =>'/property/property.iframe/property_village_list',
        ];

        $servicePackageOrder=new PackageOrderService();
        $package_content = $servicePackageOrder->getPackageContent($property_id);
        $content_id = $package_content['content'];
        if(!$content_id){
            $content_id = [0];
        }

        if(in_array(2,$content_id)) {
            $property_weixin = [
                'title' => '公众号配置',
                'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_weixin_index',
                'v20_path' =>'/property/property.iframe/property_weixin_index',
            ];
        } else {
            $property_weixin = [
                'title' => '公众号配置',
                'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_application_index',
                'v20_path' =>'/property/property.iframe/property_application_index',
            ];
        }
        if(in_array(3,$content_id)) {
            $property_weixinapp = [
                'title' => '小程序配置',
                'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_weixinapp_index',
                'v20_path' =>'/property/property.iframe/property_weixinapp_index',
            ];
        } else {
            $property_weixinapp = [
                'title' => '小程序配置',
                'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_application_index',
                'v20_path' =>'/property/property.iframe/property_application_index',
            ];
        }
        if(in_array(4,$content_id)) {
            $property_enterprise = [
                'title' => '企业微信配置',
                'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_enterprise_weixin',
                'v20_path' =>'/property/property.iframe/property_enterprise_weixin',
            ];
        } else {
            $property_enterprise = [
                'title' => '企业微信配置',
                'url' => $site_url . '/v20/public/platform/#/property/property.iframe/property_application_index',
                'v20_path' =>'/property/property.iframe/property_application_index',
            ];
        }

        $block[] = [
            'img' => $site_url .'/static/images/house/property/img_1_3.png',
            'num_img' => $site_url .'/static/images/house/property/03.png',
            'title' => '更多应用（可选)',
            'btn' => '',
            'children' => [
                $property_weixin,
                $property_weixinapp,
                $property_enterprise,
            ]
        ];
        $returnArr['block'] = $block;
        $returnArr['tip'] = '如果您已经完成配置，点击“完成配置”即可跳转到物业管理后台，以后登录将跳过此页面';
        $returnArr['btn'] = '完成配置';

        $where_property_guide = [];
        $where_property_guide[] = ['property_id','=', $property_id];
        $db_house_property_guide = new HousePropertyGuide();
        $property_guide = $db_house_property_guide->getOne($where_property_guide);
        if (!empty($property_guide)) {
            $property_guide = $property_guide->toArray();
        }
        if (empty($property_guide)) {
            $data = [
                'property_id' => $property_id,
                'last_time' => time()
            ];
            $db_house_property_guide->add($data);
        }
        return $returnArr;
    }

    /**
     * Notes: 完成引导
     * @param $property_id
     * @param array $param
     * @return array
     * @author: wanzy
     * @date_time: 2021/5/15 10:37
     */
    public function completePropertyGuide($property_id,$param=[]) {
        $where_property_guide = [];
        $where_property_guide[] = ['property_id','=', $property_id];
        $db_house_property_guide = new HousePropertyGuide();
        $property_guide = $db_house_property_guide->getOne($where_property_guide);
        if (!empty($property_guide)) {
            $property_guide = $property_guide->toArray();
        }
        if (empty($property_guide)) {
            $data = [
                'property_id' => $property_id,
                'last_time' => time()
            ];
            if (isset($param['login_role']) && $param['login_role']) {
                $data['login_role'] = $param['login_role'];
            }
            if (isset($param['login_userId']) && $param['login_userId']) {
                $data['login_userId'] = $param['login_userId'];
            }
            $data['complete_config'] = 1;
            $data['complete_time'] = time();
            $db_house_property_guide->add($data);
        } else {
            $data = [
                'last_time' => time()
            ];
            if (isset($param['login_role']) && $param['login_role']) {
                $data['login_role'] = $param['login_role'];
            }
            if (isset($param['login_userId']) && $param['login_userId']) {
                $data['login_userId'] = $param['login_userId'];
            }
            $data['complete_config'] = 1;
            $data['complete_time'] = time();
            $where_save = [];
            $where_save[] = ['property_id','=', $property_id];
            $db_house_property_guide->updateThis($where_save,$data);
        }
        return $data;
    }
}