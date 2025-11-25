<?php
/**
 * 景区团体票旅行社列表
 */

namespace app\life_tools\model\service\group;

use app\life_tools\model\db\LifeToolsGroupSetting;
use app\life_tools\model\db\LifeToolsGroupTravelAgency;
use app\life_tools\model\db\LifeToolsGroupTicket;
use app\life_tools\model\db\User;
use app\life_tools\model\db\LifeTools;

class LifeToolsGroupTravelAgencyService
{
    public $lifeToolsGroupTravelAgencyModel = null;

    public function __construct()
    {
        $this->lifeToolsGroupTravelAgencyModel = new LifeToolsGroupTravelAgency();
        $this->lifeToolsGroupSettingModel = new LifeToolsGroupSetting();
        $this->lifeToolsGroupTicketModel = new LifeToolsGroupTicket();
        $this->userModel = new User();
        $this->lifeToolsModel = new LifeTools();
    }

    /**
     * 新增旅行社认证审核记录
     * @author nidan
     * @date 2022/3/21
     */
    public function addAudit($param)
    {
        if(empty($param['mer_id']) || empty($param['custom_form'])){
            throw new \think\Exception('参数错误');
        }
        //检查自定义表单
        foreach ($param['custom_form'] as $v){
            if($v['type'] == 'image' && count($v['show_value'])>$v['image_max_num'] && $v['image_max_num'] > 0){//判断自定义表单中图片数量是否超标
                throw new \think\Exception($v['title'].'最多上传'.$v['image_max_num'].'张图片');
            }
            if($v['is_must']>0 && empty($v['show_value'])){
                throw new \think\Exception($v['title'].'参数不能为空');
            }
        }
        //查询设置
        $auditSetInfo = $this->lifeToolsGroupSettingModel->getSetDetal($param['mer_id']);
        //是否为手动审核
        $isHand = ($auditSetInfo && $auditSetInfo['travel_agency_audit'] == 1) ? false : true;
        $status = $isHand ? 0:1;
        //查询是否已经申请过认证
        $audit = $this->lifeToolsGroupTravelAgencyModel->getStatus($param['mer_id'],$param['uid']);

        if($audit && $audit['status'] == 0 && $isHand){
            throw new \think\Exception('您已提交认证审核，请等待审核结果');
        }else
        if($audit && $audit['status'] == 1){
            throw new \think\Exception('您的认证审核已通过，请勿重复提交');
        }else
        if(($audit && $audit['status'] == 2) || ($audit && $audit['status'] == 0 && !$isHand)){
            //编辑
            $travel_agency_custom_form = json_encode($param['custom_form'],JSON_UNESCAPED_UNICODE);
            if($audit['travel_agency_custom_form'] !== $travel_agency_custom_form){
                $update = $this->lifeToolsGroupTravelAgencyModel->where(['mer_id'=>$param['mer_id'],'uid'=>$param['uid']])->update([
                    'status'=>$status,
                    'travel_agency_custom_form'=>$travel_agency_custom_form
                ]);
                if(!$update){
                    return false;
                }
            }
        }else
        if(!$audit){
            $data= [
                'mer_id'=>$param['mer_id'],
                'uid'=>$param['uid'],
                'travel_agency_custom_form'=>json_encode($param['custom_form'],JSON_UNESCAPED_UNICODE),
                'status'=>$status,
                'create_time'=>time()
            ];
            $add = $this->lifeToolsGroupTravelAgencyModel->insert($data);
            if(!$add){
                return false;
            }
        }else{
            throw new \think\Exception('您的认证审核状态异常，请联系管理员');
        }
        return true;
    }

    /**
     * 获取旅行社列表
     * @author nidan
     * @date 2022/3/21
     * @param $param
     */
    public function getTravelList($params)
    {
        $where = [];
        if(isset($params['mer_id']) && $params['mer_id']){
            $where[] = ['l.mer_id' ,'=', $params['mer_id']];
        }
        
        if(isset($params['uid']) && $params['uid']){
            $where[] = ['u.uid' ,'=', $params['uid']];
        }

        if(isset($params['mer_id_arr']) && $params['mer_id_arr']){
            $where[] = ['l.mer_id' ,'in', implode(',' ,$params['mer_id_arr'])];
        }

        if(!empty($params['keyword_name'])){
            $where[] = ['u.nickname' ,'=', $params['keyword_name']];
        }
        if(!empty($params['keyword_phone'])){
            $where[] = ['u.phone' ,'=', $params['keyword_phone']];
        }
        if(isset($params['status']) && $params['status'] !== 'all' && in_array($params['status'],[0,1,2])){
            $where[] = ['l.status' ,'=',$params['status']];
        }
        $where[] = ['l.is_del','=',0];
        $data = $this->lifeToolsGroupTravelAgencyModel->getTravelList($where,$params['page_size']);
        return $data;
    }

    /**
     * 获取旅行社
     * @param $uid
     */
    public function getTravelByUid($uid, $merId)
    {
        $condition = [];
        $condition[] = ['mer_id' ,'=', $merId];
        $condition[] = ['uid' ,'=', $uid];
        $condition[] = ['is_del','=',0];
        $data = $this->lifeToolsGroupTravelAgencyModel->getOne($condition);
        return $data;
    }

    /**
     * 修改旅行社认证审核状态
     * @author nidan
     * @date 2022/3/22
     */
    public function updateTravelStatus($params)
    {
        $merId = $params['mer_id'] ?? 0;
        $travelId = $params['travel_id'] ?? 0;
        $status = $params['status'] ?? 0;
        if(!in_array($status,[1,2])){
            throw new \think\Exception(L_('审核状态异常，请重新提交'), 1001);
        }
        if(!$merId || !$travelId || !$status){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where = [
            'mer_id' => $merId,
            'id' => $travelId
        ];
        $data = [
            'status' => $params['status'],
            'audit_msg' => $params['note'],
            'audit_time' => time()
        ];
        $update = $this->lifeToolsGroupTravelAgencyModel->where($where)->update($data);
        if(!$update){
            throw new \think\Exception('操作失败');
        }
        return ['msg' => '操作成功'];
    }

    /**
     * 获取旅行社用户信息
     * @author nidan
     * @date 2022/3/23
     */
    public function getTravelUser($uid)
    {
        //获取用户信息
        $user = $this->userModel->getOne(['uid'=>$uid],['nickname','avatar']);
        //获取认证商家数量
        $authenticationNum = $this->lifeToolsGroupTravelAgencyModel->getAuthenticationTravelNum($uid);
        if(empty($user['avatar'])){
            // 读取配置缓存
            $cache = cache();
            $config = $cache->get('config');
            $user['avatar']   =   $config['site_url'] . '/static/images/user_avatar.jpg';
        }
        $data = [
            'user'  =>  $user->toArray(),
            'authentication_num'  =>  $authenticationNum
        ];
        return $data;
    }

    /**
     * 获取商家列表
     * @author nidan
     * @date 2022/3/23
     */
    public function getMerchantList($params)
    {
        $uid = $params['uid'];
        $pageSize = $params['pageSize'];
        $select_type = $params['select_type'] ?: 0;
        $where = [
            'a.is_del' => 0,
        ];
        if($select_type){
            $where['c.uid'] = $uid;
            $order = 'c.audit_time desc';//我的认证列表根据审核提交时间排序
        }else{
            $order = 'b.mer_id';
        }
        $field = 'b.mer_id,b.name,b.logo,b.phone,c.id as audit_id,c.status as audit_status,c.audit_msg';
        $merchant = $this->lifeToolsGroupTicketModel->getHotMerchantList($where,$field,$pageSize,$order,$uid);
        //查询商家旗下景区
        if($merchant['data']){
            $merIdAry = [];
            foreach ($merchant['data'] as $v){
                $merIdAry[] = $v['mer_id'];
            }
            $toolWhere = [
                'is_del' => 0,
                'type' => 'scenic'
            ];
            $scenicList = $this->lifeToolsModel->getListByMerchant($toolWhere,$merIdAry,$toolField = 'mer_id,title',$order='sort desc');
            $scenicInfoAry = [];
            foreach ($scenicList as $scenic){
                $scenicInfoAry[$scenic['mer_id']][] = $scenic['title'];
            }
            foreach ($merchant['data'] as &$v){
                $v['logo'] = replace_file_domain($v['logo']);
                $v['scenic'] = $scenicInfoAry[$v['mer_id']]??[];
            }
        }
        return $merchant;
    }
}