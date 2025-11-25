<?php
/*
 * 短信发送记录
 *
 */

class SmsAction extends BaseAction
{

    // 对应关键字占据汉字长度  一个汉字 2个字符 即 {楼号} 为 4个字符
    public $sms_param_length = array(
        0=>array('key' => '{楼栋}', 'num' => 2),
        1=>array('key' => '{单元名称}', 'num' => 2),
        2=>array('key' => '{房间号}', 'num' => 2),
        3=>array('key' => '{业主姓名}', 'num' => 3),
        4=>array('key' => '{发送对象}', 'num' => 3),
    );
    public $sms_param_word = array(
        0=>array('key' => '{楼栋}', 'word' => 'floor_layer'),
        1=>array('key' => '{单元名称}', 'word' => 'floor_name'),
        2=>array('key' => '{房间号}', 'word' => 'room'),
        3=>array('key' => '{业主姓名}', 'word' => 'name'),
        4=>array('key' => '{发送对象}', 'word' => 'send_name'),
    );
    public $sms_param_txt = "{楼栋} {单元名称} {房间号} {业主姓名} {发送对象}";

    public $sms_word_length = 70; // 超过70字符算2条短信


    public function index()
    {
        $village_id = $this->house_session['village_id'];
        $where = [
            'a.village_id' => $village_id
        ];
        $count = D("House_sms_group_send")
            ->join(" as b LEFT JOIN ". C('DB_PREFIX') . "house_sms_template as a ON a.template_id=b.template_id")
            ->where($where)
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 20);
//        $mod = new Model();
        $res = D("House_sms_group_send")
            ->join(" as b LEFT JOIN ". C('DB_PREFIX') . "house_sms_template as a ON a.template_id=b.template_id")
            ->field('a.template_name, b.*')
            ->order('send_id DESC')
            ->where($where)
            ->limit($p->firstRow, $p->listRows)
            ->select();
//        $sql = "SELECT a.template_name, b.* FROM ". C('DB_PREFIX') . "house_sms_template AS a RIGHT JOIN ". C('DB_PREFIX') . "house_sms_group_send AS b ON a.template_id=b.template_id where a.village_id={$village_id} ORDER BY send_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
//        $res = $mod->query($sql);
        if (empty($res)) {
            $res = array();
        }

        $this->assign('list', $res);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    public function sms_index()
    {
        $send_id = I('send_id');
        $village_id = $this->house_session['village_id'];
        $count = D("Sms_record")
            ->join("as b LEFT JOIN ". C('DB_PREFIX') . "house_sms_group_send AS a ON a.send_id=b.village_send_id")
            ->where(array('b.village_send_id' => array('gt', 0), 'b.type' => 'house_group_send', 'a.send_id' => $send_id))
            ->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 20);
        $mod = new Model();
        $d_house_village=D('House_village');
        $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
        $sql = "SELECT a.send_id,a.send_type, b.* FROM ". C('DB_PREFIX') . "house_sms_group_send AS a RIGHT JOIN ". C('DB_PREFIX') . "sms_record AS b ON a.send_id=b.village_send_id WHERE b.village_send_id > 0 AND b.type = 'house_group_send' AND send_id={$send_id} ORDER BY pigcms_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
        $res = $mod->query($sql);
        if($res){
            foreach ($res as $kk=>$vv){
                if($vv['phone']){
                    $res[$kk]['phone']= $d_house_village->phone_desensitization_v($vv['phone'],$village_id,$village_info);
                }
            }
        }
        $status = array('0' =>'发送成功', '-1' => '验证失败未购买', '-2' => '短信不足', '-3' => '操作失败', '-4' => '非法字符', '-5' => '内容过多', '-6' => '号码过多', '-7' => '频率过快', '-8' => '号码内容空', '-9' => '账号冻结', '-10' => '禁止频繁单条发送', '-11' => '系统暂定发送', '-12' => '有错误号码', '-13' => '定时时间不对', '-14' => '账号被锁，10分钟后登录', '-15' => '连接失败', '-16' => '禁止接口发送', '-17' => '绑定IP不正确', '-18' => '系统升级', '-19' => '域名不对', '-20' => 'key不匹配', '-21' => '用户不存在', '-22' => '余额不足', '-100' => '发送的token不合法','-999'=>'频繁发送');

        $this->assign('record_list', $res);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('status', $status);
        $this->display();
    }

    public function sms_template()
    {
        $d_house_sms_template = D('House_sms_template');
        $village_id = $this->house_session['village_id'];
        $where['status'] = array('neq', 4);
        $info = $d_house_sms_template->house_sms_template_page($village_id,20,$where);
        $this->assign($info);
        $this->display();
    }

    public function sms_add()
    {
        $d_house_sms_template = D('House_sms_template');
        $village_id = $this->house_session['village_id'];
        $now_village = D('House_village')->get_one($this->house_session['village_id']);
        $village_info = D('House_village_info')->getVillageInfo(array('village_id'=>$this->house_session['village_id']),'sms_type');
        $sms_type=1; //短信使用类型 1小区短信，2 平台短信
        if($village_info && $village_info['sms_type']){
            $sms_type=$village_info['sms_type'];
        }
        $now_sms_number = $now_village['now_sms_number'];
        if (IS_POST) {
            //2020/8/24 start 新增
            $is_open_pay_management = D('House_menu_new')->getPayCostAppIsUsable($this->house_session['village_id']);
            if(!$is_open_pay_management){
                //由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用
                $this->error('由于您购买的套餐中不包括收费管理功能，所以导致该功能无法使用！');
            }
            //2020/8/24 end 新增
            $template_id = I('template_id', '', 'intval');
            if (!$template_id) {
                $this->error('对不起，请选择短信模板');exit;
            }
            $template_info = $d_house_sms_template->where(array('template_id' => $template_id, 'status' => array('neq', 4)))->find();
            if (empty($template_info)) {
                $this->error('对不起，所选短信模板不存在或者已经被删除');exit;
            }
            if ($template_info && $template_info['status']==1) {
                $this->error('对不起，所选短信模板已经被禁用');exit;
            }
            $send_type = I('send_type', '', 'intval');
            if (!$send_type) {
                $this->error('对不起，请选择发送类型');exit;
            }
            if (!in_array($send_type, array(1,2))) {
                $this->error('对不起，请选择正确的发送类型');exit;
            }
            $single_id = I('single_id', '', 'intval');
            $floor_id = I('floor_id', '', 'intval');
            $send_user = $_POST['send_user'];
            $template_content = $template_info['template_content'];
            $word_length = $this->word_length($template_content);
            $sms_word_length = $this->sms_word_length;
            // 短信条数向上取整  1个模板短信条数
            $template_num = ceil($word_length/$sms_word_length);
            if ($template_num<=0) {
                $template_num = 1;
            }

            if (1==$send_type && empty($send_user)) {
                $this->error('对不起，请选择发送对象');exit;
            }
            $send_phone = $_POST['send_phone'];
            if (2==$send_type && empty($send_phone)) {
                $this->error('对不起，请填写发送手机号');exit;
            }
            // 需要添加的数据
            $data = array();
            $sms_word = array();
            $data['village_id'] = $village_id;
            $data['send_type'] = $send_type;
            $data['template_id'] = $template_id;
            $data['add_time'] = time();
            $data['status'] = 0;
            // 计算需要发送数量
            if (1==$send_type) {
                $where_bind = array();
                $where_bind['village_id'] = $village_id;
                if ($single_id>0) {
                    $where_bind['single_id'] = $single_id;
                    $single_info = M('House_village_single')->where(array('id' => $single_id, 'status' => 1))->field('id, single_name')->find();
                    if (empty($single_info)) {
                        $this->error('对不起，所选'.(D('House_village')->getHouseAlias('single_name')?:'楼栋').'不存在或者已经被删除！');exit;
                    }
                    $sms_word['floor_layer'] = $single_info['single_name'];
                }
                if ($floor_id>0) {
                    $where_bind['floor_id'] = $floor_id;
                    $floor_info = M('House_village_floor')->where(array('floor_id' => $floor_id, 'status' => 1))->field('floor_name, floor_layer')->find();
                    if (empty($floor_info)) {
                        $this->error('对不起，所选'.(D('House_village')->getHouseAlias('floor_name')?:'单元').'不存在或者已经被删除！');exit;
                    }
                    if (!$sms_word['floor_layer']) {
                        $sms_word['floor_layer'] = $floor_info['floor_layer'];
                    }
                    $sms_word['floor_name'] = $floor_info['floor_name'];
                }
                // 业主
                if (in_array(0, $send_user)) {
                    $where_bind['_string'] = '(type in (0,3))';
                }
                // 配偶
                if (in_array(1, $send_user)) {
                    if ($where_bind['_string']) {
                        $where_bind['_string'] .= ' OR (type=1 && relatives_type=1)';
                    } else {
                        $where_bind['_string'] = '(type=1 && relatives_type=1)';
                    }
                }
                // 父母
                if (in_array(2, $send_user)) {
                    if ($where_bind['_string']) {
                        $where_bind['_string'] .= ' OR (type=1 && relatives_type=2)';
                    } else {
                        $where_bind['_string'] = '(type=1 && relatives_type=2)';
                    }
                }
                // 子女
                if (in_array(3, $send_user)) {
                    if ($where_bind['_string']) {
                        $where_bind['_string'] .= ' OR (type=1 && relatives_type=3)';
                    } else {
                        $where_bind['_string'] = '(type=1 && relatives_type=3)';
                    }
                }
                // 亲朋好友
                if (in_array(4, $send_user)) {
                    if ($where_bind['_string']) {
                        $where_bind['_string'] .= ' OR (type=1 && relatives_type=4)';
                    } else {
                        $where_bind['_string'] = '(type=1 && relatives_type=4)';
                    }
                }
                // 租客
                if (in_array(5, $send_user)) {
                    if ($where_bind['_string']) {
                        $where_bind['_string'] .= ' OR (type=2)';
                    } else {
                        $where_bind['_string'] = '(type=2)';
                    }
                }
                $d_house_village_user_bind = M('House_village_user_bind');
                $where_bind['status'] = 1;
                $where_bind['phone'] = array('neq', '');
                $village_info = $d_house_village_user_bind->where($where_bind)->field('phone,uid,name,vacancy_id,village_id')->select();
                if (empty($village_info)) {
                    fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($_POST,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($where_bind,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($d_house_village_user_bind,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    $this->error('对不起，没有符合条件的发送对象');exit;
                }
                $send_phone_count = count($village_info);
                $sms_num = $send_phone_count * $template_num;
                if ($now_sms_number<$sms_num  && $sms_type!=2) {
                    $this->error("抱歉当前群发需要发送短信{$sms_num}条，您剩余{$now_sms_number}条，请前往购买！");exit;
                }
                if ($floor_id>0) {
                    $data['floor_id'] = $floor_id;
                }
                $data['send_user_txt'] = implode(',',$send_user);
                $data['send_num'] = $sms_num;
            } elseif (2==$send_type) {
                $send_phone = explode(PHP_EOL,$send_phone);
                if (empty($send_phone)) {
                    $this->error('对不起，请填写发送手机号');exit;
                }
                $sms_word['floor_layer'] = '';
                $sms_word['floor_name'] = '';
                $send_phone_count = count($send_phone);
                $sms_num = $send_phone_count * $template_num;
                if ($now_sms_number<$sms_num  && $sms_type!=2) {
                    $this->error("抱歉当前群发需要发送短信{$sms_num}条，您剩余{$now_sms_number}条，请前往购买！");exit;
                }
                $data['send_phone_txt'] = implode(',',$send_phone);
                $data['send_num'] = $sms_num;
            }

            $add_id = M('House_sms_group_send')->data($data)->add();
            if ($add_id) {
                $real_send_num = 0;
                if (1==$send_type && $village_info) {
                    $d_house_village_user_vacancy = M('House_village_user_vacancy');
                    foreach ($village_info as $val) {
                        if ($val['phone']) {
                            $room = $d_house_village_user_vacancy->where(array('pigcms_id' => $val['vacancy_id']))->getField('room');
                            $sms_word['name'] = $val['name'];
                            $sms_word['send_name'] = $val['name'];
                            $sms_word['room'] = $room;
                            $word_replce_msg = $this->word_replce_msg($template_content, $sms_word);
                            $sms_data['uid'] = $val['uid'];
                            $sms_data['mobile'] = $val['phone'];
                            $sms_data['sendto'] = 'user';
                            $sms_data['type'] = 'house_group_send';
                            $sms_data['content'] = $word_replce_msg;
                            $sms_data['village_id'] = $val['village_id'];
                            $sms_data['village_send_id'] = $add_id;
                            $sms_data['template_num'] = $template_num;
                            Sms::sendSms(($sms_data));
                            $real_send_num  = $real_send_num + $template_num;
                        }
                    }
                } elseif(2==$send_type && $send_phone) {
                    foreach ($send_phone as $val) {
                        if ($val) {
                            $sms_word['name'] = '';
                            $sms_word['send_name'] = '';
                            $sms_word['room'] = '';
                            $word_replce_msg = $this->word_replce_msg($template_content, $sms_word);
                            $sms_data['mobile'] = $val;
                            $sms_data['sendto'] = 'user';
                            $sms_data['type'] = 'house_group_send';
                            $sms_data['content'] = $word_replce_msg;
                            $sms_data['village_id'] = $village_id;
                            $sms_data['village_send_id'] = $add_id;
                            $sms_data['template_num'] = $template_num;
                            Sms::sendSms(($sms_data));
                            $real_send_num  = $real_send_num + $template_num;
                        }
                    }
                } else {
                    fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump('line---'.__LINE__,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($_POST,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($send_phone,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                }
                if ($real_send_num>0) {
                    M('House_village')->where(array('village_id' => $village_id))->setDec('now_sms_number', $real_send_num);
                    M('House_sms_group_send')->where(array('send_id' => $add_id))->setField('real_send_num', $real_send_num);
                } else {
                    fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump('line---'.__LINE__,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($_POST,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($village_info,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                    fdump($data,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                }
                $this->success('群发添加成功');exit;
            } else {
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sms_add_err_log',true);
                fdump('line---'.__LINE__,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                fdump($_POST,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                fdump($data,'api/log/'.date('Ymd').'/sms_add_err_log',true);
                fdump(M('House_sms_group_send'),'api/log/'.date('Ymd').'/sms_add_err_log',true);
                $this->error('群发添加失败');exit;
            }
        } else {
            $template_where = array(
                'village_id' => $village_id,
                'status' => 0,
            );
            $template_select = $d_house_sms_template->where($template_where)->order('template_id DESC')->select();
            $this->assign('template_select', $template_select);
            if (empty($template_select)) {
                $this->assign('is_empty', true);
            }
            if ($now_sms_number<=0 && $sms_type!=2) {
                $this->assign('is_pay_sms', true);
            }
            $database_house_village_floor = D('House_village_floor');
            $condition['village_id'] = $village_id;
            $condition['status'] = 1;
            $floor_list = $database_house_village_floor->house_village_floor_page_list($condition , true ,'floor_id desc' , 99999);
            if ($floor_list && $floor_list['list'] && $floor_list['list']['list']) {
                $floor_list_select = $floor_list['list']['list'];
            } else {
                $floor_list_select = array();
            }
            $this->assign('floor_list' ,$floor_list_select);

            $this->display();
        }
    }

    public function sms_template_add() {
        $village_id = $this->house_session['village_id'];
        $template_id = I('template_id', '0', 'intval');
        $d_house_sms_template = M('House_sms_template');
        if (IS_POST) {
            $template_name = I('template_name', '', 'trim');
            if (!$template_name) {
                $this->error('对不起，短信模板标题不能为空');exit;
            }
            $template_content = I('template_content', '', 'trim');
            if (!$template_content) {
                $this->error('对不起，短信内容不能为空');exit;
            }
            $status = I('status', '0', 'intval');
            $add_info = array(
                'template_name' => $template_name,
                'template_content' => $template_content,
                'village_id' => $village_id,
                'status' => $status
            );
            if ($template_id && $template_id >0) {
                $add_info['template_id'] = $template_id;
                $add_info['last_time'] = time();
                $set_id = $d_house_sms_template->data($add_info)->save();
                if ($set_id) {
                    $this->success('修改成功');exit;
                } else {
                    $this->error('修改失败');exit;
                }
            } else {
                $add_info['add_time'] = time();
                $add_id = $d_house_sms_template->data($add_info)->add();
                if ($add_id) {
                    $this->success('添加成功');exit;
                } else {
                    $this->error('添加失败');exit;
                }
            }
        } elseif ($template_id && $template_id>0) {
            $sms_template = $d_house_sms_template->where(array('template_id' => $template_id))->find();
            $this->assign('sms_template', $sms_template);
        }
        $sms_param_txt = $this->sms_param_txt;
        $this->assign('sms_param_txt', $sms_param_txt);
        $this->display();
    }

    public function sms_param_length() {
        $sms_param_length = $this->sms_param_length;
        exit(json_encode($sms_param_length));
    }

    public function word_length($word) {
        $sms_param_length = $this->sms_param_length;
        $font_num = 0; // 初始化字段长度
        $word_length = 0;
        if ($word && $sms_param_length) {
            foreach ($sms_param_length as $val) {
                if ($val && $val['key']) {
                    $n = count(explode($val['key'],$word)) - 1;
                    //$font_num += $n*2;// 1个汉字2个字符
                    $font_num += $n*1; 
                    $word = preg_replace('/'.$val['key'].'/', '', $word);
                }
            }
            $word_info = preg_replace("#[^\x{00}-\x{ff}]#u", '**', $word);
            $word_length = strlen($word_info);
        }
        $font_num += $word_length;
        return $font_num;
    }

    public function word_replce_msg($word, $data=array()) {
        $sms_param_word = $this->sms_param_word;
        $word = preg_replace('/{楼号}/', '{楼栋}', $word);
        if ($word && $sms_param_word) {
            foreach ($sms_param_word as $val) {
                if ($val && $val['word'] && isset($data[$val['word']])) {
                    $word = preg_replace('/'.$val['key'].'/', $data[$val['word']], $word);
                }
            }
        }
        return $word;
    }
}
?>