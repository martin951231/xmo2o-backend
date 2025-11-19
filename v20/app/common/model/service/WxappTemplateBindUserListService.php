<?php
/**
 * 小程序订阅消息模板用户授权service
 * Author: 钱大双
 * Date Time: 2020年12月8日14:58:49
 */

namespace app\common\model\service;

use app\common\model\db\WxappTemplateBindUserList;

class WxappTemplateBindUserListService
{
    public $userObj = null;

    public function __construct()
    {
        $this->userObj = new WxappTemplateBindUserList();
    }

    /**
     * 保存小程序订阅消息模板授权信息
     * @param $param array
     * @return bool
     */
    public function saveTemplateRole($param)
    {
        // 绑定的模板id
        $templateList = $param['template_list'] ?? [];
        // 微信小程序openID
        if (empty($param['wxapp_openid'])) {
            $user = (new \app\common\model\service\UserService())->userFind('wxapp_openid', $where = ['uid' => $param['uid']]);
            $param['wxapp_openid'] = $user['wxapp_openid'];
        }
        $wxappOpenid = $param['wxapp_openid'] ?? '';

        if (empty($templateList) || empty($wxappOpenid)) {
            return false;
        }

        foreach ($templateList as $templateKey) {
            $data[] = [
                'template_key' => $templateKey,
                'wxapp_openid' => $wxappOpenid,
            ];
        }
        $res = $this->userObj->addAll($data);
        return $res;
    }
}