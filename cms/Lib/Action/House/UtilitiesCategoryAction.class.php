<?php

use app\community\model\service\RepairCateService;

/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/12/18
 * Time: 10:16
 */
class UtilitiesCategoryAction extends BaseAction
{
    protected $village_id;
    protected $village;

    public function _initialize()
    {
        parent::_initialize();

        $this->village_id = $this->house_session['village_id'];
        $this->village = M('House_village')->where(array('village_id' => $this->village_id))->find();
        if (empty($this->village)) {
            $this->error('该' . C('config.house_name') . '不存在！');
        }
    }

    //分类管理
    public function fee_category_index()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['village_id'] = $this->village_id;
        $count_user = M('utilities_category')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('utilities_category')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->assign('village_id', $this->village_id);
        $this->display();
    }

    public function fee_category_add()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (empty($_POST['name'])) {
                $this->error('分类名称必须填!');
            }
            $data['name'] = $_POST['name'];
            $data['unit'] = $_POST['unit'];
            $data['parent_id'] = $_POST['parent_id']?$_POST['parent_id']:0;
            $data['village_id'] = $this->village_id;
            $data['addtime'] = date('Y-m-d H:i:s', time());
            $data['status'] = 1;
            $insert_id = M('utilities_category')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('fee_category_index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $cate_where['village_id'] = $this->village_id;
            $list = M('utilities_category')->where($cate_where)->select();
            $this->assign('newCategoryList', $list);
            $this->display();
        }
    }

    public function fee_category_edit()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            if (empty($_POST['name'])) {
                $this->error('分类名称必须填!');
            }

            $data['name'] = $_POST['name'];
            $data['unit'] = $_POST['unit'];
            $data['parent_id'] = $_POST['parent_id']?$_POST['parent_id']:0;
            $data['village_id'] = $this->village_id;
            $data['status'] = $_POST['status'];

            $insert_id = M('utilities_category')->where(array('id' => $id))->data($data)->save();
            if ($insert_id) {
                $this->success('修改成功！', U('fee_category_index'));
            } else {
                $this->error('修改失败');
            }
        } else {
            $id = $_GET['id'];
            $category_info = D('utilities_category')->where(array('id' => $id))->find();
            $cate_where['village_id'] = $this->village_id;
            $list = M('utilities_category')->where($cate_where)->select();
            $this->assign('category_info', $category_info);
            $this->assign('newCategoryList', $list);
            $this->display();
        }
    }

    public function fee_category_del()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('utilities_category')->where(array('id' => $_GET['id']))->delete();
        if ($id) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    // 核心方法：生成近一年的年月数据
    function getLastYearMonths() {
        $months = [];
        // 获取当前时间
        $currentTime = new DateTime();

        // 循环生成近12个月（包含当前月）
        for ($i = 0; $i < 12; $i++) {
            // 往前推i个月
            $date = clone $currentTime;
            $date->modify("-$i month");

            // 拼接值（YYYY-MM）和显示文本（YYYY年MM月）
            $value = $date->format('Y-m');
            $label = $date->format('Y年m月');

            $months[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        // 按时间倒序排列（最新的月份在最前面）
        return $months;
    }

}
