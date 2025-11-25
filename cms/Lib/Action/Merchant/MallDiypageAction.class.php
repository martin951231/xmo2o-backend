<?php

class MallDiypageAction extends BaseAction
{
    public function mall_create()
    {
        $this->display();
    }

    public function create_ajax()
    {
        //初始化
        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['store_id'] = $_POST['store_id'];
        $condition_merchant_store['mer_id'] = $this->merchant_session['mer_id'];
        if ($this->merchant_session['store_id'] && $this->merchant_session['store_id'] != $condition_merchant_store['store_id']) {
            $this->error(L_('您没有这个权限'));
        }
        $this->now_store = $database_merchant_store->where($condition_merchant_store)->find();
        if (IS_POST) {
            if (empty($this->now_store)) {
                $this->error(L_('店铺不存在！'));
            }
            if (!empty($this->now_store['pic_info'])) {
                $store_image_class = new store_image();
                $tmp_pic_arr = explode(';', $this->now_store['pic_info']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $this->now_store['pic'][$key]['title'] = $value;
                    $this->now_store['pic'][$key]['url'] = $store_image_class->get_image_by_path($value);
                }
            }
        }
        $returnArr['now_store'] = $this->now_store;
        $store_id = $_POST['store_id'];
        if ($store_id) {
            $condition_page['store_id'] = $this->now_store['store_id'];
            $now_page = D('Mall_merchant_store_decorate')->where($condition_page)->find();
            if ($now_page) {
                $condition_page['page_id'] = $now_page['page_id'];
                $now_page_custom = D('Mall_merchant_store_decorate_field')->where($condition_page)->order('`field_id` ASC')->select();
                fdump($now_page_custom, '$now_page_custom');
                foreach ($now_page_custom as $key => $value) {
                    $content_arr = unserialize($value['content']);
                    $new_content_arr = $this->reg_image($content_arr);
                    $now_page_custom[$key]['content'] = $new_content_arr;
                    if ($value['field_type'] == 'rich_text') {
                        $now_page_custom[$key]['content'] = str_replace("font-family:'Microsoft YaHei';", '', $now_page_custom[$key]['content']);
                        $now_page_custom[$key]['content'] = str_replace("'allowfullscreen'", '"allowfullscreen"', $now_page_custom[$key]['content']);
                        $now_page_custom[$key]['content'] = str_replace("font-family:&quot;", '', $now_page_custom[$key]['content']);
                        $now_page_custom[$key]['content'] = str_replace("&quot;", '', $now_page_custom[$key]['content']);
                    }
                }
                $returnArr['now_page'] = $now_page;
                $returnArr['page_id'] = $now_page['page_id'];
                $returnArr['now_page_custom'] = $now_page_custom;
            }
        }
        echo json_encode($returnArr);
        exit;
    }

    //格式化图片格式
    public function reg_image($content_arr)
    {
        if (is_array($content_arr)) {
            foreach ($content_arr as $k => $v) {
                if (is_array($v)) {
                    $content_arr[$k] = $this->reg_image($v);
                } elseif ($k == 'image' || $k == 'img') {
                    $content_arr[$k] = replace_file_domain($v);
                } elseif ($k == 'content') {
                    $content_arr[$k] = replace_file_domain_content($v);
                }
            }
        }
        return $content_arr;
    }

    public function see_qrcode()
    {
        $this->display();
    }

    public function upload()
    {
        if ($_FILES['file']['error'] != 4) {
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'diypage', 1);

            if ($image['error']) {
                exit(json_encode($image));
            } else {
                if ($image['url']['imgFile'])
                    $url = $image['url']['imgFile'];
                else
                    $url = $image['url']['file'];
                $image = D('Image')->field(true)->where(array('pic_md5' => md5($url)))->find();

                exit(json_encode(array('error' => 0, 'pigcms_id' => $image['pigcms_id'], 'url' => $url, 'id' => $_POST['id'])));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => L_('没有选择图片'))));
        }
    }

    public function page_add()
    {
        $data_page['page_name'] = $_POST['page_name'];
        $data_page['page_desc'] = $_POST['page_desc'];
        $data_page['bgcolor'] = $_POST['bgcolor'];
        $data_page['page_goods_source'] = $_POST['page_goods_source'];
        $data_page['store_cate'] = $_POST['store_cate'];// 店铺分类
        $data_page['add_time'] = time();

        $param['mer_id'] = $this->merchant_session['mer_id'];
        $param['store_id'] = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $param['page_id'] = $_POST['page_id'];
        $param['page_name'] = $_POST['page_name'];
        $param['page_desc'] = $_POST['page_desc'];
        $param['bgcolor'] = $_POST['bgcolor'];
        $param['custom'] = $_POST['custom'];

        $data_page_field['mer_id'] = $param['mer_id'];
        $data_page_field['store_id'] = $param['store_id'];
        $data_page_field['page_id'] = $param['page_id'];
        $data_page['page_name'] = $param['page_name'];
        $data_page['page_desc'] = $param['page_desc'];
        $data_page['bgcolor'] = $param['bgcolor'];
        $data_page['create_time'] = time();
        $data_page['mer_id'] = $param['mer_id'];
        $data_page['store_id'] = $param['store_id'];
        // 启动事务
        M('')->startTrans();
        try {
            if (empty($param['page_id'])) {
                $result1 = M('Mall_merchant_store_decorate')->add($data_page);
                $param['page_id'] = $result1;
                $result2 = true;
            } else {
                $pageWhere = ['page_id' => $param['page_id']];
                $result1 = M('Mall_merchant_store_decorate')->where($pageWhere)->save($data_page);;
                //先删除之前的所有装修
                $pageFieldWhere = ['page_id' => $param['page_id']];
                $data_diypage_field['page_id'] = $param['page_id'];
                $result2 = M('Mall_merchant_store_decorate_field')->where($pageFieldWhere)->delete();
            }
            //添加新的装修
            foreach ($param['custom'] as $key => $val) {
                $data_page_field['field_type'] = $val['type'];
                $data_page_field['page_id'] = $param['page_id'];
                unset($param['custom'][$key]['type']);
                $data_page_field['content'] = serialize($param['custom'][$key]);
                $result3 = M('Mall_merchant_store_decorate_field')->add($data_page_field);
            }
            // 提交事务
            M('')->commit();
        } catch (\Exception $e) {
            // 回滚事务
            M('')->rollback();
        }
        if ($result1 === false || $result2 === false || $result3 === false) {
            $this->error(L_('操作失败，请重试'));
        } else {
            $this->success(L_('保存成功'));
        }

    }

    public function group()
    {
        $store_id = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $now_time = $_SERVER['REQUEST_TIME'];
        $where = "`store_id`='$store_id' AND`is_del`='0' AND `status`='1' AND `end_time`>'$now_time' AND `type`='group'";
        if ($_POST['keyword']) {
            $where .= "name` LIKE %" . $_POST['keyword'] . "%";
        }
        $count = D('mall_activity')->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 8);
        $group_list = D('mall_activity')->field('`id`,`name` AS `group_name`,`start_time`,`end_time`')->where($where)->order('`sort` DESC,`id` DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign(array('group_list' => $group_list, 'page_bar' => $Page->show()));
        $this->display();
    }

    public function activity_module()
    {
        $store_id = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $now_time = $_SERVER['REQUEST_TIME'];
        $where = "`store_id`='$store_id' AND`is_del`='0' AND `status`='1' AND `end_time`>'$now_time'";
        if ($_POST['keyword']) {
            $where .= "name` LIKE %" . $_POST['keyword'] . "%";
        }
        $count = D('mall_activity')->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 8);
        $group_list = D('mall_activity')->field('`id`,`name` AS `group_name`,`desc`')->where($where)->order('`sort` DESC,`id` DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page_bar', $Page->show());
        $this->assign('wxapp_list', $group_list);
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->display();
    }

    //选择页面
    public function page()
    {
        $database_diypage = D('Merchant_store_diypage');
        $condition_diypage = array('store_id' => $this->now_store['store_id']);
        if ($_POST['keyword']) {
            $condition_diypage['page_name'] = array('like', $_POST['keyword']);
        }
        $count_diypage = $database_diypage->where($condition_diypage)->count();
        import('@.ORG.diypage');
        $p = new Page($count_diypage, 8);

        $page_list = $database_diypage->where($condition_diypage)->order('`page_id` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('page_bar', $p->show());
        $this->assign('page_list', $page_list);
        $this->display();
    }

    //选择商品
    public function good()
    {
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->assign('keyword', $_POST['keyword']);
        $param['store_id'] = $_GET['store_id'];
        $param['selectedItems'] = $_GET['selecteditems'];
        $param['keyword'] = $_POST['keyword'];
        $this->assign(D('Mall_goods')->get_list_by_storeid_diypage($param));
        $this->display();
    }

    public function good_group_detail()
    {
        $store_id = $_POST['store_id'] ? $_POST['store_id'] : 0;
        $where['store_id'] = $store_id;
        $selecteditemsArr = $_POST['selecteditems'] ? explode(',', $_POST['selecteditems']) : [];
        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }
        $field = 'id,name';
        $order = 'id DESC';
        $count = M('Mall_goods_sort')->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 6);
        $arr = M('Mall_goods_sort')->field($field)->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if (!empty($arr)) {
            $count = 0;
            //获取叶子节点
            foreach ($arr as $key => $val) {
                $where = ['fid' => $val['id'], 'store_id' => $store_id];
                $son = M('Mall_goods_sort')->field($field)->where($where)->order($order)->select();;
                if (empty($son)) {
                    $list[] = $val;
                    $count++;
                }
            }
        }
        $where = array();
        $goods_group_goods_list = array();
        foreach ($list as $key => $value) {
            $where['l.group_id'] = $value['id'];
            $goods_list = M('Mall_goods_sort_list')->field('l.id,l.goods_id,g.name,g.image,g.price')->join(' as l left join ' . C('DB_PREFIX') . 'mall_goods as g on l.goods_id = g.goods_id')->where($where)->select();
            $goods_ids = array();
            $title = array();
            $image = array();
            $price = array();
            $goods_image_class = new goods_image();
            foreach ($goods_list as $_goods) {
                $goods_ids[] = $_goods['goods_id'];
                $title[] = $_goods['name'];

                $pic_arr = explode(';', $_goods['image']);
                $tmp_img = $goods_image_class->get_image_by_path($pic_arr[0]);
                $image[] = $tmp_img['m_image'];
                $price[] = $_goods['price'];
            }
            $goods_group_goods_list[$value['id']] = array(
                'id' => $value['id'],
                'goods_ids' => $goods_ids,
                'title' => $title,
                'image' => $image,
                'price' => $price,
                'group_info' => $value,
            );
        }
        $list['count'] = $count;
        fdump('$goods_group_goods_list', $goods_group_goods_list);
        echo json_encode($goods_group_goods_list);
        exit();
    }

    //选择商品分组
    public function good_group()
    {
        $store_id = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $where['store_id'] = $store_id;
        $selecteditemsArr = $_GET['selecteditems'] ? explode(',', $_GET['selecteditems']) : [];
        $this->assign('selecteditemsArr', $selecteditemsArr);
        if (isset($_GET['keyword']) && !$_POST['keyword']) {
            $_POST['keyword'] = $_GET['keyword'];
        }
        $this->assign('keyword', $_POST['keyword']);
        if ($_POST['keyword']) {
            $where['name'] = array('like', '%' . $_POST['keyword'] . '%');
        }
        $field = 'id,name';
        $order = 'id DESC';
        $count = M('Mall_goods_sort')->where($where)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 6);
        $arr = M('Mall_goods_sort')->field($field)->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if (!empty($arr)) {
            $count = 0;
            //获取叶子节点
            foreach ($arr as $key => $val) {
                $where = ['fid' => $val['id'], 'store_id' => $store_id];
                $son = M('Mall_goods_sort')->field($field)->where($where)->order($order)->select();;
                if (empty($son)) {
                    $list[] = $val;
                    $count++;
                }
            }
        }
        $goods_group_goods_list = array();
        $where = array();
        foreach ($list as $key => $value) {
            $where['l.group_id'] = $value['id'];
            $goods_list = M('Mall_goods_sort_list')->field('l.id,l.goods_id,g.name,g.image,g.price')->join(' as l left join ' . C('DB_PREFIX') . 'mall_goods as g on l.goods_id = g.goods_id')->where($where)->select();
            $goods_ids = array();
            $title = array();
            $image = array();
            $price = array();
            $goods_image_class = new goods_image();
            foreach ($goods_list as $_goods) {
                $goods_ids[] = $_goods['goods_id'];
                $title[] = $_goods['name'];

                $pic_arr = explode(';', $_goods['image']);
                $tmp_img = $goods_image_class->get_image_by_path($pic_arr[0]);
                $image[] = $tmp_img['m_image'];
                $price[] = $_goods['price'];
            }
            if ($selecteditemsArr && in_array($value['id'], $selecteditemsArr)) {
                $goods_group_list[$key]['is_choose'] = true;
                $is_choose = true;
            }
            $goods_group_goods_list[$value['id']] = array(
                'id' => $value['id'],
                'goods_ids' => $goods_ids,
                'title' => $title,
                'image' => $image,
                'price' => $price,
                'group_info' => $value,
            );
        }
        $list['count'] = $count;
        $this->assign('goods_list', json_encode($goods_group_goods_list));
        $this->assign('goods_group_list', $list);
        $this->assign('is_choose', $is_choose);
        $this->assign('page_bar', $Page->show());
        $this->display();
    }

    //选择图片
    public function imageList()
    {
        $search = isset($_POST['keyword']) ? $_POST['keyword'] : '';
        $condition = "`otype`=1 AND `oid`='{$this->merchant_session['mer_id']}' AND `status`=1";
        if ($search) $condition .= " AND `img_remark` LIKE '%{$search}%'";
        $count = D('Image')->where($condition)->count();
        import('@.ORG.diypage');
        $Page = new Page($count, 27);
        $image_list = D('Image')->where($condition)->order('`pigcms_id` DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($image_list as $k => $v) {
            $v['pic'] = replace_file_domain($v['pic']);
            $image_list[$k] = $v;
        }
        $return['count'] = $count;
        $return['image_list'] = $image_list;
        $return['page_bar'] = $Page->show();
        $this->success($return);
    }

    //选择优惠券
    public function coupon()
    {
        if ($_GET['type'] == 'system') {
            $couponList = D('System_coupon')->get_coupon_list_by_merid_diypage();
        } else {
            $couponList = D('Card_new_coupon')->get_coupon_list_by_merid_diypage($this->merchant_session['mer_id']);
        }
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign($couponList);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $this->display();
    }

    /**
     * 活动调取：1:秒杀     2：团购     3：砍价 4:周期购
     */
    public function activity_info()
    {
        $huodong_type = $_GET['huodong_type'] ? $_GET['huodong_type'] : 'limited';
        if($huodong_type == 'seckill'){
            $huodong_type = 'limited';
        }
        $hide_others = $_GET['hide_others'] == '1' ? true : false;
        $this->assign('hide_others', $hide_others);
        $this->assign('huodong_type', $huodong_type);
        $this->assign('page_target', $_GET['page_target']);
        $selecteditemsArr = explode(',', $_GET['selecteditems']);
        $this->assign('selecteditemsArr', $selecteditemsArr);
        $param['store_id'] = $_GET['store_id'] ? $_GET['store_id'] : 0;
        $param['act_type'] = $huodong_type;
        $param['selectedItems'] = $_GET['selecteditems'];
        $param['keyword'] = $_POST['keyword'];

        if (empty($param['store_id'])) {
            $this->error(L_('缺少store_id参数！'));
        }
        if (empty($param['act_type'])) {
            $this->error(L_('缺少act_type参数！'));
        }
        //获取活动表id
        $where = ['store_id' => $param['store_id'], 'status' => 1, 'is_del' => 0, 'type' => $param['act_type']];
        $field = 'id,act_id,start_time,end_time';
        $count = M('mall_activity')->field($field)->where($where)->count();
        import('@.ORG.diypage');
        $page = new Page($count, 6);
        $arr = M('mall_activity')->field($field)->where($where)->limit($page->firstRow,$page->listRows)->select();
        $gfield = 'g.goods_id,g.name';
        if (!empty($arr)) {
            foreach ($arr as $val) {
                $goodsDetail = M('mall_activity_detail')->where(['activity_id' => $val['id']])->field('goods_id')->find();
                $goods_id = $goodsDetail['goods_id'];
                $gwhere = ['g.status' => 1, 'g.is_del' => 0, 'g.goods_id' => $goods_id];
                if ($param['keyword']) {
                    $where['g.name'] = array('like', '%' . $param['keyword'] . '%');
                }
                $tmp_list = D('Mall_goods')->getActGoods($gwhere,$gfield);
                if (!empty($tmp_list)) {
                    if (in_array($tmp_list['goods_id'], explode(',', $param['selectedItems']))) {
                        $tmp_list['selected'] = 1;
                    } else {
                        $tmp_list['selected'] = 0;
                    }
                    $tmp_list['start_time'] = $val['start_time'];
                    $tmp_list['end_time'] = $val['end_time'];
                    $tmp_list['id'] = $val['id'];
                    $tmp_list['act_id'] = $val['act_id'];
                }
                $list[] = $tmp_list;
            }
        }
        $goods_image_class = new goods_image();
        $site_url = C('config.site_url');
        foreach ($list as $key => $value) {
            $tmp_pic_arr = explode(',', $value[0]['image']);
            foreach ($tmp_pic_arr as $k => $v) {
                $list[$key]['pic_arr'][$k]['title'] = $v;
                $list[$key]['pic_arr'][$k]['url'] = $goods_image_class->get_image_by_path($v);
                $list[$key]['image_url'] = $value['pic_arr'][$k]['url']['image'];
            }
            $url = $site_url . '/wap.php?g=Wap&c=Mall&a=detail&goods_id=' . $value["goods_id"];
            $list[$key]['link_url'] = $url;
        }
        $act_list = array('good_list' => $list, 'page_bar' => $page->show());
        switch ($huodong_type) {
            //团购
            case 'group':
                // 标识已选择过的活动
                $this->assign('group_list', $act_list['good_list']);
                $this->assign('group_page', $act_list['page_bar']);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $param['keyword']);
                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_group_ajax');
                } else {
                    $this->display('activity_group');
                }
                break;
            //砍价
            case 'bargain':
                // 标识已选择过的活动
                $this->assign('bargain_list', $act_list['good_list']);
                $this->assign('bargain_page', $act_list['page_bar']);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $param['keyword']);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_bargain_ajax');
                } else {
                    $this->display('activity_bargain');
                }
                break;
            //秒杀
            case 'limited':
                // 标识已选择过的活动
                $this->assign('limiteds', $act_list['good_list']);
                $this->assign('limited_page', $act_list['page_bar']);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $param['keyword']);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_limited_ajax');
                } else {
                    $this->display('activity_limited');
                }
                break;
            //周期购
            case 'periodic':
                // 标识已选择过的活动
                $this->assign('periodic_list', $act_list['good_list']);
                $this->assign('periodic_page', $act_list['page_bar']);
                $this->assign('now_store', $this->now_store);
                $this->assign('keyword', $param['keyword']);

                if ($_REQUEST['type'] == 'ajax') {
                    $this->display('activity_periodic_ajax');
                } else {
                    $this->display('activity_periodic');
                }
                break;
        }

    }

    public function select_store()
    {
        $province_id = I("province_id");
        $city_id = I("city_id");

        $selecteditemsArr = $_GET['selecteditems'] ? explode(',', $_GET['selecteditems']) : [];
        $this->assign('selecteditemsArr', $selecteditemsArr);

        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['have_shop'] = '1';
        $condition_merchant_store['status'] = '1';
        $where = "`s`.`store_id`=`ss`.`store_id` AND `s`.`status`='1' AND `s`.`have_shop`='1'";
        if ($province_id) {
            $where .= " and `s`.province_id=" . $province_id;
        }
        if ($city_id) {
            $where .= " and `s`.city_id=" . $city_id;
        }
        if ($_GET['search']) {
            $where .= " AND s.name like '%{$_GET['search']}%'";
        }
        $count_store = M()->table(array(C('DB_PREFIX') . 'merchant_store' => 's', C('DB_PREFIX') . 'merchant_store_shop' => 'ss'))->where($where)->count();

        import('@.ORG.diypage');
        $p = new Page($count_store, 8);

        $store_list = M()->field("`s`.`store_id`, `s`.`pic_info`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`,`ss`.`sale_count`,`ss`.`deliver_type`")->table(array(C('DB_PREFIX') . 'merchant_store' => 's', C('DB_PREFIX') . 'merchant_store_shop' => 'ss'))->where($where)->order("`s`.`sort` DESC,`s`.`store_id` ASC")->limit($p->firstRow . ',' . $p->listRows)->select();

        $store_image_class = new store_image();
        foreach ($store_list as &$value) {
            $tmp_pic_arr = explode(';', $value['pic_info']);
            $value['image'] = $store_image_class->get_image_by_path($tmp_pic_arr[0]);

            if ($selecteditemsArr && in_array($value['store_id'], $selecteditemsArr)) {
                $value['is_choose'] = true;
                $is_choose = true;
            }
            $value['url'] = C('config.site_url') . '/wap.php?g=Wap&c=Shop&a=index&shop-id=' . $value['store_id'];
        }

        $this->assign('store_list', $store_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }
}

?>