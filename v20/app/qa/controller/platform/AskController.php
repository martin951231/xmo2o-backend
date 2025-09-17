<?php

namespace app\qa\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\MerchantService;
use app\qa\model\service\AskService;

class AskController extends AuthBaseController
{
    public function getAll()
    {
        $param['mer_id'] = $this->request->param('mer_id', 0, 'intval');
        $param['ask_type'] = $this->request->param('ask_type', 0, 'intval');
        $param['page'] = $this->request->param('page', 1, 'intval');
        $param['page_size'] = $this->request->param('page_size', 20, 'intval');
        $param['keyword'] = $this->request->param('keyword', '', 'trim');
        $param['is_del'] = 0;
        $result = (new AskService())->getAll($param);
        return api_output(0, $result);
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $result = (new AskService())->deleteByIds([$id]);
        return api_output(0, $result);
    }

    public function searchMerchant()
    {
        $keyword = $this->request->param('keyword', '', 'trim');
        $result = (new MerchantService())->getMerBySearchName($keyword, 50);
        return api_output(0, $result);
    }

    public function askDetail(){
        $id = $this->request->param('id', 0, 'intval');
        $result = (new AskService())->askDetail($id);
        return api_output(0, $result);
    }
}

?>