<?php
/**
 *配置信息
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/29 20:46
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class AppapiAppConfig extends Model{

    /**
     * @author: wanziyang
     * @date_time: 2020/4/29 20:56
     * @return array|null|Model
     */
    public function get_list(){
        $appapi_app_config = $this->select();
        return $appapi_app_config;
    }
}