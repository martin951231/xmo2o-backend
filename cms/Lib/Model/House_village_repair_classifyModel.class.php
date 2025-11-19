<?php
class House_village_repair_classifyModel extends Model{

    //todo 针对社区 同步生成  公共报修、个人报修   D('House_village_repair_classify')->sync_classify();
    public function sync_classify(){
        $House_village_repair_cate = M('House_village_repair_cate');
        $House_village_repair_classify = M('House_village_repair_classify');
        $repair_village_id=$House_village_repair_cate->where('id > 0')->distinct(true)->field('village_id')->getField('village_id',true);
        if(empty($repair_village_id)){
            return true;
        }
        $classify_village_id=$House_village_repair_classify->where(['del_time'=>0])->distinct(true)->field('village_id')->getField('village_id',true);
        if(empty($classify_village_id)){
            $village_id_all=$repair_village_id;
        }else{
            $village_id_all=array_diff($repair_village_id,$classify_village_id);
        }
        if(empty($village_id_all)){
            return true;
        }
        $time=time();
        $type=[
            1=>'公共报修',
            2=>'个人报修'
        ];
        foreach ($village_id_all as $v){
            foreach ($type as $k2=>$v2){
                $data=[
                    'village_id'=>$v,
                    'type'=>$k2,
                    'cate_name'=>$v2,
                    'sort'=>0,
                    'status'=>1,
                    'add_time'=>$time
                ];
                $classify_id=$this->data($data)->add();
                if($classify_id){
                    $where=[
                        'cate_status'=>$k2,
                        'parent_id'=>0,
                        'type'=>1,
                        'village_id'=>$v
                    ];
                    $House_village_repair_cate->where($where)->data(['cate_status'=>$classify_id])->save();
                }
            }
        }
        return true;
    }


    //todo 针对社区 同步生成  公共报修、个人报修优化
    public function sync_repair_cate(){
        $house_village_repair_cate=D('House_village_repair_cate');
        $house_village_repair_classify=D('House_village_repair_classify');
        $list=$house_village_repair_cate->where([
            'parent_id'=>0,
            'type'=>1,
            'cate_status'=>['gt', 0],
            'classify_id'=>0
        ])->field('id,cate_status,classify_id')->select();
        if(empty($list)){
            return true;
        }
        foreach ($list as $v){
            $classify=$house_village_repair_classify->where(['id'=>$v['cate_status'],'del_time'=>0])->find();
            if($classify){
                $cate_status=1;
                if($classify['cate_name'] == '个人报修'){
                    $cate_status=2;
                }
                if($cate_status != $classify['type']){
                    $house_village_repair_classify->where(['id'=>$classify['id']])->data(['type'=>$cate_status])->save();
                }
                $house_village_repair_cate->where(['id'=>$v['id']])->data(['cate_status'=>$cate_status,'classify_id'=>$classify['id']])->save();
            }
        }
        return true;
    }


    //todo 查询一级分类
    public function get_repair_classify($where,$field,$order){
       return $this->where($where)->field($field)->order($order)->select();
    }

    //todo 查询单条数据
    public function get_find($where,$field){
        return $this->where($where)->field($field)->find();
    }

    //todo 添加数据
    public function repair_classify_add($data){
        return $this->data($data)->add();
    }

    //todo 编辑数据
    public function repair_classify_edit($where,$data){
        return $this->where($where)->data($data)->save();
    }
    
}