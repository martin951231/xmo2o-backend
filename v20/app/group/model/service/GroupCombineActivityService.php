<?php
/**
 * 团购优惠组合
 * Author: 衡婷妹
 * Date Time: 2020/11/16 16:29
 */

namespace app\group\model\service;

use app\group\model\db\GroupCombineActivity;
use app\group\model\service\order\GroupOrderService;

class GroupCombineActivityService
{
    public $groupCombineActivityModel = null;

    public function __construct()
    {
        $this->groupCombineActivityModel = new GroupCombineActivity();
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getList($param = [])
    {
        $page = request()->param('page', '0', 'intval');//页码

        $start = 0;
        $pageSize = 0;
        if($page){
            $pageSize = isset($param['pageSize']) ? $param['pageSize'] : 10;//每页显示数量
            $start = ($page-1)*$pageSize;
        }

        $condition = [];

        // 排序
        $order = [
            'combine_id' => 'DESC',
        ];
        $condition[] = ['combine_id','>','0'];
        // 搜索名称
        if(isset($param['keyword']) && $param['keyword']){
            $condition[] = ['title','like','%' . $param['keyword'] . '%'];
        }

        // 分类id
        if(isset($param['cat_id']) && $param['cat_id']>=0){
            $condition[] = ['cat_id','=', $param['cat_id']];
        }

        // 时间
        if(isset($param['time_type']) && $param['start_time'] && $param['end_time'] ){
            $condition[] = [$param['time_type'],'between', [strtotime($param['start_time'] ),strtotime($param['end_time'] )+86400]];
        }

        // 用户端显示条件
        if(isset($param['is_wap']) && $param['is_wap']==1){
            $condition[] = ['start_time','<=', time()];
            $condition[] = ['end_time','>=', time()];
            $condition[] = ['status','=', 1];
        }

        // 团购装修
        if(isset($param['is_renovation']) && $param['is_renovation']==1){
            $condition[] = ['start_time','<=', time()];
            $condition[] = ['end_time','>=', time()];
            $condition[] = ['status','=', 1];
        }

        // 商品列表
        $list = $this->getSome($condition, true, $order, $start, $pageSize);
        $count = $this->groupCombineActivityModel->getCount($condition);

        // 查看分类相关信息
        $catIdArr = $list ? array_column($list,'cat_id') : [];
        $where = [
            ['cat_id', 'in', implode(',',$catIdArr)]
        ];
        $catArr = (new GroupCategoryService())->getSome($where);
        $catArr = array_merge($catArr,[['cat_id'=>'0','cat_name'=>L_('其他')]]);
        $catArr = array_column($catArr,'cat_name','cat_id');

        foreach($list as &$_group){
            $_group['cat_name'] = $catArr[$_group['cat_id']] ?? '';
            $_group['start_time'] = date('Y-m-d H:i',$_group['start_time']);
            $_group['end_time'] = date('Y-m-d H:i',$_group['end_time']);
            $_group['status'] = $_group['status'] == 1 ? L_('开启') : L_('关闭');
            $_group['detail_url'] = cfg('site_url').'/packapp/plat/pages/group/groupCombineDetail?combine_id='.$_group['combine_id'];
            $_group['price'] = get_format_number($_group['price']);
            $_group['old_price'] = get_format_number($_group['old_price']);
            $_group['cfg_sort'] = 0;

            if(isset($param['goods_detail']) && $param['goods_detail']==1){
                // 显示商品详情
                $where['limit'] = 6;
                $where['combine_id'] = $_group['combine_id'];
                $where['order'] = 'sale_count';
                $where['image_size'] = ['width'=>'190','height'=>'132'];
                $_group['group_list'] = (new GroupCombineActivityGoodsService())->getBindList($where);
                $where = [
                    'combine_id' => $_group['combine_id']
                ];
                $_group['goods_count'] = (new GroupCombineActivityGoodsService())->getCount($where);
            }
        }

        $returnArr['list'] = $list;
        $returnArr['count'] = $count;
        return $returnArr;
    }

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getGroupCombineDetail($param = [], $userInfo = [])
    {
        //优惠组合id
        $combineId = $param['combine_id'] ?? 0;
        //用户信息
        $userInfo = $param['user'] ?? [];

        $lat = $param['lat'] ?? 0;//维度
        $lng = $param['lng'] ?? 0;//经度

        if(empty($combineId)){
            throw new \think\Exception(L_("缺少参数"), 1003);
        }

       $where = [
           'combine_id' => $combineId
       ];

        // 活动详情
        $detail = $this->getOne($where);

        if(empty($detail)){
            throw new \think\Exception(L_("活动不存在"), 1003);
        }

        $detail['start_time'] = date('Y-m-d H:i:s',$detail['start_time']);
        $detail['end_time'] = date('Y-m-d H:i:s',$detail['end_time']);
        $detail['create_time'] = date('Y-m-d H:i:s',$detail['create_time']);
        $detail['rule_detail'] = htmlspecialchars($detail['rule_detail']);

        // 绑定商品
        if($lat>0 && $lng>0){
            // 用户查看 按距离排序
            $param['order'] = 'distance';
        }

        // 分类名
        if($detail['cat_id']){
            $cate = (new GroupCategoryService())->getOne(['cat_id'=>$detail['cat_id']]);
            $detail['cat_name'] = $cate['cat_name'] ?? '';
        }else{
            $detail['cat_name'] = L_('其他');
        }

        $detail['banner_img'] = replace_file_domain($detail['banner_img']);
        $detail['share_img'] = replace_file_domain($detail['share_img']);

        $detail['price'] = get_format_number($detail['price']);
        $detail['old_price'] = get_format_number($detail['old_price']);
        // 用户查看
        if($userInfo){
            if($detail['stock_num'] != -1){
                $detail['stock_num'] =  $detail['stock_num'] - $detail['sell_count'];
            }
            // 用户已购买数量
            $detail['has_buy_count'] = 0;
            $where = [
                ['uid', '=' , $userInfo['uid']],
                ['status', 'not in' , '3,4'],
                ['combine_id', '=' , $detail['combine_id']],
            ];
            $detail['has_buy_count'] = (new GroupOrderService())->getCount($where);

            $detail['share_title'] = $detail['share_title'] ?: $detail['title'];
            $detail['share_desc'] = $detail['share_desc'] ?: L_('点击进入');
        }

        // 获得绑定商品列表
        $groupGoods = (new GroupCombineActivityGoodsService())->getBindList($param);
        $detail['group_list'] = $groupGoods;
        return $detail;
    }
    /**
     * 编辑活动
     * @param $where
     * @return array
     */
    public function editGroupCombine($param = [])
    {
        $combineId = $param['combine_id'] ?? 0;
        $goodsList = $param['goods_list'] ?? [];

        unset($param['goods_list']);
        if ($param['price'] < 0 ) {
            throw new \think\Exception(L_("价格必须大于或等于0！"));
        }

        if($goodsList){
            $merIdArr = [];//验证商家，一个商家只能添加一个商品
            foreach ($goodsList as $key => $value) {
                if(in_array($value['mer_id'],$merIdArr)){
                    throw new \think\Exception(L_("同一个商家不能添加多个商品，请修改后再提交！"));
                }
                $merIdArr[] = $value['mer_id'];
            }
        }

        $param['start_time'] = strtotime($param['start_time']);
        $param['end_time'] = strtotime($param['end_time']);


        // 查询商品是否已经添加过
        $where = [
            'combine_id' => $combineId,
        ];

        $goods = $this->getOne($where);

        if($goods){
            //编辑
            $res = $this->updateThis($where, $param);
        }else{
            // 新增
            $combineId = $res = $this->add($param);
        }

        // 商品保存
        if($goodsList){
            $data = [];
            foreach ($goodsList as $key => $value) {
                $data[] = [
                    'combine_id' => $combineId,
                    'group_id' => $value['group_id'],
                    'cost_price' => $value['cost_price'] ?? 0,
                    'use_count' => $param['use_rule'] == 1 ? 0 : ($value['use_count'] ?? 0),
                    'mer_id' => $value['mer_id'],
                    'create_time' => time(),
                ];
            }

            // 删除原有的
            $where = [
                'combine_id' => $combineId
            ];
            (new GroupCombineActivityGoodsService())->del($where);

            // 保存新加的
            (new GroupCombineActivityGoodsService())->addAll($data);
        }

        if($res===false){
            throw new \think\Exception(L_("操作失败请重试"),1003);

        }
        return true;
    }

    /**
     * 库存处理
     * @param $combineId int 活动id
     * @param $type 操作类型 1-减少库存 2 增加库存
     * @return array
     */
    public function updateStock($combine, $type=1)
    {
        $combineId = $combine['combine_id'] ?? 0; // 活动id
        $num = $combine['num'] ?? 0;

        if (empty($combineId) || empty($num)) {
            return false;
        }

        if($type == 1){
            $changeNum = $combine['sell_count'] + $num;
        }else{
            $changeNum = max(0,$combine['sell_count'] - $num);
        }

        $where = [
            'combine_id' => $combineId
        ];
        $saveData = [
            'sell_count' => $changeNum
        ];
        $res = $this->groupCombineActivityModel->where($where)->save($saveData);

        if($res===false){
            return false;
        }

        return true;
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupCombineActivityModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->groupCombineActivityModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->groupCombineActivityModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->groupCombineActivityModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->groupCombineActivityModel->getSome($where,$field ,$order,$page,$limit);
//            var_dump($this->groupCombineActivityModel->getLastSql());
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}