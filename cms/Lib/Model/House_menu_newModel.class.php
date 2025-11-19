<?php
/**
* Class Face_door_a5_serviceModel
* @author weili
* @datetime 2020/07/20
*/
class House_menu_newModel extends Model
{
    //获取是否添加收费管理应用
    public function getPayCostAppIsUsable($village_id)
    {
        $app_where = array(
            'b.from' => 0,
            'b.use_id' => intval($village_id),
//            'b.other_id' => intval($property_id),
            'b.status' => 0,
            'n.id'=>65
        );
        $app_count = M('House_menu_new')
            ->join('as n LEFT JOIN '.C('DB_PREFIX').'application_bind as b ON n.application_id=b.application_id')
            ->where($app_where)
            ->count();
        return $app_count;
    }

    // 处理下目录权限结构数据
    public function repair_menus_info($menus_arr) {
        if (!$menus_arr) {
            return $menus_arr;
        }
        if (!is_array($menus_arr)) {
            $menus_arr = explode(',', $menus_arr);
        }
        $where_val = [];
        $where_val['id'] = ['in', $menus_arr];
        $where_val['type'] = 2;
        $menus_arr_msg = $this->where($where_val)->order('sort DESC,id asc')->field('id,fid')->select();
        foreach ($menus_arr_msg as &$val) {
            if ($val['fid']>0){
                if (!in_array($val['fid'],$menus_arr)) {
                    $menus_arr[] = $val['fid'];
                }
                $ffid = $this->where(['id'=>$val['fid']])->getField('fid');
                if ($ffid>0){
                    if (!in_array($ffid,$menus_arr)) {
                        $menus_arr[] = $ffid;
                    }
                    $fffid = $this->where(['id'=>$ffid])->getField('fid');
                    if ($fffid>0 && !in_array($fffid,$menus_arr)){
                        $menus_arr[] = $fffid;
                    }
                }
            }
        }
        if ($menus_arr_msg && is_array($menus_arr_msg) && !in_array(430,$menus_arr_msg)) {
            $menus_arr_msg[] = 430;
        }
        return $menus_arr;
    }
}