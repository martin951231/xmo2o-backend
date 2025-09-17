<?php
/**
 * UserService.php
 * 用户service
 * Create on 2020/9/9 16:46
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\MallOrder;
use app\mall\model\db\User;

class UserService
{
    public function __construct()
    {
        $this->UserModel = new User();
    }

    /**
     * 获取某个用户
     * @param $uid
     * @return array
     */
    public function getUser($uid)
    {
        if (empty($uid)) {
            throw new \think\Exception('缺少uid参数');
        }
        $user = $this->UserModel->getUserById($uid);
        /*if(empty($user)){
            throw new \think\Exception('uid为' . $uid . '的用户信息不存在');
        }*/
        return $user;
    }

    /**
     * 获取个人中心的页面信息
     * @param $log_uid
     * @return array
     */
    public function getPersonalInfo($log_uid)
    {
        if (empty($log_uid)) {
            throw new \think\Exception('缺少uid参数', 1002);
        }
        //用户信息
        $user = $this->UserModel->getUserById($log_uid);
        $user['phone'] = $user['phone'] ? phone_show($user['phone']) : '';
        $user['user_logo'] = $user['user_logo'] ? replace_file_domain($user['user_logo']) : cfg('site_url') . '/static/images/user_avatar.jpg';
        //获取链接
        $links['all'] = cfg('site_url') . '/packapp/plat/pages/my/my_order?state=0&type=mall';
        $links['dfk'] = cfg('site_url') . '/packapp/plat/pages/my/my_order?state=1&type=mall';
        $links['dfh'] = cfg('site_url') . '/packapp/plat/pages/my/my_order?state=2&type=mall';
        $links['dsh'] = cfg('site_url') . '/packapp/plat/pages/my/my_order?state=3&type=mall';
        $links['dpj'] = cfg('site_url') . '/packapp/plat/pages/my/my_order?state=4&type=mall';
        $links['tksh'] = cfg('site_url') . '/packapp/plat/pages/my/my_order?state=5&type=mall';

        $links['group'] = cfg('site_url') . '/packapp/plat/pages/plat_menu/my';
        $links['bargain'] = cfg('site_url') . '/packapp/plat/pages/plat_menu/my';
        $links['benefit'] = cfg('site_url') . '/packapp/plat/pages/coupon/myCoupon';
        $links['browser'] = cfg('site_url') . '/packapp/plat/pages/plat_menu/my';
        $links['address'] = cfg('site_url') . '/packapp/plat/pages/plat_menu/my';

        $links['connect_staff'] = cfg('site_url') . '/packapp/plat/pages/plat_menu/my?popup=kfservice';
        $links['cms_member'] = cfg('site_url') . '/wap.php?g=Wap&c=My&a=cards';
        $links['my_collect'] = cfg('site_url') . '/packapp/plat/pages/my/collect?type=mall';
        $links['my_spread'] = cfg('site_url') . '/wap.php?c=My&a=my_spread';//我的推广
        $links['my_money'] = cfg('site_url') . '/wap.php?c=My&a=my_money';//余额
        $links['my_integral'] = cfg('site_url') . '/wap.php?c=My&a=integral';//积分
        $links['my_level'] = cfg('site_url') . '/wap.php?c=My&a=levelUpdate';//会员等级

        //当前登录的用户的订单数量信息
        $links['dfk_num'] = (new MallOrder())->getCount([['o.uid', '=', $log_uid], ['o.status', '>=', 0], ['o.status', '<', 10]]);
        $links['dfh_num'] = (new MallOrder())->getCount([['o.uid', '=', $log_uid], ['o.status', '>=', 10], ['o.status', '<', 20]]);
        $links['dsh_num'] = (new MallOrder())->getCount([['o.uid', '=', $log_uid], ['o.status', '>=', 20], ['o.status', '<', 30]]);
        $links['dpj_num'] = (new MallOrder())->getCount([['o.uid', '=', $log_uid], ['o.status', '>=', 30], ['o.status', '<', 40]]);
        $links['shtk_num'] = 0;//不展示数量

        $arr = array_merge($user, $links);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 获取某个用户的收藏
     * @param $log_uid
     * @return array
     */
    public function getCollections($log_uid)
    {
        if (empty($log_uid)) {
            throw new \think\Exception('缺少uid参数');
        }
        $collectService = new MallUserCollectService();
        $collections = $collectService->getCollections($log_uid);
        if (!empty($collections)) {
            return $collections;
        } else {
            return [];
        }
    }
}