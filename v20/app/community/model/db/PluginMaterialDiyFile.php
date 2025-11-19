<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/22
 * Time: 10:48
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class PluginMaterialDiyFile extends Model
{
    /**
     * 获取列表
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$page=0,$limit=10,$field='*',$order='file_id desc')
    {
        $sql = $this->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        if($list &&  !$list->isEmpty()){
            $list = $list->toArray();
            return $list;
        }else{
            return [];
        }
    }

    /**
     * 获取查询条件下的总条数
     * @param $where
     * @return int
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 修改数据
     * @param $where
     * @param $data
     * @return bool
     */
    public function updatePluginMaterialDiyFile($where,$data){
        return $this->where($where)->save($data);
    }

    /**
     * 插入数据
     * @param $data
     * @return int|string
     */
    public function addPluginMaterialDiyFile($data){
        return $this->insert($data);
    }
}