<?php

namespace app\building\model;

use Exception;
use think\facade\Db;
use think\Model;

class ShoppingMallParkingSetting extends Model
{

    public function saveParkingDiscount($params)
    {
        if (empty($params['mall_id'])) {
            throw new \Exception("请选择商场");
        }

        $rules = [
            'mall_id' => 'require|integer|gt:0',
            'level' => 'require|integer|gt:0',
            'parking_config' => 'require|array'
        ];
        if (validate($rules)->check($params)) {
            $update = [
                'mall_id' => $params['mall_id'],
                'level' => $params['level'],
                'parking_config' => json_encode($params['parking_config'], JSON_UNESCAPED_UNICODE)
            ];

            $old = $this->where('mall_id', $params['mall_id'])->where('level', $params['level'])->find();
            if ($old) {
                //修改
                return $this->where('id', $old->id)->update($update);
            } else {
                //新增
                return $this->insert($update);
            }
        }
    }

    public function userLevels($mallId)
    {
        $levels = Db::name('user_level')->field('id,level,lname,icon,validity')->select()->each(function ($item) {
            $item['icon'] = $item['icon'] ? replace_file_domain($item['icon']) : '';
            return $item;
        })->toArray();
        $config = $this->where('mall_id', $mallId)->column('parking_config', 'level');
        foreach ($levels as &$level) {
            $level['parking_config'] = !empty($config[$level['level']]) ? json_decode($config[$level['level']], true) : [];
            $txtArr = [];
            if (empty($level['parking_config'])) {
                $level['parking_config_txt'] = '未配置';
            } else {
                foreach ($level['parking_config'] as $item) {
                    if ($item['hours']  > 0 && $item['count'] > 0) {
                        $txtArr[] = sprintf("【%s】可领取【%s】张【%s】小时停车券", $item['title'], $item['count'], $item['hours']);
                    }
                }
            }
            $level['parking_config_txt'] = $txtArr;
        }
        return $levels;
    }
}
