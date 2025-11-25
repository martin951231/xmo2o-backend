<?php

namespace app\new_marketing\model\service;

use app\common\model\db\Area;
use app\common\model\db\User;
use app\common\model\service\AreaService;
use app\new_marketing\model\db\NewMarketingArtisan;
use app\new_marketing\model\db\NewMarketingArtisanTeam;
use app\new_marketing\model\db\NewMarketingPersonManager;
use app\new_marketing\model\db\NewMarketingLog;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\db\NewMarketingPersonSalesman;
use app\new_marketing\model\db\NewMarketingPersonAgency;
use app\new_marketing\model\db\NewMarketingTeam;
use app\new_marketing\model\db\NewMarketingTeamArtisan;
use think\Exception;
use think\facade\Db;

class RegionalAgencyService
{
    /**
     * @param $param
     * @return mixed
     * 区域管理列表
     */
    public function regionalAgencyList($param)
    {
        $where = [];
        if ($param['province_id'] && $param['city_id'] && $param['area_id']) {
            $where = [['r.province_id', '=', $param['province_id']], ['r.city_id', '=', $param['city_id']], ['r.area_id', '=', $param['area_id']]];
        }

        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $param['start_time'] = strtotime($param['start_time']);
            array_push($where, ['r.add_time', '>', $param['start_time']]);
        }

        if (!empty($param['end_time']) && $param['start_time'] != $param['end_time']) {
            $param['end_time'] = strtotime($param['end_time'] . " 23:59:59");
            array_push($where, ['r.add_time', '<=', $param['end_time']]);
        }
        /*if($param['uid']){
            array_push($where,['s.uid','=',$param['uid']]);
        }*/
        if (!empty($param['put_text'])) {
            array_push($where, ['u.phone|s.name', 'like', '%' . $param['put_text'] . '%']);
        }
        array_push($where, [['r.is_del', '=', 0]]);
        $all = (new NewMarketingPerson())->getRegionalAgencyList($where, "s.*,r.person_id,r.invitation_code,r.total_performance,r.total_percentage,r.store_percent,r.village_percent,r.province_id,r.city_id,r.area_id,r.add_time", 'r.id desc');
        foreach ($all['list'] as $k => $v) {
            $all['list'][$k]['team_nums'] = (new NewMarketingTeam())->getCount(['area_uid' => $v['id']]);
            $all['list'][$k]['total_performance'] = (new NewMarketingTeam())->where(['area_uid' => $v['id']])->sum('achievement');
            if ($v['province_id'] && $v['city_id'] && $v['area_id']) {
                $where_pro = [['area_id', '=', $v['province_id']]];
                $where_city = [['area_id', '=', $v['city_id']]];
                $where_area = [['area_id', '=', $v['area_id']]];
                $field = "area_name";
                $pro = (new Area())->getNowCityTimezone($field, $where_pro);
                $city = (new Area())->getNowCityTimezone($field, $where_city);
                $area = (new Area())->getNowCityTimezone($field, $where_area);
                $all['list'][$k]['areas'] = $pro . " " . $city . " " . $area;
            } else {
                $all['list'][$k]['areas'] = "";
            }
            if ($v['add_time'] > 0) {
                $all['list'][$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        $list['list'] = $all['list'];
        $list['count'] = $all['count'];
        $list['areaList'] = (new AreaService())->getAllArea(2, "*,area_id as value,area_name as label");
        return $list;
    }

    /**
     * @return \json
     * 新增查看用户是否合法
     */
    public function findRight($where)
    {
        $ret = (new User())->getOne($where);
        if (!empty($ret)) {
            $ret = $ret->toArray();
            $user = (new NewMarketingPerson())->getOne(['uid' => $ret['uid'], 'is_del' => 0]);
            if (empty($user)) {
                $assign['status'] = 1;
                $assign['data'] = $ret;
            } else {
                $assign['status'] = 2;
                $assign['data'] = [];
            }
        } else {
            $assign['status'] = 0;
            $assign['data'] = [];
        }
        return $assign;
    }

    /**
     * @return \json
     * @throws \think\Exception
     * 编辑查看用户是否合法
     */
    public function findRightEdit($where, $param)
    {
        $ret = (new User())->getOne($where);
        $user_agency = (new NewMarketingPerson())->getOne(['is_agency' => 1, 'id' => $param['id']]);
        if (!empty($ret)) {
            $ret = $ret->toArray();
            if (!empty($user_agency)) {
                $user_agency = $user_agency->toArray();
                if ($user_agency['phone'] != $param['phone']) {
                    $user_agency1 = (new NewMarketingPerson())->getOne(['is_agency' => 1, 'phone' => $param['phone']]);
                    if (!empty($user_agency1)) {
                        $assign['status'] = 2;
                        $assign['data'] = $ret;
                    } else {
                        $assign['status'] = 1;
                        $assign['data'] = $ret;
                    }
                } else {
                    $assign['status'] = 1;
                    $assign['data'] = $ret;
                }
                return $assign;
            } else {
                throw new Exception('缺少必要参数');
            }
        } else {
            $assign['status'] = 0;
            $assign['data'] = [];
            return $assign;
        }

    }

    public function findUserRight($where)
    {
        $ret = (new User())->getOne($where);
        if (!empty($ret)) {
            $ret = $ret->toArray();
            $assign['status'] = 1;
            $assign['data'] = $ret;
        } else {
            $assign['status'] = 0;
            $assign['data'] = [];
        }

        return $assign;
    }

    /**
     * @param $param
     * @return mixed
     * 添加保存
     */
    public function addRegionalAgency($param)
    {
        $param['invitation_code'] = $this->makeInvitationCode();
        $return['status'] = 1;
        $return['msg'] = "";
        Db::startTrans();
        try {
            if (empty($param['uid']) || !empty($param['phone'])) {
                $where = [['phone', '=', $param['phone']]];
                $msg=$this->findRight($where);
                if($msg['status']==1){
                    $param['uid']=$msg['data']['uid'];
                }elseif($msg['status']==0){
                    throw new Exception(L_('绑定账号不存在'));
                }else{
                    throw new Exception(L_('此用户id已经是业务员,不可以直接成为区域代理'));
                }
            }

            if (empty($param['uid']) || empty($param['phone'])) {
                throw new Exception(L_('绑定账号不存在'));
            }

            $thisPerson = (new NewMarketingPerson())->getOne(['uid' => $param['uid'],'is_del'=>0]);
            if ($thisPerson) {
                if ($thisPerson->is_agency) {
                    throw new Exception(L_('绑定账号已是区域代理，请勿重复添加'));
                } else if ($thisPerson->is_manager) {
                    throw new Exception(L_('绑定账号已是业务经理，请更换'));
                } else if ($thisPerson->is_salesman) {
                    throw new Exception(L_('绑定账号已是业务员，请更换'));
                }
            }

            $param1['uid'] = $param['uid'];
            $param1['phone'] = $param['phone'];
            $param1['is_agency'] = 1;
            $param1['name'] = $param['name'];
            $ret = (new NewMarketingPerson())->add($param1);
            if ($ret) {
                $param['person_id'] = $ret;
                unset($param['uid']);
                unset($param['phone']);
                unset($param['name']);
                $ret1 = (new NewMarketingPersonAgency())->add($param);
                if (!$ret1) {
                    throw new Exception(L_('添加失败,数据异常'));
                }
            } else {
                throw new Exception(L_('添加失败,数据异常'));
            }
            Db::commit();
            return $return;
        } catch (\Exception $e) {
            Db::rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $where
     * 修改
     */
    public function editRegionalAgency($where, $field)
    {
        $data = (new NewMarketingPerson())->getRegionalAgency($where, $field);
        $assign['list'] = [];
        if (!empty($data)) {
            $assign['list'] = $data;
        }
        $assign['areaList'] = (new AreaService())->getAllArea(2, "*,area_id as value,area_name as label");
        return $assign;
    }

    /**
     * @return array
     * 区域三级省市区
     */
    public function ajax_province()
    {
        return (new AreaService())->getAllArea(2, "*,area_id as value,area_name as label");
    }

    /**
     * @param $where
     * @param $param
     * @return bool
     * @throws Exception
     * 修改保存区域代理
     */
    public function saveRegionalAgency($param)
    {
        $return['status'] = 1;
        $return['msg'] = "";
        $where = [['id', '=', $param['id']]];
        $res = (new NewMarketingPerson())->getOne($where);
        if (empty($res)) {
            $return['status'] = 0;
            $return['msg'] = "用户不存在";
            return $return;
        }
        /*$where=[['phone','=',$param['phone'],['is_agency','=',0],['is_manager','=',1]]];
        $res = (new NewMarketingPerson())->getOne($where);

        $where=[['phone','=',$param['phone'],['is_agency','=',0],['is_salesman','=',1]]];
        $res1 = (new NewMarketingPerson())->getOne($where);
        if (!empty($res) || !empty($res1)) {
            $return['status'] = 0;
            $return['msg'] = "此用户id已经是业务员,不可以直接成为区域代理";
            return $return;
        }*/
        $user = (new User())->getOne(['phone' => $param['phone']]);
        if (empty($user)) {
            $return['status'] = 0;
            $return['msg'] = "用户不存在";
            return $return;
        }
        Db::startTrans();
        try {
            $param1['name'] = $param['name'];
            $param1['uid'] = $param['uid'];
            $ret = (new NewMarketingPerson())->updateThis($where, $param1);
            if ($ret !== false) {
                unset($param['phone']);
                unset($param['name']);
                unset($param['uid']);
                $where = [['person_id', '=', $param['id']]];
                $ret1 = (new NewMarketingPersonAgency())->updateThis($where, $param);
                if ($ret1 !== false) {
                    Db::commit();
                } else {
                    $return['status'] = 0;
                    $return['msg'] = "修改失败,数据异常";
                }
            } else {
                $return['status'] = 0;
                $return['msg'] = "修改失败,数据异常";
            }
            return $return;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * @param $where
     * @param $param
     * @return bool
     * 只修改主表
     */
    public function saveRegionalAgencyOne($where, $param)
    {
        $res = (new NewMarketingPerson())->getOne($where);
        if (!$res) {
            throw_exception('用户不存在');
        }
        $ret = (new NewMarketingPerson())->updateThis($where, $param);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \json
     * 删除区域代理
     */
    public function delRegionalAgency($param)
    {
        Db::startTrans();
        try {
            if (!empty($param['sel_id'])) {
                $data['is_agency'] = 1;
                $where = [['id', '=', $param['sel_id']]];
                (new NewMarketingPerson())->updateThis($where, $data);
                //接手的区域代理添加
                $agencyCount = (new NewMarketingPersonAgency())->where(['person_id'=>$param['sel_id'],'is_del'=>0])->count();
                if($agencyCount < 1){
                    $up_person_data['person_id'] = $param['sel_id'];
                    $up_person_data['invitation_code'] = $this->makeInvitationCode();
                    $up_person_data['add_time'] = time();
                    (new NewMarketingPersonAgency())->add($up_person_data);
                }

                $where_area = [['area_uid', '=', $param['id']]];
                $data_area['area_uid'] = $param['sel_id'];
                (new NewMarketingTeam())->updateThis($where_area, $data_area);
            }

            $person=(new NewMarketingPerson())->getOne(['id'=>$param['id']]);
            if(!empty($person)){
                $person=$person->toArray();
                if($person['is_agency']==1 && $person['is_manager']==0 && $person['is_salesman']==0){
                    (new NewMarketingPerson())->updateThis(['id'=>$param['id']], ['is_del'=>1]);
                }else{
                    (new NewMarketingTeam())->updateThis(['area_uid'=>$param['id']],['area_uid'=>0]);
                    (new NewMarketingPerson())->updateThis(['id'=>$param['id']], ['is_agency'=>0]);
                }
            }
            $ret = (new NewMarketingPersonAgency())->updateThis([['person_id', '=', $param['id']]],['is_del'=>1]);
            if ($ret!==false) {
                Db::commit();
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * 修改区域代理
     */
    public function updateAgency($param)
    {
        $where = [['id', '=', $param['id']]];
        $data['is_del'] = 1;
        $ret = (new NewMarketingPerson())->updateThis($where, $data);
        if ($ret !== false) {
            $where = [['person_id', '=', $param['id']]];
            $ret = (new NewMarketingPersonAgency())->updateThis($where, $data);
            if ($ret !== false) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * @return string
     * 随机生成邀请码
     */
    public function makeInvitationCode()
    {
        return createRandomStr();
    }

    /**
     * @param $where
     * @param $id
     * @return mixed
     * 打开降级弹窗
     */
    public function reduceWin($id)
    {
        $assign['manager'] = [];
        $msg = (new NewMarketingPersonAgency())->getOne(['person_id' => $id]);
        if (!empty($msg)) {
            $msg = $msg->toArray();
            $where = [['p.id', '<>', $id], ['p.is_agency', '=', 1], ['p.is_del', '=', 0], ['bp.province_id', '=', $msg['province_id']], ['bp.city_id', '=', $msg['city_id']], ['bp.area_id', '=', $msg['area_id']]];
            $assign['manager'] = (new NewMarketingPerson())->getSomePerson($where, 'p.id,p.name', true, 3);
        }
        $assign['team'] = (new NewMarketingTeam())->getSome(['is_del' => 0], 'id,name')->toArray();
        return $assign;
    }

    /**
     * @param $param
     * 降级保存
     */
    public function addReduce($param)
    {

        $sel = ((new NewMarketingPersonAgency()))->getOne(['person_id'=>$param['id']])->toArray();//降级的区域代理信息
        $sel1 = ((new NewMarketingPerson()))->getOne(['id'=>$param['id']])->toArray();//降级的人员信息
        Db::startTrans();
        try {
            $data['is_agency'] = 1;
            if (!empty($param['sel_id'])) {//有选择接手的区域代理人员
                $sel_manager = ((new NewMarketingPerson()))->getOne(['id'=>$param['sel_id']]);//接手的人员
                if (!empty($sel_manager)) {
                    $sel_manager = $sel_manager->toArray();
                    $ret = (new NewMarketingPerson())->updateThis(['id'=>$param['sel_id']], $data);//选择的人接手区域代理
                    if ($ret !== false) {
                        if ($param['sel_id']) {
                            //接手的区域代理添加
                            $sel = ((new NewMarketingPersonAgency()))->getOne(['person_id'=>$param['sel_id']]);
                            if(empty($sel)){
                                $up_person_data['person_id'] = $param['sel_id'];
                                $up_person_data['invitation_code'] = $this->makeInvitationCode();
                                $up_person_data['add_time'] = time();
                                (new NewMarketingPersonAgency())->add($up_person_data);
                            }
                            $data_area['area_uid'] = $param['sel_id'];
                            (new NewMarketingTeam())->updateThis(['area_uid'=>$param['id']], $data_area);//接手之前区域代理的团队
                        }
                        //升级接手记录
                        $add['status'] = 1;
                        $add['uid'] = $sel_manager['uid'];
                        $add['identity'] = 3;
                        $add['add_time'] = time();
                        (new NewMarketingLog())->add($add);
                    }
                }
            }

            if ($param['identity'] == 2) {//降级业务经理
                $data1['is_agency'] = 0;
                $data1['is_manager'] = 1;
               /* $data1['is_salesman'] = 0;*/
                //给业务经理加个记录
                $manager=(new NewMarketingPersonManager())->getOne(['person_id'=>$param['id']]);
                if(empty($manager)){
                    $newData['invitation_code'] = $this->makeInvitationCode();
                    $newData['person_id'] = $param['id'];
                    $newData['total_performance'] = $sel['total_performance'];
                    $newData['total_percentage'] = $sel['total_percentage'];
                    $newData['province_id'] = $sel['province_id'];
                    $newData['city_id'] = $sel['city_id'];
                    $newData['area_id'] = $sel['area_id'];
                    $newData['add_time'] = time();
                    (new NewMarketingPersonManager())->add($newData);
                }
                $add1['identity'] = 2;
            } else {//降级业务员
                $data1['is_agency'] = 0;
                $data1['is_salesman'] = 1;
               /* $data1['is_manager'] = 0;*/
                $add1['identity'] = 1;
                //给业务经员加个记录
                $salesman=(new NewMarketingPersonSalesman())->getOne([['person_id', '=', $param['id']], ['is_del', '=', 0]]);
                if(empty($salesman)) {
                    $newData['invitation_code'] = $this->makeInvitationCode();
                    $newData['person_id'] = $param['id'];
                    $newData['team_id'] = $param['team_id'];
                    $newData['join_team'] = 0;
                    $newData['add_time'] = time();
                    (new NewMarketingPersonSalesman())->add($newData);
                }
            }

            $where = [['id', '=', $param['id']]];//降级
            (new NewMarketingPerson())->updateThis($where, $data1);
            (new NewMarketingPersonAgency())->updateThis([['person_id', '=', $param['id']]], ['is_del'=>1]);//删除区域代理

            //降级记录
            $add1['status'] = 0;
            $add1['uid'] = $sel1['uid'];
            $add1['add_time'] = time();
            (new NewMarketingLog())->add($add1);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * @param $param
     * 团队列表
     */
    public function getTeamList($param)
    {
        $where = [['area_uid', '=', $param['id']]];
        $list = (new NewMarketingTeam())->getSome($where)->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['team_nums'] = (new NewMarketingPersonSalesman())->getCount(['team_id' => $v['id']]);
            $area_person = (new NewMarketingPerson())->getOne(['id' => $v['area_uid']]);
            if (!empty($area_person)) {
                $area_person = $area_person->toArray();
                $list[$k]['manager_name'] = $area_person['name'];
            } else {
                $list[$k]['manager_name'] = "";
            }
            $name_list = (new NewMarketingTeamArtisan())->getMarketingArtisanTeamName(['g.team_id' => $v['id']], 'n.name');
            $list[$k]['tec_name'] = implode(',', $name_list);
            $list[$k]['add_time'] = date("Y-m-d H:i:s", $v['add_time']);
            $list[$k]['total_performance'] = $v['achievement'];
        }
        return $list;
    }

    /**
     * @param $param
     * 更新折扣
     */
    public function updatePercent($param)
    {
        if ($param['length']) {
            foreach ($param['data'] as $k => $val) {
                $where = [['person_id', '=', $val['id']]];
                if (!empty($val['discount_ratio'])) {
                    $data['store_percent'] = $val['discount_ratio'];
                }

                if (!empty($val['discount_ratio1'])) {
                    $data['village_percent'] = $val['discount_ratio1'];
                }
                (new NewMarketingPersonAgency())->updateThis($where, $data);
            }
        } else {
            /*if (!empty($param['discount_ratio'])) {
                $data['store_percent'] = $param['discount_ratio'];
            }

            if (!empty($param['discount_ratio1'])) {
                $data['village_percent'] = $param['discount_ratio1'];
            }
            //$data['percent']=$param['discount_ratio'];
            $where = [['person_id', '>', 0]];
            (new NewMarketingPersonAgency())->updateThis($where, $data);*/
        }
        return true;
    }
}