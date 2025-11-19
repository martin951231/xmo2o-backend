<?php
declare (strict_types = 1);

namespace app\middleware;

class Http
{
    public function handle($request, \Closure $next)
    {
        /* 判断强制跳转https开始 */
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {    //其他cdn
            $_SERVER['REQUEST_SCHEME'] = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } else if (isset($_SERVER['HTTP_X_CLIENT_SCHEME'])) {    //阿里cdn
            $_SERVER['REQUEST_SCHEME'] = $_SERVER['HTTP_X_CLIENT_SCHEME'];
        }
        if (empty($_SERVER['REQUEST_SCHEME'])) {
            if ($_SERVER['SERVER_PORT'] == '443') {
                $_SERVER['REQUEST_SCHEME'] = 'https';
            } else {
                $_SERVER['REQUEST_SCHEME'] = 'http';
            }
        }
        if($_SERVER['REQUEST_SCHEME'] == 'https'){
            $_SERVER['SERVER_PORT'] = '443';
        }
        $request->withServer($_SERVER);
        return $next($request);
    }
}
