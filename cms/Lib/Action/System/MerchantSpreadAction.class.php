<?php

/**
 * 推广商管理
 */
class MerchantSpreadAction extends BaseAction
{
    /**
     * 已审核推广商
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function audit()
    {
        $_GET['handle_status'] = 1;
        $_GET['is_del'] = 0;
        $lists = (new Merchant_spread_applyModel())->getLists($_GET);
        $this->assign('pagebar',$lists['pagebar']);
        $this->assign('lists',$lists['lists']);
        $this->display();
    }

    /**
     * 待审核推广商
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function notAudit()
    {
        $_GET['handle_status'] = 0;
        $_GET['is_del'] = 0;
        $lists = (new Merchant_spread_applyModel())->getLists($_GET);
        $this->assign('pagebar',$lists['pagebar']);
        $this->assign('lists',$lists['lists']);
        $this->display();
    }

    /**
     * 编辑推广商
     * @author: 张涛
     * @date: 2021/01/15
     */
    public function edit()
    {
        $mod = M('Merchant_spread_user');
        if (IS_POST) {
            $uid = I('post.uid', 0, 'intval');
            $commission = I('post.commission', 0);
            $mod->where(['uid' => $uid])->save(['commission' => $commission]);
            $this->frame_submit_tips(1,'编辑成功');
        } else {
            $uid = I('get.uid', 0, 'intval');
            $user = $mod->where(['uid' => $uid])->find();
            $this->assign('user', $user);
            $this->display();
        }
    }

    public function del(){
        $id = I('get.id',0,'intval');
        $applyMod = M('Merchant_spread_apply');
        $thisApply = $applyMod->where(['id'=>$id])->find();
        if ($thisApply) {
            $applyMod->where(['id' => $id])->save(['is_del' => 1]);
            $update = [];
            $thisApply['type'] == 1 && $update['is_vip_spread'] = 0;
            $thisApply['type'] == 2 && $update['is_city_spread'] = 0;
            M('Merchant_spread_user')->where(['uid' => $thisApply['uid']])->save($update);
        }
        $this->success('删除成功');
    }

    /**
     * 申请内容
     * @author: 张涛
     * @date: 2021/01/15
     */
    public function handleApply()
    {
        if (IS_POST) {
            $applyId = I('post.id', 0, 'intval');
            $handleStatus = I('post.handle_status', 0, 'intval');
            if (!in_array($handleStatus, [1, 2])) {
                $this->error('请选择审核状态');
            }
            $applyMod = new Merchant_spread_applyModel();
            $thisApply = $applyMod->where(['id' => $applyId])->find();
            if (empty($thisApply)) {
                $this->error('申请记录不存在');
            }
            $thisOrder = M('Merchant_spread_order')->where(['order_id'=>$thisApply['order_id']])->find();
            $data = [
                'handle_status' => $handleStatus,
                'handle_time' => time(),
                'handle_admin' => $this->system_session['account']
            ];
            $rs = $applyMod->where(['id' => $applyId])->save($data);
            if ($rs !== false && $handleStatus == 1) {
                //审核成功
                $update = [];
                $thisApply['type'] == 1 && $update['is_vip_spread'] = 1;
                $thisApply['type'] == 2 && $update['is_city_spread'] = 1;
                if (M('Merchant_spread_user')->where(['uid' => $thisApply['uid']])->find()) {
                    M('Merchant_spread_user')->where(['uid' => $thisApply['uid']])->save($update);
                } else {
                    $update['uid'] = $thisApply['uid'];
                    M('Merchant_spread_user')->add($update);
                }
                //添加邀请记录
                $spread = D('User_spread')->where(['uid'=>$thisApply['uid']])->find();

                //按照上级身份取佣金比例，优先顺序：城市推广商>vip推广商
                if ($spread && $spread['spread_uid']) {
                    $spreadUserMod = new Merchant_spread_userModel();
                    $spreadUser = $spreadUserMod->where(['uid' => $spread['spread_uid']])->find();
                    $type = 0;
                    if ($spreadUser['is_city_spread']) {
                        $type = 2;
                    } else if ($spreadUser['is_vip_spread']) {
                        $type = 1;
                    }
                    $canSpread = $type ? true : false;
                    if ($canSpread) {
                        $setting = M('Merchant_spread_setting')->where(['type'=>$type])->find();
                        $spreadMoney = getFormatNumber(($thisOrder['price'] * $setting['spread_percent'] / 100));
                        $record = [
                            'uid' => $spread['spread_uid'],
                            'invite_uid' => $thisApply['uid'],
                            'type' => $thisApply['type'],
                            'create_time' => time(),
                            'money' => $spreadMoney
                        ];
                        $rid = M('Merchant_spread_invite_record')->add($record);
                        if ($rid > 0) {
                            $spreadUserMod->where(['uid' => $spread['spread_uid']])->setInc('spread_money', $spreadMoney);
                            D('User')->add_money($spread['spread_uid'],$spreadMoney,L_('推广商佣金'));
                        }
                    }
                }
            } else if ($rs !== false && $handleStatus == 2) {
                //审核失败走退款流程
                $rs = (new Merchant_spread_orderModel())->check_refund($thisOrder['order_id']);
                if ($rs['error_no']) {
                    $applyMod->where(['id' => $applyId])->save(['handle_status' => 0, 'handle_time' => 0, 'handle_admin' => '']);
                    $this->error($rs['error_msg']);
                }
            }
            $this->success('审核成功');
        } else {
            $applyId = I('get.id', 0, 'intval');
            $apply = (new Merchant_spread_applyModel())->getOne($applyId);
            $this->assign('apply', $apply);
            $this->display();
        }
    }

    /**
     * 推广商奖励设置
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function setting()
    {
        $settingMod = D('Merchant_spread_setting');
        if ($_POST) {
            $isClearCache = false;
            if (isset($_POST['open_vip_merchant_spread'])) {
                M('Config')->where(['name' => 'open_vip_merchant_spread'])->save(['value' => $_POST['open_vip_merchant_spread']]);
                $isClearCache = true;
            }
            if (isset($_POST['open_city_merchant_spread'])) {
                M('Config')->where(['name' => 'open_city_merchant_spread'])->save(['value' => $_POST['open_city_merchant_spread']]);
                $isClearCache = true;
            }
            if ($isClearCache) {
                D('Config')->clearDataCacheDir('system_config');
            }

            if (isset($_POST['vip_merchant_spread_price']) && isset($_POST['vip_merchant_spread_percent'])) {
                if ($settingMod->where(['type' => 1])->find()) {
                    $settingMod->where(['type' => 1])->save(['spread_price' => $_POST['vip_merchant_spread_price'], 'spread_percent' => $_POST['vip_merchant_spread_percent']]);
                } else {
                    $settingMod->add(['type' => 1, 'spread_price' => $_POST['vip_merchant_spread_price'], 'spread_percent' => $_POST['vip_merchant_spread_percent']]);
                }
            }

            if (isset($_POST['city_merchant_spread_price']) && isset($_POST['city_merchant_spread_percent'])) {
                if ($settingMod->where(['type' => 2])->find()) {
                    $settingMod->where(['type' => 2])->save(['spread_price' => $_POST['city_merchant_spread_price'], 'spread_percent' => $_POST['city_merchant_spread_percent']]);
                } else {
                    $settingMod->add(['type' => 2, 'spread_price' => $_POST['city_merchant_spread_price'], 'spread_percent' => $_POST['city_merchant_spread_percent']]);
                }
            }
            $this->frame_submit_tips(1, '设置成功');
        } else {
            $config = M('Config')->where(['name' => ['IN', ['open_vip_merchant_spread', 'open_city_merchant_spread']]])->select();
            $configIndex = array_column($config, 'value', 'name');
            $vipSpread = $settingMod->where(['type' => 1])->find();
            $citySpread = $settingMod->where(['type' => 2])->find();
            $this->assign('vipSpread', $vipSpread);
            $this->assign('citySpread', $citySpread);
            $this->assign('configIndex', $configIndex);
            $this->display();
        }
    }

    /**
     * 推广商申请设置
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 填写项设置
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function cueField()
    {
        $type = I('get.type', 0, 'intval');
        if ($type == 0) {
            $this->error('非法请求');
        }
        $setting = D('Merchant_spread_setting')->where(['type' => $type])->find();
        if ($setting) {
            $setting['cue_field'] = D('Merchant_spread_setting_fields')->where(['setting_id' => $setting['id']])->order('sort desc')->select();
        }
        $cueFieldTypes = D('Merchant_spread_setting')->cueFieldsType();
        $this->assign('cueFieldTypes', $cueFieldTypes);
        $this->assign('setting', $setting);
        $this->display();
    }

    /**
     * 添加填写项
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function cueFieldAdd()
    {
        if (IS_POST) {
            $settingMod = D('Merchant_spread_setting');
            $fieldMod = D('Merchant_spread_setting_fields');
            $setting = $settingMod->where(['type' => intval($_POST['spread_type'])])->find();

            $_POST['name'] = trim($_POST['name']);
            if ($fieldMod->where(['setting_id' => $setting['id'], 'name' => $_POST['name']])->find()) {
                $this->error('该填写项已经添加，请勿重复添加！');
            }

            $data['name'] = $_POST['name'];
            $data['type'] = $_POST['type'];
            $data['sort'] = strval($_POST['sort']);
            $data['iswrite'] = $_POST['iswrite'];
            if (!empty($_POST['use_field'])) {
                $data['use_field'] = explode(PHP_EOL, $_POST['use_field']);
                foreach ($data['use_field'] as $ulkey => $ulvalue) {
                    $data['use_field'][$ulkey] = trim($ulvalue);
                }
            }
            $data['use_field'] && $data['use_field'] = serialize($data['use_field']);
            $data['setting_id'] = $setting['id'];
            if ($fieldMod->add($data)) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！请重试~');
            }
        } else {
            $cueFieldTypes = D('Merchant_spread_setting')->cueFieldsType();
            $this->assign('cueFieldTypes', $cueFieldTypes);
            $this->display();
        }
    }

    /**
     * 删除表单填写项
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function cueFieldDel()
    {
        $id = intval($_POST['id']);
        D('Merchant_spread_setting_fields')->where(['id' => $id])->delete();
        $this->success('删除成功');
    }

}