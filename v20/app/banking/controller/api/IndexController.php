<?php
/**
 * 金融产品首页api接口
 * Author: hengtingmei
 * Date Time: 2022/01/06
 */

namespace app\banking\controller\api;

use app\banking\model\service\BankingApplyService;
use app\common\model\service\AdverService;
use app\community\model\service\HouseVillageService;

class IndexController extends ApiBaseController
{
   
    /**
     * 获得首页数据
     */
    public function index()
    {
        $param['village_id'] = $this->request->param("village_id", "0", "intval");

        if(empty($param['village_id'])){
            throw new \think\Exception('未选择小区', 1003);
        }

        $returnArr['category_list'] = [
            [
                'id' => 'loans',
                'name' => '贷款',
                'desc' => '极速到账 无需抵押',
                'icon' => cfg('site_url').'/static/images/banking/loans.png',
                'image_bg' => cfg('site_url').'/static/images/banking/loans_bg.png',
            ],
            [
                'id' => 'bank',
                'name' => '电子银行',
                'desc' => '极速到账 无需抵押',
                'icon' => cfg('site_url').'/static/images/banking/bank.png',
                'image_bg' => cfg('site_url').'/static/images/banking/bank_bg.png',
            ],
            [
                'id' => 'deposit',
                'name' => '存款',
                'desc' => '极速到账 无需抵押',
                'icon' => cfg('site_url').'/static/images/banking/deposit.png',
                'image_bg' => cfg('site_url').'/static/images/banking/deposit_bg.png',
            ],
            [
                'id' => 'information',
                'name' => '普惠金融知识',
                'desc' => '极速到账 无需抵押',
                'icon' => cfg('site_url').'/static/images/banking/information.png',
                'image_bg' => cfg('site_url').'/static/images/banking/information_bg.png',
            ],
        ];
        $returnArr['adver_list'] = (new AdverService())->getAdverByCatKey('banking_index_adver');

        // 获得列表
        $village = (new HouseVillageService())->getHouseVillage($param['village_id'],'village_name');
        $returnArr['village_id'] = $param['village_id'];
        $returnArr['village_name'] = $village['village_name'] ?? '';
        return api_output(0, $returnArr);
    }

    // 贷款首页
    public function loansIndex(){
        if(empty($this->_uid)){
            throw new \think\Exception('未登录', 1002);
        }
        $res = (new BankingApplyService())->loansIndex($this->_uid);
        return api_output(0, $res);
    }

    /**
     * 电子银行首页
     */
    public function eBankIndex()
    {
        $param['village_id'] = $this->request->param("village_id", "0", "intval");

        if(empty($param['village_id'])){
            throw new \think\Exception('未选择小区', 1003);
        }

        $returnArr['category_list'] = [
            [
                'type' => 'credit_card',
                'name' => '信用卡',
                'pic' => cfg('site_url').'/static/images/banking/credit_card.png',
            ],
            [
                'type' => 'ecard',
                'name' => 'E支付',
                'pic' => cfg('site_url').'/static/images/banking/ecard.png',
            ],
            [
                'type' => 'mobile_bank',
                'name' => '手机银行',
                'pic' => cfg('site_url').'/static/images/banking/mobile_bank.png',
            ],
            [
                'type' => 'wechat_bank',
                'name' => '微信银行',
                'pic' => cfg('site_url').'/static/images/banking/wechat_bank.png',
            ],
        ];
        $returnArr['adver_list'] = (new AdverService())->getAdverByCatKey('banking_electronic_adver');

        $returnArr['bank_download_qrcode'] = replace_file_domain(config_data('bank_download_qrcode')); // 农行APP下载二维码
        $returnArr['bank_wechat_qrcode'] = replace_file_domain(config_data('bank_wechat_qrcode'));// 农行公众号二维码
        return api_output(0, $returnArr);
    }

    

    /**
     * 首页
     */
    public function config()
    {
        $returnArr['bank_download_qrcode'] = replace_file_domain(config_data('bank_download_qrcode')); // 农行APP下载二维码
        $returnArr['bank_weixin_qrcode'] = replace_file_domain(config_data('bank_weixin_qrcode'));// 农行公众号二维码
        $returnArr['banking_user_agreement'] = replace_file_domain_content(config_data('banking_user_agreement')); // 用户协议
        $returnArr['deposit_need_input_money'] = config_data('deposit_need_input_money'); // 用户存款提交信息是否需要填写存款金额
        $returnArr['ecard_need_industry'] = config_data('ecard_need_industry'); // E支付提交信息是否需要填写行业
        $returnArr['banking_user_agreement_show'] = config_data('banking_user_agreement_show'); // E支付提交信息是否需要填写行业
        return api_output(0, $returnArr);
    }

}
