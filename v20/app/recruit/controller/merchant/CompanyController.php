<?php

namespace app\recruit\controller\merchant;

use app\recruit\model\service\CompanyService;
use app\recruit\model\service\RecruitWelfareService;
use think\Exception;

/**
 * 公司信息补充
 * @package app\recruit\controller\merchant
 */
class CompanyController extends ApiBaseController
{

    public function getInfo()
    {
        $merId = $this->request->log_uid;
        $info = (new CompanyService())->getInfoByMerId($merId);
        if (!$info) {
            return api_output(1000, []);
        } else {
            $info['images_arr'] = explode(';', $info['images']);
            $info['show_images_arr'] = array_map(function ($r) {
                return ['path' => $r, 'url' => replace_file_domain($r)];
            }, $info['images_arr']);
        }
        return api_output(1000, $info);
    }


    public function saveInfo()
    {
        $params['mer_id'] = $this->request->log_uid;
        $params['name'] = $this->request->param('name', '', 'trim');
        $params['short_name'] = $this->request->param('short_name', '', 'trim');
        $params['long'] = $this->request->param('long', 0);
        $params['lat'] = $this->request->param('lat', 0);
        $params['people_scale'] = $this->request->param('people_scale', 1, 'intval');
        $params['financing_status'] = $this->request->param('financing_status', 1, 'intval');
        $params['nature'] = $this->request->param('nature', 1, 'intval');
        $params['intro'] = $this->request->param('intro', '', 'trim');
        $params['images'] = $this->request->param('images', []);
        $params['defaultIndustry'] = $this->request->param('defaultIndustry', []);
        try {
            (new CompanyService())->saveInfo($params);
            return api_output(1000, []);
        } catch (Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    public function industryTree()
    {
        $tree = (new CompanyService())->industryTree('id AS value,name AS label,sort,fid,id');
        return api_output(1000, $tree);
    }

    /**
     * 福利标签列表
     */
    public function getRecruitWelfareLabelList(){
        $result = (new RecruitWelfareService())->getRecruitWelfareLabelList();
        return api_output(1000, $result);
    }

    /**
     * 保存福利标签
     */
    public function getRecruitWelfareLabelCreate(){
        $merId = $this->request->log_uid;
        $info = (new CompanyService())->getInfoByMerId($merId);
        if (!$info) {
            return api_output(1003, [], L_('请完善公司信息'));
        } else {
            $welfare = $this->request->param('checked', [], 'trim');
            if($welfare){
                $welfareStr = implode(',', $welfare);
                $info = (new CompanyService())->getRecruitWelfareLabelCreate($merId, $welfareStr);
            }
        }
        return api_output(1000, $info);
    }

    /**
     * 获取福利标签
     */
    public function getRecruitWelfareLabelInfo(){
        $merId = $this->request->log_uid;
        $info = (new CompanyService())->getInfoByMerId($merId);
        if (!$info) {
            return api_output(1003, [], L_('商家信息不存在'));
        } else {
            if($info['welfare']){
                $return = explode(',', $info['welfare']);
            }else{
                $return = [];
            }
        }
        return api_output(1000, $return);
    }
}