<?php
/**
 * 在线保修
 * @author weili
 * @datetime 2020/07/14
**/
namespace app\community\controller\manage_api\v1;
use app\community\model\service\ExpressPickupService;
use app\community\model\service\HouseVillageService;
use think\facade\Request;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseVillageRepairListService;
use app\community\model\service\HouseVillageUserBindService;
use think\response\Json;

class ExpressPickupController extends BaseController
{
    /**
     * 获取快递列表
     * @author mashuaifei
     * @datetime 2025-09-25
     * @return \json
     */
    public function getExpressPickupList()
    {
        $villageId = $this->request->param('village_id','','int');
        $pickup_code = $this->request->param('pickup_code','');
        $recipient_phone = $this->request->param('recipient_phone','');
        $status = $this->request->param('status','');
        $location_id = $this->request->param('location_id','');
        $search = $this->request->param('search','');
        if(empty($villageId)){
            return api_output_error(1001,'登录信息有误, 请重新登录!');
        }

        $where[] = ['p.village_id','=',$villageId];
        $service_express_pick_up = new ExpressPickupService();

        $page = $this->request->post('page',1);
        $limit = $this->request->post('pageSize',10);

        $pickup_code && $where[] = ['p.pickup_code', 'like', '%'.$pickup_code.'%'];
        $recipient_phone && $where[] = ['p.recipient_phone', 'like', '%'.$recipient_phone.'%'];
        ( $status || $status === 0 ) && $where[] = ['p.status', '=', $status];
        $location_id && $where[] = ['p.location_id', '=', $location_id];
        if( $search !== '' ){
             $where[] = ['p.track_num|p.pickup_code|p.recipient_phone|p.recipient_name|e.location', 'like', '%'.$search.'%'];
        }

        $field = 'p.*,e.location';

        try{
            $data = $service_express_pick_up->getExpressApplicationList($where,$field,$page,$limit,'p.id DESC')->toArray();

            $count = $service_express_pick_up->getExpressApplicationCount($where);
        }catch (\Exception $e){
            return api_output_error(-1,$e->getMessage());
        }
        $res=array();
        // 总页数 = 向上取整(总条数 / 每页条数)
        $totalPage = (int)ceil($count / $limit);

        // 是否有下一页：当前页 < 总页数
        $hasNextPage = $page < $totalPage;
        $res['total_limit'] = $limit;
        $res['list'] = $data;
        $res['count'] = $count;
        $res['hasMore'] = $hasNextPage;
        return api_output(0,$res);
    }

    /**
     * 获取在线报修,投诉建议,水电煤上报 详情
     * @author weili
     * @datetime 2020/07/14 13:22
     * @return \Json
     */
    public function getDetails()
    {
        $pigcmsId = $this->request->param('pigcms_id','','int');
        if(empty($pigcmsId)){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceHouseVillageRepairList = new HouseVillageRepairListService();
        $where[] = ['r.pigcms_id','=',$pigcmsId];
        //$field = 'pigcms_id,village_id,uid,bind_id,content,type,time,status,pic,wid,repair_type,repair_time,user_name,cate_id,cate_fid,single_id,floor_id,layer_id,vacancy_id,reply_time,reply_pic,reply_content,msg,comment,comment_pic,comment_time,is_read,score';
        $field = 'r.*,u.usernum,u.bind_number,u.name,u.address,u.phone as uphone,u.single_id as u_single_id,u.floor_id as u_floor_id,u.layer_id as u_layer_id,u.vacancy_id as u_vacancy_id';
        $info = $serviceHouseVillageRepairList->getRepairFind($where,$field);
        return api_output(0,$info);
    }

    /**
     * 指派工作人员
     * @return \json
     * @throws \think\Exception
     * @author weili
     * @datetime 2020/07/14 17:06
     */
    public function pickupActive()
    {
        $village_id = $this->request->param('village_id','','int');
        $id = $this->request->param('id','');
        $img_path = $this->request->param('img_path','');
        $img_path2 = $this->request->param('img_path2','');
        $img_path3 = $this->request->param('img_path3','');
        $reason = $this->request->param('reason','');
        if(empty($village_id)){
            return api_output_error(1001,'必传参数缺失');
        }
        if( empty($id) ){
            return api_output_error(1001,'请选择要取的快递');
        }
        $where[] = ['village_id','=',$village_id];
        $where[] = ['id','=',$id];
        $service_express_pick_up = new ExpressPickupService();

        $data = [
            'status'=>2,
        ];
        $img_path && $data['img_path'] = $img_path;
        $img_path2 && $data['img_path2'] = $img_path2;
        $img_path3 && $data['img_path3'] = $img_path3;
        $reason && $data['reason'] = $reason;
        $res = $service_express_pick_up->updatePickupApplication($where, $data);
        if($res)
        {
            return api_output(0,['success'=>true],'保存成功');
        }else{
            return api_output(1003,[],'操作失败！');
        }
    }
    /**
     * Notes: 上传图片
     * @author: wanzy
     * @date_time: 2021/8/16 14:05
     */
    public function  postEventImg() {
        $file = $this->request->file('imgFile');
        $service_house_village = new HouseVillageService();
        try{
            $imgurl = $service_house_village->uploads($file,'v20ExpressPickup');
            $arr = [];
            $arr['url'] = thumb_img($imgurl,200, 200, 'fill');
            $arr['imageUrl_path'] = $imgurl;
            $arr['imageUrl'] = replace_file_domain($imgurl);
            return api_output(0, $arr);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

}
