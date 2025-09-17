<?php

declare (strict_types=1);

namespace app\middleware;

use app\thirdAccess\model\service\ThirdAccessCheckService;

class ThirdAccess
{

    private $appid;
    private $appkey;
    /**
     * 检验三方参数
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $this->appid = cfg('third_building_appid');
        $this->appkey = cfg('third_building_appkey');
        $request->thirdErrMsg = '';// 三方错误
        $param = $request->param();
        $appid = isset($param['appid'])&&trim($param['appid'])?trim($param['appid']):'';
        $appkey = isset($param['appkey'])&&trim($param['appkey'])?trim($param['appkey']):'';
        $appsecret = isset($param['appsecret'])&&trim($param['appsecret'])?trim($param['appsecret']):'';
        if($this->appid && $this->appkey && !$appsecret && ($appid != $this->appid || $appkey != $this->appkey)){
            $request->thirdErrCode = 1001;
            $request->thirdErrMsg = '鉴权失败';
        }
        if (!$appid && !$appkey && (!$this->appid || !$this->appkey)) {
            // 系统未配置此功能，无法使用。
            $request->thirdErrCode = 1001;
            $request->thirdErrMsg = '鉴权失败';
        }
        if ($appid && $appkey) {
            $thirdAccessCheckService = new ThirdAccessCheckService();
            $thirdAccess = $thirdAccessCheckService->getThirdConfig($appid,$appkey,$appsecret);
            if (isset($thirdAccess['businessType']) && isset($thirdAccess['businessId']) && $thirdAccess['businessType'] && $thirdAccess['businessId']) {
                $request->thirdBusinessType = $thirdAccess['businessType'];
                $request->thirdBusinessId = $thirdAccess['businessId'];
                $request->thirdType = $thirdAccess['thirdType'];
                $request->thirdSite = $thirdAccess['thirdSite'];
            }
        }
        return $next($request);
    }
}