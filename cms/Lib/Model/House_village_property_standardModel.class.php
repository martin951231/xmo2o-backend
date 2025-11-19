<?php


class House_village_property_standardModel extends Model
{
    /**
     * 添加物业缴费规则
     * @author lijie
     * @date_time 2020/07/22
     * @param $data
     * @return mixed
     */
    public function house_village_property_standard_add($data)
    {
        $res = $this->data($data)->add();
        if($res)
            return array('status'=>1,'msg'=>'添加成功！');
        else
            return array('status'=>0,'msg'=>'添加失败！');
    }

    /**
     * 编辑物业缴费规则
     * @author lijie
     * @date_time 2020/07/23
     * @param $where
     * @param $data
     * @return array
     */
    public function house_village_property_standard_edit($where,$data)
    {
        $res = $this->where($where)->data($data)->save();
        if($res)
            return array('status'=>1,'msg'=>'添加成功！');
        else
            return array('status'=>0,'msg'=>'添加失败！');
    }

    /**
     * 获取物业费标准列表
     * @author lijie
     * @date_time 2020/07/23
     * @param $where
     * @param bool $fields
     * @param string $order
     * @param int $pageSize
     * @return array|bool
     */
    public function getLists($where , $fields = true , $order = 'id desc',$pageSize = 20)
    {
        if(!$where){
            return false;
        }

        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count , $pageSize , 'page');

        $house_village_proerty_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $list['list'] = $house_village_proerty_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }
}