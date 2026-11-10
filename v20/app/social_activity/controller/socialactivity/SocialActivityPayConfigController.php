<?php

namespace app\social_activity\controller\socialactivity;

use app\social_activity\controller\socialactivity\AuthBaseController as BaseController;
use app\social_activity\model\service\SocialActivityPayConfigService;
use app\social_activity\validate\SocialActivityPayConfigValidate;

class SocialActivityPayConfigController extends BaseController
{
    protected $validateClass = SocialActivityPayConfigValidate::class;

    /**
     * 获取支付配置
     */
    public function getConfig()
    {
        $param = [
            'village_id' => $this->request->param('village_id', 0, 'intval'),
        ];
        $param = $this->validateParameter($param, 'getConfig');

        try {
            $data = (new SocialActivityPayConfigService())->getConfig($param['village_id']);
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 保存支付配置
     */
    public function saveConfig()
    {
        $param = [
            'village_id' => $this->request->param('village_id', 0, 'intval'),
            'status' => $this->request->param('status', 0, 'intval'),
            'app_id' => $this->request->param('app_id', '', 'trim'),
            'merchant_no' => $this->request->param('merchant_no', '', 'trim'),
            'private_key' => $this->request->param('private_key', '', 'trim'),
        ];
        $param = $this->validateParameter($param, 'saveConfig');

        try {
            (new SocialActivityPayConfigService())->saveConfig($param);
            return api_output(0, [], '保存成功');
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
