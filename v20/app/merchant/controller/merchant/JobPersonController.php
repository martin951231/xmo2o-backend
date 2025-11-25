<?php


namespace app\merchant\controller\merchant;


use app\merchant\model\service\JobPersonService;
use app\merchant\controller\merchant\AuthBaseController;

class JobPersonController extends AuthBaseController
{

    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
    }

    /**
     * 技师列表
     */
    public function jobList()
    {
        try {
            $param['store_id'] = $this->request->param('store_id', '', 'intval');
            //$param['merId']=$this->merId;
            if (empty($param['store_id'])) {
                return api_output_error(1001, L_('店铺id获取失败'));
            }
            $list = (new JobPersonService())->jobList($param);
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 选择岗位
     */
    public function selJob(){
        try {
            $list = (new JobPersonService())->selJob();
            return api_output(0, $list);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
    /**
     * @param $param
     * 技师添加
     */
    public function addJob()
    {
        $param['store_id'] = $this->request->param('store_id', '', 'intval');
        $param['job_id'] = $this->request->param('job_id', '', 'intval');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['mer_id'] = $this->merId;
        $param['headimg'] = $this->request->param('headimg', '', 'trim');
        $job_time= $this->request->param('job_time', '', 'trim');
        $param['job_time']=strtotime($job_time);
        $param['specialty'] = $this->request->param('specialty', '', 'trim');
        $param['desc'] = $this->request->param('desc', '', 'trim');
        $param['add_time'] = time();
        try {
            if (empty($job_time)) {
                return api_output_error(1003, L_('缺少从业时间'));
            }
            if (empty($param['headimg'])) {
                return api_output_error(1003, L_('缺少半身照片'));
            }
            if (empty($param['name']) ) {
                return api_output_error(1003, L_('缺少技师名称'));
            }
            if (empty($param['job_id'])) {
                return api_output_error(1003, L_('缺少岗位名称'));
            }
            if (empty($param['specialty'])) {
                return api_output_error(1003, L_('缺少擅长'));
            }
            if (empty($param['desc'])) {
                return api_output_error(1003, L_('缺少简介'));
            }

            $ret = (new JobPersonService())->addJob($param);
            if ($ret) {
                return api_output(0, $ret);
            } else {
                return api_output_error(1003, L_('新增失败'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @return \json
     * 编辑技师
     */
    public function editJob(){
        $param['id'] = $this->request->param("id", 0, "intval");
        try {
            if ($param['id'] == 0) {
                return api_output_error(1001, L_('缺少必要参数'));
            } else {
                $ret['list'] = (new JobPersonService())->editJob($param);
                $ret['sel_job'] = (new JobPersonService())->selJob();
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output_error(1003, L_('获取失败'));
                }
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @return \json
     * 更新技师
     */
    public function updateJob(){
        //$data=$this->request->param();
        $data['store_id'] = $this->request->param('store_id', '', 'intval');
        $data['job_id'] = $this->request->param('job_id', '', 'intval');
        $data['name'] = $this->request->param('name', '', 'trim');
        $data['mer_id'] = $this->merId;
        $data['headimg'] = $this->request->param('headimg', '', 'trim');
        $job_time= $this->request->param('job_time', '', 'trim');
        $data['job_time']=strtotime($job_time);
        $data['specialty'] = $this->request->param('specialty', '', 'trim');
        $data['desc'] = $this->request->param('desc', '', 'trim');
        $data['id'] = $this->request->param('id', '', 'intval');
        try {
            if (empty($data['id'])) {
                return api_output_error(1003, L_('缺少必要参数'));
            }
            if (empty($job_time)) {
                return api_output_error(1003, L_('缺少从业时间'));
            }
            if (empty($data['headimg'])) {
                return api_output_error(1003, L_('缺少半身照片'));
            }
            if (empty($data['name'])) {
                return api_output_error(1003, L_('缺少技师名称'));
            }
            if (!isset($data['job_id']) || empty($data['job_id'])) {
                return api_output_error(1003, L_('缺少岗位名称'));
            }
            if (empty($data['specialty'])) {
                return api_output_error(1003, L_('缺少擅长'));
            }
            if (empty($data['desc'])) {
                return api_output_error(1003, L_('缺少简介'));
            }
            $ret = (new JobPersonService())->updateJob($data);
            if ($ret!==false) {
                return api_output(0,  $data['id']);
            } else {
                return api_output_error(1003, L_('修改失败'));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @return \json
     * 获取验证信息
     */
    public function resJob(){
        $param['phone'] = $this->request->param("phone", '', "trim");
        try {
            if (empty($param['phone'])) {
                return api_output_error(1001, L_('缺少必要参数'));
            } else {
                $ret = (new JobPersonService())->resJob($param);
                if ($ret) {
                    return api_output(0, $ret);
                } else {
                    return api_output(0, []);
                   // return api_output_error(1003, L_('获取失败'));
                }
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 认证
     */
    public function authentica(){
        try{
        $param = $this->request->param();
        if(!isset($param['id'])){
            if(isset($param['system_type'])){
                unset($param['system_type']);
            }
            $ret = (new JobPersonService())->addJob($param);
            if ($ret) {
                return api_output(0, $ret);
            } else {
                return api_output_error(1003, L_('添加失败'));
            }
        }else{
            $param['auth_time']=time();
            $param['status']=1;
            if(isset($param['system_type'])){
                unset($param['system_type']);
            }
            $ret = (new JobPersonService())->updateJob($param);
            if ($ret) {
                return api_output(0, 0);
            } else {
                return api_output_error(1003, L_('修改失败'));
            }
        }
        }catch (\Exception $e){
            dd($e);
        }
    }

    /**
     * 批量删除
     */
    public function delJob(){
        $param['ids'] = $this->request->param("ids", '', "trim");
        try {
            if (!isset($param['ids'])) {
                return api_output_error(1001, L_('缺少必要参数'));
            }
            $ret = (new JobPersonService())->delJob($param);
            if ($ret) {
                return api_output(0, 0);
            } else {
                return api_output_error(1003, L_("删除失败"));
            }
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
}