<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 23:35
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class HouseVillageThirdUserInfo extends Model
{
    /**
     * 获取小区三方对接用户信息
     * @param $where
     * @param bool $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field = true,$whereOr = []){
        if(empty($where)){
            return [];
        }
        $sql = $this->field($field);
        if(!empty($whereOr)){
            $sql->whereOr([$where,$whereOr]);
        }else{
            $sql->where($where);
        }
        $data = $sql->find();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }

    /**
     * 修改数据
     * @param $where
     * @param $data
     */
    public function saveData($where,$data){
        if(empty($where)){
            return 0;
        }
        return $this->where($where)->save($data);
    }

    /**
     * 添加数据
     * @param $data
     * @return int|string
     */
    public function addData($data){
        if(empty($data)){
            return 0;
        }
        return $this->insert($data);
    }

    /**
     * 批量插入数据
     * @param $data
     * @return int
     */
    public function addDataAll($data){
        if(empty($data)){
            return 0;
        }
        return $this->insertAll($data);
    }

    /**
     * 获取小区三方对接用户列表
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getThirdUserList($where,$field = true,$page=0,$limit=10){
        if(empty($where)){
            return [];
        }
        $count = $this->where($where)->count();
        $sql = $this->where($where)->field($field)->order('third_user_id DESC');
        if($page > 0){
            $data = $sql->page($page,$limit)->select();
        }else{
            $data = $sql->select();
        }
        if($data && !$data->isEmpty()){
            return [
                'list' => $data->toArray(),
                'count' => $count
            ];
        }else{
            return [
                'list' => [],
                'count' => 0
            ];
        }
    }


    public function getThirdUserLists($where,$field=true,$order='t.third_user_id DESC')
    {
        $data = $this->alias('t')
            ->leftJoin('house_village_user_bind b','b.third_id = t.third_ryid')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }

}