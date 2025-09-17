<?php
/**
 * 团购商品规格
 * Author: 衡婷妹
 * Date Time: 2020/11/27 13:51
 */

namespace app\group\model\service;

use app\group\model\db\GroupSpecifications;
use redis\Redis;
use think\facade\Db;

class GroupSpecificationsService
{
    public $groupSpecificationsModel = null;

    public function __construct()
    {
        $this->groupSpecificationsModel = new GroupSpecifications();
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupSpecificationsModel->insertGetId($data);
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
            $result = $this->groupSpecificationsModel->insertAll($data);
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
            $result = $this->groupSpecificationsModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

     /**
     * 增加redis
     *
     * @param int $groupId
     * @param array $specificationsInfo 规格信息
     * @return void
     * @author: 衡婷妹
     * @date: 2021/05/11
     */
    public function addRedisStock($groupId, $specificationsId, $num)
    {
        try {
            $redis = new Redis();
        } catch (\Exception $e) {
            return false;
        }
        $key = 'group_'.$groupId.'-s_'.$specificationsId;
        for($i=0; $i<$num; $i++){
            $redis->lpush($key,1);
        }
        return true;
    }

    /**
     * 库存处理
     * @param $specificationsId int 规格id
     * @param $num int 修改数量
     * @param $type 操作类型 1-减少库存 2 增加库存
     * @return array
     */
    public function updateStock($specificationsId, $num, $type=1)
    {

        if (empty($specificationsId) || empty($num)) {
            return false;
        }

        $where = [
            'specifications_id' => $specificationsId
        ];
        $specificationsInfo = $this->getOne($where);
        if($type == 1){
            $res = $this->groupSpecificationsModel->where($where)->inc('sale_count', $num)->update();
            /*if($specificationsId && $specificationsInfo['count_num']>0){
                $this->groupSpecificationsModel->where($where)->dec('count_num', $num)->update();
            }*/
        }else{
            $res = $this->groupSpecificationsModel->where($where)->dec('sale_count', $num)->update();
        }


        $saveData = [
            'last_time' => time()
        ];
        $this->groupSpecificationsModel->where($where)->save($saveData);

        if($res===false){
            return false;
        }

        // 更新销售总量
        (new GroupService)->updateStockTotal($specificationsInfo['group_id'], $num, $type);
        return true;
    }

    // 计算团购的规格是否所有均售空
    public function getSpecSurplusCount($groupId) {
        $where = [
            'group_id' => $groupId,
            'status' => 1
        ];
        $specInfo = $this->getSome($where);
        // 是否还有剩余未售空
        $isSurplus = false;
        if ($specInfo) {
            foreach ($specInfo as $val) {
                if ($val['count_num'] <= 0) {
                    $isSurplus = true;
                    break;
                }
                if (intval($val['count_num']) > intval($val['sale_count'])) {
                    $isSurplus = true;
                }
            }
        }
        return $isSurplus;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $field=true, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupSpecificationsModel->getOne($where,$field, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取总数
     * @param array $where 
     * @param string $field
     * @return array
     */
    public function getFieldSum($where, $field='sale_count'){
        $result = $this->groupSpecificationsModel->where($where)->sum($field);
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
            $result = $this->groupSpecificationsModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}