<?php


namespace app\community\controller\manage_api\v1;

use app\community\controller\CommunityBaseController;
use app\community\model\db\User;
use app\community\model\service\HouseVillageService;

class ExpressController extends CommunityBaseController
{
    /**
     * 获取快递列表
     * @author lijie
     * @date_time 2020/08/17 16:57
     * @return \json|mixed
     */
    public function receivedExpress()
    {
        $village_id = $this->request->post('village_id',0);
        $status = $this->request->post('status',0);
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',10);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_service = new HouseVillageService();
        $where[] = ['hve.village_id','=',$village_id];
        $where[] = ['hve.phone|hve.express_no','like',"%$con%"];
        if($status == 0){
            $where[] = ['hve.status','=',0];
        }else{
            $where[] = ['hve.status','in','1,2'];
        }
        $field = 'hve.village_id,e.name,hve.phone,hve.express_no,hve.id,hve.add_time,hve.delivery_time';
        $order = 'hve.id DESC';
        $data = $house_village_service->getExpressLists($where,$field,$page,$limit,$order);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 获取快递详情
     * @author lijie
     * @date_time 2020/08/17 17:04
     * @return \json|mixed
     */
    public function expressDetail()
    {
        $id = $this->request->post('id',0);
        if(!$id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_service = new HouseVillageService();
        $where['hve.id'] = $id;
        $field = 'hve.village_id,hve.phone,hve.status,hve.express_no,e.name,hve.delivery_time,hve.memo,hve.add_time';
        $data = $house_village_service->getExpressDetail($where,$field);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 添加快递
     * @author lijie
     * @date_time 2020/08/18 10:26
     * @return \json
     */
    public function addExpress()
    {
        $post_params = $this->request->post();
        if(!$post_params['village_id'] || !$post_params['express_no'] || !$post_params['phone'] || !$post_params['express_type'])
            return api_output_error(1001,'请完善快递信息');
        $user = New User();
        $user_info = $user->getOne(['phone'=>$post_params['phone']],'uid');
        $fetch_code =rand(100000,999999);
        $inserDatas['uid'] = isset($user_info['uid'])?$user_info['uid']:'';
        $inserDatas['village_id'] = $post_params['village_id'];
        $inserDatas['express_type'] = $post_params['express_type'];
        $inserDatas['express_no'] = $post_params['express_no'];
        $inserDatas['memo'] = isset($post_params['memo'])?$post_params['memo']:'';
        $inserDatas['phone'] = $post_params['phone'];
        $inserDatas['status'] = 0;
        $inserDatas['add_time'] = time();
        $inserDatas['fetch_code'] = $fetch_code;
        $house_village_service = new HouseVillageService();

        $where_replace = [];
        $where_replace[] = ['hve.express_type', '=', $post_params['express_type']];
        $where_replace[] = ['hve.express_no', '=', $post_params['express_no']];
        $field = 'hve.id';
        $replace = $house_village_service->getExpressDetail($where_replace,$field);
        if ($replace) {
            return api_output_error(1003,'当前快递单号已经存在，请检查后重新添加');
        }

        $res = $house_village_service->addExpress($inserDatas);
        if($res)
            return api_output(0,'','添加成功');
        else
            return api_output_error(1001,'服务异常');
    }

    /**
     * 快递类型
     * @author lijie
     * @date_time 2020/08/19 14:19
     * @return \json
     */
    public function getExpressLists()
    {
        $where['status'] = 1;
        $field = 'id as express_type,name';
        $house_village_service = new HouseVillageService();
        $data = $house_village_service->getExpress($where,$field);
        return api_output(0,$data,'获取成功');
    }
}