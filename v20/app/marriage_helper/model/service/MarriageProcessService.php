<?php


namespace app\marriage_helper\model\service;


use app\marriage_helper\model\db\MarriageProcess;
use app\marriage_helper\model\db\MarriageProcessCategory;

class MarriageProcessService
{

    /**
     * @return \json
     * 流程列表
     */
    public function getProcessList($param)
    {
        $where=[['uid','=',$param['uid']],['is_del','=',0]];
        $ret=(new MarriageProcessCategory())->getSome($where,"id,name",'id asc')->toArray();
        if(!empty($ret)){
           foreach ($ret as $key=>$val){
               $where1=[['process_id','=',$val['id']],['is_del','=',0]];
               $ret1=(new MarriageProcess())->getSome($where1,"id,process_id,process_name,process_time,person",'process_time asc')->toArray();
               if(!empty($ret1)){
                   foreach ($ret1 as $k=>$v){
                       if(!empty($ret1[$k]['process_time'])){
                           $arr=explode(":",$ret1[$k]['process_time']);
                           $ret1[$k]['process_time']=$arr[0].':'.$arr[1];
                       }
                   }
                   $ret[$key]['child']=$ret1;
               }else{
                   $ret[$key]['child']=[];
               }
           }
            $assign['status']=0;
            $assign['msg']=L_("有流程内容");
            $assign['data']=$ret;
        }else{
            $assign['status']=0;
            $assign['msg']=L_("暂未添加任何内容");
            $assign['data']=[];
        }
        return $assign;
    }
    /**
     * 添加当日流程分类
     */
    public function addProcessCategory($param){
        $ret=(new MarriageProcessCategory())->add($param);
        if($ret){
            $assign['status']=0;
            $assign['msg']=L_("添加成功");
            $assign['data']=$ret;
        }else{
            $assign['status']=0;
            $assign['msg']=L_("添加失败");
            $assign['data']=[];
        }
        return $assign;
    }

    /**
     * @param $param
     * 编辑当日流程分类
     */
    public function editProcessCategory($param)
    {
        $where=[['id','=',$param['id']]];
        $ret=(new MarriageProcessCategory())->getOne($where);
        if(!empty($ret)){
            $ret=$ret->toArray();
            $assign['status']=0;
            $assign['msg']=L_("获取分类信息成功");
            $assign['data']=$ret;
        }else{
            $assign['status']=0;
            $assign['msg']=L_("获取这个分类信息失败");
            $assign['data']=[];
        }
        return $assign;
    }

    /**
     * @param $param
     * 更新当日流程分类
     */
    public function updateProcessCategory($param){
        $where=[['id','=',$param['id']]];
        unset($param['id']);
        $data['name']=$param['name'];
        $ret=(new MarriageProcessCategory())->updateThis($where,$data);
        if($ret!==false){
            $assign['status']=0;
            $assign['msg']=L_("更新成功");
            $assign['data']=$ret;
        }else{
            $assign['status']=0;
            $assign['msg']=L_("添加失败");
            $assign['data']=[];
        }
        return $assign;
    }

    /**
     * @param $param
     * @return mixed
     * 删除当日流程分类
     */
    public function delProcessCategory($param){
        $where=[['id','=',$param['id']],['uid','=',$param['uid']]];
        $data['is_del']=1;
        $ret=(new MarriageProcessCategory())->updateThis($where,$data);
        if($ret!==false){
            $where1=[['process_id','=',$param['id']]];
            (new MarriageProcess())->updateThis($where1,$data);
            $assign['status']=0;
            $assign['msg']=L_("删除成功");
            $assign['data']=[];
        }else{
            $assign['status']=0;
            $assign['msg']=L_("删除失败");
            $assign['data']=[];
        }
        return $assign;
    }



    /**
     * 添加当日流程
     */
    public function addProcess($param){
        $param['person']=str_replace(" ","、",$param['person']);
        $ret=(new MarriageProcess())->add($param);
        if($ret){
            $assign['status']=0;
            $assign['msg']=L_("添加成功");
            $assign['data']=$ret;
        }else{
            $assign['status']=0;
            $assign['msg']=L_("添加失败");
            $assign['data']=[];
        }
        return $assign;
    }

    /**
     * @param $param
     * 编辑当日流程
     */
    public function editProcess($param)
    {
        $where=[['id','=',$param['id']]];
        $ret=(new MarriageProcess())->getOne($where);
        if(!empty($ret)){
            $ret=$ret->toArray();
            if(!empty($ret['process_time'])){
                    $arr=explode(":",$ret['process_time']);
                    $ret['process_time']=$arr[0].':'.$arr[1];
            }
            $assign['status']=0;
            $assign['msg']=L_("获取流程信息成功");
            $assign['data']=$ret;
        }else{
            $assign['status']=0;
            $assign['msg']=L_("获取此流程信息失败");
            $assign['data']=[];
        }
        return $assign;
    }

    /**
     * @param $param
     * 更新当日流程
     */
    public function updateProcess($param){
        $where=[['id','=',$param['id']]];
        unset($param['id']);
        $data=$param;
        $ret=(new MarriageProcess())->updateThis($where,$data);
        if($ret!==false){
            $assign['status']=0;
            $assign['msg']=L_("更新成功");
            $assign['data']=[];
        }else{
            $assign['status']=1100;
            $assign['msg']=L_("更新失败");
            $assign['data']=[];
        }
        return $assign;
    }

    /**
     * @param $param
     * @return mixed
     * 删除当日流程
     */
    public function delProcess($param){
        $where=[['id','=',$param['id']]];
        $data['is_del']=1;
        unset($param['uid']);
        $ret=(new MarriageProcess())->updateThis($where,$data);
        if($ret!==false){
            $assign['status']=0;
            $assign['msg']=L_("删除成功");
            $assign['data']=[];
        }else{
            $assign['status']=0;
            $assign['msg']=L_("删除失败");
            $assign['data']=[];
        }
        return $assign;
    }
}