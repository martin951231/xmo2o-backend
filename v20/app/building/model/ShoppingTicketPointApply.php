<?php

namespace app\building\model;

use think\facade\Db;
use think\Model;

class ShoppingTicketPointApply extends Model
{

    public function getLists($params)
    {
        $where = [];
        if (!empty($params['nickname'])) {
            $where[] = ['u.nickname', 'like', '%' . $params['nickname'] . '%'];
        }
        if (!empty($params['phone'])) {
            $where[] = ['u.phone', '=', $params['phone']];
        }
        if (!empty($params['real_orderid'])) {
            $where[] = ['a.real_orderid', '=', $params['real_orderid']];
        }
        if (isset($params['status'])) {
            $where[] = ['a.status', '=', $params['status']];
        }

        $fields = 'u.nickname,u.phone,m.name AS mall_name,a.*';
        $lists = $this->alias('a')
            ->join('user u', 'a.uid = u.uid', 'left')
            ->join('shopping_mall m', 'm.id = a.mall_id', 'left')
            ->where($where)
            ->field($fields)
            ->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
            ->each(function ($item, $key) {
                $item['ticket_pic'] = replace_file_domain($item['ticket_pic']);
                $payPicArr = array_filter(explode(';', $item['pay_pic']));
                $payPicArr = array_map(function ($pic) {
                    return replace_file_domain($pic);
                }, $payPicArr);
                $item['pay_pic'] = $payPicArr;
                $item['apply_time'] = date('Y-m-d H:i:s', $item['apply_time']);
                $item['review_time'] = $item['review_time'] > 0 ? date('Y-m-d H:i:s', $item['review_time']) : '';
                return $item;
            })
            ->toArray();
        return $lists;
    }

    public function getTicketPointDetail($id)
    {
        $fields = 'u.nickname,u.phone,a.*';
        $item = $this->alias('a')
            ->join('user u', 'a.uid = u.uid', 'left')
            ->where(['a.id' => $id])
            ->field($fields)
            ->findOrEmpty()
            ->toArray();
        if ($item) {
            $item['ticket_pic'] = replace_file_domain($item['ticket_pic']);
            $payPicArr = array_filter(explode(';', $item['pay_pic']));
            $payPicArr = array_map(function ($pic) {
                return replace_file_domain($pic);
            }, $payPicArr);
            $item['pay_pic'] = $payPicArr;
            $item['apply_time'] = date('Y-m-d H:i:s', $item['apply_time']);
            $item['review_time'] = $item['review_time'] > 0 ? date('Y-m-d H:i:s', $item['review_time']) : '';
            $item['order_price'] = $item['real_orderid']?Db::name('store_order')->where('orderid', $item['real_orderid'])->value('price'):0;
        }
        return $item;
    }
}
