<?php
/**
 * 汪晨
 * 2021/08/17
 * 技术人员
 */
namespace app\new_marketing\model\service;

use app\common\model\service\UserService;
use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingTeamArtisan;
use app\common\model\db\User;
use think\Exception;

class MarketingArtisanService
{
    // 技术人员列表
    public function getMarketingArtisanList($where, $field, $order, $page, $pageSize){
        $list = (new NewMarketingArtisan())->getMarketingArtisanList($where, $field, $order, $page, $pageSize);
        $count = (new NewMarketingArtisan())->getMarketingArtisanCount($where, $field);
        if($list){
            foreach($list as $k=>$v){
                // 时间
                $list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                $list[$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
                // 主管
                if($v['director_id'] > 0){
                    $dirFind = (new NewMarketingArtisan())->where(['id'=>$v['director_id'],'is_director'=>1,'status'=>0])->find();
                    $list[$k]['director_name'] = $dirFind['name'];
                }else{
                    $list[$k]['director_name'] = '-';
                }
                // 团队
                $teamList = (new NewMarketingTeamArtisan())->getMarketingArtisanTeamList(['artisan_id'=>$v['id']],'a.name');
                $team_name = '';
                if($teamList){
                    $teamList = $teamList->toArray();
                    foreach($teamList as $ks=>$vs){
                        if($vs['name']){
                            if($ks == 0){
                                $team_name = $vs['name'];
                            }else{
                                $team_name = $team_name.'、'.$vs['name'];
                            }
                        }
                    }
                }
                $list[$k]['team_name'] = $team_name;
            }
            $return['list'] = $list;
        }else{
            $return['list'] = [];
        }
        $return['count'] = $count;
        // 筛选主管列表
        $dirList = $this->getDirectorList(['status'=>0,'is_director'=>1]);
        $return['dirList'] = $dirList;
        return $return;
    }

    // 选择更换主管列表
    public function getDirectorList($where){
        $return = (new NewMarketingArtisan())->field('id,name')->where($where)->select();
        if(empty($return)){
            return [];
        }else{
            $return = $return->toArray();
            foreach($return as $k=>$v){
                $return[$k]['director_id'] = $v['id'];
                $return[$k]['director_name'] = $v['name'];
                unset($return[$k]['name']);
                unset($return[$k]['id']);
            }
            return $return;
        }
    }

    // 技术人员操作
    public function getMarketingArtisanCreate($param){
        $data = array(
            'name' => $param['name'],
            'director_id' => $param['director_id'],
        );
        if(empty($param['id'])){
            // 判断uid

            (new MarketingPersonService())->teamMemberCode(['uid' => $param['phone']]);

            $thisUser = (new UserService())->getOne([['phone', '=', $param['phone']], ['status', '<>', 4]]);
            if (empty($thisUser)) {
                throw new Exception('绑定账号不存在');
            }
            $data['uid'] = $thisUser['uid'];
            $data['phone'] = $thisUser['phone'];

            // 新增
            $data['add_time'] = $data['update_time'] = time();
            $id = (new NewMarketingArtisan())->add($data);
            // 添加技术主管人数
            if($param['director_id'] > 0){
                (new NewMarketingArtisan())->where(['id'=>$param['director_id'], 'is_director'=>1, 'status'=>0])->inc('num')->update();
            }
        }else{
            // 修改
            $where = ['id'=>$param['id']];
            $data['update_time'] = time();
            $idFind = (new NewMarketingArtisan())->where($where)->find();
            // echo $idFind['director_id'];
            if($idFind['director_id'] > 0){
                // 旧数据减少
                (new NewMarketingArtisan())->where(['id'=>$idFind['director_id'], 'is_director'=>1, 'status'=>0])->dec('num')->update();
            }
            if($param['director_id'] > 0){
                // 新数据增加
                (new NewMarketingArtisan())->where(['id'=>$param['director_id'], 'is_director'=>1, 'status'=>0])->inc('num')->update();
            }
            (new NewMarketingArtisan())->where($where)->save($data);
            $id = $param['id'];
        }
        return $id;
    }

    // 技术人员根据条件获取一条数据
    public function getMarketingArtisanInfo($id){
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where = [
            'id' => $id,
        ];
        $returnArr = (new NewMarketingArtisan())->where($where)->find();
        if(empty($returnArr)){
            return [];
        }
        return $returnArr;
    }

    // 技术人员选择、更换主管
    public function getMarketingArtisanDir($param){
        if(empty($param['id'])){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        // 修改
        $where = ['id'=>$param['id']];
        $data['director_id'] = $param['director_id'];
        $data['update_time'] = time();
        (new NewMarketingArtisan())->where($where)->save($data);
        return $param['id'];
    }

    // 技术人员移除
    public function getMarketingArtisanDel($id)
    {
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $list = (new NewMarketingArtisan())->where(['id'=>$id])->update(['status'=>1]);
        return $list;
    }


    
    // 技术主管操作
    public function getMarketingDirectorCreate($param){
        $data = array(
            'name' => $param['name'],
            'team_percent' => $param['team_percent'],
        );
        if(empty($param['id'])){
            // 判断uid

            (new MarketingPersonService())->teamMemberCode(['uid' => $param['phone']]);
            // 新增
            $thisUser = (new UserService())->getOne([['phone', '=', $param['phone']], ['status', '<>', 4]]);
            if (empty($thisUser)) {
                throw new Exception('绑定账号不存在');
            }

            $data['uid'] = $thisUser['uid'];
            $data['phone'] = $thisUser['phone'];

            $data['is_director'] = 1;
            $data['add_time'] = $data['update_time'] = time();
            $id = (new NewMarketingArtisan())->add($data);
        }else{
            // 修改
            $where = ['id'=>$param['id']];
            $data['update_time'] = time();
            (new NewMarketingArtisan())->where($where)->save($data);
            $id = $param['id'];
        }
        return $id;
    }

    // 技术主管移除
    public function getMarketingDirectorDel($id)
    {
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $find = (new NewMarketingArtisan())->where(['id'=>$id])->find();
        if(empty($find)){
            throw new \think\Exception(L_('此技术主管不存在'), 1001);
        }
        $list = (new NewMarketingArtisan())->where(['id'=>$id])->update(['status'=>1]);
        if($list){
            (new NewMarketingArtisan())->where(['director_id'=>$find['id']])->update(['director_id'=>0]);
        }
        return $list;
    }

    // 技术主管移出技术人员
    public function getMarketingDirectorArtisan($id)
    {
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $find = (new NewMarketingArtisan())->where(['id'=>$id])->find();
        $list = (new NewMarketingArtisan())->where(['id'=>$id])->update(['director_id'=>0]);
        if($find['director_id'] > 0){
            (new NewMarketingArtisan())->where(['id'=>$find['director_id'], 'is_director'=>1, 'status'=>0])->dec('num')->update();
        }
        return $list;
    }

    /**
     * 获得多条数据
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
		$start = ($page - 1) * $limit;
		$start = max($start, 0);
        $list = (new NewMarketingArtisan())->getSome($where,$field,$order,$start,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

    /**
     * 增加提成金额
     * @return bool
     */
    public function incCommission($where, $money, $field){
		if(empty($money) || empty($where) || empty($field)){
			return false;
		}
        $res = (new NewMarketingArtisan())->where($where)->inc($field, $money)->update();
        if(!$res) {
            return false;
        }
        return $res;
    }

	/**
	 * 获得一条数据
	 * @return array
	 */
	public function getOne($where = [], $field = true, $order = []){
		$res = (new NewMarketingArtisan())->getOne($where,$field,$order);
		if(!$res) {
			return [];
		}
		return $res->toArray();
	}

	/**
	 * @param $where
	 * @return array
	 * 获取关于团队的技术员数组
	 */
	public function getMarketingTeamArtisanList($where,$field){
		$res = (new NewMarketingArtisan())->getMarketingTeamArtisanList($where,$field);
		if(!$res){
			return [];
		}
		return $res;
	}

}