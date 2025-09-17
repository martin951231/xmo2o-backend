<?php
namespace app\marriage_helper\model\db;

use think\Model;

class MarriageCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 分类列表
     * @return \json
     */
    public function getCategoryList($where, $order, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix . 'merchant_position' . ' b', 'b.id = a.pos_id')
            ->field('a.*,b.name as pos_name')
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
     * 分类列表总数
     * @param $where
     * @return mixed
     */
    public function getCategoryCount($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('a')
            ->join($prefix . 'merchant_position' . ' b', 'b.id = a.pos_id')
            ->field('a.*,b.name')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 分类操作
     * @return \json
     */
    public function getCategoryCreate($cat_id, $pos_id, $cat_name, $sort, $status)
    {
        $data = ['pos_id'=>$pos_id, 'cat_name'=>$cat_name, 'sort'=>$sort, 'status'=>$status, 'create_time'=>time()];
        if($cat_id > 0){
            // 修改
            $where = ['cat_id' => $cat_id];
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
     * 分类详情
     * @return \json
     */
    public function getCategoryInfo($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if(!empty($arr)){
            return $arr->toArray();
        }else{
            return [];
        }
    }

    /**
     * 分类排序
     * @return \json
     */
    public function getCategorySort($cat_id,$sort)
    {
        $list = $this->where(array('cat_id'=>$cat_id))->update(array('sort'=>$sort));
        return $list;
    }

    /**
     * 分类删除
     * @return \json
     */
    public function getCategoryDelAll($where)
    {
        $result = $this->where($where)->update(['is_del'=>1]);
        return $result;
    }

    /**
     * 分类无分页列表
     * @return \json
     */
    public function getCategoryLists($where, $order, $field)
    {
        $arr = $this->where($where)->order($order)->field($field)->select();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}