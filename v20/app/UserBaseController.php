<?php

namespace app;
/**
 * 平台用户基类
 * @package app
 */
class UserBaseController extends BaseController
{
    /**
     * 登录用户信息
     * @var
     */
    public $userInfo;

    /**
     * 登录用户ID
     * @var
     */
    public $uid;

    public function initialize()
    {
        parent::initialize();

        $this->uid = intval($this->request->log_uid);

        $userId = intval($this->request->log_uid);
        if ($userId) {
            // 获得用户信息
            $userService = new UserService();
            $user = $userService->getUser($userId);

            // 用户id
            $this->_uid = $userId;

            // 用户信息
            $this->userInfo = $user;
        } else {
            // 临时用户信息
            $this->userTemp = [
                'uid' => 0,
                'nickname' => $this->request->param('nickname'),
                'avatar' => $this->request->param('avatar'),
                'openid' => $this->request->param('openid'),
                'wxapp_openid' => $this->request->param('wxapp_openid'),
                'alipay_uid' => $this->request->param('alipay_uid'),
            ];
        }
        // var_dump($this->userInfo);
        // 读取配置缓存
        $cache = cache();
        $config = $cache->get('config');
        if (empty($config)) {
            $configService = new \app\common\model\service\ConfigService;
            $all_config = $configService->getConfigData();
            $cache->set('config', $all_config);
            $config = $all_config;
        }

        // 设置当前城市
        $config = (new AreaService())->setNowCity($config);

        // 设置域名
        $config['site_url'] = $this->request->server('REQUEST_SCHEME') . '://' . $this->request->server('SERVER_NAME');

        // 设置配置缓存
        $cache->set('config', $config);

    }
}