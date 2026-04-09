<?php

use app\community\model\service\RepairCateService;

/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/12/18
 * Time: 10:16
 */
class UtilitiesAction extends BaseAction
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
    public function fee_index()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['ul.village_id'] = $this->village_id;
        $count_user = M('utilities_log')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('utilities_log as ul')
            ->join('pigcms_utilities_category as uc ON ul.category_id = uc.id')
            ->field('ul.*,uc.name as category_name')
            ->where($where)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->assign('village_id', $this->village_id);
        $this->display();
    }

    public function fee_add()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (empty($_POST['year_month'])) {
                $this->error('年月必须填!');
            }
            if (empty($_POST['parent_id'])) {
                $this->error('一级分类必须填!');
            }
            if (empty($_POST['second_category'])) {
                $this->error('二级分类必须填!');
            }
            if (empty($_POST['consumption'])) {
                $this->error('用量必须填!');
            }
            if (empty($_POST['rate'])) {
                $this->error('费率必须填!');
            }
            $data['village_id'] = $this->village_id;
            $data['category_id'] = $_POST['second_category'];
            $data['add_date'] = $_POST['year_month'];
            $data['consumption'] = $_POST['consumption'];
            $data['rate'] = $_POST['rate'];
            $data['status'] = 1;

            $exists_where['village_id'] = $this->village_id;
            $exists_where['add_date'] = $_POST['year_month'];
            $exists_where['category_id'] = $_POST['second_category'];

            $exists = M('utilities_log')->where($exists_where)->find();
            if($exists){
                $this->error('当前分类本月已填写,请检查后再重试!');
                exit;
            }

            $insert_id = M('utilities_log')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('fee_index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $cate_where['village_id'] = $this->village_id;
            $list = M('utilities_category')->where($cate_where)->select();
            $month = $this->getLastYearMonths();
            $this->assign('monthList', $month);
            $this->assign('newCategoryList', $list);
            $this->display();
        }
    }

    public function fee_edit()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (empty($_POST['year_month'])) {
                $this->error('年月必须填!');
            }
            if (empty($_POST['parent_id'])) {
                $this->error('一级分类必须填!');
            }
            if (empty($_POST['second_category'])) {
                $this->error('二级分类必须填!');
            }
            if (empty($_POST['consumption'])) {
                $this->error('用量必须填!');
            }
            if (empty($_POST['rate'])) {
                $this->error('费率必须填!');
            }
            $data['village_id'] = $this->village_id;
            $data['category_id'] = $_POST['second_category'];
            $data['add_date'] = $_POST['year_month'];
            $data['consumption'] = $_POST['consumption'];
            $data['rate'] = $_POST['rate'];
            $data['status'] = $_POST['status'];

            $id = $_GET['id'];
            $insert_id = M('utilities_log')->where(array('id' => $id))->data($data)->save();
            if ($insert_id) {
                $this->success('保存成功！', U('fee_index'));
            } else {
                $this->error('保存失败');
            }
        } else {
            $id = $_GET['id'];
            $category_info = D('utilities_log')->where(array('id' => $id))->find();
            $cate_where['village_id'] = $this->village_id;
            $list = M('utilities_category')->where($cate_where)->select();
            $month = $this->getLastYearMonths();
            $this->assign('category_info', $category_info);
            $this->assign('monthList', $month);
            $this->assign('newCategoryList', $list);
            $this->display();
        }
    }

    public function fee_del()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = M('utilities_log')->where(array('id' => $_GET['id']))->delete();
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
