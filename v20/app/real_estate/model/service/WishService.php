<?php
namespace app\real_estate\model\service;

use app\common\model\db\Admin;
use app\real_estate\model\db\RealEstateProcess;
use app\real_estate\model\db\RealEstateProject;
use app\real_estate\model\db\RealEstateType;
use app\real_estate\model\db\RealEstateWish;
use app\common\model\service\export\ExportService as BaseExportService;
use think\Model;

class WishService
{

    public $process;

    public function __construct(){
        $this->wish = new RealEstateWish();
    }

    /**
     * 购房意愿-获取信息列表
     * @param $param
     * @return array
     */
    public function getList($param){
        $where = [];
        if($param['level']==0){
            $where[] = ['a.uid','=',$param['uid']];
        }
        if($param['search_kewords']){
            if($param['type']==1){
                $where[] = ['a.buyer_name','like','%'.$param['search_kewords'].'%'];
            }elseif($param['type']==2){
                $where[] = ['a.buyer_phone','like','%'.$param['search_kewords'].'%'];
            }
        }
        if($param['search_project']>0){
            $where[] = ['a.project_id','=',$param['search_project']];
        }
        if($param['search_process']>0){
            $where[] = ['a.process_id','=',$param['search_process']];
        }
        if($param['search_pay_type']){
            $where[] = ['a.pay_type','=',$param['search_pay_type']];
        }
        if($param['search_type']){
            $where[] = ['a.type_id','=',$param['search_type']];
        }
        if($param['user_id']>0){
            $where[] = ['a.uid','=',$param['user_id']];
        }
        if($param['search_sdate']&&$param['search_edate']){
            $where[] = ['a.add_time','>=',strtotime($param['search_sdate'].' 00:00:00')];
            $where[] = ['a.add_time','<=',strtotime($param['search_edate'].' 23:59:59')];
        }
        $list = $this->wish->getList($where,$param['page'],$param['page_size']);
        $arr_type = array(1 => '贷款', 2 => '全款', 3 => '其它');
        foreach ($list as $key=>$item){
            $list[$key]['add_time'] = date('Y.m.d H:s',$item['add_time']);
            $list[$key]['pay_type'] = $arr_type[$item['pay_type']];
            $list[$key]['commission_pay_name'] = $item['commission_pay_name']==1?'已结清':'未结清';
            $list[$key]['note'] = $item['note']?:"";
            $list[$key]['account'] = $item['realname']?:"";
        }
        return $list;

    }

    /**
     * 购房意愿-添加信息
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add($param){
        $where = array(
            'project_id' => $param['project_id'],
            'process_id' => $param['process_id'],
            'type_id' => $param['type_id'],
            'pay_type' => $param['pay_type'],
            'buyer_name' => $param['buyer_name'],
            'buyer_phone' => $param['buyer_phone'],
            'referee_name' => $param['referee_name'],
            'referee_phone' => $param['referee_phone'],
            'note' => $param['note'],
            'uid' => $param['uid']
        );
        if(!$where['project_id']){
            throw new \think\Exception('请选择项目！');
        }
        if(!$where['buyer_name']){
            throw new \think\Exception('购房者姓名不能为空！');
        }
        if(!$where['referee_name']){
            throw new \think\Exception('推荐人姓名不能为空！');
        }
        if(!in_array($where['pay_type'],array(1,2,3))){
            throw new \think\Exception('请选择付款类型！');
        }
        if(!$where['type_id']){
            throw new \think\Exception('请选择住房类型！');
        }
        if(!$where['process_id']){
            throw new \think\Exception('请选择购房状态！');
        }

        $phone_preg = '/1[3-9]{1}[0-9]{9}/';
        if(!preg_match($phone_preg,$where['buyer_phone'])){
            throw new \think\Exception('购房者手机号格式错误！');
        }

        if(!preg_match($phone_preg,$where['referee_phone'])){
            throw new \think\Exception('推荐人手机号格式错误！');
        }

        //查询是否重复
        $check_name = $this->wish->where(['buyer_phone'=>$where['buyer_phone'],'project_id'=>$where['project_id']])->find();
        if($check_name){
            throw new \think\Exception('购房者已存在！');
        }

        //查询购房流程是否存在
        $process_info = (new RealEstateProcess())->find($where['process_id']);
        if(!$process_info){
            throw new \think\Exception('购房状态不存在！');
        }
        //查询项目列表是否存在
        $project_info = (new RealEstateProject())->find($where['project_id']);
        if(!$project_info){
            throw new \think\Exception('项目不存在！');
        }
        //查询购房状态是否存在
        $type_info = (new RealEstateType())->find($where['type_id']);
        if(!$type_info){
            throw new \think\Exception('住房类型不存在！');
        }

        $where['add_time'] = time();
        $where['update_time'] = time();
        try {
            $this->wish->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 购房意愿-编辑信息
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($param){
        $where = array(
            'project_id' => $param['project_id'],
            'process_id' => $param['process_id'],
            'type_id' => $param['type_id'],
            'pay_type' => $param['pay_type'],
            'buyer_name' => $param['buyer_name'],
            'buyer_phone' => $param['buyer_phone'],
            'referee_name' => $param['referee_name'],
            'referee_phone' => $param['referee_phone'],
            'note' => $param['note']
        );
        if(!$where['project_id']){
            throw new \think\Exception('请选择项目！');
        }
        if(!$where['buyer_name']){
            throw new \think\Exception('购房者姓名不能为空！');
        }
        if(!$where['referee_name']){
            throw new \think\Exception('推荐人姓名不能为空！');
        }
        if(!in_array($where['pay_type'],array(1,2,3))){
            throw new \think\Exception('请选择付款类型！');
        }
        if(!$where['type_id']){
            throw new \think\Exception('请选择住房类型！');
        }
        if(!$where['process_id']){
            throw new \think\Exception('请选择购房状态！');
        }

        $phone_preg = '/1[3-9]{1}[0-9]{9}/';
        if(!preg_match($phone_preg,$where['buyer_phone'])){
            throw new \think\Exception('购房者手机号格式错误！');
        }

        if(!preg_match($phone_preg,$where['referee_phone'])){
            throw new \think\Exception('推荐人手机号格式错误！');
        }

        //查询是否重复
        $check_name = $this->wish->where(['buyer_phone'=>$where['buyer_phone'],'project_id'=>$where['project_id']])->find();
        if($check_name&&($check_name['id']!=$param['id'])){
            throw new \think\Exception('购房者已存在！');
        }

        //查询购房流程是否存在
        $process_info = (new RealEstateProcess())->find($where['process_id']);
        if(!$process_info){
            throw new \think\Exception('购房状态不存在！');
        }
        //查询项目列表是否存在
        $project_info = (new RealEstateProject())->find($where['project_id']);
        if(!$project_info){
            throw new \think\Exception('项目不存在！');
        }
        //查询购房状态是否存在
        $type_info = (new RealEstateType())->find($where['type_id']);
        if(!$type_info){
            throw new \think\Exception('住房类型不存在！');
        }

        //查询信息是否存在
        $info = $this->wish->find($param['id']);
        if(!$info){
            throw new \think\Exception('修改信息不存在！');
        }
        if($info['uid']!=$param['uid']&&$param['level']!=2){
            throw new \think\Exception('无法修改他人购房者信息！');
        }
        $where['update_time'] = time();
        try {
            $this->wish->where(['id'=>$param['id']])->save($where);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }

        return true;

    }

    /**
     * 购房意愿-获取住宅类型、购房流程、项目列表、付款状态列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOtherList(){
        $data['pay_name_list'] = [
            [
                'id'=>1,
                'value'=>'贷款'
            ],
            [
                'id'=>2,
                'value'=>'全款'
            ],
            [
                'id'=>3,
                'value'=>'其它'
            ]
        ];
        $data['project_list']  = [];
        //获取推荐项目
        $project = (new RealEstateProject())->field('id,name,place')->order('id desc')->select();
        foreach ($project as $item){
            $data['project_list'][] =  [
                'id'=>$item['id'],
                'value'=>$item['name'],
                'place'=>$item['place']
            ];
        }
        $data['process_list'] = [];
        //获取流程
        $process = (new RealEstateProcess())->field('id,name,font_color')->order('sort asc,id asc')->select();
        foreach ($process as $item){
            $data['process_list'][] =  [
                'id'=>$item['id'],
                'value'=>$item['name'],
                'font_color'=>$item['font_color']
            ];
        }
        $data['type_list'] = [];
        //获取房产类型
        $type = (new RealEstateType())->field('id,name')->order('id desc')->select();
        foreach ($type as $item){
            $data['type_list'][] =  [
                'id'=>$item['id'],
                'value'=>$item['name'],
            ];
        }

        return $data;
    }

    /**
     * 购房意愿-获取单个信息详情
     * @param $param
     * @return array|Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function show($param){
        $id = $param['id'];
        if(!$id){
            throw new \think\Exception('信息不存在！');
        }
        $info = $this->wish->find($id);
        if(!$info){
            throw new \think\Exception('信息不存在！');
        }
        if($info['uid']!=$param['uid']&&$param['level']!=2){
            throw new \think\Exception('无法查看他人购房者信息！');
        }
        return $info;
    }

    /**
     * 购房意愿-单个/批量删除
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function delete($param){
        $id = $param['id'];
        if(!is_array($id)){
            $id = array($id);
        }
        if(!$id){
            throw new \think\Exception('信息已删除！');
        }
        $where[] = ['id','in',$id];
        if($param['level']!=2){
            $where[] = ['uid','=',$param['uid']];
        }
        try {
            $this->wish->where($where)->delete();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 购房意愿-修改佣金支付状态
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeStatus($param){
        $id = $param['id'];
        $commission_pay = $param['status'];
        if(!$id){
            throw new \think\Exception('信息不存在！');
        }
        if(!in_array($commission_pay,array(1,0))){
            throw new \think\Exception('状态不存在！');
        }
        $info = $this->wish->find($id);
        if(!$info){
            throw new \think\Exception('信息不存在！');
        }
        if($info['uid']!=$param['uid']&&$param['level']!=2){
            throw new \think\Exception('无法修改他人购房者信息！');
        }
        try {
            $this->wish->where([['id','=',$id]])->save(['commission_pay'=>$commission_pay,'update_time'=>time()]);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 购房意愿-单个/批量修改购房状态
     * @param $param
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeProcess($param){
        $id = $param['id'];
        $process_id = $param['process_id'];
        if(!is_array($id)){
            $id = array($id);
        }
        if(!$id){
            throw new \think\Exception('信息不存在！');
        }
        if(!$process_id){
            throw new \think\Exception('请选择要修改的状态！');
        }
        //查询状态是否存在
        $process_info = (new RealEstateProcess())->field('id')->find($process_id);
        if(!$process_info){
            throw new \think\Exception('状态不存在！');
        }
        $where[] = ['id','in',$id];
        if($param['level']!=2){
            $where[] = ['uid','=',$param['uid']];
        }
        try {
            $this->wish->where($where)->save(['process_id'=>$process_id,'update_time'=>time()]);
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
        return true;
    }

    /**
     * 用户端-房产推荐列表
     * @param $param
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRealStateList($param){
        $where[] = ['a.referee_phone','=',$param['user_phone']];
        if($param['search_process']>0){
            $where[] = ['a.process_id','=',$param['search_process']];
        }
        //获取信息列表
        $list = $this->wish->getList($where,$param['page'],$param['page_size'],'a.id,a.process_id,a.buyer_name,a.buyer_phone,b.sort');

        //获取今日推荐数
        $totay_where[] = ['a.add_time','>=',strtotime(date('Y-m-d 00:00:00'))];
        $totay_where[] = ['a.add_time','<=',strtotime(date('Y-m-d 23:59:59'))];
        $totay_where[] = ['a.referee_phone','=',$param['user_phone']];
        $totay_num = $this->wish->getCount($totay_where);
        $totay_num = $totay_num->toArray()[0]['num'];

        //获取购房状态列表
        $process = (new RealEstateProcess())->field('id,sort,name,font_color')->order('sort asc,id asc')->select();
        $process_list = [];
        foreach ($process as $item){
            $process_list[] = [
                'id' => $item['id'],
                'value' => $item['name'],
                'sort' => $item['sort']
            ];
        }
        $list = $list->toArray();
        $list['today_num'] = $totay_num?:0;
        $list['process_list'] = $process?:[];
        return $list;
    }

    /**
     * 购房意愿-导出数据列表
     * @return \json
     */
    public function exportData($param){
        $list = $this->getList($param);
        $list = $list?$list->toArray():[];
        $csvHead = array(
            L_('客户姓名'),
            L_('手机号'),
            L_('推荐人'),
            L_('推荐人手机号'),
            L_('推荐项目'),
            L_('房产类型'),
            L_('付款方式'),
            L_('当前状态'),
            L_('置业顾问'),
            L_('佣金是否支付'),
            L_('录入时间'),
            L_('备注')
        );
        $csvData = [];
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $csvData[$key] = [
                    $value['buyer_name'],
                    $value['buyer_phone'],
                    $value['referee_name'],
                    $value['referee_phone'],
                    $value['project_name'],
                    $value['type_name'],
                    $value['pay_type'],
                    $value['process_name'],
                    $value['account'],
                    $value['commission_pay_name'],
                    $value['add_time'],
                    $value['note']
                ];
            }
        }
        $filename = date("Y-m-d", time()) . '-购房列表.csv';
        (new BaseExportService())->putCsv($filename, $csvData, $csvHead);
        $file_path = request()->server('REQUEST_SCHEME').'://'.request()->server('SERVER_NAME').'/v20/runtime/'.$filename;
        return array('filename'=>$file_path);
    }

    /**
     * 获取用户列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserList($param){
        if($param['level']!=0){
            $user_list = (new Admin())->field('id,realname')->where('status = 1 and level in (0,1,2)')->select();
            $list[] = [
                'id' => 0,
                'value' => '全部'
            ];
            foreach ($user_list as $item){
                $list[] = [
                    'id' => $item['id'],
                    'value' => $item['realname']
                ];
            }
            $is_show = 1;
        }else{
            $list = [];
            $is_show = 0;
        }
        $data = [
            'data' => $list,
            'is_show' => $is_show
        ];
        return $data;
    }
}