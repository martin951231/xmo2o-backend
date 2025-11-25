<?php
namespace app\recruit\controller\api;

use app\recruit\model\service\JobCollectService;

class JobCollectController extends ApiBaseController
{
    /**
     * 我的已收藏职位列表
     */
    public function recruitJobCollectList(){
        $uid = $this->_uid;
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 20, 'intval');
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new JobCollectService())->recruitJobCollectList($uid,'id DESC','*', $page, $pageSize);
            return api_output(0, $list);
        }
    }
}