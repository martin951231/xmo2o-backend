<?php
/**
 * Created by PhpStorm.
 * User: wanziyang
 * Date: 2019/9/25
 * Time: 16:18
 * 投票插件接口
 *
 */

class ApiAction extends BaseAction
{
    /**
     * 获取投票列表
     */
    public function vote_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','20','intval');
        // 条件搜索
        $where = array();
        // id 精确查询
        $vote_id = I('vote_id','','intval');
        if ($vote_id>0) {
            $where['vote_id'] = $vote_id;
        }
        // 投票标题模糊查询
        $vote_title = I('vote_title','','trim');
        if ($vote_title) {
            $where['vote_title'] = array('like','%'.$vote_title.'%');
        }
        // 时间范围查询  2019/9/26    2019/10/5
        $start_time = I('start_time','');
        $end_time = I('end_time','');
        if(!empty($start_time)&&!empty($end_time)){
            $period = array(strtotime($start_time." 00:00:00"),strtotime($end_time." 23:59:59"));
            if ($period[0]>$period[1]) {
                returnCode(41, [], '结束时间应大于开始时间！');
            }
            $time_condition = " (pay_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $condition['_string'] = $time_condition;
        }
        // 关于来源处理
        $where['from'] = $this->from;
        if ($this->from_id>0) {
            $where['from_id'] = $this->from_id;
        }
        if($this->from == 'street'){
            if(!$this->from_id)
                returnCode(41, [], '缺少必传参数！');
            //$where['from_id'] = $street_id;
        } elseif ($this->from == 'community'){
            if(!$this->from_id)
                returnCode(41, [], '缺少必传参数！');
            //$where['from_id'] = $street_id;
        }
        $join_where = array();
        if ($this->_uid) {
            $join_where['uid'] = $this->_uid;
        }
        if ($this->from) {
            $join_where['from'] = $this->from;
            if ($this->from=='house' && $this->village_bind) {
                $join_where['bind_id'] = $this->village_bind['pigcms_id'];
            }
        }
        if ($this->from_id>0) {
            $join_where['from_id'] = $this->from_id;
        }
        if ($this->from=='house' && 0!=$this->village_bind['type'] && 3!=$this->village_bind['type']) {
            $where['limit_type'] = 0;
        }
        $where['status'] = array('not in', array(0,4));
        // 投票未开始不进行显示
        $where['start_time'] = array('elt', time());

        $list_info = D('Plugin_vote')->vote_list($where, $page, $page_size, false, $join_where,'','vote_id,vote_image,start_time,end_time,status,vote_title');
        $list_info['from_limit_type'] = $this->from_limit_type;

        $list_info['share_info']=[
            'share_switch'=>intval($this->config['share_switch']),
            'share_img'=>$this->config['site_url'] . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>$this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username'] ? 'wxapp' : 'h5',
            'userName'=>$this->config['pay_wxapp_username'],
            'title'=>'投票表决',
            'info'=>'投票表决，进入可查看详情。'
        ];

        returnCode(0, $list_info);
    }


    /**
     * 获取投票详情
     */
    public function vote_detail() {
        // 字段过滤
        $vote_id = I('vote_id');
        if (empty($vote_id)) {
            returnCode(31, [], '请上传获取对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_vote = D('Plugin_vote');
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $d_plugin_vote->where($where_vote)->field('from, from_id, limit_type,limit_num')->find();
        if (empty($vote_info)) {
            returnCode(31, [], '获取对象不存在！');
        }
        if($this->from != 'street' || $this->from != 'community'){
            if (1==$vote_info['limit_type'] && 0==$this->from_limit_type) {
                if (!$this->from_limit_msg) {
                    $this->from_limit_msg = '您不能进行投票,仅业主可进行投票';
                }
                returnCode(99, [], $this->from_limit_msg);
            }
        }

        $from = $this->from;
        if ($this->from_id>0) {
            $from_id = $this->from_id;
        }
        if ($from != $vote_info['from'] || ($from_id && $from_id != $vote_info['from_id'])) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $join_where = array();
        if ($this->_uid) {
            $join_where['uid'] = $this->_uid;
        }
        if ($this->from) {
            $join_where['from'] = $this->from;
            if ($this->from=='house' && $this->village_bind) {
                $join_where['bind_id'] = $this->village_bind['pigcms_id'];
            }
        }
        if ($this->from_id>0) {
//            $join_where['from_id'] = $this->from_id;
            $join_where['_string'] = "from_id={$this->from_id} OR from_id=0";
        }
        $vote_info=$d_plugin_vote->vote_detail($vote_id, false, false, $join_where);
        if (isset($vote_info['from_id']) && 'house'==$vote_info['from'] && $vote_info['from_id']) {
            $village_logo = M('House_village')->where(['village_id'=> $vote_info['from_id']])->getField('village_logo');
        }
        $share_info = [];
        $share_info['share_img'] = $village_logo?$village_logo:replace_file_domain($this->config['site_logo']);
        $share_info['share_wx'] = $this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username'] ? 'wxapp' : 'h5';
        $share_info['userName'] = $this->config['pay_wxapp_username'];
        $vote_info['share_info'] = $share_info;
        returnCode(0, $vote_info);
    }


    /**
     * 用户投票 编辑也走这个接口
     * $vote_id 投票活动id
     * $block_info
     * array(
     *       block_id => '这个一定有，投票区块id',
     *       option_id => '这个根据投票区块类型来  单选和多选必须由，单选为单个，多选为数组array(1,2)',
     *       option_txt => '如果单选是 其他  选项类型 需要有值  或者多选选中了一个  其他 类型 需要有值',
     *       option_num => '如果是评分类型 必须有值  0-10 2分为一星'
     * )
     */
    public function vote_join() {
        // 字段过滤
        $vote_id = I('vote_id','','intval');
        if (empty($vote_id)) {
            returnCode(31, [], '请上传投票对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_vote = D('Plugin_vote');
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $d_plugin_vote->where($where_vote)->field('from, from_id, limit_type')->find();
        if (empty($vote_info)) {
            returnCode(61, [], '获取对象不存在！');
        }
        if($this->from != 'street' || $this->from != 'community'){
            if (1==$vote_info['limit_type'] && 0==$this->from_limit_type) {
                if (!$this->from_limit_msg) {
                    $this->from_limit_msg = '您不能进行投票,仅业主可进行投票';
                }
                returnCode(99, [], $this->from_limit_msg);
            }
        }


        $vote_info=$d_plugin_vote->vote_detail($vote_id,true);
        if (0==$vote_info['vote_status']) {
            returnCode(62, [], '投票活动还未开始，请稍后再试！');
        } elseif (2==$vote_info['vote_status']) {
            returnCode(62, [], '投票活动已经结束了！');
        } elseif (3==$vote_info['vote_status']) {
            returnCode(62, [], '投票活动已经取消了！');
        } elseif (4==$vote_info['vote_status']) {
            returnCode(62, [], '投票活动已经取消了！');
        }
        $block_info = $_POST['block_info'];
        if (empty($block_info)) {
            returnCode(63, [], '请上传投票数据！');
        }
        if (!is_array($block_info)) {
            returnCode(64, [], '请上传投票数据正确格式！');
        }
        $vote_block = $vote_info['vote_block'];
        $join_data = array();
        $join_data['vote_id'] = $vote_id;
        $where_vote_block = array(
            'vote_id' => $vote_id
        );
        if ($this->_uid>0) {
            $join_data['uid'] = $this->_uid;
            $where_vote_block['uid'] = $this->_uid;
        }
        if ($this->village_bind && $this->village_bind['pigcms_id']>0) {
            $join_data['bind_id'] = $this->village_bind['pigcms_id'];
            $where_vote_block['bind_id'] = $this->village_bind['pigcms_id'];
        }
        if ($this->from) {
            $join_data['from'] = $this->from;
            $where_vote_block['from'] = $this->from;
        }
        if ($this->from_id) {
            $join_data['from_id'] = $this->from_id;
            $where_vote_block['from_id'] = $this->from_id;
        }

        $join_data['join_ip'] = get_client_ip();

        $d_plugin_vote_join = M('Plugin_vote_join');

        if ($vote_info['limit_num']>1) {
            $join_vote_msg = [];
        } else {
            $join_vote_msg = $d_plugin_vote_join->where($where_vote_block)->find();
        }

        $now_time = time();
        //开启事务
        $d_plugin_vote_join->startTrans();
        $dis_key = time();
        foreach ($block_info as $val) {
            if (!$val['block_id']) {
                returnCode(65, [], '请上传对应区块id！');
                break;
            }
            if (!$vote_block[$val['block_id']]) {
                returnCode(65, [], '请上传对应正确区块id！');
                break;
            }
            $join = $join_data;
            $join['block_id'] = intval($val['block_id']);
            $block_msg = $vote_block[$val['block_id']];
            $where_vote_block['block_id'] = intval($val['block_id']);
            if ($vote_info['limit_num']>1) {
                $join_info = [];
            } else {
                $join_info = $d_plugin_vote_join->where($where_vote_block)->find();
                if ($join_info && 2==$vote_info['limit_user_set']) {
                    fdump('==========当前投票后不可修改================'.__LINE__, 'vote_join', true);
                    fdump($where_vote_block, 'vote_join', true);
                    fdump($d_plugin_vote_join, 'vote_join', true);
                    returnCode(65, [], '当前投票后不可修改！');
                    break;
                } elseif (!$join_info) {
                    $join_info = array();
                }
            }

            if(3==$block_msg['block_type'] || 4==$block_msg['block_type']) {
                if ((3==$block_msg['block_type'] || 4==$block_msg['block_type']) && !$val['choose_option_id'] && 1==$val['is_write']) {
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(65, [], "请选择【{$val['block_title']}】对应选项！");
                    break;
                }
                if (4==$block_msg['block_type'] && !is_array($val['choose_option_id'])) {
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(65, [], "请选择【{$val['block_title']}】正确选项信息！");
                    break;
                }
                $vote_option = $block_msg['info_options'];
                if (3==$block_msg['block_type'] && !$vote_option[$val['choose_option_id']] && 1==$val['is_write']) {
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(65, [], "请选择【{$val['block_title']}】正确选项信息！");
                    break;
                }
                if (4==$block_msg['block_type']) {
                    foreach ($val['choose_option_id'] as $item) {
                        if (!$vote_option[$item] && 1==$val['is_write']) {
                            if (empty($join_vote_msg)) {
                                unset($where_vote_block['block_id']);
                                $where_vote_block['dis_key'] = $dis_key;
                                $d_plugin_vote_join->where($where_vote_block)->delete();
                            }
                            returnCode(65, [], '请选择正确选项信息！');
                            break;
                        }
                        if (1==$vote_option[$item]['option_type'] && empty(trim($val['option_txt']))) {
                            if (empty($join_vote_msg)) {
                                unset($where_vote_block['block_id']);
                                $where_vote_block['dis_key'] = $dis_key;
                                $d_plugin_vote_join->where($where_vote_block)->delete();
                            }
                            returnCode(65, [], "请填写【{$val['block_title']}】其他选项信息！");
                            break;
                        }
                        $join['option_txt'] = trim($val['option_txt']);
                    }
                    $join['option_id'] = implode(",",$val['choose_option_id']);
                } elseif (3==$block_msg['block_type']) {
                    $join['option_id'] = intval($val['choose_option_id']);
                    if (1==$vote_option[$val['choose_option_id']]['option_type'] && empty(trim($val['option_txt']))) {
                        if (empty($join_vote_msg)) {
                            unset($where_vote_block['block_id']);
                            $where_vote_block['dis_key'] = $dis_key;
                            $d_plugin_vote_join->where($where_vote_block)->delete();
                        }
                        returnCode(65, [], "请填写【{$val['block_title']}】其他选项信息！");
                        break;
                    }
                    $join['option_txt'] = trim($val['option_txt']);
                } else {
                    $join['option_num'] = intval($val['option_num']);
                }
                if (!empty($join_info)){
                    $join_id = $join_info['join_id'];
                    $join['join_id'] = $join_id;
                    $join['last_time'] = $now_time;
                    $join['dis_key'] = $dis_key;
                    $join_set = $d_plugin_vote_join->data($join)->save();
                    if (!$join_set) {
                        $join_id = false;
                    }
                } else {
                    $join['add_time'] = $now_time;
                    $join['dis_key'] = $dis_key;
                    $join_id = $d_plugin_vote_join->data($join)->add();
                }
                if (!$join_id) {
                    fdump('==========投票失败================'.__LINE__, 'vote_join', true);
                    fdump($join, 'vote_join', true);
                    fdump($d_plugin_vote_join, 'vote_join', true);
                    $d_plugin_vote_join->rollback();
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(66, [], '投票失败！');
                    break;
                }
            } elseif (1==$block_msg['block_type'] || 2==$block_msg['block_type']) {
                if (1==$val['is_write'] && empty($val['option_txt'])) {
                    $d_plugin_vote_join->rollback();
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(65, [], "请填写【{$val['block_title']}】必填项！");
                    break;
                }
                $join['option_txt'] = trim($val['option_txt']);
                if (!empty($join_info)){
                    $join_id = $join_info['join_id'];
                    $join['join_id'] = $join_id;
                    $join['last_time'] = $now_time;
                    $join['dis_key'] = $dis_key;
                    $join_set = $d_plugin_vote_join->data($join)->save();
                    if (!$join_set) {
                        $join_id = false;
                    }
                } else {
                    $join['add_time'] = $now_time;
                    $join['dis_key'] = $dis_key;
                    $join_id = $d_plugin_vote_join->data($join)->add();
                }
                if (!$join_id) {
                    fdump('==========投票失败================'.__LINE__, 'vote_join', true);
                    fdump($join, 'vote_join', true);
                    fdump($d_plugin_vote_join, 'vote_join', true);
                    $d_plugin_vote_join->rollback();
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(66, [], '投票失败！');
                    break;
                }
            } elseif (5==$block_msg['block_type']) {
                if (1==$val['is_write'] && !isset($val['option_num'])) {
                    $d_plugin_vote_join->rollback();
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(65, [], "请对【{$val['block_title']}】评分！");
                    break;
                }
                $join['option_num'] = intval($val['option_num']);
                if (!empty($join_info)){
                    $join_id = $join_info['join_id'];
                    $join['join_id'] = $join_id;
                    $join['last_time'] = $now_time;
                    $join['dis_key'] = $dis_key;
                    $join_set = $d_plugin_vote_join->data($join)->save();
                    if (!$join_set) {
                        $join_id = false;
                    }
                } else {
                    $join['add_time'] = $now_time;
                    $join['dis_key'] = $dis_key;
                    $join_id = $d_plugin_vote_join->data($join)->add();
                }
                if (!$join_id) {
                    fdump('==========投票失败================'.__LINE__, 'vote_join', true);
                    fdump($join, 'vote_join', true);
                    fdump($join_info, 'vote_join', true);
                    fdump($d_plugin_vote_join, 'vote_join', true);
                    $d_plugin_vote_join->rollback();
                    if (empty($join_vote_msg)) {
                        unset($where_vote_block['block_id']);
                        $where_vote_block['dis_key'] = $dis_key;
                        $d_plugin_vote_join->where($where_vote_block)->delete();
                    }
                    returnCode(66, [], '投票失败！');
                    break;
                }
            }
            // 处理一个对应清除一个 最后剩余的单独处理
            unset($vote_block[$val['block_id']]);
        }
        if (!empty($vote_block)) {
            foreach ($vote_block as $judge) {
                if (1==$judge['is_write']) {
                    fdump('==========请填写必填选项================'.__LINE__, 'vote_join', true);
                    fdump($judge, 'vote_join', true);
                    fdump($vote_block, 'vote_join', true);
                    $d_plugin_vote_join->rollback();
                    returnCode(67, [], '请填写必填选项！');
                    break;
                }
            }
        }
        // 没有错误统一生效
        $d_plugin_vote_join->commit();
        if ($vote_id>0) {
            $d_plugin_vote->statistical_voting($vote_id);
            // 考虑走子计划任务
//            $d_plugin_vote->add_plan($vote_id);
        }
        returnCode(0, array('vote_id'=>$vote_id,'limit_num' => $vote_info['limit_num']));
    }

}