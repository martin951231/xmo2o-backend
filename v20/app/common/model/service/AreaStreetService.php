<?php
/**
 * 街道/乡镇服务类
 * Author: hengtingmei
 * Date Time: 2022/02/25 10:10
 */

namespace app\common\model\service;

use app\common\model\db\AreaStreet;

class AreaStreetService
{
    public $areaStreetMod = null;

    public function __construct()
    {
        $this->areaStreetMod = new AreaStreet();
    }

    /**
     * 根据区获取乡镇
     * @param $areaId
     * @author: 张涛
     * @date: 2021/02/04
     */
    public function getStreetByAreaId($areaId)
    {
        $where = ['area_pid' => $areaId, 'is_open' => 1];
        $lists = $this->areaStreetMod->where($where)->order(['area_sort' => 'DESC', 'area_id' => 'ASC'])->select()->toArray();
        return $lists;
    }

    /**
     * 根据乡镇id获取乡镇
     * @author: 张涛
     * @date: 2021/02/05
     */
    public function getStreetById($id, $fields = '*')
    {
        return $this->areaStreetMod->where('area_id', $id)->field($fields)->findOrEmpty()->toArray();
    }


    /**
     * 获得城市区域街道信息
     * @param $param
     * @return array
     */
    public function getStreetList($param)
    {
        $id = isset($param['id']) ? $param['id'] : '';
        $name = isset($param['name']) ? $param['name'] : '';

        $where = [];
        $where[] = ['area_pid', '=', $id];
        $where[] = ['is_open', '=', 1];
        $where[] = ['area_type', '=', 0];
        
        $data = $this->getSome($where);

        $areaList = array();
        foreach ($data as $key => $value) {
            $temp = array(
                'id' => $value['area_id'],
                'name' => $value['area_name']
            );
            $areaList[] = $temp;
        }
        if (!empty($areaList)) {
            $return['error'] = 0;
            $return['list'] = $areaList;
        } else {
            $return['error'] = 1;
            $return['info'] = $name . ' 城市下没有开启了的街道！';
        }
        return $return;
    }

    /**
     * 获得街道列表
     * @param array $where 条件
     * @param string $field 字段
     * @param array|string $order 排序
     * @param int $page 页码
     * @param int $limit 每页显示数量
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $res = $this->areaStreetMod->getSome($where, $field, $order, $page, $limit);
        if(empty($res)){
            return [];
        }
        return $res->toArray();
    }
}
