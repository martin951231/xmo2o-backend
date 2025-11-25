<?php
/**
 * 团购优化-团购商品
 * Author: hengtingmei
 * Date Time: 2021/05/10 
 */

namespace app\group\controller\api;

use app\common\model\service\ResourceService;
use app\group\model\service\appoint\GroupAppointService;
use app\group\model\service\GroupService;
use app\group\model\service\order\GroupOrderService;
use app\group\model\service\StoreGroupService;

class GoodsController extends ApiBaseController
{
    /**
     * 获得团购商品详情
     */
    public function getGoodsDetail(){

        $uid = request()->log_uid;
        //如果用户登录了判断是否有抖音openid
        if ($uid > 0 && request()->param('Device-Id') == 'dyapp') {
            $user = (new \app\common\model\db\User())->getOne(['uid' => $uid]);
            if (empty($user) || empty($user->dy_openid)) {
                return api_output_error(1002, '用户未登录');
            }
        }

        $param = $this->request->param();
        $result = (new GroupService())->getGoodsDetail($param);
        $groupId = $param['group_id'] ?? 0;
        (new GroupService())->addHits($groupId);
        return api_output(1000, $result);

    }

    /**
     * 根据商品id得到拼团商品的正在拼团列表
     */
    public function getPinOrderListByGroupId()
    {
        $groupId = $this->request->param('group_id','','intval');
        $page = $this->request->param('page','1','intval');
        $pageSize = $this->request->param('pageSize','10','intval');
      
        $list = (new GroupOrderService())->getPinOrderListByGroupId($groupId,$page,$pageSize);
        return api_output(1000, $list);
        
    }

    /**
     * 根据商品id得到拼团商品的正在拼团列表
     */
    public function groupOrderDetail()
    {
        $orderId = $this->request->param('order_id',0,'intval');
        empty($this->_uid) && throw_exception('请登录后重试！');
        
        $order = (new GroupOrderService())->getPinOrderByOrderId($orderId, $this->_uid);
        
        return api_output(1000, $order);

    }

    /**
     * 根据商品id得到绑定店铺
     */
    public function getstoreListByGroupId()
    {
        $groupId = $this->request->param('group_id','','intval');
        $param['lng'] = $this->request->param('lng','','trim');
        $param['lat'] = $this->request->param('lat','','trim');
        $param['group_ids'] = [$groupId];//团购id
        $param['status'] = 1;
        $stores = (new StoreGroupService())->getStoreByGroup($param);

        $list['lists'] = ResourceService::storeListsModel($stores, $param['lat'],  $param['lng']);
        return api_output(1000, $list);
        
    }

    /**
     * 课程预约提交
     */
    public function appointCourse()
    {
        $param = $this->request->param();
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        (new GroupAppointService())->appointCourse($param);

        return api_output(1000, ['msg'=>L_('预约成功')]);
        
    }

    
}
