<?php

declare(strict_types=1);

namespace app\wechatLive\model\service;

use app\merchant\model\db\MerchantExtends;
use app\wechatLive\model\db\WechatLiveAnchor;
use Exception;
use think\facade\Db;
use wechatLive\MiniLive;

class LiveAnchorService
{
    public $anchorMod = null;

    public $merExtendsMod = null;

    public function __construct()
    {
        $this->anchorMod = new WechatLiveAnchor();
        $this->merExtendsMod = new MerchantExtends();
    }

    public function saveRole($params)
    {
        if (empty($params['mer_id'])) {
            throw new Exception('非法请求');
        }
        if (empty($params['wechat_id'])) {
            throw new Exception('微信号不能为空');
        }

        $find = $this->anchorMod->where(['wechat_id' => $params['wechat_id']])->find();
        if ($find) {
            throw new Exception('当前微信号已绑定过主播，请更换');
        }

        Db::startTrans();
        try {
            $data = [
                'mer_id' => $params['mer_id'],
                'wechat_id' => $params['wechat_id'],
            ];


            $miniObj = new MiniLive();
            //创建成员并获取成员详细信息
            $miniObj->doAction(true, 'addRole', ['username' => $params['wechat_id'], 'role' => 2]);
            $roleInfo = $miniObj->doAction(false, 'getRole', ['role' => 2, 'keyword' => $params['wechat_id']]);
            $roleDetail = $roleInfo['list'][0] ?? [];
            if (empty($roleDetail)) {
                throw new Exception('查询主播信息失败，请重试');
            }
            $data['nickname'] = $roleDetail['nickname'] ?? '';
            $data['headingimg'] = $roleDetail['headingimg'] ?? '';
            $data['openid'] = $roleDetail['openid'] ?? '';
            $data['updateTimestamp'] = $roleDetail['updateTimestamp'] ?? 0;
            $this->anchorMod->add($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }


    public function getRolesByMerid($merid)
    {
        $roles = $this->anchorMod->where('mer_id', $merid)->withOutField('openid')->select()->toArray();
        return array_map(function ($r) {
            $r['updateTimestamp'] = date('Y-m-d H:i:s', $r['updateTimestamp']);
            return $r;
        }, $roles);
    }

    public function delRolesById($merid, $id)
    {
        $find = $this->anchorMod->where(['mer_id' => $merid, 'id' => $id])->find();
        if (empty($find)) {
            throw new Exception('非法请求');
        }
        $result = $this->anchorMod->where(['mer_id' => $merid, 'id' => $id])->delete();
        if ($result) {
            //删除成员
            (new MiniLive())->doAction(true, 'deleteRole', ['username' => $find['wechat_id'], 'role' => 2]);
        }
        return true;
    }

    public function delAllRolesByMerid($merid)
    {
        $roles = $this->anchorMod->where(['mer_id' => $merid])->select()->toArray();
        if ($roles) {
            $minlive = new MiniLive();
            foreach ($roles as $r) {
                $minlive->doAction(true, 'deleteRole', ['username' => $r['wechat_id'], 'role' => 2]);
            }
        }
        return true;
    }
}
