<?php

namespace app\building\model;

use Exception;
use think\Model;

class ShoppingMallWifi extends Model
{
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '关闭', 1 => '开启'];
        return $status[$data['status']];
    }

    public function getLists($params)
    {
        $where = [];

        if (!empty($params['mall_id'])) {
            $where[] = ['mall_id', '=', $params['mall_id']];
        }

        $lists = $this->where($where)->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']]);

        return $lists;
    }

    public function changeWifiStatus($id)
    {
        $wifi = $this->find($id);
        if (empty($wifi)) {
            throw new \Exception('WiFi配置不存在');
        }
        $wifi->status = $wifi->status == 1 ? 0 : 1;
        $wifi->save();
        return true;
    }

    public function saveWifi($params)
    {
        if (empty($params['mall_id'])) {
            throw new \Exception("请选择商场");
        }

        $rules = [
            'mall_id' => 'require|integer',
            'ssid' => 'require',
            'password' => 'require',
            'status' => 'require|integer',
        ];
        if (validate($rules)->check($params)) {
            $update = [
                'mall_id' => $params['mall_id'],
                'ssid' => $params['ssid'],
                'password' => $params['password'],
                'status' => $params['status'],
            ];

            $id = $params['id'] ?? 0;
            if ($id) {
                //修改
                return $this->where('id', $id)->update($update);
            } else {
                //新增
                return $this->insert($update);
            }
        }
    }


    public function deleteWifi($id)
    {
        if ($id < 1) {
            throw new Exception('参数错误');
        }
        return $this->where('id', $id)->delete();
    }
}
