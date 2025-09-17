<?php


namespace app\group\model\service\order;


use app\common\model\db\Reply;
use app\common\model\service\order\SystemOrderService;
use app\group\model\db\Group;
use app\group\model\db\GroupOrder;
use app\merchant\model\db\MerchantScore;

class GroupOrderAutoEvaluationService
{
    /**
     * 团购商品确认订单到期后自动评价
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function autoEvaluation(){
        $hour = cfg('group_order_auto_evaluation')?:0; //小时
        $start_time = time()-7*24*3600;
        if($hour>0){
            $where[] = ['status','=',1];
            $where[] = ['use_time','<',(time()-($hour*3600))];
            $where[] = ['add_time','>',$start_time];
            $order_list = (new GroupOrder())->where($where)->limit(10)->select()->toArray();
            $score = 5;
            $comment = '默认好评！';
            foreach ($order_list as $item){
                $data_reply = [
                    'parent_id' => $item['group_id'],
                    'store_id' => $item['store_id'],
                    'mer_id' => $item['mer_id'],
                    'score' => $score,
                    'order_type' => 0,
                    'order_id' => intval($item['order_id']),
                    'anonymous' => 1,
                    'comment' => $comment,
                    'uid' => $item['uid'],
                    'add_time' => time(),
                    'add_ip' => get_client_ip(0),
                ];
                $r_id = (new Reply())->insertGetId($data_reply);
                if($r_id){
                    $this->setInc_group_reply($item['group_id'],$score);
                    (new GroupOrder())->where(['order_id'=>$item['order_id']])->save(['status'=>2]);
                    (new SystemOrderService())->commentOrder('group',$item['order_id']);

                    if($item['is_group_combine']==0) {
                        $database_merchant_score = new MerchantScore();
                        $now_merchant_score = $database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id' => $item['mer_id'], 'type' => '1'))->find();
                        if (empty($now_merchant_score)) {
                            $data_merchant_score['parent_id'] = $item['mer_id'];
                            $data_merchant_score['type'] = '1';
                            $data_merchant_score['score_all'] = $score;
                            $data_merchant_score['reply_count'] = 1;
                            $database_merchant_score->save($data_merchant_score);
                        } else {
                            $data_merchant_score['score_all'] = $now_merchant_score['score_all'] + $score;
                            $data_merchant_score['reply_count'] = $now_merchant_score['reply_count'] + 1;
                            $database_merchant_score->where(array('pigcms_id' => $now_merchant_score['pigcms_id']))->save($data_merchant_score);
                        }
                        $now_store_score = $database_merchant_score->field('`pigcms_id`,`score_all`,`reply_count`')->where(array('parent_id' => $item['store_id'], 'type' => '2'))->find();
                        if (empty($now_store_score)) {
                            $data_store_score['parent_id'] = $item['store_id'];
                            $data_store_score['type'] = '2';
                            $data_store_score['score_all'] = $score;
                            $data_store_score['reply_count'] = 1;
                            $database_merchant_score->save($data_store_score);
                        } else {
                            $data_store_score['score_all'] = $now_store_score['score_all'] + $score;
                            $data_store_score['reply_count'] = $now_store_score['reply_count'] + 1;
                            $database_merchant_score->where(array('pigcms_id' => $now_store_score['pigcms_id']))->save($data_store_score);
                        }
                    }
                }
            }
        }
    }

    /**
     * 增加团购评论数
     */
    public function setInc_group_reply($group_id,$score){
        $group = (new Group())->where(array('group_id'=> $group_id))->findOrEmpty()->toArray();
        if($group){
            $data_group['reply_count']= $group['reply_count'] + 1;
            $data_group['score_all']= $group['score_all']+ $score;
            $data_group['score_mean']= round(($data_group['score_all'] / $data_group['reply_count']),1);
            (new Group())->where(array('group_id'=>$group_id))->save($data_group);
        }
    }
}