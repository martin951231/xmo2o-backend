<?php


namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetPartyBuildService;

class PartyBuildController extends CommunityBaseController
{

    /**
     * 党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildCategoryLists()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        if($this->request->post('cat_name',''))
            $where[]  = ['cat_name','like','%'.$this->request->post('cat_name').'%'];
        $where[] = ['area_id','=',$street_id];
        $where[] = ['cat_status','<>',2];
        $field = true;
        $data = $area_street_party_build_service->getPartyBuildCategoryLists($where,$field,'cat_sort DESC','end');
        $data['total_limit'] = 10;
        return api_output(0,$data);
    }

    /**
     * 党内资讯详情
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function PartyBuildCategoryDetail()
    {
        $cat_id = $this->request->post('cat_id',0);
        if(!$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['cat_id'] = $cat_id;
        $data = $area_street_party_build_service->PartyBuildCategoryDetail($where,true);
        return api_output(0,$data);
    }

    /**
     * 删除党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     * @throws \Exception
     */
    public function delPartyBuildCategory()
    {
        $cat_id = $this->request->post('cat_id',0);
        if(!$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['cat_id'] = $cat_id;
        $res = $area_street_party_build_service->delPartyBuildCategory($where);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 编辑党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     */
    public function savePartyBuildCategory()
    {
        $cat_name = $this->request->post('cat_name','');
        $cat_sort = $this->request->post('cat_sort',0);
        $cat_status = $this->request->post('cat_status',1);
        $cat_id = $this->request->post('cat_id',0);
        if(!$cat_id || !$cat_name)
            return api_output_error(1001,'必传参数缺失');
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['cat_id'] = $cat_id;
        $data['cat_name'] = $cat_name;
        $data['cat_sort'] = $cat_sort;
        $data['cat_status'] = $cat_status;
        $res = $area_street_party_build_service->savePartyBuildCategory($where,$data);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'未修改');
    }

    /**
     * 添加党内资讯分类
     * @author lijie
     * @date_time 2020/10/14
     * @return \json
     */
    public function addPartyBuildCategory()
    {
        $cat_name = $this->request->post('cat_name','');
        $cat_sort = $this->request->post('cat_sort',0);
        $cat_status = $this->request->post('cat_status',1);
        $street_id = $this->adminUser['area_id'];
        if(!$street_id || !$cat_name)
            return api_output_error(1001,'必传参数缺失');
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $data['cat_name'] = $cat_name;
        $data['cat_sort'] = $cat_sort;
        $data['cat_status'] = $cat_status;
        $data['area_id'] = $street_id;
        $res = $area_street_party_build_service->addPartyBuildCategory($data);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 党内资讯列表
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPartyBuildLists()
    {
        $cat_id = $this->request->post('cat_id',0);
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        $date = $this->request->post('date',[]);
        if(!$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $start_time=$end_time=0;
        if(!empty($date)){
            $start_time=isset($date[0]) ? strtotime($date[0].' 00:00:00') : 0;
            $end_time=isset($date[1]) ? strtotime($date[1].' 23:59:59') : 0;
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        if($this->request->post('title','')){
            $where[] = ['title','like','%'.$this->request->post('title','').'%'];
        }
        if($start_time && $end_time){
            $where[] = ['add_time','between',[$start_time,$end_time]];
        }
        elseif ($start_time){
            $where[] = ['add_time','>=',$start_time];
        }
        elseif ($end_time){
            $where[] = ['add_time','<=',$end_time];
        }

        $where[] = ['cat_id','=',$cat_id];
        $where[] = ['status','in','1,2'];
        $field = true;
        $data = $area_street_party_build_service->getPartyBuildLists($where,$field,$page,$limit,'is_hot,build_id DESC','end');
        $data['total_limit'] = $limit;
        $data['cat_id'] = $cat_id;
        return api_output(0,$data);
    }

    /**
     * 党建资讯详情
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function PartyBuildDetail()
    {
        $build_id = $this->request->post('build_id',0);
        if(!$build_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['build_id'] = $build_id;
        $data = $area_street_party_build_service->getPartyBuildDetail($where,true);
        return api_output(0,$data);
    }

    /**
     * 添加党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     */
    public function addPartyBuild()
    {
        $street_id = $this->adminUser['area_id'];
        $postData = $this->request->post();
        if(!$street_id || !$postData['cat_id'] || !$postData['title'] || !$postData['content']){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $postData['area_id'] = $street_id;
        $postData['add_time'] = time();
        $res = $area_street_party_build_service->addPartyBuild($postData);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 编辑党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     */
    public function savePartyBuild()
    {
        $postData = $this->request->post();
        if(!$postData['build_id'] || !$postData['cat_id'] || !$postData['title'] || !$postData['content']){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['build_id'] = $postData['build_id'];
        $res = $area_street_party_build_service->savePartyBuild($where,$postData);
        return api_output(0,'','成功');
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 删除党内资讯
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     */
    public function delPartyBuild()
    {
        $build_id = $this->request->post('build_id',0);
        if(!$build_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $data['status'] = 3;
        $where['build_id'] = $build_id;
        $res = $area_street_party_build_service->savePartyBuild($where,$data);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 党内资讯评论列表
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     */
    public function getPartyBuildReplyLists()
    {
        $street_id = $this->adminUser['area_id'];
        $build_id = $this->request->post('build_id',0);
        $page = $this->request->post('page',1);
        if(!$build_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where[] = ['r.build_id','=',$build_id];
        $where[] = ['r.status','in','1,3'];
        $data = $area_street_party_build_service->getPartyBuildReplyLists($where,'r.*,u.nickname',$page,10,'','end',$street_id);
        $data['build_id'] = $build_id;
        return api_output(0,$data);
    }

    /**
     * 删除党内资讯评论
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     * @throws \Exception
     */
    public function delPartyBuildReply()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['pigcms_id'] = $pigcms_id;
        $data['status'] = 2;
        $res = $area_street_party_build_service->delPartyBuildReply($where,$data);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 修改党内资讯评论
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     */
    public function changeReplyStatus()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        $data = $this->request->post();
        if(!$pigcms_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['pigcms_id'] = $pigcms_id;
        $res = $area_street_party_build_service->savePartyBuildReply($where,$data);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 评论是否需要审核
     * @author lijie
     * @date_time 2020/10/15
     * @return \json
     */
    public function isSwitch()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $post = $this->request->post();
        $area_street_party_build_service = new AreaStreetPartyBuildService();
        $where['area_id'] = $street_id;
        $res = $area_street_party_build_service->changeAreaStreetConfig($where,$post);
        if($res)
            return api_output(0,'','成功');
        else
            return api_output_error(-1,'失败');
    }

    /**
     * 发送模板消息通知
     * @author lijie
     * @date_time 2020/12/17
     * @return \json
     */
    public function weChatNotice()
    {
        $street_id = $this->adminUser['area_id'];
        $build_id = $this->request->post('id',0);
        if(!$street_id || !$build_id){
            return api_output_error(1001,'必传参数缺失');
        }
        //发送模板消息。。。
        $service_area_stret_party_build = new AreaStreetPartyBuildService();
        $res = $service_area_stret_party_build->savePartyBuild(['build_id'=>$build_id],['is_notice'=>1]);
        return api_output(0,'','成功');
    }
}