<?php
/**
 * @author : liukezhu
 * @date : 2021/8/18
 */
class VisualizeAction extends BaseAction{
    protected $jsdhwy_new_wap_index = 0;
    protected $huizhisq=false;
    protected $yuanJino2oCustomized=0;
    protected $guess_you_like_goods = 0;
    public function __construct(){
        parent::__construct();
        /**
         * todo 社区可视化页面开关状态
         */
        $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
        if (intval($switch) < 1){
            die('社区可视化关闭');
        }

        // 营山定制开关
        $this->huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$this->huizhisq);
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        $this->jsdhwy_new_wap_index=$jsdhwy_new_wap_index;
        $this->assign('jsdhwy_new_wap_index',$jsdhwy_new_wap_index);

        $yuanJino2oCustomized=D('Config_customization')->getYuanJino2oCustomized();
        $this->yuanJino2oCustomized=$yuanJino2oCustomized;
        $this->assign('yuanJino2oCustomized',$yuanJino2oCustomized);
        $this->guess_you_like_goods=D('Config_customization')->getGuessYouLikeGoodsCustomized();
        $this->assign('guess_you_like_goods',$this->guess_you_like_goods);

    }

    //todo 首页
    public function viewpage_list(){
        $this->display();
    }

    //todo 底部导航
    public function footer_list()
    {
        // 查询分配权限的小区
        $village_arr = $this->get_visualize_assign(7);

        $where = [];
        $where['village_id'] = array('not in',$village_arr);
        $template_floor = D('House_village_info')->where($where)->field('template_floor')->find();
        switch ($template_floor['template_floor']) {
            case 3:
                $footer_template['template'] = '模板三';
                break;
            case 2:
                $footer_template['template'] = '模板二';
                break;
            default :
                $footer_template['template'] = '模板一';
                break;
        }
        $this->assign('footer_template', $footer_template['template']);

        $now_village = D('Config_data')->where(['name'=>['in',['visualize_app_footer_bgcolor','visualize_app_footer_txtcolor','visualize_app_footer_txtactivecolor']]])->getField('name,value',true);
        $this->assign('now_village', $now_village);
        $this->display();
    }

    //todo 顶部导航
    public function top_list()
    {
        // 查询分配权限的小区
        $village_arr  = $this->get_visualize_assign(12);

        $where = [];
        $where['village_id'] = array('not in',$village_arr);
        $template_top = D('House_village_info')->where($where)->field('template_top')->find();
        $this->assign('template_top', (int)$template_top['template_top']);

        $data = [
            [
                'id' => 1,
                'template' => '模板一',
                'image' => $this->config['site_url'].'/static/images/house/top_template_1.png'
            ],
            [
                'id' => 2,
                'template' => '模板二',
                'image' => $this->config['site_url'].'/static/images/house/top_template_2.png'
            ],
            [
                'id' => 3,
                'template' => '模板三',
                'image' => $this->config['site_url'].'/static/images/house/top_template_3.png'
            ]
        ];
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        if($jsdhwy_new_wap_index>0){
            $data[]=array( 'id' => 4, 'template' => '模板四', 'image' => $this->config['site_url'].'/static/images/house/top_template_4.png');
        }
        if($this->yuanJino2oCustomized>0){
            $data = [
                [
                    'id' => 1,
                    'template' => '模板一',
                    'image' => $this->config['site_url'].'/static/images/house/top_template_1.png'
                ],
                [
                    'id' => 5,
                    'template' => '模板二',
                    'image' => $this->config['site_url'].'/static/images/house/top_template_5.png'
                ],
            ];
        }
        $this->assign('template',$data);
        $this->display();
    }

    //todo 分配小区列表
    public function visualize_assign(){
        if(!$_GET['source_type']){
            exit('缺少必要参数');
        }
        $order_string = '`village_id` DESC';
        if ($_GET['sort'] == 'lastTime') {
            $style = "border: solid blue;border-width: 0 3px 3px 0;display: inline-block;padding: 3px;transform: rotate(45deg);color:blue";
            $this->assign('style', $style);
        }
        //搜索
        if (!empty($_GET['keyword'])) {
            $condition_house_village['a.village_name'] = array('like', '%' . $_GET['keyword'] . '%');
        }
        $condition_house_village['a.status'] = ['in', [0,1]];
        $count = 30;
        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $condition_house_village[$area_index] = $this->system_session['area_id'];
        }

        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['a.city_id'] = $where['city_id'] = $_GET['city_idss'];
        } else {
            if ($this->config['many_city'] && $_GET['province_idss']) {
                $condition_house_village['a.province_id'] = $where['province_id'] = $_GET['province_idss'];
            }
        }
        if ($_GET['area_id']) {
            $condition_house_village['a.area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_idss']) {
            $condition_house_village['a.street_id'] = $where['street_id'] = $_GET['street_idss'];
        }
        if ($_GET['community_idss']) {
            $condition_house_village['a.community_id'] = $where['community_id'] = $_GET['community_idss'];
        }

        $database_house_village = D('House_village');
        $count_village = $database_house_village->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_visualize_assign as v ON (v.village_id=a.village_id and v.type=".$_GET['source_type'].")")->where($condition_house_village)->count();
        import('@.ORG.system_page');
        $p = new Page($count_village, $count);
        $field = "a.*,v.type as v_type";
        $village_list = $database_house_village->join(" as a  left  JOIN " . C('DB_PREFIX') . "house_visualize_assign as v ON (v.village_id=a.village_id and v.type=".$_GET['source_type'].")")->where($condition_house_village)->field($field)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();


        $this->assign('village_list', $village_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('source_type', $_GET['source_type']);
        $this->display();
    }

    //todo 分配小区操作
    public function visualize_assign_post(){
        if(IS_POST){
            if(!isset($_POST['status']) || !isset($_POST['source_type']) || !isset($_POST['village_id'])){
                $this->error( '缺少必要参数');
            }
            $status=$_POST['status'];
            $source_type=$_POST['source_type'];
            $village_id=$_POST['village_id'];
            if(empty($village_id)){
                $this->error( '缺少village_id参数');
            }
            $village_ids=array();
            if(!is_array($village_id)){
                $village_ids[]=$village_id;
            }else{
                $village_ids=$village_id;
            }
            $database_house_visualize_assign = D('House_visualize_assign');
            $rel=[];
            if($status == 1){
                //绑定
               $rel= $database_house_visualize_assign->village_binding($source_type,$village_ids);
            }elseif ($status == 4){
                //解绑
                $rel= $database_house_visualize_assign->village_unbound($source_type,$village_ids);
            }else{
                $this->error( '参数不合法');
            }
            if($rel['error']){
                $this->success($rel['msg']);
            }else{
                $this->error( $rel['msg']);
            }
        }
    }

    //todo 广告位
    public function adver_list(){
        if(!$_GET['cat_key']){
            exit('缺少必要参数');
        }
        $cat_key_arr=array(
            'house_four_adver'=>5,
            'house_center_adver'=>3,
            'house_footer_adver'=>4
        );
        if(!isset($cat_key_arr[$_GET['cat_key']])){
            exit('参数不合法');
        }
        $cat_key=$cat_key_arr[$_GET['cat_key']];
        $database_adver_category  = D('House_adver_category');
        $condition_adver_category['cat_key'] = $_GET['cat_key'];
        $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
        $database_adver = D('House_adver');
        $condition_adver['is_platform'] = $cat_key;
        $condition_adver['status'] = array('neq',4);
        $adver_list = $database_adver->field(true)->where($condition_adver)->order('`id` DESC')->select();
        $this->assign('now_category', $now_category);
        $this->assign('adver_list', $adver_list);
        $this->assign('cat_key', $cat_key);
        $this->display();
    }

    //todo 添加广告位
    public function adver_add(){
        if(IS_POST){
            $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
            } else {
                $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
            }
            unset($_POST['dosubmit']);
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['village_id'] = 0;
            $_POST['is_platform'] = $_POST['cat_id'];
            //todo 记录下小区功能库返回的参数
            $_POST['source_type']=D('House_link')->get_source_type($_POST['url']);
            $database_adver = D('House_adver');
            if($id = $database_adver->data($_POST)->add()){
                D('Image')->update_table_id('/upload/adver/' . $_POST['pic'], $id, 'adver');

                D('Config')->clearCache();
                exit('<html><head><script>window.top.msg(1,"添加成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11,'11添加成功！');
            }else{
                exit('<html><head><script>window.top.msg(0,"添加失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
                //$this->frame_submit_tips(0,'添加失败！请重试~');
            }
        }else{
            if(!$_GET['cat_key']){
                exit('缺少必要参数');
            }
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list', $wxapp_list);
            $this->assign('cat_key', $_GET['cat_key']);
            $this->display();
        }
    }

    //todo 编辑广告位
    public function adver_edit(){
        if(IS_POST){
            $database_adver = D('House_adver');
            $condition_adver['id'] = $_POST['id'];
            $now_adver = $database_adver->field(true)->where($condition_adver)->find();
            if($_FILES['pic']['error'] != 4){
                $image = D('Image')->handle($this->system_session['id'], 'adver', 0, array('size' => 10), false);
                if (!$image['error']) {
                    $_POST = array_merge($_POST, str_replace('/upload/adver/', '', $image['url']));
                } else {
                    $this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
                }
            }
            unset($_POST['dosubmit']);
            $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            //todo 记录下小区功能库返回的参数
            $_POST['source_type']=D('House_link')->get_source_type($_POST['url']);
            $database_adver = D('House_adver');
            if($database_adver->data($_POST)->save()){
                D('Image')->update_table_id('/upload/adver/' . $_POST['pic'], $_POST['id'], 'adver');
                D('Config')->clearCache();
                if($_POST['pic']){
                   // unlink('./upload/adver/'.$now_adver['pic']);
                }
                exit('<html><head><script>window.top.msg(1,"编辑成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11,'编辑成功！');
            }else{
                exit('<html><head><script>window.top.msg(0,"编辑失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
                //$this->frame_submit_tips(0,'编辑失败！请重试~');
            }
        }else{
            $database_adver = D('House_adver');
            $condition_adver['id'] = $_GET['id'];
            $now_adver = $database_adver->field(true)->where($condition_adver)->find();
            if(empty($now_adver)){
                $this->frame_error_tips('该广告不存在！');
            }
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list', $wxapp_list);
            $this->assign('now_adver',$now_adver);
            $this->display();
        }
    }

    //todo 删除广告位
    public function adver_del(){
        $database_adver = D('House_adver');
        $condition_adver['id'] = $_POST['id'];
        $now_adver = $database_adver->field(true)->where($condition_adver)->find();
        if($database_adver->where($condition_adver)->delete()){
            //unlink('./upload/adver/'.$now_adver['pic']);
            D('Config')->clearCache();
            $this->success('删除成功');
        }else{
            $this->error('删除失败！请重试~');
        }
    }


    //todo 底部导航装修列表
    public function foot_menu_list(){
        $home_menu_list = M('House_village_footer_menu')->where(array('village_id'=>0))->order('sort desc,id asc')->select();
        $this->assign('home_menu_list',$home_menu_list);
        $this->display();
    }

    //todo 底部导航装修添加
    public function foot_menu_add() {
        if (IS_POST) {
            if (($_FILES['pic_path']['error'] != 4) || ($_FILES['hover_pic_path']['error'] != 4)) {
                $image = D('Image')->handle($this->system_session['id'], 'slider');
                if (!$image['error']) {
                    if($image['url']['pic_path']){
                        if (is_array($image['url']['pic_path'])) {
                            $pic_path = reset($image['url']['pic_path']);
                        } else {
                            $pic_path = $image['url']['pic_path'];
                        }
                        $_POST['pic_path'] =str_replace('/upload/slider/', '', $pic_path);
                    }

                    if($image['url']['hover_pic_path']){
                        if (is_array($image['url']['hover_pic_path'])) {
                            $hover_pic_path = reset($image['url']['hover_pic_path']);
                        } else {
                            $hover_pic_path = $image['url']['hover_pic_path'];
                        }
                        $_POST['hover_pic_path'] = str_replace('/upload/slider/', '', $hover_pic_path);
                    }
                } else {
                    //$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
                    $this->error($image['msg'] ? $image['msg'] : $image['message']);
                }
            }

            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['village_id'] =0;
            $_POST['type'] = 0;
            $_POST['add_time'] = time();

            //todo 记录下小区功能库返回的参数
            $_POST['source_type']=D('House_link')->get_source_type($_POST['url']);

            $result = M('House_village_footer_menu')->data($_POST)->add();
                if ($result) {
                    $id = $result;
                    D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');
                    exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                } else {
                    exit('<html><head><script>window.top.msg(0,"添加失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
                }

        } else {
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list', $wxapp_list);
            $this->assign('bg_color','#F3F3F3');
            $this->display();
        }
    }

    //todo 底部导航装修编辑
    public function foot_menu_edit(){
        $this->assign('bg_color', '#F3F3F3');
        $id = $this->_get('id');
        $where['id'] = $id;
        if (IS_POST) {

            if (($_FILES['pic_path']['error'] != 4) || ($_FILES['hover_pic_path']['error'] != 4)) {
                $image = D('Image')->handle($this->system_session['id'], 'slider');
                if (!$image['error']) {
                    if($image['url']['pic_path']){
                        if (is_array($image['url']['pic_path'])) {
                            $pic_path = reset($image['url']['pic_path']);
                        } else {
                            $pic_path = $image['url']['pic_path'];
                        }
                        $_POST['pic_path'] =str_replace('/upload/slider/', '', $pic_path);
                    }

                    if($image['url']['hover_pic_path']){
                        if (is_array($image['url']['hover_pic_path'])) {
                            $hover_pic_path = reset($image['url']['hover_pic_path']);
                        } else {
                            $hover_pic_path = $image['url']['hover_pic_path'];
                        }
                        $_POST['hover_pic_path'] = str_replace('/upload/slider/', '', $hover_pic_path);
                    }
                } else {
                    $this->frame_submit_tips(0, $image['msg']);
                }
            }

            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['add_time'] = time();

            //todo 记录下小区功能库返回的参数
            $_POST['source_type']=D('House_link')->get_source_type($_POST['url']);
            $result = M('House_village_footer_menu')->data($_POST)->save();
            if($result){
                exit('<html><head><script>window.top.msg(1,"修改成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
                //D('House_link')->frame_submit_tips(11,'修改成功');
            }else{
                exit('<html><head><script>window.top.msg(0,"修改失败！请重试~！",true,2);window.parent.closeiframe();</script></head></html>');
                //$this->frame_error_tips(0,'修改失败！请重试~');
            }
        } else {
            $this->assign('bg_color','#F3F3F3');
            $condition_adver['id'] = $_GET['id'];
            $foot_menu_info = M('House_village_footer_menu')->where($condition_adver)->find();
            if (!$foot_menu_info) {
                $this->frame_error_tips('该信息不存在！');
            } else {
                $this->assign('foot_menu_info', $foot_menu_info);
            }
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list', $wxapp_list);
            $this->display();
        }
    }

    //todo 底部导航装修删除
    public function foot_menu_del(){
        $where['id'] = $_POST['id'];
        $result = M('House_village_footer_menu')->where($where)->delete();
        if($result){
            $this->success('删除成功!');
        }else{
            $this->error('删除失败!');
        }
    }

    //todo 底部导航选颜色
    public function change_footer_color(){
        if(IS_POST){
            if(isset($_POST['name']) && isset($_POST['color'])){
                if(in_array($_POST['name'],['app_footer_bgcolor','app_footer_txtactivecolor','app_footer_txtcolor'])){
                    $name = $_POST['name'];
                    $value = $_POST['color'];
                    $database_house_visualize_assign = D('House_visualize_assign');
                    if(!empty($name) && !empty($value)){
                        $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
                        $rel = D('Config_data')->where(['name'=>'visualize_'.$name])->data(['value'=>$value])->save();
                        if($rel){
                            if(intval($switch) > 0){
                                //查询没有被分配权限的小区
                                $get_not_visualize_assign= $database_house_visualize_assign->get_not_visualize_assign();
                                //初始化没有参数
                                $where[$name] = array('EXP','IS NULL');
                                if(!empty($get_not_visualize_assign)){
                                    $where['village_id']  = array('in',$get_not_visualize_assign);
                                }
                                $where['_logic'] = 'or';
                                $map['_complex'] = $where;
                                D('House_village')->where($map)->data([$name=>$value])->save();
                            }
                            echo json_encode(array('status'=>1));
                        }else{
                            echo json_encode(array('status'=>0));
                        }
                    }
                }
            }

        }

    }

    /**
     * 底部模板导航
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 17:36
     */
    public function foot_menu_template(){
        // 查询分配权限的小区
        $village_arr = $village_arr = $village_arr = $this->get_visualize_assign(7);

        $where = [];
        $where['village_id'] = array('not in',$village_arr);
        $template_floor = D('House_village_info')->where($where)->field('template_floor')->find();
        $this->assign('template_floor',$template_floor['template_floor']);
        // 底部导航模板
        $data = [
            [
                'id' => 1,
                'template' => '模板一',
                'image' => $this->config['site_url'].'/static/images/house/template1.png'
            ],
            [
                'id' => 2,
                'template' => '模板二',
                'image' => $this->config['site_url'].'/static/images/house/template2.png'
            ]
        ];
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        if($jsdhwy_new_wap_index>0){
            $data[]=array( 'id' => 3, 'template' => '模板三', 'image' => $this->config['site_url'].'/static/images/house/template3.png');
        }
        $this->assign('template',$data);
        $this->display();
    }

    /**
     * 设置底部、头部导航模板
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 17:43
     */
    public function set_footer_template(){
        $type = remove_xss($_POST['type']);
        $template = remove_xss($_POST['template']);

        $village_info = D('House_village_info');
        // 1是顶部 0是底部
        $assign_type = 7;
        if($type == 1){
            if($this->yuanJino2oCustomized>0 && !in_array($template,array(1,5))){
                $template=1;
            }
            $data = [
                'template_top' => $template,
                'add_time' => time()
            ];
            $assign_type = 12;
        }else{
            $data = [
                'template_floor' => $template,
                'add_time' => time()
            ];
        }
        // 查询分配权限的小区
        $village_arr = $this->get_visualize_assign($assign_type);

        $where = [];
        $where['village_id'] = array('not in',$village_arr);
        $res = $village_info->where($where)->data($data)->save();
        if($res){
            exit(json_encode(array('status'=>0,'msg'=>'设置成功。')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'设置失败！请重试。')));
        }
    }

    /**
     * 获取权限分配
     * User: zhanghan
     * Date: 2022/2/22
     * Time: 11:58
     * @param $type
     * @return array|int[]
     */
    public function get_visualize_assign($type){
        // 查询分配权限的小区
        $village_arr = [0];
        $assign_type = D('House_visualize_assign')->where(array('type' => $type))->field('village_id')->select();
        if(!empty($assign_type)){
            $village_arr = array_merge(array_column($assign_type,'village_id'),$village_arr);
        }
        return $village_arr;
    }

    /**
     * 社群活动展示开关
     */
    public function change_social_events_slot_show(){
        D('Config')->remove_cache();
        if (isset($this->config['social_events_slot_show'])) {
            if (D('Config')->where(array('name' => 'social_events_slot_show'))->save(array('value' => $_POST['value']))) {

                //删除缓存
                D('Config')->clearDataCacheDir('system_config');

                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        } else {
            if (D('Config')->add(array('name' => 'social_events_slot_show', 'value' => $_POST['value'], 'info' => '可以设置社群活动位是否展示', 'gid' => 0, 'status' => 1))) {

                //删除缓存
                D('Config')->clearDataCacheDir('system_config');

                $this->success('切换成功');
            } else {
                $this->error('切换失败');
            }
        }
    }
}