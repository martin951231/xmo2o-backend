<?php
/**
 * HR管理serice
 * Author: wangchen
 * Date Time: 2021/6/22
 */

namespace app\recruit\model\service;

use app\common\model\db\User;
use app\common\model\service\UserService;
use net\Http;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitCompany;

class NewRecruitHrService
{
    /**
     * 列表
     */
    public function getRecruitHrList($cont, $mer_id, $page, $pageSize){
        if ($mer_id < 1) {
            throw new \think\Exception("商家ID不存在");
        }
        $where[] = ['g.status','=',0];
        $where[] = ['g.mer_id','=',$mer_id];
        if(!empty($cont)) {
            $where[] = ['g.name', 'like', '%'.$cont.'%'];
        }
        $field = "g.*, u.last_time";
        $order = 'g.sort DESC, g.id DESC';
        $list = (new NewRecruitHr())->getRecruitHrList($where, $field, $order, $page, $pageSize);
        if($list['list']){
            foreach($list['list'] as $k=>$v){
                $list['list'][$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
                $list['list'][$k]['last_time'] = date('Y-m-d H:i:s',$v['last_time']);
                $list['list'][$k]['release'] = (new NewRecruitJob())->where(['author'=>$v['uid'],'is_del'=>0,'add_type'=>0])->count();
            }
        }
        return $list;
    }

    /**
     * 保存
     */
    public function getRecruitHrCreate($id, $params){
        if ($params['phone']) {
            $where = [['phone', '=', $params['phone']], ['status', '=', 0]];
            if ($id > 0) {
                $where[] = ['id', '<>', $id];
            }
            $phoneUseCount = (new NewRecruitHr())->getRecruitHrCount($where);
            if ($phoneUseCount > 0) {
                throw new \think\Exception("手机号已被使用");
            }
            $thisUserUid = (new UserService())->getUserByPhone($params['phone']);
            if ($thisUserUid < 1) {
                throw new \think\Exception("登录手机号未注册平台 用户");
            }
            $params['uid'] = $thisUserUid;
        }
        $params['add_time'] = $params['update_time'] = time();
        $list = (new NewRecruitHr())->getRecruitHrCreate($id, $params);
        return $list;
    }

    /**
     * 单条
     */
    public function getRecruitHrInfo($id){
        if($id < 1){
			return [];
		}
        $where = ['id'=>$id];
        $list = (new NewRecruitHr())->getRecruitHrInfo($where);
        return $list;
    }

    /**
     * 单条UID
     */
    public function getRecruitHrOneInfo($uid){
        if($uid < 1){
			return [];
		}
        $where = ['uid'=>$uid];
        $list = (new NewRecruitHr())->getRecruitHrInfo($where);
        return $list;
    }

    /**
     * 单条
     */
    public function getRecruitHrOne($id){
        if($id < 1){
            return [];
        }
        $where = ['uid'=>$id];
        $ret=(new User())->getOne($where);
        if(!empty($ret)){
            $ret=$ret->toArray();
            $where = ['phone'=>$ret['phone']];
            $list = (new NewRecruitHr())->getRecruitHrInfo($where);
            if(!empty($list)){
                $list=$list->toArray();
            }
            return $list;
        }else{
            return false;
        }
    }
    /**
     * 移除
     */
    public function getRecruitHrDel($id){
        if($id < 1){
			throw new \think\Exception("缺少参数");	
		}
        $where = ['id'=>$id];
        // 判断是否存在发布职位
        $release = (new NewRecruitHr())->getRecruitHrInfo($where);
        if($release){
            // 删除对应发布职位
            $count = (new NewRecruitJob())->where(['author'=>$release['uid'],'is_del'=>0,'status'=>1])->count();
            if($count){
                (new NewRecruitJob())->where(['author'=>$release['uid'],'is_del'=>0,'status'=>1])->update(['is_del'=>1]);
                (new NewRecruitCompany())->where(['mer_id'=>$release['mer_id']])->dec('jobs',$count)->update();
            }
        }
        $list = (new NewRecruitHr())->getRecruitHrDel($where);
        return $list;
    }
}