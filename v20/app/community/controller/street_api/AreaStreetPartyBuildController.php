<?php


namespace app\community\controller\street_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetPartyBuildService;

class AreaStreetPartyBuildController extends CommunityBaseController
{
    /**
     * 党建资讯分类列表
     * @author lijie
     * @date_time 2020/09/16
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildCategoryLists()
    {
        $street_id = $this->request->post('area_street_id',0);// 街道id
        if(!$street_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['area_id'] = $street_id;
        $where['cat_status'] = 1;
        $field = 'cat_id,cat_name';
        $data = $area_street_party_build_service->getPartyBuildCategoryLists($where,$field,'cat_sort DESC');
        if ($data) {
            $data = $data->toArray();
            array_unshift($data,['cat_id'=>0,'cat_name'=>'全部']);
        }
        return api_output(0,$data);
    }

    /**
     * 党建资讯详情
     * @author lijie
     * @date_time 2020/09/16
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildDetail()
    {
        $build_id = $this->request->post('build_id',0);
        if(!$build_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['build_id'] = $build_id;
        $field = 'title,content,build_id,add_time,read_sum';
        $data = $area_street_party_build_service->getPartyBuildDetail($where,$field);
        return api_output(0,$data);
    }

    /**
     * 党建资讯列表
     * @author lijie
     * @date_time 2020/09/16
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildLists()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $cat_id = $this->request->post('cat_id',0);
        $street_id = $this->request->post('area_street_id',0);
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        if($cat_id)
            $where['cat_id'] = $cat_id;
        if($street_id)
            $where['area_id'] = $street_id;
        $where['status'] = 1;
        $field = 'title,add_time,title_img,build_id,cat_id';
        $data = $area_street_party_build_service->getPartyBuildLists($where,$field,$page,$limit,'is_hot,build_id DESC');
        return api_output(0,$data);
    }

    /**
     * 党建资讯评论列表
     * @author lijie
     * @date_time 2020/09/16
     * @return \json
     */
    public function getPartyBuildReply()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $build_id = $this->request->post('build_id',0);
        if(!$build_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['r.build_id'] = $build_id;
        $res = $area_street_party_build_service->getAreaConfig(['b.build_id'=>$build_id],'a.party_build_switch');
        if($res['party_build_switch'] == 1)
            $where['r.status'] = 1;
        $field = 'r.content,r.add_time,u.nickname,u.avatar';
        $data = $area_street_party_build_service->getPartyBuildReplyLists($where,$field,$page,$limit,'r.pigcms_id DESC');
        return api_output(0,$data);
    }

    /**
     * 添加党建资讯留言
     * @author lijie
     * @date_time 2020/09/16
     * @return \json
     */
    public function addReply()
    {
        $uid = $this->request->post('uid',0);
        $street_id = $this->request->post('area_street_id',0); // 街道id
        $build_id = $this->request->post('build_id',0);
        $content = $this->request->post('content','');
        if(!$uid || !$street_id || !$build_id || !$content)
            return api_output_error(1001,'缺少必传参数');
        $data['uid'] = $uid;
        $data['area_id'] = $street_id;
        $data['content'] = $content;
        $data['build_id'] = $build_id;
        $data['add_time'] = time();
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $res = $area_street_party_build_service->addReply($data);
        if($res)
            return api_output(0,'','留言成功');
        return api_output_error(1001,'服务异常');
    }

}