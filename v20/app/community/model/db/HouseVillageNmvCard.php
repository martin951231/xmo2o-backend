<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 15:14
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class HouseVillageNmvCard extends Model
{
    /**
     * 获取非机动车卡号信息
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCardInfo($where,$field){
        $res = $this->where($where)->field($field)->find();
        if($res && !$res->isEmpty()){
            return $res->toArray();
        }else{
            return [];
        }
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return bool
     */
    public function updateCardInfo($where,$data){
        $data['update_at'] = time();
        return $this->where($where)->save($data);
    }

    /**
     * 插入数据
     * @param $data
     * @return int|string
     */
    public function addCard($data){
        return $this->insertGetId($data);
    }

    /**
     * 获取非机动车卡列表
     * @param $where
     * @param int $page
     * @param int $limit
     * @param bool $field
     * @param string $order
     * @return array
     */
    public function getNmvList($where,$page = 0,$limit = 10,$field = true,$order = 'c.id DESC'){
        $data = [];
        if(empty($where)){
            return $data;
        }
        $sql = $this->alias('c')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = c.bind_id')
            ->where($where)->field($field)->order($order);
        if($page > 0){
            $count = $sql->count();
            $list = $sql->page($page,$limit)->select();
            $data['list'] = [];
            if($list && !$list->isEmpty()){
                $data['list'] = $list->toArray();
            }
            $data['count'] = $count;
        }else{
            $data = $sql->select();
            if($data && !$data->isEmpty()){
                $data = $data->toArray();
            }
        }
        return $data;
    }

    /**
     * 获取业主非机动车卡信息
     * @param $where
     * @param bool $field
     * @return array
     */
    public function getNmvInfo($where,$field = true){
        if(empty($where)){
            return [];
        }
        $data = $this->alias('c')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = c.bind_id')
            ->where($where)
            ->field($field)
            ->find();
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            return [];
        }
    }
}