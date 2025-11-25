<?php
/**
 * 系统后台站点group
 * Author: chenxiang
 * Date Time: 2020/5/18 10:40
 */

namespace app\common\model\service;

use app\common\model\db\ConfigGroup;

class ConfigGroupService
{
    public $configGroupObj = null;
    public function __construct()
    {
        $this->configGroupObj = new ConfigGroup();
    }

    /**
     * 获取配置项分组信息
     * @param bool $field
     * @param array $where
     * @param string $sort
     * @param array $config
     * @return array
     */
    public function getConfigGroupList($field = true, $where = [], $sort = '', $config = []){
        $group_list = $this->configGroupObj->getConfigGroupList($field, $where, $sort);

        if(empty($group_list->toArray())) {
            return [];
        }

        foreach($group_list as &$gListValue){
            $gListValue['gname'] = str_replace('订餐',$config['meal_alias_name'],$gListValue['gname']);
            if(strpos($gListValue['gname'], '社区团购')!==false){
                $gListValue['gname'] = str_replace('社区团购', $config['village_group_alias'] ?? '社区团购', $gListValue['gname']);
            }elseif(strpos($gListValue['gname'], '社区拼团')!==false){
                $gListValue['gname'] = str_replace('社区拼团', $config['village_group_alias'] ?? '社区拼团', $gListValue['gname']);
            }elseif (strpos($gListValue['gname'], '团购')!==false){
                $gListValue['gname'] = str_replace('团购',$config['group_alias_name'],$gListValue['gname']);
            }

            $gListValue['gname'] = str_replace('预约',$config['appoint_alias_name'],$gListValue['gname']);
            $gListValue['gname'] = str_replace('礼品',$config['gift_alias_name'],$gListValue['gname']);
            $gListValue['gname'] = str_replace('快店',$config['shop_alias_name'],$gListValue['gname']);
        }

        return $group_list->toArray();
    }

    /**
     * 获取一条数据
     * @param array $where
     * @return array|\think\Model|null
     */
    public function getDataOne($where = []) {
        $data = $this->configGroupObj->getDataOne($where);
        return $data;
    }
}
