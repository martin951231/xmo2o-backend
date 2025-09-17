<?php

class KefuAction extends BaseAction
{
    protected function _initialize()
    {
        parent::_initialize();
        if (ACTION_NAME != 'login' && !session('NewKefu')) {
            $this->redirect('Kefu/login');
        }
    }

    public function login()
    {
        $this->display();
    }

    public function index()
    {
        $bindUid = session('NewKefu.uid');
        $this->assign('bindUid', $bindUid);
        $this->display();
    }

    public function chat()
    {
        $url = isset($_GET['redirect']) ? trim(urldecode($_GET['redirect'])) : '';
        $this->assign('url', $url);
        $this->display();
    }
}