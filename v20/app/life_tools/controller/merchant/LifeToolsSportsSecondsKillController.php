<?php

/**
 * 体育限时秒杀活动
 */
namespace app\life_tools\controller\merchant;

use app\life_tools\model\service\sport_second_kill\LifeToolsSportsSecondsKillService;
use app\merchant\controller\merchant\AuthBaseController;

class LifeToolsSportsSecondsKillController extends AuthBaseController
{
    /**
     * 获取限时优惠活动列表
     * @return \json
     */
    public function getSecondsKillList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;
        if ($merId < 0) {
            return api_output(1001, [], '商家ID不存在');
        }
        $param['mer_id'] = $merId;
        //查询条件
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['status'] = $this->request->param('status', 3, 'intval');
      
        $result = (new LifeToolsSportsSecondsKillService())->getSecondsKillList($param);
        return api_output(1000, $result, 'success');
    }

    /**
     * 添加/编辑
     * @return \json
     */
    public function saveSecondsKill()
    {
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['type'] = 'limited';
        $param['sort'] = 10;
        $param['name'] = $this->request->param('name', '', 'trim');
        $param['id'] = $this->request->param('id', 0, 'intval');
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['start_time'] = $this->request->param('start_time', '', 'trim');
        $param['end_time'] = $this->request->param('end_time', '', 'trim');
        $param['is_discount_share'] = $this->request->param('is_discount_share', 2, 'intval');
        $param['notice_type'] = $this->request->param('notice_type', 1, 'intval');
        $param['notice_time'] = $this->request->param('notice_time', 0, 'intval');
        $param['stock_type'] = $this->request->param('stock_type', 1, 'intval');
       
        $res = (new LifeToolsSportsSecondsKillService())->saveSecondsKill($param);
        return api_output(0, $res);      
    }

    /**
     * 获得秒杀详情
     * @return \json
     */
    public function getSecondsKillDetail()
    {
        $id = $this->request->param('id', '', 'intval'); //限时优惠表id
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }

        $result = (new LifeToolsSportsSecondsKillService())->getSecondsKillDetail($id);
        return api_output(1000, $result, 'success');
    }


    /**
     * 失效操作
     * @return \json
     */
    public function ChangeSportsSecondsKill()
    {
        $id = $this->request->param('id', '', 'intval');
        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['status'] = $this->request->param('status', 2, 'intval');
        try {
            (new LifeToolsSportsSecondsKillService())->ChangeSportsSecondsKill($param, $id);
            return api_output(1000, []);
        } catch (\Exception $e) {
            return api_output_error(1003, [], $e->getMessage());
        }
    }


    /**
     * 删除操作
     * @return \json
     */
    public function delSecondsKill()
    {

        $id = $this->request->param('id', '', 'intval');

        if ($id < 1) {
            return api_output(1001, [], 'ID不存在');
        }
        $param['is_del'] = 1;
       
        $where = [
            'act_id' => $id,
            'type' => 'limited'
        ];
        $res = (new LifeToolsSportsSecondsKillService())->updateThis($where, $param);
           
        return api_output(0, $res);      
    }
}