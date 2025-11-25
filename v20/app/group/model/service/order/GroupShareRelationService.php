<?php
/**
 * 团购
 * Author: 衡婷妹
 * Date Time: 2020/11/27 16:00
 */

namespace app\group\model\service\order;

use app\group\model\db\GroupShareRelation;

class GroupShareRelationService
{
    public $groupShareRelationModel = null;

    public function __construct()
    {
        $this->groupShareRelationModel = new GroupShareRelation();
    }


    //获取组团购人数
    //未消费状态的订单才能分享
    public function getShareNum($uid,$orderId){
        $fid = $this->getShareFid($uid,$orderId);
        $where = [
            'r.fid'=>$fid,
            'o.status'=>0,
            'o.paid'=>1
        ];
        $field = 'SUM(o.num) num';

        $num = $this->getOnePin($where, $field);
        $num = $num['num'];
        return $num;
    }

    public function getShareFid($uid,$orderId){
        $where = [
            'r.uid'=>$uid,
            'r.order_id'=>$orderId
        ];
        $field = 'r.fid';

        $data = $this->getOnePin($where, $field);
        return $data['fid'];
    }

    /**
     * 获得一条记录
     * @return array
     */
    public function getOneByJoin($where,$field=true){

        $result = $this->groupShareRelationModel->getOneByJoin($where, $field);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupShareRelationModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupShareRelationModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->groupShareRelationModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupShareRelationModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupShareRelationModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}