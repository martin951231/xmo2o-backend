<?php


namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetNewsService;

class AreaStreetNewsController extends CommunityBaseController
{
    /**
     * 资讯分类列表
     * @author lijie
     * @date_time 2020/09/22
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewsCategoryLists()
    {
        $street_id = $this->adminUser['area_id'];
        $page = $this->request->post('page',0);
        if(!$street_id || !$page)
            return api_output_error(1001,'缺少必传参数');
        $service_area_street_news = new AreaStreetNewsService();
        $where['area_id'] = $street_id;
        $where['cat_status'] = 1;
        $data = $service_area_street_news->getNewsCategoryLists($where,'*','cat_sort DESC',$page=0,$limit=20);

        return api_output(0,$data);
    }
}