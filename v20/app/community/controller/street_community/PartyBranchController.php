<?php


namespace app\community\controller\street_community;
use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;
use app\community\model\service\PartyBranchService;
class PartyBranchController extends CommunityBaseController
{
    /**
     * Notes: 获取党支部列表
     * @return \json
     * @author: weili
     * @datetime: 2020/9/14 9:35
     */
    public function getList()
    {
        $info = $this->adminUser;
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if (1==$this->adminUser['area_type'] && $this->adminUser['area_pid']) {
            // 如果是社区  查询父级街道信息
            $street_id = $this->adminUser['area_pid'];
        }
        $keyword = $this->request->param('name','','trim');
        $page = $this->request->param('page',0,'intval');
        $limit_page = 10;
        if($page)
        {
            $page = $page-1;
            $limit = $page*$limit_page;
        }else{
            $page = 0;
            $limit = $limit_page;
        }
        $servicePartyBranch = new PartyBranchService();
        try {
            $list = $servicePartyBranch->getList($street_id,$keyword,$page,$limit);

        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['area_type'] = $this->adminUser['area_type'];
        $list['total_limit'] = $limit_page;
        return api_output(0, $list, "成功");
    }
    //获取社区及社区下的小区
    public function getCommunity()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if (1==$this->adminUser['area_type'] && $this->adminUser['area_pid']) {
            // 如果是社区  查询父级街道信息
            $street_id = $this->adminUser['area_pid'];
        }
        $party_id = $this->request->param('party_id','','intval');
        $servicePartyBranch = new PartyBranchService();
        try {
            $list = $servicePartyBranch->getCommunity($street_id,$party_id, $this->adminUser['area_type']);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");
    }

    /**
     * Notes: 添加/编辑党支部
     * @return \json
     * @author: weili
     * @datetime: 2020/9/14 13:48
     */
    public function addPartyBranch()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        if (1==$this->adminUser['area_type']) {
            // 如果是社区  查询父级街道信息
            return api_output_error(1001,'请联系街道添加');
        }
        $id = $this->request->param('id',0,'intval');
        $name = $this->request->param('name','','trim');
        $details = $this->request->param('details');
        $type = $this->request->param('type',0,'intval');
        $long = $this->request->param('long','');
        $lat = $this->request->param('lat','');
        $adress = $this->request->param('adress','');
        if(!$name){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!$type){
            return api_output_error(1001,'请选择类型');
        }
        $community = $this->request->param('community');
        $data = [
            'name'=>$name,
            'details'=>$details,
            'type'=>$type,
            'long'=>$long,
            'lat'=>$lat,
            'adress'=>$adress,
        ];
        if($community)
        {
            $data['community'] = $community;
        }
        $servicePartyBranch = new PartyBranchService();
        try {
            $list = $servicePartyBranch->addPartyBranch($data,$street_id,$id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list, "成功");

    }

    /**
     * Notes: 获取党建支部详情
     * @return \json
     * @author: weili
     * @datetime: 2020/9/14 18:02
     */
    public function getPartyInfo()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id',0,'intval');
        $servicePartyBranch = new PartyBranchService();
        try {
            $info = $servicePartyBranch->getPartyInfo($id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }

    /**
     *获取党支部类型
     * @author: liukezhu
     * @date : 2022/4/23
     * @return \json
     */
    public function getPartyType(){
        try {
            $info = (new PartyBranchService())->PartyBranchType;
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }

    /**
     * 删除党支部
     * @author: liukezhu
     * @date : 2022/4/23
     * @return \json
     */
    public function delPartyBranch(){
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id',0,'intval');
        $servicePartyBranch = new PartyBranchService();
        try {
            $info = $servicePartyBranch->delPartyBranch($street_id,$id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }

    /**
     * 获取街道经纬度
     * @author: liukezhu
     * @date : 2022/4/23
     * @return \json
     */
    public function getPartyLocation(){
        $street_id = $this->adminUser['area_id'];
        try{
            $list = (new AreaStreetService())->getAreaStreetFind($street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

}