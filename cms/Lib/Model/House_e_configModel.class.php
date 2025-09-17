<?php


class House_e_configModel extends Model
{

    /**
     * 新增小区电子发票信息配置
     * @param $data 传参
     * @author lijie
     * @date_time 2020/07/06
     * @return array|bool
     */
    public function addConfig($data)
    {
        if(!$data){
            return false;
        }
        $insert_id = $this->data($data)->add();
        if($insert_id){
            return array('status'=>1,'msg'=>L_('添加成功！'));
        }else{
            return array('status'=>0,'msg'=>L_('添加失败！'));
        }
    }

    /**
     * 编辑小区电子发票信息配置
     * @param $where
     * @param $data
     * @author lijie
     * @date_time 2020/07/06
     * @return array|bool
     */
    public function editConfig($where,$data)
    {
        if(!$data || !$where){
            return false;
        }
        $res = $this->where($where)->data($data)->save();
        if($res){
            return array('status'=>1,'msg'=>L_('编辑成功！'));
        }else{
            return array('status'=>0,'msg'=>L_('编辑失败！'));
        }
    }

    /**
     * * 根据小区id获取小区电子发票配置
     * @author lijie
     * @date_time 2020/07/06
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getConfig($where,$field='*')
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

}