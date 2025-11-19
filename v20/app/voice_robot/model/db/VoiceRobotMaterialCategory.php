<?php
/**
 * 素材分类
 */

namespace app\voice_robot\model\db;
use think\Model;
class VoiceRobotMaterialCategory extends Model {
    /**
     * 获取指定分类基本信息
     */
    public function detail($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        $info = $info ? $info->toArray() : $info;
        return $info;
    }
    
    /**
     * 查询列表
     */
    public function getList($where,$field,$pageSize)
    {
        if(!$pageSize){
            $data = $this->where($where)->field($field)->order('cate_id desc')->select()->toArray();
        }else{
            $data = $this->where($where)->field($field)->order('cate_id desc')->paginate($pageSize)->toArray();
        }
        return $data;
    }
}