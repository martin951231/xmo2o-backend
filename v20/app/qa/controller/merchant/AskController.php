<?php

namespace app\qa\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\qa\model\service\AskService;
use think\Exception;

class AskController extends AuthBaseController
{
    /**
     * 提问列表
     * @author: 张涛
     * @date: 2021/05/18
     */
    public function lists()
    {
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['index_show'] = $this->request->param('index_show', -1, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['page_size'] = $this->request->param('page_size', 20, 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['mer_id'] = $this->merId;
        $param['is_del'] = 0;
        $param['fid'] = 0;

        $result = (new AskService())->getLists($param);
        return api_output(0, $result);
    }

    /**
     * 设置/取消首页展示
     * @author: 张涛
     * @date: 2021/05/18
     */
    public function setIndexShow()
    {
        $id = $this->request->param('id', 0, 'intval');
        $indexShow = $this->request->param('index_show', -1, 'intval');
        $result = (new AskService())->setIndexShow($id, $indexShow);
        return api_output(0, $result);
    }

    /**
     * 保存问答标签
     * @author: 张涛
     * @date: 2021/05/19
     */
    public function saveLabels()
    {
        try {
            $merId = $this->merId;
            $labels = $this->request->param('label_names', []);
            $result = (new AskService())->saveLabels($merId, $labels);
            return api_output(0, $result);
        } catch (Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 获取标签
     * @author: 张涛
     * @date: 2021/05/19
     */
    public function getLabels()
    {
        $merId = $this->merId;
        $labels = (new AskService())->getLabelsByMerId($merId);
        return api_output(0, $labels);
    }

    /**
     * 问答关联标签
     * @author: 张涛
     * @date: 2021/05/19
     */
    public function saveAskLabel()
    {
        $id = $this->request->param('id', 0, 'intval');
        $labelId = $this->request->param('label_id', 0, 'intval');
        $result = (new AskService())->saveAskLabel($id, $labelId);
        return api_output(0, $result);
    }

    public function askDetail(){
        $id = $this->request->param('id', 0, 'intval');
        $result = (new AskService())->askDetail($id);
        return api_output(0, $result);
    }
}

