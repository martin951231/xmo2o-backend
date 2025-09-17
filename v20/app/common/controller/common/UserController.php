<?php
/**
 * 前台用户
 * Created by subline.
 * Author: lumin
 * Date Time: 2020/08/10
 */
namespace app\common\controller\common;

use app\common\controller\CommonBaseController;
use app\common\model\service\WxappTemplateBindUserListService;
use token\Token;
class UserController extends CommonBaseController{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 用于对其他系统（老的O2O系统）提供V20的ticket
     * @return [type] [description]
     */
    public function getUserToken(){
        $uid = $this->request->param("uid", "", "intval");
        if(empty($uid)){
            return api_output_error(1001, "uid必传");
        }
        $ticket = Token::createToken($uid);
        return api_output(0, ['ticket' => $ticket], "登录成功");
	}

    /**
     * 保存小程序订阅消息模板授权信息
     */
    public function saveTemplateRole()
    {
        try {
            $param['template_list'] = $this->request->param("template_list", "", "");
            $param['wxapp_openid'] = $this->request->param("wxapp_openid", "", "trim");
            $param['uid'] = $this->request->param("uid", "", "intval");

            (new WxappTemplateBindUserListService())->saveTemplateRole($param);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }
        return api_output(0);
    }
}