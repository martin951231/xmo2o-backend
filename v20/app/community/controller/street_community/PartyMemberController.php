<?php
/**
 * 党员管理相关
 * @author weili
 * @date 2020/9/16
 */

namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\PartyBranchService;
use app\community\model\service\PartyMemberService;
use file_handle\FileHandle;

class PartyMemberController extends CommunityBaseController
{
    /**
     * Notes: 获取党员信息列表
     * @return \json
     * @author: weili
     * @datetime: 2020/9/16 13:44
     */
    public function getList(){
        $street_id = 0;
        $street_id_tmp = 0;
        if ($this->adminUser['area_type']==1) {
            $street_id_tmp= $this->adminUser['area_pid'];
            $community_id = $this->adminUser['area_id'];
        } else {
            $street_id = $this->adminUser['area_id'];
            $street_id_tmp=$street_id;
            $community_id = 0;
        }
        if(!$street_id && !$community_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyMember = new PartyMemberService();
        $page = $this->request->param('page','1','intval');
        $name = $this->request->param('name','','trim');
        $phone = $this->request->param('phone','','trim');
        $party_status = $this->request->param('party_status',0,'intval');
        $xtype = $this->request->param('xtype','','trim');  //workerMemberParty  街道工作人员的党员
        $party_branch_type = $this->request->param('party_branch_type',0,'intval');
        $keyword = [
            'name'=>$name,
            'phone'=>$phone,
            'party_status'=>$party_status,
            'party_branch_type'=>$party_branch_type
        ];
        $limit = $this->request->param('limit','10','intval');
        if(isset($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']) && !empty($this->adminUser['street_worker']['village_ids'])){
            $village_ids=explode(',',$this->adminUser['street_worker']['village_ids']);
            if(!empty($village_ids)){
                $keyword['village_ids']=$village_ids;
            }
        }
        try {

            if($xtype==='workerMemberParty'){
                $list = $servicePartyMember->getWorkerMemberList($street_id_tmp,$page,$limit,$keyword, $community_id);
            }else{
                $list = $servicePartyMember->getList($street_id,$page,$limit,$keyword, $community_id);
            }
            
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        return api_output(0, $list, "成功");
    }

    /**
     * Notes: 编辑党员资料
     * @return \json
     * @author: weili
     * @datetime: 2020/9/17 15:35
     */
    public function editPartyMember()
    {
        $post = $this->request->param();
        $servicePartyMember = new PartyMemberService();
        try {
            $res= $servicePartyMember->editPartyMember($post);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res)
        {
            return api_output(0, $res, "成功");
        }else{
            return api_output_error(-1, "失败");
        }

    }

    /**
     * Notes: 获取用户资料
     * @return \json
     * @author: weili
     * @datetime: 2020/9/16 15:35
     */
    public function getPartyMemberInfo()
    {
        $street_id = $this->adminUser['area_id'];
        $area_type = $this->adminUser['area_type'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyMember = new PartyMemberService();
        try {
            $data = $servicePartyMember->getInfo($id,$street_id,$area_type);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }
    public function upload(){
        $file = $this->request->file('img');
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public_upload')->putFile( 'party',$file);
        if(strpos($savename,"\\") !== false){
            $savename = str_replace('\\','/',$savename);
        }
        $imgurl = '/upload/'.$savename;
        $params = ['savepath'=>'/upload/' . $imgurl];

        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image', $params);
        }
        return api_output(0, $imgurl, "成功");
    }


    /**
     * 查询党支部集合
     * @author: liukezhu
     * @date : 2022/10/29
     * @return \json
     */
    public function getPartyBranchAll(){
        if ($this->adminUser['area_type']==1) {
            $street_id = $this->adminUser['area_pid'];
            $community_id = $this->adminUser['area_id'];
        } else {
            $street_id = $this->adminUser['area_id'];
            $community_id = 0;
        }
        if(!$street_id && !$community_id){
            return api_output_error(1001,'必传参数缺失');
        }
        try {
            $info = (new PartyBranchService())->getPartyBranchAll($street_id);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }

    /**
     * 查询党员所在房间列表
     * @author: liukezhu
     * @date : 2022/11/1
     * @return \json
     */
    public function getPartyMemberRoomInfo(){
        if ($this->adminUser['area_type']==1) {
            $street_id = 0;
            $community_id = $this->adminUser['area_id'];
        } else {
            $street_id = $this->adminUser['area_id'];
            $community_id = 0;
        }
        if(!$street_id && !$community_id){
            return api_output_error(1001,'您不属于当前社区，是否去绑定社区？');
        }
        $page = $this->request->param('page','1','intval');
        $limit = $this->request->param('limit','10','intval');
        $uid = $this->request->param('uid','0','intval');
        $param=[
            'street_id'=>$street_id,
            'community_id'=>$community_id,
            'uid'=>$uid,
            'page'=>$page,
            'limit'=>$limit
        ];
        try {
            $list = (new PartyMemberService())->getPartyMemberRoomInfo($param);
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        return api_output(0, $list, "成功");
    }
}