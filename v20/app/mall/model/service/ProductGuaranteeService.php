<?php

namespace app\mall\model\service;

use app\mall\model\db\MallProductGuarantee;

/**
 * 服务保障
 */
class ProductGuaranteeService
{

    public $productGuaranteeModel;

    public function __construct()
    {
        $this->productGuaranteeModel = new MallProductGuarantee();
    }

    public function getLists($param)
    {
        $pageSize = $param['page_size'] ?? 10;
        $where = [];
        if (isset($param['keyword']) && $param['keyword']) {
            $where[] = ['title', 'like', '%' . trim($param['keyword']) . '%'];
        }
        return $this->productGuaranteeModel->where($where)->paginate($pageSize);
    }

    public function save($param)
    {
        $id = $param['id'] ?? 0;
        if (empty($param['title'])) {
            throw new \think\Exception('请输入服务条款');
        }
        if (empty($param['content'])) {
            throw new \think\Exception('请输入服务描述');
        }

        $data = [
            'title' => $param['title'],
            'content' => $param['content'],
            'status' => $param['status'],
            'sort' => $param['sort'],
        ];
        if ($id) {
            return $this->productGuaranteeModel->where('id', $id)->update($data);
        } else {
            return $this->productGuaranteeModel->insert($data);
        }
    }

    public function delete($id)
    {
        return $this->productGuaranteeModel->where('id', $id)->delete();
    }   

    public function changeStatus($id){
        $info = $this->productGuaranteeModel->where('id', $id)->find();
        $info->status = $info->status == 1 ? 0 : 1;
        return $info->save();
    }


    public function getGuaranteeList()
    {
        return $this->productGuaranteeModel->where('status', 1)->field('id,title,content')->select();
    }
}
