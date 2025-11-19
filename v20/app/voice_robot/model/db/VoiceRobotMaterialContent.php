<?php
/**
 * 素材分类内容
 */

namespace app\voice_robot\model\db;
use think\Model;
class VoiceRobotMaterialContent extends Model {
    /**
     * 获取指定分类内容基本信息
     */
    public function detail($where,$field)
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
        $data = $this->where($where)->field($field)->order('material_id desc')->paginate($pageSize)->toArray();
        return $data;
    }
}