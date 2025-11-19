<?php
/**
 * 商城营销活动 -- 砍价
 */
namespace app\mall\controller\merchant;

use app\mall\model\service\activity\MallNewBargainActService;
use app\merchant\controller\merchant\AuthBaseController;

class MallBargainController extends AuthBaseController
{
    public $merId;

    public function initialize()
    {
        parent::initialize();
        $this->merId = $this->merchantUser['mer_id'] ?? 0;
    }
    /**
     * 获取砍价列表
     * User: chenxiang
     * Date: 2020/9/15 16:04
     */
    public function getBargainList() {
        //店铺id
        $store_id = $this->request->param('store_id', '', 'intval');
        if ($store_id < 1) {
            return api_output(1001, [], '店铺ID不存在');
        }

        $param = $this->request->param();
        $mallBargainService = new MallNewBargainActService();
        try {
            $result = $mallBargainService->getBargainList($store_id, $param);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 添加活动 和 编辑活动
     * User: chenxiang
     * Date: 2020/10/12 15:24
     * @return \json
     */
    public function addBargain() {
        $merId = $this->merchantUser['mer_id'] ?? 0;

        if($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }

        $param = $this->request->param();
        $param['mer_id'] = intval($merId);
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['sort'] = 10;
        if($param['store_id'] < 1) {
            throw new \think\Exception('店铺ID不存在');
        }

        try {
            $res = (new MallNewBargainActService())->addBargainAct($param);

            if ($res['status'] == 0) {
                return api_output(1000, [], $res['msg']);
            } else {
                return api_output(1003, [], $res['msg']);
            }

        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 查看-页面
     * User: chenxiang
     * Date: 2020/11/9 8:55
     * @return \json
     */
    public function editDetail()
    {
        $id = $this->request->param('id', '', 'intval'); //表id

        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        try {
            $mallNewBargainActService = new MallNewBargainActService();
            $result = $mallNewBargainActService->getInfoById($id);
            return api_output(1000, $result, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 失效操作
     * User: chenxiang
     * Date: 2020/10/13 18:48
     * @return \json
     */
    public function changeState() {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['status'] = $this->request->param('status', 2, 'intval');
        try {
            (new MallNewBargainActService())->changeState($param, $id);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 软删除 活动
     * User: chenxiang
     * Date: 2020/10/13 18:53
     * @return \json
     */
    public function del() {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['is_del'] = $this->request->param('is_del', 1, 'intval');
        try {
            (new MallNewBargainActService())->del($param, $id);
            return api_output(1000, []);
        } catch(\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}