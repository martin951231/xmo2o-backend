<?php

/**
 * 客服中心
 */
class KefuAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct();
        if (empty($this->user_session)) {
            if ($this->is_app_browser) {
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                $this->error_tips('请先进行登录！', U('Login/index', $location_param));
            } else {
                $location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
                redirect(U('Login/index', $location_param));
            }
        }
    }

    /**
     * 店铺客服中转
     */
    public function store()
    {
        $merId = I('get.mer_id', 0, 'intval');
        $storeId = I('get.store_id', 0, 'intval');
        $uid = $this->user_session['uid'];
        if ($merId < 0 || $storeId < 0) {
            $this->error_tips('参数有误');
        }
        $kf = M('Merchant_store_kefu')->where(array('store_id' => $storeId, 'bind_uid' => $uid))->find();
        if (empty($kf)) {
            $this->error_tips('抱歉！您还不是店铺客服，暂时无法使用店铺客服功能', $this->config['site_url'] . '/wap.php');
        }
        $imUrl = build_store_im_conversation_url($kf['username'], 'store2user');
        redirect($imUrl);
    }

    /**
     * 平台客服中转
     */
    public function plat()
    {
        $uid = $this->user_session['uid'];
        $storeId = 0;
        $kf = M('Merchant_store_kefu')->where(array('store_id' => $storeId, 'bind_uid' => $uid))->find();
        if (empty($kf)) {
            $this->error_tips('抱歉！您还不是平台客服，暂时无法使用平台客服功能', $this->config['site_url'] . '/wap.php');
        }
        $imUrl = build_store_im_conversation_url($kf['username'], 'plat2user');
        redirect($imUrl);
    }

    /**
     * 用户登录入口
     */
    public function user()
    {
        $uid = $this->user_session['uid'];
        if ($uid < 1) {
            $this->error_tips('抱歉！您还未登录，暂时无法使用客服功能', $this->config['site_url'] . '/wap.php');
        }
        $kf = M('Merchant_store_kefu')->where(array('store_id' => 0))->find();
        if (empty($kf)) {
            $this->error_tips('抱歉！系统未设置平台客服', $this->config['site_url'] . '/wap.php');
        }
        $imUrl = build_im_chat_url('user_' . $uid, $kf['username'], 'user2plat', ['back' => 1]);
        redirect($imUrl);
    }

    /**
     * 用户会话列表
     */
    public function user_chat_list()
    {
        $uid = $this->user_session['uid'];
        if ($uid < 1) {
            $this->error_tips('抱歉！您还未登录，暂时无法使用客服功能', $this->config['site_url'] . '/wap.php');
        }
        $imUrl = build_store_im_conversation_url('user_' . $uid, 'user2plat');
        redirect($imUrl);
    }

    public function contactStore(){
        $uid = $this->user_session['uid'];
        if ($uid < 1) {
            $this->error_tips('抱歉！您还未登录，暂时无法使用客服功能', $this->config['site_url'] . '/wap.php');
        }
        $storeId = $_GET['store_id'];
        $imUrl = build_im_chat_url('user_' . $uid, 'store_'.$storeId, 'user2store');
        redirect($imUrl);
    }
}

?>