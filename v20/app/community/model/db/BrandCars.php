<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/11 11:38
 */
namespace app\community\model\db;
use think\Model;
use think\Db;

class BrandCars extends Model {
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: zhubaodi
     * @datetime: 2021/11/11 11:38
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * 根据条件获取列表
     * @author zhubaodi
     * @date_time 2021/11/10
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true,$page=0,$limit=15,$order='first_name ASC')
    {
        if ($page){
            $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $data = $this->where($where)->field($field)->order($order)->select();
        }
        //  print_r($data);exit;
        return $data;
    }
}