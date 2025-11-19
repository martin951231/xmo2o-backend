<?php

namespace app\recruit\controller\api;

use app\recruit\model\service\HrService;
use think\Exception;

/**
 * 招聘者
 * @package app\recruit\controller\api
 */
class HrController extends ApiBaseController
{
    /**
     * 我的首页
     * @date: 2021/06/25
     */
    public function center()
    {
        $this->checkLogin();
        $user = $this->userInfo;
        $hrService = new HrService();
        $phone = $user['phone'] ?? '';
        $info = $hrService->getInfoByPhone($phone);
        if (empty($info)) {
            return api_output_error(1003, L_('用户信息不存在'));
        }
        $company = $hrService->getCompany($info['mer_id']);
        if (empty($company)) {
            return api_output_error(1003, L_('公司信息不存在'));
        }
        $retval = [
            'user' => [
                'name' => $info['first_name'] . $info['last_name'],
                'avatar' => replace_file_domain($user['avatar']),
            ],
            'compay' => [
                'logo' => $company['logo'] ?? '',
                'name' => $company['name'] ?? '',
                'industry' => $company['industry'] ?? '',
                'nature' => $company['nature'] ?? '',
                'people_scale' => $company['people_scale'] ?? '',
                'financing_status' => $company['financing_status'] ?? '',
                'address' => $company['address'] ?? '',
                'intro' => $company['intro'] ?? '',
                'images' => $company['images'] ?? [],
            ]
        ];
        return api_output(0, $retval);
    }

    /**
     * 我的
     * @date: 2021/06/25
     */
    public function my()
    {
        $this->checkLogin();
        $phone = $this->userInfo['phone'] ?? '';
        $info = (new HrService())->getInfoByPhone($phone);
        if ($info) {
            $info['sex'] = $info['sex'] == 1 ? L_('男') : L_('女');
            return api_output(0, $info);
        } else {
            return api_output(1003, [], L_('用户信息不存在'));
        }
    }

    /**
     * 保存对外公开信息
     * @date: 2021/06/25
     */
    public function saveShowSet()
    {
        $this->checkLogin();
        $values = $this->request->param('values', []);
        $phone = $this->userInfo['phone'] ?? '';
        try {
            (new HrService())->saveShowSet($phone, $values);
            return api_output(1000, []);
        } catch (Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }
}