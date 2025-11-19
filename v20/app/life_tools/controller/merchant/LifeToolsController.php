<?php
namespace app\life_tools\controller\merchant;

use app\life_tools\model\db\LifeToolsCard;
use app\life_tools\model\db\LifeToolsCardTools;
use app\life_tools\model\service\LifeToolsCardOrderService;
use app\life_tools\model\service\LifeToolsCardService;
use app\merchant\controller\merchant\AuthBaseController;
use app\life_tools\model\service\LifeToolsService;
use map\Coordinate;

/**
 * 课程 场馆 景区
 */
class LifeToolsController extends AuthBaseController
{
    /**
     * 获取场馆列表
     */
    public function getInformationList()
    {
        $params = []; 
        $params['page_size'] = $this->request->post('page_size', 10, 'trim,intval'); 
        $params['keywords'] = $this->request->post('keywords', '', 'trim'); 
        $params['type'] = $this->request->post('type', '', 'trim'); 
        $params['audit_status'] = $this->request->post('audit_status', '', 'trim');
        $params['audit_status'] = $params['audit_status'] == '' ? null : $params['audit_status'];
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsService)->getLifeToolsList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 设置属性
     */
    public function setLifeToolsAttrs()
    {
        $params = []; 
        $params['sort'] = $this->request->post('sort', null, 'trim,intval'); 
        $params['status'] = $this->request->post('status', null, 'trim,intval');   
        $params['is_hot'] = $this->request->post('is_hot', null, 'trim,intval');   
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');  
        $params['mer_id'] = $this->merId;
        try {
            $data = (new LifeToolsService)->setLifeToolsAttrs($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 获取地图配置信息
     */
    public function getMapConfig()
    {
        $params = [];
        $params['city_id'] = $this->merchantUser['city_id'];
        try {
            $data = (new LifeToolsService)->getMapConfig($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
      
    }

    /**
     * 获取地区信息
     */
    public function getAddressList()
    {
        $params = []; 
        $params['pid'] = $this->request->post('pid', 0, 'trim,intval');  
        try {
            $data = (new LifeToolsService)->getAddressList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }


    /**
     * 获取场馆课程详情
     */
    public function getLifeToolsDetail()
    {
        $params = []; 
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');  
        try {
            $data = (new LifeToolsService)->getLifeToolsDetail($params);
            if (cfg('tianditu_web_key')) {
                list($data['long'], $data['lat']) = Coordinate::bd09ToWgs84($data['long'], $data['lat']);
                $data['longlat'] = $data['long'] . ',' . $data['lat'];
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

   

    /**
     * 增加修改场馆课程
     */
    public function addEditLifeTools()
    {
        $params = [];
        $params['mer_id'] = $this->merId;
        $params['ip'] = $this->request->ip();
        $params['tools_id'] = $this->request->post('tools_id',  0, 'trim,intval'); 
        $params['type'] = $this->request->post('type', '', 'trim'); 
        $params['cat_id'] = $this->request->post('cat_id', 0, 'trim,intval'); 
        $params['title'] = $this->request->post('title', '', 'trim,htmlspecialchars'); 
        $params['introduce'] = $this->request->post('introduce', '', 'trim,htmlspecialchars'); 
        $params['images'] = $this->request->post('images', '', 'trim'); 
        $params['phone'] = $this->request->post('phone', '', 'trim'); 
        $params['address'] = $this->request->post('address', '', 'trim'); 
        $params['longlat'] = $this->request->post('longlat', '', 'trim'); 
        $params['province_id'] = $this->request->post('province_id', 0, 'trim,intval'); 
        $params['city_id'] = $this->request->post('city_id', 0, 'trim,intval'); 
        $params['area_id'] = $this->request->post('area_id', 0, 'trim,intval'); 
        $params['money'] = $this->request->post('money', 0, 'trim'); 
        $params['start_time'] = $this->request->post('start_time', '', 'trim'); 
        $params['end_time'] = $this->request->post('end_time', '', 'trim');  
        $params['description'] = $this->request->post('description', '', 'trim'); 
        $params['tickets_description'] = $this->request->post('tickets_description', '', 'trim'); 
        $params['label'] = $this->request->post('label', []); 
        $params['is_appoint'] = $this->request->post('is_appoint', 0, 'trim,intval'); 
        $params['status'] = $this->request->post('status', 0, 'trim,intval'); 
        $params['coach'] = $this->request->post('coach', '', 'trim'); 
        $params['cover_image'] = $this->request->post('cover_image', '', 'trim'); 
        $params['time_txt'] = $this->request->post('time_txt', '', 'trim');
        $params['is_close'] = $this->request->post('is_close', 0, 'trim,intval');
        $params['is_close_body'] = $this->request->post('is_close_body', '', 'trim');

        try {
            $data = (new LifeToolsService)->addEditLifeTools($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

 
    /**
     * 删除课程
     */
    public function delLifeTools()
    {
        $params = [];
        $params['tools_id'] = $this->request->post('tools_id', 0, 'trim,intval');  
        try {
            $data = (new LifeToolsService)->delLifeTools($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**----------------次卡 begin-------------*/

    /**
     * 获取次卡列表
     */
    public function getToolsCardList()
    {
        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', '', 'trim');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        try {
            $arr = (new LifeToolsService())->getToolsCardList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取次卡编辑信息
     */
    public function getToolsCardEdit()
    {
        $pigcms_id = $this->request->param('pigcms_id', 1, 'intval');
        try {
            $arr = (new LifeToolsCard())->getOne(['pigcms_id' => $pigcms_id])->toArray();
            if($arr['type'] == 'stadium' || $arr['type'] == 'course'){
                $arr['type'] = 'sports';
            }
            $LifeToolsCardTools = new LifeToolsCardTools();
            $arr['scenic_ids'] = $LifeToolsCardTools->where(['card_id' => $pigcms_id, 'type'=>'scenic'])->column('tools_id');
            $arr['stadium_ids'] = $LifeToolsCardTools->where(['card_id' => $pigcms_id, 'type'=>'stadium'])->column('tools_id');
            $arr['course_ids'] = $LifeToolsCardTools->where(['card_id' => $pigcms_id, 'type'=>'course'])->column('tools_id');
            $arr['image'] = replace_file_domain($arr['image']);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除次卡
     */
    public function delToolsCard()
    {
        $pigcms_id = $this->request->param('pigcms_id', 1, 'intval');
        try {
            $arr = (new LifeToolsCard())->updateThis(['pigcms_id' => $pigcms_id], ['is_del' => 1]);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加或编辑次卡
     */
    public function AddOrEditToolsCard()
    {
        $param = $this->request->param();
        $param['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsService())->AddOrEditToolsCard($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取所有景区体育健身
     */
    public function getAllToolsList()
    {
        $param['type']   = $this->request->param('type', '', 'trim');
        $param['mer_id'] = $this->merId;
        try {
            $arr = (new LifeToolsService())->getAllToolsList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取次卡核销列表
     */
    public function getToolsCardRecord()
    {
        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', '', 'trim');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['order_id'] = $this->request->post('order_id', 0, 'intval');
        $param['begin_time'] = $this->request->post('begin_time', '', 'trim');
        $param['end_time'] = $this->request->post('end_time', '', 'trim');
        try {
            $arr = (new LifeToolsService())->getToolsCardRecord($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取次卡订单列表
     * @return \json
     */
    public function getCardOrderList()
    {
        $param['mer_id']      = $this->merId;
        $param['keyword']     = $this->request->param('keyword', '', 'trim');
        $param['page']        = $this->request->param('page', 1, 'intval');
        $param['pageSize']    = $this->request->param('pageSize', 10, 'intval');
        $param['type']        = $this->request->param('type', '', 'trim');
        $param['status']      = $this->request->param('status', -1, 'intval');
        $param['search_type'] = $this->request->param('search_type', 1, 'intval');
        $param['begin_time']  = $this->request->param('begin_time', '', 'trim');
        $param['end_time']    = $this->request->param('end_time', '', 'trim');
        $param['time_type']   = $this->request->param('time_type', 2, 'intval');
        try {
            $arr = (new LifeToolsCardOrderService())->getList($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 获取次卡订单详情
     * @return \json
     */
    public function getCardOrderDetail()
    {
        $order_id = $this->request->param('order_id', 0, 'intval');
        if (empty($order_id)) {
            return api_output_error(1003, '参数错误');
        }
        try {
            $arr = (new LifeToolsCardOrderService())->getDetail($order_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 同意退款-次卡订单
     * @return \json
     */
    public function agreeCardOrderRefund()
    {
        $order_id  = $this->request->param('order_id', 0, 'intval');
        $order_ids = $this->request->param('order_ids', '', 'trim');
        if (empty($order_id) && empty($order_ids)) {
            return api_output_error(1003, '参数错误');
        }
        if (empty($order_ids)) {
            $order_ids = [$order_id];
        }
        try {
            $arr = (new LifeToolsCardOrderService())->agreeRefund($order_ids);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 拒绝退款-次卡订单
     * @return \json
     */
    public function refuseCardOrderRefund()
    {
        $order_id  = $this->request->param('order_id', 0, 'intval');
        $order_ids = $this->request->param('order_ids', '', 'trim');
        $reason    = $this->request->param('reason', '', 'trim');
        if ((empty($order_id) && empty($order_ids)) || empty($reason)) {
            return api_output_error(1003, '参数错误');
        }
        if (empty($order_ids)) {
            $order_ids = [$order_id];
        }
        try {
            $arr = (new LifeToolsCardOrderService())->refuseRefund($order_ids, $reason);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**
     * 添加编辑次卡时获取商家信息
     */
    public function getAddEditCardMerchantInfo()
    {
        $mer_id = $this->merId;
        try {
            $arr = (new LifeToolsCardService())->getAddEditCardMerchantInfo($mer_id);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }


    /**----------------次卡 end-------------*/


    /**
     * 景区/体育开启/关闭暂停功能
     * @return \json
     */
    public function changeCloseStatus(){
        $param['tools_id']  = $this->request->param('tools_id', 0, 'trim,intval');
        $param['is_close'] = $this->request->param('is_close', '', 'trim,intval');
        $param['is_close_body']    = $this->request->param('is_close_body', '', 'trim');
        $param['mer_id'] = $this->merId;
        if ($param['tools_id']<1) {
            return api_output_error(1003, '参数错误');
        }
        if ($param['is_close']==1&&!$param['is_close_body']) {
            return api_output_error(1003, '自定义文案不能为空！');
        }
        try {
            $arr = (new LifeToolsService())->changeCloseStatus($param);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}