<?php
class Employee_reply_infoModel extends Model
{
    /**
     * @param int $reply_id  从业人员id
     * @param int $page_size  每页取数据条数
     * @param int $status  状态
     * @param int $uid  获取信息的用户id
     * @return mixed
     */
    public function employee_reply_info($reply_id=0, $page_size=20, $status=0, $uid=0)
    {

        if ($status < 0) {
            $where['a.status'] = 0;
        } else {
            $where['a.status'] = array('in', array(0,1));
        }
        if ($reply_id) {
            $where['a.reply_id'] = $reply_id;
        }

        // 默认排序
        $order = 'a.add_time DESC';

        $count_reply_info_list = $this->alias('a')
            ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
            ->where($where)->COUNT();

        import('@.ORG.system_page');
        $p = new Page($count_reply_info_list,$page_size);
        if ($p->nowPage < $_GET['page']) { // 当前页面小于传值页面的时候返回空数据
            $reply_list = array();
        } else {
            $reply_list = $this->field('a.*, b.nickname, b.avatar')->alias('a')
                ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
                ->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();


            if ($reply_list) {
                foreach ($reply_list as &$val) {
                    $val['add_time_info'] = date('Y-m-d H:i:s', $val['add_time']);

                    if ($val && !$val['avatar']) {
                        // 头像没有赋予默认头像
                        $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
                    }
                    if ($uid && $uid == $val['uid']) {
                        $val['is_user'] = true;
                        $val['is_del'] = true;
                    } else {
                        $val['is_user'] = false;
                        $val['is_del'] = false;
                    }
                }
            }
        }

        $info['list'] = $reply_list;
        $info['count'] = $count_reply_info_list;
        $info['totalPage'] = ceil($count_reply_info_list/$page_size);
        return $info;
    }



    /**
     * @param int $reply_id  从业人员id
     * @param int $page_size  每页取数据条数
     * @param int $status  状态
     * @param int $uid  获取信息的用户id
     * @return mixed
     */
    public function employee_comment_reply_list($reply_id=0, $page_size=20, $status=0, $uid=0)
    {
        if ($status) {
            $where['a.status'] = $status;
        }
        if ($reply_id) {
            $where['a.reply_id'] = $reply_id;
        }

        // 默认排序
        $order = 'a.add_time DESC';

        $count_reply_info_list = $this->alias('a')
            ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
            ->where($where)->COUNT();

        import('@.ORG.system_page');
        $p = new Page($count_reply_info_list,$page_size);
        if ($p->nowPage < $_GET['page']) { // 当前页面小于传值页面的时候返回空数据
            $reply_list = array();
        } else {
            $reply_list = $this->field('a.*, b.nickname, b.avatar')->alias('a')
                ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
                ->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();


            if ($reply_list) {
                foreach ($reply_list as &$val) {
                    $val['add_time_info'] = date('Y-m-d H:i:s', $val['add_time']);

                    if ($val && !$val['avatar']) {
                        // 头像没有赋予默认头像
                        $val['avatar'] = C('config.site_url') . '/tpl/Wap/pure/static/employee/images/pic-default.png?t=001';
                    }
                    if ($uid && $uid == $val['uid']) {
                        $val['is_user'] = true;
                        $val['is_del'] = true;
                    } else {
                        $val['is_user'] = false;
                        $val['is_del'] = false;
                    }
                }
            }
        }

        $pagebar = $p->show();
        $info['pagebar'] = $pagebar;
        $info['list'] = $reply_list;
        $info['count'] = $count_reply_info_list;
        $info['totalPage'] = ceil($count_reply_info_list/$page_size);
        return $info;
    }


    /**
     * 处理从业者填写信息 单独提取地址信息
     * @param $image
     * @return string
     */
    public function reply_image($image){
        foreach ($image as &$val) {
            if($val) {
                $val = replace_file_domain($val);
            } else {
                unset($val);
            }
        }
        return $image;
    }

}
?>