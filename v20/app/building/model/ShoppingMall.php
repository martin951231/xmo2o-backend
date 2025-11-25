<?php

namespace app\building\model;

use app\building\service\ShoppingMallBuildingService;
use think\facade\Db;
use think\Model;

class ShoppingMall extends Model
{
	const SELF_MALL = 1;//1 系统创建
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '关闭', 1 => '开启'];
        return $status[$data['status']];
    }

    public function getLists($params)
    {
        $where = [];

        if (!empty($params['name'])) {
            $where[] = ['name', 'like', '%' . $params['name'] . '%'];
        }
		
		if (!empty($params['mall_ids'])) {
            $where[] = ['id', 'IN', $params['mall_ids']];
        }

        $lists = $this->where($where)->paginate($params['page_size'] ?? 10)->each(function ($item) {
            $page = 'pages/shoppingMall/index?mall_id=' . $item->id;
            $item->h5_url = get_base_url($page);
            $item->h5_qrcode = cfg('site_url') . '/index.php?g=Index&c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($item->h5_url);
            $item->wxapp_qrcode = cfg('site_url') . '/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page=' . urlencode($page);
            return $item;
        });

        return $lists;
    }

    public function changeStatus($id)
    {
        $mall = $this->find($id);
        if (empty($mall)) {
            throw new \Exception('商场记录不存在');
        }
        $mall->status = $mall->status == 1 ? 0 : 1;
        $mall->save();
        return true;
    }

    public function getDetail($id)
    {
        $mall = $this->where('id', $id)->append(['status_text'])->findOrEmpty()->toArray();
        return $mall;
    }

    public function getBindStoreLists($params)
    {
        $fields = 's.store_id,s.name AS store_name,s.status,s.building,s.floor,s.room,s.building_id,s.floor_id,s.room_id';
        $where = [
            ['m.id', '=', $params['mall_id']],
            ['s.status', '<>', 4]
        ];
        if (!empty($params['store_name'])) {
            $where[] = ['s.name', 'like', '%' . $params['store_name'] . '%'];
        }
        $lists = Db::name('merchant_store')->alias('s')->join('shopping_mall m', 's.mall_id = m.id')
            ->where($where)
            ->field($fields)
            ->paginate($params['page_size'] ?? 10)
            ->toArray();

        $mall_building = (new ShoppingMallBuildingService())->getBuilding($params['mall_id']);
        $lists['building'] = $mall_building['building'] ?? [];
        $lists['mall_is_self'] = $mall_building['mall_is_self'] ?? 0;
        return $lists;
    }

    public function getBindAdminUserLists($params)
    {
        $adminIds = Db::name('shopping_mall_bind_admin')->where('mall_id', $params['mall_id'])->column('admin_id');
        $fields = 'id,account,realname,phone,level,status';
        if ($adminIds) {
            $str = implode(',', $adminIds);
            $fields .= ",IF(FIND_IN_SET(id,'" . $str . "'),1,0) AS is_bind";
            $lists = Db::name('admin')->field($fields)->order(Db::raw("FIND_IN_SET(id,'" . $str . "') DESC"))->paginate($params['page_size'] ?? 10);
        } else {
            $fields .= ",0 AS is_bind";
            $lists = Db::name('admin')->field($fields)->order('id ASC')->paginate($params['page_size'] ?? 10);
        }
        return $lists;
    }

    public function changeBindStatus($mallId, $adminId)
    {
        if ($mallId < 0 || $adminId < 0) {
            throw new \Exception('参数错误');
        }

        $mall = $this->find($mallId);
        if (empty($mall)) {
            throw new \Exception('商场记录不存在');
        }

        $admin = Db::name('admin')->find($adminId);
        if (empty($admin)) {
            throw new \Exception('管理员记录不存在');
        }

        $bind = Db::name('shopping_mall_bind_admin')->where('mall_id', $mallId)->where('admin_id', $adminId)->find();
        if ($bind) {
            //去解绑
            Db::name('shopping_mall_bind_admin')->where('id', $bind['id'])->delete();
        } else {
            //去绑定
            Db::name('shopping_mall_bind_admin')->insert([
                'mall_id' => $mallId,
                'admin_id' => $adminId,
            ]);
        }
        return true;
    }
}
