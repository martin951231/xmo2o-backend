<?php

/**
 * 同步IM用户表
 */
class ImUser
{
    /**
     * 同步用户昵称和头像
     */
    public function syncInfo(array $params)
    {
        $type = isset($params['type']) ? trim($params['type']) : '';
        if (!$type) {
            return false;
        }
        if ($type == 'user') {
            //普通用户
            $uid = isset($params['uid']) ? intval($params['uid']) : 0;
            $user = M('User')->where(['uid' => $uid])->field('uid,nickname,avatar')->find();
            if (empty($user)) {
                return false;
            }
            $imUser = M('Im_user')->where(array('username' => 'user_' . $uid))->find();
            if (empty($imUser) || ($imUser['nickname'] == $user['nickname'] && $imUser['avatar'] == $user['avatar'])) {
                return false;
            }
            M('Im_user')->where(array('id' => $imUser['id']))->save(array('nickname' => $user['nickname'], 'avatar' => $user['avatar']));
        } else if ($type == 'store') {
            //店铺客服
            $storeId = isset($params['store_id']) ? intval($params['store_id']) : 0;
            $store = M('Merchant_store')->where(array('store_id' => $storeId))->field("name ,logo")->find();
            if (empty($store)) {
                return false;
            }
            $imUser = M('Im_user')->where(array('uuid' => $storeId, 'type' => 'store'))->find();
            if (empty($imUser) || ($imUser['nickname'] == $store['name'] && $imUser['avatar'] == $store['logo'])) {
                return false;
            }
            M('Im_user')->where(array('uuid' => $storeId, 'type' => 'store'))->save(array('nickname' => $store['name'], 'avatar' => $store['logo']));
        } else if ($type == 'plat') {
            //平台客服
            $nickname = M('Config')->where(array('name' => 'plat_kefu_alias'))->getField('value');
            $avatar = M('Config')->where(array('name' => 'wechat_share_img'))->getField('value');
            $imUser = M('Im_user')->where(array('uuid' => 0, 'type' => 'plat'))->find();
            if (empty($imUser) || ($imUser['nickname'] == $nickname && $imUser['avatar'] == $avatar)) {
                return false;
            }
            M('Im_user')->where(array('uuid' => 0, 'type' => 'plat'))->save(array('nickname' => $nickname, 'avatar' => $avatar));
        }
    }
}