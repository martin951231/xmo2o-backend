<?php

/**
 * 新商家会原卡
 * User: 李俊
 * Date: 2016/8/17
 * Time: 11:21
 */
class Card_newAction extends BaseAction
{

    //实体卡编辑
    public function card_edit()
    {
        $mer_id = $this->merchant_session['mer_id'];

        if (IS_POST) {

            if (empty($_POST['support_score_select'])) {
                $_POST['support_score'] = 0;
            } elseif (!is_numeric($_POST['support_score']) || empty($_POST['support_score'])) {
                $this->error(L_('消费一元获得积分数必须是大于1的数字'));
            }

            if (!is_numeric($_POST['discount'])) {
                $this->error('请填写0到10的数字,0相当于不打折,比如95折 填写9.5即可');
            }

            if ($_POST['recharge_back_rule'] == 1 && !$_POST['support_score_select']) {
                $this->error(L_('积分支持已关闭，充值返现规则不能再选积分'));
            }

            if ($_POST['recharge_count']) {

                foreach ($_POST['recharge_count'] as $key => $v) {
                    if (empty($v)) continue;
                    $tmp['count'] = $v;
                    $tmp['back_money'] = $_POST['recharge_back_money'][$key];
                    $tmp['back_score'] = $_POST['recharge_back_score'][$key];

                    if (!is_numeric($tmp['back_money']) && !is_numeric($tmp['back_score'])) {
                        $this->error(L_('请填写正确的数据'));
                    }
                    $_POST['recharge_rule'][] = $tmp;
                }
            }
            $_POST['recharge_rule'] = serialize($_POST['recharge_rule']);
            unset($_POST['recharge_count'], $_POST['recharge_back_money'], $_POST['recharge_back_score']);

            if (!is_numeric($_POST['begin_money']) && $this->config['merchant_card_recharge_offline'] || $_POST['begin_money'] < 0) {
                $this->error(L_('开卡初始金额设置错误'));
            }

            if ($_POST['discount'] < 0 || $_POST['discount'] > 10) {
                $this->error(L_('会员卡折扣设置错误,不给折扣请填10'));
            }
            //逗号替换
            $_POST['recharge_suggest'] = preg_replace("/(，)/", ',', $_POST['recharge_suggest']);
            $suggest = explode(',', $_POST['recharge_suggest']);
            $_POST['recharge_suggest'] = $suggest ? implode(',', array_filter($suggest)) : '';

            $_POST['mer_id'] = $mer_id;
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $tmp = array();
            foreach ($_POST as $key => $v) {
                if (!(strpos($key, 'wx') === false)) {
                    $tmp[str_replace('wx_', '', $key)] = $v;
                }
            }

            //购买指定商品发卡
            $_POST['buy_goods_list'] = '';
            $buy_goods_list = array();
            $relation_goods_id = $_POST['relation_goods_id'];
            $relation_type = $_POST['relation_type'];
            foreach ($relation_goods_id as $key => $value) {
                $buy_goods_list[$relation_type[$key]][] = $value;
            }
            $_POST['buy_goods_list'] = serialize($buy_goods_list);

            $_POST['wx_param'] = serialize($tmp);

            $sysc_weixin = $_POST['sysc_weixin']>0?$_POST['sysc_weixin']:0;
            unset($_POST['sysc_weixin']);
            $_POST['auto_get'] = 1;//【自动领卡】配置项写死
            
            if(!$_POST['money_need_score']||$_POST['money_need_score']<0){
                $_POST['money_need_score'] = 0;
            }
            
            if ($card = M(_view('Card_new'))->where(array('mer_id' => $_POST['mer_id']))->find()) {
                //多语言表增加数据
                M('Card_new')->where(array('mer_id' => $mer_id))->data($_POST)->save();
                $lang_model = D('Lang');
                $lang_model->edit_lang_data('Card_new',$_POST);

                if ($sysc_weixin && $this->config['coupon_wx_sync']) {
                    if (empty($_POST['wx_prerogative']) && $card['card_id'] != '') {
                        $this->error(L_('特权说明不能为空！'));
                    }
                    $res = $this->sysc_wxcard();
                }
                $this->success(L_('保存成功') . $res['msg'], "javascript:history.back(-1);");
            } else {
                $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
                $id = M('Card_new')->add($_POST);
                //多语言表增加数据
                $lang_model = D('Lang');
                $_POST['card_id'] = $id;
                $lang_model->add_lang_data('Card_new',$_POST);

                if ($sysc_weixin && $this->config['coupon_wx_sync']) {
                    $res = $this->sysc_wxcard();
                }
                $this->success(L_('添加会员卡成功') . $res['msg'], "javascript:history.back(-1);");
            }

        } else {
            $data = M(_view('Card_new'))->where(array('mer_id' => $mer_id))->find();
            $data['wx_param'] = unserialize($data['wx_param']);
            $data['buy_goods_list'] = unserialize($data['buy_goods_list']);
            $data['buy_goods_list_str'] = '';
            if(!empty($data['buy_goods_list'])){
                $condition = array();
                $database = null;
                $mallgoods_list = array();
                $groupgoods_list = array();
                $shopgoods_list = array();
                $result = array();
                $temp = array();
                if($data['buy_goods_list']['mall']){//商城商品
                    $database = D(_view('Shop_goods'));
                    $condition['goods_id']=array('in',$data['buy_goods_list']['mall']);
                    $field = 'goods_id, name, last_time, price';
                    $mallgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($mallgoods_list as $key => $value) {
                        $mallgoods_list[$key]['model'] = '商城';
                        $mallgoods_list[$key]['type'] = 'mall';
                        $result[] = $mallgoods_list[$key];
                        $temp[] = 'mall'.'-'.$value['goods_id'];
                    }
                }
                if($data['buy_goods_list']['shop']){
                    $database = D(_view('Shop_goods'));
                    $condition['goods_id']=array('in',$data['buy_goods_list']['shop']);
                    $field = 'goods_id, name, last_time, price';
                    $shopgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($shopgoods_list as $key => $value) {
                        $shopgoods_list[$key]['model'] = '外卖';
                        $shopgoods_list[$key]['type'] = 'shop';
                        $result[] = $shopgoods_list[$key];
                        $temp[] = 'shop'.'-'.$value['goods_id'];
                    }
                }
                if($data['buy_goods_list']['group']){
                    $database = D('Group');
                    $condition = array('group_id'=>array('in', $data['buy_goods_list']['group']));
                    $field = 'group_id as goods_id, name, last_time, price';
                    $groupgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($groupgoods_list as $key => $value) {
                        $groupgoods_list[$key]['model'] = '团购';
                        $groupgoods_list[$key]['type'] = 'group';
                        $result[] = $groupgoods_list[$key];
                        $temp[] = 'group'.'-'.$value['goods_id'];
                    }
                }
                $data['buy_goods_list'] = $result;
                $data['buy_goods_list_str'] = implode(',', $temp);
            }
            $data['sysc_weixin']=$data['is_wx_card'];
            $data['recharge_rule'] = unserialize($data['recharge_rule']);
            foreach ($data['wx_param']['text_image_list'] as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                $text_image_list[] = array(
                    'image_url' => $v['image_url'],
                    'text' => $v['text'],
                );
            }
            $color_list = D('System_coupon')->color_list();
            $this->assign("color_list", $color_list);
            $data['wx_param']['text_image_list'] = $text_image_list;
            $data['wx_param']['color'] = $color_list[$data['wx_param']['color']];
            $this->assign('card', $data);
            $this->display();
        }
    }

    public function select_goods(){
        $type = I("type") ? : 'shop';//默认商城
        $alltype = I("alltype") ? : "group,shop,new_mall";
        $this->assign("alltypestr", $alltype);
        $this->assign("alltype", explode(',', $alltype));
        $this->assign('type', $type);

        $selected = trim(I("selected"),',');
        $this->assign("selected", $selected);

        $type_arr = array();
        if($selected){
            $selected_arr = explode(',', $selected);
            foreach ($selected_arr as $value) {
                $temp = explode('-', $value);
                $type_arr[$temp[0]][] = $temp[1];
            }
        }

        import('@.ORG.diypage');
        $condition = array();
        $database = null;
        if($type == 'mall'){//商城商品
            $database = D(_view('Shop_goods'));
            // $condition['g.cat_fid']=array('gt',0);
            $condition['s.mer_id'] = $this->merchant_session['mer_id'];
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.last_time, g.price';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'shop'){
            $database = D(_view('Shop_goods'));
            $condition['g.goods_type']=array('neq',1);
            $condition['s.mer_id'] = $this->merchant_session['mer_id'];
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.last_time, g.price';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }
        elseif($type == 'group'){
            $database = D('Group');
            $condition['mer_id'] = $this->merchant_session['mer_id'];
            $condition['status'] = ['eq', 1];
            $condition['begin_time'] = ['lt', time()];
            $condition['end_time'] = ['gt', time()];
            if($_GET['keyword']){
                $condition['name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'group_id as goods_id, name, last_time, price';
            $count = $database->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }elseif($type == 'new_mall'){
            $database = D('Mall_goods');
            // $condition['g.cat_fid']=array('gt',0);
            $condition['g.is_del'] = 0;
            $condition['s.mer_id'] = $this->merchant_session['mer_id'];
            if($_GET['keyword']){
                $condition['g.name'] = array('like','%'.$_GET['keyword'].'%');
            }
            $field = 'g.goods_id, g.name, g.update_time as last_time, g.price';
            $count = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->where($condition)->count();
            $p = new Page($count, 10);
            $goods_list = $database->join("as g left join ".C("DB_PREFIX")."merchant_store as s on s.store_id=g.store_id")->field($field)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->select();
        }

        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['select'] = 0;
            if(in_array($value['goods_id'], $type_arr[$type])){
                $goods_list[$key]['select'] = 1;
            }
        }

        $this->assign("mall_url", U("select_goods",['type'=>'mall','alltype'=>$alltype]));
        $this->assign("group_url", U("select_goods",['type'=>'group','alltype'=>$alltype]));
        $this->assign("shop_url", U("select_goods",['type'=>'shop','alltype'=>$alltype]));
        $this->assign("new_mall_url", U("select_goods",['type'=>'new_mall','alltype'=>$alltype]));
        $this->assign("keyword", $_GET['keyword']);
        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());

        $this->display();
    }

    public function ajaxGetGoods(){
        $selected = trim(I("selected"),',');

        $type_arr = array();
        if($selected){
            $selected_arr = explode(',', $selected);
            foreach ($selected_arr as $value) {
                $temp = explode('-', $value);
                $type_arr[$temp[0]][] = $temp[1];
            }
        }

        $condition = array();
        $database = null;
        $mallgoods_list = array();
        $groupgoods_list = array();
        $shopgoods_list = array();
        $result = array();
        if($type_arr['mall']){//商城商品
            $database = D(_view('Shop_goods'));
            $condition['goods_id']=array('in',$type_arr['mall']);
            $field = 'goods_id, name, last_time, price';
            $mallgoods_list = $database->field($field)->where($condition)->select();
            foreach ($mallgoods_list as $key => $value) {
                $mallgoods_list[$key]['model'] = '商城';
                $mallgoods_list[$key]['type'] = 'mall';
                $result[] = $mallgoods_list[$key];
            }
        }
        if($type_arr['shop']){
            $database = D(_view('Shop_goods'));
            $condition['goods_id']=array('in',$type_arr['shop']);
            $field = 'goods_id, name, last_time, price';
            $shopgoods_list = $database->field($field)->where($condition)->select();
            foreach ($shopgoods_list as $key => $value) {
                $shopgoods_list[$key]['model'] = '外卖';
                $shopgoods_list[$key]['type'] = 'shop';
                $result[] = $shopgoods_list[$key];
            }
        }
        if($type_arr['group']){
            $database = D('Group');
            $condition = array('group_id'=>array('in', $type_arr['group']));
            $field = 'group_id as goods_id, name, last_time, price';
            $groupgoods_list = $database->field($field)->where($condition)->select();
            foreach ($groupgoods_list as $key => $value) {
                $groupgoods_list[$key]['model'] = '团购';
                $groupgoods_list[$key]['type'] = 'group';
                $result[] = $groupgoods_list[$key];
            }
        }
        if($type_arr['new_mall']){//商城商品
            $database = D('Mall_goods');
            $condition['goods_id']=array('in',$type_arr['new_mall']);
            $field = 'goods_id, name, update_time as last_time, price';
            $mallgoods_list = $database->field($field)->where($condition)->select();
            foreach ($mallgoods_list as $key => $value) {
                $mallgoods_list[$key]['goods_id'] = (int)$value['goods_id'];
                $mallgoods_list[$key]['model'] = '新版商城';
                $mallgoods_list[$key]['type'] = 'new_mall';
                $result[] = $mallgoods_list[$key];
            }
        }
        
        echo json_encode($result);
        exit;
    }

    public function uploadimg($url, $point = true)
    {
        $mode = D('Access_token_expires');
        $res = $mode->get_access_token();
        import('ORG.Net.Http');
        $http = new Http();
        $file = str_replace('https', 'http', $url);
        if(substr($url, 0, 4) != 'http' && substr($url, 0, 1) != '.' && substr($url, 0, 1) == '/' && $point){
            $file = '.'.$file;
        }
        $return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=' . $res['access_token'], $file, 1);
        return json_decode($return, true);
    }

    //同步微信卡包
    public function sysc_wxcard()
    {
        $mer_id = $this->merchant_session['mer_id'];
        $card_info = M(_view('Card_new'))->where(array('mer_id' => $mer_id))->find();
        $card_info['wx_param'] = unserialize($card_info['wx_param']);
        $_POST = $card_info['wx_param'];
        import('@.ORG.weixincard');
        $mode = D('Access_token_expires');
        $token = $mode->get_access_token();

        if($this->merchant_session['logo']){
            $logo = replace_file_domain($this->merchant_session['logo']);
            //将oss上的图片先下来
            $file_handle = new file_handle();
            if($file_handle->check_open_oss()) {
                $file_handle->download($logo);
                $logo = $file_handle->get_path($logo);
                $point = true;
            }else{
				$logo = $_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$logo);
                $point = false;
			}
            $logo_url = $this->uploadimg($logo, $point);
        }elseif($this->config['wechat_share_img']){
            $logo = replace_file_domain($this->config['wechat_share_img']);
            //将oss上的图片现在下来
            $file_handle = new file_handle();
            if($file_handle->check_open_oss()) {
                $file_handle->download($logo);
				$logo = $file_handle->get_path($logo);
                $point = true;
            } else {
                $logo = $_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$logo);
                $point = false;
            }
            $logo_url = $this->uploadimg($logo, $point);
        }else{
            $this->error('没有设置商家logo');
        }
        $param['logo_url'] = $logo_url['url'];
        if($card_info['diybg']!=''){
            $diybg = replace_file_domain($card_info['diybg']);
            //将oss上的图片先下来
            $file_handle = new file_handle();
            if($file_handle->check_open_oss()) {
                $file_handle->download($diybg);
				$diybg = $file_handle->get_path($diybg);
                $point = true;
            } else {
                $diybg = $_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$diybg);
                $point = false;
            }
            $background_url = $this->uploadimg($diybg, $point);
        }elseif($card_info['bg']!=''){
            $background_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace('./','/',$card_info['bg']));
        }else{
            $this->error('没有设置背景');
        }
        
        if(!$this->config['pay_wxapp_username']){
            $this->error(L_('同步微信卡券出错，小程序原始ID未设置'));
        }
        $param['background_pic_url'] = $background_url['url'];
        $param['card_id'] = $card_info['wx_cardid'];
        $param['brand_name'] = mb_substr($this->merchant_session['name'], 0, 12, 'utf-8');
        $param['title'] = mb_substr($_POST['title'], 0, 9, 'utf-8');
        $param['notice'] = mb_substr($_POST['notice'], 0, 16, 'utf-8');
        $param['phone'] = substr($this->merchant_session['phone'], 0, 11);
        $param['description'] = $card_info['info'];
        $param['prerogative'] = $_POST['prerogative'];
        $param['color'] = $_POST['color'];
        if ($card_info['discount'] == 0) {
            $card_info['discount'] = 10;
        }
        $param['discount'] = intval(100 - $card_info['discount'] * 10);
        $param['coupon_url'] = $this->config['site_url'] . '/wap.php?c=My_card&a=merchant_coupon&mer_id=' . $mer_id;
        $param['balance_url'] = $this->config['site_url'] . '/wap.php?c=My_card&a=merchant_prepay&mer_id=' . $mer_id;
        $param['bonus_url'] = $this->config['site_url'] . '/wap.php?c=My_card&a=merchant_point&mer_id=' . $mer_id;
        $param['card_url'] = $this->config['site_url'] . '/wap.php?c=My_card&a=merchant_point&mer_id=' . $mer_id;

        $param['center_title'] = mb_substr($_POST['center_title'], 0, 6, 'utf-8');;
        $param['center_sub_title'] = mb_substr($_POST['center_sub_title'], 0, 8, 'utf-8');
        $param['center_url'] = html_entity_decode($_POST['center_url']);
        $param['center_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
        $param['center_app_brand_pass'] = D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['center_url']));
        $param['custom_url_name'] = mb_substr($_POST['custom_url_name'], 0, 5, 'utf-8');
        $param['custom_url'] = html_entity_decode($_POST['custom_url']);
        $param['custom_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
        $param['custom_app_brand_pass'] = D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['custom_url']));
        $param['custom_url_sub_title'] = mb_substr($_POST['custom_url_sub_title'], 0, 6, 'utf-8');
        $param['promotion_url'] = html_entity_decode($_POST['promotion_url']);
        $param['promotion_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
        $param['promotion_app_brand_pass'] = D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['promotion_url']));
        $param['promotion_url_name'] = mb_substr($_POST['promotion_url_name'], 0, 6, 'utf-8');
        $param['promotion_url_sub_title'] = mb_substr($_POST['promotion_url_sub_title'], 0, 6, 'utf-8');

        $param['custom_cell1_name'] = mb_substr($_POST['custom_cell1_name'], 0, 5, 'utf-8');
        $param['custom_cell2_name'] = mb_substr($_POST['custom_cell2_name'], 0, 5, 'utf-8');
        $param['custom_cell3_name'] = mb_substr($_POST['custom_cell3_name'], 0, 5, 'utf-8');

        $param['custom_cell1_tips'] = mb_substr($_POST['custom_cell1_tips'], 0, 6, 'utf-8');
        $param['custom_cell2_tips'] = mb_substr($_POST['custom_cell2_tips'], 0, 6, 'utf-8');
        $param['custom_cell3_tips'] = mb_substr($_POST['custom_cell3_tips'], 0, 6, 'utf-8');

        $param['custom_cell1_url'] = html_entity_decode($_POST['custom_cell1_url']);
        $param['custom_cell2_url'] = html_entity_decode($_POST['custom_cell2_url']);
        $param['custom_cell3_url'] = html_entity_decode($_POST['custom_cell3_url']);
        foreach ($_POST['image_url'] as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $text_image_list[] = array(
                'image_url' => $v,
                'text' => $_POST['text'][$k],
            );
        }

        $param['text_image_list'] = $text_image_list;
        $param['business_service'] = $_POST['business_service'];

        $param['token'] = $token;

        $card = new Create_wxcard($param);
        if ($param['card_id']) {
            $cardinfo = $card->update();
        } else {
            $cardinfo = $card->create();
        }
        $ticket = $cardinfo['ticket'];
        $qrcode_url = $cardinfo['qrcode_url'];
        $return = $cardinfo['return'];

        if ($return['errcode'] == 0) {
            if ($param['card_id'] == '') {
                $wx_data['wx_cardid'] = $return['card_id'];
                $wx_data['jsapi_ticket'] = $ticket['ticket'];
                $wx_data['expires_in'] = $ticket['expires_in'];
                $wx_data['wx_qrcode'] = $qrcode_url['show_qrcode_url'];
                $wx_data['wx_ticket_addtime'] = $_SERVER['REQUEST_TIME'];
                $wx_data['is_wx_card'] = 1;
//                        unset($param['res']);
                       $wx_data['wx_param'] = serialize($param);
                $wx_data['cardsign'] = sha1($wx_data['wx_ticket_addtime'] . $ticket['ticket'] . $return['card_id']);
                M('Card_new')->where(array('mer_id' => $mer_id))->save($wx_data);
                $errormsg = $return['errmsg'];
            }
            else{
                $wx_data['wx_param'] = serialize($param);
                M('Card_new')->where(array('mer_id' => $mer_id))->save($wx_data);
            }
            if (IS_AJAX) {

                $this->success(L_('微信会员卡同步成功'));
            }else{
                return array('error_code'=>0,'msg'=>L_('，微信会员卡同步成功'));
            }
        } else {
            $wx_data['is_wx_card'] = 0;
            $errormsg = $return['errmsg'];
            $wx_data['weixin_err'] = serialize($errormsg);
            M('Card_new')->where(array('mer_id' => $mer_id))->save($wx_data);

            if (IS_AJAX) {
                $this->error(L_('同步微信会员卡出错，请检查您配置的数据是否正确，微信返回信息：') . $return['errmsg']);
            } else {
                return array('error_code' => 1, 'msg' => L_('，同步微信会员卡出错，请检查您配置的数据是否正确，微信返回信息：') . $return['errmsg']);
            }
        }
    }

    private function diy_field_search($condition_user,$get){
        $condition = $this->get_diy_field($get);
        if($condition){
            $where = array();
            foreach ($condition as $key => $val) {
                if($val){
                    $field = $key.':'.$val;
                    $where[] = "FIND_IN_SET('$field',c.diy_field_select)";
                    //$where[] = 'c.diy_field_select like %'.$field.'%';
                }
                
            }
            $where_str = implode(' AND ', $where);
            if($where_str){
                $condition_user['_string'] = $where_str;
            }
            
        }
        return $condition_user;
    }

    //获取自定义的筛选字段
    private function get_diy_field($get){
        $pre = D('Merchant_card_field')->pre;
        $condition = array();
        foreach ($get as $key => $value) {
            if(substr($key, 0, strlen($pre)) === $pre){//判断字符串是以diy开始的就是自定义字段
                $condition[$key] = $value;
            }
        }
        return $condition;
    }

    //会员卡用户列表
    public function index()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_GET['keyword'] . '%');
            }
            if ($_GET['searchtype'] == 'card_id') {
                $condition_['c.id'] = array('like', '%' . $_GET['keyword'] . '%');
                $condition_['c.wx_card_code'] = array('like', '%' . $_GET['keyword'] . '%');
                $condition_['_logic'] = 'or';
                $condition_user['_complex'] = $condition_;
            }
            if ($_GET['searchtype'] == 'physical_id') {
                $condition_user['c.physical_id'] = array('like', '%' . $_GET['keyword'] . '%');
            }
            if ($_GET['searchtype'] == 'nickname') {
                $condition_user['u.nickname'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }

        // 默认近1年数据
        if(!isset($_GET['begin_time']) && !isset($_GET['end_time'])){
            $_GET['end_time'] = date('Y-m-d');
            $_GET['begin_time'] = date('Y-m-d',strtotime("-1 year"));
        }

        if(!empty($_GET['begin_time']) && !empty($_GET['end_time'])){
            $condition_user['c.add_time'] = array('between', [strtotime($_GET['begin_time']), strtotime($_GET['end_time'])+86400]);
        }

        $condition_user = $this->diy_field_search($condition_user,$_GET);
        $condition_user['c.mer_id'] = $this->merchant_session['mer_id'];

        $count_count = M('Card_userlist')->join('as c left join ' . C('DB_PREFIX') . 'user as u ON u.uid = c.uid')->where($condition_user)->count();

        import('@.ORG.merchant_page');
        $p = new Page($count_count, 20);
        $p->show_go_page = true;
        $card_user_list = M('Card_userlist')->field('c.*,u.nickname,u.birthday,u.phone,c.add_time as card_add_time,c.status as card_status')->join('as c left join ' . C('DB_PREFIX') . 'user as u ON u.uid = c.uid')->where($condition_user)->order('c.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        if (C('customization.open_tuwei_sync')) {
            foreach ($card_user_list as $u) {
                D('Card_new')->sync_tw_card($u['uid'], 0, $u['mer_id']);
            }
            $card_user_list = M('Card_userlist')->field('c.*,u.nickname,u.birthday,u.phone,c.add_time as card_add_time,c.status as card_status')->join('as c left join ' . C('DB_PREFIX') . 'user as u ON u.uid = c.uid')->where($condition_user)->order('c.id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        }

        $pagebar = $p->show();

        // 会员卡余额总额
        $total_card_money = M('Card_userlist')->field('sum(card_money) as card_money')->where(array('mer_id'=>$this->merchant_session['mer_id'],'status'=>1))->find();
        $total_card_money_give = M('Card_userlist')->field('sum(card_money_give) as card_money_give')->where(array('mer_id'=>$this->merchant_session['mer_id'],'status'=>1))->find();

        $stop_card_money = M('Card_userlist')->field('sum(card_money) as card_money')->where(array('mer_id'=>$this->merchant_session['mer_id'],'status'=>0))->find();
        $stop_card_money_give = M('Card_userlist')->field('sum(card_money_give) as card_money_give')->where(array('mer_id'=>$this->merchant_session['mer_id'],'status'=>0))->find();

        $this->assign('total_money', getFormatNumber($total_card_money['card_money']+$total_card_money_give['card_money_give']));
        $this->assign('stop_total_money', getFormatNumber($stop_card_money['card_money']+$stop_card_money_give['card_money_give']));
    
        //获取自定义筛选框
        $diy_select = D('Merchant_card_field')->get_select_field();
        $this->assign('diy_select', $diy_select);

        // 用户分组
        $card_group =M('Card_group')->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $card_group_formart = array();
        foreach ($card_group as $key => $value) {
            $card_group_formart[$value['id']] = $value['name'];
        }
        foreach ($card_user_list as &$_value) {
            $_value['group_name'] = $card_group_formart[$_value['gid']];
        }

        $this->assign('pagebar', $pagebar);
        $this->assign('card_user_list', $card_user_list);

        $card_group =M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $card_group_formart = array();
        foreach ($card_group as $key => $value) {
            $card_group_formart[$value['id']] = $value['name'];
        }
        $this->assign('card_group', $card_group_formart);
        $this->assign('card_group_new', $card_group?json_encode($card_group,JSON_UNESCAPED_UNICODE):[]);
        $this->display();
    }

    public function add_user()
    {
        $card_group = M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $this->assign('card_group', $card_group);
        if (!D('Card_new')->get_card_by_mer_id($this->merchant_session['mer_id'])) {
            exit('<html><head><style>*{margin:0;padding:0;}</style></head><body>请先编辑完善会员卡信息再添加用户</body></html>');
        }
        if (IS_POST) {
			//如果传了手机号，自动注册会员
			$_POST['phone'] = trim($_POST['phone']);
			if($_POST['phone']){
                if(!preg_match("/^1\d{10}$/", $_POST['phone'])){
                    $this->error(L_('手机号码格式不正确'));
                }
				$now_user = D('User')->get_user($_POST['phone'], 'phone');
				if($now_user){
					$_POST['uid'] = $now_user['uid'];
				}else{
					$data_user['phone'] = $_POST['phone'];
					$data_user['source'] = 'merchant_card_auto';
					$reg_result = D('User')->autoreg($data_user);
					if($reg_result['uid']){
						$_POST['uid'] = $reg_result['uid'];
					}
				}
			}
			unset($_POST['phone']);
			$_POST['add_time'] = time();
			
            //卡号规则
            $_POST['mer_id'] = $this->merchant_session['mer_id'];

            if ($_POST['physical_id']) {
                $isTure = preg_match('/^[0-9a-zA-Z]+$/', $_POST['physical_id']);
                if (!$isTure) {
                    $this->error(L_('实体卡号只能包含字母、数字'));
                }
                $where_py['physical_id'] = $_POST['physical_id'];
                $where_py['mer_id'] = array('eq', $this->merchant_session['mer_id']);
                if (M('Card_userlist')->where($where_py)->find()) {
                    $this->error(L_('该实体卡已绑定其他会员，不能再绑定'));
                }
            }

            if(M('Card_userlist')->where(array('mer_id' => $_POST['mer_id'], 'uid' => $_POST['uid']))->find()){
                $this->error(L_('添加失败，该手机号已绑定该商家会员，不能再绑定'));
            }

            if ($card_id = M('Card_userlist')->add($_POST)){
                D('Card_new')->create_tw_vip($card_id);


                if (!empty($_POST['card_money_give'])) {
                    $data_money['card_id'] = $card_id;
                    $data_money['type'] = 1;
                    $data_money['money_add'] = $_POST['card_money_give'];
                    $data_money['desc'] = L_('会员卡创建初始金额');
                    D('Card_new')->add_row($data_money);
                }
                if (!empty($_POST['card_score'])) {
                    $data_score['card_id'] = $card_id;
                    $data_score['type'] = 1;
                    $data_score['score_add'] = $_POST['card_score'];
                    $data_score['desc'] = L_('会员卡创建初始积分');
                    D('Card_new')->add_row($data_score);
                }
               $this->success(L_('添加成功'));
            }else{
               $this->error(L_('添加失败'));
            }
        } else {
            $this->display();
        }
    }

    public function import_user()
    {
        if (IS_POST) {
            if ($_FILES['file']['error'] != 4) {
                set_time_limit(0);
                
                import('ORG.Util.Dir');
                Dir::delDirnotself('./upload/excel/merchant/card_user');
        
                $upload_dir = './upload/excel/merchant/card_user/' . date('Ymd') . '/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                import('ORG.Net.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 10 * 1024 * 1024;
                $upload->allowExts = array('xls', 'xlsx');
                $upload->allowTypes = array(); // 允许上传的文件类型 留空不做检查
                $upload->savePath = $upload_dir;
                $upload->thumb = false;
                $upload->thumbType = 0;
                $upload->imageClassPath = '';
                $upload->thumbPrefix = '';
                $upload->saveRule = 'uniqid';
                if ($upload->upload()) {
                    $uploadList = $upload->getUploadFileInfo();
                    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel/IOFactory.php';
                    $path = $uploadList['0']['savepath'] . $uploadList['0']['savename'];
                    $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
                    $objReader = PHPExcel_IOFactory::createReader($fileType);
                    $excelObj = $objReader->load($path);
                    $result = $excelObj->getActiveSheet()->toArray(null, true, true, true);
                    unlink($path);  //删除物业上传的excel表格
                    $database_user = D('User');
                    $error_arr = array();
                    if (!empty($result) && is_array($result)) {
                        unset($result[1]);
                        $last_user_id = 0;
                        $err_msg = '';
                        foreach ($result as $kk => $vv) {
                            if (array_sum($vv) == 0) {
                                continue;
                            }
                            if ($vv['A'] === null && $vv['B'] === null && $vv['C'] === null && $vv['D'] === null && $vv['E'] === null && $vv['F'] === null && $vv['G'] === null && $vv['H'] === null) continue;

                            $tmpdata['add_time'] = time();
                            //如果传了手机号，自动注册会员
                            if($vv['D']){
                                $tmpdata['phone'] = htmlspecialchars(trim($vv['D']), ENT_QUOTES);
                                $now_user = D('User')->get_user($vv['D'], 'phone');
                                if($now_user){
                                    $tmpdata['uid'] = $now_user['uid'];
                                }else{
                                    $data_user['phone'] = $tmpdata['phone'];
                                    $data_user['source'] = 'merchant_card_auto';
                                    $reg_result = D('User')->autoreg($data_user);
                                    if($reg_result['uid']){
                                        $tmpdata['uid'] = $reg_result['uid'];
                                    }
                                }
                            }

                            // 实体卡号
                            $tmpdata['physical_id'] = htmlspecialchars(trim($vv['A']), ENT_QUOTES);
                            // 姓名
                            $tmpdata['name'] = htmlspecialchars(trim($vv['B']), ENT_QUOTES);
                            // 性别
                            $sex = htmlspecialchars(trim($vv['C']), ENT_QUOTES);
                            if ($sex == '男') { 
                                $tmpdata['sex'] = 1;
                            }elseif ($sex == '女') {
                                $tmpdata['sex'] = 2;
                            }else{
                                $tmpdata['sex'] = 0;
                            }
                            // 赠送金额
                            $tmpdata['card_money_give'] = htmlspecialchars(trim($vv['E']), ENT_QUOTES);
                            // 会员卡积分数量
                            $tmpdata['card_score'] = htmlspecialchars(trim($vv['F']), ENT_QUOTES);

                            // 分组
                            $group_name = htmlspecialchars(trim($vv['G']), ENT_QUOTES);
                            if($group_name){ 
                                $card_group = M(_view('Card_group'))->where(array('name' => array('like','%'.$group_name.'%')))->find();
                                if ($card_group) {
                                    $tmpdata['gid'] = $card_group['id'];
                                }else{
                                    $err_msg = '分组名错误！';
                                    $vv['I'] = $err_msg ;
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }
                           
                            // 状态
                            $status = htmlspecialchars(trim($vv['H']), ENT_QUOTES);
                            if ($status == '正常') { 
                                $tmpdata['status'] = 1;
                            }elseif ($sex == '禁止') {
                                $tmpdata['status'] = 0;
                            }
              
                            //卡号规则
                            $tmpdata['mer_id'] = $this->merchant_session['mer_id'];

                            $where_py1['uid'] = $tmpdata['uid'];
                            $where_py1['mer_id'] = $tmpdata['mer_id'];
                            if (M('Card_userlist')->where($where_py1)->find()) {
                                $err_msg = '该手机号已绑定会员卡，不可重复绑定';
                                $vv['I'] = $err_msg;
                                $error_arr[] = $vv;
                                continue;
                            }

                            if ($tmpdata['physical_id']) {
                                $isTure = preg_match('/^[0-9a-zA-Z]+$/', $tmpdata['physical_id']);
                                if (!$isTure) {
                                    $err_msg = '实体卡号只能包含字母、数字';
                                    $vv['I'] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }

                                $where_py['physical_id'] = $tmpdata['physical_id'];
                                $where_py['mer_id'] = array('eq', $tmpdata['mer_id']);
                                if (M('Card_userlist')->where($where_py)->find()) {
                                    $err_msg = '该实体卡已绑定其他会员，不能再绑定';
                                    $vv['I'] = $err_msg;
                                    $error_arr[] = $vv;
                                    continue;
                                }
                            }

                            if ($card_id = M('Card_userlist')->add($tmpdata)){
                                if (!empty($tmpdata['card_money_give'])) {
                                    $data_money['card_id'] = $card_id;
                                    $data_money['type'] = 1;
                                    $data_money['money_add'] = $tmpdata['card_money_give'];
                                    $data_money['desc'] = L_('会员卡创建初始金额');
                                    D('Card_new')->add_row($data_money);
                                }
                                if (!empty($tmpdata['card_score'])) {
                                    $data_score['card_id'] = $card_id;
                                    $data_score['type'] = 1;
                                    $data_score['score_add'] = $tmpdata['card_score'];
                                    $data_score['desc'] = L_('会员卡创建初始积分');
                                    D('Card_new')->add_row($data_score);
                                }
                            }else{ 
                                $err_msg = '姓名为' . $vv['B'] . ' 导入失败！';
                                $vv['I'] = $err_msg ;
                                $error_arr[] = $vv;
                                continue;
                            }
                        }
                        if (empty($error_arr)) {
                            $this->success('导入成功');
                            exit;
                        } else {
                            $num = count($error_arr);
                            // echo "<script language='JavaScript' type='text/javascript'>alert('失败{$num}条，点击下载！')</script>";
                            //导出失败信息
                            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                            error_reporting(E_ALL);  
                            date_default_timezone_set('Europe/London');  
                            $objExcel = new PHPExcel();  
                      
                            $title = '导入失败列表';
                            // $title = $this->village['village_name'] . '社区-导入失败列表';
                            /*以下是一些设置 ，什么作者  标题之类的*/  
                             $objExcel->getProperties()->setCreator($title)  
                               ->setLastModifiedBy($title)  
                               ->setTitle($title)  
                               ->setSubject("失败信息导出")  
                               ->setDescription("备份数据")  
                               ->setKeywords("excel")  
                               ->setCategory("result file");  
                            
                            $i = 0;
                            $objExcel->createSheet();
                            $objExcel->setActiveSheetIndex($i);
                            $objExcel->getActiveSheet()->setTitle($title);
                            $objActSheet = $objExcel->getActiveSheet();
                            $objActSheet->setCellValue('A1', '实体卡号');
                            $objActSheet->setCellValue('B1', '姓名');
                            $objActSheet->setCellValue('C1', '性别');
                            $objActSheet->setCellValue('D1', '手机号');
                            $objActSheet->setCellValue('E1', '会员卡余额初始值');
                            $objActSheet->setCellValue('F1', '会员卡积分初始值');
                            $objActSheet->setCellValue('G1', '用户分组');
                            $objActSheet->setCellValue('H1', '状态');
                            $objActSheet->setCellValue('I1', '失败信息');
                            $index = 2;
                            foreach ($error_arr as  $value) {  
                                $objActSheet->setCellValueExplicit('A' . $index, $value['A']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['B']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['C']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['D']);
                                $objActSheet->setCellValueExplicit('E' . $index, $value['E']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['F']);
                                $objActSheet->setCellValueExplicit('G' . $index, $value['G']);
                                $objActSheet->setCellValueExplicit('H' . $index, $value['H']);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['I']);
                                $index++;
                            }
                        
                            //输出
                            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                            ob_end_clean();
                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type:application/force-download");
                            header("Content-Type:application/vnd.ms-execl");
                            header("Content-Type:application/octet-stream");
                            header("Content-Type:application/download");
                            header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
                            header("Content-Transfer-Encoding:binary");
                            $objWriter->save('php://output');
                            exit();
                        }
                    }
                } else {
                    $this->error($upload->getErrorMsg());
                    exit;
                }
            }
            $this->error('文件上传失败');
            exit;
        } else {
            $this->display();
        }
    }

    //用户扫描二维码绑定会员卡
    public function see_qrcode()
    {
        // $id = intval($_GET['id']);
        // if (!D('Card_new')->get_card_by_mer_id($this->merchant_session['mer_id'])) {
        //     exit('<html><head><style>*{margin:0;padding:0;}</style></head><body>请先编辑完善会员卡信息再添加用户</body></html>');
        // }
        // $qrCon = $this->config['site_url'] . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id='.$this->merchant_session['mer_id'].'&id=' . $id;
        // import('@.ORG.phpqrcode');
        // $size = $_GET['size'] ? $_GET['size']: 11;
        // QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1);


        if ($_GET['wx_qrcode']) {
            $coupon = M('Card_new_coupon')->where(array('coupon_id' => $_GET['id']))->find();
            if (empty($coupon['wx_cardid'])) {
                echo '<html><head><style>*{margin:0;padding:0;}</style></head><body>微信同步失败</body></html>';
                die;
            }
            echo '<html><head><style>*{margin:0;padding:0;}</style></head><body><img src="' . $coupon['wx_qrcode'] . '"/></body></html>';
            die;
        }
        if (!D('Card_new')->get_card_by_mer_id($this->merchant_session['mer_id'])) {
            exit('<html><head><style>*{margin:0;padding:0;}</style></head><body>请先编辑完善会员卡信息再添加用户</body></html>');
        }
        $where['id'] = $_GET['id'];
        $qrcode_id = 800000000 + $_GET['id'];
        $res = D('Recognition')->get_tmp_qrcode($qrcode_id);
        $date['qrcode_id'] = $qrcode_id;
        $date['ticket'] = $res['ticket'];
        $where['id'] = $_GET['id'];
        M('Card_userlist')->where($where)->save($date);
        echo '<html><head><style>*{margin:0;padding:0;}</style></head><body><img src="' . $res['ticket'] . '"/></body></html>';
    }

    //ajax 获取创建的会员卡绑定状态
    public function ajax_get_bind_status()
    {
        $cardid = $_POST['cardid'];
        if ($uid = M('Card_userlist')->where(array('id' => $cardid))->getField('uid')) {
            echo json_encode(array('error_code' => 0, 'msg' => L_('绑定成功')));
            die;
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => L_('绑定失败')));
            die;
        }
    }

    //会员卡详情
    public function card_detail()
    {
        $card_group = M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $this->assign('card_group', $card_group);
        if (IS_POST) {
            $card_info = D('Card_new')->get_cardinfo_by_id($_POST['id']);
            if (empty($card_info)) {
                $this->error(L_('会员卡不存在'));
            } else {
                $data['card_money_give'] = pow(-1, ($_POST['set_money_type'] + 1)) * $_POST['set_money'] + $card_info['card_money_give'];
                if ($data['card_money_give'] < 0) {
                    $this->error(L_('管理员只能减少会员卡的赠送余额，减少的金额已超出赠送余额，您最多可减少X1',$card_info['card_money_give'] . C('config.Currency_txt')));
                }
                $data['card_score'] = pow(-1, ($_POST['set_score_type'] + 1)) * $_POST['set_score'] + $card_info['card_score'];
                if ($data['card_money_give'] < 0) {
                    $data['card_money_give'] = 0;
                }

                if ($data['card_score'] < 0) {
                    $data['card_score'] = 0;
                }

                if ($_POST['physical_id']) {
                    $isTure = preg_match('/^[0-9a-zA-Z]+$/', $_POST['physical_id']);
                    if (!$isTure) {
                        $this->error(L_('实体卡号只能包含字母、数字'));
                    }
                    $where_py['physical_id'] = $_POST['physical_id'];
                    $where_py['id'] = array('neq', $_POST['id']);
                    $where_py['mer_id'] = array('eq', $this->merchant_session['mer_id']);

                    if (M('Card_userlist')->where($where_py)->find()) {
                        $this->error(L_('该实体卡已绑定其他会员，不能在绑定'));
                    }
                }

                $data['physical_id'] = $_POST['physical_id'];
                $data['status'] = $_POST['status'];
                $data['gid'] = $_POST['gid'];
                $data['name'] = $_POST['name']?$_POST['name']:'';
                $data['sex'] = $_POST['sex']?$_POST['sex']:'';
                $data['remark'] = $_POST['remark']?$_POST['remark']:'';
                $data = $this->deal_diy_field($data,$_POST);

                if (M('Card_userlist')->where(array('id' => $_POST['id'], 'uid' => $_POST['uid']))->data($data)->save()) {
                    if (!empty($_POST['set_money'])) {
                        $des = $_POST['set_money_type'] == 1 ? L_('增加') : L_('减少');
                        $add_use = $_POST['set_money_type'] == 1 ? 'add' : 'use';
                        $data_money['card_id'] = $card_info['id'];
                        $data_money['type'] = $_POST['set_money_type'];
                        $data_money['money_' . $add_use] = $_POST['set_money'];
                        $data_money['desc'] = L_('商家后台操作X1赠送余额',$des);

                        D('Card_new')->add_row($data_money);
                        //if ($card_info['wx_card_code'] != '') {
                            D('Card_new')->update_wx_card($card_info['wx_card_code'], $data['card_money_give'] - $card_info['card_money_give'], 0, $data_money['desc'],'',$_POST['uid'],$this->merchant_session['mer_id']);
                        //}
                    }

                    if (!empty($_POST['set_score'])) {
                        $des = $_POST['set_score_type'] == 1 ? L_('增加') : L_('减少');
                        $add_use = $_POST['set_score_type'] == 1 ? 'add' : 'use';
                        $data_score['card_id'] = $card_info['id'];
                        $data_score['type'] = $_POST['set_score_type'];
                        $data_score['score_' . $add_use] = $_POST['set_score'];
                        $data_score['desc'] = L_('商家后台操作X1积分',$des);
                        D('Card_new')->add_row($data_score);

                        //if ($card_info['wx_card_code'] != '') {
                            D('Card_new')->update_wx_card($card_info['wx_card_code'], 0, $data['card_score'] - $card_info['card_score'], '', $data_score['desc'],$_POST['uid'],$this->merchant_session['mer_id']);
                        //}
                    }
                    $this->success(L_('修改成功！'));
                } else {
                    $this->error(L_('修改失败！请重试。'));
                }
            }
        } else {
            $id = $_GET['id'];
            $card_info = D('Card_new')->get_cardinfo_by_id($id);
            $diy_field_info = $card_info['diy_field']?unserialize($card_info['diy_field']):'';
            $diy_field = D('Merchant_card_field')->get_detail_list($diy_field_info);
            $this->assign('diy_field', $diy_field);
            if (empty($card_info)) {
                $this->error(L_('会员卡不存在'));
            } else {
                $this->assign('card', $card_info);
                $this->display();
            }
        }
    }

    //处理一下自定义字段
    private function deal_diy_field($data,$post){
        $model = D('Merchant_card_field');
        $diy_field = array();
        $diy_field_select = array();
        $pre = $model->pre;
        $select_url = $model->get_select_url();

        foreach ($post as $key => $value) {
            if(substr($key, 0, strlen($pre)) === $pre){
                $diy_field[$key] = $value;//全字段信息
                if(in_array($key, $select_url)){//记录下筛选字段信息
                    if(is_array($value)){//如果是数组
                        foreach ($value as $v) {
                            $diy_field_select[] = $key.':'.$v;
                        }
                    }else{
                        if($value){
                            $diy_field_select[] = $key.':'.$value;
                        }
                    }
                }
            }
        }
        $data['diy_field_select'] = implode(',', $diy_field_select);
        $data['diy_field'] = serialize($diy_field);
        return $data;
    }

    //消费记录
    public function consume_record()
    {
        $money_record = D('Card_new')->card_use_record($_GET['id']);
        $this->assign('record', $money_record['record']);
        $this->assign('pagebar', $money_record['pagebar']);
        $this->display();
    }


//会员卡优惠券
    public function card_new_coupon()
    {
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'coupon_id') {
                $condition_coupon['coupon_id'] = $_GET['keyword'];
            } else if ($_GET['searchtype'] == 'name') {
                $condition_coupon['name'] = array('like', '%' . $_GET['keyword'] . '%');
            }
        }
        if (isset($_GET['status']) && $_GET['status'] != '-1') {
            $condition_coupon['status'] = intval($_GET['status']);
        }else{
            $_GET['status'] = -1;
            $condition_coupon['status'] = array('neq', 4);
        }

        //排序 /*/
        $order_string = '`coupon_id` DESC';
        if ($_GET['sort']) {
            switch ($_GET['sort']) {
                case 'uid':
                    $order_string = '`uid` DESC';
                    break;
                case 'lastTime':
                    $order_string = '`last_time` DESC';
                    break;
                case 'money':
                    $order_string = '`now_money` DESC';
                    break;
                case 'score':
                    $order_string = '`score_count` DESC';
                    break;
            }
        }
        $condition_coupon['mer_id'] = $this->merchant_session['mer_id'];
        $condition_coupon['is_partition'] = 0;
        $coupon = M('Card_new_coupon');
        $count_count = $coupon->where($condition_coupon)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_count, 15);
        $coupon_list = $coupon->field(true)->where($condition_coupon)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($coupon_list as $key => &$v) {
            $v['platform'] = unserialize($v['platform']);
            if ($v['cate_name'] != 'all' && !empty($v['cate_id'])) {
                $tmp = unserialize($v['cate_id']);
                $v['cate_id'] = $tmp['cat_name'];
            }
            if ($v['end_time'] < time()) {
                D('Card_new_coupon')->where(array('coupon_id' => $v['coupon_id']))->setField('status', 2);
                $v['status'] = 2;
            }

            if ($v['cate_id'] == 'all' || !$v['cate_id']) {
                $v['cate_id'] = 0;
            }

            $v['use_count'] = M('Card_new_coupon_hadpull')->where(array('coupon_id' => $v['coupon_id'], 'is_use' => 1))->count();

        }
        $return = D('Card_new_coupon')->cate_platform();
        $this->assign("category", $return['category']);
        $this->assign("platform", $return['platform']);
        $this->assign('coupon_list', $coupon_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function add_coupon()
    {
        if (IS_POST) {
//           var_dump($_POST);die;
//            $now_card = M(_view('Card_new'))->where(array('mer_id'=>$this->merchant_session['mer_id']))->find();
//            if(empty($now_card)){
//                $this->error('请先创建会员卡，再添加优惠券！');
//            }
//            if (strtotime($_POST['end_time']) < strtotime($_POST['start_time']) || strtotime($_POST['end_time']) < time() || strtotime($_POST['start_time']) < strtotime(date('Y-m-d'))) {
//                $this->error('起始时间设置有误！');
//            }
            if ($_POST['limit'] > $_POST['num']) {
                $this->error(L_('领取限制不能大于数量！'));
            }
            if ($_POST['use_limit'] > $_POST['limit'] || $_POST['use_limit'] > $_POST['num']) {
                $this->error(L_('使用限制设置错误，不能大于领取限制和数量！'));
            }
            if ($_POST['cate_name'] != 'all') {
                //为商城显示满减
                if($_POST['cate_name'] == 'mall'){
                    $data['is_receive_full_reduction'] = $_POST['is_receive_full_reduction'];
                }
                if ($_POST['cate_name'] == 'shop') {
                    $data['is_shop'] = 1;
                }
                if ($_POST['cate_id'] != 0) {
                    if ($_POST['cate_name'] == 'meal') {
                        $cate_id = D(ucfirst($_POST['cate_name']) . '_store_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->find();
                    } else if ($_POST['cate_name'] == 'mall') {
                        $cate_id = D('Goods_category')->field('id as cat_id,name as cat_name')->where(array('id' => $_POST['cate_id']))->order('`sort` DESC, `id` ASC')->find();
                    } else {
                        $cate_id = D(ucfirst($_POST['cate_name']) . '_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->find();
                    }
                    $_POST['cate_id'] = serialize($cate_id);
                }
            } else {
                $_POST['cate_id'] = 0;
                //为商城满减
                $data['is_receive_full_reduction'] = $_POST['is_receive_full_reduction'];
            }
            $data['platform'] = serialize($_POST['platform']);
            unset($_POST['dosubmit']);
            unset($_POST['platform']);
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'card_new_coupon', 1);
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }
//            if(strlen($_POST['des'])>){
//
//            }
            $data = array_merge($data, $_POST);
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']) + 86399;//到 23:59:59
            if ($data['end_time'] < $data['start_time']) {
                $this->error(L_('起始时间设置有误'));
            }
            $data['add_time'] = $data['last_time'] = time();
            $data['mer_id'] = $this->merchant_session['mer_id'];
//            $data['card_id'] = $now_card['card_id'];
            $data['notice'] = $_POST['notice'];
            $data['auto_get'] = $_POST['auto_get'];
            if (empty($_POST['store_id'])) {
                $this->error(L_('店铺不能为空'));
            }
            $data['store_id'] = implode(',', $_POST['store_id']);

            //优惠券指定商品
            if($_POST['selgoods'] == '1'){
                $_POST['buy_goods_list'] = '';
                $buy_goods_list = array();
                $relation_goods_id = $_POST['relation_goods_id'];
                $relation_type = $_POST['relation_type'];
                foreach ($relation_goods_id as $key => $value) {
                    $buy_goods_list[$relation_type[$key]][] = (int)$value;
                }
                $data['point_goodids'] = serialize($buy_goods_list);
            }
            else{
                $data['point_goodids'] = '';   
            }

            if ($_POST['sync_wx']) {
                import('@.ORG.weixincard');
                import('ORG.Net.Http');
                $http = new Http();
                $mode = D('Access_token_expires');
                $res = $mode->get_access_token();

                if($this->merchant_session['logo']){
                    $logo = replace_file_domain($this->merchant_session['logo']);
                    //将oss上的图片先下来
                    $file_handle = new file_handle();
                    if($file_handle->check_open_oss()) {
                        $file_handle->download($logo);
						$logo = $file_handle->get_path($logo);
                    } else {
                        $logo = str_replace($this->config['site_url'],'',$logo);
                    }

                    $logo_url = $this->uploadimg($logo);
                }elseif($this->config['wechat_share_img']){
                    $logo = replace_file_domain($this->config['wechat_share_img']);
                    //将oss上的图片现在下来
                    $file_handle = new file_handle();
                    if($file_handle->check_open_oss()) {
                        $file_handle->download($logo);
                        $logo = $file_handle->get_path($logo);
                    } else {
                        $logo = $_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$logo);
                    }
                    $logo_url = $this->uploadimg($logo);
                }else{
                    $this->error('没有设置商家logo');
                }
                $param['logo_url'] = $logo_url['url'];
                $param['card_type'] = 'cash';

//				if($this->merchant_session['logo']){
//					$param['logo_url'] = $this->config['site_url'].$this->merchant_session['logo'];
//				}else{
//					$param['logo_url'] = $this->config['wechat_share_img'] ? $this->config['wechat_share_img'] : $this->config['site_logo'];
//				}
                $param['brand_name'] = mb_substr($_POST['brand_name'], 0, 12, 'utf-8');
                $param['title'] = mb_substr($_POST['name'], 0, 9, 'utf-8');
                $param['color'] = $_POST['color'];
                $param['notice'] = mb_substr($_POST['notice'], 0, 16, 'utf-8');
                $param['phone'] = $this->config['site_phone'];
                $param['description'] = $_POST['des'];
                $param['begin_time'] = $data['start_time'];
                $param['end_time'] = $data['end_time'];
                $param['num'] = $_POST['num'];
                $param['limit'] = $_POST['limit'];
                $param['center_title'] = L_('立即使用');
                $param['center_sub_title'] = mb_substr($_POST['center_sub_title'], 0, 6, 'utf-8');
                $param['center_url'] = html_entity_decode($_POST['center_url']);
                $param['center_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
                $param['center_app_brand_pass'] = $_POST['center_url'] ? D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['center_url'])) : D('House_village')->home_url;
                $param['custom_url_name'] = mb_substr($_POST['custom_url_name'], 0, 5, 'utf-8');
                $param['custom_url'] = html_entity_decode($_POST['custom_url']);
                $param['custom_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
                $param['custom_app_brand_pass'] = D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['custom_url']));
                $param['custom_url_sub_title'] = mb_substr($_POST['custom_url_sub_title'], 0, 6, 'utf-8');
                $param['promotion_url'] = html_entity_decode($_POST['promotion_url']);
                $param['promotion_app_brand_user_name'] = $this->config['pay_wxapp_username'].'@app';
                $param['promotion_app_brand_pass'] = D('House_village')->home_url . '?redirect=webview&webview_url=' . urlencode(htmlspecialchars_decode($_POST['promotion_url']));
                $param['promotion_url_name'] = L_('更多优惠');
                $param['icon_url_list'] = $_POST['icon_url_list']; //封面图片
                if(!$this->config['pay_wxapp_username']){
                    $this->error(L_('同步微信卡券出错，小程序原始ID未设置'));
                }
//                if($this->merchant_session['logo']){
//                    $logo_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$this->merchant_session['logo']));
//                }else{
//                    $logo_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$this->config['wechat_share_img']));
//                }
//                $param['icon_url_list'] = $logo_url['url'];
                $param['abstract'] = $_POST['abstract']; //封面图片
                $param['share_friends'] = $_POST['share_friends'];

                foreach ($_POST['image_url'] as $k => $v) {
                    $text_image_list[] = array(
                        'image_url' => $v,
                        'text' => $_POST['text'][$k],
                    );
                }

                $param['text_image_list'] = $text_image_list;
                $param['business_service'] = $_POST['business_service'];
                $param['least_cost'] = $_POST['order_money'] * 100;
                $param['reduce_cost'] = $_POST['discount'] * 100;
                $param['res'] = $res;

                $card = new Create_card($param);
                $cardinfo = $card->create();
                $ticket = $cardinfo['ticket'];
                $qrcode_url = $cardinfo['qrcode_url'];
                $return = $cardinfo['return'];
                if ($return['errcode']) {
                    $this->error(L_('同步微信卡券出错，请检查您配置的数据是否正确，微信返回信息：') . $return['errmsg']);
                }
            }
            $data['coupon_url'] = isset($_POST['coupon_url']) ? htmlspecialchars_decode($_POST['coupon_url']) : '';
            if ($id = M('Card_new_coupon')->add($data)) {
                $errorms = '';
                if ($this->config['coupon_wx_sync'] && $_POST['sync_wx']) {
                    $wx_data['sync_wx'] = $_POST['sync_wx'];
                    unset($param['res']);

                    $wx_data['wx_param'] = serialize($param);
                    $errormsg = '';
                    if ($return['errcode'] == 0) {
                        $wx_data['wx_cardid'] = $return['card_id'];
                        $wx_data['jsapi_ticket'] = $ticket['ticket'];
                        $wx_data['expires_in'] = $ticket['expires_in'];
                        $wx_data['wx_qrcode'] = $qrcode_url['show_qrcode_url'];
                        $wx_data['wx_ticket_addtime'] = $_SERVER['REQUEST_TIME'];
                        $wx_data['is_wx_card'] = 1;
//                        unset($param['res']);
//                        $wx_data['wx_param'] = serialize($param);
                        $wx_data['cardsign'] = sha1($wx_data['wx_ticket_addtime'] . $ticket['ticket'] . $return['card_id']);
                        M('Card_new_coupon')->where(array('coupon_id' => $id))->save($wx_data);
                        $errormsg = $return['errmsg'];

                    } else {
                        $wx_data['is_wx_card'] = 0;
                        $errormsg = $return['errmsg'];
                        $wx_data['weixin_err'] = serialize($errormsg);
                        M('Card_new_coupon')->where(array('coupon_id' => $id))->save($wx_data);

                    }
                }

                $this->success(L_('添加优惠券成功！') . $errormsg,  U('card_new_coupon'));
            } else {

                $this->error(L_('添加失败！'));
            }
        } else {
            $return = D('Card_new_coupon')->cate_platform();
            unset($return['category']['village_group']);//商家中心没有社区拼团的优惠券
            $color_list = D('System_coupon')->color_list();
            $store_list = D('Merchant_store')->get_store_list_by_merId($this->merchant_session['mer_id']);
            $this->assign("store_list", $store_list);
            $this->assign("color_list", $color_list);
            $this->assign("category", $return['category']);
            $this->assign("platform", $return['platform']);
            $this->display();
        }
    }

    public function edit_coupon()
    {
        if (IS_POST) {
            $add = pow(-1, intval($_POST['add']));
            $_POST['num'] += $add * (int)$_POST['num_add'];//数量增减
            if ((int)$_POST['num'] < (int)$_POST['had_pull']) {
                $this->error(L_('更新优惠券数量有误，不能小于已领取的数量！'));
            } else if (strtotime($_POST['end_time']) < strtotime($_POST['start_time'])) {
                $this->error(L_('起始时间设置有误！'));
            }

            if ($_POST['num'] > $_POST['had_pull'] && $_POST['status'] == 3) {
                if ($_POST['end_time'] > time()) {
                    $_POST['status'] = 1;
                }
            }
            if ($_POST['num'] <= $_POST['had_pull']) {
                $_POST['status'] = 3;
            }

            unset($_POST['dosubmit']);
            $data = $_POST;
            $data['start_time'] = strtotime($_POST['start_time']);
            $data['end_time'] = strtotime($_POST['end_time']) + 86399;//到 23:59:59
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'card_new_coupon', 1);
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }
            $data['last_time'] = time();
            $data['mer_id'] = $this->merchant_session['mer_id'];
            if (empty($_POST['store_id'])) {
                $this->error(L_('店铺不能为空'));
            }
            $data['store_id'] = implode(',', $_POST['store_id']);

            //优惠券指定商品
            if($_POST['selgoods'] == '1'){
                $_POST['buy_goods_list'] = '';
                $buy_goods_list = array();
                $relation_goods_id = $_POST['relation_goods_id'];
                $relation_type = $_POST['relation_type'];
                foreach ($relation_goods_id as $key => $value) {
                    $buy_goods_list[$relation_type[$key]][] = (int)$value;
                }
                $data['point_goodids'] = serialize($buy_goods_list);
            }
            else{
                $data['point_goodids'] = "";   
            }

            $data['coupon_url'] = isset($_POST['coupon_url']) ? htmlspecialchars_decode($_POST['coupon_url']) : '';
            if (M('Card_new_coupon')->where(array('coupon_id' => $_POST['coupon_id']))->save($data)) {

                $errorms = '';
                if ($this->config['coupon_wx_sync']) {
                    if ($_POST['is_wx_card']) {
                        import('ORG.Net.Http');
                        $res = D('Access_token_expires')->get_access_token();
                        //修改描述
                        $update_info['card_id'] = $_POST['wx_cardid'];
                        if ($image['url']['img']) {
                            $update_info['general_coupon']['base_info']['logo_url'] = $image['url']['img'];
                        }
                        $update_info['cash']['base_info']['description'] = $_POST['des_detial'];
                        $update_info['cash']['base_info']['date_info']['type'] = 'DATE_TYPE_FIX_TIME_RANGE';
                        $update_info['cash']['base_info']['date_info']['begin_timestamp'] = intval(strtotime($_POST['start_time']));
                        $update_info['cash']['base_info']['date_info']['end_timestamp'] = intval(strtotime($_POST['end_time']));
                        // $wx_data['begin_timestamp'] = strtotime($_POST['start_time']);
                        // $wx_data['end_timestamp'] = strtotime($_POST['end_time']);
                        $update_wx_card = httpRequest('https://api.weixin.qq.com/card/update?access_token=' . $res['access_token'], 'post', json_encode($update_info, JSON_UNESCAPED_UNICODE));
                        $update_wx_card = json_decode($update_wx_card[1], true);

                        //修改库存
                        $wx_data['card_id'] = $_POST['wx_cardid'];
                        // $wx_data['color'] = $_POST['color'];
                        $_POST['num_add'] = 1;
                        intval($_POST['num_add']) && $wx_data['increase_stock_value'] = $add > 0 ? intval($_POST['num_add']) : 0;
                        intval($_POST['num_add']) && $wx_data['reduce_stock_value'] = $add < 0 ? $_POST['num_add'] : 0;
                        $update_wx_card = httpRequest('https://api.weixin.qq.com/card/modifystock?access_token=' . $res['access_token'], 'post', json_encode($wx_data, JSON_UNESCAPED_UNICODE));
                        $update_wx_card = json_decode($update_wx_card[1], true);
                        $errorms = $update_wx_card['errmsg'];
                    }
                }

                $date_log['type'] = 1;
                $date_log['mer_id'] = $this->merchant_session['mer_id'];
                $date_log['add'] = $add > 0 ? 1 : 0;
                $date_log['add_time'] = time();
                $date_log['num'] = $_POST['num_add'];
                $date_log['coupon_id'] = $_POST['coupon_id'];
                M('Coupon_add_log')->add($date_log);
                $this->success(L_('保存成功！') . $errorms, U('card_new_coupon'));
            } else {

                $this->error(L_('保存失败！'));
            }
        } else {
            $return = D('Card_new_coupon')->cate_platform(); //模板中定义相关中文名字
            $this->assign("category", $return['category']);
            $coupon = D('Card_new_coupon')->where(array('coupon_id' => $_GET['coupon_id']))->find();
            $coupon['platform'] = unserialize($coupon['platform']);
            $coupon['wx_param'] = unserialize($coupon['wx_param']);
            $coupon['store_id'] = explode(',', $coupon['store_id']);
            $coupon['now_num'] = $coupon['now_num'];
            foreach ($coupon['platform'] as &$vv) {
                $vv = $return['platform'][$vv];
            }
            $coupon['platform'] = implode(',', $coupon['platform']);
            $coupon['cate_name'] = $coupon['cate_name'] == 'all' ? L_('全品类通用') : $return['category'][$coupon['cate_name']];
            if (empty($coupon['cate_id'])) {
                $coupon['cate_id'] = L_('全部分类');
            } else {
                $coupon['cate_id'] = unserialize($coupon['cate_id']);
                $coupon['cate_id'] = $coupon['cate_id']['cat_name'];
            }
            $store_list = D('Merchant_store')->get_store_list_by_merId($this->merchant_session['mer_id']);
            $this->assign("store_list", $store_list);
            $color_list = D('System_coupon')->color_list();

            $coupon['buy_goods_list'] = unserialize($coupon['point_goodids']);
            $coupon['goodids'] = '';
            if(!empty($coupon['buy_goods_list'])){
                $condition = array();
                $database = null;
                $mallgoods_list = array();
                $groupgoods_list = array();
                $shopgoods_list = array();
                $result = array();
                $temp = array();
                if($coupon['buy_goods_list']['mall']){//商城商品
                    $database = D(_view('Shop_goods'));
                    $condition['goods_id']=array('in',$coupon['buy_goods_list']['mall']);
                    $field = 'goods_id, name, last_time, price';
                    $mallgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($mallgoods_list as $key => $value) {
                        $mallgoods_list[$key]['model'] = '商城';
                        $mallgoods_list[$key]['type'] = 'mall';
                        $result[] = $mallgoods_list[$key];
                        $temp[] = 'mall'.'-'.$value['goods_id'];
                    }
                }
                if($coupon['buy_goods_list']['shop']){
                    $database = D(_view('Shop_goods'));
                    $condition['goods_id']=array('in',$coupon['buy_goods_list']['shop']);
                    $field = 'goods_id, name, last_time, price';
                    $shopgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($shopgoods_list as $key => $value) {
                        $shopgoods_list[$key]['model'] = '外卖';
                        $shopgoods_list[$key]['type'] = 'shop';
                        $result[] = $shopgoods_list[$key];
                        $temp[] = 'shop'.'-'.$value['goods_id'];
                    }
                }
                if($coupon['buy_goods_list']['group']){
                    $database = D('Group');
                    $condition = array('group_id'=>array('in', $coupon['buy_goods_list']['group']));
                    $field = 'group_id as goods_id, name, last_time, price';
                    $groupgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($groupgoods_list as $key => $value) {
                        $groupgoods_list[$key]['model'] = '团购';
                        $groupgoods_list[$key]['type'] = 'group';
                        $result[] = $groupgoods_list[$key];
                        $temp[] = 'group'.'-'.$value['goods_id'];
                    }
                }
                if($coupon['buy_goods_list']['new_mall']){//新版商城商品
                    $database = M('Mall_goods');
                    $condition['goods_id']=array('in',$coupon['buy_goods_list']['new_mall']);
                    $field = 'goods_id, name, update_time as last_time, price';
                    $mallgoods_list = $database->field($field)->where($condition)->select();
                    foreach ($mallgoods_list as $key => $value) {
                        $mallgoods_list[$key]['goods_id'] = (int)$value['goods_id'];
                        $mallgoods_list[$key]['model'] = '新版商城';
                        $mallgoods_list[$key]['type'] = 'new_mall';
                        $result[] = $mallgoods_list[$key];
                        $temp[] = 'new_mall'.'-'.$value['goods_id'];
                    }
                }
                $coupon['buy_goods_list'] = $result;
                $coupon['goodids'] = implode(',', $temp);
            }

            $this->assign("color_list", $color_list);
            $this->assign("coupon", $coupon);
            $this->display('add_coupon');
        }
    }

    public function ajax_ordertype_cateid()
    {
        if ($_POST['order_type'] == 'meal') {
            $cate_id = D(ucfirst($_POST['order_type']) . '_store_category')->field('cat_id,cat_name')->where(array('cat_status' => 1, 'cat_fid' => 0))->select();
        }
//        else if($_POST['order_type']=='mall'){
//            $cate_id = D('Goods_category')->field('id as cat_id,name as cat_name')->where(array('fid' => 0))->order('`sort` DESC, `id` ASC')->select();
//        }
        else {
            $cate_id = D(ucfirst($_POST['order_type']) . '_category')->field('cat_id,cat_name')->where(array('cat_status' => 1, 'cat_fid' => 0, 'is_del'=>0))->select();
        }
        echo json_encode($cate_id);
    }

    //优惠券领取记录
    public function had_pull()
    {
        $now_card = M(_view('Card_new'))->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
        $where['card_id'] = $now_card['card_id'];
        $order_string = 'h.receive_time DESC ,h.id DESC';
        $coupon = M('Card_new_coupon_hadpull');
        $count_count = $coupon->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_count, 15);
        $coupon_list = $coupon->join('as h left join ' . C('DB_PREFIX') . 'card_new_coupon c ON h.coupon_id = c.coupon_id')->join(C('DB_PREFIX') . 'user u ON h.uid = u.uid')->field('h.id,c.name,u.nickname,h.num,h.receive_time,h.is_use,h.phone')->where($where)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('coupon_list', $coupon_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //编辑分组
    public function card_group()
    {
        $card_group_count = M('Card_group')->where(array('mer_id' => $this->merchant_session['mer_id']))->count();
        import('@.ORG.merchant_page');
        $p = new Page($card_group_count, 15);
        $card_group_list = M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($card_group_list as &$v) {
            $v['user_count'] = M('Card_userlist')->where(array('mer_id' => $this->merchant_session['mer_id'], 'gid' => $v['id']))->count();
        }
        $this->assign('card_group_list', $card_group_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function add_card_group()
    {
        if ($_POST) {
            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            if (empty($_POST['name'])) {
                $this->error(L_('名字不能为空！'));
            }

            if (empty($_POST['des'])) {
                $this->error(L_('备注不能为空！'));
            }

            $res = false;
            if (empty($_POST['gid'])) {
                $res = M('Card_group')->add($_POST);
                //多语言表增加数据
                $lang_model = D('Lang');
                $_POST['id'] = $res;
                $lang_model->add_lang_data('Card_group',$_POST);
            } else {
                $date['mer_id'] = $_POST['mer_id'];
                $date['name'] = $_POST['name'];
                $date['des'] = $_POST['des'];
                $res = M('Card_group')->where(array('id' => $_POST['gid']))->save($date);
                //多语言表修改数据
                $lang_model = D('Lang');
                $_POST['id'] = $_POST['gid'];
                $lang_model->edit_lang_data('Merchant_store',$date);

            }
            if ($res !== false) {
                $this->success(L_('保存成功！'));
            } else {
                $this->error(L_('保存失败！'));
            }
        } else {
            if (!empty($_GET['gid'])) {
                $now_group = M(_view('Card_group'))->where(array('id' => $_GET['gid']))->find();
                $this->assign('now_group', $now_group);
            }
            $this->display();

        }
    }

    //分组用户列表
    public function card_group_user_list()
    {
        $gid = $_GET['gid'];
        $mer_id = $this->merchant_session['mer_id'];
        $user_list = D('Card_new')->get_user_by_gid_merid($gid, $mer_id);
        $this->assign('user_list', $user_list);
        $card_group =M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $card_group_formart = array();
        foreach ($card_group as $key => $value) {
            $card_group_formart[$value['id']] = $value['name'];
        }
        $this->assign('card_group', $card_group_formart);
        $this->display();
    }

    //派发优惠券
    public function send_coupon()
    {
        $mer_id = $this->merchant_session['mer_id'];
        $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($mer_id, 1, true, true);
        if (empty($coupon_list)) {
            $this->error(L_('没有可分配的优惠券!'));
        }
        $this->assign('coupon_list', $coupon_list);
        $card_group_list = M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        foreach ($card_group_list as &$v) {
            $v['user_count'] = M('Card_userlist')->where(array('mer_id' => $this->merchant_session['mer_id'], 'gid' => $v['id']))->count();
        }
        $this->assign('card_group', $card_group_list);
		//自定义标签
		$diy_list = D('Merchant_card_field')->get_field_list();
		$this->assign('diy_list', $diy_list);

        $labelList=M('Employee_card_lable')->where(array('mer_id' => $this->merchant_session['mer_id'], 'is_del' => 0))->select();
        $this->assign('label_list', $labelList);
        $this->display();
    }

    public function send_all()
    {
        $mer_id = $this->merchant_session['mer_id'];
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        $user_count = M('Card_userlist')->where($where)->count();
        $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($mer_id,0,true,true);
        foreach ($coupon_list as $key => $vv) {
            if ($vv['num'] < $user_count) {
                unset($coupon_list[$key]);
            }
        }
        if (empty($coupon_list)) {
            $this->error(L_('没有可分配的优惠券!'));
        }
        $this->assign('coupon_list', $coupon_list);
        $card_group_list = M(_view('Card_group'))->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        foreach ($card_group_list as &$v) {
            $v['user_count'] = M('Card_userlist')->where(array('mer_id' => $this->merchant_session['mer_id'], 'gid' => $v['id']))->count();
        }
        $this->assign('card_group', $card_group_list);
        $this->display();
    }

    //派发记录
    public function send_history()
    {
        $mer_id = $this->merchant_session['mer_id'];
        import('@.ORG.merchant_page');
        $count = M('Card_coupon_send_history')->where(array('mer_id' => $mer_id))->count();
        $p = new Page($count, 15);
        $res = M('Card_coupon_send_history')->join('as h left join ' . C('DB_PREFIX') . 'user u ON h.uid = u.uid')
            ->join(C('DB_PREFIX') . 'card_new_coupon c ON h.coupon_id = c.coupon_id')->field('h.*,u.nickname,u.phone,c.name as coupon_name')->where(array('h.mer_id' => $mer_id))->order('add_time DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('history', $res);

        $this->display();
    }

    public function ajax_send()
    {
        $mer_id = $this->merchant_session['mer_id'];
        $coupon_list = explode(',', $_POST['coupon_list']);
        $coupon_send_num = explode(',', $_POST['coupon_send_num']);
        $uid = $_POST['uid'];
        $model = D('Card_new_coupon');
        foreach ($coupon_list as $key=>$item) {
            $send_num = isset($coupon_send_num[$key]) ? ($coupon_send_num[$key] ?: 1) : 1;
            $success = 0;
            $fail = 0;
            $fail_msg = [];
            for ($index = 0; $index < $send_num; $index++) {
                $tmp = $model->had_pull($item, $uid, '', false, true, false, 0, 1,true);
                $model->decrease_sku(0, 1, $item);//网页领取完，微信卡券库存需要同步减少
                if ($tmp['error_code'] > 0) {
                    $fail++;
                    $fail_msg[] = $this->coupon_error($tmp['error_code']);
                } else {
                    $success++;
                }
            }
            if ($fail > 0) {
                $tmp['msg'] = sprintf("部分发送成功,成功数量%d,失败数量%d,失败原因如下:%s", $success, $fail, implode(array_unique($fail_msg)));
            } else {
                $tmp['msg'] = sprintf("全部发送成功,数量%d", $success);
            }
            $data['uid'] = $uid;
            $data['mer_id'] = $mer_id;
            $data['coupon_id'] = $item;
            $data['error_code'] = $tmp['error_code'];
            $data['msg'] = $tmp['msg'];
            $data['add_time'] = time();
            $id = M('Card_coupon_send_history')->add($data);
            //多语言表增加数据
            $lang_model = D('Lang');
            $_POST['id'] = $id;
            $lang_model->add_lang_data('Card_coupon_send_history',$data);
            $return[$tmp['coupon']['coupon_id']]['error_msg'] = $tmp['msg'];
            $return[$tmp['coupon']['coupon_id']]['coupon_name'] = $tmp['coupon']['name'];
            $return[$tmp['coupon']['coupon_id']]['send_code'] = $tmp['error_code'];
        }
        $this->ajaxReturn(array('error_code' => 0, 'return' => $return));
    }

    public function coupon_error($error_code)
    {
        switch ($error_code) {
            case '0':
                $error_msg = L_('领取成功');
                break;
            case '1':
                $error_msg = L_('领取发生错误');
                break;
            case '2':
                $error_msg = L_('优惠券已过期');
                break;
            case '3':
                $error_msg = L_('优惠券已经领完了');
                break;
            case '4':
                $error_msg = L_('只允许新用户领取');
                break;
            case '5':
                $error_msg = L_('不能再领取了');
                break;
        }
        return $error_msg;
    }

    public function send()
    {
        if($_POST['diy_ids']){
            $res = D('Card_new')->add_send_log($this->merchant_session['mer_id'], $_POST['card_group_id'], $_POST['coupon_id'],'',$_POST['diy_ids'],$_POST['coupon_send_num']);
            // $this->assign('user_list',$res);
            if ($res['error_code']) {
                $this->error($res['msg']);
            } else {
                $this->template_plan_msg($res['id']);
                $this->success($res['msg']);
            }
            die;
        }else if ($_POST['card_group_id']) {
            //$res = D('Card_new')->get_userlist_by_car_group($_GET['card_group_id']);
            $res = D('Card_new')->add_send_log($this->merchant_session['mer_id'], $_POST['card_group_id'], $_POST['coupon_id'],'','',$_POST['coupon_send_num']);
            // $this->assign('user_list',$res);
            if ($res['error_code']) {
                $this->error($res['msg']);
            } else {
                $this->template_plan_msg($res['id']);
                $this->success($res['msg']);
            }
            die;
        } else if ($_POST['all']) {
            $_POST['coupon_send_num'] = $_POST['coupon_send_num'] === null ? 1 : $_POST['coupon_send_num'];
            //$res = D('Card_new')->get_card_user_list_by_mer_id($this->merchant_session['mer_id']);
            $res = D('Card_new')->add_send_log($this->merchant_session['mer_id'], '', $_POST['coupon_id'],'','',$_POST['coupon_send_num']);
            //$this->assign('user_list',$res);
            if ($res['error_code']) {
                $this->error($res['msg']);
            } else {
                $this->template_plan_msg($res['id']);
                $this->success($res['msg']);
            }
            die;
        } else if ($_POST['uid']) {
            $res = D('Card_new')->add_send_log($this->merchant['mer_id'], '', $_POST['coupon_id'], $_POST['uid']);
            if ($res['error_code']) {
                $this->returnCode('20170118', '', $res['msg']);

            } else {
                $this->template_plan_msg($res['id']);
                $this->returnCode(0, array('msg' => $res['msg']));
            }

        } else if ($_GET['uid']) {
            $user = M('User')->where(array('uid' => $_GET['uid']))->field('uid,nickname')->find();
            $this->assign('user', $user);
        }else if(!empty($_POST['userLabel'])){
            $coupon_ids = explode(',', $_POST['coupon_id']);
 
            $couponList = M('Card_new_coupon')->field('name,limit')->where(['coupon_id'=>['in', $coupon_ids]])->select();
            foreach($couponList as $key => $val){
                if($_POST['coupon_send_num'] > $val['limit']){
                    $this->error("发券数量不能超过【{$val['name']}】优惠券领取数量限制【{$val['limit']}】");
                }
            }

            $userLabel = substr($_POST['userLabel'],0,strlen($_POST['userLabel'])-1);
            $where['_string'] = '';
            $lable_ids = explode(',', $userLabel);
            $where['_string'] .= '(';
            foreach ($lable_ids as $lk => $lv) {
                if ($lk == 0) {
                    $where['_string'] .= 'FIND_IN_SET("' . $lv . '", lable_ids)';
                } else {
                    $where['_string'] .= ' OR FIND_IN_SET("' . $lv . '", lable_ids)';
                }
            }
            $where['_string'] .= ')';
            $employee_list=M('Employee_card_user')->where($where)->select();
            if(!empty($employee_list)){
                foreach ($employee_list as $kr=>$vr){
                    $res = D('Card_new')->add_send_log($this->merchant_session['mer_id'], '', $_POST['coupon_id'], $vr['uid'],'',$_POST['coupon_send_num']);
                    if ($res['error_code']) {
                        $this->error($res['msg']);
                    } else {
                        $this->template_plan_msg($res['id']);
                    }
                }
                $this->success("派发成功");
            }
        }
        $this->assign('coupon_list', $_GET['coupon_id']);
        $this->assign('coupon_send_num', $_GET['coupon_send_num']);
        $this->display();
    }

    // 添加临时计划任务
    public function template_plan_msg($send_id)
    {
        import('@.ORG.plan');
        $plan_class = new plan();
        $param = array(
            'file' => 'send_coupon',
            'plan_time' => time(),
            'param' => array(
                'id' => $send_id,
            ),
        );
        $plan_class->addTask($param);
    }

    //获取可以派发的优惠券列表
    public function ajax_get_send_coupon()
    {
        if (!empty($_POST['card_group_id'])) {
            $count = M('Card_userlist')->where(array('gid' => array('in', $_POST['card_group_id'])))->count();
            $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($this->merchant_session['mer_id'], 1,true,true);
            foreach ($coupon_list as &$v) {
                if ($count > $v['num'] - $v['had_pull']) {
                    $v['disable'] = true;
                } else {
                    $v['disable'] = false;
                }
            }
        } else {
            $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($this->merchant_session['mer_id'], 1,true,true);

        }
        $this->ajaxReturn($coupon_list);
    }

    public function ajax_get_user_merid()
    {
        $where['c.mer_id'] = $this->merchant_session['mer_id'];
        $where['u.' . $_POST['keyword']] = array('like', "%" . trim($_POST['search_val']) . "%");
        $res = M('Card_userlist')->join('as c left join ' . C('DB_PREFIX') . 'user u ON c.uid = u.uid')->field('c.*,u.nickname,u.phone')->where($where)->limit(50)->select();

        //查询粉丝
        $fans_lists = M('Merchant_user_relation')->join('as c left join ' . C('DB_PREFIX') . 'user u ON c.openid = u.openid')->field('c.*,u.nickname,u.phone,u.uid')->where($where)->limit(50)->select();

        if ($res) {
            foreach ($res as &$card_user) {
                $card_user['is_card'] = 1;
                foreach ($fans_lists as $key => $fans) {
                    if ($fans['uid'] == $card_user['uid']) {
                        unset($fans_lists[$key]);
                    }
                }
            }
            $res = $fans_lists ? array_merge($res, $fans_lists) : $res;
        } else {
            $res = $fans_lists;
        }
        $this->ajaxReturn($res);
    }

    #微信购买派发设置
    public function weixin_send()
    {
        $mer_id = $this->merchant_session['mer_id'];
        $now_card = M(_view('Card_new'))->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
        if (!$now_card['weixin_send']) {
            $this->error(L_('没有开启微信购买派送优惠券功能!'));
        }
        $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($mer_id);
        if (empty($coupon_list)) {
            $this->error(L_('没有可分配的优惠券!'));
        }
        $now_card['weixin_send_couponlist'] = explode(',', $now_card['weixin_send_couponlist']);
        $this->assign('coupon_list', $coupon_list);
        $this->assign('now_card', $now_card);
        if (IS_POST) {
            if (!is_numeric($_POST['money']) || $_POST['money'] < 0) {
                $this->ajaxReturn(array('error_code' => 1, 'msg' => L_('金额设置错误!')));
            }
            $data['weixin_send_money'] = $_POST['money'];
            $data['weixin_send_couponlist'] = $_POST['coupon_id'];

            if (M('Card_new')->where(array('mer_id' => $mer_id))->save($data)) {
                $this->ajaxReturn(array('error_code' => 0, 'msg' => L_('保存成功!')));
            }
        } else {
            $this->display();
        }
    }


    public function recharge_list($is_return_all = false)
    {
        $searchtype = $_GET['searchtype'] ?? '';
        $keyword = $_GET['keyword'] ?? '';

        $mer_id = $this->merchant_session['mer_id'];
        import('@.ORG.merchant_page');
        $where['u.mer_id'] = $mer_id;
        if($keyword){
            switch ($searchtype) {
                case 'phone':
                    $where['uu.phone'] = $keyword;
                    break;
                case 'card_id':
                    $where['_complex'] = [
                        ['u.id'=>$keyword],
                        ['u.wx_card_code'=>$keyword],
                        '_logic'=>'or'
                    ];
                    break;
                case 'physical_id':
                    $where['u.physical_id'] = $keyword;
                    break;
                case 'nickname':
                    $where['uu.nickname'] = $keyword;
                    break;
            }
        }
        if ($_GET['begin_time']) {
            $where['c.time'][] = ['gt', strtotime($_GET['begin_time'])];
        }
        if ($_GET['end_time']) {
            $where['c.time'][] = ['lt', strtotime($_GET['end_time']) + 86400];
        }
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'card_userlist as u ON c.card_id = u.id ';
        $join .= 'LEFT JOIN ' . C('DB_PREFIX') . 'user as uu on uu.uid=u.uid';

        if ($is_return_all) {
            $result = M(_view('Card_new_record'))->alias('c')
                ->field('c.*,uu.phone,uu.nickname')
                ->join($join)
                ->where($where)
                ->order('c.time DESC')
                ->select();
            return $result;
        }

        $count = M(_view('Card_new_record'))->alias('c')->join($join)->where($where)->count();
        $p = new Page($count, 20);

        $result = [];
        $report = [
            'total_money_add' => 0,
            'total_money_use' => 0,
            'total_score_add' => 0,
            'total_score_use' => 0,
            'total_coupon_add' => 0,
            'total_coupon_use' => 0,
            'total_online_money' => 0, //在线充值金额
        ];
        if ($count > 0) {
            $result = M(_view('Card_new_record'))->alias('c')
                ->field('c.*,uu.phone,uu.nickname')
                ->join($join)
                ->where($where)
                ->order('c.time DESC')
                ->limit($p->firstRow, $p->listRows)
                ->select();
            $fields = [
                'SUM(`money_add`)' => 'total_money_add',
                'SUM(`money_use`)' => 'total_money_use',
                'SUM(`score_add`)' => 'total_score_add',
                'SUM(`score_use`)' => 'total_score_use',
                'SUM(`coupon_add`)' => 'total_coupon_add',
                'SUM(`coupon_use`)' => 'total_coupon_use',
            ];
            $report = M(_view('Card_new_record'))->alias('c')->join($join)->where($where)->field($fields)->find();
            if($report){
                if ($_GET['begin_time']) {
                    if(isset($where['c.time'])){
                        unset($where['c.time']);
                    }
                    $where['c.add_time'][] = ['gt', strtotime($_GET['begin_time'])];
                }
                if ($_GET['end_time']) {
                    if(!$_GET['begin_time']&&isset($where['c.time'])){
                        unset($where['c.time']);
                    }
                    $where['c.add_time'][] = ['lt', strtotime($_GET['end_time']) + 86400];
                }
                $where['o.paid'] = 1;
                $join = ' INNER JOIN ' . C('DB_PREFIX') . 'plat_order as o on c.order_id = o.business_id AND o.business_type = "card_new_recharge"';
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'card_userlist as u ON c.uid  = u.uid AND c.mer_id = u.mer_id';
                $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'user as uu on uu.uid=u.uid';
                $report['total_online_money'] = M('Card_new_recharge_order')->alias('c')->join($join)->where($where)->sum('c.money');
                $report['total_online_money'] = $report['total_online_money']?:0;
            }
        }
        $pagebar = $p->show();
        $this->assign('record', $result);
        $this->assign('report', $report);
        $this->assign('pagebar', $pagebar);
        $this->display();
    }


    public function show()
    {
        echo '<html><head><style>*{margin:0;padding:0;}</style></head><body><img style="width:320px;height:600px;float:left" src="./static/images/wx_coupon.png" />
	<img style="width:320px;height:600px;float:left" src="./static/images/wx_coupon2.png" /></body></html>';
        die;

    }

    public function export()
    {
        $param = $_POST;
        $param['type'] = 'card_new';
        $param['rand_number'] = time();
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if ($res = D('Order')->order_export($param)) {
            echo json_encode(array('error_code' => 0, 'msg' => L_('添加导出计划成功'), 'file_name' => $res['file_name'], 'export_id' => $res['export_id'], 'rand_number' => $param['rand_number']));
        } else {
            echo json_encode(array('error_code' => 1, 'msg' => L_('导出失败')));
        }
        die;
    }


    public function delete_coupon()
    {
        $coupon_id = $_GET['coupon_id'];
        $coupon = M('Card_new_coupon')->where(array('coupon_id' => $coupon_id))->find();
        if ($coupon['wx_cardid'] != '') {

            import('@.ORG.plan');
            $plan_class = new plan();
            $param = array(
                'file' => 'delete_wx_coupon',
                'plan_time' => time(),
                'param' => array(
                    'id' => $coupon_id,
                    'type' => 1,//
                ),
            );
            $plan_class->addTask($param);


        }
        M('Card_new_coupon')->where(array('coupon_id' => $coupon_id))->setField('status', 4);
        $this->success(L_('删除成功'), U('Card_new/card_new_coupon'));
    }

    public function coupon_add_log()
    {
        $count = M('Coupon_add_log')->where(array('coupon_id' => $_GET['coupon_id'], 'type' => 1))->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $res = M('Coupon_add_log')->where(array('coupon_id' => $_GET['coupon_id']))->limit($p->firstRow, $p->listRows)->select();
        $this->assign('list', $res);
        $this->assign('pagebar', $p->show());
        $this->display();
    }


    // 批量修改余额
    public function money_edit()
    {
        if (IS_POST) {
            $gid = $_POST['gid'];
            $ids = $_POST['ids'];
            $give_money = getFormatNumber($_POST['give_money']);
            $type = $_POST['type'];
            if (!$ids) {
                $this->error(L_('没有要修改的会员'));
            }
            if ($give_money<=0) {
                $this->error(L_('请输入正确金额'));
            }
            $user_list = M('Card_userlist')->where(array('id'=>array('in',$ids)))->select();
            if ($type == 1) {
                // 增加余额
                foreach ($user_list as $key => $value) {
                    if ($value['id']) {
                        if (M('Card_userlist')->where(array('id' => $value['id']))->setInc('card_money_give', $give_money)) {
                            $data_money['card_id'] = $value['id'];
                            $data_money['type'] = 1;
                            $data_money['money_add'] = $give_money;
                            $data_money['desc'] = L_('商家后台批量增加余额');
                            D('Card_new')->add_row($data_money);
                        }
                    }
                }
            }elseif($type == 2){
                // 减少余额
                foreach ($user_list as $key => $value) {
                    if ($value['card_money_give']<$give_money) {
                        $this->error(L_('操作失败'));
                    }
                }
                foreach ($user_list as $key => $value) {
                    if ($value['id']) {
                       if( M('Card_userlist')->where(array('id'=>$value['id']))->setDec('card_money_give',$give_money)){
                           $data_money['card_id'] = $value['id'];
                           $data_money['type'] = 2;
                           $data_money['money_use'] = $give_money;
                           $data_money['desc'] = L_('商家后台批量减少余额');
                           D('Card_new')->add_row($data_money);
                       }
                    }
                }
            }
            $this->success(L_('操作成功'));
        }else{
            $gid = $_GET['gid'];
            $ids = $_GET['ids'];
            $user_count = M('Card_userlist')->where(array('id'=>array('in',$ids)))->count();
            $card_group = M('Card_group')->where(array('id'=>$gid))->find();
            $this->assign('user_count', $user_count);
            $this->assign('card_group', $card_group);
        }
        $this->display();
    }

    // 非自充余额清零
    public function money_clear()
    { 

        if (IS_POST) {
            $gid = $_POST['gid']; 
            if (!$gid) {
                $this->error(L_('分组不存在'));
            }
            $user_list = M('Card_userlist')->where(array('gid'=>$gid))->select();
            if (!$user_list) {
                $this->error(L_('没有要操作的会员'));
            }
           
            $res = M('Card_userlist')->where(array('gid'=>$gid))->setField('card_money_give',0);
            if ($res) {
                $this->success(L_('操作成功'));
            }else{
                $this->error(L_('操作失败'));
            }
        }else{
            $gid = $_GET['gid'];
            $card_group = M('Card_group')->where(array('id'=>$gid))->find();
            $user_count = M('Card_userlist')->where(array('gid'=>$gid))->count();
            $this->assign('user_count', $user_count);
            $this->assign('card_group', $card_group);
        }
        $this->display();
     }

    /**
     * 导出会员卡消费记录
     * @author 张涛
     * @date 2020/06/13
     */
    public function exportRechargeList()
    {
        $result = $this->recharge_list(true);
        $tabHeader = [
            ['title' => L_('会员卡') . 'ID', 'value_key' => 'card_id'],
            ['title' => L_('会员名称'), 'value_key' => 'nickname'],
            ['title' => L_('会员手机'), 'value_key' => 'phone'],
            ['title' => L_('时间'), 'value_key' => 'time', 'formate' => ['date', 'Y-m-d H:i:s']],
            ['title' => L_('详情'), 'value_key' => 'desc'],
            ['title' => L_('金额增加') . '(' . C('config.Currency_txt') . ')', 'value_key' => 'money_add'],
            ['title' => L_('金额减少') . '(' . C('config.Currency_txt') . ')', 'value_key' => 'money_use'],
            ['title' => C('config.score_name') . L_('增加（分）'), 'value_key' => 'score_add'],
            ['title' => C('config.score_name') . L_('减少（分）'), 'value_key' => 'score_use'],
            ['title' => L_('优惠券增加') . '(' . C('config.Currency_txt') . ')', 'value_key' => 'coupon_add'],
            ['title' => L_('优惠券减少') . '(' . C('config.Currency_txt') . ')', 'value_key' => 'coupon_use'],
        ];
        $config = [
            'header' => $tabHeader,
            'body' => $result,
            'file_name' => L_('会员卡消费记录导出')];
        (new ShopAction())->exportExcel($config);
    }


    ################## 微信商圈积分同步start ######################

    /**
     * 微信商圈积分记录
     */
    public function wx_circleintegral()
    {
        $condition = [];
        $condition['mer_id'] = $this->merchant_session['mer_id'];
        $field = 'id,mer_id,add_time,status,sync_time,amount,refund_amount,event_type,sync_type,shop_name,uid,phone,card_id,score';

        $keywords = I('keywords');
        $begin_time = I('begin_time');
        $end_time = I('end_time');
        $shop_name = I('shop_name');
        if(!empty($begin_time) && !empty($end_time)){
            if($end_time < $begin_time){
                $this->error(L_('结束时间不能小于开始时间'));
            }
            $condition['add_time'] = ['between', [strtotime($begin_time), strtotime($end_time) + 86400]];
        }
        if(!empty($shop_name)){
            $condition['shop_name'] = array('like', '%'.$shop_name.'%');
        }
        if(!empty($keywords)){
            $where['card_id'] = array('like', '%'.$keywords.'%');
            $where['phone'] = array('like','%'.$keywords.'%');
            $where['_logic'] = 'or';
            $condition['_complex'] = $where;
        }
        $wxSync = M('Wx_sync_score_log');
        $count_count = $wxSync->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_count, 15);
        $wxSyncList = $wxSync->field($field)->where($condition)->order('add_time DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($wxSyncList as $key => $v) {
            $wxSyncList[$key]['add_time_txt'] = date('Y-m-d H:i:s', $v['add_time']);
            $wxSyncList[$key]['sync_time_txt'] = date('Y-m-d H:i:s', $v['sync_time']);
            $wxSyncList[$key]['status_txt'] = $v['status'] == 1 ? '已同步' : '未同步';
            $wxSyncList[$key]['event_type_txt'] = $v['event_type'] == 1 ? '支付' : '退款';
            $sync_type_txt = '';
            if($v['sync_type'] == 1){
                $sync_type_txt = '自动同步';
            }else if($v['sync_type'] == 2){
                $sync_type_txt = '手动同步';
            }
            $wxSyncList[$key]['sync_type_txt'] = $sync_type_txt;
        }
        
        $this->assign("wxSyncList", $wxSyncList);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    //签名生成
    public function sign_encode($body = '', $url, $mchid, $serial_no, $private_key,$type)
    {
        // Authorization: <schema> <token>
        $nostr = time(); //随机字符串
        $timestamp = time(); //时间戳
        $http_method = $type; //提交方式
        $url_parts = parse_url($url);
        $canonical_url = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : "")); //这个是官方样例里面的
        $mch_private_key = file_get_contents(replace_file_domain($private_key)); //商户的私钥
        $message = $http_method . "\n" . $canonical_url . "\n" . $timestamp . "\n" . $nostr . "\n" . $body . "\n"; //拼接加密字符串
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption'); //进行加密
        $sign = base64_encode($raw_sign); //加密
        //$schema = 'WECHATPAY2-SHA256-RSA2048'; //官方写的 完全不知道干啥用就注释了
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', $mchid, $nostr, $timestamp, $serial_no, $sign); //得出结果 然后传递给Authorization这个头文件
        return $token;
    }


    //积分同步
    protected function toget_wx($data){
        $w_user = M('Weixin_bind_user')->where(['wxapp_openid' => $data['openid']])->find();
        if (!empty($w_user)) {
            if (!empty($w_user['wxapp_openid'])) {
                $w_app = M('Weixin_app_bind')->where(['bind_type' => 0, 'other_id' => $w_user['mer_id']])->find();
                $APIv3_key = $w_app['apiv3_key'];
                $serial_no = $w_app['serial_no'];
                $private_file = $w_app['private_key'];
                if ($w_app['circle_status']) {
                    import('ORG.Net.Http');
                    $url = 'https://api.mch.weixin.qq.com/v3/businesscircle/points/notify';
                    $syncData = [
                        'transaction_id' => $data['transaction_id'],
                        'appid' => $data['appid'],
                        'openid' => $data['openid'],
                        'earn_points' => $data['earn_points'],
                        'increased_points' => $data['increased_points'],
                        'points_update_time' => $data['points_update_timedate'],
                    ];
                    $signature = $this->sign_encode(json_encode($syncData), $url, $data['mchid'], $serial_no, $private_file, 'POST');
                    $headers = [
                        'Content-Type:application/json',
                        'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
                        'Authorization: WECHATPAY2-SHA256-RSA2048 ' . $signature
                    ];
                    $return = Http::curlPostOwnWithHeader($url, json_encode($syncData), $headers, 15);
                    fdump([$url, $syncData, $headers, $return], "wxapp_circle_pay_after");
                    $jsonrt = json_decode($return, true);
                    $jsonrt['req_data'] = ['req'=>$syncData,'header'=>$headers];
                    return $jsonrt;
                }
            }
        }
    }

    public function sync_score(){
        $id = I('post.id');
        $taskMod = M('Wx_sync_score_log');
        $record = $taskMod->where(['id' => $id])->find();

        if(!$record){
            echo json_encode(['code'  =>  1001,'msg'   =>  '记录不存在']);
            exit;
        }

        if($record['status'] == 1){
            echo json_encode(['code'  =>  1001,'msg'   =>  '此记录已同步']);
            exit;
        }

        $wxapp_openid = $record['openid'];//用户小程序openid
        $w_user = M('Weixin_bind_user')->alias('bu')->join(C('DB_PREFIX') . 'user u ON bu.uid = u.uid')
            ->where(['bu.wxapp_openid' => $wxapp_openid, 'u.status' => ['NEQ', 4]])
            ->field('bu.*,u.uid,u.phone')
            ->find();

        $data = [];
        $data['openid'] = $record['openid'];
        $data['transaction_id'] = $record['transaction_id'];
        $data['appid'] = $record['appid'];
        $data['earn_points'] = true;
        //支付
        if($record['event_type'] == 1){
            $data['money'] = $record['amount'];//购买金额
            $data['type'] = 1;//增加积分
            $data['increased_points'] = intval($record['score']);
            $data['no_points_remarks'] = "【{$record['shop_name']}】微信商圈支付积分";
            
            $this->auto_get($w_user['uid'], $w_user['mer_id'], '', true, $record['score'], $record['shop_name']);
        }else{ //退款
            $data['money'] = $record['refund_amount'];//退款金额
            $data['type'] = 0;//减少积分
            $data['increased_points'] = intval($record['amount']) * -1;
            $data['no_points_remarks'] = "【{$record['shop_name']}】微信商圈退款积分";
            $user_card = M('Card_userlist')->where(array('uid' => $w_user['uid'], 'mer_id' => $w_user['mer_id']))->find();
            // $card = M(_view('Card_new'))->where(array('mer_id' => $w_user['mer_id']))->find();
            $date_row['card_id'] = $user_card['id'];
            $date_row['type'] = 2;
            $date_row['score_use'] = $record['score'] * -1;
            $date_row['desc'] = "【{$record['shop_name']}】微信商圈退款积分";
            $date_row['order_type'] = 20;
            $res = D('Card_new')->add_row($date_row);
            if($res){
                M('Card_userlist')->where(['id'=>$user_card['id']])->save([
                    'card_score'    => $user_card['card_score'] + $record['score']
                ]);
            }
        }
        $data['points_update_timedate'] = date('c', time());;
        $data['mchid'] = $record['mchid'];

        $res = $this->toget_wx($data);//积分同步

        if(!$res){
            echo json_encode(['code'  =>  1001,'msg'   =>  '同步失败！']);
            exit;
        }
        
        $update = [
            'status' => 1,
            'response_data' => json_encode($res ?? [], JSON_UNESCAPED_UNICODE),
            'sync_time' => time(),
            'sync_type' =>  2
        ];

        $taskMod->where(['id' => $id])->save($update);
        echo json_encode(['code'  =>  1000,'msg'   =>  '同步成功']);
        exit;
    }

    //自动领卡
    public function auto_get($uid, $mer_id, $wxcardcode = '', $is_auto = false, $amount, $shop_name='')
    {
        if ($card = M(_view('Card_new'))->where(array('mer_id' => $mer_id))->find()) {
            if (!$card['auto_get'] && !$card['auto_get_buy'] && !$is_auto) {
                fdump_sql('该商家' . $mer_id . '会员卡不能自动领卡', "wxapp_circle_pay_after");
                return array('status' => false);
            }
            if (!$card['status']) {
                fdump_sql('该商家' . $mer_id . '会员卡没有启用', "wxapp_circle_pay_after");
                return array('status' => false);
            }
            if($amount <= 0){
                fdump_sql(['err'=>"兑换积分为0！",$uid, $mer_id, $wxcardcode, $is_auto, $amount,$shop_name], "wxapp_circle_pay_after");
                return array('status' => false);
            }
            $user_card = M('Card_userlist')->where(array('uid' => $uid, 'mer_id' => $mer_id))->find();
            if (($uid && $user_card)) {
                $date_row['card_id'] = $user_card['id'];
                $date_row['type'] = 1;
                $date_row['score_add'] = $amount;
                $date_row['desc'] = "【{$shop_name}】微信商圈支付积分";
                $date_row['order_type'] = 20;
                D('Card_new')->add_row($date_row);

                $data_userlist['card_score'] = $user_card['card_score'] + $date_row['score_add'];
                M('Card_userlist')->where(array('id'=>$user_card['id']))->data($data_userlist)->save();
                return array('status' => true, 'card_id' => $user_card['card_id']);
            }
            $data['card_id'] = $card['card_id'];
            $data['mer_id'] = $mer_id;
            $data['uid'] = $uid;
            $data['card_money_give'] = $card['begin_money'];
            $data['card_score'] = $card['begin_score'];
            $data['add_time'] = time();
            $data['status'] = 1;
            $data['wx_card_code'] = $wxcardcode;
            if ($card_id = M('Card_userlist')->add($data)) {
                if ($card['begin_money'] > 0 || $card['begin_score'] > 0) {
                    $date_row['card_id'] = $card_id;
                    $date_row['type'] = 1;
                    $date_row['money_add'] = $card['begin_money'];
                    $date_row['score_add'] = $card['begin_score'];
                    $date_row['order_type'] = 20;
                    $date_row['desc'] = "【{$shop_name}】微信商圈支付积分";
                    D('Card_new')->add_row($date_row);
                }

                if ($amount > 0) {
                    $date_row['card_id'] = $card_id;
                    $date_row['type'] = 1;
                    $date_row['score_add'] = $amount;
                    $date_row['order_type'] = 20;
                    $date_row['desc'] = "【{$shop_name}】微信商圈支付积分";
                    D('Card_new')->add_row($date_row);
                }

                if ($now_user = M('User')->where(array('uid' => $uid))->find()) {
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
                    $model->sendTempMsg('OPENTM200964573', array('href' => C('config.site_url') . '/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id=' . $mer_id, 'wecha_id' => $now_user['openid'], 'first' => L_('您成功领取了商家【X1】的会员卡', $now_merchant['name']), 'keyword1' => $card_id, 'keyword2' => $now_user['nickname'], 'keyword3' => $now_user['phone'], 'keyword4' => date("Y年m月d日 H:i"), 'remark' => L_('感谢您的关注！')), $now_merchant['mer_id']);
                }
                return array('status' => true, 'card_id' => $card_id);
            } else {
                return array('status' => false);
            }
        } else {
            fdump_sql('未查询到商家' . $mer_id . '的会员卡', "wxapp_circle_pay_after");
            return array('status' => false);
        }
    }


    ################## 微信商圈积分同步end ######################

    /**
     * 批量修改会员卡用户分组
     */
    public function changeCradGroup(){
        $group_id = $_POST['group_id'];
        $ids = $_POST['ids'];
        if(!$group_id){
            $this->ajaxReturn(array('error_code' => 1, 'msg' => '请选择分组！'));
        }
        if(!$ids||!is_array($ids)){
            $this->ajaxReturn(array('error_code' => 2, 'msg' => '请选择会员！'));
        }
        $card_group = D('Card_group')->where(['mer_id'=>$this->mer_id,'id'=>$group_id])->find();
        if(!$card_group){
            $this->ajaxReturn(array('error_code' => 3, 'msg' => '分组不存在！'));
        }
        $res = D('Card_userlist')->where(['id'=>['in',$ids]])->save(['gid'=>$group_id]);
        if($res||$res===0){
            $this->ajaxReturn(array('error_code' => 0, 'msg' => '修改成功'));
        }else{
            $this->ajaxReturn(array('error_code' => 4, 'msg' => '修改失败'));
        }
    }
}
