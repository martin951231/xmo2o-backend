<?php

/**
 * 平台客服配置
 */
class PlatAction extends BaseAction
{
    //虚拟用户。store_id=0则为平台
    private $storeId = 0;

    public $belong = 'plat';

    public function kefu()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        if ($page > 0) {
            $lists = M('merchant_store_kefu')->alias('kf')
                ->join(C('DB_PREFIX') . 'user u ON kf.bind_uid = u.uid')
                ->field('kf.*,u.nickname,u.avatar AS bind_user_avatar')
                ->where(array('kf.store_id' => $this->storeId,'kf.belong' => $this->belong))
                ->limit(($page - 1) * $limit, $limit)
                ->select();
            foreach ($lists as &$kefu) {
                if ($kefu['avatar']) {
                    $kefu['avatar'] = replace_file_domain($kefu['avatar']);
                }
                if ($kefu['bind_user_avatar']) {
                    $kefu['bind_user_avatar'] = replace_file_domain($kefu['bind_user_avatar']);
                }
            }
            $count = M('merchant_store_kefu')->where(array('store_id' => $this->storeId, 'belong' => $this->belong))->count();
            layerOk($count, $lists);
        } else {
            if (empty($this->system())) {
                return $this->error('未登录', $this->config['site_url'] . '/admin.php');
            }
            $this->assign('store_id', $this->storeId);
            $this->display('');
        }
    }

    public function delKefu()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id < 1) {
            returnCodeError('请选择一条记录');
        }
        $kefu = M('merchant_store_kefu')->find($id);
        if (!$kefu) {
            returnCodeError('客服不存在');
        }
        M('Merchant_store_kefu')->where(array('id' => $id))->delete();
        M('Merchant_store_kefu_customer')->where(array('kefu_id' => $id))->delete();
        M('Personal_short_phrases_group')->where(array('kefu_id' => $id))->delete();
        D('Im_user')->jDeleterUser(Im_userModel::PREFIX_PLAT . '_' . $kefu['store_id'] . '_' . $id);
        returnCodeOk();
    }

    public function addKefu()
    {
        $post = $_POST;
        if ($post) {
            $storeId = $this->storeId;
            $name = isset($post['kefu_name']) ? trim($post['kefu_name']) : '';
            $welcome = isset($post['welcome']) ? trim($post['welcome']) : '';
            $bindUid = isset($post['bind_uid']) ? intval($post['bind_uid']) : 0;
            $customerUids = isset($post['customer_uids']) && is_array($post['customer_uids']) ? array_unique($post['customer_uids']) : array();
            $kefuMod = M('merchant_store_kefu');
            if (empty($name)) {
                returnCodeError('请填写客服名称');
            } else if ($bindUid < 1) {
                returnCodeError('请先绑定用户');
            } else if ($kefuMod->where(array('store_id' => $storeId, 'bind_uid' => $bindUid))->find()) {
                returnCodeError('该用户已绑定客服，请勿重复绑定');
            } else {
                $kdata = array(
                    'store_id' => $storeId,
                    'belong' => $this->belong,
                    'name' => $name,
                    'first_letter' => $this->_getFirstCharter($name),
                    'welcome' => $welcome ?: '',
                    'bind_uid' => $bindUid,
                    'bind_consumer_count' => count($customerUids),
                    'max_receive' => isset($post['max_receive']) ? $post['max_receive'] : 0,
                    'recommended_goods' => isset($post['recommended_goods']) ? self::switch2int($post['recommended_goods']) : 0,
                    'status' => isset($post['status']) ? self::switch2int($post['status']) : 0,
                    'can_send_coupon' => isset($post['can_send_coupon']) ? self::switch2int($post['can_send_coupon']) : 0,
                );
                $kid = $kefuMod->add($kdata);
                if ($customerUids) {
                    $kcData = array();
                    foreach ($customerUids as $u) {
                        $kcData[] = array(
                            'kefu_id' => $kid,
                            'uid' => $u
                        );
                    }
                    M('merchant_store_kefu_customer')->addAll($kcData);
                }
                $username = Im_userModel::PREFIX_PLAT . '_' . $storeId . '_' . $kid;
                //客服添加成功，需要调用极光注册该用户
                $rs = D('Im_user')->jRegisterUser(Im_userModel::PREFIX_PLAT . '_' . $storeId . '_' . $kid, array('nickname' => $name));
                if ($rs['errorCode'] != 0) {
                    $kefuMod->where(array('id' => $kid))->delete();
                    M('merchant_store_kefu_customer')->where(array('kefu_id' => $kid))->delete();
                    returnCodeError($rs['errorMsg']);
                } else {
                    $kefuMod->where(array('id' => $kid))->save(array('username' => $username));
                    returnCodeOk();
                }
            }
        } else {
            $this->assign('store_id', $this->storeId);
            $this->display();
        }
    }

    public function editKefu()
    {
        $post = $_POST;
        if ($post) {
            $id = isset($post['id']) ? intval($post['id']) : 0;
            $name = isset($post['kefu_name']) ? trim($post['kefu_name']) : '';
            $welcome = isset($post['welcome']) ? trim($post['welcome']) : '';
            $bindUid = isset($post['bind_uid']) ? intval($post['bind_uid']) : 0;
            $customerUids = isset($post['customer_uids']) && is_array($post['customer_uids']) ? array_unique($post['customer_uids']) : array();
            $kefuMod = M('merchant_store_kefu');
            $consumerMod = M('merchant_store_kefu_customer');

            if ($id < 1) {
                returnCodeError('请选择一条记录');
            } else if (empty($name)) {
                returnCodeError('请填写客服名称');
            } else if ($bindUid < 1) {
                returnCodeError('请先绑定用户');
            } else {
                $kefu = $kefuMod->find($id);
                if (!$kefu) {
                    returnCodeError('客服不存在');
                } else if ($kefuMod->where(array('store_id' => $this->storeId, 'id' => array('neq', $id), 'bind_uid' => $bindUid))->find()) {
                    returnCodeError('该用户已绑定客服，请勿重复绑定');
                } else if ($kefu['cur_receive'] > $post['max_receive']) {
                    returnCodeError('客服最大接待数不能小于当前接待数量');
                }
                $kdata = array(
                    'name' => $name,
                    'first_letter' => $this->_getFirstCharter($name),
                    'welcome' => $welcome ?: '',
                    'bind_uid' => $bindUid,
                    'bind_consumer_count' => count($customerUids),
                    'max_receive' => isset($post['max_receive']) ? $post['max_receive'] : 0,
                    'recommended_goods' => isset($post['recommended_goods']) ? self::switch2int($post['recommended_goods']) : 0,
                    'status' => isset($post['status']) ? self::switch2int($post['status']) : 0,
                    'can_send_coupon' => isset($post['can_send_coupon']) ? self::switch2int($post['can_send_coupon']) : 0,
                );
                $kefuMod->where(array('id' => $id))->save($kdata);
                $consumerMod->where(array('kefu_id' => $id))->delete();
                if ($customerUids) {
                    $kcData = array();
                    foreach ($customerUids as $u) {
                        $kcData[] = array(
                            'kefu_id' => $id,
                            'uid' => $u
                        );
                    }
                    $consumerMod->addAll($kcData);
                }
                returnCodeOk();
            }
        } else {
            if (empty($_GET['id']) || $_GET['id'] < 1) {
                exit('请选择一条记录');
            }
            $id = intval($_GET['id']);
            $kefuInfo = M('merchant_store_kefu')
                ->alias('kf')
                ->join(C('DB_PREFIX') . 'user u ON kf.bind_uid = u.uid')
                ->field('kf.*,u.nickname,u.avatar AS bind_user_avatar')
                ->where(array('kf.id' => $id))
                ->find();
            $consumers = M('merchant_store_kefu_customer')
                ->alias('c')
                ->join(C('DB_PREFIX') . 'user AS u ON c.uid = u.uid')
                ->where(array('c.kefu_id' => $id))
                ->field('c.*,u.nickname,u.avatar AS user_avatar')
                ->select();

            $kefuInfo['bind_user_avatar'] = replace_file_domain($kefuInfo['bind_user_avatar']);
            $consumers = array_map(function ($v) {
                $v['user_avatar'] = replace_file_domain($v['user_avatar']);
                return $v;
            }, $consumers);

            $kefuInfo['bind_consumers'] = $consumers;
            $this->assign('kefuInfo', $kefuInfo);
            $this->display();
        }
    }

    private function _getFirstCharter($str)
    {
        loadSystemOrg('words');
        $string = (new words())->wordgetFirstCharter($str);
        return strtoupper(substr($string, 0, 1));
    }

    public function privilege()
    {
        $post = $_POST;
        if ($post) {
            //保存
            $mod = M('store_kefu_params');
            $rs = array('error_code' => 0, 'error_msg' => '');

            $storeId = $this->storeId;
            $data = array(
                'store_id' => $storeId,
                'belong' => $this->belong,
                'customer_unrespon_switch' => isset($post['customer_unrespon_switch']) ? self::switch2int($post['customer_unrespon_switch']) : 0,
                'customer_unrespon_min' => isset($post['customer_unrespon_min']) ? $post['customer_unrespon_min'] : 0,
                'customer_unrespon_tips' => isset($post['customer_unrespon_tips']) ? $post['customer_unrespon_tips'] : '',
                'customer_unreceived_switch' => isset($post['customer_unreceived_switch']) ? self::switch2int($post['customer_unreceived_switch']) : 0,
                'customer_unreceived_min' => isset($post['customer_unreceived_min']) ? $post['customer_unreceived_min'] : 0,
                'customer_unreceived_tips' => isset($post['customer_unreceived_tips']) ? $post['customer_unreceived_tips'] : '',
                'saturation_assign_kefu' => isset($post['saturation_assign_kefu']) ? self::switch2int($post['saturation_assign_kefu']) : 0,
                'priority_assign_kefu' => isset($post['priority_assign_kefu']) ? self::switch2int($post['priority_assign_kefu']) : 0,
                'appoint_assign_kefu' => isset($post['appoint_assign_kefu']) ? self::switch2int($post['appoint_assign_kefu']) : 0,
                'welcome' => isset($post['welcome']) ? $post['welcome'] : '',
                'off_duty_notice' => isset($post['off_duty_notice']) ? $post['off_duty_notice'] : '',
            );
            if ($mod->where(array('store_id' => $storeId,'belong' => $this->belong))->find()) {
                $mod->where(array('store_id' => $storeId,'belong' => $this->belong))->save($data);
            } else {
                $mod->add($data);
            }
            json($rs);
        } else {
            $storeId = $this->storeId;
            $kefuParams = M('store_kefu_params')->where(array('store_id' => $storeId,'belong'=>$this->belong))->find();
            $this->assign('kefuParams', $kefuParams);
            $this->assign('store_id', $storeId);
            $this->display();
        }
    }

    static function switch2int($val)
    {
        return $val == 'on' ? 1 : $val;
    }

    public function shortPhrases()
    {
        $storeId = $this->storeId;
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;

        //区分团队快捷语（type=1）和个人快捷语(type=2)
        if ($type == 1) {
            $groups = D('Short_phrases_group')->where(array('store_id' => $storeId,'belong'=>$this->belong))->order('sort DESC')->select();
            $tpl = '';
        } else if ($type == 2) {
            $groups = D('Personal_short_phrases_group')->alias('g')
                ->join(C('DB_PREFIX') . 'merchant_store_kefu AS k ON g.kefu_id = k.id')
                ->where(array('k.store_id' => $storeId,'k.belong'=>$this->belong))
                ->field('g.*')
                ->order('g.sort DESC')->select();
            $tpl = 'shortPhrasesForPersonal';
        } else {
            return $this->error('非法参数');
        }
        $this->assign('groups', $groups);
        $this->assign('type', $type);
        $this->assign('store_id', $storeId);
        $this->display($tpl);
    }

    public function addPhrasesGroup()
    {
        $post = $_POST;
        if ($post) {
            if (empty(trim($post['name']))) {
                returnCodeError('分组名称不能为空');
            }
            if (!isset($post['type']) || !D('Short_phrases_group')->checkType($post['type'])) {
                returnCodeError('快捷短语分类参数有误');
            }
            $data = array(
                'store_id' => $this->storeId,
                'belong'=>$this->belong,
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'name' => trim($post['name']),
                'type' => $post['type']
            );

            if (D('Short_phrases_group')->add($data)) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }

        } else {
            $storeId = $this->storeId;
            $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
            $this->assign('type', $type);
            $this->assign('store_id', $storeId);
            $this->display();
        }
    }

    public function editPhrasesGroup()
    {
        $post = $_POST;
        if ($post) {
            if (empty(trim($post['name']))) {
                returnCodeError('分组名称不能为空');
            }
            if (empty($post['id']) || $post['id'] < 1) {
                returnCodeError('请选择一条记录');
            }
            $data = array(
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'name' => trim($post['name']),
            );
            $type = isset($post['type']) ? intval($post['type']) : 1;
            if ($type == 1) {
                $rs = D('Short_phrases_group')->where(array('id' => $post['id']))->save($data);
            } else if ($type == 2) {
                $rs = D('Personal_short_phrases_group')->where(array('id' => $post['id']))->save($data);
            }
            if ($rs !== false) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
            if ($id < 1) {
                return $this->error('请选择一条记录');
            }
            if ($type == 1) {
                $group = D('Short_phrases_group')->find($id);
            } else if ($type == 2) {
                $group = D('Personal_short_phrases_group')->find($id);
            }
            if (!$group) {
                return $this->error('记录不存在');
            }
            $this->assign('group', $group);
            $this->assign('type', $type);
            $this->display();
        }
    }

    public function delPhrasesGroup()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
        if ($id < 1) {
            returnCodeError('请选择一条记录');
        } else {
            if ($type == 1) {
                D('Short_phrases_group')->where(array('id' => $id))->delete();
                D('Short_phrases')->where(array('group_id' => $id))->delete();
            } else if ($type == 2) {
                D('Personal_short_phrases_group')->where(array('id' => $id))->delete();
                D('Personal_short_phrases')->where(array('group_id' => $id))->delete();
            }
            returnCodeOk();
        }
    }


    public function ajaxGetPhrases()
    {
        $groupId = intval($_GET['group_id']);
        $type = intval($_GET['type']);
        if ($groupId < 1) {
            layerOk(0, array());
        } else {
            if ($type == 1) {
                $phrases = D('Short_phrases')->where(array('group_id' => $groupId))->order('sort DESC')->select();
            } else if ($type == 2) {
                $phrases = D('Personal_short_phrases')->alias('p')
                    ->join(C('DB_PREFIX') . 'personal_short_phrases_group AS g ON p.group_id = g.id')
                    ->join(C('DB_PREFIX') . 'merchant_store_kefu AS k ON k.id = g.kefu_id')
                    ->field('p.*,k.name AS kefu_name')
                    ->where(array('p.group_id' => $groupId))
                    ->order('p.sort DESC')->select();
            } else {
                returnCodeError('type参数有误');
            }
            layerOk(count($phrases), $phrases);
        }
    }

    public function addPhrase()
    {
        $post = $_POST;
        if ($post) {
            if (empty(trim($post['context']))) {
                returnCodeError('内容不能为空');
            }
            if (empty($post['group_id']) || $post['group_id'] < 1) {
                returnCodeError('缺少分组参数');
            }
            if (!D('Short_phrases_group')->where(array('id' => $post['group_id']))->find()) {
                returnCodeError('分组不存在');
            }

            $title = isset($post['title']) ? trim($post['title']) : '';
            if ($title) {
                $autoReplyCount = D('Short_phrases')
                    ->alias('p')->join(C('DB_PREFIX' . 'short_phrases_group AS g ON g.id = p.group_id'))
                    ->where(['p.title' => ['NEQ', ''], 'g.belong' => $this->belong, 'g.store_id' => $this->storeId])
                    ->count();
                if ($autoReplyCount > self::MAX_AUTO_REPLY_COUNT) {
                    returnCodeError('最多可设置' . self::MAX_AUTO_REPLY_COUNT . '条自动回复');
                }
            }

            $data = array(
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'context' => trim($post['context']),
                'title' => $title,
                'group_id' => $post['group_id']
            );
            if (D('Short_phrases')->add($data)) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }
        } else {
            $groupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
            if ($groupId < 1) {
                return $this->error('请选择分组');
            }
            if (!D('Short_phrases_group')->find($groupId)) {
                return $this->error('分组不存在');
            }
            $this->assign('group_id', $groupId);
            $this->display();
        }
    }

    public function editPhrase()
    {
        $post = $_POST;
        if ($post) {
            if (empty(trim($post['context']))) {
                returnCodeError('内容不能为空');
            }
            if (empty($post['id']) || $post['id'] < 1) {
                returnCodeError('请选择一条记录');
            }
            $type = isset($post['type']) ? intval($post['type']) : 1;
            $title = isset($post['title']) ? trim($post['title']) : '';
            $data = array(
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'context' => trim($post['context']),
                'title' => $title
            );

            if ($type == 1) {
                $rs = D('Short_phrases')->where(array('id' => $post['id']))->save($data);
            } else if ($type == 2) {
                $rs = D('Personal_short_phrases')->where(array('id' => $post['id']))->save($data);
            }
            if ($rs !== false) {
                returnCodeOk();
            } else {
                returnCodeError('编辑失败');
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
            if ($id < 1) {
                return $this->error('请选择一条记录');
            }
            if ($type == 1) {
                $phrase = D('Short_phrases')->find($id);
            } else if ($type == 2) {
                $phrase = D('Personal_short_phrases')->find($id);
            }
            if (empty($phrase)) {
                return $this->error('快捷短信记录不存在');
            }
            $this->assign('phrase', $phrase);
            $this->assign('type', $type);
            $this->display();
        }
    }


    public function delPhrase()
    {
        $ids = isset($_POST['ids']) ? $_POST['ids'] : array();
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
        if (empty($ids) || !is_array($ids)) {
            returnCodeError('请至少选择一条删除记录');
        } else {
            if ($type == 1) {
                $rs = D('Short_phrases')->where(array('id' => array('IN', $ids)))->delete();
            } else if ($type == 2) {
                $rs = D('Personal_short_phrases')->where(array('id' => array('IN', $ids)))->delete();
            }
            if ($rs) {
                returnCodeOk('删除成功');
            } else {
                returnCodeError('删除失败');
            }
        }
    }

    public function selectPhrasesGroup()
    {
        $post = $_POST;
        if ($post) {
            $ids = isset($post['id']) && is_array($post['id']) ? array_unique(array_filter($post['id'])) : array();
            $groupId = isset($post['group_id']) ? intval($post['group_id']) : 0;
            if (empty($ids)) {
                returnCodeError('请选择修改分组的快捷短语记录');
            }
            if (!D('Short_phrases_group')->find($groupId)) {
                returnCodeError('分组不存在');
            }

            if (D('Short_phrases')->where(array('id' => array('IN', $ids)))->save(array('group_id' => $groupId))) {
                returnCodeOk();
            } else {
                returnCodeError('已属于当前组，不可重复修改');
            }
        } else {
            $ids = isset($_GET['id_str']) ? array_unique(array_filter(explode('-', trim($_GET['id_str'])))) : array();
            $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
            $storeId = $this->storeId;
            if (empty($ids)) {
                return $this->error('请选择一条记录');
            }
            if (!D('Short_phrases_group')->checkType($type)) {
                return $this->error('类型参数有误');
            }
            $groups = D('Short_phrases_group')->where(array('type' => $type, 'store_id' => $storeId,'belong'=>$this->belong))->field('id,name')->select();
            $this->assign('ids', $ids);
            $this->assign('groups', $groups);
            $this->display();
        }
    }

    public function menu()
    {
        $storeId = $this->storeId;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $lists = D('Kefu_menu')
                ->where(array('store_id' => $storeId,'belong'=>$this->belong))
                ->order('sort DESC')
                ->select();
            foreach ($lists as &$l) {
                $l['type_desc'] = D('Kefu_menu')->getTypeDesc($l['type'],false,true);
            }
            layerOk(count($lists), $lists);
        } else {
            $this->assign('store_id', $storeId);
            $this->display();
        }
    }

    public function addMenu()
    {
        $post = $_POST;
        if ($post) {
            $data = array();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['sort'] = isset($post['sort']) ? intval($post['sort']) : 0;
            if (empty(trim($post['name']))) {
                returnCodeError('菜单名称不能为空');
            }
            $data['name'] = trim($post['name']);
            $data['store_id'] = $this->storeId;
            $data['belong'] = $this->belong;
            if (!isset($post['type']) || !D('Kefu_menu')->checkType($post['type'])) {
                returnCodeError('菜单类型有误');
            }
            $data['type'] = $post['type'];
            if ($data['type'] == Kefu_menuModel::LINK) {
                if (empty($post['link'])) {
                    returnCodeError('请填写自定义链接');
                } else {
                    $data['link'] = $post['link'];
                }
            } else {
                $data['link'] = $post['link'];
            }
            if (in_array($data['type'], array(Kefu_menuModel::COUPON, Kefu_menuModel::SYSTEM_COUPON))) {
                $coupons = isset($post['coupons']) && is_array($post['coupons']) ? $post['coupons'] : array();
                $data['coupons'] = json_encode($coupons, JSON_UNESCAPED_UNICODE);
            } else {
                $data['coupons'] = json_encode([]);
            }
            if (D('Kefu_menu')->add($data)) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }

        } else {
            $storeId = $this->storeId;
            $menuTypes = D('Kefu_menu')->getTypeDesc('', true, true);
            $this->assign('menuTypes', $menuTypes);
            $this->assign('store_id', $storeId);
            $this->display();
        }
    }

    public function editMenu()
    {
        $post = $_POST;
        if ($post) {
            $data = array();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['sort'] = isset($post['sort']) ? intval($post['sort']) : 0;
            if (empty($post['id']) || $post['id'] < 1) {
                returnCodeError('请选择一条记录');
            }
            if (!D('Kefu_menu')->find($post['id'])) {
                returnCodeError('菜单不存在');
            }
            if (empty(trim($post['name']))) {
                returnCodeError('菜单名称不能为空');
            }
            $data['name'] = trim($post['name']);
            if (!isset($post['type']) || !D('Kefu_menu')->checkType($post['type'])) {
                returnCodeError('菜单类型有误');
            }
            $data['type'] = $post['type'];
            if ($data['type'] == Kefu_menuModel::LINK) {
                if (empty($post['link'])) {
                    returnCodeError('请填写自定义链接');
                } else {
                    $data['link'] = $post['link'];
                }
            } else {
                $data['link'] = '';
            }
            if (in_array($data['type'], array(Kefu_menuModel::COUPON, Kefu_menuModel::SYSTEM_COUPON))) {
                $coupons = isset($post['coupons']) && is_array($post['coupons']) ? $post['coupons'] : array();
                $data['coupons'] = json_encode($coupons, JSON_UNESCAPED_UNICODE);
            } else {
                $data['coupons'] = json_encode([]);
            }
            if (D('Kefu_menu')->where(array('id' => $post['id']))->save($data)) {
                returnCodeOk();
            } else {
                returnCodeError('编辑失败');
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id < 1) {
                return $this->error('缺少店铺参数');
            }
            $menu = D('Kefu_menu')->find($id);
            if($menu['coupons']){
                $menu['coupon_encode'] = $menu['coupons'];
                $menu['coupons'] = json_decode($menu['coupons'], true);
            }else{
                $menu['coupon_encode'] = '[]';
                $menu['coupons'] = json_decode(array(), true);
            }
            $menuTypes = D('Kefu_menu')->getTypeDesc('', true, true);
            $this->assign('menuTypes', $menuTypes);
            $this->assign('menu', $menu);
            $this->display();
        }
    }

    public function delMenu()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id < 1) {
            returnCodeError('请选择一条删除记录');
        } else {
            $rs = D('Kefu_menu')->where(array('id' => $id))->delete();
            if ($rs) {
                returnCodeOk('删除成功');
            } else {
                returnCodeError('删除失败');
            }
        }
    }

    /**
     * 客服会话列表
     */
    public function merchantChat()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $limit = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : 10;
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
            $status = $_GET['status'] ?? -1;
            $where = ['conv.relation' => ['IN',['store2user','store2wechat']]];
            if ($keyword) {
                if ($type == 'all') {
                    $where['_complex'] = [
                        'm.name' => ['like', '%' . $keyword . '%'],
                        's.name' => ['like', '%' . $keyword . '%'],
                        'kf.name' => ['like', '%' . $keyword . '%'],
                        'im_user.nickname' => ['like', '%' . $keyword . '%'],
                        '_logic' => 'OR'
                    ];
                } else if ($type == 'merchant_name') {
                    $where['m.name'] = ['like', '%' . $keyword . '%'];
                } else if ($type == 'store_name') {
                    $where['s.name'] = ['like', '%' . $keyword . '%'];
                } else if ($type == 'kf_name') {
                    $where['kf.name'] = ['like', '%' . $keyword . '%'];
                } else if ($type == 'customer_name') {
                    $where['im_user.nickname'] = ['like', '%' . $keyword . '%'];
                }
            }
            if($status >= 0){
                $where['conv.status'] = intval($status);
            }
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_kefu AS kf ON kf.username = conv.from_user';
            $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = kf.store_id';
            $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = s.mer_id';
            $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'im_user AS im_user ON im_user.username = conv.to_user';
            $count = M('im_conversation')->alias('conv')->join($join)->where($where)->count();
            if ($count > 0) {
                $lists = M('im_conversation')->alias('conv')
                    ->join($join)
                    ->where($where)
                    ->field('m.name AS merchant_name,s.name AS store_name,kf.name AS kf_name,conv.from_user,conv.to_user,conv.id AS chat_id,from_unixtime(conv.last_msg_time,"%Y-%m-%d %H:%m") AS last_time,im_user.nickname AS customer_name,im_user.avatar AS customer_avatar,conv.status')
                    ->order('conv.last_msg_time DESC')
                    ->limit(($page - 1) * $limit . ',' . $limit)
                    ->select();
            } else {
                $lists = [];
            }
            foreach ($lists as $k => $v) {
                $lists[$k]['customer_avatar'] = replace_file_domain($v['customer_avatar']);
                $lists[$k]['status'] = $v['status'] ==1 ?'对话中':'已断开';
            }
            layerOk($count, $lists);
        } else {
            $this->display();
        }
    }

    public function systemChat()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $limit = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : 10;
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
            $status = $_GET['status']??-1;
            $where = ['conv.relation' => ['IN',['plat2user','plat2wechat']]];
            $platNickname = M('Config')->where(array('name' => 'site_name'))->getField('value');
            if ($keyword) {
                if ($type == 'all') {
                    $where['_complex'] = [
                        'kf.name' => ['like', '%' . $keyword . '%'],
                        'im_user.nickname' => ['like', '%' . $keyword . '%'],
                        '_logic' => 'OR'
                    ];
                } else if ($type == 'kf_name') {
                    $where['kf.name'] = ['like', '%' . $keyword . '%'];
                } else if ($type == 'customer_name') {
                    $where['im_user.nickname'] = ['like', '%' . $keyword . '%'];
                }
            }
            if($status >= 0){
                $where['conv.status'] = intval($status);
            }
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_kefu AS kf ON kf.username = conv.from_user';
            $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'im_user AS im_user ON im_user.username = conv.to_user';
            $count = M('im_conversation')->alias('conv')->join($join)->where($where)->count();
            if ($count > 0) {
                $lists = M('im_conversation')->alias('conv')
                    ->join($join)
                    ->where($where)
                    ->field('"' . $platNickname . '" AS merchant_name,"' . $platNickname . '" AS store_name,kf.name AS kf_name,conv.from_user,conv.to_user,conv.id AS chat_id,from_unixtime(IF(conv.last_msg_time,conv.last_msg_time,conv.create_time),"%Y-%m-%d %H:%i") AS last_time,im_user.nickname AS customer_name,im_user.avatar AS customer_avatar,conv.status')
                    ->order('conv.last_msg_time DESC')
                    ->limit(($page - 1) * $limit . ',' . $limit)
                    ->select();
            } else {
                $lists = [];
            }
            foreach ($lists as $k => $v) {
                $lists[$k]['customer_avatar'] = replace_file_domain($v['customer_avatar']);
                $lists[$k]['status'] = $v['status'] ==1 ?'对话中':'已断开';
            }
            layerOk($count, $lists);
        } else {
            $this->display();
        }
    }

    /**
     * 查看聊天记录
     */
    public function chatDetail()
    {
        $conversationId = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
        if ($conversationId < 1) {
            return $this->error('请选择一个会话');
        }
        if ($conversationId) {
            $chat = M('im_conversation')->find($conversationId);
            if (empty($chat)) {
                return $this->error('会话不存在');
            }
            $fromUser = $chat['from_user'];
            $toUser = $chat['to_user'];
            $fromUserInfo = M('im_user')->where(['username' => $fromUser])->find();
            $toUserInfo = M('im_user')->where(['username' => $toUser])->find();

            $index = Im_chat_recordModel::buildChatIndexKey($fromUser, $toUser);
            $where = sprintf("`index_key` = '%s' AND (`show_rule` = 0 OR (`from_id` = '%s' AND `show_rule` = 1) OR (`target_id` = '%s' AND `show_rule` = 2)) AND `msg_type` IN ('text','image')", $index, $fromUser, $fromUser);
            $messages = D('Im_chat_record')
                ->where($where)
                ->field('id,msg_id,from_id,target_id,msg_type,msg_body,create_time')
                ->order('id ASC')
                ->limit(1000)
                ->select();
            $fromUUID = Im_userModel::getUUIDByUsername($fromUser);
            foreach ($messages as &$message) {
                $message['msg_body'] = json_decode($message['msg_body'], true);
                $message['date'] = date('m/d H:i:s',$message['create_time']);
                //判断登录用户
                if ($fromUUID == Im_userModel::getUUIDByUsername($message['from_id'])) {
                    $message['is_from_user'] = 1;
                    $message['from_nickname'] = $fromUserInfo['nickname'];
                    $message['from_avatar'] = replace_file_domain($fromUserInfo['avatar']);
                    if (empty($message['from_avatar'])) {
                        $message['from_avatar'] = '/static/images/severImg.png';
                    }
                } else {
                    $message['is_from_user'] = 0;
                    $message['from_nickname'] = $toUserInfo['nickname'];
                    $message['from_avatar'] = replace_file_domain($toUserInfo['avatar']);
                }
            }
            //dump($messages);exit;
            $this->assign('messages', $messages);
        }
        $this->display();
    }

    public function repairKefu()
    {
        $imUserMod = D('Im_user');
        $imUsers = $imUserMod->select();
        require_once APP_PATH . 'Lib/ORG/JMessage/autoload.php';
        $configData = PD('Config_data')->get_jg_im_config();
        $appkey = isset($configData['im_appkey']) ? $configData['im_appkey'] : '';
        $masterKey = isset($configData['im_masterkey']) ? $configData['im_masterkey'] : '';
        $jm = new \JMessage\JMessage($appkey, $masterKey, array('disable_ssl' => true));
        $user = new \JMessage\IM\User($jm);

        foreach ($imUsers as $i) {
            $rs = $user->show($i['username']);
            if ($rs['http_code'] != 200) {
                $imUserMod->jRegisterUser($i['username'], [], true);
            }
        }
        $this->success('操作成功');
    }

    public function imStatus()
    {
        if ("WINNT" != PHP_OS) {
            if(!function_exists("shell_exec")){
                exit("shell_exec函数不存在");
            }
            $imPath = PLUGIN_PATH . '../../v20/tools/im';
            $phpBinPath = PHP_BINDIR;
            if ($_POST) {
                $type = $_POST['type'] ?? '';
                //start：启动   stop:停止   reload:重启
                if ($type == 'start') {
                    $cmd = "cd {$imPath} && {$phpBinPath}/php start.php start -d";
                } else if ($type == 'stop') {
                    $cmd = "cd {$imPath} && {$phpBinPath}/php start.php stop";
                } else if ($type == 'restart') {
                    $cmd = "cd {$imPath} && {$phpBinPath}/php start.php restart -d";
                }
                if ($cmd && shell_exec($cmd)) {
                    $this->success('操作成功');
                } else {
                    $this->error('操作失败');
                }
            } else {
                $cmd = "cd {$imPath} && {$phpBinPath}/php start.php status";
                $res = shell_exec($cmd);
                $isRunning = false;
                if (preg_match('/start(.*?)run(.*?hours)/', $res, $matches)) {
                    $isRunning = true;
                }

                $this->assign('isRunning', $isRunning);
                $this->assign('data', $isRunning ? ['beigin_time' => $matches[1], 'up_days' => $matches[2]] : []);
                $this->display();
            }
        } else {
            exit("Windows系统不支持查看运行状态");
        }
    }
}
