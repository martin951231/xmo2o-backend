<?php

namespace app\im\controller;

use App;
use app\BaseController;
use app\im\model\ImChatRecord;
use app\im\model\ImConversation;
use app\im\model\ImUser;
use app\im\model\KefuMenu;
use app\im\model\MerchantStoreKefu;
use think\facade\Db;

/**
 * 新版客服接口列表
 *
 * @author: zt
 * @date: 2024/06/25
 */
class UserController extends BaseController
{
    private $curEnv;

    protected function initialize()
    {
        parent::initialize();
        $this->curEnv = request()->param('cur_env', '', 'trim');
        if (!$this->check()) {
            $output = [
                'status' => 1003,
                'msg'      => '非法请求',
                'data'      => []
            ];
            exit(json_encode($output));
        }
    }

    private function check()
    {
        $action = request()->action();
        //内部调用
        if($this->curEnv == 'internal'){
            return true;
        }

        if ($this->curEnv == 'pc' && $action == 'login') {
            $uname = request()->param('username', '', 'trim');
            $s = request()->param('s', '', 'trim');
            $t = request()->param('t', 0, 'trim');
            $md5 = md5($t . $uname . cfg('site_url'));
            if (abs($t - time()) > 120 || $md5 != $s) {
                return false;
            }
        }

        if (request()->action() != 'login' && empty(session('im_login_key'))) {
            return false;
        }
        return true;
    }

    /**
     * 登录
     *
     * @return \think\Response
     */
    public function login()
    {
        $username = request()->param('username', '', 'trim');
        if (empty($username)) {
            return api_output_error(1001, '参数有误!');
        }

        $userinfo = (new \app\im\model\ImUser())->register($username);
        $result = [
            'username' => $userinfo['username'],
            'nickname' => $userinfo['nickname'],
            'avatar' => replace_file_domain($userinfo['avatar']),
            'role' => $userinfo['type'],
            'uuid' => $userinfo['uuid'],
            'token' => md5($userinfo['type'] . '_' . $userinfo['uuid']),
        ];

        //生成鉴权token
        $timestamp = floor(microtime(true) * 1000);
        $imKey = cfg('im_key');
        $imSecret = cfg('im_secret');
        $random = createRandomStr(10);
        $token = md5("appkey=$imKey&timestamp=$timestamp&random_str=$random&key=$imSecret");

        $parseResult = parse_url(cfg('site_url'));
        $host = $parseResult['host'] ?? '';
        $scheme = $parseResult['scheme'] ?? '';
        if ($host == 'www.group.com') {
            $socket = 'ws://127.0.0.1:7601';
        } else {
            $socket = ($scheme == 'https' ? 'wss://' : 'ws://') . $host . '/im';
        }
        $result['wss'] = $socket . '?' . http_build_query(['appkey' => $imKey, 'timestamp' => $timestamp, 'random_str' => $random, 'token' => $token]);

        session('im_login_key', $username);
        return api_output(0, $result);
    }

    /**
     * 获取用户当前状态
     *
     * @return void
     * @author: zt
     * @date: 2024/06/26
     */
    public function stat()
    {
        $username = request()->param('username', '', 'trim');
        if (empty($username)) {
            return api_output_error(1001, '参数有误!');
        }

        $result = [];
        $result['sum_unread_msg_count'] = (new ImConversation())->getUnreadMsgCount($username, '');

        //客服角色获取连接数和状态
        $merKefu = (new MerchantStoreKefu())->where('username', $username)->field('status,max_receive,cur_receive')->find();
        if ($merKefu) {
            $result['online'] = $merKefu->status;
            $result['max_connections'] = $merKefu->max_receive;
            $result['current_connections'] = $merKefu->cur_receive;
        }
        return api_output(0, $result);
    }


    /**
     * 获取会话列表
     *
     * @return void
     * @author: zt
     * @date: 2024/06/26
     */
    public function chatLists()
    {
        $username = request()->param('username', '', 'trim');
        $keyword = request()->param('keyword', '', 'trim');
        $page = request()->param('page', 1, 'intval');
        $limit = request()->param('limit', 30, 'intval');
        if (empty($username)) {
            return api_output_error(1001, '参数有误!');
        }

        $chatLists = (new ImConversation())->getChatLists($username, $page, $limit, $keyword);
        return api_output(0, $chatLists);
    }

    /**
     * 设置客服状态
     *
     * @return void
     * @author: zt
     * @date: 2024/07/01
     */
    public function setStatus()
    {
        $username = request()->param('username', '', 'trim');
        $status = request()->param('status', 0, 'intval');
        if (empty($username)) {
            return api_output_error(1001, '参数有误!');
        }

        $rs = (new MerchantStoreKefu())->where('username', $username)->update(['status' => $status]);
        if ($rs) {
            return api_output();
        } else {
            return api_output_error(1003, '设置状态失败!');
        }
    }

    /**
     * 删除会话
     *
     * @return void
     * @author: zt
     * @date: 2024/07/01
     */
    public function delChat()
    {
        $username = request()->param('username', '', 'trim');
        $id = request()->param('id', '', 'intval');
        if (empty($username) || empty($id)) {
            return api_output_error(1001, '参数有误!');
        }

        (new ImConversation())->delChat($username, $id);
        return api_output();
    }


    /**
     * 获取聊天记录
     *
     * @return void
     * @author: zt
     * @date: 2024/07/01
     */
    public function getChatRecord()
    {
        $username = request()->param('username', '', 'trim');
        $toUser = request()->param('toUser', '', 'trim');
        $page = request()->param('page', 1, 'intval');
        $pageSize = request()->param('pageSize', 30, 'intval');
        $keyword    = request()->param('keyword', '', 'trim');

        if (empty($username) || empty($toUser)) {
            return api_output_error(1001, '参数有误!');
        }

        $chatKey = (new ImChatRecord())->buildChatIndexKey($username, $toUser);

        $records = (new ImChatRecord())->getRecord($chatKey, $username, $page, $pageSize, $keyword);


        $imUserMod = new ImUser();
        list($fromPrefix,) = $imUserMod->getUidAndRole($username);
        list($targetPrefix, $targetUid) = $imUserMod->getUidAndRole($toUser);
        if ($fromPrefix == 'user' && $page == 1) {
            $autoReply = Db::name('short_phrases_group')->alias('g')
                ->join('short_phrases s', 'g.id=s.group_id')
                ->where([['g.store_id', '=', $targetUid], ['g.belong', '=', $targetPrefix], ['s.title', '<>', '']])
                ->order('s.sort DESC')
                ->select()
                ->toArray();
            if ($autoReply) {
                $autoMessage = "您好，有什么可以帮到您？<br/>";
                $tm = time();
                foreach ($autoReply as $a) {
                    $autoMessage .= "<span class='faq' style='color: #1e90ff'>{$a['title']}</span><br/>";
                }
                $auto = [
                    'id' => 'auto_' . $tm,
                    'msg_id' => 'auto_' . $tm,
                    'create_time' => $tm,
                    'from_id' => $toUser,
                    'msg_body' => ['text' => $autoMessage],
                    'msg_type' => 'text',
                    'is_oneself' => 0,
                    'is_read' => 1,
                    'target_id' => $username
                ];
                array_unshift($records, $auto);
            }
        }
        return api_output(0, $records);
    }

    /**
     * 获取用户信息
     *
     * @return void
     * @author: zt
     * @date: 2024/07/01
     */
    public function getUserInfo()
    {
        $username = $this->request->param('username', '', 'trim');
        if (!$username) {
            return api_output_error(1001, '缺少参数');
        }
        $imUserMod = new ImUser();
        $userInfo = $imUserMod->getUser($username, 'username,nickname,avatar,type as role,uuid');
        if (empty($userInfo)) {
            //没有注册符合用户角色的就自动注册下
            (new \app\im\model\ImUser())->register($username);
            $userInfo = $imUserMod->getUser($username, 'username,nickname,avatar,type as role,uuid');
        }
        $userInfo['extends'] = [];
        $baseinfo = $imUserMod->getUserBaseInfo($username);
        switch ($userInfo['role']) {
            case 'user':
                $user = Db::name('user')->where(array('uid' => $userInfo['uuid']))
                    ->field('uid,real_name,phone,FROM_UNIXTIME(add_time,"' . L_('X1年X2月X3日', ['X1' => '%Y', 'X2' => '%m', 'X3' => '%d']) . '") AS register_date,now_money,score_count')
                    ->find();
                if ($user) {
                    $userInfo['extends'] =  [
                        ['name' => '实名认证', 'value' => $user['real_name'] == 1 ? '是' : '否', 'type' => 'text', 'url' => ''],
                        ['name' => '手机号码', 'value' => phone_show($user['phone']), 'type' => 'text', 'url' => ''],
                        ['name' => '注册时间', 'value' => $user['register_date'], 'type' => 'text', 'url' => ''],
                        ['name' => '平台余额', 'value' => $user['now_money'], 'type' => 'text', 'url' => ''],
                        ['name' => '平台积分', 'value' => $user['score_count'], 'type' => 'text', 'url' => ''],
                    ];
                }
                $userInfo['nickname'] = $baseinfo['nickname'] ?? '';
                $userInfo['avatar'] = $baseinfo['avatar'] ? replace_file_domain($baseinfo['avatar']) : cfg('site_url') . '/static/im/icon/user_icon.png';
                break;
            case 'store':
                $store = (new \app\common\model\db\MerchantStore())->where('store_id', $userInfo['uuid'])->find();
                if ($store['phone']) {
                    $userInfo['phone'] = explode(' ', $store['phone'])[0];
                }
                $userInfo['store_index'] = get_base_url('pages/store/v1/home/index?store_id=' . $userInfo['uuid'], true);
                $complaintUrl = get_base_url('pages/my/complaint/submit?type=shop&mer_id=' . $store['mer_id'] . '&store_id=' . $store['store_id']);
                $userInfo['extends'] =  [
                    ['name' => '进店逛逛', 'value' => '  ', 'type' => 'url', 'url' => $userInfo['store_index']],
                    ['name' => '举报投诉', 'value' => '  ', 'type' => 'url', 'url' => $complaintUrl]
                ];
                $userInfo['nickname'] = $baseinfo['nickname'] ?? '';
                $userInfo['avatar'] = replace_file_domain($baseinfo['avatar'] ?? '');
                break;
            case 'plat':
                $userInfo['nickname'] = cfg('plat_kefu_alias');
                $userInfo['avatar'] = replace_file_domain(cfg('wechat_share_img'));
                if(cfg('site_phone')){
                    $userInfo['phone'] = explode(' ', cfg('site_phone'))[0];
                }
                $complaintUrl = get_base_url('pages/my/complaint/submit?type=plat&mer_id=&store_id=');
                $userInfo['extends'] =  [
                    ['name' => '举报投诉', 'value' => '  ', 'type' => 'url', 'url' => $complaintUrl]
                ];
                break;
            case 'deliver':
                //配送员
                $info = Db::name('deliver_user')->where(['uid' => $userInfo['uuid']])
                    ->field('name AS nickname,score,phone,average_score,create_time')
                    ->find();
                if ($info) {
                    $userInfo['nickname'] = '骑手' . ' ' . $info['nickname'];
                    $userInfo['avatar'] = cfg('site_url') . '/static/im/icon/deliver_icon.png';
                    $userInfo['extends'] =  [
                        ['name' => '加入时间', 'value' => date('Y/m/d', $info['create_time']), 'type' => 'text', 'url' => ''],
                        ['name' => '手机号码', 'value' => phone_show($info['phone']), 'type' => 'text', 'url' => ''],
                        ['name' => '客户评价', 'value' => $info['average_score'] . '分', 'type' => 'text', 'url' => '']
                    ];
                }
        }
        return api_output(0, $userInfo);
    }

    /**
     * 发送消息
     *
     * @return void
     * @author: zt
     * @date: 2024/07/03
     */
    public function send()
    {
        $fromUser = $this->request->param('from_id', '', 'trim');
        $toUser = $this->request->param('target_id', '', 'trim');
        $type = $this->request->param('msg_type', '', 'trim');
        $body = $this->request->param('msg_body', '', 'trim');
        $msgId = $this->request->param('msg_id', '', 'trim');
        if (is_string($body)) {
            $body = json_decode(html_entity_decode($body, ENT_QUOTES), true);
        }
        if (empty($fromUser) || empty($toUser) || empty($body) || empty($type)) {
            return api_output_error(1001, '缺少参数');
        }

        $tm = time();
        $imUserMod = new ImUser();
        $kefuMod = new MerchantStoreKefu();
        $conversationMod = new ImConversation();
        $chatRecordMod = new ImChatRecord();
        list($fRole, $fUUID) = $imUserMod->getUidAndRole($fromUser);
        list($tRole, $tUUID) = $imUserMod->getUidAndRole($toUser);

        $showRule = 0;  //0:消息双方可看  1：发送者可见   2、接收方可见
        if (in_array($type, array('coupon', 'search_orders', 'guess_you_like', 'customer_notify'))) {
            $showRule = 1;
        }

        //推送用户消息中心数据
        $msgNotice = [];

        //自动回复
        $indexKey = $chatRecordMod->buildChatIndexKey($fromUser, $toUser);
        $autoReplyContent = '';
        if ($type == 'text' && $body['text']) {
            $autoReplyContent = Db::name('short_phrases_group')->alias('g')
                ->join('short_phrases s', 'g.id=s.group_id')
                ->where(['g.store_id' => $tUUID, 'g.belong' => $tRole, 's.title' => $body['text']])
                ->value('s.context');
        }

        if ($autoReplyContent) {
            $msgId = createRandomStr(10, true);
            $msgCtime = time();

            //记录聊天信息
            $autoReplyData = [
                [
                    'index_key' => $indexKey,
                    'msg_id' => $msgId,
                    'from_id' => $fromUser,
                    'target_id' => $toUser,
                    'target_type' => 'single',
                    'from_platform' => 'auto_reply',
                    'version' => 1,
                    'msg_type' => $type,
                    'create_time' => $msgCtime,
                    'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                    'show_rule' => 1,
                ],
                [
                    'index_key' => $indexKey,
                    'msg_id' => $msgId + 1,
                    'from_id' => $toUser,
                    'target_id' => $fromUser,
                    'target_type' => 'single',
                    'from_platform' => 'auto_reply',
                    'version' => 1,
                    'msg_type' => $type,
                    'create_time' => $msgCtime + 1,
                    'msg_body' => json_encode(['text' => $autoReplyContent], JSON_UNESCAPED_UNICODE),
                    'show_rule' => 2,
                ],
            ];
            $chatRecordMod->insertAll($autoReplyData);

            $msgNotice = [
                'type' => $tRole == 'store' ? 1 : 2,
                'store_id' => $tRole == 'store' ? $fUUID : 0,
                'kefu_id' => explode('_', $fromUser)[2] ?? 0,
                'content' => json_encode(array_merge(['text' => $autoReplyContent], ['type' => 'text']), JSON_UNESCAPED_UNICODE),
                'uid' => $fUUID,
                'is_read' => 1
            ];
        } else {
            if (in_array($tRole, [$imUserMod::PREFIX_STORE, $imUserMod::PREFIX_PLAT])) {
                //分配客服
                $assignRs = $kefuMod->assignKefu($fromUser, $toUser, $type, $body);
                if ($assignRs['code'] == 0) {
                    $toUser = $assignRs['retval']['username'];
                    $welcome = $assignRs['retval']['welcome'] ?? '';
                } else {
                    return api_output_error(1001, $assignRs['errorMsg'] ?? '');
                }
            }

            $conversationMod->build($fromUser, $toUser, $conversationMod::TYPE_SINGLE);

            $data = array(
                'index_key' => $indexKey,
                'msg_id' => $msgId,
                'from_id' => $fromUser,
                'target_id' => $toUser,
                'target_type' => 'single',
                'from_platform' => 'a',
                'version' => 1,
                'msg_type' => $type,
                'create_time' => $tm,
                'msg_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                'is_read' => 0,
                'show_rule' => $showRule
            );
            $chatRecordMod->insert($data);

            //写入用户消息通知
            if($tRole=='user' && in_array($fRole,['store','plat'])){
                $msgNotice = [
                    'type' => $fRole == 'store' ? 1 : 2,
                    'store_id' => $fRole == 'store' ? $fUUID : 0,
                    'kefu_id' => explode('_', $fromUser)[2] ?? 0,
                    'content' => json_encode(array_merge($body,['type'=>$data['msg_type']]), JSON_UNESCAPED_UNICODE),
                    'uid' => $tUUID
                ];
            }
        }

        //记录到消息中心
        $msgNotice && (new \app\common\model\service\user\UserNoticeService())->addNotice($msgNotice);

        //更新最后一条聊天信息，同时增加接收人未读消息数
        $lastMsg = $chatRecordMod->getSimpleText($type, $body);
        $lastMsgTime = $tm;
        $lastMsgId = $msgId;

        $fromUUID = $fRole . '_' . $fUUID;
        $toUUID = $tRole . '_' . $tUUID;
        $update = [
            'last_msg' => $lastMsg,
            'last_msg_time' => $lastMsgTime,
            'last_msg_id' => $lastMsgId,
        ];
        if ($showRule == 1 || $showRule == 0) {
            $conversationMod->where(['from_uuid' => $fromUUID, 'to_uuid' => $toUUID])->update($update);
        }
        if ($showRule == 2 || $showRule == 0) {
            $conversationMod->where(['from_uuid' => $toUUID, 'to_uuid' => $fromUUID])->inc('unread_msg_count', 1)->update($update);
        }

        return api_output(0, ['to_user' => $toUser, 'auto_reply' => $autoReplyContent,'extra'=>['welcome'=>$welcome??'']]);
    }

    /**
     * 操作权限
     *
     * @return void
     * @author: zt
     * @date: 2024/07/05
     */
    public function operate()
    {
        $username = $this->request->param('username', '', 'trim');
        $env = $this->request->param('cur_env', 'h5', 'trim');
        if (!$username) {
            return api_output_error(1001, '缺少参数');
        }

        $imUserMod = new ImUser();
        list($role,) = $imUserMod->getUidAndRole($username);

        $menus = [
            [
                'action' => 'photo',
                'title' => L_('相册'),
                'icon' => cfg('site_url') . '/static/im/icon/' . $env . '/photo.png'
            ],
            // [
            //     'action' => 'camera',
            //     'title' => L_('拍摄'),
            //     'icon' => cfg('site_url') . '/static/im/icon/'.$env.'/camera.png'
            // ],
            [
                'action' => 'video',
                'title' => L_('视频'),
                'icon' => cfg('site_url') . '/static/im/icon/' . $env . '/video.png'
            ],
        ];

        if (in_array($role, [$imUserMod::PREFIX_STORE, $imUserMod::PREFIX_PLAT])) {
            $kefu = (new MerchantStoreKefu())->where(['username' => $username, 'belong' => $role])->field('recommended_goods,can_send_coupon')->find();
            if (!$kefu) {
                return api_output_error(1003, '客服用户不存在');
            }
            $menus[] = [
                'action' => 'transfer',
                'title' => L_('转接'),
                'icon' => cfg('site_url') . '/static/im/icon/' . $env . '/transfer.png'
            ];
            $menus[] = [
                'action' => 'quick_reply',
                'title' => L_('快捷回复'),
                'icon' => cfg('site_url') . '/static/im/icon/' . $env . '/quick_reply.png'
            ];
            if ($kefu->can_send_coupon == 1) {
                $menus[] = [
                    'action' => 'coupon',
                    'title' => L_('优惠券'),
                    'icon' => cfg('site_url') . '/static/im/icon/' . $env . '/coupon.png'
                ];
            }
            if ($kefu->recommended_goods == 1) {
                $menus[] = [
                    'action' => 'recommend_goods',
                    'title' => L_('推荐商品'),
                    'icon' => cfg('site_url') . '/static/im/icon/' . $env . '/recommend_goods.png'
                ];
            }
        }
        return api_output(0, $menus);
    }

    /**
     * 转接客服列表
     *
     * @return void
     * @author: zt
     * @date: 2024/07/05
     */
    public function kfLists()
    {
        $username = $this->request->param('username', '', 'trim');
        if (!$username) {
            return api_output_error(1001, '缺少参数');
        }
        list($role, $uid) = (new ImUser())->getUidAndRole($username);

        $kflists = (new MerchantStoreKefu())->getOnlineKfListsByStoreId($uid, $role, $username);
        return api_output(0, $kflists);
    }

    /**
     * 转接客服
     *
     * @return void
     * @author: zt
     * @date: 2024/07/05
     */
    public function transferKf()
    {
        $fromKf = $this->request->param('from_kf', '', 'trim');
        $toKf = $this->request->param('to_kf', '', 'trim');
        $customer = $this->request->param('customer', '', 'trim');
        if (empty($fromKf) || empty($toKf) || empty($customer)) {
            return api_output_error(1001, '缺少参数');
        }
        if ($fromKf == $toKf) {
            return api_output_error(1001, '禁止转接给自己');
        }


        $rs = (new MerchantStoreKefu())->transferKf($fromKf, $toKf, $customer);
        if ($rs['result']) {
            //转接成功，记录一条消息到数据库
            $custom = array(
                'type' => 'kf_notify',
                'show_rule' => 2,
                'body' => array(
                    'action' => 'transfer_success',
                    'text' => $rs['msg'],
                    'from_user' => $toKf,
                    'to_user' => $customer,
                    'relation' => 'store2user'
                )
            );
            $msgId = $msgCtime = time();
            //记录聊天信息
            $data = array(
                'index_key' => (new ImChatRecord())->buildChatIndexKey($toKf, $customer),
                'msg_id' => $msgId,
                'from_id' => $customer,
                'target_id' => $toKf,
                'target_type' => 'single',
                'from_platform' => 'a',
                'version' => 1,
                'show_rule' => 2,
                'msg_type' => $custom['type'],
                'create_time' => $msgCtime,
                'msg_body' => json_encode($custom['body'], JSON_UNESCAPED_UNICODE)
            );
            (new ImChatRecord())->insert($data);
            return api_output();
        } else {
            return api_output_error(1003, $rs['msg'] ?? '转接失败');
        }
    }

    /**
     * 获取用户菜单（优惠券、猜你喜欢、订单列表等）
     */
    public function menu()
    {
        $username = $this->request->param('username', '', 'trim');
        if (empty($username)) {
            return api_output_error(1001, '缺少参数');
        }

        $imUserMod = new ImUser();
        list($role, $uid) = $imUserMod->getUidAndRole($username);
        if (in_array($role, array(ImUser::PREFIX_STORE, ImUser::PREFIX_PLAT))) {
            $menus = Db::name('kefu_menu')->where(array('store_id' => $uid, 'belong' => $role))->field('id,name,type')->order('sort DESC')->select()->toArray();
        } else {
            $menus = [];
        }
        $menus = array_map(function ($item) {
            $item['type'] = $item['type'] == 'system_coupon' ? 'coupon' : $item['type'];
            return $item;
        }, $menus);
        return api_output(0, $menus);
    }

    /**
     * 获取自助菜单数据
     */
    public function menuData()
    {
        $id = $this->request->param('menu_id', 0, 'intval');
        $username = $this->request->param('username', '', 'trim');
        $goodId = $this->request->param('goods_id', 0, 'intval');
        $from = $this->request->param('from', '', 'trim');
        if (empty($id) || empty($username)) {
            return api_output_error(1001, '缺少参数');
        }

        $menuMod = new KefuMenu();

        $menu =  $menuMod->where('id', $id)->find();
        if (empty($menu)) {
            return api_output_error(1001, '快捷菜单未配置');
        }

        $rs = array('type' => $menu->type == $menuMod::SYSTEM_COUPON ? $menuMod::COUPON : $menu->type);

        switch ($menu->type) {
            case $menuMod::COUPON:
                $coupon = json_decode($menu['coupons'], true);
                $couponIds = array_column($coupon, 'coupon_id');
                $couponsInfo =  $menuMod->getCouponByIds($couponIds);
                if ($couponsInfo) {
                    foreach ($couponsInfo as &$c) {
                        $c['type'] = 'store';
                    }
                    $rs['retval'] = $couponsInfo;
                }
                break;
            case $menuMod::SYSTEM_COUPON:
                $coupon = json_decode($menu['coupons'], true);
                $couponIds = array_column($coupon, 'coupon_id');

                $couponsInfo = $menuMod->getSystemCouponByIds($couponIds);
                if ($couponsInfo) {
                    foreach ($couponsInfo as &$c) {
                        $c['type'] = 'plat';
                    }
                    $rs['retval'] = $couponsInfo;
                }
                break;
            case $menuMod::LINK:
                $rs['retval'] = array('url' => html_entity_decode($menu->link, ENT_QUOTES));
                break;
            case $menuMod::GUESS_YOU_LIKE:
                $guess = $menuMod->guessYouLike($menu['store_id'], $goodId, $from);
                if ($guess) {
                    $rs['retval'] = $guess;
                } else {
                    $rs['retval'] = array();
                }
                break;
            case $menuMod::SEARCH_ORDERS:
                list($role, $uid) = (new ImUser())->getUidAndRole($username);
                if ($role == ImUser::PREFIX_USER && $uid > 0) {
                    $lastOrder = $menuMod->findLastPayOrder($menu['store_id'], $uid, $from);
                }
                $rs['retval'] = $lastOrder ?? [];
                break;
            default:
                return api_output_error(1001, '快捷菜单未配置');
                break;
        }
        return api_output(0, $rs);
    }

    /**
     * 获取客服优惠券
     *
     * @return void
     * @author: zt
     * @date: 2024/07/09
     */
    public function getKfCoupon()
    {
        $username = $this->request->param('username', '', 'trim');
        if (!$username) {
            return api_output_error(1001, '参数有误');
        }

        $kf = (new MerchantStoreKefu())->where('username', $username)->find();
        if (empty($kf)) {
            return api_output_error(1001, '客服用户不存在');
        }

        $imUserMod = new ImUser();
        list($role, $uid) = $imUserMod->getUidAndRole($username);

        if ($role == $imUserMod::PREFIX_STORE) {
            $res = invoke_cms_model('Card_new_coupon/getAllImGetCoupon', [$uid]);
            $allCoupons = $res['retval'] ?? [];
        } else if ($role == $imUserMod::PREFIX_PLAT) {
            //平台客服获取平台优惠券
            $where = [
                ['end_time', '>', time()],
                ['start_time', '<', time()],
                ['allow_im_get', '=', 1],
                ['status', '=', 1]
            ];
            $field = "coupon_id, name, `num`-`had_pull` AS left_num,discount,order_money,start_time,end_time,img AS image,platform";
            $allCoupons = Db::name('System_coupon')->where($where)->field($field)->select()->toArray();
        }

        $platform = [
            'wap' => '移动网页',
            'app' => 'APP',
            'weixin' => '微信',
            'wxapp' => '小程序'
        ];
        foreach ($allCoupons as &$v) {
            $v['platform'] = unserialize($v['platform']);
            $v['platform'] = array_map(function ($item) use ($platform) {
                return $platform[$item];
            }, $v['platform']);
            $v['image'] = replace_file_domain($v['image']);
            $v['type'] = $role;
            $v['start_time'] = date('Y/m/d', $v['start_time']);
            $v['end_time'] = date('Y/m/d', $v['end_time']);
            $v['discount'] = getFormatNumber($v['discount']);
            $v['order_money'] = getFormatNumber($v['order_money']);
        }
        return api_output(0, $allCoupons);
    }

    public function recommendGoods()
    {
        $type = $this->request->param('type', '', 'trim');
        $username = $this->request->param('username', '', 'trim');
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 10, 'intval');
        if (empty($username)) {
            return api_output_error(1001, '客服用户不存在');
        }
        $imUserMod = new ImUser();
        list($role, $storeId) = $imUserMod->getUidAndRole($username);

        $goods = [];
        switch ($type) {
            case "shop":
                $goods = Db::name('shop_goods')->where(array('store_id' => $storeId, 'status' => 1, 'goods_type' => 0))
                    ->field('goods_id AS id,store_id,`name`,price,image,sell_count AS sale_count')
                    ->order('sell_count DESC')
                    ->page($page, $pageSize)
                    ->select()->toArray();
                break;
            case "mall":
                $goods = Db::name('mall_goods')->where(array('store_id' => $storeId, 'status' => 1,))
                    ->field('goods_id AS id,store_id,`name`,price,image,sale_num AS sale_count')
                    ->order('sale_num DESC')
                    ->page($page, $pageSize)
                    ->select()->toArray();
                break;
            case "group":
                $tm = time();
                $goods = Db::name('Group_store')->alias('s')
                    ->field('g.group_id AS id,g.`name`,g.sale_count,s.store_id,g.price,g.pic AS image')
                    ->join('group g', 'g.group_id = s.group_id')
                    ->where('s.store_id', $storeId)
                    ->where('g.group_cate', 'normal')
                    ->where('g.begin_time', '>', $tm)
                    ->where('g.end_time', '<', $tm)
                    ->order('g.sale_count DESC')
                    ->page($page, $pageSize)
                    ->select()->toArray();
                break;
            default:
                return api_output_error(1001, '非法参数请求');
                break;
        }
        foreach ($goods as &$good) {
            if ($type == 'shop') {
                $good['url'] = get_base_url('pages/shop_new/goodsDetail/goodsDetail?store_id=' . $good['store_id'] . '&product_id=' . $good['id']);
            } else if ($type == 'mall') {
                $good['url'] = get_base_url('pages/shopmall_third/commodity_details?goods_id=' . $good['id']);
            } else if ($type == 'group') {
                $good['url'] = get_base_url('pages/group/v1/groupDetail/index?group_id=' . $good['id'], true);
            }
            $images = explode(';', $good['image']);
            $good['image'] = replace_file_domain($images[0] ?? '');
            $good['type'] = $type;
        }
        return api_output(0, $goods);
    }

    public function chatinfo()
    {
        $username = $this->request->param('username', '', 'trim');
        $toUser = request()->param('toUser', '', 'trim');
        if (!$username || !$toUser) {
            return api_output_error(1001, '缺少必要参数');
        }
        $chatKey = (new ImChatRecord())->buildChatIndexKey($username, $toUser);
        return api_output(0, ['chat_id' => $chatKey]);
    }

    /**
     * 已读消息上报
     *
     * @return void
     * @author: zt
     * @date: 2024/07/15
     */
    public function readReport()
    {
        $tm = time();
        $fromUser = $this->request->param('from_id', '', 'trim');
        $toUser = $this->request->param('target_id', '', 'trim');
        $chatid = $this->request->param('chat_id', '', 'trim');

        if (empty($fromUser) || empty($toUser) || empty($chatid)) {
            return api_output_error(1001, '参数有误');
        }

        $imChatRecordMod = new ImChatRecord();
        $chatMod = new ImConversation();
        $imUserMod = new ImUser();

        if ($imChatRecordMod->buildChatIndexKey($fromUser, $toUser) !== $chatid) {
            return api_output_error(1001, '非法参数请求');
        }

        $fromUUID = $imUserMod->getUUIDByUsername($fromUser);
        $toUUID = $imUserMod->getUUIDByUsername($toUser);
        //全部标记为已读取
        $imChatRecordMod->where('index_key', $chatid)
            ->where('is_read', '=', 0)
            ->where('show_rule', 'IN', '0,2')
            ->where('target_id', 'like', $fromUUID . '%')
            ->update(['is_read' => 1]);

        $chatMod->where([
            ['from_uuid', '=', $fromUUID],
            ['to_uuid', '=', $toUUID],
            ['unread_msg_count', '>', 0],
        ])->update(array('unread_msg_count' => 0));

        $toUser = $chatMod->where([
            ['from_uuid', '=', $fromUUID],
            ['to_uuid', '=', $toUUID],
        ])->value('to_user');

        //用户消息中心已读
        if (substr($fromUUID, 0, 4) == 'user' && (substr($toUUID, 0, 4) == 'plat' || substr($toUUID, 0, 5) == 'store')) {
            $param['type'] = explode('_', $toUUID)[0] == 'store' ? 1 : 2;
            $param['store_id'] = explode('_', $toUUID)[0] == 'store' ? explode('_', $toUUID)[1] : 0;
            $param['uid'] = explode('_', $fromUser)[1] ?? 0;
            (new \app\common\model\service\user\UserNoticeService())->readNotice($param);
        }
        return api_output(0, ['to_user' => $toUser]);
    }

    /**
     * 获取当前用户所有角色
     *
     * @return void
     * @date: 2024/07/24
     */
    public function myRoles()
    {
        $username = $this->request->param('username', '', 'trim');
        if (!$username) {
            return api_output_error(1001, '缺少必要参数');
        }

        $bindUid = (new MerchantStoreKefu())->where('username', $username)->value('bind_uid');

        $lists = Db::name('merchant_store_kefu')->alias('kf')
            ->leftJoin('merchant_store s', 'kf.store_id = s.store_id AND kf.belong="store"')
            ->field('kf.username,kf.belong,kf.avatar,kf.bind_uid,s.name AS store_name,s.logo')
            ->where([['kf.bind_uid', '=', $bindUid], ['kf.belong', 'IN', 'plat,store']])
            ->select()
            ->toArray();
        foreach ($lists as &$kefu) {
            if ($kefu['belong'] == 'plat') {
                $kefu['store_name'] = '平台客服';
                $kefu['avatar'] = replace_file_domain(cfg('wechat_share_img'));
            } else if ($kefu['belong'] == 'store') {
                $kefu['store_name'] = '店铺客服（所属店铺：' . $kefu['store_name'] . '）';
                $kefu['avatar'] = replace_file_domain($kefu['logo']);
            }
            $kefu['current_role'] = intval($username == $kefu['username']);
            unset($kefu['bind_uid'], $kefu['belong'], $kefu['logo']);
            $kefu['url'] = build_store_im_conversation_url($kefu['username'], '', [], $this->curEnv == 'pc');
        }

        return api_output(0, $lists);
    }

    //检查会话是否回复过期
    public function checkTimeout()
    {
        $username = $this->request->param('username', '', 'trim');
        $chatId = $this->request->param('chat_id', '', 'trim');

        if (!$username) {
            return api_output_error(1001, '参数有误');
        }
        $result = (new ImConversation())->checkReplyTimeout($username, $chatId);
        return api_output(0, $result);
    }

}
