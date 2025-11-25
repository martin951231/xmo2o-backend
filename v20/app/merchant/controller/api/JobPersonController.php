<?php


namespace app\merchant\controller\api;


use app\BaseController;
use app\merchant\model\service\JobPersonService;
use app\merchant\controller\merchant\AuthBaseController;

class JobPersonController extends AuthBaseController
{

    /**
     * @return \json
     * 验证用户是不是技师
     */
    public function jobPerson(){
        $param['uid'] = request()->log_uid?? 0;
        $param['phone'] = $this->request->param('phone', '', 'trim');
        try {
            $where=[];
            if(isset($param['s.uid']) && !empty($param['uid'])){
                array_push($where,['uid','=',$param['uid']]);
            }
            if(isset($param['m.phone']) && !empty($param['phone'])){
                array_push($where,['phone','=',$param['phone']]);
            }
            $field="m.sex,st.name as store_name,mp.name as job_name,st.city_id";
            $ret=(new JobPersonService())->findPerson($where,$field);
            return api_output(0, $ret);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
    }
}