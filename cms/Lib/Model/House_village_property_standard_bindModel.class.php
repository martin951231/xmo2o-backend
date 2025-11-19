<?php
class House_village_property_standard_bindModel extends Model
{
    /**
     * 获取用户绑定物业收费列表
     * @author lijie
     * @date_time 2020/07/23
     * @param $where
     * @param bool $fields
     * @param string $order
     * @param int $pageSize
     * @return array|bool
     */
    public function getLists($where , $fields = true , $order = 'sb.id desc',$pageSize = 20)
    {
        if(!$where){
            return false;
        }

        import('@.ORG.merchant_page');
        $count = $this->alias('sb')->where($where)->count();
        $p = new Page($count , $pageSize , 'page');

        $house_village_proerty_list = M('House_village_property_standard_bind')
            ->join('as sb left join '.C('DB_PREFIX').'house_village_single s ON s.id = sb.single_id')
            ->join(C('DB_PREFIX').'house_village_floor f ON f.floor_id = sb.floor_id')
            ->join(C('DB_PREFIX').'house_village_layer l ON l.id = sb.layer_id')
            ->join(C('DB_PREFIX').'house_village_user_vacancy v ON v.pigcms_id = sb.pigcms_id')
            ->where($where)
            ->field($fields)
            ->order($order)
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        $list['list'] = $house_village_proerty_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }

    /**
     * 解绑用户物业收费标准
     * @author lijie
     * @date_time 2020/07/23
     * @param $where
     * @param $data
     * @return bool
     */
    public function relieve($where,$data)
    {
        $res = $this->where($where)->data($data)->save();
        if($res){
            return true;
        }else{
            return false;
        }
    }
}