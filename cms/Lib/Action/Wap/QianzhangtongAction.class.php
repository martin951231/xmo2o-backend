<?php

class QianzhangtongAction extends BaseAction
{
    //会员开户结果通知  member.open
    public function notify()
    {
        fdump_api($_POST, 'memberOpenNotify', true);
        $eventType = $_POST['event_type'] ?? '';
        if (empty($eventType)) {
            exit("error");
        }

        import('@.ORG.pay.Lakala');
        $lakala = new Lakala();
        $mod = M('Lakala_qianzhangtong_archives');
        $_POST['event'] = html_entity_decode($_POST['event']);

        FdsSdk::getService()->getNotifyResult($_POST);

        if ($eventType == 'member.open') {
            $event = json_decode($_POST['event'], true);
            $mod->where(['out_member_no' => $event['out_member_no']])->save($event);
        }
        exit("SUCCESS");
    }

    public function register()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $token = isset($_GET['token']) ? $_GET['token'] : '';
        $applyMod = M('Lakala_qianzhangtong_archives');

        $apply = $applyMod->where(['id' => $id])->find();
        if (empty($apply) || md5($apply['item_type'].$apply['item_id']) != $token) {
            $this->error('非法操作');
        }
        $this->assign('apply', $apply);
        $this->display();
    }

    //绑定商家
    public function bindMerchant()
    {
        $applyMod = M('Lakala_qianzhangtong_archives');
        if (IS_POST) {
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $channelMerchantNo = isset($_POST['channel_merchant_no']) ? $_POST['channel_merchant_no'] : '';
            $channelTerminalNo = isset($_POST['channel_terminal_no']) ? $_POST['channel_terminal_no'] : '';
            if ($id < 1 || empty($channelMerchantNo) || empty($channelTerminalNo)) {
                $this->error('参数有误');
            }

            $apply = $applyMod->where(['id' => $id])->find();
            if (empty($apply)) {
                $this->error('非法操作');
            }

            try {
                import('@.ORG.pay.Lakala');
                $result = (new Lakala())->memberMerchantBind($apply['member_no'], $channelMerchantNo, $channelTerminalNo);
                $data = [
                    'merchant_no' => $result['merchant_no'],
                    'merchant_status' => $result['merchant_status'],
                    'channel_merchant_no' => $channelMerchantNo,
                    'channel_terminal_no' => $channelTerminalNo,
                ];
                $applyMod->where(['id' => $id])->save($data);
                $this->success('绑定成功', U('register', ['id' => $id, 'token' => md5($apply['out_member_no'])], true, false, true));
            } catch (\Throwable $th) {
                $this->error($th->getMessage());
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $token = isset($_GET['token']) ? $_GET['token'] : '';

            $apply = $applyMod->where(['id' => $id])->find();
            if (empty($apply) || md5($apply['item_type'].$apply['item_id']) != $token) {
                $this->error('非法操作');
            }
            $this->assign('apply', $apply);
            $this->display();
        }
    }

    //解绑商家
    public function unbindMerchant()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $token = isset($_POST['token']) ? $_POST['token'] : '';

        $applyMod = M('Lakala_qianzhangtong_archives');
        $apply = $applyMod->where(['id' => $id])->find();
        if (empty($apply) || md5($apply['item_type'].$apply['item_id']) != $token) {
            $this->error('非法操作');
        }

        import('@.ORG.pay.Lakala');
        (new Lakala())->memberMerchantUnbind($apply['member_no'], $apply['channel_merchant_no'], $apply['channel_terminal_no']);
        $data = [
            'merchant_no' => '',
            'merchant_status' => 0,
            'channel_merchant_no' => '',
            'channel_terminal_no' => '',
        ];
        $applyMod->where(['id' => $id])->save($data);
        $this->success('解绑成功');
    }

    //重新绑定银行卡
    public function reBindCard()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $token = isset($_GET['token']) ? $_GET['token'] : '';

        $applyMod = M('Lakala_qianzhangtong_archives');
        $apply = $applyMod->where(['id' => $id])->find();
        if (empty($apply) || md5($apply['item_type'].$apply['item_id']) != $token) {
            $this->error('非法操作');
        }

        import('@.ORG.pay.Lakala');
        $result = (new Lakala())->memberBankCardPageUrlGet($apply['member_no']);
        redirect($result['url']);
    }

    //重新注册会员
    public function reRegister()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $token = isset($_GET['token']) ? $_GET['token'] : '';

        $applyMod = M('Lakala_qianzhangtong_archives');
        $apply = $applyMod->where(['id' => $id])->find();
        if (empty($apply) || md5($apply['item_type'] . $apply['item_id']) != $token) {
            $this->error('非法操作');
        }

        if ($apply['member_state'] != 20) {
            $this->error('当前会员状态非关闭状态，禁止重新注册会员');
        }

        $applyMod->startTrans();
        try {
            //将当前会员移动到历史记录中
            $log = $apply;
            unset($log['id']);
            D('Lakala_qianzhangtong_archives_log')->add($log);

            //重新生成会员标识发起注册
            $oldOutMemberNo = $apply['out_member_no'];
            $arr = explode('_', $oldOutMemberNo);
            if (count($arr) == 1) {
                $newOutMemberNo = $oldOutMemberNo . '_1';
            } else {
                $newOutMemberNo = $arr[0] . '_' . ($arr[1] + 1);
            }
            $newData = [
                'out_member_no' => $newOutMemberNo,
                'member_no' => '',
                'mobile' => '',
                'member_state' => 0,
                'message' => '',
                'card_no' => '',
                'merchant_no' => '',
                'merchant_status' => 0,
                'channel_merchant_no' => '',
                'channel_terminal_no' => '',
                'create_time' => time(),
            ];
            $applyMod->where(['id' => $apply['id']])->save($newData);

            import('@.ORG.pay.Lakala');
            $result = (new Lakala())->register($newOutMemberNo);
            $applyMod->commit();
            redirect($result['url']);
        } catch (Exception $e) {
            $applyMod->rollback();
            $this->error('操作失败');
        }
    }
}
