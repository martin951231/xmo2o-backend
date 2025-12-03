<?php

/**
 * Created by PhpStorm.
 * User: 郭先森
 * Date: 2019/12/18
 * Time: 10:16
 */
class WisdomQrcodeAction extends BaseAction
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

    public function cate_index()
    {
        if (!in_array(426, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $where['village_id'] = $this->village_id;
        $count_user = D('Wisdom_qrcode_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = D('Wisdom_qrcode_cate')->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('id desc')->select();
        if (!empty($dataList)) {
            $third_robot_deviceDb = D('Third_robot_device');
            foreach ($dataList as $lkk => $lvv) {
                $dataList[$lkk]['robot_name'] = '';
                if ($lvv['from_type'] && $lvv['from_type'] == 1 && !empty($lvv['robot_code'])) {
                    $robot_device = $third_robot_deviceDb->where(array('robot_code' => $lvv['robot_code']))->field('village_id,name,robot_code')->find();
                    if ($robot_device) {
                        $dataList[$lkk]['robot_name'] = $robot_device['name'];
                    }
                }
            }
        }
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());

        $this->display();
    }

    public function cate_add()
    {
        if (!in_array(541, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (!$_POST['cate_name']) {
                $this->error('名称不能为空!');
            }
            $data['distance'] = $_POST['distance'] ? $_POST['distance'] : 0;
            $data['cate_name'] = $_POST['cate_name'];
            $data['add_time'] = time();
            $data['village_id'] = $this->village_id;
            $data['type'] = $_POST['type'] ?: 1;
            $inset_id = D('Wisdom_qrcode_cate')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('cate_index'));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }
    public function cate_edit()
    {
        if (!in_array(542, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            $data['distance'] = $_POST['distance'] ? $_POST['distance'] : 0;
            $data['cate_name'] = $_POST['cate_name'];
            $data['type'] = $_POST['type'];
            $result = D('Wisdom_qrcode_cate')->where(array('id' => $id))->data($data)->save();
            if ($result !== false) {
                $this->success('修改成功！', U('cate_index'));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_cate')->where(array('id' => $id))->find();
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function cate_del()
    {
        if (!in_array(543, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        //介绍字段
        $qrcode_field_cate = M('Wisdom_qrcode_field_cate')->where(array('cate_id' => $_GET['id']))->find();
        if ($qrcode_field_cate) {

            //介绍字段配置项
            $qrcode_cate_field_cate_config = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $qrcode_field_cate['id']))->find();
            if ($qrcode_cate_field_cate_config) {
                $this->error('请先删除当前设备介绍字段管理下面的配置项列表');
            } else {
                $this->error('请先删除当前设备介绍字段管理列表');
            }
        }

        //二维码
        $qrcode = M('Wisdom_qrcode')->where(array('cate_id' => $_GET['id']))->find();
        if ($qrcode) {
            $this->error('请删除当前类别下的所有二维码!');
        }

        //记录内容管理
        $record_cate = M('Wisdom_qrcode_record_cate')->where(array('cate_id' => $_GET['id']))->find();
        if ($record_cate) {
            $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('record_cate_id' => $record_cate['id']))->find();
            if ($qrcode_record_cate_field_config) {
                $this->error('请先删除当前记录记录内容管理列表下面的配置项列表');
            } else {
                $this->error('请先删除当前记录记录内容管理列表');
            }
        }

        //负责人
        $qrcode_person = M('Wisdom_qrcode_person')->where(array('cate_id' => $_GET['id']))->find();
        if ($qrcode_person) {
            $this->error('删除当前类别下的所有的负责人!');
        }

        $id =  D('Wisdom_qrcode_cate')->where(array('id' => $_GET['id']))->delete();

        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function cate_field_index()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['cate_id'] = $_GET['cate_id'];
        $count_user = D('Wisdom_qrcode_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode_field_cate')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }


    public function cate_field_add()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (!$_POST['field_name']) {
                $this->error('名称不能为空!');
            }
            $data['cate_id'] = $_POST['cate_id'];
            $data['field_name'] = $_POST['field_name'];
            $data['sort'] = $_POST['sort'];
            $data['add_time'] = time();
            $data['village_id'] = $this->village_id;
            $inset_id = M('Wisdom_qrcode_field_cate')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('cate_field_index', array('cate_id' => $_GET['cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function cate_field_edit()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            $data['field_name'] = $_POST['field_name'];
            $data['sort'] = $_POST['sort'];
            $result = M('Wisdom_qrcode_field_cate')->where(array('id' => $id))->data($data)->save();
            if ($result !== false) {
                $this->success('修改成功！', U('cate_field_index', array('cate_id' => $_GET['cate_id'])));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_field_cate')->where(array('id' => $id))->find();
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function cate_field_del()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $qrcode_cate_field_cate_config = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $_GET['id']))->find();
        if ($qrcode_cate_field_cate_config) {
            $this->error('请先删除当前设备介绍字段管理下面的配置项列表');
        }
        $id =  M('Wisdom_qrcode_field_cate')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function cate_field_cate_config_index()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $field_cate_id = $_GET['field_cate_id'];
        $dataList = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $field_cate_id))->select();
        $this->assign('dataList', $dataList);
        $this->display();
    }

    public function cate_field_cate_config_add()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if (empty($_POST['title'])) {
                $this->error('名称不能为空!');
            }
            if (!isset($_POST['key']) || empty($_POST['key'])) {
                $_POST['key'] = uniqid() . generate_password(8);
            }
            if (empty($_POST['key'])) {
                $this->error('唯一标识不能为空!');
            }
            $cate = D('Wisdom_qrcode_cate_field_cate_config')->where(array('key' => $_POST['key']))->find();
            if ($cate) {
                $this->error('标识必须唯一!');
            }
            $data['title'] = $_POST['title'];
            $data['field_cate_id'] = $_POST['field_cate_id'];
            $data['field_sort'] = $_POST['field_sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $_POST['key'];
            $data['add_time'] = time();
            $inset_id = M('Wisdom_qrcode_cate_field_cate_config')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！');
                //$this->success('添加成功！',U('cate_field_cate_config_index',array('field_cate_id'=>$_POST['field_cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function cate_field_cate_config_edit()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $where['acid'] = $_POST['id'];
            $cate = D('Wisdom_qrcode_cate_field_cate_config')->where($where)->find();
            if (!$cate) {
                $this->error('数据不存在!');
            }
            $id = $_POST['id'];
            $data['title'] = $_POST['title'];
            $data['field_sort'] = $_POST['field_sort'];
            $data['type'] = $_POST['type'];
            if (empty($cate['key'])) {
                $data['key'] = uniqid() . generate_password(8);
            }
            $result = M('Wisdom_qrcode_cate_field_cate_config')->where(array('acid' => $id))->data($data)->save();
            if ($result !== false) {
                $this->success('修改成功！');
                //                $this->success('修改成功！',U('cate_field_cate_config_index',array('field_cate_id'=>$_POST['field_cate_id'])));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_cate_field_cate_config')->where(array('acid' => $id))->find();

            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function cate_field_cate_config_del()
    {
        if (!in_array(544, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id =  M('Wisdom_qrcode_cate_field_cate_config')->where(array('acid' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //二维码管理
    public function qrcode_index()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $where['cate_id'] = $_GET['cate_id'];
        $count_user = M('Wisdom_qrcode')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());

        $base_url = D('House_village')->base_url;

        //二维码
        $qrcode_url = $this->config['site_url'] . $base_url . 'pages/village/smartQRcode/elevatorManagement';
        $this->assign('qrcode_url', $qrcode_url);
        $this->assign('village_id', $this->village_id);
        $this->display();
    }

    public function qrcode_add()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $cate_id = $_POST['cate_id'];
            if (empty($_POST['name'])) {
                $this->error('二维码名称必须填!');
            }
            if (empty($_POST['image_path'])) {
                $this->error('设备logo必须填!');
            }
            if (empty($_POST['long_lat'])) {
                $this->error('经纬度必须填!');
            }
            $data['name'] = $_POST['name'];
            $long_lat = explode(',', $_POST['long_lat']);
            $data['long'] = $long_lat[0];
            $data['lat'] = $long_lat[1];
            $data['image_path'] = $_POST['image_path'];
            $data['add_time'] = time();
            $data['cate_id'] = $_POST['cate_id'];
            $data['num_id'] = $_POST['num_id'];
            unset($_POST['name']);
            unset($_POST['image_path']);
            unset($_POST['long_lat']);
            unset($_POST['cate_id']);
            unset($_POST['num_id']);
            //            var_dump(json_encode($_POST));die;
            $data['entication_field'] = serialize($_POST);
            //var_dump($data);die;
            $insert_id = M('Wisdom_qrcode')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('qrcode_index', array('cate_id' => $cate_id)));
            } else {
                $this->error('添加失败');
            }
        } else {
            $cate_id = $_GET['cate_id'];
            $dataList = M('Wisdom_qrcode_field_cate')->where(array('cate_id' => $cate_id))->select();
            if ($dataList) {
                foreach ($dataList as $k => &$v) {
                    $v['field_cate_test'] = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $v['id']))->select();
                }
            }
            //            echo '<pre>';
            //            var_dump($dataList);die;
            $this->assign('dataList', $dataList);
            $this->display();
        }
    }

    public function qrcode_edit()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $cate_id = $_POST['cate_id'];
            $id = $_POST['id'];
            if (empty($_POST['name'])) {
                $this->error('二维码名称必须填!');
            }
            if (empty($_POST['image_path'])) {
                $this->error('设备logo必须填!');
            }
            if (empty($_POST['long_lat'])) {
                $this->error('经纬度必须填!');
            }

            $data['name'] = $_POST['name'];
            $long_lat = explode(',', $_POST['long_lat']);
            $data['long'] = $long_lat[0];
            $data['lat'] = $long_lat[1];
            $data['image_path'] = $_POST['image_path'];
            $data['add_time'] = time();
            $data['cate_id'] = $_POST['cate_id'];
            $data['num_id'] = $_POST['num_id'];
            unset($_POST['name']);
            unset($_POST['image_path']);
            unset($_POST['long_lat']);

            unset($_POST['cate_id']);
            unset($_POST['id']);
            unset($_POST['num_id']);
            $data['entication_field'] = serialize($_POST);

            $insert_id = M('Wisdom_qrcode')->where(array('id' => $id))->data($data)->save();
            if ($insert_id) {
                $this->success('修改成功！', U('qrcode_index', array('cate_id' => $cate_id)));
            } else {
                $this->error('修改失败');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode')->where(array('id' => $id))->find();

            $cate['entication_field'] = unserialize($cate['entication_field']);
            //            echo '<pre>';
            //            var_dump($cate['entication_field']);die;
            $cate_id = $_GET['cate_id'];
            $dataList = M('Wisdom_qrcode_field_cate')->where(array('cate_id' => $cate_id))->select();
            if ($dataList) {
                foreach ($dataList as $k => &$v) {
                    $v['field_cate_test'] = M('Wisdom_qrcode_cate_field_cate_config')->where(array('field_cate_id' => $v['id']))->select();
                }
            }
            $this->assign('dataList', $dataList);
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function qrcode_del()
    {
        if (!in_array(545, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id =  M('Wisdom_qrcode')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function record_cate_index()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['cate_id'] = $_GET['cate_id'];
        $count_user = D('Wisdom_qrcode_record_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode_record_cate')->where($where)->limit($p->firstRow . ',' . $p->listRows)->select();
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function record_cate_add()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $cate_id = $_POST['cate_id'];
            $data['record_name'] = $_POST['record_name'];
            $data['sort'] = $_POST['sort'];
            $data['add_time'] = time();
            $data['status'] = $_POST['status'];
            $data['cate_id'] = $_POST['cate_id'];
            $insert_id = M('Wisdom_qrcode_record_cate')->data($data)->add();
            if ($insert_id) {
                $this->success('添加成功！', U('record_cate_index', array('cate_id' => $cate_id)));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    public function record_cate_edit()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];

            $data['record_name'] = $_POST['record_name'];
            $data['sort'] = $_POST['sort'];
            $data['status'] = $_POST['status'];
            //            var_dump($data);die;
            $result = M('Wisdom_qrcode_record_cate')->where(array('id' => $id))->data($data)->save();
            if ($result !== false) {
                $this->success('修改成功！', U('record_cate_index', array('cate_id' => $_GET['cate_id'])));
            } else {
                $this->error('修改失败！请重试。');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_record_cate')->where(array('id' => $id))->find();
            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function record_cate_del()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('record_cate_id' => $_GET['id']))->find();
        if ($qrcode_record_cate_field_config) {
            $this->error('请先删除当前记录记录内容管理列表下面的配置项列表');
        }
        $id =  M('Wisdom_qrcode_record_cate')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function record_cate_field_config_index()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $record_cate_id = $_GET['record_cate_id'];
        $dataList = M('Wisdom_qrcode_record_cate_field_config')->where(array('record_cate_id' => $record_cate_id))->order('sort desc,acid desc')->select();
        $this->assign('dataList', $dataList);
        $this->display();
    }

    public function record_cate_field_config_add()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if (IS_POST) {
            if (empty($_POST['title'])) {
                $this->error('名称不能为空!');
            }
            // if (empty($_POST['key'])) {
            //     $this->error('唯一标识不能为空!');
            // }

            $uni_key = $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($_POST['title'], true);
            if ($this->isOnlyUnderscore($uni_key)) {
                $this->error('唯一标识不能为空!');
            }
            $record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('key' => $uni_key))->find();
            if ($record_cate_field_config) {
                $this->error('唯一标识已存在!');
            }
            if ($_POST['type'] == 3) {
                if (empty($_POST['title'])) {
                    $this->error('单选内容不能为空!');
                }
            }
            $data['title'] = $_POST['title'];
            $data['sort'] = $_POST['sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $uni_key;
            $data['is_must'] = $_POST['is_must'];
            $data['add_time'] = time();
            $data['record_cate_id'] = $_POST['record_cate_id'];
            if ($_POST['use_field']) {
                $data['use_field'] = implode(',', explode(PHP_EOL, $_POST['use_field']));
            }
            $inset_id = M('Wisdom_qrcode_record_cate_field_config')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('record_cate_field_config_index', array('record_cate_id' => $_POST['record_cate_id'], 'cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    /**
     * 获取中文字符串的拼音首字母
     * * @param string $str 输入的字符串 (UTF-8)
     * @return string 返回拼音首字母字符串 (大写)
     */
    public function getPinyinInitials($str)
    {
        if (empty($str)) {
            return '';
        }

        // 1. 将 UTF-8 转为 GBK，无法转换的字符忽略
        // GBK 编码中，汉字是按拼音排序的，这是此方法的核心
        $str = iconv("UTF-8", "GBK//IGNORE", $str);
        $result = '';
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            $ord = ord($str[$i]);

            // 2. 如果是 ASCII 字符 (0-127)，直接保留 (如数字、字母)
            if ($ord < 128) {
                $result .= strtoupper($str[$i]);
                continue;
            }

            // 3. 如果是双字节字符 (汉字)，获取其十进制值
            // GBK 汉字由两个字节组成，第一个字节 > 128
            if ($i + 1 < $length) {
                $asc = $ord * 256 + ord($str[$i + 1]) - 65536;

                // 4. 根据 GBK 编码范围判断首字母
                if ($asc >= -20319 && $asc <= -20284) $result .= 'A';
                elseif ($asc >= -20283 && $asc <= -19776) $result .= 'B';
                elseif ($asc >= -19775 && $asc <= -19219) $result .= 'C';
                elseif ($asc >= -19218 && $asc <= -18711) $result .= 'D';
                elseif ($asc >= -18710 && $asc <= -18527) $result .= 'E';
                elseif ($asc >= -18526 && $asc <= -18240) $result .= 'F';
                elseif ($asc >= -18239 && $asc <= -17923) $result .= 'G';
                elseif ($asc >= -17922 && $asc <= -17418) $result .= 'H';
                elseif ($asc >= -17417 && $asc <= -16475) $result .= 'J';
                elseif ($asc >= -16474 && $asc <= -16213) $result .= 'K';
                elseif ($asc >= -16212 && $asc <= -15641) $result .= 'L';
                elseif ($asc >= -15640 && $asc <= -15166) $result .= 'M';
                elseif ($asc >= -15165 && $asc <= -14923) $result .= 'N';
                elseif ($asc >= -14922 && $asc <= -14915) $result .= 'O';
                elseif ($asc >= -14914 && $asc <= -14631) $result .= 'P';
                elseif ($asc >= -14630 && $asc <= -14150) $result .= 'Q';
                elseif ($asc >= -14149 && $asc <= -14091) $result .= 'R';
                elseif ($asc >= -14090 && $asc <= -13319) $result .= 'S';
                elseif ($asc >= -13318 && $asc <= -12839) $result .= 'T';
                elseif ($asc >= -12838 && $asc <= -12557) $result .= 'W';
                elseif ($asc >= -12556 && $asc <= -11848) $result .= 'X';
                elseif ($asc >= -11847 && $asc <= -11056) $result .= 'Y';
                elseif ($asc >= -11055 && $asc <= -10247) $result .= 'Z';

                // 跳过下一个字节，因为汉字占两个字节
                $i++;
            }
        }

        return $result;
    }

    public function isOnlyUnderscore($str)
    {
        // 1. 字符串不能为空
        // 2. 剔除 '_' 后必须为空字符串
        return $str !== '' && trim($str, '_') === '';
    }

    public function record_cate_field_config_edit()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $id = $_POST['id'];
            if (empty($_POST['title'])) {
                $this->error('名称不能为空!');
            }

            $uni_key = $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($_POST['title'], true);
            if ($this->isOnlyUnderscore($uni_key)) {
                $this->error('唯一标识不能为空!');
            }
            $where['acid'] = array('neq', $_POST['id']);
            $record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where(array('key' => $uni_key))->find();

            if ($record_cate_field_config) {
                $this->error('唯一标识已存在!');
            }
            if ($_POST['type'] == 3) {
                if (empty($_POST['title'])) {
                    $this->error('单选内容不能为空!');
                }
            }
            $data['title'] = $_POST['title'];
            $data['sort'] = $_POST['sort'];
            $data['type'] = $_POST['type'];
            $data['key'] = $uni_key;
            $data['is_must'] = $_POST['is_must'];
            $data['record_cate_id'] = $_POST['record_cate_id'];
            if ($_POST['type'] == 3) {
                if ($_POST['use_field']) {
                    $data['use_field'] = implode(',', explode(PHP_EOL, $_POST['use_field']));
                }
            } else {
                $data['use_field'] = '';
            }

            $inset_id = M('Wisdom_qrcode_record_cate_field_config')->where(array('acid' => $id))->data($data)->save();
            if ($inset_id) {
                $this->success('修改成功！', U('record_cate_field_config_index', array('record_cate_id' => $_POST['record_cate_id'], 'cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('修改失败');
            }
        } else {
            $id = $_GET['id'];
            $cate = D('Wisdom_qrcode_record_cate_field_config')->where(array('acid' => $id))->find();
            if ($cate['use_field']) {
                $cate['use_field'] = implode(PHP_EOL, explode(',', $cate['use_field']));
            }

            $this->assign('cate', $cate);
            $this->display();
        }
    }

    public function record_cate_field_config_del()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id =  M('Wisdom_qrcode_record_cate_field_config')->where(array('acid' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }


    //负责人
    public function perpon_index()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['cate_id'] = $_GET['cate_id'];
        $count_user = D('Wisdom_qrcode_person')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 20, 'page');
        $dataList = M('Wisdom_qrcode_person')->where($where)->order('id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($dataList as $k => &$v) {
            $house_worker =  D('House_worker')->where(array('wid' => $v['uid'], 'village_id' => $this->village_id, 'del' => 0))->find();
            if ($house_worker) {
                $v['name'] = $house_worker['name'];
            }
        }

        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }

    public function perpon_add()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            $data['uid'] = $_POST['uid'];
            $data['user_phone'] = $_POST['user_phone'];
            $data['record_type'] = $_POST['record_type'];
            if ($_POST['record_type'] == 1) { //每日
                $data['record_time'] = '';
            } elseif ($_POST['record_type'] == 2) { //每周

                $data['record_time'] = implode(',', $_POST['record_time']);
            } elseif ($_POST['record_type'] == 3) { //每月

                $data['record_time'] = implode(',', $_POST['record_time']);
            }

            $data['start_time'] = $_POST['start_time'];
            $data['end_time'] = $_POST['end_time'];
            $data['remind_time'] = $_POST['remind_time'];
            $data['status'] = $_POST['status'];
            $data['cate_id'] = $_POST['cate_id'];
            $data['add_time'] = time();
            $inset_id = M('Wisdom_qrcode_person')->data($data)->add();
            if ($inset_id) {
                $this->success('添加成功！', U('perpon_index', array('cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('添加失败');
            }
        } else {
            $house_worker = D('House_worker')->where(array('village_id' => $this->village_id, 'is_del' => 0, 'status' => 1))->select();
            foreach ($house_worker as $k => &$v) {
                //                $user = D('User')->where(array('phone'=>$v['phone']))->find();
                //                if($user){
                //                   $v['uid']=$user['uid'];
                //                }
            }
            $this->assign('house_worker', $house_worker);
            $this->display();
        }
    }

    public function perpon_edit()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        if (IS_POST) {
            if ($_POST['start_time'] && $_POST['end_time']) {
                $start_time = (int)str_replace(":", "", $_POST['start_time']);
                $end_time = (int)str_replace(":", "", $_POST['end_time']);
                if ($start_time > $end_time) {
                    $this->error('起止开始时间不可大于起止结束时间（仅限当天范围）');
                }
            }
            $data['uid'] = $_POST['uid'];
            $data['user_phone'] = $_POST['user_phone'];
            $data['record_type'] = $_POST['record_type'];
            if ($_POST['record_type'] == 1) { //每日
                $data['record_time'] = '';
            } elseif ($_POST['record_type'] == 2) { //每周
                $datas['record_time'] = '';
                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
                $data['record_time'] = implode(',', $_POST['record_time2']);
            } elseif ($_POST['record_type'] == 3) { //每月
                $datas['record_time'] = '';
                M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($datas)->save();
                $data['record_time'] = implode(',', $_POST['record_time3']);
            }

            $data['start_time'] = $_POST['start_time'];
            $data['end_time'] = $_POST['end_time'];
            $data['remind_time'] = $_POST['remind_time'];
            $data['status'] = $_POST['status'];
            $data['cate_id'] = $_POST['cate_id'];

            $inset_id = M('Wisdom_qrcode_person')->where(array('id' => $_POST['id']))->data($data)->save();
            if ($inset_id) {
                $this->success('修改成功！', U('perpon_index', array('cate_id' => $_POST['cate_id'])));
            } else {
                $this->error('修改失败');
            }
        } else {
            $qrcode_person = M('Wisdom_qrcode_person')->where(array('id' => $_GET['id']))->find();
            $qrcode_person['record_time'] = explode(',', $qrcode_person['record_time']);
            $this->assign('qrcode_person', $qrcode_person);
            $house_worker = D('House_worker')->where(array('village_id' => $this->village_id, 'is_del' => 0, 'status' => 1))->select();
            //            foreach($house_worker as $k=>&$v){
            ////                $user = D('User')->where(array('phone'=>$v['phone']))->find();
            ////                if($user){
            ////                    $v['uid']=$user['uid'];
            ////                }
            //            }
            $this->assign('house_worker', $house_worker);
            $this->display();
        }
    }

    public function perpon_del()
    {
        if (!in_array(547, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id =  M('Wisdom_qrcode_person')->where(array('id' => $_GET['id']))->delete();
        if ($id) {

            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    public function qrcode_record_ai()
    {
        $where = array();
        $where['village_id'] = $this->village_id;
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        $this->assign('begin_time', $add_time_start);
        $this->assign('end_time', $add_time_end);

        if ($add_time_start && $add_time_end) {

            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');

            $where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('lt', $add_time_end);
        }
        $visionLogTyppeInfo = array(
            'doorStatus' => '大门检测',
            'vehicle' => '车辆乱停放',
            'garbageCan' => '垃圾桶满溢',
            'trash' => '绿化带垃圾',
            'climb' => '翻越围栏',
            'crowd' => '人员聚集',
            'countVehicle' => '车辆堵塞',
            'carPlate' => '车位识别',
            'lawnTrample' => '践踏草坪',
            'cvMeter' => '巡检仪表识别',
            'countCan' => '垃圾桶数量识别',
            'trashbin' => '垃圾桶盖',
            'bottle' => '地面垃圾',
            'occupyEA' => '应急通道占用',
        );
        if ($find_type = $_GET['find_type'] + 0) {
            switch ($find_type) {
                case 1: //二维码名称
                    //$where['usernum'] = $_GET['find_value'];
                    $qrcode['name'] = $_GET['find_value'];
                    $qrcode_ids = [];
                    $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
                    foreach ($qrcode_list as $k => $v) {
                        $qrcode_ids[] = $v['id'];
                    }
                    $where['qrcode_id'] = array('in', $qrcode_ids);
                    break;
                case 2: //二维码类别
                    //$where['phone'] = $_GET['find_value'];
                    $qrcode['cate_name'] = $_GET['find_value'];
                    $cate_ids = [];
                    $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
                    foreach ($qrcode_cate as $k => $v) {
                        $cate_ids[] = $v['id'];
                    }
                    $where['cate_id'] = array('in', $cate_ids);
                    break;
                default:
                    break;
            }
        }
        $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
        $patrol_type = 3; //1 系统巡检 2智能巡检  3AI识别巡检 
        switch ($view_type) {
            case 1:
                $where['view_time'] = array('gt', 1);
                break;
            case 2:
                $where['view_time'] = array('lt', 1);
                break;
            case 3:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('gt', 0);
                break;
            case 4:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('lt', 1);
                break;
            default:
                break;
        }
        if ($patrol_type == 1) {
            $where['from_type'] = 0;
        } else if ($patrol_type == 2) {
            $where['from_type'] = 1;
            $where['rtype'] = 1;
        } else if ($patrol_type == 3) {
            $where['from_type'] = 1;
            $where['rtype'] = 2;
        }
        $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
        if (!empty($recognition_type)) {
            $where['robot_recognition_type'] = $recognition_type;
        }
        $count_user = D('Wisdom_qrcode_record_log')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 15, 'page');
        $dataList = M('Wisdom_qrcode_record_log')->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        $third_robot_deviceDb = D('Third_robot_device');
        foreach ($dataList as $k => &$v) {
            $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
            if ($cate) {
                $v['cate_name'] = $cate['cate_name'];
            } else {
                $v['cate_name'] = '';
            }
            $v['patrol_type_str'] = '系统巡检';
            if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                $v['patrol_type_str'] = '智能巡检';
            } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                $v['patrol_type_str'] = 'AI识别巡检';
            }
            $v['recognition_type_str'] = $v['robot_recognition_type'] ? $v['robot_recognition_type'] : '';
            if ($v['robot_recognition_type'] && isset($visionLogTyppeInfo[$v['robot_recognition_type']])) {
                $v['recognition_type_str'] = $visionLogTyppeInfo[$v['robot_recognition_type']];
            }
            $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
            if ($qrcode) {
                $v['qrcode_name'] = $qrcode['name'];
            } else {
                $v['qrcode_name'] = '';
            }
            $v['name'] = '';
            $v['phone'] = '';
            if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                if ($robot_device) {
                    $v['name'] = $robot_device['name'];
                }
            } elseif ($v['uid'] > 0) {
                $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                if ($worker) {
                    $v['name'] = $worker['name'];
                    $v['phone'] = $worker['phone'];
                }
            }
        }
        $visionLogTyppeArr = array(
            ['type_key' => 'doorStatus', 'type_name' => '大门检测'],
            ['type_key' => 'vehicle', 'type_name' => '车辆乱停放'],
            ['type_key' => 'garbageCan', 'type_name' => '垃圾桶满溢'],
            ['type_key' => 'trash', 'type_name' => '绿化带垃圾'],
            ['type_key' => 'climb', 'type_name' => '翻越围栏'],
            ['type_key' => 'crowd', 'type_name' => '人员聚集'],
            ['type_key' => 'countVehicle', 'type_name' => '车辆堵塞'],
            ['type_key' => 'carPlate', 'type_name' => '车位识别'],
            ['type_key' => 'lawnTrample', 'type_name' => '践踏草坪'],
            ['type_key' => 'cvMeter', 'type_name' => '巡检仪表识别'],
            ['type_key' => 'countCan', 'type_name' => '垃圾桶数量识别'],
            ['type_key' => 'trashbin', 'type_name' => '垃圾桶盖'],
            ['type_key' => 'bottle', 'type_name' => '地面垃圾'],
            ['type_key' => 'occupyEA', 'type_name' => '应急通道占用'],
        );
        $this->assign('visionLogTyppeArr', $visionLogTyppeArr);
        $this->assign('dataList', $dataList);
        $this->assign('pagebar', $p->show());
        $this->display();
    }
    public function qrcode_record_index()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $is_property_robot_jsdhwy = D('Config_customization')->getPropertyRobotJsdhwy();
        $sqAeondeLightJiangsuCustomized_judge = D('Config_customization')->getSqAeondeLightJiangsuCustomized();
        if ($_GET['type'] == 1) {
            $property_endtime_start = $_GET['start_time'];
            $property_endtime_end = $_GET['end_time'];
            $property_endtime_start = strtotime($property_endtime_start);
            $property_endtime_end = strtotime($property_endtime_end);
            $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
            $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 
            $where = array();
            switch ($view_type) {
                case 1:
                    $where['view_time'] = array('gt', 1);
                    break;
                case 2:
                    $where['view_time'] = array('lt', 1);
                    break;
                case 3:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('gt', 0);
                    break;
                case 4:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('lt', 1);
                    break;
                default:
                    break;
            }
            if ($patrol_type == 1) {
                $where['from_type'] = 0;
            } else if ($patrol_type == 2) {
                $where['from_type'] = 1;
                $where['rtype'] = 1;
            } else if ($patrol_type == 3) {
                $where['from_type'] = 1;
                $where['rtype'] = 2;
            }
            $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
            if (!empty($recognition_type)) {
                $where['robot_recognition_type'] = $recognition_type;
            }
            $where['cate_id'] = $_GET['cate_id'];
            $where['village_id'] = $this->village_id;
            $where['_string'] .= " add_time between '" . $property_endtime_start . "' and '" . $property_endtime_end . "'";
            $user_list = M('Wisdom_qrcode_record_log')->where($where)->select();

            if (count($user_list) <= 0) {
                $this->error('无数据导出！');
            }

            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

            $title = '123456';
            $test = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $objExcel = new PHPExcel();
            $objProps = $objExcel->getProperties();
            // 设置文档基本属性
            $objProps->setCreator($title);
            $objProps->setTitle($title);
            $objProps->setSubject($title);
            $objProps->setDescription($title);

            $length = ceil(count($user_list) / 1000);

            for ($i = 0; $i < intval($length); $i++) {
                $i && $objExcel->createSheet();
                $objExcel->setActiveSheetIndex($i);

                $objExcel->getActiveSheet()->setTitle('智慧二维码记录');
                $objActSheet = $objExcel->getActiveSheet();

                $objActSheet->setCellValue('A1', '二维码类别');
                $objActSheet->setCellValue('B1', '二维码名称');
                $objActSheet->setCellValue('C1', '负责人姓名');
                $objActSheet->setCellValue('D1', '负责人联系方式');
                $objActSheet->setCellValue('E1', '记录时间');

                //                if (!empty($user_list)) {
                //                    $index = 2;
                //
                //
                //                    foreach ($user_list as $value) {
                //                        $objActSheet->setCellValueExplicit('A' . $index, $value['cate_id']);
                //                        $objActSheet->setCellValueExplicit('B' . $index, $value['qrcode_id']);
                //                        $objActSheet->setCellValueExplicit('C' . $index, $value['uid']);
                //                        $index++;
                //                    }
                //                }
                sleep(2);
                //输出
                $objWriter = new PHPExcel_Writer_Excel5($objExcel);
                ob_end_clean();
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                header("Content-Type:application/force-download");
                header("Content-Type:application/vnd.ms-execl");
                header("Content-Type:application/octet-stream");
                header("Content-Type:application/download");
                header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
                header("Content-Transfer-Encoding:binary");
                $objWriter->save('php://output');
                exit();
            }
        } else {
            $where['village_id'] = $this->village_id;
            $add_time_start = $_GET['begin_time'];
            $add_time_end = $_GET['end_time'];
            $this->assign('begin_time', $add_time_start);
            $this->assign('end_time', $add_time_end);

            if ($add_time_start && $add_time_end) {

                $start_time = strtotime($add_time_start . '00:00:00');
                $end_time = strtotime($add_time_end . '23:59:59');

                $where['add_time'] = array('between', array($start_time, $end_time));
            } else if ($add_time_start) {
                $add_time_start = strtotime($add_time_start);
                $where['add_time'] = array('egt', $add_time_start);
            } else if ($add_time_end) {
                $add_time_end = strtotime($add_time_end . '23:59:59');
                $where['add_time'] = array('lt', $add_time_end);
            }

            if ($find_type = $_GET['find_type'] + 0) {
                switch ($find_type) {
                    case 1: //二维码名称
                        //$where['usernum'] = $_GET['find_value'];
                        $qrcode['name'] = $_GET['find_value'];
                        $qrcode_ids = [];
                        $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
                        foreach ($qrcode_list as $k => $v) {
                            $qrcode_ids[] = $v['id'];
                        }
                        $where['qrcode_id'] = array('in', $qrcode_ids);
                        break;
                    case 2: //二维码类别
                        //$where['phone'] = $_GET['find_value'];
                        $qrcode['cate_name'] = $_GET['find_value'];
                        $cate_ids = [];
                        $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
                        foreach ($qrcode_cate as $k => $v) {
                            $cate_ids[] = $v['id'];
                        }
                        $where['cate_id'] = array('in', $cate_ids);
                        break;
                    case 3: //负责人姓名
                        //$where['address'] = $_GET['find_value'];
                        $worker['name'] = $_GET['find_value'];
                        $worker['village_id'] = $this->village_id;
                        $worker = D('House_worker')->where($worker)->select();
                        foreach ($worker as $k => $v) {
                            $worker_ids[] = $v['wid'];
                        }
                        $where['uid'] = array('in', $worker_ids);
                        break;
                    case 4: //负责人联系方式
                        //$where['address'] = $_GET['find_value'];
                        $worker['phone'] = $_GET['find_value'];
                        $worker['village_id'] = $this->village_id;
                        $worker = D('House_worker')->where($worker)->select();

                        foreach ($worker as $k => $v) {
                            $worker_ids[] = $v['wid'];
                        }
                        $where['uid'] = array('in', $worker_ids);
                        break;

                    default:
                        break;
                }
            }
            $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
            $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 
            switch ($view_type) {
                case 1:
                    $where['view_time'] = array('gt', 1);
                    break;
                case 2:
                    $where['view_time'] = array('lt', 1);
                    break;
                case 3:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('gt', 0);
                    break;
                case 4:
                    $where['from_type'] = 1;
                    $where['work_order_id'] = array('lt', 1);
                    break;
                default:
                    break;
            }
            if ($patrol_type == 1) {
                $where['from_type'] = 0;
            } else if ($patrol_type == 2) {
                $where['from_type'] = 1;
                $where['rtype'] = 1;
            } else if ($patrol_type == 3) {
                $where['from_type'] = 1;
                $where['rtype'] = 2;
            }
            $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
            if (!empty($recognition_type)) {
                $where['robot_recognition_type'] = $recognition_type;
            }
            $count_user = D('Wisdom_qrcode_record_log')->where($where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_user, 15, 'page');
            $dataList = M('Wisdom_qrcode_record_log')->where($where)->order('add_time desc')->limit($p->firstRow . ',' . $p->listRows)->select();
            $third_robot_deviceDb = D('Third_robot_device');
            foreach ($dataList as $k => &$v) {
                $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
                if ($cate) {
                    $v['cate_name'] = $cate['cate_name'];
                } else {
                    $v['cate_name'] = '';
                }
                $v['patrol_type_str'] = '系统巡检';
                if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                    $v['patrol_type_str'] = '智能巡检';
                } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                    $v['patrol_type_str'] = 'AI识别巡检';
                }
                $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
                if ($qrcode) {
                    $v['qrcode_name'] = $qrcode['name'];
                } else {
                    $v['qrcode_name'] = '';
                }
                $v['name'] = '';
                $v['phone'] = '';
                if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                    $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                    if ($robot_device) {
                        $v['name'] = $robot_device['name'];
                    }
                } elseif ($v['uid'] > 0) {
                    $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                    if ($worker) {
                        $v['name'] = $worker['name'];
                        $v['phone'] = $worker['phone'];
                    }
                }
            }
            $qrcodeRecordExportDetails = D('Config_customization')->getQrcodeRecordExportDetails();
            if ($sqAeondeLightJiangsuCustomized_judge) {
                $qrcodeRecordExportDetails = $sqAeondeLightJiangsuCustomized_judge;
            }
            $this->assign('qrcodeRecordExportDetails', $qrcodeRecordExportDetails);
            $this->assign('is_property_robot_jsdhwy', $is_property_robot_jsdhwy);
            $this->assign('dataList', $dataList);
            $this->assign('pagebar', $p->show());
            $this->display();
        }
    }

    public function qrcode_record_info()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $id = $_GET['id'];
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $id, 'village_id' => $this->village_id))->find();
        if ($record_log['entication_field']) {
            $entication_field = unserialize($record_log['entication_field']);
            $record_log['entication_field'] = $entication_field['test'];
            foreach ($record_log['entication_field'] as $k1 => &$v1) {

                if ($v1['record_cate_field_config']) {
                    foreach ($v1['record_cate_field_config'] as $kk => &$vv) {
                        if ($vv['type'] == 2) {
                            foreach ($vv['value'] as $k3 => $v3) {
                                if (!empty($v3) && strpos($v3, 'http:') === false && strpos($v3, 'https:') === false) {
                                    $vv['image_value'][] = replace_file_domain($v3);
                                } elseif (!empty($v3)) {
                                    $vv['image_value'][] = $v3;
                                }
                            }
                            $vv['value'] = '';
                        }
                    }
                }
            }
        } else {
            $record_log['entication_field'] = [];
        }
        $robot_record = array();
        if (isset($record_log['robot_record']) && !empty($record_log['robot_record'])) {
            $robot_record = json_decode($record_log['robot_record'], 1);
            if ($robot_record) {
                $robot_record['start_time_str'] = $robot_record['start_time'] > 100 ? date('Y-m-d H:i:s', $robot_record['start_time']) : '';
                $robot_record['end_time_str'] = $robot_record['end_time'] > 100 ? date('Y-m-d H:i:s', $robot_record['end_time']) : '';
            }
        }
        M('Wisdom_qrcode_record_log')->where(array('id' => $id))->data(['view_time' => time()])->save();
        $this->assign('robot_record', $robot_record);
        $this->assign('record_log', $record_log);

        $this->display('qrcode_record_info_test');
    }

    public function handle_record_log_pic($log_id = 0, $surl = '', $img_key = '', $opt = 'add')
    {
        if (empty($img_key) || empty($surl)) {
            return false;
        }
        $surl_path_arr = explode('upload/', $surl);
        $surl_path = $surl_path_arr['1'];
        $img_key_arr = explode('_', $img_key);
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $log_id, 'village_id' => $this->village_id))->find();
        if ($record_log && $record_log['entication_field']) {
            $entication_field = unserialize($record_log['entication_field']);
            $record_log['entication_field'] = $entication_field['test'];
            $is_del_handle = false;
            $is_add_handle = false;
            foreach ($record_log['entication_field'] as $k1 => $v1) {
                if ($is_del_handle || $is_add_handle) {
                    break;
                }
                if ($k1 != $img_key_arr['0']) {
                    continue;
                }
                if (empty($v1['record_cate_field_config'])) {
                    continue;
                }
                foreach ($v1['record_cate_field_config'] as $kk => $vv) {
                    if ($is_del_handle || $is_add_handle) {
                        break;
                    }
                    if ($kk != $img_key_arr['1'] || $vv['type'] != 2) {
                        continue;
                    }
                    if ($opt == 'add') {
                        if (!isset($entication_field['test'][$k1]['record_cate_field_config'][$kk]['value']) || !is_array($entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'])) {
                            $entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'] = array();
                        }
                        $entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'][] = $surl;
                        $is_add_handle = true;
                    } elseif ($opt == 'del') {
                        foreach ($vv['value'] as $k3 => $v3) {
                            if (strpos($v3, $surl_path)) {
                                unset($vv['value'][$k3]);
                                unset($entication_field['test'][$k1]['record_cate_field_config'][$kk]['value'][$k3]);
                                $is_del_handle = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ($is_del_handle && $opt == 'del') {
                $saveArr = array('entication_field' => serialize($entication_field));
                M('Wisdom_qrcode_record_log')->where(array('id' => $log_id, 'village_id' => $this->village_id))->data($saveArr)->save();
                return true;
            }
            if ($is_add_handle && $opt == 'add') {
                $saveArr = array('entication_field' => serialize($entication_field));
                M('Wisdom_qrcode_record_log')->where(array('id' => $log_id, 'village_id' => $this->village_id))->data($saveArr)->save();
                return true;
            }
        }
        return false;
    }
    public function ajax_add_record_pic()
    {
        $record_log_id = $_POST['record_log_id'];
        $record_log_id = $record_log_id ? intval($record_log_id) : 0;
        $surl = $_POST['surl'];
        $surl = $surl ? trim($surl) : '';
        $surl = $surl ? urldecode($surl) : '';
        $img_key = $_POST['img_key'];
        $img_key = $img_key ? trim($img_key) : '';
        $ret = $this->handle_record_log_pic($record_log_id, $surl, $img_key, 'add');
        if ($ret && !empty($surl)) {
            echo  json_encode(array('err_code' => 0, 'msg' => '图片添加成功！'), JSON_UNESCAPED_UNICODE);
        } else {
            echo  json_encode(array('err_code' => 1, 'msg' => '图片添加失败了！'), JSON_UNESCAPED_UNICODE);
        }
    }
    public function ajax_del_record_pic()
    {
        $record_log_id = $_POST['record_log_id'];
        $record_log_id = $record_log_id ? intval($record_log_id) : 0;
        $surl = $_POST['surl'];
        $surl = $surl ? trim($surl) : '';
        $surl = $surl ? urldecode($surl) : '';
        $img_key = $_POST['img_key'];
        $img_key = $img_key ? trim($img_key) : '';
        $ret = $this->handle_record_log_pic($record_log_id, $surl, $img_key, 'del');
        if ($ret && !empty($surl)) {
            echo  json_encode(array('err_code' => 0, 'msg' => '图片删除成功！'), JSON_UNESCAPED_UNICODE);
        } else {
            echo  json_encode(array('err_code' => 1, 'msg' => '图片删除失败了！'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function qrecord_to_work_order()
    {
        $log_id = $_GET['log_id'];
        $log_id = intval($log_id);
        if ($log_id < 1) {
            $this->error('记录日志不存在！');
        }
        $where = array();
        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $where['parent_id'] = 0;
        $field = 'cate_name,id';
        $workcates = D('House_new_repair_cate')->where($where)->field($field)->order('id desc')->select();
        $this->assign('workcates', $workcates);
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $log_id))->find();
        $robot_record = array();
        if (isset($record_log['robot_record']) && !empty($record_log['robot_record'])) {
            $robot_record = json_decode($record_log['robot_record'], 1);
            if ($robot_record) {
                $robot_record['start_time_str'] = $robot_record['start_time'] > 100 ? date('Y-m-d H:i:s') : '';
                $robot_record['end_time_str'] = $robot_record['end_time'] > 100 ? date('Y-m-d H:i:s') : '';
            }
        }
        $this->assign('robot_record', $robot_record);
        $this->assign('record_log', $record_log);
        $this->display();
    }
    public function send_ai_message_set()
    {
        $where = array('village_id' => $this->village_id, 'status' => 1, 'is_del' => 0);
        $house_worker = M('House_worker')->where($where)->field('wid,name,village_id,nickname,phone,openid')->select();
        $this->assign('house_worker', $house_worker);
        $this->display();
    }
    public function get_work_order_cate()
    {
        $f_cate_id = $_GET['f_cate_id'];
        $f_cate_id = intval($f_cate_id);
        $where = array();
        $where['village_id'] = $this->village_id;
        $where['status'] = 1;
        $where['parent_id'] = $f_cate_id;
        $field = 'cate_name,id';
        $workcates = D('House_new_repair_cate')->where($where)->field($field)->order('id desc')->select();
        echo json_encode(array('workcates' => $workcates), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    public function add_work_order()
    {
        $f_cate_id = $_POST['f_cate_id'];
        $f_cate_id = intval($f_cate_id);
        $cate_id = $_POST['cate_id'];
        $cate_id = intval($cate_id);
        $log_id = $_POST['log_id'];
        $log_id = intval($log_id);
        if ($f_cate_id < 1) {
            echo json_encode(array('err_code' => 1, 'msg' => '请选择工单类目！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        if ($cate_id < 1) {
            echo json_encode(array('err_code' => 1, 'msg' => '请选择工单分类！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        if ($log_id < 1) {
            echo json_encode(array('err_code' => 1, 'msg' => '二维记录ID错误！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        $record_log = M('Wisdom_qrcode_record_log')->where(array('id' => $log_id))->find();
        if ($record_log && isset($record_log['robot_record']) && !empty($record_log['robot_record'])) {
            $params = array();
            $params['f_cate_id'] = $f_cate_id;
            $params['cate_id'] = $cate_id;
            $params['log_id'] = $log_id;
            $params['village_id'] = $this->village_id;
            $postData = array('params' => $params);
            $returnres = invoke_v20_service('\app\community\model\service\RepairCateService/addWisdomQrcodeToWorksOrder', $postData);
            fdump_api(['postData' => $postData, 'res' => $returnres], '00addWisdomQrcodeToWorksOrder', 1);
            if ($returnres && isset($returnres['data']) && isset($returnres['data']['retval'])) {
                $tmpret = $returnres['data']['retval'];
                if ($tmpret['err_code'] == 1) {
                    $msg = $tmpret['msg'] ? $tmpret['msg'] : '生成工单失败了！';
                    echo json_encode(array('err_code' => 1, 'msg' => $msg), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit();
                } else {
                    echo json_encode(array('err_code' => 0, 'msg' => '生成工单成功！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    exit();
                }
            } else {
                echo json_encode(array('err_code' => 1, 'msg' => '生成工单失败了！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit();
            }
        } else {
            echo json_encode(array('err_code' => 1, 'msg' => '二维记录不存在！'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
    }
    public function ajax_user_info()
    {
        $uid = $_POST['uid'];
        if (!$uid) {
            echo json_encode(array('code' => 1, 'msg' => '用户不存在!'));
            die;
        }
        $user = D('House_worker')->where(array('wid' => $uid))->find();
        if ($user) {
            echo json_encode(array('code' => 2, 'msg' => $user['phone']));
            die;
        } else {
            echo json_encode(array('code' => 1, 'msg' => '用户不存在!'));
            die;
        }
    }


    public function download_record()
    {
        if (IS_POST) {
        } else {
            $wisdom_qrcode_cate = M('Wisdom_qrcode_cate')->where(array('village_id' => $this->village_id))->select();
            $this->assign('wisdom_qrcode_cate', $wisdom_qrcode_cate);
            $this->display();
        }
    }

    public function download_person_ten_record()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $where = array('village_id' => $this->village_id, 'uid' => array('gt', 0));
        $add_time_start = isset($_GET['begin_time']) && $_GET['begin_time'] ? trim($_GET['begin_time']) : '';
        $add_time_end = isset($_GET['end_time']) && $_GET['end_time'] ? trim($_GET['end_time']) : '';
        if ($add_time_start && $add_time_end) {
            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('lt', $add_time_end);
        }
        $find_type = isset($_GET['find_type']) &&  $_GET['find_type'] > 0 ? $_GET['find_type'] : 0;
        $find_value = isset($_GET['find_value']) && $_GET['find_value'] ? trim($_GET['find_value']) : '';
        if ($find_type > 0 && !empty($find_value)) {
            switch ($find_type) {
                case 1: //二维码名称
                    $qrcode = array();
                    $qrcode['name'] = $find_value;
                    $qrcode_ids = [];
                    $qrcode_list = M('wisdom_qrcode')->where($qrcode)->field('id')->select();
                    foreach ($qrcode_list as $k => $v) {
                        $qrcode_ids[] = $v['id'];
                    }
                    unset($qrcode_list);
                    $where['qrcode_id'] = array('in', $qrcode_ids);
                    break;
                case 2: //二维码类别
                    $qrcode = array();
                    $qrcode['cate_name'] = $find_value;
                    $cate_ids = [];
                    $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->field('id')->select();
                    foreach ($qrcode_cate as $k => $v) {
                        $cate_ids[] = $v['id'];
                    }
                    unset($qrcode_cate);
                    $where['cate_id'] = array('in', $cate_ids);
                    break;
                case 3: //负责人姓名
                    $workerwhere = array();
                    $workerwhere['name'] = $find_value;
                    $workerwhere['village_id'] = $this->village_id;
                    $worker = D('House_worker')->where($workerwhere)->field('wid')->select();
                    foreach ($worker as $k => $v) {
                        $worker_ids[] = $v['wid'];
                    }
                    unset($worker);
                    $where['uid'] = array('in', $worker_ids);
                    break;
                case 4: //负责人联系方式
                    $workerwhere = array();
                    $workerwhere['phone'] = $find_value;
                    $workerwhere['village_id'] = $this->village_id;
                    $worker = D('House_worker')->where($workerwhere)->field('wid')->select();
                    foreach ($worker as $k => $v) {
                        $worker_ids[] = $v['wid'];
                    }
                    unset($worker);
                    $where['uid'] = array('in', $worker_ids);
                    break;

                default:
                    break;
            }
        }
        $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
        $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 
        switch ($view_type) {
            case 1:
                $where['view_time'] = array('gt', 1);
                break;
            case 2:
                $where['view_time'] = array('lt', 1);
                break;
            case 3:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('gt', 0);
                break;
            case 4:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('lt', 1);
                break;
            default:
                break;
        }
        if ($patrol_type == 1) {
            $where['from_type'] = 0;
        } else if ($patrol_type == 2) {
            $where['from_type'] = 1;
            $where['rtype'] = 1;
        } else if ($patrol_type == 3) {
            $where['from_type'] = 1;
            $where['rtype'] = 2;
        }

        $user_list = M('Wisdom_qrcode_record_log')->where($where)->field('id,cate_id,qrcode_id,uid,village_id,add_time,entication_field')->order('id desc')->select();
        if (empty($user_list)) {
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = $this->village['village_name'] . '导出巡检明细表';
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle('智慧二维码记录');
        $objActSheet = $objExcel->getActiveSheet();

        $objActSheet->setCellValue('A1', '二维码类别');
        $objActSheet->setCellValue('B1', '二维码名称');
        $objActSheet->setCellValue('C1', '负责人姓名');
        $objActSheet->setCellValue('D1', '负责人联系方式');
        $objActSheet->setCellValue('E1', '记录时间');
        $objActSheet->setCellValue('F1', '检查项目1');
        $objActSheet->setCellValue('G1', '检查项目2');
        $objActSheet->setCellValue('H1', '检查项目3');
        $objActSheet->setCellValue('I1', '检查项目4');
        $objActSheet->setCellValue('J1', '检查项目5');
        $objActSheet->setCellValue('K1', '检查项目6');
        $objActSheet->setCellValue('L1', '检查项目7');
        $objActSheet->setCellValue('M1', '检查项目8');
        $objActSheet->setCellValue('N1', '检查项目9');
        $objActSheet->setCellValue('O1', '检查项目10');
        $objActSheet->setCellValue('P1', '检查项目11');
        $objActSheet->setCellValue('Q1', '检查项目12');
        $objActSheet->setCellValue('R1', '检查项目13');
        $objActSheet->setCellValue('S1', '检查项目14');
        $objActSheet->setCellValue('T1', '检查项目15');
        $objActSheet->setCellValue('U1', '检查项目16');
        $objActSheet->setCellValue('V1', '检查项目17');
        $objActSheet->setCellValue('W1', '检查项目18');
        $cell_list = range('A', 'E');
        foreach ($cell_list as $cell) {
            $objActSheet->getColumnDimension($cell)->setWidth(30);
        }
        $cell_list = range('F', 'X');
        foreach ($cell_list as $cell) {
            $objActSheet->getColumnDimension($cell)->setWidth(80);
        }
        $objActSheet->getRowDimension(1)->setRowHeight(25);
        $objActSheet->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objActSheet->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $wisdom_qrcode_cateDb = M('Wisdom_qrcode_cate');
        $wisdom_qrcode_record_cateDb = M('Wisdom_qrcode_record_cate');
        $index = 2;
        $otherField = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X');
        if ($user_list && is_array($user_list)) {
            foreach ($user_list as $value) {
                $objActSheet->getRowDimension($index)->setRowHeight(100);
                $qrcode_cate = $wisdom_qrcode_cateDb->where(array('id' => $value['cate_id'], 'village_id' => $value['village_id']))->field('id,cate_name')->find();
                if ($qrcode_cate) {
                    $objActSheet->setCellValueExplicit('A' . $index, $qrcode_cate['cate_name']);
                } else {
                    $objActSheet->setCellValueExplicit('A' . $index, '');
                }
                $qrcode = M('Wisdom_qrcode')->where(array('id' => $value['qrcode_id']))->field('id,name')->find();
                if ($qrcode) {
                    $objActSheet->setCellValueExplicit('B' . $index, $qrcode['name']);
                } else {
                    $objActSheet->setCellValueExplicit('B' . $index, '');
                }
                $house_worker = D('House_worker')->where(array('wid' => $value['uid']))->field('wid,name,phone')->find();
                if ($house_worker) {
                    $objActSheet->setCellValueExplicit('C' . $index, $house_worker['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $house_worker['phone']);
                } else {
                    $objActSheet->setCellValueExplicit('C' . $index, '');
                    $objActSheet->setCellValueExplicit('D' . $index, '');
                }
                //$tenwhere=array('qrcode_id'=>$value['qrcode_id'],'cate_id'=>$value['cate_id'],'village_id' => $this->village_id,'uid' => $value['uid']);
                //$record_ten_list = M('Wisdom_qrcode_record_log')->where($tenwhere)->field('id,cate_id,qrcode_id,uid,village_id,add_time,entication_field')->order('id desc')->limit('0,30')->select();
                $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['add_time']));


                if ($value['entication_field']) {
                    $entication_field = unserialize($value['entication_field']);
                    if ($entication_field) {
                        foreach ($entication_field as $k3 => $fdv) {
                            if ($fdv) {
                                $mindex = 0;
                                foreach ($fdv as $k4 => $v4) {
                                    foreach ($v4['record_cate_field_config'] as $k5 => $v5) {
                                        $msg_test = '';
                                        $msg_test = $v4['record_name'] . "：\n";
                                        $mc = $k5 + 1;
                                        $msg_test .=  $v5['title'] . '：';
                                        if ($v5['type'] == 2 && is_array($v5['value'])) {
                                            $msg_test .= "\n";
                                            foreach ($v5['value'] as $img) {
                                                $logo = replace_file_domain($img);
                                                $msg_test .= $logo . "\n";
                                            }
                                        } else {
                                            $msg_test .= $v5['value'] . "\n";
                                        }
                                        $objActSheet->getStyle($otherField[$mindex] . $index)->getAlignment()->setWrapText(true);
                                        $objActSheet->setCellValueExplicit($otherField[$mindex] . $index, $msg_test);
                                        $mindex++;
                                        if ($mindex >= 10) {
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    /*
                        if ($msg_test) {
                            $objActSheet->getStyle($otherField[$mindex] . $index)->getAlignment()->setWrapText(true);
                            $objActSheet->setCellValueExplicit($otherField[$mindex] . $index, $msg_test);
                            $mindex++;
                        }
                        if ($mindex >= 10) {
                            break;
                        }
                        */
                }

                $index++;
            }
            sleep(2);
        }

        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=' . urlencode($title) .  '_' . date("Y-m-d h:i:sa", time()) . '.xls');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }
    public function  download_record_list()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where = array();

//        if ($_GET['find_type'] != 2) {
//            $this->error('请选择二维码类别进行导出条件！');
//        }

        //        if(empty($_GET['begin_time']) || empty($_GET['end_time'])){
        //            $this->error('请选择请选择开始时间和结束时间！');
        //        }
        $where['village_id'] = $this->village_id;
        if( !empty($_GET['find_value']) ){
            $where['cate_name'] = trim( $_GET['find_value'] );
        }
        $qrcode_cate = M('Wisdom_qrcode_cate')->where( $where )->select();

        if (empty($qrcode_cate)) {
            $this->error('无数据导出！');
        }
        $view_type = isset($_GET['view_type']) ? intval($_GET['view_type']) : 0;
        $patrol_type = isset($_GET['patrol_type']) ? intval($_GET['patrol_type']) : 0; //1 系统巡检 2智能巡检  3AI识别巡检 

        switch ($view_type) {
            case 1:
                $where['view_time'] = array('gt', 1);
                break;
            case 2:
                $where['view_time'] = array('lt', 1);
                break;
            case 3:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('gt', 0);
                break;
            case 4:
                $where['from_type'] = 1;
                $where['work_order_id'] = array('lt', 1);
                break;
            default:
                break;
        }
        if ($patrol_type == 1) {
            $where['from_type'] = 0;
        } else if ($patrol_type == 2) {
            $where['from_type'] = 1;
            $where['rtype'] = 1;
        } else if ($patrol_type == 3) {
            $where['from_type'] = 1;
            $where['rtype'] = 2;
        }
        $recognition_type = isset($_GET['recognition_type']) ? trim($_GET['recognition_type']) : '';
        if (!empty($recognition_type)) {
            $where['robot_recognition_type'] = $recognition_type;
        }
        $qrcode_cate_list = array_column($qrcode_cate, 'id');
        $where['cate_id'] = ['in', $qrcode_cate_list];
        $where['village_id'] = $this->village_id;
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        if ($add_time_start && $add_time_end) {

            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');

            $where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $where['add_time'] = array('lt', $add_time_end);
        }
        $user_list = M('Wisdom_qrcode_record_log')->where($where)->order('id desc')->select();
        if (count($user_list) <= 0) {
            $this->error('无数据导出！');
        }
        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = $this->village['village_name'] . '智慧二维码记录';
        $test = array('F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($user_list) / 1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('智慧二维码记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '二维码类别');
            $objActSheet->setCellValue('B1', '二维码名称');
            $objActSheet->setCellValue('C1', '负责人姓名');
            $objActSheet->setCellValue('D1', '负责人联系方式');
            $objActSheet->setCellValue('E1', '记录时间');
            foreach ($user_list as $value1) {
                if ($value1['entication_field']) {
                    $entication_field = unserialize($value1['entication_field']);
                    foreach ($entication_field as $k3 => $v3) {
                        if ($v3) {
                            foreach ($v3 as $k4 => $v4) {
                                foreach ($v4['record_cate_field_config'] as $k5 => $v5) {
                                    $qrcode_record_cate = M('Wisdom_qrcode_record_cate')->where(array('cate_id' => $qrcode_cate['id']))->select();
                                    if ($qrcode_record_cate) {
                                        $qrcode_record_cate_data = array();
                                        $qrcode_record_cate_name_arr = array();
                                        foreach ($qrcode_record_cate as $kk => $vv) {
                                            $qrcode_record_cate_data[] = $vv['id'];
                                            $qrcode_record_cate_name_arr[$vv['id']] = $vv['record_name'];
                                        }
                                        $field_cate_data['record_cate_id'] = array('in', $qrcode_record_cate_data);
                                        $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where($field_cate_data)->select();
                                        foreach ($qrcode_record_cate_field_config as $k8 => $v8) {
                                            if (($v8['key'] == $v5['key']) && ($v8['title'] == $v5['title'])) {
                                                $xtitle = $v8['title'];
                                                if (isset($qrcode_record_cate_name_arr[$v8['record_cate_id']])) {
                                                    $xtitle = $qrcode_record_cate_name_arr[$v8['record_cate_id']] . "\r\n" . $v8['title'];
                                                }
                                                $objActSheet->setCellValue($test[$k8] . '1', $xtitle);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // 获取A1单元格的样式
            $styleArray = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap' => true  // 启用自动换行
                ),
            );
            // 应用样式到A1单元格
            $objExcel->getActiveSheet()->getStyle('A1:AZ1')->applyFromArray($styleArray);
            if (!empty($user_list)) {
                $index = 2;
                $cell_list = range('A', 'Z');
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }
                foreach ($test as $cell) {
                    $objActSheet->getColumnDimension($cell)->setWidth(30);
                }
                $qrcodeImageArr = [];
                foreach ($user_list as $value) {
                    $qrcode_cate = M('Wisdom_qrcode_cate')->where(array('id' => $value['cate_id'], 'village_id' => $value['village_id']))->find();
                    if ($qrcode_cate) {
                        $objActSheet->setCellValueExplicit('A' . $index, $qrcode_cate['cate_name']);
                    } else {
                        $objActSheet->setCellValueExplicit('A' . $index, '');
                    }
                    $qrcode = M('Wisdom_qrcode')->where(array('id' => $value['qrcode_id']))->find();
                    if ($qrcode) {
                        $objActSheet->setCellValueExplicit('B' . $index, $qrcode['name']);
                    } else {
                        $objActSheet->setCellValueExplicit('B' . $index, '');
                    }
                    $house_worker = D('House_worker')->where(array('wid' => $value['uid']))->find();
                    if ($house_worker) {
                        $objActSheet->setCellValueExplicit('C' . $index, $house_worker['name']);
                        $objActSheet->setCellValueExplicit('D' . $index, $house_worker['phone']);
                    } else {
                        $objActSheet->setCellValueExplicit('C' . $index, '');
                        $objActSheet->setCellValueExplicit('D' . $index, '');
                    }
                    $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['add_time']));
                    if ($value['entication_field']) {
                        $entication_field = unserialize($value['entication_field']);
                        foreach ($entication_field as $k3 => $v3) {
                            if ($v3) {
                                foreach ($v3 as $k4 => $v4) {
                                    foreach ($v4['record_cate_field_config'] as $k5 => $v5) {
                                        $qrcode_record_cate = M('Wisdom_qrcode_record_cate')->where(array('cate_id' => $qrcode_cate['id']))->select();
                                        if ($qrcode_record_cate) {
                                            $qrcode_record_cate_data = array();
                                            foreach ($qrcode_record_cate as $kk => $vv) {
                                                $qrcode_record_cate_data[] = $vv['id'];
                                            }
                                            $field_cate_data['record_cate_id'] = array('in', $qrcode_record_cate_data);
                                            $qrcode_record_cate_field_config = M('Wisdom_qrcode_record_cate_field_config')->where($field_cate_data)->select();
                                            $countConfig = count($qrcode_record_cate_field_config) - 1;

                                            foreach ($qrcode_record_cate_field_config as $k8 => $v8) {
                                                $configJudge = false;
                                                if (($v8['key'] == $v5['key']) && ($v8['title'] == $v5['title'])) {
                                                    $configJudge = true;
                                                }
                                                if (!$configJudge && $countConfig == $k8 && $v8['key'] == $v5['key']) {
                                                    // 如果走到了最后一个分类都没有匹配上 直接匹配关键值 key
                                                    $configJudge = true;
                                                }
                                                if ($configJudge) {
                                                    $objExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(80);
                                                    if ($v5['type'] == 2) {
                                                        if (isset($qrcodeImageArr[$test[$k8] . $index]) && intval($qrcodeImageArr[$test[$k8] . $index]) == 1) {
                                                            continue;
                                                        }
                                                        $objDrawing[$test[$k8]] = new \PHPExcel_Worksheet_Drawing();
                                                        $imgArr = array();
                                                        foreach ($v5['value'] as $ivv) {
                                                            if (!empty($ivv)) {
                                                                $imgArr[] = $ivv;
                                                            }
                                                        }
                                                        if ($imgArr[0]) {
                                                            $logo = replace_file_domain($imgArr[0]);
                                                            //将oss上的图片先下来
                                                            $file_handle = new file_handle();
                                                            if ($file_handle->check_open_oss()) {
                                                                $file_handle->download($logo);
                                                                $logo = $file_handle->get_path($logo);
                                                                $logo_file = './' . ltrim($logo, '/');
                                                            } else {
                                                                $logo = $_SERVER['DOCUMENT_ROOT'] . str_replace($this->config['site_url'], '', $imgArr[0]);
                                                                $logo_file = $logo;
                                                            }
                                                            if (!file_exists($logo_file)) {
                                                                continue;
                                                            }
                                                            $objDrawing[$test[$k8]]->setPath($logo_file);
                                                            // 设置宽度高度
                                                            $objDrawing[$test[$k8]]->setWidth(120); //照片宽度
                                                            $objDrawing[$test[$k8]]->setHeight(100); //照片高度
                                                            /*设置图片要插入的单元格*/
                                                            $objDrawing[$test[$k8]]->setCoordinates($test[$k8] . $index);
                                                            // 图片偏移距离
                                                            $objDrawing[$test[$k8]]->setOffsetX(12);
                                                            $objDrawing[$test[$k8]]->setOffsetY(12);
                                                            $objDrawing[$test[$k8]]->setWorksheet($objExcel->getActiveSheet());
                                                            $qrcodeImageArr[$test[$k8] . $index] = 1;
                                                            //                                                            $objActSheet->setCellValueExplicit($test[$k8].$index, file_domain().$imgArr[0]);
                                                        } else {
                                                            $objActSheet->setCellValueExplicit($test[$k8] . $index, '');
                                                        }
                                                    } else {
                                                        if ($v5['value']) {
                                                            $v5['value'] = trim($v5['value']);
                                                            $v5['value'] = removeEmojiChar($v5['value']);
                                                        }
                                                        $objActSheet->setCellValueExplicit($test[$k8] . $index, $v5['value']);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $index++;
                }
            }

            sleep(2);
            //输出
            $objWriter = new PHPExcel_Writer_Excel5($objExcel);
            ob_end_clean();
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:sa", time()) . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');

            exit();
        }
    }

    //巡检定位记录
    public function position_record_list()
    {
        $cate_id = intval($_GET['cate_id']);
        $name = trim($_GET['name']);
        $begin_time = trim($_GET['begin_time']);
        $end_time = trim($_GET['end_time']);
        $where = [
            'a.village_id' => $this->village_id,
            'a.cate_id' => $cate_id,
            'a.del_time' => 0
        ];
        if ($begin_time && $end_time) {
            $start_time = strtotime($begin_time . ' 00:00:00');
            $end_time = strtotime($end_time . ' 23:59:59');
            if ($start_time > $end_time) {
                $this->error('请重新选择，【开始时间不可大于结束时间】');
            }
            $where['a.add_time'] = array('between', array($start_time, $end_time));
        }
        $from_type = 0;
        $whereArr = array('id' => $cate_id, 'village_id' => $this->village_id);
        $qrcode_cate = D('Wisdom_qrcode_cate')->where($whereArr)->field('*')->find();
        if ($qrcode_cate && isset($qrcode_cate['from_type']) && ($qrcode_cate['from_type'] == 1) && !empty($qrcode_cate['task_instance_id'])) {
            $robot_where = [
                'village_id' => $this->village_id,
                'cate_id' => $cate_id,
                'del_time' => 0,
                'from_type' => 1,
                'task_instance_id' => $qrcode_cate['task_instance_id'],
            ];
            if (isset($where['a.add_time'])) {
                $robot_where['add_time'] = $where['a.add_time'];
            }
            $list = D('Wisdom_qrcode_cate')->get_robot_position_record_list($robot_where);
            $third_robot_deviceDb = D('Third_robot_device');
            $robot_device = $third_robot_deviceDb->where(array('robot_code' => $qrcode_cate['robot_code']))->field('village_id,name,robot_code')->find();
            $from_type = 1;
            $this->assign('robot_device', $robot_device);
        } else {
            if (!empty($name)) {
                $where['b.name'] = ['like', '%' . $name . '%'];
            }
            $list = D('Wisdom_qrcode_cate')->get_position_record_list($where);
        }
        $no_line_path = 0;
        $isAiHorse = D('Config_customization')->getIsAihorseCustomized();
        if ($this->village_id == 119 && $isAiHorse) {
            $no_line_path = 1;
        }
        $this->assign('no_line_path', $no_line_path);
        $this->assign('from_type', $from_type);
        $this->assign('dataList', $list['dataList']);
        $this->assign('pagebar', $list['pagebar']);
        $this->display();
    }

    public function inspection_record_detail()
    {
        if (!in_array(548, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $where['village_id'] = $this->village_id;
        $count_user = D('Wisdom_qrcode_cate')->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_user, 10, 'page');
        $dataList = D('Wisdom_qrcode_cate')
            ->where($where)
            // ->limit($p->firstRow . ',' . $p->listRows)
            ->order('id desc')
            ->select();
        if (!empty($dataList)) {
            $third_robot_deviceDb = D('Third_robot_device');
            foreach ($dataList as $lkk => $lvv) {
                $dataList[$lkk]['robot_name'] = '';
                if ($lvv['from_type'] && $lvv['from_type'] == 1 && !empty($lvv['robot_code'])) {
                    $robot_device = $third_robot_deviceDb->where(array('robot_code' => $lvv['robot_code']))->field('village_id,name,robot_code')->find();
                    if ($robot_device) {
                        $dataList[$lkk]['robot_name'] = $robot_device['name'];
                    }
                }
            }
        }


        //项目id
        $detail_where['village_id'] = $this->village_id;

        // $this->assign('begin_time', $add_time_start);
        // $this->assign('end_time', $add_time_end);

        // $this->assign('qrcode_name', $qrcode_name);
        // $this->assign('qrcode_type', $qrcode_type);


        //开始结束时间
        $add_time_start = $_GET['begin_time'];
        $add_time_end = $_GET['end_time'];
        if ($add_time_start && $add_time_end) {
            $start_time = strtotime($add_time_start . '00:00:00');
            $end_time = strtotime($add_time_end . '23:59:59');
            $detail_where['add_time'] = array('between', array($start_time, $end_time));
        } else if ($add_time_start) {
            $add_time_start = strtotime($add_time_start);
            $detail_where['add_time'] = array('egt', $add_time_start);
        } else if ($add_time_end) {
            $add_time_end = strtotime($add_time_end . '23:59:59');
            $detail_where['add_time'] = array('lt', $add_time_end);
        }

        //二维码名称
        $qrcode_name = $_GET['qrcode_name'];
        if ($qrcode_name) {
            $qrcode['name'] = $_GET['find_value'];
            $qrcode_ids = [];
            $qrcode_list = M('wisdom_qrcode')->where($qrcode)->select();
            foreach ($qrcode_list as $k => $v) {
                $qrcode_ids[] = $v['id'];
            }
            $detail_where['qrcode_id'] = array('in', $qrcode_ids);
        }

        //二维码类别
        $qrcode_type = $_GET['qrcode_type'];
        if ($qrcode_type) {
            $qrcode['cate_name'] = $_GET['find_value'];
            $cate_ids = [];
            $qrcode_cate = M('wisdom_qrcode_cate')->where($qrcode)->select();
            foreach ($qrcode_cate as $k => $v) {
                $cate_ids[] = $v['id'];
            }
            $detail_where['cate_id'] = array('in', $cate_ids);
        }

        $count_user = D('Wisdom_qrcode_record_log')->where($detail_where)->count();

        import('@.ORG.merchant_page');
        // $p = new Page($count_user, 15, 'page');
        $detailDataList = M('Wisdom_qrcode_record_log')
            ->where($detail_where)
            ->order('add_time desc')
            // ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $third_robot_deviceDb = D('Third_robot_device');

        foreach ($detailDataList as $k => &$v) {
            $cate = D('Wisdom_qrcode_cate')->where(array('id' => $v['cate_id']))->find();
            if ($cate) {
                $v['cate_name'] = $cate['cate_name'];
            } else {
                $v['cate_name'] = '';
            }
            $v['patrol_type_str'] = '系统巡检';
            if ($v['from_type'] == 1 && $v['rtype'] == 1) {
                $v['patrol_type_str'] = '智能巡检';
            } else if ($v['from_type'] == 1 && $v['rtype'] == 2) {
                $v['patrol_type_str'] = 'AI识别巡检';
            }
            $qrcode = D('Wisdom_qrcode')->where(array('id' => $v['qrcode_id']))->find();
            if ($qrcode) {
                $v['qrcode_name'] = $qrcode['name'];
            } else {
                $v['qrcode_name'] = '';
            }
            $v['name'] = '';
            $v['phone'] = '';
            if ($v['from_type'] == 1 && !empty($v['robot_code'])) {
                $robot_device = $third_robot_deviceDb->where(array('robot_code' => $v['robot_code']))->field('village_id,name,robot_code')->find();
                if ($robot_device) {
                    $v['name'] = $robot_device['name'];
                }
            } elseif ($v['uid'] > 0) {
                $worker = D('House_worker')->where(array('wid' => $v['uid']))->find();
                if ($worker) {
                    $v['name'] = $worker['name'];
                    $v['phone'] = $worker['phone'];
                }
            }
        }
        unset($v);

        // $this->assign('detailDataList', $detailDataList);
        // $this->assign('pagebar', $p->show());

        foreach ($dataList as $k_ => &$v_) {
            $v_['detailDataList'] = filterArrayByField($detailDataList, 'cate_name', $v_['cate_name']);
        }
        unset($v_);

        $this->assign('dataList', $dataList);
        // $this->assign('pagebar', $p->show());
        $this->display();


        // echo "<pre>";
        // var_dump( $dataList, $detailDataList );die;
    }

    //地图轨迹
    public function position_map_trail()
    {
        $cate_id = intval($_GET['cate_id']);
        $index_id = intval($_GET['index_id']);
        if (!$cate_id || !$index_id) {
            $this->error('缺少必要参数');
        }
        $list = D('Wisdom_qrcode_cate')->get_position_record($this->village_id, $cate_id, $index_id);
        $this->assign('res', $list);
        $this->display();
    }

    //同步记录内容
    public function qrcode_synchronize_recorded()
    {
        if (!in_array(546, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作!');
        }

        echo "<pre>";
        $id = $_GET['id'];
        $cate_id = $_POST['cate_id'];
        $cate_name = $_POST['cate_name'];
        if (IS_POST) {
            if( !isset($_POST['cate_id']) || !isset($_POST['cate_name']) || !isset($_POST['village_id'])){
                $this->error('缺少参数, 请重试!');
            }
            $origin_cate_info = D('Wisdom_qrcode_cate')->where(array('cate_name' => $cate_name, 'village_id' => $_POST['village_id']))->find();
            if( !isset($origin_cate_info['id']) ){
                $this->error('该分类不存在, 请重试!');
            }
            $cate_list = D('Wisdom_qrcode_record_cate')->where(array('cate_id' => $origin_cate_info['id']))->select();
            $cate_record_ids = implode( ',' ,array_column($cate_list, 'id') );
            foreach ($cate_list as &$list) {
                unset($list['id']);
                unset($list['add_time']);
                $list['cate_id'] = $cate_id;
                $list['add_time'] = time();
            }
            unset($list);
            $res_key2 = 0;
            foreach($cate_list as $k => $v){
                $res = D('Wisdom_qrcode_record_cate')->where(['cate_id'=>$v['cate_id'],'record_name'=>$v['record_name']])->find();
                if( count( $res ) <= 0 ){
                    $result = D('Wisdom_qrcode_record_cate')->data($v)->add();
                    $res_key2++;
                }
            }

            $record_list = D('Wisdom_qrcode_record_cate_field_config')
            ->where('record_cate_id in ('. $cate_record_ids. ')')
            ->select();

            $res_key = 0;
            foreach ($record_list as &$list2) {
                unset($list2['acid']);
                unset($list2['add_time']);
                unset($list2['key']);
                $list2['add_time'] = time();
                $list2['key'] =  $this->getPinyinInitials($this->village['village_name'], true) . '_' . $this->getPinyinInitials($list2['title'], true);
                $record_cate_name = D('Wisdom_qrcode_record_cate')
                ->where(['id'=>$list2['record_cate_id']])
                ->find();
                $record_cate_id = D('Wisdom_qrcode_record_cate')
                ->where(['record_name'=>$record_cate_name['record_name'],'cate_id' => $cate_id ])
                ->find();
                unset($list2['record_cate_id']);
                if( isset($record_cate_id['id']) ){
                    $list2['record_cate_id'] = $record_cate_id['id'];
                }

                $res = D('Wisdom_qrcode_record_cate_field_config')->where(['key'=>$list2['key']])->find();
                if( count( $res ) <= 0 ){
                    $result = D('Wisdom_qrcode_record_cate_field_config')->data($list2)->add();
                    if( $result ){
                        $res_key++;
                    }
                }
            }
            if( $res_key > 0 || $res_key2 > 0 ){
                $this->success('同步成功！', U('cate_index'));
            }else{
                $this->error('同步失败！请重试。');
            }
        } else {
            $cate_info = D('Wisdom_qrcode_cate')->where(array('id' => $id))->find();
            if (!$cate_info) {
                $this->error('参数错误');
            }

            $village_list = D('house_village')
                ->where(['status' => 1])
                ->field('village_id,village_name')
                ->select();
            $mapped_list = array_filter(array_map(function ($item) use ($cate_info) {
                $is_contain = D('Wisdom_qrcode_cate')
                    ->where([
                        'village_id' => $item['village_id'],
                        'cate_name' => $cate_info['cate_name']
                    ])
                    ->select();
                if ($item['village_id'] != $this->village_id && count($is_contain) > 0) {
                    return $item;
                } else {
                    return null;
                }
            }, $village_list));
            sort($mapped_list);
            $this->assign('cate_info', $cate_info);
            $this->assign('village_list', $mapped_list);
            $this->display();
        }
    }
}
