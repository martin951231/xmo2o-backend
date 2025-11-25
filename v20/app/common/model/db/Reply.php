<?php
namespace app\common\model\db;

use think\Model;
class Reply extends Model {
	use \app\common\model\db\db_trait\CommonFunc;

    //插入数据
    public function insert_record($data){
        return $this->insertGetId($data);
    }

    /**
     * 更新数据
     * @param $orderId int
     * @param $data array
     * @return array
     */
    public function updateByOrderId($orderId, $data) {
        if(empty($orderId) || empty($data)){
            return false;
        }

        $where = [
            'order_id' => $orderId
        ];
        $result = $this->where($where)->update($data);
        if(!$result) {
            return false;
        }
        return $result;
    }

    /**
     * 更新数据
     * @param $pigcms_id int
     * @param $data array
     * @return array
     */
    public function updateByPigcmsId($pigcms_id, $data) {
        if(empty($pigcms_id) || empty($data)){
            return false;
        }

        $where = [
            'pigcms_id' => $pigcms_id
        ];
        $result = $this->where($where)->update($data);
        if(!$result) {
            return false;
        }
        return $result;
    }

     /**
     * 获取某个团购商品的评价
     * @param array $where 条件
     * @param array $field 查询字段
     * @param array $order 排序值
     * @param array $page 页码
     * @param array $limit 每页显示条数
     * @return array|bool|Model
     */
    public function getListByGroupGoods($where,$field=true,$order=[],$page=1,$limit=0) {
        if(empty($where)){
            return false;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('r')
                    ->field($field) 
                    ->where($where) 
                    ->order($order)
                    ->where('order_type','0') 
                    ->leftJoin($prefix.'group_order o','o.order_id=r.order_id')
                    ->leftJoin($prefix.'user u','u.uid=r.uid')
                    ->page($page,$limit)
                    ->select();
        return $result;
    }

    /**
     * 获取某个团购商品的评价总数
     * @param array $where 条件
     * @param array $field 查询字段
     * @param array $order 排序值
     * @param array $page 页码
     * @param array $limit 每页显示条数
     * @return array|bool|Model
     */
    public function getCountByGroupGoods($where) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('r')
                    ->where($where) 
                    ->where('order_type','0') 
                    ->leftJoin($prefix.'group_order o','o.order_id=r.order_id')
                    ->count();
        return $result;
    }
    
    	
   /**
   * 获取某个团购商品的评价最大值
   * @param array $where 条件
   * @return array
   */
  public function getScoreByGroupGoods($where){
    // 表前缀
    $prefix = config('database.connections.mysql.prefix');
    $result = $this->alias('r')
                // ->field('AVG(r.score)') 
                ->where($where) 
                ->where('order_type','0') 
                ->leftJoin($prefix.'group_order o','o.order_id=r.order_id')
                ->order(['r.score'=>'DESC'])
                ->value('score');
    return $result;
    }
    
    /**
     * @return mixed
     * 取评论评分均值
     */
    public function getScore($where){
        $result = $this->alias('r')
            ->field("AVG(r.score) as r_score")
            ->join('merchant_store s', 's.store_id = r.store_id')
            ->where($where)
            ->select();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }
}