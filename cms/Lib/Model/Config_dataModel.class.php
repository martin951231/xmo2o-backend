<?php

class Config_dataModel extends Model
{
    public function get_config_data($expire = 3600, $forceUpdate = false)
    {
        $config_data = S('system_config_data');
        if (empty($config_data) || $forceUpdate) {
            $config_data = array();
            $configs = $this->field('`name`,`value`')->select();
            foreach ($configs as $key => $value) {
                $config_data[$value['name']] = $value['value'];
            }

            S('system_config_data', $config_data, $expire);
        }

        return $config_data;
    }

    public function save_config_data($name, $value)
    {
        $config_data = $this->get_config_data();

        if (isset($config_data[$name])) {
            if ($this->where(array('name' => $name))->data(array('value' => $value))->save() !== FALSE) {
                $this->remove_cache();
                return true;
            } else {
                return false;
            }
        } else {
            if ($this->data(array('name' => $name, 'value' => $value))->add()) {
                $this->remove_cache();
                return true;
            } else {
                return false;
            }
        }
    }

    public function save_config_data_all($data)
    {
        $config_data = $this->get_config_data();
        $config_key = array_keys($config_data);
        foreach ($data as $key => $value) {
            if (in_array($key,$config_key)) {
                $this->where(array('name' => $key))->data(array('value' => $value))->save();
            } else {
                $this->data(array('name' => $key, 'value' => $value))->add();
            }
        }
        $this->remove_cache();
        return true;
        
    }

    /*1. 店铺综合页面， 主标题： 品质  副标题：潮玩享乐
    2. 团购，主标题：团购别称   副标题：大牌甄选
    3. 外卖，主标题：外卖别称   副标题：本周特惠
    4. 餐饮，主标题：餐饮别称   副标题：吃遍全城
    5.  酒店，主标题：酒店   副标题：特价精选
    6.  拼团，主标题： 拼团    副标题：甄选优惠*/
    public function get_index_guess_like($is_plan = false, $from = '')
    {
        $config_data = $this->get_config_data();
        $return = array();
        $is_sort = 0;
        // 店铺
        if ($config_data['index_guess_like_hide_store']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_store'] ? $config_data['index_guess_like_title_store'] : L_('品质'),
                'desc' => $config_data['index_guess_like_desc_store'] ? $config_data['index_guess_like_desc_store'] : L_('潮玩享乐'),
                'sort' => intval($config_data['index_guess_like_sort_store']),
                're_type' => 'store',
                'default_sort' => 6,
            );
            $config_data['index_guess_like_sort_store']>0 && $is_sort = 1;
        }
        // 外卖
        if ($config_data['index_guess_like_hide_shop']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_shop'] ? $config_data['index_guess_like_title_shop'] : C('config.shop_alias_name'),
                'desc' => $config_data['index_guess_like_desc_shop'] ? $config_data['index_guess_like_desc_shop'] : L_('本周特惠'),
                'sort' => intval($config_data['index_guess_like_sort_shop']),
                're_type' => 'shop',
                'default_sort' => 5,
            );
            $config_data['index_guess_like_sort_shop']>0 && $is_sort = 1;
        }
        // 团购
        if ($config_data['index_guess_like_hide_group']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_group'] ? $config_data['index_guess_like_title_group'] : C('config.group_alias_name'),
                'desc' => $config_data['index_guess_like_desc_group'] ? $config_data['index_guess_like_desc_group'] : L_('大牌甄选'),
                'sort' => intval($config_data['index_guess_like_sort_group']),
                're_type' => 'group',
                'default_sort' => 4,
            );
            $config_data['index_guess_like_sort_group']>0 && $is_sort = 1;
        }
        // 餐饮
        if ($config_data['index_guess_like_hide_meal']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_meal'] ? $config_data['index_guess_like_title_meal'] : C('config.meal_alias_name'),
                'desc' => $config_data['index_guess_like_desc_meal'] ? $config_data['index_guess_like_desc_meal'] : L_('吃遍全城'),
                'sort' => intval($config_data['index_guess_like_sort_meal']),
                're_type' => 'meal',
                'default_sort' => 3,
            );
            $config_data['index_guess_like_sort_meal']>0 && $is_sort = 1;
        }
        // 酒店
        if ($config_data['index_guess_like_hide_hotel']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_hotel'] ? $config_data['index_guess_like_title_hotel'] : L_('酒店'),
                'desc' => $config_data['index_guess_like_desc_hotel'] ? $config_data['index_guess_like_desc_hotel'] : L_('特价精选'),
                'sort' => intval($config_data['index_guess_like_sort_hotel']),
                're_type' => 'hotel',
                'default_sort' => 2,
            );
            $config_data['index_guess_like_sort_hotel']>0 && $is_sort = 1;
        }
        // 拼团
        if ($config_data['index_guess_like_hide_pin_group']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_pin_group'] ? $config_data['index_guess_like_title_pin_group'] : L_('拼团'),
                'desc' => $config_data['index_guess_like_desc_pin_group'] ? $config_data['index_guess_like_desc_pin_group'] : L_('甄选优惠'),
                'sort' => intval($config_data['index_guess_like_sort_pin_group']),
                're_type' => 'pin_group',
                'default_sort' => 1,
            );
            $config_data['index_guess_like_sort_pin_group']>0 && $is_sort = 1;
        }

        //种草
        if ($from != 'house' && $config_data['index_guess_like_hide_grass']==0) {
            $return[] = array(
                'title' => $config_data['index_guess_like_title_grass'] ? $config_data['index_guess_like_title_grass'] : L_('种草'),
                'desc' => $config_data['index_guess_like_desc_grass'] ? $config_data['index_guess_like_desc_grass'] : L_('种草优选'),
                'sort' => intval($config_data['index_guess_like_sort_grass']),
                're_type' => 'grass',
                'default_sort' => 1,
            );
            $config_data['index_guess_like_sort_grass']>0 && $is_sort = 1;
        }

        // 发现好菜
        if ($from != 'house' && $config_data['index_guess_like_hide_shop_goods']==0) {
            $condition_goods = [];
            $condition_goods['business_type'] = 'plat';
            $condition_goods['city_id'] = C('config.now_city');
            $goods_list = D('Shop_goods')->get_goods_by_guess_like($condition_goods);
            $count = $goods_list['count'];
            if($count){
                $return[] = array(
                    'title' => $config_data['index_guess_like_title_shop_goods'] ? $config_data['index_guess_like_title_shop_goods'] : L_('发现好菜'),
                    'desc' => $config_data['index_guess_like_desc_shop_goods'] ? $config_data['index_guess_like_desc_shop_goods'] : L_('吃遍全城'),
                    'sort' => intval($config_data['index_guess_like_sort_shop_goods']),
                    're_type' => 'shop_goods',
                    'default_sort' => 1,
                );
                $config_data['index_guess_like_sort_shop_goods']>0 && $is_sort = 1;
            }

        }

        if ($is_sort ) {
            $sort_arr = array_column($return, 'sort');
            array_multisort($sort_arr,SORT_DESC,SORT_NUMERIC  ,$return);
        }else{
            $default_sort = array_column($return, 'default_sort');
            $is_sort && array_multisort($default_sort,SORT_DESC,SORT_NUMERIC  ,$return);
        }

        // 系统推荐
        if ($is_plan) {
            return $return;
        }
        if ($config_data['index_guess_like_hide']==0) {
            $all = array(
                'title' => $config_data['index_guess_like_title'] ? $config_data['index_guess_like_title'] : L_('猜你喜欢'),
                'desc' => $config_data['index_guess_like_desc'] ? $config_data['index_guess_like_desc'] : L_('推荐更多的好物'),
                're_type' => 'all',
            );
            // if (count($return)==1 && $config_data['index_guess_like_hide_shop_goods']!=0) {
            //     $return = array($all);
            // }else{
            //     $return = array_merge(array($all),$return);
            // }
            $return = array_merge(array($all),$return);

        }
        return $return;
    }

    public function remove_cache()
    {
        S('system_config_data', null);
    }

    public function get_jg_im_config()
    {
        $jg_im_appkey = M('Config')->where(['name' => 'jg_im_appkey'])->getField('value');
        $jg_im_masterkey = M('Config')->where(['name' => 'jg_im_masterkey'])->getField('value');
        return ['im_appkey' => $jg_im_appkey, 'im_masterkey' => $jg_im_masterkey];
    }
}

?>