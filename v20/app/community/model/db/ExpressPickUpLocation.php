<?php


namespace app\community\model\db;

use think\Model;
class ExpressPickUpLocation extends Model
{
    /**
     * 获取快递公司详情
     * @author
     * @date_time 2020/08/17 17:18
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 快递公司列表
     * @author
     * @date_time 2020/08/19 14:16
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getList($where,$field,$order,$page,$limit)
    {

        $sql = $this->alias('p')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        //Cache::tag($this->cacheTag)->set($cachKey,$list,7200-mt_rand(10,30));
        return $list;
    }
}