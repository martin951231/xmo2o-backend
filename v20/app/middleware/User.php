<?php
declare (strict_types = 1);

namespace app\middleware;

use token\Token;
use token\Ticket;
class User
{
    /**
     * 生成用户信息
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        //校验token
        $token = Token::checkToken(Token::getToken());

        //万一v20_ticket不存在，但是老的存在。
        if(isset($token['memberId']) && $token['memberId']){
            $request->log_uid = $token['memberId'];
            $request->log_extends = $token['extends'];
            $request->refresh_ticket = $token['refresh_ticket'];
        } else {
            $deviceId = $request->param('Device-Id') ? $request->param('Device-Id') : ($_POST['Device-Id'] ?? '');
            $ticket = $request->param('ticket') ? $request->param('ticket') : ($_POST['ticket'] ?? '');
            if ($deviceId && $ticket) {
                $info = Ticket::get($ticket, $deviceId, true);
                $request->log_uid = intval($info['uid']);
            }
        }

        return $next($request);
    }
}
