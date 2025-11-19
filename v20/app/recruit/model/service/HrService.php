<?php

namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitIndustry;
use think\Exception;

/**
 * HRservice
 * @package app\recruit\model\service
 */
class HrService
{
    public $hrMod = null;

    public $companyMod = null;

    public function __construct()
    {
        $this->hrMod = new NewRecruitHr();
        $this->companyMod = new NewRecruitCompany();
    }

    /**
     * 根据手机号获取HR信息
     * @param $phone
     * @date: 2021/06/25
     */
    public function getInfoByPhone($phone)
    {
        if (empty($phone)) {
            return [];
        }
        $hr = $this->hrMod->getOne([['phone', '=', $phone], ['status', '=', 0]], 'id,mer_id,first_name,last_name,sex,phone,email,tel,wechat,qq,show_set');
        return $hr ? $hr->toArray() : [];
    }


    /**
     * 根据uid获取HR信息
     * @param $phone
     * @date: 2021/06/25
     */
    public function getInfoByUid($uid)
    {
        if (empty($uid)) {
            return [];
        }
        $hr = $this->hrMod->where([['uid', '=', $uid], ['status', '=', 0]])->find();
        return $hr ? $hr->toArray() : [];
    }

    /**
     * 获取公司信息
     * @param $merId
     * @date: 2021/06/25
     */
    public function getCompany($merId)
    {
        $company = $this->companyMod
            ->alias('c')
            ->rightJoin('merchant m', 'm.mer_id=c.mer_id')
            ->field('m.name AS merchant_name,m.logo,m.address,c.*')
            ->where('m.mer_id', '=', $merId)
            ->findOrEmpty()
            ->toArray();

        $industryMod = new NewRecruitIndustry();
        if ($company) {
            $company['logo'] = $company['logo'] ? replace_file_domain($company['logo']) : '';
            $company['people_scale'] = NewRecruitCompany::getPeopleScale($company['people_scale']);
            $company['financing_status'] = NewRecruitCompany::getFinancingStatus($company['financing_status']);
            $company['nature'] = NewRecruitCompany::getNature($company['nature']);
            $images = explode(';', $company['images']);
            $company['images'] = array_map(function ($r) {
                return replace_file_domain($r);
            }, $images);

            //获取行业
            $industryNames = $industryMod->whereIn('id', [$company['industry_id1'], $company['industry_id2']])->order('fid', 'asc')->column('name');
            $company['industry'] = $industryNames ? implode('/', $industryNames) : '';
        }
        return $company;
    }

    /**
     * 设置对外公开
     * @param $phone
     * @param $values
     * @date: 2021/06/25
     */
    public function saveShowSet($phone, array $values)
    {
        $showSet = 0;
        foreach ($values as $v) {
            $showSet |= $v;
        }
        $this->hrMod->where('phone', '=', $phone)->update(['show_set' => $showSet]);
        return true;
    }
}