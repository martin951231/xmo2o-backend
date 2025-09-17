<?php


namespace app\community\model\service;

use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseCameraDeviceVtype;
use think\Exception;

class HouseCameraDeviceVtypeService
{

    public function getOneData($where, $field = true)
    {

        $db_houseCameraDeviceVtype = new HouseCameraDeviceVtype();
        $info = $db_houseCameraDeviceVtype->getOne($where, $field);
        return $info;
    }

    public function addOneData($addData = array())
    {
        if (empty($addData)) {
            return false;
        }
        $db_houseCameraDeviceVtype = new HouseCameraDeviceVtype();
        if (!isset($addData['add_time'])) {
            $addData['add_time'] = time();
        }
        $idd = $db_houseCameraDeviceVtype->addOneData($addData);
        return $idd;
    }

    //更新数据
    public function updateOneData($where = array(), $updateArr = array())
    {
        if (empty($where) || empty($updateArr)) {
            return false;
        }
        $db_houseCameraDeviceVtype = new HouseCameraDeviceVtype();
        return $db_houseCameraDeviceVtype->updateOneData($where, $updateArr);
    }

    /**
      * 获取列表数据
     */
    public function getDataLists($where=array(), $field = true, $order = 'a.id DESC',$page=0, $limit = 20)
    {
        $retdata=array('list'=>array(),'count'=>0,'total_limit'=>$limit);
        $db_houseCameraDeviceVtype = new HouseCameraDeviceVtype();
        $count=$db_houseCameraDeviceVtype->getCount($where);
        $count=$count>0 ? $count:0;
        $list = $db_houseCameraDeviceVtype->getDataLists($where, $field, $order, $page, $limit);
        if (!empty($list)) {
            $service_house_face_device = new HouseCameraDevice();
            foreach ($list as $kk=>$vv){
                $update_time=$vv['update_time'] > 0 ? $vv['update_time']:$vv['add_time'];
                $list[$kk]['updatetime_str']=date('Y-m-d H:i:s',$update_time);
                $list[$kk]['relation_count']=0;
                if($vv['xtype']==0){
                    $whereArr=array('village_id'=>$vv['village_id'],'device_type'=>$vv['id']);
                    $relation_count=$service_house_face_device->getCount($whereArr);
                    $list[$kk]['relation_count']=$relation_count>0 ? $relation_count:0;
                }
            }
            $retdata['list']=$list;
        }
        return $retdata;
    }

}