<?php
/**
 * 团购商品
 * Author: hengtingmei
 * Date Time: 2021/04/28 14:24
 */

namespace app\group\controller\merchant;
use app\group\controller\merchant\AuthBaseController;
use app\group\model\service\GroupLabelService;
use app\group\model\service\GroupService;
use app\group\model\service\order\GroupOrderService;
use app\group\model\service\StoreGroupService;
use app\group\model\service\export\ExportService;
use app\group\model\service\GroupPackagesService;
use Exception;

class GoodsController extends AuthBaseController
{
    /**
     * 获得商品列表
     * Author: 衡婷妹
     * Date Time: 2021/05/12 
     */
    public function getGoodsList(){
        
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        // 获得列表
        $list = (new GroupService())->getGoodsList($param);
        // print_r($list);
        return api_output(0, $list);
    }
    
    
    /**
     * 获得添加编辑团购商品所需的数据
     * Author: 衡婷妹
     * Date Time: 2021/04/28
     */
    public function getGroupEditInfo(){
        // 获得列表
        $list = (new GroupService())->getGroupEditInfo($this->merchantUser['mer_id']);
        return api_output(0, $list);
    }

    /**
     * 获得商家的店铺列表
     * Author: 衡婷妹
     * Date Time: 2021/04/28 
     */
    public function getMerchantStoreList(){
        
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        // 获得列表
        $list = (new GroupService())->getMerchantStoreList($param);
        return api_output(0, $list);
    }    
    
    /**
    * 设置店铺推荐
    * Author: 衡婷妹
    * Date Time: 2021/05/13 
    */
   public function setStoreRecommend(){
       
       $param = $this->request->param();
       $param['mer_id'] = $this->merchantUser['mer_id'];
       // 获得列表
       $list = (new StoreGroupService())->setStoreRecommend($param);
       return api_output(0, $list);
   }
    
   /**
   * 获得店铺推荐
   * Author: 衡婷妹
   * Date Time: 2021/05/13 
   */
  public function getStoreRecommend(){
      
      $param = $this->request->param();
      $param['mer_id'] = $this->merchantUser['mer_id'];
      // 获得列表
      $list = (new StoreGroupService())->getStoreRecommend($param);
      return api_output(0, $list);
  }


    /**
     * 新增编辑普通团购商品-团购商品
     * Author: 衡婷妹
     * Date Time: 2021/04/28 
     */
    public function saveNomalGoods(){
        
        $param = $this->request->param();
        $param['group_id'] = $this->request->param('group_id','0','intval');
        $res = (new GroupService())->saveNomalGoods($param, $this->merchantUser);
        return api_output(0, $res);

    }

    /**
     * 获得普通团购商品详情-团购商品
     * Author: 衡婷妹
     * Date Time: 2021/04/28 
     */
    public function getGoodsNormalDetail(){
        
        $groupId = $this->request->param('group_id','0','intval');
        $detail = (new GroupService())->getGoodsNormalDetail($groupId);
        return api_output(0, $detail);
    }

    /**
     * 添加标签
     * Author: 衡婷妹
     * Date Time: 2021/04/28 14:30
     */
    public function addLabel()
    {
        $param = $this->request->param();
        $param['name'] = $this->request->param('name','','trim');
        $param['fid'] = $this->request->param('fid','0','intval');
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $res = (new GroupLabelService())->addLabel($param);
        return api_output(0, $res);
    }
    
    /**
     * 获得标签列表
     * Author: 衡婷妹
     * Date Time: 2021/04/28 14:30
     */
    public function getLabelList()
    {
        $list = (new GroupLabelService())->getLabelTree($this->merchantUser['mer_id']);
        return api_output(0, $list);
    }

    /**
     * 代金券商家详情接口
     * @return [type] [description]
     */
    public function getGoodsCashingDetail(){
        $param = $this->request->param();
        $param['group_id'] = $this->request->param('group_id', 0, 'intval');
        $param['mer_id'] = $this->merchantUser['mer_id'];
        try {
            $detail = (new GroupService())->getGoodsCashingDetail($param);
        } catch (\Exception $e) {
            return api_output(1005, $e->getMessage());
        }        
        return api_output(0, $detail);
    }

    /**
     * 添加/编辑代金券
     * @return [type] [description]
     */
    public function submitCashing(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        try {
            (new GroupService())->submitCashing($param);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }        
        return api_output(0, ['msg'=>'success']);
    }

    /**
     * 新增/编辑场次预约
     *
     * @return void
     * @author: 张涛
     * @date: 2021/04/29
     */
    public function saveBookingAppoint()
    {
        try {
            $param = $this->request->param();
            $param['mer_id'] = $this->merchantUser['mer_id'];
            (new GroupService())->saveBookingAppoint($param);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 更新价格日历
     *
     * @author: 张涛
     * @date: 2021/05/12
     */
    public function updatePriceCalendar()
    {
        try {
            $groupId = $this->request->param('group_id', 0, 'intval');
            $ruleId = $this->request->param('rule_id', 0, 'intval');
            $calendar =  $this->request->param('price_calendar');
            (new GroupService())->updatePriceCalendar($groupId, $ruleId, $calendar);
            return api_output(0);
        } catch (\Exception $e) {
            return api_output(1005, $e->getMessage());
        }
    }

    /**
     * 根据场次ID获取价格日历
     *
     * @author: 张涛
     * @date: 2021/05/12
     */
    public function getCalendar()
    {
        $ruleId = $this->request->param('rule_id', 7, 'intval');
        $startDate = $this->request->param('start_date', '2021-05-01');
        $endDate = $this->request->param('end_date', '2021-05-30');
        $rs = (new GroupService())->getPriceCalendarByRuleId($ruleId, $startDate, $endDate);
        return api_output(0, $rs);
    }

    /**
     * 场次预约详情
     *
     * @return void
     * @author: 张涛
     * @date: 2021/04/29
     */
    public function showBookingAppoint()
    {
        try {
            $groupId = $this->request->param('group_id', '0', 'intval');
            $detail = (new GroupService())->showBookingAppoint($groupId);
            return api_output(0, $detail);
        } catch (\Exception $e) {
            return api_output(1005, $e->getMessage());
        }
    }

    /**
     * 添加/编辑课程预约
     *
     * @return void
     * @author: 汪晨
     * @date: 2021/04/30
     */
    public function courseAppoint(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        try {
            (new GroupService())->courseAppoint($param);
        } catch (\Exception $e) {
            return api_output(1005, $e->getMessage());
        }        
        return api_output(0);
    }

    /**
     * 课程预约详情
     *
     * @return void
     * @author: 汪晨
     * @date: 2021/04/30
     */
    public function getCourseAppointDetail(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['group_id'] = $this->request->param('group_id', 0, 'intval');
        try {
            $detail = (new GroupService())->getCourseAppointDetail($param);
            $detail['content'] = empty($detail['content']) ? '' : str_replace('http://o2o-static.pigcms.com',$this->config['site_url'],$detail['content']);
        } catch (\Exception $e) {
            return api_output(1005, $e->getMessage());
        }        
        return api_output(0, $detail);
    }

    /**
     * 获得订单列表
     * Author: 汪晨
     * Date Time: 2021/05/13
     */
    public function getGoodsOrderList(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        // 获得列表
        $list = (new GroupOrderService())->getGroupOrderList($param);
        return api_output(0, $list);
    }

    /**
     * 获得订单详情
     * Author: 汪晨
     * Date Time: 2021/05/15
     */
    public function getGoodsOrderDetail(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        // 获得列表
        $list = (new GroupOrderService())->getGroupOrderList($param);
        return api_output(0, $list);
    }

    /**
     * 导出订单
     * Author: 汪晨
     * Date Time: 2021/05/15
     */
    public function exportOrder(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
       
        try {
            $result = (new ExportService())->addOrderExport('goods_order',$param, $this->merchantUser);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, $result);
    }

    /**
     * 保存备注
     * Author: 汪晨
     * Date Time: 2021/05/15
     */
    public function noteInfo()
    {
        $id = $this->request->param('id','','intval');
        $note_info = $this->request->param('note_info');
        $groupOrderService = new GroupOrderService();
        try {
            $arr = $groupOrderService->noteInfo($note_info,$id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 订单详情
     * @author mrdeng
     * Date Time: 2021/05/28
     */
    public function orderDetail(){
        $order_id = $this->request->param('order_id','','intval');
        if(empty($order_id)){
            return api_output_error(1003, L_("订单号缺失"));
        }
        try {
            $groupOrderService = new GroupOrderService();
            $where=[['o.order_id','=',$order_id]];
            $arr = $groupOrderService->getGoodsOrderDetail($where);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 订单详情备注修改
     * @author mrdeng
     * Date Time: 2021/05/28
     */
    public function updateOrderNote(){
        $order_id = $this->request->param('order_id','','intval');
        $note_info = $this->request->param('note_info','','trim');
        if(empty($order_id)){
            return api_output_error(1003, L_("订单id缺失,请刷新页面再提交"));
        }
        if(empty($note_info)){
            return api_output_error(1003, L_("提交内容不能为空"));
        }
        $where=[['order_id','=',$order_id]];
        $data['note_info']=$note_info;
        $groupOrderService = new GroupOrderService();
        try {
            $ret=$groupOrderService->updateThis($where,$data);
            if($ret!==false){
                return api_output(0,$ret);
            }else{
                return api_output_error(1003, L_("修改失败"));
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 分销员列表
     * @author wangchen
     */
    public function getRatioList(){
        $param['store_id'] = $this->request->param('store_id',0,'intval');
        try {
            $ret = (new GroupService())->getRatioList($param);
            return api_output(0,$ret);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 获得团购券列表
     */
    public function getGoodsCouponList(){
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        $param['page'] = $this->request->param('page',1,'intval');
        $param['pageSize'] = $this->request->param('pageSize',10,'intval');
        // 获得列表
        $list = (new GroupOrderService())->getGroupCouponList($param);
        return api_output(0, $list);
    }

    /**
     * 团购券详情
     */
    public function couponDetail(){
        $order_id = $this->request->param('order_id','','intval');
        $groupPassId = $this->request->param('group_pass_id','','intval');
        if(empty($order_id) || empty($groupPassId)){
            return api_output_error(1003, L_("订单号或者券序列号缺失"));
        }
        try {
            $groupOrderService = new GroupOrderService();
            $arr = $groupOrderService->getGoodsCouponDetail($order_id,$groupPassId);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购券核销
     */
    public function couponVerify()
    {
        $order_id = $this->request->param('order_id','','intval');
        $groupPassId = $this->request->param('group_pass_id','','trim');
        if(empty($order_id) || empty($groupPassId)){
            return api_output_error(1003, L_("订单号或者券序列号缺失"));
        }
        try {
            $groupOrderService = new GroupOrderService();
            $arr = $groupOrderService->couponVerify($order_id,$groupPassId);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
    /**
     * 导出团购券列表
     */
    public function exportGoodsCouponList()
    {
        $param = $this->request->param();
        $param['mer_id'] = $this->merchantUser['mer_id'];
        try {
            $groupOrderService = new GroupOrderService();
            $arr = $groupOrderService->exportGoodsCouponList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 团购套餐列表
     *
     * @return void
     * @date: 2023/08/14
     */
    public function groupPackageLists()
    {
        $params['mer_id'] = $this->request->log_uid;
        $params['page'] = $this->request->param('page', 1, 'intval');
        $params['pageSize'] = $this->request->param('page_size', 20, 'intval');
        try {
            $rs = (new GroupPackagesService())->groupPackageLists($params);
            return api_output(0, $rs);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

     /**
     * 展示团购套餐
     *
     * @return void
     * @date: 2023/08/14
     */
    public function showGroupPackage()
    {
        $id = $this->request->param('id',0,'intval');
        $merId = $this->request->log_uid;
        try {
            $rs = (new GroupPackagesService())->showGroupPackage($id,$merId);
            return api_output(0, $rs);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 保存团购套餐
     *
     * @return void
     * @date: 2023/08/14
     */
    public function saveGroupPackage()
    {
        $merId = $this->request->log_uid;
        $params['id'] =  $this->request->param('id', 0, 'intval');
        $params['title'] =  $this->request->param('title', '', 'trim');
        $params['description'] =  $this->request->param('description', '', 'trim');
        try {
            $rs = (new GroupPackagesService())->saveGroupPackage($merId, $params);
            return api_output(0, $rs);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除团购套餐
     *
     * @return void
     * @date: 2023/08/14
     */
    public function delGroupPackage()
    {
        $ids = $this->request->param('ids',[]);
        $merId = $this->request->log_uid;
        try {
            $rs = (new GroupPackagesService())->delGroupPackage($merId, $ids);
            return api_output(0, $rs);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 删除套餐绑定团购商品
     *
     * @return void
     * @author: zt
     * @date: 2023/08/14
     */
    public function delPackageBindGroup()
    {
        $groupId = $this->request->param('group_id', 0, 'intval');
        $packageid = $this->request->param('packageid', 0, 'intval');
        $merId = $this->request->log_uid;
        try {
            if (empty($merId) || empty($groupId) || empty($packageid)) {
                throw new Exception(L_('参数有误'));
            }
            $where = [
                ['mer_id', '=', $merId],
                ['group_id', '=', $groupId],
                ['packageid', '=', $packageid],
            ];
            $rs = (new GroupPackagesService())->delPackageBindGroup($where);
            return api_output(0, $rs);
        } catch (Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }




}
