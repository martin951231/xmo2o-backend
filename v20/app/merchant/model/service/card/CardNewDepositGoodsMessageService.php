<?php
/**
 * 用户端会员卡寄存消息service
 * Author: fenglei
 * Date Time: 2021/11/09 13:51
 */

namespace app\merchant\model\service\card;
use app\merchant\model\db\CardNewDepositGoodsBindUser;
use app\merchant\model\db\CardNewDepositGoodsMessage;
use app\merchant\model\db\CardUserlist;
use app\merchant\model\service\MerchantStoreService;
use think\facade\Db;

class CardNewDepositGoodsMessageService {
 
    public $cardUserlistModel = null;
    public $cardNewDepositGoodsBindUserModel = null;
    public $cardNewDepositGoodsMessageModel = null;

    public function __construct()
    {  
        $this->cardUserlistModel = new CardUserlist();
        $this->cardNewDepositGoodsBindUserModel = new CardNewDepositGoodsBindUser();
        $this->cardNewDepositGoodsMessageModel = new CardNewDepositGoodsMessage();
    }

    /**
     * 获取消息列表
     */
    public function getMessageList($params)
    {
        $user_list_condition = array();
        $user_list_condition[] = ['uid', '=', $params['uid']];
        
        $card_ids = $this->cardUserlistModel->where($user_list_condition)->column('id');

        if(!count($card_ids)){
            return [];
        }

        $bind_user_condition = array();
        $bind_user_condition[] = ['card_id', 'in', $card_ids];
        $bind_user_condition[] = ['mer_id', '=', $params['mer_id']];
        $bin_ids = $this->cardNewDepositGoodsBindUserModel->where($bind_user_condition)->column('id');

        $message_condition = array();
        $message_condition[] = ['bind_id', 'in', $bin_ids]; 


        $with = array(
            'binduser'=> function($query){
                $query->field(['id', 'mer_id', 'staff_id', 'goods_id'])
                ->with([
                    'merchant' => function($query){
                        $query->field(['mer_id','name']);
                    },
                    'staff' => function($query){
                        $query->field(['id','name','tel']);
                    },
                    'goods' => function($query){
                        $query->field(['goods_id','name','image']);
                    }
                ]);
            }
        );

        $data = $this->cardNewDepositGoodsMessageModel
                ->field(['id AS message_id', 'bind_id', 'type', 'create_time'])
                ->with($with)
                ->where($message_condition)
                ->order('create_time desc')
                ->paginate($params['pageSize'])
                ->each(function($item, $key){
                    $this->formatMessageData($item);
                });
        return $data->toArray()['data']; 
    }

    /**
     * 格式化消息列表数据
     */
    private function formatMessageData(&$item)
    {
        $bind = $item->binduser;
        $item->mer_id = $bind->mer_id;
        $item->staff_id = $bind->staff_id;
        $item->goods_id = $bind->goods_id;
        $item->merchant = $bind->merchant;
        $item->staff = $bind->staff;
        $item->goods = $bind->goods;
        unset($item->binduser);
        $today = strtotime(date('Y-m-d 00:00:00')); 
        $item->create_time = $item->create_time > $today ? date('H:i', $item->create_time) : date('m/d H:i', $item->create_time);
    }

}