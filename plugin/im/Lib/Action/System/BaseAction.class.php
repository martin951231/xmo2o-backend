<?php

class BaseAction extends Action
{

    public $pageSize = 10;

    const MAX_AUTO_REPLY_COUNT = 10;

    protected function _initialize()
    {
        //基类
        $plugin_base = include(PLUGIN_PATH . '../plugin_public/plugin_base.php');
        //基类公共参数
        foreach ($plugin_base['action'] as $key => $value) {
            $this->$key = $value;
        }
        foreach ($plugin_base['tpl'] as $key => $value) {
            $this->assign($key, $value);
        }
        session_start();
    }

    public function merchant($field = '')
    {
        $sess = session('merchant');
        if (empty($field)) {
            return $sess;
        } else {
            return isset($sess[$field]) ? $sess[$field] : null;
        }
    }

    public function system($field = '')
    {
        $sess = session('system');
        if (empty($field)) {
            return $sess;
        } else {
            return isset($sess[$field]) ? $sess[$field] : null;
        }
    }

    public function village($field = '')
    {
        $sess = session('house');
        if (empty($field)) {
            return $sess;
        } else {
            return isset($sess[$field]) ? $sess[$field] : null;
        }
    }
}