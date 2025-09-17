<?php
class Employee_replyModel extends Model
{
    // 定义好评 8-10分 即4-5星
    private $praise = array(array('egt',8),array('elt',10));
    // 定义中评 4-7分 即2-3.5星
    private $medium = array(array('egt',4),array('elt',7));
    //定义差评 1-3分 即1-1.5星
    private $negative = array(array('egt',1),array('elt',3));

    /**
     * @param int $employee_id  从业人员id
     * @param int $page_size  每页取数据条数
     * @param int $memory_id  标签id
     * @param int $have_img  是否存在图片  0 不查询  1 有图  2 无图
     * @param int $grade  评级  0 不查询  1 好评  2 中评  3 差评
     * @param int $status  状态
     * @return mixed
     */
    public function employee_reply($employee_id=0, $page_size=20, $memory_id=0, $have_img = 0, $grade=0, $status=0)
    {

        $where['a.status'] = 0;
        if ($status) {
            $where['a.status'] = $status;
        }
        if ($employee_id) {
            $where['a.employee_id'] = $employee_id;
        }
        if ($have_img > 0 && $have_img == 1) {
            $where['a.image'] = array('neq', '');
        } elseif ($have_img > 0 && $have_img == 2) {
            $where['a.image'] = array('eq', '');
        }
        if ($grade==1) {
            $where['a.score_num'] = $this->praise;
        } elseif ($grade==2) {
            $where['a.score_num'] = $this->medium;
        } elseif ($grade==3) {
            $where['a.score_num'] = $this->negative;
        }

        // 默认排序
        $order = 'a.add_time DESC';

        // 如果存在标签id 查询标签绑定的评论id
        if ($memory_id) {
            $reply_id_arr = M('Employee_memory_bind')->field('reply_id')->where(array('memory_id' => $memory_id))->select();
            $reply_id = array();
            foreach ($reply_id_arr as $val) {
                $reply_id[] = $val['reply_id'];
            }
            if (!empty($reply_id)) {
                $where['a.id'] = array('in', $reply_id);
            }
        }



        $count_reply_list = $this->alias('a')
            ->join('LEFT JOIN pigcms_employee_list as b on a.employee_id=b.id')
            ->where($where)->COUNT();

        import('@.ORG.system_page');
        $p = new Page($count_reply_list,$page_size);
        if ($p->nowPage < $_GET['page']) {
            $reply_list = array();
        } else {
            $reply_list = $this->field('a.*, b.nickname, b.avatar')->alias('a')
                ->join('LEFT JOIN pigcms_user as b on a.reply_uid=b.uid')
                ->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();


            if ($reply_list) {
                $d_employee_memory_bind = D('Employee_memory_bind');
                foreach ($reply_list as &$val) {
                    if ($val['image']){
                        $val['image'] = unserialize($val['image']);
                        $val['image'] = $this->reply_image($val['image']);
                    }
                    $val['memory_info'] = $d_employee_memory_bind->reply_memory($val['id']);
                    $val['add_time_info'] = date('Y-m-d H:i:s', $val['add_time']);

                    $val['start'] = $val['score_num']/10 *100 +1;
                    if ($val && !$val['avatar']) {
                        // 头像没有赋予默认头像
                        $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
                    }
                }
            }
        }

        $pagebar = $p->show();
        $info['list'] = $reply_list;
        $info['count'] = $count_reply_list;
        $info['totalPage'] = ceil($count_reply_list/$page_size);
        return $info;
    }


    /**
     * 处理从业者填写信息 单独提取地址信息
     * @param $image
     * @return array
     */
    public function reply_image($image){
        if (is_array($image)) {
            foreach ($image as &$val) {
                if($val) {
                    $val = replace_file_domain($val);
                } else {
                    unset($val);
                }
            }
        } else {
            $image_info = array();
            $image_info[] = replace_file_domain($image);
            $image = $image_info;
        }
        return $image;
    }


    public function reply_memory_info($employee_id) {
        $info = array();
        $where = array(
            'employee_id' => $employee_id,
            'status' => 0
        );
        //全部
        $all_info['msg'] = L_('全部');
        $all_info['count'] = M('Employee_reply')->where($where)->count();
        $all_info['type'] = 'all';
        $all_info['type_id'] = 0;
        $info[] = $all_info;
        //有图
        $where_img = array(
            'employee_id' => $employee_id,
            'status' => 0,
            'image' => array('neq', ''),
        );
        $img_info['msg'] = L_('有图');
        $img_info['count'] = M('Employee_reply')->where($where_img)->count();
        $img_info['type'] = 'have_img';
        $img_info['type_id'] = 0;
        $info[] = $img_info;
        // 定义好评
        $where['score_num'] = $this->praise;
        $praise_info['msg'] = L_('好评');
        $praise_info['count'] = M('Employee_reply')->where($where)->count();
        $praise_info['type'] = 'praise';
        $praise_info['type_id'] = 0;
        $info[] = $praise_info;
        // 定义中评
        $where['score_num'] = $this->medium;
        $medium_info['msg'] = L_('中评');
        $medium_info['count'] = M('Employee_reply')->where($where)->count();
        $medium_info['type'] = 'medium';
        $medium_info['type_id'] = 0;
        $info[] = $medium_info;
        //定义差评
        $where['score_num'] = $this->negative;
        $negative_info['msg'] = L_('差评');
        $negative_info['count'] = M('Employee_reply')->where($where)->count();
        $negative_info['type'] = 'negative';
        $negative_info['type_id'] = 0;
        $info[] = $negative_info;
        $memory_info = D('Employee_memory_bind')->employee_memory_info($employee_id);
        if ($memory_info) {
            foreach ($memory_info as $item) {
                if ($item['num'] && $item['num'] > 0) {
                    $memory_arr['msg'] = $item['memory_name'];
                    $memory_arr['count'] = $item['num'];
                    $memory_arr['type'] = 'memory';
                    $memory_arr['type_id'] = $item['memory_id'];
                    $info[] = $memory_arr;
                }
            }
        }
        return $info;
    }

}
?>