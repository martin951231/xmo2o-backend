<?php

declare(strict_types=1);

namespace app\im\model;

use Exception;
use think\facade\Db;
use think\Model;

/**
 * @mixin \think\Model
 */
class ImUser extends Model
{
    const PREFIX_STORE = 'store';

    const PREFIX_USER = 'user';

    const PREFIX_PLAT = 'plat';

    const PREFIX_DELIVER = 'deliver';
    const PREFIX_VILLAGE_USER = 'villageBind';
    const PREFIX_VILLAGE_KF = 'village';
    public function getUidAndRole($username)
    {
        $split = explode('_', $username);
        return [$split[0], $split[1]];
    }

    /**
     * 用户名格式 user/store/merchant_{记录id}_其他   前两段为用户标识uuid
     * @param $username
     *
     */
    public function getUUIDByUsername($username)
    {
        $split = explode('_', $username);
        return isset($split[0]) ? $split[0] . '_' . $split[1] : '';
    }


    public function getUserBaseInfo($username)
    {
        $split = explode('_', $username);
        $prefix = $split[0];
        $uuid = intval($split[1]);
        if ($prefix == self::PREFIX_USER) {
            //用户
            $user = Db::name('user')
                ->where('uid', $uuid)
                ->field("nickname,avatar")
                ->find();
        } else if ($prefix == self::PREFIX_STORE) {
            //店铺客服
            $tmp_store = Db::name('merchant_store')->where('store_id', $uuid)->find();
            $user = [];
            $user['nickname'] = $tmp_store['name'];
            $user['avatar'] = $tmp_store['logo'];
        } else if ($prefix == self::PREFIX_PLAT) {
            //平台
            $user['nickname'] = cfg('plat_kefu_alias');
            $user['avatar'] = cfg('wechat_share_img');
        } else if ($prefix == self::PREFIX_DELIVER) {
            //配送员
            $user = Db::name('deliver_user')->where('uid', $uuid)
                ->field('name AS nickname')
                ->find();
            $user && $user['avatar'] = '/static/im/icon/deliver_icon.png';
        }else if ($prefix == self::PREFIX_VILLAGE_USER) {
            //业主
            $user = Db::name('house_village_user_bind')->where('pigcms_id', $uuid)
                ->field('pigcms_id,village_id,uid,phone,name AS nickname')
                ->find();
            if($user && $user['uid']){
                $user['avatar']= cfg('site_url').'/static/im/icon/user_icon.png';
                $uinfo = Db::name('user')
                    ->where('uid', $user['uid'])
                    ->field("nickname,avatar")
                    ->find();
                if($uinfo && isset($uinfo['avatar']) && $uinfo['avatar']){
                    $user['avatar']=$uinfo['avatar'];
                }
            }
            $user && $user['avatar'] =  cfg('site_url').'/static/im/icon/user_icon.png';
        }else if ($prefix == self::PREFIX_VILLAGE_KF) {
            //业主
            $user = Db::name('im_user')->where('username', $username)->where('uuid', $uuid)
                ->field('*')
                ->find();
            if($user && empty($user['avatar'])){
                $user['avatar']=cfg('site_url').'/static/im/icon/user_icon.png';
            }elseif($user && !empty($user['avatar'])){
                $user['avatar']=replace_file_domain($user['avatar']);
            }
            
        }
        return $user ?? [];
    }

    public function register($username)
    {
        $user = $this->where(['username' => $username])->find();
        $baseInfo = $this->getUserBaseInfo($username);
        if (empty($baseInfo)) {
            throw new Exception('用户信息获取失败');
        }

        if (empty($user)) {
            $split = explode('_', $username);
            $data = [
                'username' => $username,
                'nickname' => $baseInfo['nickname'] ?? '',
                'avatar' => $baseInfo['avatar'] ?? '',
                'type' => $split[0],
                'uuid' => $split[1]
            ];
            $data['id'] = $this->insertGetId($data);
            return $data;
        }else{
            $user->nickname = $baseInfo['nickname'] ?? '';
            $user->avatar = $baseInfo['avatar'] ?? '';
            return $user->toArray();
        }
    }

    public function getUser($username, $field = true)
    {
        $user = $this->where(['username' => $username])->field($field)->find();
        if ($user) {
            $user->avatar = $user->avatar ? replace_file_domain($user->avatar) : '';
            return $user->toArray();
        } else {
            return [];
        }
    }
}
