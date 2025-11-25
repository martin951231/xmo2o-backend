<?php
declare(strict_types=1);

namespace app\villageGroup\controller\platform;

use app\villageGroup\model\service\VillageGroupOrderService;

class GroupOrderController extends AuthBaseController
{
    /**
     * 直接用户分享明细列表
     * @return \json
     */
    public function groupOrderShareList()
    {
        $params = request()->post();
        
        empty($params['type']) && $params['type'] = 1;
        $type = [
          1 => 'share_user_name',
          2 => 'user_name',
          3 => 'goods_name'  
        ];

        $params[$type[$params['type']]] = $params['keyword'] ?? '';
        
        $list = app(VillageGroupOrderService::class)->groupOrderShareList($params);
        
        return api_output(0, $list);
    }
}
