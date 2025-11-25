<?php
/**
 * 订单评价service
 * Author: hengtingmei
 * Date Time: 2021/5/10
 */

namespace app\common\model\service\order;

use app\common\model\db\Merchant;
use app\common\model\db\Reply;
use app\common\model\db\User;

class ReplyService{
	public $replyModel = null;
    public function __construct()
    {
        $this->replyModel = new Reply();
       
    }
	

    /**
     * 获取某个团购商品的评价
     * @param array $where 条件
     * @param array $field 查询字段
     * @param array $order 排序值
     * @param array $page 页码
     * @param array $limit 每页显示条数
     * @return array
     */
    public function getListByGroupGoods($where,$field='r.*,u.nickname,u.avatar',$order=[],$page=1,$limit=0){
        $result = $this->replyModel->getListByGroupGoods($where,$field,$order,$page,$limit);
        if(empty($result)) return [];
		$result = $this->formatData($result);
        return $result;
    }    
	
	/**
	* 获取某个团购商品的评价总数
	* @param array $where 条件
	* @return array
	*/
   public function getCountByGroupGoods($where){
	   $result = $this->replyModel->getCountByGroupGoods($where);
	   if(empty($result)) return 0;
	   return $result;
   }
	
   /**
   * 获取某个团购商品的评价平均值
   * @param array $where 条件
   * @return array
   */
  public function getScoreByGroupGoods($where){
	  $result = $this->replyModel->getScoreByGroupGoods($where);
	  if(empty($result)) return 0;
	  return $result;
  }

   	/**
	* 处理数据
	* @param array $list 条件
	* @return array
	*/
	public function formatData($list){
		// 评价图
		$ReplyImage = new ReplyImage();
        $userModel = new User();
        $merchantModel = new Merchant();
		foreach($list as $reply){
			$reply['pic_list'] = $reply['images'] = [];
			if($reply['pic']){
				$reply['pic_list'] = $ReplyImage->getImageByIds($reply['pic'], 'group');
			}
            $reply['nickname'] = $reply['anonymous']==1?'匿名用户':$reply['nickname'];
            $reply['images'] = array_column($reply['pic_list'],'image');
			$reply['label'] = L_('消费后评价');
			$reply['add_time'] = date('Y-m-d H:i:s',$reply['add_time']);
            empty($reply['avatar']) && $reply['avatar'] = cfg('site_url')."/static/images/user_avatar.jpg";

            $merchant_reply = null;
            if(!empty($reply['merchant_reply_content'])){
                $merInfo = $merchantModel->field(['name','logo'])->where('mer_id', $reply['mer_id'])->find();
                $merchant_reply = [
                    'headimg'   =>  !empty($merInfo['logo']) ? replace_file_domain($merInfo['logo']) : '',
                    'nickname'  =>  $merInfo['name'] ?? '',
                    'content'   =>  $reply['merchant_reply_content'],
                    'time'      =>  date('Y/m/d H:i', $reply['merchant_reply_time'])
                ];
            }
            $user_reply = null;
            if(!empty($reply['user_reply_merchant'])){
                $userInfo = $userModel->field(['nickname','avatar'])->where('uid', $reply['uid'])->find();
                $user_reply = [
                    'headimg'   =>  !empty($userInfo['avatar']) ? replace_file_domain($userInfo['avatar']) : '',
                    'nickname'  =>  $userInfo['nickname'] ?? '',
                    'content'   =>  $reply['user_reply_merchant'],
                    'time'      =>  date('Y/m/d H:i', $reply['user_reply_merchant_time'])
                ];
            }
            $reply['merchant_reply'] = $merchant_reply;
            $reply['user_reply'] = $user_reply;
		}
		return $list->toArray();
	}


    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $id = $this->replyModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取一条条数据
     * @param $where array
     * @return array
     */
    public function getOne($where){
        $result = $this->replyModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->replyModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->replyModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->replyModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}