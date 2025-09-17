<?php
/**
 * 商城营销活动 -- n元n件
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/10/22 15:48
 */

namespace app\mall\controller\merchant;

use app\mall\model\service\activity\MallReachedActService;
use app\merchant\controller\merchant\AuthBaseController;

class MallReachedController extends AuthBaseController
{
    public $merId;

    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
    }

    /**
     * 获取 n元n件 活动列表
     * User: chenxiang
     * Date: 2020/10/22 15:54
     */
    public function getReachedList() {
        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');
        if ($store_id < 1) {
            return api_output(1001, [], '店铺ID不存在');
        }
        //每页页数
        $param['pageSize'] = $this->request->param('pageSize', '10', 'intval');
        //页码
        $param['page'] = $this->request->param('page', '1', 'intval');

        //查询条件
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['status'] = $this->request->param('status', 3);

        $mallReachedService = new MallReachedActService();
        try {
            $result = $mallReachedService->getReachedActList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }


    /**
     * 添加 和 编辑
     * User: chenxiang
     * Date: 2020/10/22 17:46
     * @return \json
     */
    public function addReached() {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param = $this->request->param();
        $param['id'] = $this->request->param('id')??0;
        $param['mer_id'] = $merId;
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');

        $param['sort'] = 10;
        if($param['store_id'] < 1) {
            throw new \think\Exception('店铺ID不存在');
        }
        try {
            $res = (new MallReachedActService())->addReachedAct($param);

            if ($res['status'] == 0) {
                return api_output(1000, [], $res['msg']);
            } else {
                return api_output(1003, [], $res['msg']);
            }
        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }

    }


    /**
     * 修改/查看页面
     * User: chenxiang
     * Date: 2020/11/10 14:17
     * @return \json
     */
    public function edit()
    {
        $id = $this->request->param('id', '', 'intval'); //表id

        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        try {
            $mallReachedActService = new MallReachedActService();
            $result = $mallReachedActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }



    /**
     * 修改活动状态
     * User: chenxiang
     * Date: 2020/10/22 17:47
     * @return \json
     */
    public function changeState() {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['status'] = $this->request->param('status', 2, 'intval');
        try {
            (new MallReachedActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


    /**
     * 软删除 活动
     * User: chenxiang
     * Date: 2020/10/22 17:47
     * @return \json
     */
    public function del() {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['is_del'] = $this->request->param('is_del', 1, 'intval');
        try {
            (new MallReachedActService())->del($param, $id);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


}