<?php

namespace app\mall\model\service;

use app\mall\model\db\MallProductBrands;

/**
 * 品牌管理
 */
class BrandsService
{

    public $brandsModel;

    public function __construct()
    {
        $this->brandsModel = new MallProductBrands();
    }

    public function getLists($param)
    {
        $pageSize = $param['page_size'] ?? 10;
        $where = [];
        if (isset($param['keyword']) && $param['keyword']) {
            $where[] = ['name', 'like', '%' . trim($param['keyword']) . '%'];
        }
        return $this->brandsModel->where($where)->paginate($pageSize)->each(function ($item) {
            $item['logo'] = replace_file_domain($item['logo']);
        });
    }

    public function save($param)
    {
        $id = $param['id'] ?? 0;
        if (empty($param['name'])) {
            throw new \think\Exception('请输入品牌名称');
        }
        if (empty($param['introduction'])) {
            throw new \think\Exception('品牌介绍');
        }
        if (empty($param['logo'])) {
            throw new \think\Exception('请上传品牌logo');
        }


        $data = [
            'name' => $param['name'],
            'introduction' => $param['introduction'],
            'logo' => $param['logo'],
            'status' => $param['status'],
            'sort' => $param['sort'],
        ];
        if ($id) {
            return $this->brandsModel->where('id', $id)->update($data);
        } else {
            return $this->brandsModel->insert($data);
        }
    }

    public function delete($id)
    {
        return $this->brandsModel->where('id', $id)->delete();
    }

    public function changeStatus($id)
    {
        $info = $this->brandsModel->where('id', $id)->find();
        $info->status = $info->status == 1 ? 0 : 1;
        return $info->save();
    }

    public function getBrandsSelect()
    {
        $brands = $this->brandsModel->where('status',1)->field('id as value,name as title,status')->select();
        return $brands;
    }
}
