<?php


namespace app\common\model\service;

use think\facade\Db;

/**
 * 语言服务类
 * @author: 张涛
 * @date: 2020/9/8
 * @package app\common\model\service
 */
class LangService
{

    public $serviceLangTable = [//请全部小写'appintro',
        'area',
        'card_coupon_send_history',
        'card_group',
        'card_new',
        'card_new_coupon',
        'card_new_record',
        'home_menu',
        'merchant_category',
        'merchant',
        'merchant_store',
        'merchant_store_shop',
        'national_phone',
        'new_merchant_menu',
        'shop_category',
        'shop_goods',
        'shop_goods_sort',
        'shop_goods_spec',
        'shop_goods_spec_value',
        'shop_goods_subsidiary_group',
        'shop_subsidiary_piece',
        'slider',
        'system_coupon',
        'user_level',
        'shop_goods_properties',
        'shop_combination',
        'merchant_store_foodshop_data',
        'foodshop_customer_form',
        'foodshop_table_type',
        'foodshop_table',
        'merchant_store_foodshop',
        'foodshop_customer_label',
        'meal_store_category',
        'foodshop_goods_package',
        'foodshop_goods_package_detail',
        'foodshop_goods_sort',
        'group'
    ];

    public function getServiceLang()
    {
        $prefix = config('database.connections.mysql.prefix');
        $lang = Db::table($prefix . 'lang_service')->select();
        return $lang ? $lang->toArray() : [];
    }

    /**
     * 验证表是否设置了多语言
     * @param  $model string 表名
     * @return boolean
     * @author: 衡婷妹
     * @date: 2021/3/8
     */
    public function checkLangModel($model)
    {
        if(!in_array(str_replace('_','',strtolower($model)),str_replace('_','',$this->serviceLangTable))){
            return false;
        }
        return true;
    }

    /**
     * 获取语言列表
     * @return array
     * @author: 张涛
     * @date: 2020/9/8
     */
    public function langList()
    {
        $return = [
            'lang_list' => [],
            'now_lang' => cfg('default_language') ? cfg('default_language') : 'chinese'
        ];
        $default = '';
        $clientLang = $_SERVER['HTTP_ACCEPT_LANGUAGE']??'';
        if ($clientLang) {
            $clientLangArr = explode(',', $clientLang);
            $first = $clientLangArr[0] ? strtolower($clientLangArr[0]) : strtolower($default);
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            preg_match_all('/[lL]anguage\/([a-zA-Z_]+){1}/', $_SERVER['HTTP_USER_AGENT'], $result);
            $first = $result[1] ? strtolower($result[1][0]) : strtolower($default);
        }
        $serviceLang = $this->getServiceLang();
        if ($serviceLang) {
            foreach ($serviceLang as $key => $value) {
                $value['as'] = strtolower($value['as']);
                if ($first && strpos($value['as'], $first) !== false) {
                    $return['now_lang'] = $value['val'];
                }
                $return['lang_list'][] = $value;
            }
        }
        if (cfg('system_lang')) {
            $return['now_lang'] = cfg('system_lang');
        }
        return $return;
    }

}