<?php

namespace app\common\controller\api;

use app\common\model\service\order\SystemOrderService;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\MerchantService;
use app\common\model\service\ReplyService;
use app\common\model\service\UserService;
use app\merchant\model\service\store\MerchantCategoryService;
use app\common\model\service\AreaService;


class ReplyController extends ApiBaseController
{
	public function myReply(){
		$this->checkLogin();
		$output = [
			'orders' => [],		
		];

		$uid = $this->request->log_uid;
		$orderType = $this->request->param('order_type', null, 'trim');

		//获取我未评价的订单
		$where = [
			['system_status', '=', 2],
			['paid', '=', 1],
			['is_del', '=', 0],
			['uid', '=', $uid],
		];
		if (!is_null($orderType)) {
			$where[] = ['type', '=', $orderType];
		} else {
			$where[] =['type', 'in', ['group', 'shop', 'dining']];
		}

		$SystemOrder = new SystemOrderService();
		$orders = $SystemOrder->getSome($where, 'type,order_id,mer_id,store_id', 'create_time desc', 0, 10);
		$MerchantStoreService = new MerchantStoreService();
		$MerchantService = new MerchantService();
		if($orders){
			$orders = $orders->toArray();
			foreach ($orders as $key => $value) {
				$temp = [];
				$store = $MerchantStoreService->getStoreInfo($value['store_id']);
				$temp['img'] = $store['image'] ?? '';
				$temp['store_name'] = $store['name'] ?? '';
				$temp['order_type'] = $value['type'];
				$temp['order_id'] = $value['order_id'];
				$merchant = $MerchantService->getMerchantByMerId($value['mer_id']);
				$temp['merchant_name'] = $merchant['name'] ?? '';
				$temp['comment_url'] = '';
				switch ($value['type']) {
					case 'shop':
						$temp['comment_url'] = cfg("site_url") . "/packapp/plat/pages/my/make_comments?order_id=".$value['order_id'];
						break;
					case 'dining':
						//新版餐饮找前端要一下
						$temp['comment_url'] = get_base_url().'pages/foodshop/order/comments?order_id='.$value['order_id'];
						break;
					case 'group':
						//新版团购找前端要一下
						$temp['comment_url'] = cfg("site_url") . '/wap.php?g=Wap&c=My&a=group_feedback&order_id='.$value['order_id'];
						break;					
				}
				$output['orders'][] = $temp;
			}
		}

		return api_output(0, $output);
	}

	public function getList(){
		$this->checkLogin();
		$output = [
			'list' => [],			
		];

		$uid = $this->request->log_uid;
		$orderType = $this->request->param('order_type', null, 'trim');
		$page = $this->request->param('page', 1, 'intval');
		$pageSize = 10;

		$Reply = new ReplyService();

		$where = [
			['uid', '=', $uid],
			['is_del', '=', 0],	
		];

		if (!is_null($orderType)) {
			$maps = [
				'shop' => 3,
				'appoint' => 2,
				'group' => 0
			];
			$where[] = ['order_type', '=', $maps[$orderType] ?? null];
		} else {
			$where[] = ['order_type', 'in', ['0', '3', '4']];
		}

		$data = $Reply->getSome($where, '*', 'add_time desc', ($page-1)*$pageSize, $pageSize);
		$MerchantStoreService = new MerchantStoreService();
		if($data){
			$data = $data->toArray();
			$UserService = new UserService();
			foreach ($data as $key => $value) {
				$temp = [];
				$temp['reply_id'] = $value['pigcms_id'];
				$user = $UserService->getUser($value['uid']);
				$temp['headimg'] = $user['avatar'];
				$temp['nickname'] = $user['nickname'];
				$temp['score'] = $value['score'];
				$temp['is_consume'] = $value['is_consume'];
				$temp['is_good'] = $value['is_good'];
				$temp['add_time'] = date('Y-m-d', $value['add_time']);
				$temp['comment'] = $value['comment'];
				$temp['self_zan'] = $value['self_zan'];
				$reply_pic = [];
				if(!empty($value['reply_pic'])){
					$reply_pic = explode(';', $value['reply_pic']);
					foreach ($reply_pic as $kp => $pic) {
						$reply_pic[$kp] = file_domain().$pic;
					}
				}
				$temp['images'] = $reply_pic;
				$temp['zan'] = $value['zan'];
				$temp['view'] = $value['view'];
				$temp['merchant_reply_content'] = $value['merchant_reply_content'];
				$temp['store_id'] = '';
				$temp['store_img'] = '';
				$temp['store_name'] = '';
				$temp['store_cate'] = [];
				$temp['store_area'] = [];
				if($value['store_id']){
					$store = $MerchantStoreService->getStoreInfo($value['store_id']);
					$temp['store_id'] = $value['store_id'];
					$temp['store_img'] = $store['image'];
					$temp['store_name'] = $store['name'];
					$temp['store_url'] =cfg("site_url")."/packapp/platn/pages/store/v1/home/index?store_id=".$value['store_id'];
					$cate = (new MerchantCategoryService())->getOne(['cat_id'=>$store['cat_fid']]);
					$temp['store_cate'][] = $cate['cat_name'] ?? '';
					$cate = (new MerchantCategoryService())->getOne(['cat_id'=>$store['cat_id']]);
					$temp['store_cate'][] = $cate['cat_name'] ?? '';
					$area = (new AreaService())->getAreaByAreaId($store['area_id']);
					$temp['store_area'][] = $area['area_name'] ?? '';
					$area = (new AreaService())->getAreaByAreaId($store['circle_id']);
					$temp['store_area'][] = $area['area_name'] ?? '';
				}
				$output['list'][] = $temp;
			}
		}
		return api_output(0, $output);
	}

	public function zan(){
		$this->checkLogin();

		$uid = $this->request->log_uid;
		$reply_id = $this->request->param('reply_id', 0, 'intval');
		$zan = $this->request->param('zan', 0, 'intval');
		$Reply = new ReplyService();

		$data = [
			'self_zan' => $zan
		];

		$where = [
			'pigcms_id' => $reply_id
		];
		if($zan == 0){
			$Reply->decrZan($reply_id);
		}
		else{
			$Reply->incrZan($reply_id);	
		}
		$Reply->updateThis($where, $data);
		return api_output(0, [], '成功');
	}

	public function delReply(){
		$this->checkLogin();

// $uid = 112358755;
		$uid = $this->request->log_uid;
		$reply_id = $this->request->param('reply_id', 0, 'intval');
		if(empty($reply_id)){
			return api_output_error(1003, "参数错误");
		}
		$Reply = new ReplyService();

		$where = [
			['uid', '=', $uid],
			['is_del', '=', 0],	
			['pigcms_id', '=', $reply_id],		
		];
		if($Reply->updateThis($where, ['is_del'=>1]) !== false){
			return api_output(0, [], '删除成功');
		}
		else{
			return api_output_error(1003, "参数失败");
		}
	}

    /**
     * 获取店铺评价
     * @author: 张涛
     * @date: 2021/05/31
     */
    public function storeReply()
    {
        $params['store_id'] = $this->request->param('store_id', 0, 'intval');
        $params['score_type'] = $this->request->param('score_type', 'all', 'trim');
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['group_id'] = $this->request->param('group_id', 0, 'intval');
        $params['page_size'] = $this->request->param('page_size', 20, 'intval');
		$params['uid'] = $this->request->log_uid;
        $lists = (new ReplyService())->getStoreReplyLists($params);
        return api_output(0, $lists);
    }

    /**
     * 获取评分数量
     * @author: 张涛
     * @date: 2021/05/31
     */
    public function storeScoreLevelCount()
    {
        $param['store_id'] = $this->request->param('store_id', 0, 'intval');
        $param['group_id'] = $this->request->param('group_id', 0, 'intval');
        $lists = (new ReplyService())->getStoreScoreLevelCount($param);
        return api_output(0, $lists);
    }


	/**
	 * 用户回复商家
	 */
	public function userReplyMerchant()
	{
		$this->checkLogin();
		$uid = $this->request->log_uid;
        $reply_id = $this->request->post('reply_id', 0, 'intval');
        $content = $this->request->post('content', '', 'trim');
        try {
            $service = new ReplyService();
            $arr = $service->userReplyMerchant($reply_id, $content, $uid);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error('1003', $e->getMessage());
        }
	}

	// 获取评价类型
	public function getTypeList()
	{
		$types = [];
		cfg('shop_alias_name') && $types[] = ['name' => cfg('shop_alias_name'), 'type' => 'shop'];
		cfg('appoint_alias_name') && $types[] = ['name' => cfg('appoint_alias_name'), 'type' => 'appoint'];
		cfg('mall_alias_name_new') && $types[] = ['name' => cfg('mall_alias_name_new'), 'type' => 'mall3'];
		cfg('group_alias_name') && $types[] = ['name' => cfg('group_alias_name'), 'type' => 'group'];
		return api_output(0, $types, 'success');
	}
}