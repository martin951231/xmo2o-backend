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
        redirect(C('config.site_url') . '/packapp/vote/index.html');
	}

    /**
     * 添加投票
     */
    public function add_vote() {
        // 字段过滤
        $vote_title = I('vote_title','','trim');
        if (empty($vote_title)) {
            returnCode(1, [], '请填写投票标题！');
        }
        $vote_des = I('vote_des','','trim'); // 投票描述 暂定为非必填
        $vote_des_style = I('vote_des_style','','trim'); // 投票描述 样式 暂定为非必选 默认为 样式 居中 text-align: center;
        if (!empty($vote_des) && empty($vote_des_style)) {
            // 如果存在投票描述 不存在样式 添加默认样式 居中
            $vote_des_style = 'text-align: center;';
        }
        // 图片
        $vote_image = $_POST['vote_image'];
        if ($vote_image) {
            // 数组存储多张图片  title url file_url
            $vote_image = serialize($vote_image);
        }

        // 投票开始和结束时间 前端需要传时间戳
        $start_time = I('start_time');
        if (empty($start_time)) {
            returnCode(2, [], '请选择投票开始时间！');
        }
        $end_time = I('end_time');
        if (empty($end_time)) {
            returnCode(2, [], '请选择投票结束时间！');
        }
        $now_time = time();
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($now_time>$end_time) {
            returnCode(2, [], '投票结束时间必须大于当前时间！');
        }
        if ($start_time>$end_time) {
            returnCode(2, [], '投票结束时间必须大于开始时间！');
        }
        // 状态 0 禁止 1 开启 3结束 4删除
        $status = I('status','1');
        if (!in_array($status, array(0,1,3,4))) {
            returnCode(6, [], '传递状态错误！');
        }
        // 限制对象 例如 from为house 代表的是 0 社区成员可投票 1 只有业主可投票
        $limit_type = I('limit_type','0');
        // 每人可投票次数（默认每人一票）
        $limit_num = I('limit_num','1');
        // 限制用户修改  1 投票后可修改  2 投票后不可修改
        $limit_user_set = I('limit_user_set','1');
        // 限制公开  1 公开  2 不公开（参与者可见）
        $limit_open = I('limit_open','1');
        // 限制创建者修改  1 添加后可修改   2 添加后只可产看不可修改
        $limit_creator_set = I('limit_creator_set','1');
        // 投票排序值 数字越大越靠前
        $vote_sort = I('vote_sort','0');

        // 关于来源处理
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        $from_type = $returnArr['from_type'];
        $from_name = $returnArr['from_name'];


        //实例化M()方法
        $Model = M();
        //开启事务
        $Model->startTrans();
        $vote_data = array(
            'vote_title' => $vote_title,
            'vote_des' => $vote_des,
            'vote_des_style' => $vote_des_style,
            'vote_image' => $vote_image ? $vote_image : '',
            'start_time' => $start_time,
            'end_time' => $end_time,
            'status' => $status,
            'limit_type' => $limit_type,
            'limit_num' => $limit_num,
            'limit_user_set' => $limit_user_set,
            'limit_open' => $limit_open,
            'limit_creator_set' => $limit_creator_set,
            'vote_sort' => $vote_sort,
            'from' => $from,
            'from_id' => $from_id,
            'from_type' => $from_type,
            'from_name' => $from_name,
            'add_time' => $now_time,
        );
        $vote_id = M('Plugin_vote')->data($vote_data)->add();
        if (!$vote_id) {
            $Model->rollback();
            fdump('==========投票添加失败================'.__LINE__, 'add_vote', true);
            fdump($vote_data, 'add_vote', true);
            returnCode(4, [], '投票添加失败！');
        }
        // 以上是投票主体内容 pigcms_plugin_vote
        // 以下是投票区块内容 pigcms_plugin_vote_block
        $vote_block = $_POST['vote_block'];
        fdump($vote_block, '$vote_block', true);
        if (empty($vote_block)) {
            returnCode(30, [], '至少设置一个填写选择项！');
        }
        // 考虑对应顺序值 存储 选项信息
        $vote_option = I('vote_option');

        $block_sort = array();
        $d_plugin_vote_block = M('Plugin_vote_block');
        $d_plugin_vote_option = M('Plugin_vote_option');

        foreach ($vote_block as $key=>$item) {
            if (empty(trim($item['block_title']))) {
                $Model->rollback();
                returnCode(30, [], '请填写题目！');
                break;
            }
            if (empty($item['block_type'])) {
                $Model->rollback();
                returnCode(30, [], '请选择题目类型！');
                break;
            }
            if (empty($item['block_sort'])) {
                // 顺序值  1,2,3,4   其 中单选多选 信息  对应 顺序值关联 没有顺序值取键值
                $item['block_sort'] = $key;
            }
//            if($block_sort && in_array(intval($item['block_sort']), $block_sort)){
//                // 顺序值  1,2,3,4   其 中单选多选 信息  对应 顺序值关联 顺序值不允许重复
//                $Model->rollback();
//                returnCode(30, [], '顺序值重复！');
//                break;
//            }else {
//                // 顺序值存储起来
//                $block_sort[] = intval($item['block_sort']);
//            }
            if ((3==intval($item['block_type']) || 4==intval($item['block_type']))) {
                $block_sort_key = intval($item['block_sort']);
                // 考虑对应顺序值 存储 选项信息
                $option = $vote_option[$block_sort_key];
                if (empty($option) && $item['info_options']) {
                    // 考虑直接双层数组上传选项信息
                    $option = $item['info_options'];
                }
            }
            if ((3==intval($item['block_type']) || 4==intval($item['block_type'])) && (empty($option) || count($option)<=1)) {
                // 如果是单选和多选处理选项 记录
                $Model->rollback();
                returnCode(3, [], '请至少设置两个选项！');
                break;
            }

            if (empty($item['is_write'])) {
                $Model->rollback();
                returnCode(3, [], '请选择题目是否必填！');
                break;
            }
            $block = array();
            $block['vote_id'] = $vote_id;
            $block['block_title'] = trim($item['block_title']);
            $block['block_type'] = intval($item['block_type']);
            $block['is_write'] = $item['is_write'];
            $block['block_sort'] = $item['block_sort'];
            $block['block_last_time'] = $now_time;
            $block_id = $d_plugin_vote_block->data($block)->add();
            if (!$block_id) {
                $Model->rollback();
                fdump('==========投票区块添加失败================'.__LINE__, 'add_vote', true);
                fdump($block, 'add_vote', true);
                returnCode(4, [], '投票添加失败！');
                break;
            } elseif (3==intval($item['block_type']) || 4==intval($item['block_type']) && $option) {
                // 如果是单选和多选处理选项 记录
                $option_arr = array();
                $option_data = array();
                $option_data['vote_id'] = $vote_id;
                $option_data['block_id'] = $block_id;
                foreach ($option as $val) {
                    if (1==intval($val['option_type']) && empty(trim($val['option_name']))) {
                        $Model->rollback();
                        returnCode(3, [], '请填写选项名称！');
                        break;
                    }
                    $option_data['option_type'] = intval($val['option_type']);
                    if (0==intval($val['option_type']) && empty($val['option_name'])) {
                        $option_data['option_name'] = '其他';
                    } else {
                        $option_data['option_name'] = trim($val['option_name']);
                    }
                    $option_data['last_time'] = $now_time;
                    $option_arr[] = $option_data;
                }
                if (empty($option_data)) {
                    $Model->rollback();
                    fdump('==========请正确设置选项================'.__LINE__, 'add_vote', true);
                    fdump($option, 'add_vote', true);
                    fdump($item, 'add_vote', true);
                    returnCode(3, [], '请正确设置选项！');
                    break;
                }
                $add_id = $d_plugin_vote_option->addAll($option_arr);
                if (!$add_id) {
                    $Model->rollback();
                    fdump('==========投票区块添加失败================'.__LINE__, 'add_vote', true);
                    fdump($option_arr, 'add_vote', true);
                    returnCode(4, [], '投票添加失败！');
                    break;
                }
            }
        }
        // 没有错误统一生效
        $Model->commit();
        returnCode(0, array('vote_id'=>$vote_id));
    }

    /**
     * 投票详情
     * 当前本地测试 进入小区编号42 http://www.group.com/plugin.php?plugin=vote&c=Index&a=vote_detail&from=house&vote_id=1
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
        $vote_info = $d_plugin_vote->where($where_vote)->field('from, from_id')->find();
        if (empty($vote_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $vote_info['from'] || $from_id != $vote_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $vote_info=$d_plugin_vote->vote_detail($vote_id, false);

        fdump('==========投票详情================'.__LINE__, 'vote_detail', true);
        fdump($vote_info, 'vote_detail', true);
        returnCode(0, $vote_info);
    }

    /**
     * 编辑投票
     */
    public function edit_vote() {
        // 字段过滤
        $vote_id = I('vote_id');
        if (empty($vote_id)) {
            returnCode(11, [], '请上传修改对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_vote = M('Plugin_vote');
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $d_plugin_vote->where($where_vote)->find();
        if (empty($vote_info)) {
            returnCode(11, [], '修改对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $vote_info['from'] || $from_id != $vote_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }

        $vote_title = I('vote_title','','trim'); // 投票标题
        $vote_des = I('vote_des','','trim'); // 投票描述 暂定为非必填
        $vote_des_style = I('vote_des_style','','trim'); // 投票描述 样式 暂定为非必选 默认为 样式 居中 text-align: center;
        if (!empty($vote_des) && empty($vote_des_style)) {
            // 如果存在投票描述 不存在样式 添加默认样式 居中
            $vote_des_style = 'text-align: center;';
        }
        // 图片
        $vote_image = $_POST['vote_image'];
        if ($vote_image) {
            // 数组存储多张图片  title url file_url
            $vote_image = serialize($vote_image);
        }

        // 投票开始和结束时间 前端需要传时间戳
        $start_time = I('start_time','');
        $end_time = I('end_time','');
        $now_time = time();
        if ($start_time) {
            $start_time = strtotime($start_time);
        }
        if ($end_time) {
            $end_time = strtotime($end_time);
        }
        if ($end_time && $now_time>$end_time) {
            returnCode(12, [], '投票结束时间必须大于当前时间！');
        }
        if ($start_time && $end_time && $start_time>$end_time) {
            returnCode(12, [], '投票结束时间必须大于开始时间！');
        }
        // 状态 0 禁止 1 开启 3结束 4删除
        $status = I('status','-1');
        if ($status>=0 && !in_array($status, array(0,1,3,4))) {
            returnCode(16, [], '传递状态错误！');
        }
        // 限制对象 例如 from为house 代表的是 0 社区成员可投票 1 只有业主可投票
        $limit_type = I('limit_type','-1');
        // 每人可投票次数（默认每人一票）
        $limit_num = I('limit_num','1');
        // 限制用户修改  1 投票后可修改  2 投票后不可修改
        $limit_user_set = I('limit_user_set','-1');
        // 限制公开  1 公开  2 不公开（参与者可见）
        $limit_open = I('limit_open','-1');
        // 限制创建者修改  1 添加后可修改   2 添加后只可产看不可修改
        $limit_creator_set = I('limit_creator_set','-1');
        // 投票排序值 数字越大越靠前
        $vote_sort = I('vote_sort','-1');


        //实例化M()方法
        $Model = M();
        //开启事务
        $Model->startTrans();
        // 处理修改参数
        $vote_data = array(
            'vote_id'=>$vote_id
        );
        if ($vote_title && $vote_title!=$vote_info['vote_title']) {
            $vote_data['vote_title'] = $vote_title;
        }
        if ($vote_des && $vote_des!=$vote_info['vote_des']) {
            $vote_data['vote_des'] = $vote_des;
        }
        if ($vote_des_style && $vote_des_style!=$vote_info['vote_des_style']) {
            $vote_data['vote_des_style'] = $vote_des_style;
        }
        if ($vote_image && $vote_image!=$vote_info['vote_image']) {
            $vote_data['vote_image'] = $vote_image;
        }
        if ($start_time && $start_time!=$vote_info['start_time']) {
            $vote_data['start_time'] = $start_time;
        }
        if ($end_time && $end_time!=$vote_info['end_time']) {
            $vote_data['end_time'] = $end_time;
        }
        if ($status>=0 && $status!=$vote_info['status']) {
            $vote_data['status'] = $status;
        }
        if ($limit_type>=0 && $limit_type!=$vote_info['limit_type']) {
            $vote_data['limit_type'] = $limit_type;
        }
        if ($limit_num>=0 && $limit_num!=$vote_info['limit_num']) {
            $vote_data['limit_num'] = $limit_num;
        }
        if ($limit_user_set>=0 && $limit_user_set!=$vote_info['limit_user_set']) {
            $vote_data['limit_user_set'] = $limit_user_set;
        }
        if ($limit_open>=0 && $limit_open!=$vote_info['limit_open']) {
            $vote_data['limit_open'] = $limit_open;
        }
        if ($limit_creator_set>=0 && $limit_creator_set!=$vote_info['limit_creator_set']) {
            $vote_data['limit_creator_set'] = $limit_creator_set;
        }
        if ($vote_sort>=0 && $vote_sort!=$vote_info['vote_sort']) {
            $vote_data['vote_sort'] = $vote_sort;
        }
        $vote_data['last_time'] = $now_time;

        $vote_set = M('Plugin_vote')->data($vote_data)->save();
        if (!$vote_set) {
            $Model->rollback();
            fdump('==========投票修改失败================'.__LINE__, 'edit_vote', true);
            fdump($vote_data, 'edit_vote', true);
            returnCode(14, [], '投票修改失败！');
        }
        // 以上是投票主体内容 pigcms_plugin_vote
        // 以下是投票区块内容 pigcms_plugin_vote_block
        $vote_block = $_POST['vote_block'];
        // 如果是修改 需要带上 block_id 新增不需要  原来有的没有上传的统一删除
        if (empty($vote_block)) {
            returnCode(13, [], '至少设置一个填写选择项！');
        }
        // 考虑对应顺序值 存储 选项信息
        // 如果是修改 需要带上 option_id 新增不需要  原来有的没有上传的统一删除
        $vote_option = I('vote_option');

        $block_sort = array();
        // 存储区块id
        $block_id_arr = array();
        $d_plugin_vote_block = M('Plugin_vote_block');
        $d_plugin_vote_option = M('Plugin_vote_option');

        foreach ($vote_block as $key=>$item) {
            if (empty(trim($item['block_title']))) {
                $Model->rollback();
                returnCode(30, [], '请填写题目！');
                break;
            }
            if (empty($item['block_type'])) {
                $Model->rollback();
                returnCode(30, [], '请选择题目类型！');
                break;
            }

            if (empty($item['block_sort'])) {
                // 顺序值  1,2,3,4   其 中单选多选 信息  对应 顺序值关联 没有顺序值取键值
                $item['block_sort'] = $key;
            }
//            if($block_sort && in_array(intval($item['block_sort']), $block_sort)){
//                // 顺序值  1,2,3,4   其 中单选多选 信息  对应 顺序值关联 顺序值不允许重复
//                $Model->rollback();
//                returnCode(30, [], '顺序值重复！');
//                break;
//            }else {
//                // 顺序值存储起来
//                $block_sort[] = intval($item['block_sort']);
//            }
            if ((3==intval($item['block_type']) || 4==intval($item['block_type']))) {
                $block_sort_key = intval($item['block_sort']);
                // 考虑对应顺序值 存储 选项信息
                $option = $vote_option[$block_sort_key];
                if (empty($option) && $item['info_options']) {
                    // 考虑直接双层数组上传选项信息
                    $option = $item['info_options'];
                }
            }
            if ((3==intval($item['block_type']) || 4==intval($item['block_type'])) && (empty($option) || count($option)<=1)) {
                // 如果是单选和多选处理选项 记录
                $Model->rollback();
                returnCode(30, [], '请至少设置两个选项！');
                break;
            }

            if (empty($item['is_write'])) {
                $Model->rollback();
                returnCode(30, [], '请选择题目是否必填！');
                break;
            }
            $block = array();
            $block['vote_id'] = $vote_id;
            $block['block_title'] = trim($item['block_title']);
            $block['block_type'] = intval($item['block_type']);
            $block['is_write'] = $item['is_write'];
            $block['block_sort'] = $item['block_sort'];
            $block['block_last_time'] = $now_time;
            if (intval($item['block_id'])>0) {
                $block['block_id'] = intval($item['block_id']);
                $block_set = $d_plugin_vote_block->data($block)->save();
                if ($block_set) {
                    $block_id = $block['block_id'];
                }
            } else {
                $block_id = $d_plugin_vote_block->data($block)->add();
            }
            $block_id_arr[] = $block_id;
            if (!$block_id) {
                $Model->rollback();
                fdump('==========投票区块修改添加失败================'.__LINE__, 'edit_vote', true);
                fdump($block, 'edit_vote', true);
                returnCode(14, [], '投票修改失败！');
                break;
            } elseif (3==intval($item['block_type']) || 4==intval($item['block_type']) && $option) {
                // 如果是单选和多选处理选项 记录
                $option_arr = array();
                $option_data = array();
                $option_data['vote_id'] = $vote_id;
                $option_data['block_id'] = $block_id;
                $option_id_arr = array();
                foreach ($option as $val) {
                    if (1==intval($val['option_type']) && empty(trim($val['option_name']))) {
                        $Model->rollback();
                        returnCode(3, [], '请填写选项名称！');
                        break;
                    }
                    $save_option_data=$option_data;
                    $save_option_data['option_type'] = intval($val['option_type']);
                    if (0==intval($val['option_type']) && empty($val['option_name'])) {
                        $save_option_data['option_name'] = '其他';
                    } else {
                        $save_option_data['option_name'] = trim($val['option_name']);
                    }
                    $save_option_data['last_time'] = $now_time;
                    // 如果存在选项id 单独进行修改 option_id
                    if (intval($val['option_id'])>0) {
                        $save_option_data['option_id'] = intval($val['option_id']);
                        $set_id = $d_plugin_vote_option->data($save_option_data)->save();
                        $option_id_arr[] = intval($val['option_id']);
                        if (!$set_id) {
                            $Model->rollback();
                            fdump('==========投票选项修改失败================'.__LINE__, 'edit_vote', true);
                            fdump($save_option_data, 'edit_vote', true);
                            returnCode(14, [], '投票修改失败！');
                            break;
                        }
                    } else {
                        // 需要添加的统一记录
                        $option_arr[] = $save_option_data;
                    }
                }
                // 去除没有修改的选项
                $where_option = array(
                    'vote_id'=>$vote_id,
                    'block_id'=>$block_id,
                );
                if (!empty($option_id_arr)) {
                    // 如果存在
                    $where_option['option_id'] = array('not in', $option_id_arr);
                }
                $other =  $d_plugin_vote_option->where($where_option)->find();
                if ($other) {
                    // 直接进行删除
                    $del_option = $d_plugin_vote_option->where($where_option)->delete();
                } else {
                    $del_option = true;
                }
                if (!$del_option) {
                    $Model->rollback();
                    fdump('==========投票删除多余选项失败================'.__LINE__, 'edit_vote', true);
                    fdump($where_option, 'edit_vote', true);
                    returnCode(14, [], '投票修改失败！');
                } elseif (!empty($option_arr)) {
                    $add_id = $d_plugin_vote_option->addAll($option_arr);
                    if (!$add_id) {
                        $Model->rollback();
                        fdump('==========投票选项修改失败================'.__LINE__, 'edit_vote', true);
                        fdump($option_arr, 'edit_vote', true);
                        returnCode(14, [], '投票修改失败！');
                        break;
                    }
                }
            }
        }
        // 删除投票下没有上传修改的区块（也排除新添加的）
        $where_block = array(
            'vote_id'=>$vote_id,
            'block_id' => array('not in', $block_id_arr)
        );
        $other_block =  $d_plugin_vote_block->where($where_block)->find();
        if ($other_block) {
            // 直接进行删除
            $del_block = $d_plugin_vote_block->where($where_block)->delete();
        } else {
            $del_block = true;
        }
        if (!$del_block) {
            $Model->rollback();
            fdump('==========投票删除多余区块失败================'.__LINE__, 'edit_vote', true);
            fdump($where_block, 'edit_vote', true);
            returnCode(14, [], '投票修改失败！');
        }


        // 没有错误统一生效
        $Model->commit();
        returnCode(0, array('vote_id'=>$vote_id));
    }

    /**
     * 删除投票
     */
    public function del_vote() {
        // 字段过滤
        $vote_id = I('vote_id');
        if (empty($vote_id)) {
            returnCode(21, [], '请上传删除对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_vote = M('Plugin_vote');
        $where_vote = array(
            'vote_id' => $vote_id
        );
        $vote_info = $d_plugin_vote->where($where_vote)->find();
        if (empty($vote_info)) {
            returnCode(21, [], '删除对象不存在！');
        }
        if (4==$vote_info['status']) {
            returnCode(21, [], '当前对象已经删除！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $vote_info['from'] || $from_id != $vote_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $data = array(
            'status' => 4,
            'last_time' =>time()
        );
        $del_vote = $d_plugin_vote->where($where_vote)->data($data)->save();
        if (!$del_vote) {
            fdump('==========删除投票失败================'.__LINE__, 'del_vote', true);
            fdump($data, 'del_vote', true);
            fdump($d_plugin_vote, 'del_vote', true);
            returnCode(22, [], '删除失败！');
        }

        returnCode(0, array('vote_id'=>$vote_id));
    }

    /**
     * 获取投票列表
     */
    public function vote_list() {
        $page = I('page','1','intval');
        $page_size = I('page_size','20','intval');

        // 条件搜索
        $where = array();
        $search_type = I('search_type','','trim');
        $search_val = I('search_val','','trim');
        // id 精确查询
        if ($search_type && 'vote_id'==$search_type) {
            $vote_id = intval($search_val);
            if($search_val == '0' && $vote_id == 0){
                returnCode(41, [], '请输入大于零的投票ID！');
            }
        }
        if ($vote_id>0) {
            $where['vote_id'] = $vote_id;
        }
        // 投票标题模糊查询
        if ($search_type && 'vote_title'==$search_type) {
            $vote_title = $search_val;
        }
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
            $time_condition = " (start_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $where['_string'] = $time_condition;
        }

        // 关于来源处理
        $returnArr = build_redirect_info();
        $where['from'] = $returnArr['from'];
        $where['from_id'] = $returnArr['from_id'];
        fdump($where,'$where1');
        $list_info = D('Plugin_vote')->vote_list($where, $page, $page_size, false, array(), 'add_time DESC');
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
            if (!$image['error']) {
                $msg = array('error' => 0,'url' => $image['url']['file'], 'title' => $image['title']['file'], 'file_url' => replace_file_domain($image['url']['file']));
                returnCode(0, $msg);
            }
            returnCode(0, $image);
        }else{
            returnCode(5, [], '没有选择图片！');
        }
    }


    /**投票统计先关模块*/
    /**
     * 单个投票数据统计详情
     */
    public function statistical_vote_detail() {
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
        $vote_info = $d_plugin_vote->where($where_vote)->field('from, from_id')->find();
        if (empty($vote_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        if ($from != $vote_info['from'] || $from_id != $vote_info['from_id']) {
            returnCode(17, [], '当前不允许进行此操作！');
        }
        $vote_info=$d_plugin_vote->statistical_vote_detail($vote_id);

        fdump('==========投票详情================'.__LINE__, 'vote_detail', true);
        fdump($vote_info, 'vote_detail', true);
        returnCode(0, $vote_info);
    }

    /**
     * 获取对应区块详细投票信息
     */
    public function statistical_vote_block_detail() {
        $page = I('page','1','intval');
        $page_size = I('page_size','20','intval');
        // 字段过滤
        $block_id = I('block_id');
        if (empty($block_id)) {
            returnCode(31, [], '请上传获取对象！');
        }
        // 对应获取投票信息，查看是否存在
        $d_plugin_vote = D('Plugin_vote');
        $d_plugin_vote_block = D('Plugin_vote_block');
        $where_vote = array(
            'block_id' => $block_id
        );
        $vote_info = $d_plugin_vote_block->where($where_vote)->field('vote_id, block_id')->find();
        if (empty($vote_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        // 关于来源处理 同一操作平台的同一来源id
//        $returnArr = build_redirect_info();
//        $from = $returnArr['from'];
//        $from_id = $returnArr['from_id'];
//        if ($from != $vote_info['from'] || $from_id != $vote_info['from_id']) {
//            returnCode(17, [], '当前不允许进行此操作！');
//        }
        $vote_info=$d_plugin_vote->vote_block_list($block_id, $page, $page_size);

        fdump('==========投票详情================'.__LINE__, 'vote_detail', true);
        fdump($vote_info, 'vote_detail', true);
        returnCode(0, $vote_info);
    }

    /**
     * 导出数据
     */
    public function export()
    {
        $param = $_POST;
        $returnArr = build_redirect_info();
        $from = $returnArr['from'];
        $from_id = $returnArr['from_id'];
        $param['from'] = $from;
        $param['from_id'] = $from_id;
        $param['type'] = 'plugin_vote';
        $param['rand_number'] = time();
        if($res = PD('Order')->order_export($param)){
            $export = array(
                'file_name'=>$res['file_name'],
                'export_id'=>$res['export_id'],
                'rand_number'=> $param['rand_number']
            );
            returnCode(0, $export);
        }else{
            returnCode(9001, [], '导出失败！');
        }
    }


    public function download_export_file(){
        $where['export_id']=I('export_id');

        $export  = M('Export_log')->where($where)->find();
        if(file_exists('./runtime/'.$export['file_name'])){
            if(IS_POST){
                $file = C('config.site_url') . "/runtime/".$export['file_name'];
                $export['file_url'] = $file;
                returnCode(0, $export);
            }else{
                $filename = $export['title'].'_'.$export['file_name'];

                $ua = $_SERVER['HTTP_USER_AGENT'];
                $ua = strtolower($ua);
                if(preg_match('/msie/', $ua) || preg_match('/edge/', $ua) || preg_match('/trident/', $ua)) { //判断是否为IE或Edge浏览器
                    $filename = str_replace('+', '%20', urlencode($filename)); //使用urlencode对文件名进行重新编码
                }
                header('Content-Disposition: attachment; filename="'.$filename.'"');


                header("Content-type:application/vnd.ms-excel");

                readfile('./runtime/'.$export['file_name']) ;

            }
        }else{
            returnCode(1, [], '还未导出！');
        }
        exit;
    }


    public function test() {
        $info = D('Plugin_vote')->statistical_voting(6);
        dump($info);
    }

}