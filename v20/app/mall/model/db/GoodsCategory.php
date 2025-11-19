<?php
/**
 * GoodsCategory.php
 * 后台分类管理DB
 * Create on 2020/9/7 17:04
 * Created by zhumengqun
 */
namespace app\mall\model\db;
use think\Model;

class GoodsCategory extends Model{
    /**
     * @param $where
     * @param $order
     * @return array
     */
    public function getCategoryByCondition($where, $order)
    {
        $sort = $this->field(['id','fid','name','sort','status','image'])->where($where)->order($order)->select();
        if (!$sort) {
            return [];
        }
        return $sort->toArray();
    }

    /**
     * @param $where
     * @param  $order array
     * @param $page
     * @param $pageSize
     * @return array :  array
     * @Desc:   获取分类列表
     */
    public function getCategoryByCondition2($where,$order,$page,$pageSize)
    {
        $sort = $this->field(['id','fid','name','sort','status','image'])->where($where)->order($order)->page($page,$pageSize)->select();
        if (!$sort) {
            return [];
        }
        return $sort->toArray();
    }

    /**
     * @return int
     * 获取分类总数
     */
    public function getCategoryCount(){
        $count = $this->select()->count('id');
        return $count;
    }

    /**
     * 新增分类
     * @param $where
     * @return GoodsCategory
     */
    public function editCategory($where,$arr){
        $result = $this->where($where)->update($arr);
        return $result;
    }

    /**
     * 删除分类
     * @param $where
     * @return bool
     */
    public function addCategory($arr){
        $result = $this->insert($arr);
        return $result;
    }

    /**
     * @param $where
     * @return array
     * 获取编辑的分类
     */
    public function getEditCategory($where){
        $arr = $this->field(['id','fid','name','sort','status','image'])->where($where)->find();
        return $arr->toArray();

    }

    /**
     * @param $where
     * @return bool
     * 删除平台分类
     */
    public function delCategory($where){
        $result = $this->where($where)->delete();
        return $result;
    }
    public function getNameById($where){
        $catename = $this->field('name')->where($where)->find()->toArray();
        return $catename;
    }
}
