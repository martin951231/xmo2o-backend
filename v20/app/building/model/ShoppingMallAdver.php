<?php

namespace app\building\model;

use think\Model;

//商场广告位
class ShoppingMallAdver extends Model
{

    public function getRecommendTypeTextAttr($value, $data)
    {
        $status = [0 => '无', 1 => '置顶', 2 => '穿插商品'];
        return $status[$data['recommend_type']];
    }

    public function getList($mallId, $catKey, $pageSize = 10)
    {
        $where = [
            ["mall_id", "=", $mallId],
            ["cat_key", "=", $catKey],
        ];
        $lists = $this->where($where)->append(['recommend_type_text'])->paginate($pageSize)->each(function ($item, $key) {
            $item['pic'] = replace_file_domain($item['pic']);
            $item['focus_pic'] = replace_file_domain($item['focus_pic']);
            $item['last_time'] = date("Y-m-d H:i:s", $item['last_time']);
            return $item;
        });
        return $lists;
    }

    public function getAdver($id)
    {
        if (empty($id)) {
            return [];
        }

        $where = [
            ["id", "=", $id],
        ];
        $arr = $this->where($where)->find();
        $arr['raw_pic'] = $arr['pic'];
        $arr['pic'] = replace_file_domain($arr['pic']);

        $arr['raw_focus_pic'] = $arr['focus_pic'];
        $arr['focus_pic'] = replace_file_domain($arr['focus_pic']);
        return $arr;
    }



    public function saveAdver($params)
    {
        if (empty($params['mall_id'])) {
            throw new \Exception("请选择商场");
        }

        $update = [
            'name' => $params['name'],
            'pic' => $params['pic'],
            'focus_pic' => $params['focus_pic'] ?? '',
            'url' => $params['url'],
            'wxapp_open_type' => $params['wxapp_open_type'],
            'wxapp_id' => $params['wxapp_id'],
            'wxapp_page' => $params['wxapp_page'],
            'app_open_type' => $params['app_open_type'],
            'ios_app_name' => $params['ios_app_name'],
            'ios_app_url' => $params['ios_app_url'],
            'android_app_name' => $params['android_app_name'],
            'android_app_url' => $params['android_app_url'],
            'app_wxapp_id' => $params['app_wxapp_id'],
            'app_wxapp_page' => $params['app_wxapp_page'],
            'sort' => $params['sort'],
            'status' => $params['status'],
            'cat_key' => $params['cat_key'],
            'mall_id' => $params['mall_id'],
            'last_time' => time(),
            'recommend_type' => $params['recommend_type'] ?? 0,
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

    public function delAdver($id)
    {
        return $this->where('id', $id)->delete();
    }
}
