<?php
/**
 * 出入证管理
 * Class PassAction
 */

class PassAction extends BaseAction
{
    protected $village_id;

    public function _initialize(){
        parent::_initialize();
        $this->village_id = $this->house_session['village_id'];
    }
    //出入证列表
    public function pass_list()
    {
        //访客登记-查看 权限
        if (!in_array(1015, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $list = D('House_village_pass')->getPassList($this->village_id);
//        dump($list);die;
        if(!$list){
            $this->error('数据处理有误！');
        }else{
            $this->assign('list',$list);
        }
        $this->display();
    }
    // 二维码
    public function get_own_qrcode(){
        header('Content-Type: image/png');
        ob_clean();
        $qrCon = $_GET['qrCon'];
        import('@.ORG.phpqrcode');
        $size = $_GET['size'] ? $_GET['size']: 10;
        $is_down = $_GET['down'] ? true: false;

        QRcode::png(htmlspecialchars_decode(urldecode($qrCon)),false,0,$size,1,$is_down);
    }
    //添加
    public function pass_add()
    {
        if(IS_POST){
            $code_num = I('num',0);
            if(!$code_num){
                $this->error('请填写数量');
            }
            if($code_num>100){
                $this->error('数量不得大于100');
            }
            $res = D('House_village_pass')->addPass($code_num,$this->village_id);
            if($res){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }

        }else{
            $this->display();
        }
    }
    //编辑
    public function pass_edit()
    {
        $pass_id = I('pass_id');
        if(IS_POST){
            $data['status'] = I('status');
            $res = D('House_village_pass')->edit($pass_id,$data);
            if($res){
                $this->success('编辑成功');
            }else{
                $this->error('编辑失败');
            }
        }else{
            $where['pass_id'] = $pass_id;
            $info = D('House_village_pass')->getInfo($where);
            $this->assign('info',$info);
            $this->display();
        }
    }
    //绑定成员
    public function pass_bind_member()
    {
        $pass_id = I('pass_id');
        if(IS_POST){
            $post = I('post.');
            if(!$post['code_num'] || !$post['come_name'] || !$post['come_phone'] || !$post['start_time'] || !$post['due_time'])
            {
                $this->error('必传参数异常');
            }
            if(strtotime($post['start_time'])<strtotime(date('Y-m-d',time()))){
                $this->error('出入开始时间不得小于当前时间');
            }
            if(strtotime($post['due_time'].' 23:59:59')<time()){
                $this->error('出入到期时间不得小于当前时间');
            }
            if(strtotime($post['start_time']) > strtotime($post['due_time'].' 23:59:59')){
                $this->error('出入开始时间不得小于出入到期时间');
            }
            $open_door_type = D('House_village_about_config')->where(['village_id' => $this->village_id])->getField('open_door_type');
            if($post['open_status'] == 0 )
            {
                if(!$post['guard']){
                    $this->error('必传参数异常');
                }
                //1人脸开门 2远程开门
                if($open_door_type == 1 && !$post['face_id']){
                    $this->error('必传参数异常');
                }
            }
            $bind_where['village_id'] = $this->village_id;
            $bind_where['come_phone'] = $post['come_phone'];
            $bind_where['due_time'] = ['gt',time()];
            $bind_where['status'] = ['in',[0,1,3]];

            $bind_pass_info = D('House_village_pass_bind_member')->where($bind_where)->find();
            if($bind_pass_info){
                $this->error('该用户当前使用的出入码未到期');
            }
            $res = D('House_village_pass_bind_member')->addBindMember($post,$this->village_id);
            if($res){
                $this->success('绑定成功');
            }else{
                $this->error('绑定失败');
            }
        }else{
            $village_id = $this->village_id;
            $guard = $this->entrance_guard($village_id);
            $this->assign('guard',$guard);
            $where['pass_id'] = $pass_id;
            $info = D('House_village_pass')->getInfo($where);
            $this->assign('info',$info);
            $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
            $this->assign('open_door_type',$open_door_type);
            $this->display();
        }
    }
    //处理门禁 获取门禁所在的楼栋单元
    public function entrance_guard($village_id)
    {
        //蓝牙门禁
        $door_info = D('House_village_door')->where(['village_id'=>$village_id,'door_status'=>1])->field('door_id,floor_id,public_area_id')->select();
        $public_area_ids = [];
        $floor_ids = [];
        foreach ($door_info as $key=>$val){
            if($val['floor_id'] == '-1'){
                $public_area_ids[] = $val['public_area_id'];
            }else{
                $floor_ids[] = $val['floor_id'];
            }
        }
        //人脸门禁
        $device_info = D('House_face_device')->where(['village_id'=>$village_id,'device_status'=>1,'is_del'=>0])->field('floor_id,public_area_id')->select();
        $public_area_id = [];
        $floor_id = [];
        foreach ($device_info as $key=>$val){
            if($val['floor_id'] == '-1'){
                $public_area_id[] = $val['public_area_id'];
            }else{
                $floor_id[] = $val['floor_id'];
            }
        }
        $public_area_id = array_merge($public_area_ids,$public_area_id);
        $floor_id = array_merge($floor_ids,$floor_id);

        $new_public=[];
        //公共区域
        if($public_area_id) {
            $public_area_id = array_unique($public_area_id);
            $map['public_area_id'] = ['in',$public_area_id];
            $map['village_id']=$village_id;
            $map['status'] = 1;
            $public_guard = D('House_village_public_area')->where($map)->field('public_area_id,public_area_name')->select();
            foreach ($public_guard as $key=>$val){
                $new_public[] =[
                    'id'=>'0-'.$val['public_area_id'],
                    'title'=>'公共区域'.'-'.$val['public_area_name'],
                ];
            }
        }
        $new_floor = [];
        //指定 楼栋 单元
        if ($floor_id) {
            $floor_id = implode(',', $floor_id);
            $floor_id = explode(',', $floor_id);
            $floor_id = array_unique($floor_id);

            $where['f.floor_id'] = ['in',$floor_id];
            $where['f.status'] = 1;
            $where['s.status'] = 1;
            $left_join = "as f left join ".C('DB_PREFIX').'house_village_single as s on `f`.`single_id`=`s`.`id`';
            $floor_guard = D('House_village_floor')->join($left_join)->where($where)->field('f.floor_id,f.floor_name,f.single_id,s.single_name')->select();
            foreach ($floor_guard as $key=>$val){
                $new_floor[] = [
                    'id'=>$val['single_id'].'-'.$val['floor_id'],
                    'title'=>$val['single_name'].'-'.$val['floor_name'],
                ];
            }
        }
        $guard = array_merge($new_public,$new_floor);
        return $guard;
    }
    //添加人脸
    public function faces()
    {
        $pigcmsId = I('id','', 'intval');
        if ($pigcmsId) {
            $msg = D('Face_door_service')->get_bind_user_device_img($pigcmsId);
            $info = D('House_village_user_bind')->where(['pigcms_id'=>$pigcmsId])->field('face_img_status,face_img_reason')->find();
            $img_id = array_column($msg['img_info'],'id');
            $img_id = implode(',',$img_id);
        } else {
            $msg = [];
            $info = [];
            $img_id ='';
        }
        $this->assign('img_id',$img_id);
        $this->assign('info',$info);
        $this->assign('dataImg',$msg['img_info']);
        $this->assign('pigcms_id',$pigcmsId);
        $this->display();
    }
    //摄像头拍照页面
    public function cap()
    {
        $pigcmsId = I('pigcms_id','', 'intval');
        $this->assign('pigcms_id',$pigcmsId);
        $this->display();
    }
    //上传图片
    public function add_face_img()
    {
        if(IS_POST){
            //base64上传
            if(isset($_POST['base'])){
                $img_base64 = $_POST['base'];
                $url = $this->base64_to_img(str_replace('data:image/png;base64,', '', $img_base64));
                $imgUrl = D('Image')->transform_image($url);
                fdump_api([$url, $imgUrl],'$add_face_img1');
                $id = $this->add_face($imgUrl);
                exit(json_encode(array('error'=>2,'msg'=>'上传成功','data'=>['url'=>$imgUrl,'id'=>$id])));
            }
            //上传图片
            $image = D('Image')->handle(round(9), 'houseface', 0, array('size' => 10));
            if (!$image['error']) {
                if (!isset($image['extension']) || (!in_array($image['extension'], ['jpg', 'jpeg']) && !in_array($image['type'], ['image/jpg', 'image/jpeg']))) {
                    $imgUrl = D('Image')->transform_image($image['url']['file']);
                }
                if(!isset($imgUrl) || !$imgUrl) {
                    $imgUrl = $image['url']['file'];
                }
                fdump_api([$image, $imgUrl],'$add_face_img2');
                $id = $this->add_face($imgUrl);
                exit(json_encode(array('error'=>2,'msg'=>'上传成功','data'=>['url'=>$imgUrl,'id'=>$id,'pigcms_id'=>$_POST['pigcms_id']])));
            } else {
                exit(json_encode(array('error'=>1,'msg'=>'上传失败，请重试！')));
            }
        }else{
            exit(json_encode(array('error'=>1,'msg'=>'上传失败，请重试！')));
        }
    }
    //添加人脸图片
    public function  add_face($img_url)
    {
        $face_img = D('House_face_img');
        if ($img_url) {
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            $img_url = $word_encryption->text_encryption($img_url);
            $data['status'] = 3;
        } else {
            $data['status'] = 0;
        }
        $data['img_url'] = $img_url;
        $data['add_time'] = time();
        $id = $face_img->add_face_img($data);
        return $id;
    }

    /**
     * 删除图片
     */
    public function house_face_img_del()
    {
        $id = I('id',0);
        if(empty($id))
            exit(json_encode(array('error'=>1,'msg'=>'缺少必传参数！')));
        $res = D('house_face_img')->where(['id'=>$id])->save(['status'=>1]);
        if($res)
            exit(json_encode(array('error'=>2,'msg'=>'删除成功')));
        else
            exit(json_encode(array('error'=>1,'msg'=>'系统异常！')));
    }
    //
    private function base64_to_img($base_img) {
        $img_mer_id = sprintf("%09d", round(9));
        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $up_dir = './upload/houseface/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'png';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = trim($new_file,'.');
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
    //出入记录
    public function record_list()
    {
        $pass_id = I('pass_id');
        if(!$pass_id){
            $this->error('必传参数不能为空');
        }
        $list = D('House_village_pass_bind_member')->getOpenDoorRecord($pass_id,$this->village_id);
        $this->assign('list',$list);
        $this->display();
    }
    //人员列表
    public function pass_member()
    {
        //访客登记-查看 权限
        if (!in_array(1015, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $keyword = I('get.');
        if($keyword){
            if($keyword['find_type'] == 1 && $keyword['find_value']){
                $where['m.come_name'] = ['like','%'.$keyword['find_value'].'%'];
            }
            if($keyword['find_type'] == 2 && $keyword['find_value']){
                $where['m.come_phone'] = ['like','%'.$keyword['find_value'].'%'];
            }
            if($keyword['status'] != 4){
                switch ($keyword['status']){
                    case '1':
                        $where['m.status'] = 1;
                        break;
                    case '2':
                        $where['m.status'] = 0;
                        break;
                    case '3':
                        $where['m.status'] = 2;
                        break;
                }
            }else{
                $where['m.status'] = ['neq','-1'];
            }
        }else{
            $where['m.status'] = 1;
        }
        $where['m.village_id'] = $this->village_id;
        $list = D('House_village_pass_bind_member')->passMemberList($where);
        if(!$list){
            $this->error('数据处理有误！');
        }else{
            $this->assign('list',$list);
        }
        $this->display();
    }
    //添加成员
    public function member_add()
    {
        $village_id = $this->village_id;
        if(IS_POST){
            $post = I('post.');
            if(!$post['code_num'] || !$post['come_name'] || !$post['come_phone'] || !$post['start_time'] || !$post['due_time'])
            {
                $this->error('必传参数异常');
            }
            if(strtotime($post['start_time'])<strtotime(date('Y-m-d',time()))){
                $this->error('出入开始时间不得小于当前时间');
            }
            if(strtotime($post['due_time'].' 23:59:59')<time()){
                $this->error('出入到期时间不得小于当前时间');
            }
            if(strtotime($post['start_time']) > strtotime($post['due_time'].' 23:59:59')){
                $this->error('出入开始时间不得小于出入到期时间');
            }
            $open_door_type = D('House_village_about_config')->where(['village_id' => $this->village_id])->getField('open_door_type');
            if($post['open_status'] == 0 )
            {
                if(!$post['guard']){
                    $this->error('必传参数异常');
                }
                //1人脸开门 2远程开门
                if($open_door_type == 1 && !$post['face_id']){
                    $this->error('必传参数异常');
                }
            }
            $bind_where['village_id'] = $this->village_id;
            $bind_where['come_phone'] = $post['come_phone'];
            $bind_where['due_time'] = ['gt',time()];
            $bind_pass_info = D('House_village_pass_bind_member')->where($bind_where)->find();
            if($bind_pass_info){
                $this->error('该用户当前使用的出入码未到期');
            }
            $res = D('House_village_pass_bind_member')->addBindMember($post,$this->village_id);
            if($res){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $where=['status'=>0,'use_status'=>0,'village_id'=>$village_id];
            $pass_list = D('House_village_pass')->where($where)->field('pass_id,code_num')->select();
            $guard = $this->entrance_guard($village_id);
            $this->assign('guard',$guard);
            $this->assign('pass_list',$pass_list);
            $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
            $this->assign('open_door_type',$open_door_type);
            $this->display();
        }
    }
    //编辑人员
    public function member_edit()
    {
        $village_id = $this->village_id;
        $id = I('id');
        if(!$id){
            $this->error('参数异常');
        }
        if(IS_POST){
            $post = I('post.');
            if(!$post['code_num'] || !$post['come_name'] || !$post['come_phone'] || !$post['start_time'] || !$post['due_time'])
            {
                $this->error('必传参数异常');
            }
//            if(strtotime($post['start_time'])<time()){
//                $this->error('出入开始时间不得小于当前时间');
//            }
            if(strtotime($post['due_time'].' 23:59:59')<time()){
                $this->error('出入到期时间不得小于当前时间');
            }
            if(strtotime($post['start_time']) > strtotime($post['due_time'].' 23:59:59')){
                $this->error('出入开始时间不得小于出入到期时间');
            }
            $open_door_type = D('House_village_about_config')->where(['village_id' => $this->village_id])->getField('open_door_type');
            if($post['open_status'] == 0 )
            {
                if(!$post['guard']){
                    $this->error('必传参数异常');
                }
                //1人脸开门 2远程开门
                if($open_door_type == 1 && !$post['face_id']){
                    $this->error('必传参数异常');
                }
            }
            $res = D('House_village_pass_bind_member')->addBindMember($post,$village_id,$id);
            if($res){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $where['id'] = $id;
            $member_info = D('House_village_pass_bind_member')->getMemberInfo($where);
            $member_info['start_time'] = date('Y-m-d',$member_info['start_time']);
            $member_info['due_time'] = date('Y-m-d',$member_info['due_time']);
            $member_info['guard'] = explode(',',$member_info['guard']);

            $guard = $this->entrance_guard($village_id);
            $buser_where['uid'] = $member_info['uid'];
            $buser_where['status'] = ['neq',1];
//            $buser_where['del_time'] = ['eq',null];
            $face_img = D('House_face_img')->where($buser_where)->getField('id',true);
            if($face_img) {
                $img_id = implode(',', $face_img);
            }else{
                $img_id = '';
            }
            $open_door_type = D('House_village_about_config')->where(['village_id' => $village_id])->getField('open_door_type');
            $this->assign('open_door_type',$open_door_type);
            $this->assign('img_id',$img_id);
            $this->assign('info',$member_info);
            $this->assign('guard',$guard);
            $this->display();
        }
    }
    //删除人员
    public function member_del()
    {
        $id = I('id');
        if(!$id)
        {
            $this->error('参数异常');
        }
        $res = D('House_village_pass_bind_member')->delMember($id);
        if($res){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
    //纸质出入证下载
    public function paper_download()
    {
        $this->display();
    }
    //导出二维码
    public function pass_exports(){
        $village_id = $this->village_id;
        $count = D('House_village_pass')->where(['village_id'=>$village_id,'status'=>0,'use_status'=>0])->count();
        $pass_list = D('House_village_pass')->where(['village_id'=>$village_id,'status'=>0,'use_status'=>0])->select();
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = '出入证二维码';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($count)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('共' . $count . '条记录');
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', '序号');
            $objActSheet->setCellValue('B1', '二维码链接');
            if (!empty($pass_list)) {
                $index = 2;
                foreach ($pass_list as $value) {
                    $objActSheet->setCellValueExplicit('A' . $index, $value['code_num']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['pass_url']);
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
        header('Content-Disposition:attachment;filename="'.'_'.$title.'_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }
}