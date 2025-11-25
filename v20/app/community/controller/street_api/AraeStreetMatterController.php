<?php


namespace app\community\controller\street_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetMatter;

class AraeStreetMatterController extends CommunityBaseController
{
    /**
     * 事项分类
     * @author lijie
     * @date_time 2020/09/21
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMatterCategoryLists()
    {
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_matter_service = new AreaStreetMatter();
        $where['area_id'] = $street_id;
        $where['cat_status'] = 1;
        $field = 'cat_id,cat_name';
        $data = $area_street_matter_service->getMatterCategoryLists($where,$field,'cat_sort DESC');
        return api_output(0,$data);
    }

    /**
     * 事项详情
     * @author lijie
     * @date_time 2020/09/21
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMatterDetail()
    {
        $matter_id = $this->request->post('matter_id',0);
        if(!$matter_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_matter_service = new AreaStreetMatter();
        $where['matter_id'] = $matter_id;
        $field = 'title,content,matter_id,add_time,read_sum';
        $data = $area_street_matter_service->getMatterDetail($where,$field);
        $data['share_info']=[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=>cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>$data['title'],
            'info'=>stringText($data['content'])
        ];
        return api_output(0,$data);
    }

    /**
     * 事项列表
     * @author lijie
     * @date_time 2020/09/21
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMatterLists()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $cat_id = $this->request->post('cat_id',0);
        $area_street_matter_service = new AreaStreetMatter();
        if($cat_id)
            $where['cat_id'] = $cat_id;
        $where['status'] = 1;
        $field = 'title,add_time,title_img,matter_id';
        $data = $area_street_matter_service->getMatterLists($where,$field,$page,$limit,'is_hot,matter_id DESC');
        return api_output(0,['list'=>$data,'share_info'=>[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=> cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>'事项管理',
            'info'=>'事项管理，进入可查看详情。'
        ]]);
    }
}