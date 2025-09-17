<?php
/**
 * @author : liukezhu
 * @date : 2021/8/18
 */
class House_visualize_assignModel extends Model{


    //todo 批量绑定
    public function village_binding($type,$village_ids=[]){
        $assign_village_id=$this->where(['type'=>$type,'village_id'=>['in',$village_ids]])->getField('village_id',true);
        if(!empty($assign_village_id)){
            $data=array_diff($village_ids,$assign_village_id);
            if(empty($data)){
                return ['error'=>false,'msg'=>'暂无可【绑定】小区'];
            }
        }else{
            $data=$village_ids;
        }
        $param=[];
        foreach ($data as $v){
            $param[]=array(
                'village_id'=>$v,
                'type'=>$type,
                'add_time'=>date('Y-m-d H:i:s',time())
            );
        }
        $rr= $this->addAll($param);
        if($rr){
            return ['error'=>true,'msg'=>'操作成功'];
        }else{
            return ['error'=>false,'msg'=>'操作失败'];
        }
    }

    //todo 批量解绑
    public function village_unbound($type,$village_ids=[]){
        $assign_village_id=$this->where(['type'=>$type,'village_id'=>['in',$village_ids]])->getField('village_id',true);
        if(empty($assign_village_id)){
            return ['error'=>false,'msg'=>'暂无可【解绑】小区'];
        }
        $database_adver_category  = D('House_adver_category');
        $database_adver = D('House_adver');
        $database_house_village_nav = D('House_village_nav');
        $database_house_village_footer_menu = D('House_village_footer_menu');
        $str='';
        foreach ($village_ids as $k=>$v2){
            if($k > 0){
                $str.=' or FIND_IN_SET('.$v2.', filter_village_ids ) ';
            }else{
                $str=' FIND_IN_SET('.$v2.', filter_village_ids ) ';
            }
        }
        if(in_array($type,[1,3,4,5,6])){
            //todo 处理轮播图、中间栏轮播广告图、四图广告位、中部单图广告位、底部单图广告位
            $type_arr=[
                1=>'house_app_index_top',
                3=>'house_app_index_center',
                4=>'house_four_adver',
                5=>'house_center_adver',
                6=>'house_footer_adver'
            ];
            if(isset($type_arr[$type]) && !empty($type_arr[$type])){
                $cat_id = $database_adver_category->where(['cat_key'=>$type_arr[$type]])->getField('cat_id');
                if($cat_id){
                    $database_adver->where([
                        'cat_id'=>$cat_id,
                        'village_id'=>['in',$village_ids]
                    ])->data(['status'=>4])->save();
                    $where_str='(cat_id='.$cat_id.' and village_id = 0 and status <> 4) and ('.$str.')';
                    $list=$database_adver->where($where_str)->field('id,filter_village_ids')->select();
                    $this->eliminate_village_ids($list,$village_ids,$database_adver);
                }
            }
        }
        elseif ($type == 2){
            //todo 处理功能导航
            $database_house_village_nav->where([
                    'village_id'=>['in',$village_ids]
                ])->data(['status'=>4])->save();
            $where_str='(village_id = 0 and status <> 4) and ('.$str.')';
            $list=$database_house_village_nav->where($where_str)->field('id,filter_village_ids')->select();
            $this->eliminate_village_ids($list,$village_ids,$database_house_village_nav);
        }
        elseif ($type == 7){
            //todo 处理底部导航
            $database_house_village_footer_menu->where([
                'village_id'=>['in',$village_ids]
            ])->data(['status'=>4])->save();
            $where_str='(village_id = 0 and status <> 4) and ('.$str.')';
            $list=$database_house_village_footer_menu->where($where_str)->field('id,filter_village_ids')->select();
            $this->eliminate_village_ids($list,$village_ids,$database_house_village_footer_menu);

        }
        $this->where([
            'type'=>$type,
            'village_id'=>['in',$village_ids]
        ])->delete();
        return ['error'=>true,'msg'=>'解绑成功'];
    }

    //todo 剔除过滤village_ids
    public function eliminate_village_ids($list,$village_ids,$db){
        if(!empty($list)){
            foreach ($list as $v){
                if(!empty($v['filter_village_ids'])){
                    $filter_village_ids=explode(',',$v['filter_village_ids']);
                    $diff_village_id=array_diff($filter_village_ids,$village_ids);
                    $db->where(['id'=>$v['id']])->data(['filter_village_ids'=>implode(',',$diff_village_id)])->save();
                }
            }
        }
    }
    //todo 可视化平台分配小区参数
    public function house_visualize_assign($village_id,$type=0){
        $database_house_visualize_assign  = D('House_visualize_assign');
        $where=[
            'village_id'=>$village_id
        ];
        if($type > 0){
            $where['type']=$type;
        }
        $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        if (intval($switch) < 1){
            // 社区可视化页面开关 关闭时 平台配置项关闭
            $assign_type=[
                'type_0'=>true,
                'type_1'=>true,
                'type_2'=>true,
                'type_3'=>true,
                'type_4'=>true,
                'type_5'=>true,
                'type_6'=>true,
                'type_7'=>true,
                'type_8'=>true,
                'type_9'=>true,
                'type_10'=>true,
                'type_11'=>true,
                'type_12'=>true,
                'type_13'=>true,
                'type_14'=>true,
                'type_15'=>true,
                'type_40'=>true,
                
            ];
        }else{
            $assign_type=[
                'type_0'=>false,
                'type_1'=>false,
                'type_2'=>false,
                'type_3'=>false,
                'type_4'=>false,
                'type_5'=>false,
                'type_6'=>false,
                'type_7'=>false,
                'type_8'=>false,
                'type_9'=>false,
                'type_10'=>false,
                'type_11'=>false,
                'type_12'=>false,
                'type_13'=>false,
                'type_14'=>false,
                'type_15'=>false,
                'type_40'=>false,

            ];
            $house_visualize_assign=$database_house_visualize_assign->where($where)->getField('type',true);
            if($house_visualize_assign){
                foreach ($house_visualize_assign as $v){
                    if(isset($assign_type['type_'.$v])){
                        $assign_type['type_'.$v]=true;
                    }
                }
            }
        }
        return $assign_type;
    }

    // todo 获取未分配小区
    public function get_not_visualize_assign(){
        $assign_village_id=$this->where(['type'=>7])->getField('village_id',true);
        $where=[];
        if($assign_village_id){
            $where['village_id']=['not in',$assign_village_id];
        }
        return D('House_village')->where($where)->getField('village_id',true);
    }


}