<?php
/**
 * 团购优惠组合
 * Author: 衡婷妹
 * Date Time: 2020/11/16 16:29
 */

namespace app\group\model\service\group_combine;

use app\common\model\db\Reply;
use app\common\model\db\ReplyPic;
use app\common\model\db\User;
use app\common\model\service\image\ImageService;
use app\common\model\service\order\ReplyImage;
use app\common\model\service\SystemRobotService;
use app\common\model\service\UserService;
use app\group\model\db\GroupCombineActivity;
use app\group\model\db\GroupOrder;
use app\group\model\service\GroupCategoryService;
use app\group\model\service\order\GroupCombineActivityBuyLogService;
use app\group\model\service\order\GroupOrderService;
use app\merchant\model\service\MerchantService;
use file_handle\FileHandle;

class GroupCombineActivityService
{
    public $groupCombineActivityModel = null;

    public function __construct()
    {
        $this->groupCombineActivityModel = new GroupCombineActivity();
    }


    /**
     * 分享获得佣金
     * @param array $nowOrder
     */
    public function sendSpreadMoney($nowOrder)
    {
        if(empty($nowOrder['share_uid']) || $nowOrder['spread_money'] <=0){
           return false;
        }

        $user = (new UserService())->getUser($nowOrder['uid']);

        $combineDetail = $this->getOne(['combine_id'=>$nowOrder['combine_id']]);
        $data = [
            'combine_id' => $nowOrder['combine_id'],
            'order_id' => $nowOrder['order_id'],
            'group_name' => $combineDetail['title'],
            'avatar' => $user['avatar'],
            'user_name' => $nowOrder['nickname'],
            'uid' => $nowOrder['share_uid'],
            'spread_money' => $nowOrder['spread_money'],
            'spread_num' => 1,
            'create_time' => time(),
        ];
        $res = (new GroupCombineActivitySpreadListService())->add($data);

        if(!$res){
            return false;
        }

        // 给分享者加钱
        $desc = L_("X1用户购买了你分享的X2获得佣金",array("X1" => $user['nickname'],"X2" =>$combineDetail['title'])); 
        $res = (new UserService())->addMoney($nowOrder['share_uid'], $nowOrder['spread_money'], $desc);
        if($res['error_code']){
            return false;
        }

        return true;
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
        $detail['share_poster_img'] = replace_file_domain($detail['share_poster_img']);

        $detail['price'] = get_format_number($detail['price']);
        $detail['old_price'] = get_format_number($detail['old_price']);
        $detail['spread_money'] = get_format_number($detail['spread_money']);
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

            // 记录参与记录
            $where = [
                'combine_id' => $combineId,
                'uid' => $userInfo['uid']
            ];
            $visitLog = (new GroupCombineActivityVisitListService())->getOne($where);
            if(empty($visitLog)){
                $saveData = [
                    'combine_id' => $combineId,
                    'uid' => $userInfo['uid'],
                    'group_name' => $detail['title'],
                    'avatar' => $userInfo['avatar'],
                    'user_name' => $userInfo['nickname'],
                    'create_time' => time(),
                ];
                (new GroupCombineActivityVisitListService())->add($saveData);
            }
        }

        // 获得绑定商品列表
        $groupGoods = (new GroupCombineActivityGoodsService())->getBindList($param);
        $detail['group_list'] = $groupGoods;

        // 存在佣金时 显示更多内容
        $detail['mer_list'] = [];
        $detail['spread_list'] = [];
        $detail['visit_count'] = 0;
        $detail['visit_list'] = [];
        if($detail['spread_money'] && !$detail['can_cancel']){
            // mer_list //联盟商家列表
            $merIdArr = array_column($groupGoods,'mer_id');
            $where = [
                ['mer_id','in',implode(',',$merIdArr)]
            ];
            $order = [
                'mer_id' => 'ASC'
            ];
            $merList = (new MerchantService())->getSome($where,'name,logo',$order);
            foreach ($merList as &$_mer){
                $_mer['logo'] = replace_file_domain($_mer['logo']);
            }
            $detail['mer_list'] = $merList;

            //排行榜列表
            $where = [
                ['combine_id','=',$combineId],
            ];
            $spreadList = (new GroupCombineActivitySpreadListService())->getTopListByCombineId($combineId);
            foreach ($spreadList as &$_spread){
                $_spread['avatar'] = $_spread['avatar'] ? replace_file_domain($_spread['avatar']) : '';
            }
            $detail['spread_list'] = $spreadList;

            //参与人列表
            $where = [
                ['combine_id','=',$combineId],
            ];
            // 参与人总数
            $detail['visit_count'] = (new GroupCombineActivityVisitListService())->getCount($where);

            // 参与人最新42名
            $order = [
                'id' => 'DESC'
            ];
            $visitList = (new GroupCombineActivityVisitListService())->getSome($where,true,$order,0,42);

            foreach ($visitList as &$_visit){
                $_visit['avatar'] = $_visit['avatar'] ? replace_file_domain($_visit['avatar']) : '';
            }
            $detail['visit_list'] = $visitList;

        }

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
         if(isset($param['system_type'])){
             unset($param['system_type']);
         }
        unset($param['goods_list']);
        if ($param['price'] < 0 ) {
            throw new \think\Exception(L_("价格必须大于或等于0！"));
        }

        if ($param['can_cancel'] == 1 ) {//购买后可取消不能获得佣金
            $param['spread_money'] = 0;
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
     * 团购优惠组合分享海报生成
     * @param $where
     * @return array
     */
    public function getGroupCombineSharePoster($param = [],$user)
    {
        $combineId = $param['combine_id'] ?? 0;
        if(empty($user)){
            throw new \think\Exception(L_('用户信息不存在'),1002);
        }

        if(empty($combineId)){
            throw new \think\Exception(L_('缺少参数'),1003);
        }

        // 优惠组合详情
        $where = [
            'combine_id' => $combineId
        ];
        $combine = $this->getOne($where);
        if(empty($combine['share_poster_img'])){
            return false;
            throw new \think\Exception(L_('未上传分享海报图'),1003);
        }
		
		$fileHandle = new FileHandle();

        // 海报背景图
        $backgroundImg = $fileHandle->get_path($combine['share_poster_img']);

        // 项目根目录
        $DOCUMENT_ROOT = request()->server('DOCUMENT_ROOT');

        //将oss上的图片先下来
        if(cfg('static_oss_switch') == 1) {
            $fileHandle->download($backgroundImg);
            $backgroundImg = $DOCUMENT_ROOT.$backgroundImg;
        }else{
            $backgroundImg = $DOCUMENT_ROOT.$backgroundImg;
        }

        // 背景图不存在
        if(!file_exists($backgroundImg)){
            return false;
        }

        // 创建目录
        if(!file_exists($DOCUMENT_ROOT.'/runtime/group/combine')){
            mkdir($DOCUMENT_ROOT.'/runtime/group/combine',0777,true);
        }

        // 生成网页二维码
        $url = cfg('site_url').'/packapp/plat/pages/group/groupCombineDetail?combine_id='.$combineId.'&share_uid='.$user['uid'];
        $filename = $DOCUMENT_ROOT.'/runtime/group/combine/qrcode_'.$combineId.'_'.$user['uid'].'.png';
        if(!file_exists($filename)){
            require $DOCUMENT_ROOT."/v20/extend/phpqrcode/phpqrcode.php";
            $QRcode = new \QRcode();

            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png($url,$filename,$errorCorrectionLevel,$matrixPointSize);
        }

        $config = array(
            'image'=>array(
                array(
                    'url'=>$filename,     //二维码图片
                    'stream'=>0,
                    'left'=>60,
                    'top'=>1610,
                    'right'=>0,
                    'bottom'=>0,
                    'width'=>250,
                    'height'=>250,
                    'opacity'=>100,
                    'radius'=>30,
                    'is_unhyaline' => true
                ),
            ),
            'background' => $backgroundImg,          //背景图
        );

        $filename = $DOCUMENT_ROOT.'/runtime/group/combine/backgroud_'.$combineId.'_'.$user['uid'].'.png';
        $res = (new ImageService())->createPoster($config,$filename);
		
        if($res){
            $returnArr['share_image'] = $res;
        }else{
            $returnArr['share_image'] = '';
        }
        return $returnArr;
    }




    /**
     * 获得机器人列表
     * @param $param array 数据
     * @return array
     */
    public function getRobotList($param){
        $combineId = $param['combine_id'] ?? 0;
        $type = $param['type'] ?? '';
        $page = $param['page'] ?? '1';
        $pageSize = $param['pageSize'] ?? '10';
        $page = ($page-1)*$pageSize;
        if(empty($combineId) || empty($type)){
            throw new \think\Exception('缺少参数',1001);
        }

        $where = [
            ['type', '=', 1],
            ['combine_id', '=', $combineId]
        ];

        $service = '';
        switch ($type){
            case 'spread':
                $service = new GroupCombineActivitySpreadListService();
                break;
            case 'visit':
                $service = new GroupCombineActivityVisitListService();
                break;
            case 'buy':
                $service = new GroupCombineActivityBuyLogService();
                break;
        }
        $list = $service->getSome($where, true, [], $page, $pageSize);
        $count = $service->getCount($where);
        foreach ($list as &$_v){
            $_v['avatar'] = isset($_v['avatar']) ? replace_file_domain($_v['avatar']) : '';
            if(isset($_v['spread_money'])){
                $_v['spread_money'] = get_format_number($_v['spread_money']);
            }
            if(isset($_v['price'])){
                $_v['price'] = get_format_number($_v['price']);
            }
        }
        $returnArr['list'] = $list;
        $returnArr['total'] = $count;
        return $returnArr;
    }

    /**
     * 获得机器人列表
     * @param $param array 数据
     * @return array
     */
    public function addRobot($param){
        $combineId = $param['combine_id'] ?? 0;
        $type = $param['type'] ?? '';
        $endDate = $param['end_date'] ?? '';//支付时间结束
        $startDate = $param['start_date'] ?? '';//支付时间开始
        $number = $param['number'] ?? '0';//添加人数
        $nimiPeople = $param['nimi_people'] ?? '0';//推荐最少人数
        $maxPeople = $param['max_people'] ?? '0';//推荐最多人数

        if(empty($combineId) || empty($type)){
            throw new \think\Exception('缺少参数',1001);
        }

        // 验证团购优惠组合信息
        $where = [
            'combine_id' => $combineId
        ];
        $combine = $this->getOne($where);
        if(empty($combine)){
            throw new \think\Exception('该团购优惠组合不存在',1003);
        }
        if($combine['spread_money'] <= 0){
            throw new \think\Exception('该团购优惠组合未设置佣金不能添加机器人',1003);
        }

        $service = '';
        switch ($type){
            case 'spread':
                $service = new GroupCombineActivitySpreadListService();
                break;
            case 'visit':
                $service = new GroupCombineActivityVisitListService();
                break;
            case 'buy':
                $service = new GroupCombineActivityBuyLogService();
                break;
        }

        // 查询出已添加的机器人 避免添加重复了
        $where = [
            ['type', '=', 1],
            ['combine_id', '=', $combineId],
        ];
        $oldRobotList = $service->getSome($where);

        // 查询出对应的机器人
        $whereRobot = [
            ['id', '>' , '0']
        ];
        if($oldRobotList){
            $robotIdArr = array_column($oldRobotList,'robot_id');
            $whereRobot[] = [
                'id', 'not in', implode(',',$robotIdArr)
            ];
        }
        $robotList = (new SystemRobotService())->getSome($whereRobot,true,[],0,$number);
        if(empty($robotList)){
            throw new \think\Exception('无可添加的机器人了，请先添加机器人',1003);
        }
        $saveData = [];
        foreach ($robotList as $robot){
            $temp = [
                'robot_id' => $robot['id'],
                'combine_id' => $combineId,
                'group_name' => $combine['title'],
                'avatar' => $robot['avatar'],
                'user_name' => $robot['name'],
                'type' => 1,
                'create_time' => time()
            ];
            switch ($type){
                case 'spread':
                    $spreadNum = mt_rand($nimiPeople,$maxPeople);
                    $spreadMoney = $spreadNum*$combine['spread_money'];
                    $temp['spread_num'] = $spreadNum;
                    $temp['spread_money'] = $spreadMoney;
                    break;
                case 'visit':
                    break;
                case 'buy':
                    $temp['price'] = $combine['price'];
                    $temp['pay_time'] = mt_rand(strtotime($startDate),strtotime($endDate));
                    break;
            }
            $saveData[] = $temp;
        }
        $res = $service->addAll($saveData);
        if(empty($res)){
            throw new \think\Exception('添加失败请稍后重试',1003);
        }

        return true;
    }

    /**
     * 获得机器人列表
     * @param $param array 数据
     * @return array
     */
    public function delRobot($param){
        $combineId = $param['combine_id'] ?? 0;
        $type = $param['type'] ?? '';
        $idArr = $param['idArr'] ?? [];//删除id

        if(empty($combineId) || empty($type) || empty($idArr)){
            throw new \think\Exception('缺少参数',1001);
        }

        $service = '';
        switch ($type){
            case 'spread':
                $service = new GroupCombineActivitySpreadListService();
                break;
            case 'visit':
                $service = new GroupCombineActivityVisitListService();
                break;
            case 'buy':
                $service = new GroupCombineActivityBuyLogService();
                break;
        }

        $where = [
            ['id', 'in' , implode(',',$idArr)]
        ];

        $res = $service->del($where);
        if($res === false){
            throw new \think\Exception('删除失败请稍后重试',1003);
        }

        return true;
    }

    /**
     * 编辑机器人推荐人数
     * @param $param array 数据
     * @return array
     */
    public function editSpreadNum($param){
        $spreadNum = $param['spread_num'] ?? '';
        $id = $param['id'] ?? [];//删除id

        if(empty($spreadNum) || empty($id)){
            throw new \think\Exception('缺少参数',1001);
        }
        $service = new GroupCombineActivitySpreadListService();

        // 机器人详情
        $where = [
            ['id', '=' , $id]
        ];
        $detail = $service->getOne($where);
        if(empty($detail)){
            throw new \think\Exception('数据不存在或已删除',1001);
        }

        // 优惠组合详情
        $where = [
            'combine_id' => $detail['combine_id']
        ];
        $combineInfo = $this->getOne($where);

        // 保存信息
        $where = [
            ['id', '=' , $id]
        ];
        $data = [
            'spread_num' => $spreadNum,
            'spread_money' => $combineInfo['spread_money'] * $spreadNum,
        ];
        $res = $service->updateThis($where, $data);
        if($res === false){
            throw new \think\Exception('修改失败',1003);
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

    /**
     * 优惠组合评论列表
     * @param $param
     */
    public function getReply($param){
        $id = $param['id'];
        $page = $param['page'];
        $pageSize = $param['pageSize'];
        if(!$id){
            throw new \think\Exception(L_('参数错误'));
        }
        $com_group = (new GroupCombineActivity())->where(['combine_id'=>$id])->findOrEmpty()->toArray();
        if(!$com_group){
            throw new \think\Exception(L_('优惠组合不存在'));
        }
        $where[] = ['o.combine_id','=',$id];
        $where[] = ['o.is_group_combine','=',1];
        $where[] = ['o.combine_order_id','=',0];
        $field = 'r.pigcms_id,r.comment,r.score,r.add_time,o.order_name,u.nickname,u.uid,u.avatar';
        $list = (new GroupOrder())->getOrderReply($where,$field,$page,$pageSize);
        foreach ($list['data'] as $key=>$item){
            $list['data'][$key]['avatar'] = $item['avatar']?replace_file_domain($item['avatar']):'';
            $list['data'][$key]['add_time'] = $item['add_time']?date('Y-m-d H:i:s',$item['add_time']):'';
        }
        return $list;
    }

    /**
     * 优惠组合评论详情
     * @param $param
     */
    public function getReplyDetail($param){
        $id = $param['id'];
        if(!$id){
            throw new \think\Exception(L_('参数错误'));
        }
        $reply = (new Reply())->where(['pigcms_id'=>$id])->findOrEmpty()->toArray();
        if(!$reply){
            throw new \think\Exception(L_('评论不存在'));
        }
        $user = (new User())->where(['uid'=>$reply['uid']])->findOrEmpty()->toArray();
        $reply['order_name'] = (new GroupOrder())->where(['order_id'=>$reply['order_id']])->value('order_name');
        $reply['add_time'] = $reply['add_time']?date('Y-m-d H:i:s',$reply['add_time']):'';
        $reply['avatar'] = $user['avatar']?replace_file_domain($user['avatar']):'';
        $pic = $reply['pic']?explode(',',$reply['pic']):[];
        $pic_arr = [];
        if($pic){
            $reply_pic = (new ReplyPic())->where([['pigcms_id','in',$pic]])->select()->toArray();
            foreach ($reply_pic as $item){
                if($item['pic']){
                    $reply_pic_arr = (new ReplyImage())->getImageByPath($item['pic'],'group');
                    if($reply_pic_arr){
                        $pic_arr[] = $reply_pic_arr['image'];
                    }
                }
            }
        }
        
        $reply['pic'] = $pic_arr;
        return $reply;
    }
}