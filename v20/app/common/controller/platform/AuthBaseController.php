<?php
/**
 * 判断用户登录
 * Author: lumin
 */
namespace app\common\controller\platform;

use app\common\controller\CommonBaseController;
use app\common\model\service\admin_user\AdminUserService as AdminUserService;
use app\http\exceptions\ParametersException;
use think\helper\Str;

class AuthBaseController extends CommonBaseController
{
	protected $validateClass;
	/**
	 * 控制器登录用户信息
	 * @var array
	 */
	public $systemUser;

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
		$userService = new AdminUserService();
		$user = $userService->getUserRole($userId);

		// 用户id
		$this->_uid = $userId;

		// 用户信息
		$this->systemUser = $user;

		request()->systemUser = $this->systemUser;
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