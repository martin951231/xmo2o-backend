<?php

/**
 * 朱梦群
 * 自定义页面
 * 2020/9/28
 * Class Mall_merchant_store_diypage_fieldModel
 */
class Mall_merchant_store_diypage_fieldModel extends Model{
    // 富文本中链接的store_id替换成当前店铺的store_id
    private function repalace_store_id($content = '',$seller_id){
        if($content){
            $html = htmlspecialchars_decode($content, ENT_QUOTES);
            $content = preg_replace_callback('/href=[\'\"]?([^\'\" ]+).*?[\'\"]?/', function($matches){ return richTextFormat($matches[1],$seller_id);}, $html);
            return htmlspecialchars($content);
        } else {
            return $content;
        }
    }

    /**
     * 查询
     *
     * store_id  	  店铺字段
     * page_id      页面ID
     *
     */
    public function get_field($store_id, $page_id, $seller_id = 0) {
        if ($store_id) {
            $condition_field['store_id'] = $store_id;
        }
        if (empty($seller_id)) {
            $seller_id	= $store_id;
        }

        $condition_field['page_id'] = $page_id;
        $field_list = $this->field('`field_type`,`content`')->where($condition_field)->order('`field_id` ASC')->select();
        foreach ($field_list as &$value) {
            $value['content'] = unserialize($value['content']);
            if (strtolower($value['field_type']) == 'goods') {
                if (!empty($value['content']['goods'])) {
                    $goods = array();
                    foreach ($value['content']['goods'] as $product) {
                        $product['title'] = htmlspecialchars_decode($product['title'], ENT_QUOTES);
                        $product['image'] = getAttachmentUrl($product['image']);
                        $goods[] = $product;
                    }
                    $value['content']['goods'] = $goods;
                }
            } else if (strtolower($value['field_type']) == 'rich_text') {

                //替换富文本中的store_id
                $content = $this->repalace_store_id($value['content']['content'],$seller_id);
                $value['content']['content'] = str_replace('&quot;', '"', $content);
            } else if (strtolower($value['field_type']) == 'title') {
                $value['content']['title'] = htmlspecialchars_decode($value['content']['title'], ENT_QUOTES);
                $value['content']['sub_title'] = htmlspecialchars_decode($value['content']['sub_title'], ENT_QUOTES);
            } else if (strtolower($value['field_type']) == 'text_nav') {
                $text_navs = array();
                foreach ($value['content'] as $key => $nav) {
                    $nav['title'] = htmlspecialchars_decode($nav['title'], ENT_QUOTES);
                    $nav['name'] = htmlspecialchars_decode($nav['name'], ENT_QUOTES);
                    $text_navs[$key] = $nav;
                }
                $value['content'] = $text_navs;
            } else if (strtolower($value['field_type']) == 'image_nav') {
                $image_navs = array();
                foreach ($value['content'] as $key => $nav) {
                    $nav['title'] = htmlspecialchars_decode($nav['title'], ENT_QUOTES);
                    $nav['name'] = htmlspecialchars_decode($nav['name'], ENT_QUOTES);
                    $nav['image'] = getAttachmentUrl($nav['image']);
                    $image_navs[$key] = $nav;
                }
                $value['content'] = $image_navs;
            } else if (strtolower($value['field_type']) == 'link') {
                $links = array();
                if (!empty($value['content'])) {
                    foreach ($value['content'] as $key => $link) {
                        $link['title'] = htmlspecialchars_decode($link['title'], ENT_QUOTES);
                        $link['name'] = htmlspecialchars_decode($link['name'], ENT_QUOTES);
                        $links[$key] = $link;
                    }
                }
                $value['content'] = $links;
            } else if (strtolower($value['field_type']) == 'notice') {
                $value['content']['content'] = htmlspecialchars_decode($value['content']['content'], ENT_QUOTES);
            } else if (strtolower($value['field_type']) == 'image_ad') {
                if (!empty($value['content']['nav_list'])) {
                    $ads = array();
                    foreach ($value['content']['nav_list'] as $ad) {
                        $ad['title'] = htmlspecialchars_decode($ad['title'], ENT_QUOTES);
                        $ad['name'] = htmlspecialchars_decode($ad['name'], ENT_QUOTES);
                        $ad['image'] = getAttachmentUrl($ad['image']);
                        $ads[] = $ad;
                    }
                    $value['content']['nav_list'] = $ads;
                }
            } else if (strtolower($value['field_type']) == 'tpl_shop') {
                $value['content']['title'] = htmlspecialchars_decode($value['content']['title'], ENT_QUOTES);
            } else if  (strtolower($value['field_type']) == 'tpl_shop1') {
                $value['content']['title'] = htmlspecialchars_decode($value['content']['title'], ENT_QUOTES);
            } else if (strtolower($value['field_type']) == 'map') {
                $store_contact = D('Store_contact')->where(array('store_id' => $store_id))->find();

                if (empty($store_contact)) {
                    $store_contact['province'] = '';
                    $store_contact['city'] = '';
                    $store_contact['area'] = '';
                    $store_contact['address'] = '';
                    $store_contact['lng'] = '';
                    $store_contact['lat'] = '';
                    $store_contact['entity_name'] = '';
                } else {
                    $store_contact['area'] = $store_contact['county'];
                    $store_contact['lng'] = $store_contact['long'];
                    $store_contact['entity_name'] = $store_contact['entity_name'] ? $store_contact['entity_name'] : '';
                }

                $value['content'] = $store_contact;
            }
        }
        return $field_list;
    }

    /**
     *
     *  清空数组里空的字段
     *
     */
    protected function clear_field($array) {
        $new_array = array();
        if (is_array($array)) {
            if (!empty($array['goods'])) {
                $goods = array();
                foreach ($array['goods'] as $product) {
                    $product['title'] = htmlspecialchars(stripcslashes($product['title']), ENT_QUOTES); //解决serialize序列化单引号问题
                    $product['image'] = getAttachment($product['image']);
                    $goods[] = $product;
                }
                $array['goods'] = $goods;
            } else if (strtolower($array['type']) == 'rich_text') {
                $array['content'] = htmlspecialchars(stripcslashes($array['content']), ENT_QUOTES);
            } else if (strtolower($array['type']) == 'title') {
                $array['title'] = htmlspecialchars(stripcslashes($array['title']), ENT_QUOTES);
                $array['sub_title'] = htmlspecialchars(stripcslashes($array['sub_title']), ENT_QUOTES);
            } else if (strtolower($array['type']) == 'text_nav') {
                $type = array_pop($array);
                $text_navs = array();
                foreach ($array as $key => $nav) {
                    $nav['title'] = htmlspecialchars(stripcslashes($nav['title']), ENT_QUOTES);
                    $nav['name'] = htmlspecialchars(stripcslashes($nav['name']), ENT_QUOTES);
                    $text_navs[$key] = $nav;
                }
                $array = $text_navs;
                $array['type'] = $type;
            } else if (strtolower($array['type']) == 'image_nav') {
                $type = array_pop($array);
                $image_navs = array();
                foreach ($array as $key => $nav) {
                    $nav['title'] = htmlspecialchars(stripcslashes($nav['title']), ENT_QUOTES);
                    $nav['name'] = htmlspecialchars(stripcslashes($nav['name']), ENT_QUOTES);
                    $image_navs[$key] = $nav;
                }
                $array = $image_navs;
                $array['type'] = $type;
            } else if (strtolower($array['type']) == 'image_ad') {
                $nav_list = array();
                $array['nav_list'] = !empty($array['nav_list']) ? $array['nav_list'] : array();
                foreach ($array['nav_list'] as $key => $nav) {
                    $nav['title'] = htmlspecialchars(stripcslashes($nav['title']), ENT_QUOTES);
                    $nav['name'] = htmlspecialchars(stripcslashes($nav['name']), ENT_QUOTES);
                    $nav_list[] = $nav;
                }
                $array['nav_list'] = $nav_list;
                $array['type'] = 'image_ad';
            } else if (strtolower($array['type']) == 'link') {
                $type = array_pop($array);
                $links = array();
                foreach ($array as $key => $link) {
                    $link['title'] = htmlspecialchars(stripcslashes($link['title']), ENT_QUOTES);
                    $link['name'] = htmlspecialchars(stripcslashes($link['name']), ENT_QUOTES);
                    $links[$key] = $link;
                }
                $array = $links;
                $array['type'] = $type;
            } else if (strtolower($array['type']) == 'notice') {
                $array['content'] = htmlspecialchars(stripcslashes($array['content']), ENT_QUOTES);
            } else if (strtolower($array['type']) == 'component') {
                $array['name'] = htmlspecialchars(stripcslashes($array['name']), ENT_QUOTES);
            } else if (strtolower($array['type']) == 'tpl_shop') {
                $array['title'] = htmlspecialchars(stripcslashes($array['title']), ENT_QUOTES);
            } else if (strtolower($array['type']) == 'tpl_shop1') {
                $array['title'] = htmlspecialchars(stripcslashes($array['title']), ENT_QUOTES);
            }

            foreach ($array as $key => $value) {
                if (!empty($value) && $key != 'type') {
                    $new_array[$key] = $value;
                }
            }
        }
        return $new_array;
    }

    /**
     * 查询并解析，返回内容数组 返回  type,content
     *
     * store_id  	  店铺字段
     * page_id        page_id
     *
     */
    public function getParseFields($store_id, $page_id, $seller_id = 0, $drp_level = 0, $drp_diy_store = 1, $page_goods_source = 1, $uid=0) {

        if (empty($seller_id)) {
            $seller_id = $store_id;
        }
        if ($drp_level > 3) {
            $drp_level = 3;
        }

//        echo '123';
        $field_list = $this->get_field($store_id, $page_id,$seller_id);
//        echo "<pre>";
//        print_r($field_list);
        $fx_store_info = D('Store')->where("store_id='".$seller_id."'")->find();
        $supplier_store_info = D('Store')->where(array('store_id'=>$store_id))->find();
        if (empty($field_list))
            return array();

        $fieldHtmlArr = array();
        foreach ($field_list as $key => $value) {
            if ($value['field_type']=='store_cate') {
                $store_cate_arr = $value;
                unset($field_list[$key]);
            }
        }
        if ($store_cate_arr) {
            $field_list[] = $store_cate_arr;
        }

        foreach ($field_list as $key => $value) {
            if (empty($value['content']) && !in_array($value['field_type'], array('line', 'search', 'store', 'attention_collect')))
                continue;
            $fieldHtml = array();
            $fieldHtml['type'] = $value['field_type'];
            $fieldContent = $value['content'];
            $site_url = C('config.site_url');
            $shop_html = '';
            switch ($value['field_type']) {
                case 'title':
                    $fieldHtml['html'] = '<!-- 标题 -->';
                    $fieldHtml['html'] .= '<div' . (!empty($fieldContent['bgcolor']) ? ' class="custom-title-noline" style="background-color:' . $fieldContent['bgcolor'] . ';"' : '') . '>';
                    $fieldHtml['html'] .= '<div class="custom-title ' . ($fieldContent['show_method'] == 0 ? 'text-left' : ($fieldContent['show_method'] == 1 ? 'text-center' : 'text-right')) . '">';
                    if(!empty($fieldContent['txtcolor'])){
                        $fieldHtml['html'] .= '<h2 class="title" style="color:'.$fieldContent['txtcolor'].' !important;">' . htmlspecialchars_decode($fieldContent['title'], ENT_QUOTES) . '</h2>';
                    }else{
                        $fieldHtml['html'] .= '<h2 class="title">' . htmlspecialchars_decode($fieldContent['title'], ENT_QUOTES) . '</h2>';
                    }
                    if(!empty($fieldContent['txtcolor2'])){
                        $fieldHtml['html'] .= '<p class="sub_title" style="color:'.$fieldContent['txtcolor2'].' !important;">' . htmlspecialchars_decode($fieldContent['sub_title'], ENT_QUOTES) . '</p>';
                    }else{
                        $fieldHtml['html'] .= '<p class="sub_title">' . htmlspecialchars_decode($fieldContent['sub_title'], ENT_QUOTES) . '</p>';
                    }
                    $fieldHtml['html'] .= '</div>';
                    $fieldHtml['html'] .= '</div>';
                    break;

                case 'rich_text':
                    $fieldHtml['html'] = '<!-- 富文本内容区域 -->';
                    $fieldHtml['html'] .= '<div class="custom-richtext' . (!empty($fieldContent['screen']) ? ' custom-richtext-fullscreen' : '') . '" ' . (!empty($fieldContent['bgcolor']) ? 'style="background-color:' . $fieldContent['bgcolor'] . ';"' : '') . '>';
                    $fieldHtml['html'] .= htmlspecialchars_decode($fieldContent['content'], ENT_QUOTES);
                    $fieldHtml['html'] .= '</div>';
                    break;

                case 'notice':
                    if (!empty($fieldContent['content'])) {
                        $fieldHtml['html'] = '<!-- 店铺公告 -->';
                        $fieldHtml['html'] .= '<div class="custom-notice"><div class="custom-notice-inner"><div class="custom-notice-scroll"><span class="js-scroll-notice">公告：' . htmlspecialchars_decode($fieldContent['content'], ENT_QUOTES) . '</span></div></div></div>';
                    }
                    break;

                case 'line':
                    $fieldHtml['html'] = '<!-- 辅助线 -->';
                    $fieldHtml['html'] .= '<div class="custom-line-wrap"><hr class="custom-line"></div>';
                    break;

                case 'white':
                    $fieldHtml['html'] = '<!-- 辅助空白 -->';
                    $fieldHtml['html'] .= '<div class="custom-white" style="height:' . $fieldContent['height'] . 'px;"></div>';
                    break;

                case 'search':
                    $fieldHtml['html'] = '<!-- 商品搜索 -->';
                    $fieldHtml['html'] .= '<div class="custom-search"><form action="?c=Mall&a=search" method="get"><input type="hidden" name="c" value="Mall"/><input type="hidden" name="a" value="search"/><input type="search" class="custom-search-input" placeholder='.L_("商品搜索：请输入商品关键字").' name="key" value=""/><input type="hidden" name="store_id" value="' . (empty($drp_diy_store) ? $seller_id : $store_id) . '"/><button type="submit" class="custom-search-button">'.L_('搜索').'</button></form></div>';
                    break;

                case 'attention_collect':
                    $store_info = D('Merchant_store')->get_store_by_storeId($seller_id);

                    $collect_num = M('Merchant_user_relation')->where(array('store_id'=>$seller_id,'type'=>'foodshop'))->count();
                    /*
                    $Map['user_id'] = $_SESSION['wap_user']['uid'];
                    $Map['type'] = 2;
                    $Map['store_id'] = $_GET['id'];
                    $self_collect_goods_num = D('User_collect')->where($Map)->select();
                    $_Map['user_id'] = $_SESSION['wap_user']['uid'];
                    $_Map['data_type'] = 2;
                    $_Map['store_id'] = $_GET['id'];
                    $self_attention_goods_num = D('User_attention')->where($_Map)->select
                    */

                    // 浏览店铺地址
                    $look_store = C('config.site_url') . '/wap.php?g=Wap&c=Merchant&a=shop&store_id=' .  (empty($drp_diy_store) ? $seller_id : $store_id);

                    $fieldHtml['html'] = '<!-- 收藏和关注店铺 -->';
                    $fieldHtml['html'].='<style>.ft-links { padding: 15px; text-align: center }.ft-links>a { margin: 0 6px; color: #333 }@media only screen and (-webkit-min-device-pixel-ratio: 1.5), only screen and (min--moz-device-pixel-ratio: 1.5), only screen and (-o-min-device-pixel-ratio: 3/2), only screen and (min-device-pixel-ratio: 1.5) {.ft-links ~ .ft-copyright {border-top-width:1px;}}.ft-copyright>a { padding-top: 45px; color: #ccc }.ft-copyright:first-child { margin-top: 0px }.footer { margin: 0; padding: 0; min-height: 1px; text-align: center; line-height: 16px; background-color: #f8f8f8 }</style>';
                    $fieldHtml['html'] .= '<div class="ft-links custom-attention_collect"><a onclick="userCollect(' . (empty($drp_diy_store) ? $seller_id : $store_id) . ')" class="js-shouchang">收藏店铺(<span>' . ($collect_num ? $collect_num : 0) . '</span>)</a><a href="' . $look_store . '" class="js-guanzhu">浏览店铺</a></div>';
                    break;

                case 'store':
                    $fieldHtml['html'] = '<!-- 进入店铺 -->';
                    $fieldHtml['html'] .= '<div class="custom-store"><a class="custom-store-link clearfix" href="' . C('now_store.url') . '"><div class="custom-store-img"></div><div class="custom-store-name">' . C('now_store.name') . '</div><span class="custom-store-enter">进入店铺</span></a></div>';
                    break;

                case 'text_nav':
                    $fieldHtml['html'] = '<!-- 文本导航 -->';
                    $fieldHtml['html'] .= '<ul class="custom-nav clearfix">';
                    foreach ($fieldContent as $v) {
                        if (!empty($v['title'])) {
                            if (!empty($v['url']) && empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                /*if (stripos($v['url'], 'home.php?id=') !== false) {
                                    $v['url'] = preg_replace('/home.php\?id=(\d)+/i', 'home.php?id=' . $seller_id, $v['url']);
                                } else {
                                    $v['url'] .= '&store_id=' . $seller_id;
                                }*/
                                $v['url'] = $this->redirect_store($seller_id,$v['url']);
                            }
                            $fieldHtml['html'] .= '<li><a class="clearfix relative arrow-right" href="' . ($v['url'] ? $v['url'] : 'javascript:void(0);') . '" target="_blank"><span class="custom-nav-title">' . htmlspecialchars_decode($v['title'], ENT_QUOTES) . '</span></a></li>';
                        }
                    }
                    $fieldHtml['html'] .= '</ul>';
                    break;

                case 'image_nav':
                    $fieldHtml['html'] = '<!-- 图片导航 -->';
                    $fieldHtml['html'] .= '<ul class="custom-nav-4 clearfix">';
                    foreach ($fieldContent as $v) {
                        if (!empty($v['url']) && empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                            /*if (stripos($v['url'], 'home.php?id=') !== false) {
                                $v['url'] = preg_replace('/home.php\?id=(\d)+/i', 'home.php?id=' . $seller_id, $v['url']);
                            } else {
                                $v['url'] .= '&store_id=' . $seller_id;
                            }*/
                            $v['url'] = $this->redirect_store($seller_id,$v['url']);
                        }
                        $fieldHtml['html'] .= '<li><a href="' . ($v['url'] ? $v['url'] : 'javascript:void(0);') . '" target="_blank"><span class="nav-img-wap">' . ($v['image'] ? '<img class="js-lazy" src="' . replace_file_domain($v['image']) . '" style="display:inline;"/>' : '&nbsp;') . '</span><span class="title">' . htmlspecialchars_decode($v['title'], ENT_QUOTES) . '</span></a></li>';
                    }
                    $fieldHtml['html'] .= '</ul>';
                    break;

                case 'component':
                    if (!empty($fieldContent['id'])) {
                        $fieldHtml['html'] = '<!-- 自定义模块 -->';
                        $comFieldHtml = $this->getParseFields($store_id, 'custom_page', $fieldContent['id'], $seller_id, $drp_level, $drp_diy_store);
                        foreach ($comFieldHtml as $value) {
                            $fieldHtml['html'] .= $value['html'];
                        }
                    }
                    break;

                case 'link':
                    $fieldHtml['html'] = '<!-- 关联链接 -->';
                    $fieldHtml['html'] .= '<ul class="custom-nav clearfix">';
                    foreach ($fieldContent as $v) {
                        if ($v['type'] == 'link') {
                            if (!empty($v['url']) && empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                /*if (stripos($v['url'], 'home.php?id=') !== false) {
                                    $v['url'] = preg_replace('/home.php\?id=(\d)/i', 'home.php?id=' . $seller_id, $v['url']);
                                    $v['url'] = preg_replace('/home.php\?store_id=(\d)/i', 'home.php?id=' . $seller_id, $v['url']);
                                } else {
                                    $v['url'] .= '&store_id=' . $seller_id;
                                }*/
                                $v['url'] = $this->redirect_store($seller_id,$v['url']);
                            }
                            $fieldHtml['html'] .= '<li><a class="custom-link-link clearfix relative arrow-right" href="' . ($v['url'] ? $v['url'] : 'javascript:void(0);') . '" target="_blank"><span class="custom-nav-title">' . ($v['name'] ? htmlspecialchars_decode($v['name'], ENT_QUOTES) : L_('自定义外链')) . '</span></a></li>';
                        } elseif ($v['widget'] == 'pagecat') {
                            $page_list = M('Wei_page')->getCategoryPageNumberList($v['id'], $v['number']);
                            foreach ($page_list as $jv) {
                                if (empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                    $url = option('config.wap_site_url') . '/page.php?id=' . $jv['page_id'] . '&store_id=' . $seller_id;
                                } else {
                                    $url = option('config.wap_site_url') . '/page.php?id=' . $jv['page_id'];
                                }
                                $fieldHtml['html'] .= '<li><a class="clearfix relative arrow-right" href="' . $url . '" target="_blank"><span class="custom-nav-title">' . htmlspecialchars_decode($jv['page_name'], ENT_QUOTES) . '</span></a></li>';
                            }
                        } elseif ($v['widget'] == 'goodcat') {
                            /*$product_list = M('Product')->getGroupGoodNumberList($v['id'], $v['number']);
                            foreach ($product_list as $jv) {
                                if (empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                    $url = option('config.wap_site_url') . '/good.php?id=' . $jv['product_id'] . '&store_id=' . $seller_id;
                                } else {
                                    $url = option('config.wap_site_url') . '/good.php?id=' . $jv['product_id'];
                                }
                                $fieldHtml['html'] .= '<li><a class="clearfix relative arrow-right" href="' . $url . '" target="_blank"><span class="custom-nav-title">' . htmlspecialchars_decode($jv['name'], ENT_QUOTES) . '</span></a></li>';
                            }*/
                            $url = option('config.wap_site_url') . '/goodcat.php?id=' . $v['id']. '&store_id=' . $seller_id;
                            $fieldHtml['html'] .= '<li><a class="clearfix relative arrow-right" href="' . $url . '" target="_blank"><span class="custom-nav-title">' . htmlspecialchars_decode($v['name'], ENT_QUOTES) . '</span></a></li>';
                        } else {
                            if (!empty($v['url']) && empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                /*if (stripos($v['url'], 'home.php?id=') !== false) {
                                    $v['url'] = preg_replace('/home.php\?id=(\d)+/i', 'home.php?id=' . $seller_id, $v['url']);
                                } else {
                                    $v['url'] .= '&store_id=' . $seller_id;
                                }*/
                                $v['url'] = $this->redirect_store($seller_id,$v['url']);
                            }
                            if (!$v['title'] && $v['name']) {
                                $v['title'] = $v['name'];
                            }
                            $fieldHtml['html'] .= '<li><a class="clearfix relative arrow-right" href="' . ($v['url'] ? $v['url'] : 'javascript:void(0);') . '" target="_blank"><span class="custom-nav-title">' . htmlspecialchars_decode($v['title'], ENT_QUOTES) . '</span></a></li>';
                        }
                    }
                    $fieldHtml['html'] .= '</ul>';
                    break;

                case 'image_ad':
                    $fieldHtml['html'] = '<!-- 图片广告 --><style type="text/css"> .custom-image-swiper .swiper-slide a {width: 100%!important;}</style>';
                    if (!empty($fieldContent['nav_list'])) {
                        if (empty($fieldContent['image_type'])) {
                            $fieldHtml['html'] .= '<div class="custom-image-swiper" data-max-height="' . $fieldContent['max_height'] . '" data-max-width="' . $fieldContent['max_width'] . '"><div class="swiper-container" style="height:' . $fieldContent['max_height'] . 'px"><div class="swiper-wrapper">';
                            foreach ($fieldContent['nav_list'] as $v) {
                                if (!empty($v['url']) && empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                    /*if (stripos($v['url'], 'home.php?id=') !== false) {
                                        $v['url'] = preg_replace('/home.php\?id=(\d)/i', 'home.php?id=' . $seller_id, $v['url']);
                                        $v['url'] = preg_replace('/home.php\?store_id=(\d)/i', 'home.php?id=' . $seller_id, $v['url']);
                                    } else {
                                        $v['url'] .= '&store_id=' . $seller_id;
                                    }*/
                                    $v['url'] = $this->redirect_store($seller_id,$v['url']);
                                }
                                $fieldHtml['html'] .= '<div style="height:' . $fieldContent['max_height'] . 'px;" class="swiper-slide"><a href="' . ($v['url'] != '' ? $v['url'] : 'javascript:void(0);') . '" style="height:' . $fieldContent['max_height'] . 'px;">' . ($v['title'] != '' ? '<h3 class="title">' . htmlspecialchars_decode($v['title'], ENT_QUOTES) . '</h3>' : '') . '<img src="' . getAttachmentUrl($v['image']) . '"></a></div>';
                            }
                            $fieldHtml['html'] .= '</div></div>';
                            if (count($fieldContent['nav_list']) > 1) {
                                $fieldHtml['html'] .= '<div class="swiper-pagination">';
                                $num = 0;
                                foreach ($fieldContent['nav_list'] as $v) {
                                    $fieldHtml['html'] .= '<span class="swiper-pagination-switch' . ($num == 0 ? ' swiper-active-switch' : '') . '"></span>';
                                    $num++;
                                }
                                $fieldHtml['html'] .= '</div>';
                            }
                            $fieldHtml['html'] .= '</div>';
                        } else {
                            $fieldHtml['html'] .= '<ul class="custom-image clearfix">';
                            foreach ($fieldContent['nav_list'] as $v) {
                                if (!empty($v['url']) && empty($drp_diy_store) && stripos($v['url'], option('config.site_url')) !== false) {
                                    /*if (stripos($v['url'], 'home.php?id=') !== false) {
                                        $v['url'] = preg_replace('/home.php\?id=(\d)/i', 'home.php?id=' . $seller_id, $v['url']);
                                        $v['url'] = preg_replace('/home.php\?store_id=(\d)/i', 'home.php?id=' . $seller_id, $v['url']);
                                    } else {
                                        $v['url'] .= '&store_id=' . $seller_id;
                                    }*/
                                    $v['url'] = $this->redirect_store($seller_id,$v['url']);
                                }
                                $fieldHtml['html'] .= '<li' . ($fieldContent['image_size'] == '1' ? ' class="custom-image-small"' : '') . '><a href="' . ($v['url'] != '' ? $v['url'] : 'javascript:void(0);') . '">' . ($v['title'] != '' ? '<h3 class="title">' . htmlspecialchars_decode($v['title']) . '</h3>' : '') . '<img src="' . ($v['image']) . '"/></a></li>';
                            }
                            $fieldHtml['html'] .= '</ul>';
                        }
                    }
                    break;

                case 'goods':
                    //echo $fieldContent['size'];

                    $fieldHtml['html'] = '<!-- 商品 -->';
                    if (!empty($fieldContent['goods'])) {
                        if ($fieldContent['size'] == 0) {
                            $fieldHtml['html'].= '<ul class="js-goods-list sc-goods-list pic clearfix size-0">';
                            $i = 1;
                            foreach ($fieldContent['goods'] as $key => $value) {
                                $product = D('Shop_goods')->get_goods_by_id($value['id']);

                                if (empty($product)) {
                                    continue;
                                }
                                if ($page_goods_source == 2) {
                                    // 商城
                                    $value['url'] = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$value['id'];
                                } else {
                                    //快店
                                    $value['url'] = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id='.$value['id'];
                                }

                                $price = $product['price'];
                                //商品类型
                                $type = 0;

                                $fieldHtml['html'].='<li class="goods-card goods-list big-pic ' . ($fieldContent['size_type'] == 2 ? 'normal' : 'card') . '">';
                                $fieldHtml['html'].='<a href="' . $value['url'] . '&store_id=' . $seller_id . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name']) . '">';
                                $fieldHtml['html'].='<div class="photo-block" style="height:auto;">';
                                $fieldHtml['html'].='<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;">';
                                $fieldHtml['html'].='</div>';
                                $fieldHtml['html'].='<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . 'btn' . $fieldContent['buy_btn_type'] . ($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1 ? '' : ' hide') . '">';
                                $fieldHtml['html'].='<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                $fieldHtml['html'].='<p class="goods-sub-title c-black hide"></p>';
                                if ($fieldContent['price']) {
                                    $fieldHtml['html'].='<p class = "goods-price">';
                                    if($fx_store_info['is_point_mall'] == 1) {
                                        $fieldHtml['html'].='<em><span class="point_ico"></span>' . $price . '</em>';
                                    } else {
                                        $fieldHtml['html'].='<em>' . C('config.Currency_symbol') . $price . '</em>';
                                    }
                                    $fieldHtml['html'].='</p>';
                                }
                                $fieldHtml['html'].='<p class="goods-price-taobao hide"></p>';
                                $fieldHtml['html'].='</div>';
                                $fieldHtml['html'].='</a>';
                                if ($fieldContent['buy_btn'] && $fieldContent['size_type'] == 0) {
                                    $fieldHtml['html'].='<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                    $fieldHtml['html'].='<a href="javascript:void(0)" class="js-goods-cart-btn" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                }

                                $fieldHtml['html'].='</li>';
                                $i++;
                            }
                            $fieldHtml['html'].='</ul>';
                        } else if ($fieldContent['size'] == 1) {
                            if ($fieldContent['size_type'] != 1) {
                                $fieldHtml['html'].='<ul class="js-goods-list sc-goods-list pic clearfix size-1">';
                                $i = 1;
                                foreach ($fieldContent['goods'] as $key => $value) {
                                    $product = D('Shop_goods')->get_goods_by_id($value['id']);
                                    if (empty($product)) {
                                        continue;
                                    }
                                    if ($page_goods_source == 2) {
                                        // 商城
                                        $value['url'] = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$value['id'];
                                    } else {
                                        //快店
                                        $value['url'] = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id='.$value['id'];
                                    }

                                    //商品类型
                                    $type = 0;

                                    //商品限购
                                    $buyer_quota = !empty($product['buyer_quota']) ? $product['buyer_quota'] : 0;
                                    //价格
                                    if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                        $price = $product['drp_level_' . $drp_level . '_price'];
                                    } else {
                                        $price = $product['price'];
                                    }
                                    $fieldHtml['html'].='<li class="goods-card goods-list small-pic ' . ($fieldContent['size_type'] == 2 ? 'normal' : 'card') . '">';
                                    $fieldHtml['html'].='<a href="' . $value['url'] . '&store_id=' . $seller_id . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                    $fieldHtml['html'].='<div class="photo-block">';
                                    $fieldHtml['html'].='<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;"/>';
                                    $fieldHtml['html'].='</div>';
                                    $fieldHtml['html'].='<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . 'btn' . $fieldContent['buy_btn_type'] . ($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1 ? '' : ' hide') . '">';
                                    $fieldHtml['html'].='<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                    $fieldHtml['html'].='<p class="goods-sub-title c-black hide"></p>';
                                    if ($fieldContent['price']) {
                                        if($fx_store_info['is_point_mall'] == 1) {
                                            $fieldHtml['html'].='<p class="goods-price">';
                                            $fieldHtml['html'].='<em><span class="point_ico"></span>' . $price . '</em>';
                                            $fieldHtml['html'].='</p>';
                                        } else {
                                            $fieldHtml['html'].='<p class="goods-price">';
                                            $fieldHtml['html'].='<em>' . C('config.Currency_symbol') . $price . '</em>';
                                            $fieldHtml['html'].='</p>';
                                        }
                                    }
                                    $fieldHtml['html'].='<p class="goods-price-taobao hide"></p>';
                                    $fieldHtml['html'].='</div>';
                                    $fieldHtml['html'].='</a>';
                                    if ($fieldContent['buy_btn'] && $fieldContent['size_type'] == 0) {
                                        $fieldHtml['html'].='<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                        $fieldHtml['html'].='<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                    }
                                    $fieldHtml['html'].='</li>';
                                    $i++;
                                }
                                $fieldHtml['html'].='</ul>';
                            } else {
                                $fieldHtml['html'].='<ul class="js-goods-list sc-goods-list pic clearfix size-1 waterfall">';
                                $i = 1;
                                foreach ($fieldContent['goods'] as $key => $value) {
                                    $product = D('Shop_goods')->get_goods_by_id($value['id']);
                                    if (empty($product)) {
                                        continue;
                                    }
                                    if ($page_goods_source == 2) {
                                        // 商城
                                        $value['url'] = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$value['id'];
                                    } else {
                                        //快店
                                        $value['url'] = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id='.$value['id'];
                                    }

                                    //商品类型
                                    $type = 0;

                                    //商品限购
                                    $buyer_quota = !empty($product['buyer_quota']) ? $product['buyer_quota'] : 0;
                                    //价格
                                    if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                        $price = $product['drp_level_' . $drp_level . '_price'];
                                    } else {
                                        $price = $product['price'];
                                    }
                                    $fieldHtml['html'].='<li class="goods-card goods-list small-pic waterfall" style="width:155px;">';
                                    $fieldHtml['html'].='<a href="' . $value['url'] . '&store_id=' . $seller_id . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                    $fieldHtml['html'].='<div class="photo-block">';
                                    $fieldHtml['html'].='<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;">';
                                    $fieldHtml['html'].='</div>';
                                    $fieldHtml['html'].='<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . 'btn' . $fieldContent['buy_btn_type'] . ($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1 ? '' : ' hide') . '">';
                                    $fieldHtml['html'].='<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                    $fieldHtml['html'].='<p class="goods-sub-title c-black hide"></p>';
                                    if ($fieldContent['price']) {
                                        $fieldHtml['html'].='<p class="goods-price">';
                                        if($fx_store_info['is_point_mall'] == 1) {
                                            $fieldHtml['html'].='<em><span class="point_ico"></span>' . $price . '</em>';
                                        } else {
                                            $fieldHtml['html'].='<em>' . C('config.Currency_symbol') . $price . '</em>';
                                        }
                                        $fieldHtml['html'].='</p>';
                                    }

                                    $fieldHtml['html'].='<p class="goods-price-taobao hide"></p>';
                                    $fieldHtml['html'].='</div>';
                                    $fieldHtml['html'].='</a>';
                                    if ($fieldContent['buy_btn'] && ($fieldContent['size_type'] == 0 || $fieldContent['size_type'] == 1)) {
                                        $fieldHtml['html'].='<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                        $fieldHtml['html'].='<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                    }
                                    $fieldHtml['html'].='</li>';
                                    $i++;
                                }
                                $fieldHtml['html'].='</ul>';
                            }
                        } else if ($fieldContent['size'] == 2) {
                            $fieldHtml['html'].='<ul class="js-goods-list sc-goods-list pic clearfix size-2">';
                            $i = 1;
                            foreach ($fieldContent['goods'] as $key => $value) {
                                $product = D('Shop_goods')->get_goods_by_id($value['id']);
                                if (empty($product)) {
                                    continue;
                                }
                                if ($page_goods_source == 2) {
                                    // 商城
                                    $value['url'] = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$value['id'];
                                } else {
                                    //快店
                                    $value['url'] = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id='.$value['id'];
                                }
                                //商品类型
                                $type = 0;

                                //价格
                                $price = $product['price'];

                                $fieldHtml['html'].='<li class="goods-card goods-list ' . ($i % 3 == 1 ? 'big-pic' : 'small-pic') . ($fieldContent['size_type'] == 2 ? ' normal' : ' card') . '">';
                                $fieldHtml['html'].='<a href="' . $value['url'] . '&store_id=' . $seller_id . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                $fieldHtml['html'].='<div class="photo-block">';
                                $fieldHtml['html'].='<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;">';
                                $fieldHtml['html'].='</div>';
                                $fieldHtml['html'].='<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . '' . $fieldContent['buy_btn_type'] . (($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1) ? '' : ' hide') . '">';
                                $fieldHtml['html'].='<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                $fieldHtml['html'].='<p class="goods-sub-title c-black hide"></p>';
                                if ($fieldContent['price']) {
                                    $fieldHtml['html'].='<p class="goods-price">';
                                    $fieldHtml['html'].='<em>' . C('config.Currency_symbol') . $price . '</em>';
                                    $fieldHtml['html'].='</p>';
                                }

                                $fieldHtml['html'].='<p class="goods-price-taobao hide"></p>';
                                $fieldHtml['html'].='</div>';
                                $fieldHtml['html'].='</a>';
                                if ($fieldContent['buy_btn'] && $fieldContent['size_type'] == 0) {
                                    $fieldHtml['html'].='<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                    $fieldHtml['html'].='<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                }
                                $fieldHtml['html'].='</li>';
                                $i++;
                            }
                            $fieldHtml['html'].='</ul>';
                        } else if ($fieldContent['size'] == 3) {
                            $fieldHtml['html'].='<ul class="js-goods-list sc-goods-list clearfix list size-3">';
                            $i = 1;
                            foreach ($fieldContent['goods'] as $key => $value) {
                                $product = D('Shop_goods')->get_goods_by_id($value['id']);
                                if (empty($product)) {
                                    continue;
                                }
                                if ($page_goods_source == 2) {
                                    // 商城
                                    $value['url'] = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id='.$value['id'];
                                } else {
                                    //快店
                                    $value['url'] = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id='.$value['id'];
                                }

                                //商品类型
                                $type = 0;

                                //商品限购
                                $buyer_quota = !empty($product['buyer_quota']) ? $product['buyer_quota'] : 0;
                                //价格
                                if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                    $price = $product['drp_level_' . $drp_level . '_price'];
                                } else {
                                    $price = $product['price'];
                                }
                                $fieldHtml['html'].='<li class="goods-card goods-list ' . ($i % 3 == 1 ? 'big-pic' : 'small-pic') . ($fieldContent['size_type'] == 2 ? ' normal' : ' card') . '">';
                                $fieldHtml['html'].='<a href="' . $value['url'] . '&store_id=' . $seller_id . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                $fieldHtml['html'].='<div class="photo-block">';
                                $fieldHtml['html'].='<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:block;">';
                                $fieldHtml['html'].='</div>';
                                $fieldHtml['html'].='<div class="info">';
                                $fieldHtml['html'].='<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';

                                if ($fieldContent['price']) {
                                    $fieldHtml['html'].='<p class="goods-price">';
                                    if($fx_store_info['is_point_mall'] == 1) {
                                        $fieldHtml['html'].='<em><span class="point_ico"></span>' . $price . '</em>';
                                    } else {
                                        $fieldHtml['html'].='<em>' . C('config.Currency_symbol') . $price . '</em>';
                                    }
                                    $fieldHtml['html'].='</p>';
                                }

                                $fieldHtml['html'].='</div>';
                                $fieldHtml['html'].='</a>';
                                if ($fieldContent['buy_btn']) {
                                    $fieldHtml['html'].='<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                    $fieldHtml['html'].='<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                }
                                $fieldHtml['html'].='</li>';
                                $i++;
                            }
                            $fieldHtml['html'].='</ul>';
                        }
                    }
                    break;

                //头部
                case 'tpl_shop':
                    $rooturl = C('config.site_url');
                    $fieldHtml['html'] = '<!-- logo抬头 -->';
                    $bg1 = "style='";
                    if (!empty($fieldContent['bgcolor'])) {
                        $bg1 .= "background-color:" . $fieldContent['bgcolor'] . "; ";
                    }
                    if (!empty($fieldContent['shop_head_bg_img'])) {
                        if (preg_match("/^http/", $fieldContent['shop_head_bg_img'])) {
                            $bg1 .= " background-image:url(" . replace_file_domain($fieldContent['shop_head_bg_img']) . ");";
                        } else {
                            $bg1 .= " background-image:url(" . replace_file_domain($fieldContent['shop_head_bg_img']) . ");";
                        }
                    }
                    $bg1 .= "'";

                    $imgs = "";
                    if (!empty($fieldContent['shop_head_logo_img'])) {
                        if (preg_match("/^http/", $fieldContent['shop_head_logo_img'])) {
                            $imgs = $fieldContent['shop_head_logo_img'];
                        } else {
                            $imgs = $fieldContent['shop_head_logo_img'];
                        }
                    }

                    //$count = M('Product')->getTotalByStoreId($store_id);
                    //全部已经上架的商品

                    $where_goods = array(
                        'store_id' => $store_id,
                        'status'=>1
                    );
                    if ($page_goods_source==2) {
                        $where_goods['cat_fid'] = array('gt',0);
                    }

                    $count = M('Shop_goods')->where($where_goods)->count();

                    $imgs = array_shift(C('now_store.all_pic'));
                    $count = ($count > 9999) ? '9999+' : $count;
                    $logo = C('now_store.logo');

                    $fieldHtml['html'] .= '<div class="custom-title text-left"  style="padding:0px;" ><div class="tpl-shop">';
                    $fieldHtml['html'] .= '		<div class="tpl-shop-header" ' . $bg1 . '>';
                    $fieldHtml['html'] .= '		<div class="tpl-shop-title">' . C('now_store.name') . '</div>';
                    $fieldHtml['html'] .= '		<div class="tpl-shop-avatar"><img width="80" height="80" src="' . ($logo ? replace_file_domain($logo) : replace_file_domain($imgs)) . '" alt="'.C('now_store.name').'"/></div></div>';
                    $fieldHtml['html'] .= '	<div class="tpl-shop-content">';

                    if ($page_goods_source==1) {
                        $url = C('config.site_url') . '/wap.php?g=Wap&c=Shop&a=index#shop-'.$seller_id;
                        $fieldHtml['html'] .= '<ul class="clearfix"><li><a href="'.$url.'"><span class="count">' . $count . '</span> <span class="text">全部商品</span></a></li><li><a href="' . U('My_card/merchant_card',array('mer_id'=>C('now_store.mer_id'))) .'"><span class="js-shouchang"><span class="count mycard"></span></span> <span class="text">会员卡</span></a></li><li><a href="' . U('My/shop_order_list',array('store_id'=>C('now_store.store_id'))) . '"><span class="count user"></span> <span class="text">我的订单</span></a></li></ul>';
                    } else {
                        $fieldHtml['html'] .= '<ul class="clearfix"><li><a href="'.C('now_store.url').'&show_own=1"><span class="count">' . $count . '</span> <span class="text">全部商品</span></a></li><li><a href="' . U('My_card/merchant_card',array('mer_id'=>C('now_store.mer_id'))) .'"><span class="js-shouchang"><span class="count mycard"></span></span> <span class="text">会员卡</span></a></li><li><a href="' . U('Mall/mall_order_list',array('store_id'=>C('now_store.store_id'))) . '"><span class="count user"></span> <span class="text">我的订单</span></a></li></ul>';
                    }
                    $fieldHtml['html'] .= '</div></div></div><div class="component-border"></div>';

                    break;

                //头部
                case 'tpl_shop1':
                    $fieldHtml['html'] = '<!-- logo抬头 -->';
                    $bg1 = "style='";
                    if (!empty($fieldContent['bgcolor'])) {
                        $bg1 .= "background-color:" . $fieldContent['bgcolor'] . ";";
                    }
                    if (!empty($fieldContent['shop_head_bg_img'])) {
                        if (preg_match("/^http/", $fieldContent['shop_head_bg_img'])) {
                            $bg1 .= " background-image:url(" . replace_file_domain($fieldContent['shop_head_bg_img']) . ");";
                        } else {
                            $bg1 .= " background-image:url(" . replace_file_domain($fieldContent['shop_head_bg_img']) . ");";
                        }
                    }


                    $bg1 .= "'";
                    $imgs = "";
                    if (!empty($fieldContent['shop_head_logo_img'])) {
                        if (preg_match("/^http/", $fieldContent['shop_head_logo_img'])) {
                            $imgs = $fieldContent['shop_head_logo_img'];
                        } else {
                            $imgs = $fieldContent['shop_head_logo_img'];
                        }
                    }

                    if (empty($drp_diy_store) && !empty($seller_id)) {
                        $stores = D('Merchant_store')->where(array('store_id' => $seller_id))->field("logo,name")->find();
                    } else {
                        $stores = D('Merchant_store')->where(array('store_id' => $store_id))->field("logo,name")->find();
                    }
                    $atten = $stores['atten_num'];
                    $imgs = array_shift(C('now_store.all_pic'));
                    $logo = C('now_store.logo');

                    if ($drp_level) {
                        $title = (!empty($stores['name']) ? $stores['name'] : $fieldContent['title']);
                    } else {
                        $title = !empty($fieldContent['title']) ? $fieldContent['title'] : $stores['name'];
                    }
                    if($store_id != $seller_id) {
                        $title = $stores['name'];
                    }

                    $fieldHtml['html'] .= '<div style="padding:0px;" class="custom-title text-left">';
                    $fieldHtml['html'] .= '<div class="tpl-shop1 tpl-wxd">';
                    $fieldHtml['html'] .= '<div class="tpl-wxd-header" ' . $bg1 . '>';
                    $fieldHtml['html'] .= '<div class="tpl-wxd-title">' . $title . '</div><div class="tpl-wxd-avatar">';
                    $fieldHtml['html'] .= '<img src="' . ($logo ? replace_file_domain($logo) : replace_file_domain($imgs)) . '" alt=""></div> </div></div>';
                    $fieldHtml['html'] .= '</div>';

                    break;

                //专题栏目导航
                case 'subject_menu':
                    $fieldHtml['html'] = '<!-- 专题导航抬头 -->';

                    /*                 	$fieldHtml['html'] .= '<div class="top_sidebars">';
                                        $fieldHtml['html'] .= '<menu class=""> <span class=""><i></i></span>';
                                        $fieldHtml['html'] .= '		<div class="menu" style="overflow: hidden; display: none;">';
                                        $fieldHtml['html'] .= '			<div class="menu_titel"><span>切换频道</span><span>排序或删除</span></div>';
                                        $fieldHtml['html'] .= '			<ul><li>1精选 </li><li> 礼物 </li><li> 美食 </li><li> 家居 </li><li> 运动 </li><li> 精选 </li><li> 精选 </li><li> 礼物 </li><li> 美食 </li><li>家居 </li><li> 运动 </li><li> 精选 </li></ul>';
                                        $fieldHtml['html'] .= '		</div>';
                                        $fieldHtml['html'] .= '		<div class="mui-slider-indicator mui-segmented-control mui-segmented-control-inverted menu_list" id="sliderSegmentedControl">';
                                        $fieldHtml['html'] .= '			<ul class="clearfix" style="width: 630px;">';
                                        $fieldHtml['html'] .= '				<li class=" "><a href="#item1mobile" class="mui-control-item mui-active">2精选</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item2mobile" class="mui-control-item"> 礼物</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item3mobile" class="mui-control-item"> 美食</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item4mobile" class="mui-control-item"> 家居</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item5mobile" class="mui-control-item"> 运动</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item6mobile" class="mui-control-item"> 精选</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item7mobile" class="mui-control-item"> 精选</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item8mobile" class="mui-control-item"> 礼物</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item9mobile" class="mui-control-item"> 美食</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item10mobile" class="mui-control-item">家居</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item11mobile" class="mui-control-item"> 运动</a> </li>';
                                        $fieldHtml['html'] .= '				<li><a href="#item12mobile" class="mui-control-item"> 精选</a> </li>';
                                        $fieldHtml['html'] .= '			</ul>';
                                        $fieldHtml['html'] .= '		</div>';
                                        $fieldHtml['html'] .= '		<div class="mui-slider-progress-bar mui-col-xs-4" id="sliderProgressBar"></div>';
                                        $fieldHtml['html'] .= '	</menu>';
                                        $fieldHtml['html'] .= '	</div>'; */

                    //专题读取数据库
                    $where = array('status'=>1,'topid'=>array('>',0));
                    $subtype = M('Subtype')->getLists($where,false,'px asc');

                    foreach($subtype as $k => $v) {
                        $subtype_info[$v['id']]=$v['typename'];
                    }


                    $fieldHtml['html'] .= '<div class="top_sidebars">';
                    $fieldHtml['html'] .= '<menu class=""> <span class="span_iss_old"><i></i></span>';
                    $fieldHtml['html'] .= '		<div class="menu" style="overflow: hidden; display: none;">';
                    $fieldHtml['html'] .= '			<div class="menu_titel"><span>切换频道</span><span style="display:none">排序或删除</span><span class="iss_span" style="text-align:right"><i class="iss"></i></span></div>';
                    $fieldHtml['html'] .= '			<ul><li>1精选 </li><li> 礼物 </li><li> 美食 </li><li> 家居 </li><li> 运动 </li><li> 精选 </li><li> 精选 </li><li> 礼物 </li><li> 美食 </li><li>家居 </li><li> 运动 </li><li> 精选 </li></ul>';
                    $fieldHtml['html'] .= '		</div>';
                    $fieldHtml['html'] .= '		<div class="mui-slider-indicator mui-segmented-control mui-segmented-control-inverted menu_list" id="sliderSegmentedControl">';
                    $fieldHtml['html'] .= '			<ul class="clearfix" style1="width: 630px;">';
                    $store_url = option('config.wap_site_url').'/home.php?id='.$seller_id;
                    $fieldHtml['html'] .= '				<li class=" "><a href="'.$store_url.'" class="mui-control-item mui-active">精选</a> </li>';

                    $array['subtype_list'] = !empty($fieldContent['subtype_list']) ? $fieldContent['subtype_list'] : array();
                    foreach ($array['subtype_list'] as $key => $subtype2) {
                        if($subtype_info[$subtype2['id']]) {
                            $db_subtype_list2[$key] = $subtype;
                            $db_subtype_list2[$key]['title'] = $subtype_info[$subtype2['id']];
                        } else {
                            continue;
                        }
                        $subtype_name = $subtype_info[$subtype2['id']];
                        if($subtype_name) $subtype_name = msubstr($subtype_name,0,4,false,"utf-8");
                        $fieldHtml['html'] .= '			<li><a hrefs="#item'.$key.'mobile" href="' . option('config.site_url') . '/wap/subtype.php?id='.$seller_id."&sid=".$subtype2[id].'" class="mui-control-item">'.$subtype_name.'</a> </li>';

                    }
                    //$fieldHtml['html'] .= '				<li class=" "><a href="#item1mobile" class="mui-control-item">2精选</a> </li>';


                    $fieldHtml['html'] .= '			</ul>';
                    $fieldHtml['html'] .= '		</div>';
                    $fieldHtml['html'] .= '		<div class="mui-slider-progress-bar mui-col-xs-4" id="sliderProgressBar"></div>';
                    $fieldHtml['html'] .= '	</menu>';
                    $fieldHtml['html'] .= '	</div>';

                    break;

                //29日可以使用的 ，无修改(beifen)
                case '~~~subject_display':
                    $fieldHtml['html'] = '<!-- 专题展示抬头 -->';

                    $fieldContent['px_style'];
                    $update_hour = $fieldContent['hour'] ? ( $fieldContent['hour'].':00'):'0:00';
                    $fieldContent['day'];
                    $fieldContent['number'];




                    $weekarray=array(L_("日"),L_("一"),L_("二"),L_("三"),L_("四"),L_("五"),L_("六"));
                    //echo "星期".$weekarray[date("w")];

                    if ($fieldContent['day'] === 3) {// 三天
                        $time = strtotime('-2 day', $now);
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    } elseif ($fieldContent['day'] === 5) {// 5日
                        $time = strtotime('-4 day', $now);
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    } elseif ($fieldContent['day'] === 7) {// 一周
                        $time = strtotime('-6 day', $now);
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    } elseif ($fieldContent['day'] === 15) {// 半个月
                        $time = strtotime('-14 day', $now);
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    }

                    $endtime = strtotime($endtime);
                    $subject_info = D('Subject')-> where("   store_id = '".$store_id."' and timestamp > '".$endtime."'")->order("timestamp desc")->select();

                    foreach($subject_info as $k=>$v) {

                        $date = date("Y_m_d",$v['timestamp']);
                        $new[$date][] = $v;
                    }
                    $new = array_values($new);


                    $fieldHtml['html'] .= '<article>';
                    $fieldHtml['html'] .= '		<section>';
                    $fieldHtml['html'] .= '			<ul class="show_list">';

                    foreach ($new as $k=>$v) {

                        $fieldHtml['html'] .= '			<li>';
                        foreach($v as $k1=>$v1) {
                            if($k1=='0')	$fieldHtml['html'] .= '		<div class="show_title clearfix"> <span>'.date("m月d日",$v1['timestamp']).'&nbsp;星期'.$weekarray[date("w",$v1['timestamp'])].'</span><i><em></em>下次更新'.$update_hour.'</i> </div>';
                        }
                        $fieldHtml['html'] .= '				<ul class="product_show">';

                        foreach($v as $k1=>$v1) {
                            if($v1['pic']) {
                                $image = getAttachmentUrl($v1['pic']);
                            }
                            $fieldHtml['html'] .= '					<li style="background:url('.$image.') center no-repeat;"> <a href="product_info.html"><i class="active"><em ></em>5165</i>';
                            $fieldHtml['html'] .= '						<p style="width:100%;text-align:left;line-height:18px;height:18px;font-size:14px;">'.$v1['name'].'</p>';
                            $fieldHtml['html'] .= '						</a> ';
                            $fieldHtml['html'] .= '					</li>';
                        }
                        $fieldHtml['html'] .= '				</ul>';
                        $fieldHtml['html'] .= '			</li>';
                    }

                    $fieldHtml['html'] .= '</ul>';
                    $fieldHtml['html'] .= '</section>';
                    $fieldHtml['html'] .= '</article>';

                    break;

                case 'subject_display':

                    $fieldHtml['html'] = '<!-- 专题展示抬头 -->';
                    $fieldHtml['html'] .= '<div  class="subject_display_div">';
                    $update_hour = $fieldContent['hour'] ? ( $fieldContent['hour']):'0';
                    $now_time = time();$now = time();
                    $now_hour = date("H");

                    if($now_hour > $update_hour) {
                        if($fieldContent['hour']) {
                            $next_update_time = ($now_hour - $update_hour)%$fieldContent['update_hour']+$now_hour;
                        } else {
                            $next_update_time = $now_hour + $fieldContent['update_hour'];
                            $next_update_time = ($next_update_time >= 24) ? ($next_update_time - 24) : $next_update_time;
                        }
                    } elseif ($now_hour == $update_hour) {
                        $next_update_time = $update_hour+$fieldContent['update_hour'];
                    }else {
                        $next_update_time = $update_hour;
                    }

                    $update_hour = $next_update_time? ( $next_update_time.':00'):'0:00';
                    $weekarray=array(L_("日"),L_("一"),L_("二"),L_("三"),L_("四"),L_("五"),L_("六"));
                    //$fieldContent['day'] = 1;
                    if ($fieldContent['day'] == 3) {// 三天
                        $time = strtotime('-2 day');
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59');
                    } elseif ($fieldContent['day'] == 5) {// 5日
                        $time = strtotime('-4 day');
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    } elseif ($fieldContent['day'] == 7) {// 一周
                        $time = strtotime('-6 day');
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    } elseif ($fieldContent['day'] == 15) {// 半个月
                        $time = strtotime('-14 day');
                        $beginTime = date('Y-m-d 00:00:00', $time);
                        $endTime = date('Y-m-d 23:59:59', $now);
                    } elseif ($fieldContent['day'] == 1) {// 一天

                        $beginTime = date('Y-m-d 00:00:00',time());
                        $endTime = date('Y-m-d', $now);
                        $time = $endTime.' '."23:59:59";
                        $time = strtotime($time);
                    }

                    ///echo $fieldContent['day'];
                    $endtime = $time;
                    if($fieldContent['day_type'] == '2') {
                        //第一天数据
                        $subject_before_1 = D('Subject')-> where("show_index = 1 and   store_id = '".$store_id."'")->order("timestamp desc")->limit(1)->find();

                        $now_date = date("Y-m-d",$subject_before_1['timestamp']);
                        $now_date_starttime = strtotime($now_date);
                        $now_date_endtime = strtotime($now_date)+60*60*24;	//当天最晚时间

                        $subject_before = D('Subject')-> where("show_index = 1 and   store_id = '".$store_id."' and timestamp < '".$now_date_endtime."' and timestamp >= '".$now_date_starttime."'")->order("timestamp desc")->limit($fieldContent['number'])->select();
                        //第二天起始数据
                        $subject_before_2 =  D('Subject')-> where("show_index = 1 and   store_id = '".$store_id."' and  timestamp <= '".$now_date_starttime."'")->order("timestamp desc")->limit(1)->find();

                        for($i=0;$i<$fieldContent['day'];$i++) {

                            if($subject_before_2['timestamp']) {
                                if(!$subject_before_2['timestamp']) {
                                    //break;
                                }
                                //循环的第N 天数据
                                $now_date = date("Y-m-d",$subject_before_2['timestamp']);
                                $now_date_starttime = strtotime($now_date);
                                $now_date_endtime = strtotime($now_date)+60*60*24;	//第N天最晚时间

                                //第N 天数据
                                $subject_before2 = D('Subject')-> where("show_index = 1 and   store_id = '".$store_id."' and timestamp <= '".$now_date_endtime."' and timestamp >= '".$now_date_starttime."'")->order("timestamp desc")->limit($fieldContent['number'])->select();

                                //第N+1天 前 第一条数据
                                $subject_before_2 =  D('Subject')-> where("show_index = 1 and   store_id = '".$store_id."' and  timestamp <= '".$now_date_starttime."'")->order("timestamp desc")->limit(1)->find();

                                if(is_array($subject_before2)) {
                                    $subject_before = array_merge($subject_before,$subject_before2);
                                }
                            }
                            //unset($subject_before_2);
                        }

                        //去重复数据
                        if(is_array($subject_before)) {
                            foreach($subject_before as $k=>$v) {
                                $subject_info[$v['id']] = $v;
                            }
                        }
                        if(is_array($subject_info)) {
                            $subject_info = array_values($subject_info);
                        } else {
                            $subject_info['$subject_before'];
                        }
                    } else {
                        $subject_info = D('Subject')-> where("show_index = 1 and   store_id = '".$store_id."' and timestamp > '".$endtime."'")->order("timestamp desc")->select();
                    }

                    foreach($subject_info as $k=>$v) {
                        $subjects_infos = $v;
                        $date = date("Y_m_d",$v['timestamp']);
                        if($v['pic']) {
                            $subject_info[$k]['pic'] = getAttachmentUrl($v['pic']);
                            $v['pic'] = getAttachmentUrl($v['pic']);
                        }
                        $subject_info[$k]['dates'] = date("m_d",$v['timestamp']);
                        $url = option('config.wap_site_url') . '/subinfo.php?store_id='.$seller_id."&subject_id=".$v['id'];

                        $subject_info[$k]['dates_chinese'] = date("m月d日",$v['timestamp']);
                        $subject_info[$k]['dates_xingqi'] = L_("星期").$weekarray[date("w",$v['timestamp'])];
                        $subject_info[$k]['dates_update_click'] = $update_hour;
                        $subject_info[$k]['url'] =  $url;

                        $aleady_dianzan_info = D('User_collect')->where(array('store_id'=>$seller_id,'type'=>'3','dataid'=>$v[id]))->find();
                        if($aleady_dianzan_info) {
                            $subject_info[$k]['dianzan_tip'] =  1;
                            $v['dianzan_tip'] = 1;
                        } else {
                            $subject_info[$k]['dianzan_tip'] =  0;
                            $v['dianzan_tip'] = 0;
                        }

                        //获取该店铺 该专题点赞总数
                        $store_dz = D('Store_subject_data')->where(array('store_id'=>$seller_id,'subject_id'=> $v[id]))->field("subject_id,dz_count")->find();
                        if($store_dz['dz_count']) {
                            $subject_info[$k]['dz_count'] =  $store_dz['dz_count'];
                            $v['dz_count'] = $store_dz['dz_count'];
                        } else {
                            $subject_info[$k]['dz_count'] =  0;
                            $v['dz_count'] = 0;
                        }
                        $v['url'] = $url;
                        $v['date'] = $subject_info[$k]['dates'];
                        if(count($new_date[date("m-d",$v['timestamp'])]) >= $fieldContent['number']) {
                            continue;
                        }
                        $new[$date]['subject'][] = $v;
                        $new_date[date("m-d",$v['timestamp'])][] = $v;
                        $subject_infos[] = $subject_info[$k];
                    }
                    if(is_array($new)) {
                        $new = array_values($new);
                        //存储节点
                        $fieldHtml['html'] .= '<article>';
                        $fieldHtml['html'] .= '		<section>';
                        $fieldHtml['html'] .= '			<ul class="show_list">';
                        $j=0;
                        $s=0;
                        foreach ($new as $k=>$v) {
                            if($j>=3) break;

                            foreach($v['subject'] as $k1=>$v1) {
                                if($k1=='0') {

                                    $fieldHtml['html'] .= '	<li class="'.$v1['date'].'">';
                                    if($s == '0') {$next_objc = '<i><em></em>'.L_('下次更新').$update_hour.'</i>';} else{$next_objc="";}
                                    $fieldHtml['html'] .= '		<div class="show_title clearfix"> <span>'.date("m月d日",$v1['timestamp']).'&nbsp;星期'.$weekarray[date("w",$v1['timestamp'])].'</span>'.$next_objc.' </div>';
                                }
                                $s++;
                            }
                            $fieldHtml['html'] .= '				<ul class="product_show">';
                            //	$new[$k]['dates'] = date("m_d",$v1['timestamp']);
                            foreach($v['subject'] as $k1=>$v1) {

                                $url = option('config.wap_site_url') . '/subinfo.php?store_id='.$seller_id."&subject_id=".$v1['id'];

                                $fieldHtml['html'] .= '					<li class="subject_'.$v1[id].'" style2="background:url('.$v1['pic'].') center no-repeat;"> <a style="display:block;width:100%;height:100%;text-align:center" hrefs="javascript:void(0)" href="'.$url.'">';

                                $fieldHtml['html'] .= '						<img src="'.$v1[pic].'">';

                                if($v1['dianzan_tip']) {
                                    $fieldHtml['html'] .= '<i data-subjectid="'.$v1[id].'"  class="active dianzan_selected"><em ></em><span class="dz_count">'.$v1[dz_count].'</span></i>';
                                } else {
                                    $fieldHtml['html'] .= '<i data-subjectid="'.$v1[id].'"  class="active"><em ></em><span class="dz_count">'.$v1[dz_count].'</span></i>';
                                }

                                $fieldHtml['html'] .= '						<p style="width:100%;text-align:left;line-height:18px;height:18px;font-size:14px;">'.$v1['name'].'</p>';
                                $fieldHtml['html'] .= '						</a> ';
                                $fieldHtml['html'] .= '					</li>';
                                $j++;
                            }
                            $fieldHtml['html'] .= '				</ul>';
                            foreach($v['subject'] as $k1=>$v1) {
                                if($k1=='0') {
                                    $fieldHtml['html'] .= '			</li>';
                                }
                            }
                        }
                        $fieldHtml['html'] .= '</ul>';
                        $fieldHtml['html'] .= '</section>';
                        $fieldHtml['html'] .= '</article>';
                        $fieldHtml['html'] .= '<div class="subject_display_datas" style="height:5px;clear:both;display:none;" data-infos=\''.json_encode($subject_infos).'\'></div>';

                    }
                    $fieldHtml['html'] .= '</div><div style="clear:both"></div>';

                    break;

                case 'coupons':
                    $now_store = D('Merchant_store')->where(array('store_id'=>$store_id))->find();
                    if (!$_SESSION['sync_user']) { //非对接用户可看：优惠券
                        $fieldHtml['html'] = '<!-- 优惠券 --><div style="clear:both"></div><style type="text/css"> .custom-coupon{padding:10px;text-align:center;font-size:0}.custom-coupon li{display:inline-block;margin-left:6px;width:94px;height:67px;border:1px solid #ff93b2;border-radius:4px;background:#ffeaec;float:left;}.custom-coupon li a{color:#fa5262}.custom-coupon li:nth-child(1){margin-left:0}.custom-coupon li:nth-child(4){margin-left:0}.custom-coupon li:nth-child(7){margin-left:0}.custom-coupon li:nth-child(2){background:#f3ffef;border-color:#98e27f}.custom-coupon li:nth-child(2) a{color:#7acf8d}.custom-coupon li:nth-child(3){background:#ffeae3;border-color:#ffa492}.custom-coupon li:nth-child(3) a{color:#ff9664}.custom-coupon .custom-coupon-price{height:24px;line-height:24px;padding-top:12px;font-size:24px;overflow:hidden}.custom-coupon .custom-coupon-price span{font-size:16px}.custom-coupon .custom-coupon-desc{height:20px;line-height:20px;font-size:12px;padding-top:4px;overflow:hidden}</style>';
                        $fieldHtml['html'] .= '<div class="app-preview"><ul class="custom-coupon clearfix">';
                        foreach ($fieldContent as $v) {
                            foreach ($v as $val) {
                                if ($now_store['mer_id']==C('config.kefu_mer_id')&&C('config.open_kefu_url')) {//自营店铺
                                    $coupons = D('System_coupon')->get_coupon($val['id']);
                                }else{
                                    $coupons = D('Card_new_coupon')->get_coupon($val['id']);
                                }
                                if ($coupons) {
                                    $fieldHtml['html'] .= '<li style="margin-top:5px; width:31%;">  <a href="' . U('My_card/merchant_coupon_list',array('mer_id'=>C('now_store.mer_id'))) . '"><div class="custom-coupon-price"><span>' . C('config.Currency_symbol') . '</span>' . $coupons['discount'] . '</div><div class="custom-coupon-desc">' . $coupons['name'] . '</div>  </a> </li>';
                                }
                            }
                        }
                        $fieldHtml['html'] .= '</ul></div><div style="clear:both"></div>';
                    }
                    break;

                case 'activity_module_old':
                case 'new_activity_module_old':

                    $title = $fieldContent['name'] ? $fieldContent['name'] : L_("今日活动");
                    $fieldHtml['html'] = '<!--活动--><div class="activity_module-nav"><div class="custom-nav"><a href="'.U('Wxapp/index',array('mer_id'=>C('now_store.mer_id'))).'" class="arrow-right"><span class="custom-nav-title">'.$title.'</span></a></div></div><div class="scrollBox" data-display_mode="'.intval($fieldContent['display']).'"><div id="scrollThis" class="scrollThis_' . rand(100000, 999999) . '"><div class="scroller"><ul>';
                    foreach ($fieldContent['activity_arr'] as $v) {
                        if (!empty($v['id'])) {
                            $now_wxapp = M('Wxapp_list')->where(array('pigcms_id'=>$v['id']))->find();

                            $images = $now_wxapp['image'];

                            $fieldHtml['html'] .= '<li><a href="'.C('config.site_url').'/wap.php?c=Wxapp&a=location_href&id='.$now_wxapp['pigcms_id'].'"><img src="'.$images.'"/><h3>'.msubstr($now_wxapp['title'],0,8).'</h3></a></li>';
                        }
                    }

                    $fieldHtml['html'] .= '</ul></div></div></div>';
                    break;

                case 'new_activity_module':

                    $title = $fieldContent['name'] ? $fieldContent['name'] : L_("今日活动");
                    $bargain_db = M('Mall_bargain_act');
                    $group_db = D('Mall_group_act');
                    $shop_goods_db = M('Shop_goods');
                    $now_time = time();
                    $activities = $fieldContent['activity_arr'] ? $fieldContent['activity_arr'] : '';

                    if ($activities) {

                        foreach ($activities as $k => &$v) {
                            if (!empty($v['id'])) {
                                $activity_msg = explode('_', $v['id']);
                                switch ($activity_msg[0]) {
                                    case 'seckill':
                                        // 获取对应商品信息
                                        $shop_goods = $shop_goods_db->where(array('goods_id' => $activity_msg[1]))->find();
                                        fdump($shop_goods,'$shop_goods');
                                        // 活动对应商品不存在 去除
                                        if (empty($shop_goods)) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 隐藏已经结束活动
                                        if ($shop_goods['seckill_close_time'] < $now_time) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 隐藏已经售罄活动
//                                        if ($shop_goods['seckill_stock'] != -1 && $shop_goods['seckill_stock'] <= 0) {
//                                            unset($activities[$k]);
//                                            continue;
//                                        }
                                        $v['link_url']  = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$shop_goods['goods_id']}";
                                        break;
                                    case 'bargain':
                                        $mall_bargain_act = $bargain_db->where(array('id' => $activity_msg[1]))->find();
                                        // 活动不存在 去除
                                        if (empty($mall_bargain_act)) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 如果隐藏已经结束活动
                                        if ($mall_bargain_act['ftime'] < $now_time) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 获取对应商品信息
                                        $shop_goods = $shop_goods_db->where(array('goods_id' => $mall_bargain_act['goods_id']))->find();
                                        // 活动对应商品不存在 去除
                                        if (empty($shop_goods)) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 如果隐藏已经售罄活动
//                                        if ($shop_goods['stock_num'] != -1 && $shop_goods['stock_num'] <= 0) {
//                                            unset($activities[$k]);
//                                            continue;
//                                        }
                                        $v['link_url']  = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$shop_goods['goods_id']}&bar_id={$mall_bargain_act['id']}";
                                        break;
                                    case 'tuan':
                                        $mall_group_act = $group_db->get_group_by_id($activity_msg[1]);
                                        // 活动不存在 去除
                                        if (empty($mall_group_act)) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 如果隐藏已经结束活动
                                        if ($mall_group_act['ftime'] < $now_time) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 获取对应商品信息
                                        $shop_goods = $shop_goods_db->where(array('goods_id' => $mall_group_act['good_id']))->find();
                                        // 活动对应商品不存在 去除
                                        if (empty($shop_goods)) {
                                            unset($activities[$k]);
                                            continue;
                                        }
                                        // 如果隐藏已经售罄活动
//                                        if ($shop_goods['stock_num'] != -1 && $shop_goods['stock_num'] <= 0) {
//                                            unset($activities[$k]);
//                                            continue;
//                                        }
                                        $v['link_url'] = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$shop_goods['goods_id']}&group_id={$mall_group_act['id']}";
                                        break;

                                }
                            }
                        }
                    }


                    if ($activities) {
                        $fieldHtml['html'] = '<!--活动--><div class="activity_module-nav"><div class="custom-nav"><a href="javascript:;"><span class="custom-nav-title">' . $title . '</span></a></div></div>';

                        if ($fieldContent['display'] != 2) {
                            $fieldHtml['html'] .= '<div class="scrollBox" data-display_mode="' . intval($fieldContent['display']) . '"><div id="scrollThis" class="scrollThis_' . rand(100000, 999999) . '"><div class="scroller"><ul>';

                            fdump($activities,'$activities');
                            foreach ($activities as $val) {
                                if ($val['id']) {
                                    $fieldHtml['html'] .= '<li><a href="' . $val['link_url'] . '"><img src="' . $val['image'] . '"/><h3>' . msubstr($val['title'], 0, 8) . '</h3></a></li>';
                                }
                            }
                        } elseif ($fieldContent['display'] == 2){
                            $fieldHtml['html'] .= '<div class="new_acitivty_box"><div class="new_acitivty_msg"><div class="msg"><ul class="clearfix">';
                            foreach ($activities as $val) {
                                if ($val['id']) {
                                    $fieldHtml['html'] .= '<li><a href="' . $val['link_url'] . '"><img src="' . $val['image'] . '"/><h3>' . msubstr($val['title'], 0, 8) . '</h3></a></li>';
                                }
                            }
                        }

                        $fieldHtml['html'] .= '</ul></div></div></div>';
                    }
                    break;

                case 'goods_group1':
                    $fieldHtml['html'] = '<!-- 商品分组1 --><div style="clear:both"></div>';
                    $fieldHtml['html'] .='<div class="app-field clearfix editing"><div class="control-group"><ul class="goods_group1 clearfix"><div class="custom-tag-list clearfix"><div class="custom-tag-list-menu-block js-collection-region" style="min-height: 323px;"><ul class="custom-tag-list-side-menu"><li>';
                    $fieldHtml['html'].='<a href="javascript:;" class="current" style="display:none">全部</a>';
                    $time = time().uniqid();
                    $shop_goods_group_list = array();
                    foreach ($fieldContent['goods_group1'] as $k => $v) {
                        if (!isset($shop_goods_group_list[$v['id']])) {
                            $shop_goods_group = D('Shop_goods_group')->where(array('id' => $v['id']))->find();
                            if (empty($shop_goods_group)) {
                                continue;
                            }
                            $shop_goods_group_list[$v['id']] = $shop_goods_group;
                        }

                        //$fieldHtml['html'] .='<a href="javascript:;" class="current"';
                        $fieldHtml['html'] .='<a href="#'.$time.'_'.$v['id'].'" class="current"';
                        if ($k == 0) {
                            $fieldHtml['html'].='style="background:#fff" ';
                        }
                        $fieldHtml['html'].='>' . $shop_goods_group_list[$v['id']]['name'] . '</a>';
                    }


                    $fieldHtml['html'] .= '</li></ul></div>';

//                    //全部start
                    $fieldHtml['html'].='<div class="custom-tag-list-goods" style="display: block;"><ul class="custom-tag-list-goods-list">';
                    foreach ($fieldContent['goods_group1'] as $k => $v) {
                        if (!isset($shop_goods_group_list[$v['id']])) {
                            continue;
                        }
                        $fieldHtml['html'] .= '<p class="custom-tag-list-title" id="'.$time.'_'.$v['id'].'">' . $shop_goods_group_list[$v['id']]['name'] . '</p>';

                        $limit = 10;
                        if ($v['show_num']) {
                            $limit = $v['show_num'];
                        }

                        $goods_list = M('Shop_goods_group_list')->field('l.id,l.goods_id,g.name,g.stock_num,g.spec_value,g.price, g.image')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->order('l.goods_id')->where(['l.group_id' => $v['id'],'g.status'=>1])->limit($limit)->select();



                        if ($goods_list) {
                            $goods_image_class = new goods_image();
                            foreach ($goods_list as $val) {
                                if(!empty($val['image'])){
                                    $tmp_pic_arr = explode(';', $val['image']);
                                    foreach ($tmp_pic_arr as $key => $value) {
                                        $val['pic_arr'][$key]['title'] = $value;
                                        if('http' === substr($value,0,4)){
                                            $val['pic_arr'][$key]['url'] = $value;
                                        }else{
                                            $val['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 'm');
                                        }
                                    }
                                    $val['image'] = $val['pic_arr'][0]['url'];
                                }else if(C('config.goods_default_image')){
                                    $val['image'] = C('config.goods_default_image');
                                }

                                $price = $val['price'];

                                $price = C('config.Currency_symbol') . $price ;

                                if ($page_goods_source == 2) {
                                    // 商城
                                    $url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $val["goods_id"];
                                } else {
                                    //快店
                                    $url = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id=' . $val["goods_id"];
                                }

                                if ($val) {
                                    $fieldHtml['html'] .= '<li class = "custom-tag-list-single-goods clearfix"><a href = "' . $url . '"><div class = "custom-tag-list-goods-img"><img src = "' . $val['image'] . '" style = "display: inline;"></div><div class = "custom-tag-list-goods-detail"><p class = "custom-tag-list-goods-title">' . $val['name'] . ' </p><span class = "custom-tag-list-goods-price">' . $price . '</span></a></div></li>';
                                }
                            }
                        } else {
                            $fieldHtml['html'].='<li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="' . $site_url . '/tpl/Wap/pure/static/diypage/images/kd5.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">' . C('config.Currency_symbol') . '100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="' . $site_url . '/tpl/Wap/pure/static/diypage/images/kd1.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">' . C('config.Currency_symbol') . '100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="' . $site_url . '/tpl/Wap/pure/static/diypage/images/kd3.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">' . C('config.Currency_symbol') . '100.00</span></div></li><li class="custom-tag-list-single-goods clearfix"><div class="custom-tag-list-goods-img"><img src="' . $site_url . '/tpl/Wap/pure/static/diypage/images/kd4.jpg" style="display: inline;"></div><div class="custom-tag-list-goods-detail"><p class="custom-tag-list-goods-title">此处显示商品名称</p><span class="custom-tag-list-goods-price">' . C('config.Currency_symbol') . '100.00</span></div></li>';
                        }
                    }
                    $fieldHtml['html'] .= '</ul></div></div>';
//                    //全部end
                    $fieldHtml['html'].='<div style="clear:both"></div>';
                    break;

                case 'goods_group2':
                    $fieldHtml['html'] = '<!-- 商品分组2 --><style> a.active {color: #ed5050;border-bottom: 1px solid #ed5050;}</style><div style="clear:both"></div>';

//                  $group_id_arr = array();
                    foreach ($fieldContent['goods'] as $k => $v) {
                        $group_id_arr[] = $v['id'];
                    }
                    $goods_list = M('Shop_goods_group_list')->field('l.id,l.group_id,l.goods_id,g.name,g.price, g.image')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->where(array('l.group_id' => array('in', $group_id_arr),'g.status'=>1))->select();

                    if ($fieldContent['goods'] && !$fieldContent['goods_group2']) {
                        $fieldContent['goods_group2'] = $fieldContent['goods'];
                    }
                    if ($goods_list) {
                        $goods_arr = array();
                        foreach ($goods_list as $val) {
                            if (!$goods_arr[$val['group_id']]) {
                                $goods_arr[$val['group_id']] = array();
                            }
                            $goods_arr[$val['group_id']][] = $val;
                        }
                        $fieldContent['goods'] = $goods_arr;
                    } else {
                        $fieldContent['goods'] = array();
                    }



                    if (!empty($fieldContent['goods'])) {
                        if ($page_goods_source == 2) {
                            // 商城
                            $url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=';
                        } else {
                            //快店
                            $url = $site_url . '/wap.php?c=Shop&a=classic_good&shop_id='.$seller_id.'&good_id=';
                        }
                        $fieldHtml['html'].='<div style="clear:both"></div><div class="js-tabber-tags tabber tabber-bottom red clearfix tabber-n4 ">';
                        $product_group_list = array();
                        $ii = 0;
                        $time = time().uniqid();
                        // 重新排序 按照字段从小到大排序
                        $ids = array_column($fieldContent['goods_group2'],'id');
                        array_multisort($ids,SORT_ASC,$fieldContent['goods_group2']);

                        foreach ($fieldContent['goods_group2'] as $k => $v) {
                            if (!isset($product_group_list[$v['id']])) {
                                $product_group = D('Shop_goods_group')->where(array('id' => $v['id']))->find();
                                if (empty($product_group)) {
                                    continue;
                                }
                                $product_group_list[$v['id']] = $product_group;
                            }
                            if ($ii == 0) {
                                $fieldHtml['html'].='<a data-type="tag-1" href="#'.$time.'_'.$v['id'].'" class="active">' . $product_group['name'] . '</a>';
                            } else {
                                $fieldHtml['html'].='<a data-type="tag-1" href="#'.$time.'_'.$v['id'].'">' . $product_group['name'] . '</a>';
                            }
                            $ii++;
                        }

                        $fieldHtml['html'].='</div><div class="js-product_group_2">';
                        foreach ($fieldContent['goods'] as $g_k => $g_v) {
                            $fieldHtml['html'] .= '<p class="custom-tag-list-title" id="'.$time.'_'.$g_k.'">' . $product_group_list[$g_k]['name'] . '</p>';
                            if ($fieldContent['size'] == 0) {
                                $fieldHtml['html'] .= '<ul class="js-goods-list sc-goods-list pic clearfix size-0">';
                                $i = 1;
                                foreach ($g_v as $key => $value) {
                                    $product = D('Shop_goods')->get_goods_by_id($value['goods_id']);

                                    if (empty($product)) {
                                        continue;
                                    }
                                    $price = $product['price'];
                                    //商品类型
                                    $type = 0;

                                    $fieldHtml['html'] .= '<li class="goods-card goods-list big-pic ' . ($fieldContent['size_type'] == 2 ? 'normal' : 'card') . '">';
                                    $fieldHtml['html'] .= '<a href="' . $url . $value['goods_id'] . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name']) . '">';
                                    $fieldHtml['html'] .= '<div class="photo-block" style="height:auto;">';
                                    $fieldHtml['html'] .= '<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;">';
                                    $fieldHtml['html'] .= '</div>';
                                    $fieldHtml['html'] .= '<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . 'btn' . $fieldContent['buy_btn_type'] . ($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1 ? '' : ' hide') . '">';
                                    $fieldHtml['html'] .= '<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                    $fieldHtml['html'] .= '<p class="goods-sub-title c-black hide"></p>';
                                    if ($fieldContent['price']) {
                                        $fieldHtml['html'] .= '<p class = "goods-price">';
                                        if ($fx_store_info['is_point_mall'] == 1) {
                                            $fieldHtml['html'] .= '<em><span class="point_ico"></span>' . $price . '</em>';
                                        } else {
                                            $fieldHtml['html'] .= '<em>' . C('config.Currency_symbol') . $price . '</em>';
                                        }
                                        $fieldHtml['html'] .= '</p>';
                                    }
                                    $fieldHtml['html'] .= '<p class="goods-price-taobao hide"></p>';
                                    $fieldHtml['html'] .= '</div>';
                                    $fieldHtml['html'] .= '</a>';
                                    if ($fieldContent['buy_btn'] && $fieldContent['size_type'] == 0) {
                                        $fieldHtml['html'] .= '<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                        $fieldHtml['html'] .= '<a href="javascript:void(0)" class="js-goods-cart-btn" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                    }

                                    $fieldHtml['html'] .= '</li>';
                                    $i++;
                                }
                                $fieldHtml['html'] .= '</ul>';
                            } else if ($fieldContent['size'] == 1) {
                                if ($fieldContent['size_type'] != 1) {
                                    $fieldHtml['html'] .= '<ul class="js-goods-list sc-goods-list pic clearfix size-1">';
                                    $i = 1;
                                    foreach ($g_v as $key => $value) {
                                        $product = D('Shop_goods')->get_goods_by_id($value['goods_id']);
                                        if (empty($product)) {
                                            continue;
                                        }

                                        //商品类型
                                        $type = 0;

                                        //商品限购
                                        $buyer_quota = !empty($product['buyer_quota']) ? $product['buyer_quota'] : 0;
                                        //价格
                                        if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                            $price = $product['drp_level_' . $drp_level . '_price'];
                                        } else {
                                            $price = $product['price'];
                                        }
                                        $fieldHtml['html'] .= '<li class="goods-card goods-list small-pic ' . ($fieldContent['size_type'] == 2 ? 'normal' : 'card') . '">';
                                        $fieldHtml['html'] .= '<a href="' . $url . $value['goods_id'] . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                        $fieldHtml['html'] .= '<div class="photo-block">';
                                        $fieldHtml['html'] .= '<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;"/>';
                                        $fieldHtml['html'] .= '</div>';
                                        $fieldHtml['html'] .= '<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . 'btn' . $fieldContent['buy_btn_type'] . ($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1 ? '' : ' hide') . '">';
                                        $fieldHtml['html'] .= '<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                        $fieldHtml['html'] .= '<p class="goods-sub-title c-black hide"></p>';
                                        if ($fieldContent['price']) {
                                            if ($fx_store_info['is_point_mall'] == 1) {
                                                $fieldHtml['html'] .= '<p class="goods-price">';
                                                $fieldHtml['html'] .= '<em><span class="point_ico"></span>' . $price . '</em>';
                                                $fieldHtml['html'] .= '</p>';
                                            } else {
                                                $fieldHtml['html'] .= '<p class="goods-price">';
                                                $fieldHtml['html'] .= '<em>' . C('config.Currency_symbol') . $price . '</em>';
                                                $fieldHtml['html'] .= '</p>';
                                            }
                                        }
                                        $fieldHtml['html'] .= '<p class="goods-price-taobao hide"></p>';
                                        $fieldHtml['html'] .= '</div>';
                                        $fieldHtml['html'] .= '</a>';
                                        if ($fieldContent['buy_btn'] && $fieldContent['size_type'] == 0) {
                                            $fieldHtml['html'] .= '<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                            $fieldHtml['html'] .= '<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                        }
                                        $fieldHtml['html'] .= '</li>';
                                        $i++;
                                    }
                                    $fieldHtml['html'] .= '</ul>';
                                } else {
                                    $fieldHtml['html'] .= '<ul class="js-goods-list sc-goods-list pic clearfix size-1 waterfall">';
                                    $i = 1;
                                    foreach ($g_v as $key => $value) {
                                        $product = D('Shop_goods')->get_goods_by_id($value['goods_id']);
                                        if (empty($product)) {
                                            continue;
                                        }

                                        //商品类型
                                        $type = 0;

                                        //商品限购
                                        $buyer_quota = !empty($product['buyer_quota']) ? $product['buyer_quota'] : 0;
                                        //价格
                                        if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                            $price = $product['drp_level_' . $drp_level . '_price'];
                                        } else {
                                            $price = $product['price'];
                                        }
                                        $fieldHtml['html'] .= '<li class="goods-card goods-list small-pic waterfall" style="width:155px;">';
                                        $fieldHtml['html'] .= '<a href="' . $url . $value['goods_id'] . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                        $fieldHtml['html'] .= '<div class="photo-block">';
                                        $fieldHtml['html'] .= '<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;">';
                                        $fieldHtml['html'] .= '</div>';
                                        $fieldHtml['html'] .= '<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . 'btn' . $fieldContent['buy_btn_type'] . ($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1 ? '' : ' hide') . '">';
                                        $fieldHtml['html'] .= '<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                        $fieldHtml['html'] .= '<p class="goods-sub-title c-black hide"></p>';
                                        if ($fieldContent['price']) {
                                            $fieldHtml['html'] .= '<p class="goods-price">';
                                            if ($fx_store_info['is_point_mall'] == 1) {
                                                $fieldHtml['html'] .= '<em><span class="point_ico"></span>' . $price . '</em>';
                                            } else {
                                                $fieldHtml['html'] .= '<em>' . C('config.Currency_symbol') . $price . '</em>';
                                            }
                                            $fieldHtml['html'] .= '</p>';
                                        }

                                        $fieldHtml['html'] .= '<p class="goods-price-taobao hide"></p>';
                                        $fieldHtml['html'] .= '</div>';
                                        $fieldHtml['html'] .= '</a>';
                                        if ($fieldContent['buy_btn'] && ($fieldContent['size_type'] == 0 || $fieldContent['size_type'] == 1)) {
                                            $fieldHtml['html'] .= '<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                            $fieldHtml['html'] .= '<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                        }
                                        $fieldHtml['html'] .= '</li>';
                                        $i++;
                                    }
                                    $fieldHtml['html'] .= '</ul>';
                                }
                            } else if ($fieldContent['size'] == 2) {
                                $fieldHtml['html'] .= '<ul class="js-goods-list sc-goods-list pic clearfix size-2">';
                                $i = 1;
                                foreach ($g_v as $key => $value) {
                                    $product = D('Shop_goods')->get_goods_by_id($value['goods_id']);
                                    if (empty($product)) {
                                        continue;
                                    }
                                    //商品类型
                                    $type = 0;

                                    //价格
                                    $price = $product['price'];

                                    $fieldHtml['html'] .= '<li class="goods-card goods-list ' . ($i % 3 == 1 ? 'big-pic' : 'small-pic') . ($fieldContent['size_type'] == 2 ? ' normal' : ' card') . '">';
                                    $fieldHtml['html'] .= '<a href="' . $url . $value['goods_id'] . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                    $fieldHtml['html'] .= '<div class="photo-block">';
                                    $fieldHtml['html'] .= '<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:inline;">';
                                    $fieldHtml['html'] .= '</div>';
                                    $fieldHtml['html'] .= '<div class="info clearfix ' . ($fieldContent['show_title'] == 1 ? 'info-title ' : 'info-no-title ') . ($fieldContent['price'] == 1 ? 'info-price ' : 'info-no-price ') . '' . $fieldContent['buy_btn_type'] . (($fieldContent['show_title'] == 1 || $fieldContent['price'] == 1) ? '' : ' hide') . '">';
                                    $fieldHtml['html'] .= '<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';
                                    $fieldHtml['html'] .= '<p class="goods-sub-title c-black hide"></p>';
                                    if ($fieldContent['price']) {
                                        $fieldHtml['html'] .= '<p class="goods-price">';
                                        $fieldHtml['html'] .= '<em>' . C('config.Currency_symbol') . $price . '</em>';
                                        $fieldHtml['html'] .= '</p>';
                                    }

                                    $fieldHtml['html'] .= '<p class="goods-price-taobao hide"></p>';
                                    $fieldHtml['html'] .= '</div>';
                                    $fieldHtml['html'] .= '</a>';
                                    if ($fieldContent['buy_btn'] && $fieldContent['size_type'] == 0) {
                                        $fieldHtml['html'] .= '<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                        $fieldHtml['html'] .= '<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                    }
                                    $fieldHtml['html'] .= '</li>';
                                    $i++;
                                }
                                $fieldHtml['html'] .= '</ul>';
                            } else if ($fieldContent['size'] == 3) {
                                $fieldHtml['html'] .= '<ul class="js-goods-list sc-goods-list clearfix list size-3">';
                                $i = 1;
                                foreach ($g_v as $key => $value) {
                                    $product = D('Shop_goods')->get_goods_by_id($value['goods_id']);
                                    if (empty($product)) {
                                        continue;
                                    }

                                    //商品类型
                                    $type = 0;

                                    //商品限购
                                    $buyer_quota = !empty($product['buyer_quota']) ? $product['buyer_quota'] : 0;
                                    //价格
                                    if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                        $price = $product['drp_level_' . $drp_level . '_price'];
                                    } else {
                                        $price = $product['price'];
                                    }
                                    $fieldHtml['html'] .= '<li class="goods-card goods-list ' . ($i % 3 == 1 ? 'big-pic' : 'small-pic') . ($fieldContent['size_type'] == 2 ? ' normal' : ' card') . '">';
                                    $fieldHtml['html'] .= '<a href="' . $url . $value['goods_id'] . '" class="js-goods link clearfix" target="_blank" title="' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '">';
                                    $fieldHtml['html'] .= '<div class="photo-block">';
                                    $fieldHtml['html'] .= '<img class="goods-photo js-goods-lazy" src="' . ($product['pic_arr'][0]['url']) . '" style="display:block;">';
                                    $fieldHtml['html'] .= '</div>';
                                    $fieldHtml['html'] .= '<div class="info">';
                                    $fieldHtml['html'] .= '<p class="goods-title">' . htmlspecialchars_decode($product['name'], ENT_QUOTES) . '</p>';

                                    if ($fieldContent['price']) {
                                        $fieldHtml['html'] .= '<p class="goods-price">';
                                        if ($fx_store_info['is_point_mall'] == 1) {
                                            $fieldHtml['html'] .= '<em><span class="point_ico"></span>' . $price . '</em>';
                                        } else {
                                            $fieldHtml['html'] .= '<em>' . C('config.Currency_symbol') . $price . '</em>';
                                        }
                                        $fieldHtml['html'] .= '</p>';
                                    }

                                    $fieldHtml['html'] .= '</div>';
                                    $fieldHtml['html'] .= '</a>';
                                    if ($fieldContent['buy_btn']) {
                                        $fieldHtml['html'] .= '<div class="js-goods-buy buy-response" data-id="' . $value['id'] . '"></div>';
                                        $fieldHtml['html'] .= '<a href="javascript:void(0)" class="js-goods-cart-btn" data-buyer-quota="' . $buyer_quota . '" data-id="' . $value['id'] . '" data-store_id="' . $seller_id . '" data-type="' . $type . '"><div class="goods-buy btn' . $fieldContent['buy_btn_type'] . ' info-no-title"></div></a>';
                                    }
                                    $fieldHtml['html'] .= '</li>';
                                    $i++;
                                }
                                $fieldHtml['html'] .= '</ul>';
                            }
                        }
                        $fieldHtml['html'].='</div>';
                    }
                    break;

                case 'article' :
                    //$custom_field = D('Custom_field')->where(array('field_type'=>'article','store_id'=>$_SESSION['store']['store_id']))->find();
                    //$content = unserialize($custom_field['content']);
                    $contents = $fieldContent['activity_arr'];
                    // 动态
                    $aids = array();
                    foreach($contents as $con_article){
                        if(!in_array($con_article['id'],$aids)){
                            $aids[] = $con_article['id'];
                        }
                    }
                    $article_list = D('Article')->where(array('status'=>1, 'platform_status'=>1,'id'=>array('in',$aids)))->select();
                    if(!$article_list){
                        break;
                    }
                    $articles = false;
                    $on_aids = array();
                    foreach($article_list as $_article){
                        $_article['product'] = D('Product')->field('product_id,name,image')->where(array('product_id'=>$_article['product_id']))->find();
                        $collects = D('My_collection_article')->field('count(*) as count')->where(array('article_id'=>$_article['id']))->find();
                        $mycollected = D('My_collection_article')->where(array('article_id'=>$_article['id'],'uid'=>$_SESSION['wap_user']['uid']))->find();
                        $_article['collect'] = $collects['count'];
                        $_article['mycollected'] = $mycollected ? 1 : 0;
                        $articles[$_article['id']] = $_article;
                        $on_aids[] = $_article['id'];
                    }

                    $js = '<script src="'.STATIC_URL.'js/layer_mobile/layer.m.js"></script><script type="text/javascript">

							// 查看更多
							function get_more(obj){
								var aid = $(obj).attr("aid");
								var store_id = $(obj).attr("store_id");
								var target = "' . option('config.site_url') . '/wap/article.php?action=index&store_id="+store_id+"&aid="+aid;
								window.location.href=target;
							}

							// 添加/删除收藏
							function collections(obj){
								var aid = $(obj).attr("aid");
								var store_id = $(obj).attr("store_id");
								$.post("' . option('config.site_url') . '/wap/article.php?action=collect",{"aid":aid,"store_id":store_id},function(response){
									if(response.err_code>0){
										layer.open({title:[L_("系统提示"),"background-color:#FF6600;color:#fff;"],content: response.err_msg});
										return;
									}
									var isactive = $(obj).find("i").hasClass("active");
									var collect_count = parseInt($(obj).find("span").text());
									var flag = response.err_msg;
									if(isactive){
										if(flag=="add"){
											$(obj).find("span").text(collect_count+1);
										}else{
											$(obj).find("i").removeClass("active");
											$(obj).find("span").text(collect_count-1);
										}
									}else{
										if(flag=="add"){
											$(obj).find("i").addClass("active");
											$(obj).find("span").text(collect_count+1);
										}else{
											$(obj).find("span").text(collect_count-1);
										}
									}

								},"json");
							}

							</script>';
                    $current_store = current($articles);
                    $shop_name_my = $fieldContent['name']?$fieldContent['name']:L_('店铺动态');
                    $html  = '<div class="shopIndex  swiper-container swiper-container-horizontal">';
                    $html .= '  <div class="title"><span>'.$shop_name_my.'</span><span><a href="javascript:;" onclick="get_more(this)" id="a_get_more" aid="'.$contents[0]['id'].'" store_id="'.$current_store['store_id'].'">查看更多</a><i></i></span></div>';
                    $html .= '  <ul class="swiper-wrapper" id="shop_article">';
                    foreach($contents as $_item){

                        if (!in_array($_item['id'], $on_aids)) {
                            continue;
                        }

                        $html .= '      <li class="swiper-slide swiper-slide-active" aid="'.$_item['id'].'" store_id="'.$articles[$_item['id']]['store_id'].'">';
                        $html .= '          <div class="shopInfo clearfix">';
                        $html .= '              <div class="shopImg">';
                        $html .= '                  <a href="' . option('config.site_url') . '/wap/good.php?id='.$articles[$_item['id']]['product_id'].'&platform=1"><img src="'.getAttachmentUrl($articles[$_item['id']]['product']['image']).'" width=84 height=84></a>';
                        $html .= '              </div>';
                        $html .= '              <div class="shopTxt">';
                        $html .= '                  <h2>'.$_item['title'].'</h2>';
                        $html .= '                  <p>'.getHumanTime(time()-$articles[$_item['id']]['dateline']).L_('前').'</p>';
                        $html .= '              </div>';
                        $iscollected = $articles[$_item['id']]['mycollected']?'active':'';  // 是否收藏过
                        $html .= '              <button onclick="collections(this)" aid='.$_item['id'].'  store_id='.$articles[$_item['id']]['store_id'].'><i class="'.$iscollected.'"></i><span>'.$articles[$_item['id']]['collect'].'</span></button>';
                        $html .= '          </div>';
                        $html .= '          <ul class="shopList">';
                        $html .= '              <li class="">';
                        $html .= '                  <p>'.$articles[$_item['id']]['desc'].'</p>';
                        $html .= '                  <ul class="clearfix ">';
                        $imgs = explode(',',$articles[$_item['id']]['pictures']);
                        foreach($imgs as $___key => $img){
                            if($___key<=2){
                                $html .= '                      <li><img src="'.getAttachmentUrl($img).'" width=110 height=110></li>';
                            }
                        }
                        $html .= '                  </ul>';
                        $html .= '              </li>';
                        $html .= '          </ul>';
                        $html .= '      </li>';
                    }
                    $html .= '  </ul>';
                    $html .= '<ul class="shopSpot swiper-pagination  swiper-pagination-clickable swiper-pagination-bullets"><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet"></span></ul>
							</div>';

                    $html .= $js;
                    $fieldHtml['html'] .= $html;
                    break;

                case 'cube': //魔方
                    $fieldHtml['html'] = '<!-- 魔方 --><div style="clear:both"></div>';

                    $cube_html = '<table class="custom-cube2-table js-custom-cube2-table" style="visibility: visible;">';
                    $cube_html .= ' <tbody>';

                    $megre_td = array();
                    for ($r = 0; $r < 4; $r++) {
                        $cube_html .= '<tr>';
                        for ($c = 0; $c < 4; $c++) {
                            $_selected = false;
                            $_colspan = 1;
                            $_rowspan = 1;
                            $_index = 0;
                            $_image = '';
                            $_url = '';
                            foreach ($fieldContent['content'] as $i => $cube_data) {
                                if ($cube_data['x'] == $c && $cube_data['y'] == $r) {
                                    $_selected = true;
                                    $_colspan = $cube_data['colspan'];
                                    $_rowspan = $cube_data['rowspan'];
                                    $_index = $i;
                                    $_image = !empty($cube_data['image']) ? $cube_data['image'] : '';
                                    $_url = !empty($cube_data['url']) ? $cube_data['url'] : '';
                                }
                            }

                            //修改url中store_id参数，若没有则添加
                            if (!empty($_url)) {
                                $tmp_url = $this->redirect_store($seller_id, $_url);
                                if (!empty($tmp_url)) {
                                    $_url = $tmp_url;
                                }
                            }

                            if ($_rowspan > 1) {
                                for ($r2 = 1; $r2 < $_rowspan; $r2++) {
                                    for ($c2 = 0; $c2 < $_colspan; $c2++) {
                                        $megre_td[] = ($c + $c2) . ':' . ($r + $r2);
                                    }
                                }
                            }
                            if ($_colspan > 1) {
                                $c += ($_colspan - 1);
                            }
                            if (!in_array(($c . ':' . $r), $megre_td)) {
                                if ($_selected) {
                                    $cube_html .= '<td class="not-empty cols-' . $_colspan . ' rows-' . $_rowspan . ' index-' . $_index . '" colspan="' . $_colspan . '" rowspan="' . $_rowspan . '" data-index="' . $_index . '">';
                                    if (!empty($_image)) {
                                        $_image = replace_file_domain($_image);
                                        if (!empty($_url)) {
                                            $cube_html .= '<a href="' . $_url . '"><img src="' . $_image . '"></a>';
                                        } else {
                                            $cube_html .= '  <img src="' . $_image . '">';
                                        }
                                    } else {
                                        $cube_html .= '';
                                    }
                                    $cube_html .= '</td>';
                                } else {
                                    $cube_html .= '<td class="empty" data-x="' . $c . '" data-y="' . $r . '"></td>';
                                }
                            }
                        }
                        $cube_html .= '</tr>';
                    }
                    $cube_html .= '</body>';
                    $cube_html .= '</table>';
                    $fieldHtml['html'] .= $cube_html;
                    $fieldHtml['html'] .= '<div style="clear:both"></div>';
                    break;

                case 'map':
                    $fieldHtml['html'] = '<div><div class="custom-title text-left" style="margin-bottom:0;background:none;"><h2 class="title">店铺地图</h2></div></div><div class="edit-map-page"><div class="js-map-box" id="map-'.$key.'" data-title="'.C('now_store.name').'" data-lng="'.C('now_store.long').'" data-lat="'.C('now_store.lat').'" data-adress="'.C('now_store.adress').'"></div></div>';
                    break;

                case 'store_map':

                    if (empty($drp_diy_store) && !empty($seller_id)) {
                        $stores = D('Merchant_store')->where(array('store_id' => $seller_id))->field("adress,name,phone")->find();
                    } else {
                        $stores = D('Merchant_store')->where(array('store_id' => $store_id))->field("adress,name,phone")->find();
                    }
                    $adress = $stores['adress'];
                    $storename = $stores['name'];
                    $phone = $stores['phone'];

                    $fieldHtml['html'] = '<!-- 联系店铺 -->';
                    $fieldHtml['html'] .= '<div class="contact_store map">';
                    $fieldHtml['html'] .= '<dl><dd class="storename">'.$storename.'<dl></dd>';
                    $fieldHtml['html'] .= '<dt><span class="icon_addess adress_title">' . $adress .'</span></dt></dl>';
                    $fieldHtml['html'] .= '<div class="contact_icon">';


                    if ($fieldContent['tel_play']) {
                        $fieldHtml['html'] .= '<div class="mask2" style="display: none;"></div>';
                        $fieldHtml['html'] .= '<div class="bg_d pt30 pl30 pr30 pb10 br5 phone_content" style="color: rgb(81, 195, 50); z-index: 2223; display: none;">';
                        $fieldHtml['html'] .= '<ul>';
                        if ($phone) {
                            $phone = explode(' ', $phone);
                            foreach ($phone as $val) {
                                $fieldHtml['html'] .= '<a href="tel:'.$val.'">';
                                $fieldHtml['html'] .= '<li class="bg_f pt5 pb5 pl10 mb_20 flex br5">';
                                $fieldHtml['html'] .= '<span class="bg_f pt5 pb5 pl10 flex br5"><img src="'.$site_url.'/tpl/Wap/pure/static/diypage/images/dianhua.png" alt=""></span>';
                                $fieldHtml['html'] .= '<span style="padding-left: 5px;">'.$val.'</span>';
                                $fieldHtml['html'] .= '</li>';
                                $fieldHtml['html'] .= '</a>';
                            }
                        } else {
                            $fieldHtml['html'] .= '<a href="#">';
                            $fieldHtml['html'] .= '<li class="bg_f pt5 pb5 pl10 mb_20 flex br5">';
                            $fieldHtml['html'] .= '<span>暂无联系电话</span>';
                            $fieldHtml['html'] .= '</li>';
                            $fieldHtml['html'] .= '</a>';
                        }


                        $fieldHtml['html'] .= '</ul>';
                        $fieldHtml['html'] .= '<div class="font_size28 color_9 phone_cancel cursor">×</div>';
                        $fieldHtml['html'] .= '</div>';

                        $fieldHtml['html'] .= '<a href="#" class="phone dianhua"><img src="'.$site_url.'/tpl/Wap/pure/static/diypage/images/dianhua.png" alt=""></a>';
                    } else {
                        $fieldHtml['html'] .= '<a href="#" class="dianhua" style="display:none"><img src="'.$site_url.'/tpl/Wap/pure/static/diypage/images/dianhua.png" alt=""></a>';
                    }
                    if ($fieldContent['tel_play']) {
                        $fieldHtml['html'] .= '<a href="'.$site_url.'/wap.php?g=Wap&c=Group&a=addressinfo&store_id='.$seller_id.'" class="adressIcon"><img src="'.$site_url.'/tpl/Wap/pure/static/diypage/images/jingqujiaotong-daozhequ.png" alt=""></a>';
                    } else {
                        $fieldHtml['html'] .= '<a href="#" class="adressIcon" style="display:none"><img src="'.$site_url.'/tpl/Wap/pure/static/diypage/images/jingqujiaotong-daozhequ.png" alt=""></a>';
                    }
                    $fieldHtml['html'] .= '</div></div>';

                    break;

                case 'tuan_module':
                case 'seckill_module':
                case 'bargain_module':

                    $config = $fieldContent['config'];
                    $activities = $fieldContent['activities'] ? $fieldContent['activities'] : '';
                    $bargain_db = M('Mall_bargain_act');
                    $group_db = D('Mall_group_act');
                    $shop_goods_db = M('Shop_goods');
                    $now_time = time();
                    if ($value['field_type'] == 'bargain_module') {
                        foreach ($activities as $key => &$item) {
                            $mall_bargain_act = $bargain_db->where(array('id' => $item['id']))->find();
                            // 活动不存在 去除
                            if (empty($mall_bargain_act)) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 如果隐藏已经结束活动
                            if ($config['finish_hide'] == 'true' && $mall_bargain_act['ftime'] < $now_time) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 获取对应商品信息
                            $shop_goods = $shop_goods_db->where(array('goods_id' => $mall_bargain_act['goods_id']))->find();
                            // 活动对应商品不存在 去除
                            if (empty($shop_goods)) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 如果隐藏已经售罄活动
                            if ($config['outsale_hide'] == 'true' && $shop_goods['stock_num'] != -1 && $shop_goods['stock_num'] <= 0) {
                                unset($activities[$key]);
                                continue;
                            }

                            $item['goods_id'] = $mall_bargain_act['goods_id'];
                            $item['spec_id'] = $mall_bargain_act['spec_id'];
                            $item['spec_price'] = $mall_bargain_act['spec_price'];
                            $item['stime'] = $mall_bargain_act['stime'];
                            $item['ftime'] = $mall_bargain_act['ftime'];
                            $item['floor_price'] = $mall_bargain_act['floor_price'];


                            $item['stock_num'] = $shop_goods['stock_num'];
                            $item['unit'] = $shop_goods['unit'];
                            $item['price'] = $shop_goods['price'];
                            $item['sell_count'] = $shop_goods['sell_count'];


                        }
                    } elseif ($value['field_type'] == 'tuan_module') {
                        foreach ($activities as $key => &$item) {
                            $mall_group_act = $group_db->get_group_by_id($item['id']);
                            // 活动不存在 去除
                            if (empty($mall_group_act)) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 如果隐藏已经结束活动
                            if ($config['finish_hide'] == 'true' && $mall_group_act['ftime'] < $now_time) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 获取对应商品信息
                            $shop_goods = $shop_goods_db->where(array('goods_id' => $mall_group_act['good_id']))->find();
                            // 活动对应商品不存在 去除
                            if (empty($shop_goods)) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 如果隐藏已经售罄活动
                            if ($config['outsale_hide'] == 'true' && $shop_goods['stock_num'] != -1 && $shop_goods['stock_num'] <= 0) {
                                unset($activities[$key]);
                                continue;
                            }

                            $item['goods_id'] = $mall_group_act['good_id'];
                            $item['spec_id'] = $mall_group_act['spec_id'];
                            $item['spec_price'] = $mall_group_act['spec_price'];
                            $item['stime'] = $mall_group_act['stime'];
                            $item['ftime'] = $mall_group_act['ftime'];
                            $item['floor_price'] = $mall_group_act['group_price'];
                            $item['complete_num'] = $mall_group_act['complete_num'];

                            $item['price'] = $shop_goods['price'];
                            $item['stock_num'] = $shop_goods['stock_num'];
                            $item['unit'] = $shop_goods['unit'];
                            $item['sell_count'] = $shop_goods['sell_count'];


                            $item['price_name'] = $mall_group_act['complete_num'] . '人团';
                        }
                    } elseif ($value['field_type'] == 'seckill_module') {
                        $config = $fieldContent['config'];
                        $activities = $fieldContent['activities'] ? $fieldContent['activities'] : '';
                        fdump($config, '$seckill_module_config');
                        fdump($activities, '$seckill_module_activities');

                        foreach ($activities as $key => &$item) {
                            // 获取对应商品信息
                            $shop_goods = $shop_goods_db->where(array('goods_id' => $item['id']))->find();
                            // 活动对应商品不存在 去除
                            if (empty($shop_goods)) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 如果隐藏已经结束活动
                            if ($shop_goods['seckill_type']==1) {
                                $seckill_open_time = date('Y-m-d') . date('H:i:s',$shop_goods['seckill_open_time']);
                                $shop_goods['seckill_open_time'] = strtotime($seckill_open_time);
                                $seckill_close_time = date('Y-m-d') . date('H:i:s',$shop_goods['seckill_close_time']);
                                $shop_goods['seckill_close_time'] = strtotime($seckill_close_time);
                            }

                            if ($config['finish_hide'] == 'true' && $shop_goods['seckill_close_time'] < $now_time) {
                                unset($activities[$key]);
                                continue;
                            }
                            // 如果隐藏已经售罄活动
                            if ($config['outsale_hide'] == 'true' && $shop_goods['seckill_stock'] != -1 && $shop_goods['seckill_stock'] <= 0) {
                                unset($activities[$key]);
                                continue;
                            }


                            $item['stime'] = $shop_goods['seckill_open_time'];
                            $item['ftime'] = $shop_goods['seckill_close_time'];
                            $item['floor_price'] = $shop_goods['seckill_price'];
                            $item['unit'] = $shop_goods['unit'];
                            $item['goods_id'] = $shop_goods['goods_id'];
                            $item['price'] = $shop_goods['price'];
                            $item['sell_count'] = 0;
                            $today = date('Ymd',time());
                            if ($today == $shop_goods['sell_day']) {
                                $seckill_stock_num = $shop_goods['seckill_stock'] == -1 ? -1 : (intval($shop_goods['seckill_stock'] - $shop_goods['today_seckill_count']) > 0 ? intval($shop_goods['seckill_stock'] - $shop_goods['today_seckill_count']) : 0);
                            } else {
                                $seckill_stock_num = $shop_goods['seckill_stock'];
                            }
                            $item['stock_num'] = $seckill_stock_num;

                        }
                    } else {
                        continue;
                    }


                    if ($activities) {
                        $fieldHtml['html'] = '<!-- 人气砍价 -->';
                        $fieldHtml['html'] .= '<div class="info_module">';
                        $fieldHtml['html'] .= '<div class="control-group">';
                        $fieldHtml['html'] .= '    <div class="'.$value['field_type'].'">';
                        $fieldHtml['html'] .= '        <div class="orangeTheme">';
                        $fieldHtml['html'] .= '<p class="al-title">'.$config['title'].'</p>';

                        $fieldHtml['html'] .= '<div class="al-container al-display-style-'.$config['list_display_style'].'">';

                        // 如果 展示样式： 大图平铺
                        $backgroundSize = $config['main_image_fill_style'] == 1 ? 'cover' : 'contain';
                        $background_size_info = $config['main_image_fill_style'] == 1 ? 'background-size: 100% 100%;' : '';

                        fdump($config, '$config_msg',true);


                        if ($config['list_display_style'] == 1) {
                            foreach ($activities as $val) {
                                // 跳转页面路劲
                                if ($value['field_type'] == 'tuan_module'){
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&group_id={$val['id']}";
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&bar_id={$val['id']}";
                                } elseif ($value['field_type'] == 'seckill_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}";
                                }

                                $fieldHtml['html'] .= '<a class="al-item" href="'.$url.'">';

                                $fieldHtml['html'] .= '<div class="al-main-img al-main-image-size-style-'.$config['main_image_size_style']. ' al-main-image-fill-style-'. $config['main_image_fill_style'].'" style="background:url('.$val['image'].') center center / '.$backgroundSize.'  no-repeat">';
                                $fieldHtml['html'] .= '</div>';

                                $fieldHtml['html'] .= '<div class="al-board">';
                                $fieldHtml['html'] .= '    <div class="al-type-name"><p>'.$config['field_type_name'].'</p></div>';
                                if ($config['remain_sku_show'] == 'true') {

                                    $stock_num = intval($val['stock_num']) - intval($val['sell_count']);
                                    if ($val['stock_num']==-1) {
                                        $remain_txt = '库存充足';
                                    } elseif ($stock_num<=0) {
                                        $remain_txt = '库存不足';
                                    } elseif ($val['unit']) {
                                        $remain_txt = '剩余'.$stock_num.$val['unit'];
                                    } else {
                                        $remain_txt = '剩余'.$stock_num.'件';
                                    }
                                    $fieldHtml['html'] .= '<div class="al-remain-sku">'.$remain_txt.'</div>';
                                }
                                if ($config['time_count_show'] == 'true') {
                                    $fieldHtml['html'] .= '<div class="al-time-count timer_info_'.$val['id'].'">';
                                    $fieldHtml['html'] .= '<time-count-down data-id="'.$val['id'].'" data-stime="'.$val['stime'].'" data-ftime="'.$val['ftime'].'"></time-count-down>';
                                    $fieldHtml['html'] .= '</div>';
                                }
                                $fieldHtml['html'] .= '</div>';

                                if ($config['name_show'] == 'true') {
                                    $title_name = $val['title'] ? $val['title'] : $val['name'];
                                    $fieldHtml['html'] .= '<div class="al-good-name textOmitted">';
                                    $fieldHtml['html'] .= '<p>'.$title_name.'</p>';
                                    $fieldHtml['html'] .= '</div>';
                                }

                                $fieldHtml['html'] .= '<div class="al-action-list">';
                                $fieldHtml['html'] .= '    <div class="al-price">';
                                if ($value['field_type'] == 'tuan_module'){
                                    $price_name = $val['price_name'];
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $price_name = $config['price_name'];
                                } else {
                                    $price_name = $config['price_name'];
                                }

                                $fieldHtml['html'] .= '        <p class="al-price-prefix">'.$price_name.':'.C('config.Currency_symbol').'</p>';
                                $fieldHtml['html'] .= '        <p class="al-current-price">'.$val['floor_price'].' </p>';
                                if ($config['original_price_show'] == 'true') {
                                    $fieldHtml['html'] .= '        <p class="al-original-price"> '.C('config.Currency_symbol').$val['price'].'</p>';
                                }
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '    <div class="al-buy-btn al-buy-btn-style-'.$config['buy_btn_style'].'">';
                                $fieldHtml['html'] .= '' . $config['buy_btn_name'];
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</a>';
                            }
                        } elseif ($config['list_display_style'] == 2) {
                            $fieldHtml['html'] .= '<div style="overflow-x: scroll">';

                            foreach ($activities as $val) {
                                // 跳转页面路劲
                                if ($value['field_type'] == 'tuan_module'){
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&group_id={$val['id']}";
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&bar_id={$val['id']}";
                                } elseif ($value['field_type'] == 'seckill_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}";
                                }

                                $fieldHtml['html'] .= '<a class="al-item" href="'.$url.'">';

                                $fieldHtml['html'] .= '<div class="al-main-img al-main-image-size-style-'.$config['main_image_size_style']. ' al-main-image-fill-style-'. $config['main_image_fill_style'].'" style="background:url('.$val['image'].') center center / '.$backgroundSize.'  no-repeat;'.$background_size_info.'">';
                                $fieldHtml['html'] .= '</div>';

                                $fieldHtml['html'] .= '<div class="al-board">';
                                $fieldHtml['html'] .= '    <div class="al-type-name"><p>'.$config['field_type_name'].'</p></div>';
                                if ($config['remain_sku_show'] == 'true') {
                                    $stock_num = intval($val['stock_num']) - intval($val['sell_count']);
                                    if ($val['stock_num']==-1) {
                                        $remain_txt = '库存充足';
                                    } elseif ($stock_num<=0) {
                                        $remain_txt = '库存不足';
                                    } elseif ($val['unit']) {
                                        $remain_txt = '剩余'.$stock_num.$val['unit'];
                                    } else {
                                        $remain_txt = '剩余'.$stock_num.'件';
                                    }
                                    $fieldHtml['html'] .= '<div class="al-remain-sku">'.$remain_txt.'</div>';
                                }
                                if ($config['time_count_show'] == 'true') {
                                    $fieldHtml['html'] .= '<div class="al-time-count timer_info_'.$val['id'].'">';
                                    $fieldHtml['html'] .= '<time-count-down data-id="'.$val['id'].'" data-stime="'.$val['stime'].'" data-ftime="'.$val['ftime'].'"></time-count-down>';
                                    $fieldHtml['html'] .= '</div>';
                                }
                                $fieldHtml['html'] .= '</div>';

                                if ($config['name_show'] == 'true') {
                                    $title_name = $val['title'] ? $val['title'] : $val['name'];
                                    $fieldHtml['html'] .= '<div class="al-good-name textOmitted">';
                                    $fieldHtml['html'] .= '<p>'.$title_name.'</p>';
                                    $fieldHtml['html'] .= '</div>';
                                }

                                $fieldHtml['html'] .= '<div class="al-action-list">';
                                $fieldHtml['html'] .= '    <div class="al-price">';
                                if ($value['field_type'] == 'tuan_module'){
                                    $price_name = $val['price_name'];
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $price_name = $config['price_name'];
                                } else {
                                    $price_name = $config['price_name'];
                                }

                                $fieldHtml['html'] .= '        <p class="al-price-prefix">'.$price_name.':'.C('config.Currency_symbol').'</p>';
                                $fieldHtml['html'] .= '        <p class="al-current-price">'.$val['floor_price'].' </p>';
                                if ($config['original_price_show'] == 'true') {
                                    $fieldHtml['html'] .= '        <p class="al-original-price"> '.C('config.Currency_symbol').$val['price'].'</p>';
                                }
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '    <div class="al-buy-btn al-buy-btn-style-'.$config['buy_btn_style'].'">';
                                $fieldHtml['html'] .= '' . $config['buy_btn_name'];
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</a>';
                            }

                            $fieldHtml['html'] .= '</div>';
                        } elseif ($config['list_display_style'] == 3) {

                            $fieldHtml['html'] .= '<div  style="height: 280px;">';

                            $fieldHtml['html'] .= '    <div class="custom-image-swiper">';
                            $fieldHtml['html'] .= '        <div class="swiper-container">';
                            $fieldHtml['html'] .= '            <div class="swiper-wrapper">';

                            foreach ($activities as $val) {
                                $fieldHtml['html'] .= '<div class="swiper-slide" >';
                                // 跳转页面路劲
                                if ($value['field_type'] == 'tuan_module'){
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&group_id={$val['id']}";
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&bar_id={$val['id']}";
                                } elseif ($value['field_type'] == 'seckill_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}";
                                }

                                $fieldHtml['html'] .= '<a class="al-item" href="'.$url.'">';

                                $fieldHtml['html'] .= '<div class="al-main-img al-main-image-size-style-'.$config['main_image_size_style']. ' al-main-image-fill-style-'. $config['main_image_fill_style'].'" style="background:url('.$val['image'].') center center / '.$backgroundSize.'  no-repeat">';
                                $fieldHtml['html'] .= '</div>';

                                $fieldHtml['html'] .= '<div class="al-board">';
                                $fieldHtml['html'] .= '    <div class="al-type-name"><p>'.$config['field_type_name'].'</p></div>';
                                if ($config['remain_sku_show'] == 'true') {
                                    $stock_num = intval($val['stock_num']) - intval($val['sell_count']);
                                    if ($val['stock_num']==-1) {
                                        $remain_txt = '库存充足';
                                    } elseif ($stock_num<=0) {
                                        $remain_txt = '库存不足';
                                    } elseif ($val['unit']) {
                                        $remain_txt = '剩余'.$stock_num.$val['unit'];
                                    } else {
                                        $remain_txt = '剩余'.$stock_num.'件';
                                    }
                                    $fieldHtml['html'] .= '<div class="al-remain-sku">'.$remain_txt.'</div>';
                                }
                                if ($config['time_count_show'] == 'true') {
                                    $fieldHtml['html'] .= '<div class="al-time-count timer_info_'.$val['id'].'">';
                                    $fieldHtml['html'] .= '<time-count-down data-id="'.$val['id'].'" data-stime="'.$val['stime'].'" data-ftime="'.$val['ftime'].'"></time-count-down>';
                                    $fieldHtml['html'] .= '</div>';
                                }
                                $fieldHtml['html'] .= '</div>';

                                if ($config['name_show'] == 'true') {
                                    $title_name = $val['title'] ? $val['title'] : $val['name'];
                                    $fieldHtml['html'] .= '<div class="al-good-name textOmitted">';
                                    $fieldHtml['html'] .= '<p>'.$title_name.'</p>';
                                    $fieldHtml['html'] .= '</div>';
                                }

                                $fieldHtml['html'] .= '<div class="al-action-list">';
                                $fieldHtml['html'] .= '    <div class="al-price">';
                                if ($value['field_type'] == 'tuan_module'){
                                    $price_name = $val['price_name'];
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $price_name = $config['price_name'];
                                } else {
                                    $price_name = $config['price_name'];
                                }

                                $fieldHtml['html'] .= '        <p class="al-price-prefix">'.$price_name.':'.C('config.Currency_symbol').'</p>';
                                $fieldHtml['html'] .= '        <p class="al-current-price">'.$val['floor_price'].' </p>';
                                if ($config['original_price_show'] == 'true') {
                                    $fieldHtml['html'] .= '        <p class="al-original-price"> '.C('config.Currency_symbol').$val['price'].'</p>';
                                }
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '    <div class="al-buy-btn al-buy-btn-style-'.$config['buy_btn_style'].'">';
                                $fieldHtml['html'] .= '' . $config['buy_btn_name'];
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</a>';
                                $fieldHtml['html'] .= '</div>';
                            }

                            $fieldHtml['html'] .= '            </div>';
                            $fieldHtml['html'] .= '        </div>';
                            $fieldHtml['html'] .= '    </div>';

                            $fieldHtml['html'] .= '    <div class="swiper-pagination"><span class="swiper-pagination-switch swiper-active-switch"></span><span class="swiper-pagination-switch"></span></div>';

                            $fieldHtml['html'] .= '</div>';

                        } elseif ($config['list_display_style'] == 4) {
                            foreach ($activities as $val) {
                                // 跳转页面路劲
                                if ($value['field_type'] == 'tuan_module'){
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&group_id={$val['id']}";
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&bar_id={$val['id']}";
                                } elseif ($value['field_type'] == 'seckill_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}";
                                }

                                $fieldHtml['html'] .= '<a class="al-item clearfix" href="'.$url.'">';

                                $fieldHtml['html'] .= '<div class="al-main-img al-main-image-size-style-'.$config['main_image_size_style']. ' al-main-image-fill-style-'. $config['main_image_fill_style'].'" style="background:url('.$val['image'].') center center / '.$backgroundSize.'  no-repeat">';
                                $fieldHtml['html'] .= '</div>';

                                $fieldHtml['html'] .= '<div class="al-board">';
                                $fieldHtml['html'] .= '    <div class="al-type-name"><p>'.$config['field_type_name'].'</p></div>';
                                if ($config['time_count_show'] == 'true') {
                                    $fieldHtml['html'] .= '<div class="al-time-count timer_info_'.$val['id'].'">';
                                    $fieldHtml['html'] .= '<time-count-down data-id="'.$val['id'].'" data-stime="'.$val['stime'].'" data-ftime="'.$val['ftime'].'"></time-count-down>';
                                    $fieldHtml['html'] .= '</div>';
                                }
                                $fieldHtml['html'] .= '</div>';

                                if ($config['name_show'] == 'true') {
                                    $title_name = $val['title'] ? $val['title'] : $val['name'];
                                    $fieldHtml['html'] .= '<div class="al-good-name">';
                                    $fieldHtml['html'] .= '<p>'.$title_name.'</p>';
                                    $fieldHtml['html'] .= '</div>';
                                }

                                $fieldHtml['html'] .= '<div class="al-action-list">';
                                $fieldHtml['html'] .= '    <div class="al-price">';
                                if ($value['field_type'] == 'tuan_module'){
                                    $price_name = $val['price_name'];
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $price_name = $config['price_name'];
                                } else {
                                    $price_name = $config['price_name'];
                                }

                                $fieldHtml['html'] .= '        <p class="al-price-prefix">'.$price_name.':'.C('config.Currency_symbol').'</p>';
                                $fieldHtml['html'] .= '        <p class="al-current-price">'.$val['floor_price'].' </p>';
                                if ($config['original_price_show'] == 'true') {
                                    $fieldHtml['html'] .= '        <p class="al-original-price"> '.C('config.Currency_symbol').$val['price'].'</p>';
                                }
                                $fieldHtml['html'] .= '    </div>';


                                $fieldHtml['html'] .= '<div>';
                                if ($config['remain_sku_show'] == 'true') {
                                    $stock_num = intval($val['stock_num']) - intval($val['sell_count']);
                                    if ($val['stock_num']==-1) {
                                        $remain_txt = '库存充足';
                                    } elseif ($stock_num<=0) {
                                        $remain_txt = '库存不足';
                                    } elseif ($val['unit']) {
                                        $remain_txt = '剩余'.$stock_num.$val['unit'];
                                    } else {
                                        $remain_txt = '剩余'.$stock_num.'件';
                                    }
                                    $fieldHtml['html'] .= '<div class="al-remain-sku">'.$remain_txt.'</div>';
                                }

                                $fieldHtml['html'] .= '    <div class="al-buy-btn al-buy-btn-style-'.$config['buy_btn_style'].'">';
                                $fieldHtml['html'] .= '' . $config['buy_btn_name'];
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</a>';
                            }
                        } elseif ($config['list_display_style'] == 5) {
                            foreach ($activities as $val) {
                                // 跳转页面路劲
                                if ($value['field_type'] == 'tuan_module'){
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&group_id={$val['id']}";
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}&bar_id={$val['id']}";
                                } elseif ($value['field_type'] == 'seckill_module') {
                                    $url = $site_url . "/wap.php?g=Wap&c=Mall&a=detail&goods_id={$val['goods_id']}";
                                }

                                $fieldHtml['html'] .= '<a class="al-item clearfix" href="'.$url.'">';
                                $fieldHtml['html'] .= '<div class="al-left">';

                                $fieldHtml['html'] .= '    <div class="al-main-img al-main-image-size-style-'.$config['main_image_size_style']. ' al-main-image-fill-style-'. $config['main_image_fill_style'].'" style="background:url('.$val['image'].') center center / '.$backgroundSize.'  no-repeat">';
                                $fieldHtml['html'] .= '    </div>';

                                $fieldHtml['html'] .= '    <div class="al-board">';
                                if ($config['time_count_show'] == 'true') {
                                    $fieldHtml['html'] .= '    <div class="al-time-count timer_info_'.$val['id'].'">';
                                    $fieldHtml['html'] .= '<time-count-down data-id="'.$val['id'].'" data-stime="'.$val['stime'].'" data-ftime="'.$val['ftime'].'"></time-count-down>';
                                    $fieldHtml['html'] .= '    </div>';
                                }
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '</div>';

                                $fieldHtml['html'] .= '<div class="al-right">';
                                if ($config['name_show'] == 'true') {
                                    $title_name = $val['title'] ? $val['title'] : $val['name'];
                                    $fieldHtml['html'] .= '<div class="al-good-name">';
                                    $fieldHtml['html'] .= '<p>'.$title_name.'</p>';
                                    $fieldHtml['html'] .= '</div>';
                                }

                                $fieldHtml['html'] .= '<div class="al-action-list">';
                                $fieldHtml['html'] .= '    <div class="al-price">';
                                if ($value['field_type'] == 'tuan_module'){
                                    $price_name = $val['price_name'];
                                } elseif ($value['field_type'] == 'bargain_module') {
                                    $price_name = $config['price_name'];
                                } else {
                                    $price_name = $config['price_name'];
                                }

                                $fieldHtml['html'] .= '        <p class="al-price-prefix">'.$price_name.':'.C('config.Currency_symbol').'</p>';
                                $fieldHtml['html'] .= '        <p class="al-current-price">'.$val['floor_price'].' </p>';
                                if ($config['original_price_show'] == 'true') {
                                    $fieldHtml['html'] .= '        <p class="al-original-price"> '.C('config.Currency_symbol').$val['price'].'</p>';
                                }
                                $fieldHtml['html'] .= '    </div>';


                                $fieldHtml['html'] .= '<div>';
                                if ($config['remain_sku_show'] == 'true') {
                                    $stock_num = intval($val['stock_num']) - intval($val['sell_count']);
                                    if ($val['stock_num']==-1) {
                                        $remain_txt = '库存充足';
                                    } elseif ($stock_num<=0) {
                                        $remain_txt = '库存不足';
                                    } elseif ($val['unit']) {
                                        $remain_txt = '剩余'.$stock_num.$val['unit'];
                                    } else {
                                        $remain_txt = '剩余'.$stock_num.'件';
                                    }
                                    $fieldHtml['html'] .= '<div class="al-remain-sku">'.$remain_txt.'</div>';
                                }

                                $fieldHtml['html'] .= '    <div class="al-buy-btn al-buy-btn-style-'.$config['buy_btn_style'].'">';
                                $fieldHtml['html'] .= '' . $config['buy_btn_name'];
                                $fieldHtml['html'] .= '    </div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</div>';
                                $fieldHtml['html'] .= '</a>';
                            }
                        }


                        $fieldHtml['html'] .= '</div>';

                        $fieldHtml['html'] .= '        </div>';
                        $fieldHtml['html'] .= '    </div>';
                        $fieldHtml['html'] .= '</div>';
                        $fieldHtml['html'] .= '</div>';





                    }
                    break;

                case 'goods_group3':
                    fdump($value,'$fieldContent_goods_group3');


                    $merchant_store_info = D('Merchant_store')->where(array('store_id' => $seller_id))->find();
                    $merchant_store_shop_info = D('Merchant_store_shop')->where(array('store_id' => $seller_id))->find();
                    $business_time = D('Merchant_store_shop')->getBuniessName($merchant_store_info);
                    $now_mer = M('Merchant')->field('isverify')->where(array('mer_id'=>$merchant_store_info['mer_id']))->find();
                    $currency_txt = C('config.Currency_txt');
                    $currency_symbol = C('config.Currency_symbol');
                    $auth_files = array();
                    if (!empty($merchant_store_info['auth_files'])) {
                        $auth_file_class = new auth_file();
                        $tmp_pic_arr = explode(';', $merchant_store_info['auth_files']);
                        foreach($tmp_pic_arr as $key => $value){
                            $auth_files[] = $auth_file_class->get_image_by_path($value, 'm');//array('title' => $value, 'url' => $auth_file_class->get_image_by_path($value, 's'));
                        }
                    }
                    $auth_files_str = implode(',', $auth_files);

                    // 单页面中外卖模块具有唯一性和永久置底性

                    // 获取对应

                    if ($fieldContent && $fieldContent['goods_group3']) {
                        $shop_html .= '<div id="goods_group3" class="pageDiv nowPage" style="width: 375px; display: block;">';
                        if ($fieldContent['notice_content']) {
                            $shop_html .= '    <section id="shopBanner" style="margin-top:0px;">';
                            $shop_html .= '        <div class="discount">';
                            $shop_html .= '            <div class="noticeBox"><div class="notice">'.$fieldContent['notice_content'].'<div></div></div></div>';
                            $shop_html .= '        </div>';
                            $shop_html .= '    </section>';
                        }
                        if ($fieldContent['show_type']!=2) {
                            $shop_html .= '    <section id="shopMenuBar">';
                            $shop_html .= '        <ul>';
                            $shop_html .= '            <li class="caret product active" data-nav="product">商品</li>';
                            $shop_html .= '            <li class="caret merchant" data-nav="merchant">商家详情</li>';
                            $shop_html .= '        </ul>';
                            $shop_html .= '    </section>';
                        }



                        $shop_html .= '    <section id="shopContentBar">';
                        $shop_html .= '        <div id="shopProductBox">';
                        // 左边商品分组
                        $shop_html .= '            <div id="shopProductLeftBar2">';
                        $shop_html .= '            <dl>';

                        $goods_list = array();
                        $group_name = '';
                        foreach ($fieldContent['goods_group3'] as $key => &$val) {
                            if ($key==0) {
                                $class = 'class="active"';
                                $goods_list = M('Shop_goods_group_list')->field('l.id,l.goods_id,g.*')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->where(['l.group_id' => $val['id']])->select();
                                $group_name = $val['title'];

                            } else {
                                $class = '';
                            }
                            $shop_html .= '            <dd id="shopProductLeftBar2-'.$val['id'].'" data-cat_id="'.$val['id'].'" '.$class.'>';
                            $shop_html .= '            <span data-sort_id="'.$val['id'].'">'.$val['title'].'</span> ';
                            $shop_html .= '            </dd>';
                        }
                        $shop_html .= '            </dl>';
                        $shop_html .= '            </div>';

                        // 右边对应商品分组的商品
                        $shop_html .= '            <div id="shopProductRightBar2" style="width: 275px;">';
                        $shop_html .= '            <dl>';
                        $shop_html .= '            <dd id="shopProductRightBar2-'.$val['id'].'" data-cat_id="'.$val['id'].'">';
                        $shop_html .= '            <div class="cat_name">'.$group_name.'　</div>';
                        $shop_html .= '            <ul>';

                        if (empty($goods_list)) {
                            $shop_html .= '<li>暂无数据</li>';
                        } else {
                            $now_time = time();
                            $goods_image_class = new goods_image();
                            foreach ($goods_list as $item) {
                                if ($item['seckill_open_time'] < $now_time && $now_time < $item['seckill_close_time'] && floatval($item['seckill_price']) > 0 && $item['seckill_stock'] != 0) {
                                    $is_seckill = true;
                                } else {
                                    $is_seckill = false;
                                }
                                $uid && $item['user_buy_num']  = D('Shop_goods')->getBuyGoodsNum($uid,$item['goods_id']);
                                if (empty($item['user_buy_num'])) {
                                    $item['user_buy_num'] = 0;
                                }

                                $tmp_pic_arr = explode(';', $item['image']);

                                foreach ($tmp_pic_arr as $key => $value) {
                                    $item['pic_arr'][$key]['title'] = $value;
                                    if('http' === substr($value,0,4)){
                                        $item['pic_arr'][$key]['url'] = $value;
                                    }else{
                                        $item['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 'm');
                                    }
                                }
                                $item['pic'] = reset($item['pic_arr'])['url'];



                                $shop_html .= '<li class="product_'.$item['goods_id'].'" data-unit="'.$item['unit'].'" data-product_id="'.$item['goods_id'].'" data-product_price="'.$item['price'].'" data-packing_charge="'.$item['packing_charge'].'" data-product_name="'.$item['name'].'" data-stock="'.$item['stock_num'].'" data-max_num="'.$item['max_num'].'" data-min_num="'.$item['min_num'].'" data-old_price="'.$item['old_price'].'" data-is_seckill="'.$is_seckill.'" data-limit_type="'.$item['limit_type'].'" data-user_buy_num="'.$item['user_buy_num'].'">';
                                $shop_html .= '<div class="position_img mall">';
                                $shop_html .= '    <img src="'.$item['pic'].'" style="width:60px;">';
                                $shop_html .= '</div>';
                                $shop_html .= '<div class="product_text">';
                                $shop_html .= '<div class="title">'.$item['name'].'</div>';
                                $shop_html .= '<div class="sale">已售'.$item['sell_count'].$item['unit'].' 好评'.$item['reply_count'].'</div>';


                                if ($item['spec_value'] || $item['is_properties']) {
                                    $shop_html .= '<div class="price">'.$currency_symbol.$item['price'];
                                    if ($item['spec_value']) {
                                        $shop_html .= '起';
                                    }
                                    $shop_html .= '</div>';
                                }else{
                                    $shop_html .= '<div class="price">'.$currency_symbol.$item['price'];
                                    if ($is_seckill) {
                                        $shop_html .= '<span>'.$currency_symbol.$item['o_price'].'</span>';
                                    }
                                    $shop_html .= '</div>';
                                }

                                if ($is_seckill) {
                                    $shop_html .= '<div class="skill_discount" style="margin-top: 5px;">每个用户限购20份</div>';
                                }
                                $shop_html .= '</div>';

                                if ($item['spec_value'] || $item['is_properties']) {
                                    $shop_html .= '<div class="product_btn">可选规格</div>';
                                } else {
                                    $shop_html .= '<div class="product_btn plus"></div>';
                                    $shop_html .= '<div class="bgPlusBack"></div>';
                                    $shop_html .= '<div class="bgMinBack"></div>';
                                }

                                $shop_html .= '</li>';

                            }
                        }


                        $shop_html .= '            </ul>';
                        $shop_html .= '            </dd>';
                        $shop_html .= '            </dl>';
                        $shop_html .= '            </div>';

                        $shop_html .= '            <div id="shopProductCartShade"></div>';

                        $shop_html .= '            <div id="top_fei" style="position: fixed;background-color: white;width: 100%;bottom: 50px;z-index: 126;overflow-y:auto; display: none;">';
                        $shop_html .= '            <div class="top_header" style="background: rgba(6, 193, 174, 0.31); text-align: center; color: rgb(57, 58, 58); width: 100%; height: 30px; line-height: 30px; display: none;">加<span style="color:#CB0003;" class="header_data">3</span>'.C('config.Currency_txt').'就能配送 <span style="color:#CB0003;">[确认加钱]</span></div>';
                        $shop_html .= '            <div id="shopProductCartBox"></div>';
                        $shop_html .= '            </div>';


                        $shop_html .= '            <div id="shopProductCart">';
                        $shop_html .= '                <div id="cartInfo" class="cartLeft" style="display: none;">';
                        $shop_html .= '                    <div class="cart">';
                        $shop_html .= '                        <div id="cartNumber">0</div>';
                        $shop_html .= '                    </div>';
                        $shop_html .= '                    <div class="price"><dl><dt>共' . C('config.Currency_symbol') . '<span id="cartMoney">0.00</span></dt><dd style="font-size: 12px;color: #979796;">另需起送价附加费<span id="additional">3</span>'.C('config.Currency_txt').'</dd></dl></div>';
                        $shop_html .= '                </div>';
                        $shop_html .= '                <div id="emptyCart" style="display: block;">';
                        $shop_html .= '                    <div class="cart"></div>购物车是空的';
                        $shop_html .= '                </div>';
                        $shop_html .= '                <div id="checkCart" style="display: none;">选好了</div>';
                        $shop_html .= '                <div id="checkCartEmpty" style="display: block;">0.00'.$currency_txt.'起送</div>';
                        $shop_html .= '            </div>';


                        $shop_html .= '        </div>';

                        $shop_html .= '            <div id="shopMerchantBox" style="width: 375px; left: 750px; height: 515px; overflow-y: auto;">';
                        $shop_html .= '            <dl id="shopMerchantDescBox">';
                        $shop_html .= '            <dd class="merchant more link-url" data-url="/wap.php?g=Wap&c=Index&a=index&token='.$merchant_store_info['mer_id'].'">商家官网</dd>';
                        $shop_html .= '            <dd class="phone more" data-phone="'.$merchant_store_info['phone'].'">店铺电话：'.$merchant_store_info['phone'].'</dd>';

                        $page_link1 = 'map&param='.$merchant_store_info['store_id'].'-'.$merchant_store_info['long'].'-'.$merchant_store_info['lat'].'-'.urlencode($merchant_store_info['name']).'-'.urlencode($merchant_store_info['adress']);

                        $shop_html .= '            <dd class="address more page-link" data-url="'.$page_link1.'"><span></span>店铺地址：'.$merchant_store_info['adress'].'</dd>';
                        $shop_html .= '            <dd class="openTime">营业时间：'.$business_time.'</dd>';


                        $delivery = $merchant_store_shop_info['deliver_type'] == 2 ? false : true;//是否支持配送
                        $delivery_system = ($merchant_store_shop_info['deliver_type'] == 0 || $merchant_store_shop_info['deliver_type'] == 3 )? true : false;//是否是平台配送
                        if ($delivery) {
                            $delivery_msg = $delivery_system ? '由 平台 提供配送' : '由 店铺 提供配送';
                        } else {
                            $delivery_msg = '本店铺仅支持门店自提';
                        }

                        $shop_html .= '            <dd class="deliveryType">配送服务: '.$delivery_msg.' </dd>';
                        $shop_html .= '            <dd class="merchantNotice">店铺公告：'.$merchant_store_shop_info['store_notice'].'</dd>';

                        if ($now_mer['isverify'] == 1) {
                            $shop_html .= '            <dd class="merchantVerify">店铺认证：已认证</dd>';
                        }

                        $shop_html .= '            </dl>';

                        $shop_html .= '            <dl id="shopMerchantCouponBox">';

                        if ($merchant_store_shop_info['is_invoice']) {
                            $shop_html .= '            <dd><em class="merchant_invoice"></em>满'.$merchant_store_shop_info['is_invoice'].$currency_txt.'支持开发票，请在下单时填写发票抬头</dd>';
                        }
                        if ($merchant_store_shop_info['store_discount'] > 0 && $merchant_store_shop_info['store_discount'] < 100) {
                            $disount = $merchant_store_shop_info['store_discount'] / 10;
                            $shop_html .= '            <dd><em class="merchant_discount"></em>店内全场'.$disount.'折</dd>';
                        }

                        $discounts = D('Shop_discount')->getDiscounts($merchant_store_info['mer_id'], $seller_id);
                        $coupon_list = array();
                        $system_delivery = array();
                        if (isset($discounts[0]) && $discounts[0]) {
                            foreach ($discounts[0] as $row_d) {
                                if ($row_d['type'] == 0) {//新单
                                    $coupon_list['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                                } elseif ($row_d['type'] == 1) {//满减
                                    $coupon_list['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                                } elseif ($row_d['type'] == 2) {//配送
                                    $system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                                }
                            }
                        }
                        if (isset($discounts[$store_id]) && $discounts[$store_id]) {
                            foreach ($discounts[$store_id] as $row_m) {
                                if ($row_m['type'] == 0) {
                                    $coupon_list['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                                } elseif ($row_m['type'] == 1) {
                                    $coupon_list['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                                }
                            }
                        }
                        if ($delivery && $delivery_system) {
                            $coupon_list['delivery'] = $system_delivery;
                        }
                        if ($coupon_list) {
                            if ($coupon_list['minus']) {
                                $tip_arr = array();
                                foreach ($coupon_list['minus'] as $val) {
                                    $tip_arr[]= '满' . $val['money'] .$currency_txt. '减' . $val['minus'] .$currency_txt;
                                }
                                $tip_msg = '店铺优惠' . implode(',', $tip_arr);
                                $shop_html .= '            <dd><em class="merchant_minus"></em>'.$tip_msg.'</dd>';
                            }
                            if ($coupon_list['newuser']) {
                                $tip_arr = array();
                                foreach ($coupon_list['newuser'] as $val1) {
                                    $tip_arr[]= '满' . $val1['money'] .$currency_txt. '减' . $val1['minus'] .$currency_txt;
                                }
                                $tip_msg = '店铺首单' . implode(',', $tip_arr);
                                $shop_html .= '            <dd><em class="newuser"></em>'.$tip_msg.'</dd>';
                            }
                            if ($coupon_list['delivery']) {
                                $tip_arr = array();
                                foreach ($coupon_list['delivery'] as $val2) {
                                    $tip_arr[]= '满' . $val2['money'] .$currency_txt. '减' . $val2['minus'] .$currency_txt;
                                }
                                $tip_msg = '配送费' . implode(',', $tip_arr);
                                $shop_html .= '            <dd><em class="delivery"></em>'.$tip_msg.'</dd>';
                            }
                            if ($coupon_list['system_minus']) {
                                $tip_arr = array();
                                foreach ($coupon_list['system_minus'] as $val3) {
                                    $tip_arr[]= '满' . $val3['money'] .$currency_txt. '减' . $val3['minus'] .$currency_txt;
                                }
                                $tip_msg = '平台优惠' . implode(',', $tip_arr);
                                $shop_html .= '            <dd><em class="system_minus"></em>'.$tip_msg.'</dd>';
                            }
                            if ($coupon_list['system_newuser']) {
                                $tip_arr = array();
                                foreach ($coupon_list['system_newuser'] as $val4) {
                                    $tip_arr[]= '满' . $val4['money'] .$currency_txt. '减' . $val4['minus'] .$currency_txt;
                                }
                                $tip_msg = '平台首单' . implode(',', $tip_arr);
                                $shop_html .= '            <dd><em class="system_newuser"></em>'.$tip_msg.'</dd>';
                            }
                        }
                        $shop_html .= '            </dl>';

                        // 获取图片信息
                        $store_image_class = new store_image();
                        $images = $store_image_class->get_allImage_by_path($merchant_store_info['pic_info']);
                        fdump($images,'$images');

                        if ($images) {
                            $images_str = implode(',', $images);
                            $shop_html .= '            <div class="photo_s">';
                            $shop_html .= '                <div class="photo">';
                            $shop_html .= '                <h2>商家照片</h2>';
                            $shop_html .= '                <ul class="clearfix store_image" data-pics="'.$images_str.'">';
                            $im_i = 0;
                            foreach ($images as $key => $item) {
                                if ($key < 4) {
                                    $shop_html .= '                <li><img src="'.$item.'" width="100%" height="100%"></li>';
                                }
                                $im_i++;
                            }
                            if ($im_i>3) {
                                $shop_html .= '                <li><span>更多</span></li>';
                            }

                            $shop_html .= '                </ul>';
                            $shop_html .= '                </div>';

                            $shop_html .= '                <div class="photo">';
                            $shop_html .= '                <h2>资质证照</h2>';
                            $shop_html .= '                <ul class="clearfix store_image" data-pics="'.$auth_files_str.'">';
                            $im_is = 0;
                            foreach ($auth_files as $keys => $items) {
                                if ($keys < 4) {
                                    $shop_html .= '                <li><img src="'.$items.'" width="100%" height="100%"></li>';
                                }
                                $im_is++;
                            }
                            if ($im_is>3) {
                                $shop_html .= '                <li><span>更多</span></li>';
                            }

                            $shop_html .= '                </ul>';
                            $shop_html .= '                </div>';



                            $shop_html .= '            </div>';
                        }

                        $shop_html .= '            </div>';

                        $shop_html .= '    </section>';

                        // $merchant_store_info
                        // $merchant_store_shop_info

                        fdump($fieldContent['goods_group3'],'msg');


                        $shop_html .= '<div id="pageLoadTipShade" class="pageLoadTipBg">';
                        $shop_html .= '    <div id="pageLoadTipBox" class="pageLoadTipBox">';
                        $shop_html .= '        <div class="pageLoadTipLoader">';
                        $shop_html .= '        <div style="background-image:url('.C('config.shop_load_bg').');"></div>';
                        $shop_html .= '        </div>';
                        $shop_html .= '    </div>';
                        $shop_html .= '</div>';

                        $shop_html .= '</div>';
                    }


                    break;
                case 'store_cate':
                    $shop_html = '<div class="app-field addCategoryBtn flex">';
                    foreach ($fieldContent['store_cate_arr'] as $key => $value) {
                        $store_ids = array_column($value['store_list'], 'id');
                        $shop_html .= '<span class="flex_one" '.(count($fieldContent['store_cate_arr'])==1 ? 'style="display:none;"' : '').' data-product="'.implode(',',$store_ids).'">'.$value['title'].'</span>';
                    }
                    $shop_html .= '</div><div class="productBox"></div>
    <div id="moress" style="text-align:center;padding:10px;">正在加载...</div>
    <div id="enddate" style="text-align:center;padding:10px;display:none;">没有更多数据了</div>';
                    break;


            }
            if ($shop_html) {
                // 单页面中外卖模块具有唯一性和永久置底性
                $fieldHtml['html'].= $shop_html;
            }
            $fieldHtmlArr[] = $fieldHtml;
        }
        return $fieldHtmlArr;
    }

    /**
     * app 微页面
     */
    public function getFields($store_id, $module_name, $module_id, $seller_id = 0, $drp_level = 0, $drp_diy_store = 1, $uid = 0) {
        if (empty($seller_id)) {
            $seller_id = $store_id;
        }
        if ($drp_level > 3) {
            $drp_level = 3;
        }

        // 查找所有自定义模块
        $field_list = $this->get_field($store_id, $module_name, $module_id,$seller_id);
        if (empty($field_list)) {
            return array();
        }

        $fx_store = array();
        $supplier_store = D('Store')->where(array('store_id' => $store_id))->find();
        if ($store_id == $seller_id) {
            $fx_store = $supplier_store;
        } else {
            $fx_store = D('Store')->where(array('store_id' => $seller_id))->find();
        }

        $return = array();
        // 对自定义数据模块进行处理
        foreach ($field_list as $key => $value) {
            if (empty($value['content']) && !in_array($value['field_type'], array('line', 'search', 'store', 'attention_collect'))) {
                continue;
            }
            unset($value['module_name']);

            $field = array();
            $field['field_type'] = $value['field_type'];
            $content = $value['content'];

            switch ($value['field_type']) {
                case 'rich_text' :
                    $field['content'] = htmlspecialchars_decode($content['content'], ENT_QUOTES);
                    $field['bgcolor'] = $content['bgcolor'] ? $content['bgcolor'] : '';
                    $field['screen'] = $content['screen'] ? $content['screen'] : 0;
                    break;
                case 'goods' :
                    $product_list = array();

                    foreach ($content['goods'] as $goods) {
                        $product = M('Shop_goods')->where(array('goods_id' => $goods['id'], 'status' => 1))->find();
                        if (empty($product)) {
                            continue;
                        }

                        $is_reservation = $product['is_reservation'];

                        $price = $product['price'];


                        $tmp = array();
                        $tmp['product_id'] = $product['product_id'];
                        $tmp['image'] = $product['image'];
                        $tmp['price'] = $price;
                        $tmp['name'] = htmlspecialchars_decode($product['name'], ENT_QUOTES);
                        $tmp['url'] = url('goods:index', array('id' => $product['product_id'], 'store_id' => $seller_id));
                        $tmp['store_id'] = $seller_id;

                        $product_list[] = $tmp;
                        unset($product);
                    }

                    if (!isset($value['content']['size'])) {
                        $value['content']['size'] = 0;
                    }

                    if (!isset($value['content']['size_type'])) {
                        $value['content']['size_type'] = 0;
                    }

                    if (!isset($value['content']['show_title'])) {
                        $value['content']['show_title'] = 0;
                    }

                    if (!isset($value['content']['price'])) {
                        $value['content']['price'] = 0;
                    }

                    if (!isset($value['content']['buy_btn'])) {
                        $value['content']['buy_btn'] = 0;
                    }

                    unset($value['content']['goods']);
                    $value['content']['product_list'] = $product_list;
                    $field = $value;
                    break;
                case 'title' :
                    if (!isset($value['content']['show_method'])) {
                        $value['content']['show_method'] = 0;
                    }
                    if (!isset($value['content']['bgcolor'])) {
                        $value['content']['bgcolor'] = '#FFF';
                    }
                    $field = $value;
                    break;
                case 'text_nav' :
                    $content = array();
                    if (is_array($value['content'])) {
                        foreach ($value['content'] as $text_nav) {
                            unset($text_nav['name']);
                            unset($text_nav['prefix']);
                            if (!empty($text_nav['url']) && stripos($text_nav['url'], option('config.site_url')) !== false) {
                                $text_nav['url'] = $this->redirect_store($seller_id, $text_nav['url']);
                            } else if (empty($text_nav['url'])) {
                                $text_nav['url'] = 'javascript: void(0)';
                            }
                            $content[] = $text_nav;
                        }
                    }

                    $field['content'] = $content;
                    break;
                case 'link' :
                    $link_list = array();
                    if (is_array($value['content'])) {
                        foreach ($value['content'] as $link) {


                            if ($link['widget'] == 'pagecat') {
                                $wei_page_list = M('Wei_page')->getCategoryPageNumberList($link['id'], $link['number']);
                                foreach ($wei_page_list as $wei_page) {
                                    $tmp = array();
                                    $url = '';
                                    if (empty($drp_diy_store) && stripos($link['url'], option('config.site_url')) !== false) {
                                        $url = option('config.wap_site_url') . '/page.php?id=' . $wei_page['page_id'] . '&store_id=' . $seller_id;
                                    } else {
                                        $url = option('config.wap_site_url') . '/page.php?id=' . $wei_page['page_id'];
                                    }

                                    $tmp['name'] = htmlspecialchars_decode($wei_page['page_name'], ENT_QUOTES);
                                    $tmp['url'] = $url;

                                    $link_list[] = $tmp;
                                }
                            } else if ($link['widget'] == 'goodcat') {
                                $tmp['name'] = htmlspecialchars_decode($link['name'], ENT_QUOTES);
                                $tmp['url'] = option('config.wap_site_url') . '/goodcat.php?id=' . $link['id']. '&store_id=' . $seller_id;

                                $link_list[] = $tmp;
                            } else if ($link['type'] == 'link') {
                                $tmp['name'] = $link['name'];
                                $tmp['url'] = $link['url'];

                                $link_list[] = $tmp;
                            }
                        }
                    }
                    $field['link_list'] = $link_list;
                    break;
                case 'search' :
                    break;
                case 'line' :
                    break;
                case 'white' :
                    $field['height'] = $value['content']['height'];
                    break;
                case 'component' :
                    $field = $this->getFields($store_id, 'custom_page', $value['content']['id'], $seller_id, $drp_level, $drp_diy_store, $uid);
                    break;
                case 'store' :
                    $field['name'] = C('now_store.name');
                    $field['url'] = C('now_store.url');
                    break;
                case 'notice' :
                    $field['content'] = $value['content']['content'];
                    break;
                case 'goods_group1' :
                    $goods_group_list = array();
                    foreach ($value['content']['goods_group1'] as $goods_group) {
                        $product_group = D('Product_group')->where(array('group_id' => $goods_group['id']))->find();
                        if (empty($product_group)) {
                            continue;
                        }

                        $order = '';
                        if ($product_group['first_sort'] == 1) {
                            $order = 'p.pv DESC';
                        } else {
                            $order = 'p.sort DESC';
                        }

                        if ($product_group['second_sort'] == 0) {
                            $order .= ',`p`.`date_added` DESC';
                        } else if ($product_group['second_sort'] == 1) {
                            $order .= ',`p`.`date_added` ASC';
                        } else if ($product_group['first_sort'] != 1) {
                            $order .= ',`p`.`pv` DESC';
                        }

                        $group = array();
                        $group['title'] = $product_group['group_name'];
                        $limit = 200;
                        if ($goods_group['show_num']) {
                            $limit = $goods_group['show_num'];
                        }

                        $product_list = D('Product_to_group as pg')->join('Product as p ON p.product_id = pg.product_id')->where("pg.group_id = '" . $goods_group['id'] . "' AND p.store_id != 0 AND p.status = 1")->field('p.product_id, p.name, p.price, p.image, p.unified_price_setting, p.is_fx, p.drp_level_1_price, p.drp_level_2_price, p.drp_level_3_price, p.is_reservation')->limit($limit)->order($order)->select();

                        if (empty($product_list)) {
                            continue;
                        }

                        $product_tmp_list = array();
                        foreach ($product_list as $product) {
                            $price = $product['price'];
                            $is_reservation = $product['is_reservation'];
                            if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                $price = $product['drp_level_' . $drp_level . '_price'];
                                $is_reservation = 0;
                            }

                            $tmp = array();
                            $tmp['product_id'] = $product['product_id'];
                            $tmp['name'] = $product['name'];
                            $tmp['image'] = getAttachmentUrl($product['image']);
                            $tmp['price'] = $price;
                            $tmp['url'] = option('config.wap_site_url') .'/good.php?id=' . $product['product_id'] . '&store_id=' . $seller_id;
                            $tmp['is_reservation'] = $is_reservation;

                            $product_tmp_list[] = $tmp;
                        }


                        $group['product_list'] = $product_tmp_list;
                        $goods_group_list[] = $group;
                    }

                    $field['goods_group_list'] = $goods_group_list;
                    break;
                case 'goods_group2' :
                    $content = array();
                    $content['size'] = $value['content']['size'] + 0;
                    $content['size_type'] = $value['content']['size_type'] + 0;
                    $content['buy_btn'] = $value['content']['buy_btn'] + 0;
                    $content['buy_btn_type'] = $value['content']['buy_btn_type'] + 0;
                    $content['show_title'] = $value['content']['show_title'] + 0;
                    $content['price'] = $value['content']['price'] + 0;

                    $goods_group_list = array();
                    foreach ($value['content']['goods'] as $goods_group) {
                        $product_group = D('Product_group')->where(array('group_id' => $goods_group['id']))->find();
                        if (empty($product_group)) {
                            continue;
                        }

                        $order = '';
                        if ($product_group['first_sort'] == 1) {
                            $order = 'p.pv DESC';
                        } else {
                            $order = 'p.sort DESC';
                        }

                        if ($product_group['second_sort'] == 0) {
                            $order .= ',`p`.`date_added` DESC';
                        } else if ($product_group['second_sort'] == 1) {
                            $order .= ',`p`.`date_added` ASC';
                        } else if ($product_group['first_sort'] != 1) {
                            $order .= ',`p`.`pv` DESC';
                        }

                        $group = array();
                        $group['title'] = $product_group['group_name'];
                        $limit = 1000;
                        $product_list = D('Product_to_group as pg')->join('Product as p ON p.product_id = pg.product_id')->where("pg.group_id = '" . $goods_group['id'] . "' AND p.store_id != 0 AND p.status = 1")->field('p.product_id, p.name, p.price, p.image, p.unified_price_setting, p.is_fx, p.drp_level_1_price, p.drp_level_2_price, p.drp_level_3_price, p.is_reservation')->order($order)->limit($limit)->select();

                        if (empty($product_list)) {
                            continue;
                        }

                        $product_tmp_list = array();
                        foreach ($product_list as $product) {
                            $price = $product['price'];
                            $is_reservation = $product['is_reservation'];
                            if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                $price = $product['drp_level_' . $drp_level . '_price'];
                                $is_reservation = 0;
                            }

                            $tmp = array();
                            $tmp['product_id'] = $product['product_id'];
                            $tmp['name'] = $product['name'];
                            $tmp['image'] = getAttachmentUrl($product['image']);
                            $tmp['price'] = $price;
                            $tmp['url'] = option('config.wap_site_url') .'/good.php?id=' . $product['product_id'] . '&store_id=' . $seller_id;
                            $tmp['is_reservation'] = $is_reservation;

                            $product_tmp_list[] = $tmp;
                        }

                        $group['product_list'] = $product_tmp_list;
                        $goods_group_list[] = $group;
                    }
                    $content['goods_group_list'] = $goods_group_list;
                    $field['content'] = $content;
                    break;
                case 'goods_group3' :
                    $goods_group_list = array();
                    foreach ($value['content']['goods_group3'] as $goods_group) {
                        $product_group = D('Product_group')->where(array('group_id' => $goods_group['id']))->find();
                        if (empty($product_group)) {
                            continue;
                        }

                        $order = '';
                        if ($product_group['first_sort'] == 1) {
                            $order = 'p.pv DESC';
                        } else {
                            $order = 'p.sort DESC';
                        }

                        if ($product_group['second_sort'] == 0) {
                            $order .= ',`p`.`date_added` DESC';
                        } else if ($product_group['second_sort'] == 1) {
                            $order .= ',`p`.`date_added` ASC';
                        } else if ($product_group['first_sort'] != 1) {
                            $order .= ',`p`.`pv` DESC';
                        }

                        $group = array();
                        $group['title'] = $product_group['group_name'];
                        $limit = 200;
                        if ($goods_group['show_num']) {
                            $limit = $goods_group['show_num'];
                        }

                        $product_list = D('Product_to_group as pg')->join('Product as p ON p.product_id = pg.product_id')->where("pg.group_id = '" . $goods_group['id'] . "' AND p.store_id != 0 AND p.status = 1 AND p.supplier_id = 0")->field('p.product_id, p.name, p.price, p.image, p.unified_price_setting, p.is_fx, p.drp_level_1_price, p.drp_level_2_price, p.drp_level_3_price, p.is_reservation')->limit($limit)->order($order)->select();

                        if (empty($product_list)) {
                            continue;
                        }

                        $product_tmp_list = array();
                        foreach ($product_list as $product) {
                            $price = $product['price'];
                            $is_reservation = $product['is_reservation'];
                            if ($drp_level > 0 && !empty($product['unified_price_setting']) && $product['is_fx']) {
                                $price = $product['drp_level_' . $drp_level . '_price'];
                                $is_reservation = 0;
                            }

                            $tmp = array();
                            $tmp['product_id'] = $product['product_id'];
                            $tmp['name'] = $product['name'];
                            $tmp['image'] = getAttachmentUrl($product['image']);
                            $tmp['price'] = $price;
                            $tmp['url'] = option('config.wap_site_url') .'/good.php?id=' . $product['product_id'] . '&store_id=' . $seller_id;
                            $tmp['is_reservation'] = $is_reservation;

                            $product_tmp_list[] = $tmp;
                        }


                        $group['product_list'] = $product_tmp_list;
                        $goods_group_list[] = $group;
                    }

                    $field['show_type'] = $value['content']['show_type'] == 2 ? false : true;
                    $field['goods_group_list'] = $goods_group_list;
                    break;
                case 'tpl_shop' :
                    if (!isset($value['content']['shop_head_bg_img'])) {
                        $value['content']['shop_head_bg_img'] = '';
                    } else {
                        $value['content']['shop_head_bg_img'] = getAttachmentUrl($value['content']['shop_head_bg_img']);
                    }

                    if (!isset($value['content']['bgcolor'])) {
                        $value['content']['bgcolor'] = '#FFFFFF';
                    }

                    //全部已经上架的商品
                    $count = M('Shop_goods')->where(array('store_id' => $store_id,'status'=>1))->count();

                    if (empty($drp_diy_store)) {
                        $store = D('Store')->where(array('store_id' => $seller_id))->field("collect, logo, name")->find();
                    } else {
                        $store = D('Store')->where(array('store_id' => $store_id))->field("collect, logo, name")->find();
                    }
                    $collect = $store['collect'];

                    $logo = getAttachmentUrl($store['logo']) ? getAttachmentUrl($store['logo']) : getAttachmentUrl('/images/default_shop_2.jpg', false);
                    $count = ($count > 9999) ? '9999+' : $count;
                    $collect = ($collect > 9999) ? '9999+' : $collect;

                    $value['content']['shop_head_logo_img'] = $logo;
                    $value['content']['product_count'] = $count;
                    $value['content']['collect'] = $collect;
                    $value['content']['title'] = $value['content']['title'] ? $value['content']['title'] : $store['name'];
                    $value['content']['physical_title'] = '';
                    if ($_SESSION['physical_id']) {
                        $store_physical = D('Store_physical')->where(array('pigcms_id' => $_SESSION['physical_id']))->find();
                        $value['content']['physical_title'] = $store_physical['name'];
                    }


                    $field = $value;
                    break;
                case 'tpl_shop1' :
                    if (!isset($value['content']['shop_head_bg_img'])) {
                        $value['content']['shop_head_bg_img'] = '';
                    } else {
                        $value['content']['shop_head_bg_img'] = getAttachmentUrl($value['content']['shop_head_bg_img']);
                    }

                    if (!isset($value['content']['bgcolor'])) {
                        $value['content']['bgcolor'] = '#FFFFFF';
                    }

                    if (empty($drp_diy_store)) {
                        $store = D('Store')->where(array('store_id' => $seller_id))->field("attention_num as atten_num, logo, name")->find();
                    } else {
                        $store = D('Store')->where(array('store_id' => $store_id))->field("attention_num as atten_num, logo, name")->find();
                    }

                    $logo = getAttachmentUrl($store['logo']) ? getAttachmentUrl($store['logo']) : getAttachmentUrl('/images/default_shop_2.jpg', false);
                    $value['content']['shop_head_logo_img'] = $logo;
                    $value['content']['title'] = $value['content']['title'] ? $value['content']['title'] : $store['name'];
                    $value['content']['physical_title'] = '';
                    if ($_SESSION['physical_id']) {
                        $store_physical = D('Store_physical')->where(array('pigcms_id' => $_SESSION['physical_id']))->find();
                        $value['content']['physical_title'] = $store_physical['name'];
                    }

                    $field = $value;
                    break;
                case 'attention_collect' :
                    $where['store_id'] = $seller_id;
                    $store = D('Store')->field('collect, attention_num')->where($where)->find();

                    $field['collect'] = $store['collect'] + 0;
                    $field['attention_num'] = $store['attention_num'] + 0;
                    break;
                case 'image_nav' :
                    $content = array();
                    foreach ($value['content'] as $key => $tmp) {
                        unset($tmp['name']);
                        unset($tmp['prefix']);
                        if (!empty($tmp['url']) && stripos($tmp['url'], option('config.site_url')) !== false) {
                            $tmp['url'] = $this->redirect_store($seller_id, $tmp['url']);
                        } else if (empty($tmp['url'])) {
                            $tmp['url'] = 'javascript: void(0)';
                        }

                        $content[] = $tmp;
                    }
                    $field['content'] = $content;
                    break;
                case 'image_ad' :
                    $value['content']['image_type'] += 0;
                    $value['content']['image_size'] += 0;
                    foreach ($value['content']['nav_list'] as &$nav) {
                        unset($nav['name']);
                        unset($nav['prefix']);

                        if (!empty($nav['url']) && stripos($nav['url'], option('config.site_url')) !== false) {
                            $nav['url'] = $this->redirect_store($seller_id, $nav['url']);
                        } else if (empty($nav['url'])) {
                            $nav['url'] = 'javascript: void(0)';
                        }

                    }
                    $field = $value;
                    break;
                case 'article' :
                    $article_arr = $value['content']['activity_arr'];

                    $article_list = array();
                    $store_list = array();
                    foreach ($article_arr as $tmp) {
                        $article = D('Article')->where(array('id' => $tmp['id'], 'status' => 1))->find();
                        if (empty($article)) {
                            continue;
                        }

                        if (!isset($store_list[$article['store_id']])) {
                            $store_tmp = D('Store')->where(array('store_id' => $article['store_id']))->field('store_id, name, logo')->find();
                            if (empty($store_tmp)) {
                                continue;
                            }
                            $store_tmp['logo'] = $store_tmp['logo'] ? getAttachmentUrl($store_tmp['logo']) : getAttachmentUrl('images/default_shop.png', false);
                            $store_list[$store['store_id']] = $store_tmp;
                        }

                        $product = D('Product')->where(array('product_id' => $article['product_id'], 'store_id' => $article['store_id'], 'status' => 1))->field('product_id, name, image, price')->find();
                        if (empty($product)) {
                            continue;
                        }

                        $article_data = array();
                        $article_data['pigcms_id'] = $article['id'];
                        $article_data['store_id'] = $article['store_id'];
                        $article_data['product_id'] = $article['product_id'];
                        $article_data['product_name'] = $product['name'];
                        $article_data['product_image'] = getAttachmentUrl($product['image']);
                        $article_data['store_name'] = $store_list[$article['store_id']]['name'];
                        $article_data['store_logo'] = $store_list[$article['store_id']]['logo'];
                        $article_data['time'] = getHumanTime(time() - $article['dateline']) . L_('前');
                        $count = D('My_collection_article')->where(array('article_id' => $tmp['id']))->count('id');
                        $is_collect = D('My_collection_article')->where(array('article_id' => $tmp['id'], 'uid' => $uid))->find();
                        $article_data['collect'] = $count;
                        $article_data['mycollected'] = $is_collect ? 1 : 0;
                        if (!empty($article['pictures'])) {
                            $article_data['picture_list'] = explode(',', $article['pictures']);
                            foreach ($article_data['picture_list'] as &$picture) {
                                $picture = getAttachmentUrl($picture);
                            }
                        } else {
                            $article_data['picture_list'] = array();
                        }

                        $article_list[] = $article_data;
                    }

                    $value['content']['activity_arr'] = $article_list;
                    $field['content'] = $value['content'];
                    break;
                case 'coupons' :
                    $content = array();
                    foreach ($value['content']['coupon_arr'] as $coupon) {
                        $coupon['coupon_id'] = $coupon['id'];
                        unset($coupon['id']);
                        $content[] = $coupon;
                    }
                    $field['content'] = $content;
                    break;
                case 'subject_menu' :
                    $content = array();
                    foreach ($value['content']['subtype_list'] as $subtype) {
                        $subtype['url'] = option('config.wap_site_url') . '/subtype.php?id=' . $seller_id . '&sid=' . $subtype['id'];
                        $subtype['sid'] = $subtype['id'];
                        unset($subtype['id']);
                        $content[] = $subtype;
                    }
                    $field['content'] = $content;
                    break;
                case 'subject_display' :
                    $first_hour = max(0, $value['content']['hour']);
                    $pre_hour = max(1, $value['content']['update_hour']);
                    $day = max(3, $value['content']['day']);
                    $number = max(1, $value['content']['number']);

                    $hour = date('G');
                    $content = array();
                    $next_hour = 0;
                    if ($hour < $first_hour) {
                        $next_hour = $first_hour;
                    } else {
                        $add_step = 0;
                        if (($hour - $first_hour) % $pre_hour == 0) {
                            $add_step = 1;
                        }

                        $next_hour = max(1, ceil(($hour - $first_hour) / $pre_hour) + $add_step) * $pre_hour + $first_hour;
                        if ($next_hour > 24) {
                            $next_hour = $first_hour;
                        }
                    }

                    $content['next_update'] = $next_hour . ':00';
                    $week_arr = array(L_("日"), L_("一"), L_("二"), L_("三"), L_("四"), L_("五"), L_("六"));

                    $subject_list = array();

                    $time1 = mktime(0, 0, 0, date('m'), date('d'));
                    $time2 = mktime(23, 59, 59, date('m'), date('d'));

                    for($i = 0; $i < $day; $i++) {
                        $start_time = $time1 - $i * 24 * 3600;
                        $end_time = $time2 - $i * 24 * 3600;

                        $data = array();
                        $data['date'] = date('m-d', $start_time) . ' 星期' . $week_arr[date('w', $start_time)];

                        $subject_tmp_list = D('Subject AS s')->join("Store_subject_data AS ssd ON s.id = ssd.subject_id AND ssd.store_id = '" . $seller_id . "'")->field('s.*, ssd.dz_count')->where("s.show_index = 1 AND s.store_id = '" . $store_id . "' AND timestamp >= '" . $start_time . "' AND timestamp <= '" . $end_time . "'")->limit($number)->order('s.id DESC')->select();
                        if (empty($subject_tmp_list)) {
                            continue;
                        }

                        foreach ($subject_tmp_list as $subject) {
                            $tmp = array();
                            $tmp['sid'] = $subject['id'];
                            $tmp['name'] = $subject['name'];
                            $tmp['pic'] = getAttachmentUrl($subject['pic']);
                            $tmp['url'] = option('config.wap_site_url') . '/subinfo.php?store_id=' . $seller_id . '&subject_id=' . $subject['id'];
                            $tmp['dz_count'] = $subject['dz_count'] + 0;
                            $tmp['is_dz'] = 0;
                            $tmp['store_id'] = $seller_id;
                            if ($uid) {
                                $count = D('User_collect')->where(array('type' => 3, 'dataid' => $subject['id'], 'store_id' => $seller_id))->count('collect_id');
                                if ($count) {
                                    $tmp['is_dz'] = 1;
                                }
                            }

                            $data['subject_list'][] = $tmp;
                        }
                        $content['subject_group_list'][] = $data;
                    }

                    $field['content'] = $content;
                    break;
                case 'new_activity_module':
                    $content = array();

                    // atype 1、预售，2、团购，3、夺宝，4、砍价，5、秒杀，6、众筹，7、降价拍
                    foreach ($value['content']['activity_arr'] as $activity) {
                        if (empty($activity['id'])) {
                            continue;
                        }
                        if ($activity['atype'] == 1) {
                            $presale = D('Presale')->where("id = '" . $activity['id'] . "'")->find();

                            if (empty($presale)) {
                                continue;
                            }

                            $product = D('Product')->where("product_id = '" . $presale['product_id'] . "'")->find();
                            $image = getAttachmentUrl($product['image']);

                            $order_count = M('Order')->getActivityOrderCount(7, $activity['id']);

                            $tmp = array();
                            $tmp['pigcms_id'] = $presale['id'];
                            $tmp['type'] = 1;
                            $tmp['store_id'] = $presale['store_id'];
                            $tmp['image'] = $image;
                            $tmp['name'] = $presale['name'];
                            $tmp['dingjin'] = $presale['dingjin'];
                            $tmp['presale_person'] = $presale['presale_person'] + $order_count;
                            $tmp['tips'] = L_('预售');
                            $tmp['wap_url']  = option('config.wap_site_url') . '/presale.php?id='. $presale['id'];

                            $content[] = $tmp;
                        } else if ($activity['atype'] == 2) {
                            $tuan = D('Tuan')->where("id = '" . $activity['id'] . "'")->find();

                            if (empty($tuan)) {
                                continue;
                            }

                            $product = D('Product')->where("product_id = '".$tuan['product_id']."'")->find();
                            $tuan_person = M('Order')->getActivityOrderCount(6, $tuan['id']);
                            $image = getAttachmentUrl($product['image']);

                            $tmp = array();
                            $tmp['pigcms_id'] = $tuan['id'];
                            $tmp['type'] = 2;
                            $tmp['store_id'] = $tuan['store_id'];
                            $tmp['image'] = $image;
                            $tmp['name'] = $tuan['name'];
                            $tmp['start_price'] = $tuan['start_price'];
                            $tmp['price'] = $tuan['price'];
                            $tmp['person'] = $tuan_person;
                            $tmp['tips'] = L_('团购');
                            $tmp['wap_url']  = option('config.site_url') . '/webapp/groupbuy/#/details/' . $tuan['id'];

                            $content[] = $tmp;
                        } else if ($activity['atype'] == 3) {
                            $unitary = D('Unitary')->where("id= '" . $activity['id'] . "'")->find();
                            if (!empty($unitary)) {
                                $product = D('Product')->where("product_id='" . $unitary['product_id'] . "'")->find();
                                $unitary_person = D('Unitary_lucknum')->where(array('unitary_id' => $unitary['id']))->count('id');
                                $image = getAttachmentUrl($product['image']);

                                $per = round($unitary_person / $unitary['total_num'] * 100, 2);

                                $tmp = array();
                                $tmp['pigcms_id'] = $unitary['id'];
                                $tmp['type'] = 3;
                                $tmp['store_id'] = $unitary['store_id'];
                                $tmp['image'] = $image;
                                $tmp['name'] = $unitary['name'];
                                $tmp['item_price'] = $unitary['item_price'];
                                $tmp['price'] = $product['price'];
                                $tmp['per'] = $per;
                                $tmp['person'] = $tuan_person;
                                $tmp['tips'] = L_('夺宝');
                                $tmp['wap_url']  = option('config.site_url') . '/webapp/snatch/#/main/' . $activity['id'];

                                $content[] = $tmp;
                            }
                        } else if ($activity['atype'] == 4) {
                            $bargain = D('')-> table("Bargain b")->join("Product p On b.product_id = p.product_id", "LEFT")-> where("b.pigcms_id='" . $activity['id'] . "'")-> field("b.*, p.name as product_name, p.image")-> find();
                            if (empty($bargain)) {
                                continue;
                            }

                            $image = getAttachmentUrl($bargain['image']);
                            $bargain_person = D('Bargain_kanuser')->where(array('bargain_id' => $activity['id']))->field('count(DISTINCT(token)) AS count')->find();

                            $tmp = array();
                            $tmp['pigcms_id'] = $bargain['pigcms_id'];
                            $tmp['type'] = 4;
                            $tmp['store_id'] = $bargain['store_id'];
                            $tmp['image'] = $image;
                            $tmp['name'] = $bargain['product_name'];
                            $tmp['min_price'] = $unitary['minimum'] / 100;
                            $tmp['price'] = $product['price'] / 100;
                            $tmp['person'] = $bargain_person['count'];
                            $tmp['tips'] = L_('砍价');
                            $tmp['wap_url']  = option('config.wap_site_url') . '/bargain.php?action=detail&id=' . $bargain['pigcms_id'] . '&store_id=' . $bargain['store_id'];

                            $content[] = $tmp;
                        } else if ($activity['atype'] == 5) {
                            $seckill = D('Seckill')->where(array('pigcms_id' => $activity['id']))->find();
                            if (empty($seckill)) {
                                continue;
                            }

                            if ($seckill['sku_id']) {
                                $seckill = D('')->table("Seckill b")
                                    ->join("Product p On b.product_id = p.product_id", "LEFT")
                                    ->join("Product_sku sku On b.sku_id = sku.sku_id", "LEFT")
                                    ->where("b.pigcms_id = '" . $activity['id'] . "'")
                                    ->field("b.*, p.name as product_name, p.image, p.price as product_price")
                                    ->find();
                            } else if (empty($seckill['sku_id'])) {
                                $seckill = D('')->table("Seckill b")
                                    ->join("Product p On b.product_id = p.product_id", "LEFT")
                                    ->where("b.pigcms_id = '" . $activity['id'] . "'")
                                    ->field("b.*, p.name as product_name, p.image, p.price as product_price")
                                    ->find();
                            }

                            $image = getAttachmentUrl($seckill['image']);
                            $seckill_price = $seckill['seckill_price'] ? $seckill['seckill_price'] : $seckill['product_price'];
                            $seckill_person = D('Seckill_user')->where(array('seckill_id' => $activity['id']))->count('pigcms_id');

                            $tmp = array();
                            $tmp['pigcms_id'] = $seckill['pigcms_id'];
                            $tmp['type'] = 5;
                            $tmp['store_id'] = $seckill['store_id'];
                            $tmp['image'] = $image;
                            $tmp['name'] = $seckill['name'];
                            $tmp['seckill_price'] = $seckill_price;
                            $tmp['price'] = $seckill['product_price'];
                            $tmp['person'] = $seckill_person;
                            $tmp['tips'] = L_('秒杀');
                            $tmp['wap_url']  = option('config.wap_site_url') . '/seckill.php?seckill_id=' . $activity['id'];

                            $content[] = $tmp;
                        } else if ($activity['atype'] == 6) {
                            $zc_product = D('Zc_product')->where("product_id='" . $activity['id'] . "'")->find();
                            if (!empty($zc_product)) {
                                $image = getAttachmentUrl($zc_product['productImageMobile']);

                                $per = 100;
                                if (!empty($zc_product['amount'])) {
                                    $per = min(100, round($zc_product['collect'] / $zc_product['amount'] * 100, 2));
                                }

                                $tmp = array();
                                $tmp['pigcms_id'] = $zc_product['product_id'];
                                $tmp['type'] = 6;
                                $tmp['store_id'] = $zc_product['store_id'];
                                $tmp['image'] = $image;
                                $tmp['name'] = $zc_product['productName'];
                                $tmp['collect'] = $zc_product['collect'];
                                $tmp['per'] = $per;
                                $tmp['tips'] = L_('众筹');
                                $tmp['wap_url']  = option('config.site_url') . '/webapp/chanping/index.html#/view/' . $activity['id'];

                                $content[] = $tmp;
                            }
                        } else if ($activity['atype'] == 7) {
                            $where = array();
                            $where['pigcms_id'] = $activity['id'];
                            $where['starttime'] = array('<=', time());
                            $where['endtime'] = array('>=', time());
                            $where['state'] = 0;
                            $cutprice = D('Cutprice')->where($where)->find();

                            if ($cutprice) {
                                // 降价拍
                                $product = D('Product')->where(array('product_id' => $cutprice['product_id']))->field('product_id,name,image,info')->find();
                                $images = getAttachmentUrl($product['image']);

                                // 已有多少人参与
                                $part_in = D('Cutprice_record')->where(array('cutprice_id' => $cutprice['pigcms_id']))->count('id');

                                $cha = time() - $cutprice['starttime'];
                                $cutprice['nowprice'] = $cutprice['startprice'];
                                if($cha > 0){
                                    $chaprice = (floor($cha / 60 / $cutprice['cuttime'])) * $cutprice['cutprice'];
                                    if($cutprice['inventory'] > 0 && ($cutprice['startprice'] - $chaprice) > $cutprice['stopprice']){
                                        $cutprice['nowprice'] = $cutprice['startprice'] - $chaprice;
                                    }
                                }

                                $tmp = array();
                                $tmp['pigcms_id'] = $cutprice['pigcms_id'];
                                $tmp['name'] = $cutprice['active_name'];
                                $tmp['image'] = $images;
                                $tmp['start_price'] = $cutprice['nowprice'];
                                $tmp['price'] = $cutprice['original'];
                                $tmp['presale_person'] = $part_in;
                                $tmp['tips'] = L_('降价拍');
                                $tmp['type'] = 7;
                                $tmp['wap_url'] = option('config.site_url') . '/wap/cutprice.php?action=detail&store_id=' . $cutprice['store_id'] . '&id=' . $cutprice['pigcms_id'];

                                $content[] = $tmp;
                            }
                        }
                    }

                    if (count($content) > 0 && count($content) % 2 == 1) {
                        $content = array_merge($content, $content);
                    }

                    $field['name'] = $value['content']['name'];
                    $field['content'] = $content;
                    $field['display'] = empty($value['content']['display']) ? 1 : 0;
                    break;
                case 'cube':
                    $content = array();
                    if (is_array($value['content']['content'])) {
                        $k = 0;
                        $y = 0;
                        foreach ($value['content']['content'] as $key => $tmp) {
                            unset($tmp['type']);
                            unset($tmp['prefix']);
                            unset($tmp['title']);

                            if ($tmp['y'] > $y) {
                                $y = $tmp['y'];
                                $k = $key;
                            }

                            $tmp['image'] = getAttachmentUrl($tmp['image']);
                            if (!empty($tmp['url']) && stripos($tmp['url'], option('config.site_url')) !== false) {
                                $tmp['url'] = $this->redirect_store($seller_id, $tmp['url']);
                            } else if (empty($tmp['url'])) {
                                $tmp['url'] = 'javascript: void(0)';
                            }

                            $content[] = $tmp;
                        }

                        $botton_content = $content[$k];
                        unset($content[$k]);
                        $content[] = $botton_content;
                    }
                    $content = array_merge($content);

                    $field['content'] = $content;
                    break;
                case 'map':
                    $store_contact = D('Store_contact')->where(array('store_id' => $store_id))->find();
                    if (empty($store_contact)) {
                        continue;
                    }

                    $area = new area();

                    $content = array();
                    $content['address'] = $store_contact['address'];
                    $content['lng'] = $store_contact['long'];
                    $content['lat'] = $store_contact['lat'];
                    $content['province'] = $area->get_name($store_contact['province']);
                    $content['city'] = $area->get_name($store_contact['city']);
                    $content['area'] = $area->get_name($store_contact['county']);
                    $content['store_name'] = $store_contact['entity_name'];// $fx_store['name'];
                    $content['entity_name'] = $store_contact['entity_name'];

                    $field['content'] = $content;
                    break;
                default :
                    $field = $value;
            }

            if ($value['field_type'] == 'component') {
                $return = array_merge_recursive ($return, $field);
            } else {
                $return[] = $field;
            }
        }

        $return_data = array();
        $goods_group_3 = array();
        foreach ($return as $tmp) {
            if ($tmp['field_type'] == 'goods_group3') {
                $goods_group_3 = $tmp;
            } else {
                $return_data[] = $tmp;
            }
        }
        if (!empty($goods_group_3)) {
            $return_data[] = $goods_group_3;
        }

        return $return_data;
    }



    //重定向店铺url
    public function redirect_store($store_id, $url = '') {
        return replace_domain($url);
    }


    /**
     * @desc获取店铺自定义页面的信息（接口）
     * @param $page_id
     * @return array
     */
    public function get_store_diypage_page($page_id,$now_store,$now_page,$uid=0){
        $where = array();
        $where['page_id'] = $page_id;
        $field_list = $this->where($where)->order('field_id asc')->select();
        $image_ad_index = 0;
        $show_sort = D('Shop_goods_sort')->get_show_sort($store_id);

        // 店铺分类放到最后
        foreach ($field_list as $key => $value) {
            if ($value['field_type']=='store_cate') {
                $store_cate_arr = $value;
                unset($field_list[$key]);
            }
        }
        if ($store_cate_arr) {
            $field_list[] = $store_cate_arr;
        }

        foreach($field_list as &$value){
            $value['content'] = unserialize($value['content']);
            switch ($value['field_type']) {
                case 'title':
                    // 标题
                    $value['content']['length'] = $this->dstrlen($value['content']['content'])/2;
                    if (!isset($value['content']['txtcolor'])) {
                        $value['content']['txtcolor'] = '';
                        $value['content']['txtcolor2'] = '';
                    }
                    break;
                // 富文本
                case 'rich_text':
                    $value['content']['content'] = str_replace('<img src="/upload/','<img src="'.file_domain().'/upload/',$value['content']['content']);
                    break;
                case 'notice':
                    // 公告
                    $value['content']['length'] = $this->dstrlen($value['content']['content'])/2;
                    break;
                case 'text_nav':
                    // 文本导航
                    $value['content'] = array_values($value['content']);
                    break;
                case 'link':
                    $value['content'] = array_values($value['content']);
                    break;
                case 'image_nav':
                    foreach($value['content'] as $k=>$v){
                        $value['content'][$k]['image'] = $this->replaceImg($v['image']);
                    }
                    $value['content'] = array_values($value['content']);
                    break;
                case 'image_ad':
                    foreach($value['content']['nav_list'] as $k=>$v){
                        $value['content']['nav_list'][$k]['index'] = $image_ad_index;
                        $value['content']['nav_list'][$k]['image'] = $this->replaceImg($v['image']);
                        $image_ad_index++;
                    }
                    $value['content']['nav_list'] = array_values($value['content']['nav_list']);
                    break;
                case 'tpl_shop':
                    $value['content']['shop_head_bg_img'] = $this->replaceImg($value['content']['shop_head_bg_img']);
                    $value['content']['shop_head_logo_img'] = $this->replaceImg($now_store['all_pic'][0]);
                    $value['content']['shop_url'] = $this->config['site_url'].'/wap.php?c=Mall&a=store&store_id='.$now_store['store_id'];
                    $value['content']['card_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_card&mer_id='.$now_page['mer_id'];
                    $value['content']['order_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_order&mer_id='.$now_page['mer_id'];
                    break;
                case 'coupons':
                    $tmpArr = [];
                    foreach($value['content']['coupon_arr'] as $k=>$v){
                        $tmpArr[] = $v['id'];
                    }
                    if ($now_store['mer_id']==C('config.kefu_mer_id')&&C('config.open_kefu_url')) {//自营店铺
                        $value['content']['coupon_arr'] = D('System_coupon')->get_coupon_list_by_ids($tmpArr);

                        $cate_platform = D('System_coupon')->cate_platform();
                        $couponArr = [];
                        $time = time();
                        foreach ($value['content']['coupon_arr'] as $k=>$v) {
                            // 是否领用过 可以去使用
                            $is_had = false;
                            // 是否可以领取
                            $can = true;
                            if($v['end_time']<$time||$v['start_time']>$time||$v['status']!=1||$v['had_pull']==$v['num']){
                                $can = false;
                            }elseif($uid>0){
                                $where=array();
                                $where['coupon_id']=$v['coupon_id'];
                                $where['uid']=$uid;
                                $receive_num=M('System_coupon_hadpull')->where($where)->count(1);
                                if($receive_num){
                                    $is_had=true;
                                }
                                if (isset($v['limit'])) {
                                    $where=array();
                                    $where['coupon_id']=$v['coupon_id'];
                                    $where['uid']=$uid;
                                    $receive_num=M('System_coupon_hadpull')->where($where)->count(1);
                                    if($receive_num>=$v['limit']){
                                        $can=false;
                                    }
                                }


                            }
                            $couponArr[]=array(
                                'id'=>$v['coupon_id'],
                                'name'=>$v['name'],
                                'order_money'=>floatval($v['order_money']),
                                'discount'=>floatval($v['discount']),
                                'is_discount'=>$v['is_discount'],
                                'discount_value'=>$v['discount_value'],
                                'can'=>$can,
                                'is_had'=>$is_had,
                                'img'=>replace_file_domain($v['img']),
                            );
                        }
                        $value['content']['coupon_arr'] = $couponArr;
                    }else{
                        $value['content']['coupon_arr'] = D('Card_new_coupon')->get_coupon_list_by_ids($tmpArr,true);
                    }
                    $value['content']['coupon_arr'] = array_values($value['content']['coupon_arr']);
                    break;
                case 'store_cate':

                    $parem['pageSize'] = 5;
                    $parem['lat'] = $_POST['lat'];
                    $parem['lng'] = isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : (isset($_POST['long']) ? htmlspecialchars($_POST['long']) : 0);
                    $parem['page'] = 1;
                    foreach ($value['content']['store_cate_arr'] as $k=>$v) {
                        $store_ids = array_column($v['store_list'], 'id');
                        $value['content']['store_cate_arr'][$k]['store_ids'] = implode(',', $store_ids);

                        unset($value['content']['store_cate_arr'][$k]['store_list']);
                        $parem['store_ids'] = implode(',', $store_ids);
                        $value['content']['store_cate_arr'][$k]['store_list'] = D('Merchant_store_shop')->getShopList($parem);
                        // 没有店铺不返回
                        if (empty($value['content']['store_cate_arr'][$k]['store_list']['store_list'])) {
                            unset($value['content']['store_cate_arr'][$k]);
                        }
                    }
                    $value['content']['store_cate_arr'] = array_values($value['content']['store_cate_arr']);
                    break;
            }
        }

        $field_list = array_values($field_list);
        return $field_list;
    }

    private function replaceImg($url){
        $url = str_replace('http://','https://',$url);
        if(substr($url,0,8) == '/upload/'){
            $url = file_domain().$url;
        }else if(substr($url,0,9) == './static/'){
            $url = $this->config['site_url'].ltrim($url,'.');
        }else{
            $url = replace_file_domain($url);
        }

        return $url;
    }

    public static function dstrlen($string){
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
        }
        return $noc;
    }
}

/*富文本内容，url替换*/
function richTextFormat($matches, $store_id) {
    return 'href="' . $matches . '"';
}


