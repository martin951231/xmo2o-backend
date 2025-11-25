<?php
/**
 * 套餐相关模型
 * Class PrivilegePackageModel
 * @author weili
 * @datetime 2020/8/25
 */

class PrivilegePackageModel extends Model
{
    public function application_to_url() {
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pagePath' => 'pages/village/my/',
        ];
        $pagesMyUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        return [
            //收费管理
            5 => [
                $pagesMyUrl.'payMentlist',//小区缴费
                $pagesMyUrl.'independentPayement', //自主缴费
                $pagesMyUrl.'lifepayment',//预存物业费
            ],
            //人脸识别门禁
            7 => [
                $pagesMyUrl.'faceAccessControl',//人脸门禁
            ],
            //快递管理
            15 => [
                $pagesMyUrl.'expressManagement',//快递代收
                $pagesMyUrl.'expressManagement?current=1',//快递代发
            ],
            //在线报修/packapp/plat/
            16 => [
                $pagesMyUrl.'waterReportlist?type=1',//在线报修
            ],
            //水电煤上报
            18 => [
                $pagesMyUrl.'waterReportlist?type=2'
            ],
            //投诉建议
            19 => [
                $pagesMyUrl.'waterReportlist?type=3'
            ],
            //访客登记
            21 => [
                $pagesMyUrl.'visitorRegistration'
            ],
            //投票系统
            24 => [
                'pages/vote/index',//投票
            ],
            //预约列表
            33 => [
                'wap.php?g=Wap&c=House&a=village_appointlist',//推荐预约
            ],
        ];
    }

    //统计id 参考 （未使用）注释文字 后边的数组 对应的应用id=>application_id
    public $application_to_id=[
        1=>[],//商家管理
        2=>[],//公众号
        3=>[],//小程序管理
        4=>[554],//企业微信
        5=>[65,28,32,36,60,66,71,83,85],//收费管理 33
        6=>[226],//蓝牙门禁 4
        7=>[285,397,398],//人脸识别门禁  398=>5 (注：特殊285是主类,397/398子类)
        8=>[286],//人脸识别摄像机 6
        9=>[299],//智能快递柜  7
        10=>[416],//智慧停车场 30
        11=>[450],//智慧充电桩 31
        12=>[429],//在线文件管理 3
        13=>[289],//抄表录入 8
        14=>[419],//移动抄表 9
        15=>[421],//快递管理 14
        16=>[219],//在线报修 16
        17=>[279],//废品回收 17
        18=>[222],//水电煤上报 18
        19=>[224],//投诉建议 19
        20=>[426],//智慧二维码 23
        21=>[216],//访客登记 28
        22=>[431],//物料管理 29
        23=>[427],//客服IM 1
        24=>[428],//投票系统 2
        25=>[298],//短信群发 13
        26=>[41],//押金管理 12
        27=>[451],//电子发票 32
        28=>[423],//新冠防护登记 20
        29=>[424],//新冠健康打卡 21
        30=>[425],//新冠返程登记 22
        31=>[140],//团购列表 24
        32=>[143],//餐饮列表 25
        33=>[146],//预约列表 26
        34=>[150],//外卖列表 27
    ];
    //应用id 对应不上的特殊处理
    public $special_application_id = [7=>285,4=>554];
    //智能硬件 功能套餐对应功能应用
    public $smart_content = [6,7,8,9,10,11];//智能硬件 对应套餐应用id
    public $smart_id      = [4,5,6,7,30,31];//智能硬件 对应功能库应用id （注：下对应上 参考）

    public $my_application_content = [12,13,14,15,16,18,19,20,21,23,24,26,27,25,28,29,30];//我的应用 对应套餐应用id
    public $my_application_id      = [3, 8, 9, 14,16,18,19,23,28, 1, 2,13,12,32,20,21,22];//我的应用 对应功能库应用id （注：下对应上 参考）
    public $meter_reading = [8,9];//抄表管理 对应功能库应用id
    /**
     * Notes: 根据物业id 获取订单套餐对应功能 (注：查最新功能套餐对应id 暂弃用)
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/25 10:05
     */
    public function getPackageContents($property_id)
    {
        $where['property_id'] = $property_id;
        $where['status'] = 1;
        $where['package_end_time'] = ['gt',time()];
        $field = 'order_id,package_id,property_id';
        $package_order_data = M('Package_order')->order('order_id DESC')->where($where)->field($field)->find();
        if(!$package_order_data){//查试用套餐
            $where = [];
            $where['property_id'] = $property_id;
            $where['status'] = 0;
            $where['package_try_end_time'] = ['gt',time()];
            $field = 'order_id,package_id,property_id';
            $package_order_data = M('Package_order')->order('order_id DESC')->where($where)->field($field)->find();
        }
        if($package_order_data){
            $map['package_id'] = $package_order_data['package_id'];
            $content = 'content_id';
            $package_order_data['content'] = D('Privilege_package_bind')->where($map)->getField($content,true);

            $content_where['b.package_id'] = $package_order_data['package_id'];
            $package_order_data['application_id'] = D('Privilege_package_bind')
                ->join('as b LEFT JOIN '.C('DB_PREFIX').'privilege_package_content as c ON b.content_id=c.content_id')
                ->where($content_where)
                ->getField('application_id',true);
        }
        return $package_order_data;
    }

    /**
     * Notes:根据物业id 获取购买的套餐对应功能
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/27 10:03
     */
    public function getPackageContent($property_id)
    {
        $where['property_id'] = $property_id;
        $where['status'] = 1;
        $where['order_type'] = ['neq',3];
        $where['package_end_time'] = ['gt',time()];
        $field = 'order_id,package_id,property_id,content_id';
        $package_order_data = M('Package_order')->where($where)->field($field)->order('order_id desc')->find();
        if(!$package_order_data){//查试用套餐
            $where = [];
            $where['property_id'] = $property_id;
            $where['status'] = 0;
            $where['pay_type'] = 4;
            $where['package_try_end_time'] = ['gt',time()];
            $field = 'order_id,package_id,property_id,content_id';
            $package_order_data = M('Package_order')->order('order_id DESC')->where($where)->field($field)->find();
        }
        if($package_order_data){
            if($package_order_data['content_id']) {
                $package_order_data['content'] = explode(',', $package_order_data['content_id']);
                $map['content_id'] = ['in', $package_order_data['content']];

                $package_order_data['application_id'] = D('privilege_package_content')->where($map)->getField('application_id', true);
                unset($package_order_data['content_id']);
            }
        }
        return $package_order_data;
    }
    /**
     * Notes: 过滤小区后台导航/物业后台导航
     * @param $house_menu_list
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/26 11:58
     */
    public function filterNav($house_menu_list,$property_id){
        $special_application_id = $this->special_application_id;
        $package = $this->getPackageContent($property_id);
        $package_content_id = $package['content'];
//      $package_content_id = [1, 2, 3, 4, 5,8,7,9,10,12];
        $package_application_id = $package['application_id'];
//        $package_application_id = [1, 2, 3, 5,33];
        //智能硬件
        $array_intersect_smart = array_intersect($this->smart_content,$package_content_id);
        //我的应用
        $array_intersect_my_app = array_intersect($this->my_application_id,$package_application_id);
        //抄表管理
        $array_intersect_meter_reading = array_intersect($this->meter_reading,$package_application_id);

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
            if( (!$package_application_id || count($package_application_id) <= 0) && $value['id'] == 430){
                unset($house_menu_list[$key]);
            }
            //如果功能应用库里面没有功能或者没有智能硬件的对应的应用 则《智能硬件》导航不显示
            if( (!$array_intersect_smart || count($array_intersect_smart)<=0) && $value['id'] == 284){
                unset($house_menu_list[$key]);
            }
            //如果抄表管理里面没有抄表应用 则《我的应用》里面《抄表管理》不显示
            if( (!$array_intersect_meter_reading || count($array_intersect_meter_reading)<=0) && $value['id'] == 417){
                unset($house_menu_list[$key]);
            }
            //如果功能应用库里面没有功能或者没有我的应用对应的应用 则《我的应用》 导航不显示
            if( (!$array_intersect_my_app || count($array_intersect_my_app)<=0) && $value['id'] == 194){
                unset($house_menu_list[$key]);
            }
        }
        $data['house_menu_list'] = $house_menu_list;
//        $data['package_application_id'] = $package_application_id;
        return $data;
    }

    /**
     * Notes: 过滤前端首页栏目和便民里面的应用
     * @param $list
     * @param $village_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/26 16:26
     */
    public function getApplication($list,$village_id)
    {
        $application_to_urls = $this->application_to_url();
        $property_id = D('House_village')->where(array('village_id'=>$village_id))->getField('property_id');
        $package = $this->getPackageContent($property_id);
        $package_content_id = $package['content'];
//        $package_content_id = [1,5,20,33,15,16,18,19];
        $new_str = '';
        foreach ($application_to_urls as $key=>$val)
        {
            //找出套餐不包含的应用url ,拼接
            if(!in_array($key,$package_content_id)){
                foreach ($val as $v){
                    $new_str .= ','.$v;
                }
            }
        }
        if($new_str) {
            $new_str = trim($new_str, ',');
            //得出新的数组
            $new_arr = explode(',', $new_str);
            //去除不包含在套餐里面的功能应用
            foreach ($list as $key => &$value) {
                foreach ($new_arr as $k => $vs) {
                    if (array_key_exists('cat_url',$value) && strpos($value['cat_url'], $vs) !== false) {
                        $new_list[] = $value;
                        unset($list[$key]);
                    }elseif (array_key_exists('url',$value) && strpos($value['url'], $vs) !== false){
                        $new_list[] = $value;
                        unset($list[$key]);
                    }
                }
            }
        }
        return $list;
    }

    /**
     * Notes: 根据功能套餐购买功能应用 处理小区后台权限管理
     * @param $arr
     * @param $property_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/9/28 17:37
     */
    public function disposeAuth($arr,$property_id)
    {
        $package_order_info = $this->getPackageContent($property_id);
        $application_id = $package_order_info['application_id'];
        $count = count($application_id);
        $application_id[$count] = 0;
        $id_arr = [];
        $th_id = [];
        foreach ($arr as $key=>&$val)
        {
            if($val['fid'] && $val['type'] == 1 && $val['application_id'] && !in_array($val['application_id'],$application_id))
            {
                $id_arr[] = [
                    'id'=>$val['id'],
                    'fid'=>$val['fid'],
                ];
                unset($arr[$key]);
            }
            if($val['fid'] && $val['type'] == 2 && $val['application_id'] && !in_array($val['application_id'],$application_id))
            {
                $th_id[] = [
                    'id'=>$val['id'],
                    'fid'=>$val['fid'],
                ];
                unset($arr[$key]);
            }
        }
        if($th_id)
        {
            $id_arr = array_merge($id_arr,$th_id);
        }
        if($id_arr) {
            $id = array_column($id_arr, 'id');
            $fid = array_column($id_arr, 'fid');
            $fid = array_unique($fid);
            foreach ($arr as $key => &$val) {
                if (in_array($val['fid'], $id)) {
                    unset($arr[$key]);
                }
                if (in_array($val['id'], $fid) && $val['application_txt']) {
                    if($val['application_txt']){
                        $application_txt = explode(',',$val['application_txt']);
                        $array_diff = array_diff($application_txt,$application_id);
                        $array_diff_txt = array_diff($application_txt,$array_diff);
                        if($array_diff && !$array_diff_txt){
                            unset($arr[$key]);
                        }
                    }
                }
            }
        }
        $data['list'] = $arr;
        $data['application_id'] = $application_id;
        return $data;
    }
}