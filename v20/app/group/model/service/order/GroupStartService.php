<?php
/**
 * 发起团购的列表
 * Author: 衡婷妹
 * Date Time: 2020/11/27 16:18
 */

namespace app\group\model\service\order;

use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\group\model\db\GroupStart;
use app\group\model\service\GroupImageService;
use think\facade\Db;

class GroupStartService
{
    public $groupStartModel = null;

    public function __construct()
    {
        $this->groupStartModel = new GroupStart();
    }

    //参加团购 按人数算
    public function addGroup($fid, $uid, $nowGroup, $orderId, $gid=0){
        $where = [
            ['group_id', '=', $fid],
            ['start_time', '>=', $nowGroup['pin_effective_time']*3600],
            ['uid','<>',$uid]
        ];

        //团购排序
        if(empty($gid)){
            $startWhere = [
                ['group_id', '=', $fid],
                ['status', '<=', 2],
            ];
            $order = [
                'status' => 'ASC',
                'num' => 'DESC',
                'start_time' => 'DESC',
            ];

            $the_fastest = $this->getOne($startWhere, $order);
        }else{
            $startWhere = [
                ['id', '=', $gid],
            ];
            $the_fastest = $this->getOne($startWhere);
        }

        $where = [
            'fid'=>$the_fastest['id'] ?? 0,
            'uid'=>$uid
        ];
        $already_buy = (new GroupBuyerListService())->getOne($where);

        (new GroupBuyerListService())->addBuyerList($the_fastest['id'],$uid,$orderId);

        if($the_fastest && empty($already_buy)){
            //参团人数加1
            $where = [];
            $where['id'] = $the_fastest['id'];
            $this->groupStartModel->where($where)->inc('num',1)->update();

            //用户参团后参团人数大于等于设置的成团人数，团购成功
            if($the_fastest['complete_num']<=$the_fastest['num']+1){
                //获取参团用户信息，推送成团消息
                $buyer = $this->getBuyererByOrderId('',$where['id']);
                foreach($buyer as $v){
                    if($v['type']==1){
                        continue;
                    }
                    $sms_data = array('mer_id' => $v['mer_id'], 'store_id' => $v['store_id'], 'type' => 'group');
                    $href = cfg('site_url').'/wap.php?c=My&a=group_order&order_id='.$v['order_id'];

                    $remark = L_("X1成功",array("X1" => cfg('group_alias_name')));
                    // 实物没有消费码
                    if ($nowGroup['tuan_type']!=2) {
                        $remark .= L_("，您的消费码：X1。",array("X1" => $v['group_pass']));
                    }

                    (new TemplateNewsService())->sendTempMsg('TM00017',
                        array('href' => $href,
                            'wecha_id' => $v['openid'],
                            'first' => cfg('group_alias_name').L_('提醒'),
                            'OrderSn' => $v['real_orderid'],
                            'OrderStatus' => L_('您购买的拼团x1已于 x2 成团',array('x1'=>$v['order_name'],'x2'=>date('Y-m-d H:i:s'))),
                            'remark' => $remark), $v['mer_id']);

                    if(cfg('sms_pin_success_order')==1) {
                        $content = L_("您购买的拼团x1已于 x2 成团",array('x1'=>$v['order_name'],'x2'=>date('Y-m-d H:i:s')));
                        // 实物没有消费码
                        if ($nowGroup['tuan_type']!=2) {
                            $content .= L_("，您的消费码：X1。",array("X1" => $v['group_pass']));
                        }

                        $sms_data['uid'] = $uid;
                        $sms_data['mobile'] = $v['phone'];
                        $sms_data['sendto'] = 'user';
                        $sms_data['content'] = $content;
                        (new SmsService())->sendSms($sms_data);
                    }
                }

                //更新团的状态

                return $this->updateThis($where,array('status'=>1));
            }else{
                return $the_fastest;
            }
        }else{
            //不存在可参与的团或已参与过该团，自动发起新的团
            return $this->startGroup($uid,$nowGroup);
        }
    }
    
    //发起团购
    public function startGroup($uid,$nowGroup){
        $data['uid'] = $uid;    //发起团购的用户id
        $data['group_id'] = $nowGroup['group_id'];
        $data['mer_id'] = $nowGroup['mer_id'];
        $data['num'] = 0;
        $data['complete_num'] = $nowGroup['pin_num'];
        $data['status'] = 4; //暂定 还未支付
        $data['start_time'] = time();
        $data['last_time'] = $data['start_time'];
        $data['discount'] = $nowGroup['start_discount'];
        $fid = $this->add($data);
        if(!$fid){
            return array('error_code'=>1,'msg'=>L_('发起团购失败'));
        }else{
            return array('error_code'=>0,'msg'=>$fid);
        }
    }
    
    //记录参团用户信息
    public function addBuyerList($fid,$uid,$orderId){
        $buyerInfo['fid'] = $fid;
        $buyerInfo['order_id'] = $orderId;
        $buyerInfo['uid'] = $uid;
        return $this->add($buyerInfo);
    }

    //获取拼团小组成员 包括机器人
    public function getBuyererByOrderId($order_id,$gid=0){
        if(!empty($order_id)){
            $where = [
                'order_id' => $order_id
            ];
            $res = (new GroupBuyerListService())->getOne($where);

            $where = [
                'id' => $res['fid']
            ];
            $res = $this->getOne($where);
        }else{
            $res['id'] = $gid;
        }

        $res = (new GroupBuyerListService())->getBuyererByFid($res['id']);

        return $res;
    }

    /**
     * 根据订单id获取拼团组信息
     * @param int $orderId 订单id
     * @return array
     */
    public function getGroupStartByOrderId($orderId){
        $res = (new GroupBuyerListService())->getOne(['order_id'=>$orderId]);
        return $this->getOne(array('id'=>$res['fid']));
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->groupStartModel->insertGetId($data);
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
            $result = $this->groupStartModel->insertAll($data);
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
            $result = $this->groupStartModel->where($where)->update($data);
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

        $result = $this->groupStartModel->getOne($where, $order);
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
            $result = $this->groupStartModel->getSome($where,$field ,$order,$page,$limit);
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }

    /**
     * 获取当前店铺正在进行中的普通团购商品拼团订单数量
     * 
     * @return void
     * @author: 张涛
     * @date: 2021/05/11
     */
    public function getNormalGoodsCountInProgress($storeId)
    {
        $where = [
            ['st.store_id', '=', $storeId],
            ['s.status', '=', 0],
            [Db::raw('s.start_time+g.pin_effective_time*3600'), '>', time()],
        ];
        return $this->groupStartModel->alias('s')
            ->join('group g', 'g.group_id=s.group_id')
            ->join('group_store st', 'st.group_id = g.group_id')
            ->where($where)
            ->count('distinct g.group_id');
    }


    /**
     * 获取当前店铺拼团人数剩余最少，拼团时间最短的拼单记录
     *
     * @param int $storeId
     * @return void
     * @author: 张涛
     * @date: 2021/05/12
     */
    public function getShortestPinTime($storeId)
    {
        $where = [
            ['st.store_id', '=', $storeId],
            ['s.status', '=', 0],
            [Db::raw('s.start_time+g.pin_effective_time*3600'), '>', time()],
        ];
        $record = $this->groupStartModel->alias('s')
            ->join('group g', 'g.group_id=s.group_id')
            ->join('group_store st', 'st.group_id = g.group_id')
            ->where($where)
            ->order(Db::raw('s.complete_num-s.num asc'))
            ->order(Db::raw('s.start_time+g.pin_effective_time*3600 asc'))
            ->findOrEmpty();

        if ($record->isEmpty()) {
            return [];
        }

        $info = [
            'group_id' => $record->group_id,
            'name' => $record->s_name,
            'left_num' => $record->complete_num - $record->num,
            'left_time' => $record->start_time + $record->pin_effective_time * 3600 - time()
        ];
        return $info;
    }

    /**
     * 获取所有拼单中的商品信息
     * @param $params
     * @author: 张涛
     * @date: 2021/05/31
     */
    public function getPinLists($params)
    {
        $tm = time();
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        $where = [['s.status', '=', 0]];
        if (isset($params['store_id'])) {
            $where[] = ['st.store_id', '=', $params['store_id']];
        }
        if (isset($params['group_id']) && $params['group_id']) {
            $where[] = ['g.group_id', '=', $params['group_id']];
        }
        $where[] = ['o.paid', '=', 1];
        $where[] = ['s.start_time', 'exp',  Db::raw(' > '.time().'-g.pin_effective_time*3600')];
        $count = $record = $this->groupStartModel->alias('s')
            ->join('group g', 'g.group_id=s.group_id')
            ->join('group_store st', 'st.group_id = g.group_id')
            ->join('group_order o', 'o.is_head = s.id')
            ->where($where)
            ->count();

        $lists = [];
        if ($count > 0) {
            $records = $this->groupStartModel->alias('s')
                ->field('g.*, st.*, o.*,s.*,s.num as has_pin_num')
                ->join('group g', 'g.group_id=s.group_id')
                ->join('group_store st', 'st.group_id = g.group_id')
                ->join('group_order o', 'o.is_head = s.id')
                ->where($where)
                ->order(Db::raw('s.complete_num-s.num asc'))
                ->order(Db::raw('s.start_time+g.pin_effective_time*3600 asc'))
                ->page($page, $pageSize)
                ->select();
            $groupImageService = new GroupImageService();
            foreach ($records as $r) {
                $picArr = array_filter(explode(';', $r->pic));
                $mainImage = isset($picArr[0]) ? $groupImageService->getImageByPath($picArr[0], 0) : '';
                $lists[] = [
                    'id' => $r->id,
                    'group_id' => $r->group_id,
                    's_name' => $r->s_name,
                    'img' => $mainImage ? thumb_img($mainImage, 450, 250, 'fill') : '',
                    'left_num' => $r->complete_num - $r->has_pin_num,
                    'left_time' => $r->start_time + $r->pin_effective_time * 3600 - $tm,
                    'old_price' => get_format_number($r->old_price),
                    'price' => get_format_number($r->price)
                ];
            }
        }
        return ['count' => $count, 'total_page' => ceil($count / $pageSize), 'lists' => $lists];
    }
}