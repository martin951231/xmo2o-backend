<?php

namespace app\qa\controller\api;

use app\qa\model\service\AskService;

class IndexController extends ApiBaseController
{
    /**
     * 问大家
     * @return void
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function askLabels()
    {
        $storeId = $this->request->param('store_id', 0, 'intval');
        $label = (new AskService)->getLabelsByStoreId($storeId);
        return api_output(0, $label);
    }

    /**
     * 问答列表
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function askList()
    {
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['label_id'] = $this->request->param('label_id', 0, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['page_size'] = $this->request->param('page_size', 20, 'intval');
        $param['is_del'] = 0;
        $label = (new AskService())->getAskList($param, ['a.index_show' => 'desc', 'a.reply_count' => 'desc']);
        return api_output(0, $label);
    }

    /**
     * 我要提问/回复
     * @author: 张涛
     * @date: 2021/05/17
     */
    public function saveAsk()
    {
        $this->checkLogin();
        $param['content'] = $this->request->param('content', '', 'trim');
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['uid'] = $this->request->log_uid;
        $param['fid'] = $this->request->param('fid', 0, 'intval');
        $param['image'] = $this->request->param('image', []);
        $label = (new AskService)->saveAsk($param);
        return api_output(0, $label);
    }

    /**
     * 问答详情
     * @author: 张涛
     * @date: 2021/06/01
     */
    public function askDetail()
    {
        $id = $this->request->param('id', 0, 'intval');
        $detail = (new AskService)->askDetail($id);
        $ask = [];
        $reply = [];

        foreach ($detail as $d) {
            $isAsk = $d['is_ask'];
            unset($d['is_ask']);
            if ($isAsk) {
                $ask = $d;
            } else {
                $reply[] = $d;
            }
        }
        return api_output(0, ['ask' => $ask, 'reply' => $reply]);
    }
}
