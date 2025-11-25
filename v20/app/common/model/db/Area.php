<?php
/**
 * 系统后台区域model
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/9 13:54
 */

namespace app\common\model\db;
use think\Model;
class Area extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 根据区域id获取区域表的数据
     * @param $areaId
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAreaByAreaId($areaId) {
        if(empty($areaId)) {
            return false;
        }

        $where = [
            "area_id" => intval($areaId),
        ];

        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 获取当前地区的时区标识
     * @param string $field
     * @param array $where
     * @return mixed
     */
    public function getNowCityTimezone($field = 'timezone', $where = []) {
        $result = $this->where($where)->value($field);
        return $result;
    }

    /**
     * @param string $field
     * @param array $where
     * @return array
     * 获取字段值
     */
    public function getNowCityColumn($field = 'timezone', $where = []) {
        $result = $this->where($where)->column($field); 
        return $result;
    }
    
    /**
     * 根据条件返回分类列表
     * @param $where
     * @param $order 排序
     * @return array|bool|Model|null
     */
    public function getAreaListByCondition($where,$order=[],$field=true) {
        $this->name = _view($this->name);
        $result = $this->where($where)->field($field)->order($order)->select();
        return $result;
    }

}