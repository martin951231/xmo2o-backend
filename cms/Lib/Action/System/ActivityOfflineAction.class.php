<?php
/*
 * 线下活动管理
 *
 * @  BuildTime  2019/04/04
 * 
 */
class ActivityOfflineAction extends BaseAction{
    //活动管理首页
    public function manage(){
        $database_activity_offline_cate = M('Activity_offline_cate');
        $where['fid'] = 0;
        $count = $database_activity_offline_cate->where($where)->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $database_activity_offline_cate->field(true)->where($where)->order('sort DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $pagebar = $page->show();

        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 关闭分类
     */
    public function close_cate(){
        $id = intval($_POST['id']);
        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $database_activity_offline_cate = M('Activity_offline_cate');
        $info = $database_activity_offline_cate->where(array('id'=>$id))->find();

        if (empty($info)) {
            $this->error('该分类不存在或者已经删除');
        }
        if ($info['status'] == 1) {
            $this->error('该分类已经关闭');
        }
        // 确认存在未关闭的正常该信息 做关闭处理
        $close1 = $database_activity_offline_cate->where(array('id'=>$id))->data(array('status' => 1))->save();
        $close2 = $database_activity_offline_cate->where(array('fid'=>$id))->data(array('status' => 1))->save();
        if($close1){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }

    /**
     * 开启分类
     */
    public function open_cate(){
        $id = intval($_POST['id']);

        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $database_activity_offline_cate = M('Activity_offline_cate');
        $info = $database_activity_offline_cate->where(array('id'=>$id))->find();
        if($_POST['is_second']){
            $pid = $database_activity_offline_cate->where(array('id'=>$info['fid']))->getField('status');
            if($pid == 1){
                $this->error('所属分类已关闭,子分类不能进行开启操作!');
            }
        }
        if (empty($info)) {
            $this->error('该分类不存在或者已经删除');
        }
        if ($info['status'] == 0) {
            $this->error('该分类已经开启');
        }
        // 确认存在未关闭的正常该信息 做开启处理
        $open1 = $database_activity_offline_cate->where(array('id'=>$id))->data(array('status' => 0))->save();
        $open2 = $database_activity_offline_cate->where(array('fid'=>$id))->data(array('status' => 0))->save();
        if($open1){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }

    /**
     * 删除分类
     */
    public function del_cate(){
        $id = $_POST['id'];
        $database_activity_offline_cate = M('Activity_offline_cate');
        $info = $database_activity_offline_cate->where(array('id'=>$id))->find();
        if (empty($info)) {
            $this->error('该分类不存在或者已经删除');
        }
        if($id){
            $res = $database_activity_offline_cate->where(array('id'=>$id))->delete();
            if($res){
                if ($info['fid'] == 0) {
                    $database_activity_offline_cate->where(array('fid'=>$id))->delete();
                }
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error_tips('参数传递错误'); 
        }
    }

    public function edit(){
        $database_activity_offline_cate = M('Activity_offline_cate');
        $is_classfiy = $_GET['is_classfiy'];
        if(IS_POST){
            $data['id'] = intval($_POST['id']);
            $data['cate_name'] = htmlspecialchars($_POST['cate_name']);
            $data['sort'] = intval($_POST['sort']);
            $data['status'] = intval($_POST['status']);
            if($data['id']){
                $res1 = $database_activity_offline_cate->data($data)->save();
                if($res1){
                    $this->frame_submit_tips(1,'操作成功！');
                }else{
                    $this->frame_submit_tips(0,'操作失败！请重试~');
                }
            }else{
                $this->frame_submit_tips(0,'缺少数据~请重试!');
            }
        }else{
            $id = $_GET['id'];
            $list= $database_activity_offline_cate->where(array('id'=>$id))->find();
            $list['f_cate_name'] = $database_activity_offline_cate->where(array('id'=>$list['fid']))->getField('cate_name');
            if($list){
                $this->assign('list',$list);
            }else{
                $this->frame_submit_tips(0,'没有查询到数据!');
            }
            $this->display();
        }
    }
    //添加分类
    public function add(){
        if(IS_POST){
            $data['cate_name'] = htmlspecialchars($_POST['cate_name']);
            if(!$data['cate_name']){
                $this->error('分类名称不能为空!');
            }
            $data['sort'] = intval($_POST['sort']) ? intval($_POST['sort']) : 0;
            $data['status'] = intval($_POST['status']) ? intval($_POST['status']) : 0;
            $data['fid'] = intval($_POST['fid']) ? intval($_POST['fid']) : 0;
            $data['add_time'] = time();
            if($data){
                $res = M('Activity_offline_cate')->add($data);
                if($res){
                    $this->frame_submit_tips(1,'操作成功！');
                }else{
                    $this->frame_submit_tips(0,'操作失败！请重试~');
                }
            }else{
                $this->frame_submit_tips(0,'缺少数据!~');
            }
        }
        $this->display();
    }


    public function classify(){
        $database_activity_offline_cate = M('Activity_offline_cate');
        $id = $_GET['id'];
        $where = array();
        if(!$id){
            $this->error('参数传递错误!');
        }else{
            $where['fid'] = $id;
        }

        $count = $database_activity_offline_cate->where($where)->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $database_activity_offline_cate->field(true)->where($where)->order('sort DESC')->limit($page->firstRow.','.$page->listRows)->select();
        foreach($list as $key=>$value){
            $list[$key]['f_cate_name'] = $database_activity_offline_cate->where(array('id'=>$value['fid']))->getField('cate_name');
            $area['province_id'] = $value['province_id'];
            $area['city_id'] = $value['city_id'];
            $area['id'] = $value['fid'];
        }
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->display();
    }

    //活动列表
    public function activity_list(){
        $database_activity_offline = M('Activity_offline');
        $where=array();
        if($_GET['province_idss']){
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['a.city_id'] = $_GET['city_idss'];
        }
        $where['a.is_del'] = 0;
        $count = $database_activity_offline->alias('a')->where($where)->join('join pigcms_activity_offline_cate as b on a.cate_id=b.id')->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $database_activity_offline->alias('a')->field('a.*,b.cate_name,c.nickname,c.phone as login_phone')->where($where)->join('join pigcms_activity_offline_cate as b on a.cate_id=b.id')->join('join pigcms_user as c on a.uid=c.uid')->limit($page->firstRow.','.$page->listRows)->order('a.add_time desc')->select();
        foreach($list as $key=>$value){
            if($value['status'] !=2){
                if ($value['end_time'] > time()) {
                    $list[$key]['status'] = 1;
                } else {
                    $list[$key]['status'] = 3;
                }
            }

        }
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 删除活动
     */
    public function del_activity(){
        $activity_id = $_POST['activity_id'];
        $database_activity_offline = M('Activity_offline');
        $info = $database_activity_offline->where(array('activity_id'=>$activity_id))->find();
        if (empty($info)) {
            $this->error('该活动不存在或者已经删除');
        }
        if($activity_id){

            $res = $database_activity_offline->where(array('activity_id'=>$activity_id))->delete();
            if($res){
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error_tips('参数传递错误');
        }
    }    

    /**
     * 审核活动
     */
    public function activity_audit(){
        $database_activity_offline = M('Activity_offline');
        if(IS_POST){
            $activity_id = intval($_POST['activity_id']);
            $activity_detail = $database_activity_offline->where(array('activity_id'=>$activity_id))->find();
            if (empty($activity_detail)) {
                $this->error('该活动不存在或者已经删除');
            }

            // $data['activity_id'] = $activity_id;
            $data['examine_status'] = intval($_POST['examine_status']);
            if(isset($_POST['reject_message']) && $_POST['reject_message'] !=''){
                $data['reject_message'] = htmlspecialchars($_POST['reject_message']);
                $message='不通过';
            }else{
                $message='通过';
                $data['reject_message'] = '';
            }

            $res = $database_activity_offline->where(array('activity_id'=>$activity_id))->save($data);
            if($res){
                $user_info = M('User')->field(true)->where(array('uid'=>$activity_detail['uid']))->find();

                if ($user_info['openid']) {
                    $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                    $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=indexDetail&activity_id=' . $activity_id;
                    $model->sendTempMsg('TM00017', array(
                        'href' => $href,
                        'wecha_id' => $user_info['openid'],
                        'first' => '活动【'.$activity_detail['title'].'】审核'.$message,
                        'OrderSn' => $activity_detail['activity_id'],
                        'OrderStatus' => '审核'.$message,
                        'remark' => date('Y-m-d H:i:s')
                    ));//活动审核成功模板推送
                }

                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $activity_id = $_GET['activity_id'];
            $activity_detail = $database_activity_offline->where(array('activity_id'=>$activity_id))->find();
            if (empty($activity_detail)) {
                $this->error('该活动不存在或者已经删除');
            }
            $this->assign('activity_detail',$activity_detail);
            $this->display();
        }
       
    }
 
    public function activity_join(){
        $database_activity_offline_join = M('Activity_offline_join');
        $id = intval($_GET['activity_id']);
        $where=array();
        if(!$id){
            $this->frame_submit_tips(0,'参数传递错误!');
        }else{
            $where['a.activity_id'] = $id;
        }
        // if($_GET['title']){
        //     $where['b.title'] = array('like','%'.$_GET['title'].'%');
        // }
        if($_GET['order_id'] || $_GET['order_id'] ==='0'){
            $where['a.order_id'] = intval($_GET['order_id']);
        }
        if($_GET['phone']){
            $where['a.phone'] = array('like','%'.$_GET['phone'].'%');
        }
        if($_GET['name']){
            $where['a.name'] = array('like','%'.$_GET['name'].'%');
        }
        $where['b.is_del'] = 0;
        $count = $database_activity_offline_join->where($where)->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $database_activity_offline_join
        ->alias('a')
        ->field('a.*,b.title,c.level,d.lname')
        ->where($where)->order('a.add_time DESC')
        ->join('join pigcms_activity_offline as b on a.activity_id=b.activity_id')
        ->join('join pigcms_user as c on a.uid=c.uid')
        ->join('left join pigcms_user_level as d on c.level=d.level')
        ->limit($page->firstRow.','.$page->listRows)->select();
        foreach($list as $key=>$value){
            $list[$key]['arr'] = json_decode($value['content']);
        }
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->display();
    }

    public function activity_join_edit(){
        if(IS_POST){
            $data['join_id'] = intval($_POST['id']);
            $data['status'] = intval($_POST['status']);
            $data['money'] = $_POST['money'];
            $data['phone'] = $_POST['phone'];
            $data['name'] = $_POST['name'];
            if(!$data['join_id']){
                $this->frame_submit_tips(0,'参数传递错误!');
            }else{
                $res = M('Activity_offline_join')->where(array('join_id'=>$data['join_id']))->save($data);
                if($res){
                    $this->frame_submit_tips(1,'编辑成功!');
                }else{
                    $this->frame_submit_tips(0,'编辑失败,请重试!');
                }
            }
        }else{
            $id = intval($_GET['id']);
            if(!$id){
                $this->frame_submit_tips(0,'参数传递错误!');
            }
            $list = M('Activity_offline_join')->alias('a')->field('a.*,b.title')->where(array('a.join_id'=>$id))->join('join pigcms_activity_offline as b on a.activity_id=b.activity_id')->find();
            if($list){
                $this->assign('list',$list);
            }else{
                $this->frame_submit_tips(0,'没有查询到数据!');
            }
        }
        
        $this->display();
    }


    // 活动报名导出
    public function activity_export(){
        if (empty($_GET['activity_id'])) {
            $this->frame_submit_tips(0,'传递参数错误!');
        }
        $activity_id = $_GET['activity_id'];

        $activity_info = D('Activity_offline')->where(array('activity_id' => $activity_id,'is_del'=>0))->find();
        if (empty($activity_info)) {
            $this->frame_submit_tips(0,'没有查询到数据!');
        }

        // 查询一下选项信息
        $activity_offline_option = D('Activity_offline_option')->field('title')->where(array('activity_id' => $activity_id, 'status' => 1))->select();
        $where = array(
            'a.activity_id' => $activity_id,
            'a.status' => array('in',array(1,2,4,5,6)),
        );
        $activity_join = D('Activity_offline_join')
        ->alias('a')
        ->field('a.*,b.order_id,d.lname')
        ->where($where)
        ->join('left join pigcms_activity_offline_order as b on a.activity_id=b.activity_id and b.uid != '.$activity_info['uid'])
        ->join('join pigcms_user as c on a.uid=c.uid')
        ->join('left join pigcms_user_level as d on c.level=d.level')
        ->order('a.add_time ASC')
        ->select();

        $join_number = count($activity_join);
        if(count($activity_join) <= 0){
            $this->frame_submit_tips(0,'数据数量错误!');
        }
        // 全部收款
        $total_money =  D('Activity_offline_join')->alias('a')->where($where)->sum('money');

        // 实际收款
        $where['status'] = array('in',array(1,2,5));
        $real_money =  D('Activity_offline_join')->alias('a')->where($where)->sum('money');

        $title_msg = "活动名称： " . $activity_info['title'] . '；共计' . $join_number . '人报名；共收款' . $total_money.C('config.Currency_txt').'，退款' . round($total_money-$real_money,2).C('config.Currency_txt').'，实收'.$real_money.C('config.Currency_txt');
        $col_num = count($activity_option) + 5;

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = $activity_info['title'] . '活动-报名列表';

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($activity_join)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle($title);
            $objActSheet = $objExcel->getActiveSheet();
            // 添加第一行表头
            $objActSheet->mergeCellsByColumnAndRow('0', '1', '' . $col_num, '1');
            $objActSheet->setCellValueByColumnAndRow('0', '1', $title_msg);
            $objActSheet->setCellValueByColumnAndRow('0', '2', '序号');
            $objActSheet->setCellValueByColumnAndRow('1', '2', '姓名');
            $objActSheet->setCellValueByColumnAndRow('2', '2', '手机号');
            $objActSheet->setCellValueByColumnAndRow('3', '2', '报名费用');
            $objActSheet->setCellValueByColumnAndRow('4', '2', '订单号');
            $column = 3;

            if (!empty($activity_option) ) {

                foreach ($activity_option as $key => $val) {
                    if($val['title'] == '姓名' || $val['title']=='手机'){
                        unset($activity_option[$key]);
                        continue;
                    }
                    $column = $key + 3;
                    $col_str = '' . $column;
                    $objActSheet->setCellValueByColumnAndRow($col_str, '2', $val['title']);
                }
            }
            $col_str = '' . ($column + 1);
            $objActSheet->setCellValueByColumnAndRow($col_str, '2', '会员等级');
            $col_str = '' . ($column + 2);
            $objActSheet->setCellValueByColumnAndRow($col_str, '2', '活动状态');
            $col_str_1 = '' . (intval($col_str) + 1);
            $objActSheet->setCellValueByColumnAndRow($col_str_1, '2', '报名时间');

            $status_info = array(
                0 => '报名失败',
                1 => '报名成功',
                2 => '已完成',
                3 => '待付款',
                4 => '已退款'
            );
            if (!empty($activity_join)) {
                $index = 3;

                $cell_list = range(0,$col_num);
                foreach ($cell_list as $cell) {           
                    $objActSheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($cell))->setWidth(20);
                    $objActSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex($cell))->getAlignment()->setWrapText(true);
                }

                foreach ($activity_join as $value) {
                    $objActSheet->setCellValueExplicitByColumnAndRow('0', $index, trim($value['join_id']));
                    $objActSheet->setCellValueExplicitByColumnAndRow('1', $index, trim($value['name']));
                    $objActSheet->setCellValueExplicitByColumnAndRow('2', $index, trim($value['phone']));
                    $objActSheet->setCellValueExplicitByColumnAndRow('3', $index, trim($value['money']));
                    $objActSheet->setCellValueExplicitByColumnAndRow('4', $index, trim($value['order_id']));
                    $columns = 3;
                    if (!empty($activity_option) ) {
                        $content = !empty($value['content']) ? json_decode($value['content'], true) : '';
                        foreach ($activity_option as $key => $val) {
                            if($val['title'] == '姓名' || $val['title']=='手机'){
                                unset($activity_option[$key]);
                                continue;
                            }

                            $columns = $key + 3;
                            if (!empty($content) && $content[$val['title']]) {
                                $content_info = $content[$val['title']];
                                if (is_array($content_info)) {
                                    $content_info = implode("/", $content_info);
                                }
                                    $objActSheet->setCellValueExplicitByColumnAndRow('' . $columns, $index, trim($content_info));
                            } else {
                                $objActSheet->setCellValueExplicitByColumnAndRow('' . $columns, $index, '');
                            }
                        }
                    }
                    if(!$value['lname']){
                      $value['lname']='暂无';
                    }
                    $objActSheet->setCellValueExplicitByColumnAndRow('' . ($columns + 1), $index, $value['lname']);
                    $objActSheet->setCellValueExplicitByColumnAndRow('' . ($columns + 2), $index, $status_info[$value['status']]);
                    $objActSheet->setCellValueExplicitByColumnAndRow('' . ($columns + 3), $index, date("Y-m-d H:i:s", $value['add_time']));
                    $index++;
                }
            }
        }
        $file_id = sprintf("%09d", $activity_id);
        $rand_num = substr($file_id, 0, 3) . '/' . substr($file_id, 3, 3) . '/' . substr($file_id, 6, 3);
        $title_info = $activity_id . '.xlsx';
        $upload_dir = "upload/activity/activity/{$rand_num}/";
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        $_savePath = $upload_dir . $title_info;
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        $objWriter->save($_savePath);

        $excel_url = '/' . $_savePath;
        $msg = D('Activity')->where(array('activity_id' => $activity_id))->data(array('excel_url' => $excel_url))->save();
        //输出
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

    public function activity_reply(){
        $database_activity_offline_reply = M('Activity_offline_reply');
        $id = intval($_GET['activity_id']);
        $where=array();
        if(!$id){
            $this->frame_submit_tips(0,'参数传递错误!');
        }else{
            $where['a.activity_id'] = $id;
        }
        $where['a.fid'] = 0;
        
        if($_GET['name']){
            $where['b.nickname'] = array('like','%'.$_GET['name'].'%');
        }
        $where['c.is_del'] = 0;
        $count = $database_activity_offline_reply->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity_offline as c on a.activity_id=c.activity_id')->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $database_activity_offline_reply->alias('a')->field('a.*,b.nickname,c.title')->where($where)->order('a.add_time DESC')->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity_offline as c on a.activity_id=c.activity_id')->limit($page->firstRow.','.$page->listRows)->select();
        foreach($list as $key=>$value){
            $arr = unserialize($value['image']);
            if(is_array($arr)){
                foreach($arr as $k=>$v){
                    $list[$key]['img'][] = $v;
                    $list[$key]['images'][] = replace_file_domain($v);
                }
            }elseif($arr){
                $list[$key]['img'] = $arr;
                $list[$key]['images'][] = replace_file_domain($arr);
            }
        }
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->display();
    }


    public function activity_comment_reply(){
        $database_activity_offline_reply = M('Activity_offline_reply');
        $id = intval($_GET['activity_id']);
        $fid = intval($_GET['fid']);
        $where=array();
        if(!$id || !$fid){
            $this->frame_submit_tips(0,'参数传递错误!');
        }else{
            $where['a.activity_id'] = $id;
        }
        $where['a.fid'] = $fid;

        if($_GET['name']){
            $where['b.nickname'] = array('like','%'.$_GET['name'].'%');
        }
        $where['c.is_del'] = 0;
        $count = $database_activity_offline_reply->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity_offline as c on a.activity_id=c.activity_id')->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $database_activity_offline_reply->alias('a')->field('a.*,b.nickname,c.title')->where($where)->order('a.add_time DESC')->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity_offline as c on a.activity_id=c.activity_id')->limit($page->firstRow.','.$page->listRows)->select();
        foreach($list as $key=>$value){
            $arr = unserialize($value['image']);
            if(is_array($arr)){
                foreach($arr as $k=>$v){
                    $list[$key]['img'][] = $v;
                    $list[$key]['images'][] = replace_file_domain($v);
                }
            }elseif($arr){
                $list[$key]['img'] = $arr;
                $list[$key]['images'][] = replace_file_domain($arr);
            }
        }
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->display();
    }

    public function reply_img(){
        $id = $_GET['id'];
        if(!$id){
            $this->frame_submit_tips(0,'参数传递错误!~');
        }else{
            $imgs = M('Activity_offline_reply')->field(true)->where(array('id'=>$id))->find();
            $arr = unserialize($imgs['image']);
            if(is_array($arr)){
                foreach($arr as $key=>$value){
                    $img[] = replace_file_domain($value);
                }
            }else{
                $img = replace_file_domain($arr);
            }
            $this->assign('imgs',$img);
        }
        $this->display();
    }


    public function del_reply(){
        $id = $_POST['id'];
        $database_activity_offline_reply = M('Activity_offline_reply');
        $info = $database_activity_offline_reply->where(array('id'=>$id))->find();
        if (empty($info)) {
            $this->error('该评论不存在或者已经删除');
        }
        if($id){
            $res = $database_activity_offline_reply->where(array('id'=>$id))->delete();
            if($res){
                if ($info['fid']) {
                    // 添加讨论数
                    $database_activity_offline_reply->where(array('id' => intval($info['fid'])))->setDec('reply_num');
                }
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error('参数传递错误'); 
        }
    }

    //协议配置
    public function config(){
        redirect(U('Config/index',array('galias'=>'activity_offline','header'=>'ActivityOffline/config')));
    }
}