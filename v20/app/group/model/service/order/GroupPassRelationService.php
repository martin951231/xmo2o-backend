<?php
/**
 * 团购订单核销码
 * Author: 衡婷妹
 * Date Time: 2020/11/27 16:18
 */

namespace app\group\model\service\order;

use app\common\model\db\MerchantStore;
use app\group\model\db\GroupOrder;
use app\group\model\db\GroupPassRelation;

class GroupPassRelationService
{
    public $groupPassRelationModel = null;

    public function __construct()
    {
        $this->groupPassRelationModel = new GroupPassRelation();
    }

    /**
     * 获得核销码数量
     * @param int $orderId 订单id
     * @param int $status 状态
     * @return int
     */
    public function getPassNum($orderId,$status=0){
		$count = $this->getCount(array('order_id'=>$orderId,'status'=>$status));
		return $count;
	}

    /**
     * 修改核销码状态
     * @param int $orderId 订单id
     * @param int $status 状态
     * @param array $staffInfo 
     * @return bool
     */
	public function changeRefundStatus($orderId, $status=2, $staffInfo=array()){
        $where = [
            ['order_id', '=', $orderId],
            ['status', 'in', '0,3']
        ];
	    if ($staffInfo) {
            $staffInfo['status'] = $status;
            $this->updateThis($where, $staffInfo);
        } else {
            $this->updateThis($where, ['status'=>$status]);
        }
        return true;
	}

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupPassRelationModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupPassRelationModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->groupPassRelationModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }    
    
    /**
    *获取总数
    * @param $where array
    * @return array
    */
   public function getCount($where){
      
       $result = $this->groupPassRelationModel->getCount($where);
       if(empty($result)){
           return 0;
       }

       return $result;
   }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupPassRelationModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupPassRelationModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
    
    /**
     * 获取核销记录
     */
    public function groupCouponList($param)
    {
        $merId = $param['mer_id'] ?? 0;
        $storeId = $param['store_id'] ?? 0;
        $where = [['gp.status','=',1],['o.tuan_type','=',0]];
        $where_one_pass = [['o.tuan_type','=',0],['o.verify_time','>',0],['o.pass_num','<=',1]];
        if($merId){
            $where[] = ['o.mer_id', '=', $merId];
            $where_one_pass[] = ['o.mer_id', '=', $merId];
        }
        if($storeId){
            $where[] = ['o.store_id', '=', $storeId];
            $where_one_pass[] = ['o.store_id', '=', $storeId];
        }
        if(!empty($param['keywords'])){//核销人、核销码或者卡券名称关键词查询
            $where[] = ['gp.group_pass|g.s_name|gp.staff_name', 'like', '%' .$param['keywords']. '%'];
            $where_one_pass[] = ['o.group_pass|o.order_name|o.staff_name', 'like', '%' .$param['keywords']. '%'];
        }
        if(!empty($param['start_time']) && !empty($param['end_time']) && $param['select_time_type']==0){//核销时间查询
            $where[] = ['gp.verify_time', 'between', [strtotime($param['start_time']), strtotime($param['end_time'])+60]];
            $where_one_pass[] = ['o.verify_time', 'between', [strtotime($param['start_time']), strtotime($param['end_time'])+60]];
        }
        if(!empty($param['start_time']) && !empty($param['end_time']) && $param['select_time_type']==1){//下单时间查询
            $where[] = ['o.add_time', 'between', [strtotime($param['start_time']), strtotime($param['end_time'])+60]];
            $where_one_pass[] = ['o.add_time', 'between', [strtotime($param['start_time']), strtotime($param['end_time'])+60]];
        }
        $order = ['gp.verify_time' => 'DESC'];
        $order_one_pass = ['o.verify_time' => 'DESC'];
        $field = 'gp.id,gp.verify_time,u.nickname,g.s_name,gp.group_pass,gp.staff_name,o.store_id';
        $field_one_pass = 'o.order_id as id,o.verify_time,u.nickname,g.s_name,o.group_pass,o.staff_name,o.store_id';
        $list_one_pass = $one_pass_order_ids = [];
        if(!empty($param['page'])&&$param['page']==1){
            $list_one_pass = (new GroupOrder())->getGroupOnePassList($where_one_pass, $field_one_pass, $order_one_pass);
            $one_pass_order_ids = $list_one_pass?array_column($list_one_pass,'id'):[];
        }
        if($one_pass_order_ids){
            $where[] = ['gp.order_id','not in',$one_pass_order_ids];
        }
        $list = (new GroupPassRelation())->getGroupPassList($where, $field, $order, $param['pageSize']);
        $weekarray=array(L_("日"),L_("一"),L_("二"),L_("三"),L_("四"),L_("五"),L_("六"));
        if($list_one_pass){
            $list['data'] = array_merge($list_one_pass,$list['data']);
        }
        foreach ($list['data'] as $key=>$item){
            //店鋪名称
            $list['data'][$key]['verify_date'] = $item['verify_time'] ? date('Y-m-d',$item['verify_time']) : '';
//            $list['data'][$key]['verify_week'] = $item['verify_time'] ? date('l',$item['verify_time']) : '';
            $list['data'][$key]['verify_week'] = $item['verify_time'] ? L_("星期").$weekarray[date("w",$item['verify_time'])] : '';
            $list['data'][$key]['verify_time'] = $item['verify_time'] ? date('H:i',$item['verify_time']) : '';
            $list['data'][$key]['store_name'] = (new MerchantStore())->where('store_id',$item['store_id'])->value('name');
        }
        return $list;
    }
}