<?php

/**
 * 公共模块下的数据模型
 */

namespace app\common\model\service;

use app\common\model\db\Merchant;
use app\common\model\db\ReplyPic;
use app\common\model\db\User;

class ResourceService
{
    /**
     * 评论列表模型
     * @author: 张涛
     * @date: 2021/05/24
     */
    public static function replyModel($data)
    {
        $rs = [];
        $userService = new UserService();
        $replyPicMod = new ReplyPic();
        $userModel = new User();
        $merchantModel = new Merchant();
        foreach ($data as $d) {
            if ($d['reply_pic']) {
                $images = array_filter(explode(';', $d['reply_pic']));
                $images = array_map(function ($r) {
                    return replace_file_domain($r);
                }, $images);
            } else if (isset($d['pic']) && isset($d['order_type']) &&  in_array($d['order_type'],[0,2]) && $d['pic']) {
                //兼容以前旧版团购评论记录图片
                $picIds = explode(',', $d['pic']);
                $images = $replyPicMod->whereIn('pigcms_id', $picIds)->column('pic');
                $images = array_map(function ($r) {
                    $imgArr = explode(',', $r);
                    if (stripos($r, '/upload/') === false) {
                        $r = '/upload/reply/group/' . str_replace(',', '/', $r);
                    } else if (count($imgArr) == 2) {
                        $r = str_replace(',', '/', $r);
                    }
                    return replace_file_domain($r);
                }, $images);
            } else {
                $images = [];
            }
            $merchant_reply = null;
            if(!empty($d['merchant_reply_content'])){
                $merInfo = $merchantModel->field(['name','logo'])->where('mer_id', $d['mer_id'])->find();
                $merchant_reply = [
                    'headimg'   =>  !empty($merInfo['logo']) ? replace_file_domain($merInfo['logo']) : '',
                    'nickname'  =>  ($merInfo['name'] ?? '')  . ' （' . date('Y/m/d H:i', $d['merchant_reply_time']) . '）',
                    'content'   =>  $d['merchant_reply_content'],
                    'time'      =>  date('Y/m/d H:i', $d['merchant_reply_time'])
                ];
            }
            $user_reply = null;
            if(!empty($d['user_reply_merchant'])){
                $userInfo = $userModel->field(['nickname','avatar'])->where('uid', $d['uid'])->find();
                $user_reply = [
                    'headimg'   =>  !empty($userInfo['avatar']) ? replace_file_domain($userInfo['avatar']) : '',
                    'nickname'  =>  $userInfo['nickname'] ?? '',
                    'content'   =>  $d['user_reply_merchant'],
                    'time'      =>  date('Y/m/d H:i', $d['user_reply_merchant_time'])
                ];
            }
            
            $rs[] = [
                'reply_id' => $d['pigcms_id'] ?? 0,
                'headimg' => $userService->userAvatarDisplay($d['avatar'] ?? ''),
                'nickname' => (isset($d['anonymous']) && $d['anonymous'] == 1) ? '匿名用户' : ($d['nickname'] ?? ''),
                'score' => $d['score'] ?? 5,
                'is_consume' => 1,
                'is_good' => $d['is_good'] ?? 0,
                'add_time' => date('Y-m-d', $d['add_time'] ?? 0),
                'comment' => $d['comment'] ?? '',
                'images' => $images,
                'merchant_reply'    =>  $merchant_reply,
                'user_reply'        =>  $user_reply,
                'uid'               =>  $d['uid']
            ];
        }

        return $rs;
    }

    /**
     * 店铺列表模型，可拓展
     * @author: 张涛
     * @date: 2021/05/24
     */
    public static function storeListsModel($stores, $lat, $lng)
    {
        $allAreaIds = array_merge(array_column($stores, 'province_id'), array_column($stores, 'city_id'), array_column($stores, 'area_id'));
        $areaInfo = [];
        if ($allAreaIds) {
            $areaInfo = (new AreaService())->getNameByIds($allAreaIds);
        }

        $return = [];
        foreach ($stores as $s) {
            $areaName = $areaInfo[$s['area_id']] ?? '';
            $provinceName = $areaInfo[$s['province_id']] ?? '';
            $cityName = $areaInfo[$s['city_id']] ?? '';
            $address = $provinceName . $cityName . $areaName . $s['adress'];
            $phones = array_filter(explode(' ', $s['phone']));
            if ($lng && $lat) {
                $distance = get_distance($lat, $lng, $s['lat'], $s['long']);
            } else {
                $distance = 0;
            }

            $return[] = [
                'store_id' => $s['store_id'],
                'name' => $s['name'],
                'phone' => $phones,
                'score' => get_format_number($s['score']) ? : '5.0',
                'distance' => $distance > 0 ? get_range($distance, false, false) : '',
                'address' => $address
            ];
        }
        return $return;
    }
}