<?php


namespace app\community\model\service;
use app\community\model\db\WisdomQrcode;
use app\community\model\db\WisdomQrcodeCate;
use app\community\model\db\WisdomQrcodePerson;
use app\community\model\db\WisdomQrcodeRecordLog;
use app\community\model\db\WisdomQrcodePositionIndex;

class WisdowQrcodeService
{
    public $wisdomQrcodeCateModel = '';
    public $wisdomQrcodePersonModel = '';
    public $wisdomQrcodeRecordLogModel = '';
    public $wisdomQrcode = '';
    public function __construct()
    {
        $this->wisdomQrcodeCateModel = new WisdomQrcodeCate();
        $this->wisdomQrcodePersonModel = new WisdomQrcodePerson();
        $this->wisdomQrcodeRecordLogModel = new WisdomQrcodeRecordLog();
        $this->wisdomQrcode = new WisdomQrcode();
    }

    /**
     * 获取巡检列表
     * @author lijie
     * @date_time 2020/08/04 14:20
     * @param $where
     * @param bool $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWisdomQrcodeCateList($where,$field=true)
    {
        $list = $this->wisdomQrcodeCateModel->getLists($where,$field);
        return $list;
    }

    public function getWisdomQrcodeCateOne($where,$field=true)
    {
        $list = $this->wisdomQrcodeCateModel->getFind($where,$field);
        return $list;
    }

    public function getWisdomQrcodeCateLists($where,$field=true)
    {
        $list = $this->wisdomQrcodeCateModel->getSelect($where,$field);
        return $list;
    }
    /**
     * 取巡检人员
     * @author lijie
     * @date_time 2020/08/04 16:33
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getWisdomQrcodePerson($where,$field=true)
    {
        $data = $this->wisdomQrcodePersonModel->getList($where,$field);
        return $data;
    }

    public function updateWisdomQrcodeRecordLog($where,$data = [])
    {
        if( empty($data) ){
            return false;
        }
        $res =  $this->wisdomQrcodeRecordLogModel->saveOneData($where,$data);
        return $res;
    }

    /**
     * 获取巡检记录数
     * @author lijie
     * @date_time 2020/08/04 16:49
     * @param $where
     * @return int
     */
    public function getRecordCount($where)
    {
        $count = $this->wisdomQrcodeRecordLogModel->getCount($where);
        return $count;
    }
    /**
     * 获取巡检点位数据
     * @author lijie
     * @date_time 2020/08/04 16:49
     * @param $where
     * @return int
     */
    public function getQrcodeOne($where)
    {
        $list = $this->wisdomQrcode->getFind($where);
        return $list;
    }

    /**
     * 判断是否完成任务
     * @param $data 任务下的所有人员
     * @return bool
     */
    public function isComplete($data,$type=1)
    {
        foreach ($data as $k=>$v){
            $where = array();
            if($v['record_type'] == 1){
                $where[] = ['cate_id','=',$v['cate_id']];
                $where[] = ['uid','=',$v['uid']];
                $time = strtotime(date('Y-m-d',time()));
                $where[] = ['add_time','>=',$time];
                $count = $this->getRecordCount($where);
                $count1 = $this->wisdomQrcode->getCount(['cate_id'=>$v['cate_id']]);
                if($count >= $count1)
                    $data[$k]['is_com'] = 1;
                else
                    $data[$k]['is_com'] = 0;
            }elseif ($v['record_type'] == 2){
                $where[] = ['cate_id','=',$v['cate_id']];
                $where[] = ['uid','=',$v['uid']];
                $time = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
                $where[] = ['add_time','>=',$time];
                $count = $this->getRecordCount($where);
                $count1 = $this->wisdomQrcode->getCount(['cate_id'=>$v['cate_id']]);
                if($count >= count(explode(',',$v['record_time']))*$count1)
                    $data[$k]['is_com'] = 1;
                else
                    $data[$k]['is_com'] = 0;
            }elseif($v['record_type'] == 3){
                $where[] = ['cate_id','=',$v['cate_id']];
                $where[] = ['uid','=',$v['uid']];
                $time = mktime(0,0,0,date('m'),1,date('Y'));
                $where[] = ['add_time','>=',$time];
                $count = $this->getRecordCount($where);
                $count1 = $this->wisdomQrcode->getCount(['cate_id'=>$v['cate_id']]);
                if($count >= count(explode(',',$v['record_time']))*$count1)
                    $data[$k]['is_com'] = 1;
                else
                    $data[$k]['is_com'] = 0;
            }
        }
        $group1 = array();
        $group2 = array();
        $group3 = array();
        foreach ($data as $key=>$value){
            if($value['record_type'] == 1)
                $group1[$key] = $value;
            if($value['record_type'] == 2)
                $group2[$key] = $value;
            if($value['record_type'] == 3)
                $group3[$key] = $value;
        }
        if(count($group1) >= 1){
            $res = $this->is_com($group1,$type);
            return $res;
        }
        if(count($group2) >= 1){
            $res = $this->is_com($group2,$type);
            return $res;
        }
        if(count($group3) >= 1){
            $res = $this->is_com($group3,$type);
            return $res;
        }
        return false;
    }

    /**
     * 判断分组是否有完成任务的
     * @author lijie
     * @date_time 2020/08/04 17:29
     * @param $group
     * @param $type
     * @return bool
     */
    public function is_com($group,$type=1)
    {
        if($type == 1){//默认为：按单个负责人逻辑（如果是其中一个负责人巡检完成，只有当前负责人算完成，其他负责人不算完成巡检）
            foreach ($group as $k=>$v){
                if($v['is_com'] == 0)
                    return false;
            }
            return true;
        }else{
            foreach ($group as $k=>$v){//只有其中一个负责人巡检过所有的二维码点位，所有负责人都算已完成巡检
                if($v['is_com'] == 1)
                    return true;
            }
            return false;
        }
    }
    
    public function getPropertyCheckTask($village_id_arr=array(),$property_id=0){
        $taskCount=0;
        if(empty($village_id_arr)){
           return $taskCount;
        }
        $whereArr=array();
        $whereArr[]=array('village_id','in',$village_id_arr);
        $taskCate=$this->wisdomQrcodeCateModel->getLists($whereArr,'id');
        $cate_id_arr=array();
        $alltask=0;
        if(!empty($taskCate)){
            foreach($taskCate as $tcv){
                $cate_id_arr[]=$tcv['id'];
            }
            $whereArr=array();
            $whereArr[]=array('cate_id','in',$cate_id_arr);
            $wisdomQrcodeObj=$this->wisdomQrcode->getList($whereArr,'id,cate_id');
            $cateQrcode=array();
            $qrcode_id_arr=array();
            if($wisdomQrcodeObj && !$wisdomQrcodeObj->isEmpty()){
                $wisdomQrcode=$wisdomQrcodeObj->toArray();
                foreach ($wisdomQrcode as $vv){
                    $qrcode_id_arr[]=$vv['id'];
                    if(isset($cateQrcode[$vv['cate_id']])){
                        $cateQrcode[$vv['cate_id']]['qrcode']++;
                    }else{
                        $cateQrcode[$vv['cate_id']]['qrcode']=1;
                    }
                }
            }
            $dayNum=date('j');
            $weekNum=date('N');
            $whereStr='status=1 AND cate_id in ('.implode(',',$cate_id_arr).') AND (record_type=1 OR (record_type=2 AND FIND_IN_SET('.$weekNum.',record_time)) OR (record_type=3 AND FIND_IN_SET('.$dayNum.',record_time)))';
            $wisdomPerson=$this->wisdomQrcodePersonModel->getRawList($whereStr,'id,uid,cate_id');
            $catePerson=array();
            $uid_id_arr=array();
            if(!empty($wisdomPerson)){
                foreach ($wisdomPerson as $pvv){
                    $uid_id_arr[]=$pvv['uid'];
                    if(isset($catePerson[$pvv['cate_id']])){
                        $catePerson[$pvv['cate_id']]['person']++;
                    }else{
                        $catePerson[$pvv['cate_id']]['person']=1;
                    }
                }
                foreach($catePerson as $cate_id_key=>$pp){
                    if(isset($cateQrcode[$cate_id_key])){
                        $catetask=$cateQrcode[$cate_id_key]['qrcode']*$pp['person'];
                        $alltask  +=$catetask;
                    }
                }
            }
            
            if($alltask>0){
                $dateday=date('Y-m-d');
                $start_time=strtotime($dateday);
                $end_time=$start_time+86399;
                $whereArr=array();
                $whereArr[]=array('cate_id','in',$cate_id_arr);
                $whereArr[]=array('qrcode_id','in',$qrcode_id_arr);
                $whereArr[]=array('uid','in',$uid_id_arr);
                $whereArr[]=array('add_time','between',[$start_time,$end_time]);
                $xcount=$this->wisdomQrcodeRecordLogModel->getCount($whereArr);
                $taskCount=$alltask-$xcount;
                $taskCount=$taskCount>0 ? $taskCount:0;
            }
        }
        return $taskCount;
    }
    public function getOnePositionIndex($where=array(),$field='*',$order='id DESC'){
        $wisdomQrcodePositionIndexDb=new WisdomQrcodePositionIndex();
        $positionIndex=$wisdomQrcodePositionIndexDb->getFind($where,$field,$order);
        $onedata=array();
        if($positionIndex && !$positionIndex->isEmpty()){
            $onedata=$positionIndex->toArray();
        }
        return $onedata;
    }

}