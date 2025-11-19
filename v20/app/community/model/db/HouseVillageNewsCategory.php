<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageNewsCategory extends Model
{
    /**
     * 获取新闻分类
     * @author lijie
     * @date_time 2020/08/17 17:33
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=1,$limit=10,$order='cat_sort DESC,cat_id DESC')
    {
        if($page)
            $data = $this->field($field)->where($where)->page($page,$limit)->order($order)->select();
        else
            $data = $this->field($field)->where($where)->order($order)->select();
        return $data;
    }
    
    public function getCount($where){
        $count=$this->where($where)->count();
        return $count>0 ? $count:0;
    }
    public function getList($where,$field='c.*',$page=0,$limit=10,$order='c.cat_sort DESC')
    {
        $sql = $this->alias('c')->leftJoin('house_village v','v.village_id = c.village_id')->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
}