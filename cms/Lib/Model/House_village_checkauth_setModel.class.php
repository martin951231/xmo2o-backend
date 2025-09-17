<?php

class House_village_checkauth_setModel extends Model
{

    //保存数据
    public function save_set($sdata = array())
    {
        if (empty($sdata)) {
            return false;
        }
        $where = array();
        if (isset($sdata['id'])) {
            $where['id'] = $sdata['id'];
            unset($sdata['id']);
        } else {
            if (isset($sdata['village_id'])) {
                $where['village_id'] = $sdata['village_id'];
                unset($sdata['village_id']);
            }
            if (isset($sdata['xtype'])) {
                $where['xtype'] = $sdata['xtype'];
            }else{
                return false;
            }
            if (empty($where) || empty($where['xtype'])) {
                return false;
            }
        }
        if (isset($sdata['property_id'])) {
            $where['property_id'] = $sdata['property_id'];
            unset($sdata['property_id']);
        }
        $tmpdata = $this->where($where)->find();
        if (!empty($tmpdata)) {
            $sdata['update_time'] = time();
            $ret = $this->where(array('id' => $tmpdata['id']))->data($sdata)->save();
            return $ret;
        } else {
            if (isset($where['id'])) {
                unset($where['id']);
            }
            $sdata = array_merge($sdata, $where);
            $sdata['add_time'] = time();
            $idd = $this->data($sdata)->add();
            return $idd;
        }
    }

    public function get_one_set($whereArr,$order='id desc'){
        if(empty($whereArr)){
            return array();
        }
        $tmpdata=$this->where($whereArr)->order($order)->find();
        return $tmpdata;
    }

}

?>