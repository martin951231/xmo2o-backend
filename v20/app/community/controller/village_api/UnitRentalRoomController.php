<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      楼栋、单元、楼层管理控制器
 */

namespace app\community\controller\village_api;

use app\community\controller\CommunityBaseController;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\HouseVillageSingleService;
class UnitRentalRoomController extends CommunityBaseController
{

    /**
     * 楼栋列表
     * @return \json
     */
    public function index()
    {
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $keytype = $this->request->post('keytype', '','trim');
        $keyword = $this->request->post('keyword', '','trim');
        $keyword=htmlspecialchars($keyword,ENT_QUOTES);
        $status = $this->request->post('status', 0,'intval');
        $vacancy = $this->request->post('vacancy');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');

        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $where = [];
            $where[] = ['a.village_id', '=', $village_id];
            $where[] = ['a.is_public_rental', '=', 1];
            $where[] = ['b.is_public_rental', '=', 1];
            $where[] = ['a.is_del', '=', 0];
            if(!empty($keytype) && in_array($keytype,array('usernum','name','phone','card_no')) && !empty($keyword)){
                if($keytype=='usernum'){
                    $where[] = ['a.usernum|a.property_number', 'LIKE', "%{$keyword}%"];
                }elseif($keytype=='name'){
                    $user_bind_where=array();
                    $user_bind_where[]=['village_id','=',$village_id];
                    $user_bind_where[]=['name','LIKE',"%{$keyword}%"];
                    $houseVillageUserBindService=new HouseVillageUserBindService();
                    $user_bind = $houseVillageUserBindService->getList($user_bind_where,'vacancy_id');
                    if ($user_bind && !$user_bind->isEmpty()) {
                        $user_binds=$user_bind->toArray();
                        $uids = [];
                        foreach ($user_binds as $k => $v) {
                            $uids[] = $v['vacancy_id'];
                        }
                        $where[] = ['a.pigcms_id', 'in', $uids];
                    }else{
                        $where[] = ['a.pigcms_id', '=', -1];
                    }
                }elseif($keytype=='phone'){
                    $user_bind_where=array();
                    $user_bind_where[]=['village_id','=',$village_id];
                    $user_bind_where[]=['phone','LIKE',"%{$keyword}%"];
                    $houseVillageUserBindService=new HouseVillageUserBindService();
                    $user_bind = $houseVillageUserBindService->getList($user_bind_where,'vacancy_id');
                    if ($user_bind && !$user_bind->isEmpty()) {
                        $user_binds=$user_bind->toArray();
                        $uids = [];
                        foreach ($user_binds as $k => $v) {
                            $uids[] = $v['vacancy_id'];
                        }
                        $where[] = ['a.pigcms_id', 'in', $uids];
                    }else{
                        $where[] = ['a.pigcms_id', '=', -1];
                    }
                }elseif ($keytype=='card_no'){

                }
            }
            if($status==-1){
                $where[] = ['a.status', '=', 0];
            }elseif ($status>0){
                $where[] = ['a.status', '=', $status];
            }
            if(!empty($vacancy)){
                if($vacancy[0]>0){
                    $where[] = ['a.single_id', '=', $vacancy[0]];
                }
                if(isset($vacancy[1]) && $vacancy[1]>0){
                    $where[] = ['a.floor_id', '=', $vacancy[1]];
                }
                if(isset($vacancy[2]) && $vacancy[2]>0){
                    $where[] = ['a.layer_id', '=', $vacancy[2]];
                }
                if(isset($vacancy[3]) && $vacancy[3]>0){
                    $where[] = ['a.pigcms_id', '=', $vacancy[3]];
                }
            }
            $field = "a.*,b.single_name,c.floor_name,d.layer_name";
            $oderby = ['sort' => 'DESC', 'pigcms_id' => 'DESC'];
            $datas = $houseVillageUserVacancyService->getVillageVacancyList($where, $field, $oderby,$page,$limit);
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $datas['excelExportInUrl']=$site_url.'/shequ.php?g=House&c=Unit&a=import_village_add&export_from=unitRental';
            $datas['excelExportOutUrl']=$site_url.'/shequ.php?g=House&c=Unit&a=roomExport&export_from=unitRental';
            $datas['excelExportOutFileUrl']=$site_url.'/index.php?g=Index&c=ExportFile&a=download_export_file&export_from=unitRental';
            return api_output(0, $datas);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //获取详情 由于编辑
    public function getRoomDetail(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pigcms_id = $this->request->post('pigcms_id', '0','intval');
        if($pigcms_id<1){
            return api_output(1001, [], '参数房间ID错误');
        }
        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $where = [];
            $where[] = ['village_id', '=', $village_id];
            $where[] = ['pigcms_id', '=', $pigcms_id];
            $where[] = ['is_public_rental', '=', 1];
            $roomData=$houseVillageUserVacancyService->getUserVacancyEditInfo($where);
            $ret=array('roominfo'=>$roomData);
            $ret['room_types']=$houseVillageUserVacancyService->get_room_types();
            return api_output(0, $ret);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //编辑
    public function saveRoomEdit(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pigcms_id = $this->request->post('pigcms_id', '0','intval');
        $contract_time_start_str = $this->request->post('contract_time_start_str', '', 'trim');
        $contract_time_end_str = $this->request->post('contract_time_end_str', '', 'trim');
        $room = $this->request->post('room', '', 'trim');
        $room_number = $this->request->post('room_number', '', 'trim');
        $housesize = $this->request->post('housesize', '', 'trim');
        $house_type = $this->request->post('house_type', 0, 'intval');
        $room_type = $this->request->post('room_type', 0, 'intval');
        $user_status = $this->request->post('user_status', 0, 'intval');
        $sell_status = $this->request->post('sell_status', 0, 'intval');
        $sort = $this->request->post('sort', 0, 'intval');
        $status = $this->request->post('status', 0, 'intval');
        if($pigcms_id<1){
            return api_output(1001, [], '参数房间ID错误');
        }
        $savedata=array();
        $contract_time_start=0;
        if(!empty($contract_time_start_str)){
            $contract_time_start=strtotime($contract_time_start_str);
        }
        $savedata['contract_time_start']=$contract_time_start;
        $contract_time_end=0;
        if(!empty($contract_time_end_str)){
            $contract_time_end=strtotime($contract_time_end_str);
        }
        $savedata['contract_time_end']=$contract_time_end;
        if(empty($room)){
            return api_output(1001, [], '请输入房间号！');
        }
        $savedata['room']=$room;
        if(empty($room_number)){
            return api_output(1001, [], '请输入房间编号！');
        }
        $savedata['room_number']=$room_number;
        if(empty($housesize)){
            return api_output(1001, [], '请输入房屋面积！');
        }
        $savedata['housesize']=$housesize;
        $savedata['house_type']=$house_type;
        $savedata['room_type']=$room_type;
        $savedata['user_status']=$user_status;
        $savedata['sell_status']=$sell_status;
        $savedata['sort']=$sort;
        $savedata['status']=$status;
        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $ret=$houseVillageUserVacancyService->saveRoomEdit($pigcms_id,$village_id,$savedata);
            return api_output(0, $ret);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    //删除
    public function deleteRoom(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $pigcms_ids = $this->request->post('pigcms_ids');
        $del_pigcms_ids=array();
        if($pigcms_ids && is_array($pigcms_ids)){
            foreach ($pigcms_ids as $idd){
                $del_pigcms_ids[]=intval($idd);
            }
        }else if($pigcms_ids && !is_array($pigcms_ids) && is_numeric($pigcms_ids)){
            $del_pigcms_ids[]=intval($pigcms_ids);
        }
        if(empty($del_pigcms_ids)){
            return api_output(1001, [], '要删除的房间ID参数出错！');
        }
        $del_pigcms_ids=array_unique($del_pigcms_ids);
        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $ret=$houseVillageUserVacancyService->deleteRoom($village_id,$del_pigcms_ids);
            return api_output(0, $ret);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
    public function getUserRecordList(){

        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $vacancy_id = $this->request->post('vacancy_id',0,'intval');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        if($vacancy_id<1){
            return api_output(1001, [], '参数房间ID错误');
        }
        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $whereArr=[];
            $whereArr[]=['village_id','=',$village_id];
            $whereArr[]=['vacancy_id','=',$vacancy_id];
            $ret=$houseVillageUserVacancyService->getUserRecordList($whereArr,'*',$page,$limit);
            return api_output(0, $ret);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function getRoomIcCardList(){

        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $vacancy_id = $this->request->post('vacancy_id',0,'intval');
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',20,'intval');
        if($vacancy_id<1){
            return api_output(1001, [], '参数房间ID错误');
        }
        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $whereArr=[];
            $whereArr[]=['village_id','=',$village_id];
            $whereArr[]=['vacancy_id','=',$vacancy_id];
            $whereArr[]=['status','<>',4];
            $ret=$houseVillageUserVacancyService->getRoomIcCardList($whereArr,'*',$page,$limit);
            $site_url=cfg('site_url');
            $site_url=trim($site_url,'/');
            $ret['icCardAddUrl']=$site_url.'/shequ.php?g=House&c=User&a=readCard&add_from=unitRental&type=1&vacancy_id='.$vacancy_id;
            return api_output(0, $ret);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }

    public function  deleteRoomIcCard(){
        $village_id = $this->adminUser['village_id'];
        if (empty($village_id)) {
            return api_output(1002, [], '请先登录到小区后台！');
        }
        $idd = $this->request->post('idd',0,'intval');
        if($idd<1){
            return api_output(1001, [], '记录参数出错');
        }
        try {
            $houseVillageUserVacancyService = new HouseVillageUserVacancyService();
            $ret=$houseVillageUserVacancyService->deleteRoomIcCard($idd,$village_id);
            return api_output(0, $ret);
        }catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}