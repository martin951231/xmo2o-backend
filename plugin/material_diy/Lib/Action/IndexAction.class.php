<?php

/**
 * Class IndexAction
 *
 * 投票插件接口
 */


class IndexAction extends BaseAction{
    public function index(){
		echo '插件文件已生成。<br/>目录所在位置：'.realpath(WEB_PATH.'plugin/'.PLUGIN_NAME);
    }

    /**
     * 后台跳转	系统后台、商家后台、社区后台
     */
	public function redirect(){
//		$returnArr = build_redirect_info();
        redirect(C('config.site_url') . '/packapp/material_diy/index.html');
	}

	/**
     * 获取后台配置
	 */
	public function template_config() {
        $d_plugin_material_diy_template = D('Plugin_material_diy_template');
        $template_config = array();
        $add_material_btn = $d_plugin_material_diy_template->add_material_btn;
        $template_config['add_material_btn'] = $add_material_btn;
        $template_tip = $d_plugin_material_diy_template->template_tip;
        $template_config['template_tip'] = $template_tip;
        $template_config['owner_identity_type'] = $d_plugin_material_diy_template->owner_identity_type;

        returnCode(0, $template_config);
    }

    /**
     * 添加模板
     */
    public function add_template() {
        // 字段过滤
        $template_title = I('template_title','','trim');
        if (empty($template_title)) {
            returnCode(1, [], '请填写模板标题！');
        }
        $template_img = I('template_img',''); // 背景图片  必填
        if (empty($template_img)) {
            returnCode(1, [], '请上传背景图片！');
        }
        // 模板信息
        $template_content = $_POST['template_content'];
        if (empty($template_content)) {
            returnCode(1, [], '请上传模板信息！');
        }
        $template_width = I('template_width',''); // 背景图片  前端展示固定宽度

        $now_time = time();

        // 关于来源处理
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        $from_type = $returnArr['from_type'];
        $from_name = $returnArr['from_name'];
        
        //开启事务
        $template_data = array(
            'template_title' => $template_title,
            'template_img' => $template_img,
            'template_content' => serialize($template_content),
            'template_width' => $template_width ? $template_width : '',
            'from' => $from,
            'from_id' => $from_id,
            'from_type' => $from_type,
            'from_name' => $from_name,
            'add_time' => $now_time,
        );
        $template_id = M('Plugin_material_diy_template')->data($template_data)->add();
        if (!$template_id) {
            fdump('==========模板添加失败================'.__LINE__, 'add_template', true);
            fdump($template_data, 'add_template', true);
            fdump(M('Plugin_material_diy_template'), 'add_template', true);
            returnCode(4, [], '模板添加失败！');
        }
        returnCode(0, array('template_id'=>$template_id));
    }

    /**
     * 模板详情
     */
    public function template_detail() {
        // 字段过滤
        $template_id = I('template_id');
        if (empty($template_id)) {
            returnCode(31, [], '请上传获取对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_material_diy_template = D('Plugin_material_diy_template');
        $where_template = array(
            'template_id' => $template_id
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->field('from, from_id')->find();
        if (empty($template_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($template_info['from']!='default' && ($from != $template_info['from'] || $from_id != $template_info['from_id'])) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $template_info=$d_plugin_material_diy_template->template_detail($template_id, false);

        //todo 办公时间读取
        $template_info['time_options']=D('Plugin_material_diy_template')->get_work_time($this->house_session['village_id']);
        returnCode(0, $template_info);
    }

    /**
     * 编辑模板
     */
    public function edit_template() {
        fdump('==========编辑模板================'.__LINE__, 'edit_template', true);
        fdump($_GET, 'edit_template', true);
        fdump($_POST, 'edit_template', true);
        // 字段过滤
        $template_id = I('template_id');
        if (empty($template_id)) {
            returnCode(11, [], '请上传修改对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_material_diy_template = M('Plugin_material_diy_template');
        $where_template = array(
            'template_id' => $template_id
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->find();
        if (empty($template_info)) {
            returnCode(11, [], '修改对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $template_info['from'] || $from_id != $template_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }

        // 字段过滤
        $template_title = I('template_title','','trim');
        if (empty($template_title)) {
            returnCode(1, [], '请填写模板标题！');
        }
        $template_img = I('template_img',''); // 背景图片  必填
        if (empty($template_img)) {
            returnCode(1, [], '请上传背景图片！');
        }
        // 模板信息
        $template_content = $_POST['template_content'];
        if (empty($template_content)) {
            returnCode(1, [], '请上传模板信息！');
        }
        $template_width = I('template_width',''); // 背景图片  前端展示固定宽度

        $now_time = time();


        // 处理修改参数
        $template_data = array(
            'template_id'=>$template_id
        );
        if ($template_title && $template_title!=$template_info['template_title']) {
            $template_data['template_title'] = $template_title;
        }
        if ($template_img && $template_img!=$template_info['template_img']) {
            $template_data['template_img'] = $template_img;
        }
        $template_content = serialize($template_content);
        if ($template_content && $template_content!=$template_info['template_content']) {
            $template_data['template_content'] = $template_content;
        }
        if ($template_width && $template_width!=$template_info['template_width']) {
            $template_data['template_width'] = $template_width;
        }
        $template_data['last_time'] = $now_time;

        $vote_set = $d_plugin_material_diy_template->data($template_data)->save();
        if (!$vote_set) {
            fdump('==========模板修改失败================'.__LINE__, 'edit_template', true);
            fdump($template_data, 'edit_template', true);
            fdump($d_plugin_material_diy_template, 'edit_template', true);
            returnCode(14, [], '模板修改失败！');
        }
        returnCode(0, array('template_id'=>$template_id));
    }

    /**
     * Notes: 一键复制模板
     * @author: wanzy
     * @date_time: 2020/9/16 17:07
     */
    public function copy_template() {
        // 字段过滤
        $template_id = I('template_id');
        if (!empty($template_id)) {
            // 对应获取投票信息，查看是否存在
            $d_plugin_material_diy_template = M('Plugin_material_diy_template');
            $where_template = array(
                'template_id' => $template_id
            );
            $template_info = $d_plugin_material_diy_template->where($where_template)->find();
            if (empty($template_info)) {
                returnCode(11, [], '修改对象不存在！');
            }
        } else {
            $post_from = I('from');
            if ('default'==$post_from) {
                $template_info = D('Plugin_material_diy_template')->get_default_template_info();
            } else {
                returnCode(11, [], '复制对象不存在！');
            }
        }
        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        $from_type = $returnArr['from_type'];
        $from_name = $returnArr['from_name'];
        // 暂定只能默认模板可以复制
        if ('default' != $template_info['from']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $now_time = time();
        // 关于来源处理
        //开启事务
        $template_data = array(
            'template_title' => $template_info['template_title'],
            'template_img' => $template_info['template_img'],
            'template_content' => $template_info['template_content'],
            'template_width' => $template_info['template_width'] ? $template_info['template_width'] : '',
            'from' => $from,
            'from_id' => $from_id,
            'from_type' => $from_type,
            'from_name' => $from_name,
            'add_time' => $now_time
        );
        $template_id = M('Plugin_material_diy_template')->data($template_data)->add();
        if (!$template_id) {
            fdump('==========模板复制失败================'.__LINE__, 'add_template', true);
            fdump($template_data, 'add_template', true);
            fdump(M('Plugin_material_diy_template'), 'add_template', true);
            returnCode(4, [], '模板添加失败！');
        }
        returnCode(0, array('template_id'=>$template_id));
    }

    /**
     * Notes: 填写标题文本
     * @author: wanzy
     * @date_time: 2020/9/16 17:08
     */
    public function write_title_template() {
        // 字段过滤
        $template_id = I('template_id');
        if (empty($template_id)) {
            returnCode(11, [], '请上传修改对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_material_diy_template = M('Plugin_material_diy_template');
        $where_template = array(
            'template_id' => $template_id
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->find();
        if (empty($template_info)) {
            returnCode(11, [], '修改对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $template_info['from'] || $from_id != $template_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }

        // 模板信息
        $template_content = $_POST['template_content'];
        if (empty($template_content)) {
            returnCode(1, [], '请上传模板信息！');
        }
        // 处理修改参数
        $template_data = array(
            'template_id'=>$template_id
        );
        $template_content = serialize($template_content);
        if ($template_content && $template_content!=$template_info['template_content']) {
            $template_data['template_content'] = $template_content;
        }

        $now_time = time();
        $template_data['last_time'] = $now_time;

        $vote_set = $d_plugin_material_diy_template->data($template_data)->save();
        if (!$vote_set) {
            fdump('==========填写标题文本修改失败================'.__LINE__, 'edit_template', true);
            fdump($template_data, 'edit_template', true);
            fdump($d_plugin_material_diy_template, 'edit_template', true);
            returnCode(14, [], '模板填写标题文本失败！');
        }
        returnCode(0, array('template_id'=>$template_id));
    }

    /**
     * 删除模板
     */
    public function del_template() {
        // 字段过滤
        $template_id = I('template_id');
        if (empty($template_id)) {
            returnCode(21, [], '请上传删除对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_material_diy_template = M('Plugin_material_diy_template');
        $where_template = array(
            'template_id' => $template_id
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->find();
        if (empty($template_info)) {
            returnCode(21, [], '删除对象不存在！');
        }
        if (4==$template_info['template_status']) {
            returnCode(21, [], '当前对象已经删除！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $template_info['from'] || $from_id != $template_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $data = array(
            'template_status' => 4,
            'last_time' =>time()
        );
        $del_vote = $d_plugin_material_diy_template->where($where_template)->data($data)->save();
        if (!$del_vote) {
            fdump('==========删除模板失败================'.__LINE__, 'del_template', true);
            fdump($data, 'del_template', true);
            fdump($d_plugin_material_diy_template, 'del_template', true);
            returnCode(22, [], '删除失败！');
        }

        returnCode(0, array('template_id'=>$template_id));
    }

    /**
     * 获取模板列表
     */
    public function template_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','10','intval');

        // 条件搜索
        $where = array();
        $search_type = I('search_type','','trim');
        $search_val = I('search_val','','trim');
        // 关于来源处理
        $returnArr = build_redirect_info();
        $where['_string'] = "(`from`='{$returnArr['from']}' AND `from_id`={$returnArr['from_id']})";
        // id 精确查询
        if ($search_type && 'template_id'==$search_type) {
            $template_id = intval($search_val);
        }
        if ($template_id>0) {
//            $where['template_id'] = $template_id;
            $where['_string'] .= " AND `template_id`={$template_id}";
        }
        // 标题模糊查询
        if ($search_type && 'template_title'==$search_type) {
            $template_title = $search_val;
        }
        if ($template_title) {
//            $where['template_title'] = array('like','%'.$template_title.'%');
            $where['_string'] .= " AND `template_title` LIKE '%{$template_title}%'";
        }
        // 时间范围查询  2019/9/26    2019/10/5
        $start_time = I('start_time','');
        $end_time = I('end_time','');
        if(!empty($start_time)&&!empty($end_time)){
            $period = array(strtotime($start_time." 00:00:00"),strtotime($end_time." 23:59:59"));
            if ($period[0]>$period[1]) {
                returnCode(41, [], '结束时间应大于开始时间！');
            }
            $time_condition = " AND (add_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string'] .= $time_condition;
        }
        $list_info = D('Plugin_material_diy_template')->template_list($where, $page, $page_size, 'sort DESC, add_time DESC');
        returnCode(0, $list_info);
    }

    /**
     * 图片上传 支持单张 多张
     * 上传图片参数  imgFile
     * 单张图  图片返回 url 直接为图片路径   多张图片上传 url 返回时图片路径数组
     */
    public function ajax_upload_pic(){
        if($_FILES['file']['error'] != 4){
            // 关于来源处理
            $returnArr = build_redirect_info();
            $from = $returnArr['from'];
            $from_id = $returnArr['from_id'];
            if($_GET['type']){
                $type = $_GET['type'];
            }else{
                $type = $from;
            }
            $image = PD('Image')->handle($from_id, $type, 0, array('size' => 3), false);
            fdump($image,'$image');
            if (!$image['error']) {
                $msg = array('error' => 0,'url' => $image['url']['file'], 'title' => $image['title']['file'], 'file_url' => replace_file_domain($image['url']['file']));
                returnCode(0, $msg);
            }
            returnCode(0, $image);
        }else{
            returnCode(5, [], '没有选择图片！');
        }
    }

    /**
     * 填写模板时候获取的详情
     */
    public function template_write_detail() {
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(31, [], '请上传获取对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_material_diy_template = D('Plugin_material_diy_template');
        $where_diy = array(
            'id' => $diy_id
        );
        $diy_value_info = M('Plugin_material_diy_value')->where($where_diy)->field('from, from_id')->find();
        if (empty($diy_value_info)) {
            returnCode(31, [], '获取对象不存在！');
        }
        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $diy_value_info['from'] || $from_id != $diy_value_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $diy_value_info=$d_plugin_material_diy_template->template_write_detail($diy_id, true);

        $where_bind = [
            'b.diy_id' => $diy_id
        ];
        $bind_deposit_info = $d_plugin_material_diy_template->material_diy_bind_deposit_list($where_bind, true);
        if (!$bind_deposit_info['list']) {
            $bind_deposit_info['list'] = [];
        }
        $diy_value_info['bind_deposit_info'] = $bind_deposit_info['list'];

        //todo 办公时间读取
        $diy_value_info['time_options']=D('Plugin_material_diy_template')->get_work_time($this->house_session['village_id']);

        returnCode(0, $diy_value_info);
    }

    public function choose_msg() {
        // 字段过滤
        $type = I('type');
        if (empty($type)) {
            returnCode(31, [], '请上传获取对象！');
        }
        $page = I('page', '1', 'intval');
        $page_size = I('page_size', '8', 'intval');
        $search_val = I('search_val', '', 'trim');
        // 暂定 floor_choose 单元选择框 owner_choose 业主姓名选择框 owner_phone 业主联系方式框


        $limit = (($page-1)*$page_size).','.$page_size;
        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($type=='floor_choose') {
            $where = array();
            $where['village_id'] = $from_id;
            $where['status'] = 1;
            if ($search_val) {
                $where['_string'] = "(floor_name LIKE '%".$search_val."%' OR floor_layer LIKE '%".$search_val."%')";
            }
            $database_house_village_floor = M('House_village_floor');
            $count = $database_house_village_floor->where($where)->count();
            $total_page = ceil($count/$page_size);
            $order = 'sort desc, floor_id desc';
            //2020/9/8 start
            //$list = $database_house_village_floor->where($where)->limit($limit)->order($order)->field('floor_id, floor_name, floor_layer')->select();
            $l_where['f.village_id'] = $from_id;
            $l_where['f.status'] = 1;
            if ($search_val) {
                $l_where['f._string'] = "(floor_name LIKE '%".$search_val."%' OR floor_layer LIKE '%".$search_val."%')";
            }
            $orders = 'f.sort desc, f.floor_id desc';
            $list = $database_house_village_floor->join("as f LEFT JOIN ".C('DB_PREFIX')."house_village_single as s ON f.single_id=s.id")
                ->where($l_where)->field('f.floor_id, f.floor_name, f.floor_layer,s.single_name')->limit($limit)->order($orders)->select();
            //2020/9/8 end
            if (!empty($list)) {
                foreach ($list as &$item) {
                    if($item['floor_layer']) {
                        $item['choose_info'] = $item['floor_layer'] . '#' . $item['floor_name'];
                    }else{
                        $item['choose_info'] = $item['single_name'].'#'.$item['floor_name'];
                    }
//                    $item['msg'] = $item['floor_name'];
                    $item['msg'] = $item['choose_info'];
                }
            } else {
                $list = array();
            }
        }
        elseif ($type=='owner_choose' || $type=='owner_phone') {
            $where = array();
            $where['ub.village_id'] = $from_id;
            $where['ub.status'] = 1;
            $where['vf.status'] = 1;
            $where['ub.type'] = array('in', array(0,3));
            if ($type=='owner_choose' && $search_val) {
                $where['ub.name'] = array('like','%'.$search_val.'%');
            } elseif ($type=='owner_phone' && $search_val) {
                $where['ub.phone'] = array('like','%'.htmlentities($search_val).'%');
            }
            $database_house_village_user_bind = M('House_village_user_bind');
            $count = $database_house_village_user_bind->join('as ub LEFT JOIN '.C('DB_PREFIX').'house_village_floor as vf ON ub.floor_id=vf.floor_id')->where($where)->count();
            $total_page = ceil($count/$page_size);
            $order = 'ub.pigcms_id desc';
            $list = $database_house_village_user_bind->join('as ub LEFT JOIN '.C('DB_PREFIX').'house_village_floor as vf ON ub.floor_id=vf.floor_id')
                ->where($where)->limit($limit)->order($order)->field('ub.pigcms_id, ub.name, ub.phone, ub.floor_id, vf.floor_name, vf.floor_layer,ub.single_id,ub.floor_id,ub.layer_id,ub.vacancy_id')->select();

            if (!empty($list)) {
                foreach ($list as &$items) {
                    //2020/9/4 优化 start
                    $address = D('House_village')->get_single_floor_room($items['single_id'],$items['floor_id'],$items['layer_id'],$items['vacancy_id'],$from_id);
                    //2020/9/4 优化 end
                    if ($type == 'owner_choose') {
                        //2020/9/4 优化 start
//                        $items['choose_info'] = $items['name'] . '-' . $items['floor_layer'] . '#' . $items['floor_name'];
                        $items['choose_info'] = $items['name'] . '-' . $address;
                        //2020/9/4 优化 end
                        $items['msg'] = $items['name'];
                    } elseif ($type == 'owner_phone') {
                        //2020/9/4 优化 start
//                        $items['choose_info'] = $items['phone'] . '-' . $items['floor_layer'] . '#' . $items['floor_name'];
                        $items['choose_info'] = $items['phone'] . '-' . $address;
                        //2020/9/4 优化 end
                        $items['msg'] = $items['phone'];
                    }
                }
            } else {
                $list = array();
            }
        }elseif($type=='room_choose'){
            $d_plugin_material_diy_template = D('Plugin_material_diy_template');
            $list=$d_plugin_material_diy_template->get_room_types();
            $count=count($list);
            $total_page=ceil($count/$page_size);
        }
        else {
            returnCode(31, [], '上传信息有误！');
        }
        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        returnCode(0, $data);
    }

    /**添加填写内容*/
    public function add_diy_value() {
        // 字段过滤
        $template_id = I('template_id');
        if (empty($template_id)) {
            returnCode(11, [], '请上传填写对象！');
        }
        // 字段过滤
        $template_title = I('template_title','','trim');
        if (empty($template_title)) {
            returnCode(1, [], '请填写模板标题！');
        }
        $template_img = I('template_img',''); // 背景图片  必填
        if (empty($template_img)) {
            returnCode(1, [], '请上传背景图片！');
        }
        // 模板信息
        $diy_value = $_POST['diy_value'];
        if (empty($diy_value)) {
            returnCode(1, [], '请上传填写信息！');
        }
        $record_id = I('record_id',0,'intval');
        //todo 校验办公时间
        D('Plugin_material_diy_template')->check_work_time($this->house_session['village_id'],$diy_value);
        $template_width = I('template_width',''); // 背景图片  前端展示固定宽度
        $copy_id = I('copy_id','0', 'intval'); //复制来源id

        $now_time = time();

        // 关于来源处理
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        $from_type = $returnArr['from_type'];
        $from_name = $returnArr['from_name'];
        if ('house'==$from) {
            $account = $this->house_session['account'];
        } else {
            $account = '';
        }

        //开启事务
        $diy_value_data = array(
            'title' => $template_title,
            'template_img' => $template_img,
            'diy_value' => serialize($diy_value),
            'template_width' => $template_width ? $template_width : '',
            'from' => $from,
            'from_id' => $from_id,
            'from_type' => $from_type,
            'from_name' => $from_name,
            'add_time' => $now_time,
            'copy_id' => $copy_id,
            'template_id' => $template_id,
            'account' => $account,
            'diy_tatus' => 1
        );
        $diy_id = M('Plugin_material_diy_value')->data($diy_value_data)->add();
        if (!$diy_id) {
            fdump('==========添加填写内容失败================'.__LINE__, 'add_diy_value', true);
            fdump($diy_value_data, 'add_diy_value', true);
            fdump(M('Plugin_material_diy_value'), 'add_diy_value', true);
            returnCode(4, [], '模板添加失败！');
        }
        $diy_value_data['value_id']=$diy_id;
        $diy_value_data['record_id']=$record_id;
        D('Plugin_material_diy_template')->contract_relation_rental_record($diy_value_data);
        returnCode(0, array('diy_id'=>$diy_id));
    }

    /**
     * 编辑填写内容
     */
    public function edit_diy_value() {
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(11, [], '请上传修改对象！');
        }
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $where_diy_value = array(
            'id' => $diy_id
        );
        $info = $d_plugin_material_diy_value->where($where_diy_value)->find();
        if (empty($info)) {
            returnCode(11, [], '修改对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $info['from'] || $from_id != $info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        // 模板信息
        $diy_value = $_POST['diy_value'];
        if (empty($diy_value)) {
            returnCode(11, [], '请上传填写信息！');
        }
        //todo 校验办公时间
        D('Plugin_material_diy_template')->check_work_time($this->house_session['village_id'],$diy_value);
        $now_time = time();
        // 处理修改参数
        $data = array(
            'id'=>$diy_id
        );
        $diy_value = serialize($diy_value);
        if ($diy_value && $diy_value!=$info['diy_value']) {
            $data['diy_value'] = $diy_value;
        }
        $data['last_time'] = $now_time;
        $diy_tatus = I('diy_tatus', 0, 'intval');
        $diy_reason = I('diy_reason', '', 'trim');
        $status=0;
        if ($info['diy_tatus']==0 && in_array($diy_tatus, [1,3])) {
            $data['diy_tatus'] = $diy_tatus;
            if (3==$data['diy_tatus'] && !$diy_reason) {
                returnCode(11, [], '审核拒绝请填写理由！');
            } elseif(3==$data['diy_tatus']) {
                $data['diy_reason'] = $diy_reason;
            }
            $status=$diy_tatus;
        }

        $vote_set = $d_plugin_material_diy_value->data($data)->save();
        if (!$vote_set) {
            fdump('==========修改失败================'.__LINE__, 'edit_diy_value', true);
            fdump($d_plugin_material_diy_value, 'edit_diy_value', true);
            fdump($data, 'edit_diy_value', true);
            returnCode(14, [], '模板修改失败！');
        } else {
            if($status){
                $templat_info=D('Plugin_material_diy_template')->where(['template_id'=>$info['template_id']])->field('template_type')->find();
                if($templat_info && (int)$templat_info['template_type'] == 1){
                    //审核同步公租房
                    $param=[
                        'village_id'=>$info['from_id'],
                        'template_id'=>$info['template_id'],
                        'value_id'=>$info['id'],
                        'diy_tatus'=>$status
                    ];
                    D('Plugin_material_diy_template')->syn_apply_record($param);
                }
            }else{
            if ($info['uid'] > 0) {
                $toUser = M('User')->where(['uid' => $info['uid']])->field('uid,openid,wxapp_openid,nickname')->find();
                $openid = $toUser['openid'];
                if ($openid) {
                    $base_url = D('House_village')->base_url;
//                    $href = $this->config['site_url'].$base_url."pages/village/materialDiy/materialList?template_id={$info['template_id']}&from={$info['from']}&from_id={$info['from_id']}";

//                    $href=$this->config['site_url'] .str_replace('plugin.php', 'wap.php', U('Wap/Renovation/user_know', ['template_id'=>$info['template_id']]));
                    $href = $this->config['site_url'].$base_url."pages/village/materialDiy/materialDetails?diy_id={$diy_id}";
                    $param=[];
                    $template_title=M('Plugin_material_diy_template')->where(['template_id'=>$info['template_id']])->getField('template_title');
                    if (3==$data['diy_tatus']) {
                        $keyword1 = $info['title'];
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM400166399',
                            array(
                                'href' => $href,
                                'wecha_id' => $openid,
                                'first' => '审核结果',
                                'keyword1' => $keyword1,
                                'keyword2' => '您的申请未通过。原因：'.$data['diy_reason'],
                                'keyword3' => date('Y年m月d日 H:i'),
                                'remark' => '点击查看详情！'
                            )
                        );
                        $param=[
                            'type'=>2,
                            'title'=>'您已预约失败【'.$template_title.'】',
                            'content'=>'时间：'.date('Y-m-d H:i:s',time()),
                            'status'=>2,
                            'add_time'=>time()
                        ];
                    } elseif(1==$data['diy_tatus']) {
                        $keyword1 = $info['title'];
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('OPENTM400166399',
                            array(
                                'href' => $href,
                                'wecha_id' => $openid,
                                'first' => '审核结果',
                                'keyword1' => $keyword1,
                                'keyword2' => '您的申请已通过',
                                'keyword3' => date('Y年m月d日 H:i'),
                                'remark' => '点击查看详情！'
                            )
                        );
                        $param=[
                            'type'=>2,
                            'title'=>'您已预约成功【'.$template_title.'】',
                            'content'=>'时间：'.date('Y-m-d H:i:s',time()),
                            'status'=>1,
                            'add_time'=>time()
                        ];
                    }
                    if(!empty($param) && intval($info['pigcms_id']) > 0){
                        $param['pigcms_id']=$info['pigcms_id'];
                        $param['uid']=$info['uid'];
                        M('House_venue_news_record')->data($param)->add();
                    }
                }
            }
            }
        }
        returnCode(0, array('diy_id'=>$diy_id));
    }

    /**
     * 获取模板填写列表
     */
    public function write_template_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','10','intval');

        // 条件搜索
        $where = array();
        $search_type = I('search_type','','trim');
        $search_val = I('search_val','','trim');
        // id 精确查询
        if ($search_type && 'id'==$search_type) {
            $diy_id = intval($search_val);
        }
        if ($diy_id>0) {
            $where['id'] = $diy_id;
        }
        // 投票标题模糊查询
        if ($search_type && 'title'==$search_type) {
            $template_title = $search_val;
        }
        if ($template_title) {
            $where['title'] = array('like','%'.$template_title.'%');
        }
        $template_id = I('template_id','','intval');
        $pigcms_id = I('pigcms_id','','intval');
        if ($template_id) {
            $where['template_id'] = $template_id;
        }
        if ($pigcms_id) {
            $where['pigcms_id'] = $pigcms_id;
        }

        // 时间范围查询
        $start_time = I('start_time','');
        $end_time = I('end_time','');
        if(!empty($start_time)&&!empty($end_time)){
            $period = array(strtotime($start_time." 00:00:00"),strtotime($end_time." 23:59:59"));
            if ($period[0]>$period[1]) {
                returnCode(41, [], '结束时间应大于开始时间！');
            }
            $time_condition = " (add_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string'] = $time_condition;
        }

        // 关于来源处理
        $returnArr = build_redirect_info();
        $where['from'] = $returnArr['from'];
        $where['from_id'] = $returnArr['from_id'];
        $list_info = D('Plugin_material_diy_template')->write_template_list($where, $page, $page_size, 'add_time DESC');
        returnCode(0, $list_info);
    }

    /**
     * 删除模板填写
     */
    public function del_diy_value() {
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(11, [], '请上传修改对象！');
        }
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $where_diy_value = array(
            'id' => $diy_id
        );
        $info = $d_plugin_material_diy_value->where($where_diy_value)->find();
        if (empty($info)) {
            returnCode(11, [], '修改对象不存在！');
        }
        if (4==$info['diy_tatus']) {
            returnCode(21, [], '当前对象已经删除！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $info['from'] || $from_id != $info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $data = array(
            'diy_tatus' => 4,
            'last_time' =>time()
        );
        $del_vote = $d_plugin_material_diy_value->where($where_diy_value)->data($data)->save();
        if (!$del_vote) {
            fdump('==========删除模板失败================'.__LINE__, 'del_template', true);
            fdump($data, 'del_template', true);
            fdump($d_plugin_material_diy_value, 'del_template', true);
            returnCode(22, [], '删除失败！');
        }

        returnCode(0, array('diy_id'=>$diy_id));
    }

    /**上传文件*/
    public function ajax_upload_file() {
        fdump($_FILES,'$_file');
        fdump($_POST,'$_POST');
        fdump($_GET,'$_GET');
        if($_FILES['file']['error'] != 4){
            // 关于来源处理
            $returnArr = build_redirect_info();
            $from = $returnArr['from'];
            $from_id = $returnArr['from_id'];
            if($_GET['type']){
                $type = $_GET['type'];
            }else{
                $type = $from;
            }
            $from_type = $returnArr['from_type'];
            $from_name = $returnArr['from_name'];
            $other = array(
                'from' => $from,
                'from_id' => $from_id,
                'from_type' => $from_type,
                'from_name' => $from_name,
            );
            $d_plugin_material_diy_template = D('Plugin_material_diy_template');
            $file = $d_plugin_material_diy_template->file_handle($from_id, $type, array('size' => 50), true, $other);
            fdump($file, '$file');
            if (!$file['error']) {
                returnCode(0, $file);
            }
            returnCode(0, $file);
        }else{
            returnCode(5, [], '没有选择图片！');
        }
    }

    /**关联上填写内容和文件*/
    public function write_and_file() {
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(31, [], '请上传获取对象！');
        }
        $file_id = I('file_id');
        if (empty($file_id)) {
            returnCode(31, [], '请上传获取对象！');
        }
        $d_plugin_material_diy_file = M('Plugin_material_diy_file');
        $file_info = $d_plugin_material_diy_file->where(array('file_id' => $file_id, 'file_status' => 0))->find();
        if (empty($file_info)) {
            returnCode(31, [], '获取对象不存在！');
        }
        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $file_info['from'] || $from_id != $file_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $data['diy_id'] = $diy_id;
        $data['file_id'] = $file_id;
        $data['last_time'] = time();

        $set = $d_plugin_material_diy_file->data($data)->save();
        if (!$set) {
            fdump('==========修改失败================'.__LINE__, 'edit_diy_value', true);
            fdump($d_plugin_material_diy_file, 'edit_diy_value', true);
            fdump($data, 'edit_diy_value', true);
            returnCode(14, [], '模板修改失败！');
        }
        returnCode(0, array('file_id'=>$file_id));

    }

    /**
     * 获取模板填写附件列表
     */
    public function write_file_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','10','intval');

        // 条件搜索
        $where = array();
        $search_type = I('search_type','','trim');
        $search_val = I('search_val','','trim');
        // id 精确查询
        if ($search_type && 'file_id'==$search_type) {
            $file_id = intval($search_val);
        }
        if ($file_id>0) {
            $where['file_id'] = $file_id;
        }
        // 投票标题模糊查询
        if ($search_type && 'file_name'==$search_type) {
            $file_remark = $search_val;
        }
        if ($file_remark) {
            $where['file_remark'] = array('like','%'.$file_remark.'%');
        }
        $diy_id = I('diy_id','','intval');
        if ($diy_id) {
            $where['diy_id'] = $diy_id;
        }

        // 时间范围查询
        $start_time = I('start_time','');
        $end_time = I('end_time','');
        if(!empty($start_time)&&!empty($end_time)){
            $period = array(strtotime($start_time." 00:00:00"),strtotime($end_time." 23:59:59"));
            if ($period[0]>$period[1]) {
                returnCode(41, [], '结束时间应大于开始时间！');
            }
            $time_condition = " (add_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string'] = $time_condition;
        }

        // 关于来源处理
        $returnArr = build_redirect_info();
        $where['from'] = $returnArr['from'];
        $where['from_id'] = $returnArr['from_id'];
        $list_info = D('Plugin_material_diy_template')->write_file_list($where, $page, $page_size, 'add_time DESC');
        returnCode(0, $list_info);
    }

    /**
     * 删除关联附件
     */
    public function del_write_file() {
        // 字段过滤
        $file_id = I('file_id');
        if (empty($file_id)) {
            returnCode(11, [], '请上传修改对象！');
        }
        $d_plugin_material_diy_file = M('Plugin_material_diy_file');
        $file_info = $d_plugin_material_diy_file->where(array('file_id' => $file_id, 'file_status' => 0))->find();
        if (empty($file_info)) {
            returnCode(31, [], '获取对象不存在！');
        }
        if (4==$file_info['file_status']) {
            returnCode(21, [], '当前对象已经删除！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $file_info['from'] || $from_id != $file_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $data = array(
            'file_status' => 4,
            'last_time' =>time()
        );
        $del_vote = $d_plugin_material_diy_file->where(array('file_id' => $file_id, 'file_status' => 0))->data($data)->save();
        if (!$del_vote) {
            fdump('==========删除模板失败================'.__LINE__, 'del_template', true);
            fdump($data, 'del_template', true);
            fdump($d_plugin_material_diy_file, 'del_template', true);
            returnCode(22, [], '删除失败！');
        }

        returnCode(0, array('file_id'=>$file_id));
    }

    /**
     * Notes:获取对应模板填写内容的备注列表
     * @author: wanzy
     * @date_time: 2020/9/18 11:01
     */
    public function write_remark_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','10','intval');
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(11, [], '没有备注对象！');
        }
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $where_diy_value = array(
            'id' => $diy_id
        );
        $info = $d_plugin_material_diy_value->where($where_diy_value)->find();
        if (empty($info)) {
            returnCode(11, [], '备注对象不存在！');
        }
        $where = [
            'diy_id' => $diy_id
        ];
        // 关于来源处理
        build_redirect_info();
        $list_info = D('Plugin_material_diy_template')->write_remark_list($where, $page, $page_size, 'add_time DESC');
        returnCode(0, $list_info);
    }

    /**
     * Notes: 对应模板填写内容添加备注
     * @author: wanzy
     * @date_time: 2020/9/18 11:10
     */
    public function add_remark() {
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(11, [], '没有备注对象！');
        }
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $where_diy_value = array(
            'id' => $diy_id
        );
        $info = $d_plugin_material_diy_value->where($where_diy_value)->find();
        if (empty($info)) {
            returnCode(11, [], '备注对象不存在！');
        }
        $remark = I('remark','','trim');
        if (empty($remark)) {
            returnCode(11, [], '请填写备注！');
        }

        // 关于来源处理
        $returnArr = build_redirect_info();
        // 字段过滤
        $remark_id = I('remark_id');
        $now_time = time();
        if ($remark_id) {
            $remark_info = M('Plugin_material_diy_remark')->where(['remark_id' => $remark_id])->find();
            if ($remark_info['remark']==$remark) {
                returnCode(11, [], '您没做任何改动！');
            }
            $diy_value_data = [
                'remark' => $remark,
                'add_time' => $now_time
            ];
            $remark_set = M('Plugin_material_diy_remark')->where(['remark_id' => $remark_id])->save($diy_value_data);
        } else {
            $from = $returnArr['from'];
            if ('house'==$from) {
                $account = $this->house_session['account'] ? $this->house_session['account'] : $this->house_session['village_name'];
            } else {
                $account = '';
            }
            //开启事务
            $diy_value_data = array(
                'template_id' => $info['template_id'],
                'diy_id' => $diy_id,
                'remark' => $remark,
                'from' => $from,
                'add_time' => $now_time,
                'account' => $account,
            );
            $remark_id = M('Plugin_material_diy_remark')->data($diy_value_data)->add();
            $remark_set = $remark_id;
        }
        if (!$remark_set) {
            returnCode(4, [], '备注添加失败！');
        }
        returnCode(0, array('remark_id'=>$remark_id));
    }

    /**
     * Notes: 删除备注
     * @author: wanzy
     * @date_time: 2020/9/21 17:30
     */
    public function del_remark() {
        // 字段过滤
        $diy_id = I('diy_id');
        if (empty($diy_id)) {
            returnCode(11, [], '没有删除对象！');
        }
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $where_diy_value = array(
            'id' => $diy_id
        );
        $info = $d_plugin_material_diy_value->where($where_diy_value)->find();
        if (empty($info)) {
            returnCode(11, [], '删除对象不存在！');
        }

        // 关于来源处理
        $returnArr = build_redirect_info();
        // 字段过滤
        $remark_id = I('remark_id');
        $remark_info = M('Plugin_material_diy_remark')->where(['remark_id' => $remark_id])->find();
        if (empty($remark_info)) {
            returnCode(11, [], '删除对象不存在！');
        }
        $remark_del = M('Plugin_material_diy_remark')->where(['remark_id' => $remark_id])->delete();

        if (!$remark_del) {
            returnCode(4, [], '备注删除失败！');
        }
        returnCode(0, array('remark_id'=>$remark_id));
    }

    /**
     * Notes: 获取对应押金列表
     * @author: wanzy
     * @date_time: 2020/9/18 13:27
     */
    public function material_diy_deposit_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','6','intval');

        // 条件搜索
        $where = array();
        $search_type = I('search_type','','trim');
        $search_val = I('search_val','','trim');
        // 业主姓名 模糊查询
        if ($search_type && 'name'==$search_type) {
            $name = trim($search_val);
        }
        if ($name) {
            $where['name'] = array('like','%'.$name.'%');;
        }
        // 业主手机号 模糊查询
        if ($search_type && 'phone'==$search_type) {
            $phone = $search_val;
        }
        if ($phone) {
            $where['phone'] = array('like','%'.$phone.'%');
        }
        // 业主房间号 模糊查询
        if ($search_type && 'room_addrss'==$search_type) {
            $room_addrss = $search_val;
        }
        if ($room_addrss) {
            $where['room_addrss'] = array('like','%'.$room_addrss.'%');
        }
        // 关于来源处理
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ('house'==$from) {
            $where['a.village_id'] = $from_id;
        }
        $where['a.is_del'] = 1;
        $where['_string'] = "b.bind_id IS NULL";

        $list_info = D('Plugin_material_diy_template')->material_diy_deposit_list($where, $page, $page_size);
        returnCode(0, $list_info);
    }

    /**
     * Notes: 绑定押金
     * @author: wanzy
     * @date_time: 2020/9/18 13:52
     */
    public function add_bind_material_diy_deposit() {
        $diy_id = I('diy_id');
        $deposit_id = I('deposit_id');
        if (empty($diy_id) || empty($deposit_id)) {
            returnCode(11, [], '没有绑定对象！');
        }
        // 关于来源处理
        $returnArr = build_redirect_info();
        // 对应信息，查看是否存在
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $where_diy_value = array(
            'id' => $diy_id
        );
        $info = $d_plugin_material_diy_value->where($where_diy_value)->find();
        if (empty($info)) {
            returnCode(11, [], '绑定对象不存在！');
        }

        $d_house_village_deposit = M('House_village_deposit');
        $where_deposit = array(
            'deposit_id' => $deposit_id
        );
        $deposit_info = $d_house_village_deposit->where($where_deposit)->find();
        if (empty($deposit_info)) {
            returnCode(11, [], '绑定的押金不存在或者已经被删除！');
        }
        if ('house'==$info['from'] && $info['from_id']!=$deposit_info['village_id']) {
            returnCode(11, [], '不能绑定非本小区的押金！');
        }
        $where_bind = [
            'deposit_id' => $deposit_id,
        ];
        $d_plugin_material_diy_deposit = M('Plugin_material_diy_deposit');
        $bind_info = $d_plugin_material_diy_deposit->where($where_bind)->find();
        if ($bind_info) {
            returnCode(11, [], '该押金已经被绑定！');
        }
        $from = $returnArr['from'];
        if ('house'==$from) {
            $account = $this->house_session['account'];
        } else {
            $account = '';
        }
        $bind_data = [
            'template_id' => $info['template_id'],
            'diy_id' => $diy_id,
            'deposit_id' => $deposit_id,
            'account' => $account,
            'add_time' => time(),
        ];
        $bind_id = $d_plugin_material_diy_deposit->data($bind_data)->add();
        if (!$bind_id) {
            returnCode(4, [], '绑定押金失败！');
        }
        returnCode(0, array('bind_id'=>$bind_id));
    }

    /**
     * Notes: 解除绑定
     * @author: wanzy
     * @date_time: 2020/9/18 14:44
     */
    public function del_bind_material_diy_deposit() {
        $diy_id = I('diy_id');
        $deposit_id = I('deposit_id');
        $bind_id = I('bind_id');
        if ((empty($diy_id) || empty($deposit_id)) && !$bind_id) {
            returnCode(11, [], '没有绑定对象！');
        }
        if ($bind_id) {
            $where_bind = [
                'bind_id' => $bind_id,
            ];
        } else {
            $where_bind = [
                'diy_id' => $diy_id,
                'deposit_id' => $deposit_id,
            ];
        }
        $d_plugin_material_diy_deposit = M('Plugin_material_diy_deposit');
        $bind_info = $d_plugin_material_diy_deposit->where($where_bind)->find();
        if (!$bind_info) {
            returnCode(11, [], '当前绑定不存在或者已经被删除！');
        }
        $bind_id = $d_plugin_material_diy_deposit->where($where_bind)->delete();
        if (!$bind_id) {
            returnCode(4, [], '解除绑定失败！');
        }
        returnCode(0, array('bind_id'=>$bind_info['bind_id']));
    }


    public function get_diy_template($template_id,$field=true){
        $d_plugin_material_diy_template = D('Plugin_material_diy_template');
        $where_template = array(
            'template_id' => intval($template_id)
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->field($field)->find();
        return $template_info;
    }

    //todo 获取模板提示数据
    public function template_tips() {
        // 字段过滤
        $template_id = I('template_id');
        if (empty($template_id)) {
            returnCode(11, [], '请上传获取对象！');
        }
        // 对应获取投票信息，查看是否存在
        $template_info=self::get_diy_template($template_id,'template_id,tips_title as title,tips_content as content,tips_status as status');
        if (empty($template_info)) {
            returnCode(11, [], '获取对象不存在！');
        }
        returnCode(0, $template_info);
    }

    //todo 提交模板提示数据
    public function template_tips_sub(){
        if(!isset($_POST['status'])){
            returnCode(11, [], '请选择状态！');
        }
        if(!isset($_POST['template_id']) || empty($_POST['template_id'])){
            returnCode(11, [], '缺少必要参数！');
        }
        if(!isset($_POST['title']) || empty($_POST['title'])){
            returnCode(11, [], '请输入提示标题！');
        }
        if(!isset($_POST['content']) || empty($_POST['content'])){
            returnCode(11, [], '请输入提示内容！');
        }
        $template_info=self::get_diy_template($_POST['template_id'],'template_id,tips_title as title,tips_content as content,tips_status as status');
        if (empty($template_info)) {
            returnCode(11, [], '获取对象不存在！');
        }
        $param=[
            'tips_title'=>$_POST['title'],
            'tips_content'=>htmlspecialchars_decode($_POST['content']),
            'tips_status'=>$_POST['status']
        ];
        $rr=D('Plugin_material_diy_template')->where(['template_id'=>$template_info['template_id']])->save($param);
        returnCode(0, ['status'=>$rr ? 1 : 0]);
    }
    
    public function save_bind_deal_with(){
        $use_type_id = I('use_type_id');
        $template_id = I('template_id');
        $use_type_id = trim($use_type_id);
        $template_id = intval($template_id);
        if($template_id<1){
            returnCode(11, [], '模板参数ID错误！');
        }
        $where_template=array('template_id'=>$template_id);
        $plugin_material_diy_template=M('Plugin_material_diy_template');
        $saveData=array('template_type'=>$use_type_id);
        $rets = $plugin_material_diy_template->where($where_template)->data($saveData)->save();
        if (!$rets) {
            //returnCode(11, [], '数据保存失败了！');
        }
        returnCode(0, array('template_id'=>$template_id));
    }

}