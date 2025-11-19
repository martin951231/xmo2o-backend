<?php
/**
 * 限时优惠规格价格库存表
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/20 10:25
 */

namespace app\shop\model\db;
use think\Model;
class TimeLimitedDiscountGoodsSpec extends Model {
    /**
     * 根据id更新数据
     * @param $id
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateById($id,$data) {
        if(!$id || $data){
            return false;
        }

        $where = [
            'id' => $id
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 根据limitId更新数据
     * @param $limitId
     * @param $data
     * @return array|bool|Model|null
     */
    public function updateByLimitId($limitId,$data) {
        if(!$id || $data){
            return false;
        }

        $where = [
            'limit_id' => $limitId
        ];

        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 根据限时优惠id获取规格信息
     * @param $limitId
     * @param $field
     * @return array|bool|Model|null
     */
    public function getSpecList($limitId,$field='*') {
        if(!$limitId){
            return false;
        }

        $where = [
            'limit_id' => $limitId
        ];

        $result = $this->where($where)->select();
        return $result;
    }
    
    /**
     * 根据id获取
     * @param $id
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpecById($id) {
        if(empty($id)) {
            return false;
        }

        $where = [
            'id' => $id
        ];
         
        $result = $this->where($where)->find();
        return $result;
    }
}