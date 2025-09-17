<?php


namespace app\community\model\service;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageDeposit;
use app\community\model\db\HouseVillagePayType;

class HouseVillageDepositService
{
    public $model = '';

    public function __construct()
    {
        $this->model = new HouseVillageDeposit();
    }

    /**
     * 根据条件获取押金列表
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDepositLists($where,$field=true,$page=1,$limit=15)
    {
        $deposit_lists = $this->model->getLists($where,$field,$page,$limit)->toArray();
        if($deposit_lists){
            $service_house_village = new HouseVillageService();
            foreach ($deposit_lists as $k=>$v){
                $deposit_lists[$k]['address'] = $service_house_village->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                $deposit_lists[$k]['pay_time'] = date('Y-m-d H:i:s',$v['pay_time']);
                if($v['refund_time'])
                    $deposit_lists[$k]['refund_time'] = date('Y-m-d H:i:s',$v['refund_time']);
            }
        }
        return $deposit_lists;
    }

    /**
     * 获取押金详细信息
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getDepositDetail($where,$field)
    {
        $service_house_village = new HouseVillageService();
        $detail = $this->model->getOne($where,$field);
        $detail['address'] = $service_house_village->getSingleFloorRoom($detail['single_id'],$detail['floor_id'],$detail['layer_id'],$detail['vacancy_id'],$detail['village_id']);
        $detail['pay_time'] = date('Y-m-d H:i:s',$detail['pay_time']);
        if($detail['refund_time'])
            $detail['refund_time'] = date('Y-m-d H:i:s',$detail['refund_time']);
        return $detail;
    }

    /**
     * 修改押金管理
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveDeposit($where,$data)
    {
        $res = $this->model->saveOne($where,$data);
        return $res;
    }

    /**
     * 取线下支付类型
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @return array|\think\Model|null
     */
    public function getPay($where)
    {
        $house_village_pay_type = new HouseVillagePayType();
        $lists = $house_village_pay_type->get_list($where);
        return $lists;
    }

    /**
     * 添加押金管理
     * @author lijie
     * @date_time 2020/07/14
     * @param $data
     * @return int|string
     */
    public function addDeposit($data)
    {
        $res = $this->model->addOne($data);
        return $res;
    }


    /**
     * 押金列表
     * @author: liukezhu
     * @date : 2021/11/10
     * @param $where
     * @param $field
     * @param $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getDepositList($where,$field,$order,$page=0,$limit=10){
        $list = $this->model->getList($where,$field,$order,$page,$limit);
        $count=0;
        if($list){
            $list=$list->toArray();
            foreach ($list as &$v){
                if(isset($v['pay_time']) && !empty($v['pay_time'])){
                    $v['pay_time']=date('Y-m-d H:i:s',$v['pay_time']);
                }
            }
            unset($v);
            $count= $this->model->getCount($where);
        }
        return ['list'=>$list,'count'=>$count];
    }

    /**
     *押金详情
     * @author: liukezhu
     * @date : 2021/11/10
     * @param $where
     * @param $field
     * @return array
     */
    public function getDepositInfo($where,$field){
        $data = $this->model->getOne($where,$field);
        return !empty($data) ? $data->toArray() : [];
    }

    public function getPayList($village_id){
        $house_village = new HouseVillage();
        $service_house_new_property = new HouseNewPorpertyService();
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $house_village_pay_type = new HouseVillagePayType();
        $village_info=$house_village->getOne(['village_id'=>$village_id],'property_id');
        if (!$village_info || $village_info->isEmpty()) {
            throw new \think\Exception('小区不存在');
        }
        $property_id=$village_info['property_id'];
        $is_new_charge = $service_house_new_property->getTakeEffectTimeJudge($property_id);
        $data=[];
        if($is_new_charge){
            $where[] = ['property_id','=',$property_id];
            $where[] = ['status','=',1];
            $pay_list = $db_house_new_offline_pay->getList($where, 'id,name');
        }else{
            $where[] = ['village_id','=',$village_id];
            $pay_list = $house_village_pay_type->get_list($where,'id,name');
        }
        if($pay_list && !$pay_list->isEmpty()){
            $data= $pay_list->toArray();
        }
        return $data;
    }
}