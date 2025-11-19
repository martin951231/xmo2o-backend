<?php


namespace app\recruit\controller\api;


use app\recruit\model\service\JobPersonCenterService;

class JobPersonCenterController extends ApiBaseController
{
    /**
     * 个人中心
     */
    public function center()
    {
        try{
            $param['uid'] = $this->_uid;
            if ($param['uid']) {
                $where = [['uid', '=', $param['uid']]];
                $list = (new JobPersonCenterService())->center($where,$param['uid']);
                return api_output(0, $list);
            } else {
                return api_output_error(1002, '', '请登录');
            }
        }catch (\Exception $exception){
            return api_output_error(1003, $exception->getMessage());
        }
    }

    /**
     * 求职状态
     */
    public function jobStatus()
    {
        $list = (new JobPersonCenterService())->job_status;
        return api_output(0, $list);
    }

    /**
     * @param $where
     * @param $data
     * 用户职位状态切换更新
     */
    public function updateStatus()
    {
        $param['uid'] = $this->_uid;
        if ($param['uid']) {
            $where = [['uid', '=', $param['uid']]];
            $data['job_status'] = $this->request->param('job_status', '', 'intval');
            if (!isset($data['job_status'])) {
                return api_output_error(1003, '', '缺少用户状态');
            }
            $list = (new JobPersonCenterService())->updateStatus($where, $data);
            return api_output(0, $list);
        } else {
            return api_output_error(1002, '', '请登录');
        }
    }

    /**
     * 判断是不是hr，切换招聘者
     */
    public function resHr(){
        $param['uid'] = $this->_uid;
        if ($param['uid']) {
            $where = [['u.uid', '=', $param['uid']],['s.status','=',0]];
            $list = (new JobPersonCenterService())->resHr($where);
            return api_output(0, $list);
        } else {
            return api_output_error(1002, '', '请登录');
        }
    }
}