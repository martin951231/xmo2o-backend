<?php
/**
 * 用户收藏公司
 */
namespace app\recruit\controller\api;

use app\recruit\model\service\CompanyUserCollectService;
use app\common\model\db\Area;

class CompanyUserCollectController extends ApiBaseController
{
	//我的收藏公司列表
	public function companyUserCollectList(){
		$uid = $this->_uid;
        $page = $this->request->param('page', 1, 'intval');
        $pageSize = $this->request->param('page_size', 20, 'intval');
        if (!$uid) {
            return api_output_error(1002, L_('请登录'));
        } else {
            $list = (new CompanyUserCollectService())->companyUserCollectList($uid, 'id DESC', '*', $page, $pageSize);
            return api_output(0, $list);
        }
	}
}