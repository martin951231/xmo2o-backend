<?php


class House_new_repair_cateModel extends Model{


    //获取工单分类和绑定的工作人员
    public function get_cate_list($village_id){
        $cate_list=$worker_list=[];
        $House_new_repair_cate_bind_worker=D('House_new_repair_cate_bind_worker');
        $House_worker=D('House_worker');
        $list=$this->where(['village_id'=>$village_id,'status'=>1])->field('id,parent_id,cate_name')->order('sort desc,id desc')->select();
        if($list){
            $bind_worker=$House_new_repair_cate_bind_worker->where(['village_id'=>$village_id])->field('cate_id,wid')->select();
            $cate_list=$data2=[];
            foreach ($list as $v){
                if($v['parent_id'] == 0){
                    $cate_list[]=$v;
                }else{
                    $data2[]=$v;
                }
            }
            foreach ($cate_list as &$v1){
                foreach ($data2 as $v2){
                    if($v1['id'] == $v2['parent_id']){
                        $value=0;
                        if($bind_worker){
                            foreach ($bind_worker as $v21){
                                if($v21['cate_id'] == $v2['id']){
                                    $value=$v21['wid'];
                                    break;
                                }
                            }
                        }
                        $v2['value']=$value;
                        $v1['children'][]=$v2;
                    }
                }
            }
            unset($v1);
            $worker_list = $House_worker->where(['status' => 1,'is_del' => 0, 'village_id' => $village_id])->field('wid,type,name')->order('type asc')->select();

        }
        return ['cate_list'=>$cate_list,'worker_list'=>$worker_list];
    }


    //工作分类绑定工作人员
    public function cate_bind_worker($village_id,$param){
        $data=[];
        D('House_new_repair_cate_bind_worker')->where(['village_id'=>$village_id])->delete();
        foreach ($param as $k=>$v){
            if(strpos($k,'_mark_') !==false){
                $kk=explode('_mark_',$k);
                if(empty($v)){
//                    return ['error'=>false,'msg'=>'【'.$kk[1].'】请选择指定工作人员'];
//                    break;
                }
            }
            if(strpos($v,'-') !==false){
                $vv=explode('-',$v);
                if(isset($vv[1]) && isset($vv[2])){
                    $data[]=[
                        'village_id'=>$village_id,
                        'cate_id'=>$vv[1],
                        'wid'=>$vv[2],
                        'add_time'=>time()
                    ];
                }
            }
        }
        if(empty($data)){
            return ['error'=>false,'msg'=>'请选择指定工作人员'];
        }
        $rr= D('House_new_repair_cate_bind_worker')->addAll($data);
        if($rr){
            return ['error'=>true,'msg'=>'操作成功，请点击下方【保存】按钮'];
        }else{
            return ['error'=>false,'msg'=>'操作失败'];
        }
    }

    //校验配置项
    public function check_bind_worker($village_id,$param){
        $House_new_repair_cate_bind_worker=D('House_new_repair_cate_bind_worker');
        $House_new_repair_cate_group_relation=D('House_new_repair_cate_group_relation');
        $House_new_repair_works_order=D('House_new_repair_works_order');
        $Property_group=D('Property_group');
        if(isset($param['is_grab_order'])){
            //开启新版工单自动抢单 对应工单分类自动生成与部门对应关系
            if(intval($param['is_grab_order']) == 1){
                $relatio_id=$House_new_repair_cate_group_relation->where(['village_id'=>$village_id])->distinct(true)->getField('cate_id',true);
                $where=[
                    'village_id'=>$village_id,
                    'parent_id'=>0,
                    'status'=>['neq', 4]
                ];
                if($relatio_id){
                    $where['id'] = ['not in', $relatio_id];
                }
                $cate_id=$this->where($where)->order('id asc')->getField('id',true);
                if(empty($cate_id)){
                    return true;
                }
                $group_id=$Property_group->where(['is_del'=>0,'village_id'=>$village_id])->order('id asc')->getField('id',true);
                if($cate_id && $group_id){
                    $data=[];
                    foreach ($cate_id as $v1){
                        foreach ($group_id as $v2){
                            $data[]=[
                                'village_id'=>$village_id,
                                'cate_id'=>$v1,
                                'group_id'=>$v2,
                                'add_time'=>time()
                            ];
                        }
                    }
                    if($data){
                        $House_new_repair_cate_group_relation->addAll($data);
                    }
                }
                //todo 触发抢单计划任务通知
                invoke_v20_service('\app\community\model\service\HouseNewRepairService/triggerGrabOrderNoticeWork',['village_id'=>$village_id,'cate_id'=>0]);
            }else{
                //清空工单分类绑定的工作人员
                $House_new_repair_cate_bind_worker->where(['village_id'=>$village_id])->delete();
                //更改工单抢单状态
                $House_new_repair_works_order->where(['village_id'=>$village_id,'event_status'=>15])->data(['event_status'=>10])->save();
            }
        }
        return true;
    }

    //新版工单超时未接单 指定工作人员接单
    public function new_repair_bind_work(){
        $time=time();
        $list= D('House_village_info')
            ->join('as a left join '.C('DB_PREFIX').'house_new_repair_works_order as b ON a.village_id = b.village_id')
            ->join(' left join '.C('DB_PREFIX').'house_new_repair_cate_bind_worker as c ON c.cate_id=b.cat_id and c.village_id = a.village_id')
            ->where([
                'a.is_grab_order'   =>  1,
                'a.grab_order_time' => array('gt', 0),
                'b.event_status'    =>  15,
                'b.cat_id'          =>  array('gt', 0),
                'c.wid'             =>  array('gt', 0)
            ])->where('('.$time.' - b.add_time) >= (a.grab_order_time * 60 * 60)')
            ->field('a.village_id,a.property_id,a.is_grab_order,a.grab_order_time,b.order_id,b.cat_fid,b.cat_id,b.event_status,b.add_time,c.wid')
            ->page(0, 20)
            ->order('b.order_id asc')->select();
        fdump_api(['指定工作人员接单==='.__LINE__,D('House_village_info')->_sql(),$list],'repair/new_repair_bind_work');
        if(empty($list)){
            return true;
        }
        foreach ($list as $v){
            if(empty($v['wid'])){
                continue;
            }
            $worker=D('House_worker')->where(['wid'=>$v['wid']])->field('wid,name,department_id')->find();
            $log_data = [
                'order_id' => $v['order_id'],
                'log_name' => 'work_assign',
                'operator_type' => 20,
                'log_content' => '',
                'log_imgs' => '',
                'add_time' => $time,
                'log_operator' => $worker['name'],
                'log_phone' => '',
                'operator_id' => 0,
                'log_uid' => 0,
                'evaluate' => 0,
                'village_id' => $v['village_id'],
                'property_id' => $v['property_id']
            ];
            $id = D('House_new_repair_works_order_log')->add($log_data);
            if ($id) {
                $data['update_time'] = $time;
                if ($v['wid']) {
                    $data['event_status'] = 20;
                    $data['worker_id'] = $v['wid'];
                    $data['group_id']=$worker['department_id'];
                } else {
                    $data['event_status'] = 40;
                }
                D('House_new_repair_works_order')->where(['order_id' => $v['order_id']])->save($data);
                if($v['wid']){
                    invoke_v20_service('\app\community\model\service\HouseNewRepairService/newRepairSendMsg',[
                        'type'=>60,
                        'order_id'=>$v['order_id']
                    ]);
                }
            }
        }
        return true;
    }


}