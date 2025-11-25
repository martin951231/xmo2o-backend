<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/28 9:38
 */

namespace app\community\model\service;

use app\community\model\db\ApplicationBind;

class ApplicationService
{

    /**
     * 获取应用application_id组成的数组
     * @author: wanziyang
     * @date_time: 2020/4/28 9:38
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function get_application_id_arr($where){
        $db_application_bind = new ApplicationBind();
        $info = $db_application_bind->getApplicationIdArr($where);
        $arr = [];
        if (!($info->isEmpty())) {
            foreach ($info as $val) {
                if ($val['application_id']) {
                    $arr[] = $val['application_id'];
                }
            }
        }
        return $arr;
    }

    /**
     * 获取我的应用
     * @author lijie
     * @date_time 2020/09/02 15:01
     * @param $where
     * @param bool $filed
     * @return array|\think\Model|null
     */
    public function getAppBind($where,$filed=true)
    {
        $app_bind = new ApplicationBind();
        $data = $app_bind->getOne($where,$filed);
        return $data;
    }
}