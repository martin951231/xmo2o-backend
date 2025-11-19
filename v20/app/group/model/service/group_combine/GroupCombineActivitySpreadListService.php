<?php
/**
 * 团购优惠组合佣金获得记录
 * Author: 衡婷妹
 * Date Time: 2020/12/24
 */

namespace app\group\model\service\group_combine;

use app\group\model\db\GroupCombineActivitySpreadList;

class GroupCombineActivitySpreadListService
{
    public $groupCombineActivitySpreadListModel = null;

    public function __construct()
    {
        $this->groupCombineActivitySpreadListModel = new GroupCombineActivitySpreadList();
    }

    /**
     * 获取排行榜前十名
     * @param $where array
     * @return array
     */
    public function getTopListByCombineId($combineId){
        $list = $this->groupCombineActivitySpreadListModel
                ->field('sum(spread_money) as spread_money,sum(spread_num) as spread_num,combine_id,group_name,avatar,user_name,uid')
                ->where(['combine_id'=>$combineId])
                ->group('user_name')
                ->order(['spread_money'=>'DESC'])
                ->limit(10)
                ->select();
        if(empty($list)){
            return [];
        }
        $list = $list->toArray();
        foreach ($list as &$value){
            $value['spread_money'] = get_format_number($value['spread_money']);
        }
        return $list;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCombineActivitySpreadListModel->insertGetId($data);
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
            $result = $this->groupCombineActivitySpreadListModel->insertAll($data);
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
            $result = $this->groupCombineActivitySpreadListModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
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
            $result = $this->groupCombineActivitySpreadListModel->where($where)->delete();
//            var_dump($this->groupCombineActivitySpreadListModel->getLastSql());
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where){
        $result = $this->groupCombineActivitySpreadListModel->getCount($where);
        if(empty($result)){
            return 0;
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

        $result = $this->groupCombineActivitySpreadListModel->getOne($where, $order);
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
            $result = $this->groupCombineActivitySpreadListModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}