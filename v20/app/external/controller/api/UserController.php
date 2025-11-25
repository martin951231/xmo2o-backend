<?php
/**
 * 用户接口
 */

namespace app\external\controller\api;
use app\external\model\service\UserService;
use tools\Sign;

class UserController extends ApiBaseController
{

    /**
     * 用户无登录跳转小程序接口（人才系统接口）
     * @return \json
     */
    public function getPath()
    {
        $checkParam['realname'] = $param['realname'] = $this->request->param('realname', '', 'trim');
        $checkParam['phone'] = $param['phone'] = $this->request->param('phone', '', 'trim');
        $param['path'] = $this->request->param('path', '', 'trim');
        $userService = new UserService();
        $re = $this->checkSign($checkParam);
        if(!$re){
            return api_output(2003, [], '签名验证失败！');
        }
        if(isset($re['msg'])){
            return api_output(2003, [], $re['msg']);
        }
        try {
            $arr = $userService->getPath($param);
            fdump(['param'=>$param,'return'=>$arr],'User_getPath',1);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            fdump(['param'=>$param,'error'=>$e->getMessage()],'User_getPath',1);
            return api_output_error(1003, $e->getMessage());
        }
    }
}