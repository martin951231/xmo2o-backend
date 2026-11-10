<?php


namespace app\community\model\service;


use app\common\model\service\config\ConfigCustomizationService;
use app\common\model\service\ConfigDataService;
use app\community\model\db\Area;
use app\community\model\db\BrandCars;
use app\community\model\db\Car\HouseVillageCarFlowRecord;
use app\community\model\db\HouseNewChargeStandardBind;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageBindCar;
use app\community\model\db\HouseVillageCarAccessRecord;
use app\community\model\db\HouseVillagePark;
use app\community\model\db\HouseVillageParkBlack;
use app\community\model\db\HouseVillageParkCharge;
use app\community\model\db\HouseVillageParkConfig;
use app\community\model\db\HouseVillageParkCoupon;
use app\community\model\db\ParkCoupons;
use app\community\model\db\HouseVillageParkFree;

use app\community\model\db\HouseNewChargeRule;

use app\community\model\db\HouseVillageParkingCar;
use app\community\model\db\HouseVillageParkingCarBindPosition;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageBindPosition;
use app\community\model\service\Car\CarHandleGetInfoService;
use app\community\model\service\Car\ParkCarToJobService;
use app\community\model\service\HouseVillageParkGarageConfigService;
use app\community\model\db\HouseVillageParkingTemp;
use app\community\model\db\HouseVillageParkShowscreenConfig;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\InPark;
use app\community\model\db\NolicenceInPark;
use app\community\model\db\OutPark;
use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkPlateresultLog;
use app\community\model\db\ParkScrcuRecord;
use app\community\model\db\ParkSerialLog;
use app\community\model\db\ParkShowscreenLog;
use app\community\model\db\ParkTotalRecord;
use app\community\model\db\ParkWhiteRecord;
use app\community\model\db\PlatOrder;
use app\community\model\db\User;
use app\community\model\service\Car\CarFlowShowScreenService;
use app\community\model\service\Car\HouseVillageCarFlowService;
use app\community\model\service\Device\HikGateWayEventService;
use app\community\model\service\Device\HouseDeviceQrcodeService;
use app\community\model\service\Park\A11Service;
use app\community\model\service\Park\D3ShowScreenService;
use app\community\model\service\Park\D5Service;
use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\Park\D6Service;
use app\community\model\service\Park\QinLinCloudService;
use app\foodshop\model\service\message\SmsSendService;
use app\community\model\service\HouseNewParkingService;
use app\pay\model\db\PayOrderInfo;
use app\pay\model\service\channel\ScrcuService;
use app\pay\model\service\PayService;
use error_msg\GetErrorMsg;
use think\Exception;
use think\facade\Cache;
use customization\customization;
use app\traits\CommonLogTraits;
use app\traits\house\AdminLoginTraits;
class HouseVillageParkingService
{
    use customization;
    use CommonLogTraits;
    use AdminLoginTraits;
    public $parkingGarageModel = '';
    public $parkingPositionModel = '';
    public $parkingCarModel = '';
    public $bindPositionModel = '';
    public $cacheTagKey='';
    //D3定制停车参数
    public $AppID = '平台测试';
    public $AppSecret = '12345678';
    public $url = 'http://admin.scxhwtkj.com/parking/api';
    public $url_v2 = 'http://admin.scxhwtkj.com/parking/api/v2';
    public $province_car = ["京", "津", "冀", "晋", "蒙", "辽", "吉", "黑", "沪", "苏", "浙", "皖", "闽", "赣", "鲁", "豫", "鄂", "湘", "粤", "桂", "琼", "渝", "川", "贵", "云", "藏", "陕", "甘", "青", "宁", "新"];
    public $relationship = ['1' => '业主', '2' => '父母', '3' => '配偶', '4' => '子女', '5' => '亲戚', '6' => '朋友', '7' => '其他'];
    public $examine_status = ['0' => ['text' => '待审核', 'color' => '#46ecc4'], '1' => ['text' => '通过', 'color' => '#1890ff'], '2' => ['text' => '拒绝', 'color' => '#f72682']];

    public $webhookurl = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=edf0375a-5029-4fe9-a398-06c871208106';

    public $register_day = 15;
    public $pay_time = 900;
    public $car_logo = '/static/images/car_logo/default.png';
    public $car_color = array(
        array(
            'id' => 'A',
            'car_color' => '白色'
        ),
        array(
            'id' => 'B',
            'car_color' => '灰色'
        ),
        array(
            'id' => 'C',
            'car_color' => '黄色'
        ),
        array(
            'id' => 'D',
            'car_color' => '粉色'
        ),
        array(
            'id' => 'E',
            'car_color' => '红色'
        ),
        array(
            'id' => 'F',
            'car_color' => '紫色'
        ),
        array(
            'id' => 'G',
            'car_color' => '绿色'
        ),
        array(
            'id' => 'H',
            'car_color' => '蓝色'
        ),
        array(
            'id' => 'I',
            'car_color' => '棕色'
        ),
        array(
            'id' => 'J',
            'car_color' => '黑色'
        ),
        array(
            'id' => 'Z',
            'car_color' => '其他'
        ),

    );

    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
    public $pay_type=[0=>'cash',1=>'wallet',2=>'sweepcode',3=>'escape',4=>'monthuser',5=>'free',6=>'scancode'];


    public function __construct()
    {
        $this->parkingGarageModel = new HouseVillageParkingGarage();
        $this->parkingPositionModel = new HouseVillageParkingPosition();
        $this->parkingCarModel = new HouseVillageParkingCar();
        $this->bindPositionModel = new HouseVillageBindPosition();
    }

    protected function setCacheTag($village_id)
    {
        $this->cacheTagKey = 'village:cache:'.$village_id;
    }

    protected function getCacheTagKey()
    {
        return $this->cacheTagKey;
    }

    /**
     * 获取车库列表
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/16 13:42
     */

    public function getParkingGarageLists($where, $field = true, $page = 0, $limit = 0, $order = 'garage_id DESC')
    {
        $data = $this->parkingGarageModel->getLists($where, $field, $page, $limit, $order)->toArray();

        return $data;
    }

    /**
     * 根据条件获取车库信息
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/17 14:02
     */
    public function getParkingGarageByCondition($where, $field = true)
    {
        $data = $this->parkingGarageModel->getOne($where, $field);
        if($data && !$data->isEmpty()){
            $data=$data->toArray();
        }else{
            $data=array();
        }
        $return_data = array();
        $res=array();
        $res['remark']='';
        if($data){
            if ($data['garage_num']) {
                $return_data[] = array(
                    'name' => '名称',
                    'value' => $data['garage_num']
                );
            }
            if ($data['garage_position']) {
                $return_data[] = array(
                    'name' => '地址',
                    'value' => $data['garage_position']
                );
            }
            $return_data[] = array(
                'name' => '备注',
                'value' => $data['garage_remark'] ? $data['garage_remark'] : '无'
            );
            $res['remark'] = $data['garage_remark'];
        }
        
        $res['data'] = $return_data;
        $res['detail'] = $data;
        return $res;
    }

    /**
     *编辑车库
     * @param $where
     * @param $data
     * @return bool
     * @author lijie
     * @date_time 2020/07/16 13:53
     */
    public function editParkingGarage($where, $data)
    {
        $res = $this->parkingGarageModel->saveOne($where, $data);
        $info = $this->parkingGarageModel->getOne($where);
        if (isset($info['village_id']) && $info['village_id']) {
            (new CarHandleGetInfoService())->clearCarParkingGarageInfo($info['village_id']);
        }
        return $res;
    }

    /**
     * 添加车库
     * @param $data
     * @return int|string
     * @author lijie
     * @date_time 2020/07/16 14:39
     */
    public function addParkingGarage($data)
    {
        $res = $this->parkingGarageModel->addOne($data);
        return $res;
    }

    /**
     * 删除车库
     * @param $where
     * @return bool
     * @throws \Exception
     * @author lijie
     * @date_time 2020/07/16 14:00
     */
    public function delParkingGarage($where)
    {
        $delInfo = $this->parkingGarageModel->getOne($where);
        $res = $this->parkingGarageModel->delOne($where);
        if (isset($delInfo['village_id']) && $delInfo['village_id']) {
            (new CarHandleGetInfoService())->clearCarParkingGarageInfo($delInfo['village_id']);
        }
        return $res;
    }

    /**
     * 获取车位列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/17 9:29
     */
    public function getParkingPositionLists($where, $field = true, $page = 1, $limit = 15, $order = 'pp.position_id DESC')
    {
        $db_house_village_parking_garage=new HouseVillageParkingPosition();
        $village_id=0;
        if (!empty($where)){
            foreach ($where as $v){
                if ($v[0]=='pp.village_id'){
                    $village_id=$v[2];
                }
            }
        }
        if (empty($village_id)){
            return [];
        }
        if($field!==true && $field!='*' && is_string($field) && strpos($field,'garage_id')===false){
            $field=$field.',pp.garage_id';
        }
        $data = $this->parkingPositionModel->getLists($where, $field, $page, $limit, $order);
        if ($data) {
            $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
            $parkGarageConfig=array();
            foreach ($data as &$val) {
                if ($val['garage_id'] == 0 || ($val['position_type']==3 && $val['position_pattern']==1)) {
                    $carInfo = $this->parkingCarModel->getOne(['car_position_id' => $val['position_id']], 'province');
                    $val['position_num'] = $carInfo['province'] . $val['position_num'];
                }
                if ($val['position_area']) {
                    $val['position_area'] = $val['position_area'] . 'm²';
                }else{
                    $val['position_area'] = '0.00m²';
                }
                $val['parent_position_num']='--';
                $children_position_type=0;
                if(isset($parkGarageConfig[$val['garage_id']]) && !empty($parkGarageConfig[$val['garage_id']])){
                    $children_position_type=$parkGarageConfig[$val['garage_id']];
                }else{
                    $park_config_info = $houseVillageParkGarageConfigService->getVillageParkConfig($val['garage_id'],$village_id);
                    if($park_config_info && !empty($park_config_info['park_sys_type'])){
                        $parkGarageConfig[$val['garage_id']]=$park_config_info['children_position_type'];
                        $children_position_type=$park_config_info['children_position_type'];
                    }
                }
                
                if ($children_position_type==1){
                    if (!empty($vv['parent_position_id'])&&$val['children_type']==2){
                        $parent_position_info=$db_house_village_parking_garage->getFind(['position_id'=>$val['parent_position_id'],'garage_id'=>$val['garage_id']]);
                        if (!empty($parent_position_info)){
                            $val['parent_position_num']=$parent_position_info['position_num'];
                        }
                    }
                    $val['children_type_txt']=$val['children_type']==1?'母车位':($val['children_type']==2?'子车位':'--');
                }else{
                    $val['children_type_txt']='--';
                }
            }
        }
        return $data;
    }

    /**
     * 车辆信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2021/09/24
     */
    public function getCar($where = [], $field = true)
    {
        $data = $this->parkingCarModel->getOne($where, $field);
        return $data;
    }

  
    public function getOneCar($where = [],$whereRaw='', $field = '*')
    {
        $data = $this->parkingCarModel->getOneCar($where,$whereRaw, $field);
        return $data;
    }

    /**
     * 获取车位列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author zhubaodi
     * @date_time 2021/06/18 17:29
     */
    public function getParkingPositionList($id, $where, $field = true, $page = 1, $limit = 15, $order = 'pp.position_id DESC',$nobinded=false)
    {
        // 车位ID
        $position_id = [];
        // 根据收费标准获取所有已绑定的房间对应的业主信息
        $userBind = $this->getVacancyToPosition($id,'position_id');

        if($userBind && !empty($userBind)){
            $position_id = array_column($userBind,'position_id');
        }
        //不需要已绑定的
        if($nobinded){
            $ruleBindPosition=$this->getRuleBindToPosition($id,'position_id');
            if(!empty($ruleBindPosition)){
                $positionArr = array_column($ruleBindPosition,'position_id');
                $position_id=array_merge($position_id,$positionArr);
            }
        }
        // 同一收费标准不能绑定同一业主的房产和车位  过滤已绑定房间的车位
        if(!empty($position_id)){
            $where[] = ['pp.position_id','not in',$position_id];
        }
        if (empty($id)) {
            throw new \think\Exception("收费标准id不能为空");
        }
        $db_rule = new HouseNewChargeRuleService();
        $ruleInfo = $db_rule->getRuleInfo($id);
        if (empty($ruleInfo)) {
            throw new \think\Exception("收费标准信息不存在");
        }

        $count = $this->parkingPositionModel->getCountss($where);
        //$data1 = $this->parkingPositionModel->getListss($where, $field, $page, $limit, $order);
        $data1 = $this->parkingPositionModel->getPositionListss($where, $field, $page, $limit, $order);
        if ($ruleInfo['charge_type']==2){
            $is_show = 1;
        }else {
            if (empty($ruleInfo['fees_type'])||in_array($ruleInfo['fees_type'],[3,4]) || ($ruleInfo['bill_type'] == 1 && empty($ruleInfo['unit_gage'])) || ($ruleInfo['charge_type'] == 1 && empty($ruleInfo['unit_gage']))) {
                $is_show = 2;
            } else {
                $is_show = 1;
            }
        }
        if (!empty($data1)) {
            $data1 = $data1->toArray();
            if (!empty($data1)) {
                foreach ($data1 as $k => $v) {
                    $data1[$k] = $v;
                    $data1[$k]['show'] = true;
                    if (empty($ruleInfo['fees_type']) ||in_array($ruleInfo['fees_type'],[3,4]) || ($ruleInfo['bill_type'] == 1 && empty($ruleInfo['unit_gage'])) || ($ruleInfo['charge_type'] == 1 && empty($ruleInfo['unit_gage']))) {

                        $data1[$k]['is_show'] = 2;
                    } else {
                        $data1[$k]['is_show'] = 1;
                    }

                }
            }
        }
        $data = [];
        $data['count'] = $count;
        $data['list'] = $data1;
        $data['is_show'] = $is_show;
        $data['total_limit'] = $limit;

        return $data;
    }

    /**
     * 获取车位列表
     * @param $where
     * @param bool $field
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author zhubaodi
     * @date_time 2021/06/18 17:29
     */
    public function getPositionList($where, $field = true)
    {
        $data = $this->parkingPositionModel->getLists($where, $field, 0);
        $id_arr = [];
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $id_arr[$k] = $v['position_id'];
            }
        }

        $datas['list'] = $data;
        $datas['ids'] = $id_arr;

        return $datas;
    }

    /**
     * 获取车位详情
     * @param $where
     * @param bool $field
     * @return mixed
     * @throws Exception
     * @author lijie
     * @date_time 2020/07/17 10:32
     */
    public function getParkingPositionDetail($where, $field = true,$source=0)
    {
        $data = $this->parkingPositionModel->getOne($where, $field);
        if (empty($data)){
            if($source == 0){
                throw new Exception('参数错误');
            }else{
                return [];
            }
        }

        $return_data = array();

        if (isset($data['position_num'])) {
            $return_data[] = array(
                'name' => '车位号',
                'value' => $data['position_num']
            );
        }

        if (isset($data['position_area'])) {
            $return_data[] = array(
                'name' => '产权面积',
                'value' => $data['position_area'] . 'm³'
            );
        }
        if (isset($data['garage_num'])) {
            $return_data[] = array(
                'name' => '车库',
                'value' => $data['garage_num']
            );
        }
        if (isset($data['name'])) {
            $return_data[] = array(
                'name' => '绑定业主',
                'value' => $data['name'] . '，' . $data['phone']
            );
        }
        $return_data[] = array(
            'name' => '备注',
            'value' => isset($data['position_note']) ? $data['position_note'] : '无'
        );
        $res['remark'] = isset($data['position_note']) ? $data['position_note'] : '无';
        $res['data'] = $return_data;
        $res['detail'] = $data;
        return $res;
    }

    /**
     * 添加车位
     * @param $data
     * @return int|string
     * @author lijie
     * @date_time 2020/07/17 9:32
     */
    public function addParkingPosition($data)
    {
        $res = $this->parkingPositionModel->addOne($data);
        return $res;
    }

    /**
     * 编辑车位
     * @param $where
     * @param $data
     * @return mixed
     * @author lijie
     * @date_time 2020/07/17 13:39
     */
    public function editParkingPosition($where, $data)
    {
        $db_house_new_pay_order=new HouseNewPayOrder();
        $db_house_new_charge_standard_bind=new HouseNewChargeStandardBind();
        if (!isset($data['village_id'])||empty($data['village_id'])){
            $position_info11=$this->parkingPositionModel->getFind($where);
            if (!empty($position_info11)){
                $data['village_id']=$position_info11['village_id'];
                $data['position_id']=$position_info11['position_id'];
                $data['garage_id']=$position_info11['garage_id'];
                $data['children_type']=$position_info11['children_type'];
            }else{
                throw new \think\Exception("车位信息不存在，无法编辑车位");
            }
        }
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($data['garage_id'],$data['village_id']);
       
        $where11=[
            ['village_id','=',$data['village_id']],
            ['position_id','=',$data['position_id']],
        ];
        $position_info=$this->parkingPositionModel->getFind($where11);
        if (empty($position_info)){
            throw new \think\Exception("车位信息不存在，无法编辑车位");
        }
        if ($house_village_park_config['children_position_type']==1){
            if ($position_info['children_type']==2&&!empty($position_info['parent_position_id'])){
                if ($data['garage_id']!=$position_info['garage_id']){
                    throw new \think\Exception("当前子车位已绑定母车位，请解除绑定关系后在修改所属车库");
                }
            }
            if ($position_info['children_type']==1){
                $where_parent=[];
                $where_parent['village_id']=$data['village_id'];
                $where_parent['parent_position_id']=$data['position_id'];
                $parent_position_count=$this->parkingPositionModel->getCounts($where_parent);
                if ($parent_position_count>0&&$data['garage_id']!=$position_info['garage_id']){
                    throw new \think\Exception("当前母车位已绑定子车位，请解除绑定关系后在修改所属车库");
                }
            }
            if ($data['children_type']==1&&$position_info['children_type']==2&&!empty($position_info['parent_position_id'])){
                $where_parent1=[];
                $where_parent1['village_id']=$data['village_id'];
                $where_parent1['garage_id']=$data['garage_id'];
                $where_parent1['parent_position_id']=$position_info['parent_position_id'];
                $position_info1=$this->parkingPositionModel->getFind($where11);
                if (empty($position_info1)){
                    $this->parkingPositionModel->saveOne(['position_id'=>$data['position_id']],['parent_position_id'=>0]);
                }else{
                    throw new \think\Exception("当前车位有绑定母车位，请先解除绑定");
                }
            }
            if ($data['children_type']==2&&$position_info['children_type']==1){
                $where_order=[];
                $where_order['village_id']=$data['village_id'];
                $where_order['position_id']=$data['position_id'];
                $where_order['is_paid']=2;
                $where_order['is_discard']=1;
                $count_order=$db_house_new_pay_order->getCount($where_order);
                if ($count_order>0){
                    throw new \think\Exception("当前车位有待缴账单或作废账单审核中时，无法修改");
                }
                $where_bind=[];
                $where_bind['b.village_id']=$data['village_id'];
                $where_bind['b.position_id']=$data['position_id'];
                $where_bind['b.is_del']=1;
                $count_bind=$db_house_new_charge_standard_bind->getCount($where_bind);
                if ($count_bind>0){
                    throw new \think\Exception("当前车位有收费标准未解绑，无法修改");
                }
                if ($parent_position_count>0){
                    throw new \think\Exception("当前车位有绑定子车位，请先解除绑定");
                }

            }
        }
        $res = $this->parkingPositionModel->saveOne($where, $data);
        return $res;
    }

    /**
     * 删除车位
     * @param $where
     * @return bool
     * @throws \Exception
     * @author lijie
     * @date_time 2020/07/17 9:35
     */
    public function delParkingPosition($where)
    {
        $positionInfo=$this->parkingPositionModel->getFind($where);
        if (empty($positionInfo)){
            throw new Exception('车位信息不存在');
        }
        $village_id = isset($positionInfo['village_id']) ? intval($positionInfo['village_id']) : 0;
        $cacheTagKey = 'village:'.$village_id;
        Cache::tag($cacheTagKey)->clear();
        $res = $this->parkingPositionModel->delOne($where);
        if ($village_id > 0) {
            (new CarHandleGetInfoService())->clearCarPositionInfo($village_id);
        }
        return $res;
    }

    /**
     * 根据条件获取车位信息
     * @param $where
     * @param bool $field
     * @return mixed
     * @author lijie
     * @date_time 2020/07/17 13:54
     */
    public function getParkingPositionByCondition($where, $field = true)
    {
        $data = $this->parkingPositionModel->getOne($where, $field);
        if (isset($data['end_time']) && !empty($data['end_time'])){
            $data['end_time'] = date('Y-m-d H:i:s', $data['end_time']);
        }
        return $data;
    }

    /**
     * 获取车辆列表
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/18 9:22
     */
    public function getParkingCarLists($where, $field = true, $page = 1, $limit = 15, $order = 'car_id DESC')
    {
        $data = $this->parkingCarModel->getHouseVillageParkingCarLists($where, $field, $page, $limit, $order);
        return $data;
    }
    public function getParkingCarNewLists($whereArr, $position_id=0,$field = '*', $page = 0, $limit = 15, $order = 'car_id DESC')
    {
        $whereRaw = '(car_position_id =' . $position_id . ' OR FIND_IN_SET(' . $position_id . ',bind_position_ids))';
        $car_list_obj = $this->parkingCarModel->getNewList($whereArr, '*', $page, $limit, $order, $whereRaw);
        $car_list=array();
        if($car_list_obj && !$car_list_obj->isEmpty()){
            $car_list=$car_list_obj->toArray();
        }
        return $car_list;
    }
    /**
     * 获取车辆详情
     * @param $where
     * @param bool $field
     * @return array|bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/07/18 9:25
     */
    public function getParkingCarDetail($where, $field = true)
    {
        if($field!==true && $field!='*' && is_string($field) && strpos($field,'garage_id')===false){
            $field=$field.',pp.garage_id';
        }
        $data = $this->parkingCarModel->getHouseVillageParkingCarById($where, $field);
        if (empty($data)) {
            return false;
        }
        $return_data = array();
        if ($data['position_num']) {
            $return_data[] = array(
                'name' => '车位号',
                'value' => $data['position_num']
            );
        } else {
            $data['car_position_id'] = 0;
            $return_data[] = array(
                'name' => '车位号',
                'value' => '无'
            );
        }
        if ($data['car_number']) {
            $data['car_number']= $data['province'].$data['car_number'];
            $return_data[] = array(
                'name' => '车牌号',
                'value' => $data['car_number']
            );
        }
        if ($data['car_stop_num']) {
            $return_data[] = array(
                'name' => '停车卡号',
                'value' => $data['car_stop_num']
            );
        }
        $data['binding_type']=2;  //2业主
        $data['single_name']='';
        $data['floor_name']='';
        $data['layer_name']='';
        $data['room']='';
        $data['room_address']='';
        $data['garage_num']='';
        $data['parking_car_type_str']='';
        $house_village_park_config=array();
        if(isset($data['garage_id']) && $data['garage_id']>0){
            $db_house_village_ParkIng_garage=new HouseVillageParkingGarage();
            $garage_info=$db_house_village_ParkIng_garage->getOne(['garage_id'=>$data['garage_id'],'status'=>1]);
            if($garage_info && !$garage_info->isEmpty()){
                $data['garage_num']=$garage_info['garage_num'];
            }
            $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
            $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($data['garage_id'],$garage_info['village_id']);
        }
        if(isset($data['parking_car_type'])){
            $houseNewParkingService= new HouseNewParkingService();
            if ($house_village_park_config && $house_village_park_config['park_sys_type'] == 'D3') {
                $parking_car_type_arr =$houseNewParkingService->parking_d3_car_type_arr;
            } elseif ($house_village_park_config && $house_village_park_config['park_sys_type'] == 'D7') {
                $parking_car_type_arr =$houseNewParkingService->parking_d7_car_type_arr;
            } else {
                $parking_car_type_arr =$houseNewParkingService->parking_car_type_arr;
            }
            $data['parking_car_type_str']=isset($parking_car_type_arr[$data['parking_car_type']]) ? $parking_car_type_arr[$data['parking_car_type']]:'';
        }
        if(isset($data['room_id']) && $data['room_id']>0){
            $data['binding_type']=1;  //2房间
            $service_house_village = new HouseVillageService();
            $service_house_village_single = new HouseVillageSingleService();
            $where_room = [];
            $where_room[] = ['pigcms_id', '=', $data['room_id']];
            $info = $service_house_village->getRoomInfoWhere($where_room,'pigcms_id,usernum,property_number,layer,room,housesize,house_type,user_status,sell_status,name,phone,single_id,layer_id,floor_id');
            $data['room']=$info['room'];
            if($info['single_id']>0){
                $house_single=$service_house_village_single->getSingleInfo(['id'=>$info['single_id']],'single_name');
                if(!empty($house_single)){
                    $data['single_name']=$house_single['single_name'];
                    if(is_numeric($house_single['single_name'])){
                        $data['single_name']=$house_single['single_name'].(cfg('single_name')?:'栋');
                    }
                }
            }

            if($info['floor_id']>0){
                $house_single=$service_house_village_single->getFloorInfo(['floor_id'=>$info['floor_id']],'floor_name');
                if(!empty($house_single)){
                    $data['floor_name']=$house_single['floor_name'];
                    if(is_numeric($house_single['floor_name'])){
                        $data['floor_name']=$house_single['floor_name'].'单元';
                    }
                }
            }

            if($info['layer_id']>0){
                $house_single=$service_house_village_single->getLayerInfo(['id'=>$info['layer_id']],'layer_name');
                if(!empty($house_single)){
                    $data['layer_name']=$house_single['layer_name'];
                    if(is_numeric($house_single['layer_name'])){
                        $data['layer_name']=$house_single['layer_name'].'层';
                    }
                }
            }
            $data['room_address']=$data['single_name'].$data['floor_name'].$data['layer_name'].$info['room'];
            $return_data[] = array(
                'name' => '绑定房间',
                'value' => $data['room_address']
            );
        }
        if ($data['car_user_name']) {
            $return_data[] = array(
                'name' => '车主姓名',
                'value' => $data['car_user_name']
            );
        }
        $houseVillageDb=new HouseVillage();
        $village_info=$houseVillageDb->getInfo(array('village_id'=>$data['village_id']),'*');
        if ($data['car_user_phone']) {
            $car_user_phone=$houseVillageDb->phone_desensitization_v($data['car_user_phone'],$data['village_id'],$village_info);
            $return_data[] = array(
                'name' => '车主手机号',
                'value' => $car_user_phone,
                'is_phone' => 1
            );
        }

        $data['end_time_str'] = $data['end_time']>1 ? date('Y-m-d', $data['end_time']) : '';
        if ($data['end_time_str']) {
            $return_data[] = array(
                'name' => '停车到期时间',
                'value' => $data['end_time_str']
            );
        }
        if ($data['equipment_no']) {
            $return_data[] = array(
                'name' => '车辆识别代码',
                'value' => $data['equipment_no']
            );
        }
        if ($data['car_color']) {
            foreach ($this->car_color as $k => $v) {
                if ($v['id'] == $data['car_color']) {
                    $data['color_id'] = $data['car_color'];
                    $data['car_color'] = $v['car_color'];
                    break;
                }
            }
            $return_data[] = array(
                'name' => '车辆颜色',
                'value' => $data['car_color']
            );
        }
        if (!empty($data['car_brands'])){
            $arr_brans=explode('-',$data['car_brands']);
            $data['brands_type']=$arr_brans[0];
            $data['brands']=$arr_brans[1];

        }else{
            $data['brands_type']='';
            $data['brands']='';
        }
        $res['data'] = $return_data;
        $res['detail'] = $data;
        return $res;
    }

    /**
     * 添加车辆
     * @param $data
     * @return int|string
     * @author lijie
     * @date_time 2020/07/18 9:26
     */
    public function addParkingCar($data)
    {
        $res = $this->parkingCarModel->addHouseVillageParkingCar($data);
        return $res;
    }

    /**
     * 编辑车辆
     * @param $where
     * @param $data
     * @return bool
     * @author lijie
     * @date_time 2020/07/18 9:28
     */
    public function editParkingCar($where, $data)
    {
        $res = $this->parkingCarModel->editHouseVillageParkingCar($where, $data);
        return $res;
    }

    public function saveCarOtherInfo($village_id = 0, $binding_type = 0, $pdata = array())
    {

        if ($village_id < 1 || $binding_type < 1 || empty($pdata)) {
            return false;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $village_user_bind = array();
        $pigcms_id = 0;
        $uid = 0;
        if (!isset($pdata['car_id']) || !isset($pdata['relationship'])) {
            return false;
        }
        if ($binding_type == 2 && isset($pdata['pigcms_id']) && $pdata['pigcms_id'] > 0) {
            $user_bind = $db_house_village_user_bind->getSumBills(['village_id' => $village_id, 'pigcms_id' => $pdata['pigcms_id'], 'type' => [0, 1, 2, 3], 'status' => 1], 'pigcms_id,vacancy_id,uid');
            if (empty($user_bind) || $user_bind->isEmpty()) {
                return false;
            }
            if ($user_bind && isset($user_bind['pigcms_id'])) {
                $pigcms_id = $user_bind['pigcms_id'];
                $village_user_bind[] = $user_bind['pigcms_id'];
                $uid = $user_bind['uid'];
            }
        } elseif ($binding_type == 1 && isset($pdata['room_id']) && $pdata['room_id'] > 0) {

            $user_bind = $db_house_village_user_bind->getSumBills(['village_id' => $village_id, 'vacancy_id' => $pdata['room_id'], 'type' => [0, 1, 2, 3], 'status' => 1], 'pigcms_id,vacancy_id,uid');
            if (empty($user_bind) || $user_bind->isEmpty()) {
                return false;
            }
            $pigcms_id = $user_bind['pigcms_id'];
            $village_user_bind[] = $user_bind['pigcms_id'];
            $uid = $user_bind['uid'];
        }
        $db_house_village_bind_car = new HouseVillageBindCar();
        if (!empty($village_user_bind)) {
            $bing_info = $db_house_village_bind_car->getFind(['village_id' => $village_id, 'car_id' => $pdata['car_id']]);
            if (!empty($bing_info) && !$bing_info->isEmpty()) {
                $db_house_village_bind_car->saveOne(['village_id' => $village_id, 'car_id' => $pdata['car_id']], ['relationship' => $pdata['relationship'], 'user_id' => $pigcms_id, 'uid' => $uid]);
            } else {
                $db_house_village_bind_car->addOne(['village_id' => $village_id, 'car_id' => $pdata['car_id'], 'relationship' => $pdata['relationship'], 'user_id' => $pigcms_id, 'uid' => $uid]);
            }
        }
        return true;
    }
    
    /**
     * 删除车辆
     * @param $where
     * @return bool
     * @throws \Exception
     * @author lijie
     * @date_time 2020/07/18 9:30
     */
    public function delParkingCar($where)
    {
        $res = $this->parkingCarModel->delHouseVillageParkingCar($where);
        return $res;
    }

    /**
     * 车位数量
     * @param $where
     * @return int
     * @author lijie
     * @date_time 2020/12/04
     */
    public function get_village_park_position_num($where)
    {
        $count = $this->parkingPositionModel->get_village_park_position_num($where);
        return $count;
    }

    /**
     * 查询对应条件下车辆数量
     * @param $where
     * @return int
     * @author: lijie
     * @date_time: 2020/12/07
     */
    public function getCarNum($where)
    {
        $count = $this->parkingCarModel->get_village_car_num($where);
        return $count;
    }

    /**
     * 添加用户绑定车位
     * @param $data
     * @return int|string
     * @author lijie
     * @date_time 2020/02/02
     */
    public function addBindPosition($data)
    {
        $res = $this->bindPositionModel->addOne($data);
        return $res;
    }

    /**
     * 业主绑定车位列表
     * @param array $where
     * @param bool $field
     * @return mixed
     * @author lijie
     * @date_time 2021/09/17
     */
    public function getBindPositionList($where = [], $field = true)
    {
        $data = $this->bindPositionModel->getList($where, $field);
        return $data;
    }

    /**
     * 查询用户绑定车位
     * @param $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author lijie
     * @date_time 2020/02/02
     */
    public function getBindPosition($where)
    {
        $data = $this->bindPositionModel->getOne($where);
        return $data;
    }

    /**
     * 主解绑车位
     * @param $where
     * @return bool
     * @throws \Exception
     * @author lijie
     * @date_time 2020/02/02
     */
    public function delBindPosition($where)
    {
        $data = $this->bindPositionModel->delOne($where);
        return $data;
    }


    public function getNav($where = [], $field = true,$select=1)
    {
        $cacheKey = 'village:'.$where['village_id'].':Car:getNav:position_num_asc:'.md5(\json_encode($where).\json_encode($field).\json_encode($select));
        $data = Cache::get($cacheKey);
        if (!empty($data)){
            return $data;
        }
        $this->setCacheTag($where['village_id']);
        $garage_list = $this->parkingGarageModel->getLists($where, $field)->toArray();
        $items = [];
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $children_position_type=0;
        foreach ($garage_list as $key => $value) {
            $list['permission_id'] = $value['garage_id'];
            $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($value['garage_id'],$where['village_id']);
            if($house_village_park_config && $house_village_park_config['park_sys_type'] && $house_village_park_config['children_position_type']>0){
                $children_position_type=$house_village_park_config['children_position_type'];
            }
            $list['title'] = $value['garage_num'];
            if ($select==1){
                $list['disabled'] = true;
            }else{
                $list['disabled'] = false;
            }
            $list['scopedSlots'] = ['icon' => 'cluster','switcherIcon' => 'garage'];
            $list['village_id'] = $where['village_id'];
            $list['key'] = $value['garage_id'] . '|' . $value['garage_num'] . '|' . 'garage';
            $items[] = $list;
        }
        $count = count($items);
        $items[$count]['permission_id'] = '999999999';
        $items[$count]['title'] = '临时车位';
        $items[$count]['disabled'] = false;
        $items[$count]['scopedSlots'] = ['icon' => 'cluster','switcherIcon' => 'temgarage'];
        $items[$count]['key'] = '100000|临时车位|garage';
        $items[$count]['type'] = '临时车位';
        $items[$count]['village_id'] = $where['village_id'];
        $data[0]['scopedSlots'] = ['icon' => 'cluster','switcherIcon' => 'car'];
        if ($select==1){
            $data[0]['disabled'] = true;
        }else{
            $data[0]['disabled'] = false;
        }
        if ($items){
            $res = $this->getTree($items, 'garage',$select,$children_position_type);
        }else{
            $res = [];
            $data[0]['disabled'] = true;
            $data[0]['scopedSlots'] = ['icon' => 'cluster','switcherIcon' => 'car_empty'];
        }
        $data[0]['children_position_type'] = $children_position_type;
        $data[0]['park_sys_type'] = '';
        $data[0]['permission_id'] = 0;
        $data[0]['title'] = '车场';
        $data[0]['key'] = '0|车场|car';
        $data[0]['children'] = $res;
        try {
            Cache::tag($this->getCacheTagKey())->set($cacheKey,$data,1800);
        }catch (\Exception $e){

        }
        return $data;
    }

    public function getTree($arr = [], $type = '',$select=0,$children_position_type)
    {

        foreach ($arr as $k => $v) {
            $where=array();
            if ($type == 'garage') {
                $where[]=array('pp.garage_id','=',$v['permission_id']);
                $where[]=array('pp.position_pattern','>',0);
                if ($children_position_type==1){
                    $where[]=array('pp.children_type','=',1);
                }
                if(isset($v['village_id'])){
                    $where[]=array('pp.village_id','=',$v['village_id']);
                }
                $position_list = $this->getPosition($where, 'pp.position_id,pp.position_num,pp.garage_id,pp.position_type,pp.position_pattern');
                $list = [];
                foreach ($position_list as $k1 => $v1) {
                    $list[$k1]['title'] = $v1['title'];
                    $list[$k1]['disabled'] = false;
                    $list[$k1]['scopedSlots'] = $v1['scopedSlots'];
                    $list[$k1]['parent_permission_id'] = $v['permission_id'];
                    $list[$k1]['permission_id'] = $v1['position_id'];
                    $list[$k1]['key'] = $v1['key'];
                }
                if(empty($list) && ($v['key']=='100000|临时车位|garage')){
                    $arr[$k]['scopedSlots']=['icon' => 'cluster','switcherIcon' => 'temgarage_empty'];
                }elseif(empty($list) && ($v['key']!='100000|临时车位|garage')){
                    $arr[$k]['scopedSlots']=['icon' => 'cluster','switcherIcon' => 'garage_empty'];
                }
                if(empty($list)){
                    $arr[$k]['disabled'] = true;
                }
                $arr[$k]['children'] = $list;
            }
        }
        return $arr;
    }

    public function getPosition($where = [], $field = true)
    {
        $position_list = $this->parkingPositionModel->getLists($where, $field, 0, 0, 'pp.position_num ASC,pp.position_id ASC');
        if ($position_list) {
            foreach ($position_list as $k => $v) {
                /**
                 * $carInfo = $this->parkingCarModel->getOne(['car_position_id'=>$v['position_id']],'province');
                 * $position_list[$k]['title'] = $carInfo['province'].$v['position_num'];
                **/
                $position_list[$k]['title'] = $v['position_num'];
                $position_list[$k]['disabled'] = false;
                if($v['position_type'] == 3 && $v['position_pattern']==1){
                    $position_list[$k]['scopedSlots'] = ['icon' => 'user','title'=>'edit_out','switcherIcon' => 'position']; 
                }else{
                    $position_list[$k]['scopedSlots'] = ['icon' => 'user','switcherIcon' => 'position'];
                }
                $position_list[$k]['key'] = $v['position_id'] . '|' . $v['position_num'] . '|' . 'position';
            }
        }
        return $position_list;
    }

    /**
     * 车辆详情
     * @author lijie
     * @date_time 2021/07/06
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCarDetail($where=[],$field=true)
    {
        $data = $this->parkingCarModel->getOne($where,$field);
        if($data['room_id']){
            $service_house_village_user_vacancy = new HouseVillageUserVacancyService();
            $room_info = $service_house_village_user_vacancy->getUserVacancyInfo(['pigcms_id'=>$data['room_id']],'room');
            if($room_info){
                $data['room_address'] = $room_info['room'];
            }else{
                $data['room_address'] = '';
            }
        }
        return $data;
    }

    /**
     *定制停车-获取停车场列表
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:54
     */
    public function getParkingList($data)
    {
        
        $db_house_village = new HouseVillage();
        $village_info = [];
        /*
         *
        $db_house_village_park_config = new HouseVillageParkConfig();
        $where_list=[
            'c.park_show'=>1,
            'c.park_versions'=>2,
            'b.status'=>1,
        ];
        $field='b.village_name,b.village_id,b.long,lat,b.village_address,c.id';
        $list = $db_house_village_park_config->getVillagelist($where_list, $field);
        
        if (!empty($list)) {
            $list=$list->toArray();
        }
        */
        $whereArr = array();
        $whereArr[] = array('village_id', '>', 0);
        $whereArr[] = array('garage_id', '>', 0);
        if($data['park_name'] && !empty($data['park_name'])){
            $whereArr[] = array('garage_num', 'like', '%'.$data['park_name'].'%');
        }
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $list = $houseVillageParkGarageConfigService->getVillageParkingList($whereArr);
        $parkLst = [];
        $is_temporary_park = 0;
        $house_village_arr=array();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $house_village = array();
                if(isset($house_village_arr[$v['village_id']])){
                    $house_village=$house_village_arr[$v['village_id']];
                }else{
                    $house_village = $db_house_village->getInfo(array(['village_id', '=', $v['village_id']]), 'village_id,village_name,status,village_address,long,lat');
                    if ($house_village && !$house_village->isEmpty()) {
                        $house_village = $house_village->toArray();
                        $house_village_arr[$v['village_id']]=$house_village;
                    } 
                }
                if ($house_village && $house_village['status'] == 1) {
                    $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($v['garage_id'], $v['village_id']);
                    if ($house_village_park_config && $house_village_park_config['park_show'] == 1 && $house_village_park_config['park_versions'] == 2) {
                        $park_sys_type=$house_village_park_config['park_sys_type'] ? ucfirst($house_village_park_config['park_sys_type']):'';
                        if($park_sys_type != 'D5'){
                            $is_temporary_park=1;
                        }
                        $parkTmp = array();
                        $parkTmp['id'] = $v['village_id'];
                        $parkTmp['village_id'] = $v['village_id'];
                        $parkTmp['garage_id'] = $v['garage_id'];
                        $parkTmp['name'] = $v['garage_num'];
                        $parkTmp['address'] = $v['garage_position'];
                        $parkTmp['lng'] = $house_village['long'];
                        $parkTmp['lat'] = $house_village['lat'];
                        if ($v['lat'] > 0) {
                            $parkTmp['lat'] = $v['lat'];
                            $parkTmp['lng'] = $v['lng'];
                        }
                        $distance = getDistance($data['lat'], $data['lng'], $parkTmp['lat'], $parkTmp['lng']);
                        $parkTmp['distance_sort'] = $distance;
                        if ($distance >= 1000) {
                            $parkTmp['distance'] = round_number($distance / 1000, 2) . 'km';
                        } else {
                            $parkTmp['distance'] = $distance . 'm';
                        }
                        $parkLst[] = $parkTmp;
                    }

                }
            }
            if (count($parkLst) > 1) {
                $distance1 = array_column($parkLst, 'distance_sort');
                array_multisort($distance1, SORT_ASC, SORT_NUMERIC, $parkLst);
            }
        }
        unset($list);
        unset($house_village_arr);
    /**
        if (!empty($village_info)) {
            $parkLst[0]['id'] = $village_info['village_id'];
            $parkLst[0]['name'] = $village_info['village_name'];
            $parkLst[0]['address'] = $village_info['village_address'];
            $parkLst[0]['lng'] = $village_info['long'];
            $parkLst[0]['lat'] = $village_info['lat'];
            $parkLst[0]['distance'] = getDistance($data['lat'], $data['lng'], $village_info['lat'], $village_info['long']);
            if ($parkLst[0]['distance'] >= 1000) {
                $parkLst[0]['distance'] = round_number($parkLst[0]['distance'] / 1000, 2) . 'km';
            } else {
                $parkLst[0]['distance'] = $parkLst[0]['distance'] . 'm';
            }
        }
        **/

        
        $tips = [
            'title' => '温馨提示',
            'info' => '请点击停车场列表，进行临时停车登记。',
            'icon' => cfg('site_url') . '/static/images/house/park/notice.gif'
        ];
        if ($is_temporary_park===0) {
                $tips = '';
        }
        return [
            'list' => $parkLst,
            'skiptype' => $is_temporary_park,
            'tips' => $tips
        ];
    }
    /**
     * 百度地图坐标转腾讯地图等火星坐标
     * @param [String] $lat 百度地图坐标的纬度
     * @param [String] $lng 百度地图坐标的经度
     * @return [Array] 返回记录纬度经度的数组
     */
    function baiduToGcj02($lat,$lng){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta);
        $lat = $z * sin($theta);
        return array('lng'=>$lng,'lat'=>$lat);
    }
    /**
     *定制停车-获取停车纪录
     * @author:zhubaodi
     * @date_time: 2021/8/21 13:54
     */
    public function getInParkingList($uid, $village_id, $page)
    {
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village = new HouseVillage();
        $wherePark = [];
        $wherePark[] = ['village_id', '=', $village_id];
        $wherePark[] = ['uid', '=', $uid];
        $wherePark[] = ['order_type', '=', 'park'];
        $wherePark[] = ['paid', '=', '1'];
        $pay_order_list = $db_house_village_pay_order->get_list($wherePark);

        $order_id_arr = $db_house_village_pay_order->getColumn($wherePark, 'order_id');
        $whereParkCar = $wherePark;
        $whereParkCar[] = ['car_id', '>', 0];
        $car_id_arr   = $db_house_village_pay_order->getColumn($whereParkCar, 'car_id');
//        $village_id_arr = $db_house_village_pay_order->getColumn($wherePark, 'village_id');
        $village_id_arr = [$village_id];
        if (!empty($order_id_arr)) {
            $whereInPark = [];
            $whereInPark[] = ['pay_order_id', 'in', $order_id_arr];
            $whereInPark[] = ['park_id',      '=', $village_id];
            $in_park_arr = $db_in_park->getColumn($whereInPark, 'in_time, out_time, car_number, order_id', 'pay_order_id');
            $park_order_id_arr = $db_in_park->getColumn($whereInPark, 'order_id');
            $whereOutPark = [];
            $whereOutPark[] = ['order_id', 'in', $park_order_id_arr];
            $whereOutPark[] = ['out_time', '>', 0];
            $out_park_arr = $db_out_park->getColumn($whereOutPark, 'in_time, out_time, car_number, order_id', 'order_id');
        } else {
            $in_park_arr = [];
            $out_park_arr = [];
        }

        // 查询新版停车收费
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $whereParkNew = [];
        $whereParkNew[] = ['village_id', '=', $village_id];
        $whereParkNew[] = ['uid',        '=', $uid];
        $whereParkNew[] = ['order_type', '=', 'park_new'];
        $whereParkNew[] = ['is_paid',    '=', '1'];
        $whereParkCarNew = $whereParkNew;
        $new_pay_order_list = $db_house_new_pay_order->getPayLists($whereParkNew, true, 0, 0, 'pay_time DESC, order_id DESC');
        $whereParkNew[] = ['car_type',   '=', 'temporary_type'];
        $summary_id_arr = $db_house_new_pay_order->getColumn($whereParkNew, 'summary_id');

        $whereParkCarNew[] = ['car_id', '>', 0];
        $car_id_new_arr   = $db_house_new_pay_order->getColumn($whereParkCarNew, 'car_id');
        if (!empty($car_id_new_arr)) {
            $car_id_arr = array_merge($car_id_arr, $car_id_new_arr);
        }
        $car_id_arr = array_unique($car_id_arr);

        if (!empty($summary_id_arr)) {
            $whereNewInPark = [];
            $whereNewInPark[] = ['pay_order_id', 'in', $summary_id_arr];
            $whereNewInPark[] = ['park_id',      '=', $village_id];
            $new_in_park_arr       = $db_in_park->getColumn($whereNewInPark, 'id, in_time, out_time, car_number, order_id', 'pay_order_id');
            $new_park_order_id_arr = $db_in_park->getColumn($whereNewInPark, 'order_id');
            $new_from_id_arr       = $db_in_park->getColumn($whereNewInPark, 'id');
            $whereNewOutPark = [];
            $whereNewOutPark[] = ['order_id', 'in', $new_park_order_id_arr];
            $whereNewOutPark[] = ['out_time', '>', 0];
            $new_out_park_arr = $db_out_park->getColumn($whereNewOutPark, 'id, in_time, out_time, car_number, order_id', 'order_id');

            if (!empty($new_from_id_arr)) {
                $whereInAccessRecord = [];
                $whereInAccessRecord[] = ['business_type', '=', 0];
                $whereInAccessRecord[] = ['business_id',   '=', $village_id];
                $whereInAccessRecord[] = ['accessType',    '=', 1];
                $whereInAccessRecord[] = ['from_id',       'in', $new_from_id_arr];
                $accessOrderIdArr = $db_house_village_car_access_record->getColumn($whereInAccessRecord, 'order_id');
                $accessInInfoArr  = $db_house_village_car_access_record->getColumn($whereInAccessRecord, 'record_id, order_id, car_number,accessTime, from_id','from_id');
                $whereOutAccessRecord = [];
                $whereOutAccessRecord[] = ['business_type', '=', 0];
                $whereOutAccessRecord[] = ['business_id',   '=', $village_id];
                $whereOutAccessRecord[] = ['accessType',    '=', 2];
                $whereOutAccessRecord[] = ['order_id',      'in', $accessOrderIdArr];
                $accessOutInfoArr = $db_house_village_car_access_record->getColumn($whereOutAccessRecord, 'record_id, order_id, car_number,accessTime, from_id', 'order_id');
            } else {
                $accessInInfoArr  = [];
                $accessOutInfoArr = [];
            }
        } else {
            $accessInInfoArr  = [];
            $accessOutInfoArr = [];
            $new_in_park_arr  = [];
            $new_out_park_arr = [];
        }

        if (!empty($car_id_arr)) {
            $whereCar = [];
            $whereCar[] = ['village_id', '=', $village_id];
            $whereCar[] = ['car_id',     'in', $car_id_arr];
            $db_house_village_parking_car = new HouseVillageParkingCar();
            $car_info_arr = $db_house_village_parking_car->getColumn($whereCar, 'car_id, village_id, province, car_number', 'car_id');
        } else {
            $car_info_arr = [];
        }
        if (!empty($village_id_arr)) {
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $village_id_arr];
            $village_info_arr = $db_house_village->getColumn($whereVillage, 'village_id, village_name', 'village_id');
        } else {
            $village_info_arr = [];
        }
        $carList1 = [];
        $carKey = 0;
        if (!empty($new_pay_order_list)) {
            if (!is_array($new_pay_order_list)) {
                $new_pay_order_list = $new_pay_order_list->toArray();
            }
            foreach ($new_pay_order_list as $item) {
                if ($item['pay_time']) {
                    $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
                } else {
                    $item['pay_time'] = '--';
                }
                $in_park  = isset($new_in_park_arr[$item['summary_id']]) && $new_in_park_arr[$item['summary_id']] ? $new_in_park_arr[$item['summary_id']] : [];
                $out_park = isset($new_out_park_arr[$in_park['order_id']]) && $new_out_park_arr[$in_park['order_id']] ? $new_out_park_arr[$in_park['order_id']] : [];
                if (!empty($in_park) && empty($out_park)) {
                    $accessIn  = isset($accessInInfoArr[$in_park['id']]) && $accessInInfoArr[$in_park['id']] ? $accessInInfoArr[$in_park['id']] : [];
                    $accessOut = isset($accessIn['order_id']) && isset($accessOutInfoArr[$accessIn['order_id']]) && $accessOutInfoArr[$accessIn['order_id']] ? $accessOutInfoArr[$accessIn['order_id']] : [];
                } else {
                    $accessIn  = [];
                    $accessOut = [];
                }
                $in_time  = isset($in_park['in_time'])   && $in_park['in_time']   ? $in_park['in_time']   : 0;
                $out_time = isset($out_park['out_time']) && $out_park['out_time'] ? $out_park['out_time'] : 0;
                if (!$in_time && isset($out_park['in_time']) && $out_park['in_time']) {
                    $in_time = $out_park['in_time'];
                }
                if (!$in_time && isset($accessIn['accessTime']) && $accessIn['accessTime']) {
                    $in_time = $accessIn['accessTime'];
                }
                if (!$out_time && isset($in_time['out_time']) && $in_time['out_time']) {
                    $out_time = $in_time['out_time'];
                }
                if (!$out_time && isset($accessOut['accessTime']) && $accessOut['accessTime']) {
                    $out_time = $accessOut['accessTime'];
                }
                $standing_time = $out_time - $in_time;
                if ($standing_time > 0) {
                    $item['standing_time'] = $standing_time ? round_number(($standing_time) / 3600, 1) . '小时' : '无';
                } else {
                    $item['standing_time'] = '无';
                }
                $village_name = isset($village_info_arr[$item['village_id']]) && $village_info_arr[$item['village_id']] ? $village_info_arr[$item['village_id']]['village_name'] : '';

                switch ($item['pay_type']){
                    case 0:
                        $item['pay_type'] = '余额支付';
                        break;
                    case 1:
                        $item['pay_type'] = '扫码支付';
                        break;
                    case 2:
                        $item['pay_type'] = '线下支付';
                        break;
                    case 3:
                        $item['pay_type'] = '收款码支付';
                        break;
                    case 4:
                        $item['pay_type'] = '线上支付';
                        break;
                }
                $item['park_name']  = $village_name;
                $item['pay_type']   = empty($item['pay_type']) ? '线上支付' : $item['pay_type'];
                $item['pay_status'] = '支付成功';
                $item['color']      = 'red';
                switch ($item['car_type']) {
                    case 'temporary_type':
                        $item['car_type_text'] = '临时车缴费';
                        break;
                    case 'month_type':
                        $item['car_type_text'] = '月租车充值';
                        break;
                    case 'stored_type':
                        $item['car_type_text'] = '停车卡储值';
                        break;
                    default:
                        $item['car_type_text'] = '无';
                        break;
                }
                if (!$item['car_number'] && $item['car_id'] && isset($car_info_arr[$item['car_id']])) {
                    $item['car_number'] = $car_info_arr[$item['car_id']]['province'] . $car_info_arr[$item['car_id']]['car_number'];
                }
                $carList1[$carKey] = [];
                $carList1[$carKey][]  = [
                    'key'   => '停车场名称',
                    'value' => $item['park_name'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '支付状态',
                    'value' => $item['pay_status'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '车牌号',
                    'value' => $item['car_number'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '停车时长',
                    'value' => $item['standing_time'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '支付金额',
                    'value' => $item['total_money'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '支付时间',
                    'value' => $item['pay_time'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '支付方式',
                    'value' => $item['pay_type'],
                ];
                $carList1[$carKey][]  = [
                    'key'   => '缴费类型',
                    'value' => $item['car_type_text'],
                ];
                $carKey++;
            }
        }
        if (!empty($pay_order_list)) {
            if (!is_array($pay_order_list)) {
                $pay_order_list = $pay_order_list->toArray();
            }
            if (!empty($pay_order_list)) {
                foreach ($pay_order_list as $k => $val) {
                    if ($val['pay_time']) {
                        $val['pay_time'] = date('Y-m-d H:i:s', $val['pay_time']);
                    } else {
                        $val['pay_time'] = '--';
                    }
                    $in_park  = isset($in_park_arr[$val['order_id']])      && $in_park_arr[$val['order_id']]      ? $in_park_arr[$val['order_id']]      : [];
                    $out_park = isset($out_park_arr[$in_park['order_id']]) && $out_park_arr[$in_park['order_id']] ? $out_park_arr[$in_park['order_id']] : [];
                    if (!empty($in_park)) {
                        $in_time  = isset($in_park['in_time'])   && $in_park['in_time']   ? $in_park['in_time']   : $out_park['in_time'];
                        $out_time = isset($out_park['out_time']) && $out_park['out_time'] ? $out_park['out_time'] : $in_park['out_time'];
                        $standing_time = $out_time - $in_time;
                        $val['standing_time'] = $standing_time ? round_number(($standing_time) / 3600, 1) . '小时' : '无';
                        $val['car_number']    = $in_park['car_number'];
                    } else {
                        $val['standing_time'] = 0;
                        $val['number'] = '';
                    }
                    $village_name = isset($village_info_arr[$val['village_id']]) && $village_info_arr[$val['village_id']] ? $village_info_arr[$val['village_id']]['village_name'] : '';

                    $val['park_name'] = $village_name;
                    $val['pay_type'] = empty($val['pay_type']) ? '线上支付' : $val['pay_type'];
                    $val['pay_status'] = '支付成功';
                    $val['color'] = 'red';
                    switch ($val['car_type']) {
                        case 'temporary_type':
                            $val['car_type_text'] = '临时车缴费';
                            break;
                        case 'month_type':
                            $val['car_type_text'] = '月租车充值';
                            break;
                        case 'stored_type':
                            $val['car_type_text'] = '停车卡储值';
                            break;
                        default:
                            $val['car_type_text'] = '无';
                            break;
                    }
                    if (!$val['car_number'] && $val['car_id'] && isset($car_info_arr[$val['car_id']])) {
                        $val['car_number'] = $car_info_arr[$val['car_id']]['province'] . $car_info_arr[$val['car_id']]['car_number'];
                    }
                    $carList1[$carKey] = [];
                    $carList1[$carKey][]  = [
                        'key'   => '停车场名称',
                        'value' => $val['park_name'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '支付状态',
                        'value' => $val['pay_status'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '车牌号',
                        'value' => $val['car_number'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '停车时长',
                        'value' => $val['standing_time'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '支付金额',
                        'value' => $val['money'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '支付时间',
                        'value' => $val['pay_time'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '支付方式',
                        'value' => $val['pay_type'],
                    ];
                    $carList1[$carKey][]  = [
                        'key'   => '缴费类型',
                        'value' => $val['car_type_text'],
                    ];
                    $carKey++;
                }
            }
        }
        return $carList1;
    }

    /**
     * 定制停车-添加访客登记纪录
     * @author:zhubaodi
     * @date_time: 2021/8/21 15:37
     */
    public function add_visitor_list($data)
    {
        if (isset($data['phone'])) {
            $phone = $data['phone'];
        } else {
            throw new Exception('请输入手机号');
        }

        $db_house_visitor = new HouseVillageVisitor();
        $village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
        fdump_api(['line' => __LINE__, '$data' => $data, 'village_id' => $village_id], '00add_visitor_list'.date('H'),1);
        if($this->hasCloudIntercom() && in_array($data['uid'],array(274212))){
            throw new Exception('您的账户已经被禁用！');
        }
        $nowTime = time();
        if($data['uid']>0){
            $db_user = new User();
            $user_info=$db_user->getOne(['uid'=>$data['uid'],'status'=>1],'nickname,phone,uid');
            if(empty($user_info) || $user_info->isEmpty()){
                throw new Exception('您的账户已经被禁用了！');
            }
            $add_time_temp=$nowTime-600;
            $whereTmpArr=array(['village_id','=',$village_id],['uid','=',$data['uid']],['is_car','=',1],['add_time','>',$add_time_temp]);
            $visitorinfo=$db_house_visitor->get_one($whereTmpArr,'*');
            if($visitorinfo && !$visitorinfo->isEmpty()){
                throw new Exception('您已经添加过临时车辆【'.$visitorinfo['car_id'].'】登记进场了，请不要重复登记！');
            }
        }
        $add_data = [
            'uid' => $data['uid'],
            'village_id' => $village_id,
            'visitor_name' => $data['username'],
            'visitor_phone' => $data['phone'],
            'is_car' => 1,
            'car_id' => $data['car_id'],
            'pass_time' => $nowTime,
            'add_time' => $nowTime,
            'owner_uid' => 0,
            'owner_name' => '',
            'owner_phone' => '',
            'owner_address' => '',
            'memo' => '',
            'owner_bind_id' => 0,
            'single_id' => 0,
            'floor_id' => 0,
            'layer_id' => 0,
            'vacancy_id' => 0,
            'visitor_bind_id' => 0,
        ];
        $is_visit_owner = isset($data['is_visit_owner']) ? intval($data['is_visit_owner']) : 0;
        fdump_api(['line' => __LINE__, 'village_id' => $village_id], '00add_visitor_list'.date('H'),1);
        $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($village_id);
        if ($deviceQrCodeJudge && $is_visit_owner != 1) {
            $is_visit_owner = 1;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        if ($is_visit_owner >= 1) {
            // 添加访客身份
            $visit_owner_switch = isset($data['visit_owner_switch']) ? trim($data['visit_owner_switch']) : '';
            if ($visit_owner_switch === 'true' || $visit_owner_switch === '1') {
                $add_data['visit_owner_switch'] = 2;
            } else {
                $add_data['visit_owner_switch'] = 1;
            }
            $vacancy_id = isset($data['vacancy_id']) ? intval($data['vacancy_id']) : 0;
            $layer_id = isset($data['layer_id']) ? intval($data['layer_id']) : 0;
            $floor_id = isset($data['floor_id']) ? intval($data['floor_id']) : 0;
            $single_id = isset($data['single_id']) ? intval($data['single_id']) : 0;


            fdump_api(['line' => __LINE__, 'village_id' => $village_id], '00add_visitor_list'.date('H'),1);
            if ($add_data['visit_owner_switch'] == 2) {
                $whereRoom = [];
                $whereRoom[] = ['pigcms_id', '=', $vacancy_id];
                $whereRoom[] = ['village_id', '=', $village_id];
                $vacancyInfo = $db_house_village_user_vacancy->getOne($whereRoom, 'pigcms_id, village_id, single_id, floor_id, layer_id');
                if ($vacancyInfo && !is_array($vacancyInfo)) {
                    $vacancyInfo = $vacancyInfo->toArray();
                }
                if (empty($vacancyInfo)) {
                    throw new Exception('请选择对应正常的房屋');
                }
                if (!$vacancy_id || !$layer_id || !$floor_id || !$single_id) {
                    $single_id = isset($vacancyInfo['single_id']) ? intval($vacancyInfo['single_id']) : 0;
                    $floor_id = isset($vacancyInfo['floor_id']) ? intval($vacancyInfo['floor_id']) : 0;
                    $layer_id = isset($vacancyInfo['layer_id']) ? intval($vacancyInfo['layer_id']) : 0;
                    $vacancy_id = isset($vacancyInfo['pigcms_id']) ? intval($vacancyInfo['pigcms_id']) : 0;
                }

                if (!$vacancy_id || !$layer_id || !$floor_id || !$single_id) {
                    throw new Exception('请选择对应正常的房屋');
                }
                fdump_api(['line' => __LINE__, '$vacancy_id' => $vacancy_id], '00add_visitor_list'.date('H'),1);
            }

            fdump_api(['line' => __LINE__, '$vacancy_id' => $vacancy_id, 'add_data' => $add_data], '00add_visitor_list'.date('H'),1);

            if ($add_data['visit_owner_switch'] == 1) {
                // 访问非业主权限
                $authParams = ['village_id' => $village_id];
                $visitToDevices = (new HouseDeviceQrcodeService())->getAllOpenDeviceAuth($authParams);
                $visitToDeviceString = implode(',', $visitToDevices);
                $add_data['visit_to_devices'] = $visitToDeviceString;
                $single_id = $floor_id = $layer_id = $vacancy_id = 0;
            }
            $sHouseFaceService = new HouseFaceService();
            if ($vacancy_id > 0) {
                $whereVacancyOwner = [];
                $whereVacancyOwner[] = ['vacancy_id', '=', $vacancy_id];
                $whereVacancyOwner[] = ['status', '=', 1];
                $whereVacancyOwner[] = ['type', 'in', [0,3]];
                $vacancyOwnerInfo = $db_house_village_user_bind->getOne($whereVacancyOwner, 'pigcms_id, village_id, name, phone, uid');
                $owner_uid = isset($vacancyOwnerInfo['uid']) && $vacancyOwnerInfo['uid'] ? intval($vacancyOwnerInfo['uid']) : 0;
                $owner_name = isset($vacancyOwnerInfo['name']) && $vacancyOwnerInfo['name'] ? trim($vacancyOwnerInfo['name']) : '';
                $owner_phone = isset($vacancyOwnerInfo['phone']) && $vacancyOwnerInfo['phone'] ? trim($vacancyOwnerInfo['phone']) : '';
                if ($owner_uid <= 0 && ($owner_name || $owner_phone)) {
                    $owner_uid = $sHouseFaceService->getFaceToRegUserUid($owner_name, $owner_phone);
                }
                $add_data['owner_uid'] = $owner_uid;
                $add_data['owner_name'] = $owner_name;
                $add_data['owner_phone'] = $owner_phone;
                if ($village_id > 0 && $single_id > 0 && $floor_id > 0 && $layer_id > 0 && $vacancy_id > 0) {
                    $service_house_village = new HouseVillageService();
                    $address = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $vacancy_id, $village_id);
                    $address && $add_data['owner_address'] = $address;
                }
            }

            $add_data['village_id'] = $village_id;
            $add_data['single_id'] = $single_id;
            $add_data['floor_id'] = $floor_id;
            $add_data['layer_id'] = $layer_id;
            $add_data['vacancy_id'] = $vacancy_id;
            fdump_api(['line' => __LINE__, 'village_id' => $village_id, 'add_data' => $add_data], '00add_visitor_list'.date('H'),1);

            $name = isset($add_data['visitor_name']) ? trim($add_data['visitor_name']) : '';
            $phone = isset($add_data['visitor_phone']) ? trim($add_data['visitor_phone']) : '';
            $uid = $sHouseFaceService->getFaceToRegUserUid($name, $phone);
            $nowTimeText = date('YmdHis', $nowTime);
            $usernum = md5(uniqid() . $nowTime) . $nowTimeText;
            $arr = [
                'village_id' => $village_id, 'uid' => $uid, 'phone' => $phone, 'name' => $name, 'type' => 6,
                'usernum' => $usernum, 'single_id' => $single_id, 'floor_id' => $floor_id, 'layer_id' => $layer_id,
                'vacancy_id' => $vacancy_id, 'add_time' => $nowTime
            ];
            // 默认车辆入场后开门权限一直有效 离场后失效
            $property_starttime = strtotime(date('Y-m-d 00:00:00', $nowTime));
            $property_endtime = 2145887999;
            $arr['property_starttime'] = $property_starttime;
            $arr['property_endtime'] = $property_endtime;
            $visitor_bind_id = $db_house_village_user_bind->addOne($arr);
            $visitor_bind_id && $add_data['visitor_bind_id'] = $visitor_bind_id;
        }
        $id = $db_house_visitor->addOne($add_data);
        if ($id > 0) {
            $this->addParkOpenLog($data);
            isset($visitor_bind_id) && $visitor_bind_id && (new HouseDeviceQrcodeService())->addDeviceQrCode($visitor_bind_id);
            (new CarHandleGetInfoService())->clearPassInfo($add_data['village_id']);
        }
        $res['id']=$id;
        $res['url']=get_base_url('pages/village_menu/index');
        return $res;

    }


    public function addParkOpenLog($data){
        fdump_api([$data], 'addParkOpenLog_0217', 1);
        $db_park_open_log = new ParkOpenLog();
        $db_park_total_record = new ParkTotalRecord();
        $db_park_config = new HouseVillageParkConfig();
        $db_park_passage = new ParkPassage();
        $village_id = intval($data['village_id']);
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $house_village_park_config = $houseVillageParkGarageConfigService->getVillageParkConfig(0, $village_id);
        $temp_in_park_type=0;
        if($house_village_park_config && isset($house_village_park_config['temp_in_park_type'])){
            $temp_in_park_type=intval($house_village_park_config['temp_in_park_type']);
        }
        $park_sys_type = isset($house_village_park_config['park_sys_type']) ? trim($house_village_park_config['park_sys_type']) : '';
        $car_number = trim($data['car_id']);
        $province = mb_substr($car_number, 0, 1);
        $sCarHandleGetInfoService = new CarHandleGetInfoService();
        if ($province != 'L' && $province != '临' && $province != '无') {

            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('park_sys_type', '=', 'D7');
            $park_config_d7_info = $houseVillageParkGarageConfigService->getListData($whereArr, '*', 'id DESC', 1, 1);
            $d7_park_config = array();
            if ($park_config_d7_info && !empty($park_config_d7_info)) {
                $d7_park_config = $park_config_d7_info['0'];
            }
            if ($d7_park_config && $d7_park_config['park_sys_type'] == 'D7') {
                $visitor_data = [];
                $visitor_data['parkId'] = $d7_park_config['d7_park_id'];
                $visitor_data['mobileNumber'] = $data['phone'];
                $visitor_data['appointmentVehicle'] = $car_number;
                $visitor_data['receptionist'] = $data['username'];
                $visitor_data['visitorName'] = $data['username'];
                $visitor_data['appointmentReason'] = '登记入场';
                $visitor_data['appointmentDate'] = time();
                $visitor_data['source'] = 2;
                (new QinLinCloudService())->visitor($visitor_data);
            } else {
                $houseVillageCarFlowService = new HouseVillageCarFlowService();
                $whereFlow = [];
                $whereLog = [];
                $limitTop = time() + 100;
                $limitLow = time() - 300;
                $whereFlow[] = ['flow_time', '>=', $limitLow];
                $whereFlow[] = ['flow_time', '<=', $limitTop];
                $whereLog[] = ['add_time', '>=', $limitLow];
                $whereLog[] = ['add_time', '<=', $limitTop];
                $recordLogParam = [
                    'car_number' => $car_number, 'garage_id' => 0, 'flow_direction' => 1, 'park_sys_type' => $park_sys_type,
                ];
                $log_info = $sCarHandleGetInfoService->getFlowIdentifyRecord($village_id,$recordLogParam, $whereFlow, $whereLog);
                fdump_api([$data,$log_info],'addParkOpenLog_0217',1);
                $channel_id='';
                if(isset($data['channel_id']) && $data['channel_id']){
                    $channel_id=$data['channel_id'];
                }
                if (!empty($log_info)) {
                    $device_number='';
                    if(isset($log_info['channel_id']) && $log_info['channel_id']){
                        $device_number=$log_info['channel_id'];
                        if(empty($channel_id)){
                            $channel_id=$log_info['channel_id'];
                        }
                    }
                    if($data['village_id']<1 && $log_info['village_id']>0){
                        $data['village_id']=$log_info['village_id'];
                        $village_id=$log_info['village_id'];
                    }
                    $passage_info=array();
                    $park_config_info=array();
                    $garage_id=0;
                    if($channel_id){
                        $whereArr=['status' => 1];
                        if(!empty($data['channel_id'])){
                            $whereArr['channel_number']=$data['channel_id'];
                        }
                        if($device_number){
                            $whereArr['device_number']=$device_number;
                        }
                        if($data['village_id']>0){
                            $whereArr['village_id']=$data['village_id'];
                        }
                        $passage_info_obj = $db_park_passage->getFind($whereArr,'*');
                        if($passage_info_obj && !$passage_info_obj->isEmpty()){
                            $passage_info=$passage_info_obj->toArray();
                            if($data['village_id']<1 && $passage_info['village_id']>0){
                                $data['village_id']=$passage_info['village_id'];
                                $village_id=$passage_info['village_id'];
                            }
                            if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
                                $garage_id=$passage_info['garage_id'];
                            }elseif ($passage_info['area_type']==2 && !empty($passage_info['passage_area'])){
                                $garage_id= intval($passage_info['passage_area']);
                            }
                            if($garage_id>0){
                                $park_config_info=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
                                $park_sys_type = isset($park_config_info['park_sys_type']) ? trim($park_config_info['park_sys_type']) : '';
                                if($park_config_info && isset($park_config_info['temp_in_park_type'])){
                                    $temp_in_park_type= intval($park_config_info['temp_in_park_type']);
                                }
                            }
                        }
                    }
                    fdump_api([$data,'passage_info'=>$passage_info,'channel_id'=>$channel_id,'garage_id'=>$garage_id,'temp_in_park_type'=>$temp_in_park_type],'addParkOpenLog_0217',1);
                    if (in_array($park_sys_type,['D3','A11'])&& $temp_in_park_type==1){
                        $park_log_data = [];
                        $park_log_data['car_number'] = $log_info['car_number'];
                        $park_log_data['channel_id'] = $log_info['channel_id'];
                        $park_log_data['park_type'] = $log_info['park_type'];
                        $park_log_data['add_time'] = time();
                        $park_log_data['open_type'] = 3;
                        $id=$db_park_open_log->add($park_log_data);
                        if (!empty($passage_info) && isset($passage_info['device_number'])) {
                            fdump_api(['passage_info'=>$passage_info], 'addParkOpenLog_0217', 1);
                            if ($passage_info['passage_direction'] == 0) {
                                $park_type = 2;
                            } else {
                                $park_type = 1;
                            }
                            $data_screen = [
                                'passage' => $passage_info,
                                'car_type' => 'temporary_type',
                                'village_id' => $data['village_id'],
                                'car_number' => $data['car_id'],
                                'channel_id' => $passage_info['device_number'],
                                'content' => '欢迎光临',
                                'voice_content' => 1
                            ];
                            if ($park_sys_type == 'A11') {
                                (new CarFlowShowScreenService())->addA11ParkShowScreenLog($data_screen);
                            } elseif ($park_sys_type == 'D3') {
                                (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
                            } else {
                                $this->addParkShowScreenLog($data_screen);
                            }
                        }
                        fdump_api([$data,$log_info,$id],'addParkOpenLog_0217',1);
                    }elseif($park_sys_type=='A1'){
                        $json_data['service_name'] = 'operate_gate';
                        $json_data['park_id'] = $data['village_id'];
                        $json_data['channel_id'] = $log_info['channel_id'];
                        $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                        $json_data['command'] = 1;
                        $post_data['unserialize_desc'] = serialize($json_data);
                        $post_data['park_id'] = $data['village_id'];
                        $post_data['service_name'] = 'operate_gate';
                        $post_data['car_number'] = $log_info['car_number'];
                        $post_data['create_time'] = time();
                        $post_data['village_id'] = $data['village_id'];
                        $post_data['msg_id'] =  $json_data['msg_id'];
                        $id=$db_park_total_record->add($post_data);//临时车C登记完成后, 平台发送远程开闸命令给设备
                        fdump_api([$data,$log_info,$id],'addParkOpenLog_0217',1);
                    }
                }
            }

        }else{
            if (!empty($data['channel_id'])){
                //查询设备
                $passage_info=array();
                $park_config_info=array();
                $passage_info_obj = $db_park_passage->getFind(['channel_number' => $data['channel_id'], 'village_id'=>$data['village_id'],'status' => 1]);
                if($passage_info_obj && !$passage_info_obj->isEmpty()){
                    $passage_info=$passage_info_obj->toArray();
                    $garage_id=0;
                    if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
                        $garage_id=$passage_info['garage_id'];
                    }elseif ($passage_info['area_type']==2 && !empty($passage_info['passage_area'])){
                        $garage_id= intval($passage_info['passage_area']);
                    }
                    if($garage_id>0){
                        $park_config_info=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
                        if($park_config_info && isset($park_config_info['temp_in_park_type'])){
                            $temp_in_park_type= intval($park_config_info['temp_in_park_type']);
                        }
                    }
                }
                fdump_api([$data,$passage_info],'addParkOpenLog_0217',1);
                if ($passage_info['passage_direction']==0){
                    $park_type=2;
                }else{
                    $park_type=1;
                }
                if (in_array($passage_info['park_sys_type'],['D3','A11'])&& $temp_in_park_type==1){
                    $park_log_data = [];
                    $park_log_data['car_number'] = $data['car_id'];
                    $park_log_data['channel_id'] = $passage_info['device_number'];
                    $park_log_data['park_type'] = $park_type;
                    $park_log_data['add_time'] = time();
                    $park_log_data['open_type'] = 3;
                    $id=$db_park_open_log->add($park_log_data);
                    $data_screen=[
                        'passage'=>$passage_info,
                        'car_type'=>'temporary_type',
                        'village_id'=>$data['village_id'],
                        'car_number'=>$data['car_id'],
                        'channel_id'=>$passage_info['device_number'],
                        'content'=>'欢迎光临',
                        'voice_content'=>1
                    ];
                    if ($park_sys_type == 'A11') {
                        (new CarFlowShowScreenService())->addA11ParkShowScreenLog($data_screen);
                    } elseif ($park_sys_type == 'D3') {
                        (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
                    } else {
                        $this->addParkShowScreenLog($data_screen);
                    }
                    fdump_api([$data,$park_log_data,$id],'addParkOpenLog_0217',1);
                }
                elseif($passage_info['park_sys_type']=='A1'){
                    $park_system = M('Park_system')->where(array('park_id'=>$data['village_id']))->find();
                    if(!empty($park_system)){
                        $nolicence_data=[];
                        $nolicence_data['uid'] = $data['uid'];
                        $nolicence_data['car_number'] = $data['car_id'];
                        $nolicence_data['is_out_park'] = 0;
                        $nolicence_data['token'] = '';
                        $nolicence_data['village_id'] =$data['village_id'];
                        $nolicence_data['park_id'] = $data['village_id'];
                        $nolicence_data['channel_id'] = $data['channel_id'];
                        $nolicence_data['is_in_park'] = 0;
                        $db_nolicence_in_park=new NolicenceInPark();
                        $db_nolicence_in_park->insertOne($nolicence_data);

                        $json_data['service_name'] = 'nolicence_in_park';
                        $json_data['car_number'] = $data['car_id'];//车牌
                        $json_data['park_id'] = $data['village_id'];
                        $json_data['channel_id'] = $data['channel_id'];
                        $json_data['timetemp'] = time();

                        $post_data['unserialize_desc'] = serialize($json_data);
                        $post_data['park_id'] = $data['village_id'];
                        $post_data['token'] = $park_system['token'];
                        $post_data['service_name'] = 'nolicence_in_park';
                        $post_data['create_time'] = time();
                        $post_data['village_id'] = $data['village_id'];
                        $post_data['car_number'] = $data['car_id'];
                        $id=$db_park_total_record->add($post_data);//无牌车入场
                        /* $json_data['service_name'] = 'operate_gate';
                         $json_data['park_id'] = $data['village_id'];
                         $json_data['channel_id'] = $data['channel_id'];
                         $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                         $json_data['command'] = 1;
                         $post_data['unserialize_desc'] = serialize($json_data);
                         $post_data['park_id'] = $data['village_id'];
                         $post_data['service_name'] = 'operate_gate';
                         $post_data['car_number'] = $data['car_id'];
                         $post_data['create_time'] = time();
                         $post_data['village_id'] = $data['village_id'];
                         $post_data['msg_id'] =  $json_data['msg_id'];
                         $id=$db_park_total_record->add($post_data);//临时车C登记完成后, 平台发送远程开闸命令给设备*/
                        fdump_api([$data,$post_data,$id],'addParkOpenLog_0217',1);
                    }

                }
                elseif($passage_info['park_sys_type']=='D7'){
                    $nolicence_data=[];
                    $nolicence_data['uid'] = $data['uid'];
                    $nolicence_data['car_number'] = $data['car_id'];
                    $nolicence_data['is_out_park'] = 0;
                    $nolicence_data['token'] = '';
                    $nolicence_data['village_id'] =$data['village_id'];
                    $nolicence_data['park_id'] = $data['village_id'];
                    $nolicence_data['channel_id'] = $data['channel_id'];
                    $nolicence_data['is_in_park'] = 1;
                    $nolicence_data['park_sys_type'] = 'D7';
                    $db_nolicence_in_park=new NolicenceInPark();
                    $res= $db_nolicence_in_park->insertOne($nolicence_data);
                    if ($res>0){
                        $res1=(new QinLinCloudService())->noLicenseIn($park_config_info['d7_park_id'],$data['phone'],$passage_info['d7_channelId']);
                        fdump_api([$res1,$park_config_info['d7_park_id'],$data['phone'],$passage_info['d7_channelId']],'D7/noLicenseIn',1);
                    }

                }

            }
        }
        return true;
    }

    /**
     * 定制停车-添加访客登记纪录根据id
     * @author:zhubaodi
     * @date_time: 2021/8/21 15:37
     */
    public function add_visitor_id($data)
    {
        $db_park_passage = new ParkPassage();
        $db_park_config = new HouseVillageParkConfig();
        $db_house_visitor = new HouseVillageVisitor();
        $db_park_total_record = new ParkTotalRecord();
        $visitor_info = $db_house_visitor->get_one(['id' => $data['id']]);
        if (empty($visitor_info)) {
            throw new Exception('登记信息不存在');
        }
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $village_id = isset($visitor_info['village_id']) ? intval($visitor_info['village_id']) : 0;
        $nowTime = time();
        $add_data = [
            'uid' => $visitor_info['uid'], 'village_id' => $village_id, 'visitor_name' => $visitor_info['visitor_name'],
            'visitor_phone' => $visitor_info['visitor_phone'], 'is_car' => 1, 'car_id' => $visitor_info['car_id'],
            'pass_time' => $nowTime, 'add_time' => $nowTime, 'owner_uid' => 0, 'owner_name' => '', 'owner_phone' => '',
            'owner_address' => '', 'memo' => '', 'owner_bind_id' => 0, 'single_id' => 0, 'floor_id' => 0,
            'layer_id' => 0, 'vacancy_id' => 0, 'visitor_bind_id' => 0,
        ];

        $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($village_id);
        if ($deviceQrCodeJudge) {
            // 添加访客身份
            $visit_owner_switch = isset($visitor_info['visit_owner_switch']) ? intval($visitor_info['visit_owner_switch']) : '';

            $add_data['visit_owner_switch'] = $visit_owner_switch;
            
            $vacancy_id = isset($visitor_info['vacancy_id']) ? intval($visitor_info['vacancy_id']) : 0;
            $layer_id = isset($visitor_info['layer_id']) ? intval($visitor_info['layer_id']) : 0;
            $floor_id = isset($visitor_info['floor_id']) ? intval($visitor_info['floor_id']) : 0;
            $single_id = isset($visitor_info['single_id']) ? intval($visitor_info['single_id']) : 0;

            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $whereRoom = [];
            $whereRoom[] = ['pigcms_id', '=', $vacancy_id];
            $whereRoom[] = ['village_id', '=', $village_id];
            $vacancyInfo = $db_house_village_user_vacancy->getOne($whereRoom, 'pigcms_id, village_id, single_id, floor_id, layer_id');
            if ($vacancyInfo && !is_array($vacancyInfo)) {
                $vacancyInfo = $vacancyInfo->toArray();
            }
            if ($add_data['visit_owner_switch'] == 2 && empty($vacancyInfo)) {
                throw new Exception('之前的登记记录缺少正常房屋信息');
            }
            if (!$vacancy_id || !$layer_id || !$floor_id || !$single_id) {
                $single_id = isset($vacancyInfo['single_id']) ? intval($vacancyInfo['single_id']) : 0;
                $floor_id = isset($vacancyInfo['floor_id']) ? intval($vacancyInfo['floor_id']) : 0;
                $layer_id = isset($vacancyInfo['layer_id']) ? intval($vacancyInfo['layer_id']) : 0;
                $vacancy_id = isset($vacancyInfo['pigcms_id']) ? intval($vacancyInfo['pigcms_id']) : 0;
            }

            if ($add_data['visit_owner_switch'] == 2 && (!$vacancy_id || !$layer_id || !$floor_id || !$single_id)) {
                throw new Exception('之前的登记记录缺少正常房屋信息');
            }

            if ($add_data['visit_owner_switch'] == 1) {
                // 访问非业主权限
                $authParams = ['village_id' => $village_id];
                $visitToDevices = (new HouseDeviceQrcodeService())->getAllOpenDeviceAuth($authParams);
                $visitToDeviceString = implode(',', $visitToDevices);
                $add_data['visit_to_devices'] = $visitToDeviceString;
                $single_id = $floor_id = $layer_id = $vacancy_id = 0;
            }
            
            $add_data['village_id'] = $village_id;
            $add_data['single_id'] = $single_id;
            $add_data['floor_id'] = $floor_id;
            $add_data['layer_id'] = $layer_id;
            $add_data['vacancy_id'] = $vacancy_id;

            $name = isset($add_data['visitor_name']) ? trim($add_data['visitor_name']) : '';
            $phone = isset($add_data['visitor_phone']) ? trim($add_data['visitor_phone']) : '';
            $sHouseFaceService = new HouseFaceService();
            $uid = $sHouseFaceService->getFaceToRegUserUid($name, $phone);
            $nowTimeText = date('YmdHis', $nowTime);
            $usernum = md5(uniqid() . $nowTime) . $nowTimeText;
            $arr = [
                'village_id' => $village_id, 'uid' => $uid, 'phone' => $phone, 'name' => $name, 'type' => 6,
                'usernum' => $usernum, 'single_id' => $single_id, 'floor_id' => $floor_id, 'layer_id' => $layer_id,
                'vacancy_id' => $vacancy_id, 'add_time' => $nowTime
            ];
            $db_house_village_user_bind = new HouseVillageUserBind();
            // 默认车辆入场后开门权限一直有效 离场后失效
            $property_starttime = strtotime(date('Y-m-d 00:00:00', $nowTime));
            $property_endtime = 2145887999;
            $arr['property_starttime'] = $property_starttime;
            $arr['property_endtime'] = $property_endtime;
            $visitor_bind_id = $db_house_village_user_bind->addOne($arr);
            $visitor_bind_id && $add_data['visitor_bind_id'] = $visitor_bind_id;
        }
        $id = $db_house_visitor->addOne($add_data);
        if ($id > 0) {
            (new CarHandleGetInfoService())->clearPassInfo($add_data['village_id']);
            $where = [
                ['car_number', '=', $visitor_info['car_id']],
                ['park_type', '=', 1],
                ['add_time', '>=', time() - 150],
                ['add_time', '<=', time() + 100],
            ];
            $db_park_plateresult_log = new ParkPlateresultLog();
            $log_info = $db_park_plateresult_log->get_one($where);
            $db_park_open_log = new ParkOpenLog();
            if (!empty($log_info)) {
                //查询设备
                $passage_info=array();
                $park_config_info=array();
                $passage_info_obj = $db_park_passage->getFind(['channel_number' => $log_info['channel_id'], 'village_id'=>$data['village_id'],'status' => 1]);
                if($passage_info_obj && !$passage_info_obj->isEmpty()){
                    $passage_info=$passage_info_obj->toArray();
                    $garage_id=0;
                    if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
                        $garage_id=$passage_info['garage_id'];
                    }elseif ($passage_info['area_type']==2 && !empty($passage_info['passage_area'])){
                        $garage_id= intval($passage_info['passage_area']);
                    }
                    $park_config_info=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$visitor_info['village_id']);
                }
                if ($log_info['park_sys_type']=='D3' &&$park_config_info['temp_in_park_type']==1){
                    $park_log_data = [];
                    $park_log_data['car_number'] = $log_info['car_number'];
                    $park_log_data['channel_id'] = $log_info['channel_id'];
                    $park_log_data['park_type'] = $log_info['park_type'];
                    $park_log_data['add_time'] = time();
                    $db_park_open_log->add($park_log_data);
                    $data_screen=[
                        'passage'=>$passage_info,
                        'car_type'=>'temporary_type',
                        'village_id'=>$visitor_info['village_id'],
                        'car_number'=>$log_info['car_number'],
                        'channel_id'=>$log_info['channel_id'],
                        'content'=>'欢迎光临',
                        'voice_content'=>1
                    ];
                    if ($log_info['park_sys_type'] == 'A11') {
                        (new CarFlowShowScreenService())->addA11ParkShowScreenLog($data_screen);
                    } elseif ($log_info['park_sys_type'] == 'D3') {
                        (new CarFlowShowScreenService())->addParkShowScreenLog($data_screen);
                    } else {
                        $this->addParkShowScreenLog($data_screen);
                    }
                }elseif($log_info['park_sys_type']=='A1'){
                    $json_data['service_name'] = 'operate_gate';
                    $json_data['park_id'] = $log_info['village_id'];
                    $json_data['channel_id'] = $log_info['channel_id'];
                    $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                    $json_data['command'] = 1;
                    $post_data['unserialize_desc'] = serialize($json_data);
                    $post_data['park_id'] = $log_info['village_id'];
                    $post_data['service_name'] = 'operate_gate';
                    $post_data['car_number'] = $log_info['car_number'];
                    $post_data['create_time'] = time();
                    $post_data['village_id'] = $log_info['village_id'];
                    $post_data['msg_id'] = $json_data['msg_id'];
                    $db_park_total_record->add($post_data);//临时车C登记完成后, 平台发送远程开闸命令给设备
                }
            }
            isset($visitor_bind_id) && $visitor_bind_id && (new HouseDeviceQrcodeService())->addDeviceQrCode($visitor_bind_id);
        }
        return $id;
    }

    /**
     * api-获取token
     * @author:zhubaodi
     * @date_time: 2021/8/21 14:36
     */
    public function getToken()
    {
        $url = $this->url;
        $data = [
            'method' => 'auth',
            'AppID' => $this->AppID,
            'AppSecret' => $this->AppSecret,
        ];
        /*$headers = [
            "cache-control: no-cache",
            "content-type: multipart/form-data; boundary=---011000010111000001101001",
        ];*/
        $headers = [];
        $token = [];
        $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
        fdump_api(['获取token' . __LINE__, $res_arr, $url, $data, $headers], 'getTemporaryCarInfo', 1);
        if ($res_arr[0] == 200) {
            $res = json_decode($res_arr[1], true);
            if ($res['code'] == 200) {
                $token = $res['data']['token'];
            }
        }
        fdump_api(['获取token' . __LINE__, $token], 'getTemporaryCarInfo', 1);
        return $token;
    }

    /**
     * api-获取停车场列表
     * @author:zhubaodi
     * @date_time: 2021/8/21 14:38
     */
    public function parkingList()
    {
        $token = $this->getToken();
        $list = [];
        if (!empty($token)) {
            $url = $this->url;
            $data = [
                'method' => 'ParkingList',
                'page' => '',
                'size' => '',
            ];
            /*$headers = [
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=---011000010111000001101001",
                "token:".$token
            ];*/
            $headers = [
                "token:" . $token
            ];
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            // print_r($res_arr);exit;
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = $res['data'];
                }
            }
        }
        return $list;

    }

    /**
     * api-获取停车场列表
     * @author:zhubaodi
     * @date_time: 2021/8/21 14:38
     */
    public function CarParkLog($car_no)
    {
        $token = $this->getToken();
        $list = [];
        if (!empty($token)) {
            $url = $this->url;
            $data = [
                'method' => 'CarParkLog',
                'car_no' => '',
            ];
            $headers = [
                "token:" . $token
            ];
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = $res['data'];
                }
            }
        }
        return $list;

    }

    /**
     * api-临时车放行操作
     * @author:zhubaodi
     * @date_time: 2021/8/21 14:38
     */
    public function temporaryCarAllow($park_id, $car_no)
    {
        $token = $this->getToken();
        fdump_api(['临时车进场放行' . __LINE__, $token], 'getTemporaryCarInfo', 1);
        $list = '操作失败';
        if (!empty($token)) {
            $url = $this->url_v2;
            $data = [
                'method' => 'temporaryCarAllow',
                'park_id' => $park_id,
                'id' => $car_no,
            ];
            /* $headers = [
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=---011000010111000001101001",
                "token:".$token
            ];*/
            $headers = [
                "token:" . $token
            ];
            fdump_api(['临时车进场放行' . __LINE__, $url, $data, $headers], 'getTemporaryCarInfo', 1);
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            fdump_api(['临时车进场放行' . __LINE__, $res_arr], 'getTemporaryCarInfo', 1);
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = '操作成功';
                    fdump_api(['临时车进场放行' . __LINE__, $list, $res], 'getTemporaryCarInfo', 1);
                }
            }
        }
        fdump_api(['临时车进场放行' . __LINE__, $list], 'getTemporaryCarInfo', 1);
        return $list;

    }

    /**
     * api-临时车不放行操作
     * @author:zhubaodi
     * @date_time: 2021/8/21 14:38
     */
    public function temporaryCarProhibit($park_id, $car_no)
    {
        $token = $this->getToken();
        fdump_api(['临时车进场不放行' . __LINE__, $token], 'getTemporaryCarInfo', 1);
        $list = '操作失败';
        if (!empty($token)) {
            $url = $this->url_v2;
            $data = [
                'method' => 'temporaryCarProhibit',
                'park_id' => $park_id,
                'id' => $car_no,
            ];
            /* $headers = [
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=---011000010111000001101001",
                "token:".$token
            ];*/
            $headers = [
                "token:" . $token
            ];
            fdump_api(['临时车进场不放行' . __LINE__, $url, $data, $headers], 'getTemporaryCarInfo', 1);
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            fdump_api(['临时车进场不放行' . __LINE__, $res_arr], 'getTemporaryCarInfo', 1);
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = '操作成功';
                    fdump_api(['临时车进场不放行' . __LINE__, $res, $list], 'getTemporaryCarInfo', 1);
                }
            }
        }
        fdump_api(['临时车进场不放行' . __LINE__, $list], 'getTemporaryCarInfo', 1);
        return $list;

    }

    /**
     * api-获取进场临时车信息
     * @author:zhubaodi
     * @date_time: 2021/8/21 14:38
     */
    public function getTempCarInfo($park_id, $deviceID)
    {
        $token = $this->getToken();
        fdump_api(['临时车进场获取信息' . __LINE__, $token], 'getTemporaryCarInfo', 1);
        $list = [];
        if (!empty($token)) {
            $url = $this->url_v2;
            $data = [
                'method' => 'getTemporaryCarInfo',
                'park_id' => $park_id,
                'deviceID' => $deviceID,
            ];
            /* $headers = [
                 "cache-control: no-cache",
                 "content-type: multipart/form-data; boundary=---011000010111000001101001",
                 "token:".$token
             ];*/
            $headers = [
                "token:" . $token
            ];
            fdump_api(['临时车进场获取信息' . __LINE__, $url, $data, $headers], 'getTemporaryCarInfo', 1);
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            fdump_api(['临时车进场获取信息' . __LINE__, $res_arr], 'getTemporaryCarInfo', 1);
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = $res['data'];
                    fdump_api(['临时车进场获取信息' . __LINE__, $res, $list, $res_arr], 'getTemporaryCarInfo', 1);
                }
            }
        }
        fdump_api(['临时车进场获取信息' . __LINE__, $list], 'getTemporaryCarInfo', 1);
        return $list;

    }

    public function getTemporaryCarInfo($data)
    {
        fdump_api(['临时车进场' . __LINE__, $data], 'getTemporaryCarInfo', 1);
        if (!empty($data['car_no']) && !empty($data['park_id'])) {
            $db_park_config = new HouseVillageParkConfig();
            $db_house_visitor = new HouseVillageVisitor();
            //$carInfo=$this->getTempCarInfo($data['park_id'],$data['deviceCodeIn']);
            $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
            $whereArr=array();
            $whereArr[]=array('village_id','>',0);
            $whereArr[]=array('comid','=',$data['park_id']);
            $whereArr[]=array('park_sys_type','=','D3');
            $parkGarageConfig=$houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr);
            $parkConfig=array();
            if($parkGarageConfig){
                $parkConfig=$parkGarageConfig;
            }
            fdump_api(['临时车进场' . __LINE__, $parkConfig], 'getTemporaryCarInfo', 1);
            if (!empty($parkConfig)) {
                $time = strtotime(date('Y-m-d 00:00:00', time()));
                $end_time = strtotime(date('Y-m-d 23:59:59', time()));
                fdump_api(['临时车进场' . __LINE__, $parkConfig], 'getTemporaryCarInfo', 1);
                $parkInfo = $db_house_visitor->get_one([['car_id', '=', $data['car_no']], ['village_id', '=', $parkConfig['village_id']], ['pass_time', '>=', $time], ['pass_time', '<=', $end_time]]);
                fdump_api(['临时车进场' . __LINE__, $parkInfo], 'getTemporaryCarInfo', 1);
                if (!empty($parkInfo)) {
                    fdump_api(['临时车进场' . __LINE__, $data], 'getTemporaryCarInfo', 1);
                    $res = $this->temporaryCarAllow($data['park_id'], $data['temporaryCarID']);
                    fdump_api(['临时车进场' . __LINE__, $res], 'getTemporaryCarInfo', 1);
                    return $res;
                }
            }
            fdump_api(['临时车进场' . __LINE__, $data], 'getTemporaryCarInfo', 1);
            $res = $this->temporaryCarProhibit($data['park_id'], $data['temporaryCarID']);
            fdump_api(['临时车进场' . __LINE__, $res], 'getTemporaryCarInfo', 1);
            return $res;
        }
        return '操作失败';
    }


    public function SetPushAddress()
    {
        $token = $this->getToken();
        $list = [];
        if (!empty($token)) {
            $url = $this->url_v2;
            $data = [
                'method' => 'SetTemporaryCarPushAddress',
                'url' => 'https://hz.huizhisq.com/index.php?g=Index&c=Index&a=getTemporaryCarInfo',
            ];
            /* $headers = [
                 "cache-control: no-cache",
                 "content-type: multipart/form-data; boundary=---011000010111000001101001",
                 "token:".$token
             ];*/
            $headers = [
                "token:" . $token
            ];
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = $res;
                }
            }
        }
        return $list;
    }


    public function getParkChargeRule()
    {
        $token = $this->getToken();
        $list = [];
        if (!empty($token)) {
            $url = $this->url_v2;
            $data = [
                'method' => 'getParkChargeRule',
                'park_id' => 108,
            ];
            $headers = [
                "token:" . $token
            ];
            $res_arr = http_request($url, 'POST', http_build_query($data), $headers);
            if ($res_arr[0] == 200) {
                $res = json_decode($res_arr[1], true);
                if ($res['code'] == 200) {
                    $list = $res;
                }
            }
        }
        return $list;

    }

    /**
     * D3-计算停车费
     * @author:zhubaodi
     * @date_time: 2021/11/3 16:15
     */
    public function get_temp_pay($data)
    {
        $car_number = isset($data['car_number']) && $data['car_number'] ? $data['car_number'] : '';
        fdump_api(['计算停车费', $data], 'D3Park/temp_pay_' . $car_number, 1);
        $db_in_park = new InPark();
        $db_house_village_park_charge = new HouseVillageParkCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_house_village_parking_car = new HouseVillageParkingCar();

        $garage_id = 0;
        if (isset($data['garage_id']) && $data['garage_id'] > 0) {
            $garage_id = $data['garage_id'];
        }
        $houseVillageParkGarageConfigService = new HouseVillageParkGarageConfigService();
        $park_config = $houseVillageParkGarageConfigService->getVillageParkConfig($garage_id, $data['village_id']);

        $where = [];
        $park_sys_type = 'D3';
        $where['car_number'] = $data['car_number'];
        $where['park_id'] = $data['village_id'];
        $where['park_sys_type'] = $park_sys_type;
        $where['is_paid'] = 0;
        $where['is_out'] = 0;
        $where['del_time'] = 0;
        $park_info = $db_in_park->getOne1($where);
        $park_count = $db_in_park->getCount(['car_number' => $data['car_number'], 'park_id' => $data['village_id']]);
        fdump_api([__LINE__, $park_info, $where, $park_count], 'D3Park/temp_pay_' . $car_number, 1);
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        if (empty($park_info) || $park_info->isEmpty()) {
            $houseVillage = new HouseVillage();
            $houseVillageInfo = $houseVillage->getOne(['village_id' => $data['village_id']], 'village_name');
            $park_name = '';
            if ($houseVillageInfo && !$houseVillageInfo->isEmpty()) {
                $park_name = $houseVillageInfo['village_name'];
            }
            if ($park_config['not_inPark_money'] > 0) {
                /***无入场记录收费**/
                $data_temp = [];
                $data_temp['village_id'] = $data['village_id'];
                $data_temp['add_time'] = time();
                $data_temp['free_out_time'] = 0;
                $data_temp['duration'] = 0;
                $data_temp['derate_money'] = 0;
                $data_temp['derate_duration'] = 0;
                $order_id = '330' . date('YmdHis') . rand(100, 999);
                $data_temp['order_id'] = $order_id;
                $data_temp['query_order_no'] = build_real_orderid($data['uid']);
                $data_temp['errmsg'] = '';
                $data_temp['price'] = $park_config['not_inPark_money'];
                $data_temp['total'] = $park_config['not_inPark_money'];
                $data_temp['in_time'] = 0;
                $data_temp['car_number'] = $data['car_number'];
                $data_temp['out_channel_id'] = isset($data['channel_number']) ? $data['channel_number'] : '';
                $data_temp['is_pay_scene'] = 0;
                $data_temp['park_sys_type'] = $park_sys_type;
                $data_temp['garage_id']=$garage_id;
                fdump_api([__LINE__, 'data_temp' => $data_temp], 'D3Park/temp_pay_' . $car_number, 1);
                $id1 = $db_house_village_park_temp->addOne($data_temp);
                fdump_api([__LINE__, 'id1' => $id1], 'D3Park/temp_pay_' . $car_number, 1);

                $list = [];
                $list['time'] = '--';
                $list['park_time'] = 0;
                $list['pay_money'] = $park_config['not_inPark_money'];
                $list['order_id'] = $order_id;
                $list['park_name'] = $park_name;
                $list['in_time'] = '--';
                $list['pay_time'] = time();
                $list['total_money'] = $park_config['not_inPark_money'];
                $list['coupon_money'] = 0;
                $list['coupon_id'] = 0;
                $list['parkTemp_money'] = 0;
                $list['park_temp_id'] = 0;
                fdump_api([__LINE__, 'list' => $list], 'D3Park/temp_pay_' . $car_number, 1);
                return $list;
            } else {
                fdump_api(['00计算停车费', 'err' => '没有停车纪录,直接开门'], 'D3Park/temp_pay_' . $car_number, 1);
                $list = [];
                $order_id = '300' . date('YmdHis') . rand(100, 999);
                $list['time'] = '--';
                $list['park_time'] = 0;
                $list['pay_money'] = 0;
                $list['order_id'] = $order_id;
                $list['park_name'] = $park_name;
                $list['in_time'] = '--';
                $list['pay_time'] = time();
                $list['total_money'] = 0;
                $list['coupon_money'] = 0;
                $list['coupon_id'] = 0;
                $list['parkTemp_money'] = 0;
                $list['park_temp_id'] = 0;
                return $list;
                //throw new \Exception('没有停车纪录');
            }
        }

        $where_rule = [
            ['village_id', '=', $data['village_id']],
            ['status', '=', 1],
            ['charge_valid_time', '<=', time()],
            ['fees_type', '=', 3],  //3临时车 4月租车
        ];
        $rule_info = $db_house_new_charge_rule->getOne($where_rule, '*', 'charge_valid_time DESC');
        if (empty($rule_info)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $rule_id=$rule_info['id'];
        $project_id=$rule_info['charge_project_id'];
        $where_out = [];
        $where_out['car_number'] = $data['car_number'];
        $where_out['park_id'] = $data['village_id'];
        $where_out['park_sys_type'] = $park_sys_type;
        $where_out['is_out'] = 1;
        $where_out['del_time'] = 0;
        $park_info1 = $db_in_park->getOne1($where_out);
        if (!empty($park_info1) && $park_info1['out_time'] > $park_info['in_time'] && $park_info1['out_time'] < time()) {
            $park_info['in_time'] = $park_info1['out_time'];
        }
        $where_charge = [];
        $where_charge['village_id'] = $data['village_id'];
        //$where_charge['park_sys_type'] = $park_sys_type;
        $where_charge['status'] = 1;
        $where_charge['id'] = $rule_info['park_charge_id'];
        $park_charge = $db_house_village_park_charge->getFind($where_charge);
        fdump_api([$park_charge, $where_charge, $park_info['in_time']], 'D3Park/temp_pay_' . $car_number, 1);
        if (empty($park_charge)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $now_time = time();
        $park_time = $now_time - $park_info['in_time'];
        $province = mb_substr($data['car_number'], 0, 1);
        $car_no = mb_substr($data['car_number'], 1);
        if(isset($data['temporary_end_time']) && $data['temporary_end_time']>100 ){
            $park_time=$data['temporary_end_time']-$park_info['in_time'];
        }else if(isset($data['car_end_time']) && $data['car_end_time']>100 && $park_info['in_time'] < $data['car_end_time'] && $now_time > $data['car_end_time']  ){
            $park_time = $now_time - $data['car_end_time'];
        }else{
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $data['village_id']]);
            fdump_api([$car_info], 'D3Park/temp_pay_' . $car_number, 1);
            if (!empty($car_info) && isset($car_info['end_time'])&& $car_info['end_time'] > 1 && $park_info['in_time'] < $car_info['end_time'] && $now_time > $car_info['end_time']) {
                $park_time = $now_time - $car_info['end_time'];
            }
        }
        $whereTemp = [];
        $whereTemp[] = ['car_number', '=', $data['car_number']];
        $whereTemp[] = ['village_id', '=', $data['village_id']];
        $whereTemp[] = ['pay_time', '>', 0];
        $parkTemp = $db_house_village_park_temp->getOne($whereTemp,true,'pay_time DESC,id DESC');
        $park_time_total=$park_time;
        
        $is_advance_pay=0;  //付过了，识别比心跳过来的快
        fdump_api([$parkTemp, $park_info['in_time'], $park_time], 'D3Park/temp_pay_' . $car_number, 1);
        if ($parkTemp && isset($parkTemp['pay_time']) && $parkTemp['pay_time']>$park_info['in_time']) {
            $parkTemp_money=$parkTemp['price'];
            $next_last_park_time=time()-$parkTemp['pay_time'];
            if($next_last_park_time<600 && $parkTemp['order_id']==$park_info['order_id']){
                //10分钟中内相同 入场记录如果有 支付记录 记下钱
                $is_advance_pay = 1;
            }
            if (empty($park_config['free_park_time'])) {
                if (time() - $parkTemp['pay_time'] < 900) {
                    $park_time = 0;
                }
            } else {
                if (time() - $parkTemp['pay_time'] < ($park_config['free_park_time'] * 60)) {
                    $park_time = 0;
                }
            }
        } else {
            $parkTemp_money = 0;
        }
        $park_charge_info = array();
        if ($park_charge['charge_type'] == 1) {
            $park_charge_info = unserialize($park_charge['charge_set']);
            $last_ages = array_column($park_charge_info, 'time');
            array_multisort($last_ages, SORT_DESC, $park_charge_info);
            fdump_api([__LINE__, 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'D3Park/temp_pay_' . $car_number, 1);
        } elseif ($park_charge['charge_type'] == 4) {
            //24小时逐时收费制
            $park_charge_info = unserialize($park_charge['charge_set']);
            fdump_api([__LINE__, 'park_charge_info' => $park_charge_info, 'park_time' => $park_time], 'D3Park/temp_pay_' . $car_number, 1);
            if (empty($park_charge_info)) {
                fdump_api([__LINE__, 'err' => '费用设置数据错误', 'park_charge' => $park_charge], 'D3Park/temp_pay_' . $car_number, 1);
                throw new \Exception('费用设置数据错误！');
            }
        }

        fdump_api([__LINE__, 'charge_type' => $park_charge['charge_type'], 'park_time' => $park_time, 'park_charge' => $park_charge, 'park_charge_info' => $park_charge_info], 'D3Park/temp_pay_' . $car_number, 1);
        $park_money = 0;
        $pay_money = 0;
        $coupon_id = 0;
        $total_money=0;
        if ($park_time > 0) {
            /***
             *新按次收费
             * 1连续多少小时只收取一次费(在原来charge_type是3 上面时间不一定是24了) 2按次收费（和 charge_type是2一样） 3当天只收取一次费(和 charge_type是3一样)
             */
            $charge_type5_set = isset($park_charge['charge_type5_set']) ? $park_charge['charge_type5_set'] : 0;

            $parkTime = ceil($park_time / 60);
            if ($parkTime <= $park_charge['free_time']) {
                $pay_money = 0;
            } else {
                if (isset($park_charge['free_time_no_count']) && $park_charge['free_time_no_count'] == 1 && $park_charge['free_time'] > 0) {
                    //1从免费时间后开始计算费用 
                    $park_time = $park_time - ($park_charge['free_time'] * 60);
                    $park_time = $park_time > 0 ? $park_time : 0;
                }
                $parkTime = ceil($park_time / 60);

                if ($park_count == 1 && !empty($park_charge['first_free_time']) && $parkTime <= $park_charge['first_free_time']) {
                    $pay_money = $park_charge['first_charge_money'];
                    fdump_api([__LINE__, 'pay_money' => $pay_money], 'D3Park/temp_pay_' . $car_number, 1);
                } else {
                    if ($park_charge['charge_type'] == 1) {
                        if (!empty($park_charge_info)) {
                            $parkTime1 = ceil($park_time / 3600);
                            fdump_api([__LINE__, 'parkTime1' => $parkTime1],'D3Park/temp_pay_' . $car_number, 1);
                            if ($park_charge['max_charge_money'] > 0 && $parkTime1 > 24) {
                                $level = intval($park_time / 86400);
                                $park_money = $level * $park_charge['max_charge_money'];
                                $park_time = $park_time - $level * 86400;
                                $parkTime1 = $parkTime1 - 24 * $level;
                                fdump_api([__LINE__, 'level' => $level, 'park_money' => $park_money, 'park_time' => $park_time, 'parkTime1' => $parkTime1],'D3Park/temp_pay_' . $car_number, 1);
                            }
                            if ($park_charge_info[0]['time'] < $parkTime1) {
                                $pay_money = $park_charge_info[0]['money'];
                                $parkTime2 = $park_time - $park_charge_info[0]['time'] * 3600;
                                fdump_api([__LINE__, 'pay_money' => $pay_money, 'parkTime2' => $parkTime2],'D3Park/temp_pay_' . $car_number, 1);
                                if ($park_charge['max_charge_money'] > 0) {
                                    /* if($parkTime1>24){
                                         $level=intval($park_time / 86400);
                                         $park_money=$level*$park_charge['max_charge_money'];
                                         $parkTime2=$park_time-$level*86400;
                                     }*/
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    fdump_api([__LINE__, 'parkTime3' => $parkTime3, 'park_money1' => $park_money1],'D3Park/temp_pay_' . $car_number, 1);
                                    if ($parkTime1 > 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $park_money += $park_charge['max_charge_money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                                    } elseif ($parkTime1 <= 24 && ($pay_money + $park_money1) > $park_charge['max_charge_money']) {
                                        $pay_money = $park_charge['max_charge_money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                                    } else {
                                        $park_money += $park_money1;
                                        fdump_api([__LINE__, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                                    }

                                } else {
                                    $parkTime3 = ceil($parkTime2 / ($park_charge['charge_time'] * 60));
                                    $park_money1 = $parkTime3 * $park_charge['charge_money'];
                                    $park_money += $park_money1;
                                    fdump_api([__LINE__, 'parkTime3' => $parkTime3, 'park_money1' => $park_money1, 'park_money' => $park_money], 'D3Park/temp_pay_' . $car_number, 1);
                                }
                            } else {
                                foreach ($park_charge_info as $k => $v) {
                                    if ($v['time'] == $parkTime1) {
                                        $pay_money = $v['money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                                        break;
                                    } elseif ($v['time'] < $parkTime1) {
                                        $pay_money = $park_charge_info[$k - 1]['money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                                        break;
                                    } elseif (!isset($park_charge_info[$k + 1]) && $v['time'] > $parkTime1) {
                                        $pay_money = $park_charge_info[$k]['money'];
                                        fdump_api([__LINE__, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                                        break;
                                    }
                                }
                            }
                        }
                    } elseif ($park_charge['charge_type'] == 4) {
                        $parkTimeHour = ceil($park_time / 3600); //算小时
                        $tmp_pay_money = 0;
                        do {
                            foreach ($park_charge_info as $kk => $vv) {
                                $new_key = $kk + 1;
                                if ($vv['hour'] < 24 && ($parkTimeHour >= $vv['hour']) && ($parkTimeHour < $park_charge_info[$new_key]['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if ($vv['hour'] == 1 && $parkTimeHour <= $vv['hour']) {
                                    $tmp_pay_money += $vv['fee'];
                                } else if (($vv['hour'] == 24) && ($parkTimeHour >= $vv['hour'])) {
                                    $tmp_pay_money += $vv['fee'];
                                }
                            }
                            $parkTimeHour = $parkTimeHour - 24;
                        } while ($parkTimeHour > 0);
                        $pay_money = round($tmp_pay_money, 2);
                    } elseif ($park_charge['charge_type'] == 2 || ($park_charge['charge_type'] == 5 && $charge_type5_set == 2)) {
                        $parkTime_charge2 = ceil($park_time / 86400);
                        $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                        fdump_api([__LINE__, 'parkTime_charge2' => $parkTime_charge2, 'park_money' => $park_money],'D3Park/temp_pay_' . $car_number, 1);
                    } else {
                        $parkTime_charge3 = $park_info['in_time'] + $park_time;
                        $oneDaySecond = 86400;  //24小时
                        if ($park_charge['charge_type'] == 5 && $charge_type5_set == 1 && $park_charge['hour_once_fee'] > 0) {
                            $oneDaySecond = $park_charge['hour_once_fee'] * 3600;  //自定义的
                        }
                        $pay_money=0;
                        $time_charge = strtotime(date('Y-m-d 00:00:00', $park_info['in_time'])) + $oneDaySecond;
                        fdump_api([__LINE__, 'parkTime_charge3' => $parkTime_charge3, 'time_charge' => $time_charge],'D3Park/temp_pay_' . $car_number, 1);
                        if ($time_charge < $parkTime_charge3) {
                            $parkTime_charge2 = ceil($park_time / $oneDaySecond);
                            $pay_money = $park_charge['max_charge_money'] * $parkTime_charge2;
                            fdump_api([__LINE__, 'parkTime_charge2' => $parkTime_charge2, 'pay_money' => $pay_money],'D3Park/temp_pay_' . $car_number, 1);
                        } elseif(empty($parkTemp) || !isset($parkTemp['price']) || ($parkTemp['price']<=0)) {
                            //第一次
                            $pay_money = $park_charge['max_charge_money'];
                            if($charge_type5_set == 3 || $charge_type5_set == 1){
                                $faccessTime=strtotime(date('Y-m-d 00:00:00'));
                                $recordWhere=[
                                    ['business_type','=','0'],
                                    ['business_id','=',$data['village_id']],
                                    ['accessType','=',2],
                                    ['car_number','=',$car_number],
                                    ['is_out','=',1],
                                ];
                                $recordWhere[]=['accessTime','>',$faccessTime];
                                $parkRecord = (new HouseVillageCarAccessRecord())->getOne($recordWhere, 'record_id,accessTime,car_number');
                                if($parkRecord && !$parkRecord->isEmpty()){
                                    $pay_money = 0;
                                }
                            }
                            fdump_api([__LINE__, 'park_money' => $park_money], 'D3Park/temp_pay_' . $car_number, 1);
                        }
                    }
                }
            }
            $pay_money = $pay_money + $park_money - $parkTemp_money;
            $pay_money=$pay_money>0 ? $pay_money:0;
            $total_money = $pay_money;
            if ($pay_money > 0) {
                $this->checkCouponUseStatus($data['village_id'], $data['car_number'], $park_sys_type);
                $db_house_village_park_coupon = new HouseVillageParkCoupon();
                $where_coupon = [];
                $where_coupon['village_id'] = $data['village_id'];
                $where_coupon['car_number'] = $data['car_number'];
                /*$where_coupon['user_id'] = $data['uid'];*/
                //$where_coupon['park_sys_type'] = $park_sys_type;
                $where_coupon['is_use'] = 0;
                $coupon_info = $db_house_village_park_coupon->getFind($where_coupon);
                if (!empty($coupon_info)) {
                    $coupon_info = $coupon_info->toArray();
                    fdump_api([$data['car_number'],'coupon_info'=>$coupon_info],'D3Park/get_temp_pay_log',1);
                    if (!empty($coupon_info)) {
                        $coupon_id = $coupon_info['id'];
                        $pay_money = $pay_money - $coupon_info['money'];
                        if ($pay_money <= 0) {
                            $pay_money = 0;
                        }
                        $db_house_village_park_coupon->saveOne($where_coupon,['is_use'=>1,'use_time'=>time()]);

                    } else {
                        $coupon_info['money'] = 0;
                    }

                } else {
                    $coupon_info['money'] = 0;
                }
            }
        } elseif ($park_time_total <= 0) {
            fdump_api([__LINE__, 'err' => '没有停车时长'], 'D3Park/temp_pay_' . $car_number, 1);
            throw new Exception('没有停车时长');
        }
        $data_temp = [];
        $data_temp['village_id'] = $data['village_id'];
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $park_charge['free_time'];
        $data_temp['duration'] = round_number($park_time_total / 60, 2);
        $data_temp['derate_money'] = isset($coupon_info['money']) ? $coupon_info['money'] : 0;
        $data_temp['derate_duration'] = 0;
        $data_temp['order_id'] = $park_info['order_id'];
        $data_temp['query_order_no'] = build_real_orderid($data['uid']);
        $data_temp['errmsg'] = '';
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $park_info['in_time'];
        $data_temp['car_number'] = $data['car_number'];
        $data_temp['out_channel_id'] = isset($data['channel_number']) ? $data['channel_number'] : '';
        $data_temp['is_pay_scene'] = 0;
        $data_temp['park_sys_type'] = $park_sys_type;
        $data_temp['coupon_id'] = $coupon_id;
        $data_temp['garage_id']=$garage_id;
        if ($pay_money == 0) {
            $data_temp['is_paid'] = 1;
            $data_temp['pay_time'] = time();
        }
        if (isset($data['in_record_id']) && $data['in_record_id']) {
            $data_temp['in_record_id'] = $data['in_record_id'];
        }
        $data_temp['rule_id']=$rule_id;
        $data_temp['project_id']=$project_id;
        $id1 = $db_house_village_park_temp->addOne($data_temp);
        fdump_api(['计算后记录临时表中', $data_temp], 'D3Park/temp_pay_' . $car_number, 1);

        $hours = intval($park_time_total / 3600);
        $mins = intval(($park_time_total - $hours * 3600) / 60);
        $second = $park_time_total - $hours * 3600 - $mins * 60;
        $list = [];
        $list['time'] = $hours . '小时' . $mins . '分钟' . $second . '秒';
        $list['park_time'] = $park_time_total;
        $list['pay_money'] = $pay_money;
        $list['order_id'] = $park_info['order_id'];
        $list['park_name'] = $park_info['park_name'];
        $list['in_time'] = date('Y-m-d H:i:s', $park_info['in_time']);
        $list['pay_time'] = time();
        $list['total_money'] = $total_money;
        $list['coupon_money'] = $coupon_info['money'];
        $list['coupon_id'] = $coupon_id;
	    $list['park_temp_id'] = $id1;
        $list['parkTemp_money'] = $parkTemp_money;
        $list['is_advance_pay'] = $is_advance_pay;
        fdump_api(['返回', $list], 'D3Park/temp_pay_' . $car_number, 1);
        return $list;
    }
    public function checkCouponUseStatus($village_id=0,$car_number='',$park_sys_type=''){
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $whereTemp = [];
        $whereTemp[] = ['village_id', '=', $village_id];
        $whereTemp[] = ['car_number', '=', $car_number];
        $whereTemp[] = ['is_paid', '=', 0];
        if($park_sys_type){
            $whereTemp[] = ['park_sys_type', '=', $park_sys_type];
        }
        $nowtime=time();
        $tmptime=$nowtime-36000;
        $whereTemp[] = array('add_time','<',$nowtime);
        $whereTemp[] = array('add_time','>',$tmptime);
        $whereTemp[] = ['coupon_id', '>', 0];
        $parkTemp = $db_house_village_park_temp->getOne($whereTemp,'id,coupon_id,duration,derate_money,price,total','id DESC');
        fdump_api([$car_number,'whereTemp'=>$whereTemp],'D3Park/get_temp_pay_log',1);
        if($parkTemp && !$parkTemp->isEmpty()){
            fdump_api([$car_number,'parkTemp'=>$parkTemp],'D3Park/get_temp_pay_log',1);
            $db_house_village_park_coupon = new HouseVillageParkCoupon();
            $whereArr=array('vpc.id'=>$parkTemp['coupon_id'],'vpc.village_id'=>$village_id,'sc.village_id'=>$village_id);
            $oneCoupon= $db_house_village_park_coupon->getOneCoupon($whereArr,'sc.*');
            $is_coupon_can_use=0;
            if($oneCoupon && !$oneCoupon->isEmpty() && $oneCoupon['cid']){
                $parkCouponsDb= new ParkCoupons();
                $whereCoupon=array('village_id'=>$village_id,'c_id'=>$oneCoupon['cid']);
                $parkCouponsObj=$parkCouponsDb->getFind($whereCoupon,'*');
                if($parkCouponsObj && !$parkCouponsObj->isEmpty() && $parkCouponsObj['status']==0){
                    $is_coupon_can_use=1;
                }
            }
            if($is_coupon_can_use>0){
                $where_coupon = [];
                $where_coupon['village_id'] =$village_id;
                $where_coupon['car_number'] = $car_number;
                $where_coupon['id'] = $parkTemp['coupon_id'];
                $saveArr=array('is_use'=>0,'use_time'=>0,'use_txt'=>'');
                $db_house_village_park_coupon->saveOne($where_coupon,$saveArr);
                return $parkTemp['coupon_id'];
            }
        }
        return true;
    }
    public function get_temp_pay_1($data)
    {
        $db_in_park = new InPark();
        $db_house_village_park_charge = new HouseVillageParkCharge();
        $db_house_new_charge_rule = new HouseNewChargeRule();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $where = [];
        $where['car_number'] = $data['car_number'];
        $where['park_id'] = $data['village_id'];
        $where['park_sys_type'] = 'D3';
        $where['is_paid'] = 0;
        $where['is_out'] = 0;
        $where['del_time'] = 0;
        $park_info = $db_in_park->getOne1($where);
        //   print_r($park_info);exit;
        if (empty($park_info)) {
            throw new Exception('没有停车纪录');
        }

        $where_rule=[
            ['village_id','=',$data['village_id'] ],
            ['status','=',1],
            ['charge_valid_time','<=',time()],

        ];
        $rule_info=$db_house_new_charge_rule->getOne($where_rule,'*','charge_valid_time DESC');
        if (empty($park_charge)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $rule_id=$rule_info['id'];
        $project_id=$rule_info['charge_project_id'];
        $where_charge = [];
        $where_charge['village_id'] = $data['village_id'];
        $where_charge['park_sys_type'] = 'D3';
        $where_charge['status'] = 1;
        $where_charge['id'] = $rule_info['park_charge_id'];
        $park_charge = $db_house_village_park_charge->getFind($where_charge);
        // print_r($park_charge);exit;
        if (empty($park_charge)) {
            throw new Exception('该停车场未绑定收费规则');
        }
        $province = mb_substr($data['car_number'], 0, 1);
        $car_no = mb_substr($data['car_number'], 1);
        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$data['village_id']]);
        if (!empty($car_info)&&$car_info['end_time']>1&&$park_info['in_time']<$car_info['end_time']){
            $park_time = time() - $car_info['end_time'];
        }else{
            $park_time = time() - $park_info['in_time'];
        }
        $park_charge_info = unserialize($park_charge['charge_set']);
        $last_ages=
        $last_ages = array_column($park_charge_info, 'time');
        array_multisort($last_ages ,SORT_DESC,$park_charge_info);
        $park_money=0;
        $pay_money = 0;
        if ($park_time > 0) {
            $parkTime = ceil($park_time / 60);
            if ($parkTime <= $park_charge['free_time']) {
                $pay_money = 0;
            } else {
                if (!empty($park_charge_info)) {
                    $parkTime1 = ceil($park_time / 3600);
                    if ($park_charge_info[0]['time']<$parkTime1){
                        $pay_money = $park_charge_info[0]['money'];
                        $parkTime2=$park_time-$park_charge_info[0]['time']*3600;
                        if ($park_charge['max_charge_money']>0){
                            if($parkTime1>24){
                                $level=intval($park_time / 86400);
                                $park_money=$level*$park_charge['max_charge_money'];
                                $parkTime2=$park_time-$level*86400;
                            }
                            $parkTime3=$parkTime2/$park_charge['charge_time']*60;
                            $park_money1=$parkTime3*$park_charge['charge_money'];
                            if (($pay_money+$park_money1)>$park_charge['max_charge_money']){
                                $park_money+=$park_charge['max_charge_money'];
                            }else{
                                $park_money+=$park_money1;
                            }

                        }else{
                            $parkTime3=$parkTime2/$park_charge['charge_time']*60;
                            $park_money1=$parkTime3*$park_charge['charge_money'];
                            $park_money+=$park_money1;
                        }
                    }else{
                        foreach ($park_charge_info as $k=>$v) {
                            if ($v['time'] == $parkTime1) {
                                $pay_money = $v['money'];
                                break;
                            }
                        }
                    }
                }
            }
            $pay_money=round_number($pay_money+$park_money,2);
            $total_money = $pay_money;
            if ($pay_money > 0) {
                $db_house_village_park_coupon = new HouseVillageParkCoupon();
                $where_coupon = [];
                $where_coupon['village_id'] = $data['village_id'];
                $where_coupon['car_number'] = $data['car_number'];
                /*$where_coupon['user_id'] = $data['uid'];*/
                $where_coupon['park_sys_type'] = 'D3';
                $where_coupon['is_use'] = 0;
                $coupon_info = $db_house_village_park_coupon->getFind($where_coupon);
                if (!empty($coupon_info)) {
                    $coupon_info = $coupon_info->toArray();
                    if (!empty($coupon_info)) {
                        $pay_money = $pay_money - $coupon_info['money'];
                        if ($pay_money < 0) {
                            $pay_money = 0;
                        }
                        $db_house_village_park_coupon->saveOne($where_coupon,['is_use'=>1]);

                    } else {
                        $coupon_info['money'] = 0;
                    }

                } else {
                    $coupon_info['money'] = 0;
                }
            }
        } else {
            throw new Exception('没有停车时长');
        }
        $data_temp = [];
        $data_temp['village_id'] = $data['village_id'];
        $data_temp['add_time'] = time();
        $data_temp['free_out_time'] = $park_charge['free_time'];
        $data_temp['duration'] = round_number($park_time / 60, 2);
        $data_temp['derate_money'] = isset($coupon_info['money']) ? $coupon_info['money'] : 0;
        $data_temp['derate_duration'] = 0;
        $data_temp['order_id'] = $park_info['order_id'];
        $data_temp['query_order_no'] = build_real_orderid($data['uid']);
        $data_temp['errmsg'] = '';
        $data_temp['price'] = $pay_money;
        $data_temp['total'] = $total_money;
        $data_temp['in_time'] = $park_info['in_time'];
        $data_temp['car_number'] = $data['car_number'];
        $data_temp['out_channel_id'] = $park_info['id'];
        $data_temp['park_sys_type'] = 'D3';
        $data_temp['rule_id']=$rule_id;
        $data_temp['project_id']=$project_id;
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        $db_house_village_park_temp->addOne($data_temp);

        $hours = intval($park_time / 3600);
        $mins = intval(($park_time - $hours * 3600) / 60);
        $second = $park_time - $hours * 3600 - $mins * 60;
        $list = [];
        $list['time'] = $hours . '小时' . $mins . '分钟' . $second . '秒';
        $list['park_time'] = $park_time;
        $list['pay_money'] = $pay_money;
        $list['order_id'] = $park_info['order_id'];
        $list['park_name'] = $park_info['park_name'];
        $list['in_time'] = date('Y-m-d H:i:s', $park_info['in_time']);
        $list['pay_time'] = time();
        $list['total_money'] = $total_money;
        $list['coupon_money'] = $coupon_info['money'];
        fdump_api([$list],'get_temp_pay_01',1);
        return $list;
    }
    
    public function get_park_car_garage($village_id=0,$car_number=''){

        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        if(empty($car_number)){
            $park_config_info=$houseVillageParkGarageConfigService->getVillageParkConfig(0,$village_id);
            $returnArr=array('garage_id'=>0,'park_garage_config'=>$park_config_info,'passage_info'=>'');
            return $returnArr;
        }
        $park_sys_type='';
        $channel_id=0;
        $channel_number='';
        $db_in_park = new InPark();
        $whereArr=array();
        $whereArr[]=array('park_id','=',$village_id);
        $whereArr[]=array('car_number','=',$car_number);
        $whereArr[]=array('is_out','=',0);
        $in_park=$db_in_park->getOne1($whereArr);
        if($in_park && !$in_park->isEmpty()){
            $channel_id=!empty($in_park['in_channel_id'])? $in_park['in_channel_id']:0;
            $park_sys_type=!empty($in_park['park_sys_type'])? $in_park['park_sys_type']:'';
        }
        if(empty($channel_id)){
            $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
            $whereArr=array();
            $whereArr[]=array('park_id','=',$village_id);
            $whereArr[]=array('car_number','=',$car_number);
            $whereArr[]=array('accessType','=',1);
            $car_access_record=$db_house_village_car_access_record->getOne($whereArr);
            if($car_access_record && !$car_access_record->isEmpty()){
                $car_access_record=$car_access_record->toArray();
                if($car_access_record['is_out']==0){
                    $channel_id=$car_access_record['channel_id'];
                    $channel_number=$car_access_record['channel_number'];
                    $park_sys_type=!empty($car_access_record['park_sys_type'])? $car_access_record['park_sys_type']:$park_sys_type;
                }
            }
        }
        if(empty($channel_id)){
            $db_park_plateresult_log = new ParkPlateresultLog();
            $whereArr=array();
            //$whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('car_number','=',$car_number);
            $whereArr[]=array('park_type','=',1);
            $plateresult_log=$db_park_plateresult_log->get_one($whereArr);
            if($plateresult_log && !$plateresult_log->isEmpty()){
                $channel_number=$plateresult_log['channel_id'];
                $park_sys_type=$plateresult_log['park_sys_type']? $plateresult_log['park_sys_type']:$park_sys_type;
            }
        }
        $wherePassage=array();
        if($channel_id){
            $wherePassage[]=array('id','=',$channel_id);
        }elseif ($channel_number){
            $wherePassage[]=array('device_number','=',$channel_number);
        }
        $garage_id=0;
        $passage_info=array();
        if($wherePassage){
            $wherePassage[]=array('village_id','=',$village_id);
            $db_park_passage = new ParkPassage();
            $passage_info = $db_park_passage->getFind($wherePassage);
            if($passage_info && !$passage_info->isEmpty()){
                $passage_info=$passage_info->toArray();
                $park_sys_type=$passage_info['park_sys_type'];
                if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
                    $garage_id=$passage_info['garage_id'];
                }elseif($passage_info['passage_area'] && $passage_info['area_type']==2){
                    $garage_id=intval($passage_info['passage_area']);
                }
            }
        }
        $park_config_info=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        $returnArr=array('garage_id'=>$garage_id,'park_garage_config'=>$park_config_info,'passage_info'=>$passage_info);
        return $returnArr;
    }
    /**
     * 计算停车需交费用
     * @author:zhubaodi
     * @date_time: 2021/11/11 14:49
     */
    public function get_park_money($data)
    {
        $parkGarageConfig=$this->get_park_car_garage($data['village_id'],$data['car_number']);
        $park_config_info=$parkGarageConfig['park_garage_config'];
        if($park_config_info){
            $park_sys_type=$park_config_info['park_sys_type'];
        }
        $data['garage_id']=$parkGarageConfig['garage_id'];
        $payment_info = [];
        $park_info=[
            'is_async'=>false,
            'temp_id'=>0
        ];
        if ($park_config_info['park_versions'] == 2 && $park_sys_type == 'D3') {
            $payment_info = $this->get_temp_pay($data);
        }elseif ($park_config_info['park_versions'] == 2 && $park_sys_type == 'D6'){
            $park_info =$this->getVillageParkType($park_config_info,$data['car_number']);
            $paramArr=array('village_id'=>$data['village_id'],'car_number'=>$data['car_number'],'query_order_no'=>$park_info['temp_id']);
            $list=$this->timingQuery($paramArr);
            $list['park_info']=$park_info;
            return $list;
        }elseif ($park_config_info['park_versions'] == 2 && $park_sys_type == 'D7'){
            $payment_info=$this->queryOrderPriceD7($data,$park_config_info);

        }else if($park_config_info['park_versions'] == 2 && $park_sys_type == 'A11'){
            $service_a11 = new A11Service();
            $data['car_number_type']=0;
            $payment_info =$service_a11->get_temp_pay($data,$data['garage_id']);
        }
        $list['park_info']=$park_info;
        $pay_list2 = [];
        $pay_list1 = [];
        $pay_list3 = [];
        $pay_list4 = [];
        if (!empty($payment_info)) {
            $pay_list1[0]['title'] = '车牌号';
            $pay_list1[0]['val'] = $data['car_number'];
            $pay_list1[1]['title'] = '应付金额';
            $pay_list1[1]['val'] = $payment_info['total_money'];
            $pay_list1[2]['title'] = '停车时间';
            $pay_list1[2]['val'] = $payment_info['time'];
            $pay_list2[0]['title'] = '入场时间';
            $pay_list2[0]['val'] = $payment_info['in_time'];
            $pay_list3[0]['title'] = '订单编号';
            $pay_list3[0]['val'] = $payment_info['order_id'];
            $pay_list3[1]['title'] = '车场名称';
            $pay_list3[1]['val'] = $payment_info['park_name'];
            $pay_list3[2]['title'] = '缴费时间';
            $pay_list3[2]['val'] = $payment_info['pay_time'];
            $pay_list4[0]['title'] = '应付金额';
            $pay_list4[0]['val'] = $payment_info['total_money'];
            $pay_list4[1]['title'] = '停车抵扣券';
            $pay_list4[1]['val'] = $payment_info['coupon_money'];
            $pay_list4[2]['title'] = '实付金额';
            $pay_list4[2]['val'] = $payment_info['pay_money'];
        }

        if(!empty($payment_info)){
            $data=[
                'car_number'=>$data['car_number'],
                'total_money'=>$payment_info['total_money'],
                'parking_duration'=>$payment_info['time'],
                'in_time'=>$payment_info['in_time'],
                'order_no'=>$payment_info['order_id'],
                'park_name'=>$payment_info['park_name'],
                'pay_time'=>$payment_info['pay_time'],
                'coupon_money'=>$payment_info['coupon_money'],
                'pay_money'=>$payment_info['total_money']
            ];
            $list['pay_money']=$data['pay_money'];
            $listtmp=$this->assembleOrderData($data);
            $list['list']=array();
            $list['list']['top']=$listtmp['top'];
            $list['list']['middle']=$listtmp['middle'];
            $list['list']['bottom']=$listtmp['bottom'];
            $list['pay_list1']=$listtmp['pay_list1'];
            $list['pay_list2']=$listtmp['pay_list2'];
            $list['pay_list3']=$listtmp['pay_list3'];
            $list['pay_list4']=$listtmp['pay_list4'];

        }
        return $list;
    }


    /**
     * D7查询车辆的需缴费信息
     * @author:zhubaodi
     * @date_time: 2021/10/8 17:05
     */
    public function queryOrderPriceD7($data,$park_config_info=array())
    {
        $db_house_village_park_temp = new HouseVillageParkingTemp();
        if(empty($park_config_info)){
            $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
            $garage_id=0;
            if(isset($data['garage_id']) && !empty($data['garage_id'])){
                $garage_id=$data['garage_id'];
            }
            $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        }else{
            $park_config=$park_config_info;
        }
        if (empty($park_config)||empty($park_config['d7_park_id'])){
            throw new Exception('停车场信息不存在');
        }

        if (empty($data['car_number'])){
            throw new Exception('车牌号不能为空');
        }
        $query=(new QinLinCloudService())->queryParkingBill($park_config['d7_park_id'],1,'',$data['car_number']);
        if(empty($query) || empty($query['data'])||$query['state']!=200){
            throw new Exception($query['msg']);
        }
        $price_data=$query['data'];
        $data_temp=[];
        $data_temp['village_id']=$data['village_id'];
        $data_temp['add_time']=time();
        $data_temp['query_time']=time();
        $data_temp['duration']=$price_data['parkingTime'];
        $data_temp['derate_money']=isset($price_data['discountMoney'])?round_number($price_data['discountMoney']/100,2):0;
        $data_temp['derate_duration']=isset($price_data['discountTime'])?$price_data['discountTime']:0;
        $data_temp['order_id']=$price_data['orderNo'];
        $data_temp['query_order_no']=$price_data['parkingId'];
        $data_temp['price']=round_number($price_data['paymentAmount']/100,2);
        $data_temp['total']=round_number($price_data['orderMoney']/100,2);
        $data_temp['in_time']=$price_data['inTime'];
        $data_temp['car_number']=empty($car_number)?$price_data['carnumber']:$car_number;
        $data_temp['park_sys_type']='D7';
        $data_temp['is_pay_scene']=0;
        $data_temp['errmsg']=serialize($price_data['orderDetails']);
        $db_house_village_park_temp->addOne($data_temp);
        fdump_api(['计算后记录临时表中',$data_temp],'D7park/get_temp_pay_log',1);

        $list = [];
        $list['time'] = $price_data['parkingTimes'];
        $list['park_time'] = $price_data['parkingTime']*60;
        $list['pay_money'] = round_number($price_data['paymentAmount']/100,2);
        $list['order_id'] = $price_data['parkingId'];
        $list['park_name'] = $price_data['parkName'];
        $list['in_time'] = date('Y-m-d H:i:s', $price_data['inTime']);
        $list['pay_time'] = time();
        $list['total_money'] = round_number($price_data['orderMoney']/100,2);
        $list['coupon_money'] =isset($price_data['discountMoney'])?round_number($price_data['discountMoney']/100,2):0;

        fdump_api(['返回',$list],'D7park/get_temp_pay_log',1);
        return $list;

    }



    /**
     * 设备获取车牌后回调接口
     * @author:zhubaodi
     * @date_time: 2021/11/11 13:23
     */
    public function add_park_info($data,$parkTime=0)
    {
        $res=false;
        $car_number = '';
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['license'])) {
            $car_number = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        }
        $db_park_passage = new ParkPassage();
        //查询设备
        if (isset($data['AlarmInfoPlate']['serialno'])) {
            $serialno = $data['AlarmInfoPlate']['serialno'];
            
            if ($serialno && !empty(cache('park:'.$serialno.':serialno'))) {
                $passage_info = cache('park:'.$serialno.':serialno');
            } else {
                $passage_info = $db_park_passage->getFind(['device_number' => $serialno, 'status' => 1]);
                if($passage_info && !$passage_info->isEmpty()){
                    $passage_info=$passage_info->toArray();
                }
                if($passage_info && isset($passage_info['device_ip']) && empty($passage_info['device_ip']) && isset($data['ipaddr']) && $data['ipaddr']){
                    $saveUpdateArr=array('device_ip'=>$data['ipaddr']);
                    if(isset($data['user_name']) && $data['user_name']){
                        $saveUpdateArr['device_account']=$data['user_name'];
                    }
                    if(isset($data['pass_wd']) && $data['pass_wd']){
                        $saveUpdateArr['device_pwd']=$data['pass_wd'];
                    }
                    $db_park_passage->save_one(array('id'=>$passage_info['id']),$saveUpdateArr);
                }
                if (!empty($passage_info)) {
                    cache('park:'.$serialno.':serialno', $passage_info, 86400);
                }
            }
        } else {
            $serialno = '';
            $passage_info = [];
        }
        if (!empty($passage_info) && !empty($car_number) && $passage_info['park_sys_type'] == 'A11') {
            if (strpos($car_number, '无') !== false) {
                return false;
            }
            $service_a11 = new A11Service();
            $res = $service_a11->add_park_info($data, $passage_info, $car_number, $serialno, $parkTime);
            return $res;
        }

        if (isset($passage_info['village_id']) && $passage_info['village_id'] && !empty(cache($passage_info['village_id']))) {
            $village_info = cache($passage_info['village_id']);
        } else {
            $db_house_village = new HouseVillage();
            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
            if (!empty($village_info)) {
                cache($passage_info['village_id'], $village_info, 86400);
            }
        }
        if ($parkTime) {
            fdump_api(['设备获取车牌后回调接口'=>$car_number,'passage_info'=>$passage_info,'village_info'=>$village_info],'D3Park/plateresult'.$parkTime,true);
        }
        if (empty($passage_info)) {
            //TODO:屏显和语音提示错误信息
            //企业微信群提醒
            $webhookurl = $this->webhookurl;
            $data = [
                "msgtype" => "markdown",
                "markdown" => [
                    "content" => "<font color='info'>**停车设备不存在**</font>
                                 >小区名称: <font color='info'>**无**</font>
                                 >车牌号码: <font color='info'>**无**</font>
                                 >设备名称: <font color='info'>**无**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**无**</font> 
                                 >行为原因: <font color='info'>**停车设备不存在**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                ],
            ];
            http_request($webhookurl, 'POST', json_encode($data,JSON_UNESCAPED_UNICODE));

            if ($parkTime) {
                fdump_api(['停车设备不存在'=>$car_number],'D3Park/plateresult'.$parkTime,true);
            }
            //停车设备不存在
            return false;
        }
        if ($passage_info['passage_direction'] == 1) {
            $passage_direction = '入口';
        } else {
            $passage_direction = '出口';
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';

        //未识别到车牌
        if (!$car_number || strpos($car_number, '无') !== false) {
            //TODO:屏显和语音提示错误信息

            //企业微信群提醒
            $webhookurl = $this->webhookurl;
            $data = [
                "msgtype" => "markdown",
                "markdown" => [
                    "content" => "<font color='info'>**" . $passage_info['passage_name'] . "**</font>设备未识别到车牌。
                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**" . $passage_direction . "**</font> 
                                 >行为原因: <font color='info'>**未识别到车牌**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                ],
            ];
            http_request($webhookurl, 'POST', json_encode($data,JSON_UNESCAPED_UNICODE));
            //未识别到车牌号
            if ($parkTime) {
                fdump_api(['未识别到车牌'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
            }
            return false;
        }
        $service_config_data = new ConfigDataService();
        $config_data =$service_config_data->getConfigData();
        //黑名单查询
        if (!empty(cache('backList_'.$passage_info['village_id']))) {
            $black_list= cache('backList_'.$passage_info['village_id']);
        }else {
            $db_house_village_park_black = new HouseVillageParkBlack();
            $whereBlackList = [];
            $whereBlackList[] = ['village_id', '=', $passage_info['village_id']];
            $black_list = $db_house_village_park_black->getList($whereBlackList);
            if($black_list && !$black_list->isEmpty()){
                $black_list = $black_list->toArray();
            }else{
                $black_list=array();
            }
            cache('backList_'.$passage_info['village_id'],$black_list,3600);
        }
        if (!empty($black_list)) {
            if ($parkTime) {
                fdump_api(['黑名单'=>$car_number,'black_list' => $black_list],'D3Park/plateresult'.$parkTime,true);
            }
            if (!empty($black_list)) {
                foreach ($black_list as $va) {
                    if ($car_number == $va['car_number']) {
                        //TODO:屏显和语音提示错误信息
                        //企业微信群提醒
                        $webhookurl = $this->webhookurl;
                        $data = [
                            "msgtype" => "markdown",
                            "markdown" => [
                                "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆是黑名单车辆，<font color='info'>**" . $passage_info['passage_name'] . "**</font>设备不允许通行。
                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**" . $passage_direction . "**</font> 
                                 >行为原因: <font color='info'>**黑名单车辆禁止通行**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                            ],
                        ];
                        http_request($webhookurl, 'POST', json_encode($data,JSON_UNESCAPED_UNICODE));
                        //黑名单车辆不允许通行
                        if ($parkTime) {
                            fdump_api(['黑名单车辆不允许通行'=>$car_number,'black_list' => $black_list],'D3Park/plateresult'.$parkTime,true);
                        }
                        return false;
                    }
                }
            }
        }
        $showscreen_data=[];
        $showscreen_data['passage']=$passage_info;
        $showscreen_data['village_id']=$passage_info['village_id'];
        $showscreen_data['car_number']= $car_number;
        $showscreen_data['channel_id']= $serialno;
        $showscreen_data['car_type']= '';
        $showscreen_data['content']= '';
        //车辆入场
        if ($passage_info['passage_direction'] == 1){
            $param = [
                'parkTime' => $parkTime,
                'car_number' => $car_number,
                'serialno' => $serialno,
            ];
            if ($car_number) {
                fdump_api(['车辆入场'=>$car_number,'param'=>$param],'D3Park/plateresult'.$parkTime,true);
            }
            try {
                $res =$this->in_park($car_number,$data,$config_data,$village_info,$passage_info,$param);
            }catch (\Exception $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }catch (\Throwable $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }catch (\RuntimeException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }catch (\InvalidArgumentException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }
            fdump_api(['车辆入场'=>$car_number,'res'=>$res],'D3Park/plateresult'.$parkTime,true);
        }
        //车辆出场
        if ($passage_info['passage_direction'] == 0){
            $param = [
                'parkTime' => $parkTime,
                'car_number' => $car_number,
                'serialno' => $serialno,
            ];
            if ($car_number) {
                fdump_api(['车辆出场'=>$car_number,'param'=>$param],'D3Park/plateresult'.$parkTime,true);
            }
            try {
                $res =$this->out_park($car_number,$data,$config_data,$village_info,$passage_info,$param);
            }catch (\Exception $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }catch (\Throwable $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }catch (\RuntimeException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }catch (\InvalidArgumentException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param],'D3Park/errLog1',true);
            }
            fdump_api(['车辆出场'=>$car_number,'res'=>$res],'D3Park/plateresult'.$parkTime,true);
        }
        return $res;
    }

    /**
     * 车辆入场
     * @author:zhubaodi
     * @date_time: 2022/3/17 11:52
     */
    public function in_park($car_number,$data,$config_data,$village_info,$passage_info,$param=[]){
        if (isset($param['parkTime'])&&$param['parkTime']) {
            $parkTime = $param['parkTime'];
        } else {
            $parkTime = 0;
        }
        if (isset($param['car_number'])&&$param['car_number']) {
            $car_number = trim($param['car_number']);
        } elseif (isset($data['AlarmInfoPlate']['result']['PlateResult']['license'])) {
            $car_number = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        }
        if (isset($param['serialno'])&&$param['serialno']) {
            $serialno = trim($param['serialno']);
        } elseif (isset($data['AlarmInfoPlate']['serialno'])) {
            $serialno = $data['AlarmInfoPlate']['serialno'];
        } else {
            $serialno = '';
        }
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        $db_in_park = new InPark();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_park_free = new HouseVillageParkFree();
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_house_village_visitor = new HouseVillageVisitor();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $showscreen_data=[];
        $showscreen_data['passage']=$passage_info;
        $showscreen_data['village_id']=$passage_info['village_id'];
        $showscreen_data['car_number']= $car_number;
        $showscreen_data['channel_id']= $serialno;
        $showscreen_data['car_type']= '';
        $showscreen_data['voice_content']= 1;
        $showscreen_data['content']= '欢迎光临';

        $now_time = time();
        $park_log_data = [];
        $park_log_data['car_number'] = $car_number;
        $park_log_data['channel_id'] = $serialno;
        $park_log_data['park_type'] = 1;
        $park_log_data['village_id'] =$passage_info['village_id'];
        $park_log_data['add_time'] = $now_time;
        $logId = $db_park_plateresult_log->add($park_log_data);
        if ($parkTime) {
            fdump_api(['添加识别记录' => $car_number, 'park_log_data' => $park_log_data, 'showscreen_data' => $showscreen_data, 'logId' => $logId], 'D3Park/plateresult' . $parkTime, true);
        }
        $rand_num = date('Ymd', $now_time);// 换成日期存储
        // $up_dir = './upload/park_log/' . $rand_num . '/'.$serialno.'/';
        $path = '/upload/park_log/' . $rand_num . '/'.$serialno.'/';
        $up_dir = $_SERVER['DOCUMENT_ROOT'].$path;
        $park_data = [];
        // 记录车辆进入信息
        $car_access_record = [];
        $park_data['car_number'] = $car_number;
        $park_data['in_time'] = $now_time;
        $park_data['order_id'] = uniqid();
        $park_data['in_channel_id'] = $passage_info['id'];
        $park_data['is_paid'] = 0;
        $park_data['park_id'] = $passage_info['village_id'];
        $park_data['park_sys_type'] = $park_sys_type;
        $park_data['park_name'] = $village_info['village_name'];

        if ($parkTime) {
            fdump_api(['车辆相关信息' => $car_number, 'park_data' => $park_data], 'D3Park/plateresult' . $parkTime, true);
        }
        $ymdhis = date('Ymdhis', $now_time);
        $imageToJob = [];
        $isImageToJob = false;
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
            $imageFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            $isImageToJob = true;
            $imageToJob['park_type'] = 'in_park';
            $imageToJob['serialno'] = $serialno;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['in_time'] = $now_time;
            $imageToJob['order_id'] = $park_data['order_id'];
            $imageToJob['village_id'] = $passage_info['village_id'];
            $imageToJob['nowTime'] = $now_time;
            $imageToJob['imageSavePath'] = $up_dir;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
            if(isset($data['from_xtype'])){
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }
            if ($parkTime) {
                fdump_api(['车辆相关信息-1图片' => $car_number], 'D3Park/plateresult' . $parkTime, true);
            }
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
            $imageFragmentFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            if (!$isImageToJob) {
                $isImageToJob = true;
                $imageToJob['park_type'] = 'in_park';
                $imageToJob['serialno'] = $serialno;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['in_time'] = $now_time;
                $imageToJob['order_id'] = $park_data['order_id'];
                $imageToJob['village_id'] = $passage_info['village_id'];
                $imageToJob['nowTime'] = $now_time;
                $imageToJob['imageSavePath'] = $up_dir;
            }
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
            if(isset($data['from_xtype'])){
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }
            if ($parkTime) {
                fdump_api(['车辆相关信息-2图片' => $car_number], 'D3Park/plateresult' . $parkTime, true);
            }
        }
        if ($parkTime) {
            fdump_api(['车辆相关信息' => $car_number, 'park_data' => $park_data], 'D3Park/plateresult' . $parkTime, true);
        }
        $car_access_record['channel_id']=$passage_info['id'];
        $car_access_record['channel_number']=$passage_info['channel_number'];
        $car_access_record['channel_name']=$passage_info['passage_name'];
        $imageBigUrlPath='';
        $imageSmallUrlPath='';
        $car_access_record['channel_id']=$passage_info['id'];
        $car_access_record['channel_number']=$passage_info['channel_number'];
        $car_access_record['channel_name']=$passage_info['passage_name'];
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imagePath']) && $data['AlarmInfoPlate']['result']['PlateResult']['imagePath']){
            $imageUrlPathTmp= base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imagePath']);
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageBigUrlPath=$imageUrlPathArr['0'];
                $imageBigUrlPath=str_replace('http:','https:',$imageBigUrlPath);
                $car_access_record['accessBigImage']=$imageBigUrlPath;
                $park_data['in_image_big']=$imageBigUrlPath;
            }
        }
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']) && $data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']){
            $imageUrlPathTmp= base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']);
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageSmallUrlPath=$imageUrlPathArr['0'];
                $imageSmallUrlPath=str_replace('http:','https:',$imageSmallUrlPath);
                $car_access_record['accessImage']=$imageSmallUrlPath;
                $park_data['in_image_small']=$imageSmallUrlPath;
            }
        }
        //查询免费车
        if (!empty(cache('freeList_'.$passage_info['village_id']))) {
            $free_list= cache('freeList_'.$passage_info['village_id']);
        }else {
            $free_list_obj = $db_house_village_park_free->getList(['village_id' => $passage_info['village_id']]);
            $free_list='';
            if ($free_list_obj && !$free_list_obj->isEmpty()) {
                $free_list = $free_list_obj->toArray();
            }
            cache('freeList_'.$passage_info['village_id'],$free_list, 3600);
        }
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        if ($isImageToJob) {
            $imageToJob['car_number_no'] = $car_no;
        }
        if ($parkTime) {
            fdump_api(['查询条件'=>$car_number,'province' => $province,'car_no' => $car_no],'D3Park/plateresult'.$parkTime,true);
        }
        $carCachekey='village:'.$passage_info['village_id'].':car:'.$car_number;
        
        if (!empty(cache($carCachekey))) {
            $car_info = cache($carCachekey);
            if (!isset($car_info['end_time']) || $car_info['end_time'] <= $now_time) {
                // 缓存中取不出来车辆信息或者截止时间过期了 避免出现问题 及时查询下最新的信息并缓存
                $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                if($car_info && !$car_info->isEmpty()){
                    $car_info=$car_info->toArray();
                    cache($carCachekey,$car_info,3600);
                }else{
                    cache($carCachekey,null);
                }
            }
        } else {
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
            if($car_info && !$car_info->isEmpty()){
                $car_info=$car_info->toArray();
                cache($carCachekey,$car_info,3600);
            }else{
                cache($carCachekey,null);
            }
        }
        $car_access_record['garage_id']=$garage_id;
        $rightPosition=array();
        if($car_info && isset($car_info['car_id'])){
            $rightPosition=$this->getCarRightInPosition($car_info,$passage_info['village_id'],$garage_id);
            fdump_api(['car_info'=>$car_info,'rightPosition' => $rightPosition], 'D3Park/plateresult'.$parkTime, 1);
            if(!empty($rightPosition)){
                if($rightPosition['start_time']<=$now_time && $rightPosition['end_time']>=$car_info['end_time']){
                    $car_info['end_time']=$rightPosition['end_time'];
                    cache($carCachekey,$car_info,3600);
                }
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time']>$now_time){
                    //车位时间还没开始
                    $car_info['end_time']=0;
                }
                if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                    $car_access_record['position_id'] = $rightPosition['position_id'];
                }
                if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                    $car_access_record['garage_id'] = $rightPosition['garage_id'];
                }
                if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                    $car_access_record['position_num'] = $rightPosition['position_num'];
                }
            }elseif(empty($rightPosition)){
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑，没有可有车位时
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;   //没有可有车位时
                }
                if(isset($car_info['garage_id']) && $car_info['garage_id']>0 &&  $car_info['garage_id']!=$garage_id){
                    //进入的不是同一个车库
                    $car_info['end_time']=0;
                }
                if($car_info && $car_info['car_position_id']){
                    $bind_position_ids[]=$car_info['car_position_id'];
                }
                if(empty($bind_position_ids)){
                    //标记一下无车位进场临时车
                    $car_access_record['flag_status']='temporaryCarNoPosition';
                    $car_access_record['park_car_type']='temporaryCarNoPosition';
                    $car_access_record['car_type']='temporaryCarNoPosition';
                }
            }
            $car_access_record['car_id']=$car_info['car_id'];
        }else{
            //标记一下无车位进场临时车
            $car_access_record['flag_status']='temporaryCarNoPosition';
            $car_access_record['park_car_type']='temporaryCarNoPosition';
            $car_access_record['car_type']='temporaryCarNoPosition';
        }
        
        $user_name  = empty($car_info['car_user_name']) ? '' : $car_info['car_user_name'];
        $user_phone = empty($car_info['car_user_phone'])? '' : $car_info['car_user_phone'];
        if ($parkTime) {
            fdump_api(['车辆信息'=>$car_number,'car_info' => $car_info],'D3Park/plateresult'.$parkTime,true);
        }
        $car_visitor_cache='village:'.$passage_info['village_id'].':car_visitor:' . $car_number;
        if (!$user_name && !$user_phone && !empty(cache($car_visitor_cache))) {
            $visitor_info = cache($car_visitor_cache);
            $user_name  = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid        = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        } elseif (!$user_name && !$user_phone) {
            $visitor_info = $db_house_village_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], 'id, visitor_name, visitor_phone, uid');
            cache($car_visitor_cache, $visitor_info);
            $user_name  = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid        = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        } else {
            $visitor_info = [];
        }
        if ($user_name) {
            $car_access_record['user_name'] = $user_name;
        }
        if ($user_phone) {
            $car_access_record['user_phone'] = $user_phone;
        }
        if (isset($uid) && $uid) {
            $car_access_record['uid'] = $uid;
        }
        $starttime = $now_time - 100;
        $endtime = $now_time + 10;
        // 查询in_park
        $park_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['in_time', '>=', $starttime],
            ['in_time', '<=', $endtime],
            ['del_time', '<', 1],
        ];
        // 查询house_village_car_access_record
        $whereAccessRecord = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['accessType', '=', 1],
            ['park_sys_type', '=', $park_sys_type],
            ['accessTime', '>=', $starttime],
            ['accessTime', '<=', $endtime],
            ['del_time', '<', 1],
        ];
        
        if (!empty($free_list)) {
            fdump_api(['免费车' => $car_number, 'free_list' => $free_list], 'D3Park/plateresult' . $parkTime, true);
            foreach ($free_list as $v) {
                $first_number = substr($car_number, 0, strlen($v['first_name']));
                $last_name = substr($car_number, '-' . strlen($v['last_name']));
                if ($parkTime) {
                    fdump_api(['免费车' => $car_number, 'first_number' => $first_number, 'last_name' => $last_name], 'D3Park/plateresult' . $parkTime, true);
                }
                $isFreePark = false;
                if (!empty($v['first_name']) && $first_number == $v['first_name']) {
                    $isFreePark = true;
                } elseif (!empty($v['last_name']) && $last_name == $v['last_name']) {
                    $isFreePark = true;
                } elseif ($car_number == $v['free_park']) {
                    $isFreePark = true;
                }
                if ($isFreePark) {
                    if ($parkTime) {
                        fdump_api(['符合免费车条件' => $car_number, 'first_name' => $v['first_name']], 'D3Park/plateresult' . $parkTime, true);
                    }
                    $park_info_car = $db_in_park->getOne1($park_where);
                    if ($park_info_car && !$park_info_car->isEmpty()) {
                        $park_info_car = $park_info_car->toArray();
                    } else {
                        $park_info_car = array();
                    }
                    if ($parkTime) {
                        fdump_api(['条件查询结果' => $car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car], 'D3Park/plateresult' . $parkTime, true);
                    }
                    if (empty($park_info_car) || !isset($park_info_car['id'])) {
                        //写入车辆入场表
                        $insert_id = $db_in_park->insertOne($park_data);
                        if ($parkTime) {
                            fdump_api(['in_park' => '插入in_park表', 'park_data' => $park_data, 'insert_id' => $insert_id], 'D3Park/plateresult' . $parkTime, true);
                        }
                        //写入车辆入场表
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $car_number;
                        $car_access_record['accessType'] = 1;
                        $car_access_record['accessTime'] = $now_time;
                        $car_access_record['accessMode'] = 8;
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['is_out'] = 0;
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                        $car_access_order_id = date('YmdHis') . rand(100, 999);
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['update_time'] = $now_time;
                        if (isset($insert_id) && !empty($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        if ($parkTime) {
                            fdump_api(['记录通行记录' => $car_number, 'car_access_record' => $car_access_record], 'D3Park/plateresult' . $parkTime, true);
                        }
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                        if ($record_id > 0) {
                            /*
                            $access_record_where_near = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['is_out', '=', 0],
                                ['del_time', '<', 1],
                                ['record_id', '<', $record_id],
                            ];
                            //最近一条未出场的 记录
                            $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                            if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                                $access_record_tmp=$access_record_tmp->toArray();
                                $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                            }
                            */
                            $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                            if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                                $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                                $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                            }
                            if ($parkTime) {
                                fdump_api(['car_number' => $car_number, 'record_id' => $record_id, 'msg' => '数据记录添加完成'], 'D3Park/plateresult' . $parkTime, true);
                            }
                            $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                        }
                        if ($parkTime) {
                            fdump_api(['记录通行记' => $car_number, 'record_id' => $record_id], 'D3Park/plateresult' . $parkTime, true);
                        }
                    } else {
                        $insert_id = $park_info_car['id'];
                        $record_id = isset($park_info_car['access_record_id']) ? intval($park_info_car['access_record_id']) : 0;
                        $car_access_order_id = isset($park_info_car['access_order_id']) ? trim($park_info_car['access_order_id']) : '';
                    }
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $insert_id;
                    }
                    //$in_record_info = $db_house_village_car_access_record->getOne($whereAccessRecord);

                    //TODO:屏显和语音提示特殊车辆通行
                    $showscreen_data['content'] = '免费车通行';
                    if ($parkTime) {
                        fdump_api(['记录通行记录' => $car_number, 'showscreen_data' => $showscreen_data], 'D3Park/plateresult' . $parkTime, true);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    //特殊车辆通行
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['in_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    return true;
                }
            }
        }
        //查询车辆到期时间
        if (!empty($car_info) && $car_info['end_time'] > $now_time) {
            if ($parkTime) {
                fdump_api(['查询下1分钟内入场时间'=>$car_number,'park_where' => $park_where],'D3Park/plateresult'.$parkTime,true);
            }
            $park_info_car = $db_in_park->getOne1($park_where);
            if($park_info_car && !$park_info_car->isEmpty()){
                $park_info_car=$park_info_car->toArray();
            }else{
                $park_info_car=array();
            }
            if ($parkTime) {
                fdump_api(['条件查询结果' => $car_number, 'park_where' => $park_where, 'park_info_car' => $park_info_car], 'D3Park/plateresult' . $parkTime, true);
            }
            if (empty($park_info_car) || !isset($park_info_car['id'])) {
                //写入车辆入场表
                $insert_id = $db_in_park->insertOne($park_data);
                if ($parkTime) {
                    fdump_api(['in_park' => '插入in_park表', 'park_data' => $park_data, 'insert_id' => $insert_id], 'D3Park/plateresult' . $parkTime, true);
                }
                //写入车辆入场表
                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $car_number;
                $car_access_record['accessType'] = 1;
                $car_access_record['accessTime'] = $now_time;
                $car_access_record['accessMode'] = 5;
                $car_access_record['park_sys_type'] = $park_sys_type;
                $car_access_record['is_out'] = 0;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                $car_access_order_id = date('YmdHis') . rand(100, 999);
                $car_access_record['order_id'] = $car_access_order_id;
                $car_access_record['update_time'] = $now_time;
                if (isset($insert_id) && !empty($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                if ($parkTime) {
                    fdump_api(['入车辆入场表' => $car_number, 'car_access_record' => $car_access_record], 'D3Park/plateresult' . $parkTime, true);
                }
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                if ($record_id > 0) {
                    /*
                    $access_record_where_near = [
                        ['car_number', '=', $car_number],
                        ['park_id', '=', $passage_info['village_id']],
                        ['is_out', '=', 0],
                        ['del_time', '<', 1],
                        ['record_id', '<', $record_id],
                    ];
                    //最近一条未出场的 记录
                    $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                    if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                        $access_record_tmp=$access_record_tmp->toArray();
                        $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                    }
                    */
                    $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                    if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                        $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                        $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                    }
                    if ($parkTime) {
                        fdump_api(['car_number' => $car_number, 'record_id' => $record_id, 'msg' => '数据记录添加完成'], 'D3Park/plateresult' . $parkTime, true);
                    }
                    $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                    (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                }
                if ($parkTime) {
                    fdump_api(['入车辆入场表' => $car_number, 'record_id' => $record_id], 'D3Park/plateresult' . $parkTime, true);
                }

            } else {
                $insert_id = $park_info_car['id'];
                $record_id = isset($park_info_car['access_record_id']) ? intval($park_info_car['access_record_id']) : 0;
                $car_access_order_id = isset($park_info_car['access_order_id']) ? trim($park_info_car['access_order_id']) : '';
            }
            if ($isImageToJob) {
                $imageToJob['in_park_id'] = $insert_id;
            }
            //写入车辆入场表
            //$in_record_info = $db_house_village_car_access_record->getOne($whereAccessRecord);

            //TODO:屏显和语音提示特殊车辆通行
            $showscreen_data['car_type']= 'month_type';
            $showscreen_data['end_time']= $car_info['end_time'];
            $showscreen_data['end_time_txt']= date('Y-m-d',$car_info['end_time']);
            if ($parkTime) {
                fdump_api(['屏显和语音提示特殊车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
            }
            if(!empty($rightPosition)){
                $whereArr=array('position_id'=>$rightPosition['position_id']);
                $saveArr=array('use_car_id'=>$car_info['car_id'],'use_start_time'=>$now_time,'use_car_number'=>$car_number,'access_record_id'=>$record_id);
                $this->updateParkingPositionUse($whereArr,$saveArr,$car_number,'in_park');
            }
            $this->addParkShowScreenLog($showscreen_data);
            //特殊车辆通行
            if ($isImageToJob && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['in_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
            return true;
        } else {
            //临时车入场
            $park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$passage_info['village_id']);
            $expire_month_car_notin=0;
            //曾今是月租车 现在过期了
            if(($car_info && $car_info['end_time']>100 && $car_info['end_time']<$now_time) && $park_config && isset($park_config['expire_month_car_type']) && $park_config['expire_month_car_type']){
                /*** 1月租车过期按临时车收费2月租车过期禁止入场3过期多少天后禁止入场**/
                $expire_month_car_type=$park_config['expire_month_car_type'];
                $expire_month_car_day=$park_config['expire_month_car_day'];
                if($expire_month_car_type==2 || ($expire_month_car_type==3 && empty($expire_month_car_day))){
                    $expire_month_car_notin=1;
                }else if($expire_month_car_type==3 && $expire_month_car_day>0){
                    $expire_month_car_second=$expire_month_car_day*24*3600;
                    $month_end_time=!empty($car_info) && $car_info['end_time']>0 ? $car_info['end_time']:0;
                    $expire_month_time_tmp=$now_time-$month_end_time;
                    if($expire_month_time_tmp>$expire_month_car_second){
                        $expire_month_car_notin=1;
                    }
                }
            }
            //开启禁止临时车入场配置，禁止临时车入场
            if (!empty($park_config)&& (empty($park_config['temp_in_park_type']) || ($expire_month_car_notin==1))){
                $showscreen_data['car_type']= 'temporary_type';
                $showscreen_data['content']= '临时车禁止通行';
                $showscreen_data['voice_content']= 7;
                //TODO:屏显和语音提示错误信息
                $this->addParkShowScreenLog($showscreen_data);
                //企业微信群提醒
                $webhookurl = $this->webhookurl;
                $data = [
                    "msgtype" => "markdown",
                    "markdown" => [
                        "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆未登记,<font color='info'>**" . $passage_info['passage_name'] . "**</font>设备不允许通行，请先登记。
                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**入口**</font> 
                                 >行为原因: <font color='info'>**临时车未登记，禁止入场**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                    ],
                ];
                http_request($webhookurl, 'POST', json_encode($data));
                //停车设备不存在

                if ($parkTime) {
                    fdump_api(['停车设备不存在'=>$car_number,'data' => $data,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                }
                return false;
            }
            //获取配置的免登记时长
            elseif (!empty($park_config)&&!empty($park_config['register_day'])){
                $addTime=strtotime(date('Y-m-d 00:00:00'))-$park_config['register_day']*86400;
            }
            else{
                $addTime=strtotime(date('Y-m-d 00:00:00'))-$this->register_day*86400;
            }
            //查询临时车
            $where_temp = [
                ['car_id', '=', $car_number],
                ['village_id', '=', $passage_info['village_id']],
                ['add_time', '>',$addTime],
                ['add_time', '<', strtotime(date('Y-m-d 23:59:59'))],
            ];
            $temp_info = $db_house_village_visitor->get_one($where_temp);
            if ($parkTime) {
                $pri_temp_info = $temp_info && !is_array($temp_info) ? $temp_info->toArray() : $temp_info;
                fdump_api(['查询临时车结果'=>$car_number,'park_config' => $park_config,'temp_info' => $pri_temp_info,'where_temp' => $where_temp],'D3Park/plateresult'.$parkTime,true);
            }
            /*** 重复代码
            $showscreen_data['car_type']= 'temporary_type';
            $showscreen_data['content']= '临时车未登记，请扫码入场';
            $showscreen_data['voice_content']= 3;

            //开启临时车登记配置项，查询登记信息
            if (empty($temp_info)&&$park_config['register_type']==1) {
                //TODO:屏显和语音提示错误信息
                $this->addParkShowScreenLog($showscreen_data);
                //企业微信群提醒
                $webhookurl = $this->webhookurl;
                $data = [
                    "msgtype" => "markdown",
                    "markdown" => [
                        "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆未登记,<font color='info'>**" . $passage_info['passage_name'] . "**</font>设备不允许通行，请先登记。

                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**入口**</font> 
                                 >行为原因: <font color='info'>**临时车未登记，禁止入场**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                    ],
                ];
                http_request($webhookurl, 'POST', json_encode($data));
                //停车设备不存在
                if ($parkTime) {
                    fdump_api(['屏显和语音提示错误信息'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
                }
                return false;
            } //获取配置的免登记时长
            elseif (!empty($park_config) && !empty($park_config['register_day'])) {
                $addTime = strtotime(date('Y-m-d 00:00:00')) - $park_config['register_day'] * 86400;
            } else {
                $addTime = strtotime(date('Y-m-d 00:00:00')) - $this->register_day * 86400;
            }
            //查询临时车登记数据
            $where_temp = [
                ['car_id', '=', $car_number],
                ['village_id', '=', $passage_info['village_id']],
                ['add_time', '>', $addTime],
                ['add_time', '<', strtotime(date('Y-m-d 23:59:59'))],
            ];
            $temp_info = $db_house_village_visitor->get_one($where_temp);
            if ($parkTime) {
                fdump_api(['查询临时车结果'=>$car_number,'$temp_info' => $temp_info,'$park_config' => $park_config],'D3Park/plateresult'.$parkTime,true);
            }
            */
            // 是否是多车多位的月租车
            $isCarsAndPositionsMonth = 0;
            if (isset($car_info['car_position_id']) && intval($car_info['car_position_id']) > 0 && isset($car_info['car_id']) && intval($car_info['car_id']) > 0) {
                try {
                    $db_house_village_parking_position = new HouseVillageParkingPosition();
                    $wherePosition = [];
                    $wherePosition[] = ['village_id', '=', $passage_info['village_id']];
                    $wherePosition[] = ['position_id', '=', intval($car_info['car_position_id'])];
                    $wherePosition[] = ['end_time', '>=', $now_time];
                    $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, 'position_id, village_id, start_time, end_time');
                    if ($positionObj && !is_array($positionObj)) {
                        $position = $positionObj->toArray();
                    }
                    if (!empty($position) && isset($position['position_id']) && $position['position_id']) {
                        $isCarsAndPositionsMonth = 1;
                    }
                } catch (\Exception $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'func_get_args' => func_get_args()], '$d3IsCarsAndPositionsMonth');
                }
            }
            if ($parkTime) {
                fdump_api(['是否是多车多位的月租车'=>$car_number,'isCarsAndPositionsMonth' => $isCarsAndPositionsMonth],'D3Park/plateresult'.$parkTime,true);
            }
            $showscreen_data['car_type'] = 'temporary_type';
            $showscreen_data['content'] = '临时车未登记，禁止入场';
            $showscreen_data['voice_content']= 3;
            if (empty($temp_info) && $park_config['register_type'] == 1 && $isCarsAndPositionsMonth == 0) {
                //TODO:屏显和语音提示错误信息
                $this->addParkShowScreenLog($showscreen_data);
                //企业微信群提醒
                $webhookurl = $this->webhookurl;
                $data = [
                    "msgtype" => "markdown",
                    "markdown" => [
                        "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆未登记,<font color='info'>**" . $passage_info['passage_name'] . "**</font>设备不允许通行，请先登记。
                             >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                             >车牌号码: <font color='info'>**" . $car_number . "**</font>
                             >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                             >通行状态: <font color='info'>**拒绝通行**</font> 
                             >通行方向: <font color='info'>**入口**</font> 
                             >行为原因: <font color='info'>**临时车未登记，禁止入场**</font> 
                             >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                    ],
                ];
                http_request($webhookurl, 'POST', json_encode($data));
                //停车设备不存在
                if ($parkTime) {
                    fdump_api(['屏显和语音提示错误信息'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
                }
                return false;
            } else {
                $park_info_car = $db_in_park->getOne1($park_where);
                if($park_info_car && !$park_info_car->isEmpty()){
                    $park_info_car=$park_info_car->toArray();
                }else{
                    $park_info_car=array();
                }
                if ($parkTime) {
                    fdump_api(['查询车辆'=>$car_number,'park_info_car' => $park_info_car],'D3Park/plateresult'.$parkTime,true);
                }
                if (empty($park_info_car) || !isset($park_info_car['id'])) {
                    //写入车辆入场表
                    $insert_id = $db_in_park->insertOne($park_data);
                    if ($parkTime) {
                        fdump_api(['in_park' => '插入in_park表', 'park_data' => $park_data, 'insert_id' => $insert_id], 'D3Park/plateresult' . $parkTime, true);
                    }
                    //写入车辆入场表
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $car_number;
                    $car_access_record['accessType'] = 1;
                    $car_access_record['accessTime'] = $now_time;
                    $car_access_record['accessMode'] = 9;
                    $car_access_record['park_sys_type'] = $park_sys_type;
                    if (!isset($car_access_record['user_name']) || !$car_access_record['user_name']) {
                        $car_access_record['user_name'] = empty($temp_info['visitor_name']) ? '' : $temp_info['visitor_name'];
                    }
                    if (!isset($car_access_record['user_phone']) || !$car_access_record['user_phone']) {
                        $car_access_record['user_phone'] = empty($temp_info['visitor_phone']) ? '' : $temp_info['visitor_phone'];
                    }
                    $car_access_record['is_out'] = 0;
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_order_id = date('YmdHis') . rand(100, 999);
                    $car_access_record['order_id'] = $car_access_order_id;
                    $car_access_record['update_time'] = $now_time;
                    if (isset($insert_id) && !empty($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    if ($parkTime) {
                        fdump_api(['写入车辆' => $car_number, 'car_access_record' => $car_access_record, 'in_park_id' => $insert_id], 'D3Park/plateresult' . $parkTime, true);
                    }
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                    if ($record_id > 0) {
                        /*
                        $access_record_where_near = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['is_out', '=', 0],
                            ['del_time', '<', 1],
                            ['record_id', '<', $record_id],
                        ];
                        //最近一条未出场的 记录
                        $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                        if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                            $access_record_tmp=$access_record_tmp->toArray();
                            $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                        }
                        */
                        $in_park_info = $db_in_park->getOne(array('id' => $insert_id), '*');
                        if ($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])) {
                            $saveArr = array('access_record_id' => $record_id, 'access_order_id' => $car_access_order_id);
                            $db_in_park->saveOne(array('id' => $insert_id), $saveArr);
                        }
                        if ($parkTime) {
                            fdump_api(['car_number' => $car_number, 'record_id' => $record_id, 'msg' => '数据记录添加完成'], 'D3Park/plateresult' . $parkTime, true);
                        }
                        $tmpUpdateRecordArr = array('car_number' => $car_number, 'in_park_id' => $insert_id, 'village_id' => $passage_info['village_id'], 'record_id' => $record_id, 'access_order_id' => $car_access_order_id, 'access_type' => 1, 'access_time' => time());
                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                    }
                    if ($parkTime) {
                        fdump_api(['写入车辆' => $car_number, 'record_id' => $record_id], 'D3Park/plateresult' . $parkTime, true);
                    }

                } else {
                    $insert_id = $park_info_car['id'];
                    $record_id = isset($park_info_car['access_record_id']) ? intval($park_info_car['access_record_id']) : 0;
                    $car_access_order_id = isset($park_info_car['access_order_id']) ? trim($park_info_car['access_order_id']) : '';
                }
                if ($isImageToJob) {
                    $imageToJob['in_park_id'] = $insert_id;
                }
                //$in_record_info = $db_house_village_car_access_record->getOne($whereAccessRecord);

                //TODO:屏显和语音提示特殊车辆通行
                $showscreen_data['content'] = '欢迎光临';
                $showscreen_data['voice_content'] = 1;
                if ($parkTime) {
                    fdump_api(['写入车辆'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //特殊车辆通行
                try {
                    // todo 目前只有临时车支持这个 访客 二维码开门
                    (new HouseDeviceQrcodeService())->inParkVisitorToDevice($visitor_info);
                } catch (\Exception $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errInParkVisitorToDeviceLog', true);
                } catch (\Throwable $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errInParkVisitorToDeviceLog', true);
                } catch (\RuntimeException $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errInParkVisitorToDeviceLog', true);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errInParkVisitorToDeviceLog', true);
                }
                if ($isImageToJob && ($record_id || $insert_id)) {
                    if (isset($car_access_order_id) && $car_access_order_id) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                    }
                    $imageToJob['in_record_id'] = $record_id;
                    (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                }
                return true;
            }
        }
        return false;
    }

    /***
     *获取车辆绑定的车位 最长停车时间
     ***/
    public function getCarRightInPosition($car = array(), $village_id = 0, $garage_id = 0)
    {

        $bind_position_ids = array();
        if ($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])) {
            $bind_position_ids = explode(',', $car['bind_position_ids']);
            $bind_position_ids = array_map('intval', $bind_position_ids);
        }
        if ($car && $car['car_position_id']) {
            $bind_position_ids[] = intval($car['car_position_id']);
        }
        $parkTime = date('YmdH');
        $nowtime = time();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $car_number = '';
        if ($car && isset($car['car_number'])) {
            //检查一下异常的占用 重置成未占用 走队列
            /*
            $jobParams = [
                'jobType' => 'resettingUseParkingPosition', 'village_id' => $village_id, 'car_id' => $car['car_id'],
                'nowTime' => $nowtime
            ];
            (new ParkCarToJobService())->HandleUseParkingPositionToJob($jobParams);
            */

            //检查一下异常的占用 重置成未占用
            $car_number = $car['province'] . $car['car_number'];
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            //$whereArr[] = array('use_car_id', '=', $car['car_id']);
            $whereArr[] = array('use_car_number', '=', $car_number);
            $whereArr[] = array('use_start_time', '<', $nowtime);
            $fieldStr = 'position_id,garage_id,end_time,access_record_id,use_start_time,use_car_number,use_car_id';
            $use_position_obj = $db_house_village_parking_position->getList($whereArr, $fieldStr, 0);
            if ($use_position_obj && !$use_position_obj->isEmpty()) {
                $use_position = $use_position_obj->toArray();
                $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                $this->updateParkingPositionUse($whereArr, $saveArr, $car_number, 'in_park_check');
            }
        }
        $position = array();
        fdump_api(['bind_position_ids' => $bind_position_ids, 'car_number' => $car_number], 'D3Park/plateresult' . $parkTime, 1);
        if ($bind_position_ids) {
            $bind_position_ids = array_unique($bind_position_ids);
            /*
            $positionWhere=array();
            $positionWhere[] = array('village_id', '=', $village_id);
            $positionWhere[] = array('position_id', 'in', $bind_position_ids);
            $positionWhere[] = array('use_start_time', '>', 100);
            $positionWhere[] = array('use_start_time', '<', $nowtime);
            $fieldStr = 'position_id,garage_id,end_time,access_record_id,use_start_time,use_car_number,use_car_id';
            $use_position_obj = $db_house_village_parking_position->getList($positionWhere, $fieldStr, 0);
            $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
            if ($use_position_obj && !$use_position_obj->isEmpty()) {
                $use_positions = $use_position_obj->toArray();
                foreach ($use_positions as $pvv){
                    if(!empty($pvv['use_car_number'])){
                        $access_record_where=array('car_number'=>$pvv['use_car_number'],'park_id'=>$village_id,'garage_id'=>$pvv['garage_id']);
                        $park_access_record_obj = $db_house_village_car_access_record->getOne($access_record_where,'record_id,business_id,car_number,accessType,accessTime','accessTime DESC,record_id DESC');
                        if($park_access_record_obj && !$park_access_record_obj->isEmpty() && $park_access_record_obj['accessType']==2){
                           //车子已经出去了，车位占用未被解除的
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => 0);
                            $this->updateParkingPositionUse(array('position_id'=>$pvv['position_id'],'village_id'=>$village_id), $saveArr, $pvv['use_car_number'], 'in_park_check');
                        }
                    }
                }
            }
            */
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('position_id', 'in', $bind_position_ids);
            if ($garage_id > 0) {
                $whereArr[] = array('garage_id', '=', $garage_id);
            }
            //$end_time = $nowtime - 86400;
            //$whereArr[] = array('end_time', '>', $end_time);
            $whereArr[] = array('use_car_id', '=', 0);
            $whereArr[] = array('use_start_time', '<', 100);
            
            $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
            if ($positionObj && !$positionObj->isEmpty()) {
                $position = $positionObj->toArray();
            }
            fdump_api(['whereArr' => $whereArr, 'car_number' => $car_number,'position'=>$position], 'D3Park/plateresult' . $parkTime, 1);
            if (empty($position) && count($bind_position_ids)==1 && $car && isset($car['car_id'])) {
                try {
                    $db_house_village_parking_car = new HouseVillageParkingCar();
                    $whereCarPositionCount = [];
                    $whereCarPositionCount[] = ['is_del', '=', 1];
                    $whereCarPositionCount[] = ['village_id', '=', $village_id];
                    $whereCarPositionCount[] = ['car_id', '<>', $car['car_id']];
                    $whereRaw='(car_position_id ='.$bind_position_ids['0'] .' OR FIND_IN_SET('.$bind_position_ids['0'].',bind_position_ids))';
                    $otherCarObj = $db_house_village_parking_car->getOneCar($whereCarPositionCount,$whereRaw, 'car_id');
                    if (empty($otherCarObj) || $otherCarObj->isEmpty()) {
                        $wherePosition = [];
                        $wherePosition[] = ['village_id', '=', $village_id];
                        $wherePosition[] = ['position_id', '=', $bind_position_ids['0']];
                        if ($garage_id > 0) {
                            $wherePosition[] = array('garage_id', '=', $garage_id);
                        }
                        $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, '*');
                        if ($positionObj && !$positionObj->isEmpty()) {
                            $position = $positionObj->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    fdump_api(['bind_position_ids' => $bind_position_ids, 'car_number' => $car_number,'err'=>$e->getMessage()], 'D3Park/plateresult' . $parkTime, 1);
                }
            }
        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        //$this->updateD3WhiteList($position, $car, $village_id, 'in_park');
        return $position;
    }
    
    public function updateD3WhiteList($position=array(),$car=array(),$village_id=0,$comfrom=''){
        $now_time=time();
        $car_end_time=$car['end_time'];
        $garage_id=0;
        if($position && isset($position['garage_id'])){
            $garage_id=$position['garage_id'];
        }
        if($position && $position['start_time']<$now_time && $position['end_time']>$car_end_time){
            $car_end_time=$position['end_time'];
        }
        $wData=array('village_id'=>$village_id,'garage_id'=>$garage_id,'car_end_time'=>$car_end_time);
        $wData['car_number']=$car['province'].$car['car_number'];
        $wData['cfrom']=$comfrom;
        $wData['car_info']=$car;
        if($car_end_time>$now_time){
            //月租车，添加白名单
            $this->addWhitelist($wData);
        }else{
            //月租车，删除白名单
            $this->delWhitelist($wData);
        }
    }
    public function getCarRightOutPosition($car=array(),$village_id=0,$garage_id=0,$car_number=''){

        $whereArr = array();
        if(!empty($car_number)){
            $whereArr[]=array('use_car_number', '=', $car_number);
        }elseif($car && isset($car['car_id']) && !empty($car['car_id'])){
            $whereArr[]=array('use_car_id', '=', $car['car_id']);
        }
        $parkTime=date('YmdH');
        $position = array();
        if(!empty($whereArr)) {
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $whereArr[] = array('village_id', '=', $village_id);
            if ($garage_id > 0) {
                $whereArr[] = array('garage_id', '=', $garage_id);
            }
            $positionObj = $db_house_village_parking_position->getFind($whereArr, '*', 'end_time desc');
            if ($positionObj && !$positionObj->isEmpty()) {
                $position = $positionObj->toArray();
            }
			fdump_api([__LINE__,'car_number'=>$car_number,'whereArr'=>$whereArr,'position' => $position], 'D3Park/plateresult'.$parkTime, 1);
            /*
            if (empty($position) && isset($car['car_position_id']) && intval($car['car_position_id']) > 0 && isset($car['car_id']) && intval($car['car_id']) > 0) {
                try {
                    $db_house_village_parking_car = new HouseVillageParkingCar();
                    $whereCarPositionCount = [];
                    $whereCarPositionCount[] = ['car_position_id', '=', intval($car['car_position_id'])];
                    $whereCarPositionCount[] = ['is_del', '=', 1];
                    $whereCarPositionCount[] = ['car_id', '<>', $car['car_id']];
                    $otherCar = $db_house_village_parking_car->getOne($whereCarPositionCount, 'car_id');
                    if (!isset($otherCar['car_id']) || !$otherCar['car_id']) {
                        $wherePosition = [];
                        $wherePosition[] = ['village_id', '=', $village_id];
                        $wherePosition[] = ['position_id', '=', intval($car['car_position_id'])];
                        $positionObj = $db_house_village_parking_position->getFindOne($wherePosition, 'position_id, village_id, start_time, end_time, garage_id, position_num');
                        if ($positionObj && !is_array($positionObj)) {
                            $position = $positionObj->toArray();
                        }
                    }
                } catch (\Exception $e) {
                    fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'func_get_args' => func_get_args()],'$d3GetCarRightOutPosition');
                }
            }
            */
        }
        if (!empty($position)) {
            $position['start_time'] = isset($position['start_time']) && intval($position['start_time']) > 0 ? intval($position['start_time']) : 0;
        }
        $nowTime=time();
        if(empty($position)){
            $bind_position_ids=array();
            if($car && isset($car['bind_position_ids']) && !empty($car['bind_position_ids'])){
                $bind_position_ids=explode(',',$car['bind_position_ids']);
                $bind_position_ids=array_map('intval', $bind_position_ids);
            }
            if($car && $car['car_position_id']){
                $bind_position_ids[]=intval($car['car_position_id']);
            }
            if(!empty($bind_position_ids)){
                $bind_position_ids = array_unique($bind_position_ids);
                $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
                $access_record_where_near = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $village_id],
                    ['accessType', '=',1],
                    ['del_time', '<', 1],
                    ['accessTime', '<=', $nowTime],
                ];
                $houseVillageParkingCarBindPositionDb=new HouseVillageParkingCarBindPosition();
                if(count($bind_position_ids)==1){
                    //只绑了一个车位
                    $whereArr=array(['position_id','=',$bind_position_ids['0']]);
                    if ($garage_id > 0) {
                        $whereArr[] = array('garage_id', '=', $garage_id);
                    }
                    $positionObj = $db_house_village_parking_position->getFind($whereArr, '*');
                    if ($positionObj && !$positionObj->isEmpty()) {
                        //绑定的这个没被占用
                        $position = $positionObj->toArray();
						fdump_api([__LINE__,'car_number'=>$car_number,'whereArr'=>$whereArr,'position' => $position], 'D3Park/plateresult'.$parkTime, 1);
                        if($position['use_car_id']<1 && empty($position['use_car_number'])){
                            $position['start_time']=$position['start_time'] ? $position['start_time']:0;
                            $whereBind=array('position_id'=>$bind_position_ids['0'],'car_id'=>$car['car_id'],'del_time'=>0);
                            $oneBindObj=$houseVillageParkingCarBindPositionDb->getOneData($whereBind,'*');
                            if($oneBindObj && !$oneBindObj->isEmpty()){
                                $bind_position_time=$oneBindObj['bind_position_time'];
                                // 通行时间小于出场时间的最近的一条入场时间的记录
                                $park_access_record_obj = $db_house_village_car_access_record->getOne($access_record_where_near,'*','record_id DESC');
                                if($park_access_record_obj && !$park_access_record_obj->isEmpty()){
                                    $park_access_record=$park_access_record_obj->toArray();
                                    if($park_access_record['flag_status']=='temporaryCarNoPosition' || $park_access_record['park_car_type']=='temporaryCarNoPosition'|| $park_access_record['car_type']=='temporaryCarNoPosition'){
                                        //无车位进场的
                                        $inAccessTime=$park_access_record['accessTime']>100 ? $park_access_record['accessTime']:$nowTime;
                                        $inAccessTimeDay=date('Ymd',$inAccessTime);
                                        $inAccessTimeDay=intval($inAccessTimeDay);
                                        $bindTimeDay=date('Ymd',$bind_position_time);
                                        $bindTimeDay=intval($bindTimeDay);
                                        $chaDay=$bindTimeDay-$inAccessTimeDay;
                                        if($chaDay>0 && $position['end_time'] > $nowTime){
                                            //车位有效，并且绑定时间和进场时间隔了至少一个零点
                                            $bindTimeDate=date('Y-m-d',$bind_position_time);
                                            $bindTimeDay_time=strtotime($bindTimeDate);
                                            //绑定时间日 凌晨算临时车结束时间
                                            $position['temporary_end_time']=$bindTimeDay_time-1;
                                        }
                                    }
                                }

                            }
                        }else{
                            $position=array();
                        }
                    }
                }
                fdump_api(['position'=>$position,'bind_position_ids' => $bind_position_ids], 'D3Park/plateresult'.$parkTime, 1);
                //还是没找到车位
                if(empty($position)){
                    $whereBindArr=array();
                    $whereBindArr[]=array('position_id','in',$bind_position_ids);
                    $whereBindArr[]=array('del_time','<',1);
                    $whereBindArr[] = array('village_id', '=', $village_id);
                    $carPositionBindObj=$houseVillageParkingCarBindPositionDb->getListData($whereBindArr,'position_id,car_id,bind_position_time,garage_id,village_id','id DESC', 0,100);
                    $usePositionArr=array();
                    if($carPositionBindObj && !$carPositionBindObj->isEmpty()){
                        $carPositionBind= $carPositionBindObj->toArray();
                        $tmpCarPositionBind=array();
                        foreach ($carPositionBind as $ppv){
                            if(isset($tmpCarPositionBind[$ppv['position_id']])){
                                $tmpCarPositionBind[$ppv['position_id']][]=$ppv['car_id'];
                            }else{
                                $tmpCarPositionBind[$ppv['position_id']]=array($ppv['car_id']);
                            }
                        }
                        foreach ($bind_position_ids as $p_id){
                            $tmpxPosition=isset($tmpCarPositionBind[$p_id]) && $tmpCarPositionBind[$p_id] ? array_unique($tmpCarPositionBind[$p_id]):'';
                            if(empty($tmpxPosition)){
                                $usePositionArr[]=$p_id;
                            }else if(is_array($tmpxPosition) && in_array($car['car_id'],$tmpxPosition) && count($tmpxPosition)==1){
                                $usePositionArr[]=$p_id;
                            }
                        }
                    }else{
                        $usePositionArr=$bind_position_ids;
                    }
                    fdump_api(['usePositionArr'=>$usePositionArr,'bind_position_ids' => $bind_position_ids], 'D3Park/plateresult'.$parkTime, 1);
                    if(!empty($usePositionArr)){
                        $whereArr=array(['position_id','in',$usePositionArr]);
                        $whereArr[]=array('start_time','<',$nowTime);
                        $whereArr[]=array('end_time','>',$nowTime);
                        if ($garage_id > 0) {
                            $whereArr[] = array('garage_id', '=', $garage_id);
                        }
                        $positionObj = $db_house_village_parking_position->getFind($whereArr, '*','use_start_time ASC,position_id DESC');
                        if ($positionObj && !$positionObj->isEmpty()) {
                            $position = $positionObj->toArray();
                        }
                    }
                }
            }
        }
        //$this->updateD3WhiteList($position,$car,$village_id,'out_park');
        return $position;
    }
    public function updateParkingPositionUse($whereArr=array(),$saveArr=array(),$car_number='',$comfrom='out_park'){
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        fdump_api(['car_number'=>$car_number,'comfrom'=>$comfrom,'whereArr'=>$whereArr,'saveArr'=>$saveArr],'park_temp/00updateParkingPositionUse'.date('H'),1);
        $ret=$db_house_village_parking_position->saveOne($whereArr,$saveArr);
        return  $ret;
    }

        /**
     * 车辆出场
     * @author:zhubaodi
     * @date_time: 2022/3/17 11:52
     */
    public function out_park($car_number,$data,$config_data,$village_info,$passage_info,$param=[]){
        //  fdump_api([$car_number,$data,$config_data,$village_info,$passage_info,$param],'out_park_0906',1);
        if (isset($param['parkTime'])&&$param['parkTime']) {
            $parkTime = $param['parkTime'];
        } else {
            $parkTime = 0;
        }
        if (isset($param['car_number'])&&$param['car_number']) {
            $car_number = trim($param['car_number']);
        } elseif (isset($data['AlarmInfoPlate']['result']['PlateResult']['license'])) {
            $car_number = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        }
        if (isset($param['serialno'])&&$param['serialno']) {
            $serialno = trim($param['serialno']);
        } elseif (isset($data['AlarmInfoPlate']['serialno'])) {
            $serialno = $data['AlarmInfoPlate']['serialno'];
        } else {
            $serialno = '';
        }
        $garage_id=0;
        if($passage_info && isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }else if($passage_info && isset($passage_info['passage_area']) && !empty($passage_info['passage_area']) && $passage_info['area_type']==2){
            $garage_id=intval($passage_info['passage_area']);
        }
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_park_free = new HouseVillageParkFree();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_park_plateresult_log = new ParkPlateresultLog();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_house_village_park_config = new HouseVillageParkConfig();
        $db_park_passage = new ParkPassage();
        $db_house_village_visitor = new HouseVillageVisitor();
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        if (!empty(cache('config_'.$passage_info['village_id'].'_'.$garage_id))) {
            $park_config = cache('config_'.$passage_info['village_id'].'_'.$garage_id);
        }else {
            //$park_config = $db_house_village_park_config->getFind(['village_id' => $passage_info['village_id']]);
            $park_config =$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$passage_info['village_id']);
            cache('config_'.$passage_info['village_id'].'_'.$garage_id,$park_config);
        }
        $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
        $showscreen_data=[];
        $showscreen_data['passage']=$passage_info;
        $showscreen_data['village_id']=$passage_info['village_id'];
        $showscreen_data['car_number']= $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        $showscreen_data['channel_id']= $data['AlarmInfoPlate']['serialno'];
        $showscreen_data['car_type']= '';
        $showscreen_data['voice_content']= 2;
        $showscreen_data['content']= '一路平安';
        $now_time = time();
        $park_log_data = [];
        $park_log_data['car_number'] = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
        $park_log_data['channel_id'] = $data['AlarmInfoPlate']['serialno'];
        $park_log_data['park_type'] = 2;
        $park_log_data['village_id']=$passage_info['village_id'];
        $park_log_data['add_time'] = $now_time;
        $logId = $db_park_plateresult_log->add($park_log_data);
        $whereInRecord = [ 'park_id' => $passage_info['village_id'],'car_number' => $car_number, 'is_out' => 0, 'park_sys_type' => $park_sys_type, 'del_time' => 0];
        $in_park_info = $db_in_park->getOne1($whereInRecord);
        if($in_park_info && !$in_park_info->isEmpty()){
            $in_park_info=$in_park_info->toArray();
        }
        if ($parkTime) {
            fdump_api(['添加识别记录' => $car_number, 'park_log_data' => $park_log_data, 'showscreen_data' => $showscreen_data, 'in_park_info' => $in_park_info], 'D3Park/plateresult' . $parkTime, true);
        }
        $rand_num = date('Ymd', $now_time);// 换成日期存储
        //   $up_dir = './upload/park_log/' . $rand_num . '/'.$data['AlarmInfoPlate']['serialno'].'/';
        $path='/upload/park_log/' . $rand_num . '/'.$serialno.'/';
        $up_dir =$_SERVER['DOCUMENT_ROOT'].$path;
        $park_data = [];
        $park_data['out_time'] = $now_time;
        $park_data['is_out'] = 1;
        $park_data['is_paid'] = 1;
        // 记录车辆进入信息
        $car_access_record = [];
        $ymdhis = date('Ymdhis', $now_time);
        $imageToJob = [];
        $isImageToJob = false;
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
            $imageFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            $savePathFile = $ymdhis . '_image_big_' . $car_number;
            $isImageToJob = true;
            $imageToJob['park_type'] = 'out_park';
            $imageToJob['serialno'] = $serialno;
            $imageToJob['car_number'] = $car_number;
            $imageToJob['out_time'] = $now_time;
            $imageToJob['village_id'] = $passage_info['village_id'];
            $imageToJob['nowTime'] = $now_time;
            $imageToJob['imageSavePath'] = $up_dir;
            $imageToJob['imageFile'] = $imageFile;
            $imageToJob['imageFileSaveFileName'] = $savePathFile;
            if(isset($data['from_xtype'])){
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }
            if ($parkTime) {
                fdump_api(['车辆相关信息-1图片' => $car_number], 'D3Park/plateresult' . $parkTime, true);
            }
        }
        if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']) && trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
            $imageFragmentFile = trim($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            $savePathFragmentFile = $ymdhis . '_image_small_' . $car_number;
            if (!$isImageToJob) {
                $isImageToJob = true;
                $imageToJob['park_type'] = 'out_park';
                $imageToJob['serialno'] = $serialno;
                $imageToJob['car_number'] = $car_number;
                $imageToJob['out_time'] = $now_time;
                $imageToJob['village_id'] = $passage_info['village_id'];
                $imageToJob['nowTime'] = $now_time;
                $imageToJob['imageSavePath'] = $up_dir;
            }
            $imageToJob['imageFragmentFile'] = $imageFragmentFile;
            $imageToJob['imageFragmentFileSaveFileName'] = $savePathFragmentFile;
            if(isset($data['from_xtype'])){
                $imageToJob['from_xtype'] = $data['from_xtype'];
            }
            if ($parkTime) {
                fdump_api(['车辆相关信息-2图片' => $car_number], 'D3Park/plateresult' . $parkTime, true);
            }
        }
        $imageBigUrlPath='';
        $imageSmallUrlPath='';
        $car_access_record['channel_id']=$passage_info['id'];
        $car_access_record['channel_number']=$passage_info['channel_number'];
        $car_access_record['channel_name']=$passage_info['passage_name'];
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imagePath']) && $data['AlarmInfoPlate']['result']['PlateResult']['imagePath']){
            $imageUrlPathTmp= base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imagePath']);
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageBigUrlPath=$imageUrlPathArr['0'];
                $imageBigUrlPath=str_replace('http:','https:',$imageBigUrlPath);
                $car_access_record['accessBigImage']=$imageBigUrlPath;
                $park_data['out_image_big']=$imageBigUrlPath;
            }
        }
        if(isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']) && $data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']){
            $imageUrlPathTmp= base64_decode($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentPath']);
            if($imageUrlPathTmp && (strpos($imageUrlPathTmp,'ttp://') || strpos($imageUrlPathTmp,'ttps://'))){
                $imageUrlPathArr=explode('?',$imageUrlPathTmp);
                $imageSmallUrlPath=$imageUrlPathArr['0'];
                $imageSmallUrlPath=str_replace('http:','https:',$imageSmallUrlPath);
                $car_access_record['accessImage']=$imageSmallUrlPath;
                $park_data['out_image_small']=$imageSmallUrlPath;
            }
        }
        $out_data = [];
        $out_data['car_number'] = $car_number;
        $out_park_info=[];
        $out_time=$now_time;
        if (!empty($in_park_info)) {
            if ($in_park_info['in_time']>($now_time-60)){
                // 如果进入记录在一分钟内  避免由于识别到车辆尾部导致的意外开闸 直接反馈不允许开闸
                return false;
            }
            $out_data['in_time'] = $in_park_info['in_time'];
            $out_data['order_id'] = isset($in_park_info['order_id']) && $in_park_info['order_id'] ? $in_park_info['order_id'] : '';
        }else{
            $whereInOut = ['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 1, 'del_time' => 0];
            $out_park_info = $db_in_park->getOne1($whereInOut);
            if($out_park_info && !$out_park_info->isEmpty()){
                $out_park_info=$out_park_info->toArray();
            }
            $out_time=$now_time-$park_config['out_park_time']*60;
        }
        $out_data['out_time'] = $now_time;
        $out_data['park_id'] = $passage_info['village_id'];
        $out_data['park_sys_type'] = $park_sys_type;

        //查询免费车
        if (!empty(cache('freeList_'.$passage_info['village_id']))) {
            $free_list= cache('freeList_'.$passage_info['village_id']);
        }else {
            $free_list_obj = $db_house_village_park_free->getList(['village_id' => $passage_info['village_id']]);
			$free_list='';
            if ($free_list_obj && !$free_list_obj->isEmpty()) {
                $free_list = $free_list_obj->toArray();
            }
            cache('freeList_'.$passage_info['village_id'],$free_list, 3600);
        }
        $province = mb_substr($car_number, 0, 1);
        $car_no = mb_substr($car_number, 1);
        if ($isImageToJob) {
            $imageToJob['in_park_id'] = isset($in_park_info['id']) ? intval($in_park_info['id']) : 0;
            $imageToJob['in_time'] = isset($in_park_info['in_time']) ? intval($in_park_info['in_time']) : 0;
            $imageToJob['order_id'] = isset($in_park_info['order_id']) ? trim($in_park_info['order_id']) : '';
            $imageToJob['car_number_no'] = $car_no;
        }
        $carCachekey='village:'.$passage_info['village_id'].':car:'.$car_number;
        if (!empty(cache($carCachekey))) {
            $car_info = cache($carCachekey);
            if (!isset($car_info['end_time']) || $car_info['end_time'] <= $now_time) {
                // 缓存中取不出来车辆信息或者截止时间过期了 避免出现问题 及时查询下最新的信息并缓存
                $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                if($car_info && !$car_info->isEmpty()){
                    $car_info=$car_info->toArray();
                    cache($carCachekey,$car_info,3600);
                }else{
                    cache($carCachekey,null);
                }
            }
        }else {
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
            if($car_info && !$car_info->isEmpty()){
                $car_info=$car_info->toArray();
                cache($carCachekey,$car_info,3600);
            }else{
                cache($carCachekey,null);
            }
        }
        
        $user_name='';
        $user_phone='';
        $car_access_record['garage_id']=$garage_id;
        $outRightPosition=array();
        if($car_info && isset($car_info['car_id'])){
            $outRightPosition=$this->getCarRightOutPosition($car_info,$passage_info['village_id'],$garage_id,$car_number);
            fdump_api(['car_info'=>$car_info,'rightPosition' => $outRightPosition], 'D3Park/plateresult'.$parkTime, 1);
            if(!empty($outRightPosition)){
                if($outRightPosition['start_time']<=$now_time && $outRightPosition['end_time']>=$car_info['end_time']){
                    $car_info['end_time']=$outRightPosition['end_time'];
                    cache($carCachekey,$car_info,3600);
                    if(isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time']>0){
                        $car_info['end_time']=$outRightPosition['temporary_end_time'];
                        $car_info['temporary_end_time']=$outRightPosition['temporary_end_time'];
                    }
                }
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $outRightPosition['start_time']>$now_time){
                    //车位时间还没开始
                    $car_info['end_time']=0;
                }
                
                if (isset($outRightPosition['position_id']) && $outRightPosition['position_id']) {
                    $car_access_record['position_id']=$outRightPosition['position_id'];
                }
                if (isset($outRightPosition['garage_id']) && $outRightPosition['garage_id']) {
                    $car_access_record['garage_id']=$outRightPosition['garage_id'];
                }
                if (isset($outRightPosition['position_num']) && $outRightPosition['position_num']) {
                    $car_access_record['position_num']=$outRightPosition['position_num'];
                }
            }else{
                $car_info['end_time']=0;
                /*
                //没有可用车位
                $bind_position_ids=array();
                if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                    //新的绑定车位 多车多位逻辑
                    $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                    $car_info['end_time']=0;   //没有可用车位时
                }
                if(isset($car_info['garage_id']) && $car_info['garage_id']>0 &&  $car_info['garage_id']!=$garage_id){
                    //进入的不是同一个车库
                    $car_info['end_time']=0;
                }
                */
            }
            $car_access_record['car_id']=$car_info['car_id'];
            $user_name  = empty($car_info['car_user_name']) ? '' : $car_info['car_user_name'];
            $user_phone = empty($car_info['car_user_phone'])? '' : $car_info['car_user_phone'];
        }

        if ($parkTime) {
            fdump_api(['查询车辆到期时间'=>$car_number,'province' => $province,'car_no' => $car_no,'car_info' => $car_info,'in_park_info' => $in_park_info],'D3Park/plateresult'.$parkTime,true);
        }
        $car_visitor_cache='village:'.$passage_info['village_id'].':car_visitor:' . $car_number;
        
        if (!$user_name && !$user_phone && !empty(cache($car_visitor_cache))) {
            $visitor_info = cache($car_visitor_cache);
            $user_name  = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid        = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        } elseif (!$user_name && !$user_phone) {
            $visitor_info = $db_house_village_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], 'id, visitor_name, visitor_phone, uid');
            cache($car_visitor_cache, $visitor_info);
            $user_name  = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
            $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
            $uid        = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
        } else {
            $visitor_info = [];
        }
        if ($user_name) {
            $car_access_record['user_name'] = $user_name;
        }
        if ($user_phone) {
            $car_access_record['user_phone'] = $user_phone;
        }
        if (isset($uid) && $uid) {
            $car_access_record['uid'] = $uid;
        }
        $starttime = $now_time - 100;
        $endtime = $now_time + 10;
        $park_out_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['out_time', '>=', $starttime],
            ['out_time', '<=', $endtime],
            ['del_time', '<', 1],
        ];
        $access_record_where = [
            ['car_number', '=', $car_number],
            ['park_id', '=', $passage_info['village_id']],
            ['accessType', '=',2],
            ['accessTime', '>=', $starttime],
            ['accessTime', '<=', $endtime],
            ['del_time', '<', 1],
        ];
        $record_id=0;
        if (!empty($free_list)) {
            if ($parkTime) {
                fdump_api(['免费车信息'=>$car_number,'free_list' => $free_list],'D3Park/plateresult'.$parkTime,true);
            }
            if (!empty($free_list)) {
                foreach ($free_list as $v) {
                    $first_number = substr($car_number, 0, strlen($v['first_name']));
                    $last_name = substr($car_number, '-' . strlen($v['last_name']));
                    $isFreePark = false;
                    if (!empty($v['first_name']) && $first_number==$v['first_name']) {
                        $isFreePark = true;
                    }elseif (!empty($v['last_name']) && $last_name==$v['last_name']) {
                        $isFreePark = true;
                    }elseif ($car_number == $v['free_park']) {
                        $isFreePark = true;
                    }
                    if ($isFreePark) {
                        if ($parkTime) {
                            fdump_api(['免费车信息条件' => $car_number, 'first_number' => $first_number, 'last_name' => $last_name, 'first_name' => $v['first_name'], 'last_name_v' => $v['last_name'], 'free_park' => $v['free_park']], 'D3Park/plateresult' . $parkTime, true);
                        }
                        $insert_id=0;
                        if ($in_park_info && isset($in_park_info['id']) && $in_park_info['id']) {
                            $insert_id=$in_park_info['id'];
                            $db_in_park->saveOne(['id'=>$in_park_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'is_out' => 0], $park_data);
                        }
                        /*
                        $out_data['total'] = 0;
                        $out_data['pay_type'] = 'free';
                        $out_park_info_car = $db_out_park->getOne($park_out_where);
                        if ($parkTime) {
                            $pri_out_park_info_car = $out_park_info_car && !is_array($out_park_info_car) ? $out_park_info_car->toArray() : $out_park_info_car;
                            fdump_api(['出场车辆信息'=>$car_number,'park_out_where' => $park_out_where,'park_info_car' => $pri_out_park_info_car,'out_data' => $out_data],'D3Park/plateresult'.$parkTime,true);
                        }
                        //写入车辆出场表
                        if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                            $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                            $insert_id = $db_out_park->insertOne($out_data);
                        } else {
                            $insert_id = $out_park_info_car['id'];
                        }
                        if ($isImageToJob) {
                            $imageToJob['out_park_id'] = $insert_id;
                        }
                        
                        */
                        
                        $record_id=0;
                        $car_access_order_id='';
                        if($in_park_info && isset($in_park_info['access_order_id']) && $in_park_info['access_order_id']){
                            $car_access_order_id=$in_park_info['access_order_id'];
                        }
                        if($out_park_info && isset($out_park_info['id'])){
                            $access_record_info = $db_house_village_car_access_record->getOne($access_record_where);
                            if($access_record_info && !$access_record_info->isEmpty()){
                                $record_id = isset($access_record_info['record_id']) ? intval($access_record_info['record_id']) : 0;
                                $car_access_order_id = isset($access_record_info['order_id']) ? trim($access_record_info['order_id']) : '';
                            }
                        }
                        //写入车辆出场表
                        if ($record_id<1) {
                            /*
                            $access_record_where_near = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                                ['del_time', '<', 1],
                                ['accessTime', '<=', $now_time],
                            ];
                            // 通行时间小于出场时间的最近的一条入场时间的记录
                            $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                            if ($park_info_car111 && !$park_info_car111->isEmpty()){
                                $park_info_car111=$park_info_car111->toArray();
                            }else{
                                $park_info_car111=array();
                                $park_info_car111['accessTime']=0;
                                $park_info_car111['order_id']='';
                            }
                            */
                            $park_time_tmp=0;
                            if($in_park_info && isset($in_park_info['in_time'])){
                                $park_time_tmp=$now_time - $in_park_info['in_time'];
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['park_time'] = $park_time_tmp;
                            $car_access_record['accessMode'] = '3';
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            //$car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[5];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $car_access_record['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            if ($parkTime) {
                                fdump_api(['记录出场'=>$car_number,'car_access_record' => $car_access_record,'record_id' => $record_id],'D3Park/plateresult'.$parkTime,true);
                            }
                            $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                            /*
                            if($in_park_info && isset($in_park_info['record_id'])){
                                $access_record_where_near = [
                                    ['record_id', '=', $in_park_info['record_id']],
                                ];
                                $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                            }
                            */
                        } 
                        //TODO:屏显和语音提示特殊车辆通行
                        $showscreen_data['content']='免费车通行';
                        if ($parkTime) {
                            fdump_api(['显屏'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                        }
                        if(!empty($outRightPosition)){
                            //释放车位
                            $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                            $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                            $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //特殊车辆通行
                        if ($isImageToJob && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return true;
                    }
                }
            }
        }
        //查询车辆到期时间
        if ($parkTime) {
            fdump_api(['car_info' => $car_info, 'in_park_info' => $in_park_info, 'out_park_info' => $out_park_info], 'D3Park/plateresult' . $parkTime, 1);
        }
        if (!empty($car_info) && $car_info['end_time'] > $now_time) {
            if (empty($in_park_info)&&!empty($out_park_info)&&$out_park_info['out_time']>$out_time){
                // 重复离场-判断下是否需要新增记录
                //TODO:屏显和语音提示车辆通行
                $showscreen_data['car_type']     = 'month_type';
                $showscreen_data['end_time']     = $car_info['end_time'];
                $showscreen_data['end_time_txt'] = date('Y-m-d',$car_info['end_time']);
                if ($parkTime) {
                    fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                }
                if(!empty($outRightPosition)){
                    //释放车位
                    $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                    $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>0);
                    $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //车辆通行
                return true;
            }
            $insert_id=0;
            if ($in_park_info && isset($in_park_info['id']) && $in_park_info['id']) {
                $insert_id=$in_park_info['id'];
                $db_in_park->saveOne(['id'=>$in_park_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'is_out' => 0], $park_data);
            }
           
            /*
            $out_data['total'] = 0;
            $out_data['pay_type'] = 'monthuser';
            //  $out_data['is_paid'] = 1;
            $out_park_info_car = $db_out_park->getOne($park_out_where);
            //写入车辆出场表
            if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                $insert_id = $db_out_park->insertOne($out_data);
            } else {
                $insert_id = $out_park_info_car['id'];
            }
            if ($isImageToJob) {
                $imageToJob['out_park_id'] = $insert_id;
            }
            */
            $record_id=0;
            $car_access_order_id='';
            if($in_park_info && isset($in_park_info['access_order_id']) && $in_park_info['access_order_id']){
                $car_access_order_id=$in_park_info['access_order_id'];
            }
            if($out_park_info && isset($out_park_info['id'])){
                $access_record_info = $db_house_village_car_access_record->getOne($access_record_where);
                if($access_record_info && !$access_record_info->isEmpty()){
                    if ($parkTime) {
                        fdump_api(['查询是否有对应出场车辆'=>$car_number,'access_record_where' => $access_record_where ],'D3Park/plateresult'.$parkTime,true);
                    }
                    $record_id = isset($access_record_info['record_id']) ? intval($access_record_info['record_id']) : 0;
                    $car_access_order_id = isset($access_record_info['order_id']) ? trim($access_record_info['order_id']) : '';
                }
            }
            //写入车辆出场表
            if ($record_id<1) {
                /*
                $access_record_where_near = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=',1],
                    ['del_time', '<', 1],
                    ['accessTime', '<', $now_time],
                ];// 通行时间小于出场时间的最近的一条入场时间的记录
                $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near,'*');
                if ($park_info_car111 && !$park_info_car111->isEmpty()){
                    $park_info_car111=$park_info_car111->toArray();
                }else{
                    $park_info_car111=array();
                    $park_info_car111['accessTime']=0;
                    $park_info_car111['order_id']='';
                }
                */

                $park_time_tmp=0;
                if($in_park_info && isset($in_park_info['in_time'])){
                    $park_time_tmp=$now_time - $in_park_info['in_time'];
                }
                
                if ($parkTime) {
                    fdump_api(['car_number'=>$car_number,'in_park_info' => $in_park_info],'D3Park/plateresult'.$parkTime,true);
                }
                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $car_number;
                $car_access_record['accessType'] = 2;
                $car_access_record['accessTime'] = $now_time;
                $car_access_record['park_time'] = $park_time_tmp;
                $car_access_record['accessMode'] = 5;
                $car_access_record['park_sys_type'] = $park_sys_type;
                $car_access_record['is_out'] = 1;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
               // $car_access_order_id = $park_info_car111['order_id'];
                $car_access_record['order_id'] = $car_access_order_id;
                $car_access_record['total'] = 0;
                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                $car_access_record['pay_type'] = $this->pay_type[4];
                $car_access_record['update_time'] = $now_time;
                $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                if (isset($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $car_access_record['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                if ($parkTime) {
                    fdump_api(['记录出场记录'=>$car_number, 'car_access_record' => $car_access_record,'record_id' => $record_id],'D3Park/plateresult'.$parkTime,true);
                }
                $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                /*
                if($park_info_car111 && isset($park_info_car111['record_id'])){
                    $access_record_where_near = [
                        ['record_id', '=', $park_info_car111['record_id']],
                    ];
                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                }
                */
            }
            //TODO:屏显和语音提示车辆通行
            $showscreen_data['voice_content']= 4;
            $showscreen_data['content']= '';
            $showscreen_data['car_type']= 'month_type';
            $showscreen_data['end_time']= $car_info['end_time'];
            $showscreen_data['end_time_txt']= date('Y-m-d',$car_info['end_time']);
            if ($parkTime) {
                fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
            }
            if(!empty($outRightPosition)){
                //释放车位
                $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
            }
            $this->addParkShowScreenLog($showscreen_data);
            //车辆通行
            if ($isImageToJob && ($record_id || $insert_id)) {
                if (isset($car_access_order_id) && $car_access_order_id) {
                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                }
                $imageToJob['out_record_id'] = $record_id;
                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
            }
            return true;
        } else {
            if (empty($in_park_info)&&!empty($out_park_info)&&$out_park_info['out_time']>$out_time){
                // 无入场记录-重复离场
                //车辆通行
                $showscreen_data['car_type']= 'temporary_type';
                $showscreen_data['duration_txt']= 0;
                $showscreen_data['duration']= 0;
                $showscreen_data['price']= 0;
                if ($parkTime) {
                    fdump_api(['车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                }
                if(!empty($outRightPosition)){
                    //释放车位
                    $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                    $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>0);
                    $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                }
                $this->addParkShowScreenLog($showscreen_data);
                try {
                    // todo 目前只有临时车支持这个 访客 二维码开门
                    (new HouseDeviceQrcodeService())->outParkVisitorToDevice($visitor_info);
                } catch (\Exception $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errOutParkVisitorToDeviceLog', true);
                } catch (\Throwable $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errOutParkVisitorToDeviceLog', true);
                } catch (\RuntimeException $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errOutParkVisitorToDeviceLog', true);
                } catch (\InvalidArgumentException $e) {
                    fdump_api(['执行错误' => $e->getMessage(), 'car_number' => $car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data], 'qrCodeDevice/errOutParkVisitorToDeviceLog', true);
                }
                return true;
            }
            elseif(empty($in_park_info)){
                //   fdump_api([$car_number,$village_info,$passage_info],'out_park_0906',1);
                //无入场纪录，根据车牌推送信息计算停车费用
                $in_park_plateresult_info = $db_park_plateresult_log->get_one(['car_number'=>$car_number,'park_type'=>1]);
                if ($parkTime) {
                    $pri_in_park_plateresult_info = $in_park_plateresult_info && !is_array($in_park_plateresult_info) ? $in_park_plateresult_info->toArray() : $in_park_plateresult_info;
                    fdump_api(['无入场纪录，根据车牌推送信息计算停车费用'=>$car_number,'in_park_plateresult_info' => $pri_in_park_plateresult_info],'D3Park/plateresult'.$parkTime,true);
                }
                if (empty($in_park_plateresult_info)){
                    // 无识别记录 以当前时间为准加一条入场记录
                    $in_passage_conut = $db_park_passage->getCount(['village_id'=>$passage_info['village_id'], 'status' => 1,'passage_direction'=>1]);
                    $in_car_access_record=[];
                    $in_park_data = [];
                    $in_park_data['out_time'] = $now_time;
                    $in_park_data['is_out'] = 1;
                    $in_park_data['is_paid'] = 1;
                    $in_park_data['car_number'] = $car_number;
                    $in_park_data['in_time'] = $now_time;
                    $in_park_data['order_id'] = uniqid();
                    if ($in_passage_conut==1){
                        $in_passage_info = $db_park_passage->getFind(['village_id'=>$passage_info['village_id'], 'status' => 1,'passage_direction'=>1]);
                        $in_park_data['in_channel_id'] = $in_passage_info['id'];
                        $in_car_access_record['channel_id']=$in_passage_info['id'];
                        $in_car_access_record['channel_number']=$in_passage_info['channel_number'];
                        $in_car_access_record['channel_name']=$in_passage_info['passage_name'];
                    }
                    $in_park_data['park_id'] = $passage_info['village_id'];
                    $in_park_data['park_sys_type'] = $park_sys_type;
                    $in_park_data['park_name'] = $village_info['village_name'];
                    $in_park_data['out_image_big']=$imageBigUrlPath;
                    $in_park_data['out_image_small']=$imageSmallUrlPath;
                    $insert_id = $db_in_park->insertOne($in_park_data);
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $insert_id;
                        $imageToJob['in_time'] = isset($in_park_data['in_time']) ? intval($in_park_data['in_time']) : 0;
                        $imageToJob['order_id'] = isset($in_park_data['order_id']) ? trim($in_park_data['order_id']) : '';
                    }
                    if ($parkTime) {
                        fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_data' => $in_park_data], 'D3Park/plateresult' . $parkTime, true);
                    }
                    $in_car_access_record['accessImage']=$imageSmallUrlPath;
                    $in_car_access_record['accessBigImage']=$imageBigUrlPath;
                    $in_car_access_record['business_type'] = 0;
                    $in_car_access_record['business_id'] = $passage_info['village_id'];
                    $in_car_access_record['car_number'] = $car_number;
                    $in_car_access_record['accessType'] = 1;
                    $in_car_access_record['accessTime'] = $now_time;
                    $in_car_access_record['accessMode'] = 9;
                    $in_car_access_record['park_sys_type'] = $park_sys_type;
                    $in_car_access_record['exception_type']=1;
                    $in_car_access_record['is_out'] = 1;
                    $in_car_access_record['park_id'] = $passage_info['village_id'];
                    $in_car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                    $car_access_order_id = date('YmdHis') . rand(100, 999);
                    $in_car_access_record['order_id'] = $car_access_order_id;
                    $in_car_access_record['update_time'] = $now_time;
                    if (isset($insert_id) && !empty($insert_id)) {
                        $in_car_access_record['from_id'] = $insert_id;
                    }
                    if (isset($car_access_record['user_name'])) {
                        $in_car_access_record['user_name'] = $car_access_record['user_name'];
                    }
                    if (isset($car_access_record['user_phone'])) {
                        $in_car_access_record['user_phone'] = $car_access_record['user_phone'];
                    }
                    $in_record_id = $db_house_village_car_access_record->addOne($in_car_access_record);
                    if ($isImageToJob) {
                        $imageToJob['in_record_id'] = $in_record_id;
                    }
                    
                    /*
                    $out_data['total'] = 0;
                    $out_data['pay_type'] = 'scancode';
                    $out_data['in_time'] = $in_park_data['in_time'];
                    $out_data['order_id'] = isset($in_park_data['order_id']) && $in_park_data['order_id'] ? $in_park_data['order_id'] : '';

                    $out_park_info_car = $db_out_park->getOne($park_out_where);
                    //写入车辆出场表
                    if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                        $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                        $insert_id = $db_out_park->insertOne($out_data);
                    } else {
                        $insert_id = $out_park_info_car['id'];
                    }
                    if ($isImageToJob) {
                        $imageToJob['out_park_id'] = $insert_id;
                    }
                    */
                    //写入车辆出场表
                    $record_id = 0;
                    if ($in_record_id>0) {
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $car_number;
                        $car_access_record['accessType'] = 2;
                        $car_access_record['accessTime'] = $now_time;
                        $car_access_record['accessMode'] = 4;
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['total'] = 0;
                        // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                        // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                        //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                        $car_access_record['pay_type'] = $this->pay_type[6];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                        if ($parkTime) {
                            fdump_api(['车辆通行记录'=>$car_number, 'car_access_record' => $car_access_record,'record_id' => $record_id],'D3Park/plateresult'.$parkTime,true);
                        }
                    }
                    //TODO:屏显和语音提示车辆通行
                    //车辆通行
                    $showscreen_data['car_type']= 'temporary_type';
                    $showscreen_data['duration_txt']= '';
                    $showscreen_data['duration']= 0;
                    $showscreen_data['price']= 0;
                    if ($parkTime) {
                        fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                    }
                    if(!empty($outRightPosition)){
                        //释放车位
                        $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                        $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                        $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['out_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    return true;
                }
                else{
                    // 取车辆识别入场通道信息
                    $in_passage_info = $db_park_passage->getFind(['village_id'=>$passage_info['village_id'], 'device_number' => $in_park_plateresult_info['channel_id']]);
                    if(empty($in_passage_info)){
                        $in_passage_conut = $db_park_passage->getCount(['village_id'=>$passage_info['village_id'], 'status' => 1,'passage_direction'=>1]);
                        $in_car_access_record=[];
                        $in_park_data = [];
                        $in_park_data['out_time'] = $now_time;
                        $in_park_data['is_out'] = 1;
                        $in_park_data['is_paid'] = 1;
                        $in_park_data['car_number'] = $car_number;
                        $in_park_data['in_time'] = $now_time;
                        $in_park_data['order_id'] = uniqid();
                        if ($in_passage_conut==1){
                            $in_passage_info = $db_park_passage->getFind(['village_id'=>$passage_info['village_id'], 'status' => 1,'passage_direction'=>1]);
                            $in_park_data['in_channel_id'] = $in_passage_info['id'];
                            $in_car_access_record['channel_id']=$in_passage_info['id'];
                            $in_car_access_record['channel_number']=$in_passage_info['channel_number'];
                            $in_car_access_record['channel_name']=$in_passage_info['passage_name'];
                        }
                        $in_park_data['park_id'] = $passage_info['village_id'];
                        $in_park_data['park_sys_type'] = $park_sys_type;
                        $in_park_data['park_name'] = $village_info['village_name'];
                        $in_park_data['out_image_big']=$imageBigUrlPath;
                        $in_park_data['out_image_small']=$imageSmallUrlPath;
                        $insert_id = $db_in_park->insertOne($in_park_data);
                        $insert_id && $in_park_data['id'] = $insert_id;
                        if ($isImageToJob) {
                            $imageToJob['in_park_id'] = $insert_id;
                            $imageToJob['in_time'] = isset($in_park_data['in_time']) ? intval($in_park_data['in_time']) : 0;
                            $imageToJob['order_id'] = isset($in_park_data['order_id']) ? trim($in_park_data['order_id']) : '';
                        }
                        if ($parkTime) {
                            fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_data' => $in_park_data], 'D3Park/plateresult' . $parkTime, true);
                        }

                        $in_car_access_record['accessImage']=$imageSmallUrlPath;
                        $in_car_access_record['accessBigImage']=$imageBigUrlPath;
                        $in_car_access_record['business_type'] = 0;
                        $in_car_access_record['business_id'] = $passage_info['village_id'];
                        $in_car_access_record['car_number'] = $car_number;
                        $in_car_access_record['accessType'] = 1;
                        $in_car_access_record['accessTime'] = $now_time;
                        $in_car_access_record['accessMode'] = 9;
                        $in_car_access_record['park_sys_type'] = 'D3';
                        $in_car_access_record['exception_type']=1;
                        $in_car_access_record['is_out'] = 1;
                        $in_car_access_record['park_id'] = $passage_info['village_id'];
                        $in_car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                        $car_access_order_id = date('YmdHis') . rand(100, 999);
                        $in_car_access_record['order_id'] = $car_access_order_id;
                        $in_car_access_record['update_time'] = $now_time;
                        if (isset($insert_id) && !empty($insert_id)) {
                            $in_car_access_record['from_id'] = $insert_id;
                        }
                        if (isset($car_access_record['user_name'])) {
                            $in_car_access_record['user_name'] = $car_access_record['user_name'];
                        }
                        if (isset($car_access_record['user_phone'])) {
                            $in_car_access_record['user_phone'] = $car_access_record['user_phone'];
                        }
                        $in_record_id = $db_house_village_car_access_record->addOne($in_car_access_record);
                        if ($isImageToJob) {
                            $imageToJob['in_record_id'] = $in_record_id;
                        }
  
                        /*
                        $out_data['total'] = 0;
                        $out_data['pay_type'] = 'scancode';
                        $out_data['in_time'] = $in_park_data['in_time'];
                        $out_data['order_id'] = isset($in_park_data['order_id']) && $in_park_data['order_id'] ? $in_park_data['order_id'] : '';

                        $out_park_info_car = $db_out_park->getOne($park_out_where);
                        //写入车辆出场表
                        if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                            $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                            $insert_id = $db_out_park->insertOne($out_data);
                        } else {
                            $insert_id = $out_park_info_car['id'];
                        }
                        if ($isImageToJob) {
                            $imageToJob['out_park_id'] = $insert_id;
                        }
                        */
                       
                        $record_id = 0;
                        //写入车辆出场表
                        if ($in_record_id>0) {
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $car_number;
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            $car_access_record['accessMode'] = 4;
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_record['order_id'] = $car_access_order_id;
                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[6];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                            if ($parkTime) {
                                fdump_api(['车辆通行记录'=>$car_number, 'car_access_record' => $car_access_record,'record_id' => $record_id],'D3Park/plateresult'.$parkTime,true);
                            }
                        }
                        //TODO:屏显和语音提示车辆通行
                        //车辆通行
                        $showscreen_data['car_type']= 'temporary_type';
                        $showscreen_data['duration_txt']= '';
                        $showscreen_data['duration']= 0;
                        $showscreen_data['price']= 0;
                        if ($parkTime) {
                            fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                        }
                        if(!empty($outRightPosition)){
                            //释放车位
                            $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                            $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                            $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        if ($isImageToJob && ($record_id || $insert_id)) {
                            if (isset($car_access_order_id) && $car_access_order_id) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                            }
                            $imageToJob['out_record_id'] = $record_id;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return true;
                    }
                    else{
                        $whereInOut = ['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'del_time' => 0];
                        $out_park_info = $db_in_park->getOne1($whereInOut);
                        $limit_out_time = isset($out_park_info['out_time']) && intval($out_park_info['out_time']) > 1 ? intval($out_park_info['out_time']) : 0;
                        $limit_in_time = isset($out_park_info['in_time']) && intval($out_park_info['in_time']) > 1 ? intval($out_park_info['in_time']) : 0;
                        $plateresult_time = isset($in_park_plateresult_info['add_time']) && intval($in_park_plateresult_info['add_time']) > 1 ? intval($in_park_plateresult_info['add_time']) : 0;
                        $access_in_time = 0;
                        if ($limit_out_time > 100 && $limit_out_time > $plateresult_time) {
                            $access_in_time = $limit_out_time;
                        } elseif ($limit_in_time > 100 && $limit_in_time > $plateresult_time) {
                            $access_in_time = $limit_in_time;
                        } elseif ($plateresult_time > 100) {
                            $access_in_time = $plateresult_time;
                        }
                        if ($access_in_time > 0){
                            $in_car_access_record=[];
                            $in_park_data = [];
                            $in_park_data['is_paid'] = 0;
                            $in_park_data['car_number'] = $car_number;
                            $in_park_data['in_time'] = $access_in_time;
                            $in_park_data['order_id'] = uniqid();
                            $in_passage_info = $db_park_passage->getFind(['village_id'=>$passage_info['village_id'], 'device_number' => $in_park_plateresult_info['channel_id']]);
                            $in_park_data['in_channel_id'] = $in_passage_info['id'];
                            $in_car_access_record['channel_id']=$in_passage_info['id'];
                            $in_car_access_record['channel_number']=$in_passage_info['channel_number'];
                            $in_car_access_record['channel_name']=$in_passage_info['passage_name'];
                            $in_park_data['out_image_big']=$imageBigUrlPath;
                            $in_park_data['out_image_small']=$imageSmallUrlPath;
                            $in_park_data['park_id'] = $passage_info['village_id'];
                            $in_park_data['park_sys_type'] = $park_sys_type;
                            $in_park_data['park_name'] = $village_info['village_name'];
                            $insert_id = $db_in_park->insertOne($in_park_data);
                            $insert_id && $in_park_data['id'] = $insert_id;
                            if ($isImageToJob) {
                                $imageToJob['in_park_id'] = $insert_id;
                                $imageToJob['in_time'] = isset($in_park_data['in_time']) ? intval($in_park_data['in_time']) : 0;
                                $imageToJob['order_id'] = isset($in_park_data['order_id']) ? trim($in_park_data['order_id']) : '';
                            }
                            if ($parkTime) {
                                fdump_api(['无入场纪录，根据车牌推送信息计算停车费用' => $car_number, 'in_park_data' => $in_park_data], 'D3Park/plateresult' . $parkTime, true);
                            }
                            $in_car_access_record['accessImage']=$imageSmallUrlPath;
                            $in_car_access_record['accessBigImage']=$imageBigUrlPath;
                            $in_car_access_record['business_type'] = 0;
                            $in_car_access_record['business_id'] = $passage_info['village_id'];
                            $in_car_access_record['car_number'] = $car_number;
                            $in_car_access_record['accessType'] = 1;
                            $in_car_access_record['accessTime'] = $access_in_time;
                            $in_car_access_record['accessMode'] = 9;
                            $in_car_access_record['park_sys_type'] = $park_sys_type;
                            $in_car_access_record['is_out'] = 0;
                            $in_car_access_record['park_id'] = $passage_info['village_id'];
                            $in_car_access_record['park_name'] = $village_info['village_name'] ? $village_info['village_name'] : '';
                            $car_access_order_id = date('YmdHis') . rand(100, 999);
                            $in_car_access_record['order_id'] = $car_access_order_id;
                            $in_car_access_record['update_time'] = $now_time;
                            $in_car_access_record['exception_type']=1;
                            if (isset($insert_id) && !empty($insert_id)) {
                                $in_car_access_record['from_id'] = $insert_id;
                            }
                            if (isset($car_access_record['user_name'])) {
                                $in_car_access_record['user_name'] = $car_access_record['user_name'];
                            }
                            if (isset($car_access_record['user_phone'])) {
                                $in_car_access_record['user_phone'] = $car_access_record['user_phone'];
                            }
                            if ($parkTime) {
                                fdump_api(['车辆通行记录前'=>$car_number, 'in_car_access_record' => $in_car_access_record],'D3Park/plateresult'.$parkTime,true);
                            }
                            $in_record_id = $db_house_village_car_access_record->addOne($in_car_access_record);
                            if ($parkTime) {
                                fdump_api(['车辆通行记录'=>$car_number, 'in_record_id' => $in_record_id],'D3Park/plateresult'.$parkTime,true);
                            }
                            if ($isImageToJob) {
                                $imageToJob['in_record_id'] = $in_record_id;
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                            }
                            
                            $in_park_info = $db_in_park->getOne(array('id'=>$insert_id),'*');
                            if($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])){
                                $saveArr=array('access_record_id'=>$in_record_id,'access_order_id'=>$car_access_order_id);
                                $db_in_park->saveOne(array('id'=>$insert_id),$saveArr);
                            }
                            if ($parkTime) {
                                fdump_api(['添加入场记录'=>$car_number,'in_record_id' => $in_record_id],'D3Park/plateresult'.$parkTime,true);
                            }
                            $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$in_record_id,'access_order_id'=>$car_access_order_id,'access_type'=>1,'access_time'=>time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                            /*
                            $access_record_where_near = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['is_out', '=', 0],
                                ['del_time', '<', 1],
                                ['record_id', '<', $in_record_id],
                            ];// 通行时间小于出场时间的最近的一条入场时间的记录
                            $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                            if ($in_record_id && $park_info_car111 && !$park_info_car111->isEmpty()){
                                $access_record_where_near = [
                                    ['record_id', '=', $park_info_car111['record_id']],
                                ];
                                $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                            }
                            */
                        }
                    }
                }
            }
            $pay_data = [
                'car_number' => $car_number,
                'village_id' => $passage_info['village_id'],
                'device_number' => $passage_info['device_number'],
                'channel_number' => $passage_info['channel_number'],
                'garage_id'=>$garage_id,
                'car_end_time'=>0,
                'temporary_end_time'=>0,
            ];
            if($car_info && isset($car_info['end_time'])){
                $pay_data['car_end_time']=$car_info['end_time'];
            }
            if($car_info && isset($car_info['temporary_end_time'])){
                $pay_data['temporary_end_time']=$car_info['temporary_end_time'];
            }
            if (isset($in_park_data) && !empty($in_park_data)) {
                $pay_data['in_park_data'] = $in_park_data;
            }
            $pay_money = $this->get_temp_pay($pay_data);
            $whereInRecord = ['car_number' => $car_number, 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
            if($in_park_pay_info && !$in_park_pay_info->isEmpty()){
                $in_park_pay_info=$in_park_pay_info->toArray();
            }
            if ($parkTime) {
                fdump_api(['车辆通行'=>$car_number,'in_park_pay_info' => $in_park_pay_info],'D3Park/plateresult'.$parkTime,true);
            }
            if (!empty($in_park_pay_info)) {
                $out_image_save=array();
                if(isset($park_data['out_image_big'])){
                    $out_image_save['out_image_big']=$park_data['out_image_big'];
                }
                if(isset($park_data['out_image_small'])){
                    $out_image_save['out_image_small']=$park_data['out_image_small'];
                }
                if($out_image_save){
                    $db_in_park->saveOne(['id'=>$in_park_pay_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $out_image_save);
                }
                $car_access_record['park_time'] = $pay_money['park_time'];
                $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                fdump_api([$car_number,$car_info,$pay_money],'D3Park/plateresult'.$parkTime,1);
  
                if ($pay_money['pay_money']==0){
                    $db_in_park->saveOne(['id'=>$in_park_pay_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $park_data);
                    $insert_id=$in_park_pay_info['id'];
                    /*
                    $out_data['total'] = $pay_money['pay_money'];
                    $out_data['pay_type'] = 'free';

                    $out_park_info_car = $db_out_park->getOne($park_out_where);
                    //写入车辆出场表
                    if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                        $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                        $insert_id = $db_out_park->insertOne($out_data);
                    } else {
                        $insert_id = $out_park_info_car['id'];
                    }
                    */
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                        $imageToJob['out_park_id'] = 0;
                        $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                        $imageToJob['order_id'] = isset($in_park_pay_info['order_id']) ? trim($in_park_pay_info['order_id']) : '';
                    }
                    if ($parkTime) {
                        fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'D3Park/plateresult'.$parkTime,true);
                    }
                    $car_access_order_id = isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id'] ? $in_park_pay_info['access_order_id'] : '';
                    //写入车辆出场表
                        /*
                        $access_record_where_near = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',1],
                            ['is_out', '=', 0],
                            ['del_time', '<', 1],
                            ['accessTime', '<', $now_time],
                        ];// 通行时间小于出场时间的最近的一条入场时间的记录
                        $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                        if ($park_info_car111 && !$park_info_car111->isEmpty()){
                            $park_info_car111=$park_info_car111->toArray();
                        }else{
                            $park_info_car111=array();
                            $park_info_car111['accessTime']=0;
                            $park_info_car111['order_id']='';
                        }
                        */
                        $park_time_tmp=0;
                        if($in_park_pay_info && isset($in_park_pay_info['in_time'])){
                            $park_time_tmp=$now_time - $in_park_pay_info['in_time'];
                        }
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $car_number;
                        $car_access_record['accessType'] = 2;
                        $car_access_record['accessTime'] = $now_time;
                        $car_access_record['park_time'] = $park_time_tmp;
                        $car_access_record['accessMode'] = 3;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['total'] = $pay_money['pay_money'];
                        // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                        // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                        //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                        $car_access_record['pay_type'] = $this->pay_type[5];
                        if(isset($pay_money['is_advance_pay']) && $pay_money['is_advance_pay']>0 && isset($pay_money['parkTemp_money']) && $pay_money['parkTemp_money']>0){
                            $car_access_record['total']=$pay_money['parkTemp_money'];
                            $car_access_record['pay_type'] = $this->pay_type[6];
                        }
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $car_access_record['pay_order_id'] = isset($in_park_pay_info['pay_order_id']) && $in_park_pay_info['pay_order_id'] ? $in_park_pay_info['pay_order_id'] : '';
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                        if ($parkTime) {
                            fdump_api(['车辆通行记录'=>$car_number, 'car_access_record' => $car_access_record,'record_id' => $record_id],'D3Park/plateresult'.$parkTime,true);
                        }
                        $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                        /*
                        if($park_info_car111 && isset($park_info_car111['record_id'])){
                            $access_record_where_near = [
                                ['record_id', '=', $park_info_car111['record_id']],
                            ];
                            $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                        }
                        */

                    //TODO:屏显和语音提示车辆通行
                    $showscreen_data['car_type']= 'temporary_type';
                    $showscreen_data['duration_txt']= $pay_money['time'];
                    $showscreen_data['duration']= $pay_money['park_time'];
                    $showscreen_data['price']= 0;
                    if ($parkTime) {
                        fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                    }
                    if(!empty($outRightPosition)){
                        //释放车位
                        $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                        $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                        $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    //车辆通行
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['out_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    return true;
                }
                elseif ($pay_money['pay_money']>0&&!empty($car_info)&&$car_info['stored_balance']>$pay_money['pay_money']){
                    $db_in_park->saveOne(['id'=>$in_park_pay_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $park_data);
                    $insert_id=$in_park_pay_info['id'];
                    /*
                    $out_data['total'] = $pay_money['pay_money'];
                    $out_data['pay_type'] = 'wallet';

                    $out_park_info_car = $db_out_park->getOne($park_out_where);
                    //写入车辆出场表
                    if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                        $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                        $insert_id = $db_out_park->insertOne($out_data);
                    } else {
                        $insert_id = $out_park_info_car['id'];
                    }
                    */
                    if ($isImageToJob) {
                        $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                        $imageToJob['out_park_id'] = 0;
                        $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                        $imageToJob['order_id'] = isset($in_park_pay_info['order_id']) ? trim($in_park_pay_info['order_id']) : '';
                    }

                    if ($parkTime) {
                        fdump_api(['车辆通行'=>$car_number,'park_data' => $park_data],'D3Park/plateresult'.$parkTime,true);
                    }
                    //写入车辆出场表

                        /*
                        $access_record_where_near = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',1],
                            ['is_out', '=', 0],
                            ['del_time', '<', 1],
                            ['accessTime', '<=', $now_time],
                        ];// 通行时间小于出场时间的最近的一条入场时间的记录
                        $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                        if ($park_info_car111 && !$park_info_car111->isEmpty()){
                            $park_info_car111=$park_info_car111->toArray();
                        }else{
                            $park_info_car111=array();
                            $park_info_car111['accessTime']=0;
                            $park_info_car111['order_id']='';
                        }
                        $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                        */
                        $car_access_order_id='';
                        if($in_park_pay_info && isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id']){
                            $car_access_order_id=$in_park_pay_info['access_order_id'];
                        }
                        $park_time_tmp=0;
                        if($in_park_pay_info && isset($in_park_pay_info['in_time'])){
                            $park_time_tmp=$now_time - $in_park_pay_info['in_time'];
                        }
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $car_number;
                        $car_access_record['accessType'] = 2;
                        $car_access_record['accessTime'] = $now_time;
                        $car_access_record['park_time'] = $park_time_tmp;
                        $car_access_record['car_type'] = 'storedCar';
                        if (!isset($car_access_record['user_name']) || !$car_access_record['user_name']) {
                            $car_access_record['user_name'] = $car_info['car_user_name'];
                        }
                        if (!isset($car_access_record['user_phone']) || !$car_access_record['user_phone']) {
                            $car_access_record['user_phone'] = $car_info['car_user_phone'];
                        }
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['coupon_id'] = $pay_money['coupon_id'];
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['total'] = $pay_money['pay_money'];
                        $car_access_record['is_paid'] = 1;
                        $car_access_record['pay_time'] = $now_time;
                        $car_access_record['stored_balance']=$car_info['stored_balance']-$pay_money['pay_money'];
                        // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                        // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                        //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                        $car_access_record['pay_type'] = $this->pay_type[1];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $car_access_record['pay_order_id'] = isset($in_park_pay_info['pay_order_id']) && $in_park_pay_info['pay_order_id'] ? $in_park_pay_info['pay_order_id'] : '';
                        $car_access_record['prepayTotal']  = isset($pay_money['parkTemp_money']) && $pay_money['parkTemp_money'] > 0 ? $pay_money['parkTemp_money'] : 0;
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                        if ($record_id>0){
                            $db_house_village_parking_car->editHouseVillageParkingCar(['province' => $province, 'car_number' => $car_no,'village_id'=>$passage_info['village_id']],['stored_balance'=>$car_access_record['stored_balance']]);
                        }

                        if ($parkTime) {
                            $stored_balance = isset($car_info['stored_balance']) ? $car_info['stored_balance'] : 0;
                            $stored_balance1 = isset($car_access_record['stored_balance']) ? $car_access_record['stored_balance'] : 0;
                            $pay_money11 = isset($pay_money['pay_money']) ? $pay_money['pay_money'] : 0;
                            fdump_api(['对应车辆储值余额变动'=>$car_number, 'car_access_record' => $car_access_record,'record_id' => $record_id,'stored_balance' => $stored_balance,'pay_money' => $pay_money11,'stored_balance1' => $stored_balance1], 'D3Park/plateresult'.$parkTime, 1);
                        }
                        $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                        /*
                        if($park_info_car111 && isset($park_info_car111['record_id'])){
                            $access_record_where_near = [
                                ['record_id', '=', $park_info_car111['record_id']],
                            ];
                            $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                        }
                        */
                    
                    //TODO:屏显和语音提示车辆通行
                    $showscreen_data['car_type']= 'temporary_type';
                    $showscreen_data['duration_txt']= $pay_money['time'];
                    $showscreen_data['duration']= $pay_money['park_time'];
                    $showscreen_data['price']= $pay_money['pay_money'];
                    $showscreen_data['voice_content']= 9;
                    $showscreen_data['content']= '';
                    if ($parkTime) {
                        fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                    }
                    if(!empty($outRightPosition)){
                        //释放车位
                        $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                        $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                        $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    //车辆通行
                    if ($isImageToJob && ($record_id || $insert_id)) {
                        if (isset($car_access_order_id) && $car_access_order_id) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                        }
                        $imageToJob['out_record_id'] = $record_id;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    return true;
                }
                elseif (!empty($in_park_pay_info['pay_order_id'])) {
                    $pay_info = $db_house_new_pay_order->get_one(['summary_id' => $in_park_pay_info['pay_order_id'], 'is_paid' => 1]);
                    if ($parkTime) {
                        fdump_api(['支付订单'=>$car_number,'pay_info' => $pay_info],'D3Park/plateresult'.$parkTime,true);
                    }
                    if (!empty($pay_info)&&$pay_info['pay_time']>$in_park_pay_info['in_time']) {
                        if ($pay_info['pay_money'] >= $pay_money['pay_money'] || $pay_money['pay_money'] == 0) {
                            $db_in_park->saveOne(['id'=>$in_park_pay_info['id'],'car_number' => $car_number, 'park_id' => $passage_info['village_id']], $park_data);
                            $insert_id=$in_park_pay_info['id'];
                            /*
                            $out_park_info_car = $db_out_park->getOne($park_out_where);
                            //写入车辆出场表
                            if (! $out_park_info_car || ! isset($out_park_info_car['id'])) {
                                $out_data['pay_order_id'] = isset($in_park_info['pay_order_id']) && $in_park_info['pay_order_id'] ? $in_park_info['pay_order_id'] : '';
                                $insert_id = $db_out_park->insertOne($out_data);
                            } else {
                                $insert_id = $out_park_info_car['id'];
                            }
                            */
                            if ($isImageToJob) {
                                $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                                $imageToJob['out_park_id'] = 0;
                                $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                                $imageToJob['order_id'] = isset($in_park_pay_info['order_id']) ? trim($in_park_pay_info['order_id']) : '';
                            }
                            //写入车辆出场表
                                /*
                                $access_record_where_near = [
                                    ['car_number', '=', $car_number],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['accessType', '=',1],
                                    ['is_out', '=', 0],
                                    ['del_time', '<', 1],
                                    ['accessTime', '<=', $now_time],
                                ];// 通行时间小于出场时间的最近的一条入场时间的记录
                                $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                                if ($park_info_car111 && !$park_info_car111->isEmpty()){
                                    $park_info_car111=$park_info_car111->toArray();
                                }else{
                                    $park_info_car111=array();
                                    $park_info_car111['accessTime']=0;
                                    $park_info_car111['order_id']='';
                                }
                                $car_access_order_id = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                                */
                                $car_access_order_id='';
                                if($in_park_pay_info && isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id']){
                                    $car_access_order_id=$in_park_pay_info['access_order_id'];
                                }
                                $park_time_tmp=0;
                                if($in_park_pay_info && isset($in_park_pay_info['in_time'])){
                                    $park_time_tmp=$now_time - $in_park_pay_info['in_time'];
                                }
                                $car_access_record['business_type'] = 0;
                                $car_access_record['business_id'] = $passage_info['village_id'];
                                $car_access_record['car_number'] = $car_number;
                                $car_access_record['accessType'] = 2;
                                $car_access_record['accessTime'] = $now_time;
                                $car_access_record['park_time'] = $park_time_tmp;
                                $car_access_record['accessMode'] = 4;
                                $car_access_record['park_sys_type'] = $park_sys_type;
                                $car_access_record['is_out'] = 1;
                                $car_access_record['park_id'] = $passage_info['village_id'];
                                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                                
                                $car_access_record['order_id'] = $car_access_order_id;
                                $car_access_record['total'] = $pay_money['pay_money'];
                                $car_access_record['pay_time'] = time();
                                $car_access_record['is_paid'] = 1;
                                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                $car_access_record['pay_type'] = $this->pay_type[6];
                                $car_access_record['update_time'] = $now_time;
                                $car_access_record['trade_no'] = $now_time . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                if (isset($insert_id)) {
                                    $car_access_record['from_id'] = $insert_id;
                                }
                                $car_access_record['pay_order_id'] = $in_park_pay_info['pay_order_id'];
                                $car_access_record['prepayTotal']  = isset($pay_money['parkTemp_money']) && $pay_money['parkTemp_money'] > 0 ? $pay_money['parkTemp_money'] : 0;
                                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                                if ($parkTime) {
                                    fdump_api(['车辆通行记录'=>$car_number, 'car_access_record' => $car_access_record,'record_id' => $record_id],'D3Park/plateresult'.$parkTime,true);
                                }
                                $tmpUpdateRecordArr=array('car_number'=> $car_number,'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>2,'access_time'=>time());
                                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                /*
                                if($park_info_car111 && isset($park_info_car111['record_id'])){
                                    $access_record_where_near = [
                                        ['record_id', '=', $park_info_car111['record_id']],
                                    ];
                                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);
                                }
                                */
  
                            //TODO:屏显和语音提示车辆通行
                            //车辆通行
                            $showscreen_data['car_type']= 'temporary_type';
                            $showscreen_data['duration_txt']= $pay_money['time'];
                            $showscreen_data['duration']= $pay_money['park_time'];
                            $showscreen_data['price']= 0;
                            if ($parkTime) {
                                fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                            }
                            if(!empty($outRightPosition)){
                                //释放车位
                                $whereArr=array('position_id'=>$outRightPosition['position_id'],'use_car_id'=>$car_info['car_id']);
                                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                                $this->updateParkingPositionUse($whereArr,$saveArr,$car_number);
                            }
                            $this->addParkShowScreenLog($showscreen_data);
                            if ($isImageToJob && ($record_id || $insert_id)) {
                                if (isset($car_access_order_id) && $car_access_order_id) {
                                    $imageToJob['car_access_order_id'] = $car_access_order_id;
                                }
                                $imageToJob['out_record_id'] = $record_id;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                            return true;
                        } else {
                            //TODO:屏显和语音提示错误信息
                            $showscreen_data['car_type']= 'temporary_type';
                            $showscreen_data['duration_txt']= $pay_money['time'];
                            $showscreen_data['duration']= $pay_money['park_time'];
                            $showscreen_data['price']= $pay_money['pay_money'];

                            $showscreen_data['voice_content']= 5;
                            $showscreen_data['content']= '';
                            if ($parkTime) {
                                fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                            }
                            $this->addParkShowScreenLog($showscreen_data);

                            //企业微信群提醒
                            if (isset($pay_money['pay_money'])) {
                                $payMoney = $pay_money['pay_money'] - $pay_info['pay_money'];
                            } else {
                                $payMoney = $pay_money['pay_money'];
                            }
                            $webhookurl = $this->webhookurl;
                            $data = [
                                "msgtype" => "markdown",
                                "markdown" => [
                                    "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆停车时长<font color='info'>**" . $pay_money['time'] . "**</font>需缴费<font color='info'>**" . $payMoney . "**</font>元
                             >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                             >车牌号码: <font color='info'>**" . $car_number . "**</font>
                             >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                             >通行状态: <font color='info'>**拒绝通行**</font> 
                             >通行方向: <font color='info'>**入口**</font> 
                             >行为原因: <font color='info'>**临时车需缴费通行**</font> 
                             >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                                ],
                            ];
                            http_request($webhookurl, 'POST', json_encode($data));
                            //需缴费通行
                            if ($parkTime) {
                                fdump_api(['屏显和语音提示车辆通行'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
                            }
                            $access_record_where_near = [
                                ['car_number', '=', $car_number],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                                ['del_time', '<', 1],
                                ['accessTime', '<=', $now_time],
                            ];// 通行时间小于出场时间的最近的一条入场时间的记录
                            $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                            $near_in_record_id=0;
                            $car_access_order_id=0;
                            if ($park_info_car111 && !$park_info_car111->isEmpty()){
                                $park_info_car111=$park_info_car111->toArray();
                                $car_access_order_id=$park_info_car111['order_id'];
                                $near_in_record_id=$park_info_car111['record_id'];
                            }
                            //特殊车辆通行
                            if ($isImageToJob && ($in_park_pay_info && $in_park_pay_info['id'])) {
                                $imageToJob['car_access_order_id'] = $car_access_order_id;
                                $imageToJob['out_record_id'] = 0;
                                $imageToJob['near_in_record_id'] = $near_in_record_id;
                                $imageToJob['out_park_id'] = 0;
                                $imageToJob['now_no_out'] = 1;
                                $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                                $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                                (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                            }
                            return false;
                        }
                    } else {
                        //TODO:屏显和语音提示错误信息
                        $showscreen_data['voice_content']= 5;
                        $showscreen_data['content']= '';
                        $showscreen_data['car_type']= 'temporary_type';
                        $showscreen_data['duration_txt']= $pay_money['time'];
                        $showscreen_data['duration']= $pay_money['park_time'];
                        $showscreen_data['price']= $pay_money['pay_money'];
                        if ($parkTime) {
                            fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                        }
                        $this->addParkShowScreenLog($showscreen_data);
                        //企业微信群提醒
                        $payMoney = $pay_money['pay_money'];
                        $webhookurl = $this->webhookurl;
                        $data = [
                            "msgtype" => "markdown",
                            "markdown" => [
                                "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆停车时长<font color='info'>**" . $pay_money['time'] . "**</font>需缴费<font color='info'>**" . $payMoney . "**</font>元
                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**入口**</font> 
                                 >行为原因: <font color='info'>**临时车需缴费通行**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                            ],
                        ];
                        http_request($webhookurl, 'POST', json_encode($data));
                        //需缴费通行
                        if ($parkTime) {
                            fdump_api(['屏显和语音提示车辆通行'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
                        }
                        $access_record_where_near = [
                            ['car_number', '=', $car_number],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',1],
                            ['is_out', '=', 0],
                            ['del_time', '<', 1],
                            ['accessTime', '<=', $now_time],
                        ];// 通行时间小于出场时间的最近的一条入场时间的记录
                        $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                        $near_in_record_id=0;
                        $car_access_order_id=0;
                        if ($park_info_car111 && !$park_info_car111->isEmpty()){
                            $park_info_car111=$park_info_car111->toArray();
                            $car_access_order_id=$park_info_car111['order_id'];
                            $near_in_record_id=$park_info_car111['record_id'];
                        }
                        //特殊车辆通行
                        if ($isImageToJob && ($in_park_pay_info && $in_park_pay_info['id'])) {
                            $imageToJob['car_access_order_id'] = $car_access_order_id;
                            $imageToJob['out_record_id'] = 0;
                            $imageToJob['near_in_record_id'] = $near_in_record_id;
                            $imageToJob['out_park_id'] = 0;
                            $imageToJob['now_no_out'] = 1;
                            $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                            $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                            (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                        }
                        return false;
                    }
                }
                else {
                    //TODO:屏显和语音提示错误信息
                    $showscreen_data['voice_content']= 5;
                    $showscreen_data['content']= '';
                    $showscreen_data['car_type']= 'temporary_type';
                    $showscreen_data['duration_txt']= $pay_money['time'];
                    $showscreen_data['duration']= $pay_money['park_time'];
                    $showscreen_data['price']= $pay_money['pay_money'];
                    if ($parkTime) {
                        fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                    }
                    $this->addParkShowScreenLog($showscreen_data);
                    //企业微信群提醒
                    $payMoney = $pay_money['pay_money'];
                    $webhookurl = $this->webhookurl;
                    $data = [
                        "msgtype" => "markdown",
                        "markdown" => [
                            "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆停车时长<font color='info'>**" . $pay_money['time'] . "**</font>需缴费<font color='info'>**" . $payMoney . "**</font>元
                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**入口**</font> 
                                 >行为原因: <font color='info'>**临时车需缴费通行**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                        ],
                    ];
                    http_request($webhookurl, 'POST', json_encode($data));
                    //需缴费通行
                    if ($parkTime) {
                        fdump_api(['屏显和语音提示车辆通行'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
                    }
                    $access_record_where_near = [
                        ['car_number', '=', $car_number],
                        ['park_id', '=', $passage_info['village_id']],
                        ['accessType', '=',1],
                        ['is_out', '=', 0],
                        ['del_time', '<', 1],
                        ['accessTime', '<=', $now_time],
                    ];// 通行时间小于出场时间的最近的一条入场时间的记录
                    $park_info_car111 = $db_house_village_car_access_record->getOne($access_record_where_near);
                    $near_in_record_id=0;
                    $car_access_order_id=0;
                    if ($park_info_car111 && !$park_info_car111->isEmpty()){
                        $park_info_car111=$park_info_car111->toArray();
                        $car_access_order_id=$park_info_car111['order_id'];
                        $near_in_record_id=$park_info_car111['record_id'];
                    }
                    //特殊车辆通行
                    if ($isImageToJob && ($in_park_pay_info && $in_park_pay_info['id'])) {
                        $imageToJob['car_access_order_id'] = $car_access_order_id;
                        $imageToJob['out_record_id'] = 0;
                        $imageToJob['near_in_record_id'] = $near_in_record_id;
                        $imageToJob['out_park_id'] = 0;
                        $imageToJob['now_no_out'] = 1;
                        $imageToJob['in_park_id'] = $in_park_pay_info['id'];
                        $imageToJob['in_time'] = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                        (new ParkCarToJobService())->parkCarImageToJob($imageToJob);
                    }
                    return false;
                }
            }else{
                //TODO:屏显和语音提示错误信息
                $showscreen_data['voice_content']= 8;
                $showscreen_data['content']= '未入场';
                $showscreen_data['car_type']= 'temporary_type';
                $showscreen_data['duration_txt']= '';
                $showscreen_data['duration']= 0;
                $showscreen_data['price']= 0;
                if ($parkTime) {
                    fdump_api(['屏显和语音提示车辆通行'=>$car_number,'showscreen_data' => $showscreen_data],'D3Park/plateresult'.$parkTime,true);
                }
                $this->addParkShowScreenLog($showscreen_data);
                //企业微信群提醒
                $payMoney = $pay_money['pay_money'];
                $webhookurl = $this->webhookurl;
                $data = [
                    "msgtype" => "markdown",
                    "markdown" => [
                        "content" => "车牌为<font color='info'>**" . $car_number . "**</font>的车辆停车时长<font color='info'>**" . $pay_money['time'] . "**</font>需缴费<font color='info'>**" . $payMoney . "**</font>元
                                 >小区名称: <font color='info'>**" . $village_info['village_name'] . "**</font>
                                 >车牌号码: <font color='info'>**" . $car_number . "**</font>
                                 >设备名称: <font color='info'>**" . $passage_info['passage_name'] . "**</font> 
                                 >通行状态: <font color='info'>**拒绝通行**</font> 
                                 >通行方向: <font color='info'>**入口**</font> 
                                 >行为原因: <font color='info'>**临时车需缴费通行**</font> 
                                 >通知时间: <font color='info' >" . date('Y-m-d H:i:s', time()) . "</font>"
                    ],
                ];
                http_request($webhookurl, 'POST', json_encode($data));
                //需缴费通行
                if ($parkTime) {
                    fdump_api(['需缴费通行'=>$car_number,'data' => $data],'D3Park/plateresult'.$parkTime,true);
                }
                return false;
            }
        }
        return false;
    }


    /**
     * 获取临时车缴费信息
     * @author:zhubaodi
     * @date_time: 2021/11/11 15:22
     */
    public function get_temp_payment_info($data)
    {
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($data['village_id'], 'province_id');
        if (!empty($village_info)) {
            $db_area = new Area();
            $province_id = $db_area->getOne(['area_id' => $village_info['province_id']]);
        }
        if (!empty($province_id)) {
            $list['province_text'] = $province_id['area_name'];
        } else {
            $list['province_text'] = '';
        }
        $list['list'] = $this->get_bind_car_list($data);
        $garage_id=0;
        if($list['list'] && isset($list['list']['0']) && isset($list['list']['0']['garage_id'])){
            $garage_id=$list['list']['0']['garage_id'];
        }
        $park_config=$this->getVillageParkConfig($data['village_id'],'park_sys_type,park_versions',$garage_id);
        $list['skiptype'] = self::getD5ParkConfig($park_config);
        $list['province'] = $this->province_car;
        
        if(strpos($_SERVER['SERVER_NAME'],'hz.huizhisq.com') !== false){
            $list['bingtext']='去绑定月租车辆';
        }
        else{
            $list['bingtext']='去绑定车辆';
        }
        return $list;
    }

    /**
     * 获取用户绑定的车辆列表
     * @author:zhubaodi
     * @date_time: 2021/11/11 13:14
     */
    public function get_bind_car_list($data)
    {
        $db_house_village_bind_car = new HouseVillageBindCar();
        $db_brand_cars = new BrandCars();
        
        $whereArr=array(['b.village_id','=', $data['village_id']]);
        $pigcms_id_arr=array($data['pigcms_id']);
        $is_pigcms_id=$data['pigcms_id']>0 ? 1:0;
        if (isset($data['new_pigcms_id']) && $data['new_pigcms_id']) {
            if (is_array($data['new_pigcms_id']) && isset($data['new_pigcms_id']['pigcms_id'])) {
                $pigcms_id_arr[] = $data['new_pigcms_id']['pigcms_id'];
                $is_pigcms_id=$data['new_pigcms_id']['pigcms_id']>0 ? 1:0;
            } else {
                $pigcms_id_arr[] = $data['new_pigcms_id'];
                $is_pigcms_id=$data['new_pigcms_id']>0 ? 1:0;
            }
        }
        $whereRaw='a.village_id ='.$data['village_id'];
        if($is_pigcms_id>0){
            $whereRaw.=' AND a.user_id in ('.implode(',',$pigcms_id_arr).')';
        }elseif(isset($data['uid']) && $data['uid']){
            $whereRaw.=' AND a.uid ='.$data['uid'];
        }
        //$whereArr[]=array('a.user_id','in',$pigcms_id_arr);
        if(isset($data['uid']) && $data['uid']){
            //$whereArr[]=array('a.uid','=',$data['uid']);
            //$whereArr[]=array('b.binding_type','=',0);
            $whereRaw .=' OR (b.uid='.$data['uid'].' AND b.binding_type="0")';
        }
        $car_list = $db_house_village_bind_car->getLists($whereArr, 'a.id,a.uid,a.user_id,a.relationship,a.is_default,b.*',0,15,'b.car_id DESC',$whereRaw);
        $carList = [];
        $houseNewParkingService=new HouseNewParkingService();
        $db_house_village_ParkIng_position=new HouseVillageParkingPosition();
        $parking_a11_car_type_arr=$houseNewParkingService->parking_a11_car_type_arr;
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        if (!empty($car_list)) {
            $car_list = $car_list->toArray();
            if (!empty($car_list)) {
                $i=0;
                $parkGarageConfigArr=array();
                foreach ($car_list as $k => $v) {
                    if ($i>0&&$carList[$i-1]['car_id']==$v['car_id']){
                        continue;
                    }
                    $garage_id=0;
                    if(isset($v['garage_id']) && $v['garage_id']>0){
                        $garage_id=$v['garage_id'];
                    }else if($v['car_position_id']>0){
                        $garage_arr=  $db_house_village_ParkIng_position->getFind(['position_id'=>$v['car_position_id']],'position_id,garage_id,position_num');
                        if($garage_arr && isset($garage_arr['garage_id']) && $garage_arr['garage_id']>0){
                            $garage_id= $garage_arr['garage_id'];
                        }
                    }
                    if(isset($parkGarageConfigArr[$garage_id])){
                        $village_park_config=$parkGarageConfigArr[$garage_id];
                    }else{
                        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
                        $parkGarageConfigArr[$garage_id]=$house_village_park_config;
                        $village_park_config=$house_village_park_config;
                    }
                    $carList[$i]['garage_id']=$garage_id;
                    $carList[$i]['is_default'] = $v['is_default'];
                    $carList[$i]['id'] = $v['id'];
                    $carList[$i]['car_id'] = $v['car_id'];
                    $carList[$i]['car_color'] = $v['car_color'];
                    $carList[$i]['car_color_str']='';
                    foreach ($this->car_color as $cvv){
                        if($cvv['id']==$v['car_color']){
                            $carList[$i]['car_color_str']=$cvv['car_color'];
                            break;
                        }
                    }
                    $carList[$i]['uid'] = $v['uid'];
                    $carList[$i]['village_id'] = $v['village_id'];
                    $carList[$i]['car_number'] = $v['province'] . substr($v['car_number'], 0, 1) . '-' . substr($v['car_number'], 1);
                    $carList[$i]['car_brands'] = $v['car_brands'];
                    $carList[$i]['car_brands_logo'] = cfg('site_url') . $this->car_logo;
                    $carList[$i]['parking_car_type']=$v['parking_car_type'];
                    $carList[$i]['parking_car_type_str']='';
                    $carList[$i]['parking_car_type_group']='';
                    if (!empty($v['parking_car_type'])&&$village_park_config['park_sys_type']=='A11'&&$v['parking_car_type']<=20){
                        $parking_car_type_str=isset($parking_a11_car_type_arr[$v['parking_car_type']]) ? $parking_a11_car_type_arr[$v['parking_car_type']]:'';
                        $carList[$i]['parking_car_type_str']=$parking_car_type_str;
                        if($parking_car_type_str && strpos($parking_car_type_str,'月租车')!==false){
                            $carList[$i]['parking_car_type_group']='is_month_car';
                        }elseif ($parking_car_type_str && strpos($parking_car_type_str,'储值车')!==false){
                            $carList[$i]['parking_car_type_group']='is_stored_car';
                        }
                    }
                    if (!empty($v['car_brands'])) {
                        $brand_cars = explode('-', $v['car_brands']);
                        $logo = $db_brand_cars->getFind(['brand_name' => $brand_cars[0]]);
                        $carList[$i]['car_brands_logo'] = cfg('site_url') . $logo['brand_logo'];
                    }
                    $carList[$i]['relationship'] = isset($this->relationship[$v['relationship']]) ? $this->relationship[$v['relationship']]:'其他';
                    $v['examine_status']=$v['examine_status']>0 ? $v['examine_status']:0;
                    $carList[$i]['status'] = $v['examine_status'];
                    $carList[$i]['status_text'] = $this->examine_status[$v['examine_status']]['text'];
                    $carList[$i]['status_color'] = $this->examine_status[$v['examine_status']]['color'];
                    $i++;
                }
            }
        }
        if($carList){
            $carList=array_values($carList);
        }
        return $carList;
    }

    /**
     * 获取业主绑定的车辆信息
     * @author:lihongshun
     * @date_time: 2021/11/11 13:14
     */
    public function getUserBindCarList($whereArr=array(),$field='*')
    {
        $db_house_village_bind_car = new HouseVillageBindCar();
        $car_list = $db_house_village_bind_car->getLists($whereArr, $field);
        $carList = [];
        if (!empty($car_list) && !$car_list->isEmpty()) {
            $houseVillageParkingPosition= new HouseVillageParkingPosition();
            $houseVillageParkingGarage= new HouseVillageParkingGarage();
            $carList = $car_list->toArray();
            if (!empty($carList)) {
                foreach ($carList as $k => $v) {
                    $carList[$k]['car_number']=$v['province'].$v['car_number'];
                    $carList[$k]['position_num']='';
                    $carList[$k]['garage_num']='';
                    if($v['position_id']>0){
                        $whereArr=array('position_id'=>$v['position_id'],'village_id'=>$v['village_id']);
                        $parkingPosition=$houseVillageParkingPosition->getFind($whereArr,'garage_id,position_num,position_type,position_pattern');
                        if($parkingPosition && !$parkingPosition->isEmpty()){
                            $carList[$k]['position_num']=$parkingPosition['position_num'];
                            if($parkingPosition['garage_id']>0 && !($parkingPosition['position_type']==3 && $parkingPosition['position_pattern']==1)){
                                $parkingGarage=$houseVillageParkingGarage->getOne(['garage_id'=>$parkingPosition['garage_id'],'village_id'=>$v['village_id']],'garage_num');
                                if($parkingGarage && !$parkingGarage->isEmpty()){
                                    $carList[$k]['garage_num']=$parkingGarage['garage_num'];
                                }
                            }else if($parkingPosition['garage_id']==0 || ($parkingPosition['position_type']==3 && $parkingPosition['position_pattern']==1)){
                                $carList[$k]['garage_num']='虚拟车库';
                            }
                        }
                    }
                }
            }
        }
        return $carList;
    }
    /**
     * 获取业主绑定的车位信息
     * @author:lihongshun
     * @date_time: 2021/11/11 13:14
     */
    public function  getUserBindPositionList($whereArr=array(),$field='*'){
        $houseVillageBindPosition= new HouseVillageBindPosition();
        $bindPosition = $houseVillageBindPosition->getUserBindPositionList($whereArr, $field);
        if($bindPosition && !$bindPosition->isEmpty()){
            $position_list = $bindPosition->toArray();
            $houseVillageParkingGarage= new HouseVillageParkingGarage();
            foreach ($position_list as $kk => $vv ){
                if($vv['garage_id']>0 && !($vv['position_type'] == 3 && $vv['position_pattern']==1)){
                    $parkingGarage=$houseVillageParkingGarage->getOne(['garage_id'=>$vv['garage_id'],'village_id'=>$vv['village_id']],'garage_num,status');
                    if($parkingGarage && !$parkingGarage->isEmpty() && $parkingGarage['status']==1){
                        $position_list[$kk]['garage_num']=$parkingGarage['garage_num'];
                    }else if($parkingGarage && !$parkingGarage->isEmpty() && $parkingGarage['status']==0){
                        unset($position_list[$kk]);
                    }
                }else if($vv['garage_id']==0 || ($vv['position_type'] == 3 && $vv['position_pattern']==1)){
                    $position_list[$kk]['garage_num']='虚拟车库';
                }
            }
            $position_list=array_values($position_list);
            return $position_list;
        }
        return array();
    }

    public function handleBindCarAndPosition($bindCars=array(),$bindPosition=array()){
        $positionIDs=array();
        if($bindCars){
            foreach ($bindCars as $vv){
                $positionIDs[]=$vv['position_id'];
            }
        }else{
            $bindCars=array();
        }
        if($bindPosition){
            foreach ($bindPosition as $vv){
                if(!in_array($vv['position_id'],$positionIDs)){
                    $vv['car_id']=0;
                    $vv['car_number']='';
                    $bindCars[]=$vv;
                }
            }
        }
        return $bindCars;
    }
    /**
     * 获取车标列表
     * @author:zhubaodi
     * @date_time: 2021/11/11 13:14
     */
    public function get_car_logo_list()
    {
        $db_brand_cars = new BrandCars();
        $car_list = $db_brand_cars->getList([]);
        if (!empty($car_list)) {
            $car_list = $car_list->toArray();
        }
        return $car_list;
    }

    /**
     * 获取用户历史登记记录
     * @author:zhubaodi
     * @date_time: 2021/11/12 11:34
     */
    public function get_visitor_list($data)
    {
        $db_house_visitor = new HouseVillageVisitor();
        $res = $db_house_visitor->getLists(['village_id' => $data['village_id'], 'uid' => $data['uid']], '*');
        $list = [];
        if (!empty($res)) {
            $res = $res->toArray();
            if (!empty($res)) {
                $villageIds = array_unique(array_column($res, 'village_id'));
                $db_house_village = new HouseVillage();
                $whereVillageColumn = [];
                $whereVillageColumn[] = ['village_id', 'in', $villageIds];
                $villageColumnArr = $db_house_village->getColumn($whereVillageColumn, 'village_id, village_name', 'village_id');
                foreach ($res as $k => $v) {
                    $visit_owner_switch = isset($v['visit_owner_switch']) ? intval($v['visit_owner_switch']) : 0;
                    $id = isset($v['id']) ? intval($v['id']) : 0;
                    $village_id = isset($v['village_id']) ? intval($v['village_id']) : 0;
                    $visitor_bind_id = isset($v['visitor_bind_id']) ? intval($v['visitor_bind_id']) : 0;
                    $villageInfo = isset($villageColumnArr[$village_id]) ? $villageColumnArr[$village_id] : [];
                    $village_name = isset($villageInfo['village_name']) ? trim($villageInfo['village_name']) : '';
                    if ($visit_owner_switch >= 1) {
                        $baseUrl = "/pages/village_my/shareVisitor?visitor_id={$id}&village_id={$village_id}&pigcms_id={$visitor_bind_id}&village_name={$village_name}";
                        $url = get_base_url($baseUrl);
                    } else {
                        $url = '';
                    }
                    $content = [];
                    $content[] = [
                        'key' => 'id', 'value' => $v['id'],
                    ];
                    $content[] = [
                        'key' => '姓名', 'value' => $v['visitor_name'],
                    ];
                    $content[] = [
                        'key' => '联系方式', 'value' => $v['visitor_phone'],
                    ];
                    $content[] = [
                        'key' => '车牌号', 'value' => $v['car_id'],
                    ];
                    $content[] = [
                        'key' => '添加时间', 'value' => date('Y-m-d H:i', $v['add_time']),
                    ];
                    $list[] = [
                        'content' => $content,
                        'url' => $url
                    ];
                }
            }
        }
        return $list;
    }


    /**
     * 车辆绑定页面
     * @author:zhubaodi
     * @date_time: 2021/11/12 11:34
     */
    public function get_bind_car($data)
    {
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($data['village_id'], 'province_id');
        if (!empty($village_info)) {
            $db_area = new Area();
            $province_id = $db_area->getOne(['area_id' => $village_info['province_id']]);
        }
        if (!empty($province_id)) {
            $list['province_text'] = $province_id['area_name'];
        } else {
            $list['province_text'] = '';
        }

        $list['province'] = $this->province_car;
        $list['color'] = $this->car_color;
        $relationship = $this->relationship;
        $list['relationship'] = [];
        if (!empty($relationship)) {
            foreach ($relationship as $k => $v) {
                $list['relationship'][$k - 1]['id'] = $k;
                $list['relationship'][$k - 1]['name'] = $v;
            }
        }
        $db_brand_cars = new BrandCars();
        $brand_list = $db_brand_cars->getList([]);
        $brand = [];
        if (!empty($brand_list)) {
            $brand_list = $brand_list->toArray();
            $brand_list1 = $brand_list;
            $brand = [];
            $i = 0;
            $brand2 = [];
            foreach ($brand_list as $k => $v) {
                $brand1 = [];
                foreach ($brand_list1 as $kk => $vv) {
                    if ($vv['first_name'] == $v['first_name']) {
                        $brand2 ['value'] = $vv['brand_id'];
                        $brand2['label'] = $vv['brand_name'];
                        $brand1['children'][] = $brand2;
                        unset($brand_list1[$kk]);
                    }
                }

                if (!empty($brand1)) {
                    $brand1['value'] = $i;
                    $brand1['label'] = $v['first_name'];
                    unset($brand_list[$k]);
                    $brand[] = $brand1;
                    $i++;
                }

            }
        }
        $list['skiptype'] = 1;
        $configCustomizationService=new ConfigCustomizationService();
        $isAihorseCustomizedJudge=$configCustomizationService->getAihorseCustomizedJudge();
        $houseVillageParkingGarage=new HouseVillageParkingGarage();
        if($data['village_id']>0 && $isAihorseCustomizedJudge){
            $app_id=cfg('park_aicar_app_id');
            $app_id=$app_id ? trim($app_id):'';
            $app_secret=cfg('park_aicar_app_secret');
            $app_secret=$app_secret ? trim($app_secret):'';
            if($app_id && $app_secret){
                $whereArr=array(['village_id','=',$data['village_id']],['status','=',1],['third_park_key','<>','']);
                $parkingGarageObj=$houseVillageParkingGarage->getOne($whereArr,'*');
                if($parkingGarageObj && !$parkingGarageObj->isEmpty()){
                    $list['skiptype']=0;
                }
            }
        }
        $list['brand'] = $brand;
        return $list;
    }

    /**
     * 获取车牌品牌
     * @author:zhubaodi
     * @date_time: 2022/7/20 14:02
     */
    public function getCarBrands(){
        $db_brand_cars = new BrandCars();
        $brand_list = $db_brand_cars->getList([]);
        $brand = [];
        if (!empty($brand_list)) {
            $brand_list = $brand_list->toArray();
            $brand_list1 = $brand_list;
            $brand = [];
            $i = 0;
            $brand2 = [];
            foreach ($brand_list as $k => $v) {
                $brand1 = [];
                foreach ($brand_list1 as $kk => $vv) {
                    if ($vv['first_name'] == $v['first_name']) {
                        $brand2 ['value'] = $vv['brand_id'];
                        $brand2['label'] = $vv['brand_name'];
                        $brand1['children'][] = $brand2;
                        unset($brand_list1[$kk]);
                    }
                }

                if (!empty($brand1)) {
                    $brand1['value'] = $i;
                    $brand1['label'] = $v['first_name'];
                    unset($brand_list[$k]);
                    $brand[] = $brand1;
                    $i++;
                }

            }
        }
        return $brand;
    }

    public function add_bind_car($data,$is_public_car=0)
    {
        $car_data = [];
        $car_data['village_id'] = $data['village_id'];
        $car_data['car_position_id'] = 0;
        $car_data['car_type'] = 0;
        $car_data['province'] = mb_substr($data['car_number'], 0, 1);
        $car_data['car_number'] = mb_substr($data['car_number'], 1);
        $car_data['car_stop_num'] = '';
        $car_data['car_user_name'] =$data['car_user_name'];
        $car_data['car_user_phone'] = $data['car_user_phone'];
        $car_data['car_displacement'] = 0;
        $car_data['car_addtime'] = time();
        $car_data['car_color'] = $data['car_color'];
        $car_data['equipment_no'] = $data['equipment_no'];
        $car_data['car_position_id_path'] = 0;
        $car_data['examine_status'] = 0;
        $car_data['car_brands'] = $data['brands'] . '-' . $data['car_type'];
        $car_data['uid'] = $data['uid'];
        $car_data['binding_type']=1;
        if($data['relationship']==1){
            $car_data['binding_type']=2;
        }
        if($is_public_car==1){
            $car_data['binding_type']=0;
        }
        if (!empty($park_config_info)&&$park_config_info['park_sys_type']=='D7'){
            $car_data['parking_car_type'] =9;
        }

        if ($data['relationship']==1){
            $db_house_village_user_bind = new HouseVillageUserBind();
            $bing_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$data['pigcms_id'],'village_id'=>$data['village_id'],'status'=>1],'name,phone,pigcms_id');
            if (!empty($bing_info)){
                $car_data['car_user_name'] =$bing_info['name'];
                $car_data['car_user_phone'] = $bing_info['phone'];
            }else{
                $db_user = new User();
                $user_info=$db_user->getOne(['uid'=>$data['uid'],'status'=>1],'nickname,phone,uid');
                $car_data['car_user_name'] =$user_info['nickname'];
                $car_data['car_user_phone'] = $user_info['phone'];
            }
        }
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $id = $db_house_village_parking_car->addHouseVillageParkingCar($car_data);
        $ids = 0;
        if ($id) {
            $db_house_village_bind_car = new HouseVillageBindCar();
            $bind_car = [];
            $bind_car['car_id'] = $id;
            $bind_car['village_id'] = $data['village_id'];
            $bind_car['user_id'] = 0;
            if($is_public_car<1 && isset($data['pigcms_id']) && $data['pigcms_id']){
                $bind_car['user_id'] = $data['pigcms_id'];
            }
            $bind_car['uid'] = $data['uid'];
            $bind_car['relationship'] = $data['relationship'];
            $bind_car['is_default'] = 0;
            $ids = $db_house_village_bind_car->add($bind_car);
        }
        return $ids;
    }

    public function edit_bind_car($data)
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_bind_car = new HouseVillageBindCar();
        $bind_car = [];
        $bind_car['relationship'] = $data['relationship'];
        $id = $db_house_village_bind_car->saveOne(['car_id' => $data['car_id']], $bind_car);
        $ids = 0;

        $car_info = $db_house_village_bind_car->getFind(['car_id' => $data['car_id']]);
        $car_data = [];
        $car_data['examine_status']=0;
        $car_data['province'] = mb_substr($data['car_number'], 0, 1);
        $car_data['car_number'] = mb_substr($data['car_number'], 1);
        $car_data['car_color'] = $data['car_color'];
        $car_data['equipment_no'] = $data['equipment_no'];
        $car_data['car_brands'] = $data['brands'] . '-' . $data['car_type'];
        $car_data['car_user_name'] =$data['car_user_name'];
        $car_data['car_user_phone'] = $data['car_user_phone'];
        $ids = $db_house_village_parking_car->editHouseVillageParkingCar(['car_id' => $car_info['car_id']], $car_data);

        return $ids;
    }

    public function get_parking_car_info($whereArr=array(),$field='*')
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $car_info = $db_house_village_parking_car->getFind($whereArr, $field);
        if($car_info && !$car_info->isEmpty()){
            $car_info=$car_info->toArray();
        }else{
            $car_info=array();
        }
        return $car_info;
    }

    public function set_car_default($data)
    {

        $db_house_village_bind_car = new HouseVillageBindCar();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $car_info = $db_house_village_parking_car->getFind(['village_id' => $data['village_id'], 'car_id' => $data['car_id']]);
        $bind_car_info = $db_house_village_bind_car->getFind(['village_id' => $data['village_id'], 'car_id' => $car_info['car_id']]);
        $uid=isset($data['uid']) ? $data['uid']:0;
        if (!empty($car_info)&&!empty($bind_car_info)) {
            if($uid>0 && isset($car_info['uid'])){
                $db_house_village_parking_car->editHouseVillageParkingCar(array('car_id' => $car_info['car_id']),array('uid'=>$uid));
            }
            $bind_car = $db_house_village_bind_car->getFind(['village_id' => $data['village_id'], 'uid' => $bind_car_info['uid'], 'is_default' => 1]);
            if (empty($bind_car)) {
                $res = $db_house_village_bind_car->saveOne(['village_id' => $data['village_id'], 'uid' => $bind_car_info['uid'], 'car_id' => $data['car_id']], ['is_default' => 1]);
            } else {
                $db_house_village_bind_car->saveOne(['village_id' => $data['village_id'], 'uid' => $bind_car_info['uid'], 'is_default' => 1], ['is_default' => 0]);
                $res = $db_house_village_bind_car->saveOne(['village_id' => $data['village_id'], 'uid' => $bind_car_info['uid'], 'car_id' => $car_info['car_id']], ['is_default' => 1]);
            }
             return '设置成功';
        }else if(!empty($car_info)&&empty($bind_car_info)){
            if($uid>0 && isset($car_info['uid'])){
                $db_house_village_parking_car->editHouseVillageParkingCar(array('car_id' => $car_info['car_id']),array('uid'=>$uid));
            }
            $bind_car = [];
            $bind_car['car_id'] = $car_info['car_id'];
            $bind_car['village_id'] = $car_info['village_id'];
            $bind_car['user_id'] = 0;
            if(isset($data['pigcms_id']) && $data['pigcms_id']){
                $bind_car['user_id'] = $data['pigcms_id'];
            }
            $bind_car['uid'] =$uid ;
            $bind_car['relationship'] = 7;
            $bind_car['is_default'] = 1;
            $ids = $db_house_village_bind_car->add($bind_car);
            return '设置成功！';
        }
        return '修改失败！';

    }

    //D5停车设备月租车删除
    public function del_d5($village_id,$car_id){
        $houseVillageParkGarageConfigService= new HouseVillageParkGarageConfigService();
        $parkingCarModel = new HouseVillageParkingCar();
        $whereArr=array();
        $whereArr[]=array('car_id','=',$car_id);
        $whereArr[]=array('village_id','=',$village_id);
        $parking_car=$parkingCarModel->getOne($whereArr);
        $garage_id=0;
        $db_house_village_ParkIng_position=new HouseVillageParkingPosition();
        if(isset($parking_car['garage_id']) && $parking_car['garage_id']>0){
            $garage_id=$parking_car['garage_id'];
        }else if(isset($parking_car['car_position_id']) && $parking_car['car_position_id']>0){
            $garage_arr=  $db_house_village_ParkIng_position->getFind(['position_id'=>$parking_car['car_position_id']],'position_id,garage_id,position_num');
            if($garage_arr && isset($garage_arr['garage_id']) && $garage_arr['garage_id']>0){
                $garage_id= $garage_arr['garage_id'];
            }
        }
        $park_config_info =$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        if(empty($park_config_info)){
            return ['error'=>true,'msg'=>'请您开启D5停车设备'];
        }
        if($park_config_info['park_versions'] == 2 && $park_config_info['park_sys_type'] == 'D5'){
            $config=[
                'base_url'=>$park_config_info['d5_url'],
                'userName'=>$park_config_info['d5_name'],
                'passWord'=>$park_config_info['d5_pass']
            ];
            $rel=(new D5Service())->parkCarDel($config,$car_id);
            if (!isset($rel['success'])){
                return ['error'=>false,'msg'=>'删除失败'];
            }
            if(!$rel['success']){
                return ['error'=>false,'msg'=>$rel['errMsg']];
            }
        }
        return ['error'=>true,'msg'=>''];
    }

    public function del_bind_car($data)
    {
        $db_house_village_bind_car = new HouseVillageBindCar();
        $bind_car = $db_house_village_bind_car->getFind(['id' => $data['id']]);
        if (!empty($bind_car)) {
            $car_info = (new HouseVillageParkingCar())->getFind([
                'village_id' => $bind_car['village_id'],
                'car_id' => $bind_car['car_id']
            ],'examine_status');
            if($car_info && $car_info['examine_status'] == 1){
                $result=$this->del_d5($data['village_id'],$bind_car['car_id']);
                if(!$result['error']){
                    throw new \think\Exception($result['msg']);
                }
            }
            $res = $db_house_village_bind_car->delOne(['id' => $data['id']]);
        } else {
            throw new \think\Exception('绑定信息不存在');
        }

        return $res;
    }


    public function get_mouth_list($data)
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village = new HouseVillage();

        $village_info = $db_house_village->getOne($data['village_id'], 'village_name');

        $car_info = $db_house_village_parking_car->getFind(['village_id' => $data['village_id'], 'car_number' => mb_substr($data['car_number'], 1), 'province' => mb_substr($data['car_number'], 0, 1)]);

        $position_info = $db_house_village_parking_position->getLists(['pp.position_id' => $car_info['car_position_id']]);
        $db_house_village_park = new HouseVillagePark();
        $list = $db_house_village_park->getList(['village_id' => $data['village_id'], 'status' => 1]);

        $mouth_list = [];
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $mouth_list[$k]['name'] = $v['park_month_num'] . '个月';
                $mouth_list[$k]['coupon'] = '送' . $v['presented_park_month_num'] . '个月';
                $mouth_list[$k]['money'] = '￥' . $v['park_month_price'];
                $aa = $v['park_month_num'] + $v['presented_park_month_num'];
                $mouth_list[$k]['time'] = date('Y-m-d', strtotime($aa . ' mouth', $car_info['end_time']));
            }
        }
        $carInfo = [];
        $carInfo['village_name'] = $village_info['village_name'];
        $carInfo['position_num'] = $position_info['position_num'];
        $carInfo['garage_name'] = $village_info['garage_num'];
        $carInfo['villcar_numberage_name'] = $data['car_number'];
        $carInfo['end_time'] = date('Y-m-d', $car_info['end_time']);

        $list = [];
        $list['mouth'] = $mouth_list;
        $list['carInfo'] = $carInfo;
        return $list;
    }


    public function payment_car($data)
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_parking_position = new HouseVillageParkingPosition();
        $db_house_village = new HouseVillage();

        $village_info = $db_house_village->getOne($data['village_id'], 'village_name');

        $car_info = $db_house_village_parking_car->getFind(['village_id' => $data['village_id'], 'car_number' => mb_substr($data['car_number'], 1), 'province' => mb_substr($data['car_number'], 0, 1)]);

        $position_info = $db_house_village_parking_position->getLists(['pp.position_id' => $car_info['car_position_id']]);


        $carInfo = [];
        $carInfo['village_name'] = $village_info['village_name'];
        $carInfo['position_num'] = $position_info['position_num'];
        $carInfo['garage_name'] = $village_info['garage_num'];
        $carInfo['villcar_numberage_name'] = $data['car_number'];

        return $carInfo;
    }


    public function get_bind_car_info($data)
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_bind_car = new HouseVillageBindCar();
        $car_info = $db_house_village_parking_car->getFind(['village_id' => $data['village_id'], 'car_id' => $data['car_id']]);
        $bind_car_info = [];
        if (!empty($car_info)) {
            $car_brands = explode('-', $car_info['car_brands']);

            $bind_car_info['car_number'] = $car_info['province'] . $car_info['car_number'];
            $bind_car_info['car_color'] = $car_info['car_color'];
            $bind_car_info['car_color_str']='';
            foreach ($this->car_color as $cvv){
                if($cvv['id']==$car_info['car_color']){
                    $bind_car_info['car_color_str']=$cvv['car_color'];
                    break;
                }
            }
            $bind_car_info['car_brands'] = empty($car_info['car_brands']) ? '' : $car_brands[0];
            $bind_car_info['car_brands1'] = empty($car_info['car_brands']) ? '' : $car_brands[1];
            $bind_car_info['equipment_no'] = $car_info['equipment_no'];
            $bind_car_info['car_user_name'] = $car_info['car_user_name'];
            $bind_car_info['car_user_phone'] = $car_info['car_user_phone'];
            $bind_car_info['relationship']='其他';
            $bind_car_info['relationship_id']=7;
            $bind_car = $db_house_village_bind_car->getFind(['village_id' => $data['village_id'], 'car_id' => $data['car_id']]);
            if (!empty($bind_car)) {
                $bind_car_info['relationship'] = $this->relationship[$bind_car['relationship']];
                $bind_car_info['relationship_id'] = $bind_car['relationship'];
            }
        }
        return $bind_car_info;
    }

    public function get_car_bind_position_list($data = array())
    {
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $db_house_village_bind_car = new HouseVillageBindCar();
        $car_info = $db_house_village_parking_car->getFind(['village_id' => $data['village_id'], 'car_id' => $data['car_id']]);
        $db_brand_cars = new BrandCars();
        $bind_car_info = [];
        if (!empty($car_info) && !$car_info->isEmpty()) {
            $car_info = $car_info->toArray();
            $car_brands = explode('-', $car_info['car_brands']);
            $bind_car_info['car_id'] = $car_info['car_id'];
            $bind_car_info['car_number'] = $car_info['province'] . $car_info['car_number'];
            $bind_car_info['car_color'] = $car_info['car_color'];
            $bind_car_info['car_color_str'] = '';
            foreach ($this->car_color as $cvv) {
                if ($cvv['id'] == $car_info['car_color']) {
                    $bind_car_info['car_color_str'] = $cvv['car_color'];
                    break;
                }
            }
            $logo = $db_brand_cars->getFind(['brand_name' => $car_brands[0]]);
            $bind_car_info['car_brands_logo'] = cfg('site_url') . $logo['brand_logo'];
            $bind_car_info['car_brands'] = empty($car_info['car_brands']) ? '' : $car_brands[0];
            $bind_car_info['car_brands1'] = empty($car_info['car_brands']) ? '' : $car_brands[1];
            $bind_car_info['equipment_no'] = $car_info['equipment_no'];
            $bind_car_info['car_user_name'] = $car_info['car_user_name'];
            $bind_car_info['car_user_phone'] = $car_info['car_user_phone'];
            $bind_car_info['relationship'] = '其他';
            $bind_car_info['relationship_id'] = 7;
            $bind_car = $db_house_village_bind_car->getFind(['village_id' => $data['village_id'], 'car_id' => $data['car_id']]);
            if (!empty($bind_car)) {
                $bind_car_info['relationship'] = $this->relationship[$bind_car['relationship']];
                $bind_car_info['relationship_id'] = $bind_car['relationship'];
            }

            $returnArr = array('car_info' => '', 'bind_position_list' => array());
            if (!empty($bind_car_info)) {
                $returnArr['car_info'] = $bind_car_info;
            }
            $houseVillageParkingCarBindPositionDb = new HouseVillageParkingCarBindPosition();
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $bind_position_ids = array();
            if (isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])) {
                $bind_position_ids = explode(',', $car_info['bind_position_ids']);
                $bind_position_ids = array_map('intval', $bind_position_ids);
            }
            if ($car_info['car_position_id']) {
                $bind_position_ids[] = intval($car_info['car_position_id']);
            }
            $now_time = time();
            if ($bind_position_ids) {
                $bind_position_ids = array_unique($bind_position_ids);
                $bindPositionList = array();
                foreach ($bind_position_ids as $position_id) {
                    $tmp_position = array('position_id' => $position_id);
                    $tmp_position['position_num'] = '';
                    $tmp_position['garage_id'] = 0;
                    $tmp_position['garage_num'] = '';
                    $tmp_position['name'] = '';
                    $tmp_position['phone'] = '';
                    $tmp_position['end_time'] = 0;
                    $tmp_position['end_time_str'] = '';
                    $tmp_position['surplus_time_str'] = '';
                    $whereArr = array('pp.position_id' => $position_id);
                    $whereArr['pp.village_id'] = $data['village_id'];
                    $parking_position = $db_house_village_parking_position->getOne($whereArr, 'pp.position_num,pp.garage_id,pp.end_time,pg.garage_num,ub.name,ub.phone');
                    if ($parking_position && !$parking_position->isEmpty()) {
                        $tmp_position['position_num'] = $parking_position['position_num'];
                        $tmp_position['garage_id'] = $parking_position['garage_id'];
                        $tmp_position['garage_num'] = $parking_position['garage_num'] ? $parking_position['garage_num'] : '';
                        $tmp_position['name'] = !empty($parking_position['name']) ? $parking_position['name'] : '';
                        $tmp_position['phone'] = !empty($parking_position['phone']) ? $parking_position['phone'] : '';
                        if ($parking_position['end_time'] && $parking_position['end_time'] > 100) {
                            $tmp_position['end_time'] = $parking_position['end_time'];
                            $tmp_position['end_time_str'] = date('Y-m-d', $parking_position['end_time']);
                            if ($parking_position['end_time'] < $now_time) {
                                $tmp_position['surplus_time_str'] = '已经过期了';
                            } else {
                                $surplus_time = $parking_position['end_time'] - $now_time;
                                $surplus_time = $surplus_time / 86400;
                                $surplus_time = round($surplus_time);
                                if ($surplus_time < 1) {
                                    $tmp_position['surplus_time_str'] = '即将到期';
                                } else {
                                    $tmp_position['surplus_time_str'] = '剩余' . $surplus_time . '天到期';
                                }
                            }
                        }
                    }
                    $bindPositionList[] = $tmp_position;
                }
                $returnArr['bind_position_list'] = $bindPositionList;
            }
            return $returnArr;
        }
        throw new \think\Exception("车辆信息不存在！");
    }

    /**
     * 查询抬竿指令
     * @author:zhubaodi
     * @date_time: 2021/12/8 13:36
     */
    public function open_gate($data){
        $db_park_open_log=new ParkOpenLog();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_park_passage = new ParkPassage();
        $db_house_village = new HouseVillage();
        $db_house_village_pay_order = new HouseVillagePayOrder();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_park_showscreen_log = new ParkShowscreenLog();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $db_house_visitor = new HouseVillageVisitor();
        $db_house_village_parking_temp=new HouseVillageParkingTemp();
        $db_house_village_parking_car = new HouseVillageParkingCar();
        $now_time=time();
        $receiveTime = date('mdH');
        $res=[];
        $res['data']=false;
        $res['showscreen']=[];
        $serialno = isset($data['serialno']) && $data['serialno'] ? $data['serialno'] : '';
        if (!empty($serialno)){
            //查询设备
            if (!empty(cache('park:'.$serialno.':serialno'))) {
                $passage_info= cache('park:'.$serialno.':serialno');
            }else {
                $passage_info = $db_park_passage->getFind(['device_number' => $data['serialno'], 'status' => 1]);
                if($passage_info && !$passage_info->isEmpty()){
                    $passage_info=$passage_info->toArray();
                }
                if($passage_info && isset($passage_info['device_ip']) && empty($passage_info['device_ip']) && isset($data['ipaddr']) && $data['ipaddr']){
                    $saveUpdateArr=array('device_ip'=>$data['ipaddr']);
                    if(isset($data['user_name']) && $data['user_name']){
                        $saveUpdateArr['device_account']=$data['user_name'];
                    }
                    if(isset($data['pass_wd']) && $data['pass_wd']){
                        $saveUpdateArr['device_pwd']=$data['pass_wd'];
                    }
                    $db_park_passage->save_one(array('id'=>$passage_info['id']),$saveUpdateArr);
                }
                cache('park:'.$serialno.':serialno',$passage_info,86400);
            }
            $carHandleGetInfoService= new CarHandleGetInfoService();
            $park_sys_type = isset($passage_info['park_sys_type']) && $passage_info['park_sys_type'] ? trim($passage_info['park_sys_type']) : 'D3';
            if ($passage_info['park_sys_type']=='A11'){
               $heartLastTime= $carHandleGetInfoService->getParkPassageHeart($serialno);
                if (!empty($passage_info)){
                    $village_id1 = isset($passage_info['village_id']) ? intval($passage_info['village_id']) : 0;
                    $carHandleGetInfoService->setParkPassageHeart($serialno, $now_time);
                    //$db_park_passage->save_one(['device_number' => $data['serialno'], 'status' => 1],['last_heart_time'=>$now_time]);
                } else {
                    $village_id1 = 0;
                    $carHandleGetInfoService->setParkPassageHeart($serialno, $now_time);
                }
                $where = [
                    'channel_id' => $data['serialno']
                ];
                $log_info = $db_park_open_log->get_one($where);

                if (!empty($log_info) && !$log_info->isEmpty() && isset($log_info['id'])) {
                    if($log_info['open_type']==4){
                        //关闸
                        $res=array('data'=>false,'pay_info'=>false,'is_close_park_passage'=>1);
                        return $res;
                    }
                    $log_info=$log_info->toArray();
                    if($log_info['car_number']=='_无_'){
                        $db_park_open_log->delOne(['id' => $log_info['id']]);
                    }else{
                        $result = $this->addA11Park($data, $log_info, $now_time);
//                        fdump_api(['通道信息' => $serialno, 'result' => $result, 'now_time' => $now_time],'park_temp/heartbeat_'.$serialno.'_log',1);
                        $db_park_open_log->delOne(['id' => $log_info['id']]);
                        $res['data'] = $result;
                    }
                }
                //查询待同步的白名单月租车
                $white = [];
                $white['device_number'] = $data['serialno'];
                $white_list = $this->getWhitelist($white);
                if (!empty($white_list)) {
                    $res['white_list'] = $white_list;
                }
                $showscreen_info = $db_park_showscreen_log->getFind($where);
                if (!empty($showscreen_info) && isset($showscreen_info['id'])) {
                    $showscreen_info=$showscreen_info->toArray();
                    $db_park_showscreen_log->delOne(['id' => $showscreen_info['id']]);
                    $res['showscreen'] = $showscreen_info;
                    if ($passage_info['passage_type'] == 2 && $passage_info['passage_direction'] == 1) {
                        $res['showscreen']['passage_type'] = 2;
                    } else {
                        $res['showscreen']['passage_type'] = 1;
                    }
                }
                $tmpJudgeTime=$now_time-300; //5分钟 心跳时间相差5分钟 认为设备断网重连的
                if($heartLastTime<$tmpJudgeTime){
                    //重新下发显屏设置内容
                    $this->issuedScreenSetCmd($passage_info,$heartLastTime);
                }
                return $res;
            }
            
            if (!empty($passage_info)){
                $village_id1 = isset($passage_info['village_id']) ? intval($passage_info['village_id']) : 0;
                $carHandleGetInfoService->setParkPassageHeart($serialno, $now_time);
                //$db_park_passage->save_one(['device_number' => $data['serialno'], 'status' => 1],['last_heart_time'=>$now_time]);
            } else {
                $village_id1 = 0;
                $carHandleGetInfoService->setParkPassageHeart($serialno, $now_time);
            }
            $where=[
                'channel_id'=>$data['serialno']
            ];
            $log_info_obj=$db_park_open_log->get_one($where);
            $log_info=array();
            if (!empty($log_info_obj)&& !$log_info_obj->isEmpty() && isset($log_info_obj['id']) ){
                $log_info=$log_info_obj->toArray();
                $db_park_open_log->delOne(['id'=>$log_info['id']]);
                if($log_info['open_type']==4){
                    //关闸
                    $res=array('data'=>false,'pay_info'=>false,'is_close_park_passage'=>1);
                    return $res;
                }
            }
            //查询待同步的白名单月租车
            $white=[];
            $white['device_number']=$data['serialno'];
            $white_list=$this->getWhitelist($white);
            if (!empty($white_list)){
                $res['white_list']=$white_list;
            }

            $showscreen_info=$db_park_showscreen_log->getFind($where);
            if (!empty($showscreen_info)&&isset($showscreen_info['id'])){
                $showscreen_info=$showscreen_info->toArray();
                $db_park_showscreen_log->delOne(['id'=>$showscreen_info['id']]);
                $res['showscreen']=$showscreen_info;
                if ($passage_info['passage_type'] == 2 && $passage_info['passage_direction'] == 1) {
                    $res['showscreen']['passage_type'] = 2;
                } else {
                    $res['showscreen']['passage_type'] = 1;
                }
            }
            if (!empty($log_info) && isset($log_info['id'])){
                
                if (!empty($log_info['car_number'])){
                    //查询设备
                    //  $passage_info = $db_park_passage->getFind(['device_number' => $data['serialno'], 'status' => 1]);
                    // 记录车辆进入信息
                    $car_access_record = [];
                    $car_access_record['channel_id']     = $passage_info['id'];
                    $car_access_record['channel_number'] = $passage_info['channel_number'];
                    $car_access_record['channel_name']   = $passage_info['passage_name'];
                    $car_number = $log_info['car_number'];
                    $province = mb_substr($car_number, 0, 1);
                    $car_no = mb_substr($car_number, 1);
                    $carCachekey='village:'.$passage_info['village_id'].':car:'.$car_number;
                    if (!empty(cache($carCachekey))) {
                        $car_info = cache($carCachekey);
                        if (!isset($car_info['end_time']) || $car_info['end_time'] <= $now_time) {
                            // 缓存中取不出来车辆信息或者截止时间过期了 避免出现问题 及时查询下最新的信息并缓存
                            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                            cache($carCachekey,$car_info);
                        }
                    }else {
                        $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
                        cache($carCachekey,$car_info);
                    }
                    $user_name  = empty($car_info['car_user_name']) ? '' : $car_info['car_user_name'];
                    $user_phone = empty($car_info['car_user_phone'])? '' : $car_info['car_user_phone'];
                    $car_visitor_cache='village:'.$passage_info['village_id'].':car_visitor:' . $car_number;
                    
                    if (!$user_name && !$user_phone && !empty(cache($car_visitor_cache))) {
                        $visitor_info = cache($car_visitor_cache);
                        $user_name  = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
                        $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
                        $uid        = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
                    } elseif (!$user_name && !$user_phone) {
                        $visitor_info = $db_house_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], 'id, visitor_name, visitor_phone, uid');
                        cache($car_visitor_cache, $visitor_info);
                        $user_name  = empty($visitor_info['visitor_name']) ? '' : $visitor_info['visitor_name'];
                        $user_phone = empty($visitor_info['visitor_phone']) ? '' : $visitor_info['visitor_phone'];
                        $uid        = empty($visitor_info['uid']) ? '' : $visitor_info['uid'];
                    } else {
                        $visitor_info = [];
                    }
                    if ($user_name) {
                        $car_access_record['user_name'] = $user_name;
                    }
                    if ($user_phone) {
                        $car_access_record['user_phone'] = $user_phone;
                    }
                    if (isset($uid) && $uid) {
                        $car_access_record['uid'] = $uid;
                    }

                    if ($passage_info['passage_direction'] == 1) {
                        if (!empty(cache($passage_info['village_id']))) {
                            $village_info= cache($passage_info['village_id']);
                        }else{
                            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
                            cache($passage_info['village_id'],$village_info,86400);
                        }
                        $park_data = [];
                        $park_data['car_number'] = $log_info['car_number'];
                        $park_data['in_time'] = $now_time;
                        $park_data['order_id'] = uniqid();
                        $park_data['in_channel_id'] = $passage_info['id'];
                        $park_data['is_paid'] = 0;
                        $park_data['park_id'] = $passage_info['village_id'];
                        $park_data['park_sys_type'] = $park_sys_type;
                        $park_data['park_name'] = $village_info['village_name'];
                        $starttime = time() - 60;
                        $endtime = time() + 30;
                        $park_where = [
                            ['car_number', '=', $log_info['car_number']],
                            ['park_id', '=', $passage_info['village_id']],
                            ['in_time', '>=', $starttime],
                            ['in_time', '<=', $endtime],
                            ['del_time', '=', 0],
                        ];
                        $park_info_car = $db_in_park->getOne1($park_where);
                        if (!$park_info_car || !isset($park_info_car['id'])) {
                            $insert_id=$db_in_park->insertOne($park_data);
                        } else {
                            $insert_id = $park_info_car['id'];
                        }
                        $car_access_record['business_type'] = 0;
                        $car_access_record['business_id'] = $passage_info['village_id'];
                        $car_access_record['car_number'] = $log_info['car_number'];
                        $car_access_record['accessType'] = 1;
                        $car_access_record['accessTime'] = $now_time;
                        if ($log_info['open_type']==1){
                            $car_access_record['accessMode'] = 9;
                        }elseif ($log_info['open_type']==3){
                            $car_access_record['accessMode'] = 7;
                        }
                        $car_access_record['park_sys_type'] = $park_sys_type;
                        $car_access_record['is_out'] = 0;
                        $car_access_record['park_id'] = $passage_info['village_id'];
                        $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                        $car_access_order_id=date('YmdHis').rand(100,999);
                        $car_access_record['order_id'] = $car_access_order_id;
                        $car_access_record['update_time'] = $now_time;
                        if (isset($insert_id)&&!empty($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        //fdump_api(['回调接口' . __LINE__,$car_access_record], 'add_park_info_2', 1);
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                        if($record_id>0){
                            $in_park_info = $db_in_park->getOne(array('id'=>$insert_id),'*');
                            if($in_park_info && isset($in_park_info['access_order_id']) && empty($in_park_info['access_order_id'])){
                                $saveArr=array('access_record_id'=>$record_id,'access_order_id'=>$car_access_order_id);
                                $db_in_park->saveOne(array('id'=>$insert_id),$saveArr);
                            }
                            $tmpUpdateRecordArr=array('car_number'=> $log_info['car_number'],'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$car_access_order_id,'access_type'=>1,'access_time'=>time());
                            (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                            
                            /*
                            $access_record_where_near = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['is_out', '=', 0],
                                ['del_time', '<', 1],
                                ['record_id', '<', $record_id],
                            ];
                            //最近一条未出场的 记录
                            $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                            if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                                $access_record_tmp=$access_record_tmp->toArray();
                                $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                            }
                            */
                        }
                    }
                    if ($passage_info['passage_direction'] == 0) {
                        if (!empty(cache($passage_info['village_id']))) {
                            $village_info= cache($passage_info['village_id']);
                        }else{
                            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
                            cache($passage_info['village_id'],$village_info,86400);
                        }
                        $whereInRecord = ['car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
                        $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
                        if($in_park_pay_info && !$in_park_pay_info->isEmpty()){
                            $in_park_pay_info=$in_park_pay_info->toArray();
                        }else{
                            $in_park_pay_info=array();
                        }
                         fdump_api(['car_number'=>$car_number,'in_park_pay_info'=>$in_park_pay_info],'open_gate'.date('H'),1);
                        if (!empty($in_park_pay_info) && isset($in_park_pay_info['id'])) {
                            $access_record_id= isset($in_park_pay_info['access_record_id']) && $in_park_pay_info['access_record_id'] ? $in_park_pay_info['access_record_id']:0;
                            $access_order_id= isset($in_park_pay_info['access_order_id']) && $in_park_pay_info['access_order_id'] ? $in_park_pay_info['access_order_id']:'';
                            if (!empty($in_park_pay_info['pay_order_id'])) {
                                $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $log_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1],'*','price desc');
                                $pay_info = $db_house_new_pay_order->get_one(['summary_id' => $in_park_pay_info['pay_order_id'], 'is_paid' => 1]);
                                //  fdump_api([$pay_info,$in_park_pay_info['pay_order_id']],'open_gate_0519',1);
                                $in_record_id = isset($park_temp_info['in_record_id']) && $park_temp_info['in_record_id'] ? $park_temp_info['in_record_id'] : 0;
                                $pay_time = isset($park_temp_info['pay_time']) && $park_temp_info['pay_time'] ? $park_temp_info['pay_time'] : 0;
                                ! $pay_time && $pay_time = isset($pay_info['pay_time']) && $pay_info['pay_time'] ? $pay_info['pay_time'] : time();
                                $is_paid = isset($park_temp_info['in_record_id']) && intval($park_temp_info['is_pay_scene']) === 0 ? 2 : 1;
                                if (!empty($pay_info)) {
                                    //写入车辆入场表
                                    $park_data = [];
                                    $park_data['out_time'] = $now_time;
                                    $park_data['is_out'] = 1;
                                    $park_data['is_paid'] = 1;
                                    $res_in=$db_in_park->saveOne(['id'=>$in_park_pay_info['id'],'car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id']], $park_data);
                                    $insert_id=$in_park_pay_info['id'];
                                    /*
                                    $out_data = [];
                                    $out_data['car_number'] = $log_info['car_number'];
                                    $out_data['park_sys_type'] = $park_sys_type;
                                    if (!empty($in_park_pay_info)) {
                                        $out_data['in_time'] = $in_park_pay_info['in_time'];
                                        $out_data['order_id'] = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : '';
                                        $out_data['in_park_id'] = $in_park_pay_info['id'];
                                    }
                                    $out_data['out_time'] = $now_time;
                                    $out_data['park_id'] = $passage_info['village_id'];
                                    $out_data['total'] = $park_temp_info['price'];
                                    $out_data['pay_type'] = 'scancode';
                                    $starttime = time() - 30;
                                    $endtime = time() + 30;
                                    $park_where = [
                                        ['car_number', '=', $log_info['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['out_time', '>=', $starttime],
                                        ['out_time', '<=', $endtime],
                                    ];
                                    //fdump_api([$park_where,$out_data],'open_gate_0519',1);
                                    $park_info_car = $db_out_park->getOne($park_where);
                                    //写入车辆出场表
                                    if (!$park_info_car || !isset($park_info_car['id'])) {
                                        $out_data['pay_order_id'] = $in_park_pay_info['pay_order_id'];
                                        $insert_id= $db_out_park->insertOne($out_data);
                                    } else {
                                        $insert_id = $park_info_car['id'];
                                    }
                                    */
                                    $park_where = [
                                        ['car_number', '=', $log_info['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['accessType', '=',1],
                                        ['is_out', '=', 0],
                                        ['del_time', '=', 0],
                                    ];
                                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                    if($park_info_car111 && isset($park_info_car111['record_id']) && $park_info_car111['record_id']){
                                        $car_access_record['car_id']=$park_info_car111['car_id'];
                                        $car_access_record['position_id']=$park_info_car111['position_id'];
                                        $car_access_record['garage_id']=$park_info_car111['garage_id'];
                                        $car_access_record['position_num']=$park_info_car111['position_num'];
                                        if(isset($park_info_car111['order_id']) && $park_info_car111['order_id']){
                                            $access_order_id= $park_info_car111['order_id'];
                                        }
                                        $in_record_id=$park_info_car111['record_id'];
                                    }
                                    $car_access_record['business_type'] = 0;
                                    $car_access_record['business_id'] = $passage_info['village_id'];
                                    $car_access_record['car_number'] = $log_info['car_number'];
                                    $car_access_record['accessType'] = 2;
                                    $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                                    $car_access_record['park_time'] =$pay_info['pay_time']-$in_park_pay_info['in_time'] ;
                                    $car_access_record['total'] =$park_temp_info && $park_temp_info['price'] ? $park_temp_info['price']:0;
                                    $car_access_record['accessTime'] = $now_time;
                                    if(isset($in_park_pay_info['out_image_big']) && $in_park_pay_info['out_image_big']){
                                        $car_access_record['accessBigImage'] = $in_park_pay_info['out_image_big'];
                                    }
                                    if(isset($in_park_pay_info['out_image_small']) && $in_park_pay_info['out_image_small']){
                                        $car_access_record['accessImage'] = $in_park_pay_info['out_image_small'];
                                    }
                                    if ($log_info['open_type']==2){
                                        $car_access_record['accessMode'] = 4;
                                    }elseif ($log_info['open_type']==3){
                                        if (empty($park_info_car111)){
                                            $car_access_record['exception_type'] = 2;
                                        }else{
                                            
                                        }
                                        $car_access_record['accessMode'] = 7;
                                    }
                                    $car_access_record['park_sys_type'] = $park_sys_type;
                                    $car_access_record['is_out'] = 1;
                                    $car_access_record['park_id'] = $passage_info['village_id'];
                                    $car_access_record['garage_id'] = $passage_info['garage_id'];
                                    $car_access_record['flag_status'] = 'in_park:'.$in_park_pay_info['id'].';dt:'.date('Y-m-d H:i:s').';temp:'.$park_temp_info['id'];
                                    $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                                    $car_access_record['order_id'] = $access_order_id;
                                    //  $car_access_record['total'] = 0;
                                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                    $car_access_record['pay_type'] = $this->pay_type[6];
                                    $car_access_record['update_time'] = $now_time;
                                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                    if (isset($insert_id)) {
                                        $car_access_record['from_id'] = $insert_id;
                                    }
                                    if (isset($in_park_pay_info['pay_order_id']) && $in_park_pay_info['pay_order_id']) {
                                        $car_access_record['pay_order_id'] = $in_park_pay_info['pay_order_id'];
                                    } elseif ($in_record_id) {
                                        $in_record_info = $db_house_village_car_access_record->getOne(['record_id' => $in_record_id], 'pay_order_id');
                                        $pay_order_id = isset($in_record_info['pay_order_id']) && $in_record_info['pay_order_id'] ? intval($in_record_info['pay_order_id']) : 0;
                                        $car_access_record['pay_order_id'] = $pay_order_id;
                                    }
                                    if (isset($pay_time) && $pay_time > 1) {
                                        $car_access_record['pay_time'] = $pay_time;
                                        $car_access_record['is_paid'] = $is_paid;
                                    }
                                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                                    if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                                        //释放车位
                                        $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                        $this->updateParkingPositionUse($whereArr, $saveArr, $log_info['car_number'], 'heartbeat_out_park');
                                    }
                                    if($park_info_car111 && isset($park_info_car111['record_id'])){
                                        $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                                    }
                                    if($record_id>0){

                                        $tmpUpdateRecordArr=array('car_number'=> $log_info['car_number'],'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$access_order_id,'access_type'=>2,'access_time'=>time());
                                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                        /*
                                        $access_record_where_near = [
                                            ['car_number', '=', $log_info['car_number']],
                                            ['park_id', '=', $passage_info['village_id']],
                                            ['is_out', '=', 0],
                                            ['del_time', '<', 1],
                                            ['record_id', '<', $record_id],
                                        ];
                                        //最近一条未出场的 记录
                                        $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                                        if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                                            $access_record_tmp=$access_record_tmp->toArray();
                                            $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                                        }
                                        
                                        */
                                    }
                                }
                            }else{
                                $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $log_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1],'*','price desc');
                                $pay_time = isset($park_temp_info['pay_time']) && $park_temp_info['pay_time'] ? $park_temp_info['pay_time'] : time();
                                $is_paid = isset($park_temp_info['in_record_id']) && intval($park_temp_info['is_pay_scene']) === 0 ? 2 : 1;
                                if (!empty($park_temp_info)) {
                                    $in_record_id = isset($park_temp_info['in_record_id']) && $park_temp_info['in_record_id'] ? $park_temp_info['in_record_id'] : 0;
                                    //写入车辆入场表
                                    $park_data = [];
                                    $park_data['out_time'] = $now_time;
                                    $park_data['is_out'] = 1;
                                    $park_data['is_paid'] = 1;
                                    $res_in=$db_in_park->saveOne(['id'=>$in_park_pay_info['id'],'car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id']], $park_data);
                                    $insert_id=$in_park_pay_info['id'];
                                    /*
                                    $out_data = [];
                                    $out_data['car_number'] = $log_info['car_number'];
                                    $out_data['park_sys_type'] = $park_sys_type;
                                    if (!empty($in_park_pay_info)) {
                                        $out_data['in_time'] = $in_park_pay_info['in_time'];
                                        $out_data['order_id'] = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : '';
                                        $out_data['in_park_id'] = $in_park_pay_info['id'];
                                    }
                                    $out_data['out_time'] = $now_time;
                                    $out_data['park_id'] = $passage_info['village_id'];
                                    $out_data['total'] = $park_temp_info['price'];
                                    $out_data['pay_type'] = 'scancode';
                                    $starttime = time() - 30;
                                    $endtime = time() + 30;
                                    $park_where = [
                                        ['car_number', '=', $log_info['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['out_time', '>=', $starttime],
                                        ['out_time', '<=', $endtime],
                                    ];
//                                    fdump_api([$park_where,$out_data],'open_gate_0519',1);
                                    $park_info_car = $db_out_park->getOne($park_where);
                                    //写入车辆入场表
                                    if (!$park_info_car || !isset($park_info_car['id'])) {
                                        $insert_id= $db_out_park->insertOne($out_data);
                                    } else {
                                        $insert_id = $park_info_car['id'];
                                    }
                                    */
                                    $park_where = [
                                        ['car_number', '=', $log_info['car_number']],
                                        ['park_id', '=', $passage_info['village_id']],
                                        ['accessType', '=',1],
                                        ['is_out', '=', 0],
                                        ['del_time', '=', 0],
                                    ];
                                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                                    if($park_info_car111 && isset($park_info_car111['record_id']) && $park_info_car111['record_id']){
                                        $car_access_record['car_id']=$park_info_car111['car_id'];
                                        $car_access_record['position_id']=$park_info_car111['position_id'];
                                        $car_access_record['garage_id']=$park_info_car111['garage_id'];
                                        $car_access_record['position_num']=$park_info_car111['position_num'];
                                        if(isset($park_info_car111['order_id']) && $park_info_car111['order_id']){
                                            $access_order_id= $park_info_car111['order_id'];
                                        }
                                        $in_record_id=$park_info_car111['record_id'];
                                    }
                                    $car_access_record['business_type'] = 0;
                                    $car_access_record['business_id'] = $passage_info['village_id'];
                                    $car_access_record['car_number'] = $log_info['car_number'];
                                    $car_access_record['accessType'] = 2;
                                    $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                                    $car_access_record['park_time'] =$park_temp_info['pay_time']-$in_park_pay_info['in_time'] ;
                                    $car_access_record['total'] = $park_temp_info && $park_temp_info['price'] ? $park_temp_info['price']:0;
                                    $car_access_record['accessTime'] = $now_time;
                                    if(isset($in_park_pay_info['out_image_big']) && $in_park_pay_info['out_image_big']){
                                        $car_access_record['accessBigImage'] = $in_park_pay_info['out_image_big'];
                                    }
                                    if(isset($in_park_pay_info['out_image_small']) && $in_park_pay_info['out_image_small']){
                                        $car_access_record['accessImage'] = $in_park_pay_info['out_image_small'];
                                    }
                                    if ($log_info['open_type']==2){
                                        $car_access_record['accessMode'] = 4;
                                    }elseif ($log_info['open_type']==3){
                                        if (empty($park_info_car111)){
                                            $car_access_record['exception_type'] = 2;
                                        }else{
                                            $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                                        }
                                        $car_access_record['accessMode'] = 7;
                                    }
                                    $car_access_record['park_sys_type'] = $park_sys_type;
                                    $car_access_record['is_out'] = 1;
                                    $car_access_record['park_id'] = $passage_info['village_id'];
                                    $car_access_record['garage_id'] = $passage_info['garage_id'];
                                    $car_access_record['flag_status'] = 'in_park:'.$in_park_pay_info['id'].';dt:'.date('Y-m-d H:i:s').';temp:'.$park_temp_info['id'];
                                    $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                                    $car_access_record['order_id'] = $access_order_id;
                                    //  $car_access_record['total'] = 0;
                                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                    $car_access_record['pay_type'] = $this->pay_type[6];
                                    $car_access_record['update_time'] = $now_time;
                                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                                    if (isset($insert_id)) {
                                        $car_access_record['from_id'] = $insert_id;
                                    }
                                    if ($in_record_id) {
                                        $in_record_info = $db_house_village_car_access_record->getOne(['record_id' => $in_record_id], 'pay_order_id');
                                        $pay_order_id = isset($in_record_info['pay_order_id']) && $in_record_info['pay_order_id'] ? intval($in_record_info['pay_order_id']) : 0;
                                        $car_access_record['pay_order_id'] = $pay_order_id;
                                    }
                                    if ($pay_time > 1) {
                                        $car_access_record['pay_time'] = $pay_time;
                                        $car_access_record['is_paid'] = $is_paid;
                                    }
                                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                                    if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                                        //释放车位
                                        $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                        $this->updateParkingPositionUse($whereArr, $saveArr, $log_info['car_number'], 'heartbeat_out_park');
                                    }
                                    if($park_info_car111 && isset($park_info_car111['record_id'])){
                                        $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                                    }
                                    if($record_id>0){
                                        $tmpUpdateRecordArr=array('car_number'=> $log_info['car_number'],'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$access_order_id,'access_type'=>2,'access_time'=>time());
                                        (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                        /*
                                        $access_record_where_near = [
                                            ['car_number', '=', $log_info['car_number']],
                                            ['park_id', '=', $passage_info['village_id']],
                                            ['is_out', '=', 0],
                                            ['del_time', '<', 1],
                                            ['record_id', '<', $record_id],
                                        ];
                                        //最近一条未出场的 记录
                                        $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                                        if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                                            $access_record_tmp=$access_record_tmp->toArray();
                                            $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                                        }
                                        */
                                    }
                                }
                            }
                        }else{
                            $insert_id=0;
                            /*
                            $out_data = [];
                            $out_data['car_number'] = $log_info['car_number'];
                            $out_data['park_sys_type'] = $park_sys_type;
                            $out_data['out_time'] = $now_time;
                            $out_data['park_id'] = $passage_info['village_id'];
                            $out_data['total'] = 0;
                            $out_data['pay_type'] = 'scancode';
                            $starttime = time() - 30;
                            $endtime = time() + 30;
                            $park_where = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            $park_info_car = $db_out_park->getOne($park_where);
                            //写入车辆出场表
                            if (!$park_info_car || !isset($park_info_car['id'])) {
                                $insert_id= $db_out_park->insertOne($out_data);
                            } else {
                                $insert_id = $park_info_car['id'];
                            }
                            */
                            $access_order_id='';
                            $park_where = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                                ['del_time', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            if($park_info_car111 && isset($park_info_car111['record_id']) && $park_info_car111['record_id']){
                                $car_access_record['car_id']=$park_info_car111['car_id'];
                                $car_access_record['position_id']=$park_info_car111['position_id'];
                                $car_access_record['garage_id']=$park_info_car111['garage_id'];
                                $car_access_record['position_num']=$park_info_car111['position_num'];
                                $access_order_id = $park_info_car111['order_id'];
                            }
//                            fdump_api([$park_info_car111,$park_where],'open_gate_0519',1);
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $log_info['car_number'];
                            $car_access_record['accessType'] = 2;
                            $car_access_record['accessTime'] = $now_time;
                            if ($log_info['open_type']==2){
                                $car_access_record['accessMode'] = 4;
                            }elseif ($log_info['open_type']==3){
                                if (empty($park_info_car111)){
                                    $car_access_record['exception_type'] = 2;
                                }else{
                                    $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                                }
                                $car_access_record['accessMode'] = 7;
                            }
                            $car_access_record['park_sys_type'] = $park_sys_type;
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_record['order_id'] = $access_order_id;
                           
                            $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[5];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            // fdump_api([$car_access_record],'open_gate_0519',1);
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']){
                                //释放车位
                                $whereArr=array('position_id'=>$park_info_car111['position_id'],'use_car_id'=>$park_info_car111['car_id']);
                                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                                $this->updateParkingPositionUse($whereArr,$saveArr,$log_info['car_number'],'heartbeat_out_park');
                            }
                            if($park_info_car111 && isset($park_info_car111['record_id'])){
                                $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                            }
                            if($record_id>0){
                                $tmpUpdateRecordArr=array('car_number'=> $log_info['car_number'],'in_park_id'=>$insert_id,'village_id'=>$passage_info['village_id'],'record_id'=>$record_id,'access_order_id'=>$access_order_id,'access_type'=>2,'access_time'=>time());
                                (new ParkCarToJobService())->outToInParkToJob($tmpUpdateRecordArr);
                                /*
                                $access_record_where_near = [
                                    ['car_number', '=', $log_info['car_number']],
                                    ['park_id', '=', $passage_info['village_id']],
                                    ['is_out', '=', 0],
                                    ['del_time', '<', 1],
                                    ['record_id', '<', $record_id],
                                ];
                                //最近一条未出场的 记录
                                $access_record_tmp = $db_house_village_car_access_record->getOne($access_record_where_near,'record_id,is_out,accessType,car_number,accessTime');
                                if ($access_record_tmp && !$access_record_tmp->isEmpty()){
                                    $access_record_tmp=$access_record_tmp->toArray();
                                    $db_house_village_car_access_record->saveOne(array(['record_id', '=', $access_record_tmp['record_id']]),['is_out'=>1]);
                                }
                                */
                            }
                        }
                    }
                }
                //$db_park_open_log->delOne(['id'=>$log_info['id']]);
                $res['data']=true;
//                fdump_api(['serialno' => $serialno,'res'=>$res,'log_info'=>$log_info],'park_temp/heartbeat_'.$receiveTime.'_log',1);
            }
//            fdump_api([$res],'showscreen_info_'.date('H'),1);
            //查询支付结果
            $res['pay_info']=false;
            //$res['pay_info']=$this->getParkPayRecord($data['serialno']);
//            fdump_api([$res,$res['pay_info']],'showscreen_info_'.date('H'),1);
        }
        return $res;
    }

    public function addA11Park($data,$log_info,$now_time){
        $db_park_open_log=new ParkOpenLog();
        $db_in_park = new InPark();
        $db_out_park = new OutPark();
        $db_park_passage = new ParkPassage();
        $db_house_village = new HouseVillage();
        $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
        $db_house_visitor = new HouseVillageVisitor();
        $db_house_new_pay_order = new HouseNewPayOrder();
        $db_house_village_parking_temp=new HouseVillageParkingTemp();
        $serialno = isset($data['serialno']) && $data['serialno'] ? $data['serialno'] : '';
        if (!empty($log_info['car_number'])){
            $car_number = $log_info['car_number'];
            //查询设备
            $passage_info = $db_park_passage->getFind(['device_number' => $serialno, 'status' => 1]);
            // 记录车辆进入信息
            $car_access_record = [];
            $car_access_record['channel_id']=$passage_info['id'];
            $car_access_record['channel_number']=$passage_info['channel_number'];
            $car_access_record['channel_name']=$passage_info['passage_name'];
            if(isset($data['park_car_type']) && !empty($data['park_car_type'])) {
                $car_access_record['park_car_type'] = $data['park_car_type'];
            }else{
                $car_access_record['park_car_type'] = '临时车A';
            }
            $db_house_village_parking_garage=new HouseVillageParkingGarage();
            $garage_id=0;
            if (!empty($passage_info['passage_area'])&&$passage_info['area_type']==2){
                $garage_id=$passage_info['passage_area'];
            }
            if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
                $garage_id=$passage_info['garage_id'];
            }
            $garage_info=array();
            $is_real_garage=1;
            if($garage_id>0){
                $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$garage_id]);
                if($garage_info && !$garage_info->isEmpty()){
                    $garage_info=$garage_info->toArray();
                    if($garage_info['fid']>0 && $garage_info['fid']==$garage_info['garage_id']){
                        $garage_info['fid']=0;
                    }

                    if($garage_info['fid']>0 && $garage_info['fid']!=$garage_info['garage_id'] ){
                        $is_real_garage=0;
                    }
                }
            }
            
            if ($passage_info['passage_direction'] == 1) {
                $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
                $park_data = [];
                $park_data['car_number'] = $log_info['car_number'];
                $park_data['in_time'] = $now_time;
                $park_data['order_id'] = uniqid();
                $park_data['in_channel_id'] = $passage_info['id'];
                $park_data['is_paid'] = 0;
                $park_data['park_id'] = $passage_info['village_id'];
                $park_data['park_sys_type'] = 'A11';
                $park_data['park_name'] = $village_info['village_name'];
                $starttime = time() - 30;
                $endtime = time() + 30;
                $park_where = [
                    ['car_number', '=', $log_info['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['in_time', '>=', $starttime],
                    ['in_time', '<=', $endtime],
                    ['del_time', '=', 0],
                ];
                $park_info_car = $db_in_park->getOne1($park_where);
                $park_info_car_arr = $park_info_car && !is_array($park_info_car) ? $park_info_car->toArray() : $park_info_car;
                if ($serialno) {
                    fdump_api(['入场信息[入口通道]' => $serialno, 'park_info_car' => $park_info_car_arr],'park_temp/heartbeat_'.$serialno.'_log',1);
                }
                fdump_api(['入场信息[入口通道]'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_info_car'=>$park_info_car_arr],'park_temp/log_'.$car_number,1);
                if (empty($park_info_car)) {
                    $insert_id=$db_in_park->insertOne($park_data);
                }elseif($park_info_car && isset($park_info_car['id'])){
                    $insert_id=$park_info_car['id'];
                }
                $res = $db_house_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $log_info['car_number']], '*');
                $car_access_record['uid']=0;
                if (!empty($res)){
                    $car_access_record['user_name'] =isset($res['visitor_name']) && !empty($res['visitor_name']) ? $res['visitor_name']:'';
                    $car_access_record['user_phone'] =isset($res['visitor_phone']) && !empty($res['visitor_phone']) ? $res['visitor_phone']:'';
                    $car_access_record['uid'] =isset($res['uid']) && !empty($res['uid']) ? $res['uid']:0;
                }
                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $log_info['car_number'];
                $car_access_record['accessType'] = 1;
                $car_access_record['accessTime'] = $now_time;
                if ($log_info['open_type']==1){
                    $car_access_record['accessMode'] = 9;
                }elseif ($log_info['open_type']==3){
                    $car_access_record['accessMode'] = 7;
                }
                $car_access_record['park_sys_type'] = 'A11';
                $car_access_record['is_out'] = 0;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                $car_access_record['order_id'] = date('YmdHis').rand(100,999);
                $car_access_record['update_time'] = $now_time;
                if (isset($insert_id)&&!empty($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);
                //将此条之前的 都改掉
                if($record_id>0 && $is_real_garage>0) {
                    $wherexArr = [];
                    $wherexArr[]=['record_id','<',$record_id];
                    $wherexArr[] = ['car_number', '=', $car_number];
                    $wherexArr[] = ['is_out', '=', 0];
                    $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                    $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                }
                if ($serialno) {
                    fdump_api(['记录入场信息' => $serialno, 'car_access_record' => $car_access_record, 'record_id' => $record_id],'park_temp/heartbeat_'.$serialno.'_log',1);
                }
                fdump_api(['记录入场信息[入口通道]'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'car_access_record' => $car_access_record, 'record_id' => $record_id],'park_temp/log_'.$car_number,1);
            }
            if ($passage_info['passage_direction'] == 0) {
                $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
                $whereInRecord = ['car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0, 'del_time' => 0];
                $in_park_pay_info = $db_in_park->getOne1($whereInRecord);

                $in_park_pay_info_arr = $in_park_pay_info && !is_array($in_park_pay_info) ? $in_park_pay_info->toArray() : $in_park_pay_info;
                $log_info_arr = $log_info && !is_array($log_info) ? $log_info->toArray() : $log_info;
                if ($serialno) {
                    fdump_api(['入场信息[出口通道]' => $serialno, 'log_info' => $log_info_arr, 'in_park_pay_info' => $in_park_pay_info_arr],'park_temp/heartbeat_'.$serialno.'_log',1);
                }
                fdump_api(['入场信息[出口通道]' => $car_number, 'serialno' => $serialno, 'park_sys_type' => 'A11', 'log_info' => $log_info_arr, 'in_park_pay_info' => $in_park_pay_info_arr], 'park_temp/log_' . $car_number, 1);
                $car_access_record_where = [
                    ['car_number', '=', $car_number],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=', 1],
                    ['del_time', '=', 0],
                ];
                $car_user_phone = '';
                $car_user_name = '';
                $uid = 0;
                $tmp_car_access_record = $db_house_village_car_access_record->getOne($car_access_record_where);
                if ($tmp_car_access_record && !is_array($tmp_car_access_record) && !$tmp_car_access_record->isEmpty()) {
                    $tmp_car_access_record = $tmp_car_access_record->toArray();
                    $car_user_phone = $tmp_car_access_record['user_phone'];
                    $car_user_name = $tmp_car_access_record['user_name'];
                    $uid = $tmp_car_access_record['uid'] ? $tmp_car_access_record['uid'] : 0;
                    $car_access_record['park_car_type'] = $tmp_car_access_record['park_car_type'];
                }
                if(empty($car_user_phone)){
                    $res_visitor = $db_house_visitor->get_one(['village_id' => $passage_info['village_id'], 'car_id' => $car_number], '*');
                    if (!empty($res_visitor)){
                        $car_user_phone =isset($res_visitor['visitor_phone']) && !empty($res_visitor['visitor_phone']) ? $res_visitor['visitor_phone']:'';
                        if($uid<1 && isset($res_visitor['uid']) && !empty($res_visitor['uid'])){
                            $uid=$res_visitor['uid'];
                        }
                        if(empty($car_user_name) && isset($res_visitor['visitor_name']) && !empty($res_visitor['visitor_name'])){
                            $car_user_name=$res_visitor['visitor_name'];
                        }
                    }
                }
                $car_access_record['user_phone'] = $car_user_phone;
                $car_access_record['user_name'] = $car_user_name;
                $car_access_record['uid'] = $uid;
                if (!empty($in_park_pay_info)) {
                    if (!empty($in_park_pay_info['pay_order_id'])) {

                        $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $log_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1]);
                        $pay_info = $db_house_new_pay_order->get_one(['summary_id' => $in_park_pay_info['pay_order_id'], 'is_paid' => 1]);

                        $park_temp_info_arr = $park_temp_info && !is_array($park_temp_info) ? $park_temp_info->toArray() : $park_temp_info;
                        $pay_info_arr = $pay_info && !is_array($pay_info) ? $pay_info->toArray() : $pay_info;
                        if ($serialno) {
                            fdump_api(['临时信息和支付信息[出口通道]' => $serialno, 'park_temp_info' => $park_temp_info_arr, 'pay_info' => $pay_info_arr],'park_temp/heartbeat_'.$serialno.'_log',1);
                        }
                        fdump_api(['临时信息和支付信息[出口通道]'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_temp_info' => $park_temp_info_arr, 'pay_info' => $pay_info_arr],'park_temp/log_'.$car_number,1);


                        if (!empty($pay_info)) {
                            //写入车辆入场表
                            $park_data = [];
                            $park_data['out_time'] = $now_time;
                            $park_data['is_out'] = 1;
                            $park_data['is_paid'] = 1;
                            $res_in=$db_in_park->saveOne(['car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                            fdump_api([$res_in,$res_in,$log_info['car_number'],$passage_info['village_id']],'open_gate_0519',1);
                            $insert_id=0;
                            /*
                            $out_data = [];
                            $out_data['car_number'] = $log_info['car_number'];
                            if (!empty($in_park_pay_info)) {
                                $out_data['in_time'] = $in_park_pay_info['in_time'];
                                $out_data['order_id'] = $in_park_pay_info['order_id'];
                            }
                            $out_data['out_time'] = $now_time;
                            $out_data['park_id'] = $passage_info['village_id'];
                            $out_data['total'] = $park_temp_info['price'];
                            $out_data['pay_type'] = 'scancode';
                            $starttime = time() - 30;
                            $endtime = time() + 30;
                            $park_where = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            $park_info_car = $db_out_park->getOne($park_where);
                            //写入车辆入场表
                            if (empty($park_info_car)) {
                                $insert_id= $db_out_park->insertOne($out_data);
                            }
                            */
                            $park_where = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                                ['del_time', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            if($park_info_car111 && isset($park_info_car111['car_id']) && $park_info_car111['car_id']){
                                $car_access_record['car_id']=$park_info_car111['car_id'];
                                $car_access_record['position_id']=$park_info_car111['position_id'];
                                $car_access_record['garage_id']=$park_info_car111['garage_id'];
                                $car_access_record['position_num']=$park_info_car111['position_num'];
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $log_info['car_number'];
                            $car_access_record['accessType'] = 2;
                            $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                            $car_access_record['park_time'] =$pay_info['pay_time']-$in_park_pay_info['in_time'] ;
                            $car_access_record['total'] =$park_temp_info['price'];
                            $car_access_record['accessTime'] = $now_time;
                            if ($log_info['open_type']==2){
                                $car_access_record['accessMode'] = 4;
                            }elseif ($log_info['open_type']==3){
                                if (empty($park_info_car111)){
                                    $car_access_record['exception_type'] = 2;
                                }else{
                                    $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                                }
                                $car_access_record['accessMode'] = 7;
                            }
                            $car_access_record['park_sys_type'] = 'A11';
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_record['order_id'] = $park_info_car111['order_id'];
                            //  $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[6];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }

                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']){
                                //释放车位
                                $whereArr=array('position_id'=>$park_info_car111['position_id'],'use_car_id'=>$park_info_car111['car_id']);
                                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                                $this->updateParkingPositionUse($whereArr,$saveArr,$log_info['car_number'],'heartbeat_out_park');
                            }
                            //将此条之前的 都改掉
                            if($record_id>0 && $is_real_garage>0) {
                                $wherexArr = [];
                                $wherexArr[]=['record_id','<',$record_id];
                                $wherexArr[] = ['car_number', '=', $car_number];
                                $wherexArr[] = ['is_out', '=', 0];
                                $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                                $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                            }
                        }
                    }else{
                        $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $log_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1]);

                        $park_temp_info_arr = $park_temp_info && !is_array($park_temp_info) ? $park_temp_info->toArray() : $park_temp_info;
                        if ($serialno) {
                            fdump_api(['临时信息[出口通道]' => $serialno, 'park_temp_info' => $park_temp_info_arr],'park_temp/heartbeat_'.$serialno.'_log',1);
                        }
                        fdump_api(['临时信息[出口通道]'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11', 'park_temp_info' => $park_temp_info_arr],'park_temp/log_'.$car_number,1);

                        if (!empty($park_temp_info)) {
                            //写入车辆入场表
                            $park_data = [];
                            $park_data['out_time'] = $now_time;
                            $park_data['is_out'] = 1;
                            $park_data['is_paid'] = 1;
                            $res_in=$db_in_park->saveOne(['car_number' => $log_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'A11', 'is_out' => 0], $park_data);
                            $insert_id=0;
                            /*
                            $out_data = [];
                            $out_data['car_number'] = $log_info['car_number'];
                            if (!empty($in_park_pay_info)) {
                                $out_data['in_time'] = $in_park_pay_info['in_time'];
                                $out_data['order_id'] = $in_park_pay_info['order_id'];
                            }
                            $out_data['out_time'] = $now_time;
                            $out_data['park_id'] = $passage_info['village_id'];
                            $out_data['pay_type'] = 'scancode';
                            $out_data['total'] = $park_temp_info['price'];
                            $out_data['pay_type'] = 'scancode';
                            $starttime = time() - 30;
                            $endtime = time() + 30;
                            $park_where = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['out_time', '>=', $starttime],
                                ['out_time', '<=', $endtime],
                            ];
                            fdump_api([$park_where,$out_data],'open_gate_0519',1);
                            $park_info_car = $db_out_park->getOne($park_where);
                            //写入车辆入场表
                            if (empty($park_info_car)) {
                                $insert_id= $db_out_park->insertOne($out_data);
                            }
                            */
                            $park_where = [
                                ['car_number', '=', $log_info['car_number']],
                                ['park_id', '=', $passage_info['village_id']],
                                ['accessType', '=',1],
                                ['is_out', '=', 0],
                                ['del_time', '=', 0],
                            ];
                            $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                            if($park_info_car111 && isset($park_info_car111['car_id']) && $park_info_car111['car_id']){
                                $car_access_record['car_id']=$park_info_car111['car_id'];
                                $car_access_record['position_id']=$park_info_car111['position_id'];
                                $car_access_record['garage_id']=$park_info_car111['garage_id'];
                                $car_access_record['position_num']=$park_info_car111['position_num'];
                            }
                            $car_access_record['business_type'] = 0;
                            $car_access_record['business_id'] = $passage_info['village_id'];
                            $car_access_record['car_number'] = $log_info['car_number'];
                            $car_access_record['accessType'] = 2;
                            $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                            $car_access_record['park_time'] =$park_temp_info['pay_time']-$in_park_pay_info['in_time'] ;
                            $car_access_record['total'] =$park_temp_info['price'];
                            $car_access_record['accessTime'] = $now_time;
                            if ($log_info['open_type']==2){
                                $car_access_record['accessMode'] = 4;
                            }elseif ($log_info['open_type']==3){
                                if (empty($park_info_car111)){
                                    $car_access_record['exception_type'] = 2;
                                }else{
                                    $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                                }
                                $car_access_record['accessMode'] = 7;
                            }
                            $car_access_record['park_sys_type'] = 'A11';
                            $car_access_record['is_out'] = 1;
                            $car_access_record['park_id'] = $passage_info['village_id'];
                            $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                            $car_access_record['order_id'] = $park_info_car111['order_id'];
                            //  $car_access_record['total'] = 0;
                            // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                            // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                            //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                            $car_access_record['pay_type'] = $this->pay_type[6];
                            $car_access_record['update_time'] = $now_time;
                            $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                            if (isset($insert_id)) {
                                $car_access_record['from_id'] = $insert_id;
                            }
                            $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                            if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']){
                                //释放车位
                                $whereArr=array('position_id'=>$park_info_car111['position_id'],'use_car_id'=>$park_info_car111['car_id']);
                                $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                                $this->updateParkingPositionUse($whereArr,$saveArr,$log_info['car_number'],'heartbeat_out_park');
                            }
                            //将此条之前的 都改掉
                            if($record_id>0 && $is_real_garage>0) {
                                $wherexArr = [];
                                $wherexArr[]=['record_id','<',$record_id];
                                $wherexArr[] = ['car_number', '=', $car_number];
                                $wherexArr[] = ['is_out', '=', 0];
                                $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                                $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);

                            }
                            fdump_api([$record_id,$car_access_record],'open_gate_0519',1);
                        }
                    }
                }else{
                    if ($serialno) {
                        fdump_api(['无入场记录[出口通道]' => $serialno],'park_temp/heartbeat_'.$serialno.'_log',1);
                    }
                    fdump_api(['无入场记录[出口通道]'=>$car_number,'serialno'=>$serialno,'park_sys_type'=>'A11'],'park_temp/log_'.$car_number,1);
                    /*
                    $out_data = [];
                    $out_data['car_number'] = $log_info['car_number'];
                    $out_data['out_time'] = $now_time;
                    $out_data['park_id'] = $passage_info['village_id'];
                    $out_data['total'] = 0;
                    $out_data['pay_type'] = 'scancode';
                    //  $out_data['is_paid'] = 1;
                    $out_data['total'] = 0;
                    $out_data['pay_type'] = 'scancode';
                    $starttime = time() - 30;
                    $endtime = time() + 30;
                    $park_where = [
                        ['car_number', '=', $log_info['car_number']],
                        ['park_id', '=', $passage_info['village_id']],
                        ['out_time', '>=', $starttime],
                        ['out_time', '<=', $endtime],
                    ];
                    $park_info_car = $db_out_park->getOne($park_where);
                    fdump_api([$park_info_car,$park_where],'open_gate_0519',1);
                    //写入车辆入场表
                    if (empty($park_info_car)) {
                        $db_out_park->insertOne($out_data);
                    }
                    */
                    $park_where = [
                        ['car_number', '=', $log_info['car_number']],
                        ['park_id', '=', $passage_info['village_id']],
                        ['accessType', '=',1],
                        ['is_out', '=', 0],
                        ['del_time', '=', 0],
                    ];
                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                    fdump_api([$park_info_car111,$park_where],'open_gate_0519',1);
                    if($park_info_car111 && isset($park_info_car111['car_id']) && $park_info_car111['car_id']){
                        $car_access_record['car_id']=$park_info_car111['car_id'];
                        $car_access_record['position_id']=$park_info_car111['position_id'];
                        $car_access_record['garage_id']=$park_info_car111['garage_id'];
                        $car_access_record['position_num']=$park_info_car111['position_num'];
                    }
                    $car_access_record['business_type'] = 0;
                    $car_access_record['business_id'] = $passage_info['village_id'];
                    $car_access_record['car_number'] = $log_info['car_number'];
                    $car_access_record['accessType'] = 2;
                    $car_access_record['accessTime'] = $now_time;
                    if ($log_info['open_type']==2){
                        $car_access_record['accessMode'] = 4;
                    }elseif ($log_info['open_type']==3){
                        if (empty($park_info_car111)){
                            $car_access_record['exception_type'] = 2;
                        }else{
                            $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                        }
                        $car_access_record['accessMode'] = 7;
                    }
                    $car_access_record['park_sys_type'] = 'A11';
                    $car_access_record['is_out'] = 1;
                    $car_access_record['park_id'] = $passage_info['village_id'];
                    $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                    if (isset($park_info_car111['order_id'])&&!empty($park_info_car111['order_id'])){
                        $car_access_record['order_id'] = $park_info_car111['order_id'];
                    }
                    $car_access_record['total'] = 0;
                    // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                    // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                    //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                    $car_access_record['pay_type'] = $this->pay_type[5];
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    // fdump_api([$car_access_record],'open_gate_0519',1);
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                    if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']){
                        //释放车位
                        $whereArr=array('position_id'=>$park_info_car111['position_id'],'use_car_id'=>$park_info_car111['car_id']);
                        $saveArr=array('use_car_id'=>0,'use_start_time'=>0,'use_car_number'=>'','access_record_id'=>$record_id);
                        $this->updateParkingPositionUse($whereArr,$saveArr,$log_info['car_number'],'heartbeat_out_park');
                    }
                    //将此条之前的 都改掉
                    if($record_id>0 && $is_real_garage>0) {
                        $wherexArr = [];
                        $wherexArr[]=['record_id','<',$record_id];
                        $wherexArr[] = ['car_number', '=', $car_number];
                        $wherexArr[] = ['is_out', '=', 0];
                        $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                        $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                    }

                }
            }
        }

        $res=true;
        return $res;
    }

    /**
     * 收银台车场车位号/车牌号模糊搜索
     * User: zhanghan
     * Date: 2022/1/5
     * Time: 14:19
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVillageParkingSearchList($param){
        $where = [];
        if($param['option_type'] == 1){ // 车位号
            $where[] = ['pp.village_id','=',$param['village_id']];
            $where[] = ['pp.position_num','like','%'.$param['search_keyword'].'%'];
            $data = $this->parkingPositionModel->getLists($where,'distinct position_num as text',0);
        }else if ($param['option_type'] == 2){ // 车牌号
            $where[] = ['village_id','=',$param['village_id']];
            $where[] = ['car_number','like','%'.$param['search_keyword'].'%'];
            $data = $this->parkingCarModel->getHouseVillageParkingCarLists($where,'distinct car_number as text',0);
        }
        return $data;
    }

    //todo  获取小区停车配置项
    public function getVillageParkConfig($village_id,$field=true,$garage_id=0){
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $info =$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        return $info;
    }

    //todo 针对异步交互停车设备处理
    public function getVillageParkType($info,$car_number){
        if(empty($car_number)){
            throw new Exception('缺少车牌号');
        }
        $result=(new D6Service())->addQueryCarCost($info['village_id'],$car_number);
        if(!$result['error']){
            throw new Exception($result['msg']);
        }
        $data['is_async']=true;
        $data['temp_id']=$result['data']['temp_id'];
        $data['duration']=5;
        $data['interval']=1;
        return $data;
    }

    //todo 异步 定时查询
    public function timingQuery($param){
        $HouseVillageParkingTemp=new HouseVillageParkingTemp();
        $HouseVillage=new HouseVillage();
        $village_info=$HouseVillage->getOne($param['village_id'],'village_name');
        if (!$village_info || $village_info->isEmpty()) {
            throw new Exception('该数据异常，请返回/刷新页面');
        }
        $where[]=['is_out_park', '=', 0];
        $where[]=['status', '=', 0];
        $car_number='';
        if ($param['car_number']) {
            $where[]=['car_number', '=', $param['car_number']];
            $car_number=$param['car_number'];
        }
        $parkGarageConfig=$this->get_park_car_garage($param['village_id'],$car_number);
        $village_park_config=$parkGarageConfig['park_garage_config'];
        if(!$village_park_config){
            throw new Exception('该小区暂未配置');
        }
        if(intval($village_park_config['park_versions']) != 2){
            throw new Exception('请在小区后台开启智慧停车');
        }
        if(strpos($param['query_order_no'],'query_price_') !== false){
            $query_order_no = str_replace('query_price_','',$param['query_order_no']);
            $where[]=['id', '=', $query_order_no];
        }
        if($village_park_config['park_sys_type'] == 'D6'){
            $where[]=['id', '=', $param['query_order_no']];
        }
        $parking_temp=$HouseVillageParkingTemp->getFind($where,'duration,price,in_time,state,errmsg,order_id,car_number');
        if ($parking_temp && !$parking_temp->isEmpty()) {
            $parking_temp = $parking_temp->toArray();
            if(empty($car_number) && !empty($parking_temp['car_number'])){
                $car_number=$parking_temp['car_number'];
                $param['car_number']=$car_number;
            }
        }else{
            $parking_temp=array();
        }
        $status=false;
        $data=[];
        $pay_money=0;
        if (!empty($parking_temp)) {
            $hour = $day = 0;
            $duration = $parking_temp['duration'] ? $parking_temp['duration'] : 0;
            if ($duration>=1440) {
                $day = intval($duration/1440);
                $duration = $duration - $day*1440;
            }
            if ($duration>60) {
                $hour = intval($duration/60);
                $duration = $duration - $hour*60;
            }
            $minute = $duration;
            $duration_msg = '';
            if ($day) {
                $duration_msg .=$day.'天 ';
            }
            if ($hour) {
                $duration_msg .=$hour.'小时';
            }
            if ($minute) {
                $duration_msg .=$minute.'分钟';
            }
            $price=floatval($parking_temp['price'])>0 ? $parking_temp['price']:0;
            $in_time=$parking_temp['in_time'] ? date('Y-m-d H:i:s',$parking_temp['in_time']) : '';
            if($village_park_config['park_sys_type'] == 'D6'){
                if($parking_temp['state'] == 1 && !empty($parking_temp['errmsg'])){
                    throw new Exception($parking_temp['errmsg']);
                }
                $data=[
                    'car_number'=>$param['car_number'],
                    'total_money'=>$price,
                    'parking_duration'=>$duration_msg,
                    'in_time'=>$in_time,
                    'order_no'=>$parking_temp['order_id'],
                    'park_name'=>$village_info['village_name'],
                    'pay_time'=>date('Y-m-d H:i:s',time()),
                    'pay_money'=>$price
                ];
                if(!empty($duration_msg)){
                    $status=true;
                    $pay_money=$price;
                }
            }
        }
        $listtmp=$this->assembleOrderData($data);
        $list=array();
        $list['top']=$listtmp['top'];
        $list['middle']=$listtmp['middle'];
        $list['bottom']=$listtmp['bottom'];
        $pay_list1=$listtmp['pay_list1'];
        $pay_list2=$listtmp['pay_list2'];
        $pay_list3=$listtmp['pay_list3'];
        $pay_list4=$listtmp['pay_list4'];
        return ['status'=>$status,'list'=>$list,'pay_list1'=>$pay_list1,'pay_list2'=>$pay_list2,'pay_list3'=>$pay_list3,'pay_list4'=>$pay_list4,'pay_money'=>$pay_money];
    }

    //todo 组装停车订单数据
    public function assembleOrderData($param=[]){
        $top=$middle=$bottom=[];
        $pay_list1 = [];
        $pay_list2 = [];
        $pay_list3 = [];
        $pay_list4 = [];
        if(isset($param['car_number'])){
            $top[]=['title'=>'车牌号','val'=>$param['car_number'],'color'=>''];
            $pay_list1[]=['title'=>'车牌号','val'=>$param['car_number'],'color'=>''];
        }
        if(isset($param['total_money'])){
            $top[]=['title'=>'应付金额','val'=>'￥'.$param['total_money'],'color'=>''];
            $pay_list1[]=['title'=>'应付金额','val'=>$param['total_money'],'color'=>''];
        }
        if(isset($param['parking_duration'])){
            $top[]=['title'=>'停车时间','val'=>$param['parking_duration'],'color'=>''];
            $pay_list1[]=['title'=>'停车时间','val'=>$param['parking_duration'],'color'=>''];
        }

        if(isset($param['in_time'])){
            $middle[]=['title'=>'入场时间','val'=>$param['in_time'],'color'=>''];
            $pay_list2[]=['title'=>'入场时间','val'=>$param['in_time'],'color'=>''];
        }

        if(isset($param['order_no'])){
            $bottom[]=['title'=>'订单编号','val'=>$param['order_no'],'color'=>''];
            $pay_list3[]=['title'=>'订单编号','val'=>$param['order_no'],'color'=>''];
        }
        if(isset($param['park_name'])){
            $bottom[]=['title'=>'车场名称','val'=>$param['park_name'],'color'=>''];
            $pay_list3[]=['title'=>'车场名称','val'=>$param['park_name'],'color'=>''];
        }
        if(isset($param['pay_time'])){
            $bottom[]=['title'=>'缴费时间','val'=>$param['pay_time'],'color'=>''];
            $pay_list3[]=['title'=>'缴费时间','val'=>$param['pay_time'],'color'=>''];
        }
        if(isset($param['total_money'])){
            $bottom[]=['title'=>'应付金额','val'=>'￥'.$param['total_money'],'color'=>''];
            $pay_list4[]=['title'=>'应付金额','val'=>$param['total_money'],'color'=>''];
        }
        if(isset($param['coupon_money'])){
            $bottom[]=['title'=>'停车抵扣券','val'=>'￥'.$param['coupon_money'],'color'=>''];
            $pay_list4[]=['title'=>'停车抵扣券','val'=>$param['coupon_money'],'color'=>''];
        }else{
            $pay_list4[]=['title'=>'停车抵扣券','val'=>'0','color'=>''];
        }
        if(isset($param['pay_money'])){
            $bottom[]=['title'=>'实付金额','val'=>'￥'.$param['pay_money'],'color'=>'red'];
            $pay_list4[]=['title'=>'实付金额','val'=>$param['pay_money'],'color'=>'red'];
        }
        $data=[
            'top'=>$top,
            'middle'=>$middle,
            'bottom'=>$bottom
        ];
        $data['pay_list1']=$pay_list1;
        $data['pay_list2']=$pay_list2;
        $data['pay_list3']=$pay_list3;
        $data['pay_list4']=$pay_list4;
        return $data;
    }


    /**
     * D5智慧停车 隐藏临时车
     * @author: liukezhu
     * @date : 2022/1/15
     * @param $village_id
     * @return int
     */
    public function getD5ParkConfig($info){
        $is_temporary_park=1;
        if($info && $info['park_versions'] == '2' && in_array($info['park_sys_type'],['D5'])){
            $is_temporary_park=0;
        }
        return $is_temporary_park;
    }

    /**
     *返回移动端停车模块底部导航
     * @author: liukezhu
     * @date : 2022/1/15
     * @return array
     */
    public function getParkNav($village_id = 0)
    {
        if (strpos($_SERVER['SERVER_NAME'], 'hz.huizhisq.com') !== false) {
            $park_pay = '临时停车缴费';
            $park_manage = '月租车管理';
        } else {
            $park_pay = '停车缴费';
            $park_manage = '车辆管理';
        }
        $is_new_ver_d5 = false;
        $customized_third_park_key='';
        if ($village_id > 0) {
            $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
            $whereArr=array();
            $whereArr[]=array('village_id','=',$village_id);
            $whereArr[]=array('park_versions','=',2);
            $parkGarageConfigs=$houseVillageParkGarageConfigService->getListData($whereArr,'village_id,garage_id,park_sys_type,park_versions');
            if($parkGarageConfigs){
                $is_new_ver_d5 = true;
                foreach ($parkGarageConfigs as $gvv){
                    if($gvv['park_sys_type'] != 'D5'){
                        $is_new_ver_d5 = false;
                    }
                }
            }
            $configCustomizationService=new ConfigCustomizationService();
            $isAihorseCustomizedJudge=$configCustomizationService->getAihorseCustomizedJudge();
            $houseVillageParkingGarage=new HouseVillageParkingGarage();
            if($isAihorseCustomizedJudge){
                $app_id=cfg('park_aicar_app_id');
                $app_id=$app_id ? trim($app_id):'';
                $app_secret=cfg('park_aicar_app_secret');
                $app_secret=$app_secret ? trim($app_secret):'';
                if($app_id && $app_secret){
                    $whereArr=array(['village_id','=',$village_id],['status','=',1],['third_park_key','<>','']);
                    $parkingGarageObj=$houseVillageParkingGarage->getOne($whereArr,'*');
                    if($parkingGarageObj && !$parkingGarageObj->isEmpty()){
                        $customized_third_park_key=$parkingGarageObj['third_park_key'];
                    }
                }
            }
        }
        $data = [];
        if (!$is_new_ver_d5) {
            $data[] =[
                'name' => '停车场',
                'icon' => cfg('site_url') . '/static/images/house/park/tab1_nomal.png',
                'icon_active' => cfg('site_url') . '/static/images/house/park/tab1_active.png',
                'show' => true,
                'keytype'=>'car_park_market'
            ];
            if(!$customized_third_park_key){
                $data[] = [
                    'name' => $park_pay,
                    'icon' => cfg('site_url') . '/static/images/house/park/tab2_nomal.png',
                    'icon_active' => cfg('site_url') . '/static/images/house/park/tab2_active.png',
                    'show' => true,
                    'keytype'=>'car_pay_fee'
                ];
            }
            $data[] = [
                'name' => '扫一扫',
                'icon' => cfg('site_url') . '/static/images/house/park/scan_code.png',
                'icon_active' => cfg('site_url') . '/static/images/house/park/scan_code.png',
                'show' => true,
                'keytype'=>'car_scan'
            ];
        }

        $data[] = [
            'name' => $park_manage,
            'icon' => cfg('site_url') . '/static/images/house/park/tab3_nomal.png',
            'icon_active' => cfg('site_url') . '/static/images/house/park/tab3_active.png',
            'show' => true,
            'keytype'=>'car_manage'
        ];
        $data[] = [
            'name' => '缴费记录',
            'icon' => cfg('site_url') . '/static/images/house/park/tab4_nomal.png',
            'icon_active' => cfg('site_url') . '/static/images/house/park/tab4_active.png',
            'show' => true,
            'keytype'=>'car_fee_record'
        ];
        return $data;

    }

    public function  getVisitorTmpParkingLists($whereArr,$field='*',$page=1,$limit=20)
    {

        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $db_house_visitor = new HouseVillageVisitor();
        $count = $db_house_visitor->getCount($whereArr);
        if ($count > 0) {
            $dataArr['count'] = $count;
            $res = $db_house_visitor->getLists($whereArr, $field, $page, $limit);
            if (!empty($res)) {
                foreach ($res as $kk => $vv) {
                    $res[$kk]['add_time_str'] = '';
                    if ($vv['add_time'] > 0) {
                        $res[$kk]['add_time_str'] = date('Y-m-d H:i:s', $vv['add_time']);
                    }
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }

    /**
     * 根据收费标准获取所有已绑定的房间对应的业主信息
     * User: zhanghan
     * Date: 2022/1/12
     * Time: 14:21
     * @param $id
     * @param string $field
     * @return array
     */
    public function getVacancyToPosition($id,$field = 'position_id'){
        $standard_bind = new HouseNewChargeStandardBind();
        $user_bind = new HouseVillageUserBind();
        $position_bind = new HouseVillageBindPosition();

        $where_vacancy = [];
        $where_vacancy[] = ['rule_id','=',$id];
        $where_vacancy[] = ['bind_type','=',1];
        $where_vacancy[] = ['is_del','=',1];
        // 获取该收费规则下的所有绑定的房间id
        $vacancy_id = [];
        $vacancyList = $standard_bind->getLists1($where_vacancy,'vacancy_id');
        if($vacancyList && !$vacancyList->isEmpty()){
            $vacancy_id = array_column($vacancyList->toArray(),'vacancy_id');
        }
        // 根据房间id查询绑定房间的业主对应的小区用户ID
        $userBindList = [];
        if(!empty($vacancy_id)){
            $where_bind = [];
            $where_bind[] = ['vacancy_id','in',$vacancy_id];
            // 获取车位对应的用户信息房间信息
            $userList = $user_bind->getLimitUserList($where_bind,0,'pigcms_id');
            if($userList && !$userList->isEmpty()){
                $userBindList = array_column($userList->toArray(),'pigcms_id');
            }
        }
        $positionList = [];
        // 根据小区用户ID查询绑定车辆ID
        if(!empty($userBindList)){
            $where_position = [];
            $where_position[] = ['user_id','in',$userBindList];
            // 获取小区用户对应的车辆信息
            $position_list = $position_bind->getList($where_position,$field);
            if($position_list && !$position_list->isEmpty()){
                $positionList = $position_list->toArray();
            }
        }
        return $positionList;
    }

    //获取已绑定的车位
    public function  getRuleBindToPosition($rule_id,$field = 'position_id'){
        $standard_bind = new HouseNewChargeStandardBind();
        $whereArr = [];
        $whereArr[] = ['rule_id','=',$rule_id];
        $whereArr[] = ['bind_type','=',2];
        $whereArr[] = ['is_del','=',1];
        // 获取该收费规则下的所有绑定的车位id
        $tmpList = $standard_bind->getLists1($whereArr,$field);
        $tmpdatas=array();
        if($tmpList && !$tmpList->isEmpty()){
            $tmpdatas=$tmpList->toArray();
        }
        return $tmpdatas;
    }

    public function add_nolicence($village_id,$uid=''){
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $whereArr=array();
        $whereArr[]=array('village_id','=',$village_id);
        $whereArr[]=array('park_sys_type','=','D7');
        $info=$houseVillageParkGarageConfigService->getVillageParkConfigByWhere($whereArr);
        if (!empty($info)&& empty($info['d7_park_id'])){
            $car_number='';
            $db_user=new User();
            $userInfo=$db_user->getOne(['uid'=>$uid],'uid,phone,nickname');
            if (empty($userBindInfo['phone'])&&!empty($userInfo['phone'])){
                $phone=$userInfo['phone'];
            }elseif(!empty($userBindInfo['phone'])){
                $phone=$userInfo['phone'];
            }else{
                $phone='';
            }
            $service_d7=new QinLinCloudService();
            $nolicence_info=$service_d7->noLicenseQuery($info['d7_park_id'],$phone);
            if (!empty($nolicence_info)&&$nolicence_info['state']=='200'&&!empty($nolicence_info['data'])){
                $nolicence_info['data']=json_decode($nolicence_info['data'],true);
                fdump_api([$nolicence_info['data'],],'D7/addCar_0819',1);
                if (!empty($nolicence_info['data']['carnumber'])){
                    $car_number= $nolicence_info['data']['carnumber'];
                }
            }
        }else{
            // 随机八位数 数字和大写字母组合
            $car_number = '临'.createRandomStr(7,false,false,true);
            $where_replace = [
                'car_number' => $car_number,
                'is_out_park' => 0,
                'village_id' => $village_id,
            ];
            $db_nolicence_in_park=new NolicenceInPark();
            $replace_car =$db_nolicence_in_park->getOne($where_replace);
            // $replace_car = M('nolicence_in_park')->where($where_replace)->find();
            while($replace_car)
            {
                // 去除和在停车存在重复问题
                $car_number = '临'.createRandomStr(7,false,false,true);
                $where_replace['car_number'] = $car_number;
                $replace_car =$db_nolicence_in_park->getOne($where_replace);
            }
        }

        return  $car_number;
    }

    public function getParkSet($village_id,$garage_id=0){
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $info=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        $data=[];
        $data['is_park_month_type']=1;//是否开启月租车管理功能 1开启 0否
        $data['is_park_month_type_tpis']='';
        $data['is_temporary_park_type']=1;//是否开启储值车收费  1是 0否
        $data['is_temporary_park_type_tpis']='';
        if(!empty($info) && $info['park_versions'] == '2'){
            $data['is_park_month_type']=$info['is_park_month_type'];
            $data['is_temporary_park_type']=$info['is_temporary_park_type'];
            if(!empty($info) && $info['park_sys_type'] == 'D5' && $info['park_versions'] == '2'){
                $data['is_temporary_park_type']=0;
            }
        }
        if($data['is_park_month_type']==1){
            $data['is_park_month_type_tpis']='月租车缴费，是指为车辆支付月租停车费，将临时车转为月租车。';
        }
        if($data['is_temporary_park_type']==1){
            $data['is_temporary_park_type_tpis']='储值车充值，是指在账户预先充值，储值车在出场缴费时按此账号自动扣费。 ';
        }
        return $data;
    }


    //针对D3和A11 仅支持部分功能
    public function checkVillageParkConfig($village_id,$garage_id=0){
        
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$village_id);
        if(!$village_park_config){
            throw new Exception('请先开启智慧停车');
        }
        if(!in_array($village_park_config['park_sys_type'],['D3','A11','HXT85'])){
            throw new Exception('仅D3/A11智慧停车支持此类型配置');
        }
        return $village_park_config['park_sys_type'];
    }

    /**
     * 添加显屏指令
     * @author:zhubaodi
     * @date_time: 2022/4/6 17:45
     */
    public function addParkShowScreenLog($data){
        $db_house_village_park_showScreen_config=new HouseVillageParkShowscreenConfig();
        $db_park_showscreen_log=new ParkShowscreenLog();
        /*----------添加显屏指令start-----------*/
        //临时车入场
        $temp_in_content='欢迎光临';
        //临时车出场
        $temp_out_content1='停车';
        $temp_out_content2='请缴费';
        $temp_out_content3='一路顺风';
        //月租车入场
        $mouth_in_content='欢迎光临';
        //月租车出场
        $mouth_out_content1='停车有效期';
        $config_info = [];
        //  $config_info=$db_house_village_park_showScreen_config->getFind(['village_id'=>$data['village_id'],'screen_type'=>1,'passage_id'=>$data['passage']['id']]);
        // fdump_api([$config_info,$data],'showscreen_log_1',1);

        //车辆类型
        $carType= isset($data['car_type']) ? $data['car_type'] : '';
        //通道方向
        $passageDirection= isset($data['passage']['passage_direction']) ? $data['passage']['passage_direction'] : 0;
        //设备类型
        $deviceType= isset($data['passage']['device_type']) ? $data['passage']['device_type'] : 0;
        //设备类型 下发显屏指令类型（27 临显 25广告）
        $orderType=27;
        //通道类型
        if ((isset($data['passage']['passage_type']) && $data['passage']['passage_type'] == 2) && $passageDirection == 1){
            $passageType = 2;
        }else{
            $passageType = 1;
        }
        $showContentArr=[];
        //todo 进出场 显屏
        switch ($carType) {
            case 'temporary_type':
                if($passageDirection == 1){
                    //todo 车辆入场
                    if($deviceType == 1){
                        //todo 横屏
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $temp_in_content : $data['content']
                        ];
                    }
                    elseif ($deviceType == 2){
                        //todo 竖屏
                        if (empty($data['content'])){
                            $content=$temp_in_content;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $content=mb_substr($data['content'],0,6);
                            }else{
                                $content=$data['content'];
                            }
                        }
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '01',
                            'content' => $data['car_number']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 3,
                            'serial' => '02',
                            'content' => $content
                        ];
                    }
                }
                elseif ($passageDirection == 0){
                    //todo 车辆出场
                    if($deviceType == 1){
                        //todo 横屏
                        if (!isset($data['price'])||empty($data['price'])){
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => 0
                            ];
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '02',
                                'content' => empty($data['content']) ? $temp_out_content3 : $data['content'],
                                'duration' => $data['duration'],
                                'price' => 0,
                            ];
                        }
                        else{
                            if (empty($data['content'])){
                                if (isset($data['voice_content'])&&$data['voice_content']==9){
                                    $content = '扣款'.$data['price'].'元,请通行';
                                }else{
                                    $content = $temp_out_content2.$data['price'].'元';
                                }

                            }else{
                                $content = $data['content'];
                            }
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];

                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '02',
                                'content' => empty($data['content']) ? $temp_out_content1.$data['duration_txt'] : $data['content'],
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];

                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 1,
                                'serial' => '03',
                                'content' => $content,
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                        }
                    }
                    elseif ($deviceType == 2){
                        //todo 竖屏
                        if (empty($data['price'])){
                            if (empty($data['content'])){
                                $content=$temp_out_content3;
                            }
                            else{
                                if (mb_strlen($data['content'])>6){
                                    $content=mb_substr($data['content'],0,6);
                                }else{
                                    $content=$data['content'];
                                }
                            }
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => 0
                            ];
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 3,
                                'serial' => '02',
                                'content' => $content,
                                'duration' => $data['duration'],
                                'price' => 0
                            ];
                        }
                        else{
                            if (empty($data['content'])){
                                $content=$temp_out_content1.$data['duration_txt'];
                            }
                            else{
                                if (mb_strlen($data['content'])>6){
                                    $content=mb_substr($data['content'],0,6);
                                }else{
                                    $content=$data['content'];
                                }
                            }
                            if (empty($data['content'])){
                                if (isset($data['voice_content'])&&$data['voice_content']==9){
                                    $content2='扣款'.$data['price'].'元,请通行';
                                    if (mb_strlen($content2)>6){
                                        $content2=mb_substr($content2,0,6);
                                    }
                                }else{
                                    $content2=$temp_out_content2.$data['price'].'元';
                                }
                            }
                            else{
                                if (mb_strlen($data['content'])>6){
                                    $content2=mb_substr($data['content'],0,6);
                                }else{
                                    $content2=$data['content'];
                                }
                            }
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '01',
                                'content' => $data['car_number'],
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 3,
                                'serial' => '02',
                                'content' => $content,
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                            $showContentArr[]=[
                                'content_type' => 1,
                                'screen_row' => 2,
                                'serial' => '03',
                                'content' => $content2,
                                'duration' => $data['duration'],
                                'price' => $data['price']
                            ];
                        }
                    }
                }
                break;
            case 'month_type':
                if($passageDirection == 1){
                    //todo 车辆入场
                    if($deviceType == 1){
                        //todo 横屏
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $mouth_in_content : $data['content'],
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                    elseif ($deviceType == 2){
                        //todo 竖屏
                        if (empty($data['content'])){
                            $content=$mouth_in_content;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $content=mb_substr($data['content'],0,6);
                            }else{
                                $content=$data['content'];
                            }
                        }
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 3,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                }
                elseif ($passageDirection == 0){
                    //todo 车辆出场
                    if($deviceType == 1){
                        //todo 横屏
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $mouth_out_content1.$data['end_time_txt'] : $data['content'],
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                    elseif ($deviceType == 2){
                        //todo 竖屏
                        if (empty($data['content'])){
                            $content = $mouth_out_content1.$data['end_time_txt'];
                        }
                        else{
                            if (mb_strlen($data['content'])>6){
                                $content = mb_substr($data['content'],0,6);
                            }else{
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 3,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                }
                break;
            default:
                if($passageDirection == 1){
                    //todo 车辆入场
                    if($deviceType == 1){
                        //todo 横屏
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $mouth_in_content : $data['content'],
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                    elseif ($deviceType == 2){
                        //todo 竖屏

                        if (empty($data['content'])){
                            $content = $mouth_in_content;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $content = mb_substr($data['content'],0,6);
                            }else{
                                $content = $data['content'];
                            }
                        }
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 3,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                }
                elseif ($passageDirection == 0){
                    //todo 车辆出场
                    if($deviceType == 1){
                        //todo 横屏
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '02',
                            'content' => empty($data['content']) ? $temp_out_content3 : $data['content'],
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 1,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                    elseif ($deviceType == 2){
                        //todo 竖屏

                        if (empty($data['content'])){
                            $content= $temp_out_content3;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $content=mb_substr($data['content'],0,6);
                            }else{
                                $content=$data['content'];
                            }
                        }
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 3,
                            'serial' => '02',
                            'content' => $content,
                            'end_time' => $data['end_time']
                        ];
                        $showContentArr[]=[
                            'content_type' => 1,
                            'screen_row' => 2,
                            'serial' => '01',
                            'content' => $data['car_number'],
                            'end_time' => $data['end_time']
                        ];
                    }
                }
        }

        //todo 进场 语音
        if($passageDirection == 1){
            $showContentArr[]=[
                'content_type' => 2,
                'screen_row' => 2,
                'serial' => '01',
                'car_number' => $data['car_number'],
                'content' => (!isset($data['voice_content'])||empty($data['voice_content'])) ? 1: $data['voice_content'],
                'end_time' => isset($data['end_time']) ? $data['end_time'] : 0,
                'duration' => isset($data['duration']) ? $data['duration'] : 0,
                'price' => isset($data['price']) ? $data['price'] : 0
            ];
        }

        //todo 出场 语音
        if($passageDirection == 0){
            switch ($carType) {
                case 'temporary_type':
                    if (!empty($data['duration'])){
                        $content=(!isset($data['voice_content'])||empty($data['voice_content'])) ? 5: $data['voice_content'];
                    }
                    else{
                        $content=(!isset($data['voice_content'])||empty($data['voice_content'])) ? 2: $data['voice_content'];
                    }
                    $showContentArr[]=[
                        'content_type' => 2,
                        'screen_row' => 2,
                        'serial' => '01',
                        'car_number' => $data['car_number'],
                        'content' => $content,
                        'end_time' => isset($data['end_time']) ? $data['end_time'] : 0,
                        'duration' => isset($data['duration']) ? $data['duration'] : 0,
                        'price' => isset($data['price']) ? $data['price'] : 0
                    ];
                    break;
                case 'month_type':
                    if ($data['end_time']>1){
                        if (!isset($data['voice_content'])||empty($data['voice_content'])){
                            $content = 4;
                        }
                        else{
                            $content = $data['voice_content'];
                        }
                    }else{
                        if (!isset($data['voice_content'])||empty($data['voice_content'])){
                            $content = 2;
                        }
                        else{
                            $content = $data['voice_content'];
                        }
                    }
                    $showContentArr[]=[
                        'content_type' => 2,
                        'screen_row' => 2,
                        'serial' => '01',
                        'car_number' => $data['car_number'],
                        'content' => $content,
                        'end_time' => isset($data['end_time']) ? $data['end_time'] : 0,
                        'duration' => isset($data['duration']) ? $data['duration'] : 0,
                        'price' => isset($data['price']) ? $data['price'] : 0
                    ];
                    break;
                default:
                    if (!isset($data['voice_content'])||empty($data['voice_content'])){
                        $content=2;
                    }else{
                        $content=$data['voice_content'];
                    }
                    $showContentArr[]=[
                        'content_type' => 2,
                        'screen_row' => 2,
                        'serial' => '01',
                        'car_number' => $data['car_number'],
                        'content' => $content,
                        'end_time' => isset($data['end_time']) ? $data['end_time'] : 0,
                        'duration' => isset($data['duration']) ? $data['duration'] : 0,
                        'price' => isset($data['price']) ? $data['price'] : 0
                    ];
            }
        }

        $parkShowScreen=new D3ShowScreenService();
        $serialData=[];
        if($showContentArr){
            foreach ($showContentArr as $showKey=>$item){
                if($item['content_type'] == 1){ //显屏
                    $showVoiceTxt = $parkShowScreen->showVoiceTxt($item['content'], $item['screen_row'], $item['serial'], $orderType);
                }else{ //语音
                    $showVoiceTxt = $parkShowScreen->showVoice($item, '01');
                }
                $base64DataText = $parkShowScreen->translation($showVoiceTxt);
                $base64DataTextLength = strlen($base64DataText);
                $serialData[] = [
                    'serialChannel' => 0,
                    'data' => $base64DataText,
                    'dataLen' => $base64DataTextLength,
                ];
            }
        }
        $showscreen_log=[
            'village_id' => $data['village_id'],
            'park_sys_type' => 'D3',
            'content' => $data['car_number'],
            'content_type' => 1,
            'screen_row' => 1,
            'serial' => '01',
            'car_number' => $data['car_number'],
            'channel_id' => $data['channel_id'],
            'park_type' => 1,
            'add_time' => time(),
            'showcontent' => json_encode($serialData,JSON_UNESCAPED_UNICODE)
        ];
        $logId = $db_park_showscreen_log->add($showscreen_log);
        fdump_api(['参数进来了=='.__LINE__,[
            'car_number'=>$data['car_number'],
            '$showContentArr'=>$showContentArr,
            '$logId'=>$logId,
            '$showscreen_log'=>$showscreen_log
        ]],'park/d3/addShowScreenLog',1);
        return $logId;

        if (empty($config_info)){
            //车辆入场
            if ($data['passage']['passage_direction'] == 1){
                //横屏
                if ($data['passage']['device_type']==1){
                    if ($data['car_type']=='temporary_type'){
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['content_type']=1;
                        $log_data['screen_row']=1;
                        $log_data['serial']='01';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        // fdump_api([$log_data,$data],'showscreen_log_1',1);
                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']=$temp_in_content;
                        }else{
                            $log_data['content']=$data['content'];
                        }
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='02';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        // fdump_api([$log_data,$data],'showscreen_log_1',1);
                        $db_park_showscreen_log->add($log_data);
                    }
                    elseif ($data['car_type']=='month_type'){
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']=$mouth_in_content;
                        }else{
                            $log_data['content']=$data['content'];
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='02';
                        $log_data['add_time']=time();
                        //  fdump_api([$log_data,$data],'showscreen_log_1',1);
                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=1;
                        $log_data['serial']='01';
                        $log_data['add_time']=time();

                        //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                    else{
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']= $mouth_in_content;
                        }else{
                            $log_data['content']=$data['content'];
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='02';
                        //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=1;
                        $log_data['serial']='01';

                        //   fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                }
                //竖屏
                elseif($data['passage']['device_type']==2){
                    if ($data['car_type']=='temporary_type'){
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        // fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']=$temp_in_content;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $log_data['content']=mb_substr($data['content'],0,6);
                            }else{
                                $log_data['content']=$data['content'];
                            }
                        }
                        $log_data['content_type']=1;
                        $log_data['screen_row']=3;
                        $log_data['serial']='02';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();

                        // fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                    elseif ($data['car_type']=='month_type'){
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']=$mouth_in_content;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $log_data['content']=mb_substr($data['content'],0,6);
                            }else{
                                $log_data['content']=$data['content'];
                            }

                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=3;
                        $log_data['serial']='02';
                        $log_data['add_time']=time();

                        // fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['add_time']=time();

                        //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                    else{
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']= $mouth_in_content;
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $log_data['content']=mb_substr($data['content'],0,6);
                            }else{
                                $log_data['content']=$data['content'];
                            }
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=3;
                        $log_data['serial']='02';

                        //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=1;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';

                        //  fdump_api([$log_data,$data],'showscreen_log_1',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                }

            }
            //车辆出场
            if ($data['passage']['passage_direction'] == 0){
                //横屏
                if ($data['passage']['device_type']==1){
                    if ($data['car_type']=='temporary_type'){
                        if (!isset($data['price'])||empty($data['price'])){
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$data['car_number'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=0;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_2',1);

                            $db_park_showscreen_log->add($log_data);
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (empty($data['content'])){
                                $log_data['content']=$temp_out_content3;
                            }else{
                                $log_data['content']=$data['content'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=0;

                            fdump_api([$log_data,$data],'showscreen_log_2',1);

                            $db_park_showscreen_log->add($log_data);
                        }else{
                            $log_data['content']=$data['car_number'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=$data['price'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_stored_0907',1);

                            $db_park_showscreen_log->add($log_data);
                            if (empty($data['content'])){
                                $log_data['content']=$temp_out_content1.$data['duration_txt'];
                            }else{
                                $log_data['content']=$data['content'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=$data['price'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_stored_0907',1);
                            $db_park_showscreen_log->add($log_data);
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (empty($data['content'])){
                                if (isset($data['voice_content'])&&$data['voice_content']==9){
                                    $log_data['content']='扣款'.$data['price'].'元,请通行';
                                }else{
                                    $log_data['content']=$temp_out_content2.$data['price'].'元';
                                }

                            }else{
                                $log_data['content']=$data['content'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='03';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=$data['price'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_stored_0907',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                    elseif ($data['car_type']=='month_type'){
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']=$mouth_out_content1.$data['end_time_txt'];
                        }else{
                            $log_data['content']=$data['content'];
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='02';
                        $log_data['add_time']=time();

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=1;
                        $log_data['serial']='01';
                        $log_data['add_time']=time();

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                    else{
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']= '一路顺风';
                        }else{
                            $log_data['content']=$data['content'];
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='02';

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=1;
                        $log_data['serial']='01';

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                }
                //竖屏
                elseif($data['passage']['device_type']==2){
                    if ($data['car_type']=='temporary_type'){
                        if (empty($data['price'])){
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$data['car_number'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=0;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_2',1);

                            $db_park_showscreen_log->add($log_data);
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (empty($data['content'])){
                                $log_data['content']=$temp_out_content3;
                            }else{
                                if (mb_strlen($data['content'])>6){
                                    $log_data['content']=mb_substr($data['content'],0,6);
                                }else{
                                    $log_data['content']=$data['content'];
                                }
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=3;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=0;

                            fdump_api([$log_data,$data],'showscreen_log_2',1);

                            $db_park_showscreen_log->add($log_data);
                        }else{
                            $log_data['content']=$data['car_number'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=$data['price'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_stored_0907',1);

                            $db_park_showscreen_log->add($log_data);
                            if (empty($data['content'])){
                                $log_data['content']=$temp_out_content1.$data['duration_txt'];
                            }else{
                                if (mb_strlen($data['content'])>6){
                                    $log_data['content']=mb_substr($data['content'],0,6);
                                }else{
                                    $log_data['content']=$data['content'];
                                }
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=3;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=$data['price'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_stored_0907',1);

                            $db_park_showscreen_log->add($log_data);
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (empty($data['content'])){
                                if (isset($data['voice_content'])&&$data['voice_content']==9){
                                    $log_data['content']='扣款'.$data['price'].'元,请通行';
                                    if (mb_strlen($log_data['content'])>6){
                                        $log_data['content']=mb_substr($log_data['content'],0,6);
                                    }
                                }else{
                                    $log_data['content']=$temp_out_content2.$data['price'].'元';
                                }
                            }else{
                                if (mb_strlen($data['content'])>6){
                                    $log_data['content']=mb_substr($data['content'],0,6);
                                }else{
                                    $log_data['content']=$data['content'];
                                }
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='03';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['duration']=$data['duration'];
                            $log_data['price']=$data['price'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            fdump_api([$log_data,$data],'showscreen_log_stored_0907',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                    elseif ($data['car_type']=='month_type'){
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']=$mouth_out_content1.$data['end_time_txt'];
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $log_data['content']=mb_substr($data['content'],0,6);
                            }else{
                                $log_data['content']=$data['content'];
                            }
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=3;
                        $log_data['serial']='02';
                        $log_data['add_time']=time();

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['add_time']=time();

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                    else{
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        if (empty($data['content'])){
                            $log_data['content']= '一路顺风';
                        }else{
                            if (mb_strlen($data['content'])>6){
                                $log_data['content']=mb_substr($data['content'],0,6);
                            }else{
                                $log_data['content']=$data['content'];
                            }
                        }
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=3;
                        $log_data['serial']='02';

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                        $log_data=[];
                        $log_data['village_id']=$data['village_id'];
                        $log_data['park_sys_type']='D3';
                        $log_data['content']=$data['car_number'];
                        $log_data['car_number']=$data['car_number'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                        $log_data['content_type']=1;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';

                        fdump_api([$log_data,$data],'showscreen_log_2',1);

                        $db_park_showscreen_log->add($log_data);
                    }
                }
            }
        }
        else{
            //车辆入场
            if ($data['passage']['passage_direction'] == 1){
                //横屏
                if ($data['passage']['device_type']==1){
                    if ($data['car_type']=='temporary_type'){
                        if (!empty($config_info['temp_line_1'])){
                            if (strpos($config_info['temp_line_1'],'{车牌号}')){
                                $config_info['temp_line_1']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停留时长}')){
                                $config_info['temp_line_1']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车费}')){
                                $config_info['temp_line_1']=str_replace('{停车费}',$data['price'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车期时间}')){
                                $config_info['temp_line_1']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_1']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_1'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_2'])){
                            if (strpos($config_info['temp_line_2'],'{车牌号}')){
                                $config_info['temp_line_2']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停留时长}')){
                                $config_info['temp_line_2']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车费}')){
                                $config_info['temp_line_2']=str_replace('{停车费}',$data['price'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车期时间}')){
                                $config_info['temp_line_2']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_2']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_2'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_3'])){
                            if (strpos($config_info['temp_line_3'],'{车牌号}')){
                                $config_info['temp_line_3']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停留时长}')){
                                $config_info['temp_line_3']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车费}')){
                                $config_info['temp_line_3']=str_replace('{停车费}',$data['price'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车期时间}')){
                                $config_info['temp_line_3']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_3']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_3'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=3;
                            $log_data['serial']='03';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_4'])){
                            if (strpos($config_info['temp_line_4'],'{车牌号}')){
                                $config_info['temp_line_4']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停留时长}')){
                                $config_info['temp_line_4']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车费}')){
                                $config_info['temp_line_4']=str_replace('{停车费}',$data['price'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车期时间}')){
                                $config_info['temp_line_4']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_4']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_4'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=4;
                            $log_data['serial']='04';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //   fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                    elseif ($data['car_type']=='month_type') {
                        if (!empty($config_info['mouth_line_1'])) {
                            if (strpos($config_info['mouth_line_1'], '{车牌号}')) {
                                $config_info['mouth_line_1'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停留时长}')) {
                                $config_info['mouth_line_1'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车费}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车费}', $data['price'], $config_info['temp_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车期时间}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_1'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 1;
                            $log_data['serial'] = '01';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_2'])) {
                            if (strpos($config_info['mouth_line_2'], '{车牌号}')) {
                                $config_info['mouth_line_2'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停留时长}')) {
                                $config_info['mouth_line_2'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车费}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车期时间}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_2'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 2;
                            $log_data['serial'] = '02';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_3'])) {
                            if (strpos($config_info['mouth_line_3'], '{车牌号}')) {
                                $config_info['mouth_line_3'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'], '{停留时长}')) {
                                $config_info['mouth_line_3'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车费}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车期时间}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_3']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_3'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 3;
                            $log_data['serial'] = '03';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_4'])) {
                            if (strpos($config_info['mouth_line_4'], '{车牌号}')) {
                                $config_info['mouth_line_4'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停留时长}')) {
                                $config_info['mouth_line_4'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车费}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车期时间}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_4']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_4'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 4;
                            $log_data['serial'] = '04';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            // fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                }
                //竖屏
                elseif($data['passage']['device_type']==2){
                    if ($data['car_type']=='temporary_type'){
                        if (!empty($config_info['temp_line_1'])){
                            if (strpos($config_info['temp_line_1'],'{车牌号}')){
                                $config_info['temp_line_1']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停留时长}')){
                                $config_info['temp_line_1']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车费}')){
                                $config_info['temp_line_1']=str_replace('{停车费}',$data['price'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车期时间}')){
                                $config_info['temp_line_1']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_1']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_1'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_1'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_1'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_2'])){
                            if (strpos($config_info['temp_line_2'],'{车牌号}')){
                                $config_info['temp_line_2']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停留时长}')){
                                $config_info['temp_line_2']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车费}')){
                                $config_info['temp_line_2']=str_replace('{停车费}',$data['price'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车期时间}')){
                                $config_info['temp_line_2']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_2']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_2'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_2'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_2'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //    fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_3'])){
                            if (strpos($config_info['temp_line_3'],'{车牌号}')){
                                $config_info['temp_line_3']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停留时长}')){
                                $config_info['temp_line_3']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车费}')){
                                $config_info['temp_line_3']=str_replace('{停车费}',$data['price'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车期时间}')){
                                $config_info['temp_line_3']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_3']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_3'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_3'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_3'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=3;
                            $log_data['serial']='03';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_4'])){
                            if (strpos($config_info['temp_line_4'],'{车牌号}')){
                                $config_info['temp_line_4']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停留时长}')){
                                $config_info['temp_line_4']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车费}')){
                                $config_info['temp_line_4']=str_replace('{停车费}',$data['price'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车期时间}')){
                                $config_info['temp_line_4']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_4']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_4'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_4'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_4'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=4;
                            $log_data['serial']='04';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=1;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                    elseif ($data['car_type']=='month_type') {
                        if (!empty($config_info['mouth_line_1'])) {
                            if (strpos($config_info['mouth_line_1'], '{车牌号}')) {
                                $config_info['mouth_line_1'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停留时长}')) {
                                $config_info['mouth_line_1'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车费}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车费}', $data['price'], $config_info['temp_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车期时间}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_1'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_1'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_1'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 1;
                            $log_data['serial'] = '01';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            //   fdump_api([$log_data,$data],'showscreen_log_11',1);
                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_2'])) {
                            if (strpos($config_info['mouth_line_2'], '{车牌号}')) {
                                $config_info['mouth_line_2'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停留时长}')) {
                                $config_info['mouth_line_2'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车费}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车期时间}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_2'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_2'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_2'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 2;
                            $log_data['serial'] = '02';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            //      fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_3'])) {
                            if (strpos($config_info['mouth_line_3'], '{车牌号}')) {
                                $config_info['mouth_line_3'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'], '{停留时长}')) {
                                $config_info['mouth_line_3'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车费}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车期时间}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_3']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_3'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_3'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_3'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 3;
                            $log_data['serial'] = '03';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            //  fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_4'])) {
                            if (strpos($config_info['mouth_line_4'], '{车牌号}')) {
                                $config_info['mouth_line_4'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停留时长}')) {
                                $config_info['mouth_line_4'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车费}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车期时间}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_4']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_4'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_4'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_4'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 4;
                            $log_data['serial'] = '04';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 1;
                            $log_data['add_time'] = time();

                            // fdump_api([$log_data,$data],'showscreen_log_11',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                }
            }
            //车辆出场
            if ($data['passage']['passage_direction'] == 0){
                //横屏
                if ($data['passage']['device_type']==1){
                    if ($data['car_type']=='temporary_type'){
                        if (!empty($config_info['temp_line_1'])){
                            if (strpos($config_info['temp_line_1'],'{车牌号}')){
                                $config_info['temp_line_1']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停留时长}')){
                                $config_info['temp_line_1']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车费}')){
                                $config_info['temp_line_1']=str_replace('{停车费}',$data['price'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车期时间}')){
                                $config_info['temp_line_1']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_1']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_1'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //   fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_2'])){
                            if (strpos($config_info['temp_line_2'],'{车牌号}')){
                                $config_info['temp_line_2']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停留时长}')){
                                $config_info['temp_line_2']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车费}')){
                                $config_info['temp_line_2']=str_replace('{停车费}',$data['price'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车期时间}')){
                                $config_info['temp_line_2']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_2']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_2'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //    fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_3'])){
                            if (strpos($config_info['temp_line_3'],'{车牌号}')){
                                $config_info['temp_line_3']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停留时长}')){
                                $config_info['temp_line_3']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车费}')){
                                $config_info['temp_line_3']=str_replace('{停车费}',$data['price'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车期时间}')){
                                $config_info['temp_line_3']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_3']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_3'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=3;
                            $log_data['serial']='03';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_4'])){
                            if (strpos($config_info['temp_line_4'],'{车牌号}')){
                                $config_info['temp_line_4']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停留时长}')){
                                $config_info['temp_line_4']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车费}')){
                                $config_info['temp_line_4']=str_replace('{停车费}',$data['price'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车期时间}')){
                                $config_info['temp_line_4']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_4']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            $log_data['content']=$config_info['temp_line_4'];
                            $log_data['content_type']=1;
                            $log_data['screen_row']=4;
                            $log_data['serial']='04';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //  fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                    elseif ($data['car_type']=='month_type') {
                        if (!empty($config_info['mouth_line_1'])) {
                            if (strpos($config_info['mouth_line_1'], '{车牌号}')) {
                                $config_info['mouth_line_1'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停留时长}')) {
                                $config_info['mouth_line_1'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车费}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车费}', $data['price'], $config_info['temp_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车期时间}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_1'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 1;
                            $log_data['serial'] = '01';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //    fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_2'])) {
                            if (strpos($config_info['mouth_line_2'], '{车牌号}')) {
                                $config_info['mouth_line_2'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停留时长}')) {
                                $config_info['mouth_line_2'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车费}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车期时间}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_2'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 2;
                            $log_data['serial'] = '02';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //   fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_3'])) {
                            if (strpos($config_info['mouth_line_3'], '{车牌号}')) {
                                $config_info['mouth_line_3'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'], '{停留时长}')) {
                                $config_info['mouth_line_3'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车费}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车期时间}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_3']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_3'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 3;
                            $log_data['serial'] = '03';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //      fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_4'])) {
                            if (strpos($config_info['mouth_line_4'], '{车牌号}')) {
                                $config_info['mouth_line_4'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停留时长}')) {
                                $config_info['mouth_line_4'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车费}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车期时间}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_4']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            $log_data['content'] = $config_info['mouth_line_4'];
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 4;
                            $log_data['serial'] = '04';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //     fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                }
                //竖屏
                elseif($data['passage']['device_type']==2){
                    if ($data['car_type']=='temporary_type'){
                        if (!empty($config_info['temp_line_1'])){
                            if (strpos($config_info['temp_line_1'],'{车牌号}')){
                                $config_info['temp_line_1']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停留时长}')){
                                $config_info['temp_line_1']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车费}')){
                                $config_info['temp_line_1']=str_replace('{停车费}',$data['price'],$config_info['temp_line_1']);
                            }
                            if (strpos($config_info['temp_line_1'],'{停车期时间}')){
                                $config_info['temp_line_1']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_1']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_1'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_1'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_1'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=1;
                            $log_data['serial']='01';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //     fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_2'])){
                            if (strpos($config_info['temp_line_2'],'{车牌号}')){
                                $config_info['temp_line_2']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停留时长}')){
                                $config_info['temp_line_2']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车费}')){
                                $config_info['temp_line_2']=str_replace('{停车费}',$data['price'],$config_info['temp_line_2']);
                            }
                            if (strpos($config_info['temp_line_2'],'{停车期时间}')){
                                $config_info['temp_line_2']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_2']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_2'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_2'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_2'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=2;
                            $log_data['serial']='02';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //      fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_3'])){
                            if (strpos($config_info['temp_line_3'],'{车牌号}')){
                                $config_info['temp_line_3']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停留时长}')){
                                $config_info['temp_line_3']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车费}')){
                                $config_info['temp_line_3']=str_replace('{停车费}',$data['price'],$config_info['temp_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'],'{停车期时间}')){
                                $config_info['temp_line_3']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_3']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_3'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_3'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_3'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=3;
                            $log_data['serial']='03';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //   fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['temp_line_4'])){
                            if (strpos($config_info['temp_line_4'],'{车牌号}')){
                                $config_info['temp_line_4']=str_replace('{车牌号}',$data['car_number'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停留时长}')){
                                $config_info['temp_line_4']=str_replace('{停留时长}',$data['duration_txt'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车费}')){
                                $config_info['temp_line_4']=str_replace('{停车费}',$data['price'],$config_info['temp_line_4']);
                            }
                            if (strpos($config_info['temp_line_4'],'{停车期时间}')){
                                $config_info['temp_line_4']=str_replace('{停车期时间}',$data['end_time_txt'],$config_info['temp_line_4']);
                            }
                            $log_data=[];
                            $log_data['village_id']=$data['village_id'];
                            $log_data['park_sys_type']='D3';
                            if (mb_strlen($config_info['temp_line_4'])>6){
                                $log_data['content']=mb_substr($config_info['temp_line_4'],0,6);
                            }else{
                                $log_data['content']=$config_info['temp_line_4'];
                            }
                            $log_data['content_type']=1;
                            $log_data['screen_row']=4;
                            $log_data['serial']='04';
                            $log_data['car_number']=$data['car_number'];
                            $log_data['channel_id']=$data['channel_id'];
                            $log_data['park_type']=2;
                            $log_data['add_time']=time();

                            //    fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                    elseif ($data['car_type']=='month_type') {
                        if (!empty($config_info['mouth_line_1'])) {
                            if (strpos($config_info['mouth_line_1'], '{车牌号}')) {
                                $config_info['mouth_line_1'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停留时长}')) {
                                $config_info['mouth_line_1'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车费}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车费}', $data['price'], $config_info['temp_line_1']);
                            }
                            if (strpos($config_info['mouth_line_1'], '{停车期时间}')) {
                                $config_info['mouth_line_1'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_1'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_1'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_1'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 1;
                            $log_data['serial'] = '01';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //     fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_2'])) {
                            if (strpos($config_info['mouth_line_2'], '{车牌号}')) {
                                $config_info['mouth_line_2'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停留时长}')) {
                                $config_info['mouth_line_2'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车费}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_2']);
                            }
                            if (strpos($config_info['mouth_line_2'], '{停车期时间}')) {
                                $config_info['mouth_line_2'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_1']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_2'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_2'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_2'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 2;
                            $log_data['serial'] = '02';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //      fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_3'])) {
                            if (strpos($config_info['mouth_line_3'], '{车牌号}')) {
                                $config_info['mouth_line_3'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['temp_line_3'], '{停留时长}')) {
                                $config_info['mouth_line_3'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车费}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_3']);
                            }
                            if (strpos($config_info['mouth_line_3'], '{停车期时间}')) {
                                $config_info['mouth_line_3'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_3']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_3'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_3'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_3'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 3;
                            $log_data['serial'] = '03';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //     fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                        if (!empty($config_info['mouth_line_4'])) {
                            if (strpos($config_info['mouth_line_4'], '{车牌号}')) {
                                $config_info['mouth_line_4'] = str_replace('{车牌号}', $data['car_number'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停留时长}')) {
                                $config_info['mouth_line_4'] = str_replace('{停留时长}', $data['duration_txt'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车费}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车费}', $data['price'], $config_info['mouth_line_4']);
                            }
                            if (strpos($config_info['mouth_line_4'], '{停车期时间}')) {
                                $config_info['mouth_line_4'] = str_replace('{停车期时间}', $data['end_time_txt'], $config_info['mouth_line_4']);
                            }
                            $log_data = [];
                            $log_data['village_id'] = $data['village_id'];
                            $log_data['park_sys_type'] = 'D3';
                            if (mb_strlen($config_info['mouth_line_4'])>6){
                                $log_data['content']=mb_substr($config_info['mouth_line_4'],0,6);
                            }else{
                                $log_data['content']=$config_info['mouth_line_4'];
                            }
                            $log_data['content_type'] = 1;
                            $log_data['screen_row'] = 4;
                            $log_data['serial'] = '04';
                            $log_data['car_number'] = $data['car_number'];
                            $log_data['channel_id'] = $data['channel_id'];
                            $log_data['park_type'] = 2;
                            $log_data['add_time'] = time();

                            //      fdump_api([$log_data,$data],'showscreen_log_12',1);

                            $db_park_showscreen_log->add($log_data);
                        }
                    }
                }
            }
        }
        /*----------添加显屏指令end-----------*/

        /*----------添加语音指令start-----------*/
        // $config_info=$db_house_village_park_showScreen_config->getFind(['village_id'=>$data['village_id'],'screen_type'=>2,'passage_id'=>$data['passage']['id']]);
        fdump_api([$config_info,$data],'showVoice_log_1',1);
        if (empty($config_info)){
            //入场
            if ($data['passage']['passage_direction'] == 1){
                if (!isset($data['voice_content'])||empty($data['voice_content'])){
                    $log_data['content']=1;
                }else{
                    $log_data['content']=$data['voice_content'];
                }
                $log_data['content_type']=2;
                $log_data['screen_row']=2;
                $log_data['serial']='01';
                $log_data['car_number']=$data['car_number'];
                $log_data['channel_id']=$data['channel_id'];
                $log_data['park_type']=1;
                $log_data['add_time']=time();
                fdump_api([$log_data,$data],'showVoice_log_1',1);
                $db_park_showscreen_log->add($log_data);
            }
            //出场
            if ($data['passage']['passage_direction'] == 0){
                if ($data['car_type']=='temporary_type'){
                    if (!empty($data['duration'])){
                        if (!isset($data['voice_content'])||empty($data['voice_content'])){
                            $log_data['content']=5;
                        }else{
                            $log_data['content']=$data['voice_content'];
                        }
                        $log_data['content_type']= 2;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['duration']=$data['duration'];
                        $log_data['price']=$data['price'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                    }else{
                        if (!isset($data['voice_content'])||empty($data['voice_content'])){
                            $log_data['content']=2;
                        }else{
                            $log_data['content']=$data['voice_content'];
                        }
                        $log_data['content_type']=2;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['duration']=$data['duration'];
                        $log_data['price']=$data['price'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                    }
                    fdump_api([$log_data,$data],'showVoice_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                }elseif($data['car_type']=='month_type'){
                    if ($data['end_time']>1){
                        if (!isset($data['voice_content'])||empty($data['voice_content'])){
                            $log_data['content']=4;
                        }else{
                            $log_data['content']=$data['voice_content'];
                        }
                        $log_data['content_type']= 2;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['end_time']=$data['end_time'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                    }else{
                        if (!isset($data['voice_content'])||empty($data['voice_content'])){
                            $log_data['content']=2;
                        }else{
                            $log_data['content']=$data['voice_content'];
                        }
                        $log_data['content_type']=2;
                        $log_data['screen_row']=2;
                        $log_data['serial']='01';
                        $log_data['car_number']=$data['car_number'];
                        $log_data['duration']=$data['duration'];
                        $log_data['price']=$data['price'];
                        $log_data['channel_id']=$data['channel_id'];
                        $log_data['park_type']=2;
                        $log_data['add_time']=time();
                    }
                    fdump_api([$log_data,$data],'showVoice_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                }else{
                    if (!isset($data['voice_content'])||empty($data['voice_content'])){
                        $log_data['content']=2;
                    }else{
                        $log_data['content']=$data['voice_content'];
                    }
                    $log_data['content_type']=2;
                    $log_data['screen_row']=2;
                    $log_data['serial']='01';
                    $log_data['car_number']=$data['car_number'];
                    $log_data['duration']=$data['duration'];
                    $log_data['price']=$data['price'];
                    $log_data['channel_id']=$data['channel_id'];
                    $log_data['park_type']=2;
                    $log_data['add_time']=time();
                    fdump_api([$log_data,$data],'showVoice_log_1',1);
                    $db_park_showscreen_log->add($log_data);
                }
            }

        }
        else{
            //入场
            if ($data['passage']['passage_direction'] == 1){
                if ($data['car_type']=='temporary_type'){
                    $log_data['content']=$config_info['temp_line_1'];
                    $log_data['content_type']= 2;
                    $log_data['screen_row']=2;
                    $log_data['serial']='01';
                    $log_data['car_number']=$data['car_number'];
                    $log_data['duration']=$data['duration'];
                    $log_data['price']=$data['price'];
                    $log_data['channel_id']=$data['channel_id'];
                    $log_data['park_type']=1;
                    $log_data['add_time']=time();

                    fdump_api([$log_data,$data],'showVoice_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                }elseif($data['car_type']=='month_type'){
                    $log_data['content']=$config_info['mouth_line_1'];
                    $log_data['content_type']= 2;
                    $log_data['screen_row']=2;
                    $log_data['serial']='01';
                    $log_data['car_number']=$data['car_number'];
                    $log_data['duration']=$data['duration'];
                    $log_data['price']=$data['price'];
                    $log_data['channel_id']=$data['channel_id'];
                    $log_data['park_type']=1;
                    $log_data['add_time']=time();
                    fdump_api([$log_data,$data],'showVoice_log_1',1);
                    $db_park_showscreen_log->add($log_data);
                }
            }
            //出场

            if ($data['passage']['passage_direction'] == 0){
                if ($data['car_type']=='temporary_type'){
                    $log_data['content']=$config_info['temp_line_1'];
                    $log_data['content_type']= 2;
                    $log_data['screen_row']=2;
                    $log_data['serial']='01';
                    $log_data['car_number']=$data['car_number'];
                    $log_data['duration']=$data['duration'];
                    $log_data['price']=$data['price'];
                    $log_data['channel_id']=$data['channel_id'];
                    $log_data['park_type']=2;
                    $log_data['add_time']=time();
                    fdump_api([$log_data,$data],'showVoice_log_1',1);
                    $db_park_showscreen_log->add($log_data);
                }elseif($data['car_type']=='month_type'){
                    $log_data['content']=$config_info['mouth_line_1'];
                    $log_data['content_type']= 2;
                    $log_data['screen_row']=2;
                    $log_data['serial']='01';
                    $log_data['car_number']=$data['car_number'];
                    $log_data['end_time']=$data['end_time'];
                    $log_data['channel_id']=$data['channel_id'];
                    $log_data['park_type']=2;
                    $log_data['add_time']=time();

                    fdump_api([$log_data,$data],'showVoice_log_1',1);

                    $db_park_showscreen_log->add($log_data);
                }
            }
        }
        /*----------添加语音指令end-----------*/
    }

    /**
     * 获取语音配置
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function getVoiceSet($village_id,$passage_id){
        $db_park_passage=new ParkPassage();
        $db_house_village_park_showscreen_config=new HouseVillageParkShowscreenConfig();
        $service_D3_show_screen=new D3ShowScreenService();
        $passage_info=$db_park_passage->getFind(['village_id'=>$village_id,'id'=>$passage_id]);
        $voiceTempData=[];
        $voiceMouthData=[];
        $voice=[];
        if (!empty($passage_info)){
            $voiceArr=$service_D3_show_screen->getVoiceArr();
            $where=[
                'village_id'=>$village_id,
                'passage_id'=>$passage_id,
                'screen_type'=>2,
            ];
            $voice=$db_house_village_park_showscreen_config->getFind($where);
            if (!empty($voice)){
                $voice['temp_line_1']=(int)$voice['temp_line_1'];
                $voice['mouth_line_1']=(int)$voice['mouth_line_1'];
            }
            if (!empty($voiceArr)){
                foreach ($voiceArr as $v){
                    if (in_array($passage_info['passage_direction'],$v['passage_type'])){
                        if (in_array(1,$v['car_type'])){
                            $voiceTempData1['key']=$v['key'];
                            $voiceTempData1['txt']=$v['txt'];
                            $voiceTempData[]=$voiceTempData1;
                        }
                        if (in_array(2,$v['car_type'])){
                            $voiceMouthData1['key']=$v['key'];
                            $voiceMouthData1['txt']=$v['txt'];
                            $voiceMouthData[]=$voiceMouthData1;
                        }
                    }
                }
            }
        }
        $list=[];
        $list['setData']=$voice;
        $list['mouthList']=$voiceMouthData;
        $list['tempList']=$voiceTempData;
        return $list;
    }

    /**
     * 获取显屏配置
     * @author:zhubaodi
     * @date_time: 2022/4/15 14:45
     */
    public function getScreenSet($village_id,$passage_id){
        $db_park_passage=new ParkPassage();
        $db_house_village_park_showscreen_config=new HouseVillageParkShowscreenConfig();
        
        $passage_info=$db_park_passage->getFind(['village_id'=>$village_id,'id'=>$passage_id]);
        $voice=[];
        $park_sys_type='';
        if (!empty($passage_info)) {
            $garage_id=0;
            if (isset($passage_info['garage_id'])) {
                $garage_id = $passage_info['garage_id'];
            }
            if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
                $garage_id = intval($passage_info['passage_area']);
            }
            $garage_id = $garage_id > 0 ? $garage_id : 0;
            $park_sys_type=$this->checkVillageParkConfig($village_id,$garage_id);
            
            $where = [
                'village_id' => $village_id,
                'passage_id' => $passage_id,
                'screen_type' => 1,
                'park_sys_type'=>$park_sys_type
            ];
            $voiceObj = $db_house_village_park_showscreen_config->getFind($where,'temp_line_1,temp_line_2,temp_line_3,temp_line_4,mouth_line_1,mouth_line_2,mouth_line_3,mouth_line_4');
            if($voiceObj && !$voiceObj->isEmpty()){
                $voice= $voiceObj->toArray();
            }
        }
        $voice['passage_id']=$passage_id;
        $voice['park_sys_type']=$park_sys_type;
        return $voice;
    }


    /**
     * 设置语音配置项
     * @author:zhubaodi
     * @date_time: 2022/4/15 13:12
     */
    public function setVoiceSet($village_id,$passage_id,$temp_id,$mouth_id){
        $db_park_passage=new ParkPassage();
        $db_park_showscreen_log=new ParkShowscreenLog();
        $db_house_village_park_showscreen_config=new HouseVillageParkShowscreenConfig();
        $passage_info=$db_park_passage->getFind(['village_id'=>$village_id,'id'=>$passage_id]);
        $res=0;
        if (!empty($passage_info)){
            $garage_id=0;
            if (isset($passage_info['garage_id'])) {
                $garage_id = $passage_info['garage_id'];
            }
            if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
                $garage_id = intval($passage_info['passage_area']);
            }
            $garage_id = $garage_id > 0 ? $garage_id : 0;
            $park_sys_type=$this->checkVillageParkConfig($village_id,$garage_id);
            
            $where=[
                'village_id'=>$village_id,
                'passage_id'=>$passage_id,
                'screen_type'=>2,
                'park_sys_type'=>$park_sys_type
            ];
            $voice=$db_house_village_park_showscreen_config->getFind($where);
            $set_data=[];
            $set_data['village_id']=$village_id;
            $set_data['passage_id']=$passage_id;
            $set_data['screen_type']=2;
            $set_data['park_sys_type']=$park_sys_type;
            $set_data['add_time']=time();
            if (!empty($temp_id)){
                $set_data['temp_line_1']=$temp_id;
            }
            if (!empty($mouth_id)){
                $set_data['mouth_line_1']=$mouth_id;
            }
            //   print_r([$where,$set_data,$voice]);die;
            if (!empty($voice)){
                $res=$db_house_village_park_showscreen_config->save_one($where,$set_data);
            }else{
                $res=$db_house_village_park_showscreen_config->addOne($set_data);
            }
        }
        return $res;
    }

    /**
     * 设置显屏配置项
     * @author:zhubaodi
     * @date_time: 2022/4/15 14:45
     */
    public function setScreenSet($village_id,$passage_id,$content){
        $db_park_passage=new ParkPassage();
        $db_park_showscreen_log=new ParkShowscreenLog();
        $db_house_village_park_showscreen_config=new HouseVillageParkShowscreenConfig();
        
        $passage_info=$db_park_passage->getFind(['village_id'=>$village_id,'id'=>$passage_id]);
        $res=0;
        if (!empty($passage_info)){
            $garage_id=0;
            if (isset($passage_info['garage_id'])) {
                $garage_id = $passage_info['garage_id'];
            }
            if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
                $garage_id = intval($passage_info['passage_area']);
            }
            $garage_id = $garage_id > 0 ? $garage_id : 0;
            $park_sys_type=$this->checkVillageParkConfig($village_id,$garage_id);
            
            $where=[
                'village_id'=>$village_id,
                'passage_id'=>$passage_id,
                'screen_type'=>1,
                'park_sys_type'=>$park_sys_type
            ];
            $voice=$db_house_village_park_showscreen_config->getFind($where);
            $set_data=[];
            $set_data['village_id']=$village_id;
            $set_data['passage_id']=$passage_id;
            $set_data['screen_type']=1;
            $set_data['park_sys_type']=$park_sys_type;
            $set_data['add_time']=time();
            if (!empty($content['temp_line_1'])){
                $set_data['temp_line_1']=trim($content['temp_line_1']);
            }
            if (!empty($content['temp_line_2'])){
                $set_data['temp_line_2']=trim($content['temp_line_2']);
            }
            if (!empty($content['temp_line_3'])){
                $set_data['temp_line_3']=trim($content['temp_line_3']);
            }
            if (!empty($content['temp_line_4'])){
                $set_data['temp_line_4']=trim($content['temp_line_4']);
            }
            if (!empty($content['mouth_line_1'])){
                $set_data['mouth_line_1']=trim($content['mouth_line_1']);
            }
            if (!empty($content['mouth_line_2'])){
                $set_data['mouth_line_2']=trim($content['mouth_line_2']);
            }
            if (!empty($content['mouth_line_3'])){
                $set_data['mouth_line_3']=trim($content['mouth_line_3']);
            }
            if (!empty($content['mouth_line_4'])){
                $set_data['mouth_line_4']=trim($content['mouth_line_4']);
            }
            if (!empty($voice) && !$voice->isEmpty()){
                $voiceData=$voice->toArray();
                $res=$db_house_village_park_showscreen_config->save_one($where,$set_data);
                $parseInfo = $this->traitPaseTokenRequestWhoim(request()->log_uid,request()->log_extends);
                $queuData = [
                    'logData' => [
                        'tbname' => '车道管理-设置显屏内容',
                        'table'  => 'house_village_park_showscreen_config',
                        'client' => '小区后台',
                        'trigger_path' => '停车管理->车道管理',
                        'trigger_type' => $this->getUpdateNmae(),
                        'addtime'      => time(),
                        'village_id'   => $village_id,
                        'property_id' =>0,
                        'op_id'   => $parseInfo['op_id'],
                        'op_type'   => $parseInfo['op_type'],
                        'op_name'   => $parseInfo['op_name'],
                    ],
                    'newData' => $set_data,
                    'oldData' => $voiceData
                ];
                $this->laterLogInQueue($queuData);
            }else{
                $res=$db_house_village_park_showscreen_config->addOne($set_data);
            }
            if ($res>0){
                $log_data=[];
                $log_data['village_id']=$village_id;
                $log_data['park_sys_type']=$park_sys_type;
                $log_data['content_type']=1;
                $log_data['channel_id']=$passage_info['device_number'];
                $log_data['add_time']=time();
                $log_data['orderType']=25;
                //车辆入场
                if ($passage_info['passage_direction'] == 1){
                    $log_data['park_type']=1;
                }
                //车辆出场
                if ($passage_info['passage_direction'] == 0){
                    $log_data['park_type']=2;
                }
                if (!empty($set_data['temp_line_1'])){
                    $log_data['content']=$set_data['temp_line_1'];
                    //竖屏
                    if($passage_info['device_type']==2){
                        if (mb_strlen($set_data['temp_line_1'])>6){
                            $log_data['content']=mb_substr($set_data['temp_line_1'],0,6);
                        }
                    }
                    $log_data['screen_row']=1;
                    $log_data['serial']='01';
                    $db_park_showscreen_log->add($log_data);
                }
                if (!empty($set_data['temp_line_2'])){
                    $log_data['content']=$set_data['temp_line_2'];
                    //竖屏
                    if($passage_info['device_type']==2){
                        if (mb_strlen($set_data['temp_line_2'])>6){
                            $log_data['content']=mb_substr($set_data['temp_line_2'],0,6);
                        }
                    }
                    $log_data['screen_row']=2;
                    $log_data['serial']='02';
                    $db_park_showscreen_log->add($log_data);
                }
                if (!empty($set_data['temp_line_3'])){
                    $log_data['content']=$set_data['temp_line_3'];
                    //竖屏
                    if($passage_info['device_type']==2){
                        if (mb_strlen($set_data['temp_line_2'])>6){
                            $log_data['content']=mb_substr($set_data['temp_line_2'],0,6);
                        }
                    }
                    $log_data['screen_row']=3;
                    $log_data['serial']='03';
                    $db_park_showscreen_log->add($log_data);
                }
                if (!empty($set_data['temp_line_4'])){
                    $log_data['content']=$set_data['temp_line_4'];
                    //竖屏
                    if($passage_info['device_type']==2){
                        if (mb_strlen($set_data['temp_line_4'])>6){
                            $log_data['content']=mb_substr($set_data['temp_line_4'],0,6);
                        }
                    }
                    $log_data['screen_row']=4;
                    $log_data['serial']='04';
                    $db_park_showscreen_log->add($log_data);
                }
            }
        }
        return $res;
    }
    public function issuedScreenSetCmd($passage_info=array(),$heartLastTime=0){
        $db_park_showscreen_log=new ParkShowscreenLog();
        $db_house_village_park_showscreen_config=new HouseVillageParkShowscreenConfig();
        $where=[
            'village_id'=>$passage_info['village_id'],
            'passage_id'=>$passage_info['id'],
            'screen_type'=>1,
            'park_sys_type'=>$passage_info['park_sys_type']
        ];
        $showscreen_config_obj=$db_house_village_park_showscreen_config->getFind($where,'*');
        $showscreen_config=array();
        if($showscreen_config_obj && !$showscreen_config_obj->isEmpty()){
            $showscreen_config= $showscreen_config_obj->toArray();
        }
        $log_data=[];
        $log_data['village_id']=$passage_info['village_id'];
        $log_data['park_sys_type']=$passage_info['park_sys_type'];
        $log_data['content_type']=1;
        $log_data['channel_id']=$passage_info['device_number'];
        $log_data['add_time']=time();
        $log_data['orderType']=25;
        if ($passage_info['passage_direction'] == 1){
            $log_data['park_type']=1;
        }
        //车辆出场
        if ($passage_info['passage_direction'] == 0){
            $log_data['park_type']=2;
        }
        fdump_api(['log_data'=>$log_data,'heartLastTime'=>$heartLastTime,'passage_info'=>$passage_info,'showscreen_config'=>$showscreen_config],'A11/issuedScreenSetCmd'.date('H'),1);
        if (!empty($showscreen_config) && !empty($showscreen_config['temp_line_1'])){
            $log_data['content']=$showscreen_config['temp_line_1'];
            //竖屏
            if($passage_info['device_type']==2){
                if (mb_strlen($showscreen_config['temp_line_1'])>6){
                    $log_data['content']=mb_substr($showscreen_config['temp_line_1'],0,6);
                }
            }
            $log_data['screen_row']=1;
            $log_data['serial']='01';
            $db_park_showscreen_log->add($log_data);
        }
        if (!empty($showscreen_config['temp_line_2'])){
            $log_data['content']=$showscreen_config['temp_line_2'];
            //竖屏
            if($passage_info['device_type']==2){
                if (mb_strlen($showscreen_config['temp_line_2'])>6){
                    $log_data['content']=mb_substr($showscreen_config['temp_line_2'],0,6);
                }
            }
            $log_data['screen_row']=2;
            $log_data['serial']='02';
            $db_park_showscreen_log->add($log_data);
        }
        if (!empty($showscreen_config['temp_line_3'])){
            $log_data['content']=$showscreen_config['temp_line_3'];
            //竖屏
            if($passage_info['device_type']==2){
                if (mb_strlen($showscreen_config['temp_line_2'])>6){
                    $log_data['content']=mb_substr($showscreen_config['temp_line_2'],0,6);
                }
            }
            $log_data['screen_row']=3;
            $log_data['serial']='03';
            $db_park_showscreen_log->add($log_data);
        }
        if (!empty($showscreen_config['temp_line_4'])){
            $log_data['content']=$showscreen_config['temp_line_4'];
            //竖屏
            if($passage_info['device_type']==2){
                if (mb_strlen($showscreen_config['temp_line_4'])>6){
                    $log_data['content']=mb_substr($showscreen_config['temp_line_4'],0,6);
                }
            }
            $log_data['screen_row']=4;
            $log_data['serial']='04';
            $db_park_showscreen_log->add($log_data);
        }
        return true;
    }
    public function getCarInfo($data){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_user=new User();
        $arr=[];
        $userBindInfo=$db_house_village_user_bind->getSumBills(['pigcms_id'=>$data['pigcms_id'],'village_id'=>$data['village_id']],'pigcms_id,village_id,uid,name,phone');
        if (empty($userBindInfo)||empty($userBindInfo['name'])||empty($userBindInfo['phone'])){
            $userInfo=$db_user->getOne(['uid'=>$data['uid']],'uid,phone,nickname');
            if (empty($userBindInfo['name'])&&!empty($userInfo['nickname'])){
                $arr['name']=$userInfo['nickname'];
            }elseif(!empty($userBindInfo['name'])){
                $arr['name']=$userInfo['name'];
            }else{
                $arr['name']='';
            }
            if (empty($userBindInfo['phone'])&&!empty($userInfo['phone'])){
                $arr['phone']=$userInfo['phone'];
            }elseif(!empty($userBindInfo['phone'])){
                $arr['phone']=$userInfo['phone'];
            }else{
                $arr['phone']='';
            }
        }else{
            $arr['name']=$userBindInfo['name'];
            $arr['phone']=$userBindInfo['phone'];
        }
        $arr['car_number']='';
        $passage_info=array();
        $garage_id=0;
        if(!empty($data['channel_id'])){
            $db_park_passage = new ParkPassage();
            //查询设备
            $passage_info = $db_park_passage->getFind(['channel_number' => $data['channel_id'], 'village_id'=>$data['village_id'],'status' => 1]);
            $garage_id=0;
            if (isset($passage_info['garage_id'])) {
                $garage_id = $passage_info['garage_id'];
            }
            if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
                $garage_id = intval($passage_info['passage_area']);
            }
            $garage_id = $garage_id > 0 ? $garage_id : 0;
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $info=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$data['village_id']);
        if (!empty($passage_info) && isset($passage_info['device_number']) && $passage_info['device_number'] && in_array($info['park_sys_type'],['D3','A11'])){
            $where = [
                ['channel_id', '=', $passage_info['device_number']],
            ];
            //当前车道最新一条
            $db_park_plateresult_log = new ParkPlateresultLog();
            $log_info = $db_park_plateresult_log->get_one($where,true, 'id DESC');
            if (!empty($log_info)){
                $arr['car_number']= $log_info['car_number'];
            }
            $arr['nolicence']=true;
        }else{
            $arr['nolicence']=true;
        }

        if (!empty($info)&&$info['temp_in_park_type']==0&&in_array($info['park_sys_type'],['D3','A11'])){
            $village_info = (new HouseVillage())->getOne($data['village_id'],'village_name');
            $arr['tips']=$village_info['village_name'].'小区禁止临时车入场';
        }else{
            $arr['tips']='';
        }
        $deviceQrCodeJudge = (new HikGateWayEventService())->deviceQrCodeJudge($data['village_id']);
        if ($deviceQrCodeJudge) {
            // todo 返回判断条件
            $arr['is_visit_owner'] = 1;
            $arr['visit_owner_switch'] = false;
        } else {
            $arr['is_visit_owner'] = 0;
        }
        $arr['have_visit_ewm_btn'] = 0;
        $arr['visit_ewm_btn_title'] = '';
        if($this->hasCloudIntercom()){
            $arr['have_visit_ewm_btn'] = 1;
            $arr['visit_ewm_btn_title'] = '访客二维码';
            $arr['visit_ewm_btn_url'] = cfg('site_url').'/packapp/village/pages/village_my/visitorRegistration?village_id='.$data['village_id'].'&pigcms_id='.$data['pigcms_id'].'&uid='.$data['uid'].'&visitor_type=myall';
        }
        return $arr;
    }


    /**
     * 添加白名单
     * @author:zhubaodi
     * @date_time: 2022/6/24 17:37
     */
    public function addWhitelist($data){
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_park_white_record=new ParkWhiteRecord();
        $db_park_passage=new ParkPassage();
        $whereArr=array('village_id'=>$data['village_id'],'park_sys_type'=>'D3');
        if(isset($data['garage_id']) && $data['garage_id']>0){
            $whereArr['_string']='garage_id='.$data['garage_id'].' OR (area_type=2 AND passage_area='.$data['garage_id'].')';
        }
        $passage_info=$db_park_passage->getList($whereArr,'id,village_id,channel_number,device_number,passage_direction,park_sys_type');
        if (!empty($passage_info) && !$passage_info->isEmpty()){
            $passage_info=$passage_info->toArray();
        }else{
            $passage_info=array();
        }
        $province = mb_substr($data['car_number'], 0, 1);
        $car_no = mb_substr($data['car_number'], 1);
        $dateTime=date('H');
        if(isset($data['car_info']) && $data['car_info'] && isset($data['end_time'])){
            $car_info=$data['car_info'];
            unset($data['car_info']);
        }else{
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no,'village_id'=>$data['village_id']]);
        }
        $car_end_time=time();
        if(!empty($car_info)&& isset($car_info['end_time']) && $car_info['end_time']>100){
            $car_end_time=$car_info['end_time'];
        }
        if(isset($data['car_end_time']) && $data['car_end_time']>0){
            $car_end_time=$data['car_end_time'];
        }
        fdump_api([$province,$car_no,'data'=>$data,'car_end_time'=>$car_end_time,'passage_info'=>$passage_info],'D3addWhitelist'.$dateTime,1);
        if (!empty($passage_info)&& $car_end_time>100){
            foreach ($passage_info as $value){
                $white_arr=[
                    'village_id'=>$data['village_id'],
                    'park_sys_type'=>'D3',
                    'channel_id'=>$value['id'],
                    'car_number'=>$data['car_number'],
                    'enable'=>1,
                    'need_alarm'=>0,
                    'operate_type'=>0,
                    'start_time'=>isset($car_info['start_time'])&&!empty($car_info['start_time'])?$car_info['start_time']:time(),
                    'end_time'=>$car_end_time,
                    'add_time'=>time(),
                ];
                $whereArr=array(['village_id','=',$data['village_id']]);
                $whereArr[]=array('park_sys_type','=','D3');
                $whereArr[]=array('channel_id','=',$value['id']);
                $whereArr[]=array('car_number','=',$data['car_number']);
                $whereArr[]=array('operate_type','=',0);
                $findDataObj=$db_park_white_record->getFind($whereArr,'*');
                if($findDataObj && !$findDataObj->isEmpty()){
                    $findData=$findDataObj->toArray();
                    $saveData=array('enable'=>1,'start_time'=>$white_arr['start_time'],'end_time'=>$car_end_time,'add_time'=>$white_arr['add_time']);
                    $db_park_white_record->save_one(array('id'=>$findData['id']),$saveData);
                    fdump_api(['findData'=>$findData],'D3addWhitelist'.$dateTime,1);
                }else{
                    $id=$db_park_white_record->add($white_arr);
                    fdump_api(['id'=>$id,$white_arr],'D3addWhitelist'.$dateTime,1);
                }

            }
            return true;
        }
        return false;
    }

    /**
     * 添加删除白名单指令
     * @author:zhubaodi
     * @date_time: 2022/6/24 17:37
     */
    public function delWhitelist($data){
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_park_white_record=new ParkWhiteRecord();
        $db_park_passage=new ParkPassage();
        $whereArr=array('village_id'=>$data['village_id'],'park_sys_type'=>'D3');
        if(isset($data['garage_id']) && $data['garage_id']>0){
            $whereArr['_string']='garage_id='.$data['garage_id'].' OR (area_type=2 AND passage_area='.$data['garage_id'].')';
        }
        $passage_info=$db_park_passage->getList($whereArr,'id,village_id,channel_number,device_number,passage_direction,park_sys_type');
        if (!empty($passage_info) && !$passage_info->isEmpty()){
            $passage_info=$passage_info->toArray();
        }else{
            $passage_info=array();
        }
        $dateTime=date('H');
        $tmp_time=time();
        $tmp_time=$tmp_time-60;
        if (!empty($passage_info)){
            foreach ($passage_info as $value){
                $white_arr=[
                    'village_id'=>$data['village_id'],
                    'park_sys_type'=>'D3',
                    'channel_id'=>$value['id'],
                    'car_number'=>$data['car_number'],
                    'enable'=>1,
                    'need_alarm'=>0,
                    'operate_type'=>1,
                    'start_time'=>$tmp_time,
                    'end_time'=>$tmp_time,
                    'add_time'=>$tmp_time,
                ];
                $whereArr=array(['village_id','=',$data['village_id']]);
                $whereArr[]=array('park_sys_type','=','D3');
                $whereArr[]=array('channel_id','=',$value['id']);
                $whereArr[]=array('car_number','=',$data['car_number']);
                $whereArr[]=array('operate_type','=',1);
                $findDataObj=$db_park_white_record->getFind($whereArr,'*');
                if($findDataObj && !$findDataObj->isEmpty()){
                    $findData=$findDataObj->toArray();
                    $saveData=array('enable'=>1,'start_time'=>$tmp_time,'end_time'=>$tmp_time,'add_time'=>$tmp_time);
                    $db_park_white_record->save_one(array('id'=>$findData['id']),$saveData);
                    fdump_api(['findData'=>$findData],'D3delWhitelist'.$dateTime,1);
                }else{
                    $id=$db_park_white_record->add($white_arr);
                    fdump_api(['id'=>$id,$white_arr],'D3delWhitelist'.$dateTime,1);
                }
                
            }
        }
    }

    /**
     * 查询白名单列表
     * @author:zhubaodi
     * @date_time: 2022/6/24 17:37
     */
    public function getWhitelist($data, $passage_info = [])
    {
        $db_park_white_record = new ParkWhiteRecord();
        $record_info = [];
        if (!empty($data['device_number'])) {
            if (empty($passage_info)) {
                $carHandleGetInfoService = new CarHandleGetInfoService();
                $passage_info = $carHandleGetInfoService->getParkPassageInfo($data['device_number']);
            }
            if (!empty($passage_info)) {
                $record_info = $db_park_white_record->getFind(['village_id' => $passage_info['village_id'], 'channel_id' => $passage_info['id']], '*');
                if (!empty($record_info) && !$record_info->isEmpty()) {
                    $record_info=$record_info->toArray();
                    $db_park_white_record->delOne(['id' => $record_info['id']]);
                }
            }
        }
        return $record_info;
    }


    /**
     * 添加停车支付结果查询指令
     * @author:zhubaodi
     * @date_time: 2022/7/9 13:53
     */
    public function addParkPayRecord($order_no){
        fdump_api([$order_no],'addParkPayRecord_0728',1);
        $db_pay_order_info = new PayOrderInfo();
        $db_plat_order = new PlatOrder();
        $db_house_new_pay_order=new HouseNewPayOrder();
        $db_park_plateresult_log=new ParkPlateresultLog();
        $db_park_scrcu_record=new ParkScrcuRecord();
        if (empty($order_no)) {
            return false;
        }
        $payOrderInfo=$db_pay_order_info->getByOrderNo($order_no);
        fdump_api([$order_no,$payOrderInfo],'addParkPayRecord_0728',1);
        if (empty($payOrderInfo)||empty($payOrderInfo['business_order_id'])) {
            return false;
        }
        $platOrder=$db_plat_order->get_one(['order_id'=>$payOrderInfo['business_order_id']]);
        fdump_api([$payOrderInfo['business_order_id'],$platOrder],'addParkPayRecord_0728',1);
        if (!empty($payOrderInfo)&&$payOrderInfo['paid']==1){
            if (!empty($platOrder)&&$platOrder['paid']==1){
                return false;
            }
        }
        $house_new_pay_order=$db_house_new_pay_order->get_one(['summary_id'=>$platOrder['business_id'],'order_type'=>'park_new','car_type'=>'temporary_type'],'car_number,order_id,summary_id,village_id');
        fdump_api([$house_new_pay_order,$platOrder['business_id']],'addParkPayRecord_0728',1);
        if (empty($house_new_pay_order)||empty($house_new_pay_order['car_number'])||empty($house_new_pay_order['village_id'])){
            return false;
        }
        $park_info=$db_park_plateresult_log->get_one(['car_number'=>$house_new_pay_order['car_number'],'park_type'=>2]);
        fdump_api([$park_info,$house_new_pay_order['car_number']],'addParkPayRecord_0728',1);
        if (empty($park_info)||empty($park_info['channel_id'])||($park_info['add_time']+300)<time()){
            return false;
        }
        $arr=[
            'car_number'=>$house_new_pay_order['car_number'],
            'channel_id'=>$park_info['channel_id'],
            'order_number'=>$order_no,
            'add_time'=>time(),
        ];
        $res=$db_park_scrcu_record->add($arr);
        fdump_api([$res,$arr],'addParkPayRecord_0728',1);
        return true;
    }

    /**
     * 处理停车支付结果查询指令
     * @author:zhubaodi
     * @date_time: 2022/7/9 15:45
     */
    public function getParkPayRecord($channel_id){

        $db_pay_order_info = new PayOrderInfo();
        $db_plat_order= new PlatOrder();
        $db_park_scrcu_record=new ParkScrcuRecord();
        //  $service_scrcu=new ScrcuService();
        $service_scrcu=new PayService();
        if (empty($channel_id)) {
            return false;
        }
        $notime=time();
        $add_time=$notime-1800;
        $whereArr=array();
        $whereArr[]=array('channel_id','=',$channel_id);
        $whereArr[]=array('over','<',1);
        $whereArr[]=array('limit','<',125);
        $whereArr[]=array('add_time','>=',$add_time);
        $record_info= $db_park_scrcu_record->get_one($whereArr,'*','limit ASC');
        if (empty($record_info)||empty($record_info['order_number'])){
            return false;
        }

        $payOrderInfo=$db_pay_order_info->getByOrderNo($record_info['order_number']);
        $platOrderInfo=$db_plat_order->get_one(['order_id'=>$payOrderInfo['business_order_id']]);

        if (!empty($platOrderInfo)&&$platOrderInfo['paid']==1) {
            $db_park_scrcu_record->delOne(['id'=>$record_info['id']]);
            return false;
        }
        $pay_order_id = isset($platOrderInfo['business_id']) ? intval($platOrderInfo['business_id']) : 0;
        $pay_time = isset($platOrderInfo['pay_time']) ? $platOrderInfo['pay_time'] : $payOrderInfo['paid_time'];
        $park_sys_type = 'D3';
        $db_house_village_parking_temp=new HouseVillageParkingTemp();
        //fdump_api([$channel_id,$platOrderInfo],'getParkPayRecord_0906',1);
        if ((!empty($payOrderInfo)&&$payOrderInfo['channel']=='scrcu'&&$payOrderInfo['paid']==1)) {
            $db_in_park = new InPark();
            $db_out_park = new OutPark();
            $db_park_passage = new ParkPassage();
            $db_house_village = new HouseVillage();
            $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
            $now_time=time();
            //查询设备
            $passage_info = $db_park_passage->getFind(['device_number' => $channel_id, 'status' => 1, 'park_sys_type' => $park_sys_type]);
            // 记录车辆进入信息
            $car_access_record = [];
            $car_access_record['channel_id']=$passage_info['id'];
            $car_access_record['channel_number']=$passage_info['channel_number'];
            $car_access_record['channel_name']=$passage_info['passage_name'];
            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
            $whereInRecord = ['car_number' => $record_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
            if (!empty($in_park_pay_info)) {
                $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $record_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1]);
                //写入车辆入场表
                $park_data = [];
                $park_data['out_time'] = $now_time;
                $park_data['is_out'] = 1;
                $park_data['is_paid'] = 1;
                $res_in=$db_in_park->saveOne(['car_number' => $record_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0], $park_data);
                $insert_id=0;
                /*
                
                $out_data = [];
                $out_data['car_number'] = $record_info['car_number'];
                $out_data['park_sys_type'] = $park_sys_type;
                if (!empty($in_park_pay_info)) {
                    $out_data['in_time'] = $in_park_pay_info['in_time'];
                    $out_data['order_id'] = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : '';
                }
                $out_data['out_time'] = $now_time;
                $out_data['park_id'] = $passage_info['village_id'];
                $out_data['pay_type'] = 'scancode';
                $out_data['total'] = round_number($payOrderInfo['money']/100,2);
                $starttime = time() - 30;
                $endtime = time() + 30;
                $park_where = [
                    ['car_number', '=', $record_info['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['out_time', '>=', $starttime],
                    ['out_time', '<=', $endtime],
                ];
                $park_info_car = $db_out_park->getOne($park_where);
                //写入车辆入场表
                if (! $park_info_car || !isset($park_info_car['id'])) {
                    $insert_id = $db_out_park->insertOne($out_data);
                } else {
                    $insert_id = $park_info_car['id'];
                }
                */
                $park_where = [
                    ['car_number', '=', $record_info['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=',1],
                    ['is_out', '=', 0],
                    ['del_time', '=', 0],
                ];
                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                if($park_info_car111 && isset($park_info_car111['car_id']) && $park_info_car111['car_id']){
                    $car_access_record['car_id']=$park_info_car111['car_id'];
                    $car_access_record['position_id']=$park_info_car111['position_id'];
                    $car_access_record['garage_id']=$park_info_car111['garage_id'];
                    $car_access_record['position_num']=$park_info_car111['position_num'];
                }
                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $record_info['car_number'];
                $car_access_record['accessType'] = 2;
                $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                $car_access_record['park_time'] =$now_time-$in_park_pay_info['in_time'] ;
                $car_access_record['total'] =round_number($payOrderInfo['money']/100,2);
                $car_access_record['accessTime'] = $now_time;
                $car_access_record['accessMode'] = 4;
                if(isset($in_park_pay_info['out_image_big']) && $in_park_pay_info['out_image_big']){
                    $car_access_record['accessBigImage'] = $in_park_pay_info['out_image_big'];
                }
                if(isset($in_park_pay_info['out_image_small']) && $in_park_pay_info['out_image_small']){
                    $car_access_record['accessImage'] = $in_park_pay_info['out_image_small'];
                }
                if (empty($park_info_car111)){
                    $car_access_record['exception_type'] = 2;
                }else{
                    $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                }
                $car_access_record['park_sys_type'] = 'D3';
                $car_access_record['is_out'] = 1;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                $car_access_record['order_id'] = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                //  $car_access_record['total'] = 0;
                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                $car_access_record['pay_type'] = $this->pay_type[6];
                $car_access_record['update_time'] = $now_time;
                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                if (isset($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $car_access_record['pay_order_id'] = $pay_order_id;
                $car_access_record['pay_time'] = $pay_time;
                if ($park_temp_info && isset($park_temp_info['is_pay_scene']) && intval($park_temp_info['is_pay_scene']) === 0) {
                    $car_access_record['is_paid'] = 2;
                } else {
                    $car_access_record['is_paid'] = 1;
                }
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                    //释放车位
                    $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $record_info['car_number'], 'heartbeat_out_park');
                }
                if($record_id>0){
                    $access_record_where_near = [
                        ['car_number', '=', $record_info['car_number']],
                        ['park_id', '=', $passage_info['village_id']],
                        ['accessType', '=',1],
                        ['is_out', '=', 0],
                        ['del_time', '<', 1],
                        ['record_id', '<', $record_id],
                    ];
                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);

                }
            }
            $res_edit=$db_park_scrcu_record->saveOne(['id'=>$record_info['id']],['over'=>1,'over_time'=>time()]);
            return true;
        }
        try {
            $pay=$service_scrcu->query($record_info['order_number']);
        }catch (\Exception $e){
            $limittime=$record_info['limit']+1;
            if($limittime>=25 && $record_info['over']<1){
                $db_park_scrcu_record->delOne(['id'=>$record_info['id']]);
            }else{
                $retrecord= $db_park_scrcu_record->saveOne(['id'=>$record_info['id']],['limit'=>$limittime]);
            }
            return false;
        }
        fdump_api(['channel_id'=>$channel_id,'order_number'=>$record_info['order_number'],$pay],'000getParkPayRecord',1);
        if (!isset($pay)||empty($pay)||$pay['error']!=0){
            if($pay && ((isset($pay['msg']) && (strpos($pay['msg'],'无此交易')||strpos($pay['msg'],'订单不存在')))||(isset($pay['orderStat'])&&($pay['orderStat']=='04')))){
                $db_park_scrcu_record->delOne(['id'=>$record_info['id']]);
            }
            return false;
        }else{
            $db_in_park = new InPark();
            $db_out_park = new OutPark();
            $db_park_passage = new ParkPassage();
            $db_house_village = new HouseVillage();
            $db_house_village_car_access_record = new HouseVillageCarAccessRecord();
            $now_time=time();
            //查询设备
            $passage_info = $db_park_passage->getFind(['device_number' => $channel_id, 'status' => 1, 'park_sys_type' => $park_sys_type]);
            // 记录车辆进入信息
            $car_access_record = [];
            $car_access_record['channel_id']=$passage_info['id'];
            $car_access_record['channel_number']=$passage_info['channel_number'];
            $car_access_record['channel_name']=$passage_info['passage_name'];
            $village_info = $db_house_village->getOne($passage_info['village_id'], 'village_name');
            $whereInRecord = ['car_number' => $record_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'D3', 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInRecord);
            if (!empty($in_park_pay_info)) {
                $park_temp_info = $db_house_village_parking_temp->getOne(['car_number' => $record_info['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1]);
                //写入车辆入场表
                $park_data = [];
                $park_data['out_time'] = $now_time;
                $park_data['is_out'] = 1;
                $park_data['is_paid'] = 1;
                $res_in=$db_in_park->saveOne(['car_number' => $record_info['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => 'D3', 'is_out' => 0], $park_data);
                $insert_id=0;
                /*
                $out_data = [];
                $out_data['car_number'] = $record_info['car_number'];
                $out_data['park_sys_type'] = $park_sys_type;
                if (!empty($in_park_pay_info)) {
                    $out_data['in_time'] = $in_park_pay_info['in_time'];
                    $out_data['order_id'] = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : '';
                }
                $out_data['out_time'] = $now_time;
                $out_data['park_id'] = $passage_info['village_id'];
                $out_data['pay_type'] = 'scancode';
                $out_data['total'] = round_number($payOrderInfo['money']/100,2);
                $starttime = time() - 30;
                $endtime = time() + 30;
                $park_where = [
                    ['car_number', '=', $record_info['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['out_time', '>=', $starttime],
                    ['out_time', '<=', $endtime],
                ];
                $park_info_car = $db_out_park->getOne($park_where);
                //写入车辆入场表
                if (! $park_info_car || !isset($park_info_car['id'])) {
                    $insert_id = $db_out_park->insertOne($out_data);
                } else {
                    $insert_id = $park_info_car['id'];
                }
               */
                $park_where = [
                    ['car_number', '=', $record_info['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=',1],
                    ['is_out', '=', 0],
                    ['del_time', '=', 0],
                ];
                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                if($park_info_car111 && isset($park_info_car111['car_id']) && $park_info_car111['car_id']){
                    $car_access_record['car_id']=$park_info_car111['car_id'];
                    $car_access_record['position_id']=$park_info_car111['position_id'];
                    $car_access_record['garage_id']=$park_info_car111['garage_id'];
                    $car_access_record['position_num']=$park_info_car111['position_num'];
                }
                $car_access_record['business_type'] = 0;
                $car_access_record['business_id'] = $passage_info['village_id'];
                $car_access_record['car_number'] = $record_info['car_number'];
                $car_access_record['accessType'] = 2;
                $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                $car_access_record['park_time'] =$now_time-$in_park_pay_info['in_time'] ;
                $car_access_record['total'] =round_number($payOrderInfo['money']/100,2);
                $car_access_record['accessTime'] = $now_time;
                $car_access_record['accessMode'] = 4;
                if(isset($in_park_pay_info['out_image_big']) && $in_park_pay_info['out_image_big']){
                    $car_access_record['accessBigImage'] = $in_park_pay_info['out_image_big'];
                }
                if(isset($in_park_pay_info['out_image_small']) && $in_park_pay_info['out_image_small']){
                    $car_access_record['accessImage'] = $in_park_pay_info['out_image_small'];
                }
                if (empty($park_info_car111)){
                    $car_access_record['exception_type'] = 2;
                }else{
                    $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                }
                $car_access_record['park_sys_type'] = 'D3';
                $car_access_record['is_out'] = 1;
                $car_access_record['park_id'] = $passage_info['village_id'];
                $car_access_record['park_name'] = $village_info['village_name']?$village_info['village_name']:'';
                $car_access_record['order_id'] = isset($park_info_car111['order_id']) && $park_info_car111['order_id'] ? $park_info_car111['order_id'] : '';
                //  $car_access_record['total'] = 0;
                // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                $car_access_record['pay_type'] = $this->pay_type[6];
                $car_access_record['update_time'] = $now_time;
                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                if (isset($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $car_access_record['pay_order_id'] = $pay_order_id;
                $car_access_record['pay_time'] = $pay_time;
                if ($park_temp_info && isset($park_temp_info['is_pay_scene']) && intval($park_temp_info['is_pay_scene']) === 0) {
                    $car_access_record['is_paid'] = 2;
                } else {
                    $car_access_record['is_paid'] = 1;
                }
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                if($park_info_car111 && isset($park_info_car111['position_id']) && $park_info_car111['position_id'] && $park_info_car111['car_id']) {
                    //释放车位
                    $whereArr = array('position_id' => $park_info_car111['position_id'], 'use_car_id' => $park_info_car111['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $record_info['car_number'], 'heartbeat_out_park');
                }
                if($record_id>0){
                    $access_record_where_near = [
                        ['car_number', '=', $record_info['car_number']],
                        ['park_id', '=', $passage_info['village_id']],
                        ['accessType', '=',1],
                        ['is_out', '=', 0],
                        ['del_time', '<', 1],
                        ['record_id', '<', $record_id],
                    ];
                    $db_house_village_car_access_record->saveOne($access_record_where_near,['is_out'=>1]);

                }
            }
            $res_edit=$db_park_scrcu_record->saveOne(['id'=>$record_info['id']],['over'=>1,'over_time'=>time()]);
            return true;
        }
    }

    public function addParkInfo($data){
        $serialTime = isset($data['serialTime']) ? $data['serialTime'] : date('YmdH');
        $db_park_passage=new ParkPassage();
        $db_house_village_parking_car=new HouseVillageParkingCar();
        $db_house_village_car_access_record=new HouseVillageCarAccessRecord();
        $db_house_village_parking_temp=new HouseVillageParkingTemp();
        $db_house_new_pay_order=new HouseNewPayOrder();
        $db_in_park=new InPark();
        $db_out_park=new OutPark();
        $passage_info=$db_park_passage->getFind(['id'=>$data['id'],'status'=>1,'village_id'=>$data['village_id']],'*');
        if (empty($passage_info) || $passage_info->isEmpty()){
            fdump_api(['line' => __LINE__, 'data' => $data, 'errMsg' => "当前通道设备不存在"],'A11/serial'.$serialTime,true);
            throw new \think\Exception("当前通道设备不存在");
        }
        $passage_info=$passage_info->toArray();
        $db_house_village_parking_garage=new HouseVillageParkingGarage();
        $garage_id=0;
        if (!empty($passage_info['passage_area'])&&$passage_info['area_type']==2){
            $garage_id=$passage_info['passage_area'];
        }
        if(isset($passage_info['garage_id']) && $passage_info['garage_id']>0){
            $garage_id=$passage_info['garage_id'];
        }
        $comfrom='';
        if(isset($data['comfrom']) && !empty($data['comfrom'])){
            $comfrom=$data['comfrom'];
        }
        $garage_info=array();
        $is_real_garage=1;
        if($garage_id>0){
            $garage_info=$db_house_village_parking_garage->getOne(['garage_id'=>$garage_id]);
            if($garage_info && !$garage_info->isEmpty()){
                $garage_info=$garage_info->toArray();
                if($garage_info['fid']>0 && $garage_info['fid']==$garage_info['garage_id']){
                    $garage_info['fid']=0;
                }
                
                if($garage_info['fid']>0 && $garage_info['fid']!=$garage_info['garage_id'] ){
                    $is_real_garage=0;
                }
            }
        }
        $park_sys_type=$this->checkVillageParkConfig($data['village_id'],$garage_id);
        $now_time=time();
        $record_id=0;
        //查询车辆到期时间
        $car_info=array();
        if (!empty($data['car_number'])&&$data['car_number']!='无') {
            $province = mb_substr($data['car_number'], 0, 1);
            $car_no = mb_substr($data['car_number'], 1);
            $car_info = $db_house_village_parking_car->getFind(['province' => $province, 'car_number' => $car_no, 'village_id' => $passage_info['village_id']]);
            if ($car_info && !$car_info->isEmpty()) {
                $car_info = $car_info->toArray();
            }
        }
        $nowtime=time();
        //月卡车通行
        if (!empty($car_info)&&$car_info['end_time']>$nowtime){
            $accessMode=5;
        }else{
            $accessMode=7;
        }
        $car_number = isset($data['car_number']) && $data['car_number'] ? $data['car_number'] : '';
        $village_id = isset($passage_info['village_id']) && $passage_info['village_id'] ? $passage_info['village_id'] : 0;
        $park_passage_id = isset($passage_info['id']) && $passage_info['id'] ? $passage_info['id'] : 0;
        $device_number = isset($passage_info['device_number']) && $passage_info['device_number'] ? $passage_info['device_number'] : 0;
        $accessTime = strtotime($data['accessTime']);
        $from_id = 0;
        $access_record_id = 0;
        $flow_other_time = 0;
        // 记录车辆进入信息
        $car_access_record = [];
        $car_access_record['channel_id']=$passage_info['id'];
        $car_access_record['channel_number']=$passage_info['channel_number'];
        $car_access_record['channel_name']=$passage_info['passage_name'];
        $car_access_record['user_name'] = empty($data['user_name'])?'':$data['user_name'];
        $car_access_record['user_phone'] =empty($data['user_phone'])?'':$data['user_phone'];
        $car_access_record['business_type'] = 0;
        $car_access_record['business_id'] = $passage_info['village_id'];
        $car_access_record['car_number'] = $car_number;
        $car_access_record['accessTime'] = $accessTime;
        $car_access_record['park_id'] = $passage_info['village_id'];
        $car_access_record['park_name'] = $data['village_name']?$data['village_name']:'';
        $car_access_record['park_sys_type'] = $passage_info['park_sys_type'];
        $car_access_record['accessImage'] = $data['img'];
        if (isset($data['optcode'])){
            $car_access_record['optname'] = $data['optcode'];
        }
        fdump_api(['line' => __LINE__, 'car_access_record' => $car_access_record, 'passage_direction' => $passage_info['passage_direction']],'A11/serial'.$serialTime,true);
        //入场
        if ($passage_info['passage_direction']==1){
            $car_access_record['garage_id']=$garage_id;
            $rightPosition=array();
            if($car_info && isset($car_info['car_id'])){
                $rightPosition=$this->getCarRightInPosition($car_info,$passage_info['village_id'],$garage_id);
                fdump_api(['car_info'=>$car_info,'rightPosition' => $rightPosition], 'park_temp/log_' . $data['car_number'], 1);
                if(!empty($rightPosition)){
                    if($rightPosition['start_time']<=$now_time && $rightPosition['end_time']>=$car_info['end_time']){
                        $car_info['end_time']=$rightPosition['end_time'];
                    }
                    if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $rightPosition['start_time']>$now_time){
                        //车位时间还没开始
                        $car_info['end_time']=0;
                    }
                    if (isset($rightPosition['position_id']) && $rightPosition['position_id']) {
                        $car_access_record['position_id'] = $rightPosition['position_id'];
                    }
                    if (isset($rightPosition['garage_id']) && $rightPosition['garage_id']) {
                        $car_access_record['garage_id'] = $rightPosition['garage_id'];
                    }
                    if (isset($rightPosition['position_num']) && $rightPosition['position_num']) {
                        $car_access_record['position_num'] = $rightPosition['position_num'];
                    }
                }elseif(empty($rightPosition)){
                    //没有可用车位
                    $bind_position_ids=array();
                    if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                        //新的绑定车位 多车多位逻辑
                        $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                        $car_info['end_time']=0;   //没有可用车位时
                    }
                    if($car_info && $car_info['car_position_id']){
                        $bind_position_ids[]=$car_info['car_position_id'];
                    }
                    if(empty($bind_position_ids)){
                        //标记一下无车位进场临时车
                        $car_access_record['flag_status']='temporaryCarNoPosition';
                        $car_access_record['park_car_type']='temporaryCarNoPosition';
                        $car_access_record['car_type']='temporaryCarNoPosition';
                    }
                }
                $car_access_record['car_id']=$car_info['car_id'];
                if ($car_info['end_time']>$now_time){
                    $accessMode=5;
                }else{
                    $accessMode=7;
                }
            }else{
                $car_access_record['flag_status']='temporaryCarNoPosition';
                $car_access_record['park_car_type']='temporaryCarNoPosition';
                $car_access_record['car_type']='temporaryCarNoPosition';
            }
            $order_id = md5($device_number.$car_number.$nowtime);
            $park_data = [];
            $park_data['car_number'] = $car_number;
            $park_data['in_time'] = $accessTime;
            $park_data['order_id'] = $order_id;
            $park_data['in_channel_id'] = $passage_info['id'];
            $park_data['is_paid'] = 0;
            $park_data['park_id'] = $passage_info['village_id'];
            $park_data['park_sys_type'] = $passage_info['park_sys_type'];
            $park_data['park_name'] = $data['village_name'];
            $starttime = time() - 60;
            $endtime = time() + 10;
            $park_where = [
                ['car_number', '=', $data['car_number']],
                ['park_id', '=', $passage_info['village_id']],
                ['in_time', '>=', $starttime],
                ['in_time', '<=', $endtime],
                ['del_time', '=', 0],
            ];
            $park_info_car = $db_in_park->getOne1($park_where);
            fdump_api(['line' => __LINE__, 'park_data' => $park_data, 'park_where' => $park_where], 'A11/serial' . $serialTime, true);
            if (empty($park_info_car)) {
                $insert_id = $db_in_park->insertOne($park_data);
                fdump_api(['line' => __LINE__, 'insert_id' => $insert_id], 'A11/serial' . $serialTime, true);
                $from_id = $insert_id;
            } else {
                $from_id = isset($park_info_car['id']) && intval($park_info_car['id']) > 0 ? intval($park_info_car['id']) : 0;
            }
           
            $order_id = date('YmdHis') . rand(100000, 999999);
            $car_access_record['accessType'] = 1;
            $car_access_record['accessMode'] =$accessMode;
            $car_access_record['exception_type']=1;
            $car_access_record['is_out'] = 0;
            $car_access_record['order_id'] = $order_id;
            $car_access_record['update_time'] = $now_time;
            if (isset($insert_id)&&!empty($insert_id)) {
                $car_access_record['from_id'] = $insert_id;
            }
            fdump_api(['line' => __LINE__, 'car_access_record' => $car_access_record],'A11/serial'.$serialTime,true);
            $record_id = $db_house_village_car_access_record->addOne($car_access_record);
            if($record_id>0 && $is_real_garage>0){
                //将此条之前的 都改掉
                $wherexArr=[];
                $wherexArr[]=['record_id','<',$record_id];
                $wherexArr[]=['car_number','=',$data['car_number']];
                $wherexArr[]=['is_out','=',0];
                $wherexArr[]=['business_id','=',$passage_info['village_id']];
                $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                fdump_api(['line' => __LINE__,'msg' => '更改出场', 'wherexArr' => $wherexArr, 'now_time' => $now_time],'A11/serial'.$serialTime,true);
            }
            $access_record_id = $record_id;
            if(!empty($rightPosition) && $accessMode==5){
                 $whereArr=array('position_id'=>$rightPosition['position_id']);
                 $saveArr=array('use_car_id'=>$car_info['car_id'],'use_start_time'=>$now_time,'use_car_number'=>$data['car_number'],'access_record_id'=>$record_id);
                 $this->updateParkingPositionUse($whereArr,$saveArr,$data['car_number'],'addParkInfo_in_park');
                
            }
        }
        //出场
        $park_time = 0;
        if ($passage_info['passage_direction']==0){
            $outRightPosition=array();
            if($car_info && isset($car_info['car_id'])){
                $outRightPosition=$this->getCarRightOutPosition($car_info,$passage_info['village_id'],$garage_id,$data['car_number']);
                fdump_api(['car_info'=>$car_info,'rightPosition' => $outRightPosition], 'park_temp/log_' . $data['car_number'], 1);
                if(!empty($outRightPosition)){
                    if($outRightPosition['start_time']<$now_time && $outRightPosition['end_time']>$car_info['end_time']){
                        $car_info['end_time']=$outRightPosition['end_time'];
                    }
                    if(isset($outRightPosition['temporary_end_time']) && $outRightPosition['temporary_end_time']>0){
                        $car_info['end_time']=$outRightPosition['temporary_end_time'];
                    }
                    if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids']) && $outRightPosition['start_time']>$now_time){
                        //车位时间还没开始
                        $car_info['end_time']=0;
                    }
                    if (isset($outRightPosition['position_id']) && $outRightPosition['position_id']) {
                        $car_access_record['position_id']=$outRightPosition['position_id'];
                    }
                    if (isset($outRightPosition['garage_id']) && $outRightPosition['garage_id']) {
                        $car_access_record['garage_id']=$outRightPosition['garage_id'];
                    }
                    if (isset($outRightPosition['position_num']) && $outRightPosition['position_num']) {
                        $car_access_record['position_num']=$outRightPosition['position_num'];
                    }
                }else{
                    $car_info['end_time']=0;   //没有可用车位时
                    /*
                    //没有可用车位
                    $bind_position_ids=array();
                    if($car_info && isset($car_info['bind_position_ids']) && !empty($car_info['bind_position_ids'])){
                        //新的绑定车位 多车多位逻辑
                        $bind_position_ids=explode(',',$car_info['bind_position_ids']);
                        $car_info['end_time']=0;   //没有可用车位时
                    }
                    */
                }
                $car_access_record['car_id']=$car_info['car_id'];
            }
            $order_id='';
            $whereInRecord = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0, 'del_time' => 0];
            $in_park_pay_info = $db_in_park->getOne1($whereInRecord);

            fdump_api(['line' => __LINE__,'msg' => '出场', 'in_park_pay_info' => ($in_park_pay_info && !is_array($in_park_pay_info)) ? $in_park_pay_info->toArray() : $in_park_pay_info],'A11/serial'.$serialTime,true);
            if (!empty($in_park_pay_info)) {
                $in_time = isset($in_park_pay_info['in_time']) ? intval($in_park_pay_info['in_time']) : 0;
                $in_time > 0 && $in_time < $now_time && $park_time = $now_time - $in_time;
                if (!empty($in_park_pay_info['pay_order_id'])) {
                    fdump_api(['line' => __LINE__, 'msg' => '存在支付订单记录', 'pay_order_id' => $in_park_pay_info['pay_order_id']], 'A11/serial' . $serialTime, true);
                    //写入车辆入场表
                    $park_data = [];
                    $park_data['out_time'] = $accessTime;
                    $park_data['is_out'] = 1;
                    $park_data['is_paid'] = 1;
                    $park_time > 0 && $park_data['park_time'] = $park_time;
                    $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                    $res_in = $db_in_park->saveOne($whereInOut, $park_data);
                    fdump_api(['line' => __LINE__, 'msg' => '写入车辆入场表', 'whereInOut' => $whereInOut, 'park_data' => $park_data, 'res_in' => $res_in], 'A11/serial' . $serialTime, true);
                    $from_id = 0;
                    $insert_id = 0;
                    /*
                    $out_data = [];
                    $out_data['car_number'] = $data['car_number'];
                    if (!empty($in_park_pay_info)) {
                        $out_data['in_time'] = $in_time;
                        $out_data['order_id'] = $in_park_pay_info['order_id'];
                    }
                    $out_data['out_time'] = $accessTime;
                    $out_data['park_id'] = $passage_info['village_id'];
                    $out_data['pay_type'] = 'cash';
                    $out_data['total'] = $data['price'];
                    $starttime = time() - 60;
                    $endtime = time() + 10;
                    $park_where = [
                        ['car_number', '=', $data['car_number']],
                        ['business_id','=',$passage_info['village_id']],
                        ['out_time', '>=', $starttime],
                        ['out_time', '<=', $endtime],
                    ];
                    $park_info_car = $db_out_park->getOne($park_where);
                    fdump_api(['line' => __LINE__,'msg' => '写入车辆入场表', 'park_where' => $park_where],'A11/serial'.$serialTime,true);
                    //写入车辆入场表
                    if (empty($park_info_car)) {
                        $insert_id= $db_out_park->insertOne($out_data);
                        fdump_api(['line' => __LINE__,'msg' => '添加出场表', 'insert_id' => $insert_id],'A11/serial'.$serialTime,true);
                        $from_id = $insert_id;
                    } else {
                        $from_id = isset($park_info_car['id']) && intval($park_info_car['id']) > 0 ? intval($park_info_car['id']) : 0;
                    }
                    */
                    $park_where = [
                        ['car_number', '=', $data['car_number']],
                        ['park_id', '=', $passage_info['village_id']],
                        ['accessType', '=', 1],
                        ['is_out', '=', 0],
                        ['del_time', '=', 0],
                    ];
                    $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                    $car_access_record['accessType'] = 2;
                    $car_access_record['park_time'] = $data['park_time'] > 0 ? $data['park_time'] : $park_time;
                    $car_access_record['total'] = $data['price'];
                    $car_access_record['accessMode'] = $accessMode;
                    if (empty($park_info_car111)) {
                        $car_access_record['exception_type'] = 2;
                    } else {
                        fdump_api(['line' => __LINE__, 'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']], 'A11/serial' . $serialTime, true);
                        $db_house_village_car_access_record->saveOne(['record_id' => $park_info_car111['record_id']], ['is_out' => 1]);
                    }
                    $car_access_record['is_out'] = 1;
                    $car_access_record['is_paid'] = 1;
                    if ($park_info_car111 && $park_info_car111['order_id']) {
                        $car_access_record['order_id'] = $park_info_car111['order_id'];
                    } else {
                        $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                    }
                    $order_id = $car_access_record['order_id'];
                    $car_access_record['pay_type'] = $this->pay_type[0];
                    $car_access_record['update_time'] = $now_time;
                    $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                    if (isset($insert_id)) {
                        $car_access_record['from_id'] = $insert_id;
                    }
                    $park_time > 0 && $car_access_record['park_time'] = $park_time;
                    fdump_api(['line' => __LINE__, 'msg' => '记录出场', 'car_access_record' => $car_access_record], 'A11/serial' . $serialTime, true);
                    $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                    if (!empty($outRightPosition)) {
                        //释放车位
                        $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                        $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                        $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                    }
                    if ($record_id > 0 && $is_real_garage > 0) {
                        //将此条之前的 都改掉
                        $wherexArr = [];
                        $wherexArr[] = ['record_id', '<', $record_id];
                        $wherexArr[] = ['car_number', '=', $data['car_number']];
                        $wherexArr[] = ['is_out', '=', 0];
                        $wherexArr[] = ['business_id', '=', $passage_info['village_id']];
                        $db_house_village_car_access_record->saveOne($wherexArr, ['is_out' => 1, 'update_time' => $now_time]);
                        fdump_api(['line' => __LINE__, 'msg' => '更改之前记录为已出场', 'record_id' => $record_id, 'wherexArr' => $wherexArr, 'whenow_timerexArr' => $now_time], 'A11/serial' . $serialTime, true);

                    }
                    $access_record_id = $record_id;
                } else{
                    $where_park_temp = ['car_number' => $data['car_number'], 'village_id' => $passage_info['village_id'], 'order_id' => $in_park_pay_info['order_id'],'is_paid' => 1];
                    $park_temp_info = $db_house_village_parking_temp->getOne($where_park_temp);
                    fdump_api(['line' => __LINE__,'msg' => '临时中间表数据', 'where_park_temp' => $where_park_temp, 'park_temp_info' => ($park_temp_info && !is_array($park_temp_info)) ? $park_temp_info->toArray() : $park_temp_info],'A11/serial'.$serialTime,true);
                    if (!empty($park_temp_info)) {
                        //写入车辆入场表
                        $duration = isset($park_temp_info['duration']) ? intval($park_temp_info['duration']) : 0;
                        $park_data = [];
                        $park_data['out_time'] = $accessTime;
                        $park_data['is_out'] = 1;
                        $park_data['is_paid'] = 1;
                        $duration > 0 && $park_time = $duration * 60;
                        $park_time > 0 && $park_data['park_time'] = $park_time;
                        $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                        $res_in=$db_in_park->saveOne($whereInOut, $park_data);
                        fdump_api(['line' => __LINE__,'msg' => '写入车辆入场表', 'whereInOut' => $whereInOut, 'park_data' => $park_data, 'res_in' => $res_in],'A11/serial'.$serialTime,true);
                        $from_id=0;
                        $insert_id=0;
                        /*
                        $out_data = [];
                        $out_data['car_number'] = $data['car_number'];
                        if (!empty($in_park_pay_info)) {
                            $out_data['in_time'] = $in_park_pay_info['in_time'];
                            $out_data['order_id'] = $in_park_pay_info['order_id'];
                        }
                        $out_data['out_time'] = $accessTime;
                        $out_data['park_id'] = $passage_info['village_id'];
                        $out_data['pay_type'] = 'cash';
                        $out_data['total'] = $data['price'];
                        $starttime = time() - 60;
                        $endtime = time() + 10;
                        $park_where = [
                            ['car_number', '=', $data['car_number']],
                            ['park_id', '=', $passage_info['village_id']],
                            ['out_time', '>=', $starttime],
                            ['out_time', '<=', $endtime],
                        ];
                        fdump_api(['line' => __LINE__,'msg' => '写入车辆出场表', 'park_where' => $park_where, 'out_data' => $out_data],'A11/serial'.$serialTime,true);
                        $park_info_car = $db_out_park->getOne($park_where);
                        //写入车辆入场表
                        if (empty($park_info_car)) {
                            $insert_id= $db_out_park->insertOne($out_data);
                            fdump_api(['line' => __LINE__,'msg' => '添加车辆出场表', 'insert_id' => $insert_id],'A11/serial'.$serialTime,true);
                            $from_id = $insert_id;
                        } else {
                            $from_id = isset($park_info_car['id']) && intval($park_info_car['id']) > 0 ? intval($park_info_car['id']) : 0;
                        }
                        */
                        $park_where = [
                            ['car_number', '=', $data['car_number']],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',1],
                            ['is_out', '=', 0],
                            ['del_time', '=', 0],
                        ];
                        $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                        $car_access_record['accessType'] = 2;
                        $car_access_record['coupon_id'] = isset($park_temp_info['coupon_id'])?$park_temp_info['coupon_id']:0;
                        $car_access_record['park_time'] =$data['park_time']>0 ? $data['park_time']:$park_time;
                        $car_access_record['total'] =$data['price'];
                        if (empty($park_info_car111)){
                            $car_access_record['exception_type'] = 2;
                        }else{
                            fdump_api(['line' => __LINE__,'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']],'A11/serial'.$serialTime,true);
                            $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                        }
                        $car_access_record['accessMode'] = $accessMode;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['is_paid'] = 1;
                        if($park_info_car111 && $park_info_car111['order_id']){
                            $car_access_record['order_id'] = $park_info_car111['order_id'];
                        }else{
                            $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                        }
                        $order_id=$car_access_record['order_id'];
                        $car_access_record['pay_type'] = $this->pay_type[0];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $park_time > 0 && $car_access_record['park_time'] = $park_time;
                        fdump_api(['line' => __LINE__,'msg' => '添加出场记录', 'car_access_record' => $car_access_record],'A11/serial'.$serialTime,true);
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                        if(!empty($outRightPosition)) {
                            //释放车位
                            $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                            $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                        }
                        if($record_id>0 && $is_real_garage>0){
                            //将此条之前的 都改掉
                            $wherexArr=[];
                            $wherexArr[]=['record_id','<',$record_id];
                            $wherexArr[]=['car_number','=',$data['car_number']];
                            $wherexArr[]=['is_out','=',0];
                            $wherexArr[]=['business_id','=',$passage_info['village_id']];
                            $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                        }
                        $access_record_id = $record_id;
                        fdump_api([$record_id,$car_access_record],'open_gate_0519',1);
                    } else{
                        //写入车辆入场表
                        $park_data = [];
                        $park_data['out_time'] = $accessTime;
                        $park_data['is_out'] = 1;
                        $park_data['is_paid'] = 1;
                        $park_time > 0 && $park_data['park_time'] = $park_time;
                        $whereInOut = ['car_number' => $data['car_number'], 'park_id' => $passage_info['village_id'], 'park_sys_type' => $park_sys_type, 'is_out' => 0];
                        $res_in=$db_in_park->saveOne($whereInOut, $park_data);
                        fdump_api(['line' => __LINE__,'msg' => '写入车辆入场表', 'whereInOut' => $whereInOut, 'park_data' => $park_data, 'res_in' => $res_in],'A11/serial'.$serialTime,true);
                        $from_id=0;
                        $insert_id=0;
                        /*
                        $out_data = [];
                        $out_data['car_number'] = $data['car_number'];
                        $out_data['out_time'] = $accessTime;
                        $out_data['park_id'] = $passage_info['village_id'];
                        $out_data['total'] =$data['price'];
                        $out_data['pay_type'] = 'cash';
                        $starttime = time() - 60;
                        $endtime = time() + 10;
                        $park_where = [
                            ['car_number', '=', $data['car_number']],
                            ['park_id', '=', $passage_info['village_id']],
                            ['out_time', '>=', $starttime],
                            ['out_time', '<=', $endtime],
                        ];
                        fdump_api(['line' => __LINE__,'msg' => '写入车辆出场表', 'park_where' => $park_where, 'out_data' => $out_data],'A11/serial'.$serialTime,true);
                        $park_info_car = $db_out_park->getOne($park_where);
                        //写入车辆入场表
                        if (empty($park_info_car)) {
                            $insert_id=$db_out_park->insertOne($out_data);
                            fdump_api(['line' => __LINE__,'msg' => '添加车辆出场表', 'insert_id' => $insert_id],'A11/serial'.$serialTime,true);
                            $from_id = $insert_id;
                        } else {
                            $from_id = isset($park_info_car['id']) && intval($park_info_car['id']) > 0 ? intval($park_info_car['id']) : 0;
                        }
                        */
                        $park_where = [
                            ['car_number', '=', $data['car_number']],
                            ['park_id', '=', $passage_info['village_id']],
                            ['accessType', '=',1],
                            ['is_out', '=', 0],
                            ['del_time', '=', 0],
                        ];
                        $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);
                        $car_access_record['accessType'] = 2;
                        $car_access_record['accessMode'] = $accessMode;
                        $car_access_record['is_out'] = 1;
                        $car_access_record['is_paid'] = 1;
                        if (isset($park_info_car111['order_id'])&&!empty($park_info_car111['order_id'])){
                            $car_access_record['order_id'] = $park_info_car111['order_id'];
                        }else{
                            $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                        }
                        $order_id=$car_access_record['order_id'];
                        if (empty($park_info_car111)){
                            $car_access_record['exception_type'] = 2;
                        }else{
                            fdump_api(['line' => __LINE__,'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']],'A11/serial'.$serialTime,true);
                            $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                        }
                        $car_access_record['total'] = $data['price'];
                        $car_access_record['park_time'] = $data['park_time']>0 ? $data['park_time']:$park_time;
                        $car_access_record['pay_type'] = $this->pay_type[0];
                        $car_access_record['update_time'] = $now_time;
                        $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                        if (isset($insert_id)) {
                            $car_access_record['from_id'] = $insert_id;
                        }
                        $park_time > 0 && $car_access_record['park_time'] = $park_time;
                        fdump_api(['line' => __LINE__,'msg' => '添加出场记录', 'car_access_record' => $car_access_record],'A11/serial'.$serialTime,true);
                        $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                        if(!empty($outRightPosition)) {
                            //释放车位
                            $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                            $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                            $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                        }
                        if($record_id>0  && $is_real_garage>0){
                            //将此条之前的 都改掉
                            $wherexArr=[];
                            $wherexArr[]=['record_id','<',$record_id];
                            $wherexArr[]=['car_number','=',$data['car_number']];
                            $wherexArr[]=['is_out','=',0];
                            $wherexArr[]=['business_id','=',$passage_info['village_id']];
                            $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                            fdump_api(['line' => __LINE__,'msg' => '更改之前记录为已出场', 'record_id' => $record_id, 'wherexArr' => $wherexArr, 'whenow_timerexArr' => $now_time],'A11/serial'.$serialTime,true);
                        }
                        $access_record_id = $record_id;
                    }
                }
                $flow_other_time = $in_time;
            }
            else{
                $from_id=0;
                $insert_id=0;
                /*
                $out_data = [];
                $out_data['car_number'] = $data['car_number'];
                $out_data['out_time'] = $accessTime;
                $out_data['park_id'] = $passage_info['village_id'];
                $out_data['total'] =$data['price'];
                $out_data['pay_type'] = 'cash';
                $starttime = time() - 60;
                $endtime = time() + 10;
                $park_where = [
                    ['car_number', '=', $data['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['out_time', '>=', $starttime],
                    ['out_time', '<=', $endtime],
                ];
                fdump_api(['line' => __LINE__,'msg' => '查询最近1分钟出场记录', 'park_where' => $park_where, 'out_data' => $out_data],'A11/serial'.$serialTime,true);
                $park_info_car = $db_out_park->getOne($park_where);
                //写入车辆入场表
                if (empty($park_info_car)) {
                    $insert_id=$db_out_park->insertOne($out_data);
                    fdump_api(['line' => __LINE__,'msg' => '添加车辆出场表', 'insert_id' => $insert_id],'A11/serial'.$serialTime,true);
                    $from_id = $insert_id;
                } else {
                    $from_id = isset($park_info_car['id']) && intval($park_info_car['id']) > 0 ? intval($park_info_car['id']) : 0;
                }
                */
                $park_where = [
                    ['car_number', '=', $data['car_number']],
                    ['park_id', '=', $passage_info['village_id']],
                    ['accessType', '=',1],
                    ['is_out', '=', 0],
                    ['del_time', '=', 0],
                ];
                $park_info_car111 = $db_house_village_car_access_record->getOne($park_where);

                $car_access_record['accessType'] = 2;
                $car_access_record['accessMode'] = $accessMode;
                $car_access_record['exception_type'] = 2;
                $car_access_record['is_out'] = 1;
                $car_access_record['is_paid'] = 1;
                if (isset($park_info_car111['order_id'])&&!empty($park_info_car111['order_id'])){
                    $car_access_record['order_id'] = $park_info_car111['order_id'];
                }else{
                    $car_access_record['order_id'] = date('YmdHis') . rand(100000, 999999);
                }
                $order_id=$car_access_record['order_id'];
                if (empty($park_info_car111)){
                    $car_access_record['exception_type'] = 2;
                }else{
                    fdump_api(['line' => __LINE__,'msg' => '查询对应出场记录', 'record_id' => $park_info_car111['record_id']],'A11/serial'.$serialTime,true);
                    $db_house_village_car_access_record->saveOne(['record_id'=>$park_info_car111['record_id']],['is_out'=>1]);
                }
                $car_access_record['total'] = $data['price'];
                $car_access_record['park_time'] = $data['park_time']>0 ? $data['park_time']:$park_time;
                $car_access_record['pay_type'] = $this->pay_type[0];
                $car_access_record['update_time'] = $now_time;
                $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $passage_info['village_id']);
                if (isset($insert_id)) {
                    $car_access_record['from_id'] = $insert_id;
                }
                $park_time > 0 && $car_access_record['park_time'] = $park_time;
                fdump_api(['line' => __LINE__,'msg' => '添加出场记录', 'car_access_record' => $car_access_record],'A11/serial'.$serialTime,true);
                $record_id = $db_house_village_car_access_record->addOne($car_access_record);

                if(!empty($outRightPosition)) {
                    //释放车位
                    $whereArr = array('position_id' => $outRightPosition['position_id'], 'use_car_id' => $car_info['car_id']);
                    $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                    $this->updateParkingPositionUse($whereArr, $saveArr, $data['car_number'], 'addParkInfo_out_park');
                }
                if($record_id>0  && $is_real_garage>0){
                    //将此条之前的 都改掉
                    $wherexArr=[];
                    $wherexArr[]=['record_id','<',$record_id];
                    $wherexArr[]=['car_number','=',$data['car_number']];
                    $wherexArr[]=['is_out','=',0];
                    $wherexArr[]=['business_id','=',$passage_info['village_id']];
                    $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$now_time]);
                    fdump_api(['line' => __LINE__,'msg' => '更改之前记录为已出场', 'record_id' => $record_id, 'wherexArr' => $wherexArr, 'whenow_timerexArr' => $now_time],'A11/serial'.$serialTime,true);
                }
                $access_record_id = $record_id;
            }
        }

        // 车辆进出方向 1-进 2-出
        if ($passage_info['passage_direction'] == 1) {
            $flow_direction = 1;
            $is_out = 0;
            $is_paid = 0;
            $pay_type = '';
        }
        if($passage_info['passage_direction'] == 0) {
            $flow_direction = 2;
            $is_out = 1;
            $is_paid = 1;
            $pay_type = $this->pay_type[0];
        }
        $uniqueArr = [
            'car_number' => $car_number, 'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => $flow_direction, 'park_sys_type' => $park_sys_type, 'is_out' => $is_out, 'is_paid' => $is_paid
        ];
        $unique_id = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereUnique = [
            'unique_id' => $unique_id,
        ];
        $db_house_village_car_flow_record = new HouseVillageCarFlowRecord();
        $flowRecord = $db_house_village_car_flow_record->getOne($whereUnique, 'id, from_id');
        $total = isset($car_access_record['total']) && floatval($car_access_record['total']) > 0 ? $car_access_record['total'] : 0;
        if ($total > 0) {
            $total = isset($data['price']) && floatval($data['price']) > 0 ? $data['price'] : 0;
        }
        $carHandleGetInfoService = new CarHandleGetInfoService();
        $village_info = $carHandleGetInfoService->getVillageInfo($village_id);
        $park_name = isset($village_info['village_name']) && trim($village_info['village_name']) ? trim($village_info['village_name']) : '';
        $flowData = [
            'village_id' => $village_id, 'garage_id' => $garage_id, 'park_passage_id' => $park_passage_id,
            'flow_direction' => $flow_direction, 'device_number' => $device_number, 'park_sys_type' => $park_sys_type,
            'car_number' => $car_number, 'flow_time' => $now_time, 'unique_id' => $unique_id, 'order_id' => $order_id,
            'is_out' => $is_out, 'uid' => 0, 'park_name' => $park_name, 'total' => $total, 'is_paid' => $is_paid,
        ];
        $position_id = isset($car_access_record['position_id']) && intval($car_access_record['position_id']) > 0 ? intval($car_access_record['position_id']) : 0;
        $position_id && $flowData['position_id'] = $position_id;
        $access_record_id && $flowData['access_record_id'] = $access_record_id;
        $from_id && $flowData['from_id'] = $from_id;
        $park_time > 0 && $flowData['park_time'] = $park_time;
        $flow_other_time > 0 && $flowData['flow_other_time'] = $flow_other_time;
        if (isset($flowRecord['id']) && $flowRecord['id']) {
            $flowData['update_time'] = time();
            $db_house_village_car_flow_record->updateThis($whereUnique, $flowData);
        } else {
            $flowData['add_time'] = time();
            $db_house_village_car_flow_record->add($flowData);
        }
        return $record_id;
    }


    /**
     * 查询母车位下对应子车位信息列表
     * @author:zhubaodi
     * @date_time: 2022/7/29 9:08
     */
    public function getChildrenPositionList($data){
        $db_house_village_parking_position=new HouseVillageParkingPosition();
        $data1=[];
        $data1['list'] =[];
        $data1['total_limit'] = 0;
        $data1['count'] =0;
        $data1['children_arr_info'] ='';
        
        $where=[];
        $where['village_id']=$data['village_id'];
        $where['position_id']=$data['position_id'];
        $position_info=$db_house_village_parking_position->getFind($where);
        if (empty($position_info)||$position_info['children_type']==2){
            return $data1;
        }
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($position_info['garage_id'],$data['village_id']);
        if ($house_village_park_config['children_position_type']!=1){
            return $data1;
        }
        $children_where=[];
        $children_where['pp.parent_position_id']=$position_info['position_id'];
        $children_where['pp.garage_id']=$position_info['garage_id'];
        $children_where['pp.children_type']=2;
        if (!isset($data['page'])){
            $data['page']=0;
        }
        if (!isset($data['limit'])){
            $data['limit']=0;
        }
        $list=$db_house_village_parking_position->getLists($children_where,'*',$data['page'],$data['limit']);
        if (!empty($list)){
            $list=$list->toArray();
            if (!empty($list)){
                foreach ($list as &$vv){
                    if (empty($vv['position_area'])){
                        $vv['position_area']='0.00';
                    }
                    if ($vv['position_status']==1){
                        $vv['position_status_txt']='空置';
                    }
                    if ($vv['position_status']==2){
                        $vv['position_status_txt']='已使用';
                    }
                    if ($vv['position_pattern']==1){
                        $vv['position_pattern_txt']='真实车位';
                    }else{
                        $vv['position_pattern_txt']='虚拟车位';
                    }
                }
            }
        }
        $count = $db_house_village_parking_position->getCount($children_where);

        $children_arr_where=[];
        $children_arr_where['parent_position_id']=$position_info['position_id'];
        $children_arr_where['garage_id']=$position_info['garage_id'];
        $children_arr_where['children_type']=2;
        $children_arr = $db_house_village_parking_position->getColumn($children_arr_where,'position_num');
        $children_arr_info='';
        if (!empty($children_arr)){
            $children_arr_info=implode(',',$children_arr);
        }
        $data1=[];
        $data1['list'] = $list;
        $data1['total_limit'] = $data['limit'];
        $data1['count'] = $count;
        $data1['children_arr_info'] =$children_arr_info;
        return  $data1;
    }

    /**
     * 纪录遥控上报信息
     * @author:zhubaodi
     * @date_time: 2022/12/5 9:33
     */
    public function addSerialData($data){
        $db_park_passage = new ParkPassage();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_park_plateresult_log=new ParkPlateresultLog();
        $db_park_serial_log=new ParkSerialLog();
        $db_house_village=new HouseVillage();
        if (empty($data['serialno'])){
            return false;
        }
        $passage_info=$db_park_passage->getFind(['device_number'=>$data['serialno'],'status'=>1]);
        if (empty($passage_info) || $passage_info->isEmpty()){
            return false;
        }
        $passage_info=$passage_info->toArray();
        $garage_id=0;
        if (isset($passage_info['garage_id'])) {
            $garage_id = $passage_info['garage_id'];
        }
        if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $garage_id = $garage_id > 0 ? $garage_id : 0;
        $device_numberArr=array();
        $serialnoArr=array();
        if($passage_info['passage_type']==2){
            $passage_info_list=$db_park_passage->getList(['village_id'=>$passage_info['village_id'],'status'=>1,'park_sys_type'=>$passage_info['park_sys_type'],'passage_type'=>2]);
            if($passage_info_list && !$passage_info_list->isEmpty()){
                $passage_info_list=$passage_info_list->toArray();
                foreach ($passage_info_list as $kk=>$vv){
                    $device_numberArr[$vv['device_number']]=$vv;
                    $serialnoArr[]=$vv['device_number'];
                }
            }
        }
        $no_code=$data_code=$operation_code=$function_code=[];
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$passage_info['village_id']);
        if (empty($house_village_park_config)){
            return false;
        }

        $whereArr=array();
        $timeTmp=time()-7200;  //2小时前数据
        $whereArr[]=['channel_id','=',$data['serialno']];
        $whereArr[]=['village_id','=',$passage_info['village_id']];
        $whereArr[]=['add_time','<=',$timeTmp];
        $db_park_serial_log->del_datas($whereArr); //删掉2小时前数据

        $whereArr=array();
        $timeTmp=time()-3600;  //1小时内
        $whereArr[]=['channel_id','=',$data['serialno']];
        $whereArr[]=['village_id','=',$passage_info['village_id']];
        $whereArr[]=['content_de','=',$data['msg']];
        $whereArr[]=['add_time','>',$timeTmp];
        $haveResObj=$db_park_serial_log->get_one($whereArr);
        $ishaveExist=false;
        $timeTmp=time()-10;
        if($haveResObj && !$haveResObj->isEmpty()){
            $haveRes= $haveResObj->toArray();
            if(!empty($haveRes) && $haveRes['add_time']>=$timeTmp){
                //10秒内多次提交同样的数据过滤掉
                fdump_api(['msg' => '5秒内多次提交同样的数据过滤掉', 'haveRes' => $haveRes], 'A11/addSerialData'.$data['serialno'], 1);
                $ishaveExist=true;
            }
        }
        $village_info=$db_house_village->getOne($passage_info['village_id'],'village_name');
        $arr=[
            'channel_id'=>$data['serialno'],
            'content'=>$data['content'],
            'content_de'=>$data['msg'],
            'village_id'=>$passage_info['village_id'],
            'park_sys_type'=>$house_village_park_config['park_sys_type'],
            'add_time'=>time()
        ];
        $res=$db_park_serial_log->add($arr);
        if (stripos($data['msg'],'AFAB')!==false){
            $msg=str_replace('AFAB','AF,AB',$data['msg']);
            $arr_msg=explode(',',$msg);
            if (!empty($arr_msg)){
                foreach ($arr_msg as $v){
                    $function_code[]=substr($v,6,2);
                    $operation_code[]=substr($v,8,2);
                    $data_code[]=substr($v,10,2);
                    $no_code[]=substr($v,12,8);
                }
            }
        }else{
            $function_code[]=substr($data['msg'],6,2);
            $operation_code[]=substr($data['msg'],8,2);
            $data_code[]=substr($data['msg'],10,2);
            $no_code[]=substr($data['msg'],12,8);
        }
        if (!in_array('78',$function_code)||!in_array('01',$operation_code)||!in_array('06',$data_code)){
            return false;
        }
        if($ishaveExist){
            //10秒内多次提交同样的数据过滤掉
            return false;
        }
        if (empty($no_code)){
            $optcode='';
        }else{
            foreach ($no_code as $v){
                if ($v!='00000000'){
                    $optcode=base_convert($v,16,10);
                    break;
                }
            }
        }

        $where=[
            ['channel_id','=',$data['serialno']],
            ['village_id','=',$passage_info['village_id']],
            ['park_sys_type','=',$house_village_park_config['park_sys_type']],
            ['car_number','<>','_无_'],
        ];
        if(!empty($serialnoArr)){
            $where=[
                ['channel_id','in',$serialnoArr],
                ['village_id','=',$passage_info['village_id']],
                ['park_sys_type','=',$house_village_park_config['park_sys_type']],
                ['car_number','<>','_无_'],
            ];
        }

        $plateresult=$db_park_plateresult_log->get_one($where);
        if (!isset($plateresult['car_number']) || !$plateresult['car_number']) {
            return false;
        }

        if($device_numberArr && isset($device_numberArr[$plateresult['channel_id']])){
            $passage_info=$device_numberArr[$plateresult['channel_id']];
        }
        $data_add=[
            'id'=>$passage_info['id'],
            'village_id'=>$passage_info['village_id'],
            'car_number'=> '无',
            'accessTime'=>date('Y-m-d H:i:s'),
            'user_phone'=>'',
            'user_name'=>'',
            'village_name'=>$village_info['village_name'],
            'img'=>'',
            'price'=>0,
            'park_time'=>0,
            'optcode'=>$optcode,
        ];
        if (!empty($plateresult)&&($plateresult['add_time']+300)>time()){
            if ($passage_info['passage_direction']==0){
                $service_a11=new A11Service();
                $temp=[
                    'car_number'=>$plateresult['car_number'],
                    'village_id'=>$passage_info['village_id'],
                    'device_number'=> $data['serialno'],
                    'uid'=>'',
                ];
                try {
                    $pay=$service_a11->get_temp_pay($temp,$garage_id);
                }catch (\Exception $e) {
                    $txt = $e->getMessage();
                    fdump_api(['msg' => '错误', '$txt' => $txt ], 'A11/addSerialData'.$data['serialno'], 1);
                    $pay=[];
                }
            }else{
                $pay=[];
            }
            if (empty($pay)){
                $price=0;
                $park_time=0;
            }else{
                $price=$pay['pay_money'];
                $park_time=$pay['park_time'];
            }
            $data_add['price']=$price;
            $data_add['park_time']=$park_time;
            $data_add['car_number']=$plateresult['car_number'];
        }
        if (!$data_add['price'] || $data_add['price'] <= 0) {
            $db_in_park = new InPark();
            $db_house_village_parking_temp = new HouseVillageParkingTemp();
            $park_sys_type = $this->checkVillageParkConfig($passage_info['village_id'],$garage_id);
            $whereInPark = [
                'car_number' => $plateresult['car_number'],
                'park_id' => $passage_info['village_id'],
                'park_sys_type' => $park_sys_type,
                'is_out' => 0,
                'del_time' => 0
            ];
            $in_park_pay_info = $db_in_park->getOne1($whereInPark, 'order_id');
            if (!empty($in_park_pay_info)) {
                $order_id = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : 0;
                $park_temp_id = isset($pay) && isset($pay['park_temp_id']) && $pay['park_temp_id'] ? $pay['park_temp_id'] : 0;
                $whereTemp = [];
                $whereTemp[] = ['car_number', '=', $plateresult['car_number']];
                $whereTemp[] = ['village_id', '=', $passage_info['village_id']];
                $whereTemp[] = ['order_id', '=', $order_id];
                $whereTemp[] = ['is_paid',  '=', 0];
                if ($park_temp_id) {
                    $whereTemp[] = ['id', '<>', $park_temp_id];
                }
                $park_temp_info = $db_house_village_parking_temp->getOne($whereTemp);
                $total = isset($park_temp_info['total']) && $park_temp_info['total'] ? $park_temp_info['total'] : 0;
                $data_add['price'] = $total;
            }
        }
        fdump_api(['msg' => '相关信息', '$data_add' => $data_add], 'A11/addSerialData'.$data['serialno'], 1);
        try {
            $res1 = $this->addParkInfo($data_add);
        }catch (\Exception $e) {
            $txt  = $e->getMessage();
            $res1 = 0;
            fdump_api(['msg' => '错误2', '$txt' => $txt ], 'A11/addSerialData'.$data['serialno'], 1);
        }
        if ($res1){
            $showscreen_data=[];
            $showscreen_data['passage']=$passage_info;
            $showscreen_data['village_id']=$passage_info['village_id'];
            $showscreen_data['car_number']= $plateresult['car_number'];
            $showscreen_data['channel_id']= $data['serialno'];
            $showscreen_data['voice_content']= 11;
            $showscreen_data['content']= '岗亭人工确认，请通行';
            $showscreen_data['car_type']= 'temporary_type';
            $service_A11=new A11Service();
            fdump_api(['showscreen_data' => $showscreen_data], 'A11/addSerialData'.$data['serialno'], 1);
            $service_A11->addParkShowScreenLog($showscreen_data);
        }
        return  true;
    }

    /**
     * 纪录遥控上报信息
     * *端口触发信息推送
     * @author:Li
     * @date_time: 2023/03/09 9:33
     */
    public function addPortData($data){
        $db_park_passage = new ParkPassage();
        $db_house_village_park_config=new HouseVillageParkConfig();
        $db_park_plateresult_log=new ParkPlateresultLog();
        $db_house_village=new HouseVillage();
        $db_park_open_log=new ParkOpenLog();
        $datahour=date('mdH');
        fdump_api(['data' => $data], 'A11/00addPortData'.$datahour, 1);
        if (empty($data['serialno']) || $data['opt_source']!==0){
            /***opt_source 0表示开闸***/
            return false;
        }
        $passage_info=$db_park_passage->getFind(['device_number'=>$data['serialno'],'status'=>1]);
        if (empty($passage_info) || $passage_info->isEmpty()){
            return false;
        }
        $passage_info=$passage_info->toArray();
        $garage_id=0;
        if (isset($passage_info['garage_id'])) {
            $garage_id = $passage_info['garage_id'];
        }
        if ($garage_id < 1 && $passage_info['area_type'] == 2 && !empty($passage_info['passage_area'])) {
            $garage_id = intval($passage_info['passage_area']);
        }
        $garage_id = $garage_id > 0 ? $garage_id : 0;
        $device_numberArr=array();
        $serialnoArr=array();
        if($passage_info['passage_type']==2){
            $passage_info_list=$db_park_passage->getList(['village_id'=>$passage_info['village_id'],'status'=>1,'park_sys_type'=>$passage_info['park_sys_type'],'passage_type'=>2]);
            if($passage_info_list && !$passage_info_list->isEmpty()){
                $passage_info_list=$passage_info_list->toArray();
                foreach ($passage_info_list as $kk=>$vv){
                    $device_numberArr[$vv['device_number']]=$vv;
                    $serialnoArr[]=$vv['device_number'];
                }
            }
        }
        fdump_api(['passage_info' => $passage_info,'device_numberArr'=>$device_numberArr], 'A11/00addPortData'.$datahour, 1);
        $houseVillageParkGarageConfigService=new HouseVillageParkGarageConfigService();
        $house_village_park_config=$houseVillageParkGarageConfigService->getVillageParkConfig($garage_id,$passage_info['village_id']);
        if (empty($house_village_park_config) ){
            return false;
        }
        fdump_api(['house_village_park_config' => $house_village_park_config], 'A11/00addPortData'.$datahour, 1);
        $village_info=$db_house_village->getOne($passage_info['village_id'],'village_name');
        $where=[
            ['channel_id','=',$data['serialno']],
            ['village_id','=',$passage_info['village_id']],
            ['park_sys_type','=',$house_village_park_config['park_sys_type']],
            ['car_number','<>','_无_'],
        ];
        if(!empty($serialnoArr)){
            $where=[
                ['channel_id','in',$serialnoArr],
                ['village_id','=',$passage_info['village_id']],
                ['park_sys_type','=',$house_village_park_config['park_sys_type']],
                ['car_number','<>','_无_'],
            ];
        }
        $plateresult=$db_park_plateresult_log->get_one($where);
        if($plateresult && !$plateresult->isEmpty()){
            $plateresult= $plateresult->toArray();
        }
        fdump_api(['where'=>$where,'plateresult' => $plateresult], 'A11/00addPortData'.$datahour, 1);
        if (!isset($plateresult['car_number']) || !$plateresult['car_number']) {
            return false;
        }
        if($device_numberArr && isset($device_numberArr[$plateresult['channel_id']])){
            $passage_info=$device_numberArr[$plateresult['channel_id']];
        }
        fdump_api(['new_passage_info' => $passage_info], 'A11/00addPortData'.$datahour, 1);
        $data_add=[
            'id'=>$passage_info['id'],
            'village_id'=>$passage_info['village_id'],
            'car_number'=> '无',
            'accessTime'=>date('Y-m-d H:i:s'),
            'user_phone'=>'',
            'user_name'=>'',
            'village_name'=>$village_info['village_name'],
            'img'=>'',
            'price'=>0,
            'park_time'=>0,
            'optcode'=>'',
        ];
        $nowtime=time();
        if (!empty($plateresult)&&($plateresult['add_time']+300)>$nowtime){
            if ($passage_info['passage_direction']==0){
                $service_a11=new A11Service();
                $temp=[
                    'car_number'=>$plateresult['car_number'],
                    'village_id'=>$passage_info['village_id'],
                    'device_number'=> $data['serialno'],
                    'uid'=>'',
                ];
                try {
                    $pay=$service_a11->get_temp_pay($temp,$garage_id);
                    fdump_api(['temp'=>$temp,'pay' => $pay ], 'A11/00addPortData'.$datahour, 1);
                }catch (\Exception $e) {
                    $txt = $e->getMessage();
                    fdump_api(['msg' => '错误', '$txt' => $txt ], 'A11/00addPortData'.$datahour, 1);
                    $pay=[];
                }
            }else{
                $pay=[];
            }
            if (empty($pay)){
                $price=0;
                $park_time=0;
            }else{
                $price=$pay['pay_money'];
                $park_time=$pay['park_time'];
            }
            $data_add['price']=$price;
            $data_add['park_time']=$park_time;
            $data_add['car_number']=$plateresult['car_number'];
        }
        if (!$data_add['price'] || $data_add['price'] <= 0) {
            $db_in_park = new InPark();
            $db_house_village_parking_temp = new HouseVillageParkingTemp();
            $park_sys_type = $this->checkVillageParkConfig($passage_info['village_id'],$garage_id);
            $whereInPark = [
                'car_number' => $plateresult['car_number'],
                'park_id' => $passage_info['village_id'],
                'park_sys_type' => $park_sys_type,
                'is_out' => 0,
                'del_time' => 0
            ];
            $in_park_pay_info = $db_in_park->getOne1($whereInPark, 'order_id');
            if (!empty($in_park_pay_info)) {
                $order_id = isset($in_park_pay_info['order_id']) && $in_park_pay_info['order_id'] ? $in_park_pay_info['order_id'] : 0;
                $park_temp_id = isset($pay) && isset($pay['park_temp_id']) && $pay['park_temp_id'] ? $pay['park_temp_id'] : 0;
                $whereTemp = [];
                $whereTemp[] = ['car_number', '=', $plateresult['car_number']];
                $whereTemp[] = ['village_id', '=', $passage_info['village_id']];
                $whereTemp[] = ['order_id', '=', $order_id];
                $whereTemp[] = ['is_paid',  '=', 0];
                if ($park_temp_id) {
                    $whereTemp[] = ['id', '<>', $park_temp_id];
                }
                $park_temp_info = $db_house_village_parking_temp->getOne($whereTemp);
                $total = isset($park_temp_info['total']) && $park_temp_info['total'] ? $park_temp_info['total'] : 0;
                $data_add['price'] = $total;
            }
        }
        fdump_api(['msg' => '相关信息', 'data_add' => $data_add], 'A11/00addPortData'.$datahour, 1);
        try {
            $res1 = $this->addParkInfo($data_add);
        }catch (\Exception $e) {
            $txt  = $e->getMessage();
            $res1 = 0;
            fdump_api(['msg' => '错误2', '$txt' => $txt ], 'A11/00addPortData'.$datahour, 1);
        }
        if ($res1){
            $showscreen_data=[];
            $showscreen_data['passage']=$passage_info;
            $showscreen_data['village_id']=$passage_info['village_id'];
            $showscreen_data['car_number']= $plateresult['car_number'];
            $showscreen_data['channel_id']= $passage_info['device_number'];
            $showscreen_data['voice_content']= 11;
            $showscreen_data['content']= '岗亭人工确认，请通行';
            $showscreen_data['car_type']= 'temporary_type';
            $service_A11=new A11Service();
            fdump_api(['showscreen_data' => $showscreen_data], 'A11/00addPortData'.$datahour, 1);
            $service_A11->addParkShowScreenLog($showscreen_data);
            $park_type=1;
            if($passage_info['passage_direction']==0){
                $park_type=2;
            }
            $park_log_data = [];
            $park_log_data['car_number'] = $plateresult['car_number'];
            $park_log_data['channel_id'] = $passage_info['device_number'];
            $park_log_data['park_type']  = $park_type;   //1进场 2出场
            $park_log_data['open_type']=3;
            $park_log_data['add_time']   = time();
            $log_id= $db_park_open_log->add($park_log_data);
        }
        return  true;
    }
    public function getOneParkingPosition($where,$field='*'){
        $houseVillageParkingPosition= new HouseVillageParkingPosition();
        $parkingPosition=$houseVillageParkingPosition->getFind($where,$field);
        if($parkingPosition && !$parkingPosition->isEmpty()){
            $parkingPosition=$parkingPosition->toArray();
        }else{
            $parkingPosition=array();
        }
        return $parkingPosition;
    }

    /***
    *** 临时车支付后处理相关逻辑
     **/
    public function handleTemporaryCarAfterPayBusiness($parameter=array()){
        $root=dirname(dirname(dirname(__DIR__)));
        $root=dirname(dirname($root));
        $basePath =  rtrim($root,'/');
        $_SERVER['DOCUMENT_ROOT']=$basePath;
        $nowtime=time();
        fdump_api([__LINE__,$parameter,'nowtime'=>$nowtime],'222temporary_car_afterpay',1);
        if(isset($parameter['pay_current_time']) && $parameter['pay_current_time']<=$nowtime){
            if(isset($parameter['village_id']) && !empty($parameter['village_id']) && isset($parameter['order_id']) && !empty($parameter['order_id'])){
                $parking_temp_id=0;
                $car_number='';
                if(isset($parameter['parking_temp_id']) && !empty($parameter['parking_temp_id'])){
                    $parking_temp_id=$parameter['parking_temp_id'];
                }
                if(isset($parameter['car_number']) && !empty($parameter['car_number'])){
                    $car_number=$parameter['car_number'];
                }
                $free_park_time=0;
                if(isset($parameter['free_park_time']) && !empty($parameter['free_park_time'])){
                    $free_park_time=$parameter['free_park_time'];
                }
                if($free_park_time<30){
                    $free_park_time=900;
                }
                $houseNewPayOrderDb = new HouseNewPayOrder();
                $houseVillageParkingTempDb = new HouseVillageParkingTemp();
                $whereArr=array(['order_id','=',$parameter['order_id']],['village_id','=',$parameter['village_id']]);
                $orderObj= $houseNewPayOrderDb->get_one($whereArr,'*');
                if($orderObj && !$orderObj->isEmpty()){
                    $order=$orderObj->toArray();
                    $pay_time=$order['pay_time'];
                    if($pay_time<100){
                        $pay_time=$parameter['pay_current_time'];
                    }
                    if($order && $order['car_type']=='temporary_type' && !empty($order['car_number'])){
                       if(empty($car_number) ){
                           $car_number=$order['car_number'];
                       }
                       if($car_number==$order['car_number']){
                           /*
                           $whereTemp=array();
                           if($parking_temp_id>0){
                               $whereTemp[]=array('id','=',$parking_temp_id);
                           }
                           $whereTemp[]= array('village_id','=',$parameter['village_id']);
                           $whereTemp[]= array(['car_number','=',$car_number]);
                           $house_village_parking_temp =  $houseVillageParkingTempDb->getFind($whereTemp);
                           if($house_village_parking_temp && !$house_village_parking_temp->isEmpty()){
                               $house_village_parking_temp=$house_village_parking_temp->toArray();
                           }
                           */
                           $db_house_village_car_access_record= new HouseVillageCarAccessRecord();
                           $db_park_passage=new ParkPassage();
                           //查支付成功后 之前的最新进场
                           $whereAccessArr=array();
                           $whereAccessArr[]=array('car_number','=',$car_number);
                           $whereAccessArr[]=array('business_id','=',$parameter['village_id']);
                           $whereAccessArr[]=array('accessType','=',1);
                           $whereAccessArr[]=array('accessTime','<',$pay_time);
                           $in_car_access_record = $db_house_village_car_access_record->getOne($whereAccessArr,'*','record_id DESC');
                           fdump_api([__LINE__,'in_whereAccessArr'=>$whereAccessArr],'222temporary_car_afterpay',1);
                           if($in_car_access_record && !$in_car_access_record->isEmpty()){
                               $whereAccessArr=array();
                               $whereAccessArr[]=array('car_number','=',$car_number);
                               $whereAccessArr[]=array('business_id','=',$parameter['village_id']);
                               $whereAccessArr[]=array('accessType','=',2);
                               $whereAccessArr[]=array('record_id','>',$in_car_access_record['record_id']);
                               $out_access_time=$pay_time+$free_park_time;
                               $whereAccessArr[]=array('accessTime','<=',$out_access_time);
                               if(isset($in_car_access_record['order_id']) && $in_car_access_record['order_id']){
                                   $whereAccessArr[]=array('order_id','=',$in_car_access_record['order_id']);
                               }
                               //查支付成功后 在免费出场时间前的 最新出场
                               $out_car_access_record = $db_house_village_car_access_record->getOne($whereAccessArr,'*','total desc,record_id DESC');
                               fdump_api([__LINE__,'out_whereAccessArr'=>$whereAccessArr],'222temporary_car_afterpay',1);
                               if(empty($out_car_access_record) || $out_car_access_record->isEmpty()){
                                   //没有出场记录 加一条
                                   $car_access_record=array();
                                   $car_access_record['business_id'] = $parameter['village_id'];
                                   $car_access_record['car_number'] = $car_number;
                                   $car_access_record['accessType'] = 2;
                                   $car_access_record['accessTime'] = $pay_time;
                                   $car_access_record['park_time'] = $pay_time - $in_car_access_record['accessTime'];
                                   $car_access_record['accessMode'] = 3;
                                   $car_access_record['accessImage'] = '';
                                   $car_access_record['accessBigImage'] = '';
                                   $car_access_record['park_sys_type'] = $in_car_access_record['park_sys_type'];
                                   $car_access_record['is_out'] = 1;
                                   $car_access_record['park_id'] = $parameter['village_id'];
                                   $car_access_record['park_name'] = $in_car_access_record['park_name'];
                                   $car_access_record['uid']=$order['uid'] >0 ? $order['uid']:$in_car_access_record['uid'];
                                   $car_access_record['bind_id']=$order['pay_bind_id'] >0 ? $order['pay_bind_id']:$order['pigcms_id'];
                                   $car_access_record['user_name']=$order['name'] ?  $order['name']:$in_car_access_record['user_name'];
                                   $car_access_record['user_phone']=$order['pay_bind_phone'] ?  $order['pay_bind_phone']:$in_car_access_record['user_phone'];
                                   $car_access_record['pay_time']=$pay_time;
                                   $car_access_record['is_paid']=1;
                                   $car_access_record['pay_order_id']=$order['summary_id'];
                                   $car_access_record['order_id'] = isset($in_car_access_record['order_id']) && $in_car_access_record['order_id'] ? $in_car_access_record['order_id'] : '';
                                   $car_access_record['total'] = $order['pay_money'];
                                   // 支付类型,cash:现金支付，wallet:余额支付,sweepcode:扫码支付,escape:逃单出场
                                   // cash 现金支付  wallet 电子钱包、免密支付  sweepcode 扫码枪支付微信，或支付宝等 monthuser 月卡支付 free 免费放行 scancode 通道扫码支付微信，或支付宝 escape 逃单出场
                                   //交易流水号(pay_type为wallet、scancode、sweepcode必传)
                                   $car_access_record['pay_type'] = 'scancode';
                                   $car_access_record['update_time'] = $nowtime;
                                   $car_access_record['exception_type'] = 2;
                                   $car_access_record['flag_status'] = 'temporaryCarAfterPayAddOutRecord';
                                   $car_access_record['trade_no'] = time() . rand(10, 99) . sprintf("%08d", $parameter['village_id']);
                                   $garage_id=0;
                                   if(isset($in_car_access_record['garage_id'])){
                                       $car_access_record['garage_id'] = $in_car_access_record['garage_id'];
                                       $garage_id=$in_car_access_record['garage_id'];
                                   }
                                   $wherePassage=array();
                                   $wherePassage[]=array('village_id','=',$parameter['village_id']);
                                   $wherePassage[]=array('status','=',1);
                                   $wherePassage[]=array('passage_direction','=',0);
                                   $park_passage=$db_park_passage->getFind($wherePassage);
                                   if($park_passage && !$park_passage->isEmpty()){
                                       $car_access_record['channel_id']=$park_passage['id'];
                                       $car_access_record['channel_number']=$park_passage['channel_number'];
                                       $car_access_record['channel_name']=$park_passage['passage_name'];
                                   }
                                   $record_id=$db_house_village_car_access_record->addOne($car_access_record);
                                   fdump_api([__LINE__,'car_access_record'=>$car_access_record,'record_id'=>$record_id],'222temporary_car_afterpay',1);
                                   if($record_id>0){
                                       //将此条之前的 都改掉
                                       $wherexArr=[];
                                       $wherexArr[]=['record_id','<=',$in_car_access_record['record_id']];
                                       $wherexArr[]=['car_number','=',$car_number];
                                       $wherexArr[]=['is_out','=',0];
                                       $wherexArr[]=['business_id','=',$parameter['village_id']];
                                       $db_house_village_car_access_record->saveOne($wherexArr,['is_out'=>1,'update_time'=>$nowtime]);
                                       if($in_car_access_record && isset($in_car_access_record['position_id']) && $in_car_access_record['position_id']) {
                                           //释放车位
                                           $whereArr = array('position_id' => $in_car_access_record['position_id']);
                                           if($in_car_access_record['car_id']){
                                               $whereArr['use_car_id']=$in_car_access_record['car_id'];
                                           }
                                           $saveArr = array('use_car_id' => 0, 'use_start_time' => 0, 'use_car_number' => '', 'access_record_id' => $record_id);
                                           $this->updateParkingPositionUse($whereArr, $saveArr, $in_car_access_record['car_number'], 'heartbeat_out_park');
                                       }
                                   }
                               }
                               //处理掉多条出场记录问题
                               if(isset($in_car_access_record['order_id']) && $in_car_access_record['order_id']){
                                   $whereAccessArr=array();
                                   $whereAccessArr[]=array('order_id','=',$in_car_access_record['order_id']);
                                   $whereAccessArr[]=array('record_id','>',$in_car_access_record['record_id']);
                                   $whereAccessArr[]=array('business_id','=',$parameter['village_id']);
                                   $whereAccessArr[]=array('accessType','=',2);
                                   $whereAccessArr[]=array('car_number','=',$car_number);
                                   $whereAccessArr[]=array('del_time','<',1);
                                   $access_record_list=$db_house_village_car_access_record->get_list($whereAccessArr,'*',0,0,'record_id,total,pay_type,del_time');
                                   if($access_record_list && !$access_record_list->isEmpty()){
                                       $access_record_list=$access_record_list->toArray();
                                       $recordTotal= count($access_record_list);
                                       //1条以上 处理
                                       if($recordTotal>1){
                                           $not_pay_arr=array();
                                           $not_pay_num=0;
                                           $is_pay_record_id=0;
                                           foreach ($access_record_list as $recordV){
                                               if($recordV && $recordV['total']<=0){
                                                   $not_pay_arr[]=$recordV['record_id'];
                                                   $not_pay_num++;
                                               }else if($recordV && $recordV['total']>0){
                                                   $is_pay_record_id=$recordV['record_id'];
                                               }
                                           }
                                           if($is_pay_record_id>0 && !empty($not_pay_arr)){
                                               $whereDel=array();
                                               $whereDel[]=array('record_id','in',$not_pay_arr);
                                               $whereDel[]=array('business_id','=',$parameter['village_id']);
                                               $db_house_village_car_access_record->saveOne($whereDel,['is_out'=>1,'del_time'=>$nowtime,'flag_status'=>'temporaryCarAfterPayDel']);
                                           }else if($is_pay_record_id<1 && !empty($not_pay_arr) && $not_pay_num>1){
                                               unset($not_pay_arr['0']);
                                               //剩下的 全部删除
                                               $whereDel=array();
                                               $whereDel[]=array('record_id','in',$not_pay_arr);
                                               $whereDel[]=array('business_id','=',$parameter['village_id']);
                                               $db_house_village_car_access_record->saveOne($whereDel,['is_out'=>1,'del_time'=>$nowtime,'flag_status'=>'temporaryCarAfterPayDel']);
                                           }
                                       }
                                   }
                               }

                           }
                       }
                    }
                }
            }
        }
        return  true;
    }
}
