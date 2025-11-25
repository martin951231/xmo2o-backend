<?php
/**
 * 景区团体票配置
 */

namespace app\life_tools\model\service\group;

use app\life_tools\model\db\LifeToolsGroupSetting;
use app\life_tools\model\db\LifeToolsGroupTravelAgency;

class LifeToolsGroupSettingService
{
    public $lifeToolsGroupSettingModel = null;

    public function __construct()
    {
        $this->lifeToolsGroupSettingModel = new LifeToolsGroupSetting();
        $this->lifeToolsGroupTravelAgencyModel = new LifeToolsGroupTravelAgency();
    }

    /**
     * 添加编辑
     */
    public function editData($param){
        $data['mer_id'] = $param['mer_id'] ?? 0;
        $data['travel_agency_audit'] = $param['travel_agency_audit'] ?? 0; // 旅行社审核1-自动审核0-需要审核
        $data['buy_audit'] = $param['buy_audit'] ?? 0; //购票审核1-自动审核0-需要审核
        $data['expiration_time'] = $param['expiration_time'] ?? 0; // 订单过期时间
        $data['travel_agency_custom_form'] = $param['travel_agency_custom_form'] ? json_encode($param['travel_agency_custom_form']) : ''; // 旅行社审核模板配置
        $data['tour_guide_custom_form'] = $param['tour_guide_custom_form'] ? json_encode($param['tour_guide_custom_form']) : ''; // 购票导游信息模板配置
        $data['tourists_custom_form'] = $param['tourists_custom_form'] ? json_encode($param['tourists_custom_form']) : ''; // 购票游客信息模板配置
        $data['update_time'] = time();

        if(!$data['mer_id']){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $detail = $this->lifeToolsGroupSettingModel->getOne(['mer_id'=>$data['mer_id']]);
        if($detail){
            $res = $this->lifeToolsGroupSettingModel->updateThis(['mer_id'=>$data['mer_id']], $data);
        }else{
            $data['create_time'] = time();
            $res = $this->lifeToolsGroupSettingModel->add($data);
        }

        if($res === false){
            throw new \think\Exception(L_('保存失败，请稍后重试'), 1003);
        }

        return ['msg' => '保存成功'];
    }

    /**
     * 获得配置详情
     */
    public function getDataDetail($param){
        $merId = $param['mer_id'] ?? 0;
        if(!$merId){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
      
        $detail = $this->lifeToolsGroupSettingModel->getOne(['mer_id'=>$merId]);
        if(!$detail){
            $detail['id']=0;
            $detail['travel_agency_custom_form']=[];
            $detail['tour_guide_custom_form']=[];
            $detail['tourists_custom_form']=[];
            return $detail;
        }

        $detail->travel_agency_custom_form = json_decode($detail->travel_agency_custom_form, true) ?: [];
        $detail->tour_guide_custom_form = json_decode($detail->tour_guide_custom_form, true) ?: [];
        $detail->tourists_custom_form = json_decode($detail->tourists_custom_form, true) ?: [];
        $detail = $detail ->toArray();
        
        // 排序
        $sortArr = [];
        foreach ($detail['travel_agency_custom_form'] as $key => $row)
        {
            $sortArr[$key]  = $row['sort'];
            $detail['travel_agency_custom_form'][$key]['is_must']=$row['is_must']*1;
            $detail['travel_agency_custom_form'][$key]['status']=$row['status']*1;
        }
        array_multisort($sortArr, SORT_DESC, $detail['travel_agency_custom_form']);

        
        $sortArr = [];
        foreach ($detail['tour_guide_custom_form'] as $key => $row)
        {
            $sortArr[$key]  = $row['sort'];
            $detail['tour_guide_custom_form'][$key]['is_must']=$row['is_must']*1;
            $detail['tour_guide_custom_form'][$key]['status']=$row['status']*1;
        }
        array_multisort($sortArr, SORT_DESC, $detail['tour_guide_custom_form']);

        
        $sortArr = [];
        foreach ($detail['tourists_custom_form'] as $key => $row)
        {
            $sortArr[$key]  = $row['sort'];
            $detail['tourists_custom_form'][$key]['is_must']=$row['is_must']*1;
            $detail['tourists_custom_form'][$key]['status']=$row['status']*1;
        }
        array_multisort($sortArr, SORT_DESC, $detail['tourists_custom_form']);

        return $detail;
    }
    
    /**
     * 旅行社认证页数据
     * @author nidan
     * @date 2022/3/21
     */
    public function confirm($param)
    {
        $merId = $param['mer_id']??0;
        if(empty($merId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where = ['mer_id' => $merId];
        //查询审核状态
        $audit = $this->lifeToolsGroupTravelAgencyModel->getStatus($param['mer_id'],$param['uid']);
        if($audit && $audit['status'] == 0){
            throw new \think\Exception('您已提交认证审核，请等待审核结果');
        }else
        if($audit && $audit['status'] == 1){
            throw new \think\Exception('您的认证审核已通过，请勿重复提交');
        }
        //获取认证页信息
        $data = $this->lifeToolsGroupSettingModel->getOne($where,['travel_agency_custom_form']);
        $travelForm = $data && $data['travel_agency_custom_form']?json_decode($data['travel_agency_custom_form'],true):[];
        $key_arrays = [];
        foreach ($travelForm as $k=>$v){
            if(is_array($v) && $v['status']){
                $key_arrays[] = $v['sort'];
            }elseif(!$v['status']){
                unset($travelForm[$k]);
            }else{
                continue;
            }
        }
        if(count($travelForm) > 1){
            array_multisort($key_arrays,SORT_DESC,SORT_NUMERIC,$travelForm);
        }
        return $travelForm;
    }
}