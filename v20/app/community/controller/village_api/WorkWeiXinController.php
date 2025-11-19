<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      小区企微管理
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\QywxService;

class WorkWeiXinController extends CommunityBaseController
{
    public function servicesImgPreview() {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)){
            return api_output(1002, [], '请先登录到小区后台！');
        }
        // 模板类型 0 无需模板 1系统模板 2上传模板
        $template_type = $this->request->param('template_type', '0', 'intval');
        // 模板链接
        $template_url = $this->request->param('template_url', '', 'strval');
        // 企业微信群二维码链接
        $qy_qrcode = $this->request->param('qy_qrcode', '', 'strval');
        if (!$qy_qrcode) {
            return api_output(1001, [], '请上传企业微信群二维码！');
        }
        if (2 == $template_type && !$template_url) {
            return api_output(1001, [], '请上传对应模板！');
        }
        $qy_qrcode = replace_file_domain($qy_qrcode);
        if ($template_url) {
            $template_url = replace_file_domain($template_url);
        }
        try {
            $params = [
                'village_id' => $village_id, 'template_type' => $template_type,
                'template_url' => $template_url, 'qy_qrcode' => $qy_qrcode,
            ];
            $result = (new QywxService())->servicesImgPreview($params);
        } catch (\Exception $e){
            return api_output(1001, [], $e->getMessage());
        }
        return api_output(0, $result);
    }
}