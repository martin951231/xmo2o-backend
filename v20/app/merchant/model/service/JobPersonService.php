<?php


namespace app\merchant\model\service;


use app\common\model\db\Area;
use app\common\model\db\User;
use app\merchant\model\db\JobPerson;
use app\merchant\model\db\MerchantPosition;

class JobPersonService
{
    /**
     * 技师列表
     */
    public function jobList($param)
    {
        $where = [['s.store_id', '=', $param['store_id']],['s.is_del', '=', 0]];
        $ret=(new JobPerson())->getJobPersonList($where,'s.*,m.name as job_name,u.phone','s.id desc');
        if(!empty($ret)){
            foreach ($ret as $key=>$val){
                if($val['status']==0){
                    $ret[$key]['status_txt']='未申请认证';
                }elseif ($val['status']==1){
                    $ret[$key]['status_txt']='审核中';
                }elseif ($val['status']==2){
                    $ret[$key]['status_txt']='已认证';
                }else{
                    $ret[$key]['status_txt']='未通过';
                }
                if($val['job_time']>0){
                    $remainder_seconds = abs(time() - $val['job_time']);
                    $ret[$key]['job_time'] = floor($remainder_seconds / (31536000));
                }
                if(!empty($val['headimg'])) {
                    $ret[$key]['headimg'] = replace_file_domain($ret[$key]['headimg']);
                }
                if (!$val['uid']) {
                    $ret[$key]['phone'] = '未绑定';
                }
            }
        }
        $list['list'] =$ret;
        $where1 = [['store_id', '=', $param['store_id']],['is_del', '=', 0]];
        $list['count'] =(new JobPerson())->getCount($where1);
        return $list;
    }

    /**
     * 选择岗位
     */
    public function selJob()
    {
        $where=[['status','=',0]];
        $list=(new MerchantPosition())->getSome($where,'id,name')->toArray();
        return $list;
    }
    /**
     * @param $param
     * 技师添加
     */
    public function addJob($param)
    {
        $ret = (new JobPerson())->add($param);
        return $ret;
    }


    /**
     * @param $param
     * 技师状态更新
     */
    public function updateJob($param)
    {
        $where = [['id', '=', $param['id']]];
        unset($param['id']);
        $ret = (new JobPerson())->updateThis($where, $param);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $param
     * 技师批量删除
     */
    public function delJob($param)
    {
        $where = [['id', 'in', $param['ids']]];
        $data['is_del']=1;
        $ret = (new JobPerson())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $param
     * @return bool
     * 技师编辑
     */
    public function editJob($param)
    {
        $where = [['id', '=', $param['id']]];
        $ret = (new JobPerson())->getOne($where);
        if (!empty($ret)) {
            $ret = $ret->toArray();
            if($ret['uid']>0){
                $where1=[['uid','=',$ret['uid']]];
                $user=(new User())->getOne($where1)->toArray();
                $ret['phone']=$user['phone'];
                $ret['avatar']=$user['avatar'];
                if(!empty($ret['headimg'])){
                    $ret['headimg']=replace_file_domain($ret['headimg']);
                }
            }
            $ret['job_time']=date("Y-m-d", $ret['job_time']);
            return $ret;
        } else {
            return false;
        }
    }

    /**
     * @param $param
     * @return bool
     * 验证技师
     */
    public function resJob($param)
    {
        $where = [['phone', '=', $param['phone']]];
        $ret = (new User())->getOne($where);
        if (!empty($ret)) {
            $ret = $ret->toArray();
            $where1=[['uid','=',$ret['uid']],['is_del','=',0]];
            $ret1=(new JobPerson())->getOne($where1);
            if(!empty($ret1)){
                return false;
            }else{
                return $ret;
            }
            //$ret['avatar']=empty($ret['avatar'])?replace_file_domain():"";
        } else {
            return false;
        }
    }

    /**
     * @param $where
     * @return bool|mixed
     * 查看用户是不是技师
     */
    public function findPerson($where,$field){
        $ret=(new JobPerson())->getJobPerson($where,$field);
        if (!empty($ret)) {
            $ret['status']=1;//有值
            $ret['city_name']="";
            $where1=[['area_id','=',$ret['city_id']],['area_type','=',2]];
            $city=(new Area())->getOne($where1,'area_name');
            if(!empty($city)){
                $ret['city_name']=$city['area_name'];
            }
        }else{
            $ret['status']=0;//有值
            $ret['sex']=0;
            $ret['store_name']="";
            $ret['job_name']="";
            $ret['city_name']="";
        }
        return $ret;
    }
}