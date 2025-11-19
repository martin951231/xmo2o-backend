<?php
/*
 * 推广营销 - 平台活动管理
 *
 * @  Writers    Jaty
 * @  BuildTime  2015/06/15 14:25
 * 
 */
class PlatformAction extends BaseAction{
    //平台活动管理首页
    public function manage(){
        $config_status = M('Config')->where(array('name'=>'is_open_activity'))->getField('status');
        if(!$config_status){
            $this->error('您未开启平台活动,请到系统设置->站点配置里打开平台活动按钮~');
        }
        $platform_model = M('Platform_cate');
        if($_GET['province_idss']){
            $where['province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['city_id'] = $_GET['city_idss'];
        }
        $where['fid'] = 0;
        $count = $platform_model->where($where)->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $platform_model->field(true)->where($where)->order('sort DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $area_model = M('Area');
        foreach($list as $key=>$value){
            $list[$key]['province_name'] = $area_model->where(array('area_id'=>$value['province_id']))->getField('area_name');
            $list[$key]['city_name'] = $area_model->where(array('area_id'=>$value['city_id']))->getField('area_name');
        }
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
        $platform_cate = M('Platform_cate');
        $info = $platform_cate->where(array('id'=>$id))->find();

        if (empty($info)) {
            $this->error('该分类不存在或者已经删除');
        }
        if ($info['status'] == 1) {
            $this->error('该分类已经关闭');
        }
        // 确认存在未关闭的正常该信息 做关闭处理
        $close1 = $platform_cate->where(array('id'=>$id))->data(array('status' => 1))->save();
        $close2 = $platform_cate->where(array('fid'=>$id))->data(array('status' => 1))->save();
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
        $platform_cate = M('Platform_cate');
        $info = $platform_cate->where(array('id'=>$id))->find();
        if($_POST['is_second']){
            $pid = $platform_cate->where(array('id'=>$info['fid']))->getField('status');
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
        $open1 = $platform_cate->where(array('id'=>$id))->data(array('status' => 0))->save();
        $open2 = $platform_cate->where(array('fid'=>$id))->data(array('status' => 0))->save();
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
        $platform_cate = M('Platform_cate');
        $info = $platform_cate->where(array('id'=>$id))->find();
        if (empty($info)) {
            $this->error('该分类不存在或者已经删除');
        }
        if($id){
            $res = $platform_cate->where(array('id'=>$id))->delete();
            if($res){
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error_tips('参数传递错误'); 
        }
    }

    public function edit(){
        $platform_cate = M('Platform_cate');
        $is_classfiy = $_GET['is_classfiy'];
        if(IS_POST){
            $data['id'] = intval($_POST['id']);
            $data['province_id'] = intval($_POST['province_idss']);
            $data['city_id'] = intval($_POST['city_idss']);
            $data['cate_name'] = htmlspecialchars($_POST['cate_name']);
            $data['sort'] = intval($_POST['sort']);
            $data['status'] = intval($_POST['status']);
            if($data['id']){
                $res1 = $platform_cate->data($data)->save();
                $data_result = $platform_cate->field(true)->where(array('fid'=>$data['id']))->find();
                if($data_result){
                    $res2 = $platform_cate->data(array('province_id'=>intval($_POST['province_idss']),'city_id'=>intval($_POST['city_idss'])))->where(array('fid'=>$data['id']))->save();//同步子分类更新
                }
                if($res1&&$res2){
                    $this->frame_submit_tips(1,'操作成功！');
                }else{
                    $this->frame_submit_tips(0,'操作失败！请重试~');
                }
            }else{
                $this->frame_submit_tips(0,'缺少数据~请重试!');
            }
        }else{
            $id = $_GET['id'];
            $list= $platform_cate->where(array('id'=>$id))->find();
            $list['f_cate_name'] = $platform_cate->where(array('id'=>$list['fid']))->getField('cate_name');
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
            $data['province_id'] = intval($_POST['province_idss']);
            $data['city_id'] = intval($_POST['city_idss']);
            if(!$data['city_id']){
                $this->frame_submit_tips(0,'当前城市不能选全部市~');
            }
            $data['cate_name'] = htmlspecialchars($_POST['cate_name']);
            if(!$data['cate_name']){
                $this->error('分类名称不能为空!');
            }
            $data['sort'] = intval($_POST['sort']) ? intval($_POST['sort']) : 0;
            $data['status'] = intval($_POST['status']) ? intval($_POST['status']) : 0;
            $data['fid'] = intval($_POST['fid']) ? intval($_POST['fid']) : 0;
            $data['add_time'] = time();
            if($data){
                $res = M('Platform_cate')->add($data);
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
        $platform_model = M('Platform_cate');
        $id = $_GET['id'];
        $where = array();
        if(!$id){
            $this->error('参数传递错误!');
        }else{
            $where['fid'] = $id;
        }

        $count = $platform_model->where($where)->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $platform_model->field(true)->where($where)->order('sort DESC')->limit($page->firstRow.','.$page->listRows)->select();
        $area_model = M('Area');
        foreach($list as $key=>$value){
            $list[$key]['province_name'] = $area_model->where(array('area_id'=>$value['province_id']))->getField('area_name');
            $list[$key]['city_name'] = $area_model->where(array('area_id'=>$value['city_id']))->getField('area_name');
            $list[$key]['f_cate_name'] = $platform_model->where(array('id'=>$value['fid']))->getField('cate_name');
            $area['province_id'] = $value['province_id'];
            $area['city_id'] = $value['city_id'];
            $area['id'] = $value['fid'];
        }
        if(!$area){
            $res = $platform_model->field(true)->where(array('id'=>$id))->find();
            $area['province_id'] = $res['province_id'];
            $area['city_id'] = $res['city_id'];
            $area['id'] = $res['id'];
        }
        $pagebar = $page->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('list',$list);
        $this->assign('area',$area);
        $this->display();
    }

    //活动列表
    public function activity_list(){
        $activity_model =M('Activity');
        $where=array();
        if($_GET['province_idss']){
            $where['a.province_id'] = $_GET['province_idss'];
        }
        if($_GET['city_idss']){
            $where['a.city_id'] = $_GET['city_idss'];
        }
        $where['a.is_del'] = 0;
        $count = $activity_model->alias('a')->where($where)->join('join pigcms_platform_cate as b on a.cate_id=b.id')->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $activity_model->alias('a')->field('a.*,b.cate_name,c.nickname,c.phone as login_phone')->where($where)->join('join pigcms_platform_cate as b on a.cate_id=b.id')->join('join pigcms_user as c on a.uid=c.uid')->limit($page->firstRow.','.$page->listRows)->order('a.is_top desc,a.add_time desc')->select();
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
        $activity_model = M('Activity');
        $info = $activity_model->where(array('activity_id'=>$activity_id))->find();
        if (empty($info)) {
            $this->error('该活动不存在或者已经删除');
        }
        if($activity_id){

            $res = $activity_model->where(array('activity_id'=>$activity_id))->delete();
            if($res){
                $this->success('操作成功！');
            }else{
                $this->error('操作失败！请重试~');
            }
        }else{
            $this->error_tips('参数传递错误');
        }
    }


    //查看图片详情
    public function imgdetail(){
        $id = $_GET['id'];
        if(!$id){
            $this->frame_submit_tips(0,'操作失败！请重试~');
        }else{
            $imgs = M('Classify_manager')->field(true)->where(array('id'=>$id))->find();
            $imgs['face_photo'] = replace_file_domain($imgs['face_photo']);
            $imgs['back_photo'] = replace_file_domain($imgs['back_photo']);
            $imgs['hand_photo'] = replace_file_domain($imgs['hand_photo']);
            $this->assign('imgs',$imgs);
        }
        $this->display();
    }

    //查看单个信息详情
    public function detail(){
        $id = $_GET['id'];
        if(!$id){
            $this->frame_submit_tips(0,'操作失败！请重试~');
        }else{
            $imgs = M('Classify_manager')->field(true)->where(array('id'=>$id))->find();
            $imgs['face_photo'] = replace_file_domain($imgs['face_photo']);
            $imgs['back_photo'] = replace_file_domain($imgs['back_photo']);
            $imgs['hand_photo'] = replace_file_domain($imgs['hand_photo']);
            $area_model = M('Area');
            $cate_model = M('Platform_cate');
            $imgs['province_name'] = $area_model->where(array('area_id'=>$imgs['province_id']))->getField('area_name'); 
            $imgs['city_name'] = $area_model->where(array('area_id'=>$imgs['city_id']))->getField('area_name'); 
            $imgs['cate_name'] = $cate_model->where(array('id'=>$imgs['cate_id']))->getField('cate_name');
            $this->assign('imgs',$imgs);
        }
        $this->display();
    }

    /**
     * [checkList 分类管理员审核列表]
     * @return [type] [description]
     */
    public function checkList(){
        $where=array();
        $where['status'] = 0;
        $where['is_del'] = 0;
        $manager_model = M('Classify_manager');
        $count = $manager_model->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $list = $manager_model->field(true)->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $area_model = M('Area');
        $cate_model = M('Platform_cate');
        foreach($list as $key=>$value){
            $list[$key]['province_name'] = $area_model->where(array('area_id'=>$value['province_id']))->getField('area_name'); 
            $list[$key]['city_name'] = $area_model->where(array('area_id'=>$value['city_id']))->getField('area_name'); 
            $list[$key]['cate_name'] = $cate_model->where(array('id'=>$value['cate_id']))->getField('cate_name'); 
            $list[$key]['id_card'] = substr_replace($list[$key]['id_card'],'********',6,8);
        }
        $this->assign('list',$list);
        $this->display();
    }

    public function hasCheckList(){
        $where=array();
        $where['status'] = 1;
        $where['is_del'] = 0;
        $manager_model = M('Classify_manager');
        $count = $manager_model->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $list = $manager_model->field(true)->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $area_model = M('Area');
        $cate_model = M('Platform_cate');
        foreach($list as $key=>$value){
            $list[$key]['province_name'] = $area_model->where(array('area_id'=>$value['province_id']))->getField('area_name'); 
            $list[$key]['city_name'] = $area_model->where(array('area_id'=>$value['city_id']))->getField('area_name'); 
            $list[$key]['cate_name'] = $cate_model->where(array('id'=>$value['cate_id']))->getField('cate_name');
            $list[$key]['id_card'] = substr_replace($list[$key]['id_card'],'********',6,8);
        }
        $this->assign('list',$list);
        $this->display();
    }

    public function verifyManage(){
        $id = intval($_POST['id']);
        if ($id > 0) {
            $Classify_manager = M('Classify_manager');
            $info = $Classify_manager->where(array('id'=>$id))->find();
            $res = $Classify_manager->where(array('id' => $id))->save(array('status' => 1));
            if ($res) {
                if($id){
                    $data['province_id'] = $info['province_id'];
                    $data['city_id'] = $info['city_id'];
                    $data['cate_id'] = $info['cate_id'];
                    $data['uid'] = $info['uid'];
                    $data['add_time'] = time();
                    $result = M('Manager_area')->add($data);
                    if($result){
                        $this->success(1,'操作成功！');
                    }else{
                        $this->error(0,'操作失败！');
                    }

                }else{
                    $this->error(0,'查询管理员信息失败!');
                }
            } else {
                $this->error(0,'操作失败！');
            }
        }

    }

    /*     * **打入未审核*** */

    public function toVerifyManage() {
        $id = intval($_POST['id']);
        if ($id > 0) {
            M('Classify_manager')->where(array('id' => $id))->save(array('status' => 0));
            echo 0;
            exit;
        }
        
    }
    public function leaveMessage(){
        $id = intval($_POST['id']);
        if ($id > 0) {
            $data['status'] = 2;
            $data['message'] = $_POST['message'];
            $res = M('Classify_manager')->where(array('id' => $id))->save($data);
            if ($res) {
                $this->success('操作成功,留言已提交!');
            } else {
                $this->error(0,'操作失败！请重试~');
            }
        }
    }

    /*     * **删除此项*** */
    public function delItem() {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $tmp = M('Classify_manager')->where(array('id' => $id))->data(array('is_del'=>1))->save();
            if ($tmp) {
                $this->success(1,'操作成功！');
            } else {
                $this->error(0,'操作失败！请重试~');
            }
        }
    }

    public function activity_join(){
        $activity_model = M('Activity_join');
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
        $count = $activity_model->where($where)->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $activity_model
        ->alias('a')
        ->field('a.*,b.title,c.level,d.lname')
        ->where($where)->order('a.add_time DESC')
        ->join('join pigcms_activity as b on a.activity_id=b.activity_id')
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
                $res = M('Activity_join')->where(array('join_id'=>$data['join_id']))->save($data);
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
            $list = M('Activity_join')->alias('a')->field('a.*,b.title')->where(array('a.join_id'=>$id))->join('join pigcms_activity as b on a.activity_id=b.activity_id')->find();
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

        $activity_info = D('Activity')->where(array('activity_id' => $activity_id,'is_del'=>0))->find();
        if (empty($activity_info)) {
            $this->frame_submit_tips(0,'没有查询到数据!');
        }

        // 查询一下选项信息
        $activity_option = D('Activity_option')->field('title')->where(array('activity_id' => $activity_id, 'status' => 1))->select();
        $where = array(
            'a.activity_id' => $activity_id,
            'a.status' => array('in',array(1,2,4,5,6)),
        );
        $activity_join = D('Activity_join')
        ->alias('a')
        ->field('a.*,b.order_id,d.lname')
        ->where($where)
        ->join('left join pigcms_activity_order as b on a.activity_id=b.activity_id and b.uid != '.$activity_info['uid'])
        ->join('join pigcms_user as c on a.uid=c.uid')
        ->join('left join pigcms_user_level as d on c.level=d.level')
        ->order('a.add_time ASC')
        ->select();

        $join_number = count($activity_join);
        if(count($activity_join) <= 0){
            $this->frame_submit_tips(0,'数据数量错误!');
        }
        // 全部收款
        $total_money =  D('Activity_join')->alias('a')->where($where)->sum('money');

        // 实际收款
        $where['status'] = array('in',array(1,2,5));
        $real_money =  D('Activity_join')->alias('a')->where($where)->sum('money');

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
        $activity_model = M('Activity_reply');
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
        $count = $activity_model->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity as c on a.activity_id=c.activity_id')->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $activity_model->alias('a')->field('a.*,b.nickname,c.title')->where($where)->order('a.add_time DESC')->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity as c on a.activity_id=c.activity_id')->limit($page->firstRow.','.$page->listRows)->select();
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
        $activity_model = M('Activity_reply');
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
        $count = $activity_model->alias('a')->where($where)->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity as c on a.activity_id=c.activity_id')->count();
        import('@.ORG.system_page');
        $page = new Page($count, 15);
        $list = $activity_model->alias('a')->field('a.*,b.nickname,c.title')->where($where)->order('a.add_time DESC')->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity as c on a.activity_id=c.activity_id')->limit($page->firstRow.','.$page->listRows)->select();
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
            $imgs = M('Activity_reply')->field(true)->where(array('id'=>$id))->find();
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
        $activity_modle = M('Activity_reply');
        $info = $activity_modle->where(array('id'=>$id))->find();
        if (empty($info)) {
            $this->error('该评论不存在或者已经删除');
        }
        if($id){
            $res = $activity_modle->where(array('id'=>$id))->delete();
            if($res){
                if ($info['fid']) {
                    // 添加讨论数
                    $activity_modle->where(array('id' => intval($info['fid'])))->setDec('reply_num');
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
    public function platform_config(){
        $agreement_model = M('Activity_config');
        if(IS_POST){
            $database_config = D('Activity_config');
            foreach($_POST as $key=>$value){
                $where = array('alias_info'=>$key);
                $data = array(
                            'content'=>$value,
                            'update_time'=>time()
                        );
                if($key){
                    $database_config->where($where)->data($data)->save();
                }

            }
            $this->success('修改成功!');
        }else{
            $agreement = $agreement_model->field(true)->order('sort desc')->select();
            $config_info = array();
            if (!empty($agreement)) {
                foreach ($agreement as $val) {
                    $config_info[$val['alias_info']] = $val;
                }
                $this->assign('config_info',$config_info);
            }


            $this->assign('agreement',$agreement);
            $this->display();
        }
    }

    //退款审核列表
    public function examine_refundList(){
        $where=array();
        $where['a.status'] = 1;
        $where['a.reject_times'] = 2;
        $where['c.status'] = array('not in',array(0,3));
        $refund_model = M('Activity_refund_examine');
        $count = $refund_model->alias('a')->join('join pigcms_user as b on a.uid=b.uid')->where($where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, 20);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $list = $refund_model->alias('a')->field('a.*,b.nickname,b.phone,c.money')->join('join pigcms_user as b on a.uid=b.uid')->join('join pigcms_activity_order as c on a.order_id=c.order_id')->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('list',$list);
        $this->display();
    }   
    /*     * **删除此项*** */
    public function del_refund() {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $tmp = M('Activity_refund_examine')->where(array('id' => $id))->delete();
            if ($tmp) {
                $this->success(1,'操作成功！');
            } else {
                $this->error(0,'操作失败！请重试~');
            }
        }else{
            $this->error('参数传递错误'); 
        }
    }

    public function verifyRefund(){
        $id = intval($_POST['id']);
        $status = intval($_POST['status']);
        if(!in_array($status,array(1,2,3))){
            $this->error('状态参数错误'); 
        }
        if ($id > 0) {
            $res = M('Activity_refund_examine')->where(array('id' => $id))->save(array('status' => $status));
            if ($res) {
                $this->success('审核成功！');
            } else {
                $this->error(0,'操作失败！请重试~');
            }
        }else{
            $this->error('参数传递错误'); 
        }
    }

    //退款
    public function mobile_refund(){
        $activity_order = D('Activity_order');
        $activity_join = D('Activity_join');
        $activity_refund = D('Activity_refund_examine');
        $plat_order = D('Plat_order');
        $order_id = $_POST['order_id'];
        $id = $_POST['id'];
        $join_id = $_POST['join_id'];
        $refund_money = $_POST['refund_money'];
        if(IS_POST){
            $now_order =  $activity_order->field(true)->where(array('order_id'=>$order_id))->find();
            $activity_info = M('Activity')->field(true)->where(array('activity_id'=>$now_order['activity_id']))->find();
            if($now_order['uid'] == $activity_info['uid']){
                $can_refund_where['status'] = 1;
                $can_refund_where['activity_id'] = $now_order['activity_id'];
                $can_refund = $activity_join->field(true)->where($can_refund_where)->select();
                if($can_refund){//存在报名成功的用户
                    $this->error(0,'存在报名成功且未退款的用户,暂不能退款!');//退款失败
                }
            }
            if($now_order['status'] ==3 ){
                $this->error(0,'该订单已经退款!');//退款失败
            }
            if($refund_money > $now_order['money']){
                $this->error(0,'退款金额不能大于报名金额');
            }

            $refund  = $plat_order->order_refund(array('business_type'=>'activity','business_id'=>$now_order['order_id']),'',$refund_money);

            if(!$refund['error']){
                $activity_order->startTrans();
                $res1 = $activity_order->where(array('order_id'=>$order_id))->data(array('status'=>3))->save();
                if($res1){
                    $activity_order->commit();
                }else{
                    $activity_order->rollback();
                    $this->error(0,'修改订单状态失败!');//退款失败
                }
                $activity_refund->startTrans();
                $res2 = $activity_refund->where(array('id'=>$id))->data(array('status'=>3))->save();
                if($res2){
                    $activity_refund->commit();
                }else{
                    $activity_refund->rollback();
                    $this->error(0,'修改退款状态失败!');//退款失败
                }
                if($join_id == 'is_owner'){
                    $res3 = true;
                }else{
                    $activity_join->startTrans();
                    $res3 = $activity_join->where(array('join_id'=>$join_id))->data(array('status'=>4))->save();
                    if($res3){
                        $activity_join->commit();
                    }else{
                        $activity_join->rollback();
                        $this->error(0,'修改报名状态失败!');//退款失败
                    }
                }

                if($res1 && $res2 && $res3){
                    $this->success(1,'退款成功!');//退款成功
                }else{
                    $this->error(1,'退款失败!');//退款失败
                }
            }else{
                $this->error(0,'退款失败!');//退款失败
            }
        }else{
            $this->error(0,'退款失败!');
        }
    }
    /**
     * 关闭分类管理员
     */
    public function close_manager(){
        $id = intval($_POST['id']);
        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $Classify_manager = M('Classify_manager');
        $info = $Classify_manager->where(array('id'=>$id))->find();
        $activity_info = M('Manager_area')->field('cate_id')->where(array('uid'=>$info['uid']))->select();
        foreach($activity_info as $key=>$value){
            $ids[] = $value['cate_id'];
        }
        $where['examine_status'] =1;
        $where['cate_id'] =array('in',$ids);
        $res = M('Activity')->where($where)->find();
        if($res){
            $this->error('该分类管理员下有未审核的活动,不能关闭!');
        }
        if (empty($info)) {
            $this->error('该分类管理员不存在或者已经删除');
        }
        if ($info['open_close'] == 2) {
            $this->error('该分类管理员已经关闭');
        }
        // 确认存在未关闭的正常该信息 做关闭处理
        $close = $Classify_manager->where(array('id'=>$id))->data(array('open_close' => 2))->save();
        if($close){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }
    /**
     * 开启分类管理员
     */
    public function open_manager(){
        $id = intval($_POST['id']);
        // 查询单条信息-确认是否存在该信息-并判断是否已经关闭
        $Classify_manager = M('Classify_manager');
        $info = $Classify_manager->where(array('id'=>$id))->find();

        if (empty($info)) {
            $this->error('该分类管理员不存在或者已经删除');
        }
        if ($info['open_close'] == 1) {
            $this->error('该分类管理员已经开启');
        }
        // 确认存在未关闭的正常该信息 做关闭处理
        $open = $Classify_manager->where(array('id'=>$id))->data(array('open_close' => 1))->save();
        if($open){
            $this->success('操作成功！');
        }else{
            $this->error('操作失败！请重试~');
        }
    }

    //增加分类管理员权限
    public function powerdetail(){
        if(IS_POST){
            if($_POST['dosubmit']){
                $cate_ids = $_POST['cate_id'];
                $arr = array();
                $platform_cate = M('Platform_cate');
                $where['id'] = array('in',$_POST['cate_id']);
                $arr = $platform_cate->field('id,cate_name,city_id,province_id')->where($where)->select();
                foreach ($arr as $key => $value) {
                    $data[$key]['uid'] = $_POST['uid'];
                    $data[$key]['province_id'] = $value['province_id'];
                    $data[$key]['city_id'] = $value['city_id'];
                    $data[$key]['cate_id'] = $value['id'];
                    $data[$key]['add_time'] = time();
                }
                $info = M('Manager_area')->where(array('uid'=>$_POST['uid']))->find();
                if($info){
                    $delete_info = M('Manager_area')->where(array('uid'=>$_POST['uid']))->delete();
                }else{
                    $delete_info = true;
                }
                if($delete_info){
                    $result = M('Manager_area')->addAll($data);
                    if($result){
                        $this->frame_submit_tips(1,'操作成功!~');
                    }else{
                        $this->frame_submit_tips(0,'操作失败,请重试!~');
                    }
                }else{
                        $this->frame_submit_tips(0,'重置权限信息失败!~');
                }

            }
        }else{
            $area_where['area_type'] = array('in',array(1));
            $area_where['is_open'] = 1;
            $area_list = M('Area')->field(true)->where($area_where)->select();
            $where['fid'] = array('neq',0);
            $where['status'] = 0;
            $two_category = M('Platform_cate')->field(true)->where($where)->select();
            $platform_cate_list = M('Platform_cate')->field(true)->where(array('status'=>0))->select();

            foreach($area_list as $key =>$value){
                $son_list = M('Area')->field(true)->where(array('is_open'=>1,'area_pid'=>$value['area_id']))->select();
                $area_list[$key]['son_list'] = $son_list;
            }
            $manager_cate_list = M('Manager_area')->field('cate_id')->where(array('uid'=>$_GET['uid']))->select();
            foreach($manager_cate_list as $key=>$value){
                $ids[] = $value['cate_id'];
            }
            $this->assign('area_list',$area_list);
            $this->assign('platform_cate_list',$platform_cate_list);
            $this->assign('two_category',$two_category);
            $this->assign('ids',$ids);
        }
        $this->display();
    }

}