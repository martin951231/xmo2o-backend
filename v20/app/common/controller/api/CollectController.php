<?php
/**
 * 个人中心收藏
 */
namespace app\common\controller\api;


use app\common\model\service\MerchantUserRelationService;

class CollectController extends ApiBaseController
{    
	/**
	* 个人中心收藏列表
	* User: hengtingmei
	* Date: 2021/5/18
	*/
	public function getCollectList(){
		$this->checkLogin();
		$param = $this->request->param();
		$res = (new MerchantUserRelationService())->getCollectList($param);
		return api_output(0, $res);
	}

	    
	/**
	* 个人中心收藏列表头部tab
	* User: hengtingmei
	* Date: 2021/5/18
	*/
	public function getCollectTab(){
		$this->checkLogin();
		$param = $this->request->param();
		$res = (new MerchantUserRelationService())->getCollectTab($param);
		return api_output(0, $res);
	}
	

	/**
     * 个人中心收藏列表
     * User: hengtingmei
	 * Date: 2021/5/18
     */
	public function delCollect(){
		$this->checkLogin();
		$param = $this->request->param();
		$param['collect_id'] = $this->request->param('collect_id', '0', 'intval');
		$res = (new MerchantUserRelationService())->delCollect($param);
		return api_output(0, $res);
	}
}