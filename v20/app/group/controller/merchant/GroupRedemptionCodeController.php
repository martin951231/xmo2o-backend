<?php


namespace app\group\controller\merchant;


use app\group\model\service\GroupRedemptionCodeService;

class GroupRedemptionCodeController extends AuthBaseController
{
    public function getList()
    {
        $params = [
            'page' => input('page', 1, 'trim,intval'),
            'pageSize' => input('pageSize', 10, 'trim,intval'),
            'search_type' => input('search_type', '', 'trim,string'),
            'keywords' => input('keywords', '', 'trim,string'),
            'start_date' => input('start_date', '', 'trim,string'),
            'end_date' => input('end_date', '', 'trim,string'),
            'status' => input('status', -1, 'trim'),
            'is_export' => input('is_export', 0, 'trim,intval'),
            'mer_id' => $this->merchantUser['mer_id'],
        ];
        try {
            $res = (new GroupRedemptionCodeService())->getList($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}