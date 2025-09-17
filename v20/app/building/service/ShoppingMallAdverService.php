<?php

namespace app\building\service;

use app\building\model\ShoppingMallAdver;

class ShoppingMallAdverService
{


    /**
     * 获取广告
     * @param $mallId
     * @param $catKey
     * @param int|array $limit
     * @param array $extraWhere
     */
    public function getAdverByKey($mallId, $catKey, $limit = 0, $extraWhere = [])
    {
        $where = [
            'mall_id' => $mallId,
            'cat_key' => $catKey,
            'status' => 1,
        ];
        if (isset($extraWhere['recommend_type'])) {
            $where['recommend_type'] = $extraWhere['recommend_type'];
        }
        $mod = (new ShoppingMallAdver())
            ->where($where)
            ->withoutField('mall_id,sort,status,last_time,click_number,cat_key,recommend_type')
            ->order('sort', 'DESC');
        if (is_array($limit)) {
            $mod = $mod->limit($limit[0], $limit[1]);
        } else {
            $mod = $mod->limit($limit);
        }
        $adver = $mod
            ->select()
            ->each(function ($item) {
                $item['pic'] = replace_file_domain($item['pic']);
                $item['focus_pic'] = replace_file_domain($item['focus_pic']);
                return $item;
            })
            ->toArray();
        return $adver;
    }

    public function replaceUrlToWxappPage($adver)
    {
        foreach ($adver as &$_adv) {
            $_adv['url']  =   htmlspecialchars_decode($_adv['url']);
            $_adv['wxapp_page'] = htmlspecialchars_decode($_adv['wxapp_page']);

            if ($_adv['wxapp_id']) {
                $_adv['url'] = 'wxapp://' . $_adv['wxapp_id'];
                if ($_adv['wxapp_page']) {
                    // 小程序页面
                    $_adv['url'] .= '://' . $_adv['wxapp_page'];
                }
            }
            unset($_adv['wxapp_id'], $_adv['wxapp_page']);
        }
        return $adver;
    }

    public function replaceUrlToAppPage($adver, $app_type = '')
    {
        foreach ($adver as &$_adv) {
            if ($_adv['app_open_type'] == 2) {
                //打开app
                if ($_adv['ios_app_name'] && $_adv['ios_app_url'] && $_adv['android_app_name'] && $_adv['android_app_url']) {
                    $_adv['url'] = 'openApp://' . $_adv['ios_app_name'] . '://' . base64_encode($_adv['ios_app_url']) . '://' . $_adv['android_app_name'] . '://' . base64_encode($_adv['android_app_url']);
                } else {
                    if ($app_type == 1) {
                        // ios
                        if ($_adv['ios_app_name'] && $_adv['ios_app_url']) {
                            $_adv['url'] = 'openApp://' . $_adv['ios_app_name'] . '://' . base64_encode($_adv['ios_app_url']) . '://://';
                        }
                    } elseif ($app_type == 2) {
                        // 安卓
                        if ($_adv['android_app_name'] && $_adv['android_app_url']) {
                            $_adv['url'] = 'openApp://://://' . $_adv['android_app_name'] . '://' . base64_encode($_adv['android_app_url']);
                        }
                    }
                }
                unset($_adv['ios_app_name'], $_adv['ios_app_url'], $_adv['android_app_name'], $_adv['android_app_url']);
            } else {
                // 打开小程序
                if ($_adv['app_wxapp_id'] && $_adv['app_wxapp_username']) {
                    $_adv['url'] = 'wxapp://' . $_adv['app_wxapp_id'] . '://' . $_adv['app_wxapp_username'];
                    // 小程序页面
                    $_adv['url'] .= '://' . $_adv['wxapp_page'];
                }
                unset($_adv['wxapp_id'], $_adv['wxapp_page']);
            }
        }
        return $adver;
    }
}
