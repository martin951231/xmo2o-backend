<?php
/**
 * Author: hengtingmei
 * motto: 判断系统后台是否录
 * Date Time: 2020/5/7 09:01
 */
declare (strict_types = 1);
namespace app\common\middleware;

class Auth {

    public function handle($request, \Closure $next) {

        // 前置中间件
        if(empty(intval($request->log_uid)) && !preg_match("/login/", $request->pathinfo())  && !preg_match("/config/", $request->pathinfo())) {
           return api_output_error(1002, "没有登录");
        }

        $response = $next($request);

        return $response;
        // 后置中间件

    }

    /**
     * 中间件结束调度
     * @param \think\Response $response
     */
    public function end(\think\Response $response) {

    }
}