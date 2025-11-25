<?php


namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseVillageDepositService;
use app\community\model\service\HouseAdminService;
use think\facade\Request;

class DepositController extends BaseController
{
    /**
     *  添加押金管理
     * @author lijie
     * @date_time 2020/07/14 13:30
     * @param Request $request
     * @return \json
     */
    public function addDeposit(Request $request)
    {
        $info = $this->getLoginInfo();
        if (isset($info['status']) && $info['status']!=1000) {
            return api_output($info['status'],[],$info['msg']);
        }
        $post_params = $this->request->post();
        if(empty($post_params['village_id']) || empty($post_params['pigcms_id']) || empty($post_params['pay_type']) || empty($post_params['deposit_name']) || empty($post_params['payment_money']) || empty($post_params['actual_money']) || empty($post_params['room_num']))
            return api_output_error(1001,'必传参数缺失');
        $data['toll_collector'] = $info['user']['login_name'];
        $data['village_id'] = $post_params['village_id'];
        $data['pigcms_id'] = $post_params['pigcms_id'];
        $data['room_num'] = $post_params['room_num'];
        $data['pay_type'] = $post_params['pay_type'];
        $data['deposit_name'] = $post_params['deposit_name'];
        $data['deposit_balance'] = $post_params['payment_money'];
        $data['payment_money'] = $post_params['payment_money'];
        $data['actual_money'] = $post_params['actual_money'];
        $data['deposit_note'] = $post_params['deposit_note'];
        $data['pay_time'] = time();
        $house_village_deposit = new HouseVillageDepositService();
        $res = $house_village_deposit->addDeposit($data);
        if($res)
            return api_output(0,'','押金添加成功');
        else
            return api_output_error(1003,'服务异常');
    }

    /**
     * 根据用户名和手机号搜索用户
     * @author lijie
     * @date_time 2020/07/14 13:50
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function searchUser()
    {
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        if(empty($con))
            return api_output_error(1001,'必传参数缺失');
        $house_village_user = new HouseVillageUserService();
        $where[] = ['name','like',"%$con%"];
        $whereOr[] = ['phone','like',"%$con%"];
        $field = 'name,phone,address,pigcms_id,village_id,single_id,floor_id,layer_id,vacancy_id';
        $user_lists = $house_village_user->getUserListsByUserPhoneOrName($where,$whereOr,$field,$page,$limit);
        return api_output(0,$user_lists,'查询成功');
    }

    /**
     * 根据用户名和手机号获取房间信息
     * @author lijie
     * @date_time 2020/07/24 11:17
     * @return \json
     */
    public function searchRoom()
    {
        $con = $this->request->post('con','');
        $village_id = $this->request->post('village_id',0);
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_user_vacancy = new HouseVillageUserVacancyService();
        $where[] = ['a.village_id','=',$village_id];
        $where[] = ['b.parent_id','=',0];
        $where[] = ['b.vacancy_id','>',0];
        $where[] = ['b.type','in','0,3'];
        $where[]=   ['b.status','=',1];
        if($con){
            $where[] = ['a.name|a.phone','like',"%$con%"];
        }
        $field = 'a.name,a.phone,b.address,a.room as room_num,b.pigcms_id,a.usernum,b.bind_number,a.single_id,a.floor_id,a.layer_id,a.pigcms_id as room_id,a.village_id';
        $order = 'a.pigcms_id DESC';
        $room_lists = $house_village_user_vacancy->getRoomListsByUserPhoneOrName($where,$field,$order,$page,$limit);
        return api_output(0,$room_lists,'查询成功');
    }

    /**
     * 根据条件获取押金列表
     * @author lijie
     * @date_time 2020/07/14 14:28
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function RefundLists()
    {
        $is_refund = $this->request->post('is_refund',1);
        $village_id = $this->request->post('village_id',0);
        $con = $this->request->post('con','');
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',15);
        if(!$village_id)
            return api_output_error(1001,'必传参数缺失');
        $where[] = ['hvd.is_refund','=',$is_refund];
        $where[] = ['hvd.is_del','=',1];
        $where[] = ['hvd.village_id','=',$village_id];
        if(!empty($con))
            $where[] = ['hvub.name|hvub.phone','like',"%$con%"];
        $field = 'hvd.deposit_id,hvd.actual_money,hvd.refund_time,hvd.pay_time,hvub.name,hvub.phone,hvd.is_refund,hvub.single_id,hvub.floor_id,hvub.layer_id,hvub.vacancy_id,hvub.village_id';
        $house_village_deposit = new HouseVillageDepositService();
        $lists = $house_village_deposit->getDepositLists($where,$field,$page,$limit);
        return api_output(0,$lists,'查询成功');
    }

    /**
     * 获取押金详细信息
     * @author lijie
     * @date_time 2020/07/14 15：16
     * @return \json
     */
    public function refundDetail()
    {
        $deposit_id = $this->request->post('deposit_id',0);
        if(empty($deposit_id))
            return api_output_error(1001,'必传参数缺失');
        $where['hvd.deposit_id'] = $deposit_id;
        $field = 'hvd.is_refund,hvd.refund_money,hvd.deposit_note,hvd.refund_note,hvd.deposit_name,hvd.payment_money,hvd.actual_money,hvd.toll_collector,hvd.deposit_balance,hvd.pay_time,hvd.refund_time,hvub.name,hvub.address,hvub.single_id,hvub.floor_id,hvub.layer_id,hvub.vacancy_id,hvub.village_id,pt.name as pay_name';
        $house_village_deposit = new HouseVillageDepositService();
        $detail = $house_village_deposit->getDepositDetail($where,$field);
        return api_output(0,$detail,'查询成功');
    }

    /**
     * 修改退款
     * @author lijie
     * @date_time 2020/07/14 15：55
     * @return \json
     */
    public function refundDeposit()
    {
        $post_params = $this->request->post();
        if(empty($post_params['deposit_id']) || empty($post_params['refund_money']))
            return api_output_error(1001,'请输入退款金额');
        $house_village_deposit = new HouseVillageDepositService();
        $detail = $house_village_deposit->getDepositDetail(['hvd.deposit_id'=>$post_params['deposit_id']],'hvd.actual_money');
        if($post_params['refund_money'] > $detail['actual_money'])
            return api_output_error(1003,'退款金额不能大于实收金额');
        $where['deposit_id'] = $post_params['deposit_id'];
        $data['refund_money'] = $post_params['refund_money'];
        $data['refund_note'] = $post_params['refund_note'];
        $data['refund_time'] = time();
        $data['is_refund'] = 2;
        $res = $house_village_deposit->saveDeposit($where,$data);
        if($res)
            return api_output(0,'','押金退款成功');
        else
            return api_output_error(1003,'服务异常');
    }

    /**
     * 删除押金记录
     * @author lijie
     * @date_time 2020/07/14 16:25
     * @return \json
     */
    public function delDeposit()
    {
        $deposit_id = $this->request->post('deposit_id',0);
        if(empty($deposit_id))
            return api_output_error(1001,'必传参数缺失');
        $where['deposit_id'] = $deposit_id;
        $data['is_del'] = 2;
        $data['delete_time'] = time();
        $house_village_deposit = new HouseVillageDepositService();
        $res = $house_village_deposit->saveDeposit($where,$data);
        if($res)
            return api_output(0,'','押金退款成功');
        else
            return api_output_error(1003,'服务异常');
    }

    /**
     * 取线下支付类型
     * @author lijie
     * @date_time 2020/07/14 16:55
     * @return \json
     */
    public function getPay()
    {
        $village_id = $this->request->post('village_id',0);
        if(empty($village_id))
            return api_output_error(1001,'必传参数缺失');
        $house_village_deposit = new HouseVillageDepositService();
        $where['village_id'] = $village_id;
//        $lists = $house_village_deposit->getPay($where);
        $lists = $house_village_deposit->getPayList($village_id);
        return api_output(0,$lists,'查询成功');
    }
}