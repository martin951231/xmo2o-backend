<?php
/**
 * 人脸相关同步信息-包含小区,楼栋,单元楼层等
 */

namespace app\community\model\db;

use think\Model;

class FaceBindAboutInfo extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;


    public function getOneColumn($where,$field = 'floor_id',$key=''){
        return $this->where($where)->column($field,$key);
    }
}