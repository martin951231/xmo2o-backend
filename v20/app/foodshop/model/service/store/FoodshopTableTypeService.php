<?php
/**
 * 餐饮桌台分类service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/30 11:30
 */

namespace app\foodshop\model\service\store;
use app\foodshop\model\db\FoodshopTable;
use app\foodshop\model\db\FoodshopTableType as FoodshopTableTypeModel;
use app\foodshop\model\service\order\DiningOrderDetailService;
use app\foodshop\model\service\order\DiningOrderService;
use think\Exception;
use think\facade\Db;

class FoodshopTableTypeService {
    public $foodshopTableTypeModel = null;
    public function __construct()
    {
        $this->foodshopTableTypeModel = new FoodshopTableTypeModel();
    }

    /**
     * 获得店员的桌台分类列表
     * @param $where array 条件
     * @return array
     */
    public function getStaffTableTypeList($param , $staff){
        $returnArr = [];
        $returnArr['table_type_list'] = [];
        $returnArr['tab_count'] = [
            'all' => 0,
            'empty' => 0,
            'dining' => 0,
            'order' => 0,
            'clear' => 0,
        ];
        // 查询所有桌台类型
        $where = [
            'store_id' => $staff['store_id']
        ];
        $tableTypeList = $this->getTableTypeListByCondition($where,['sort'=>'desc','id'=>'asc']);

        if(empty($tableTypeList)){
            return $returnArr;
        }

        // 是否切换桌台（只查询待清台的桌台）
        $isChangeTable = $param['is_change_table'] ?? 0;

        $total = 0;
        $tableTypeListReturn = [];
        foreach ($tableTypeList as $key => $type){
            $tableTypeListReturn[$key]['id'] = $type['id'];
            $tableTypeListReturn[$key]['name'] = $type['name'];
            $tableTypeListReturn[$key]['people_num'] = L_('X1-X2人', ['X1'=>$type['min_people'],'X2'=>$type['max_people']]);

            // 查看桌台数量
            $where = [['tid', '=' ,$type['id']]];
            if($isChangeTable == 1){//空台
                $where[] = ['status', '=' ,0];
            }
            $tableTypeListReturn[$key]['table_count'] = (new FoodshopTableService())->getCount($where);
            $total += $tableTypeListReturn[$key]['table_count'];
        }

        $returnArr['table_type_list'][] = [
            'id' => 0,
            'name' => L_('全部'),
            'table_count' => $total,
            'people_num' => ''
        ];

        $returnArr['table_type_list'] = array_merge($returnArr['table_type_list'],$tableTypeListReturn);
        // 查询搜索统计
        // 全部
        $returnArr['tab_count']['all'] = (new FoodshopTableService())->getCount(['store_id' => $staff['store_id']]);

        //待清台
        // 获得桌台绑定的所有订单
        $orderList = [];
        $tableTypeIds = array_column($tableTypeList, 'id');
        if($tableTypeIds){
            // 查询订单
            $where = [
                ['table_type', 'in' ,  implode(',', $tableTypeIds)],
                ['store_id', '=',$staff['store_id']],
                ['status', 'between',[20,39]],
                ['status', '<>','21']
            ];
            $diningOrderService = new DiningOrderService();
            $orderList = $diningOrderService->getOrderListByCondition($where,['order_id'=>'ASC']);
        }

        // 订单待支付商品
        $orderIds = array_column($orderList,'order_id');
        $whereOrder = [
            ['order_id','in',implode(',',$orderIds)],
            ['status' , 'in' , '0,1,2'],
            ['num', 'exp', Db::raw(' > refundNum')],
        ];
        $orderDetailList = (new DiningOrderDetailService())->getSome($whereOrder);
        $orderDetailIds = array_unique(array_column($orderDetailList,'order_id'));

        $clear = [];
        $clearCount = 0;
        foreach ($orderList as $_order){
            if(!in_array($_order['table_id'],$clear)){
                if(!in_array($_order['order_id'], $orderDetailIds)){

                    fdump($_order,'orderDetailList',1);
                    $clearCount++;
                }
                $clear[] = $_order['table_id'];
            }
        }
        $returnArr['tab_count']['clear'] = $clearCount;
        
        //空台
        //查询所有有就餐中的桌台
        $where = [];
        $where[] = ['store_id','=', $staff['store_id']];
        // 已确认未完成
        $where[] = ['status' ,'>=', '20'];
        $where[] = ['status' ,'<', '40'];
        $where[] = ['is_temp' ,'=', '0'];
        $order = ['order_id'=>'ASC'];
        $diningOrder = (new DiningOrderService())->getOrderListByCondition($where);
        $tableIds = array_column($diningOrder,'table_id');
        $tableIds = array_unique($tableIds);
        $where = [
            ['store_id','=', $staff['store_id']],
            ['id', 'not in', implode(',',$tableIds)]
        ];
        $emptyCount = (new FoodshopTableService())->getCount($where);
        $returnArr['tab_count']['empty'] = $emptyCount;

        //点餐中
        $orderCount = 0;
        $tableIdArr  = [];
        foreach ($diningOrder as $key => $_order) {
            if($_order['table_id'] && in_array($_order['table_type'], $tableTypeIds)){
                if($_order['status'] == 21 && !in_array($_order['table_id'], $tableIdArr)){
                    $orderCount++;
                }
            }
            $tableIdArr[] =  $_order['table_id'];
        }
        $returnArr['tab_count']['order'] = $orderCount;

        //就餐中
        $returnArr['tab_count']['dining'] = $returnArr['tab_count']['all'] - $returnArr['tab_count']['empty'] - $returnArr['tab_count']['order'] - $returnArr['tab_count']['clear'];
        return $returnArr;
    }

    /**
     * 获得桌台分类列表
     * @param $where array 条件
     * @return array
     */
	public function getTableTypeListByCondition($where ,$order = []){
        $tableTypeList = $this->foodshopTableTypeModel->getTableTypeListByCondition($where, $order);
        if(!$tableTypeList) {
            return [];
        }
        return $tableTypeList->toArray();
    }

    /**
     * 获得桌台分类信息
     * @param $where array 条件
     * @return array
     */
    public function getTableTypeInfoByCondition($where){
        $tableTypeInfo = $this->foodshopTableTypeModel->getTableTypeByCondition($where);
        if(!$tableTypeInfo) {
            return [];
        }
        return $tableTypeInfo->toArray();
    }
    
    
    /**
     * 根据id获取桌台类型信息
     * @param $id int 桌台id
     * @return array
     */
	public function geTableTypeById($id){
        if(!$id){
            return [];
        }
        
        $tableType = $this->foodshopTableTypeModel->geTableTypeById($id);
        if(!$tableType) {
            return [];
        }
        return $tableType->toArray();
    }


    /**
     * 新增 + 修改桌台类型
     * @param $post
     * @author 张涛
     * @date 2020/07/10
     */
    public function saveTableType($post)
    {
        $id = $post['id'] ?? 0;
        $data = [
            'min_people' => $post['min_people'],
            'max_people' => $post['max_people'],
            'deposit' => $post['deposit'],
            'name' => $post['name'],
            'number_prefix' => $post['number_prefix'],
            'use_time' => $post['use_time'],
            'sort' => $post['sort']??0,
        ];
        if (empty($post['number_prefix'])) {
            throw new Exception('排号前缀不能为空');
        }

        if ($post['use_time'] == 0) {
            throw new Exception('使用时长不能为0');
        }

        if ($data['min_people'] >= $data['max_people']) {
            throw new Exception('最少人数不能大于或者等于最多人数');
        }

        // 查询排号前缀是否存在
        $where = [
            'store_id' => $post['store_id'],
            'number_prefix' => $post['number_prefix']
        ];
        $res = $this->getTableTypeInfoByCondition($where);
        if ($id == 0 && $res) {
            throw new Exception('排号前缀已存在');
        } elseif ($id > 0 && $res && $res['id']  != $id) {
            throw new Exception('排号前缀已存在');
        }

        if ($id > 0) {
            //修改
            $rs = $this->foodshopTableTypeModel->where('id', $id)->update($data);
        } else {
            //新增
            $data['store_id'] = $post['store_id'];
            $rs = $this->foodshopTableTypeModel->insert($data);
        }
        return true;
    }

    /**
     * 删除
     * @author 张涛
     * @date 2020/07/10
     */
    public function delTableType($id, $storeId = 0)
    {
        if ($id < 1) {
            throw new \think\Exception("请选择一条记录");
        }
        $where = ['id' => $id];
        $storeId > 0 && $where['store_id'] = $storeId;
        $rs = $this->foodshopTableTypeModel->where($where)->delete();
        if (!$rs) {
            throw new \think\Exception("删除失败");
        }
        return true;
    }

    /**
     * 更新桌台类型桌台数字段
     * @author: 张涛
     * @date: 2020/9/10
     */
    public function updateNumById($id)
    {
        $num = (new FoodshopTable())->where('tid', $id)->count();
        return $this->foodshopTableTypeModel->where('id', $id)->update(['num' => $num]);
    }
}