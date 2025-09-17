<?php
class Wisdom_qrcode_statisticsModel extends Model{
    public function addOne($data){
        $add = $this->add($data);
        return $add;
    }
    public function updateOne($where,$data){
        $add = $this->where($where)->save($data);
        return $add;
    }
    public function getOne($where,$field){
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
    public function add_num($where,$field,$num = 1)
    {
        $result = $this->where($where)->setInc($field,$num);
        return $result;
    }

    /**
     * 新增巡检数量
     */
    public function addPatrolInspectionNum($data){
        $jz_open = D('Config_customization')->getWSXCustomizedOpen();
        if(!$jz_open){
            return array('status'=>1);
        }
        if(!$data['village_id']){
            return array('status'=>0,'msg'=>'小区id不能为空！');
        }
        $where = ['village_id'=>$data['village_id'],'cate_id'=>$data['cate_id']];
        //查询小区的该类别巡检统计数据是否生成
        $statisticsInfo = $this->getOne($where,'id,uid,update_time');
        if($statisticsInfo){
            //是：查询最后更新时间是不是今天，查询已统计今天总巡检次数的uid中是否包含本次的uid
            //查询该负责人今天是否上报了该类别，如果已经上报，说明已经增加了总巡检次数，不用再增加了。如果最后修改时间不在今天，说明需要修改巡检次数
            $statisticsUidAry = $statisticsInfo['uid'] ? explode(',',$statisticsInfo['uid']) : '';
            if($statisticsInfo['update_time'] >= strtotime(date('Y-m-d')) && in_array($data['uid'],$statisticsUidAry)){
                //是：不做处理。
                return array('status'=>1);
            }
                //否：增加本次的uid。增加总巡检次数，更新最后修改时间
            //查询责任人今天该类别的总巡检次数
            $num = $this->getAddNum($data['cate_id']);
            
            //判断最后更新时间是不是今天，如果不是今天，需要清空uid，只保留本次的uid。如果是,今天需要增加一个uid
            $uidStr = $data['uid'];
            if($statisticsInfo['update_time'] >= strtotime(date('Y-m-d'))){
                $uidStr = $statisticsInfo['uid'] ? $statisticsInfo['uid'].','. $data['uid'] : $data['uid'];
            }
            $updateUid = $this->updateOne($where,['uid'=>$uidStr,'update_time'=>time()]);
            if(!$updateUid){
                return array('status'=>0,'msg'=>'更新今日增加总巡检次数的负责人id失败！');
            }
            
            //巡检总次数增加
            $update = $this->add_num($where,'patrol_inspection_num',$num);
        }else{
            //否：新增统计数据
            $village_info = D('House_village')->get_village_info($data['village_id'],'property_id');
            $property_id = $village_info['property_id'] ?? 0;
            if(!$property_id){
                return array('status'=>0,'msg'=>'修改失败！');
            }
            $num = $this->getAddNum($data['cate_id']);
            //新增统计数据
            $addData = [
                'village_id'=>$data['village_id'],
                'cate_id'=>$data['cate_id'],
                'property_id'=>$property_id,
                'patrol_inspection_num'=>$num,
                'patrol_inspection_completion_num'=>0,
                'add_time'=>time(),
                'update_time'=>time(),
                'uid'=>$data['uid']
            ];
            $update = $this->addOne($addData);
        }
        if($update){
            return array('status'=>1,'msg'=>'增加巡检数量成功！');
        }else{
            return array('status'=>0,'msg'=>'增加巡检数量失败！');
        }
    }
    
    /**
     * 获取巡检人今天的总巡检数
     */
    public function getAddNum($cate_id)
    {
        $num = M('Wisdom_qrcode')->where(['cate_id'=>$cate_id])->count();
        return $num;
    }
}
?>