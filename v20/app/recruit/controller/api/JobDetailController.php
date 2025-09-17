<?php


namespace app\recruit\controller\api;


use app\recruit\model\service\EmploymentService;
use app\recruit\model\service\JobDetailService;
use think\Exception;

class JobDetailController extends ApiBaseController
{
    /**
     * 职位详情
     */
    public function getJobDetail()
    {
         $param['uid'] = intval($this->_uid);
         $param['job_id'] = $this->request->param("job_id");
        try {
            if (isset($param['job_id'])) {
                $where = [['job_id', '=', $param['job_id']]];
                if (!$param['job_id']) {
                    $where[] = ['add_type', '=', 0];
                }
                $list = (new JobDetailService())->getJobDetail($where, $param['job_id'], $param['uid']);
                return api_output(0, $list);
            } else {
                return api_output_error(1003, L_('缺少必要参数'));
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * @return \json
     * 投递简历
     */
    public function jobDelivery()
    {
        $param['uid'] =  $this->_uid;
        $param['position_id'] = $this->request->param("job_id");
        if (!$param['uid']) {
            return api_output_error(1002, L_('请登录'));
        } else {
            try {
                if (isset($param['position_id'])) {
                    $where = [['position_id', '=', $param['position_id']], ['uid', '=', $param['uid']]];
                    $list = (new JobDetailService())->jobDelivery($where, $param);
                    return api_output(0, $list);
                } else {
                    throw new Exception(L_('请选择投递职位'));
                }
            } catch (Exception $e) {
                return api_output_error(1003, $e->getMessage());
            }
        }
    }

    /**
     * @return \json
     * 职位收藏
     */
    public function updateJobCollect()
    {
        $param['uid'] = $this->_uid;
        $param['job_id'] = $this->request->param("job_id");
        if (!$param['uid']) {
            return api_output_error(1002, L_('请登录'));
        } else {
            if (isset($param['job_id'])) {
                $where = [['job_id', '=', $param['job_id']]];
                $list = (new JobDetailService())->updateJobCollect($where, $param);
                return api_output(0, $list);
            } else {
                return api_output_error(1003, L_('缺少必要参数'));
            }
        }
    }

    /**
     * 删除职位
     */
    public function delJob()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $param['job_id'] = $this->request->param("job_id");
            if(!isset($param['job_id'])){
                return api_output_error(1003, '缺少职位id');
            }else{
                unset($param['uid']);
                $param['is_del'] =1;
                $ret=(new EmploymentService)->updateJob($param);
                if($ret){
                    return api_output(0, '删除成功');
                }else{
                    return api_output_error(1003, '删除失败');
                }
            }
        }
    }

    /**
     * 上线下线职位
     */
    public function pushJob(){
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $param['job_id'] = $this->request->param("job_id");
            if(!isset($param['job_id'])){
                return api_output_error(1003, '缺少职位id');
            }else{
                $param['status'] =$this->request->param("status");
                if(!isset($param['status'])){
                    return api_output_error(1003, '缺少状态');
                }
                unset($param['uid']);
                if($param['status']==1){
                    $param['uptime']=time();
                }
                $ret=(new EmploymentService)->updateJob($param);
                if($ret){
                    return api_output(0, '发布成功');
                }else{
                    return api_output_error(1003, '发布失败');
                }
            }
        }
    }
}