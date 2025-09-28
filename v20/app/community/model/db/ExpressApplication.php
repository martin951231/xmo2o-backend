<?php


namespace app\community\model\db;

use think\Model;
class ExpressPickUpLocation extends Model
{
    /**
     * 获取地址详情
     * @author mashuaifei
     * @date_time 2025/09/17
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
     * 获取地址列表
     * @author mashuaifei
     * @date_time 2025/09/17
     * @param $where
     * @param bool $field
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

    /**
     * 查询总数
     * @author mashuaifei
     * @date_time 2025/09/17
     * @param array $data
     * @return int|string
     */
    public function getCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }

    /**
     * 添加地址
     * @author mashuaifei
     * @date_time 2025/09/17
     * @param array $data
     * @return int|string
     */
    public function addOne($data=[])
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    /**
     * 修改地址
     * @author mashuaifei
     * @date_time 2025/09/17
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveOne($where=[],$data=[])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

}