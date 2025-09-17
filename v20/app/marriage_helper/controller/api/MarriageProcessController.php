<?php


namespace app\marriage_helper\controller\api;

use app\BaseController;
use app\marriage_helper\model\service\MarriageProcessService;
use app\shop\controller\platform\AuthBaseController;

class MarriageProcessController extends AuthBaseController
{

    /**
     * @return \json
     * 结婚当日流程内容列表
     */
    public function getProcessList(){
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $assign=(new MarriageProcessService())->getProcessList($param);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }

    /**
     * 添加当日流程分类
     */
   public function addProcessCategory(){
       $param=$this->request->param();
       $data['uid']=$param['uid'] = request()->log_uid?? 0;
       if($param['uid']){
           $data['create_time']=$param['create_time'] =time();
           $data['name']=$param['name'];
           $assign=(new MarriageProcessService())->addProcessCategory($data);
           return api_output($assign['status'], $assign['data'],$assign['msg']);
       }else{
           return api_output(1001, [],L_("获取用户信息失败"));
       }
   }

    /**
     * @param $param
     * 编辑当日流程分类
     */
    public function editProcessCategory()
    {
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $assign=(new MarriageProcessService())->editProcessCategory($param);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }

    /**
     * @param $param
     * 更新当日流程分类
     */
    public function updateProcessCategory()
    {
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $assign=(new MarriageProcessService())->updateProcessCategory($param);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }

    /**
     * @param $param
     * 删除当日流程分类
     */
    public function delProcessCategory()
    {
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $assign=(new MarriageProcessService())->delProcessCategory($param);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }

    /**
     * 添加当日流程
     */
    public function addProcess(){
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $data['create_time'] =$param['create_time'] =time();
            $data['process_id'] =$param['process_id'];
            $data['process_name'] =$param['process_name'];
            $data['person'] =$param['person'];
            $data['process_time'] =$param['process_time'];
            $assign=(new MarriageProcessService())->addProcess($data);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }

    }

    /**
     * @param $param
     * 编辑当日流程
     */
    public function editProcess()
    {
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $assign=(new MarriageProcessService())->editProcess($param);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }

    /**
     * @param $param
     * 更新当日流程
     */
    public function updateProcess()
    {
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $data['id'] =$param['id'];
            $data['process_name'] =$param['process_name'];
            $data['person'] =$param['person'];
            $data['process_time'] =$param['process_time'];
            $assign=(new MarriageProcessService())->updateProcess($data);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }

    /**
     * @param $param
     * 删除当日流程
     */
    public function delProcess()
    {
        $param=$this->request->param();
        $param['uid'] = request()->log_uid?? 0;
        if($param['uid']){
            $assign=(new MarriageProcessService())->delProcess($param);
            return api_output($assign['status'], $assign['data'],$assign['msg']);
        }else{
            return api_output(1001, [],L_("获取用户信息失败"));
        }
    }
}