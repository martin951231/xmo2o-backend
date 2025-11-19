<?php
/**
 * 店员后台
 * author by hengtingmei
 */
namespace app\storestaff\controller\storestaff;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;
use app\storestaff\model\db\MerchantStoreDateBill;
use app\storestaff\model\service\sms\StaffSmsService;
use app\storestaff\model\service\StoreStaffService;
use Carbon\Carbon;
use think\response\Json;

class IndexController extends AuthBaseController {
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * desc: 返回首页信息
     * return :array
     */
    public function index(){
        $param = $this->request->param();
        $returnArr = (new StoreStaffService())->getIndexInfo($param,$this->staffUser);
        return api_output(0, $returnArr);
    }

    /**
     * desc: 获得店员信息
     * return :array
     */
    public function getStaffInfo(){

        $returnArr = (new MerchantStoreStaffService())->getStaffInfo($this->staffUser);
        $returnArr['open_storestaff_mall_scan'] = intval(cfg('open_storestaff_mall_scan'));
        return api_output(0, $returnArr);
    }


    /**
     * desc: 修改密码
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 15:55
     */
    public function editPassword(){
        if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }

        $param['old_password']  = $this->request->param("old_password", "", "trim");
        $param['password']  = $this->request->param("password", "", "trim");

        $result = (new StoreStaffService())->editPassword($param, $this->staffUser);
        return api_output(0, $result, "修改成功");
    }

    /**
     * desc: 退出登录
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 15:55
     */
    public function logout(){
        $result = (new StoreStaffService())->logout($this->staffUser);
        return api_output(0, $result, "");
    }

    /**
     * desc: 登录成功后修改店员登录信息
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 16:37
     */
    public function updateLoginInfo(){
        $result = (new StoreStaffService())->updateLoginInfo($this->staffUser);
        return api_output(0, $result, "");
    }


    /**
     * desc: 新订单通知轮询接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/09 16:37
     */
    public function orderNotice(){
        $param = $this->request->param();
        $result = (new StoreStaffService())->orderNotice($param,$this->staffUser);
        return api_output(0, $result, "");
    }


    /**
     * desc: 修改店员接收订单状态
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/12/11 16:37
     */
    public function editNoticeStatus(){
        $param = $this->request->param();
        $result = (new StoreStaffService())->editNoticeStatus($this->staffUser);
        return api_output(0, $result, L_("修改成功"));
    }

    /**
     * desc: 发送短信验证码
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/08/29 17:45
     */
    public function sendSmsToUser(){
        if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }

        $param['phone']  = $this->request->param("phone", "", "trim");
        $param['phone_country_type']  = $this->request->param("phone_country_type", "", "trim");
        $param['store_id']  = $this->staffUser['store_id'];

        $result = (new StaffSmsService())->sendSmsToUser($param);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result );
    }

    //执行核销
    public function doCardDeposit() {
        $param['id']   = $this->request->param("id", 0, "intval");//用户寄存记录表ID
        $param['code'] = $this->request->param("code", "", "trim");//核销码
        $param['num']  = $this->request->param("num", 0, "intval");//核销数量
        $param['staffUser'] = $this->staffUser;
        try {
            (new StoreStaffService())->doCardDeposit($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }
        return api_output(0, [], '核销成功');
    }

    /**
     * 获取手机端店员核销列表
     */
    public function depositList() {
        $param     = $this->request->param();
        $returnArr = (new StoreStaffService())->getDepositList($param, $this->staffUser);
        return api_output(0, $returnArr);
    }


    /**
     * 体育健身-核销列表
     */
    public function toolsVerifyList()
    {
        try {
            $data = (new LifeToolsOrderDetail())->getSome(['staff_id' => $this->staffUser['id'], 'status' => 2], true, 'last_time desc');
            if (!empty($data)) {
                $data = $data->toArray();
                foreach ($data as $k => $v) {
                    $data[$k]['last_time'] = date('Y-m-d H:i:s', $v['last_time']);
                }
            } else {
                $data = [];
            }
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }


    public function storeDateBill()
    {
        $params = input('post.');
        $where[] = ['store_id', '=', $this->staffUser['store_id']];
        if(!empty($params['start_date'])){
            $where[] = ['bill_date', '>=', Carbon::parse($params['start_date'])->toDateString()];
        }
        if(!empty($params['end_date'])){
            $where[] = ['bill_date', '<=', Carbon::parse($params['end_date'])->toDateString()];
        }
        $data = MerchantStoreDateBill::where($where)
            ->group('store_id, bill_date')
            ->field("
                store_id,
                bill_date,
                SUM(pay_money) pay_money,
                SUM(pay_order_count) pay_order_count,
                SUM(internal_card_pay_money) internal_card_pay_money,
                SUM(balance_pay_money) balance_pay_money,
                SUM(alipay_pay_money) alipay_pay_money,
                SUM(wechat_pay_money) wechat_pay_money,
                SUM(offline_pay_money) offline_pay_money,
                SUM(refund_money) refund_money"
            )
            ->order('bill_date DESC')
            ->paginate(input('page_size', 15));
        
        return api_output(0, $data);
    }

    public function printStoreDateBill(): Json
    {
        $billDate = input('post.bill_date');
        $data = MerchantStoreDateBill::where([
                'bill_date' => $billDate,
                'store_id' => $this->staffUser['store_id']
            ])
            ->group('store_id, bill_date')
            ->field("
                store_id,
                bill_date,
                SUM(pay_money) pay_money,
                SUM(pay_order_count) pay_order_count,
                SUM(internal_card_pay_money) internal_card_pay_money,
                SUM(balance_pay_money) balance_pay_money,
                SUM(alipay_pay_money) alipay_pay_money,
                SUM(wechat_pay_money) wechat_pay_money,
                SUM(offline_pay_money) offline_pay_money,
                SUM(refund_money) refund_money"
            )->find();
        
        if(empty($data)){
            throw_exception('账单信息不存在');
        }

        $printData = $data->toArray();
        $printData['staff_name'] = $this->staffUser['name'];
        $printData['store_id'] = $this->staffUser['store_id'];
        $printData['store_name'] = MerchantStore::where('store_id', $this->staffUser['store_id'])->value('name');
        $printData['date'] = $billDate;
        $printData['mkey'] = input('post.Device-Id', '', 'trim');

        (new \app\mall\model\service\order_print\PrintHaddleService())->printInternalMembershipCardTicket($printData);

        return api_output(0, true);
    }

    public function getConfig()
    {
        $data =  [
            'balance_pay_use_sms' => intval(cfg('balance_pay_use_sms'))
        ];
        return api_output(0, $data);
    }


}