<?php
/**
 * @author : liukezhu
 * @date : 2021/8/10
 */
class Face_a185_indoorModel extends Model{


    //--------------------todo 室内机操作------------------------
    //todo 房间室内机 配置
    public function a185_indoor_room($a185_indoor,$data){
        $d=array(
            'device_sn'=>$data['device_sn'],
            'status'=>$data['status']
        );
        if(isset($a185_indoor['id']) && !empty($a185_indoor['id'])){
            //编辑
            $d['update_time']=time();
            $rr=$this->a185_indoor_unique($data['device_sn'],$a185_indoor['id']);
            if(!$rr['error']){
                return ['error'=>false,'msg'=>$rr['msg']];
            }
            $this->where(['id'=>$a185_indoor['id']])->data($d)->save();
        }else{
            //添加
            $rr=$this->a185_indoor_unique($data['device_sn']);
            if(!$rr['error']){
                return ['error'=>false,'msg'=>$rr['msg']];
            }
            $d['village_id']=$data['village_id'];
            $d['room_id']=$data['room_id'];
            $d['device_type']=2;
            $d['add_time']=time();
            $this->data($d)->add();
        }
        return ['error'=>true,'msg'=>'ok'];
    }

    //todo 校验室内机唯一性
    public function a185_indoor_unique($device_sn,$id=0){
        $where=[
            'device_sn'=>$device_sn,
            'status'=>['in',[0,1]]
        ];
        if($id > 0){
            $where['id'] = array('neq',$id);
        }
        $data=$this->where($where)->field('id,device_sn,status')->find();
        if($data){
            return ['error'=>false,'msg'=>'该室内机设备号已存在'];
        }else{
            return ['error'=>true,'msg'=>'ok'];
        }
    }

    public function a185_indoor_page_list($where , $field = true , $order = 'id desc',$pageSize = 20){
        if(!$where){
            return false;
        }
        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count,$pageSize,'page');

        $house_face_a185_indoor = $this->where($where)->field($field)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $list['list'] = $house_face_a185_indoor;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }

    public function a185_indoor_add($post){
        return $this->add($post);
    }

    public function a185_indoor_edit($where,$data){
        $data['update_time']=time();
        return $this->where($where)->data($data)->save();
    }

    public function a185_indoor_query($where,$field=true){
       return $this->where($where)->field($field)->find();
    }


}