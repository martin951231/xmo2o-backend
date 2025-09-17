<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date: 2019/9/25
 * Time: 16:18
 * 自定义打印文件预备前端插件接口
 *
 */

class ApiAction extends BaseAction
{

    /**
     * Notes: 获取模板填写列表
     * @author: wanzy
     * @date_time: 2020/9/17 14:16
     */
    public function template_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','20','intval');
        $site_url = C('config.site_url');
        // 条件搜索
        $where = array();
        $search_type = I('search_type','','trim');
        $search_val = I('search_val','','trim');
        // id 精确查询
        if ($search_type && 'template_id'==$search_type) {
            $template_id = intval($search_val);
        }
        if ($template_id>0) {
            $where['template_id'] = $template_id;
        }
        // 时间范围查询  2019/9/26    2019/10/5
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
        $where['from'] = $this->from;
        $from_id = I('from_id','');
        if ($this->from_id>0) {
            $where['from_id'] = $this->from_id;
        } elseif ($from_id) {
            $where['from_id'] = $from_id;
        }
        if ($this->_uid) {
            $where['uid'] = $this->_uid;
        } else {
            returnCode(10001, [], '当前未登录！');
        }
        $where['diy_tatus'] = array('neq', 4);
        $list_info = D('Plugin_material_diy_template')->write_template_list($where, $page, $page_size, 'add_time DESC');
        //todo 指定链接跳转
        $list_info['jump_url']=(intval($template_id) > 0) ? $site_url .str_replace('plugin.php', 'wap.php', U('Wap/Renovation/user_know', ['template_id'=>$template_id])) : '';
        returnCode(0, $list_info);
    }


    /**
     * Notes: 模板填写详情
     * @author: wanzy
     * @date_time: 2020/9/17 14:16
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
            'id' => $diy_id,
        );
        $diy_value_info = M('Plugin_material_diy_value')->where($where_diy)->field('from, from_id')->find();
        if (empty($diy_value_info)) {
            returnCode(31, [], '获取对象不存在！');
        }
        if ($this->_uid) {
            $where['uid'] = $this->_uid;
        } else {
            returnCode(10001, [], '当前未登录！');
        }

        $diy_value_info=$d_plugin_material_diy_template->template_write_detail($diy_id, false, true);

        if ($diy_value_info['template_img_path']) {
            $img_info = getimagesize($diy_value_info['template_img_path']);
            $img_weight = $img_info[0];
            $img_height = $img_info[1];
            $template_width = $diy_value_info['template_width'];
            $template_height = ($template_width/$img_weight)*$img_height;
            $template_height = round_number($template_height);
            $diy_value_info['template_height'] = $template_height . 'px';
        }
        returnCode(0, $diy_value_info);
    }


    /**
     * Notes: 模板详情
     * @author: wanzy
     * @date_time: 2020/9/17 15:05
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
            'template_id' => $template_id,
            'template_status' => 0
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->field('from, from_id')->find();
        if (empty($template_info)) {
            returnCode(31, [], '当前模板不存在或者已经被删除！');
        }
        $this->_uid = 1;
        if ($this->_uid) {
            $where['uid'] = $this->_uid;
        } else {
            returnCode(10001, [], '当前未登录！');
        }
        $template_info=$d_plugin_material_diy_template->template_detail($template_id, false, true);

        if ($template_info['template_img_path']) {
            $img_info = getimagesize($template_info['template_img_path']);
            if($img_info){
                $img_weight = $img_info[0];
                $img_height = $img_info[1];
            }else{
                $img_weight = 2480;
                $img_height =3508;
            }
            $template_width = $template_info['template_width'];
            $template_height = ($template_width/$img_weight)*$img_height;
            $template_height = round_number($template_height);
            $template_info['template_height'] = $template_height . 'px';
        }
        //todo 办公时间读取
        $template_info['time_options']=D('Plugin_material_diy_template')->get_work_time($template_info['from_id'],1);
        $template_info['room_select_options']=$d_plugin_material_diy_template->get_room_types();
        returnCode(0, $template_info);
    }

    /**
     * Notes:添加填写内容
     * @author: wanzy
     * @date_time: 2020/9/17 17:07
     */
    public function add_diy_value() {
        // 字段过滤
        $template_id = I('template_id');
        $pigcms_id = I('pigcms_id');
        $pigcms_id=intval($pigcms_id);
        if (empty($template_id)) {
            returnCode(11, [], '请上传填写对象！');
        }
        // 模板信息
        $diy_value = $_POST['diy_value'];
        if (empty($diy_value)) {
            returnCode(1, [], '请上传填写信息！');
        }

        $d_plugin_material_diy_template = D('Plugin_material_diy_template');
        $where_template = array(
            'template_id' => $template_id
        );
        $template_info = $d_plugin_material_diy_template->where($where_template)->field('*')->find();
        if (empty($template_info)) {
            returnCode(31, [], '获取对象不存在！');
        }
        $bind_info=[];
        $template_type=0;
        if (isset($template_info['template_type']) && $template_info['template_type']==3){
            if (!$pigcms_id){
                $whereArr=array('uid'=>$this->_uid,'village_id'=>$template_info['from_id'],'del_time'=>0,'vacancy_id'=>array('gt',0));
                $rental_apply=D('House_public_rental_apply_record')->where($whereArr)->order('id desc')->find();
                if($rental_apply){
                    $pigcms_id=$rental_apply['pigcms_id'];
                }else{
                    returnCode(11, [], '您暂未绑定该小区，请先绑定。');
                }
            }
            $bind_info=M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->field('pigcms_id,name,phone')->find();
            if (empty($bind_info)){
                returnCode(11, [], '绑定信息不存在，请先绑定！');
            }
        }
        
        if((!isset($template_info['template_type']) || $template_info['template_type']==0) && intval(C('config.is_material_diy')) > 0){
            if(empty($pigcms_id)){
                returnCode(11, [], '您暂未绑定该小区，请先绑定！');
            }
        }
        if(isset($template_info['template_type']) && intval($template_info['template_type']) ==  1){ //办理入住
            $template_type=1;
            $param=[
                'village_id'=>$template_info['from_id'],
                'template_id'=>$template_info['template_id'],
                'uid'=>$this->_uid,
                'pigcms_id'=>$pigcms_id ? $pigcms_id : 0
            ];
           $rr= D('Plugin_material_diy_template')->check_add_apply_record($param);
           if(!$rr['error']){
               returnCode(11, [], $rr['msg']);
           }
        }elseif (isset($template_info['template_type']) && intval($template_info['template_type']) ==  2){//电子合同
            $template_type=2;
            $param=[
                'village_id'=>$template_info['from_id'],
                'template_id'=>$template_info['template_id'],
                'uid'=>$this->_uid,
                'pigcms_id'=>$pigcms_id ? $pigcms_id : 0
            ];
            $rr= D('Plugin_material_diy_template')->check_add_contract($param);
            if(!$rr['error']){
                returnCode(11, [], $rr['msg']);
            }
        }
        elseif (isset($template_info['template_type']) && intval($template_info['template_type']) ==  3){//办理退租
            $template_type=3;
            $param=[
                'village_id'=>$template_info['from_id'],
                'template_id'=>$template_info['template_id'],
                'uid'=>$this->_uid,
                'pigcms_id'=>$pigcms_id ? $pigcms_id : 0
            ];
            $rr= D('Plugin_material_diy_template')->check_add_renting($param);
            if(!$rr['error']){
                returnCode(11, [], $rr['msg']);
            }
            $bind_info['apply_record_info']=$rr['data'];
        }

        //todo 校验办公时间
        D('Plugin_material_diy_template')->check_work_time($template_info['from_id'],$diy_value);
        // 关于来源处理
        if ($this->_uid) {
            $where['uid'] = $this->_uid;
        } else {
            returnCode(10001, [], '当前未登录！');
        }
        $user = M('User')->where(['uid' => $this->_uid])->field('uid,nickname,avatar')->find();

        $now_time = time();
        $template_title = $template_info['template_title']; // 背景图片
        $template_img = $template_info['template_img']; // 背景图片
        $template_width = $template_info['template_width']; // 背景图片  前端展示固定宽度

        $from = $template_info['from'];
        $from_id = $template_info['from_id'];
        $from_type = $template_info['from_type'];
        $from_name = $template_info['from_name'];
        if ($user['nickname']) {
            $account = $user['nickname'];
        } else {
            $account = '';
        }

        //开启事务
        $diy_value_data = array(
            'title' => $template_title,
            'pigcms_id'=>$pigcms_id,
            'template_img' => $template_img,
            'diy_value' => serialize($diy_value),
            'template_width' => $template_width ? $template_width : '',
            'from' => $from,
            'from_id' => $from_id,
            'from_type' => $from_type,
            'from_name' => $from_name,
            'add_time' => $now_time,
            'template_id' => $template_id,
            'account' => $account,
            'uid' => $this->_uid,
            'diy_tatus' => 0,
            'write_time' => $now_time,
        );
        if(isset($template_info['template_type']) && intval($template_info['template_type']) ==  2){
            $diy_value_data['diy_tatus']=1;
        }
        $diy_id = M('Plugin_material_diy_value')->data($diy_value_data)->add();
        if (!$diy_id) {
            fdump('==========添加填写内容失败================'.__LINE__, 'add_diy_value', true);
            fdump($diy_value_data, 'add_diy_value', true);
            fdump(M('Plugin_material_diy_value'), 'add_diy_value', true);
            returnCode(4, [], '模板添加失败！');
        }
        $data=[
            'diy_id'=>$diy_id,
             
        ];
        $record_id=0;
        if(isset($template_info['template_type']) && intval($template_info['template_type']) ==  1){//办理入住
            $param=[
                'village_id'=>$template_info['from_id'],
                'template_id'=>$template_info['template_id'],
                'value_id'=>$diy_id,
                'uid'=>$this->_uid,
                'pigcms_id'=>$pigcms_id ? $pigcms_id : 0,
            ];
            $record_id = D('Plugin_material_diy_template')->add_apply_record($param);
        }
        elseif(isset($template_info['template_type']) && intval($template_info['template_type']) ==  2){//电子合同
            $param=[
                'village_id'=>$template_info['from_id'],
                'template_id'=>$template_info['template_id'],
                'value_id'=>$diy_id,
                'uid'=>$this->_uid,
                'pigcms_id'=>$pigcms_id ? $pigcms_id : 0,
            ];
            $record_id = D('Plugin_material_diy_template')->syn_public_rental($param);
        }
        elseif(isset($template_info['template_type']) && intval($template_info['template_type']) ==  3){//办理退租
            $param=[
                'village_id'=>$template_info['from_id'],
                'template_id'=>$template_info['template_id'],
                'value_id'=>$diy_id,
                'uid'=>$this->_uid,
                'pigcms_id'=>$pigcms_id ? $pigcms_id : 0,
                'user_name'=>$bind_info['name'],
                'user_phone'=>$bind_info['phone'],
                'apply_record_info'=>$bind_info['apply_record_info']
            ];
            $record_id = D('Plugin_material_diy_template')->add_cancel_rental_record($param);
        }
        $data['record_id']=$record_id;
        $data['template_type']=$template_type; //0:无 1:办理入住 2:电子合同 3:办理退租
        returnCode(0, $data);
    }

}