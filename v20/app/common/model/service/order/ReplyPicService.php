<?php
/**
 * 订单评价图service
 * Author: hengtingmei
 * Date Time: 2021/5/10
 */

namespace app\common\model\service\order;

use app\common\model\db\ReplyPic;

class ReplyPicService{
	public $replyPicModel = null;
    public function __construct()
    {
        $this->replyPicModel = new ReplyPic();
       
    }
	
   	/**
	* 处理数据
	* @param array $list 条件
	* @return array
	*/
	public function formatData($list){
		// 评价图
		foreach($list as $reply){
			if($reply['pic']){
				
			}
		}
		return $list;
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

        $id = $this->replyPicModel->insertGetId($data);
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
        $result = $this->replyPicModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->replyPicModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->replyPicModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }
}