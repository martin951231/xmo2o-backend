<?php
declare (strict_types = 1);

namespace app\middleware;

class Language
{
    /**
     * 判断当前语言环境
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        
        return $next($request);
    }
}
