<?php

namespace app\social_activity\model\service;

use app\social_activity\model\db\SocialActivityPayConfig;

class SocialActivityPayConfigService
{
    /**
     * 获取支付配置
     * @param int $villageId
     * @return array
     */
    public function getConfig($villageId)
    {
        $config = (new SocialActivityPayConfig())->where(['village_id' => intval($villageId)])->find();
        if (!$config) {
            return [
                'status' => 0,
                'app_id' => '',
                'merchant_no' => '',
                'private_key' => '',
            ];
        }

        $config = $config->toArray();
        return [
            'status' => intval($config['status']),
            'app_id' => $config['app_id'],
            'merchant_no' => $config['merchant_no'],
            'private_key' => $config['private_key'],
        ];
    }

    /**
     * 获取并校验活动主办社区的支付配置。
     *
     * @param int $villageId
     * @return array
     * @throws \Exception
     */
    public function getEnabledConfig($villageId)
    {
        $config = $this->getConfig($villageId);
        if (
            intval($config['status'] ?? 0) !== 1
            || trim((string)($config['app_id'] ?? '')) === ''
            || trim((string)($config['merchant_no'] ?? '')) === ''
            || trim((string)($config['private_key'] ?? '')) === ''
        ) {
            throw new \Exception('当前小区未配置支付账户, 请联系管理员');
        }
        return $config;
    }

    /**
     * 保存支付配置
     * @param array $param
     * @return bool
     */
    public function saveConfig($param)
    {
        $model = new SocialActivityPayConfig();
        $config = $model->where(['village_id' => intval($param['village_id'])])->find();
        $data = [
            'village_id' => intval($param['village_id']),
            'status' => intval($param['status']),
            'app_id' => trim($param['app_id']),
            'merchant_no' => trim($param['merchant_no']),
            'private_key' => trim($param['private_key']),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($config) {
            $result = $model->where(['id' => $config['id']])->update($data);
        } else {
            $data['create_time'] = date('Y-m-d H:i:s');
            $result = $model->insert($data);
        }

        if ($result === false) {
            throw_exception('保存失败！');
        }

        return true;
    }
}
