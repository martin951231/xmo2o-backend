<?php


namespace app\community\controller\village_api;
use app\community\controller\manage_api\BaseController;
use app\community\model\db\HouseNewChargeTime;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseVillageConfig;
use app\community\model\service\FaceDeviceService;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseNewChargeService;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageConfigService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseVillageRepairListService;
use app\community\model\service\BbsService;
use app\community\model\service\HouseVillageOrderService;
use app\community\model\service\PackageOrderService;
use app\community\model\service\TaskReleaseService;
use app\community\model\service\WisdowQrcodeService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseUserLogService;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\ParkService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\ApplicationService;
use app\community\model\service\HouseNewPorpertyService;
use think\facade\Cache;

class DataStatisticsController extends CommunityBaseController
{

    /**
     * Notes: 获取配置
     * @return \json
     * @author: wanzy
     * @date_time: 2020/9/2 21:42
     */
    public function config() {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $arr = [];
        $arr['config_site_name'] = cfg('config_site_name');
        $service_house_village = new HouseVillageService();
        // 小区信息
        $now_village = $service_house_village->getHouseVillage($village_id,'village_id,village_name,village_logo');
        $arr['village_info'] = $now_village;
        // 系统后台LOGO
        if (cfg('system_admin_logo')) {
            $system_admin_logo = cfg('system_admin_logo');
            $system_admin_logo = replace_file_domain($system_admin_logo);
        } else {
            $system_admin_logo = cfg('site_url') . "/tpl/System/Static/images/pigcms_logo.png";
        }
        if($now_village['village_logo']){
            $system_admin_logo = replace_file_domain($now_village['village_logo']);
        }
        $arr['system_admin_logo'] = $system_admin_logo;
        return api_output(0,$arr);
    }

    /**
     * 获取房间和住户的数量
     * @author lijie
     * @date_time 2020/08/03 14:02
     * @return \json
     */
    public function villagePopulation()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $house_village_user_bind = new HouseVillageUserBindService();
        //总人口
        $where_all[] = ['status','=',1];
        $where_all[] = ['type','in','0,1,2,3'];
        $where_all[] = ['village_id','=',$village_id];
        $where_all[] = ['vacancy_id','>',0];
        $where_all[] = ['uid|phone|name', 'not in', [0,'']];
        $all_count = $house_village_user_bind->getUserCount($where_all);
        //业主数量
        $where_owner[] = ['status','=',1];
        $where_owner[] = ['type','in','0,1,3'];
        //$where_owner[] = ['parent_id','=',0];
        $where_owner[] = ['village_id','=',$village_id];
        $where_owner[] = ['vacancy_id','>',0];
        $where_owner[] = ['uid|phone|name', 'not in', [0,'']];
        $owner_count = $house_village_user_bind->getUserCount($where_owner);
        //租客数量
        $where_tenant[] = ['status','=',1];
        $where_tenant[] = ['type','=','2'];
        $where_tenant[] = ['village_id','=',$village_id];
        $where_tenant[] = ['vacancy_id','>',0];
        $tenant_count = $house_village_user_bind->getUserCount($where_tenant);

        $house_village_user_vacancy = new HouseVillageUserVacancyService();
        $room_count = $house_village_user_vacancy->getRoomCount([['is_del','=',0],['village_id','=',$village_id],['status','in','0,1,3']]);//总房间
        $type_count = $house_village_user_vacancy->getRoomCount([['is_del','=',0],['village_id','=',$village_id],['status','in','0,1,3']],'house_type');
        $office_count = 0;// 办公
        $shop_count = 0; //商品
        $home_count = 0; //住宅
        if($type_count){
            foreach ($type_count as $value){
                if($value['house_type'] == 3)
                    $office_count += $value['count'];
                if($value['house_type'] == 2)
                    $shop_count += $value['count'];
                else
                    $home_count += $value['count'];
            }
        }
        $no_use = $house_village_user_vacancy->getRoomCount(['is_del'=>0,'status'=>1,'village_id'=>$village_id]);//未入住
        $data['all_population'] = $all_count;
        $data['owner_count'] = $owner_count;
        $data['tenant_count'] = $tenant_count;
        $data['all_room'] = $room_count;
        $data['office_count'] = $office_count;
        $data['shop_count'] = $shop_count;
        $data['home_count'] = $home_count;
        $data['no_use'] = $no_use;
        return api_output(0,$data, '获取成功');
    }

    /**
     * 获取工单处理数据
     * @author lijie
     * @date_time 2020/08/03 14:53
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function workOrder_1()
    {
        
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $data = [];
        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);
        $hardware_intersect = array_intersect([16,18,19,40],$package_content);
        if($hardware_intersect && count($hardware_intersect)>0) {
            $houseNewRepairService=new HouseNewRepairService();
            $whereArr=[['o.village_id','=',$village_id]];
            $all_count=$houseNewRepairService->getOrderCount($whereArr);
            $whereArr=[['o.village_id','=',$village_id],['o.event_status','in',[20,30]]];
            $processing_count =$houseNewRepairService->getOrderCount($whereArr);

            $whereArr=[['o.village_id','=',$village_id],['o.event_status','in',[40,60,70]]];
            $processed_count=$houseNewRepairService->getOrderCount($whereArr);

            $whereArr=[['o.village_id','=',$village_id],['o.event_status','in',[10]]];
            $untreated_count=$houseNewRepairService->getOrderCount($whereArr);
            $favorable_comments_count=0;
            /*
            $start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date("Y")));
            $house_village_repair = new HouseVillageRepairListService();
            $all_count = $house_village_repair->getRepairCount([['time', '>=', strtotime($start_time)], ['village_id', '=', $village_id]]);//总共单数
            $processing_count = $house_village_repair->getRepairCount([['time', '>=', strtotime($start_time)], ['status', 'in', '1,2'], ['village_id', '=', $village_id]]);//处理中数
            $untreated_count = $house_village_repair->getRepairCount([['time', '>=', strtotime($start_time)], ['status', '=', 0], ['village_id', '=', $village_id]]);//未处理数
            $processed_count = $house_village_repair->getRepairCount([['time', '>=', strtotime($start_time)], ['status', 'in', '3,4'], ['village_id', '=', $village_id]]);//已处理数
            $favorable_comments_count = $house_village_repair->getRepairCount([['time', '>=', strtotime($start_time)], ['score', '>=', 4], ['village_id', '=', $village_id]]);//好评数
            */
             $favorable_comments_rate = $all_count ? intval(round_number(($favorable_comments_count/$all_count),2 )*100)  : 100;
             
            $data['all_count'] = $all_count;
            $data['favorable_comments_rate'] = $favorable_comments_rate;
            $data['favorable_comments_count'] = $favorable_comments_count;
            $data['processing_count'] = $processing_count;
            $data['untreated_count'] = $untreated_count;
            $data['processed_count'] = $processed_count;
        }else{
            $data = '';
        }
        return api_output(0,$data,'获取成功');
    }


    /**
     * 获取工单处理数据
     * @author lijie
     * @date_time 2020/08/03 14:53
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function workOrder()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $house_new_repair=new HouseNewRepairService();
        $res=$house_new_repair->getWorkorder($village_id);
        return api_output(0,$res,'获取成功');
    }
    
    /**
     * 事项列表
     * @author lijie
     * @date_time 2020/08/03 16:11
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function itemsLists()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $house_village_user_unbind = new HouseVillageService();
        $house_village_user_bind = new HouseVillageUserBindService();
        $where_owner[] = ['status','=',2];
        $where_owner[] = ['type','in','0,3'];
        $where_owner[] = ['village_id','=',$village_id];
        $under_review_owner_count = $house_village_user_bind->getUserCount($where_owner);//待审核业主数量
        $under_review_family_count = $house_village_user_bind->getUserCount(['type'=>1,'status'=>2,'village_id'=>$village_id]);//待审核家属数量
        $under_review_tenant_count = $house_village_user_bind->getUserCount(['type'=>2,'status'=>2,'village_id'=>$village_id]);//待审核租客数量
        $unbind_count = $house_village_user_unbind->get_village_unbind_user_num(['status'=>1,'village_id'=>$village_id]);//待申请解绑
        $house_village_repair = new HouseVillageRepairListService();
        //$start_time = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date("Y")));
        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);
        if(in_array(16,$package_content)) {
            $count1 = $house_village_repair->getRepairCount([['status', '=', 0], ['village_id', '=', $village_id], ['type', '=', 1]]);
        }
        //套餐过滤
        if(in_array(19,$package_content)) {
            $count2 = $house_village_repair->getRepairCount([['status', '=', 0], ['village_id', '=', $village_id], ['type', '=', 2]]);
        }
        //套餐过滤
        if(in_array(18,$package_content)) {
            $count3 = $house_village_repair->getRepairCount([['status', '=', 0], ['village_id', '=', $village_id], ['type', '=', 3]]);
        }
        $count1 = isset($count1)?$count1:0;
        $count2 = isset($count2)?$count2:0;
        $count3 = isset($count3)?$count3:0;
        $bbs_article = new BbsService();
        $count = $bbs_article->getArticleCount(['a.aricle_status'=>2,'b.third_id'=>$village_id]);
//        $data[0]['name'] = '待审核业主数';
//        $data[0]['num'] = $under_review_owner_count;
//        $data[1]['name'] = '待审核家属数';
//        $data[1]['num'] = $under_review_family_count;
//        $data[2]['name'] = '待审核租客数';
//        $data[2]['num'] = $under_review_tenant_count;
//        $data[3]['name'] = '待申请解绑';
//        $data[3]['num'] = $unbind_count;
//
//        $data[4]['name'] = '待审核文章';
//        $data[4]['num'] = $count;
//        $data[5]['name'] = '待处理报修';
//        $data[5]['num'] = $count1;
//        $data[6]['name'] = '待处理投诉建议';
//        $data[6]['num'] =$count3;
//        $data[7]['name'] = '待处理水电煤上报';
//        $data[7]['num'] = $count2;

        $data = [
            ['name'=>'待审核业主数','num'=>$under_review_owner_count],
            ['name'=>'待审核家属数','num'=>$under_review_family_count],
            ['name'=>'待审核租客数','num'=>$under_review_tenant_count],
            ['name'=>'待申请解绑','num'=>$unbind_count],
            ['name'=>'待审核文章','num'=>$count],
        ];
        /*
        //套餐过滤
        if(in_array(16,$package_content)) {
            $data[] = ['name' => '待处理报修', 'num' => $count1];
        }
        //套餐过滤
        if(in_array(19,$package_content)) {
            $data[] = ['name' => '待处理投诉建议', 'num' => $count3];
        }
        //套餐过滤
        if(in_array(18,$package_content)) {
            $data[] = ['name' => '待处理水电煤上报', 'num' => $count2];
        }
        */
        $len = count($data);
        //设置一个空数组 用来接收冒出来的泡
        //该层循环控制 需要冒泡的轮数
        for ($i = 1; $i < $len; $i++) { //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for ($k = 0; $k < $len - $i; $k++) {
                if ($data[$k]['num'] < $data[$k + 1]['num']) {
                    $tmp = $data[$k + 1];
                    $data[$k + 1] = $data[$k];
                    $data[$k] = $tmp;
                }
            }
        }
        $return['front'] = array_slice($data,0,4);
        $return['end'] = array_slice($data,4,4);
        return api_output(0,$return,'获取成功');
    }

    /**
     * 大数据展示中间菜单
     * @author lijie
     * @date_time 2020/08/04 9:40
     * @return \json
     */
    public function menuLists()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $data = array();
        //套餐
        $package_content = $this->getOrderPackage($village_id);
        $serviceHouseNewPorperty = new HouseNewPorpertyService();
        $takeEffectTimeJudge = $serviceHouseNewPorperty->getTakeEffectTimeJudge($property_id);
        $village_config_info = (new HouseVillageConfigService())->getConfig(['village_id'=>$village_id],'open_new_cashier');
        //新老版收银台显示判断
        $open_new_cashier=0;
        if (isset($village_config_info)){
            $open_new_cashier=$village_config_info['open_new_cashier'];
        }
        $judgeThirdProtocolDeviceConfig = (new FaceDeviceService())->judgeThirdProtocolDeviceConfig($village_id, $property_id);
        $site_url = cfg('site_url');
        for ($i = 0;$i < 8;$i++){
            $data[$i]['url'] = '';
            switch ($i){
                case 0:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/camera.png';
                    $data[$i]['name'] = '摄像头';
                    //套餐过滤
                    if(in_array(8,$package_content)) {
                        if (!$judgeThirdProtocolDeviceConfig) {
                            $data[$i]['url'] = $site_url. '/shequ.php?g=House&c=Face_door&a=camera_list';
                        } else {
                            $data[$i]['url'] = $site_url. '/v20/public/platform/#/user/village/videoPreview';
                        }
                    }
                    break;
                case 1:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/face.png';
                    $data[$i]['name'] = '人脸门禁';
                    //套餐过滤
                    if(in_array(7,$package_content)) {
                        $data[$i]['url'] = $site_url. '/shequ.php?g=House&c=Face_door&a=door_list';
                    }
                    break;
                case 2:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/member.png';
                    $data[$i]['name'] = '网格员';
                    $data[$i]['url'] = $site_url.'/shequ.php?g=House&c=NewGridding&a=area_street_custom';
                    break;
                case 3:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/camera.png';
                    $data[$i]['name'] = '巡检';
                    $res = 0;
                    //套餐过滤
                    if(in_array(20,$package_content)) {
                        $app_bind = new ApplicationService();
                        $res = $app_bind->getAppBind(['application_id' => 23, 'use_id' => $village_id, 'status' => 0], '*');
                    }
                    if($res)
                        $data[$i]['url'] = $site_url . '/shequ.php?g=House&c=WisdomQrcode&a=cate_index';
                    else
                        $data[$i]['url'] = '';
                    break;
                case 4:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/parking.png';
                    $data[$i]['name'] = '智慧停车';
                    //套餐过滤
                    if(in_array(10,$package_content)) {
                        if ($takeEffectTimeJudge){
                            $data[$i]['url'] = $site_url. '/v20/public/platform/#/village/yardManagement/parkingLot';
                        }else{
                            $data[$i]['url'] = $site_url. '/shequ.php?g=House&c=Park&a=park_month_list';
                        }

                    }
                    break;
                case 5:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/shou.png';
                    $data[$i]['name'] = '收银台';
                    //套餐过滤
                    if (in_array(5,$package_content) && $takeEffectTimeJudge) {
                        if($open_new_cashier==1){
                            $data[$i]['url'] = $site_url. '/v20/public/platform/#/community/village/charge/cashier/cashierNewOrderList';
                        }else{
                            $data[$i]['url'] = $site_url. '/v20/public/platform/#/village/village.charge.cashier/cashierOrderList';
                        }
                    } elseif(in_array(5,$package_content)) {
                        $data[$i]['url'] = $site_url. '/shequ.php?g=House&c=Cashier&a=cashier';
                    }
                    break;
                case 6:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/app.png';
                    $data[$i]['name'] = '应用功能';
                    $data[$i]['url'] = $site_url.'/shequ.php?g=House&c=Application&a=index';
                    break;
                case 7:
                    $data[$i]['img'] = $site_url.'/v20/public/static/community/images/menu/more.png';
                    $data[$i]['name'] = '更多功能';
                    $data[$i]['url'] = '1';
                    break;
                default:
                    $data[$i]['img'] = '';
                    $data[$i]['name'] = '';
                    $data[$i]['url'] = '';
                    break;
            }
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 收费统计
     * @author lijie
     * @date_time 2020/08/04 13:33
     * @return \json
     */
    public function chargeStatistics()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        if(!$village_id || !$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $date = $this->get_weeks(time(),'Y-m-d');
        $data = array();
        $house_village_pay_order = new HouseVillageOrderService();
        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);


        //todo 【新版收费-小区物业统计停车收费】
        $dbHouseNewCashierService=new HouseNewCashierService();
        $result=$dbHouseNewCashierService->getChargeProjectType(2,$property_id,$village_id);
        $new_charge_status=$result['status'];
        $new_charge_data=$result['charge_data'];
        $new_charge_type=$result['charge_type'];

        if(in_array(5,$package_content)) {
            foreach ($date as $k => $v) {
                $where = array();
                $where1 = array();
                $where2 = array();
                $where3 = array();
                if($new_charge_status == 0){
                    //todo 小区旧版收费
                    $where[] = ['pay_time', 'between', [strtotime($v), strtotime($v) + (23 * 60 + 59) * 60 + 59]];
                    $where[] = ['paid', '=', 1];
                    $where[] = ['village_id', '=', $village_id];
                    $where[] = ['order_status', '=', 1];
                    $where1[] = ['order_type', '=', 'property'];
                    $where2[] = ['order_type', '=', 'park'];
                    $where3[] = ['order_type', 'in', 'custom_payment,custom,water,electric,gas'];
                    $property_price = $house_village_pay_order->getSumMoney($where, $where1, 'money');
                    $parking_price = $house_village_pay_order->getSumMoney($where, $where2, 'money');
                    $custom_price = $house_village_pay_order->getSumMoney($where, $where3, 'money');
                    $property_price = round_number($property_price,2);
                    $parking_price = round_number($parking_price,2);
                    $custom_price = round_number($custom_price,2);

                    $data['list'][$k]['property_price'] = $property_price;
                    $data['list'][$k]['parking_price'] = $parking_price;
                    $data['list'][$k]['custom_price'] = $custom_price;
                }else{
                    //todo 小区新版收费
                    if($new_charge_data){
                        $chart_type=[
                            0=>'property_price',
                            1=>'parking_price',
                            2=>'custom_price'
                        ];
                        $other_tmp=0;
                        foreach ($new_charge_data as $k2=>$v2){
                            $where=[];
                            $where[] = ['pay_time', 'between', [strtotime($v), strtotime($v) + (23 * 60 + 59) * 60 + 59]];
                            $where[] = ['village_id', '=', $village_id];
                            $where[] = ['is_paid', '=', 1];
                            if($v2['charge_param'] == 'other'  && isset($v2['charge_param_type']) && ($v2['charge_param_type']==1)){
                                $v2['charge_type']=is_array($v2['charge_type'])?$v2['charge_type']:array($v2['charge_type']);
                                $v2['charge_type'][]='other';
                                $where[] = ['order_type', 'in', $v2['charge_type']];
                            }elseif(is_array($v2['charge_type'])){
                                $where[] = ['order_type', 'in', $v2['charge_type']];
                            }else{
                                $where[] = ['order_type', '=', $v2['charge_type']];
                            }
                            if(isset($v2['project_id']) && !empty($v2['project_id']) && !is_array($v2['project_id'])){
                                $where[] = ['project_id', '=', $v2['project_id']];
                            }
                            /*
                            if($v2['charge_param'] == 'other'  && isset($v2['charge_param_type'])){
                                //todo 针对其他费用单独处理
                                $con = implode(',', $v2['project_id']);
                                $where[] = ['project_id', 'not in', $con];
                            }else{
                                $where[] = ['project_id', '=', $v2['project_id']];
                            }
                            */
                            $tmpMoney=get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where));
                            $tmpMoney=round_number($tmpMoney);
                            if($v2['charge_param'] == 'other'  && isset($v2['charge_param_type']) && ($v2['charge_param_type']==1)){
                                $tmpMoney=$tmpMoney-$other_tmp;
                                $tmpMoney=$tmpMoney>0 ?$tmpMoney:0;
                                $tmpMoney=round_number($tmpMoney);
                            }else if($v2['charge_param'] != 'other'  || !isset($v2['charge_param_type'])){
                                $other_tmp+=$tmpMoney;
                            }
                            $data['list'][$k][$chart_type[$k2]]=$tmpMoney;
                        }

                    }
                }
                $data['list'][$k]['time'] = strtotime($v);
            }
            $month_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
            $where = array();
            if($new_charge_status == 0){
                $where[] = ['pay_time', '>=', $month_start];
                $where[] = ['paid', '=', 1];
                $where[] = ['village_id', '=', $village_id];
                $where[] = ['order_status', '=', 1];
                $property_price = $house_village_pay_order->getSumMoney($where, [['order_type', '=', 'property']], 'money');
                $parking_price = $house_village_pay_order->getSumMoney($where, [['order_type', '=', 'park']], 'money');
                $custom_price = $house_village_pay_order->getSumMoney($where, [['order_type', 'in', 'custom_payment,custom,water,electric,gas']], 'money');
                $data['total_money'] = $property_price + $parking_price + $custom_price;
                $data['total_money'] = round_number($data['total_money'],2);
                $data['type'] = [['name' => '物业收入', 'color' => ''], ['name' => '停车费收入', 'color' => ''], ['name' => '其他收入', 'color' => '']];
            }else{
                //todo  小区新版收费月总收入
                $where[] = ['pay_time', '>=', $month_start];
                $where[] = ['is_paid', '=', 1];
                $where[] = ['village_id', '=', $village_id];
                $data['total_money'] =get_number_format($dbHouseNewCashierService->getChargeProjectMoney($where));
                $data['total_money'] = round_number($data['total_money'],2);
                $data['type'] = $new_charge_type;
            }
            $date = $this->get_weeks(time(), 'm-d');
            $data['date'] = $date;
        }
        return api_output(0,$data,'获取成功');
    }

    /**
     * 获取最近一周的日期
     * @author lijie
     * @date_time 2020/08/04 9:56
     * @param string $time
     * @param string $format
     * @return array
     */
    public function get_weeks($time = '', $format='Y-m-d')
    {
        $time = $time != '' ? $time : time();
        //组合数据
        $date = [];
        for ($i=1; $i<=7; $i++){
            $date[$i-1] = date($format ,strtotime( '+' . $i-7 .' days', $time));
        }
        return $date;
    }

    /**
     * 巡检数据
     * @author lijie
     * @date_time 2020/08/04
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function inspectionData()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $wisdow_qrcode = new WisdowQrcodeService();
        $wisdom_qrcode_cate_list = [];
        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);
        $new_qrcode_cate_list=array();
        if(in_array(20,$package_content)) {
            $where[] = ['village_id', '=', $village_id];
            $field = '*';
            $wisdom_qrcode_cate_list = $wisdow_qrcode->getWisdomQrcodeCateList($where, $field);
            foreach ($wisdom_qrcode_cate_list as $k => $v) {
                if(isset($v['from_type']) && ($v['from_type']==1)){
                    $whereArr=array();
                    $whereArr[]=['village_id','=',$village_id];
                    $whereArr[]=['cate_id','=',$v['id']];
                    $whereArr[]=['task_instance_id','=',$v['task_instance_id']];
                    $oneTmp=$wisdow_qrcode->getOnePositionIndex($whereArr);
                    $wisdom_qrcode_cate_list[$k]['is_complete'] = 1;
                    if($oneTmp && $oneTmp['status']==0){
                        $wisdom_qrcode_cate_list[$k]['is_complete'] = 0;
                    }
                }else{
                    $where_con['cate_id'] = $v['id'];
                    $where_con['status'] = 1;
                    $data = $wisdow_qrcode->getWisdomQrcodePerson($where_con);
                    if(empty($data)){
                        unset($wisdom_qrcode_cate_list[$k]);
                        continue;
                    }
                    $is_complete = $wisdow_qrcode->isComplete($data,$v['type']);
                    if ($is_complete){
                        $wisdom_qrcode_cate_list[$k]['is_complete'] = 1;
                    }
                    else{
                        $wisdom_qrcode_cate_list[$k]['is_complete'] = 0;
                    }
                }
                $tmp_qrcode_cate=array();
                $tmp_qrcode_cate['cate_name']=$wisdom_qrcode_cate_list[$k]['cate_name'];
                $tmp_qrcode_cate['id']=$wisdom_qrcode_cate_list[$k]['id'];
                $tmp_qrcode_cate['is_complete']=$wisdom_qrcode_cate_list[$k]['is_complete'];
                $new_qrcode_cate_list[]=$tmp_qrcode_cate;
            }
            $data_list['patrol_show'] = true;
        }else{
            $data_list['patrol_show'] = false;
        }
        $data_list['list'] = $new_qrcode_cate_list;
        return api_output(0,$data_list,'查询成功');
    }
    //过滤套餐 2020/11/11 start
    public function getOrderPackage($village_id)
    {
        $servicePackageOrder = new PackageOrderService();
        $dataPackage = $servicePackageOrder->getPropertyOrderPackage('',$village_id);
        if($dataPackage) {
            $dataPackage = $dataPackage->toArray();
            $package_content = $dataPackage['content'];
        }else{
            $package_content = [];
        }
        return $package_content;
    }
    //过滤套餐 2020/11/11 end
    /**
     * Notes: 设备相关
     * @author: weili
     * @datetime: 2020/8/3 13:09
     */
    public function deviceStatistics()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $startTime = strtotime(date('Y-m-d',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));

        $serviceHouseUserLog = new HouseUserLogService();
        $serviceHouseFaceDevice = new HouseFaceDeviceService();
        //套餐过滤
        $package_content = $this->getOrderPackage($village_id);
        $hardware_intersect = array_intersect([6,7,8],$package_content);
        if($hardware_intersect && count($hardware_intersect)>0) {
            //今日开门 start
            $map[] = ['log_time', '>=', $startTime];
            $map[] = ['log_time', '<=', $endTime];
            $map[] = ['log_business_id', '=', $village_id];
            //$map[] = ['log_status', '=', 0];
            $map[] = ['log_from', 'in', [1,2,3,31,45]];
            $count = $serviceHouseUserLog->getOpenDoorNum($map);
        }
        //所有设备信息列表
        $data = $serviceHouseFaceDevice->getDeviceList($village_id);
        if($hardware_intersect && count($hardware_intersect)>0) {
            $data['today_open_count'] = $count;
        }
        return api_output(0,$data);

    }

    /**
     * Notes: 车辆相关
     * @author: weili
     * @datetime: 2020/8/3 13:10
     */
    public function carStatistics()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id=$this->adminUser['property_id'];
        if(!$village_id || !$property_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $startTime = strtotime(date('Y-m-d',time()));
        $endTime = strtotime(date('Y-m-d 23:59:59',time()));
        $servicePark = new ParkService();
        $info = $servicePark->parkInfo($property_id,$village_id,$startTime,$endTime);
        return api_output(0,$info);
    }

    /**
     * Notes:开门实时记录
     * @author: weili
     * @datetime: 2020/8/3 13:08
     */
    public function openDoorLog()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1002,'登录失效请登录');
        }
        $page = $this->request->param('page', '0', 'intval');
        $limit = 50;
        if ($page) {
            $page = ($page - 1) * $limit;
        }
        $serviceHouseUserLog = new HouseUserLogService();
        // 只查询人脸开门
        $in_log_from = [];
        $log_from = '';
        if (cfg('dataOpenFrom') == 1) {
            // 如果开启了 这个支持大数据查看人脸和远程开门2类数据
            $in_log_from = [1, 2];
        } else {
            $log_from = 1;
        }
        $params = [
            'village_id' => $village_id, 'in_log_from' => $in_log_from, 'log_from' => $log_from,
        ];
        /*
        $cacheTag  = 'village:cache:' . $village_id;
        $cacheKey  = 'village:openDoorLogV20:' . md5(\json_encode($params));
        $resultJson = Cache::store('redis')->get($cacheKey);
        if ($resultJson) {
            $list  = json_decode($resultJson, true);
            $refresh_ticket = $this->refresh_ticket;
            return api_output_refresh($list, $refresh_ticket);
        }
        */
        $list = $serviceHouseUserLog->getOpenDoorLogList($params);
        if (!empty($list)) {
            $resultJson = json_encode($list, JSON_UNESCAPED_UNICODE);
            //Cache::store('redis')->tag($cacheTag)->set($cacheKey,$resultJson, 600 + mt_rand(300, 600));
        }
        $refresh_ticket = $this->refresh_ticket;
        return api_output_refresh($list, $refresh_ticket);
    }

    /**
     * Notes: 开门定位（实时数据）
     * @author: weili
     * @datetime: 2020/8/3 13:09
     */
    public function openDoorLocation()
    {
        $village_id = $this->adminUser['village_id'];
        if(!$village_id){
            return api_output_error(1002,'登录失效请登录');
        }
        $page = $this->request->param('page','0','intval');
        $limit = 50;
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $sTime = time()-60;
        $serviceHouseUserLog = new HouseUserLogService();
        $where[] = ['l.log_business_id','=',$village_id];
        $where[] = ['l.log_from','in',[1,2]];
        //$field = 'l.log_id,l.log_time,l.device_id,f.long,f.lat,a.lng,a.lat as lats';
        $field = 'l.log_id,l.log_time,l.device_id,l.add_time';
        
        $list = $serviceHouseUserLog->getOpenAddress($where,$field,$village_id,$sTime);
        $refresh_ticket = $this->refresh_ticket;
        return api_output_refresh($list, $refresh_ticket);
    }
    
    public function getD7ParkCount(){
         $village_id = $this->adminUser['village_id'];
       //  $village_id = 50;
        if(!$village_id){
            return api_output_error(1002,'登录失效请登录');
        }
        $servicePark = new ParkService();
        $info = $servicePark->getD7ParkCount($village_id);
        return api_output(0,$info);
    }

    /**
     *男女比例统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationSexStatistics(){
        try{
            $village_id = $this->adminUser['village_id'];
            $village_ids=array($village_id);
            $list = (new TaskReleaseService())->getUserSexStatisticsMethod($this->adminUser['street_id'],0,$village_ids);
            $list['0']['0']['itemStyle']['color']='#00f6ff';
            $list['0']['1']['itemStyle']['color']='#e4007f';
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * 年龄段统计
     * @author: liukezhu
     * @date : 2022/5/10
     * @return \json
     */
    public function getPopulationAgeStatistics(){
        try{
            $village_id = $this->adminUser['village_id'];
            $village_ids=array($village_id);
            $list = (new TaskReleaseService())->getUserAgeStatisticsMethod($this->adminUser['street_id'],0,$village_ids);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    public function getUserLableStatistics(){
        try{
            $village_id = $this->adminUser['village_id'];
            $village_id=$village_id? $village_id:50;
            $list = (new TaskReleaseService())->getUserLableStatistics($village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
}