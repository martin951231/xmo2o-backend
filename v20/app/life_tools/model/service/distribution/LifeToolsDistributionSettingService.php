<?php


namespace app\life_tools\model\service\distribution;

use app\life_tools\model\db\LifeToolsDistributionSetting;

class LifeToolsDistributionSettingService
{
    public $lifeToolsDistributionSettingModel = null;

    public function __construct()
    {
        $this->lifeToolsDistributionSettingModel = new LifeToolsDistributionSetting();
    }
    /**
     * 编辑配置
     * @author nidan
     * @date 2022/4/6
     */
    public function editData($param)
    {
        $data['mer_id'] = $param['mer_id'] ?? 0;
        $data['status_distribution'] = $param['status_distribution'] ?? 0;
        $data['status_award'] = $param['status_award'] ?? 0;
        $data['distributor_audit'] = $param['distributor_audit'] ?? 0;
        $data['share_logo'] = $param['share_logo'] ?? '';
        $data['update_status_time'] = $param['update_status_time'] ?? 0;
        $data['personal_custom_form'] = $param['personal_custom_form'] ? json_encode($param['personal_custom_form']) : '';
        $data['business_custom_form'] = $param['business_custom_form'] ? json_encode($param['business_custom_form']) : '';
        $data['description'] = $param['description'] ?? '';
        $data['share_type'] = $param['share_type'] ?? 1;
        $data['status_show_avatar'] = $param['status_show_avatar'] ?? 1;
        $data['status_show_price'] = $param['status_show_price'] ?? 1;
        $data['update_time'] = time();

        if(!$data['mer_id']){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        $detail = $this->lifeToolsDistributionSettingModel->getOne(['mer_id'=>$data['mer_id']]);
        if($detail){
            $res = $this->lifeToolsDistributionSettingModel->updateThis(['mer_id'=>$data['mer_id']], $data);
        }else{
            $data['create_time'] = time();
            $res = $this->lifeToolsDistributionSettingModel->add($data);
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

        $detail = $this->lifeToolsDistributionSettingModel->getOne(['mer_id'=>$merId]);
        if(!$detail){
            $detail['id']=0;
            $detail['personal_custom_form']=[];
            $detail['business_custom_form']=[];
            return $detail;
        }

        $detail->personal_custom_form = json_decode($detail->personal_custom_form, true) ?: [];
        $detail->business_custom_form = json_decode($detail->business_custom_form, true) ?: [];
        $detail = $detail ->toArray();

        // 排序
        $sortArr = [];
        foreach ($detail['personal_custom_form'] as $key => $row)
        {
            $sortArr[$key]  = $row['sort'];
            $detail['personal_custom_form'][$key]['is_must']=$row['is_must']*1;
            $detail['personal_custom_form'][$key]['status']=$row['status']*1;
        }
        array_multisort($sortArr, SORT_DESC, $detail['personal_custom_form']);


        $sortArr = [];
        foreach ($detail['business_custom_form'] as $key => $row)
        {
            $sortArr[$key]  = $row['sort'];
            $detail['business_custom_form'][$key]['is_must']=$row['is_must']*1;
            $detail['business_custom_form'][$key]['status']=$row['status']*1;
        }
        array_multisort($sortArr, SORT_DESC, $detail['business_custom_form']);
        $detail['share_logo'] = $detail['share_logo']?replace_file_domain($detail['share_logo']):'';
        return $detail;
    }

}