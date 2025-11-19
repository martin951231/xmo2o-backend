<?php
/**
 * MerchantPosition.php
 * 店铺岗位管理model
 * Create on 2021/6/2
 * Created by wangchen
 */

namespace app\merchant\model\db;

use think\Model;

class MerchantPosition extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 岗位列表
     * @return \json
     */
    public function getPositionList($where, $order, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'merchant_category' . ' b', 'b.cat_id = a.cat_id')
            ->field('a.*,b.cat_name')
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 岗位列表总数
     * @param $where
     * @return mixed
     */
    public function getPositionCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix . 'merchant_category' . ' b', 'b.cat_id = a.cat_id')
            ->field('a.*,b.cat_name')
            ->where($where)
            ->count('id');
        return $count;
    }

    /**
     * 岗位分类
     * @return \json
     */
    public function getPositionCategoryList($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 岗位操作
     * @return \json
     */
    public function getPositionCreate($id, $cat_id, $name, $remarks)
    {
        $data = ['cat_id'=>$cat_id, 'name'=>$name, 'remarks'=>$remarks, 'create_time'=>time()];
        if($id > 0){
            // 修改
            $where = ['id' => $id];
            $result = $this->where($where)->update($data);
        }else{
            // 新增
            $result = $this->insert($data);
        }

        if($result===false){
            throw new \think\Exception("操作失败请重试",1005);
        }
        return $result;
    }

    /**
     * 岗位详情
     * @return \json
     */
    public function getPositionInfo($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 岗位删除
     * @return \json
     */
    public function getPositionDelAll($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }

    /**
     * 岗位列表
     * @param $where
     * @return mixed
     */
    public function getPosition($where, $order='id DESC', $field='*')
    {
        $arr = $this->field($field)->order($order)->where($where)->select();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }
}