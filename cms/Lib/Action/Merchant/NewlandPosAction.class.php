<?php

/**
 * 新大陆POS机
 */
class NewlandPosAction extends BaseAction
{
    public function index()
    {
        $merId = $this->merchant_session['mer_id'];
        $list = M('Newland_pos_device')->where(['mer_id' => $merId])->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            $mcode = isset($_POST['mcode']) ? trim($_POST['mcode']) : '';
            $mkey = isset($_POST['mkey']) ? trim($_POST['mkey']) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $merId = $this->merchant_session['mer_id'];
            if (empty($mcode)) {
                $this->error('终端设备号不能为空');
            } else if (empty($mkey)) {
                $this->error('密钥不能为空');
            } else if (empty($name)) {
                $this->error('设备名称不能为空');
            } else {
                if ($id > 0) {
                    //编辑
                    if (M('Newland_pos_device')->where(['mcode' => $mcode, 'id' => ['NEQ', $id]])->find()) {
                        $this->error('终端设备号已绑定其他商户');
                    }
                } else {
                    //新增
                    if (M('Newland_pos_device')->where(['mcode' => $mcode])->find()) {
                        $this->error('终端设备号已绑定其他商户');
                    }
                }
                $data = [
                    'mcode' => $mcode,
                    'mkey' => $mkey,
                    'mer_id' => $merId,
                    'name' => $name,
                    'updae_time' => date('Y-m-d H:i:s')
                ];
                if ($id > 0) {
                    if (M('Newland_pos_device')->where(['id' => $id])->save($data)) {
                        $this->success('编辑成功', U('NewlandPos/index'));
                    } else {
                        $this->error('编辑失败');
                    }
                } else {
                    if (M('Newland_pos_device')->add($data)) {
                        $this->success('添加成功', U('NewlandPos/index'));
                    } else {
                        $this->error('添加失败');
                    }
                }

            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $device = M('Newland_pos_device')->where(['id' => $id])->find();
            $this->assign('device', $device);
            $this->display();
        }
    }

    public function delete()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (M('Newland_pos_device')->where(['id' => $id])->delete()) {
            $this->success('删除成功', U('NewlandPos/index'));
        } else {
            $this->error('删除失败');
        }
    }
}