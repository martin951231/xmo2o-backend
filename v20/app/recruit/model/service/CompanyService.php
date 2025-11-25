<?php

namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitIndustry;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitWelfare;
use app\recruit\model\db\NewRecruitCompanyUserCollect;
use app\recruit\model\db\NewRecruitCompanyUserForbidden;
use app\common\model\db\Merchant;
use think\Exception;

/**
 * 公司相关service
 * @package app\recruit\model\service
 */
class CompanyService
{
    public $companyMod = null;

    public $industryMod = null;

    public function __construct()
    {
        $this->companyMod = new NewRecruitCompany();
        $this->industryMod = new NewRecruitIndustry();
    }

    /**
     * 获取商家公司信息
     * @param $merId
     * @date: 2021/06/23
     */
    public function getInfoByMerId($merId)
    {
        return $this->companyMod->where('mer_id', '=', $merId)->findOrEmpty()->toArray();
    }

    /**
     * 保存公司信息
     * @param $params
     * @date: 2021/06/23
     */
    public function saveInfo($params)
    {
        if ($params['mer_id'] < 1) {
            throw new Exception(L_('参数不正确'));
        }
        if (empty($params['name'])) {
            throw new Exception(L_('公司名称不能为空'));
        }
        $images = $params['images'] ? array_filter($params['images']) : [];
        if (empty($images)) {
            throw new Exception(L_('请上传公司照片'));
        }
        if (empty($params['intro'])) {
            throw new Exception(L_('公司介绍不能为空'));
        }

        $data = [
            'mer_id' => $params['mer_id'],
            'name' => $params['name'],
            'short_name' => $params['short_name'],
            'long' => $params['long'],
            'lat' => $params['lat'],
            'people_scale' => $params['people_scale'],
            'financing_status' => $params['financing_status'],
            'nature' => $params['nature'],
            'intro' => $params['intro'],
            'images' => implode(';', $images),
            'industry_id1' => $params['defaultIndustry'][0] ?? 0,
            'industry_id2' => $params['defaultIndustry'][1] ?? 0,
            'update_time' => time()
        ];
        $record = $this->companyMod->where('mer_id', '=', $params['mer_id'])->findOrEmpty();
        if ($record->isEmpty()) {
            $rs = $this->companyMod->insert($data);
        } else {
            $rs = $this->companyMod->where('mer_id', '=', $params['mer_id'])->update($data);
        }
        return $rs;
    }


    /**
     * 行业树状图
     * @date: 2021/06/23
     */
    public function industryTree($fileds = '*')
    {
        $datas = $this->industryMod->field($fileds)->order('sort', 'desc')->select()->toArray();
        $parents = array_filter($datas, function ($r) {
            return $r['fid'] == 0;
        });
        foreach ($parents as $k => $p) {
            $children = array_filter($datas, function ($r) use ($p) {
                return $r['fid'] == $p['id'];
            });
            $parents[$k]['children'] = array_values($children);
        }
        return array_values($parents);
    }

    public function dealCompanyData($data){
        // 1:民营、2:国企、3:外企、4:合资、5:股份制企业、6:事业单位、7:个体、8:其他
        // 1:<50人、2:50~100人、3:101-200人、4:201~500人、5:500人~1000人以上
        $nature = [
            '1' => "民营",
            '2' => "国企",
            '3' => "外企",
            '4' => "合资",
            '5' => "股份制企业",
            '6' => "事业单位",
            '7' => "个体",
            '8' => "其他",
        ];
        $people_scale = [
            '1' => '<50人',
            '2' => '50~100人',
            '3' => '101-200人',
            '4' => '201~500人',
            '5' => '500人~1000人以上',
        ];
        //1:未融资、2:天使轮、3:A轮、4:B轮、5:C轮、6:D轮及以上、7:已上市、8:不需要融资
        $financing_status = [
            '1' => "未融资",
            '2' => "天使轮",
            '3' => "A轮",
            '4' => "B轮",
            '5' => "C轮",
            '6' => "D轮及以上",
            '7' => "已上市",
            '8' => "不需要融资",
        ];
        $industry_ids = array_unique(array_merge(array_column($data, 'industry_id1'), array_column($data, 'industry_id2')));
        $NewRecruitIndustry = new NewRecruitIndustry();
        $industryData = $NewRecruitIndustry->getSome([['id', 'in', $industry_ids]], 'id, name');
        $industryMap = [];
        if($industryData){
            $industryMap = array_column($industryData->toArray(), 'name', 'id');
        }
        $NewRecruitWelfare = new NewRecruitWelfare();
        foreach ($data as $key => &$value) {
            $mer_find = (new Merchant())->where(['mer_id'=>$value['mer_id']])->field('logo')->find();
            if(empty($mer_find)){
                return api_output_error(1003, '没有找到该公司!');
            }
            $img = $mer_find['logo'] ? explode(';', $mer_find['logo']) : [];
            if(!empty($img)){
                $value['picture'] = replace_file_domain($img[0]);
            }
            $value['album'] = [];
            foreach ($img as $v) {
                $value['album'][] = replace_file_domain($v);
            }
            $value['welfare_arr'] = [];
            if($value['welfare']){
                $welfare = $NewRecruitWelfare->getSome([['id', 'in', explode(',', $value['welfare'])], ['status', '=', 0]], 'id,name');
                $value['welfare_arr'] = $welfare ? array_column($welfare->toArray(), 'name') : [];
            }
            $value['jobs'] =(new NewRecruitJob())->getCount(['mer_id'=>$value['mer_id'],'is_del'=>0,'status'=>1,'add_type'=> 0]);
            $value['nature_txt'] = $nature[$value['nature']] ?? "未知";
            $value['people_scale_txt'] = $people_scale[$value['people_scale']] ?? "未知";
            $value['industry_id1_txt'] = $industryMap[$value['industry_id1']] ?? "未知";
            $value['industry_id2_txt'] = $industryMap[$value['industry_id2']] ?? "未知";
            $value['financing_status_txt'] = $financing_status[$value['financing_status']] ?? "未知";

        }
        return $data;
    }

    public function dealCompanyDatas($data){
        // 1:民营、2:国企、3:外企、4:合资、5:股份制企业、6:事业单位、7:个体、8:其他
        // 1:<50人、2:50~100人、3:101-200人、4:201~500人、5:500人~1000人以上
        $nature = [
            '1' => "民营",
            '2' => "国企",
            '3' => "外企",
            '4' => "合资",
            '5' => "股份制企业",
            '6' => "事业单位",
            '7' => "个体",
            '8' => "其他",
        ];
        $people_scale = [
            '1' => '<50人',
            '2' => '50~100人',
            '3' => '101-200人',
            '4' => '201~500人',
            '5' => '500人~1000人以上',
        ];
        //1:未融资、2:天使轮、3:A轮、4:B轮、5:C轮、6:D轮及以上、7:已上市、8:不需要融资
        $financing_status = [
            '1' => "未融资",
            '2' => "天使轮",
            '3' => "A轮",
            '4' => "B轮",
            '5' => "C轮",
            '6' => "D轮及以上",
            '7' => "已上市",
            '8' => "不需要融资",
        ];
        $industry_ids = array_unique(array_merge(array_column($data, 'industry_id1'), array_column($data, 'industry_id2')));
        $NewRecruitIndustry = new NewRecruitIndustry();
        $industryData = $NewRecruitIndustry->getSome([['id', 'in', $industry_ids]], 'id, name');
        $industryMap = [];
        if($industryData){
            $industryMap = array_column($industryData->toArray(), 'name', 'id');
        }
        $NewRecruitWelfare = new NewRecruitWelfare();
        foreach ($data as $key => &$value) {
            $img = $value['images'] ? explode(';', $value['images']) : [];
            if(!empty($img)){
                $value['picture'] = replace_file_domain($img[0]);
            }
            $value['album'] = [];
            foreach ($img as $v) {
                $value['album'][] = replace_file_domain($v);
            }
            $value['welfare_arr'] = [];
            if($value['welfare']){
                $welfare = $NewRecruitWelfare->getSome([['id', 'in', explode(',', $value['welfare'])], ['status', '=', 0]], 'id,name');
                $value['welfare_arr'] = $welfare ? array_column($welfare->toArray(), 'name') : [];
            }
            $value['nature_txt'] = $nature[$value['nature']] ?? "未知";
            $value['people_scale_txt'] = $people_scale[$value['people_scale']] ?? "未知";
            $value['industry_id1_txt'] = $industryMap[$value['industry_id1']] ?? "未知";
            $value['industry_id2_txt'] = $industryMap[$value['industry_id2']] ?? "未知";
            $value['financing_status_txt'] = $financing_status[$value['financing_status']] ?? "未知";

        }
        return $data;
    }

    /**
     * 获取热门企业
     * @param  integer $page     [description]
     * @param  integer $pageSize [description]
     * @return [type]            [description]
     */
    public function getHostCompany($page = 1, $pageSize = 10){
        $where = [
            ['a.jobs', '>', 0],
            ['s.recruit_status', '=', 1],
        ];

        $order = [
            'a.views' => 'desc',
            'a.collects' => 'desc',
            'a.jobs' => 'desc',
        ];
        $data = $this->companyMod->getCompanyByMer($where, 'a.*', $order, ($page-1)*$pageSize, $pageSize);
        if(empty($data)) return [];
        $count = $this->companyMod->getCompanyByMerCount($where);

        return ['count'=>$count, 'data' => $this->dealCompanyData($data->toArray())];
    }

    /**
     * 公司列表
     * @param  integer $page     [description]
     * @param  integer $pageSize [description]
     * @return [type]            [description]
     */
    public function companyList($page = 1, $pageSize = 10){
        $where = [];

        $order = [
            'views' => 'desc',
            'collects' => 'desc',
            'jobs' => 'desc',
        ];
        $data = $this->companyMod->getSome($where, true, $order, ($page-1)*$pageSize, $pageSize);
        if(empty($data)) return [];
        $count = $this->companyMod->getCount($where);

        return ['count'=>$count, 'data' => $this->dealCompanyData($data->toArray())];
    }

    /**
     * 获取公司详细信息
     * @return [type] [description]
     */
    public function getCompanyInfo($mer_id){
        $where = [
            ['c.mer_id', '=', $mer_id]
        ];
        $field = "c.*,m.isverify, m.logo, m.province_id, m.city_id,m.area_id, m.address";
        $detail = $this->companyMod->getJoinMerchantDetail($where, $field);
        if($detail){
            return $detail->toArray();
        }
        return [];
    }

    /**
     * 增加浏览量
     * @return [type] [description]
     */
    public function addViews($mer_id){
        $where = [
            ['mer_id', '=', $mer_id]
        ];
        $this->companyMod->addViews($where);
    }

    //收藏/取消操作
    public function doCollect($uid, $mer_id){
        if(empty($uid) || empty($mer_id)) return false;
        $NewRecruitCompanyUserCollect = new NewRecruitCompanyUserCollect();
        $where = [
            ['uid', '=', $uid],
            ['mer_id', '=', $mer_id],
        ];
        $old = $NewRecruitCompanyUserCollect->getOne($where);
        if($old){//已经存在即取消操作
            $old = $old->toArray();
            $r = $NewRecruitCompanyUserCollect->deleteCollect($old['id']);
            $this->companyMod->decCollects($mer_id);
        }  
        else{
            $r = $NewRecruitCompanyUserCollect->add(['uid'=>$uid, 'mer_id'=>$mer_id, 'create_time'=>date("Y-m-d H:i:s")]);
            $this->companyMod->incCollects($mer_id);
        } 
        return $r;
    }

    public function getUserCollect($uid, $mer_id){
        if(empty($uid) || empty($mer_id)) return false;
        $NewRecruitCompanyUserCollect = new NewRecruitCompanyUserCollect();
        $where = [
            ['uid', '=', $uid],
            ['mer_id', '=', $mer_id],
        ];
        $old = $NewRecruitCompanyUserCollect->getOne($where);
        if($old) return true;
        return false;
    }

    //屏蔽/取消操作
    public function doForbidden($uid, $mer_id){
        if(empty($uid) || empty($mer_id)) return false;
        $NewRecruitCompanyUserForbidden = new NewRecruitCompanyUserForbidden();
        $where = [
            ['uid', '=', $uid],
            ['mer_id', '=', $mer_id],
        ];
        $old = $NewRecruitCompanyUserForbidden->getOne($where);
        if($old){//已经存在即取消操作
            $old = $old->toArray();
            $r = $NewRecruitCompanyUserForbidden->deleteForbidden($old['id']);
        }  
        else{
            $r = $NewRecruitCompanyUserForbidden->add(['uid'=>$uid, 'mer_id'=>$mer_id, 'create_time'=>date("Y-m-d H:i:s")]);
        } 
        return $r;
    }

    public function getUserForbidden($uid, $mer_id){
        if(empty($uid) || empty($mer_id)) return false;
        $NewRecruitCompanyUserForbidden = new NewRecruitCompanyUserForbidden();
        $where = [
            ['uid', '=', $uid],
            ['mer_id', '=', $mer_id],
        ];
        $old = $NewRecruitCompanyUserForbidden->getOne($where);
        if($old) return true;
        return false;
    }
    /**
     * 福利标签保存
     */
    public function getRecruitWelfareLabelCreate($merId, $welfareStr)
    {
        $where = ['mer_id'=>$merId];
        $data = ['welfare'=>$welfareStr,'update_time'=>time()];
        $returns = $this->companyMod->getRecruitWelfareLabelCreate($where, $data);
        return $returns;
    }

    /**
     * 获取公司地点
     */
    public function recruitResumeCompanyOne($mer_id)
    {
        $where = ['mer_id'=>$mer_id];
        $returns = $this->companyMod->where($where)->find();
        return $returns;
    }

    /**
     * 在招职位增加
     */
    public function getInfoInc($mer_id)
    {
        $where = ['mer_id'=>$mer_id];
        $returns = $this->companyMod->where($where)->inc('jobs')->update();
        return $returns;
    }

    /**
     * 在招职位减少
     */
    public function getInfoDec($mer_id)
    {
        $where = ['mer_id'=>$mer_id];
        $returns = $this->companyMod->where($where)->dec('jobs')->update();
        return $returns;
    }
}