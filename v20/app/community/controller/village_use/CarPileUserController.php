<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/4/26 9:18
 */

namespace app\community\controller\village_use;

use app\common\model\service\weixin\TemplateNewsService;
use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetMatter;
use app\community\model\service\HouseNewPileService;
use app\community\model\service\PileUserService;
use app\community\model\service\PileOrderPayService;
use app\community\model\service\UserService;
use think\App;

class CarPileUserController extends CommunityBaseController
{

    protected $uid;

    public function __construct(App $app)
    {
        parent::__construct($app);
       $this->uid = $this->request->log_uid;
        //  $this->uid =1;
    }


    
    /**
     * 开始充电
     * @author:zhubaodi
     * @date_time: 2021/4/11 10:49
     */
    public function orderPay()
    {
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['type'] = $this->request->param('type', '', 'intval');
        if (empty($data['type'])) {
            return api_output_error(1001, '请选择一种充电方式');
        }
        
        $data['id'] = $this->request->param('id', '', 'intval');
        if (empty($data['id'])) {
            return api_output_error(1001, '请上传设备编号');
        }
        
        $data['socket_no'] = $this->request->param('socket', '', 'trim');
        if (empty($data['socket_no'])) {
            return api_output_error(1001, '设备枪头编号不能为空');
        }

        $data['car_number'] = $this->request->param('car_number', '', 'trim');
       /* if (empty($data['car_number'])) {
            return api_output_error(1001, '车牌号不能为空');
        }*/
        $pileService = new HouseNewPileService();
        try {
            $res = $pileService->payOrder($data);
          
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0,$res, '操作成功');
    }


    /**
     * 结束充电
     * @author:zhubaodi
     * @date_time: 2021/7/22 11:38
     */
    public function getStopCharge()
    {

        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['order_id'] = $this->request->param('order_id', '', 'intval');//订单id
        $pileService = new HouseNewPileService();
        try {
            $res = $pileService->stopCharge($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res, '操作成功');

    }

    /**
     * 充值
     * @author:zhubaodi
     * @date_time: 2022/9/24 16:56
     */
    public function addRecharge(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', 0, 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $data['equipment_num'] = $this->request->param('equipment_num', '', 'trim');
        $data['price'] = $this->request->param('price', 0, 'trim');
        if (empty($data['price'])) {
            return api_output_error(1001, '充值金额不能为空');
        }

        $pileService = new HouseNewPileService();
        try {
            $res = $pileService->addRecharge($data);
            if (!empty($res)) {
                $link = get_base_url('pages/pay/check?order_type=village_new_pay&order_id=' . $res);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $link);
    }


    /**
     * 查询站点列表
     * @param integer $uid 用户uid
     * @author:zhubaodi
     * @date_time: 2021/4/26 9:23
     */
    public function pileList()
    {
        $data['uid'] = $this->uid;
        if (! $data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['pile_name'] = $this->request->param('pile_name', '', 'trim');
        $data['orderBy'] = $this->request->param('orderBy', '', 'intval');//列表排序 1按距离 2设备状态
        $data['lat'] = $this->request->param('lat', '', 'trim');
        $data['lng'] = $this->request->param('lng', '', 'trim');
        $data['page'] = $this->request->param('page', '1', 'intval');
        $data['limit'] =$this->request->param('limit', '10', 'intval');
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getPileList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info);
    }

    /**
     * 查询站点详情
     * @author:zhubaodi
     * @date_time: 2022/9/22 14:21\
     */
    public function getPileInfo(){
        $data['uid'] = $this->uid;
        if (empty($data['uid'])) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['pile_id'] = $this->request->param('pile_id', 0, 'intval');
        if (empty($data['pile_id'])) {
            return api_output_error(1001, '站点id不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getPileInfo($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['info'=>$info]);
    }

    /**
     * 查询收费标准详情
     * @author:zhubaodi
     * @date_time: 2022/9/22 15:01
     */
    public function getPileChargeDetail(){
        $data['uid'] = $this->uid;
        if (empty($data['uid'])) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', 0, 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $data['charge_id'] = $this->request->param('charge_id', 0, 'intval');
        if (empty($data['charge_id'])) {
            return api_output_error(1001, '收费标准id不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getPileChargeDetail($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, ['info'=>$info]);
    }
    

    /**
     * 根据类型查询设备列表
     * @author:zhubaodi
     * @date_time: 2022/9/22 15:36
     */
    public function getTypeEquipmentList()
    {
        $data['uid'] = $this->uid;
        if (!$data['uid'] ) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id']  = $this->request->param('village_id', '', 'intval');
        if (!$data['village_id']) {
            return api_output_error(1001, '小区id不能为空');
        }
        $data['type'] = $this->request->param('type', '', 'intval');//设备类型 1直流 2交流
        $data['page'] = $this->request->param('page', '1', 'intval');
       //  $data['limit'] =$this->request->param('limit', '10', 'intval');
        $data['limit'] =10;
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getTypeEquipmentList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info);
    }



    /**
     * 充电页面设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     */
    public function getTypeEquipmentDetail()
    {
        $data=array();
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['id'] = $this->request->param('id', '', 'trim');
        if (empty($data['id'])) {
            return api_output_error(1001, '设备id不能为空');
        }
        $data['socket'] = $this->request->param('socket', '', 'intval');
        if (empty($data['socket'])) {
            return api_output_error(1001, '插座编号不能为空');
        }
        $data['village_id']  = $this->request->param('village_id', '', 'intval');
        if (!$data['village_id']) {
            return api_output_error(1001, '小区id不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $data['return_charge_err']=1;
            $info = $pileService->getTypeEquipmentDetail($data);
            if(isset($info['err_code']) && $info['err_code']==1){
                return api_output(1103,$info['orderdata'], $info['err_msg']);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }


    /**
     * 根据桩编号充电页面设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     */
    public function getNumEquipmentDetail()
    {
        $data=array();
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        
        $data['equipment_num'] = $this->request->param('equipment_num', '', 'trim');
        if (empty($data['equipment_num'])) {
            return api_output_error(1001, '充电桩编码不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $data['return_charge_err']=1;
            $info = $pileService->getTypeEquipmentDetail($data);
            if(isset($info['err_code']) && $info['err_code']==1){
                return api_output(1103,$info['orderdata'], $info['err_msg']);
            }
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }

    /**
     * 充电页面设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     */
    public function getUserOrderList()
    {
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['status'] = $this->request->param('status', '2', 'intval');
        $data['page'] = $this->request->param('page', '1', 'intval');
        $data['limit'] =$this->request->param('limit', '10', 'intval');
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getUserOrderList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }

    /**
     * 充电页面设备详情
     * @author:zhubaodi
     * @date_time: 2022/9/23 10:40
     */
    public function getUserOrderDetail()
    {
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['order_id'] = $this->request->param('order_id', '', 'trim');
        if (empty($data['order_id'])) {
            return api_output_error(1001, '订单id不能为空');
        }
        $data['type'] = $this->request->param('type', '', 'trim');
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getUserOrderDetail($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }

    /**
     * 查询充值/消费明细列表
     * @author:zhubaodi
     * @date_time: 2022/10/18 10:38
     */
    public function getUserMoneyLogList(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $data['page'] = $this->request->param('page', '1', 'intval');
        $data['limit'] =$this->request->param('limit', '10', 'intval');
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getUserMoneyLogList($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }


    /**
     * 查询实体卡信息
     * @author:zhubaodi
     * @date_time: 2022/10/18 10:38
     */
    public function getCardInfo(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->getCardInfo($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }

    /**
     * 绑定实体卡
     * @author:zhubaodi
     * @date_time: 2022/10/18 10:38
     */
    public function bindCard(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $data['pile_card_no'] = $this->request->param('pile_card_no', '', 'trim');
        if (empty($data['pile_card_no'])) {
            return api_output_error(1001, '物理卡号不能为空');
        }
        $data['card_no'] =$this->request->param('card_no', '', 'trim');
        if (empty($data['card_no'])) {
            return api_output_error(1001, '逻辑卡号不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->bindCard($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }


    /**
     * 解绑实体卡
     * @author:zhubaodi
     * @date_time: 2022/10/18 10:38
     */
    public function unBindCard(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->unBindCard($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }

    /**
     * 余额提现
     * @author:zhubaodi
     * @date_time: 2022/10/18 10:38
     */
    public function withdraw(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])) {
            return api_output_error(1001, '小区id不能为空');
        }
        $data['current_money'] = $this->request->param('current_money', '', 'intval');
        if (empty($data['current_money'])) {
            return api_output_error(1001, '提现金额不能为空');
        }
        $data['type'] = $this->request->param('type', '', 'intval');//1提现到微信 2提现到平台余额
        if (empty($data['type'])) {
            return api_output_error(1001, '提现金额不能为空');
        }
        $data['true_name'] = $this->request->param('true_name', '', 'trim');
        $pileService = new HouseNewPileService();
        try {
            $info = $pileService->userWithdraw($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }

        return api_output(0, $info);
    }

    /**
     * 查询协议内容
     * @author:zhubaodi
     * @date_time: 2022/9/29 21:35
     */
    public function getNews(){
        $data['village_id'] = $this->request->param('village_id', '', 'intval');
        if (empty($data['village_id'])){
            return api_output_error(1002, '请先登录小区后台');
        }
        $data['type']=1;
        $pileService=new HouseNewPileService();
        try {
            $res = $pileService->getNews($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res,'操作成功');
    }
}
