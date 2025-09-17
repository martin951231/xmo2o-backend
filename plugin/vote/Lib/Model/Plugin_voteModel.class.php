<?php

class Plugin_voteModel extends Model
{
    public $vote_status = array(
        0=>'投票未开始',
        1=>'投票进行中',
        2=>'投票已结束',
        3=>'投票已禁止',
        4=>'投票已删除'
    );
    public $status_info = array(
        0=>'禁止',
        1=>'开启',
        2=>'',
        3=>'结束',
        4=>'删除'
    );

    public function vote_detail($vote_id,$is_key=false, $is_time_all=true, $join_where=array()) {
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $this->where($where_vote)->find();
        if (empty($vote_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        $d_plugin_vote_join = M('Plugin_vote_join');
        $d_plugin_vote_block = M('Plugin_vote_block');
        $d_plugin_vote_option = M('Plugin_vote_option');


        if ($join_where) {
            $join_where['vote_id'] = $vote_id;
        }
        $now_time = time();
        if (0==$vote_info['status']) {
            $vote_status = 3;
        } elseif (1==$vote_info['status']) {
            if ($vote_info['start_time']>$now_time) {
                $vote_status = 0;
            } elseif ($vote_info['start_time']<$now_time && $vote_info['end_time']>$now_time) {
                $vote_status = 1;
            } elseif ($vote_info['start_time']<$now_time && $vote_info['end_time']<$now_time) {
                $vote_status = 2;
            }
        } elseif (4==$vote_info['status']) {
            $vote_status = 4;
        }
        $fixed = false;
        if ($vote_info['limit_num']>1 && $join_where) {
            // 判断客户投了几次票
            $num_info = $d_plugin_vote_join->where($join_where)->group('dis_key')->field('join_id')->select();
            $join_num = count($num_info);
            if ($join_num>=$vote_info['limit_num'] && ($vote_status==1||$vote_status==0)) {
                returnCode(31, [], '您的投票次数已用完！');
            }
            // 固定不可编辑只能再次投票
            $fixed = true;
        }

        $where_block = array(
            'vote_id' => $vote_id
        );
        if ($is_key) {
            $block_field = 'block_id, block_id, vote_id, block_title, block_type, is_write, block_sort, block_number, block_last_time';
            $vote_block = $d_plugin_vote_block->where($where_block)->order('block_sort ASC, block_id ASC')->getField($block_field,true);
        } else {
            $vote_block = $d_plugin_vote_block->where($where_block)->order('block_sort ASC, block_id ASC')->select();
        }
        $vote_info['is_join'] = false;
        if (empty($vote_block)) {
            $vote_block = array();
        } else {
            // 循环区块  如果存在单选多选获取选项信息
            foreach ($vote_block as &$item) {
                if ($join_where && (1==$item['block_type'] || 2==$item['block_type'])) {
                    $join_where['block_id'] = $item['block_id'];
                    if ($fixed) {
                        $block_join = [];
                    } else {
                        $block_join = $d_plugin_vote_join->where($join_where)->find();
                    }
                    $item['active'] = '';
                    $item['error'] = '';
                    if ($block_join) {
                        $item['option_txt'] = $block_join['option_txt'];
                        $item['is_join'] = true;
                        $vote_info['is_join'] = true;
                    } else {
                        $item['option_txt'] = '';
                        $item['is_join'] = false;
                    }
                }
                if (5==$item['block_type']){
                    $join_where['block_id'] = $item['block_id'];
                    if ($fixed) {
                        $block_join = [];
                    } else {
                        $block_join = $d_plugin_vote_join->where($join_where)->find();
                    }
                    $item['active'] = '';
                    $item['error'] = '';
                    if ($block_join) {
                        $item['option_txt'] = $block_join['option_txt'];
                        $item['option_num'] = $block_join['option_num'];
                        $item['is_join'] = true;
                        $vote_info['is_join'] = true;
                    } else {
                        $item['option_txt'] = '';
                        $item['option_num'] = '';
                        $item['is_join'] = false;
                    }
                }

                if (3==$item['block_type'] || 4==$item['block_type']) {
                    $where_option = array(
                        'vote_id' => $vote_id,
                        'block_id' => $item['block_id']
                    );
                    if ($is_key) {
                        $option_field = 'option_id, option_id, vote_id, block_id, option_name, option_type, option_img, option_number, last_time';
                        $vote_option = $d_plugin_vote_option->where($where_option)->order('option_id ASC')->getField($option_field,true);
                    } else {
                        $vote_option = $d_plugin_vote_option->where($where_option)->order('option_id ASC')->select();
                    }
                    if ($vote_option) {
                        $item['info_options'] = $vote_option;
                    } else {
                        $item['info_options'] = array();
                    }
                    if ($join_where) {
                        $join_where['block_id'] = $item['block_id'];
                        if ($fixed) {
                            $block_join = [];
                        } else {
                            $block_join = $d_plugin_vote_join->where($join_where)->find();
                        }
                        $item['active'] = '';
                        $item['error'] = '';
                        if ($block_join) {
                            $item['option_txt'] = $block_join['option_txt'];
                            if (3==$item['block_type']) {
                                $item['choose_option_id'] = $block_join['option_id'];
                            } else {
                                $item['choose_option_id'] = explode(",",$block_join['option_id']);
                            }
                            $item['is_join'] = true;
                            $vote_info['is_join'] = true;
                        } else {
                            $item['option_txt'] = '';
                            if (3==$item['block_type']) {
                                $item['choose_option_id'] = '';
                            } else {
                                $item['choose_option_id'] = array();
                            }
                            $item['is_join'] = false;
                        }
                    }
                }
            }
        }
        $vote_info['vote_block'] = $vote_block;

        // 解析下时间
        $vote_info['date'] = array();
        if ($vote_info['start_time']>0) {
            if ($is_time_all) {
                $vote_info['start_time_txt'] = date('Y-m-d H:i:s',$vote_info['start_time']);
            } else {
                $vote_info['start_time_txt'] = date('Y-m-d H:i',$vote_info['start_time']);
            }
            $vote_info['date'][0] = $vote_info['start_time_txt'];
        }
        if ($vote_info['end_time']>0) {
            if ($is_time_all) {
                $vote_info['end_time_txt'] = date('Y-m-d H:i:s',$vote_info['end_time']);
            } else {
                $vote_info['end_time_txt'] = date('Y-m-d H:i',$vote_info['end_time']);
            }
            $vote_info['date'][1] = $vote_info['end_time_txt'];
        }

        // 一些信息处理
        if ($vote_info['vote_image']) {
            $vote_info['vote_image'] = unserialize($vote_info['vote_image']);
            // 存在图片，处理图片路径
            if ($vote_info['vote_image']) {
                foreach ($vote_info['vote_image'] as &$val) {
                    $val['file_url'] =  replace_file_domain($val['url']);
                }
            }
        }
        $vote_info['vote_status'] = $vote_status;
        $vote_info['vote_status_txt'] = $this->vote_status[$vote_status];
        $vote_info['status_txt'] = $this->status_info[$vote_info['status']];

        return $vote_info;
    }

    public function vote_list($where=array(), $page=1, $page_size=20, $is_time_all=true, $join_where=array(), $order='vote_sort DESC,add_time DESC',$field=true) {
        if (!$where['status']) {
            $where['status'] = array('neq', 4);
        }
        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $this->where($where)->count();
        $total_page = ceil($count/$page_size);
        if (empty($order)) {
            $order = 'vote_sort DESC,add_time DESC';
        }
        $list = $this->where($where)->field($field)->limit($limit)->order($order)->select();
        $d_plugin_vote_join = D('Plugin_vote_join');

        if (empty($list)) {
            $list = array();
        } else {
            $now_time = time();
            foreach ($list as &$val) {
                if ($val['vote_image']) {
                    $val['vote_image'] = unserialize($val['vote_image']);
                    // 存在图片，处理图片路径
                    if ($val['vote_image']) {
                        foreach ($val['vote_image'] as &$item) {
                            $item['file_url'] =  replace_file_domain($item['url']);
                        }
                    }
                }
                if (0==$val['status']) {
                    $vote_status = 3;
                } elseif (1==$val['status']) {
                    if ($val['start_time']>$now_time) {
                        $vote_status = 0;
                    } elseif ($val['start_time']<$now_time && $val['end_time']>$now_time) {
                        $vote_status = 1;
                    } elseif ($val['start_time']<$now_time && $val['end_time']<$now_time) {
                        $vote_status = 2;
                    }
                } elseif (4==$val['status']) {
                    $vote_status = 4;
                }
                $val['vote_status'] = $vote_status;
                $val['vote_status_txt'] = $this->vote_status[$vote_status];
                // 解析下时间
                if ($val['start_time']>0) {
                    if ($is_time_all) {
                        $val['start_time_txt'] = date('Y-m-d H:i:s',$val['start_time']);
                    } else {
                        $val['start_time_txt'] = date('Y-m-d H:i',$val['start_time']);
                    }
                }
                if ($val['end_time']>0) {
                    if ($is_time_all) {
                        $val['end_time_txt'] = date('Y-m-d H:i:s',$val['end_time']);
                    } else {
                        $val['end_time_txt'] = date('Y-m-d H:i',$val['end_time']);
                    }
                }
                $val['status_txt'] = $this->status_info[$val['status']];
                if ($join_where) {
                    $join_where['vote_id'] = $val['vote_id'];
                    $join_info = $d_plugin_vote_join->where($join_where)->find();
                    if ($join_info) {
                        $val['is_join']=true;
                    } else {
                        $val['is_join']=false;
                    }
                }
            }
        }

        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        return $data;
    }

    /** 统计投票
     * @param $vote_id
     * @return int|void
     */
    public function statistical_voting($vote_id) {
        if ($vote_id<=0) {
            return;
        }
        $where = array();
        $where['vote_id'] = $vote_id;
        $vote_info = $this->where($where)->find();
        if (empty($vote_info)) {
            return;
        }
        $d_plugin_vote_join = M('Plugin_vote_join');
        $where_join = array(
            'vote_id' =>$vote_id
        );
        // 统计投票参与人数（按人次计算）
        $vote_join = $d_plugin_vote_join->where($where_join)->group('vote_id, uid, bind_id, dis_key')->field('join_id')->select();
        $vote_join_count = count($vote_join);
        if (intval($vote_info['vote_number'])!=$vote_join_count) {
            $vote_set = $this->where($where)->setField('vote_number', $vote_join_count);
            if (!$vote_set) {
                fdump('==========vote_set============','api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                fdump($vote_join_count,'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                fdump($this->getDbError(),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                fdump($this->getLastSql(),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
            }
        }
        // 统计投票区块参与人数（按人次计算）
        $d_plugin_vote_block = M('Plugin_vote_block');
        $vote_block_info = $d_plugin_vote_block->where($where)->select();
        if (empty($vote_block_info)) {
            return;
        }
        $where_join = array(
            'vote_id' =>$vote_id
        );
        foreach ($vote_block_info as $item) {
            $where_join['block_id'] = $item['block_id'];
            $vote_block_join = $d_plugin_vote_join->where($where_join)->group('vote_id, uid, bind_id,dis_key')->field('join_id')->select();
            $vote_block_join_count = count($vote_block_join);
            if (intval($item['block_number'])!=$vote_block_join_count) {
                $vote_block_set = $d_plugin_vote_block->where(array('block_id' => $item['block_id']))->setField('block_number', $vote_block_join_count);
                if (!$vote_block_set) {
                    fdump('==========vote_block_set============','api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump($vote_block_join_count,'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump($d_plugin_vote_block->getDbError(),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump($d_plugin_vote_block->getLastSql(),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                }
            }
        }
        // 统计投票区块选择题选项参与人数（同一个用户-绑定身份用户记1）
        $d_plugin_vote_option = M('Plugin_vote_option');
        $vote_block_info = $d_plugin_vote_option->where($where)->select();
        if (empty($vote_block_info)) {
            return;
        }
        $where_join = array(
            'vote_id' =>$vote_id
        );
        foreach ($vote_block_info as $item) {
            $where_join['_string'] = "FIND_IN_SET({$item['option_id']},option_id)";
            $vote_option_join = $d_plugin_vote_join->where($where_join)->group('vote_id, uid, bind_id,dis_key')->field('join_id')->select();
            $vote_option_join_count = count($vote_option_join);
            if (intval($item['option_number'])!=$vote_option_join_count) {
                $vote_option_set = $d_plugin_vote_option->where(array('option_id' => $item['option_id']))->setField('option_number', $vote_option_join_count);
                if (!$vote_option_set) {
                    fdump('==========vote_option_set============','api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump($vote_option_join_count,'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump($d_plugin_vote_option->getDbError(),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                    fdump($d_plugin_vote_option->getLastSql(),'api/log/'.date('Ymd').'/statistical_vote_error_log',true);
                }
            }
        }
        return true;
    }

    public function add_plan($vote_id) {
        $now_time = time();
        $arr= array();
        $arr['param'] = serialize(array('vote_id'=>$vote_id));
        $arr['plan_time'] = 0;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_statistical_vote';
        $arr['time_type'] = 1;
        $arr['unique_id'] = 'plat_statistical_vote_'.$vote_id;
        M('process_sub_plan')->data($arr)->add();
    }

    /**
     * 投票统计详情
     * @param $vote_id
     * @param bool $is_time_all
     * @return mixed
     */
    public function statistical_vote_detail($vote_id, $is_time_all=true) {
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $this->where($where_vote)->find();
        if (empty($vote_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        $d_plugin_vote_join = M('Plugin_vote_join');
        $d_plugin_vote_block = M('Plugin_vote_block');
        $d_plugin_vote_option = M('Plugin_vote_option');

        $where_block = array(
            'vote_id' => $vote_id
        );
        $vote_block = $d_plugin_vote_block->where($where_block)->order('block_sort ASC, block_id ASC')->select();

        $where_join = array(
            'vote_id' => $vote_id
        );
        // 初始化判断条件
        $limit_type = 1;
        // 默认业主总人数
        $owner_total = 0;
        // 默认业主总面积
        $owner_house_size_total = 0;
        // 整合 社区条件
        $join_bind_arr = array();
        $vote_block_join_arr = array();
        $d_house_village_user_bind = M('House_village_user_bind');
        if ($limit_type==$vote_info['limit_type'] && 'house' == $vote_info['from']) {
            $vote_join = $d_plugin_vote_join->where($where_join)->select();
            if ($vote_join) {
                foreach ($vote_join as $val) {
                    if (!$vote_block_join_arr[$val['block_id']]) {
                        $vote_block_join_arr[$val['block_id']] = array();
                    }
                    $vote_block_join_arr[$val['block_id']][] = $val['bind_id'];
                    if(strpos($val['option_id'],',')!==false){
                        $option_id_arr = explode(',',$val['option_id']);
                    }else{
                        $option_id_arr=array($val['option_id']);
                    }
                    if($option_id_arr){
                        foreach ($option_id_arr as $option_id){
                            if ($option_id>0 && !isset($join_bind_arr[$option_id])) {
                                $join_bind_arr[$option_id] = array($val['bind_id']);
                            }else if ($option_id>0 ) {
                                $join_bind_arr[$option_id][] = $val['bind_id'];
                                $join_bind_arr[$option_id]= array_unique($join_bind_arr[$option_id]);
                            }
                        }
                    }

                }
            }
            // 获取业主总人数
            // 获取业主总面积
            $village_id = $vote_info['from_id'];
            $bind_msg = $d_house_village_user_bind->field('COUNT(pigcms_id) as owner_total, SUM(housesize) as owner_house_size_total')->where(array('village_id' => $village_id, 'type' => array('in', array(0,3)), 'status' => 1))->find();
            if ($bind_msg && $bind_msg['owner_total']) {
                $owner_total = $bind_msg['owner_total'];
            }
            if ($bind_msg && $bind_msg['owner_house_size_total']) {
                $owner_house_size_total = $bind_msg['owner_house_size_total'];
            }
        }
        $vote_info['owner_total'] = $owner_total;
        $vote_info['owner_house_size_total'] = $owner_house_size_total;


        if (empty($vote_block)) {
            $vote_block = array();
        } else {
            // 循环区块  如果存在单选多选获取选项信息
            foreach ($vote_block as &$item) {
                // 区块总参与人数 getFormatNumber
                $block_number = intval($item['block_number']);
                if (3==$item['block_type'] || 4==$item['block_type']) {

                    // 处理面积比
                    if ($limit_type==$vote_info['limit_type'] && 'house' == $vote_info['from']) {
                        // 处理面积
                        if ($vote_block_join_arr && $vote_block_join_arr[$item['block_id']]) {
                            $block_where = array(
                                'village_id' => $vote_info['from_id'],
                                'type' => array('in', array(0,3)),
                                'status' => 1,
                                'pigcms_id' => array('in', $vote_block_join_arr[$item['block_id']])
                            );
                            $bind_block_msg = $d_house_village_user_bind->field('SUM(housesize) as block_house_size_total')->where($block_where)->find();
                            if ($bind_block_msg && $bind_block_msg['block_house_size_total']) {
                                $item['block_house_size_total'] = getFormatNumber($bind_block_msg['block_house_size_total'],2);
                            } else {
                                $item['block_house_size_total'] = 0;
                            }
                        }
                    }
                    $item['owner_total'] = $owner_total;
                    $item['owner_house_size_total'] = getFormatNumber($owner_house_size_total,2);;

                    $where_option = array(
                        'vote_id' => $vote_id,
                        'block_id' => $item['block_id']
                    );
                    $vote_option = $d_plugin_vote_option->where($where_option)->order('option_id ASC')->select();
                    if ($vote_option) {
                        // 计算每个选择项占比
                        foreach ($vote_option as &$option_item) {
                            if ($block_number<=0) {
                                $option_item['proportion'] = 0;
                            } elseif (intval($option_item['option_number'])>0) {
                                // 按照百分比算 计算出来是0-100的值
                                $option_item['proportion'] = (intval($option_item['option_number'])/$block_number) * 100;
                                $option_item['proportion'] = getFormatNumber($option_item['proportion'],2);
                            } else {
                                $option_item['proportion'] = 0;
                            }
                            if (intval($option_item['option_number'])>0 && $owner_total>0) {
                                // 计算与总人数比
                                $option_item['proportion_total'] = (intval($option_item['option_number'])/$owner_total) * 100;
                                $option_item['proportion_total'] = getFormatNumber($option_item['proportion_total'],2);
                            } else {
                                $option_item['proportion_total'] = 0;
                            }
                            // 处理面积比
                            if ($limit_type==$vote_info['limit_type'] && 'house' == $vote_info['from']) {
                                $option_item['option_house_size_total'] = 0;
                                $option_item['option_house_size_proportion'] = 0;
                                $option_item['option_house_size_proportion_total'] = 0;
                                // 处理面积
                                if ($join_bind_arr && $join_bind_arr[$option_item['option_id']]) {
                                    $option_where = array(
                                        'village_id' => $vote_info['from_id'],
                                        'type' => array('in', array(0,1,2,3)),
                                        'status' => 1,
                                        'pigcms_id' => array('in', $join_bind_arr[$option_item['option_id']])
                                    );
                                    $bind_option_msg = $d_house_village_user_bind->field('SUM(housesize) as option_house_size_total')->where($option_where)->find();
                                    if ($bind_option_msg && $bind_option_msg['option_house_size_total']) {
                                        $option_item['option_house_size_total'] = getFormatNumber($bind_option_msg['option_house_size_total'],2);
                                    }
                                    // 该选项专有面积 对比 整个题目 专有面积比
                                    if (floatval($option_item['option_house_size_total'])>0 && floatval($item['block_house_size_total']) > 0) {
                                        $option_item['option_house_size_proportion'] = (floatval($option_item['option_house_size_total'])/floatval($item['block_house_size_total'])) * 100;
                                        $option_item['option_house_size_proportion'] = getFormatNumber($option_item['option_house_size_proportion'],2);
                                    }
                                    // 该选项专有面积 对比 整个小区业主 专有面积比
                                    if (floatval($option_item['option_house_size_total'])>0 && floatval($owner_house_size_total) > 0) {
                                        $option_item['option_house_size_proportion_total'] = (floatval($option_item['option_house_size_total'])/floatval($owner_house_size_total)) * 100;
                                        $option_item['option_house_size_proportion_total'] = getFormatNumber($option_item['option_house_size_proportion_total'],2);
                                    }
                                }
                            }
                        }

                        $item['info_options'] = $vote_option;
                    } else {
                        $item['info_options'] = array();
                    }
                } elseif (5==$item['block_type']) {
                    $where_join['block_id'] = $item['block_id'];
                    $block_join = $d_plugin_vote_join->field('SUM(option_num) as option_num_total')->where($where_join)->find();
                    if ($block_join && $block_join['option_num_total']>0) {
                        $option_num_total = $block_join['option_num_total'];
                        $item['option_num_total'] = $option_num_total;
                        $item['average'] = $option_num_total / $block_number;
                        $item['average'] = getFormatNumber($item['average'],2);
                    } else {
                        $item['option_num_total'] = 0;
                        $item['average'] = 0;
                    }
                }
            }
        }
        $vote_info['vote_block'] = $vote_block;

        // 解析下时间
        $vote_info['date'] = array();
        if ($vote_info['start_time']>0) {
            if ($is_time_all) {
                $vote_info['start_time_txt'] = date('Y-m-d H:i:s',$vote_info['start_time']);
            } else {
                $vote_info['start_time_txt'] = date('Y-m-d H:i',$vote_info['start_time']);
            }
            $vote_info['date'][0] = $vote_info['start_time_txt'];
        }
        if ($vote_info['end_time']>0) {
            if ($is_time_all) {
                $vote_info['end_time_txt'] = date('Y-m-d H:i:s',$vote_info['end_time']);
            } else {
                $vote_info['end_time_txt'] = date('Y-m-d H:i',$vote_info['end_time']);
            }
            $vote_info['date'][1] = $vote_info['end_time_txt'];
        }

        // 一些信息处理
        if ($vote_info['vote_image']) {
            $vote_info['vote_image'] = unserialize($vote_info['vote_image']);
            // 存在图片，处理图片路径
            if ($vote_info['vote_image']) {
                foreach ($vote_info['vote_image'] as &$val) {
                    $val['file_url'] =  replace_file_domain($val['url']);
                }
            }
        }
        $now_time = time();
        if (0==$vote_info['status']) {
            $vote_status = 3;
        } elseif (1==$vote_info['status']) {
            if ($vote_info['start_time']>$now_time) {
                $vote_status = 0;
            } elseif ($vote_info['start_time']<$now_time && $vote_info['end_time']>$now_time) {
                $vote_status = 1;
            } elseif ($vote_info['start_time']<$now_time && $vote_info['end_time']<$now_time) {
                $vote_status = 2;
            }
        } elseif (4==$vote_info['status']) {
            $vote_status = 4;
        }
        $vote_info['vote_status'] = $vote_status;
        $vote_info['vote_status_txt'] = $this->vote_status[$vote_status];
        $vote_info['status_txt'] = $this->status_info[$vote_info['status']];

        return $vote_info;
    }

    /**
     * 投票区块
     * @param $block_id
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function vote_block_list($block_id, $page=1, $page_size=20) {
        $where_block = array(
            'block_id' => $block_id
        );
        $d_plugin_vote_join = M('Plugin_vote_join');
        $d_plugin_vote_block = M('Plugin_vote_block');
        $d_plugin_vote_option = M('Plugin_vote_option');

        $vote_block = $d_plugin_vote_block->where($where_block)->find();
        if (empty($vote_block)) {
            returnCode(31, [], '获取对象不存在！');
        }
        $vote_id = $vote_block['vote_id'];
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $this->where($where_vote)->field('vote_id, vote_title, vote_des, limit_type, from, from_id')->find();

        if (3==$vote_block['block_type'] || 4==$vote_block['block_type']) {
            $option_field = 'option_id, option_id, vote_id, block_id, option_name, option_type, option_number, option_img, last_time';
            $vote_option = $d_plugin_vote_option->where($where_block)->order('last_time ASC')->getField($option_field,true);
        } else {
            $vote_option = array();
        }

        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $d_plugin_vote_join->where($where_block)->count();
        $total_page = ceil($count/$page_size);
        $list = $d_plugin_vote_join->where($where_block)->limit($limit)->order('last_time DESC,add_time DESC')->select();

        // 初始化判断条件
        $limit_type = 1;
        // 默认业主总面积
        $owner_house_size_total = 0;
        // 整合 社区条件
        $vote_block_join_arr = array();
        $d_house_village_user_bind = M('House_village_user_bind');
        if ($limit_type==$vote_info['limit_type'] && 'house' == $vote_info['from']) {
            $where_join = array(
                'vote_id' => $vote_id,
                'block_id' => $block_id,
            );
            $vote_join = $d_plugin_vote_join->where($where_join)->select();
            if ($vote_join) {
                foreach ($vote_join as $val) {
                    $vote_block_join_arr[] = $val['bind_id'];
                }
            }
            // 获取业主总人数
            // 获取业主总面积
            $village_id = $vote_info['from_id'];
            $bind_msg = $d_house_village_user_bind->field('SUM(housesize) as owner_house_size_total')->where(array('village_id' => $village_id, 'type' => array('in', array(0,3)), 'status' => 1))->find();
            if ($bind_msg && $bind_msg['owner_house_size_total']) {
                $owner_house_size_total = $bind_msg['owner_house_size_total'];
            }
            // 获取区块题目总面积
            $block_where = array(
                'village_id' => $vote_info['from_id'],
                'type' => array('in', array(0,3)),
                'status' => 1,
                'pigcms_id' => array('in', $vote_block_join_arr)
            );
            $bind_block_msg = $d_house_village_user_bind->field('SUM(housesize) as block_house_size_total')->where($block_where)->find();
            if ($bind_block_msg && $bind_block_msg['block_house_size_total']) {
                $vote_block['block_house_size_total'] = getFormatNumber($bind_block_msg['block_house_size_total'],2);
            } else {
                $vote_block['block_house_size_total'] = 0;
            }
        }
        $vote_info['owner_house_size_total'] = getFormatNumber($owner_house_size_total,2);


        // 计算统计总评分
        $option_num_total = 0;
        if (empty($list)) {
            $list = array();
        } else {
            // 循环区块  如果存在单选多选获取选项信息
            $d_house_village_user_bind = M('House_village_user_bind');
            $d_user = M('User');
            foreach ($list as &$item) {
               // if ('house' == $item['from'] && $item['bind_id']>0) {;
                    if (in_array($item['from'],['house','community','street']) && $item['bind_id']>0) {
                    $bind_info = $d_house_village_user_bind->where(array('pigcms_id' => $item['bind_id']))->find();
                    
                    if ($bind_info && $bind_info['housesize']>0) {
                        $housesize = $bind_info['housesize'];
                        $item['housesize'] = getFormatNumber($housesize,2);
                        // 该选项专有面积 对比 整个题目 专有面积比
                        if (floatval($housesize)>0 && floatval($vote_block['block_house_size_total']) > 0) {
                            $item['house_size_proportion'] = (floatval($housesize)/floatval($vote_block['block_house_size_total'])) * 100;
                            $item['house_size_proportion'] = getFormatNumber($item['house_size_proportion'],2);
                        }
                        // 该选项专有面积 对比 整个小区业主 专有面积比
                        if (floatval($housesize)>0 && floatval($owner_house_size_total) > 0) {
                            $item['house_size_proportion_total'] = (floatval($housesize)/floatval($owner_house_size_total)) * 100;
                            $item['house_size_proportion_total'] = getFormatNumber($item['house_size_proportion_total'],2);
                        }
                    } else {
                        $item['housesize'] = 0;
                    }
                    // 处理投票者的基本信息
                    if ($bind_info) {
                        $item['name'] = $bind_info['name'];
                        $item['phone'] = $bind_info['phone'];
                        $item['address'] = PD('House_village')->get_single_floor_room($bind_info['single_id'],$bind_info['floor_id'],$bind_info['layer_id'],$bind_info['vacancy_id'],$bind_info['village_id']);
                        $house_type = M('House_village_user_vacancy')->where(['pigcms_id'=>$bind_info['vacancy_id']])->getField('house_type');
                        switch ($bind_info['type']){
                            case 0:
                                $item['relative'] = '业主';
                                break;
                            case 1:
                                $item['relative'] = '家属';
                                break;
                            case 2:
                                if($house_type == 1){
                                    $item['relative'] = '租客';
                                }else{
                                    $item['relative'] = '租客/员工';
                                }
                                break;
                            default:
                                $item['relative'] = '业主';
                        }
                    }
                }
                if (empty($item['name']) && $item['uid']>0) {
                    $user_info = $d_user->where(array('uid'=>$item['uid']))->field('nickname, phone')->find();
                    // 处理投票者的基本信息
                    if ($user_info) {
                        $item['name'] = $user_info['nickname'];
                        $item['phone'] = $user_info['phone'];
                    }
                }
                // 如果是选项获取选中选项信息
                if ($vote_option && $item['option_id']) {
                    $option_id = explode(',', $item['option_id']);
                    $choose_info = array();
                    if ($option_id) {
                        foreach ($option_id as $val) {
                            if ($vote_option[$val]) {
                                $vote_option_info = $vote_option[$val];
                            } else {
                                $vote_option_info = $d_plugin_vote_option->where(array('option_id' => $val))->find();
                            }
                            if (1==$vote_option_info['option_type']) {
                                $choose_info[] = '【其他】：'. $item['option_txt'];
                            } else {
                                $choose_info[] = $vote_option_info['option_name'];
                            }
                        }
                    }
                    if ($choose_info) {
                        $item['vote_option_info'] = implode(',', $choose_info);
                    }
                } elseif ($item['option_txt']) {
                    $item['vote_option_info'] = $item['option_txt'];
                }
                // 如果是评分记录总分数
                if (5==$vote_block['block_type'] && $item['option_num']>0) {
                    $option_num_total += $item['option_num'];
                }
                if ($item['last_time']) {
                    $item['vote_time'] = date('Y-m-d H:i',$item['last_time']);
                } elseif ($item['add_time']) {
                    $item['vote_time'] = date('Y-m-d H:i',$item['add_time']);
                }
            }
        }

        $data = array();
        $data['option_num_total'] = $option_num_total;
        $data['house_size_total'] = $vote_block['block_house_size_total'];
        $data['vote_info'] = $vote_info;
        $data['vote_block'] = $vote_block;
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        return $data;
    }
}