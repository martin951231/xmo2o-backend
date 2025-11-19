<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/8/6 16:16
 */

namespace app\community\model\service;

use app\community\model\service\PackageOrderService;
use app\community\model\db\PrivilegePackage;
use app\community\model\db\PrivilegePackageBind;
use app\community\model\db\PrivilegePackageContent;
use app\community\model\db\PackageOrder;
use app\community\model\service\HouseNewPorpertyService;

class PrivilegePackageService
{

    //统计id 参考 （未使用）注释文字 后边的数组 对应的应用id=>application_id
    public $application_to_id = [
        1 => [],//商家管理
        2 => [],//公众号
        3 => [],//小程序管理
        4 => [554],//企业微信
        5 => [65, 28, 32, 36, 60, 66, 71, 83, 85],//收费管理 33
        6 => [226],//蓝牙门禁 4
        7 => [285, 397, 398],//人脸识别门禁  398=>5 (注：特殊285是主类,397/398子类)
        8 => [286],//人脸识别摄像机 6
        9 => [299],//智能快递柜  7
        10 => [416],//智慧停车场 30
        11 => [450],//智慧充电桩 31
        12 => [429],//在线文件管理 3
        13 => [289],//抄表录入 8
        14 => [419],//移动抄表 9
        15 => [421],//快递管理 14
        16 => [219],//在线报修 16
        17 => [279],//废品回收 17
        18 => [222],//水电煤上报 18
        19 => [224],//投诉建议 19
        20 => [426],//智慧二维码 23
        21 => [216],//访客登记 28
        22 => [431],//物料管理 29
        23 => [427],//客服IM 1
        24 => [428],//投票系统 2
        25 => [298],//短信群发 13
        26 => [41],//押金管理 12
        27 => [451],//电子发票 32
        28 => [423],//新冠防护登记 20
        29 => [424],//新冠健康打卡 21
        30 => [425],//新冠返程登记 22
        31 => [140],//团购列表 24
        32 => [143],//餐饮列表 25
        33 => [146],//预约列表 26
        34 => [150],//外卖列表 27
    ];
    //应用id 对应不上的特殊处理
    public $special_application_id = [7 => 285, 4 => 554];
    //智能硬件 功能套餐对应功能应用
    public $smart_content = [6, 7, 8, 9, 10, 11];//智能硬件 对应套餐应用id
    public $smart_id = [4, 5, 6, 7, 30, 31];//智能硬件 对应功能库应用id （注：下对应上 参考）

    public $my_application_content = [12, 13, 14, 15, 16, 18, 19, 20, 21, 23, 24, 26, 27, 25, 28, 29, 30];//我的应用 对应套餐应用id
    public $my_application_id = [3, 8, 9, 14, 16, 18, 19, 23, 28, 1, 2, 13, 12, 32, 20, 21, 22];//我的应用 对应功能库应用id （注：下对应上 参考）
    public $meter_reading = [8, 9];//抄表管理 对应功能库应用id

    /**
     * Notes: 添加套餐
     * @param array $data
     * @param array $bind_data
     * @param int $package_id 编辑时候的传值id
     * @return mixed
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2020/8/6 17:11
     */
    public function addPrivilegePackage($data, $bind_data = [], $package_id = 0)
    {
        $db_privilege_package = new PrivilegePackage();
        if ($package_id > 0) {
            $set_where = [];
            $set_where[] = ['package_id', '=', $package_id];
            $find_one = $db_privilege_package->getOne($set_where, 'package_id');
            if (!$find_one) {
                throw new \think\Exception("编辑对象不存在或者已经被删除");
            }
            //修改功能套餐
            if ($bind_data && count($bind_data) > 0) {
                //增减套餐功能应用
                $servicePackageOrder = new PackageOrderService();
                //增加功能套，购买过次套餐的物业都增加功能应用
                $res = $servicePackageOrder->increasePackageContent($package_id, $bind_data);
            }
            $data['last_time'] = time();
            $set_id = $db_privilege_package->updateThis($set_where, $data);
            if (!$set_id) {
                throw new \think\Exception("编辑失败");
            }
        } else {
            $where[] = ['package_title', '=', $data['package_title']];
            $where[] = ['status', '<>', 4];
            $find_one = $db_privilege_package->getOne($where, 'package_id');
            if ($find_one) {
                throw new \think\Exception("此套餐已存在");
            }
            $data['add_time'] = time();
            $data['last_time'] = 0;
            $package_id = $db_privilege_package->getAddId($data);
        }
        if (!$package_id) {
            throw new \think\Exception("添加失败");
        }

        if (is_array($bind_data)) {
            $db_privilege_package_content = new PrivilegePackageContent();
            $where = [];
            $where[] = ['content_id', 'in', $bind_data];
            $info = $db_privilege_package_content->getSome($where);

            if ($info) {
                $add_all = [];
                $now_time = time();
                $db_privilege_package_bind = new PrivilegePackageBind();
                // 先删除之前的绑定数据
                $del_where = [];
                $del_where[] = ['package_id', '=', $package_id];
                $db_privilege_package_bind->delInfo($del_where);
                foreach ($info as $val) {
                    $item = [];
                    if ($val) {
                        $item['content_id'] = $val['content_id'];
                        $item['package_id'] = $package_id;
                        $item['add_time'] = $now_time;
                        $add_all[] = $item;
                    }
                }
                if ($add_all && count($add_all) > 0) {
                    $add = $db_privilege_package_bind->addAll($add_all);
                    if (!$add) {
                        throw new \think\Exception("绑定套餐工功能失败,请编辑重新绑定");
                    }
                }
            }
//            else {
//                throw new \think\Exception("绑定套餐工功能失败,请编辑重新绑定");
//            }
        }
        return $data;
    }

    /**
     * Notes: 套餐详情
     * @param $package_id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/8/11 14:14
     */
    public function detailPrivilegePackage($package_id)
    {
        $db_privilege_package = new PrivilegePackage();
        $db_privilege_package_bind = new PrivilegePackageBind();
        $where = [];
        $where[] = ['package_id', '=', $package_id];
//        $packageInfo = $db_privilege_package->getOne($where, 'package_id');
        $packageInfo = $db_privilege_package->getOne($where);
        if (!$packageInfo) {
            throw new \think\Exception("查找的对象不存在或者已经被删除");
        }
        $where_content = [];
        $where_content[] = ['a.package_id', '=', $package_id];
        $field = 'a.content_id, a.package_id, b.name, b.type, b.desc';
        $packageContent = $db_privilege_package_bind->getBindInfo($where_content, $field);
        $property = [];//物业
        $community = [];//小区
        if ($packageContent) {
            foreach ($packageContent as $val) {
                //物业
                if ($val['type'] == 0) {
//                    $property[] = [
//                        'label'=>$val['name'],
//                        'value'=>$val['content_id'],
//                    ];
                    $property[] = $val['content_id'];
                }
                //小区
                if ($val['type'] == 1) {
//                    $community[] = [
//                        'label'=>$val['name'],
//                        'value'=>$val['content_id'],
//                    ];
                    $community[] = $val['content_id'];
                }
            }
        }
        $data['info'] = $packageInfo;
        $data['info']['property'] = $property;
        $data['info']['community'] = $community;
        return $data;
    }

    /**
     * Notes: 获取套餐 功能应用
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/11 14:24
     */
    public function getPrivilegePackageContent()
    {
        $dbPrivilegePackageContent = new PrivilegePackageContent();
        $where[] = ['is_default', '<>', 0];
        $contentList = $dbPrivilegePackageContent->getSome($where, true, 'content_sort desc,content_id asc');
        $property = []; //物业
        $community = []; //小区
        if ($contentList) {
            foreach ($contentList as $val) {
                //物业
                if ($val['type'] == 0) {
                    $property[] = [
                        'label' => $val['name'],
                        'value' => $val['content_id'],
                    ];
//                    $property[] = $val['name'];
                }
                //小区
                if ($val['type'] == 1) {
                    $community[] = [
                        'label' => $val['name'],
                        'value' => $val['content_id'],
                    ];
//                    $community[] = $val['name'];
                }
            }
        }
        $data['property'] = $property;
        $data['community'] = $community;
        return $data;
    }

    /**
     * Notes: 获取套餐功能列表
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/11 15:11
     */
    public function getPrivilegePackageList($where, $field, $page = 0, $limit = 0)
    {
        $db_privilege_package_bind = new PrivilegePackageBind();
        $db_privilege_package = new PrivilegePackage();
        $count = $db_privilege_package->getCount($where);
        $order = 'sort desc,package_id desc';
        $contentList = $db_privilege_package->getSome($where, $field, $order, $page, $limit);
        if ($contentList) {
            foreach ($contentList as $key => &$val) {
                $val['key'] = $val['package_id'];
                $val['count'] = $db_privilege_package_bind->getCount(['package_id' => $val['package_id']]);
            }
        }
        $data = [
            'list' => $contentList,
            'count' => $count ? $count : 0,
        ];
        return $data;
    }

    /**
     * Notes: 删除套餐
     * @param $where
     * @param $data
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/11 15:31
     */
    public function deletePrivilegePackage($where, $data)
    {
        $db_privilege_package = new PrivilegePackage();
        $res = $db_privilege_package->updateThis($where, $data);
        return $res;
    }

    /**
     * Notes: 查询物业最新套餐
     * @param $property_id
     * @param int $is_overdue
     * @return array|\think\Model|null
     * @author: wanzy
     * @date_time: 2020/12/12 9:42
     */
    public function getPropertyPackageOrder($property_id, $is_overdue = 0)
    {
        $dbPackageOrder = new PackageOrder();
        $trial_where = [];
        $trial_where[] = ['status', '=', 1];
        $trial_where[] = ['property_id', '=', $property_id];
        $trial_where[] = ['order_type', '<>', 3];
        if (!$is_overdue) {
            $trial_where[] = ['package_end_time', '>', time()];
        }
        $trial_field = 'order_id,package_id,property_id,package_try_end_time,package_period,package_end_time';
        $trialInfo = $dbPackageOrder->getFind($trial_where, $trial_field);
        if (!$trialInfo) {
            $trialInfo = [];
        } else {
            $trialInfo = $trialInfo->toArray();
            if (isset($trialInfo['package_try_end_time']) && $trialInfo['package_try_end_time']) {
                $trialInfo['package_try_end_time_txt'] = date('Y-m-d', $trialInfo['package_try_end_time']);
            }
            if (isset($trialInfo['package_end_time']) && $trialInfo['package_end_time']) {
                $trialInfo['package_end_time_txt'] = date('Y-m-d', $trialInfo['package_end_time']);
            }
        }
        return $trialInfo;
    }

    /**
     * Notes: 获取所有功能应用及对应功能
     * @param $where
     * @param $order
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/15 11:27
     */
    public function getPackageContent($where, $order, $property_id = 0, $is_overdue = 0)
    {
        $db_privilege_package = new PrivilegePackage();
        $db_privilege_package_bind = new PrivilegePackageBind();
        $dbPackageOrder = new PackageOrder();
        $field = 'package_id,package_title,package_try_days,package_price,room_num,package_limit_num,status,sort,details,txt_des';
        $content_field = 'a.content_id, a.package_id, b.name, b.type, b.desc';
        $data = $db_privilege_package->getSome($where, $field, $order);

        $bind = $db_privilege_package_bind->getBindInfo([], $content_field);
        $bind = $bind->toArray();
        //传有物业id 查询是否有功能套餐
        $try_end_time = 0;
        $package_end_time = 0;
        $trialInfo = [];
        if ($property_id) {
            $trial_where[] = ['status', '=', 1];
            $trial_where[] = ['property_id', '=', $property_id];
            $trial_where[] = ['order_type', '<>', 3];
            if (!$is_overdue) {
                $trial_where[] = ['package_end_time', '>', time()];
            }
            $trial_field = 'order_id,package_id,property_id,package_try_end_time,package_period,package_end_time';
            $trialInfo = $dbPackageOrder->getFind($trial_where, $trial_field);//
            if (!$trialInfo) {
                $trialInfo = [];
                $trial_wheres[] = ['status', '=', 0];
                $trial_wheres[] = ['property_id', '=', $property_id];
                $trial_wheres[] = ['order_type', '<>', 3];
                if (!$is_overdue) {
                    $trial_wheres[] = ['package_try_end_time', '>', time()];
                }
                $trialInfo = $dbPackageOrder->getFind($trial_wheres, $trial_field);
            }
            if ($trialInfo) {
                if ($trialInfo['package_try_end_time']) {
                    $try_end_time = date('Y-m-d', $trialInfo['package_try_end_time']);
                }
                if ($trialInfo['package_end_time']) {
                    $package_end_time = date('Y-m-d', $trialInfo['package_end_time']);
                }
                $packageInfo = $db_privilege_package->getOne(['package_id' => $trialInfo['package_id']], 'package_price');
            }
        }
        $bind_content = [];
        foreach ($bind as $val) {
            if ($val['type'] == 0) {
                $bind_content[$val['package_id']]['property'][] = $val;
            }
            if ($val['type'] == 1) {
                $bind_content[$val['package_id']]['community'][] = $val;
            }
        }
        foreach ($data as &$val) {
            //传有物业
            if ($property_id) {
                if ($trialInfo && $trialInfo['package_id'] == $val['package_id']) {
                    $val['pitch_type'] = 1;//续费
                    $val['package_try_end_time'] = $try_end_time ? $try_end_time : $package_end_time;
                    $val['order_id'] = $trialInfo['order_id'];
                    $package_end_period = floor($val['package_limit_num'] - intval($trialInfo['package_period']));
                    $val['package_end_period'] = $package_end_period > 0 ? $package_end_period : 0;
                } elseif ($trialInfo && $val['package_price'] <= $packageInfo['package_price']) {
                    //价格小于使用套餐的，不可操作
                    $val['pitch_type'] = 0;//不可购买续费等操作
                    $val['package_try_end_time'] = 0;
                    $val['package_end_period'] = 0;
                    $val['order_id'] = 0;
                } elseif ($trialInfo && $val['package_price'] == $packageInfo['package_price'] && $val['package_id'] <> $trialInfo['package_id']) {
                    //价格相同，但不是使用套餐
                    $val['pitch_type'] = 2;//升级
                    $val['package_try_end_time'] = 0;
                    $val['package_end_period'] = 0;
                    $val['order_id'] = $trialInfo['order_id'];
                } else {
                    //没有使用套餐，所有均为升级
                    $val['pitch_type'] = 2;//升级
                    $val['package_try_end_time'] = 0;
                    $val['package_end_period'] = 0;
                    $val['order_id'] = $trialInfo['order_id'];
                }
            }
            foreach ($bind_content as $k => $v) {
                if ($val['package_id'] == $k) {
                    $val['content'] = $v;
                }
            }
        }
        return $data;
    }


    /**
     * Notes:根据物业id 获取购买的套餐对应功能
     * @param $property_id
     * @return array|\think\Model|null
     * @author: wanzy 源自 PrivilegePackageModel->getPackageContent
     * @date_time: 2021/3/9 14:00
     */
    public function getByIdPackageContent($property_id)
    {
        $field = 'order_id,package_id,property_id,content_id';
        $dbPackageOrder = new PackageOrder();
        $where = [];
        $where[] = ['property_id', '=', $property_id];
        $where[] = ['status', '=', 1];
        $where[] = ['order_type', '<>', 3];
        $where[] = ['package_end_time', '>', time()];
        $package_order_data = $dbPackageOrder->getFind($where, $field, 'order_id DESC');
        if (!$package_order_data) {//查试用套餐
            $where = [];
            $where[] = ['property_id', '=', $property_id];
            $where[] = ['status', '=', 0];
            $where[] = ['pay_type', '=', 4];
            $where[] = ['package_try_end_time', '>', time()];

            $field = 'order_id,package_id,property_id,content_id';
            $package_order_data = $dbPackageOrder->getFind($where, $field, 'order_id DESC');
        }
        if (!empty($package_order_data)) {
            $package_order_data = $package_order_data->toArray();
            if ($package_order_data['content_id']) {
                $package_order_data['content'] = explode(',', $package_order_data['content_id']);
                $map = [];
                $map[] = ['content_id', 'in', $package_order_data['content']];
                $dbPrivilegePackageContent = new PrivilegePackageContent();
                $package_order_data['application_id'] = $dbPrivilegePackageContent->getColumn($map, 'application_id');
                unset($package_order_data['content_id']);
            }
        } else {
            $package_order_data = [];
        }
        return $package_order_data;
    }

    /**
     * Notes: 过滤小区后台导航/物业后台导航
     * @param $house_menu_list
     * @param $property_id
     * @return mixed
     * @author: wanzy 源自 源自 PrivilegePackageModel->filterNav
     * @date_time: 2021/3/9 14:00
     */
    public function filterNav($house_menu_list, $property_id)
    {
        $special_application_id = $this->special_application_id;
        $package = $this->getByIdPackageContent($property_id);
        if (isset($package['content']) && $package['content']) {
            $package_content_id = $package['content'];
        } else {
            $package_content_id = [];
        }
//      $package_content_id = [1, 2, 3, 4, 5,8,7,9,10,12];
        $package_application_id = isset($package['application_id']) && $package['application_id'] ? $package['application_id'] : [];
//        $package_application_id = [1, 2, 3, 5,33];
        //智能硬件
        $array_intersect_smart = array_intersect($this->smart_content, $package_content_id);
        //我的应用
        $array_intersect_my_app = array_intersect($this->my_application_id, $package_application_id);
        //抄表管理
        $array_intersect_meter_reading = array_intersect($this->meter_reading, $package_application_id);

        $new_str = '';
        foreach ($special_application_id as $key => $val) {
            //找出不在功能应用id 的导航 id
            if (!in_array($key, $package_content_id)) {
                $new_str .= ',' . $val;
            }
        }
        $new_arr = [];
        if ($new_str) {
            $new_str = trim($new_str, ',');
            //得出新的数组
            $new_arr = explode(',', $new_str);
        }
        foreach ($house_menu_list as $key => &$value) {
            if ($new_str) {
                foreach ($new_arr as $k => $v) {
                    //导航id 在数组里面的 清除
                    if (in_array($value['id'], $new_arr)) {
                        unset($house_menu_list[$key]);
                    }
                }
            }
            //导航关联应用id 且不在功能应用关联的应用id 的清除
            if ($value['application_id'] && !in_array($value['application_id'], $package_application_id)) {
                unset($house_menu_list[$key]);
            }
            //如果功能应用库里面没有功能 则《功能应用库》导航不显示
            if ((!$package_application_id || count($package_application_id) <= 0) && $value['id'] == 430) {
                unset($house_menu_list[$key]);
            }
            //如果功能应用库里面没有功能或者没有智能硬件的对应的应用 则《智能硬件》导航不显示
            if ((!$array_intersect_smart || count($array_intersect_smart) <= 0) && $value['id'] == 284) {
                unset($house_menu_list[$key]);
            }
            //如果抄表管理里面没有抄表应用 则《我的应用》里面《抄表管理》不显示
            if ((!$array_intersect_meter_reading || count($array_intersect_meter_reading) <= 0) && $value['id'] == 417) {
                unset($house_menu_list[$key]);
            }
            //如果功能应用库里面没有功能或者没有我的应用对应的应用 则《我的应用》 导航不显示
            if ((!$array_intersect_my_app || count($array_intersect_my_app) <= 0) && $value['id'] == 194) {
                unset($house_menu_list[$key]);
            }
        }
        $data['house_menu_list'] = $house_menu_list;
//        $data['package_application_id'] = $package_application_id;
        return $data;
    }

    /**
     * Notes: 根据功能套餐购买功能应用 处理小区后台权限管理
     * @param $arr
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/28 17:37
     */
    public function disposeAuth($arr, $property_id)
    {
        $package_order_info = $this->getByIdPackageContent($property_id);
        $application_id = $package_order_info['application_id'];
        $count = count($application_id);
        $application_id[$count] = 0;
        $id_arr = [];
        $th_id = [];
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($property_id);
        $oldVersionChargeMenus = [65, 417];
        foreach ($arr as $key => &$val) {
            if($val['fid']==65 && $val['id']==60){
                $val['fid']=2002;  //物业余额
            }
            if($val['fid']==60){
                unset($arr[$key]);
            }
            if ($val['fid'] && $val['type'] == 1 && $val['application_id'] && !in_array($val['application_id'], $application_id)) {
                $id_arr[] = [
                    'id' => $val['id'],
                    'fid' => $val['fid'],
                ];
                unset($arr[$key]);
            }
            if ($val['fid'] && $val['type'] == 2 && $val['application_id'] && !in_array($val['application_id'], $application_id)) {
                $th_id[] = [
                    'id' => $val['id'],
                    'fid' => $val['fid'],
                ];
                unset($arr[$key]);
            }

            if ($takeEffectTimeJudge && (in_array($val['id'], $oldVersionChargeMenus) || in_array($val['fid'], $oldVersionChargeMenus))) {
                unset($arr[$key]);
            }
        }
        if ($th_id) {
            $id_arr = array_merge($id_arr, $th_id);
        }
        if ($id_arr) {
            $id = array_column($id_arr, 'id');
            $fid = array_column($id_arr, 'fid');
            $fid = array_unique($fid);
            foreach ($arr as $key => &$val) {
                if (in_array($val['fid'], $id)) {
                    unset($arr[$key]);
                }
                if (in_array($val['id'], $fid) && $val['application_txt']) {
                    if ($val['application_txt']) {
                        $application_txt = explode(',', $val['application_txt']);
                        $array_diff = array_diff($application_txt, $application_id);
                        $array_diff_txt = array_diff($application_txt, $array_diff);
                        if ($array_diff && !$array_diff_txt) {
                            unset($arr[$key]);
                        }
                    }
                }
            }
        }
        $data = array();
        $data['list'] = $arr;
        $data['application_id'] = $application_id;
        return $data;
    }
}