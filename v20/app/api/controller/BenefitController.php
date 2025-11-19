<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\model\service\building\UserService;
use app\building\model\ShoppingMallBenefits;

//会员权益
class BenefitController extends BaseController
{
    //权益列表
    public function lists()
    {
        $result = (new UserService())->getBenefitLists();
        return $this->apiOutput(1000, ['result' => $result], '');
    }

    //添加、编辑权益
    public function save()
    {
        $parsms['id'] = $this->request->param('id', 0, 'intval');
        $parsms['title'] = $this->request->param('title', '', 'trim');
        $parsms['type'] = $this->request->param('type', 0, 'intval');
        $parsms['icon'] = $this->request->param('icon', '', 'trim');
        $parsms['description'] = $this->request->param('description', '', 'trim');
        try {
            $id = (new ShoppingMallBenefits())->saveBenefit($parsms);
            return $this->apiOutput(1000, ['id' => $id], '');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }

    //删除权益
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        try {
            (new ShoppingMallBenefits())->deleteBenefit($id);
            return $this->apiOutput(1000, [], '删除成功');
        } catch (\Exception $e) {
            return $this->apiOutput(1001, [], $e->getMessage());
        }
    }
}
