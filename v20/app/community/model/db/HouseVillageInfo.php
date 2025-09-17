<?php
/**
 * 小区相关信息
 * @author weili
 * @date 2020/10/13
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageInfo extends Model
{
    /**
     * Notes:获取某个字段
     * @param $where
     * @param bool $column
     * @return array|bool
     * @author: weili
     * @datetime: 2020/10/13 18:32
     */
    public function getColumn($where,$column=true)
    {
        $column = $this->where($where)->column($column);
        return $column;
    }

    /**
     * Notes: 求和
     * @param $where
     * @param $field
     * @return float
     * @author: weili
     * @datetime: 2020/10/13 18:33
     */
    public function getNum($where,$field)
    {
        $num = $this->where($where)->sum($field);
        return $num;
    }
    
    public function addOne($datas) {
        if(empty($datas)){
            return false;
        }
        $idd=$this->insertGetId($datas);
        return $idd;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }



    /**
     * 获取单个数据信息
     * @author: zhubaodi
     * @date_time: 2021/11/24 16:03
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }

    public function getVillageInfoList($whereArr,$field="*",$order='hvi.village_id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('hvi')
            ->leftJoin('house_village hv', 'hvi.village_id = hv.village_id')
            ->field($field)->where($whereArr)->order($order);
        if($page>0) {
            $result = $sql->page($page, $limit)->select();
        }else {
            $result = $sql->select();
        }
        return $result;
    }
}