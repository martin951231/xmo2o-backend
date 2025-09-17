<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 15:03
 *======================================================
 */

namespace app\community\model\service;


use app\community\model\db\HouseVillageLabel;
use app\community\model\db\HouseVillageUserLabelBind;

class HouseVillageUserLabelBindService
{
    /**
     * 获取用户绑定标签列表
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabel($where,$field){
        $db_user_label_bind = new HouseVillageUserLabelBind();
        $data = $db_user_label_bind->getUserLabelList($where,$field);
        foreach ($data as &$value){
            $value['color'] = '#ccffff';
            $value['field_color'] = '#2681F3';
        }
        return $data;
    }

    /**
     * 绑定小区用户标签
     * @param $label_ids
     * @param $bind_id
     * @return int|string
     */
    public function addUserLabelBind($label_ids,$bind_id,$village_id){
        // 获取政治面貌标签
        $house_village_label = new HouseVillageLabel();
        $political_outlook = $house_village_label->getHouseVillageLabel(array(['label_type','=',0],['village_id','=',$village_id]),'id');
        $political_outlook_data = array_column($political_outlook,'id');
        // 用户绑定标签
        $db_user_label_bind = new HouseVillageUserLabelBind();
        // 删除已有标签
        $del_data = [
            'is_delete' => 1,
            'update_at' => time()
        ];
        $db_user_label_bind->updateUserLabelBind($bind_id,$del_data);
        // 新增新标签
        $label_ids_arr = explode(',',$label_ids);
        // 政治面貌标签只能有一个
        $political_outlook_index = false;
        $data = [];
        foreach ($label_ids_arr as $value){
            if(in_array($value,$political_outlook_data) && $political_outlook_index){
                // 政治面貌标签只能有一个
                continue;
            }
            if(in_array($value,$political_outlook_data)){
                $political_outlook_index = true;
            }
            $data[] = [
                'village_label_id' => $value,
                'bind_id' => $bind_id,
                'create_at' => time(),
                'update_at' => time(),
            ];
        }
        return $db_user_label_bind->addUserLabelBind($data);
    }
}