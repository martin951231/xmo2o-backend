<?php

namespace app\merchant\model\service;

use app\merchant\model\db\Merchant;
use app\merchant\model\db\MerchantExtends;
use app\warn\model\db\WarnNotice;
use app\wechatLive\model\service\LiveAnchorService;
use Exception;
use think\facade\Db;

/**
 * 商家拓展信息服务
 *
 * @author: zt
 * @date: 2023/12/12
 */
class MerchantExtendsService
{
    protected $merExtendsMod = null;

    public function __construct()
    {
        $this->merExtendsMod = new MerchantExtends();
    }

    /**
     * 用户商家后台申请微信直播权限
     *
     * @param int $merId
     * @return void
     * @author: zt
     * @date: 2023/12/12
     */
    public function getWechatLiveApply($merId)
    {
        $merchant = (new Merchant())->getOne([['mer_id', '=', $merId], ['status', '<>', 4]]);
        if (empty($merchant)) {
            throw new Exception('商家不存在或者已关闭');
        }
        $extends = $this->merExtendsMod->getOne(['mer_id' => $merId]);
        if (empty($extends)) {
            $currentStep = 0;
        } elseif ($extends['audit_result'] == 0) {
            $currentStep = 1;
        } else {
            $currentStep = 2;
        }

        //当前商家营业额
        $totalPrice = Db::name('system_order')
            ->where(['mer_id' => $merId, 'paid' => 1])
            ->whereNotIn('system_status', [4, 5, 7])
            ->sum('price');
        //当前商家外卖、商城、团购、预约商品总数
        $shopCount = Db::name('shop_goods')->alias('sg')
            ->join('merchant_store s', 's.store_id=sg.store_id')
            ->where([['s.mer_id', '=', $merId], ['s.status', '<>', 4]])
            ->count();
        $mallCount = Db::name('mall_goods')->alias('mg')
            ->join('merchant_store s', 's.store_id=mg.store_id')
            ->where([['s.mer_id', '=', $merId], ['s.status', '<>', 4]])
            ->count();
        $groupCount = Db::name('group')->where([['mer_id', '=', $merId]])->count();
        $appointCount = Db::name('appoint')->where([['mer_id', '=', $merId]])->count();
        $productCount = $shopCount + $mallCount + $groupCount + $appointCount;

        $authMoney = cfg('wechat_mini_live_auth_money');
        $authProductCount = cfg('wechat_mini_live_auth_goods_count');
        $authRegDay = cfg('wechat_mini_live_auth_reg_days');
        $rs = [
            'conditions' => [
                [
                    'title' => '商家入驻时长至少' .  $authRegDay . '天',
                    'result' => $merchant->reg_time + 86400 * $authRegDay < time() ? 'success' : 'fail',
                    'tip' => '商家入驻时间为：' . date('Y-m-d H:i:s', $merchant->reg_time)
                ],
                [
                    'title' => '商家营业额需达到' .  $authMoney . '元',
                    'result' => $totalPrice > $authMoney ? 'success' : 'fail',
                    'tip' => '当前商家营业额' . $totalPrice . '元'
                ],
                [
                    'title' => '商家(外卖、商城、团购、预约)商品总数量需要达到' . $authProductCount . '个以上',
                    'result' => $productCount > $authProductCount ? 'success' : 'fail',
                    'tip' => '当前商家商品总数量为' . $productCount . '个'
                ]
            ],
            'current_step' => $currentStep,
            'can_submit_audit' => ($merchant->reg_time + 86400 * $authRegDay < time() &&$totalPrice > cfg('wechat_mini_live_auth_money') && $productCount > cfg('wechat_mini_live_auth_goods_count')),
            'audit_result' => $extends['audit_result'] ?? 0,
            'audit_message' => $extends['audit_message'] ?? '',
            'wechat_live_auth'=>$extends['wechat_live_auth']??0,
            'wechat_live_auth_message'=>$extends['wechat_live_auth_message']??'',
            'wechat_mini_live_auth' => cfg('wechat_mini_live_auth')
        ];
        return $rs;
    }


    public function submitWechatLiveApply($merId)
    {
        if (intval(cfg('wechat_mini_live_auth')) != 1) {
            throw new Exception('平台已关闭小程序直播申请，禁止提交申请开通');
        }
        $extends = $this->merExtendsMod->getOne(['mer_id' => $merId]);
        $data = [
            'mer_id' => $merId,
            'audit_result' => 0,
            'apply_time' => time()
        ];
        if ($extends) {
            $this->merExtendsMod->updateThis(['mer_id' => $merId], $data);
        } else {
            $this->merExtendsMod->add($data);
        }
    }

    public function getLists($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $auditResult = $params['audit_result'] ?? -1;
        $keyword = $params['keyword'] ?? '';
        $wechatLiveAuth = $params['wechat_live_auth'] ?? -1;

        $where = [];
        if ($auditResult != -1) {
            $where[] = ['me.audit_result', '=', $auditResult];
        }
        if ($wechatLiveAuth != -1) {
            $where[] = ['me.wechat_live_auth', '=', $wechatLiveAuth];
        }
        if ($keyword) {
            $where[] = ['m.name', 'LIKE', '%' . $keyword . '%'];
        }
        $total = $this->merExtendsMod->alias('me')
            ->join('merchant m', 'm.mer_id=me.mer_id')
            ->where($where)->count();
        $lists = $this->merExtendsMod->alias('me')
            ->join('merchant m', 'm.mer_id=me.mer_id')
            ->where($where)
            ->field('m.name AS mer_name,m.reg_time,m.phone,me.*')
            ->order('me.id desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        foreach ($lists as $k => $v) {
            $lists[$k]['reg_date'] = date('Y-m-d H:i:s', $v['reg_time']);
            $lists[$k]['apply_date'] = date('Y-m-d H:i:s', $v['apply_time']);
            $lists[$k]['audit_date'] = $v['audit_time'] > 0 ? date('Y-m-d H:i:s', $v['audit_time']) : '--';
        }
        return ['list' => $lists, 'total' => $total];
    }


    /**
     * 审核商家直播权限
     *
     * @param int $merId
     * @param int $auditStatus 审核状态 0:待审核 1:审核通过 2:审核不通过
     * @param string $auditMessage
     * @param int $adminId
     * @return void
     * @author: zt
     * @date: 2023/12/13
     */
    public function handleAudit($merId, $auditResult, $auditMessage, $adminId)
    {
        if (empty($merId) || empty($adminId)) {
            throw new Exception("参数有误");
        }

        if ($auditResult == 2 && $auditMessage == '') {
            throw new Exception("请填写审核备注");
        }

        $extends = $this->merExtendsMod->getOne(['mer_id' => $merId]);
        if (!$extends) {
            throw new Exception("未查询到商家申请记录");
        }
        $update = [
            'audit_result' => $auditResult,
            'audit_message' => $auditMessage,
            'audit_time' => time(),
            'wechat_live_auth' => $auditResult == 1 ? 1 : 0,
        ];
        $this->merExtendsMod->updateThis(['mer_id' => $merId], $update);
        //审核成功 推送商家站内信
        if($auditResult == 1){
            $message = L_('平台微信小程序直播权限已审核通过，欢迎使用。');
        }else{
            $message = L_('平台微信小程序直播权限已驳回，点击查看具体原因。');
        }
        $noticeData = [
            'business'=>'wechat_live_audit',
            'type'=>3,
            'mer_id'=>$merId,
            'store_id'=>0,
            'goods_id'=>0,
            'is_warn_only_once'=>1,
            'create_time'=>time(),
            'title' => L_('微信小程序直播申请通知'),
            'content' => $message,
        ];
        (new WarnNotice())->insert($noticeData);

        return true;
    }

    public function changeLiveAuth($merId, $liveAuth, $liveMessage)
    {
        $rs = $this->merExtendsMod->updateThis(['mer_id' => $merId], ['wechat_live_auth' => $liveAuth, 'wechat_live_auth_message' => $liveMessage]);
        if (!$rs) {
            throw new Exception("未查询到商家申请记录");
        }

        if ($liveAuth == 2) {
            $message = L_('当前商家平台微信小程序直播权限已禁用，关联主播已解除。禁用原因：') . $liveMessage;

            //清空商家主播
            (new LiveAnchorService())->delAllRolesByMerid($merId);
        } else {
            $message = L_('当前商家平台微信小程序直播权限已恢复使用，请严格遵守国家法律法规。');
        }
        $noticeData = [
            'business' => 'wechat_live_audit',
            'type' => 3,
            'mer_id' => $merId,
            'store_id' => 0,
            'goods_id' => 0,
            'is_warn_only_once' => 1,
            'create_time' => time(),
            'title' => L_('微信小程序直播通知'),
            'content' => $message
        ];
        (new WarnNotice())->insert($noticeData);
        return true;
    }
}
