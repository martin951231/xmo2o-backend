<?php

declare(strict_types=1);

namespace app\building\model;

use Exception;
use think\Model;

//商场用户权益
class ShoppingMallBenefits extends Model
{
    //线下权益
    const TYPE_ONLINE = 1;
    //停车权益
    const TYPE_PARK = 2;

    //添加、编辑权益
    public function saveBenefit($params)
    {
        $rules = [
            'title' => 'require|chsAlphaNum',
            'type' => 'require|in:1,2',
            'icon' => 'require|url',
            'description' => 'require',
        ];
        if (validate($rules)->check($params)) {
            $update = [
                'title' => $params['title'],
                'type' => $params['type'],
                'icon' => $params['icon'],
                'description' => $params['description'],
            ];

            $id = $params['id'] ?? 0;
            if ($id) {
                if (!$this->where('id', $id)->find()) {
                    throw new Exception('权益不存在');
                }

                //修改
                $this->where('id', $id)->update($update);
                return $id;
            } else {
                //新增
                return $this->insertGetId($update);
            }
        }
    }

    //删除权益
    public function deleteBenefit($id)
    {
        if ($id < 1) {
            throw new Exception('参数错误');
        }
        return $this->where('id', $id)->delete();
    }
}
