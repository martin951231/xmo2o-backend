<?php

/**
 * 云音箱
 */
class VoiceBoxAction extends BaseAction
{
    public function index()
    {
        $merId = $this->merchant_session['mer_id'];
        $where = ['b.mer_id' => $merId];
        if ($this->merchant_session['store_id']) {
            $where['b.store_id'] = $this->merchant_session['store_id'];
        }
        $list = M('Voice_box')->alias('b')->join(C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = b.store_id')
            ->where($where)
            ->field('b.*,s.name AS store_name')
            ->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function add()
    {
        $merId = $this->merchant_session['mer_id'];
        if (IS_POST) {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $imei = isset($_POST['imei']) ? trim($_POST['imei']) : '';
            $voice_number = isset($_POST['voice_number']) ? intval($_POST['voice_number']) : '0';
            $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if (empty($name)) {
                $this->error(L_('设备名称不能为空'));
            } else if (empty($imei)) {
                $this->error(L_('设备IMEI不能为空'));
            } else if ($storeId < 1) {
                $this->error(L_('请选择店面'));
            } else {
                if ($id > 0) {
                    if (!(M('Voice_box')->find())) {
                        $this->error(L_('音箱不存在'));
                    }
                    $data = [
                        'name' => $name,
                        'imei' => $imei,
                        'mer_id' => $merId,
                        'store_id' => $storeId,
                        'voice_number' => $voice_number,
                        'brand'=>$_POST['brand']??0
                    ];
                    $rs = M('Voice_box')->where(['id' => $id])->save($data);
                } else {
                    $data = [
                        'name' => $name,
                        'imei' => $imei,
                        'mer_id' => $merId,
                        'store_id' => $storeId,
                        'voice_number' => $voice_number,
                        'create_time' => time(),
                        'brand'=>$_POST['brand']??0
                    ];
                    $rs = M('Voice_box')->add($data);
                }
                if ($rs !== false) {
                    $this->success(L_('操作成功'), U('VoiceBox/index'));
                } else {
                    $this->error(L_('操作失败'));
                }
            }
        } else {
            $where = array('mer_id' => $merId, 'status' => 1);
            if ($this->merchant_session['store_id']) {
                $where['store_id'] = $this->merchant_session['store_id'];
            }
            $stores = D('Merchant_store')->field('store_id, name')->where($where)->select();
            $this->assign('stores', $stores);

            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $box = M('Voice_box')->where(['id' => $id])->find();
            $this->assign('box', $box);
            $this->display();
        }
    }

    public function delete()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (M('Voice_box')->where(['id' => $id])->delete()) {
            $this->success('删除成功', U('VoiceBox/index'));
        } else {
            $this->error('删除失败');
        }
    }
}