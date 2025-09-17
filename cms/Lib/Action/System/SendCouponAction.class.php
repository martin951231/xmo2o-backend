<?php

/**
 * 发券功能
 * @author 张涛
 */
class SendCouponAction extends BaseAction
{
    /**
     * 活动列表
     * @author 张涛
     * @date 2020/08/03
     */
    public function index()
    {
        $lists = (new SystemSendCouponActivityModel())->getLists($_GET);
        $this->assign('lists', $lists['lists']);
        $this->assign('pagebar', $lists['pagebar']);
        $this->display();
    }

    /**
     * 添加
     * @author 张涛
     * @date 2020/08/03
     */
    public function addActivity()
    {
        if (IS_POST) {
            $rs = (new SystemSendCouponActivityModel())->addActivity($_POST);
            if ($rs['error_no'] == 0) {
                $this->success('创建活动成功', U('index'));
            } else {
                $this->error($rs['error_msg']);
            }
        } else {
            $defaultBackground = C('config.site_url') . '/static/default_background_send_coupon.png';
            $this->assign('defaultBackground',$defaultBackground);
            $this->display();
        }
    }

    /**
     * 编辑
     * @author 张涛
     * @date 2020/08/03
     */
    public function editActivity()
    {
        if (IS_POST) {
            $rs = (new SystemSendCouponActivityModel())->editActivity($_POST);
            if ($rs['error_no'] == 0) {
                $this->success('编辑活动成功', U('index'));
            } else {
                $this->error($rs['error_msg']);
            }
        } else {
            $id = I('get.id', 0, 'intval');
            $detail = (new SystemSendCouponActivityModel())->getDetailById($id);
            $merCouponids = [];
            $platCouponids = [];
            foreach ($detail['coupons'] as $v) {
                if ($v['type'] == 'plat') {
                    $platCouponids[] = $v['coupon_id'];
                } else {
                    $merCouponids[] = $v['coupon_id'];
                }
            }

            $this->assign('merCouponids', implode(',', $merCouponids));
            $this->assign('platCouponids', implode(',', $platCouponids));
            $this->assign('detail', $detail);
            $this->display();
        }
    }

    /**
     * 设为失效
     * @author 张涛
     * @date 2020/08/03
     */
    public function setLose()
    {
        $id = $_POST['id'] ?? 0;
        $rs = ['error_no' => 0, 'error_msg' => ''];
        $bool = (new SystemSendCouponActivityModel())->where(['id' => intval($id)])->save(['status' => 0]);
        if ($bool === false) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = '保存失败';
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 删除
     * @author 张涛
     * @date 2020/08/03
     */
    public function setDel()
    {
        $id = $_POST['id'] ?? 0;
        $rs = ['error_no' => 0, 'error_msg' => ''];
        $bool = (new SystemSendCouponActivityModel())->where(['id' => intval($id)])->save(['is_del' => 1]);
        if ($bool === false) {
            $rs['error_no'] = 1;
            $rs['error_msg'] = '保存失败';
        }
        $this->ajaxReturn($rs);
    }


    /**
     * 优惠券选择
     * @author 张涛
     * @date 2020/08/03
     */
    public function couponLists()
    {
        $keyword = I('get.keyword', '', 'trim');
        $type = I('get.type', 'mer', 'trim');
        $selectMer = I('get.select_mer','','trim');
        $selectPlat = I('get.select_plat','','trim');
        $notHand = I('get.hide',1,'trim');
        $selectMerArr = !$selectMer?[]:explode(',',$selectMer);
        $selectPlatArr = !$selectPlat?[]:explode(',',$selectPlat);
        $tm = time();
        $condition = [];
        $order = 'coupon_id DESC';
        $condition['status'] = 1;
        if (!empty($keyword)) {
            $condition['name'] = array('like',"%" . $_GET['keyword'] . "%");
        }

        $count = D('System_coupon')->where($condition)->count();
        import('@.ORG.diypage');

        if ($type == 'mer') {
            $pullMod = M('Card_new_coupon_hadpull');
            $condition['start_time'] = ['lt', $tm];
            $condition['end_time'] = ['gt', $tm];
            $condition['allow_new'] = 0;
            $condition['allow_im_get'] = 0;
            $condition['is_live'] = 0;
            $count = D('Card_new_coupon')->where($condition)->count();
            $p = new Page($count, 10);
            $couponList = D('Card_new_coupon')->field(true)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
        } else {
            $pullMod = M('System_coupon_hadpull');
            $condition['get_start_time'] = ['lt', $tm];
            $condition['get_end_time'] = ['gt', $tm];
            $condition['is_privileged'] = 0;
            if($notHand){
                //$condition['is_hide'] = 0;
            }
            $condition['allow_sign'] = 0;
            $condition['allow_gift'] = 0;
            $condition['allow_im_get'] = 0;
            $condition['allow_new'] = 0;
            $condition['is_vip_level'] = 0;
            $count = D('System_coupon')->where($condition)->count();
            $p = new Page($count, 10);
            $couponList = D('System_coupon')->field(true)->where($condition)->limit($p->firstRow . ',' . $p->listRows)->order($order)->select();
        }

        $return = D('System_coupon')->cate_platform();
        $category = $return['category'];
        $platform = $return['platform'];

        foreach ($couponList as &$v) {
            $v['platform'] = unserialize($v['platform']);
            if ($v['cate_name'] != 'all' && !empty($v['cate_id'])) {
                $tmp = unserialize($v['cate_id']);
                $v['cate_id'] = $tmp['cat_name'];
            }
            if ($v['is_discount'] == 1) {
                $v['discount_txt'] = sprintf('满%s打%s折', getFormatNumber($v['order_money']), getFormatNumber($v['discount_value']));
            } else {
                $v['discount_txt'] = sprintf('满%s减%s', getFormatNumber($v['order_money']), getFormatNumber($v['discount']));
            }

            $platformDescArr = array_map(function ($item) use ($platform) {
                return $platform[$item] ?? '--';
            }, $v['platform']);
            $v['platform_txt'] = implode(' ', $platformDescArr);

            if ($v['cate_name'] == 'all') {
                $v['cate_name_txt'] = '全部类别';
            } else {
                $v['cate_name_txt'] = $category[$v['cate_name']] ?? '-';
            }
            $v['select'] = 0;
            $v['type'] = $type;
            if ($type == 'mer') {
                $v['select'] = in_array($v['coupon_id'], $selectMerArr) ? 1 : 0;
            } else {
                $v['select'] = in_array($v['coupon_id'], $selectPlatArr) ? 1 : 0;
            }

            //计算剩余数量
            $v['left_num'] = $v['num'] - $v['had_pull'];

            $v['data_json'] = json_encode([
                'coupon_id' => $v['coupon_id'],
                'name' => $v['name'],
                'discount_txt' => $v['discount_txt'],
                'time' => date('Y-m-d', $v['start_time']) . '至' . date('Y-m-d', $v['end_time']),
                'platform_txt' => $v['platform_txt'],
                'cate_name_txt' => $v['cate_name_txt'],
                'left_num' => $v['left_num'],
                'type' => $type
            ], JSON_UNESCAPED_UNICODE);
        }
        $this->assign("keyword", $keyword);
        $this->assign("select_mer", $selectMer);
        $this->assign("select_plat", $selectPlat);
        $this->assign("type", $type);
        $this->assign('couponList', $couponList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }


    function isTimeCross($beginTime1 = 0, $endTime1 = 0, $beginTime2 = 0, $endTime2 = 0)
    {
        $status = $beginTime2 - $beginTime1;
        if ($status > 0) {
            $status2 = $beginTime2 - $endTime1;
            if ($status2 >= 0) {
                return false; // 无交集
            } else {
                return true; // 有交集
            }
        } else {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 > 0) {
                return true;
            } else {
                return false;
            }
        }
    }
}