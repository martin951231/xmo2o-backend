<?php
/**
 * 种草用户互关
 * Author: hengtingmei
 * Date Time: 2021/5/17 11:01
 */

namespace app\grow_grass\model\service;
use app\common\model\db\User;
use app\grow_grass\model\db\GrowGrassFollow;
class GrowGrassFollowService {
    public $growGrassFollowModel = null;
    public function __construct()
    {
        $this->growGrassFollowModel = new GrowGrassFollow();
    }

    /**
    * 查看用户是已关注另外一个
    * @param int $uid 用户id
    * @param int $followUid 被关注uid
    * @return bool
    */
    public function setFollow($uid, $followUid){
        if(empty($uid) || empty($followUid)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        if($uid == $followUid){
            throw new \think\Exception(L_('不能关注自己'), 1003);
        }

        $where = [
            ['uid', '=', $uid ],
            ['follow_uid', '=', $followUid ],
//            ['is_del', '=', '0']
        ];
        $followed = $this->getOne($where);

        $sucessMsg = '';
        $errorMsg = '';
        if(!empty($followed)){ // 已关注取消关注
            $followed=$followed->toArray();
            if($followed['is_del']==0){
                $sucessMsg = L_('取消关注成功');
                $errorMsg = L_('取消关注失败');
                $res = $this->updateThis($where,['is_del' => 1]);
                $where1 = [['uid', '=', $followUid]];
                (new User())->setDec($where1, 'follow_nums', 1);//关注减1
            }else{
                $sucessMsg = L_('关注成功');
                $errorMsg = L_('关注失败');
                $savaData = [
                    'uid' => $uid,
                    'follow_uid' => $followUid,
                    'create_time' => time()
                ];
                //$res = $this->add($savaData);
                $res = $this->updateThis($where,['is_del' =>0]);
                $where1 = [['uid', '=', $followUid]];
                (new User())->setInc($where1, 'follow_nums', 1);//关注加1
            }
        }else{
            $sucessMsg = L_('关注成功');
            $errorMsg = L_('关注失败');
            $savaData = [
                'uid' => $uid,
                'follow_uid' => $followUid,
                'create_time' => time()
            ];
            $res = $this->add($savaData);
            $where1 = [['uid', '=', $followUid]];
            (new User())->setInc($where1, 'follow_nums', 1);//关注加1
        }
        if($res === false){
            throw new \think\Exception($errorMsg, 1003);
        }
       
        return ['msg' =>$sucessMsg ];
        
    }

    /**
    * 查看用户是已关注另外一个
    * @param int $uid 用户id
    * @param int $followUid 被关注uid
    * @return bool
    */
    public function checkFollow($uid, $followUid){
        if(empty($uid) || empty($followUid)){
            return false;
        }

        $where = [
            ['uid', '=', $uid ],
            ['follow_uid', '=', $followUid ],
            ['is_del', '=', '0']
        ];
        $list = $this->getOne($where);
       
        return $list ? true : false;
        
    }

    /**
    * 获取一条数据
    * @param $where array 条件
    * @return array
    */
    public function getOne($where){
        $result = $this->growGrassFollowModel->getOne($where);
        if(!$result) {
            return [];
        }
        return $result;
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $start = ($page-1)*$limit;
        $result = $this->growGrassFollowModel->getSome($where, $field, $order, $start, $limit);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取数据总数
     * @param $where array
     * @return array
     */
    public function getCount($where = []){
        $result = $this->growGrassFollowModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     *添加一条数据
     * @param $where array
     * @return array
     */
    public function add($data){
        if(empty($data)){
            return false;
        }
        $result = $this->growGrassFollowModel->add($data);
        if(empty($result)) return false;
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

        $result = $this->growGrassFollowModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }
}