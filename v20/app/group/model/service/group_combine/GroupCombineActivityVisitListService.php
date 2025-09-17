<?php
/**
 * 团购优惠组合参与记录
 * Author: 衡婷妹
 * Date Time: 2020/12/24
 */

namespace app\group\model\service\group_combine;

use app\group\model\db\GroupCombineActivityVisitList;

class GroupCombineActivityVisitListService
{
    public $groupCombineActivityVisitList = null;

    public function __construct()
    {
        $this->groupCombineActivityVisitList = new GroupCombineActivityVisitList();
    }


    /**
     * 删除数据
     * @param $data array
     * @return array
     */
    public function del($where){
        if(empty($where)){
            return false;
        }

        try {
            $result = $this->groupCombineActivityVisitList->where($where)->delete();
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCombineActivityVisitList->insertGetId($data);
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
            $result = $this->groupCombineActivityVisitList->insertAll($data);
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
            $result = $this->groupCombineActivityVisitList->where($where)->update($data);
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

        $result = $this->groupCombineActivityVisitList->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where){
        $result = $this->groupCombineActivityVisitList->getCount($where);
        if(empty($result)){
            return 0;
        }

        return $result;
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupCombineActivityVisitList->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}