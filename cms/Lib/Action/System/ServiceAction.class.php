<?php

/*
 * 到位服务
 */

class ServiceAction extends BaseAction {
    //分类列表
	public function index() {
        $database_Service_category = D('Service_category');
        $fcid = intval($_GET['fcid']);
        $condition['fcid'] = $fcid;

        $count_Service_category = $database_Service_category->where($condition)->count();
        import('@.ORG.system_page');
        $p = new Page($count_Service_category, 30);
        $category_list = $database_Service_category->field(true)->where($condition)->order('`cat_sort` DESC,`cid` ASC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('category_list', $category_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        if ($fcid > 0) {
            $condition_now['cid'] = $fcid;
            $now_category = $database_Service_category->field(true)->where($condition_now)->find();
            if (empty($now_category)) {
                $this->error_tips('没有找到该分类信息！', 3, U('Service/index'));
            }
            $this->assign('now_category', $now_category);
        }
        $this->assign('fcid', $fcid);
        $this->display();
    }


    public function service_config(){
        redirect(U('Config/index',array('galias'=>'service','header'=>'Service/config')));
    }

    public function ser_config(){
        redirect(U('Config/index',array('galias'=>'service_quick','header'=>'Service/config_quick')));
    }

    /*     * *目录添加操作页** */

    public function cat_add() {
        $fcid = intval($_GET['fcid']);
        $this->assign('bg_color', '#F3F3F3');
        $this->assign('fcid', $fcid);
        $this->display();
    }

    /*     * *保存目录** */

    public function cat_modify() {
        if (IS_POST) {
            $fcid = intval($_POST['fcid']);
            $pfcid = intval($_POST['pfcid']);
            if (($fcid > 0) && ($pfcid == 0))
                $_POST['subdir'] = 2;
            if (($fcid > 0) && ($pfcid > 0)) {
                $_POST['subdir'] = 3;
                $_POST['cat_sort'] = 0;
                $_POST['is_hot'] = 0;
                $_POST['cat_status'] = 1;
            }
            $datas = $this->Removalquotes($_POST);
            $database_Service_category = D('Service_category');
            $datas['addtime'] = $datas['updatetime'] = time();
            if ($database_Service_category->data($datas)->add()) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function cat_edit() {
        $this->assign('bg_color', '#F3F3F3');

        $database_Service_category = D('Service_category');
        $condition_now_Service_category['cid'] = intval($_GET['cid']);
        $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();
        if (empty($now_category)) {
            $this->frame_error_tips('没有找到该分类信息！');
        }
        $this->assign('now_category', $now_category);
        $this->display();
    }

    public function cat_amend() {
        if (IS_POST) {
            $database_Service_category = D('Service_category');
            $datas = $this->Removalquotes($_POST);
            $catInfo = $database_Service_category->where(array('cid'=>$_POST['cid']))->find();
            if ($database_Service_category->data($datas)->save()) {
                if($_POST['cat_status'] == 0){
                    $database_Service_category->where(array('fcid'=>$_POST['cid']))->data(array('cat_status'=>$_POST['cat_status']))->save();
                }else if($_POST['cat_status'] == 1){
                    if($catInfo['fcid']){
                        $database_Service_category->where(array('cid'=>$catInfo['fcid']))->data(array('cat_status'=>$_POST['cat_status']))->save();
                    }
                }
                $this->frame_submit_tips(1,'编辑成功！');
            } else {
                $this->frame_submit_tips(0,'编辑失败！请重试~');
            }
        } else {
            $this->frame_submit_tips(0,'非法提交,请重新提交~');
        }
    }

    public function cat_del() {
        if (IS_POST) {
            $database_Service_category = D('Service_category');
            $database_service_provider_category = D('Service_provider_category');
            $where['cid'] = intval($_POST['cid']);
            $now_category = $database_Service_category->field(true)->where($where)->find();
            if ($database_Service_category->where($where)->delete()) {
                $database_Service_category->where(array('fcid' => $now_category['cid']))->delete();
                $record = array();
                $record['uid'] = $this->system_session['id'];
                $record['name'] = $this->system_session['realname'];
                $record['phone'] = $this->system_session['phone'];
                $record['type'] = 0;
                $record['from_type'] = 0;
                $record['dateline'] = time();
                $record['service_id'] = intval($_POST['cid']);
                if ($now_category['fcid'] == 0) {
                    // 查询下子分类
                    $info = $database_Service_category->where(array('fcid' => $now_category['cid']))->field(true)->select();
                    $remark = array();
                    foreach ($info as $val) {
                        // 清除关联的 服务商信息
                        $remark[] = $val;
                        $database_service_provider_category->where(array('cid' => $val['cid']))->delete();
                    }
                } else {
                    // 清除关联的 服务商信息
                    $database_service_provider_category->where(array('cid' => $now_category['cid']))->delete();
                    $remark[] = $now_category;
                }
                $record['remark'] = serialize($remark);
                // 记录到 log 表格
                M('Service_log')->data($record)->add();
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function cat_field() {
        $database_Service_category = D('Service_category');
        $condition_now_Service_category['cid'] = intval($_GET['cid']);
        $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();
        if (empty($now_category)) {
            $this->frame_error_tips('没有找到该分类信息！');
        }
        if (!empty($now_category['cat_field'])) {
            $now_category['cat_field'] = unserialize($now_category['cat_field']);
        }
        $f_category = $database_Service_category->field(true)->where(array('cid' => $now_category['fcid']))->find();
        $f_empty_cat_field = empty($f_category) || empty($f_category['cat_field']) ? true : false;
        unset($f_category);
        $this->assign('f_empty_cat_field', $f_empty_cat_field);
        $this->assign('now_category', $now_category);
        $InputTypeArr = $this->getInputType();
        $this->assign('inputTypeArr', $InputTypeArr);
        $this->display();
    }

    /*     * **添加字段***** */

    public function cat_field_add() {
        $database_Service_category = D('Service_category');
        $condition_now_Service_category['cid'] = intval($_GET['cid']);
        $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();
        $i = 0;
        if (!empty($now_category['cat_field'])) {
            $cat_field = unserialize($now_category['cat_field']);
            foreach ($cat_field as $key => $vv) {
                if (isset($vv['isfilter']) && ($vv['isfilter'] == 1)) {
                    $i++;
                }
            }
        }
        $this->assign('isfilter', $i);
        $InputTypeArr = $this->getInputType();
        $this->assign('bg_color', '#F3F3F3');
        $this->assign('inputTypeArr', $InputTypeArr);
        $this->display();
    }

    /*     * **编辑字段***** */

    public function cat_field_edit() {
        $cid = intval($_GET['cid']);
        $key = isset($_GET['eid']) ? intval($_GET['eid']) : false;
        if (($cid > 0) && ($key !== false) && ($key >= 0)) {
            $Service_categoryDb = D('Service_category');
            $now_category = $Service_categoryDb->field(true)->where(array('cid' => $cid))->find();
            if (!empty($now_category) && !empty($now_category['cat_field'])) {
                $cat_field = unserialize($now_category['cat_field']);
                $subdir = $now_category['subdir'];
                unset($now_category);
                $i = 0;
                if (!empty($cat_field)) {
                    foreach ($cat_field as $kyy => $vv) {
                        if (isset($vv['isfilter']) && ($vv['isfilter'] == 1)) {
                            $i++;
                        }
                    }
                }

                $text = '';
                $thiscat_field = isset($cat_field[$key]) ? $cat_field[$key] : array();
                if (isset($thiscat_field['filtercon']) && !empty($thiscat_field['filtercon'])) {
                    foreach ($thiscat_field['filtercon'] as $fvv) {
                        $text.=$fvv . PHP_EOL;
                    }
                }

                $optstr = '';
                if (isset($thiscat_field['opt']) && !empty($thiscat_field['opt'])) {
                    foreach ($thiscat_field['opt'] as $ovv) {
                        $optstr.=$ovv . PHP_EOL;
                    }
                }

                $descstr = '';
                if (isset($thiscat_field['desc']) && !empty($thiscat_field['desc'])) {
                    foreach ($thiscat_field['desc'] as $dvo) {
                        $descstr.=$dvo . PHP_EOL;
                    }
                }

                $this->assign('isfilter', $i);
                $this->assign('fkey', $key);
                $this->assign('optstr', rtrim($optstr));
                $this->assign('descstr', rtrim($descstr));
                $this->assign('textstr', rtrim($text));
                $this->assign('thiscat_field', $thiscat_field);
                $this->assign('cid', $cid);
                $InputTypeArr = $this->getInputType();
                $this->assign('bg_color', '#F3F3F3');
                $this->assign('inputTypeArr', $InputTypeArr);
                $this->display();
            }
        }
    }


    public function cat_field_del(){
        if(IS_POST){
            $Service_categoryDb = D('Service_category');
            $condition_now_classify_category['cid'] = intval($_POST['cid']);
            $now_category = $Service_categoryDb->field(true)->where($condition_now_classify_category)->find();

            if(!empty($now_category['cat_field'])){
                $cue_field = unserialize($now_category['cat_field']);
                $new_cue_field = array();

                foreach($cue_field as $key=>$value){
                    if($value['name'] != $_POST['name']){
                        array_push($new_cue_field,$value);
                    }
                }
            }else{
                $this->error('此填写项不存在！');
            }

            $data_classify_category['cat_field'] = serialize($new_cue_field);
            $data_classify_category['cid'] = $now_category['cid'];
            if($Service_categoryDb->data($data_classify_category)->save()){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！请重试~');
            }
        }else{
            $this->error('非法提交,请重新提交~');
        }
    }

    /*     * *继承父目录字段** */

    public function fieldInherit() {
        $cid = intval($_GET['pcid']);
        $mycid = intval($_GET['cid']);
        $Service_categoryDb = D('Service_category');
        $pcategory = $Service_categoryDb->field(true)->where(array('cid' => $cid))->find();
        if (($pcategory['cat_field']) && ($mycid > 0)) {
            $fg = $Service_categoryDb->where(array('cid' => $mycid))->save(array('cat_field' => $pcategory['cat_field']));
            if ($fg) {
                $this->success('处理成功！');
                exit();
            }
        }
        $this->error('处理失败！');
    }

    private function getInputType() {
		$inputtypeDb = D('Service_inputtype');
		$session_InputType = $inputtypeDb->order('id ASC')->select();
		if (!empty($session_InputType)) {
			$newarr = array();
			foreach ($session_InputType as $vv) {
				$newarr[$vv['typ']] = $vv;
			}
			$session_InputType = $newarr;
		}else{
			$session_InputType = array();
		}
        return $session_InputType;
    }

    public function cat_field_modify() {

        if (IS_POST) {
            $Service_categoryDB = D('Service_category');
            $condition_now_Service_category['cid'] = intval($_POST['cid']);
            $fkey = isset($_POST['fkey']) ? intval($_POST['fkey']) : false;
            $now_category = $Service_categoryDB->field(true)->where($condition_now_Service_category)->find();
            $mc = 1;
            if (!empty($now_category['cat_field'])) {
                $cat_field = unserialize($now_category['cat_field']);
                foreach ($cat_field as $key => $value) {
                    if (($fkey === false) && ((!empty($_POST['alias_name']) && $value['alias_name'] == $_POST['alias_name']) || (!empty($_POST['key_value']) && $value['key_value'] == trim($_POST['key_value'])))) {
                        $this->error('字段已经添加，请勿重复添加！');
                    }
                }
            }else {
                $cat_field = array();
            }


            if(empty($cat_field)){
                $cat_field = array();
            }

            $numfilter = intval($_POST['numfilter']);
            $post_data['name'] = trim($_POST['name']);
            $post_data['type'] = intval($_POST['type']);
            $post_data['isinput'] = intval($_POST['isinput']);
            $post_data['iswrite'] = intval($_POST['iswrite']);
            $post_data['is_desc'] = intval($_POST['is_desc']);
            $post_data['alias_name'] = $_POST['alias_name'];
            $post_data['key_value'] = $_POST['key_value'];
            $post_data['istime'] = $_POST['istime'];

            if (in_array($post_data['type'], array(2, 3))) {
                $valueoftype = trim($_POST['valueoftype']);
                if (empty($valueoftype))
                    $this->error('供选择值框必须填上！');
                $valueoftype = $this->Removalquotes($valueoftype);
                $post_data['opt'] = explode(PHP_EOL, $valueoftype);

                $valuedesc = trim($_POST['valuedesc']);
                $valuedesc = $this->Removalquotes($valuedesc);
                $post_data['desc'] = explode(PHP_EOL, $valuedesc);
            }

            if (($fkey !== false) && ($fkey >= 0) && isset($cat_field[$fkey])) {
                $cat_field[$fkey] = $post_data;
            } else {
                array_push($cat_field, $post_data);
            }


            $data_Service_category['cat_field'] = serialize($cat_field);
            if ($Service_categoryDB->where(array('cid' => $now_category['cid']))->data($data_Service_category)->save()) {
                $msg = '添加字段成功！';
                if (($fkey !== false) && ($fkey >= 0))
                    $msg = '编辑字段成功！';
                $this->success($msg);
            } else {
                $msg = '添加失败！请重试~';
                if (($fkey !== false) && ($fkey >= 0))
                    $msg = '编辑失败！请重试~';
                $this->error($msg);
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    /*     * *去除单双引号*** */

    private function Removalquotes($array) {
        //$regex = "/\'|\"|\/|\\\|\<script|\<\/script/";
        $regex = "/\'|\"|\\\|\<script|\<\/script/";
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->Removalquotes($value);
                } else {
                    $value = strip_tags(trim($value));
                    $array[$key] = preg_replace($regex, '', $value);
                    //$array[$key] = htmlspecialchars($value, ENT_QUOTES);
                }
            }
            return $array;
        } else {
            $array = strip_tags(trim($array));
            $array = preg_replace($regex, '', $array);
            return $array;
            //return htmlspecialchars($array, ENT_QUOTES);
        }
    }






    // 实名认证
    public function authentication() {


        if($_GET['status'] == 2){
            $where['authentication_status'] =array('neq',1);
            $this->assign('status',2);
        }else{
            $where['authentication_status'] =1;
            $this->assign('status',1);
        }

        $count = D('Service_authentication')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 30);
        $authentication_list = D('Service_authentication')->where($where)->order('authentication_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('authentication_list', $authentication_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function authentication_check(){
        if(IS_POST){
            $data['examine_time'] = time();
            $data['authentication_status'] = $_POST['authentication_status'];
            $info = D('Service_authentication')->where(array('authentication_id'=>$_POST['authentication_id']))->find();
            $res = D('Service_authentication')->where(array('authentication_id'=>$_POST['authentication_id']))->data($data)->save();
            if($res){

                if($_POST['authentication_status'] == 2){
                    $user_info = D("User")->where(array('uid'=>$info['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=authentication';
						$model->sendTempMsg('OPENTM405462911', 
							array(
								'href' => $href, 
								'wecha_id' => $user_info['openid'], 
								'first' => '您提交的服务认证，已经通过系统审核啦。\n', 
								'keyword1' => '认证审核通过', 
								'keyword2' =>'已发送',
								'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
								'keyword4' => $user_info['nickname'], 
								'remark' => '\n请点击查看详细信息！'
							)
						);
                    }
                }elseif ($_POST['authentication_status'] == 3) {
                    $user_info = D("User")->where(array('uid'=>$info['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=authentication';
						$model->sendTempMsg('OPENTM405462911', 
							array(
								'href' => $href, 
								'wecha_id' => $user_info['openid'], 
								'first' => '您提交的服务认证，系统审核失败请重新编辑提交。\n', 
								'keyword1' => '认证审核失败', 
								'keyword2' =>'已发送',
								'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 
								'keyword4' => $user_info['nickname'], 
								'remark' => '\n请点击查看详细信息！'
							)
						);
                    }
                }

                $this->success('审核成功！');
            }else{
                $this->error('审核失败！请重试！');
            }
        }else{
            if($_GET['status'] == 1){
                $this->assign('status',$_GET['status']);
            }
            $authenticationInfo = D('Service_authentication')->where(array('authentication_id'=>$_GET['id']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $this->assign('authenticationInfo',$authenticationInfo);
            $this->display();
        }

    }
    // 实名认证
    public function authentication_config() {
        $authentication_config_list = D('Service_authentication_config')->select();
        $this->assign('authentication_config_list',$authentication_config_list);
        $this->display();
    }
    // 实名认证
    public function authentication_config_add() {
        if(IS_POST){
            $data = $_POST;
            $data['add_time'] = time();
            $info = D('Service_authentication_config')->where(array('key'=>$_POST['key']))->find();
            if($info){
                $this->error('添加失败！唯一标识已存在！');
                die;
            }
            $res = D('Service_authentication_config')->data($data)->add();
            if($res){
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！请重试！');
            }
        }else{
            $this->display();
        }

    }
    // 实名认证
    public function authentication_config_edit() {
        if(IS_POST){

            $info = D('Service_authentication_config')->where(array('acid'=>array('neq',$_POST['acid']),'key'=>trim($_POST['key'])))->find();
            if($info){
                $this->error('修改失败！唯一标识已存在！');
                die;
            }
            $data = $_POST;
            $res = D('Service_authentication_config')->where(array('acid'=>$_POST['acid']))->data($data)->save();
            if($res){
                $this->success('修改成功！');
            }else{
                $this->error('修改失败！请重试！');
            }
        }else{
            $info = D('Service_authentication_config')->where(array('acid'=>$_GET['acid']))->find();
            $this->assign('info',$info);
            $this->display();
        }

    }
    // 实名认证
    public function authentication_config_del() {
        D('Service_authentication_config')->where(array('acid'=>$_POST['acid']))->delete();
        $this->success('删除成功！');
    }





    public function cue_field() {
        $database_Service_category = D('Service_category');
        $condition_now_Service_category['cid'] = intval($_GET['cid']);
        $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();
        if (empty($now_category)) {
            $this->frame_error_tips('没有找到该分类信息！');
        }
        if (!empty($now_category['cat_fid'])) {
            $this->frame_error_tips('该分类不是主分类，无法使用商品字段功能！');
        }
        if (!empty($now_category['cue_field'])) {
            $now_category['cue_field'] = unserialize($now_category['cue_field']);
        }
        $this->assign('now_category', $now_category);

        $this->display();
    }

    public function cue_field_add() {
        $this->assign('bg_color', '#F3F3F3');

        $this->display();
    }

    public function cue_field_modify() {
        if (IS_POST) {
            $database_Service_category = D('Service_category');
            $condition_now_Service_category['cid'] = intval($_POST['cid']);
            $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();

            if (!empty($now_category['cue_field'])) {
                $cue_field = unserialize($now_category['cue_field']);
                foreach ($cue_field as $key => $value) {
                    if ($value['name'] == $_POST['name']) {
                        $this->error('该填写项已经添加，请勿重复添加！');
                    }
                }
            } else {
                $cue_field = array();
            }

            $post_data['name'] = $_POST['name'];
            $post_data['type'] = $_POST['type'];
            $post_data['sort'] = strval($_POST['sort']);

            array_push($cue_field, $post_data);
            $data_Service_category['cue_field'] = serialize($cue_field);
            $data_Service_category['cid'] = $now_category['cid'];
            if ($database_Service_category->data($data_Service_category)->save()) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function cue_field_del() {
        if (IS_POST) {
            $database_Service_category = D('Service_category');
            $condition_now_Service_category['cid'] = intval($_POST['cid']);
            $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();

            if (!empty($now_category['cue_field'])) {
                $cue_field = unserialize($now_category['cue_field']);
                $new_cue_field = array();
                foreach ($cue_field as $key => $value) {
                    if ($value['name'] != $_POST['name']) {
                        array_push($new_cue_field, $value);
                    }
                }
            } else {
                $this->error('此填写项不存在！');
            }
            $data_Service_category['cue_field'] = serialize($new_cue_field);
            $data_Service_category['cid'] = $now_category['cid'];
            if ($database_Service_category->data($data_Service_category)->save()) {
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    // 服务列表
    public function offer_list(){

         $where = "so.publish_id= sup.publish_id and u.uid = so.uid";
//        $where = "1";
        //搜索
        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_sn') {
                $where .= " AND sup.order_sn LIKE '%".$_GET['keyword']."%' ";
            }  else if ($_GET['searchtype'] == 'nickname') {
                $where .= " AND u.nickname LIKE '%".$_GET['keyword']."%' ";
            } else if ($_GET['searchtype'] == 'phone') {
                $where .= " AND u.phone LIKE '%".$_GET['keyword']."%' ";
            }
        }

        if(!empty($_GET['start_time'])&&!empty($_GET['end_time'])){
            if ($_GET['start_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['start_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $where .= " AND so.add_time BETWEEN ".$period[0].' AND '.$period[1];
        }

        if(!empty($_GET['status'])){
            if (intval($_GET['status']) == 5) {
                $where .= " AND so.status = ".$_GET['status'];
            } else {
                $where .= " AND sup.status = ".$_GET['status'];
            }
        }

         $serviceOfferCount = D()->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'user'=>'u'))->where($where)->field('so.*,sup.*,so.status as offer_status,u.nickname,u.phone')->count();
//         $join = 'as sup left join '. C('DB_PREFIX').'service_offer as so on so.publish_id= sup.publish_id left join '. C('DB_PREFIX').'user as u on u.uid = sup.uid';
//        $serviceOfferCount = D('Service_user_publish')->join($join)->where($where)->count();

        import('@.ORG.system_page');
        $p = new Page($serviceOfferCount, 30);
         $serviceOfferList = D()->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'user'=>'u'))->limit($p->firstRow . ',' . $p->listRows)->where($where)->field('so.*,sup.*,so.status as offer_status,u.nickname,u.phone')->order('so.offer_id desc')->select();
       
//        $serviceOfferList = D('Service_user_publish')->join($join)->limit($p->firstRow . ',' . $p->listRows)->where($where)->field('so.*,sup.*,so.status as offer_status,u.nickname,u.phone')->order('sup.publish_id desc')->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        foreach ($serviceOfferList as $key => $value) {
            if($value['catgory_type'] == 2){
                $publish_total_price = D('Service_user_publish_buy')->where(array('publish_id'=>$value['publish_id']))->field('total_price')->find();
                $serviceOfferList[$key]['price'] = $publish_total_price['total_price'];
            }else if($value['catgory_type'] == 3){
                $publish_total_price = D('Service_user_publish_give')->where(array('publish_id'=>$value['publish_id']))->field('total_price')->find();
                $serviceOfferList[$key]['price'] = $publish_total_price['total_price'];
            }
            $categoryInfo = D('service_category')->where(array('cid'=>$value['cid']))->field('cid,cat_name,cut_proportion,return_integral_proportion')->find();
            $serviceOfferList[$key]['cat_name'] = $categoryInfo['cat_name'];
            $providerInfo = D('Service_provider')->where(array('uid'=>$value['p_uid']))->field('name')->find();
            $serviceOfferList[$key]['provider_name'] = $providerInfo['name'];

        }
        $this->assign('serviceOfferList',$serviceOfferList);
        $this->display();
    }

    // 导出
    public function offer_order_export(){
        $param = $_POST;
        $param['type'] = 'service_offer';
        $param['rand_number'] = time();
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
        die;
    }

    public function offer_info(){
        $publish_id = isset($_GET['publish_id']) ? intval($_GET['publish_id']) : 0;
        $offer_id = isset($_GET['offer_id']) ? intval($_GET['offer_id']) : 0;
        if ($publish_id) {
            $publishInfo = D('Service_user_publish')->where(array('publish_id'=> $publish_id))->find();
        } else {
            $offerInfo = D('Service_offer')->field(true)->where(array('offer_id' => $offer_id))->find();
            $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->find();
        }
        $catInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cat_name,accept_time')->find();
        if($catInfo){
            $publishInfo['cat_name'] = $catInfo['cat_name'];
        }
        if($publishInfo['catgory_type'] == 1){
            $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
            foreach ($publishInfo['cat_field'] as $kk => $vv) {
                if($vv['type'] == 7){
                   $publishInfo['address_start'] = $vv['value']['address_start'];
                   $publishInfo['address_start_lng'] = $vv['value']['address_start_lng'];
                   $publishInfo['address_start_lat'] = $vv['value']['address_start_lat'];
                   $publishInfo['address_end'] = $vv['value']['address_end'];
                   $publishInfo['address_end_lng'] = $vv['value']['address_end_lng'];
                   $publishInfo['address_end_lat'] = $vv['value']['address_end_lat'];
                }
            }
            unset($publishInfo['cat_field']['cid']);

            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->select();
            foreach ($offer_list as $key => $value) {
                $offer_list[$key]['msg_list'] = D('Service_offer_message')->where(array('offer_id'=>$value['offer_id']))->field('message,type,add_time')->order('add_time asc')->select();
                $offer_list[$key]['msg_count'] = count($offer_list[$key]['msg_list']);

            }
            $this->assign('offer_list',$offer_list);
            $this->assign('offer_count',count($offer_list));
        }else if($publishInfo['catgory_type'] == 2){

            $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $Deliver_supply = D('Deliver_supply')->where('order_id='.$publish_id.' AND item=3')->field('deliver_user_fee')->find();
            $cat_field_info['deliver_user_fee'] = $Deliver_supply['deliver_user_fee'];
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            foreach ($cat_field_info['img'] as &$item) {
                $item = replace_file_domain($item);
            }

            // 判断表中获取到地址id 且地址信息为空 获取对应id的地址信息写入，否则取表中记录的地址
            if ($cat_field_info['adress_id'] && !$cat_field_info['end_adress_name']) {
                $adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['adress_id']);

                $cat_field_info['end_adress_name'] = $adress_info['adress'].$adress_info['detail'];
            }
            // $cat_field_info['arrival_time']= $publishInfo['add_time']+($cat_field_info['arrival_time']*60); //发布时间加上分钟
            // $cat_field_info['arrival_time']=$cat_field_info['arrival_time']/60; //小时送达

            $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();

            $plat_order_info = M('Plat_order')->where(array('business_type' => 'service', 'business_id' => $publishInfo['publish_id']))->field('pay_time, paid, is_mobile_pay, pay_type,refund_detail,is_refund,system_balance,pay_money')->find();
            if ($plat_order_info) {
                $paytypestr = D('Pay')->get_pay_name($plat_order_info['pay_type'], $plat_order_info['is_mobile_pay'], $plat_order_info['paid']);
                $cat_field_info['paytypestr'] = $paytypestr;
                $cat_field_info['paid'] = $plat_order_info['paid'] == 1 ? "已支付" : "未支付";
                $cat_field_info['pay_time'] = $plat_order_info['pay_time'];
            } else {
                $cat_field_info['paytypestr'] = '未支付';
                $cat_field_info['paid'] = '未支付';
                $cat_field_info['pay_time'] = '';
            }
            $cat_field_info['system_balance'] = $plat_order_info['system_balance'];
            $cat_field_info['pay_money'] = $plat_order_info['pay_money'];

            // 处理退款
            $cat_field_info['is_refund'] = $plat_order_info['is_refund'];
            $cat_field_info['refund_detail'] = unserialize($plat_order_info['refund_detail']);
            //增值服务费用列表
            $cat_field_info['added_fee_list'] = json_decode($cat_field_info['added_fee_list'],true);

            $this->assign('offer_info',$offer_info);

            $this->assign('cat_field_info',$cat_field_info);



        }else if($publishInfo['catgory_type'] == 3){
            $catInfo['accept_time']*60*60;

            $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $Deliver_supply = D('Deliver_supply')->where('order_id='.$publish_id.' AND item=3')->field('deliver_user_fee')->find();
            $cat_field_info['deliver_user_fee'] = $Deliver_supply['deliver_user_fee'];
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            foreach ($cat_field_info['img'] as &$item) {
                $item = replace_file_domain($item);
            }
            $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();

            $plat_order_info = M('Plat_order')->where(array('business_type' => 'service', 'business_id' => $publishInfo['publish_id']))->field('pay_time, paid, is_mobile_pay, pay_type,refund_detail,is_refund,system_balance,pay_money')->find();
            if ($plat_order_info) {
                $paytypestr = D('Pay')->get_pay_name($plat_order_info['pay_type'], $plat_order_info['is_mobile_pay'], $plat_order_info['paid']);
                $cat_field_info['paytypestr'] = $paytypestr;
                $cat_field_info['paid'] = $plat_order_info['paid'] == 1 ? "已支付" : "未支付";
                $cat_field_info['pay_time'] = $plat_order_info['pay_time'];
            } else {
                $cat_field_info['paytypestr'] = '未支付';
                $cat_field_info['paid'] = '未支付';
                $cat_field_info['pay_time'] = '';
            }
            $cat_field_info['system_balance'] = $plat_order_info['system_balance'];
            $cat_field_info['pay_money'] = $plat_order_info['pay_money'];
            // 处理退款
            $cat_field_info['is_refund'] = $plat_order_info['is_refund'];
            $cat_field_info['refund_detail'] = unserialize($plat_order_info['refund_detail']);

            $this->assign('offer_info',$offer_info);

            // 判断表中获取到地址id 且地址信息为空 获取对应id的地址信息写入，否则取表中记录的地址
            if ($cat_field_info['start_adress_id'] && !$cat_field_info['start_adress_name']) {
                $start_adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['start_adress_id']);
                $cat_field_info['start_adress_name'] = $start_adress_info['adress'].$start_adress_info['detail'];
            }
            if ($cat_field_info['end_adress_id'] && !$cat_field_info['end_adress_name']) {
                $end_adress_info = D("User_adress")->get_adress($publishInfo['uid'],$cat_field_info['end_adress_id']);
                $cat_field_info['end_adress_name'] = $end_adress_info['adress'].$end_adress_info['detail'];
            }

            fdump($cat_field_info, '$cat_field_info', true);
            //增值服务费用列表
            $cat_field_info['added_fee_list'] = json_decode($cat_field_info['added_fee_list'],true);
            $this->assign('cat_field_info',$cat_field_info);
        }

        // 查询发布服务快派用户注册的手机号
        if ($publishInfo['uid']) {
            $user_info = M('User')->field('phone')->where(array('uid' => $publishInfo['uid']))->find();
            if ($user_info['phone']) {
                $publishInfo['register_phone'] = $user_info['phone'];
            } else {
                $publishInfo['register_phone'] = '';
            }
        }

        // 获取指定店铺信息
        if ($publishInfo['auto_goods_id']) {
            $auto_goods_where['a.id'] = $publishInfo['auto_goods_id'];
            $store_info = M('Service_auto_goods')->field('s.name,s.kefu_phone')->join(' as a left join '.C('DB_PREFIX').'merchant_store as s on a.store_id=s.store_id')->where($auto_goods_where)->find();
            $this->assign('store_info',$store_info);
        }

        // 查询流水号
        $orderid = M('Plat_order')->where(array('business_id' => $publishInfo['publish_id'], 'business_type' => 'service'))->getField('orderid');
        if ($orderid) {
            $publishInfo['plat_orderid'] = 'plat_' . $orderid;
        }
        $this->assign('publishInfo',$publishInfo);
        $this->display();
    }

    public function offer_message(){
        $offerMessageList = D('Service_offer_message')->field(true)->where(array('offer_id'=>$_GET['offer_id']))->select();
        foreach ($offerMessageList as $key => $value) {
            if($value['type'] == 1){
                $userInfo = D('User')->where(array('uid'=>$value['uid']))->field('nickname,phone,avatar')->find();
            }else{
                $userInfo = D('User')->where(array('uid'=>$value['p_uid']))->field('nickname,phone,avatar')->find();
            }

            $offerMessageList[$key]['nickname'] = $userInfo['nickname'];
            $offerMessageList[$key]['phone'] = $userInfo['phone'];
            $offerMessageList[$key]['avatar'] = $userInfo['avatar'];

        }
        $this->assign('orderList',$offerMessageList);
        $this->display();
    }

    // 退款
    public function refund_reply(){
        $offerInfo = D('Service_offer')->where(array('offer_id' => $_POST['offer_id']))->find();
        if ($offerInfo['status'] == 1 || $offerInfo['status'] == 4 || $offerInfo['status'] == 6 || $offerInfo['status'] == 7 || $offerInfo['status'] == 10 || $offerInfo['status'] == 12) {
            exit(json_encode(array('error' => 2, 'msg' => '当前状态不可退款。')));
        }
        $publish_info = D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->field('publish_id,catgory_type,order_sn,uid,is_third,third_id,notifyurl,is_third_deliver')->find();

        if (1==$publish_info['catgory_type']) {
            D('User')->where(array('uid'=>$offerInfo['uid']))->setInc('now_money',$offerInfo['price']);
            D('User_money_list')->add_row($offerInfo['uid'],1,$offerInfo['price'],"管理员操作服务商退款 ".$offerInfo['price'].C('config.Currency_txt')."");

            D('Service_offer')->where(array('offer_id' => $_POST['offer_id']))->data(array('status' => 6))->save();
            D('Service_user_publish')->where(array('publish_id' => $offerInfo['publish_id']))->data(array('status' => 6))->save();

            $publishUserInfo = D('User')->where(array('uid'=>$offerInfo['uid']))->find();
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?c=Service&a=price_list&publish_id='.$offerInfo['publish_id'];
            $model->sendTempMsg('TM00017', array('href' => $href,
                'wecha_id' => $publishUserInfo['openid'],
                'first' => '管理员同意退款',
                'OrderSn' => $offerInfo['order_sn'],
                'OrderStatus' =>$this->user_session['nickname'].'同意退款',
                'remark' =>'有问题请联系服务商'));
            exit(json_encode(array('error' => 1, 'msg' => '退款成功')));
        } else {
            $operation_info = '管理员操作服务商退款, ';
            // 进行原路退款
            $plat_info = D('Plat_order')->where(array('business_type'=>'service','business_id'=>$publish_info['publish_id']))->find();
            $refund = array();
//        $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$plat_info['order_id'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();
            $payment_money_order = M('Payment_money_order')->order('id DESC')->where(array('order_id'=>$plat_info['order_id'],'orderid'=> $plat_info['orderid'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();

            if($plat_info['pay_money'] > 0 && ($plat_info['is_refund'] == 0 || $plat_info['is_refund'] == 4) && $payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'nong')){

                if($plat_info['pay_money'] == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $plat_info['pay_money'] = $payment_money_order['paid_money'];
                }
                $plat_info['payment_money'] = $plat_info['pay_money'];
                if($payment_money_order['pay_method'] == 'wft'){
                    $pay_method = 'wftpay';
                }
                if($payment_money_order['pay_method'] == 'paylinx'){
                    $pay_method = 'paylinx';
                }
                if($payment_money_order['pay_method'] == 'wepayez'){
                    $pay_method = 'wepayez';
                }
                if($payment_money_order['pay_method'] == 'weikebaba'){
                    $pay_method = 'weikebaba';
                }
                if($payment_money_order['pay_method'] == 'nong'){
                    $pay_method = 'nong';
                }
                import('@.ORG.LowFeePay');
                $lowfeepay = new LowFeePay($pay_method);
                $plat_info['order_type'] = 'service';
                $plat_info['orderNo'] = $payment_money_order['orderNo'];
                if($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                    $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                    if(!empty($mer_juhe)){
                        $lowfeepay->userId =$mer_juhe['userid'];
                        $plat_info['is_own'] =1 ;
                        $plat_info['orderNo'] =  $plat_info['orderNo'].'_1';
                    }

                }elseif($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                    $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                    if(!empty($mer_juhe)){
                        $lowfeepay->user_id = $mer_juhe['user_id'];
                        $lowfeepay->key = $mer_juhe['key'];
                        $plat_info['is_own'] =1 ;
                    }

                }
                $go_refund_param= $lowfeepay->refund($plat_info);
                $data_plat_order['order_id'] = $plat_info['order_id'];
                if(!$go_refund_param['error'] && $go_refund_param['type'] == 'ok'){
                    $data_plat_order['is_refund'] = 1;
                    $refund['error'] = 0;
                }else{
                    $data_plat_order['refund_detail'] = serialize($go_refund_param);
                    $data_plat_order['is_refund'] = 4;
                    $refund['error'] = 1;
                }
                $data_plat_order['last_time'] = time();
                D('Plat_order')->data($data_plat_order)->save();
            } else {
                $refund  = D('Plat_order')->order_refund(array('business_type'=>'service','business_id'=>$publish_info['publish_id']), $operation_info);
            }

            if(!$refund['error']) {
                $res = D('Service_offer')->where(array('offer_id' => $_POST['offer_id']))->data(array('status' => $_POST['status']))->save();


                D('Service_user_publish')->where(array('publish_id' => $offerInfo['publish_id']))->data(array('status' => $_POST['status']))->save();
//            //修复 订单金额未统一
//            if ($publish_info['catgory_type'] == 2) {
//                $buy = D('Service_user_publish_buy')->where(array('publish_id' => $offerInfo['publish_id']))->field('buy_id,publish_id,total_price')->find();
//                $price = $buy['total_price'];
//            } elseif ($publish_info['catgory_type'] == 3) {
//                $give = D('Service_user_publish_give')->where(array('publish_id' => $offerInfo['publish_id']))->field('give_id,publish_id,total_price')->find();
//                $price = $give['total_price'];
//            } else {
//                $price = $offerInfo['price'];
//            }
                if ($res) {
                    if($publish_info['is_third_deliver']!=1){
                        D('Deliver_supply')->updateStatusToZero($publish_info['publish_id']);
                    }
//                D('User')->where(array('uid' => $offerInfo['uid']))->setInc('now_money', $price);
                    // D('User_money_list')->add_row($offerInfo['uid'], 1, $price, "管理员操作服务商退款 " . $price . C('config.Currency_txt')."");
                    // 处理返回信息
                    // 获取配送员信息
                    $user = D('Deliver_user')->field(true)->where(array('uid' => $offerInfo['p_uid'],'status'=>array('neq',4)))->find();

                    // 添加记录
                    $log = array(
                        'publish_id' => $publish_info['publish_id'],
                        'uid' => $user['uid']?$user['uid']:0,
                        'status' => 6,
                        'name' => $user?$user['name']:'',
                        'phone' => $user?$user['phone']:'',
                        'remark' => '用户退款成功订单关闭',
                        'type' => 2,
                        'admin_id' => $this->system_session['id']
                    );
                    D('Service_user_publish_log')->add_log($log);
                    if ($publish_info['is_third']>0 && $publish_info['notifyurl']) {
                        // 回调信息
                        $notifyurl = $publish_info['notifyurl'];
                        $notify_data = array(
                            'third_id' => $publish_info['third_id'],
                            'publish_id' => $publish_info['publish_id'],
                            'status' => 6,
                            'name' => $user?$user['name']:'',
                            'phone' => $user?$user['phone']:'',
                            'msg' => '用户退款成功订单关闭',
                            'time' => time(),
                        );
                        D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                    }

                    if($publish_info['is_third_deliver']==1){
                        //第三方配送订单也改成取消
                        $res = invoke_v20_service('\app\mall\model\service\third_order_deliver\DeliverApiService/cancel',[['business_type'=>'service','order_no'=>$publish_info['order_sn']]]);
                        fdump_api([$publish_info['order_sn'],$res],'mall_third_deliver/cancel',1);
                    }
                    
                    $set_data = array(
                        'system_status' => 5,
                        'status' => 6
                    );
                    D("System_order")->save_order_info('service', $plat_info['order_id'], $set_data);


                    // 推送模板消息

                    $now_user = D('User')->field(true)->where(array('uid' => $publish_info['uid']))->find();
                    if ($now_user['openid']) {
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $_POST['publish_id'];
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $model->sendTempMsg('TM00017', array(
                            'href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => '您的跑腿订单退款已完成',
                            'OrderSn' => $publish_info['order_sn'],
                            'OrderStatus' => '已完成退款',
                            'remark' => date('Y-m-d H:i:s')
                        ));
                    }
                    exit(json_encode(array('error' => 1, 'msg' => '退款成功')));
                } else {
                    exit(json_encode(array('error' => 2, 'msg' => '退款失败请重试。')));
                }
            }else {
                exit(json_encode(array('error' => 2, 'msg' => $refund['msg'])));
            }
        }
    }
    //确认服务
    public function confirm_service(){
        $offerInfo = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->find();
        $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->field('publish_id,cid,uid,is_third,third_id,notifyurl')->find();
        $categoryInfo = D('service_category')->where(array('cid'=>$publishInfo['cid']))->field('cid,cat_name,cut_proportion,return_integral_proportion')->find();
        $cut_proportion = $categoryInfo['cut_proportion']/100;
        $extract_money = $offerInfo['price']*$cut_proportion;
        $price = $offerInfo['price']-$extract_money;
        $res = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>4))->save();
        if($res){
            D('User')->where(array('uid'=>$offerInfo['p_uid']))->setInc('now_money',$offerInfo['price']);
            D('User_money_list')->add_row($offerInfo['p_uid'],1,$price,"管理员操作用户付款 ".$offerInfo['price'].C('config.Currency_txt').",扣除手续费".$extract_money.C('config.Currency_txt'));


            // 处理返回信息
            // 获取配送员信息
            $user = D('Deliver_user')->field(true)->where(array('uid' => $offerInfo['p_uid'],'status'=>array('neq',4)))->find();

            D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->data(array('status'=>4))->save();
            // 添加记录
            $log = array(
                'publish_id' => $publishInfo['publish_id'],
                'uid' => $user['uid']?$user['uid']:0,
                'status' => 4,
                'name' => $user?$user['name']:'',
                'phone' => $user?$user['phone']:'',
                'remark' => '后台修改，订单完成',
                'type' => 2,
                'admin_id' => $this->system_session['id']
            );
            D('Service_user_publish_log')->add_log($log);
            if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                // 回调信息
                $notifyurl = $publishInfo['notifyurl'];
                $notify_data = array(
                    'third_id' => $publishInfo['third_id'],
                    'publish_id' => $publishInfo['publish_id'],
                    'status' => 4,
                    'name' => $user?$user['name']:'',
                    'phone' => $user?$user['phone']:'',
                    'msg' => '后台修改，订单完成',
                    'time' => time(),
                );
                D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
            }
            exit(json_encode(array('error'=>1,'msg'=>'服务已完成')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'完成服务失败请重试')));
        }
    }

    public function publish_list(){


        $publishCount = D('Service_user_publish')->count();

        import('@.ORG.system_page');
        $p = new Page($publishCount, 30);
        $publishList = D('Service_user_publish')->field(true)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);

        foreach ($publishList as $key => $value) {
            $offer_info = D('Service_offer')->field(true)->where(array('status'=>array('neq',1),'publish_id'=>$value['publish_id']))->find();
            if($value['catgory_type'] == 2){
                $publish_total_price = D('Service_user_publish_buy')->where(array('publish_id'=>$value['publish_id']))->field('total_price')->find();
                $publishList[$key]['price'] = $publish_total_price['total_price'];
            }else if($value['catgory_type'] == 3){
                $publish_total_price = D('Service_user_publish_give')->where(array('publish_id'=>$value['publish_id']))->field('total_price')->find();
                $publishList[$key]['price'] = $publish_total_price['total_price'];
            }else{
                $publishList[$key]['price'] = $offer_info['price'];
            }
            $publishList[$key]['offer_id'] = $offer_info['offer_id'];
            $categoryInfo = D('service_category')->where(array('cid'=>$value['cid']))->field('cid,cat_name,cut_proportion,return_integral_proportion')->find();
            $publishList[$key]['cat_name'] = $categoryInfo['cat_name'];
            $userInfo = D('User')->where(array('uid'=>$value['uid']))->field('nickname')->find();
            $publishList[$key]['nickname'] = $userInfo['nickname'];
        }

        $this->assign('publishList',$publishList);
        $this->display();
    }


    public function config(){
        $this->display();
    }


    // 退款
    public function publish_refund_reply(){
        $publish_info = D('Service_user_publish')->where(array('publish_id'=>$_POST['publish_id']))->field('publish_id,catgory_type,order_sn,uid,is_third_deliver')->find();

        $plat_info = D('Plat_order')->where(array('business_type'=>'service','business_id'=>$publish_info['publish_id']))->find();
        $refund = array();
        //$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$plat_info['order_id'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();
        $payment_money_order = M('Payment_money_order')->order('id DESC')->where(array('order_id'=>$plat_info['order_id'],'orderid'=> $plat_info['orderid'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();


        if($plat_info['pay_money'] > 0 && $plat_info['is_refund'] == 0 && $payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'nong')){

            if($plat_info['pay_money'] == $payment_money_order['payment_money']){
                //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                $plat_info['pay_money'] = $payment_money_order['paid_money'];
            }
            $plat_info['payment_money'] = $plat_info['pay_money'];
            if($payment_money_order['pay_method'] == 'wft'){
                $pay_method = 'wftpay';
            }
            if($payment_money_order['pay_method'] == 'paylinx'){
                $pay_method = 'paylinx';
            }
            if($payment_money_order['pay_method'] == 'wepayez'){
                $pay_method = 'wepayez';
            }
            if($payment_money_order['pay_method'] == 'weikebaba'){
                $pay_method = 'weikebaba';
            }
            if($payment_money_order['pay_method'] == 'nong'){
                $pay_method = 'nong';
            }
            import('@.ORG.LowFeePay');
            $lowfeepay = new LowFeePay($pay_method);
            $plat_info['order_type'] = 'service';
            $plat_info['orderNo'] = $payment_money_order['orderNo'];
            if($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                if(!empty($mer_juhe)){
                    $lowfeepay->userId =$mer_juhe['userid'];
                    $plat_info['is_own'] =1 ;
                    $plat_info['orderNo'] =  $plat_info['orderNo'].'_1';
                }

            }elseif($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                if(!empty($mer_juhe)){
                    $lowfeepay->user_id = $mer_juhe['user_id'];
                    $lowfeepay->key = $mer_juhe['key'];
                    $plat_info['is_own'] =1 ;
                }

            }
            $go_refund_param= $lowfeepay->refund($plat_info);
            $data_plat_order['order_id'] = $plat_info['order_id'];
            if(!$go_refund_param['error'] && $go_refund_param['type'] == 'ok'){
                $data_plat_order['is_refund'] = 1;
                $refund['error'] = 0;
            }else{
                $data_plat_order['refund_detail'] = serialize($go_refund_param);
                $data_plat_order['is_refund'] = 4;
                $refund['error'] = 1;
            }
            $data_plat_order['last_time'] = time();
            D('Plat_order')->data($data_plat_order)->save();
        } else {
            $refund  = D('Plat_order')->order_refund(array('business_type'=>'service','business_id'=>$publish_info['publish_id']));
        }

        if(!$refund['error']){
            $now_order = D('Service_user_publish')->where(array('publish_id'=>$_POST['publish_id']))->data(array('status'=>$_POST['status']))->save();
            // 只针对帮我买帮我送退款
            if ($publish_info['catgory_type'] != 2 && $publish_info['catgory_type'] != 3) {
                exit(json_encode(array('error'=>2,'msg'=>'退款失败请去订单列表退款。')));
            }
            if($now_order){
                if($publish_info['is_third_deliver']!=1) {
                    D('Deliver_supply')->updateStatusToZero($publish_info['publish_id']);
                }
                // D('User')->where(array('uid'=>$publish_info['uid']))->setInc('now_money',$price);
                // D('User_money_list')->add_row($publish_info['uid'],1,$price,"管理员操作服务商退款 ".$price.C('config.Currency_txt')."");

                // 推送模板消息
                $now_user = D('User')->field(true)->where(array('uid' => $publish_info['uid']))->find();
                if ($now_user['openid']) {
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $_POST['publish_id'];
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $model->sendTempMsg('TM00017', array(
                        'href' => $href,
                        'wecha_id' => $now_user['openid'],
                        'first' => '您的跑腿订单退款已完成',
                        'OrderSn' => $publish_info['order_sn'],
                        'OrderStatus' => '已完成退款',
                        'remark' => date('Y-m-d H:i:s')
                    ));
                }

                // 添加记录
                $log = array(
                    'publish_id' => $_POST['publish_id'],
                    'status' => 6,
                    'remark' => '后台修改，服务退款',
                    'type' => 2,
                    'admin_id' => $this->system_session['id']
                );
                D('Service_user_publish_log')->add_log($log);
                if ($publish_info['is_third']>0 && $publish_info['notifyurl']) {
                    // 回调信息
                    $notifyurl = $publish_info['notifyurl'];
                    $notify_data = array(
                        'third_id' => $publish_info['third_id'],
                        'publish_id' => $publish_info['publish_id'],
                        'status' => 11,
                        'msg' => '后台修改，服务退款',
                        'time' => time(),
                    );
                    D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                }
                if($publish_info['is_third_deliver']==1){
                    //第三方配送订单也改成取消
                    $res = invoke_v20_service('\app\mall\model\service\third_order_deliver\DeliverApiService/cancel',[['business_type'=>'service','order_no'=>$publish_info['order_sn']]]);
                    fdump_api([$publish_info['order_sn'],$res],'mall_third_deliver/cancel',1);
                }
                exit(json_encode(array('error'=>1,'msg'=>'退款成功')));
            }else{
                exit(json_encode(array('error'=>2,'msg'=>'退款失败请重试。')));
            }
        }else {
            fdump('跑腿用户退款失败>>>'.__LINE__,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($_POST,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($plat_info,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($payment_money_order,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($refund,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            M('Service_user_publish')->where(array('publish_id' => $_POST['publish_id']))->save(array('status' => 13));
            M('Deliver_supply')->where(array('item' => 3, 'order_id' => $_POST['publish_id']))->save(array('status' => 7));
            // 处理返回信息

            $log = array(
                'publish_id' => $_POST['publish_id'],
                'status' => 12,
                'remark' => '系统服务退款失败',
                'type' => 2
            );
            D('Service_user_publish_log')->add_log($log);
            if ($publish_info['is_third']>0 && $publish_info['notifyurl']) {
                // 回调信息
                $notifyurl = $publish_info['notifyurl'];
                $notify_data = array(
                    'third_id' => $publish_info['third_id'],
                    'publish_id' => $publish_info['publish_id'],
                    'status' => 12,
                    'msg' => '系统服务退款失败',
                    'time' => time(),
                );
                D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
            }
            exit(json_encode(array('error' => 2, 'msg' => '退款失败请重试。')));
        }


    }

    //自定义商品名称 分类
    public function auto_goodsname(){
        if (!empty($_GET['province_idss'])) {
            $condition['p.province'] = $_GET['province_idss'];
            $join = 'as p LEFT JOIN '.C('DB_PREFIX').'area as u on `p`.`province` = `u`.`area_pid` and  `p`.`city` = `u`.`area_id` and u.area_type=2';
            $condition['p.is_del'] = 0;
            $field="p.*,u.area_name";
        }
        if (!empty($_GET['city_idss'])) {
            $field="p.*,u.area_name";
            $condition['p.province'] = $_GET['province_idss'];
            $condition['p.city']=$_GET['city_idss'];
            $join = 'as p LEFT JOIN '.C('DB_PREFIX').'area as u on `p`.`city` = `u`.`area_id`  and `p`.`province` = `u`.`area_pid` and u.area_type=2';
            $condition['p.is_del'] = 0;
        }
        /*当其实页面不传参的时候*/
        if (empty($_GET['province_idss']) && empty($_GET['city_idss'])){
            $join = 'as p LEFT JOIN '.C('DB_PREFIX').'area as u on `p`.`city` = `u`.`area_id`  and `p`.`province` = `u`.`area_pid` and u.area_type=2';
            $condition['p.is_del'] = 0;
            $field="p.*,u.area_name";
        }
        $database_Service_category = M('Service_auto_category');

        $category_list = $database_Service_category->join($join)->field($field)->where($condition)->order('`sort` DESC')->select();
        fdump($database_Service_category->getLastSql(),"g000000",1);
        foreach ($category_list as $key => $value) {
            $category_list[$key]['nums'] = M('Service_auto_goods')->where(['cid'=>$value['id'], 'is_del'=> 0])->count();
        }
        $this->assign('category_list', $category_list);
        $this->display();
    }

    public function auto_cat_add(){
        $id = $_GET['id'];
        if($id > 0){
            $info = M('Service_auto_category')->where(['id'=> $id])->find();

            /*$now_area = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$this->system_session['area_id']))->find();*/
            /*$info['city_id'] = $info['area_pid'];*/
           /* $now_city = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$info['city']))->find();
            $info['province_id'] = $now_city['area_pid'];
            $now_province = D('Area')->field('area_name,`area_pid`')->where(array('area_id'=>$info['province']))->find();
            $info['province_name'] = $now_province['area_name'];*/

            $this->assign("info", $info);
        }
        $this->display();
    }

    public function auto_cat_modify(){
        if (IS_POST) {
            $id = intval($_POST['id']);
            if($_FILES){
                $image = D('Image')->handle($this->system_session['id'], 'system/merchant', 0, array('size' => 10));
                if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/system/merchant/', '', $image['url']));
                }
            }
            $data = [
                'name' => trim($_POST['name']),
                'sort' => intval($_POST['sort']),
                'status' => intval($_POST['status']),
                'type' => intval($_POST['type']),
                'is_common' => intval($_POST['is_common'])
            ];
            if($_POST['img']){
                $data['img'] = $_POST['img'];
            }
            $data['province'] = $_POST['province_idss'];
            $data['city'] = $_POST['city_idss'];
            if($id){
                $res = M('Service_auto_category')->where(['id'=>$id])->data($data)->save();
            }
            else{
                $res = M('Service_auto_category')->data($data)->add();
                $id = $res;
            }
            if ($res) {
                $this->frame_submit_tips(1,'操作成功！');
            } else {
                $this->frame_submit_tips(0,'操作失败！请重试~');
            }
        } else {
            $this->frame_submit_tips(0,'非法提交,请重新提交~');
        }
    }

    public function auto_cat_del(){
        if (IS_POST) {
            $id = intval($_POST['id']);
            $data['is_del'] = 1;
            $res = M('Service_auto_category')->where(['id'=>$id])->data($data)->save();
            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    //自定义商品  商品
    public function auto_goods(){
        $cid = $_GET['cid'];
        $this->assign("cid", $cid);
        $database_Service_category = M('Service_auto_category');
        $now_category = $database_Service_category->where('id='.$cid)->find();
        $this->assign("now_category", $now_category);

        if ($now_category['type'] == 1) {
            // 自定义店铺
            $result = D('Service_auto_category')->getAutoStoreByCid($cid,true);
            $goods_list = $result['store_list'];
        }else{
            $condition['is_del'] = 0;
            $condition['cid'] = $cid;
            $goods_list = M('Service_auto_goods')->field(true)->where($condition)->order('`sort` DESC')->select();
        }
        
        $this->assign('goods_list', $goods_list);
        $this->display();
    }

    public function auto_goods_add(){
        $id = $_GET['id'];
        $cid = $_GET['cid'];
        if($id > 0){
            $info = M('Service_auto_goods')->where(['id'=> $id])->find();
            $cid = $info['cid'];
            $this->assign("info", $info);
        }
        $this->assign("cid", $cid);
        $this->display();
    }

    // 设置商家起送费
    public function auto_goods_delivery_fee_set(){
        $id = intval($_POST['id']);
        $delivery_fee = $_POST['delivery_fee'];
        if($delivery_fee > 0){
            $delivery_fee = floatval($delivery_fee);
        }

        if(!$id){
            $this->error('参数错误');
        }

        $info = M('Service_auto_goods')->where(['id'=> $id])->find();
        if (!$info) {
            $this->error('内容不存在');
        }

        $data['delivery_fee'] = $delivery_fee;
        if( M('Service_auto_goods')->where(['id'=> $id])->save($data) !== false ){
            $this->success('设置成功');
        }else{
            $this->error('设置失败');
        }
    }



    public function auto_store_add(){  
        $id = $_GET['id'];
        $cid = $_GET['cid'];


        $province_id = I("province_id");
        $city_id = I("city_id");
        $this->assign('bg_color','#F3F3F3');

        $database_merchant_store = D('Merchant_store');
        $condition_merchant_store['status'] = '1';
        $where="`s`.`mer_id`=`m`.`mer_id` AND (`m`.`merchant_end_time` = 0 OR `m`.`merchant_end_time`>".time().")";

        if($province_id){
            $where .= " and `s`.province_id=".$province_id;
        }
        if($city_id){
            $where .= " and `s`.city_id=".$city_id;
        }
        if($_GET['search']){
            $where.=" AND s.name like '%{$_GET['search']}%'";
        }
        $count_store = M()->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant'=>'m'))->where($where)->count();

        import('@.ORG.system_page');
        $p = new Page($count_store, 10);

        $store_list = M()->field("`m`.`name` AS merchant_name,`m`.`merchant_end_time`,`s`.`store_id`, `s`.`logo`, `s`.`name`, `s`.`adress`, `s`.`phone`, `s`.`sort`,`s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`")->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant'=>'m'))->where($where)->order("`s`.`sort` DESC,`s`.`store_id` ASC")->limit($p->firstRow . ',' . $p->listRows)->select();

        $store_ids = array_column($store_list, 'store_id');
        if(!empty($store_ids)){
            $time_list = D('Merchant_store_open_time')->get_all_time_by_store($store_ids);
        }

        $select_store = array_column(M('Service_auto_goods')->where(['cid'=>$cid,'is_del'=>0])->select(),'store_id');
        foreach($store_list as &$value){
            $value['open_time_list'] = $time_list[$value['store_id']];
            $value['logo'] = replace_file_domain($value['logo']);
            if (in_array($value['store_id'], $select_store)) {
                $value['select'] = 1;
            }

        }

        $this->assign('store_list', $store_list);
        $this->assign("cid", $cid);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }


    public function ajax_add_store(){
        if (IS_POST) {
            $store_id = intval($_POST['store_id']);
            $data = [
                'cid' => intval($_POST['cid']),
                'store_id' => trim($_POST['store_id']),
            ];
            $res = M('Service_auto_goods')->data($data)->add();
            $id = $res;
            
            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function auto_goods_modify(){
        if (IS_POST) {
            $id = intval($_POST['id']);
            $data = [
                'cid' => intval($_POST['cid']),
                'name' => trim($_POST['name']),
                'sort' => intval($_POST['sort'])
            ];
            if($id){
                $res = M('Service_auto_goods')->where(['id'=>$id])->data($data)->save();
            }
            else{
                $res = M('Service_auto_goods')->data($data)->add();
                $id = $res;
            }
            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }

    public function auto_goods_del(){
        if (IS_POST) {
            $id = intval($_POST['id']);
            $data['is_del'] = 1;
            $res = M('Service_auto_goods')->where(['id'=>$id])->data($data)->save();
            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！请重试~');
            }
        } else {
            $this->error('非法提交,请重新提交~');
        }
    }  

    // 指定店铺订单列表
    public function auto_store_order_list(){
        $where = [];

        if (!empty($_GET['keyword'])) {
            if ($_GET['searchtype'] == 'order_sn') {
                $where['p.order_sn'] = ['like',"%".$_GET['keyword']."%"];
            }  else if ($_GET['searchtype'] == 'nickname') {
                $where['u.nickname'] = ['like',"%".$_GET['keyword']."%"];
            } else if ($_GET['searchtype'] == 'phone') {
                $where['u.phone'] = ['like',"%".$_GET['keyword']."%"];
            }
        }

        if(!empty($_GET['start_time'])&&!empty($_GET['end_time'])){
            if ($_GET['start_time']>$_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(strtotime($_GET['start_time']." 00:00:00"),strtotime($_GET['end_time']." 23:59:59"));
            $where['so.add_time'] = ['between',[$period[0],$period[1]]];
        }

        // 店铺搜索
        if(!empty($_GET['store_id'])){
            $where['b.store_id'] = intval($_GET['store_id']);
        }else{
            $where['b.store_id'] = ['gt',0]; // 指定店铺
        }
        //省市
        if (!empty($_GET['province_idss'])) {
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if (!empty($_GET['city_idss'])) {
            $where['a.province_id'] = $_GET['province_idss'];
            $where['a.city_id']=$_GET['city_idss'];
        }
        if (!empty($_GET['area_id'])) {
            $where['a.province_id'] = $_GET['province_idss'];
            $where['a.city_id']=$_GET['city_idss'];
            $where['a.area_id']=$_GET['area_id'];
        }

        if(!empty($_GET['store_id'])){
            $where['b.store_id'] = intval($_GET['store_id']);
        }else{
            $where['b.store_id'] = ['gt',0]; // 指定店铺
        }
        // 已完成
        $where['p.status'] = 4;

        $return = D('Service_auto_category')->getOrderList($where);
        $autoStoreArray = D('Service_auto_category')->getAutoStoreAll();

        $this->assign($return);
        $this->assign('autoStoreArray',$autoStoreArray['store_list']);
        $this->display();
    }

    // 指定店铺订单列表导出
    public function auto_store_order_export(){
        $param = $_POST;
        $param['type'] = 'auto_store_order';
        $param['rand_number'] = time();
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
    }



}