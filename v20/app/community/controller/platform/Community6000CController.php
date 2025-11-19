<?php


namespace app\community\controller\platform;


use app\community\controller\platform\AuthBaseController as BaseController;
use app\community\model\service\Device\Hik6000CCameraService;

class Community6000CController extends BaseController
{
    /**
     * 获取设备列表
     * @return \json
     */
    public function getDeviceHikCloudCommunitiesList() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $page     = $this->request->param('page',    '0','intval');
        $pageSize = $this->request->param('pageSize','20','intval');
        try{
            $param = [
                'page'     => $page,
                'pageSize' => $pageSize,
            ];
            $serviceHik6000CCamera = new Hik6000CCameraService();
            $count = $serviceHik6000CCamera->getDeviceHikCloudCommunitiesCount($param);
            if (!$count || $count <= 0) {
               $serviceHik6000CCamera->getSystemCommunities();
            }
            $count = $serviceHik6000CCamera->getDeviceHikCloudCommunitiesCount($param);
            $data  = $serviceHik6000CCamera->getDeviceHikCloudCommunitiesList($param);
            $data['count']     = intval($count);
            $data['page']      = intval($page);
            $data['pageSize']  = intval($pageSize);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 获取设备云平台相关信息
     * @return \json
     */
    public function getSystemCommunities() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        try{
            $serviceHik6000CCamera = new Hik6000CCameraService();
            $data = $serviceHik6000CCamera->getSystemCommunities();
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }
    
    /**
     * 删除
     * @return \json
     */
    public function deleteSystemCommunities() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $community_id = $this->request->param('community_id',    '','trim');
        try{
            $serviceHik6000CCamera = new Hik6000CCameraService();
            $data = $serviceHik6000CCamera->deleteSystemCommunities($community_id);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 解绑
     * @return \json
     */
    public function unBindHouseToSystemCommunity() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $village_id   = $this->request->param('village_id',    '','intval');
        $community_id = $this->request->param('community_id',    '','trim');
        try{
            $serviceHik6000CCamera = new Hik6000CCameraService();
            $data = $serviceHik6000CCamera->unBindHouseToSystemCommunity($village_id, $community_id);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 获取社区
     * @return \json
     */
    public function getCommunity() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $community_id = $this->request->param('community_id',    '','trim');
        try{
            $serviceHik6000CCamera = new Hik6000CCameraService();
            $data = $serviceHik6000CCamera->getCommunity($community_id);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 获取小区
     * @return \json
     */
    public  function getVillageList(){
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        try{
            $serviceHouseMeter = new Hik6000CCameraService();
            $data = $serviceHouseMeter->getVillageList();
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * 绑定社区和小区
     * @return \json
     */
    public function bindHouseToSystemCommunity() {
        // 验证是否登陆
        if(!$this->_uid){
            return api_output_error(1002);
        }
        $community_id = $this->request->param('community_id', '','trim');
        $village_id   = $this->request->param('village_id',   '','intval');
        try{
            $serviceHik6000CCamera = new Hik6000CCameraService();
            $data = $serviceHik6000CCamera->bindHouseToSystemCommunity($village_id, $community_id);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }
}