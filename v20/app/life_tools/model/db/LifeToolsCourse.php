<?php
/**
 * 景区体育健身-课程子表model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsCourse extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获取课程所有授课老师
     */
    public function getAllCoach($tools_id) {
        $coach = '';
        $list = $this->where(['tools_id' => $tools_id])->column('coach');
        if (!empty($list)) {
            $coach = implode('/', $list);
        }
        return $coach;
    }

}