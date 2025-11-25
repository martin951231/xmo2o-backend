<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      用户端机器人获取接口-控制器基础类
 */
namespace app\thirdRobot\controller\user;

use app\BaseController as BaseController;
use app\common\model\service\UserService as UserService;
use think\Exception;

class ApiBaseController extends BaseController
{
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $userInfo;

    /**
     * 临时用户信息
     * @var array
     */
    public $userTemp;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;

    /**
     * 全局配置项
     * @var array
     */
    public $config;

    public function initialize()
    {
        parent::initialize();
        $userId = intval($this->request->log_uid);
        // 获得用户信息
        $userService = new UserService();
        $user = $userService->getUser($userId);
        // 用户id
        $this->_uid = $userId;
        // 用户信息
        $this->userInfo = $user;
    }

    /**
     * 验证登录
     * @var int
     */
    protected function checkLogin(){
        $log_uid = request()->log_uid ?? 0;
        if(empty($log_uid)){
            throw_exception("未登录", Exception::class, 1002);
        }
    }
}