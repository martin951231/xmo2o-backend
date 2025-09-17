<?php
/**
 * 语音助手关键词
 */

namespace app\voice_robot\model\db;
use think\Model;
class VoiceRobotHotword extends Model {
    /**
     * 获取指定关键字基本信息
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
    public function getList($where,$field,$pageSize,$rand=0)
    {
        $data = $this->where($where)->field($field);
        if($rand){
            $data = $data->orderRaw('rand()');
        }else{
            $data = $data->order('id desc');
        }
        $data = $data->paginate($pageSize)->toArray();
        return $data;
    }
}