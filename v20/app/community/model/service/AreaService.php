<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/8 14:04
 */

namespace app\community\model\service;


use app\community\model\db\Area;
use pinyin\Pinyin;

class AreaService
{
    /**
     * 获取信息
     * @author: wanziyang
     * @date_time:2020/5/8 14:05
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getAreaOne($where,$field = true) {
        // 初始化 数据层
        $db_area = new Area();
        $info = $db_area->getOne($where,$field);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }
    public function getOneAreaData($where,$field = true) {
        // 初始化 数据层
        $db_area = new Area();
        $info = $db_area->getOne($where,$field);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        }else{
            $info = array();
        }
        return $info;
    }
    /**
     * 获取地区信息
     * @author: wanziyang
     * @date_time:2020/5/9 9:49
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @param string $order
     * @return array|null|\think\Model
     */
    public function getAreaList($where,$field =true,$order='area_sort DESC,area_id ASC') {
        // 初始化 数据层
        $db_area = new Area();
        $list = $db_area->getList($where,$field,$order);
        if (!$list || $list->isEmpty()) {
            $list = [];
        }else{
            $list = $list->toArray();
        }
        return $list;
    }

    /**
     * 汉字转拼音
     * User: zhanghan
     * Date: 2022/1/25
     * Time: 9:26
     * @param $name
     * @return mixed|string
     */
    public function getAreaPinYin($name){
        $common = new Pinyin();
        $isPinyin = $common->isChinese($name);
        return $isPinyin;
    }
    
    public function getProvince(){
        $where=[
            ['area_type','=',1],
            ['is_open','=',1],
        ];
        $field='area_id as id,area_name as name';
        $list=$this->getAreaList($where,$field);
        return $list;
    }
    
    public function getCity($id,$name){
        $where=[
            ['area_pid','=',$id],
            ['is_open','=',1],
        ];
        $field='area_id as id,area_name as name';
        $list=$this->getAreaList($where,$field);
        if(!$list){
            throw new \think\Exception($name.'省份下没有已开启的城市!');
        }
        return $list;
    }
}