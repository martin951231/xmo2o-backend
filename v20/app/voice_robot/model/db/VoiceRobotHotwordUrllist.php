<?php
/**
 * 语音助手关键词功能链接
 */

namespace app\voice_robot\model\db;
use think\Model;
class VoiceRobotHotwordUrllist extends Model {
    public function getList($where,$field='*')
    {
        $data = $this->where($where)->field($field)->order('xsort asc')->select()->toArray();
        return $data;
    }
}