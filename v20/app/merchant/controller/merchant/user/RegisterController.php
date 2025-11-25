<?php
/**
 * 商家入驻
 * Created by phpstorm.
 * Author: hengtingmei
 * Date Time: 2020/07/03 14:05
 */
namespace app\merchant\controller\merchant\user;

use app\common\controller\CommonBaseController;
use app\merchant\model\service\LoginService;
use think\captcha\facade\Captcha;
class RegisterController extends CommonBaseController{
    /**
     * desc: 商家入驻接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/07/03 14:07
     */
    public function index(){
 		if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }
        if (cfg('close_register') == 1) {
            return api_output_error(1003, "平台已关闭商家入驻，具体开放时间请留意平台通知");
        }

        $param['account']  = $this->request->param("account", "", "trim");
        $param['phone']  = $this->request->param("phone", "", "trim");
        $param['pwd']  = $this->request->param("pwd", "", "trim");
        $param['smscode']  = $this->request->param("smscode", "", "trim");
        $param['phone_country_type']  = $this->request->param("phone_country_type", "", "trim");
        $param['province_id']  = $this->request->param("province_id", "", "trim");
        $param['city_id']  = $this->request->param("city_id", "", "trim");
        $param['area_id']  = $this->request->param("area_id", "", "trim");
        $param['street_id']  = $this->request->param("street_id", "", "trim");
        $param['verify']  = $this->request->param("verify", "", "trim");
        $param['name']  = $this->request->param("name", "", "trim");
        $param['company_name']  = $this->request->param("company_name", "", "trim");
        $param['address']  = $this->request->param("address", "", "trim");
        $param['spread_code']  = $this->request->param("spread_code", "", "trim");
        $param['invit_code']  = $this->request->param("invit_code", "", "trim");
        $param['diy_form'] = $this->request->param('diy_form');
        $param['trading_certificate_image']  = $this->request->param("trading_certificate_image", "", "trim");
        $param['id_card_front']  = $this->request->param("id_card_front", "", "trim");
        $param['id_card_reverse']  = $this->request->param("id_card_reverse", "", "trim");

        $result = (new LoginService())->register($param);
        try {
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result, $result['msg']);
    }

    /**
     * desc: 发送短信验证码
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/07/03 14:07
     */
    public function sendSms(){
        if(!$this->request->isPost()) {
            return api_output_error(1003, "非法请求");
        }

        $param['verify']  = $this->request->param("verify", "", "trim");
        $param['phone']  = $this->request->param("phone", "", "trim");
        $param['phone_country_type']  = $this->request->param("phone_country_type", "", "trim");

        try {
            $result = (new LoginService())->sendSms($param);
        } catch (\Exception $e) {
            return api_output_error($e->getCode(), $e->getMessage());
        }

        return api_output(0, $result, L_('发送成功'));
    }

    public function verify()
    {
        return Captcha::create();
    }


    public function regForm()
    {
        $fields = \think\facade\Db::name('merchant_register_fields')->order('sort', 'DESC')->select()->toArray();
        foreach ($fields as $k => $v) {
            if ($v['type'] == 3) {
                $fields[$k]['use_field'] = unserialize($v['use_field']);
            } else {
                $fields[$k]['use_field'] = [];
            }
        }
        return api_output(0, $fields, L_('成功'));
    }
}