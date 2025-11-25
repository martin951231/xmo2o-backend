<?php


namespace app\group\model\service;


use app\group\model\db\GroupRedemptionCode;

class GroupRedemptionCodeService
{
    public function getList($params)
    {
        $status = $params['status'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $search_type = $params['search_type'];
        $keywords = $params['keywords'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $is_export = $params['is_export'];
        $mer_id = $params['mer_id'];
        $page = $is_export ? 0 : $page;
        $where = [
            ['g.tuan_type', '=', 3],
            ['g.mer_id', '=', $mer_id]
        ];
        if ($keywords) {
            if ($search_type == 'store_name') {
                $where[] = ['s.name', 'like', '%' . $keywords . '%'];
            } elseif ($search_type == 'order_no') {
                $where[] = ['o.real_orderid', '=', $keywords];
            } elseif ($search_type == 'code') {
                $where[] = ['c.code', '=', $keywords];
            } elseif ($search_type == 'goods_name') {
                $where[] = ['g.name', 'like', '%' . $keywords . '%'];
            } elseif ($search_type == 'phone') {
                $where[] = ['u.phone', '=', $keywords];
            } elseif ($search_type == 'nickname') {
                $where[] = ['u.nickname', 'like', '%' . $keywords . '%'];
            }
        }
        if ($start_date && $end_date) {
            $start_date = strtotime($start_date);
            $end_date = strtotime($end_date . ' 23:59:59');
            $where[] = ['o.add_time', 'between', [$start_date, $end_date]];
        }
        if ($status > -1) {
            $where[] = ['c.status', '=', $status];
        }
        $field = 'c.*,g.name as goods_name,s.name as store_name,o.real_orderid,o.pay_time,u.nickname,u.phone';
        $list = (new GroupRedemptionCode())->getList($where, $field, $page, $pageSize);
        $data = $page > 0 ? $list['data'] : $list;
        foreach ($data as &$item) {
            $item['pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '';
            $item['status_txt'] = $item['status'] ? '已售' : '未售';
            $item['store_name'] = $item['store_name'] ?: '';
            $item['real_orderid'] = $item['real_orderid'] ?: '';
            $item['nickname'] = $item['nickname'] ?: '';
            $item['phone'] = $item['phone'] ?: '';
        }
        if ($page > 0) {
            $list['data'] = $data;
            //搜索列表
            $list['search_type'] = [
                ['key' => 'code', 'label' => '券序列码'],
                ['key' => 'goods_name', 'label' => '团购名称'],
                ['key' => 'store_name', 'label' => '店铺名称'],
                ['key' => 'order_no', 'label' => '卡券订单号'],
                ['key' => 'phone', 'label' => '用户手机号'],
                ['key' => 'nickname', 'label' => '用户昵称'],
            ];
            //状态列表
            $list['status_arr'] = [
                ['key' => '-1', 'label' => '全部'],
                ['key' => '0', 'label' => '未售'],
                ['key' => '1', 'label' => '已售'],
            ];
            return $list;
        } else {
            $title = '虚拟兑换码列表';
            $xlsCell = [
                ['goods_name', '团购名称'],
                ['code', '券序列码'],
                ['status_txt', '状态'],
                ['store_name', '店铺名称'],
                ['real_orderid', '卡券订单号'],
                ['nickname', '用户昵称'],
                ['phone', '用户手机号'],
                ['pay_time', '支付时间'],
            ];
            $fileName = date("Y-m-d", time()) . '-' . time();
            $exportResult = export_excel($title, $xlsCell, $data, $fileName);
            return ['file_url' => $exportResult['url']];
        }
    }
}