<?php
/**
 * 餐饮桌台model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/30 11:26
 */

namespace app\foodshop\model\db;
use think\Model;
class FoodshopTable extends Model { 
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据id获取桌台信息
     * @param $id int 桌台id
     * @return array|bool|Model|null
     */
    public function geTableById($id) {
        if(!$id){
            return null;
        }

        $where = [
            'id' => $id
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 根据店铺id获取所有桌台
     * @author 张涛
     * @date 2020/07/10
     */
    public function getTableByStoreId($storeId)
    {
        $prefix = config('database.connections.mysql.prefix');
        $this->name = _view($this->name);
        $rs = $this->alias('table')
            ->join([$prefix . _view('foodshop_table_type') => 'type'], 'type.id=table.tid')
            ->join([$prefix . 'merchant_store_staff' => 'staff'], 'staff.id=table.staff_id', 'LEFT')
            ->where(['table.store_id' => $storeId])
            ->field('table.*,type.name AS type_name,staff.name AS staff_name')
            ->select()
            ->toArray();
        return $rs;
    }

    /**
     * 查询不同状态的桌台
     * @author 衡婷妹
     * @date 2020/08/26
     */
    public function getStaffTableArr($where = [], $whereOrder = []){
        $prefix = config('database.connections.mysql.prefix');
        if($whereOrder){
            $this->name = _view($this->name);
            $rs = $this->alias('table')
                ->where($where)
                ->where('table.id', 'in', function ($query ) use ($whereOrder) {
                    $query->table(config('database.connections.mysql.prefix').'dining_order')->where($whereOrder)->field('table_id')->order(['order_id'=> 'DESC']);
                })
                ->field('table.*,type.name AS type_name,type.min_people,type.max_people')
                ->join([$prefix . 'foodshop_table_type' => 'type'], 'type.id=table.tid')
                ->order('table.sort DESC')
                ->select();
        }else{
            $this->name = _view($this->name);
            $rs = $this->alias('table')
                ->where($where)
                ->field('table.*,type.min_people AS type_name,type.min_people,type.max_people')
                ->join([$prefix . 'foodshop_table_type' => 'type'], 'type.id=table.tid')
                ->order('table.sort DESC')
                ->select();
        }
        return $rs;
    }

    
}