<?php
/**
 * 功能套餐订单相关
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\controller\platform;
use app\community\model\service\PackageOrderService;
use app\community\controller\platform\AuthBaseController as BaseController;
class PackageOrderController extends BaseController
{
    /**
     * Notes: 获取功能套餐订单列表
     * @return \json
     * @author: weili
     * @datetime: 2020/8/13 14:38
     */
    public function getList()
    {
        $page = $this->request->param('page','0','intval');
        $title = $this->request->param('title','','trim');//套餐标题
        $type = $this->request->param('type','0','intval'); // 1 物业名称/ 2 联系方式
        $matter = $this->request->param('matter','','trim'); //对应type 里面的值
        $start_time = $this->request->param('start_time','','trim'); //开始时间
        $end_time = $this->request->param('end_time','','trim');     //结束时间
        if($title){
            $where[] = ['package_title','like','%'.$title.'%'];
        }
        if($start_time && $end_time){
            $where[] = ['pay_time','>=',strtotime($start_time)];
            $where[] = ['pay_time','<=',strtotime($end_time)];
        }else if($start_time)
        {
            $where[] = ['pay_time','>=',strtotime($start_time)];
        }else if($end_time)
        {
            $where[] = ['pay_time','<=',strtotime($end_time)];
        }
        if($type && $matter){
            if($type == 1)
            {
                $where[] = ['property_name','like','%'.$matter.'%'];
            }
            if($type == 2)
            {
                $where[] = ['property_tel','like','%'.$matter.'%'];
            }
        }
        $limit = 10;
        if($page)
        {
            $page = ($page-1)*$limit;
        }
        $where[] = ['status','=',1];
        $servicePackageOrder = new PackageOrderService();
        try{
            $data = $servicePackageOrder->getPackageOrderList($where,$field=true,$page,$limit);
        }catch(\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes: 获取功能套餐详情
     * @return \json
     * @author: weili
     * @datetime: 2020/8/13 14:47
     */
    public function getInfo()
    {
        $order_id = $this->request->param('order_id','0','intval');
        if(!$order_id){
            return api_output_error(1001,'请上传套餐id！');
        }
        $where[] = ['order_id','=',$order_id];
        $servicePackageOrder = new PackageOrderService();
        try{
            $info = $servicePackageOrder->getPackageOrderInfo($where);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }
}