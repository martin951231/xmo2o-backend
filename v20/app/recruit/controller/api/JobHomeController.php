<?php

/**
 * 职位首页
 */

namespace app\recruit\controller\api;


use app\recruit\model\service\JobHomeService;

class JobHomeController extends ApiBaseController
{
    /**
     * 在招职位
     */
    public function jopHomeList()
    {
        $param['uid'] = $this->_uid;
        if (!$param['uid']) {
            return api_output_error(1002, '请登录');
        } else {
            $param['type'] = $this->request->param("type", "1", "intval");
            $param['page'] = $this->request->param("page", "1", "intval");
            $param['pageSize'] = $this->request->param("pageSize", "10", "intval");
            $list = (new JobHomeService())->jopHomeList($param['type'],$param['uid'],$param['page'], $param['pageSize']);
            return api_output(0, $list);
        }
    }
}