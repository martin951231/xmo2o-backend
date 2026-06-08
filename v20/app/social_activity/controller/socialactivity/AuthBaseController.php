<?php

namespace app\social_activity\controller\socialactivity;

use app\BaseController;
use app\http\exceptions\ParametersException;
use app\social_activity\model\db\SocialActivityAdmin;
use think\helper\Str;

class AuthBaseController extends BaseController
{
    protected $validateClass;
    /**
     * 控制器登录用户信息
     * @var array
     */
    public $socialUser;

    /**
     * 控制器登录用户uid
     * @var int
     */
    public $_uid;

    public function initialize()
    {
        parent::initialize();

        // 验证登录
        $this->checkLogin();

        $userId = intval($this->request->log_uid);
        // 获得用户信息
        $userService = new SocialActivityAdmin();
        $user = $userService->where(['id' => $userId])->findOrEmpty()->toArray();

        // 用户id
        $this->_uid = $userId;

        // 用户信息
        $this->socialUser = $user;

        request()->socialUser = $this->socialUser;
    }

    /**
     * 验证登录
     * @var int
     */
    private function checkLogin()
    {
        $log_uid = request()->log_uid ?? 0;
        if (empty($log_uid)) {
            throw new \think\Exception("未登录", 1002);
        }
    }

    /**
     * 参数验证
     * @param string $scenario
     * @param array $params
     * @param string $method
     * @return array|mixed
     */
    protected function validateParameter(array $params = [], string $scenario = '', string $method = 'post'): array
    {
        empty($params) && $params = input($method . '.');
        $validate = validate($this->validateClass);
        if (!$validate->scene($scenario ?: Str::snake(request()->action()))->check($params)) {
            throw_exception(L_($validate->getError()), ParametersException::class);
        }

        return $params;
    }
}
