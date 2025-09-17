<?php

/**
 * 小区客服
 */
class VillageAction extends BaseAction
{
    public $belong = 'village';

    protected function _initialize()
    {
        parent::_initialize();
        if (!$this->village()) {
            return $this->error('未登录', $this->config['site_url'] . '/shequ.php?g=House&c=Login&a=new_login');
        }
    }

    public function kefu()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : $this->pageSize;
        $villageId = $this->village('village_id');
        if ($page > 0) {
            $lists = M('merchant_store_kefu')->alias('kf')
                ->join(C('DB_PREFIX') . 'house_worker AS w ON kf.bind_uid = w.wid')
                ->field('kf.*,w.name AS nickname,w.avatar AS bind_user_avatar')
                ->where(array('kf.store_id' => $villageId, 'kf.belong' => $this->belong))
                ->limit(($page - 1) * $limit . ',' . $limit)
                ->select();
            foreach ($lists as &$kefu) {
                if ($kefu['avatar']) {
                    $kefu['avatar'] = replace_file_domain($kefu['avatar']);
                } else {
                    $kefu['avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
                }
                if ($kefu['bind_user_avatar']) {
                    $kefu['bind_user_avatar'] = replace_file_domain($kefu['bind_user_avatar']);
                } else {
                    $kefu['bind_user_avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
                }
            }
            $count = M('merchant_store_kefu')->where(array('store_id' => $villageId, 'belong' => $this->belong))->count();
            layerOk($count, $lists);
        } else {
            $this->assign('village_id', $villageId);
            $this->display();
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
        D('Im_user')->jDeleterUser(Im_userModel::PREFIX_VILLAGE . '_' . $kefu['store_id'] . '_' . $id);
        returnCodeOk();
    }

    public function addKefu()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            $name = isset($post['kefu_name']) ? trim($post['kefu_name']) : '';
            $welcome = isset($post['welcome']) ? trim($post['welcome']) : '';
            $bindUid = isset($post['bind_uid']) ? intval($post['bind_uid']) : 0;
            $customerUids = isset($post['customer_uids']) && is_array($post['customer_uids']) ? array_unique($post['customer_uids']) : array();
            $kefuMod = M('merchant_store_kefu');
            if (empty($name)) {
                returnCodeError('请填写客服名称');
            } else if ($bindUid < 1) {
                returnCodeError('请先绑定用户');
            } else if ($kefuMod->where(array('store_id' => $villageId, 'bind_uid' => $bindUid))->find()) {
                returnCodeError('该用户已绑定客服，请勿重复绑定');
            } else {
                $kdata = array(
                    'store_id' => $villageId,
                    'belong' => $this->belong,
                    'name' => $name,
                    'first_letter' => getFirstCharter($name),
                    'welcome' => $welcome ?: '',
                    'bind_uid' => $bindUid,
                    'bind_consumer_count' => count($customerUids),
                    'max_receive' => isset($post['max_receive']) ? $post['max_receive'] : 0,
                    'recommended_goods' => isset($post['recommended_goods']) ? PlatAction::switch2int($post['recommended_goods']) : 0,
                    'can_send_coupon' => isset($post['can_send_coupon']) ? PlatAction::switch2int($post['can_send_coupon']) : 0,
                );
                $kid = $kefuMod->add($kdata);
                if ($customerUids) {
                    $kcData = array();
                    foreach ($customerUids as $u) {
                        if(M('merchant_store_kefu_customer')->where(array(
                            'uid' => $u
                        ))->find()){
                            continue;
                        }
                        $kcData[] = array(
                            'kefu_id' => $kid,
                            'uid' => $u
                        );
                    }
                    M('merchant_store_kefu_customer')->addAll($kcData);
                }
                $username = Im_userModel::PREFIX_VILLAGE . '_' . $villageId . '_' . $kid;
                //客服添加成功，需要调用极光注册该用户
                $rs = D('Im_user')->jRegisterUser($username, array('kefu_name' => $name,'kefu_source'=>Im_userModel::PREFIX_VILLAGE ));
                if ($rs['errorCode'] != 0) {
                    $kefuMod->where(['id' => $kid])->delete();
                    M('merchant_store_kefu_customer')->where(['kefu_id' => $kid])->delete();
                    returnCodeError($rs['errorMsg']);
                } else {
                    $kefuMod->where(array('id' => $kid))->save(['username' => $username]);
                    returnCodeOk();
                }
            }
        } else {
            $this->assign('village_id', $villageId);
            $this->display();
        }
    }

    public function editKefu()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
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
                } else if ($kefuMod->where(array('store_id' => $villageId, 'bind_uid' => $bindUid, 'id' => array('neq', $id)))->find()) {
                    returnCodeError('该用户已绑定客服，请勿重复绑定');
                }
                $kdata = array(
                    'name' => $name,
                    'first_letter' => getFirstCharter($name),
                    'welcome' => $welcome ?: '',
                    'bind_uid' => $bindUid,
                    'bind_consumer_count' => count($customerUids),
                    'max_receive' => isset($post['max_receive']) ? $post['max_receive'] : 0,
                    'recommended_goods' => isset($post['recommended_goods']) ? PlatAction::switch2int($post['recommended_goods']) : 0,
                    'can_send_coupon' => isset($post['can_send_coupon']) ? PlatAction::switch2int($post['can_send_coupon']) : 0,
                );
                $kefuMod->where(array('id' => $id))->save($kdata);
                $consumerMod->where(array('kefu_id' => $id))->delete();
                if ($customerUids) {
                    $kcData = array();
                    foreach ($customerUids as $u) {
                        if(M('merchant_store_kefu_customer')->where(array(
                            'uid' => $u
                        ))->find()){
                            continue;
                        }
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
            $kefuInfo = M('merchant_store_kefu')->alias('kf')
                ->join(C('DB_PREFIX') . 'house_worker AS w ON kf.bind_uid = w.wid')
                ->field('kf.*,w.name AS nickname,w.avatar AS bind_user_avatar')
                ->where(array('kf.store_id' => $villageId, 'kf.belong' => $this->belong,'kf.id' => $id))
                ->find();
            $kefuInfo && !$kefuInfo['avatar'] && $kefuInfo['avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
            $kefuInfo && !$kefuInfo['bind_user_avatar'] && $kefuInfo['bind_user_avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
            $consumers = M('merchant_store_kefu_customer')
                ->alias('c')
                ->join(C('DB_PREFIX') . 'house_village_user_bind AS b ON c.uid = b.pigcms_id')
                ->join(C('DB_PREFIX') . 'user AS u ON u.uid = b.uid')
                ->where(array('c.kefu_id' => $id))
                ->field('c.*,b.name AS nickname,u.avatar AS user_avatar')
                ->select();
            foreach ($consumers as &$kefu1) {
                if ($kefu1['avatar']) {
                    $kefu1['avatar'] = replace_file_domain($kefu1['avatar']);
                } else {
                    $kefu1['avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
                }
                if ($kefu1['bind_user_avatar']) {
                    $kefu1['bind_user_avatar'] = replace_file_domain($kefu1['bind_user_avatar']);
                } else {
                    $kefu1['bind_user_avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
                }
            }
            $kefuInfo['bind_consumers'] = $consumers;
            $this->assign('kefuInfo', $kefuInfo);
            $this->assign('village_id', $villageId);
            $this->display();
        }
    }

    public function privilege()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            //保存
            $mod = M('store_kefu_params');
            $rs = array('error_code' => 0, 'error_msg' => '');
            $data = array(
                'store_id' => $villageId,
                'belong' => $this->belong,
                'customer_unrespon_switch' => isset($post['customer_unrespon_switch']) ? PlatAction::switch2int($post['customer_unrespon_switch']) : 0,
                'customer_unrespon_min' => isset($post['customer_unrespon_min']) ? $post['customer_unrespon_min'] : 0,
                'customer_unrespon_tips' => isset($post['customer_unrespon_tips']) ? $post['customer_unrespon_tips'] : '',
                'customer_unreceived_switch' => isset($post['customer_unreceived_switch']) ? PlatAction::switch2int($post['customer_unreceived_switch']) : 0,
                'customer_unreceived_min' => isset($post['customer_unreceived_min']) ? $post['customer_unreceived_min'] : 0,
                'customer_unreceived_tips' => isset($post['customer_unreceived_tips']) ? $post['customer_unreceived_tips'] : '',
                'saturation_assign_kefu' => isset($post['saturation_assign_kefu']) ? PlatAction::switch2int($post['saturation_assign_kefu']) : 0,
                'priority_assign_kefu' => isset($post['priority_assign_kefu']) ? PlatAction::switch2int($post['priority_assign_kefu']) : 0,
                'appoint_assign_kefu' => isset($post['appoint_assign_kefu']) ? PlatAction::switch2int($post['appoint_assign_kefu']) : 0,
                'welcome' => isset($post['welcome']) ? $post['welcome'] : '',
                'off_duty_notice' => isset($post['off_duty_notice']) ? $post['off_duty_notice'] : '',
            );

            if ($mod->where(array('store_id' => $villageId, 'belong' => $this->belong))->find()) {
                $mod->where(array('store_id' => $villageId, 'belong' => $this->belong))->save($data);
            } else {
                $mod->add($data);
            }
            json($rs);
        } else {
            $kefuParams = M('store_kefu_params')->where(array('store_id' => $villageId,'belong'=>$this->belong))->find();
            $this->assign('kefuParams', $kefuParams);
            $this->assign('village_id', $villageId);
            $this->display();
        }
    }

    public function menu()
    {
        $villageId = $this->village('village_id');
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $lists = D('Kefu_menu')
                ->where(array('store_id' => $villageId,'belong'=>$this->belong))
                ->order('sort DESC')
                ->select();
            foreach ($lists as &$l) {
                $l['type_desc'] = D('Kefu_menu')->getTypeDesc($l['type'],false,true);
            }
            layerOk(count($lists), $lists);
        } else {
            $this->assign('village_id', $villageId);
            $this->display();
        }
    }

    public function addMenu()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            $data = array();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['sort'] = isset($post['sort']) ? intval($post['sort']) : 0;
            if (empty(trim($post['name']))) {
                returnCodeError('菜单名称不能为空');
            }
            $data['name'] = trim($post['name']);
            $data['store_id'] = $villageId;
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
            if (D('Kefu_menu')->add($data)) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }
        } else {
            $menuTypes = D('Kefu_menu')->getTypeDesc('', true, false,true);
            $this->assign('menuTypes', $menuTypes);
            $this->display();
        }
    }

    public function editMenu()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
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

    public function shortPhrases()
    {
        $villageId = $this->village('village_id');
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;

        //区分团队快捷语（type=1）和个人快捷语(type=2)
        if ($type == 1) {
            $groups = D('Short_phrases_group')->where(array('store_id' => $villageId,'belong'=>$this->belong))->order('sort DESC')->select();
            $tpl = '';
        } else if ($type == 2) {
            $groups = D('Personal_short_phrases_group')->alias('g')
                ->join(C('DB_PREFIX') . 'merchant_store_kefu AS k ON g.kefu_id = k.id')
                ->where(array('k.store_id' => $villageId,'k.belong'=>$this->belong))
                ->field('g.*')
                ->order('g.sort DESC')->select();
            $tpl = 'shortPhrasesForPersonal';
        } else {
            return $this->error('非法参数');
        }
        $this->assign('groups', $groups);
        $this->assign('type', $type);
        $this->assign('village_id', $villageId);
        $this->display($tpl);
    }

    /**
     * 自动回复分组
     * @author lijie
     * @date_time 2021/10/18
     */
    public function autoPhrases()
    {
        $villageId = $this->village('village_id');
        $groups = D('HouseAutoPhrasesGroup')->where(array('village_id' => $villageId))->order('sort DESC')->select();
        $this->assign('groups', $groups);
        $this->assign('village_id', $villageId);
        $this->display();
    }

    /**
     * 添加自动回复分组
     * @author lijie
     * @date_time 2021/10/18
     */
    public function addAuthPhrasesGroup()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            if (empty(trim($post['name']))) {
                returnCodeError('分组名称不能为空');
            }
            $data = array(
                'village_id' => $villageId,
                'belong'=>$this->belong,
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'name' => trim($post['name']),
                'create_time'=>time()
            );

            if (D('HouseAutoPhrasesGroup')->add($data)) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }

        } else {
            $this->display();
        }
    }

    /**
     * 编辑自动回复分组
     * @author lijie
     * @date_time 2021/10/18
     */
    public function editAutoPhrasesGroup()
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
            $rs = D('HouseAutoPhrasesGroup')->where(array('id' => $post['id']))->save($data);
            if ($rs !== false) {
                returnCodeOk();
            } else {
                returnCodeError('新增失败');
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id < 1) {
                return $this->error('请选择一条记录');
            }
            $group = D('HouseAutoPhrasesGroup')->find($id);
            if (!$group) {
                return $this->error('记录不存在');
            }
            $this->assign('group', $group);
            $this->display();
        }
    }

    /**
     * 编辑自动回复分组
     * @author lijie
     * @date_time 2021/10/18
     */
    public function delAutoPhrasesGroup()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id < 1) {
            returnCodeError('请选择一条记录');
        } else {
            D('HouseAutoPhrasesGroup')->where(array('id' => $id))->delete();
            returnCodeOk();
        }
    }

    /**
     * 新增图文关键字回复
     * @author lijie
     * @date_time 2021/10/18
     */
    public function AddGraphicKeyword()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            if (empty(trim($post['key']))) {
                //returnCodeError('关键词不能为空');
                $this->error('关键词不能为空');
            }
            if (empty(trim($post['title']))) {
                //returnCodeError('回复标题不能为空');
                $this->error('回复标题不能为空');
            }
            if (empty($post['group_id']) || $post['group_id'] < 1) {
                returnCodeError('缺少分组参数');
            }
            if (!D('HouseAutoPhrasesGroup')->where(array('id' => $post['group_id']))->find()) {
                returnCodeError('分组不存在');
            }
            $data = array(
                'key' => trim($post['key']),
                'group_id' => $post['group_id'],
                'title'=>trim($post['title']),
                'info'=>trim($post['info']),
                'content'=>trim($post['content']),
                'api_url'=>trim($post['api_url']),
                'url'=>trim($post['url']),
                'create_time'=>time(),
                'village_id'=>$villageId,
            );
            $image = D('Image')->handle($this->system_session['id'], 'platform');
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }
            if ($id = D('HouseAutoPhrases')->add($data)) {
                D('Image')->update_table_id($_POST['pic'], $id, 'HouseAutoPhrases');
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $groupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
            if ($groupId < 1) {
                return $this->error('请选择分组');
            }
            if (!D('HouseAutoPhrasesGroup')->find($groupId)) {
                return $this->error('分组不存在');
            }
            $this->assign('server_name',$_SERVER['SERVER_NAME']);
            $this->assign('group_id', $groupId);
            $this->display();
        }
    }

    /**
     * 新增文本关键字回复
     * @author lijie
     * @date_time 2021/10/18
     */
    public function AddTextKeyword()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            if (empty(trim($post['key']))) {
                $this->error('关键词不能为空');
            }
            if (empty($post['group_id']) || $post['group_id'] < 1) {
                returnCodeError('缺少分组参数');
            }
            if (!D('HouseAutoPhrasesGroup')->where(array('id' => $post['group_id']))->find()) {
                returnCodeError('分组不存在');
            }
            $data = array(
                'key' => trim($post['key']),
                'group_id' => $post['group_id'],
                'content'=>trim($post['content']),
                'url'=>trim($post['url']),
                'type'=>2,
                'create_time'=>time(),
                'village_id'=>$villageId,
            );
            $image = D('Image')->handle($this->system_session['id'], 'platform');
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }
            if ($id = D('HouseAutoPhrases')->add($data)) {
                D('Image')->update_table_id($_POST['pic'], $id, 'HouseAutoPhrases');
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $groupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
            if ($groupId < 1) {
                return $this->error('请选择分组');
            }
            if (!D('HouseAutoPhrasesGroup')->find($groupId)) {
                return $this->error('分组不存在');
            }
            $this->assign('group_id', $groupId);
            $this->display();
        }
    }

    /**
     * 编辑关键字
     * @author lijie
     * @date_time 2021/10/18
     */
    public function editAutoPhrase()
    {
        $post = $_POST;
        if ($post) {
            if (empty(trim($post['key']))) {
                $this->error('关键词不能为空');
            }
            if (isset($post['title']) && empty(trim($post['title']))) {
                $this->error('回复标题不能为空');
            }
            $data = array(
                'key' => trim($post['key']),
                'title'=>trim($post['title']),
                'info'=>trim($post['info']),
                'content'=>trim($post['content']),
                'api_url'=>trim($post['api_url']),
                'url'=>trim($post['url']),
                'create_time'=>time(),
            );
            $image = D('Image')->handle($this->system_session['id'], 'platform');
            if (!$image['error']) {
                $data = array_merge($data, $image['url']);
            }
            if (D('HouseAutoPhrases')->where(['id'=>$_POST['id']])->save($data)) {
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        } else {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id < 1) {
                return $this->error('请选择一条记录');
            }
            $phrase = D('HouseAutoPhrases')->find($id);
            if (empty($phrase)) {
                return $this->error('快捷短信记录不存在');
            }
            $this->assign('phrase', $phrase);
            $this->assign('server_name',$_SERVER['SERVER_NAME']);
            if($phrase['type'] == 1)
                $this->display('EditGraphicKeyword');
            else
                $this->display('EditTextKeyword');
        }
    }

    /**
     * 查询组分对应的关键字回复列表
     * @author lijie
     * @date_time 2021/10/18
     */
    public function ajaxGetAutoPhrases()
    {
        $groupId = intval($_GET['group_id']);
        if ($groupId < 1) {
            layerOk(0, array());
        } else {
            $phrases = D('House_auto_phrases')->where(array('group_id' => $groupId))->select();
            if($phrases){
                foreach ($phrases as &$v){
                    if($v['type'] == 1){
                        $v['type'] = '图文';
                    }else{
                        $v['type'] = '文本';
                    }
                }
            }
            layerOk(count($phrases), $phrases);
        }
    }

    /**
     * 删除关键字回复
     * @author lijie
     * @date_time 2021/10/18
     */
    public function delAutoPhrase()
    {
        $ids = isset($_POST['ids']) ? $_POST['ids'] : array();
        if (empty($ids) || !is_array($ids)) {
            returnCodeError('请至少选择一条删除记录');
        } else {
            $rs = D('House_auto_phrases')->where(array('id' => array('IN', $ids)))->delete();
            if ($rs) {
                returnCodeOk('删除成功');
            } else {
                returnCodeError('删除失败');
            }
        }
    }

    /**
     * 修改关键字分组
     * @author lijie
     * @date_time 2021/10/18
     */
    public function selectAutoPhrasesGroup()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            $ids = isset($post['id']) && is_array($post['id']) ? array_unique(array_filter($post['id'])) : array();
            $groupId = isset($post['group_id']) ? intval($post['group_id']) : 0;
            if (empty($ids)) {
                returnCodeError('请选择修改分组的快捷短语记录');
            }
            if (!D('HouseAutoPhrasesGroup')->find($groupId)) {
                returnCodeError('分组不存在');
            }
            if (D('House_auto_phrases')->where(array('id' => array('IN', $ids)))->save(array('group_id' => $groupId))) {
                returnCodeOk();
            } else {
                returnCodeError('已属于当前组，不可重复修改');
            }
        } else {
            $ids = isset($_GET['id_str']) ? array_unique(array_filter(explode('-', trim($_GET['id_str'])))) : array();
            if (empty($ids)) {
                return $this->error('请选择一条记录');
            }
            $groups = D('HouseAutoPhrasesGroup')->where(array('village_id' => $villageId,'belong'=>$this->belong))->field('id,name')->select();
            $this->assign('ids', $ids);
            $this->assign('groups', $groups);
            $this->display();
        }
    }

    public function addPhrasesGroup()
    {
        $post = $_POST;
        $villageId = $this->village('village_id');
        if ($post) {
            if (empty(trim($post['name']))) {
                returnCodeError('分组名称不能为空');
            }
            if (!isset($post['type']) || !D('Short_phrases_group')->checkType($post['type'])) {
                returnCodeError('快捷短语分类参数有误');
            }
            $data = array(
                'store_id' => $villageId,
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
            $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
            $this->assign('type', $type);
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
            $data = array(
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'context' => trim($post['context']),
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
            $data = array(
                'sort' => isset($post['sort']) ? intval($post['sort']) : 0,
                'context' => trim($post['context'])
            );
            $type = isset($post['type']) ? intval($post['type']) : 1;
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
        $villageId = $this->village('village_id');
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
            if (empty($ids)) {
                return $this->error('请选择一条记录');
            }
            if (!D('Short_phrases_group')->checkType($type)) {
                return $this->error('类型参数有误');
            }
            $groups = D('Short_phrases_group')->where(array('type' => $type, 'store_id' => $villageId,'belong'=>$this->belong))->field('id,name')->select();
            $this->assign('ids', $ids);
            $this->assign('groups', $groups);
            $this->display();
        }
    }

    public function villageChat()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $villageId = $this->village('village_id');
        if ($page > 0) {
            $limit = isset($_GET['limit']) && $_GET['limit'] > 0 ? intval($_GET['limit']) : 10;
            $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
            $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
            $where = ['conv.relation' => ['IN',['villageBind2village','village2villageBind']],'kf.store_id'=>$villageId];
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
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_kefu AS kf ON kf.username = conv.from_user';
            $join .= ' LEFT JOIN ' . C('DB_PREFIX') . 'im_user AS im_user ON im_user.username = conv.to_user';
            $count = M('im_conversation')->alias('conv')->join($join)->where($where)->count();
            if ($count > 0) {
                $lists = M('im_conversation')->alias('conv')
                    ->join($join)
                    ->where($where)
                    ->field('"' . $platNickname . '" AS merchant_name,"' . $platNickname . '" AS store_name,kf.name AS kf_name,conv.id AS chat_id,from_unixtime(IF(conv.last_msg_time,conv.last_msg_time,conv.create_time),"%Y-%m-%d %H:%i") AS last_time,im_user.nickname AS customer_name,im_user.avatar AS customer_avatar')
                    ->order('conv.id DESC')
                    ->limit(($page - 1) * $limit . ',' . $limit)
                    ->select();
            } else {
                $lists = [];
            }
            foreach ($lists as $k => $v) {
                $lists[$k]['customer_avatar'] = replace_file_domain($v['customer_avatar']);
            }
            layerOk($count, $lists);
        } else {
            $this->display();
        }
    }
}
