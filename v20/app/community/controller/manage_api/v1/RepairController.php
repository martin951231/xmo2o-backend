<?php
/**
 * 在线保修
 * @author weili
 * @datetime 2020/07/14
**/
namespace app\community\controller\manage_api\v1;
use think\facade\Request;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseVillageRepairListService;
use app\community\model\service\HouseVillageUserBindService;
use think\response\Json;

class RepairController extends BaseController
{
    /**
     * 获取在线报修列表,投诉建议，水电煤上报（工单处理）
     * @author weili
     * @datetime 2020/07/14 11:10
     * @param int status (0未指派 1已指派 2已受理  3已处理 4业主已评价)
     * @param int page (1第一页 2第二页 ，默认第一页)
     * @param string phone 电话
     * @param string nickname 用户姓名
     * @param int    screen (1回复时间倒叙)
     * @param int    type (前端type导航:1待解决，2已解决，0默认全部)
     * @return \json
     */
    public function index()
    {
        $page = $this->request->param('page','0','int');
        $search = $this->request->param('search','','trim');
        $nickname = $this->request->param('nickname','','string');
        $screen = $this->request->param('screen','','int');
        $type = $this->request->param('type','','int');
        $genre = $this->request->param('genre','','int'); //类型（1为在线报修，2为水电煤上报，3为投诉建议）
        $villageId = $this->request->param('village_id','','int');
        $pigcms_id = $this->request->param('pigcms_id','','int');
        if(empty($villageId)){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!in_array($genre,[1,2,3]))
        {
            return api_output_error(1001,'参数异常');
        }
        $where_count = [];
        //此type 只争对前端type导航 定义并非数据库type
        if(!empty($type)){
            if($type == 1)
            {
                $where[] = ['r.status','in',[0,1,2]];
                $where_count[] = ['status','in',[0,1,2]];
            }
            if($type == 2)
            {
                $where[] = ['r.status','in',[3,4]];
                $where_count[] = ['status','in',[3,4]];
            }
        }
        if($search){
            $where[] = ['r.phone|r.user_name|u.name|u.phone','like','%'.$search.'%'];
            $where_count[] = ['phone|user_name','like','%'.$search.'%'];
        }
//        if($nickname){
//            $where[] = ['r.user_name|u.name','like','%'.$nickname.'%'];
//        }
        $order = 'r.time desc,r.pigcms_id desc';
        //按回复时间排序
        if($screen == 1)
        {
            $order = 'r.reply_time desc';
        }
        $where[] = ['r.type','=',$genre];
        $where_count[] = ['type','=',$genre];
        $where[] = ['r.village_id','=',$villageId];
        $where_count[] = ['village_id','=',$villageId];
        $serviceHouseVillageRepairList = new HouseVillageRepairListService();
        $limit = 10;//分页 每页展示对应条数
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        if ($pigcms_id) {
            $where[] = ['r.bind_id','=',$pigcms_id];
        }
        $field='r.pigcms_id,r.village_id,r.type,r.reply_time,r.user_name,r.time,r.wid,r.reply_content,r.reply_time,r.cate_id,r.cate_fid,r.single_id,r.floor_id,r.layer_id,r.vacancy_id,r.comment,r.comment_time,r.status,r.content,r.phone,u.name,u.address,u.phone as uphone,u.single_id as u_single_id,u.floor_id as u_floor_id,u.layer_id as u_layer_id,u.vacancy_id as u_vacancy_id';
        $list = $serviceHouseVillageRepairList->getListSelect($where,$page,$limit,$order,$field);
        // 总条数
        //$count = $serviceHouseVillageRepairList->getRepairCount($where_count);
        //return api_output(0,['list' => $list,'count' => $count]);
        return api_output(0,$list);
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
     * @param int $pigcmsId 工单id
     * @param int $wid 工作人员id
     * @param int $type 工作人员工作性质(0:客服专员，1：技工)
     * @return \json
     * @throws \think\Exception
     * @author weili
     * @datetime 2020/07/14 17:06
     */
    public function saveWorker()
    {
        $pigcmsId = $this->request->param('pigcms_id','','int');
        $wid = $this->request->param('wid','','int');
        $type = $this->request->param('type','','int');
        if(empty($pigcmsId) ||  is_numeric($type) === false){
            return api_output_error(1001,'必传参数缺失');
        }
        if(empty($wid)){
            return api_output_error(1001,'请选择工作人员');
        }
        $where[] = ['pigcms_id','=',$pigcmsId];
        $serviceHouseVillageRepairList = new HouseVillageRepairListService();
        $data = [
            'wid'=>$wid,
            'status'=>1,
        ];
        $res = $serviceHouseVillageRepairList->appointWorker($where,$data,$type,$pigcmsId);
        if($res)
        {
            $data['res'] = $res;
            return api_output(0,$data,'操作成功');
        }else{
            return api_output(1003,[],'操作失败！');
        }
    }

    /**
     * Notes: 投诉建议 (暂弃用 暂合并 index方法里面，后期如果需要拆开本接口可使用)
     * @return \json
     * @author: weili
     * @datetime: 2020/7/18 17:39
     */
    public function complaintsSuggestions()
    {
        $page = $this->request->param('page','0','int');
        $phone = $this->request->param('phone','','trim');
        $nickname = $this->request->param('nickname','','string');
        $screen = $this->request->param('screen','','int');
        $type = $this->request->param('type','','int');
        $villageId = $this->request->param('village_id','','int');
        if(empty($villageId)){
            return api_output_error(1001,'必传参数缺失');
        }
        //此type 只争对前端type导航 定义并非数据库type
        if(!empty($type)){
            if($type == 1)
            {
                $where[] = ['r.status','in',[0,1,2]];
            }
            if($type == 2)
            {
                $where[] = ['r.status','in',[3,4]];
            }
        }
        if($phone){
            $where[] = ['r.phone|u.phone','=',$phone];
        }
        if($nickname){
            $where[] = ['r.user_name|u.name','like','%'.$nickname.'%'];
        }
        $order = 'r.time desc,r.pigcms_id desc';
        //按回复时间排序
        if($screen == 1)
        {
            $order = 'r.reply_time desc,r.pigcms_id desc';
        }
        $where[] = ['r.type','=',3];//type=3表示投诉建议
        $where[] = ['r.village_id','=',$villageId];
        $serviceHouseVillageRepairList = new HouseVillageRepairListService();
        $limit = 10;//分页 每页展示对应条数
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $field='r.pigcms_id,r.reply_time,r.user_name,r.time,r.wid,r.reply_content,r.reply_time,r.cate_id,r.cate_fid,r.comment,r.comment_time,r.status,r.content,r.phone,u.name,u.address,u.phone as uphone';
        $list = $serviceHouseVillageRepairList->getListSelect($where,$page,$limit,$order,$field);
        return api_output(0,$list);
    }

}
