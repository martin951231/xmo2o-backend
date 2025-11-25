<?php

declare(strict_types=1);

namespace app\api\middleware;

class Org
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $appkey = $request->post('appkey', '', 'trim');
        if (empty($appkey)) {
            return json(['code' => 1002, 'msg' => 'appkey不能为空'])->code(200);
        }
        if (empty(cfg('o2o_appkey')) || $appkey != cfg('o2o_appkey')) {
            return json(['code' => 1002, 'msg' => 'appkey校验失败'])->code(200);
        }
        return $next($request);
    }
}
