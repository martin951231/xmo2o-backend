<?php

class TestAction extends BaseAction
{
    private $storeId = 0;

    private $iniPath = __DIR__ . '/im.ini';

    public function kefu()
    {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        if ($page > 0) {
            $storeId = $this->storeId;
            $lists = M('merchant_store_kefu')->alias('kf')
                ->join(C('DB_PREFIX') . 'user u ON kf.bind_uid = u.uid')
                ->field('kf.*,u.nickname,u.avatar AS bind_user_avatar')
                ->where(array('kf.store_id' => $storeId))
                ->select();
            foreach ($lists as &$kefu) {
                if ($kefu['avatar']) {
                    $kefu['avatar'] = file_domain() . $kefu['avatar'];
                }
            }
            $count = M('merchant_store_kefu')->where(array('store_id' => $storeId))->count();
            layerOk($count, $lists);
        } else {
            $kfList = parse_ini_file($this->iniPath, true)['kefu'];
            $columns = [];
            foreach ($kfList['column'] as $v) {
                array_push($columns, json_decode($v, true));
            }
            $this->assign('columns', json_encode([$columns], JSON_UNESCAPED_UNICODE));
            $this->assign('store_id', $this->storeId);
            $this->display();
        }
    }

    public function addKefu()
    {
        $post = $_POST;
        if ($post) {
            $kdata = ['store_id' => $this->storeId];
            foreach ($post as $k => $p) {
                switch ($k) {
                    case 'kefu_name':
                        if (empty($p)) {
                            returnCodeError('请填写客服名称');
                        }
                        $kdata['name'] = $p;
                        $kdata['first_letter'] = $this->_getFirstCharter($p);
                        break;
                    case 'welcome':
                        $kdata['welcome'] = $p;
                        break;
                    case 'bind_uid':
                        if ($p < 1) {
                            returnCodeError('请先绑定用户');
                        }
                        $kdata['bind_uid'] = $p;
                        break;
                    case 'customer_uids':
                        $kdata['bind_consumer_count'] = count($p);
                        break;
                    case 'max_receive':
                        $kdata['max_receive'] = $p;
                        break;
                    case 'recommended_goods':
                        $kdata['recommended_goods'] = self::switch2int($post['recommended_goods']);
                        break;
                    case 'can_send_coupon':
                        $kdata['can_send_coupon'] = self::switch2int($post['can_send_coupon']);
                        break;
                }
            }
            $kefuMod = M('merchant_store_kefu');
            $kid = $kefuMod->add($kdata);
            if ($post['customer_uids']) {
                $kcData = [];
                foreach ($post['customer_uids'] as $u) {
                    $kcData[] = array(
                        'kefu_id' => $kid,
                        'uid' => $u
                    );
                }
                M('merchant_store_kefu_customer')->addAll($kcData);
            }
            $username = Im_userModel::PREFIX_PLAT . '_' . $this->storeId . '_' . $kid;
            //客服添加成功，需要调用极光注册该用户
            $rs = D('Im_user')->jRegisterUser(Im_userModel::PREFIX_PLAT . '_' . $this->storeId . '_' . $kid, ['nickname' => $post['kefu_name']]);
            if ($rs['errorCode'] != 0) {
                $kefuMod->where(['id' => $kid])->delete();
                M('merchant_store_kefu_customer')->where(['kefu_id' => $kid])->delete();
                returnCodeError($rs['errorMsg']);
            } else {
                $kefuMod->where(['id' => $kid])->save(['username' => $username]);
                returnCodeOk();
            }
        } else {
            $kfConfig = parse_ini_file($this->iniPath, true)['addKefu'];
            $params = [];
            foreach ($kfConfig['params'] as $v) {
                array_push($params, json_decode($v, true));
            }
            $this->assign('params', $params);
            $this->assign('store_id', $this->storeId);
            $this->display();
        }
    }

    private function _getFirstCharter($str)
    {
        loadSystemOrg('words');
        $string = (new words())->wordgetFirstCharter($str);
        return strtoupper(substr($string, 0, 1));
    }

    static function switch2int($val)
    {
        return $val == 'on' ? 1 : $val;
    }
}
