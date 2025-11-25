<?php
/**
 * Created by PhpStorm.
 * Author: weili
 * Date Time: 2020/7/7 9:20
 */

namespace app\community\model\service;
use app\community\model\db\HouseProperty;
use app\new_marketing\model\db\NewMarketingOrderType;
use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingPersonMer;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use think\facade\Db;

class HousePropertyService
{

    /**
     * 获取物业信息
     * @param int $id 物业id
     * @return array|null|\think\Model
     */
    public function getHousePropertyDetail($id)
    {
        // 初始化 数据层
        $housePropertyDb = new HouseProperty();

        $where = [
            'id' => $id
        ];
        $detail = $this->getFind($where);

        $detail['create_time'] = date('Y-m-d H:i:s', $detail['create_time']);

        // 订单详情
        $page = request()->param('page','1','intval');
        $pageSize = request()->param('pageSize','1','intval');
        $start = ($page-1)*$pageSize;
        $where = [];
        $where[] = ['property_id', '=', $id];
        $where[] = ['status', '=', 1];
        $list = (new PackageOrderService)->getPackageOrderList($where, true, $start, $pageSize);
        
        $returnArr = [];
        $returnArr['basic'] = $detail;
        $returnArr['list'] = $list['list'];
        $returnArr['count'] = $list['count'];
        return $returnArr;
    }

    /**
     * 获取信息
     * @author: weili
     * @datetime:2020/7/7 9:21
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getFind($where,$field=true,$orderby='status asc,id desc')
    {
        // 初始化 数据层
        $housePropertyDb = new HouseProperty();
        $info = $housePropertyDb->get_one($where,$field,$orderby);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }
    /**
     * 添加数据
     * @author weili
     * @datetime: 2020/7/7 9:41
     *
    **/
    public function addData($data)
    {
        $person_info=[];
        $invitation_code=$data['invitation_code'];
        if (!empty($invitation_code)){
            $person_info=$this->getPersonInfo($data['invitation_code']);
            if (empty($person_info)){
                throw new \think\Exception('邀请码错误');
            }
        }
        unset($data['invitation_code']);
        $housePropertyDb = new HouseProperty();
        Db::startTrans();
        $res = $housePropertyDb->addOne($data);
        if ($res>0){
            //聚达平台营销板块定制
            if (!empty($invitation_code)&&!empty($person_info)){
                $person_mer=[
                    'mer_id'=>$res,
                    'person_id'=>$person_info['person_id'],
                    'team_id'=>$person_info['team_id'],
                    'type'=>1,
                    'add_time'=>time(),
                ];
               $person_id= $this->addPersonMer($person_mer);
               if ($person_id<1){
                   Db::rollback();
               }
            }
            $serviceHouseNewPorperty = new HouseNewPorpertyService();
            $serviceHouseNewPorperty->setSoftwareNewEffectCharge($res);
        }
        Db::commit();
        return $res;
    }


    /**
     * 聚达平台营销板块定制添加信息
     * @author:zhubaodi
     * @date_time: 2021/9/10 13:18
     */
    public function addPersonMer($data){
        $db_order_type=new NewMarketingPersonMer();
        $mer_info=$db_order_type->getOne(['mer_id'=>$data['mer_id'],'type'=>1],'id');
        if (!empty($mer_info)){
            $res=0;
        }else{
            $res = $db_order_type->addOne($data);
        }
        return $res;

    }

    /**
     * 聚达平台营销板块定制添加信息
     * @author:zhubaodi
     * @date_time: 2021/9/10 13:18
     */
    public function getPersonInfo($invitation_code){
        $person_info=[];
       if (!empty($invitation_code)){
           $db_person_manger=new NewMarketingPersonManager();
           $person_info=$db_person_manger->getOneData(['invitation_code'=>$invitation_code,'is_del'=>0]);
           if (empty($person_info)){
               $db_person_salesman=new NewMarketingPersonSalesman();
               $person_info=$db_person_salesman->getOneData(['invitation_code'=>$invitation_code,'is_del'=>0]);
           }
       }
        return $person_info;
    }
    /**
     * 编辑数据
     * @author weili
     * @datetime 2020/7/8 10:16
     * @param array $where 查询条件
     * @param array $data 要修改的数据
     * @return integer
    **/
    public function editData($where,$data)
    {
        $housePropertyDb = new HouseProperty();
        $res = $housePropertyDb->save_one($where,$data);
        return $res;
    }
    
    public function get_room_limit_num($property_id){
        $HouseProperty=new HouseProperty();
        // 考虑到禁止也有可能开启所以禁止状态也计算数量
        $where = array(
            ['hp.id','=',$property_id],
            ['hv.status','in',[1,2]],
            ['hvu.status','in',[0,1,3]],
            ['hvu.is_del','=',0],
        );
        $field = 'count(hvu.pigcms_id) as room_num';
        $res=$HouseProperty->getPropertyBindVillage($where,$field);
        $limit_info=[];
        if(!$res){
            $limit_info['room_num'] = 0;
        }else{
            $limit_info['room_num'] = $res;
        }
        $room_limit_num=$HouseProperty->get_one(['id'=>$property_id],'room_limit_num');
        if($room_limit_num){
            $limit_info['room_limit_num'] = $room_limit_num['room_limit_num'];
        }
        if ($limit_info['room_num']>0 && $room_limit_num) {
            $limit_info['surplus_room_num'] = intval($room_limit_num['room_limit_num']) - intval($limit_info['room_num']);
        } else {
            $limit_info['surplus_room_num'] = intval($room_limit_num['room_limit_num']);
        }
        return $limit_info;
       
    }
}
