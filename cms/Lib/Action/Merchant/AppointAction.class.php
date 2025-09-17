<?php

/**
 * 预约服务列表
 * AppointAction
 * @author yaolei
 */
class AppointAction extends BaseAction
{
    /* 服务列表 */
    public function index(){
        $database_appoint = D('Appoint');
        $database_merchant = D('Merchant');
        $database_category = D('Appoint_category');
        $condition_appoint=[
            'mer_id'=>$this->merchant_session['mer_id'],
        ];
        if (!empty($_GET['keyword'])) {
            $condition_appoint['appoint_name'] = ['LIKE', '%' . $_GET['keyword'] . '%'];
        }
        if (isset($_GET['payment_status']) && $_GET['payment_status'] != -1) {
            $condition_appoint['payment_status'] = intval($_GET['payment_status']);
        }
        if (!empty($_GET['begin_time'])) {
            $condition_appoint['create_time'][] = ['gt', strtotime($_GET['begin_time'])];
        }
        if (!empty($_GET['end_time'])) {
            $condition_appoint['create_time'][] = ['lt', strtotime($_GET['end_time']) + 86400];
        }
        $appoint_count = $database_appoint->where($condition_appoint)->count();

        import('@.ORG.merchant_page');
        $page = new Page($appoint_count, 20);
        $appoint_info = $database_appoint->field(true)->where($condition_appoint)->order('`appoint_id` DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
        $merchant_info = $database_merchant->field(true)->where('mer_id = ' . $this->merchant_session['mer_id'] . '')->select();
        $category_info = $database_category->field(true)->where($condition_appoint)->select();
        $appoint_list = $this->formatArray($appoint_info, $merchant_info, $category_info);
        $this->assign('appoint_list', $appoint_list);
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);

        $this->display();
    }


    public function add(){
        $database_store = D('Merchant_store');
        $database_category = D('Appoint_category');
        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $database_appoint_custom_field = D('Appoint_custom_field');

        if (IS_POST) {
            $database_appoint = D('Appoint');
            $database_appoint_product = D('Appoint_product');
            $database_appoint_store = D('Appoint_store');

            $_PostData['appoint_name'] = trim($_POST['appoint_name']);
            $_PostData['appoint_content'] = trim($_POST['appoint_content']);
            $_POST['product_recommendation'] = $_POST['relation_goods_id'] ? implode(',', $_POST['relation_goods_id']) : '';
            unset($_POST['relation_goods_id']);
            if (empty($_PostData['appoint_name'])) {
                $this->error(L_('请填写预约名称'));
            }
            if (empty($_PostData['appoint_content'])) {
                $this->error(L_('请填写预约简介'));
            }
//            if ($_POST['payment_status'] == 1) {
//                if (empty($_POST['payment_money']) || $_POST['payment_money'] < '0.00') {
//                    $this->error(L_('请填写定金'));
//                }
//            }
            if(!$_POST['is_appoint_price']){
                $_POST['appoint_price'] = $_POST['appoint_price'];
            }

            if (intval($_POST['is_store'])==1 && empty($_POST['store_id'])) {
                $this->error(L_('请至少选择一家店铺'));
            }

            if (empty($_POST['appoint_pic_content'])) {
                $this->error(L_('请填写服务详情'));
            }
            if (empty($_POST['pic'])) {
                $this->error(L_('请至少上传一张照片'));
            }

            $times = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            if (strtotime($_POST['start_time']) < $times) {
                $this->error(L_('开始时间不能小于当前时间'));
            }
            if (strtotime($_POST['end_time']) <= $times) {
                $this->error(L_('结束时间不能小于或等于当前时间'));
            }
            if (($_POST['time_gap'] % 5 != 0) && ($_POST['time_gap'] != -1)) {
                $this->error(L_('间隔时间必须是5的倍数'));
            }
            if ($_POST['cat_id'] == null || $_POST['cat_fid'] == null) {
                $this->error(L_('分类不能为空'));
            }

            //自定义分类
            $custom_fields = array();
            foreach ($_POST['custom_name'] as $key => $val) {
                $custom_fields[$key]['name'] = $val;
            }

            //if($_POST['payment_status']){
                foreach ($_POST['custom_payment_price'] as $key => $val) {
                    if(!$val){
                        continue;
                    }

                    if((($val <= 0) || ($val == '0.00')) && ($_POST['payment_status'] == 1)){
                        $this->error('规格属性-规格定金不能为空');
                    }
                    $custom_fields[$key]['payment_price'] = $val;
                }
            /*}else{
                foreach ($_POST['custom_payment_price'] as $key => $val) {
                    $custom_fields[$key]['payment_price'] = 0;
                }
            }*/


            foreach ($_POST['custom_price'] as $key => $val) {
                $custom_fields[$key]['price'] = $val;
            }
            foreach ($_POST['custom_content'] as $key => $val) {
                $custom_fields[$key]['content'] = $val;
            }

            foreach ($_POST['use_time'] as $key => $val) {
                $custom_fields[$key]['use_time'] = $val;
            }


            //营业时间
            // if ($_POST['office_start_time'] != '00:00' || $_POST['office_stop_time'] != '00:00') {
            //     $office_time = array('open' => $_POST['office_start_time'], 'close' => $_POST['office_stop_time']);
            // } else {
            //     $office_time = array('open' => '00:00', 'close' => '00:00');
            // }
            
            $office_time = array();
            foreach ($_POST['office_start_time'] as $key => $value) {
                if ($key > 0 && strtotime($value) < strtotime($_POST['office_stop_time'][$key-1])) {
                    $this->error(L_('开始时间不能小于上一时间段结束时间'));
                }
                if ($value != '00:00' || $_POST['office_stop_time'][$key] != '00:00') {
                    $office_time[] = array('open' => $value, 'close' => $_POST['office_stop_time'][$key]);
                } else {
                    $office_time = array(array('open' => '00:00', 'close' => '00:00'));
                }
            }

            $_POST['office_time'] = serialize($office_time);
            $_POST['mer_id'] = $this->merchant_session['mer_id'];
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['create_time'] = time();
            $_POST['pic'] = implode(';', $_POST['pic']);
            $_POST['appoint_pic_content'] = fulltext_filter($_POST['appoint_pic_content']);
            $config_condition['name'] = 'appoint_verify';
            $appoint_verify = D('Config')->field('`value`')->where($config_condition)->find();
            if ($appoint_verify['value'] == '1') {
                $_POST['check_status'] = '0';
            } else {
                $_POST['check_status'] = '1';
                $_POST['appoint_status'] = '0';
            }

            if ($appoint_id = $database_appoint->data($_POST)->add()) {
                foreach ($custom_fields as $key => $val) {
                    if (!empty($custom_fields[$key]['name'])) {
                        $custom_fields[$key]['mer_id'] = $this->merchant_session['mer_id'];
                        $custom_fields[$key]['appoint_id'] = $appoint_id;
                        $custom_fields[$key]['cat_id'] = $_POST['cat_id'];
                        $database_appoint_product->data($custom_fields[$key])->add();
                    }
                }

                //店铺信息
                foreach ($_POST['store_id'] as $key => $val) {
                    $condition_merchant_store['appoint_id'] = $appoint_id;
                    $condition_merchant_store['store_id'] = $val;
                    $tmp_appoint_store = $database_store->field('`store_id`,`province_id`,`city_id`,`area_id`,`circle_id`')->where($condition_merchant_store)->find();
                    if (!empty($tmp_appoint_store)) {
                        $tmp_appoint_store['appoint_id'] = $appoint_id;
                        $data_appoint_store_arr = $tmp_appoint_store;
                        $data_appoint_store_arr['store_sort'] = $_POST['store_sort'][$key];
                    }
                    $insert_id = $database_appoint_store->data($data_appoint_store_arr)->add();
                    if (!$insert_id) {
                        $this->error(L_('关系添加失败！请重试。'));
                    }
                }

                //工作人员添加start
                $worker_arr = $_POST['worker_memus'];
                if ($worker_arr) {
                    $worker_memus = array();
                    foreach ($worker_arr as $v) {
                        $tmp_worker = explode(',', $v);
                        $worker_memus[end($tmp_worker)] = reset($tmp_worker);
                    }
                    foreach ($worker_memus as $k => $v) {
                        $merchant_workers_appoint_data['merchant_worker_id'] = $k;
                        $merchant_workers_appoint_data['merchant_store_id'] = $v;
                        $merchant_workers_appoint_data['appoint_id'] = $appoint_id;
                        $merchant_workers_appoint_data['mer_id'] = $this->merchant_session['mer_id'];
                        $merchant_workers_appoint_insert_id = $database_merchant_workers_appoint->data($merchant_workers_appoint_data)->add();
                        if (!$merchant_workers_appoint_insert_id) {
                            $this->error(L_('关系添加失败！请重试。'));
                        }
                    }
                }
                //工作人员添加end

                //自定义表单start
                $appoint_custom_fields = array();
                foreach ($_POST['appoint_custom_field_name'] as $key => $val) {
                    $appoint_custom_fields[$key]['appoint_custom_field_name'] = nl2br($val);
                }
                foreach ($_POST['appoint_custom_field_value'] as $key => $val) {
                    $appoint_custom_fields[$key]['appoint_custom_field_value'] = nl2br($val);
                }
                foreach ($_POST['appoint_custom_field_sort'] as $key => $val) {
                    $appoint_custom_fields[$key]['appoint_custom_field_sort'] = nl2br($val);
                }
                foreach ($appoint_custom_fields as $key => $v) {
                    if (!empty($appoint_custom_fields[$key]['appoint_custom_field_name'])) {
                        $appoint_custom_fields[$key]['appoint_id'] = $appoint_id;
                        $database_appoint_custom_field->data($appoint_custom_fields[$key])->add();
                    }
                }
                //自定义表单end
                $this->success(L_('添加成功！'));
            } else {
                $this->error(L_('添加失败！请重试。'));
            }
        } else {
            $store_list = $this->get_merchant_storelist();
            if (empty($store_list)) {
                $this->error(L_('现在还没有店铺，去添加吧。'));
            }
            $condition_group_category['cat_status'] = 1;
            $condition_group_category['cat_fid'] = 0;
            $f_category_list = $database_category->where($condition_group_category)->select();


            //工作人员列表
            $where['status'] = 1;
            $store_arr = array();
            foreach ($store_list as $store) {
                $store_arr[] = $store['store_id'];
            }

            $where['merchant_store_id'] = array('in', $store_arr);
            $worker_list = $database_merchant_workers->where($where)->select();

            foreach ($store_list as $key => $store) {
                foreach ($worker_list as $val) {
                    if ($store['store_id'] == $val['merchant_store_id']) {
                        $store_list[$key]['worker_list'][] = $val;
                    }
                }
            }

            $this->assign('store_list', $store_list);
            $this->assign('f_category_list', $f_category_list);
            $this->display();
        }
    }

    public function frame_edit(){
        $database_store = D('Merchant_store');
        $database_category = D('Appoint_category');
        $database_appoint = D('Appoint');
        $database_appoint_store = D('Appoint_store');
        $database_appoint_custom_field = D('Appoint_custom_field');
        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $database_appoint_product = D('Appoint_product');
        $condition_appoint['appoint_id'] = $_GET['appoint_id'] + 0;
        //$condition_appoint['mer_id'] = $this->merchant_session['mer_id'];  //会导致平台查询不到信息

        $appoint_info = $database_appoint->where($condition_appoint)->order('`appoint_id` DESC')->select();
        /*处理图片上传阿里云导致详情中的图片不展示*/
        $src = '<img src="'.file_domain().'/';
        foreach($appoint_info as $a_key => $a_info){
            $appoint_info[$a_key]['appoint_pic_content'] = str_replace('<img src="/', $src, $appoint_info[$a_key]['appoint_pic_content']);
            $appoint_info[$a_key]['appoint_pic_content'] = str_replace('<img alt="" src="/', $src, $appoint_info[$a_key]['appoint_pic_content']);
        }
        $merchant_info = $database_store->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $category_info = $database_category->where(array('mer_id' => $this->merchant_session['mer_id']))->select();
        $appoint_list = $this->formatArray($appoint_info, $merchant_info, $category_info);
        $appoint_list = reset($appoint_list);
        $this->assign('appoint_list', $appoint_list);

        $office_time = unserialize($appoint_list['office_time']);

        // if(!empty($office_time[0]['open']) && !empty($office_time[0]['close'])){
        //     $office_time['open'] = $office_time[0]['open'];
        //     $office_time['close'] = $office_time[0]['close'];
        // }
        if(isset($office_time['open']) && isset($office_time['close'])){
            $office_time = array($office_time);
        }

        $this->assign('office_time', $office_time);

        $store_list = $this->get_merchant_storelist();
        if (empty($store_list)) {
            $this->error(L_('现在还没有店铺，去添加吧。'));
        }

        $appoint_store = $database_appoint_store->where(array('appoint_id' => $condition_appoint['appoint_id']))->select();
        $store_arr = array();
        foreach ($appoint_store as $store) {
            array_push($store_arr , $store['store_id']);
        }
        $this->assign('store_arr', $store_arr);

        foreach($store_list as &$store_1){
            foreach($appoint_store as $store_2){
                if($store_1['store_id'] == $store_2['store_id']){
                    $store_1['store_sort'] = $store_2['store_sort'];
                }
            }

        }

        if (IS_POST) {
            $_PostData = array();
            $_PostData['appoint_name'] = trim($_POST['appoint_name']);
            $_PostData['appoint_content'] = trim($_POST['appoint_content']);
            if (empty($_PostData['appoint_name'])) {
                $this->error(L_('请填写预约名称'));
            }
            if (empty($_PostData['appoint_content'])) {
                $this->error(L_('请填写预约简介'));
            }
            if ($_POST['payment_status'] == 1) {
                if ($_POST['payment_money'] < 0) {
                    $this->error(L_('请填写定金'));
                }
            }else{
                $_POST['payment_money'] = '0.00';
            }

            if(!$_POST['is_appoint_price']){
                $_POST['appoint_price'] = 0;
            }

            if (intval($_POST['is_store'])==1 && empty($_POST['store_id']) && empty($_POST['store'])) {
                $this->error(L_('请至少选择一家店铺'));
            }
            
            if (empty($_POST['appoint_pic_content'])) {
                $this->error(L_('请填写服务详情'));
            }
            if (empty($_POST['pic'])) {
                $this->error(L_('请至少上传一张照片'));
            }
            if (($_POST['time_gap'] % 5 != 0) && ($_POST['time_gap'] != -1)) {
                $this->error(L_('间隔时间必须是5的倍数'));
            }
            if ($_POST['cat_id'] == null || $_POST['cat_fid'] == null) {
                $this->error(L_('分类不能为空'));
            }
            $appointCategory = M('Appoint_category')
                ->where(['cat_id' => $_POST['cat_id'] ])
                ->field('is_autotrophic')
                ->find();
            if(empty($appointCategory)){
                $this->error(L_('分类不存在'));
            }
            if($appointCategory['is_autotrophic'] == 1){
                if($_POST['payment_status']){
                    $this->error(L_('当前分类选择了平台自营分类，不可以设置收取定金'));
                }
                if($_POST['appoint_type'] == 0){
                    $this->error(L_('当前分类选择了平台自营分类，预约类别不可以设置到店'));
                }
            }
            foreach ($_POST['time_gap'] as $key => $value) {
                if (($value % 5 != 0) && ($value != -1)) {
                    $this->error(L_('间隔时间必须是5的倍数'));
                }
            }
            $office_time = array();
            foreach ($_POST['office_start_time'] as $key => $value) {
                if ($key > 0 && strtotime($value) < strtotime($_POST['office_stop_time'][$key-1])) {
                    $this->error(L_('开始时间不能小于上一时间段结束时间'));
                }
                if ($value != '00:00' || $_POST['office_stop_time'][$key] != '00:00') {
                    $office_time[] = array('open' => $value, 'close' => $_POST['office_stop_time'][$key]);
                } else {
                    $office_time = array(array('open' => '00:00', 'close' => '00:00'));
                }
            }

            //预约剩余时间段各人数start
            $reserve_num = array();
            $time_range = array();
            $time_gap = $_POST['time_gap'] * 60;
            foreach ($office_time as $v) {
                if (($v['open'] != '00:00') && ($v['close'] == '00:00')) {
                    $v['close'] = '24:00';
                }

                $start_time = strtotime($v['open']) - strtotime(date('Y-m-d'));
                $end_time = strtotime($v['close']) - strtotime(date('Y-m-d'));

                if ($start_time || $end_time) {
                    $time_range[] = range($start_time, $end_time, $time_gap);
                }
            }

            foreach ($time_range as $k => $time) {
                foreach ($time as $key => $val) {
                    if (!$time[$key + 1]) {
                        continue;
                    }

                    if ($time[$key] < $time[$key + 1]) {
                        $reserve_num[$k][$time[$key] . '-' . $time[$key + 1]] = 1;
                    } else {
                        $reserve_num[$k][$time[$key + 1] . '-' . $time[$key]] = 1;
                    }
                }
            }
            //预约剩余时间段各人数end
            //数据整合
            $_PostData['appoint_name'] = trim($_POST['appoint_name']);
            $_PostData['mer_id'] = $this->merchant_session['mer_id'];
            $_PostData['start_time'] = strtotime($_POST['start_time']);
            $_PostData['end_time'] = strtotime($_POST['end_time']);
            $_PostData['create_time'] = time();
            $_PostData['cat_fid'] = $_POST['cat_fid'] + 0;
            $_PostData['cat_id'] = $_POST['cat_id'] + 0;
            $_PostData['appoint_pic_content'] = fulltext_filter($_POST['appoint_pic_content']);
            $_PostData['payment_status'] = $_POST['payment_status'] + 0;
            $_PostData['payment_money'] = $_POST['payment_money'] + 0;
            $_PostData['appoint_status'] = $_POST['appoint_status'] + 0;
			$_PostData['expend_time'] = $_POST['expend_time'] + 0;
            if ($_POST['is_admin_operate'] && ($_POST['appoint_status'] == 0 && $appoint_list['check_status'] == 0)) {
                $_PostData['check_status'] = 1;
            }
            $_PostData['appoint_content'] = trim($_POST['appoint_content']);
            $_PostData['pic'] = implode(';', $_POST['pic']);
            $_PostData['appoint_price'] = $_POST['appoint_price'];
            $_PostData['extra_pay_price'] = $_POST['extra_pay_price'];
            $_PostData['appoint_type'] = $_POST['appoint_type'] + 0;
            $_PostData['office_time'] = serialize($office_time);
            $_PostData['reserve_num'] = serialize($reserve_num);
            $_PostData['appoint_people'] = 1;
            $_PostData['time_gap'] = $_POST['time_gap'] + 0;
            $_PostData['before_time'] = $_POST['before_time'] + 0;
            $_PostData['is_store'] = $_POST['is_store'] + 0;
            $_PostData['sort'] = $_POST['sort'] + 0;
            $_PostData['product_type'] = $_POST['product_type'];
            $_PostData['is_appoint_price'] = $_POST['is_appoint_price'] + 0;
            $_PostData['score_percent'] = $_POST['score_percent'];
            $_PostData['take_a_percentage'] = $_POST['take_a_percentage'];
            $_PostData['virtual_sales'] = $_POST['virtual_sales']??0;
	        $_PostData['is_refund'] = $_POST['is_refund'];
            $_PostData['appoint_price_pay_type'] = $_POST['appoint_price_pay_type'];
            $_PostData['is_code_verify'] = $_POST['is_code_verify'];
            isset($_POST['distributor_percent']) && $_PostData['distributor_percent'] = intval($_POST['distributor_percent']);
            $_PostData['product_recommendation'] = $_POST['relation_goods_id'] ? implode(',', $_POST['relation_goods_id']) : '';
            $_PostData['buy_give_coupon_id'] = $_POST['buy_give_coupon_id'];
            
            //自定义服务修改
            $custom_fields_s = array();
            foreach ($_POST['custom_name_s'] as $key => $val) {
                $custom_fields_s[$key]['name'] = $val;
            }

           // if($_POST['payment_status']){
                $_POST['custom_payment_price_s'] = array_filter($_POST['custom_payment_price_s']);

                if(!empty($_POST['custom_payment_price_s']) && $_POST['custom_payment_price_s'][0]){
                    foreach ($_POST['custom_payment_price_s'] as $key => $val) {
                        if((($val <= 0) || ($val == '0.00')) && ($_POST['payment_status'] == 1) && $_POST['custom_name_s'][$key]){
                            $this->error('规格属性-规格定金不能为空');
                        }
                        if(empty($_POST['payment_status'])){
                            $val = 0;
                        }
                        $custom_fields_s[$key]['payment_price'] = floatval($val);
                    }
                }

            foreach ($_POST['custom_price_s'] as $key => $val) {
                $custom_fields_s[$key]['price'] = floatval($val);
            }
            foreach ($_POST['custom_content_s'] as $key => $val) {
                $custom_fields_s[$key]['content'] = $val;
            }

            foreach ($_POST['custom_use_time_s'] as $key => $val) {
                $custom_fields_s[$key]['use_time'] = $val;
            }

            foreach ($_POST['custom_id_s'] as $key => $val) {
                $custom_fields_s[$key]['id'] = $val;
            }

            //自定义服务添加
            $custom_fields = array();
            foreach ($_POST['custom_name'] as $key => $val) {
                $custom_fields[$key]['name'] = $val;
            }
            foreach ($_POST['custom_payment_price'] as $key => $val) {
                $custom_fields[$key]['payment_price'] = $val;
            }
            foreach ($_POST['custom_price'] as $key => $val) {
                $custom_fields[$key]['price'] = $val;
            }
            foreach ($_POST['custom_content'] as $key => $val) {
                $custom_fields[$key]['content'] = $val;
            }

            foreach ($_POST['custom_use_time'] as $key => $val) {
                $custom_fields[$key]['use_time'] = $val;
            }

            //自定义表单修改start
            $appoint_custom_field_s = array();
            foreach ($_POST['appoint_custom_field_name_s'] as $key => $val) {
                $appoint_custom_field_s[$key]['appoint_custom_field_name'] = $val;
            }
            foreach ($_POST['appoint_custom_field_value_s'] as $key => $val) {
                $appoint_custom_field_s[$key]['appoint_custom_field_value'] = nl2br($val);
            }
            foreach ($_POST['appoint_custom_field_sort_s'] as $key => $val) {
                $appoint_custom_field_s[$key]['appoint_custom_field_sort'] = $val;
            }
            foreach ($_POST['appoint_custom_field_id_s'] as $key => $val) {
                $appoint_custom_field_s[$key]['id'] = $val;
            }

            $appoint_custom_fields = array();
            foreach ($_POST['appoint_custom_field_name'] as $key => $val) {
                $appoint_custom_fields[$key]['appoint_custom_field_name'] = $val;
            }
            foreach ($_POST['appoint_custom_field_value'] as $key => $val) {
                $appoint_custom_fields[$key]['appoint_custom_field_value'] = $val;
            }
            foreach ($_POST['appoint_custom_field_sort'] as $key => $val) {
                $appoint_custom_fields[$key]['appoint_custom_field_sort'] = $val;
            }
            //自定义表单修改end
            if ($database_appoint->where($condition_appoint)->data($_PostData)->save()) {
                foreach ($custom_fields_s as $key => $val) {
                    if (!empty($val['name'])) {
                        $custom_fields_s[$key]['mer_id'] = $this->merchant_session['mer_id'];
                        $custom_fields_s[$key]['appoint_id'] = $condition_appoint['appoint_id'];
                        $custom_fields_s[$key]['cat_id'] = $_POST['cat_id'];
                        $database_appoint_product->data($custom_fields_s[$key])->where('id =' . $custom_fields_s[$key]['id'])->save();
                    } else {
                        $database_appoint_product->where('id =' . $custom_fields_s[$key]['id'])->delete();
                    }
                }
                foreach ($custom_fields as $key => $val) {
                    if (!empty($custom_fields[$key]['name'])) {
                        $custom_fields[$key]['mer_id'] = $this->merchant_session['mer_id'];
                        $custom_fields[$key]['appoint_id'] = $condition_appoint['appoint_id'];
                        $custom_fields[$key]['cat_id'] = $_POST['cat_id'];
                        $database_appoint_product->data($custom_fields[$key])->add();
                    }
                }

                foreach ($appoint_custom_field_s as $key => $val) {
                    if (!empty($val['appoint_custom_field_name'])) {
                        $database_appoint_custom_field->data($appoint_custom_field_s[$key])->where('id =' . $appoint_custom_field_s[$key]['id'])->save();
                    } else {
                        $database_appoint_custom_field->where('id =' . $appoint_custom_field_s[$key]['id'])->delete();
                    }
                }
                foreach ($appoint_custom_fields as $key => $val) {
                    if (!empty($appoint_custom_fields[$key]['appoint_custom_field_name'])) {
                        $appoint_custom_fields[$key]['appoint_id'] = $condition_appoint['appoint_id'];
                        $database_appoint_custom_field->data($appoint_custom_fields[$key])->add();
                    }
                }


                $appoint_store_id = array();
                foreach ($appoint_store as $key => $val) {
                    $appoint_store_id[$key] = $val['store_id'];
                }

                $database_appoint_store->where(array('appoint_id' => $_GET['appoint_id']))->delete();
                foreach ($_POST['store'] as $Key=>$val) {
                    $condition_appoint_store['appoint_id'] = $condition_appoint['appoint_id'];
                    $condition_appoint_store['store_id'] = $val;
                    $store_info = $database_appoint_store->field(true)->where($condition_appoint_store)->find();
                    $tmp_appoint_store = $database_store->field('`store_id`,`province_id`,`city_id`,`area_id`,`circle_id`')->where($condition_appoint_store)->find();
                    if (empty($store_info)) {
                        $tmp_appoint_store['appoint_id'] = $condition_appoint['appoint_id'];
                        $data_appoint_store_arr = $tmp_appoint_store;
                        $data_appoint_store_arr['store_sort'] = $_POST['store_sort'][$Key];
                        $database_appoint_store->data($data_appoint_store_arr)->add();
                    }
                }

                //工作人员添加start
                $database_merchant_workers_appoint->where(array('appoint_id' => $condition_appoint['appoint_id']))->delete();
                $worker_arr = $_POST['worker_memus'];
                if ($worker_arr) {
                    foreach ($worker_arr as $v) {
                        $tmp_worker = explode(',', $v);
                        $worker_memus[end($tmp_worker)] = reset($tmp_worker);
                    }

                    foreach ($worker_memus as $k => $v) {
                        $merchant_workers_appoint_data['merchant_worker_id'] = $k;
                        $merchant_workers_appoint_data['merchant_store_id'] = $v;
                        $merchant_workers_appoint_data['appoint_id'] = $condition_appoint['appoint_id'];
                        $merchant_workers_appoint_data['mer_id'] = $this->merchant_session['mer_id'];
                        $merchant_workers_appoint_insert_id = $database_merchant_workers_appoint->data($merchant_workers_appoint_data)->add();
                        if (!$merchant_workers_appoint_insert_id) {
                            $this->error(L_('关系添加失败！请重试。'));
                        }
                    }
                }
                //工作人员添加end
                $this->success(L_('编辑成功！'));
            } else {
                $this->error(L_('编辑失败！请重试。'));
            }
        }else{
            $condition_group_category['cat_status'] = 1;
            $condition_group_category['cat_fid'] = 0;
            $f_category_list = $database_category->where($condition_group_category)->select();
            $s_category_list = $database_category->where('cat_fid =' . $appoint_list['cat_fid'] . ' AND is_autotrophic != 2')->select();
            $product_list = $database_appoint_product->where('appoint_id =' . $appoint_list['appoint_id'] . '')->select();
            //自定义表单列表
            $appoint_custom_fields = $database_appoint_custom_field->where('appoint_id =' . $appoint_list['appoint_id'] . '')->order('appoint_custom_field_sort desc', 'id desc')->select();
            foreach ($appoint_custom_fields as $k => $v) {
                $appoint_custom_fields[$k]['appoint_custom_field_value'] = strip_tags($v['appoint_custom_field_value']);
            }

            $appoint_image_class = new appoint_image();
            $tmp_pic_arr = explode(';', $appoint_list['pic']);
            foreach ($tmp_pic_arr as $key => $value) {
                $pic_list[$key]['title'] = $value;
                if(substr($value,1,6) == 'upload'){
                    $pic_list[$key]['url'] = file_domain().$value;
                }else{
                    $pic_list[$key]['url'] = $appoint_image_class->get_image_by_path($value, 's');
                }

            }

            //工作人员列表
            $where['status'] = 1;
            $where['appoint_type'] = array('in', array((int)$appoint_list['appoint_type'], 2));

            $worker_list = $database_merchant_workers->where($where)->select();
            foreach ($store_list as $k => $v) {
                foreach ($worker_list as $val) {
                    if ($v['store_id'] == $val['merchant_store_id']) {
                        $store_list[$k]['worker_list'][] = $val;
                    }
                }
            }

            //选中工作人员列表
            $this->assign('store_list', $store_list);
            $this->assign('appoint_store', $appoint_store);
            $this->assign('f_category_list', $f_category_list);
            $this->assign('s_category_list', $s_category_list);
            $this->assign('product_list', $product_list);
            $this->assign('appoint_custom_fields', $appoint_custom_fields);
            $this->assign('pic_list', $pic_list);
            $this->assign('is_admin',!empty($_GET['system_file']));
            
            //推荐商品
            $recommend_goods = [];
            if ($appoint_list['product_recommendation']) {
                $goods_ids = explode(',', $appoint_list['product_recommendation']);
                $recommend_goods = D('Appoint')->field('appoint_id,appoint_name,appoint_status,payment_status,is_appoint_price,payment_money,appoint_price')->where(['appoint_id' => ['in', $goods_ids]])->select();
            }
            //赠送优惠券
            $give_coupon = [];
            if ($appoint_list['buy_give_coupon_id']) {
                $give_coupon = D('Card_new_coupon')->where(['coupon_id' => $appoint_list['buy_give_coupon_id']])->select();
            }
            $this->assign('recommend_goods', $recommend_goods);
            $this->assign('give_coupon', $give_coupon);
            $this->display();
        }
    }

    /* 二级分类 */
    public function ajax_get_category(){
        $database_Appoint_category = D('Appoint_category');
        $condition_now_Appoint_category['cat_id'] = $_GET['cat_fid'] + 0;
        $condition_now_Appoint_category['cat_status'] = 1;
        $now_category = $database_Appoint_category->field(true)->where($condition_now_Appoint_category)->find();
        if (empty($now_category)) {
            $return['error'] = 1;
            $return['msg'] = L_('该分类不存在！');
        } else {
            $condition_s_Appoint_category['cat_fid'] = $_GET['cat_fid'];
            $condition_s_Appoint_category['cat_status'] = 1;
            $condition_s_Appoint_category['is_autotrophic'] = array('neq', 2);
            $s_category_list = $database_Appoint_category->field(true)->where($condition_s_Appoint_category)->order('`cat_sort` DESC,`cat_id` ASC')->select();
            if (empty($s_category_list)) {
                $return['error'] = 1;
                $return['msg'] = L_('该分类下没有添加子分类！请勿选择。');
            } else {
                $return['error'] = 0;
                $return['cat_list'] = $s_category_list;
            }
        }
        exit(json_encode($return));
    }

    public function ajax_get_appoint(){
        $database_appoint = D('Appoint');
        $store_id = $_GET['store_id'] + 0;
        $appoint_list = $database_appoint->get_store_appoint_list($store_id, 100);
        $return = array();
        if ($appoint_list) {
            $return['error'] = 0;
            $return['appoint_list'] = $appoint_list;
        }
        exit(json_encode($return));
    }

    /* 上传图片 */
    public function ajax_upload_pic(){
        if ($_FILES['imgFile']['error'] != 4) {
            $img_mer_id = sprintf("%09d", $this->merchant_session['mer_id']);
            $rand_num = mt_rand(10, 99) . '/' . substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

            $upload_dir = './upload/appoint/' . $rand_num . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = $this->config['group_pic_size'] * 1024 * 1024;
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');
            $upload->allowTypes = array('image/png', 'image/jpg', 'image/jpeg', 'image/gif');
            $upload->savePath = $upload_dir;
            $upload->thumb = true;
            $upload->imageClassPath = 'ORG.Util.Image';
            $upload->thumbPrefix = 'm_,s_';
            $upload->thumbMaxWidth = $this->config['group_pic_width'];
            $upload->thumbMaxHeight = $this->config['group_pic_height'];
            $upload->thumbRemoveOrigin = false;
            $upload->saveRule = 'uniqid';
            if ($upload->upload()) {
                $uploadList = $upload->getUploadFileInfo();

                $title = $rand_num . ',' . $uploadList[0]['savename'];

                $appoint_image_class = new appoint_image();
                $url = $appoint_image_class->get_image_by_path($title, 's');

                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            } else {
                exit(json_encode(array('error' => 1, 'message' => $upload->getErrorMsg())));
            }
        } else {
            exit(json_encode(array('error' => 1, 'message' => L_('没有选择图片'))));
        }
    }

    public function ajax_upload_pic_test()
    {
        if ($_FILES['file']['error'] != 4) {
            $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
//            $shop = D('Merchant_store_shop')->field('store_theme')->where(array('store_id' => $store_id))->find();
//            $store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
//            if ($store_theme) {
//                $width = '900,450';
//                $height = '900,450';
//            } else {
//
//            }
            $width = '900,450';
            $height = '500,250';
            $param = array('size' => $this->config['group_pic_size']);
            $param['thumb'] = true;
            $param['imageClassPath'] = 'ORG.Util.Image';
            $param['thumbPrefix'] = 'm_,s_';
            $param['thumbMaxWidth'] = $width;
            $param['thumbMaxHeight'] = $height;
            $param['thumbRemoveOrigin'] = false;
            $image = D('Image')->handle($this->merchant_session['mer_id'], 'goods', 1, $param);
            if ($image['error']) {
                exit(json_encode(array('error' => 1,'message' =>$image['message'])));
            } else {
                $title = $image['title']['imgFile']?$image['title']['imgFile']:$image['title']['file'];
                $goods_image_class = new goods_image();
                $url = $goods_image_class->get_image_by_path($title, 'm');
                exit(json_encode(array('error' => 0, 'url' => $url, 'title' => $title)));
            }
        } else {
            exit(json_encode(array('error' => 1,'message' =>L_('没有选择图片'))));
        }
    }

    /* 删除图片  */
    public function ajax_del_pic(){
        $group_image_class = new appoint_image();
        $group_image_class->del_image_by_path($_POST['path']);
    }


    public function order_list(){ 
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $database_merchant_workers = D('Merchant_workers');

        $merchant_worker_id = $_GET['merchant_worker_id'] + 0;
        $appoint_id = $_GET['appoint_id'] + 0;

        if (!empty($appoint_id)) {
            $where['a.appoint_id'] = $appoint_id;
        }

        if (!empty($merchant_worker_id)) {
            $where['a.merchant_worker_id'] = $merchant_worker_id;
        }else{
            // $where['a.merchant_worker_id'] = array('gt',0);
        }


        $where['a.mer_id'] = $this->merchant_session['mer_id'];
        //$where['a.store_id'] = array('neq', 0);


        //工作人员列表
        $Map['status'] = 1;
        $Map['mer_id'] = $this->merchant_session['mer_id'];
        $merchant_worker_list = $database_merchant_workers->where($Map)->getField('merchant_worker_id,name');

        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $where['a.order_id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
                unset($where['orderid']);
                $where['a.order_id'] = $tmp_result['order_id'];
            }elseif ($_GET['searchtype'] == 'name') {
                $where['u.username'] = htmlspecialchars($_GET['keyword']);
            } elseif ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = htmlspecialchars($_GET['keyword']);
            }elseif ($_GET['searchtype'] == 'third_id') {
                $where['a.third_id'] =$_GET['keyword'];
            }elseif ($_GET['searchtype'] == 'real_orderid') {
                $where['a.real_orderid'] =$_GET['keyword'];
            }
        }
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if($pay_type){
            if($pay_type=='balance'){
                $where['_string'] = "(`a`.`balance_pay`<>0 OR `a`.`merchant_balance` <> 0 OR a.product_merchant_balance <> 0 OR a.product_balance_pay <> 0 )";
            }else{
                $where['a.pay_type|a.product_pay_type'] = $pay_type;
            }
        }


        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));

            $_GET['searchTimeType'] = $_GET['searchTimeType'] ? $_GET['searchTimeType'] : 'order_time';
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.".$_GET['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
        }

        // 服务状态
        if (isset($_GET['service_status']) && $_GET['service_status'] !=-1 ) {
            $where['a.service_status'] = $_GET['service_status'];
        }

        // 支付状态
        $paid = isset($_GET['paid']) && $_GET['paid'] ? $_GET['paid'] : -1;
        if ($paid == -10) {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.paid=1 AND a.service_status<=0)";
        } elseif ($paid == 1) {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=1 AND a.payment_status!=1) OR (a.paid=1 AND a.service_status>0))";
        } elseif ($paid==3)  {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=3) OR (a.is_del>0))";
        }elseif ($paid==4)  {
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=1) AND (a.is_apply_refund = 1))";
        } elseif ($paid==-2)  {
            $where['a.paid'] = 0;
        } elseif ($paid>=0)  {
            $where['a.paid'] = $paid;
        }

        $order_count = $database_order->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
            ->where($where)->count();

        import('@.ORG.merchant_page');
        $page = new Page($order_count, 20);
        $order_list = $database_order->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone,s.name as store_name,s.adress as store_adress')
            ->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
            ->where($where)->order('`order_id` DESC')->limit($page->firstRow.','.$page->listRows)->select();


//        $uidArr = array();
//        foreach ($order_info as $v) {
//            array_push($uidArr, $v['uid']);
//        }
//
//        $uidArr = array_unique($uidArr);
//
//        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid' => array('in', $uidArr)))->select();
//        $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`')->select();
//        $store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
//        $order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
        $pagebar = $page->show();
        $pay_method = D('Config')->get_pay_method('','',1);
        $this->assign('pay_method',$pay_method);
        $this->assign('pay_type',$pay_type);
        $this->assign('pagebar', $pagebar);
        $this->assign('order_list', $order_list);
        $this->assign('merchant_worker_list', $merchant_worker_list);
        $this->display();
    }


    /*验证预约服务*/
    public function appoint_verify(){
        $database_order = D('Appoint_order');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_store = D('Merchant_store');
        $order_id = $_GET['order_id'] + 0;

        $where['order_id'] = $order_id;
        $now_order = $database_order->field(true)->where($where)->find();

        $now_store = $database_merchant_store->get_store_by_storeId($now_order['store_id']);
        if (empty($now_order)) {
            $this->error(L_('当前订单不存在！'));
        } else {
            if ($now_order['service_status'] == 1) {
                $this->error(L_('当前订单已服务，请勿重复操作！'));
            }
            if($now_order['is_apply_refund']){
                $this->error(L_('当前订单已申请退款！'));
            }

            $uniqueKey = md5('merchant_appoint_verify_' . $order_id);
            $lockRs = setLock($uniqueKey, 10);
            if ($lockRs['error_no']) {
                $this->error($lockRs['error_msg']);
            }
            if(empty(floatval($now_order['product_price'])) && empty(floatval($now_order['appoint_price']))){
                if($now_order['product_id']){
                    $database_order->where($where)->setField('product_price',$now_order['product_payment_price']);
                }else{
                    $database_order->where($where)->setField('appoint_price',$now_order['payment_money']);
                }
                if($now_order['merchant_worker_id']){//服务员工服务费
                    $now_order = D('Appoint_order')->where($where)->find();
                    D('Appoint_order')->changeOrderServiceFee($now_order);
                }
            }
            $condition_group['appoint_id'] = $now_order['appoint_id'];
            D('Appoint')->where($condition_group)->setInc('appoint_sum',1);
            $fields['verify_time'] = time();
            $fields['last_time'] = time();
            $fields['service_status'] = 1;
            $fields['paid'] = 1;
            $fields['last_staff'] = $this->merchant_session['name'];
            if ($database_order->where($where)->data($fields)->save()) {
                $Map['appoint_order_id'] = $order_id;
                $appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();

                $worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
                $pay_money_count = $database_appoint_visit_order_info->order_appoint_price_sum($worker_where);
                $database_merchant_workers->where($worker_where)->where($worker_where)->setField('appoint_price', $pay_money_count);
                $database_merchant_workers->where($worker_where)->setInc('order_num');

                if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
                    if (empty($now_order['phone'])) {
                        $user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
                    }
                    $sms_data['uid'] = $now_order['uid'];
                    $sms_data['mobile'] = $user['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$now_store['name'],'X2'=>$now_order['order_id']));
                    Sms::sendSms($sms_data);
                }
                //商家余额增加
                $order_info['order_id'] = $now_order['order_id'];
                $order_info['mer_id'] = $now_order['mer_id'];
                $order_info['store_id'] = $now_order['store_id'];
                $order_info['order_type'] = 'appoint';
                $order_info['score_deducte'] = $now_order['score_deducte'];
                $order_info['is_own'] = $now_order['is_own'];
                $order_info['score_used_count'] = $now_order['score_used_count'];
                $order_info['money'] = $now_order['score_deducte']
                    + $now_order['payment_money']
                    + $now_order['merchant_balance']
                    + $now_order['product_score_deducte']
                    + $now_order['product_balance_pay']
                    + $now_order['product_coupon_price']
                    + $now_order['user_pay_money']
                    + $now_order['product_merchant_balance'];

                if($now_order['product_id'] > 0){
                    $order_info['total_money'] = $now_order['product_price'];
                }else{
                    $order_info['total_money'] = $now_order['appoint_price'];
                }

                $appoint_name = M('Appoint')->field('appoint_name')->where(array('appoint_id'=>$now_order['appoint_id']))->find();

                $order_info['payment_money'] = $now_order['pay_money'] + $now_order['user_pay_money'];
                $order_info['balance_pay'] = $now_order['balance_pay'] + $now_order['product_balance_pay'];
                $order_info['merchant_balance'] = $now_order['merchant_balance'] + $now_order['product_merchant_balance'];
                $order_info['card_give_money'] = $now_order['card_give_money'] + $now_order['product_card_give_money'];
                $order_info['uid'] = $now_order['uid'];
                $order_info['desc'] = L_('用户预约X1记入收入',array('X1'=>$appoint_name['appoint_name']));
                $order_info['score_discount_type']=$now_order['score_discount_type'];
                $order_info['distributor_uid'] = $now_order['distributor_uid'];
                D('SystemBill')->bill_method($order_info['is_own'],$order_info);

                $now_user = D('User')->get_user($order_info['uid']);
                D('Merchant_spread')->add_spread_list($order_info,$now_user,'appoint',$now_user['nickname'].L_("用户购买预约获得佣金"));

                $database_appoint_supply = D('Appoint_supply');
                $now_supply = $database_appoint_supply->where($where)->find();
                if($now_supply){//copy from path cms/Lib/Model/Appoint_orderModel.class.php
                    $supply_data['end_time'] = time();
                    $supply_data['status'] = 3;
                    $supply_data['check_time'] = time();
                    if($now_order['merchant_worker_id']){//服务员工服务费
                        $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
                        $supply_data['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $orderPrice);
                    }
                    $database_appoint_supply->where($where)->data($supply_data)->save();
                }
                if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2)){
                    if($this->config['open_score_get_percent']==1){
                        $score_get = $this->config['score_get_percent']/100;
                    }else{
                        $score_get = $this->config['user_score_get'];
                    }
                        $now_merchant = D('Merchant')->get_info($this->merchant_session['mer_id']);
                        if($now_merchant['score_get_percent']>=0){
                            $score_get = $now_merchant['score_get_percent']/100;
                        }
                    if($order_info['is_own'] && C('config.user_own_pay_get_score')!=1){
                        $order_info['payment_money']= 0;
                    }   

                    $score_get = D('Percent_rate')->get_user_add_score_times($score_get,$order_info['uid']);

                    D('User')->add_score($now_order['uid'], ($order_info['balance_pay']+$order_info['payment_money']) * $score_get, L_('购买预约商品获得X1',array('X1'=>$this->config['score_name'])),0,[
                        'order_id'=>$now_order['order_id'],
                        'order_type'=>'appoint'
                    ]);
                }
                //小票打印start
                $printHaddle = new PrintHaddle();
                $printHaddle->printit($now_order['order_id'], 'appoint_order', 1);
                //小票打印end
                unLock($uniqueKey);
                $this->success(L_('验证成功！'));
            } else {
                $this->error(L_('验证失败！请重试。'));
            }
        }
    }


    /* 订单详情  */
    public function order_detail(){
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $database_merchant_workers = D('Merchant_workers');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_appoint_product = D('Appoint_product');
        $database_appoint_supply = D('Appoint_supply');

        $order_id = $_GET['order_id'] + 0;
        $where['order_id'] = $order_id;
        $where['mer_id'] = $this->merchant_session['mer_id'];

        $now_order = $database_order->where($where)->find();
        $where_user['uid'] = $now_order['uid'];
        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where($where_user)->find();
        $where_appoint['appoint_id'] = $now_order['appoint_id'];
        $appoint_info = $database_appoint->field(true)->where($where_appoint)->find();
        $where_store['store_id'] = $now_order['store_id'];
        $store_info = $database_store->field('`store_id`, `name`, `adress`')->where($where_store)->find();

        $now_order['phone'] = $user_info['phone'];
        $now_order['nickname'] = $user_info['nickname'];
        $now_order['appoint_name'] = $appoint_info['appoint_name'];
        $now_order['appoint_type'] = $appoint_info['appoint_type'];
        // $now_order['appoint_price'] = $appoint_info['appoint_price'];  // 预约的价格可以修改。此订单的预约支付价格不变
        $now_order['is_appoint_price'] = $appoint_info['appoint_price'];
        $now_order['store_name'] = $store_info['name'];
        $now_order['store_adress'] = $store_info['adress'];
        $cue_info = unserialize($now_order['cue_field']);
        $cue_list = array();
        foreach ($cue_info as $key => $val) {
            if (!empty($cue_info[$key]['value'])) {
                $cue_list[$key]['name'] = $val['name'];
                $cue_list[$key]['value'] = $val['value'];
                $cue_list[$key]['type'] = $val['type'];
                $cue_list[$key]['phone'] = $val['phone'];
                if ($cue_info[$key]['type'] == 2) {
                    $cue_list[$key]['long'] = $val['long'];
                    $cue_list[$key]['lat'] = $val['lat'];
                    if ($val['address']) {
                        $cue_list[$key]['address'] = $val['address'];
                    } else {
                        $cue_list[$key]['address'] = $val['value'];
                    }
                    $cue_list[$key]['phone'] = $val['phone'];
                }
            }
        }

        $product_detail = $database_appoint_product->get_product_info($now_order['product_id']);
        if ($product_detail['status']) {
            $now_order['product_detail'] = $product_detail['detail'];
        }
        $now_order['secondary_payment_money'] = 0;
        if($now_order['product_id']){
            $now_order['total_price'] = $now_order['product_price'];
            if(floatval($now_order['total_price']) > floatval($now_order['product_payment_price'])){
                $now_order['secondary_payment_money'] = bcsub($now_order['total_price'], $now_order['product_payment_price'], 2);
            }
        }else{
            $now_order['total_price'] = $now_order['appoint_price'];
            if(floatval($now_order['total_price']) > floatval($now_order['payment_money'])){
                $now_order['secondary_payment_money'] = bcsub($now_order['total_price'], $now_order['payment_money'], 2);
            }
        }
        //上门预约工作人员信息start
        $Map['appoint_order_id'] = $order_id;
        $Map['uid'] = $now_order['uid'];
        $appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();
        $service_address = unserialize($appoint_visit_order_info['service_address']);
        if ($appoint_info['appoint_type'] == 1) {
            $service_address_info = array();
            foreach ($service_address as $key => $val) {
                if (!empty($service_address[$key]['value'])) {
                    $service_address_info[$key]['name'] = $val['name'];
                    $service_address_info[$key]['value'] = $val['value'];
                    $service_address_info[$key]['type'] = $val['type'];
                    if ($appoint_visit_order_info[$key]['type'] == 2) {
                        $service_address_info[$key]['long'] = $val['long'];
                        $service_address_info[$key]['lat'] = $val['lat'];
                        $service_address_info[$key]['address'] = $val['address'];
                    }
                }
            }
//            $cue_list = $service_address_info;
        }
        $worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
        $worker_field = array('merchant_worker_id', 'name', 'mobile');
        $merchant_workers_info = $database_merchant_workers->appoint_worker_info($worker_where, $worker_field);
        $this->assign('merchant_workers_info', $merchant_workers_info);

        //上门预约工作人员信息end
        $now_supply = $database_appoint_supply->where(array('order_id'=>$now_order['order_id']))->find();
        if($now_supply){
            $now_order['supply_info'] = $now_supply;
        }
        $project_list = M('Appoint_project')->select();
        $this->assign('project_list',$project_list);

        $project_city_list = M('Appoint_project_city')->select();
        $this->assign('project_city_list',$project_city_list);
        $this->assign('cue_list', $cue_list);
        $this->assign('now_order', $now_order);
        $this->display();
    }

    public function ajax_city()
    {

        $appoint_project = M('Appoint_project')->where(array('city_name'=>$_POST['city']))->select();
        if($appoint_project){
            echo json_encode($appoint_project);
        }
    }

    public function ajax_order(){
        $appoint_order = M('Appoint_order')->where(array('order_id'=>$_POST['order_id']))->find();
        if($appoint_order){
            $store_id = $appoint_order['store_id'];
            $store_name = D('Merchant_store')->where(['store_id'=>$store_id])->getField('name');
            $appoint_order['store_name'] = $store_name?:'';
            echo json_encode($appoint_order);
        }
    }

    public function editOrder()
    {
        if(IS_POST){
            $order = D('Appoint_order');

            if($order->where(array('order_id'=>$_POST['order_id']))->save($_POST)){
                $this->success('提交成功！');
            }else{
                $this->error('提交失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    /*更新面议价格*/
    public function change_order_price(){
        $order_id = $_POST['order_id'];
        $price = $_POST['price'];
        $database_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $where['mer_id'] = $this->merchant_session['mer_id'];
        
        $now_order = $database_order->where($where)->find();
        if($now_order['service_status']!=0){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('订单状态不是未服务状态')));exit;
        }
        if(!$now_order){
            echo json_encode(array('errorCode'=>1,'msg'=>L_('订单不存在')));exit;
        }
        $prepaymentPrice = floatval($now_order['product_payment_price']) > 0 ? $now_order['product_payment_price'] : $now_order['payment_money'];
        if($price<$prepaymentPrice){
            $this->ajaxReturn([
                'errorCode'=>1,
                'msg'=>L_('金额不可小于定金')
            ], 'JSON');
        }
        if($now_order['merchant_worker_id']){//服务员工服务费
            D('Appoint_order')->changeOrderServiceFee($now_order);
        }
        $old_price = $now_order['appoint_price'];
        if($now_order['product_id']){
            $old_price = $now_order['product_price'];
            $res = $database_order->where($where)->setField('product_price',$price);
        }else{
            $res = $database_order->where($where)->setField('appoint_price',$price);
        }
        if($res !== false){
            M('System_order')
                ->where(['type' => 'appoint', 'order_id' => $order_id])
                ->setField('total_price',$price);
            $save_change_log = [
                'worker_id' => $this->merchant_session['mer_id'],
                'order_id' => $order_id,
                'old_price' => $old_price,
                'now_price' => $price,
                'add_time' => time(),
                'product_id' => $now_order['product_id'],
                'type' => 1,
            ];
            D('Merchant_workers_modify_order_log')->add($save_change_log);
            $this->ajaxReturn([
                'errorCode'=>0,
                'msg'=>L_('修改成功')
            ], 'JSON');
        }else{
            $this->ajaxReturn([
                'errorCode'=>1,
                'msg'=>L_('修改失败')
            ], 'JSON');
        }
    }

    /* 格式化订单数据  */
    protected function formatOrderArray($order_info, $user_info, $appoint_info, $store_info){
        if (!empty($user_info)) {
            $user_array = array();
            foreach ($user_info as $val) {
                $user_array[$val['uid']]['phone'] = $val['phone'];
                $user_array[$val['uid']]['nickname'] = $val['nickname'];
            }
        }
        if (!empty($appoint_info)) {
            $appoint_array = array();
            foreach ($appoint_info as $val) {
                $appoint_array[$val['appoint_id']]['appoint_name'] = $val['appoint_name'];
                $appoint_array[$val['appoint_id']]['appoint_type'] = $val['appoint_type'];
            }
        }
        if (!empty($store_info)) {
            $store_array = array();
            foreach ($store_info as $val) {
                $store_array[$val['store_id']]['store_name'] = $val['name'];
                $store_array[$val['store_id']]['store_adress'] = $val['adress'];
            }
        }
        if (!empty($order_info)) {
            foreach ($order_info as &$val) {
                $val['phone'] = $user_array[$val['uid']]['phone'];
                $val['nickname'] = $user_array[$val['uid']]['nickname'];
                $val['appoint_name'] = $appoint_array[$val['appoint_id']]['appoint_name'];
                $val['appoint_type'] = $appoint_array[$val['appoint_id']]['appoint_type'];
                $val['store_name'] = $store_array[$val['store_id']]['store_name'];
                $val['store_adress'] = $store_array[$val['store_id']]['store_adress'];
                
                if(
                    empty(floatval($val['payment_money'])) &&
                    empty(floatval($val['product_payment_price'])) && 
                    empty(floatval($val['paid']))
                ){
                    $val['paid'] = 1;
                }
            }
        }
        return $order_info;
    }


    //工作人员列表
    public function worker_list(){
        $database_merchant_workers = D('Merchant_workers');
        $where['mer_id'] = $this->merchant_session['mer_id'];

        $worker_count = $database_merchant_workers->where($where)->count();
        import('@.ORG.merchant_page');
        $page = new Page($worker_count, 20);
        $work_list = $database_merchant_workers->where($where)->order('`merchant_worker_id` DESC')->limit($page->firstRow . ',' . $page->listRows)->select();

        foreach ($work_list as $k => $v) {
            $work_list[$k]['avatar_path'] = str_replace(',', '/', $v['avatar_path']);
        }

        $this->assign('work_list', $work_list);
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);

        //店铺列表
        $mer_id = $this->merchant_session['mer_id'];
        $db_arr = array(C('DB_PREFIX') . 'area' => 'a', C('DB_PREFIX') . 'merchant_store' => 's');
        $store_info = D()->table($db_arr)->where("`s`.`mer_id`='$mer_id' AND `s`.`area_id`=`a`.`area_id` AND s.status!=4")->order('`sort` DESC,`store_id` ASC')->getField('store_id,name');
        $this->assign('store_info', $store_info);
        $this->display();
    }

    // 解除绑定
    public function cancel_bind_wx()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (D('Merchant_workers')->where(array('merchant_worker_id' => $id))->save(array('openid' => '', 'avatar' => '', 'nickname' => ''))) {
            exit(json_encode(array('error_code' => 0, 'msg' => 'ok')));
        } else {
            exit(json_encode(array('error_code' => 1, 'msg' => L_('取消失败'))));
        }
    }


    //添加工作人员
    public function worker_add(){
        $database_store = D('Merchant_store');
        $database_merchant_workers = D('Merchant_workers');
        $mer_id = $this->merchant_session['mer_id'];
        if (IS_POST) {
            $data['name'] = trim($_POST['worker_name']);
            $data['sex'] = $_POST['sex'] + 0;
            $data['avatar_path'] = trim($_POST['pic']);
            $data['mobile'] = $_POST['mobile'];
            $data['desc'] = trim($_POST['desc']);
            $data['appoint_type'] = $_POST['appoint_type'] + 0;
            $data['merchant_store_id'] = $_POST['store_id'] + 0;
            $data['status'] = $_POST['status'] + 0;
            $_POST['phone_country_type'] && $data['phone_country_type'] = intval($_POST['phone_country_type']);
            $data['is_reward'] = $_POST['is_reward'] + 0;
            $data['reward_money'] = $_POST['reward_money'] + 0;
            $data['info'] = $_POST['info']?:"";
            if(mb_strlen($data['info'])>200){
                $this->error(L_('简介不能超过200字！'));
            }
			
			$data['mobile'] = phone_format($data['phone_country_type'], $data['mobile']);
			$data['username'] = phone_format($data['phone_country_type'], $data['username']);

            if (empty($data['is_reward'])) {
                $data['is_reward'] = 1;
            } elseif ($data['is_reward'] == 2) {
                if (floatval($data['reward_money']) <= 0) {
                    $this->error(L_('打赏金额必须大于零！'));
                }
                $data['reward_money'] = round($data['reward_money'], 2);
            }

            if (!$data['name']) {
                $this->error(L_('姓名不能为空！'));
            }
            if(!$this->config['international_phone'] && !preg_match('/^[0-9]{11}$/',$data['mobile'])){
                $this->error(L_('请输入有效的手机号'));
            }

            if(!$this->config['international_phone'] && !preg_match('/^[0-9]{11}$/',$_POST['username'])){
                $this->error(L_('账号必须是手机号'));
            }

            if (!$data['avatar_path']) {
                $this->error(L_('头像不能为空！'));
            }

            //时间间隔
            if ($_POST['time_gap'] != 0) {
                $data['time_gap'] = $_POST['time_gap'] + 0;
            }

            if (($data['time_gap'] % 10 != 0) && ($data['time_gap'] != -1)) {
                $this->error(L_('时间间隔必须是10的倍数'));
            }

            if (!$data['merchant_store_id']) {
                $this->error(L_('请选择店铺！'));
            }

            //营业时间
            // if ($_POST['office_start_time'] != '00:00' || $_POST['office_stop_time'] != '00:00') {
            //     $office_time = array('open' => $_POST['office_start_time'], 'close' => $_POST['office_stop_time']);
            // } else {
            //     $office_time = array('open' => '00:00', 'close' => '00:00');
            // }

            $office_time = array();
            foreach ($_POST['office_start_time'] as $key => $value) {
                if ($key > 0 && strtotime($value) < strtotime($_POST['office_stop_time'][$key-1])) {
                    $this->error(L_('开始时间不能小于上一时间段结束时间'));
                }
                if ($value != '00:00' || $_POST['office_stop_time'][$key] != '00:00') {
                    $office_time[] = array('open' => $value, 'close' => $_POST['office_stop_time'][$key]);
                } else {
                    $office_time = array(array('open' => '00:00', 'close' => '00:00'));
                }
            }

            if ($office_time) {
                $data['office_time'] = serialize($office_time);
            }

            $data['username'] = trim($_POST['username']);
            $_POST['password'] = trim($_POST['password']);
            if(! $data['username']){
                $this->error(L_('帐号不能为空！'));
            }
            if(!$_POST['password']){
                $this->error(L_('密码不能为空！'));
            }

            $account_where['username'] =  $data['username'];
            $now_account = $database_merchant_workers->where($account_where)->find();
            if(!empty($now_account)){
                $this->error(L_('帐号已经存在！请换一个。'));
            }

            $data['password'] = md5($_POST['password']);

            //预约人数
            if ($_POST['appoint_people'] != 0) {
                //$data['appoint_people'] = $_POST['appoint_people'] + 0;
                $data['appoint_people'] = 1;
            }
            $data['mer_id'] = $mer_id;

            $result = $database_merchant_workers->merchant_workers_add($data);

            if ($result) {
                $this->success(L_('工作人员添加成功！'));
            } else {
                $this->error(L_('工作人员添加失败！'));
            }
        } else {
            $condition_appoint['mer_id'] = $mer_id;
            $condition_appoint['status'] = 1;
            $store_list = $database_store->where($condition_appoint)->select();
            if (empty($store_list)) {
                $this->error(L_('现在还没有店铺，去添加吧。'));
            }

            $this->assign('store_list', $store_list);
            $this->display();
        }
    }


    public function worker_edit(){
        $database_merchant_workers = D('Merchant_workers');
        $database_store = D('Merchant_store');

        if (IS_POST) {
            $data['name'] = trim($_POST['worker_name']);
            $data['sex'] = $_POST['sex'] + 0;
            $data['avatar_path'] = trim($_POST['pic']);
            $data['mobile'] = $_POST['mobile'];
            $data['desc'] = trim($_POST['desc']);
            $data['appoint_type'] = $_POST['appoint_type'] + 0;
            $data['merchant_store_id'] = $_POST['store_id'] + 0;
            $data['status'] = $_POST['status'] + 0;
            $data['is_reward'] = $_POST['is_reward'] + 0;
            $data['commission_percent'] = $_POST['commission_percent'] + 0;
            $data['help_status'] = intval($_POST['help_status']);
            $data['reward_money'] = $_POST['reward_money'] + 0;
            $data['info'] = $_POST['info']?:"";
            $data['modify_order_price'] = $_POST['modify_order_price']?:0;
            $_POST['phone_country_type'] && $data['phone_country_type'] = intval($_POST['phone_country_type']);
			
			$data['mobile'] = phone_format($data['phone_country_type'], $data['mobile']);
			$data['username'] = phone_format($data['phone_country_type'], $data['username']);

            if($data['commission_percent'] < 0 || $data['commission_percent'] > 100){
                $this->error(L_('服务费结算比例错误!'));
            }
            if(mb_strlen($data['info'])>200){
                $this->error(L_('简介不能超过200字！'));
            }
            if (!$data['name']) {
                $this->error(L_('姓名不能为空！'));
            }
            if (empty($data['is_reward'])) {
                $data['is_reward'] = 1;
            } elseif ($data['is_reward'] == 2) {
                if (floatval($data['reward_money']) <= 0) {
                    $this->error(L_('打赏金额必须大于零！'));
                }
                $data['reward_money'] = round($data['reward_money'], 2);
            }

            $phone_match = '/^(0|86|17951)?(13|14|15|16|17|18|19)[0-9][0-9]{8}$/';
            $mobile_match = '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/';
            if (!$data['mobile']) {
                $this->error(L_('联系号码不能为空！'));
            } else if (!$this->config['international_phone'] && (!preg_match($phone_match, $data['mobile'])) && (!preg_match($mobile_match, $data['mobile']))) {
                $this->error(L_('联系号码填写不正确！'));
            }

            if (!$data['avatar_path']) {
                $this->error(L_('头像不能为空！'));
            }

            if (!$data['merchant_store_id']) {
                $this->error(L_('请选择店铺！'));
            }
            //营业时间
            // if ($_POST['office_start_time'] != '00:00' || $_POST['office_stop_time'] != '00:00') {
            //     $office_time = array('open' => $_POST['office_start_time'], 'close' => $_POST['office_stop_time']);
            // } else {
            //     $office_time = array('open' => '00:00', 'close' => '00:00');
            // }

            $office_time = array();
            foreach ($_POST['office_start_time'] as $key => $value) {
                if ($key > 0 && strtotime($value) < strtotime($_POST['office_stop_time'][$key-1])) {
                    $this->error(L_('开始时间不能小于上一时间段结束时间'));
                }
                if ($value != '00:00' || $_POST['office_stop_time'][$key] != '00:00') {
                    $office_time[] = array('open' => $value, 'close' => $_POST['office_stop_time'][$key]);
                } else {
                    $office_time = array(array('open' => '00:00', 'close' => '00:00'));
                }
            }

            if ($office_time) {
                $data['office_time'] = serialize($office_time);
            }

            //时间间隔
            if ($_POST['time_gap']) {
                $data['time_gap'] = $_POST['time_gap'] + 0;
            }

            if(($data['time_gap'] % 10 != 0) && ($data['time_gap'] != -1)){
                $this->error(L_('间隔时间必须是10的倍数'));
            }

            //预约人数
            if ($_POST['appoint_people']) {
                $data['appoint_people'] = 1;
            }

            $data['username'] = trim($_POST['username']);
            $_POST['password'] = trim($_POST['password']);
            if(!$data['username']){
                $this->error(L_('帐号不能为空！'));
            }

            $account_where['username'] = $data['username'];
            $now_account = $database_merchant_workers->where($account_where)->find();

            if(!$_POST['password'] && !$now_account['password']){
                $this->error(L_('密码不能为空！'));
            }

            $merchant_worker_id = $_POST['merchant_worker_id'] + 0;

            if($now_account && ($now_account['merchant_worker_id'] != $merchant_worker_id)){
                $this->error(L_('帐号已经存在！请换一个。'));
            }

            $_POST['password'] && $data['password'] = md5($_POST['password']);

            $where['merchant_worker_id'] = $merchant_worker_id;
            $data['mer_id'] = $this->merchant_session['mer_id'];
            $result = $database_merchant_workers->merchant_workers_edit($data, $where);

            if ($result) {
                $this->success(L_('修改成功！'));
            } else {
                $this->error(L_('修改失败！'));
            }

        } else {
            $merchant_worker_id = $_GET['merchant_worker_id'] + 0;
            $where['merchant_worker_id'] = $merchant_worker_id;
            $worker_detail = $database_merchant_workers->where($where)->find();
            if (!$worker_detail) {
                $this->error(L_('该工作人员不存在！'));
            }

            $office_time = array();
            if ($worker_detail['office_time']) {
                $office_time = unserialize($worker_detail['office_time']);
                if(isset($office_time['open']) && isset($office_time['close'])){
                    $office_time = array($office_time);
                }
            }
            $worker_detail['desc'] = $worker_detail['desc']?replace_file_domain_content(htmlspecialchars_decode($worker_detail['desc'])):'';
            $worker_detail['s_avatar_path'] = str_replace(',', '/s_', $worker_detail['avatar_path']);
            $this->assign('worker_detail', $worker_detail);


            $Map['mer_id'] = $this->merchant_session['mer_id'];
            $Map['status'] = 1;
            $store_list = $database_store->field(true)->where($Map)->select();
            if (empty($store_list)) {
                $this->error(L_('现在还没有店铺，去添加吧。'));
            }
            $this->assign('store_list', $store_list);
            $this->assign('office_time', $office_time);
            $this->display();
        }
    }

    //删除工作人员
    public function worker_del(){
        $database_merchant_workers = D('Merchant_workers');
        $merchant_worker_id = $_POST['merchant_worker_id'] + 0;
        if (!$merchant_worker_id) {
            exit(json_encode(array('status' => 0, 'error_msg' => L_('传递参数有误！'))));
        }

        $where['merchant_worker_id'] = $merchant_worker_id;
        $result = $database_merchant_workers->where($where)->delete();
        M('Merchant_workers_appoint')->where($where)->delete();
        if ($result) {
            exit(json_encode(array('status' => 1, 'error_msg' => L_('删除成功！'))));
        }
    }

    //工作人员评论
    public function worker_reply(){
        $database_appoint = D('Appoint');
        $database_merchant_workers = D('Merchant_workers');

        $mer_id = $this->merchant_session['mer_id'];
        $merchant_worker_id = $_GET['merchant_worker_id'] + 0;
        $_Map['mer_id'] = $mer_id;
        $_Map['status'] = 1;
        $worker_list = $database_merchant_workers->where($_Map)->getField('merchant_worker_id,name');
        $this->assign('worker_list', $worker_list);
        $table = array(C('DB_PREFIX') . 'appoint_comment' => 'c', C('DB_PREFIX') . 'appoint_order' => 'o', C('DB_PREFIX') . 'merchant_workers' => 'w');
        if (!empty($merchant_worker_id)) {
            $condition = "`c`.mer_id=" . $mer_id . ' AND `o`.`order_id`=`c`.`order_id` AND `c`.`merchant_worker_id`=`w`.`merchant_worker_id` AND `c`.`merchant_worker_id`=' . $merchant_worker_id;
        } else {
            $condition = "`c`.mer_id=" . $mer_id . ' AND `o`.`order_id`=`c`.`order_id` AND `c`.`merchant_worker_id`=`w`.`merchant_worker_id`';
        }
        $reply_count = D('')->table($table)->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($reply_count, 20);
        $field = array('o.order_id,o.mer_id,o.uid,c.*,(c.profession_score+c.communicate_score+c.speed_score)/3 as all_avg_score');
        $reply_list = D('')->table($table)->where($condition)->field($field)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('reply_list', $reply_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);


        $store_id = $_GET['store_id'] + 0;
        $appoint_list = $database_appoint->get_store_appoint_list($store_id, 100);
        $this->assign('appoint_list', $appoint_list);
        $this->display();
    }

    public function worker_reply_detail(){
        $database_appoint_comment = D('Appoint_comment');
        $database_merchant_workers = D('Merchant_workers');
        $pigcms_id = $_GET['id'] + 0;
        if (empty($pigcms_id)) {
            $this->error(L_('传递参数有误！'));
        }

        $where['id'] = $pigcms_id;
        $appoint_comment_info = $database_appoint_comment->where($where)->field(array('*','(profession_score+communicate_score+speed_score)/3 as all_avg_score'))->find();
        $_where['merchant_worker_id'] = $appoint_comment_info['merchant_worker_id'];
        $merchant_worker_info = $database_merchant_workers->where($_where)->field('name,merchant_store_id as store_id')->find();
        $reply_detail = array_merge($appoint_comment_info, $merchant_worker_info);
        if (empty($reply_detail)) {
            $this->error(L_('该评论不存在！'));
        }

        if (IS_POST) {
            $database_appoint_comment = D('Appoint_comment');
            $condition_reply['id'] = $reply_detail['id'];

            if (!$reply_detail['merchant_reply_time']) {
                $data_reply['merchant_reply_content'] = $_POST['reply_content'] ? $_POST['reply_content'] : '';
            }
            $data_reply['status'] = $_POST['status'];
            $data_reply['merchant_reply_time'] = $_SERVER['REQUEST_TIME'];
            if ($database_appoint_comment->where($condition_reply)->data($data_reply)->save()) {
                $this->success(L_('回复成功！'));
            } else {
                $this->error(L_('回复失败！请重试。'));
            }
        } else {
            //查找出店铺名称
            if (!empty($reply_detail['store_id'])) {
                $now_store = D('Merchant_store')->get_store_by_storeId($reply_detail['store_id']);
                $this->assign('now_store', $now_store);
            }
            $this->assign('reply_detail', $reply_detail);

            //图片列表
            $where['id'] = $pigcms_id;
            $reply_pic_list = $database_appoint_comment->where($where)->getField('comment_img');
            if ($reply_pic_list) {
                $this->assign('reply_pic_list', unserialize($reply_pic_list));
            }

            $this->display();
        }
    }


    public function ajax_worker_list(){
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $appoint_type = !empty($_POST['appoint_type']) ? $_POST['appoint_type'] + 0 : 0;
        $appoint_id = !empty($_POST['appoint_id']) ? $_POST['appoint_id'] + 0 : 0;
        $where['appoint_type'] = array('in', array($appoint_type, 2));
        $where['status'] = 1;
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $database_merchant_workers = D('Merchant_workers');
        $worker_list = $database_merchant_workers->where($where)->select();
        $Map['appoint_id'] = $appoint_id;
        $merchant_workers_appoint_list = $database_merchant_workers_appoint->where($Map)->getField('id,merchant_worker_id');
        foreach ($worker_list as $k => $v) {
            if (in_array($v['merchant_worker_id'], $merchant_workers_appoint_list)) {
                $worker_list[$k]['selected'] = 'selected';
            }
        }
        exit(json_encode($worker_list));
    }


    //预约评论
    public function appoint_reply(){

        $database_appoint = D('Appoint');

        $mer_id = $this->merchant_session['mer_id'];
        $appoint_id = $_GET['appoint_id'] + 0;
        $store_list = D('Merchant_store')->field('store_id, name')->where("`have_meal`=1 AND `status`=1 AND `mer_id`='{$mer_id}'")->select();
        $this->assign('store_list', $store_list);
        $table = array(C('DB_PREFIX') . 'reply' => 'r', C('DB_PREFIX') . 'appoint_order' => 'o');
        if (!empty($appoint_id)) {
            $condition = "`r`.`order_type`='2' AND `r`.`order_id`=`o`.`order_id` AND `o`.`mer_id`='$mer_id' AND `o`.`appoint_id`='$appoint_id'";
        } else {
            $condition = "`r`.`order_type`='2' AND `r`.`order_id`=`o`.`order_id` AND `o`.`mer_id`='$mer_id'";
        }

        $reply_count = D('')->table($table)->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($reply_count, 20);

        $reply_list = D('')->table($table)->where($condition)->order('`r`.`add_time` desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('reply_list', $reply_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);


        $store_id = $_GET['store_id'] + 0;
        $appoint_list = $database_appoint->get_store_appoint_list($store_id, 100);
        $this->assign('appoint_list', $appoint_list);
        $this->display();
    }

    //预约评论详情
    public function appoint_reply_detail(){
        $mer_id = $this->merchant_session['mer_id'];
        $pigcms_id = $_GET['id'] + 0;

        $table = array(C('DB_PREFIX') . 'reply' => 'r', C('DB_PREFIX') . 'appoint_order' => 'o');
        $condition = "`r`.`order_type`='2' AND `r`.`order_id`=`o`.`order_id` AND `o`.`mer_id`='$mer_id' AND `r`.`pigcms_id`='$pigcms_id'";
        $reply_detail = D('')->table($table)->where($condition)->find();

        if (empty($reply_detail)) {
            $this->error(L_('该评论不存在！'));
        }
        $database_reply_pic = D('Reply_pic');
        if (IS_POST) {
            $database_reply = D('Reply');
            $condition_reply['pigcms_id'] = $reply_detail['pigcms_id'];
            $where_appoint_comment['order_id'] = $reply_detail['order_id'];
            $data_reply['merchant_reply_content'] = $_POST['reply_content'];
            $data_reply['merchant_reply_time'] = $_SERVER['REQUEST_TIME'];
            $data_reply['status'] = $this->_post('status');

            if ($database_reply->where($condition_reply)->data($data_reply)->save()) {
                M('Appoint_comment')->where($where_appoint_comment)->save($data_reply);
                //echo $database_reply->_sql();exit;
                $this->success(L_('回复成功！'));
            } else {
                $this->error(L_('回复失败！请重试。'));
            }
        } else {
            //查找出店铺名称
            if (!empty($reply_detail['store_id'])) {
                $now_store = D('Merchant_store')->get_store_by_storeId($reply_detail['store_id']);
                $this->assign('now_store', $now_store);
            }
            $this->assign('reply_detail', $reply_detail);

            //图片列表
            $where['order_id'] = $reply_detail['order_id'];
            $where['order_type'] = 2;
            $reply_pic_list = $database_reply_pic->where($where)->getField('pigcms_id,pic');

            fdump($reply_detail,'$reply_detail');
            fdump($reply_pic_list,'$reply_pic_list');

            if ($reply_pic_list) {
                foreach ($reply_pic_list as $k => $v) {
                    $v = str_replace(',', '/', $v);
                    if((stripos($v,'/upload/') !== false)) {
                        $reply_pic_list[$k] = replace_file_domain($v);
                    } else {
                        $reply_pic_list[$k] = file_domain() . '/upload/reply/appoint/' . $v;
                    }
                }

                $this->assign('reply_pic_list', $reply_pic_list);
            }

            $this->display();
        }
    }


    public function allot_order_list(){
        ini_set('memory_limit', '512M');
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $where['merchant_worker_id'] = 0;
        $where['type'] = 1;
        $where['is_del'] = 0;
		//临时解决搜索订单号的问题
		if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $where['order_id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
                unset($where['orderid']);
                $where['order_id'] = $tmp_result['order_id'];
            }elseif ($_GET['searchtype'] == 'third_id') {
                $where['third_id'] =$_GET['keyword'];
            }elseif ($_GET['searchtype'] == 'real_orderid') {
                $where['real_orderid'] =$_GET['keyword'];
            }
        }
		
        $order_count = $database_order->where($where)->count();

        import('@.ORG.merchant_page');
        $page = new Page($order_count, 20);
        $order_info = $database_order->field(true)->where($where)->order('`platform_allocation_time` desc ,`order_id` DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->select();
        $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`')->select();
        $store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
        $order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('order_list', $order_list);
        $this->display();
    }

    public function allot_store(){
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_store = D('Merchant_store');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_appoint_supply = D('Appoint_supply');
        $order_id = $_GET['order_id'] + 0;
        $mer_id = $this->merchant_session['mer_id'];
        $where['order_id'] = $order_id;
        $where['mer_id'] = $mer_id;

        $now_order = $database_order->where($where)->find();
        $where_user['uid'] = $now_order['uid'];
        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where($where_user)->find();
        $where_appoint['appoint_id'] = $now_order['appoint_id'];
        $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`,`payment_status`, `appoint_type`, `appoint_price`,`is_store`')->where($where_appoint)->find();

        $now_order['phone'] = $user_info['phone'];
        $now_order['nickname'] = $user_info['nickname'];
        $now_order['appoint_name'] = $appoint_info['appoint_name'];
        $now_order['appoint_type'] = $appoint_info['appoint_type'];
        $now_order['appoint_price'] = $appoint_info['appoint_price'];
        $now_order['is_appoint_price'] = floatval($appoint_info['appoint_price']);
        $now_order['is_store'] = $appoint_info['is_store'];
        
        if(
            empty(floatval($now_order['payment_money'])) &&
            empty(floatval($now_order['product_payment_price'])) &&
            empty(floatval($now_order['paid']))
        ){
            $now_order['paid'] = 1;
        }
        $cue_info = unserialize($now_order['cue_field']);
        $cue_list = array();
        foreach ($cue_info as $key => $val) {
            if (!empty($cue_info[$key]['value'])) {
                $cue_list[$key]['name'] = $val['name'];
                $cue_list[$key]['value'] = $val['value'];
                $cue_list[$key]['type'] = $val['type'];
                if ($cue_info[$key]['type'] == 2) {
                    $cue_list[$key]['long'] = $val['long'];
                    $cue_list[$key]['lat'] = $val['lat'];
                    $cue_list[$key]['address'] = $val['address'];
                    if ($val['phone']) {
                        $cue_list[$key]['phone'] = $val['phone'];
                    }
                    if ($val['showName']) {
                        $cue_list[$key]['showName'] = $val['showName'];
                    }
                }
            }
        }

        //上门预约工作人员信息start
        $Map['appoint_order_id'] = $order_id;
        $worker_where['merchant_worker_id'] = $now_order['merchant_worker_id'];
        $worker_field = array('merchant_worker_id', 'name', 'mobile', 'merchant_store_id');
       
        $merchant_workers_info = $database_merchant_workers->appoint_worker_info($worker_where, $worker_field);

        $this->assign('merchant_workers_info', $merchant_workers_info);
        //上门预约工作人员信息end


        $Map['mer_id'] = $mer_id;
        $Map['status'] = 1;

        // 开启店铺的，选择选中的店铺
        if ($appoint_info['is_store']==1) {
            $appoint_store = M('Appoint_store')->where(array('appoint_id'=>$appoint_info['appoint_id']))->select();
            $store_ids = array();
            if ($appoint_store) {
                foreach ($appoint_store as $key => $value) {
                   $store_ids[] = $value['store_id'];
                }
                $Map['store_id'] = array('in',$store_ids);
            }            
        }
        $store_list = $database_merchant_store->where($Map)->getField('store_id,name');
        $this->assign('store_list', $store_list);
        $this->assign('cue_list', $cue_list);
        $this->assign('now_order', $now_order);
        $this->assign('appoint_info', $appoint_info);

        $_Map['mer_id'] = $mer_id;
        $_Map['check_status'] = 1;
        $_Map['payment_status'] = 0;
        $_Map['appoint_status'] = 0;
        $_Map['start_time'] = array('lt', time());
        $_Map['end_time'] = array('gt', time());
        $_Map['cat_id'] = $now_order['cat_id'];
        $_Map['cat_fid'] = $now_order['cat_fid'];
        $_Map['appoint_type'] = 1;
        $appoint_list = $database_appoint->where($_Map)->getField('appoint_id,appoint_name');
        $this->assign('appoint_list', $appoint_list);


        $appoint_supply_count = $database_appoint_supply->where(array('order_id'=>$order_id))->count();
        $this->assign('appoint_supply_count' , $appoint_supply_count);
        $this->display();
    }


    public function merchant_order_list(){
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $where['mer_id'] = $this->merchant_session['mer_id'];
        $where['merchant_worker_id'] = 0;
        $where['is_del'] = 0;
		//临时解决搜索订单号的问题
		if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $where['order_id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
                unset($where['orderid']);
                $where['order_id'] = $tmp_result['order_id'];
            }elseif ($_GET['searchtype'] == 'third_id') {
                $where['third_id'] =$_GET['keyword'];
            }elseif ($_GET['searchtype'] == 'real_orderid') {
                $where['real_orderid'] =$_GET['keyword'];
            }
        }
		
        $order_count = $database_order->where($where)->count();

        import('@.ORG.merchant_page');
        $page = new Page($order_count, 20);
        $order_info = $database_order->where($where)->order('`order_time`desc , `merchant_allocation_time` desc , `order_id` DESC')->limit($page->firstRow . ',' . $page->listRows)->select();

        $uidArr = array_column($order_info, 'uid');
        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(['uid'=> ['in', array_filter($uidArr)]])->select();

        $appointIdArr = array_column($order_info, 'appoint_id');
        $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`')->where(['appoint_id'=>['in', array_filter($appointIdArr)]])->select();

        $storeIdArr = array_column($order_info, 'store_id');
        $store_info = $database_store->field('`store_id`, `name`, `adress`')->where(['uid'=> ['in', array_filter($storeIdArr)]])->select();
        $order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('order_list', $order_list);
        $this->display();
    }


    public function ajax_worker(){
        $store_id = $_POST['store_id'] + 0;
        $appoint_id = $_POST['appoint_id'] + 0;
        $is_store = $_POST['is_store'] + 0;
        if (empty($store_id)) {
            exit(json_encode(array('status' => 0, 'msg' => L_('传递参数有误！'))));
        }


        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $where['merchant_store_id'] = $store_id;
        if (!$is_store) {
            $where['status'] = 1;
            $worker_list = $database_merchant_workers->where($where)->getField('merchant_worker_id,name');
        } else {
            if (!$appoint_id) {
                exit(json_encode(array('status' => 0, 'msg' => L_('传递参数有误！'))));
            }
            $where['appoint_id'] = $appoint_id;
            $worker_arr = $database_merchant_workers_appoint->where($where)->getField('merchant_worker_id', true);
            $_Map['status'] = 1;
            $_Map['merchant_worker_id'] = array('in', $worker_arr);
            $worker_list = $database_merchant_workers->where($_Map)->getField('merchant_worker_id,name');
        }


        exit(json_encode(array('status' => 1, 'worker_list' => $worker_list)));
    }

    public function ajax_store(){
        $appoint_id = $_POST['appoint_id'] + 0;

        if (empty($appoint_id)) {
            exit(json_encode(array('status' => 0, 'msg' => L_('传递参数有误！'))));
        }
        $database_appoint_store = D('Appoint_store');
        $database_merchant_store = D('Merchant_store');
        $store_arr = $database_appoint_store->where(array('appoint_id' => $appoint_id))->getField('store_id', true);
        $where['store_id'] = array('in', $store_arr);
        $store_list = $database_merchant_store->where($where)->getField('store_id,name');
        exit(json_encode(array('status' => 1, 'store_list' => $store_list)));
    }


    public function ajax_order_edit(){
        if(IS_AJAX){
            $store_id = $_POST['store_id'] + 0;
            $merchant_worker_id = $_POST['merchant_worker_id'] + 0;
            $appoint_id = $_POST['appoint_id'] + 0;
            $order_id = $_POST['order_id'] + 0;
            $type = $_POST['type'] + 0;
            if (empty($order_id)) {
                exit(json_encode(array('status' => 0, 'msg' => L_('传递参数有误！'))));
            }

            if (empty($appoint_id)) {
                exit(json_encode(array('status' => 0, 'msg' => L_('预约不能为空！'))));
            }

            if (empty($store_id)) {
                exit(json_encode(array('status' => 0, 'msg' => L_('店铺不能为空！'))));
            }

            $database_appoint_order = D('Appoint_order');
            $now_order = $database_appoint_order->where(array('order_id'=>$order_id))->find();

            if(!$now_order){
                exit(json_encode(array('status' => 0, 'msg' => L_('订单不存在！'))));
            }

            $database_appoint = D('Appoint');
            $appoint_info = $database_appoint->get_appoint_by_appointId($appoint_id);


            $where['order_id'] = $order_id;
            $data['store_id'] = $store_id;
            $data['appoint_id'] = $appoint_id;
            $data['merchant_allocation_time'] = time();
            $data['merchant_worker_id'] = $merchant_worker_id;
            $data['payment_money'] = $appoint_info['payment_money'];
            $data['appoint_price'] =  $appoint_info['appoint_price'];
            $data['appoint_type'] =  $appoint_info['appoint_type'];
            $data['merchant_assign_time'] =  time();
            $insert_id = $database_appoint_order->where($where)->data($data)->save();
            $database_appoint_visit_order_info = D('Appoint_visit_order_info');
            $Map['appoint_order_id'] = $order_id;
            $_data['merchant_worker_id'] = $merchant_worker_id;
            $_data['type'] = $type;
            $appoint_visit_order_info_insert_id = $database_appoint_visit_order_info->where($Map)->data($_data)->save();


            $sms_key = C('config.sms_key');
            if (isset($sms_key) && !empty($sms_key) && (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3)) {
                $database_merchant_store = D('Merchant_store');
                $now_store = $database_merchant_store->get_store_by_storeId($store_id);
                $sms_data = array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id'], 'type' => 'appoint');
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $now_store['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['content'] = L_('有份新的预约订单，订单号：X1 请您注意查看并处理!',array('X1'=>$order_id));
                Sms::sendSms($sms_data);

                $staff_where['store_id'] = $store_id;
                $staff_arr = D('Merchant_store_staff')->where($staff_where)->field('tel,openid')->select();

                //发送店员
                if ($staff_arr) {
                    foreach ($staff_arr as $v) {
                        $sms_data = array('mer_id' => $now_store['mer_id'], 'store_id' => $now_store['store_id'], 'type' => 'appoint');
                        $sms_data['uid'] = 0;
                        $sms_data['mobile'] = $v['tel'];
                        $sms_data['sendto'] = 'merchant';
                        $sms_data['content'] = L_('有份新的预约订单，订单号：X1 请您注意查看并处理!',array('X1'=>$order_id));
                        Sms::sendSms($sms_data);

                        if ($v['openid']) {
                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Storestaff&a=appoint_edit&order_id='.$order_id;
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $v['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $order_id, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $appoint_info['appoint_name'], 'remark' => L_('您有新的预约，您注意查看并处理')), $now_store['mer_id']);
                        }
                    }
                }
            }
            if ($insert_id) {
                if($data['store_id']){
                    $now_order['store_id'] = $data['store_id'];
                    D('Merchant_store_staff')->sendMsgAppointOrder($now_order);
                }
                $database_appoint_visit_order_info = D('Appoint_visit_order_info');
                $worker_where['appoint_order_id'] = $now_order['order_id'];
                $now_worker = $database_appoint_visit_order_info->appoint_visit_order_detail($worker_where);
                $now_worker = $now_worker['detail'];
                if($now_order['appoint_date']&&$now_order['appoint_time']){
                    $tmp_appoint_time = $now_order['appoint_date'] . ' ' . $now_order['appoint_time'];
                    $sms_msg = L_('商家为您分配新的预约订单，预约服务时间：X1，订单号：X2，请注意查收！',array('X1'=>$tmp_appoint_time,'X2'=>$order_id));
                }else{
                    $sms_msg = L_('商家为您分配新的预约订单，订单号：X1，请注意查收！',array('X1'=>$order_id));
                }
                if((C('config.merchant_worker_sms_order') == 1) && !empty($now_worker)){
                    $sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'type' => 'appoint');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $now_worker['mobile'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = $sms_msg;
                    Sms::sendSms($sms_data);
                }
                if (!empty($now_worker)&&$now_worker['openid']) {
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                    $model->sendTempMsg('TM00017',
                        array(
                            'href' => $href,
                            'wecha_id' => $now_worker['openid'],
                            'first' => $sms_msg,
                            'OrderSn' => $now_order['order_id'],
                            'OrderStatus' => L_('分配新订单提醒'),
                            'remark' => '\n'.L_('请点击查看详细信息！')
                        )
                    );
                }

                $database_appoint_supply = D('Appoint_supply');
                $now_supply_order = $database_appoint_supply->where(array('order_id'=>$now_order['order_id']))->find();
                $supply_data['appoint_id'] = $appoint_id;
                $supply_data['mer_id'] = $now_order['mer_id'];
                $supply_data['store_id'] = $store_id;
                $supply_data['create_time'] = time();
                $supply_data['status'] =  1;
                if($merchant_worker_id > 0){
                    $supply_data['worker_id'] = $merchant_worker_id;
                    $supply_data['get_type'] = 1;
                    $supply_data['status'] =  2;
                    $orderPrice = floatval($now_order['product_price']) > 0 ? $now_order['product_price'] : $now_order['appoint_price'];
                    $supply_data['commission'] = D('Merchant_workers')->appointServiceFee($merchant_worker_id, $orderPrice);
                }

                $supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
                $supply_data['paid'] = $now_order['paid'];
                $supply_data['pay_type'] = $now_order['pay_type'];
                $supply_data['order_time'] = $now_order['order_time'];
                $supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
                $supply_data['uid'] = $now_order['uid'];

                if(!$now_supply_order){
                    $supply_data['order_id'] = $now_order['order_id'];
                    $database_appoint_supply->data($supply_data)->add();
                }else{
                    $supply_where['order_id'] = $now_order['order_id'];
                    $database_appoint_supply->where($supply_where)->data($supply_data)->save();
                }

                exit(json_encode(array('status' => 1, 'msg' => L_('订单分配成功！'))));
            } else {
                if ($appoint_visit_order_info_insert_id) {
                    $database_appoint_supply = D('Appoint_supply');

                    $supply_data['order_id'] = $now_order['order_id'];
                    $supply_data['worker_id'] = $merchant_worker_id > 0 ? $merchant_worker_id : 0;
                    $supply_data['mer_id'] = $now_order['mer_id'];
                    $supply_data['store_id'] = $store_id;
                    $supply_data['create_time'] = time();
                    $supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
                    $supply_data['paid'] = $now_order['paid'];
                    $supply_data['status'] =  1;
                    $supply_data['pay_type'] = $now_order['pay_type'];
                    $supply_data['order_time'] = $now_order['order_time'];
                    $supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
                    $supply_data['uid'] = $now_order['uid'];
                    $database_appoint_supply->data($supply_data)->add();

                    exit(json_encode(array('status' => 1, 'msg' => L_('订单分配成功！'))));
                } else {
                    exit(json_encode(array('status' => 0, 'msg' => L_('订单分配失败！'))));
                }
            }
        }else{
            $this->error_tips(L_('访问页面有误！~~~~'));
        }
    }


    //删除订单
    public function ajax_merchant_del(){
        $order_id = $_POST['order_id'] + 0;
        if (empty($order_id)) {
            exit(json_encode(array('msg' => L_('传递参数有误！'), 'status' => 0)));
        }

        $database_appoint_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $data['del_time'] = time();
        $data['is_del'] = 3;
        $result = $database_appoint_order->where($where)->data($data)->save();
        if ($result) {
            /*-----记录系统订单信息 订单取消成功 改写状态已取消 2020/8/7----*/
            D("System_order")->save_order_status('appoint',$order_id,5);
            exit(json_encode(array('msg' => L_('取消成功！'), 'status' => 1)));
        } else {
            exit(json_encode(array('msg' => L_('取消失败！'), 'status' => 0)));
        }
    }

    /* 格式化数据 */
    protected function formatArray($appoint_info, $merchant_info, $category_info){
        if (!empty($merchant_info)) {
            $merchant_array = array();
            foreach ($merchant_info as $val) {
                $merchant_array[$val['mer_id']]['mer_name'] = $val['name'];
            }
        }
        if (!empty($category_info)) {
            $category_array = array();
            foreach ($category_info as $val) {
                $category_array[$val['cat_id']]['category_name'] = $val['cat_name'];
                $category_array[$val['cat_id']]['is_autotrophic'] = $val['is_autotrophic'];
            }
        }
        if (!empty($appoint_info)) {
            foreach ($appoint_info as &$val) {
                $val['mer_name'] = $merchant_array[$val['mer_id']]['mer_name'];
                $val['category_name'] = $category_array[$val['cat_id']]['category_name'];
                $val['is_autotrophic'] = $category_array[$val['cat_id']]['is_autotrophic'];
            }
        }
        return $appoint_info;
    }


    public function export()
    {
        $param = $_POST;
        $param['type'] = 'appoint';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
       //$param['appoint_id'] = 'appoint_id';
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
        die;
        set_time_limit(0);
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = L_('预约订单信息');
        $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
        $cacheSettings = array( 'dir' => './runtime' );
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        // 设置当前的sheet
        //$where['a.appoint_id'] = intval($_GET['appoint_id']);
        $where['a.mer_id'] = $this->merchant_session['mer_id'];
        if(!empty($_GET['keyword'])){
            if ($_GET['searchtype'] == 'order_id') {
                $where['a.order_id'] = $_GET['keyword'];
            } elseif ($_GET['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_GET['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
                unset($where['orderid']);
                $where['a.order_id'] = $tmp_result['order_id'];
            }elseif ($_GET['searchtype'] == 'name') {
                $where['u.username'] = htmlspecialchars($_GET['keyword']);
            } elseif ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = htmlspecialchars($_GET['keyword']);
            }elseif ($_GET['searchtype'] == 'third_id') {
                $where['a.third_id'] =$_GET['keyword'];
            }
        }
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if($pay_type){
            if($pay_type=='balance'){
                $where['_string'] = "(`a`.`balance_pay`<>0 OR `a`.`merchant_balance` <> 0 OR a.product_merchant_balance <> 0 OR a.product_balance_pay <> 0 )";
            }else{
                $where['a.pay_type'] = $pay_type;
            }
        }
        $database_appoint = D('Appoint');
        $database_order = D('Appoint_order');
        $now_appoint = $database_appoint->field(true)->where(array('appoint_id'=>$_GET['appoint_id']))->find();
        if(!empty($_GET['begin_time'])&&!empty($_GET['end_time'])){
            if ($_GET['begin_time']>$_GET['end_time']) {
                $this->error_tips(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_GET['begin_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.order_time BETWEEN ".$period[0].' AND '.$period[1].")";
        }
        $order_count = $database_order->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
            ->where($where)->count();

        $length = ceil($order_count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息',array('X1'=>($i+1))));
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', L_('订单编号'));
            $objActSheet->setCellValue('B1', L_('定金'));
            $objActSheet->setCellValue('C1', L_('总价'));
            $objActSheet->setCellValue('D1', L_('类型'));
            $objActSheet->setCellValue('E1', L_('用户昵称'));
            $objActSheet->setCellValue('F1', L_('手机号码'));
            $objActSheet->setCellValue('G1', L_('订单状态'));
            $objActSheet->setCellValue('H1', L_('服务状态'));
            $objActSheet->setCellValue('I1', L_('平台余额支付'));
            $objActSheet->setCellValue('J1', L_('商家会员卡支付'));
            $objActSheet->setCellValue('K1', L_('在线支付金额'));
            $objActSheet->setCellValue('L1', L_('下单时间'));
            $objActSheet->setCellValue('M1', L_('支付时间'));
            $objActSheet->setCellValue('N1', L_('支付方式'));
            $order_list = $database_order->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone,s.name as store_name,s.adress as store_adress')
                ->join('AS a left join '.C('DB_PREFIX').'appoint ap on a.appoint_id =ap.appoint_id left join '.C('DB_PREFIX').'user u ON a.uid = u.uid left join '.C('DB_PREFIX').'merchant_store s ON s.store_id = a.store_id ')
                ->where($where)->limit(($i*1000).',1000')->order('`order_id` DESC')->select();
            $result_list = $order_list;

            if (!empty($result_list)) {
                $index = 2;
                foreach ($result_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    if($value['product_id']>0){
                        $objActSheet->setCellValueExplicit('B' . $index,floatval($value['product_payment_price']));
                    }else{
                        $objActSheet->setCellValueExplicit('B' . $index, floatval($value['payment_money']));
                    }
                    if($value['product_price']>0){
                        $objActSheet->setCellValueExplicit('C' . $index,floatval($value['product_price']));
                    }else{
                        $objActSheet->setCellValueExplicit('C' . $index, floatval($value['appoint_price']));
                    }
                    if($value['type']==1){
                        $objActSheet->setCellValueExplicit('D' . $index, L_('自营'));
                    }else{
                        $objActSheet->setCellValueExplicit('D' . $index, L_('商家'));
                    }
                    $objActSheet->setCellValueExplicit('E' . $index, $value['nickname'] . ' ');
                    $objActSheet->setCellValueExplicit('F' . $index, $value['phone'] . ' ');
                    if($value['paid']==0){
                        $objActSheet->setCellValueExplicit('G' . $index, L_('未支付'));
                    }elseif($value['paid']==1){
                        $objActSheet->setCellValueExplicit('G' . $index, L_('已支付'));
                    }elseif($value['paid']==2){
                        $objActSheet->setCellValueExplicit('G' . $index, L_('已退款'));
                    }
                    if($value['service_status']==1){
                        $objActSheet->setCellValueExplicit('H' . $index, L_('未服务'));
                    }elseif($value['service_status']==2){
                        $objActSheet->setCellValueExplicit('H' . $index, L_('已服务'));
                    }elseif($value['service_status']==3){
                        $objActSheet->setCellValueExplicit('H' . $index, L_('已评价'));
                    }
                    $objActSheet->setCellValueExplicit('I' . $index, floatval($value['balance_pay']));
                    $objActSheet->setCellValueExplicit('J' . $index, floatval($value['merchant_balance']));
                    $objActSheet->setCellValueExplicit('K' . $index, floatval($value['pay_money']));
                    $objActSheet->setCellValueExplicit('L' . $index, $value['order_time'] ? date('Y-m-d H:i:s', $value['order_time']) : '');
                    $objActSheet->setCellValueExplicit('M' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                    $objActSheet->setCellValueExplicit('N' . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));


                    $index++;
                }
            }
            sleep(2);
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
        header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function change_appoint_order_note(){
        $order_id = $_POST['order_id'];
        $note = $_POST['note'];
        $database_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $update = $database_order->where($where)->setField('note',$note);

        if($update){
            echo json_encode(array('errorCode'=>0,'msg'=>L_('操作成功')));exit;
        }else{
            echo json_encode(array('errorCode'=>1,'msg'=>L_('操作失败')));exit;
        }
    }
    
    public function order_price_change_log(){
        $order_id = $_GET['order_id'];
        $log_list = [];
        if($order_id){
            $log_list = D('Merchant_workers_modify_order_log')->field(true)->where(['order_id'=>$order_id])->order('id desc')->select();
            foreach ($log_list as $key=>$item){
                $content = '';
                if($item['type']==0){
                    $name = D('Merchant_workers')->where(['merchant_worker_id'=>$item['worker_id']])->getField('name');
                    $content = C('config.appoint_worker_name').'：【'.$name.'】';
                }elseif($item['type']==1){
                    $mer_id = D('Appoint_order')->where(['order_id'=>$item['order_id']])->getField('mer_id');
                    $name = D('Merchant')->where(['mer_id'=>$mer_id])->getField('name');
                    $content = '商家：【'.$name.'】';
                }elseif($item['type']==2){
                    $name = D('Merchant_store_staff')->where(['id'=>$item['worker_id']])->getField('name');
                    $content = '店员：【'.$name.'】';
                }
                $content .= '修改订单价格（修改前：'.C('config.Currency_symbol').' '.$item['old_price'].'。修改后：'.C('config.Currency_symbol').' '.$item['now_price'].'）';
                $log_list[$key]['content'] = $content;
                $log_list[$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
                $log_list[$key]['note'] = $item['note']?'备注：'.$item['note']:'';
            }
        }
        $this->assign('log_list',$log_list);
        $this->display();
    }

    public function order_trace()
    {
        $order_trace = D('Appoint_order_records')->where(array('order_id' => $_GET['order_id']))->order('id DESC')->select();
        foreach ($order_trace as &$v) {
            $v['content'] = json_decode($v['content'], true);
        }
        $this->assign('order_trace', json_encode($order_trace, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        C('DEFAULT_THEME', '');
        $this->display('System@Appoint/order_trace');
    }

    /**
     * 选择预约商品列表
     */
    public function select_goods()
    {
        $id = I("id"); //当前预约id
        $this->assign("id", $id);
        $selected = trim(I("selected"), ',');
        $this->assign("selected", $selected);
        $select_goods_ids = $selected ? explode(',', $selected) : [];
        $keyword = $_GET['keyword'];
        $this->assign("keyword", $keyword);

        import('@.ORG.diypage');
        $condition = array();
        $database = D('Appoint');
        $condition['mer_id'] = $this->merchant_session['mer_id'];
        $condition['appoint_status'] = 0;
        $condition['end_time'] = ['gt', time()];
        if ($keyword != '') {
            $condition['appoint_name'] = array('like', '%' . $keyword . '%');
        }
        if($id){
            $condition['appoint_id'] = ['neq', $id];
        }
        $field = 'appoint_id as goods_id, appoint_name as name, appoint_status, is_appoint_price, payment_money, appoint_price';
        $count = $database->where($condition)->count();
        $p = new Page($count, 10);
        $goods_list = $database->field($field)->where($condition)->order('sort desc, appoint_id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($goods_list as &$value) {
            $value['select'] = 0;
            if (in_array($value['goods_id'], $select_goods_ids)) {
                $value['select'] = 1;
            }
        }

        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());

        $this->display();
    }

    /**
     * 获取已选择商品列表
     * @throws Exception
     */
    public function ajaxGetGoods()
    {
        $selected = trim(I("selected"), ',');
        $selected_goods_ids = explode(',', $selected);
        $database = D('Appoint');
        $condition['mer_id'] = $this->merchant_session['mer_id'];
        $condition['appoint_id'] = array('in', $selected_goods_ids);
        $field = 'appoint_id, appoint_name, appoint_status, is_appoint_price, payment_money, appoint_price';
        $goods_list = $database->field($field)->where($condition)->order('sort desc, appoint_id desc')->select();
        echo json_encode($goods_list);
        exit;
    }

    /**
     * 选择优惠券列表
     */
    public function select_mer_coupon()
    {
        $selected = trim(I("selected"), ',');
        $this->assign("selected", $selected);
        $keyword = I("keyword");
        $this->assign("keyword", $keyword);

        import('@.ORG.diypage');
        $database = D('Card_new_coupon');
        $condition = [
            'is_partition' => 0,
            'status' => 1,
            'start_time' => ['lt', time()],
            'end_time' => ['gt', time()],
            'mer_id' => $this->merchant_session['mer_id'],
        ];
        if ($keyword != '') {
            $condition['name'] = array('like', '%' . $keyword . '%');
        }
        $field = 'coupon_id as goods_id, name, status';
        $count = $database->where($condition)->count();
        $p = new Page($count, 10);
        $goods_list = $database->field($field)->where($condition)->order('coupon_id desc')->limit($p->firstRow . ',' . $p->listRows)->select();

        foreach ($goods_list as &$value) {
            $value['select'] = 0;
            if ($value['goods_id'] == $selected) {
                $value['select'] = 1;
            }
        }

        $this->assign("good_list", $goods_list);
        $this->assign("pagebar", $p->show());
        $this->display();
    }

    /**
     * 获取已选择商品列表
     * @throws Exception
     */
    public function ajaxGetCoupon()
    {
        $selected = trim(I("selected"), ',');
        $database = D('Card_new_coupon');
        $condition = [
            'is_partition' => 0,
            'status' => 1,
            'start_time' => ['lt', time()],
            'end_time' => ['gt', time()],
            'mer_id' => $this->merchant_session['mer_id'],
            'coupon_id' => $selected,
        ];
        $field = 'coupon_id, name, status';
        $goods_list = $database->field($field)->where($condition)->order('coupon_id desc')->select();
        echo json_encode($goods_list);
        exit;
    }
}
?>