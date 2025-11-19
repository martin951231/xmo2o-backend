<?php
declare (strict_types = 1);

namespace app\middleware;
use app\common\model\service\AdminLogService;
use app\common\model\service\MerchantLogService;
use token\Token;
class Logger
{
	// 此日志会较user中间件先触发，先获得用户信息。
	private $log_uid;

	private $log_extends;

	private $log_iat;
    /**
     * 日志中间件
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
		//校验token
        $token = Token::checkToken(Token::getToken());
		$this->log_uid = $token['memberId']??0;

		$this->log_extends = $token['extends']??[];

        $this->log_iat = $token['iat'] ?? 0;
		
		$params = $request->param();
		// 前端会传 system_type，分别代表各个后台
		if($params && isset($params['system_type']) && count($params) >= 1 && $this->log_uid){
		    //拦截器，当密码修改则需要重新登录
            $result = $this->updatePwdInterceptor($params['system_type'], $this->log_uid, $this->log_iat);
            if ($result['login']) {
                return api_output(1501, ['logout' => 1, 'title' => '温馨提示', 'tip' => '当前登录信息已过期，请重新登录', 'url' => $result['login_url']]);
            }

            $requestUrl = request()->server('REQUEST_URI');
            $logWhitelist = ['common/common.Index/stat'];
            $isWhite = false;
            foreach ($logWhitelist as $l) {
                if (stripos($requestUrl, $l) !== false) {
                    $isWhite = true;
                    break;
                }
            }
            if ($isWhite) {
                return $next($request);
            }
            
			switch($params['system_type']){
				case 'platform':	//系统后台
					$this->logger_admin($request);
					break;
				case 'merchant':	//商家后台
					$this->logger_merchant($request, 'merchant');
					break;
				case 'storestaff':	//店员后台
					$this->logger_merchant($request, 'storestaff');
					break;
			}
		}
		
        return $next($request);
    }
	
	/**
	 * 系统后台日志记录
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-03
	 *
	 * @param [type] $request
	 * @return void
	 */
	private function logger_admin($request){
		$serverParams = $request->server();
		$dataLog = [
			'admin_id' => $this->log_uid,
			'action' => str_replace($serverParams['SCRIPT_NAME'], '', $serverParams['REQUEST_URI']),
			'content' => serialize($request->post()),
			'add_time' => time(),
			'add_ip' => get_client_ip(),
			'extends'=>serialize($this->log_extends)
		];
		$adminLogService = new AdminLogService();
		$adminLogService->addLog($dataLog);
	}

	/**
	 * 系统商家日志记录
	 *
	 * @Description
	 * @Author Jaty
	 * @DateTime 2021-06-03
	 *
	 * @param [type] $request
	 * @return void
	 */
	private function logger_merchant($request, $loginType){

		$serverParams = $request->server();
		$cookies = $request->cookie();
		$dataLog = [
			'action' => str_replace($serverParams['SCRIPT_NAME'], '', $serverParams['REQUEST_URI']),
			'content' => serialize($request->post()),
			'add_time' => time(),
			'add_ip' => get_client_ip(),
			'extends'=>serialize($this->log_extends)
		];

		//判断身份
		if($loginType == 'merchant'){
			$dataLog['mer_id'] = $this->log_uid;
		}else{
			$dataLog['staff_id'] = $this->log_uid;

			//判断是不是商家在登录店员
			if(isset($cookies['merchant_access_token'])){
				$adminToken = Token::checkToken($cookies['merchant_access_token']);
				$dataLog['mer_id'] = $adminToken['memberId'];
			}
		}

		//判断是不是平台在登录
		if(isset($cookies['platform_access_token'])){
			$adminToken = Token::checkToken($cookies['platform_access_token']);
			$dataLog['admin_id'] = $adminToken['memberId'];
		}

		
		$merchantLogService = new MerchantLogService();
		$merchantLogService->addLog($dataLog);
	}


    /**
     * 平台、商家、店员修改密码重新登录拦截器
     * @param $systemType
     * @param $uid
     * @param $time
     * @date: 2023/09/09
     */
    private function updatePwdInterceptor($systemType, $uid, $time)
    {
        $updateTime = 0;
        $loginUrl = '';
        switch ($systemType) {
            case 'platform':    //系统后台
                $updateTime = \think\facade\Db::name('admin')->where('id', $uid)->value('update_pwd_time');
                $loginUrl = cfg('site_url') . '/v20/public/platform/';
                break;
            case 'merchant':    //商家后台
                $updateTime = \think\facade\Db::name('merchant')->where('mer_id', $uid)->value('update_pwd_time');
                $loginUrl = cfg('site_url') . '/v20/public/platform/#/usernew/merchant/login';
                break;
            case 'storestaff':    //店员后台
                $updateTime = \think\facade\Db::name('merchant_store_staff')->where('id', $uid)->value('update_pwd_time');
                $loginUrl = cfg('site_url') . '/v20/public/platform/#/usernew/storestaff/login';
                break;
            default:
                break;
        }
        return [
            'login' => $updateTime > $time,
            'login_url' => $loginUrl
        ];
    }
}
