<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/27
 * Time: 18:02
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class UserTrajectoryLog extends Model
{
    public function addUserTrajectoryLog($data){
        $this->insert($data);
    }
}