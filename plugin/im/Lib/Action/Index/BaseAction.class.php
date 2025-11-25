<?php

class BaseAction extends Action
{
    public $version = 0;

    /**
     * 接口鉴权白名单
     */
    const AUTH_WHITE = array(
        'JMessage' => array('init', 'loginUser','unreadCount','orderStatus','buildGroupChat','sendGroupMessage'),
    );

    protected function _initialize()
    {
        header('Access-Control-Allow-Origin:*');
        //基类
        $plugin_base = include(PLUGIN_PATH . '../plugin_public/plugin_base.php');
        //基类公共参数
        foreach ($plugin_base['action'] as $key => $value) {
            $this->$key = $value;
        }
        foreach ($plugin_base['tpl'] as $key => $value) {
            $this->assign($key, $value);
        }

        $this->version = $this->reqParams('chat_version', '0', 'intval');
        $this->authCheck();
    }

    public function authCheck()
    {
        $authWhite = self::AUTH_WHITE;
        $skipTicket = $this->reqParams('skip_ticket', 0);
        if (!$skipTicket && isset($authWhite[MODULE_NAME]) && !in_array(ACTION_NAME, $authWhite[MODULE_NAME])) {
            //Device-Id、ticket
            $deviceId = $this->reqParams('Device-Id', 'packapp', 'trim');
            $imTicket = $this->reqParams('im_ticket', '', 'trim');
            $ticket = $this->reqParams('ticket', '', 'trim');
            $ticket = $imTicket ? $imTicket : $ticket;
            if (empty($ticket)) {
                returnCodeError(L_('ticket不能为空'));
            } else {
                $parseTicket = ticket::get($ticket, $deviceId, false);
                if (empty($parseTicket) || $parseTicket['uid'] < 1) {
                    returnCodeError(L_('ticket解析失败'));
                } else {
                    $loginUser = M('im_user')->field('username')->find($parseTicket['uid']);
                    if (empty($loginUser)) {
                        returnCodeError(L_('用户不存在'));
                    }
                }
            }
        }
    }

    public function reqParams($name = '', $default = '', $filter = '')
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $input = $_GET;
        } else {
            $input = empty($_POST) ? json_decode(file_get_contents("php://input"), true) : $_POST;
        }
        if (empty($name)) {
            return $input;
        } else if (isset($input[$name])) {
            if ($filter) {
                return is_array($input[$name]) ? array_map($filter, $input[$name]) : $filter($input[$name]);
            } else {
                return $input[$name];
            }
        } else {
            return $default;
        }
    }
}