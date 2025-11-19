<?php

/**
 * 景区闸机管理
 * @author nidan
 */
class GateAction extends BaseAction
{
    public function index()
    {
        $merId = $this->merchant_session['mer_id'];
        $list = M('Gate_device')->where(['mer_id' => $merId])->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function add()
    {
        if (IS_POST) {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $gid = isset($_POST['gid']) ? trim($_POST['gid']) : '';
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $merId = $this->merchant_session['mer_id'];
            if(empty($name)){
                $this->error('闸机名称不能为空');
            }else if(empty($gid)){
                $this->error('闸机设备ID不能为空');
            }else{
                $where = ['gid' => $gid];//默认新增
                if($id > 0){
                    //编辑
                    $where['gid'] = $gid;
                }
                $select = M('Gate_device')->where($where)->find();
                if($id > 0 && $select['mer_id'] == $merId && $select['name'] == $name && $select['gid'] == $gid){
                    $this->success('闸机设备信息未作变更', U('Gate/index'));
                    echo exit;
                }
                if($select){
                    $this->error('闸机设备ID已绑定其他商户');
                }
                $data = [
                    'name' => $name,
                    'gid' => $gid,
                    'mer_id' => $merId,
                    'update_time' => date('Y-m-d H:i:s')
                ];
                if ($id > 0) {//编辑
                    if (M('Gate_device')->where(['id' => $id])->save($data)) {
                        $this->success('编辑成功', U('Gate/index'));
                    } else {
                        $this->error('编辑失败');
                    }
                } else {//新增
                    $data['create_time'] = date('Y-m-d H:i:s');
                    if (M('Gate_device')->add($data)) {
                        $this->success('添加成功', U('Gate/index'));
                    } else {
                        $this->error('添加失败');
                    }
                }
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $device = M('Gate_device')->where(['id' => $id])->find();
            $this->assign('device', $device);
            $this->display();
        }
    }

    public function delete()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (M('Gate_device')->where(['id' => $id])->delete()) {
            $this->success('删除成功', U('gate/index'));
        } else {
            $this->error('删除失败');
        }
    }
}