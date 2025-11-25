<?php

namespace app\recruit\model\db;

use think\Model;

/**
 * 公司信息
 * @package app\recruit\model\db
 */
class NewRecruitCompany extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;


    public function getJoinMerchantDetail($where, $field){
    	$result = $this->alias('c')
                    ->leftJoin('merchant m', 'm.mer_id=c.mer_id')
                    ->field($field)
                    ->where($where)
                    ->find();
        return $result;
    }

    public function addViews($where){
    	return $this->where($where)->inc('views')->update();
    }

    public function decCollects($mer_id){
    	return $this->where('mer_id', $mer_id)->dec('collects')->update();
    }

    public function incCollects($mer_id){
    	return $this->where('mer_id', $mer_id)->inc('collects')->update();
    }

    /**
     * 福利标签保存
     */
    public function getRecruitWelfareLabelCreate($where, $data)
    {
        return $this->where($where)->update($data);
    }

    public static function getPeopleScale($val)
    {
        $arr = [
            1 => '<50人',
            2 => '50~100人',
            3 => '101-200人',
            4 => '201~500人',
            5 => '500人~1000人以上'
        ];
        return $arr[$val] ?? '';
    }

    public static function getFinancingStatus($val)
    {
        $arr = [
            1 => '未融资',
            2 => '天使轮',
            3 => 'A轮',
            4 => 'B轮',
            5 => 'C轮',
            6 => 'D轮及以上',
            7 => '已上市',
            8 => '需要融资',
        ];
        return $arr[$val] ?? '';
    }

    public static function getNature($val)
    {
        $arr = [
            1 => '民营',
            2 => '国企',
            3 => '外企',
            4 => '合资',
            5 => '股份制企业',
            6 => '事业单位',
            7 => '个体',
            8 => '其他',
        ];
        return $arr[$val] ?? '';

    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return mixed
     * 查询商家
     */
    public function getCompanyByMer($where,$field=true,$order=true,$page=0,$limit=0){
        $prefix = config('database.connections.mysql.prefix');
        if($limit){
            $return = $this->alias('a')
                ->join($prefix . 'merchant' . ' s', 's.mer_id = a.mer_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($page,$limit)
                ->select();
        }else{
            $return = $this->alias('a')
                ->join($prefix . 'merchant' . ' s', 's.mer_id = a.mer_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->select();
        }
        return $return;
    }

    public function getCompanyByMer1($where,$field=true,$order=true,$page=0,$limit=0){
        $prefix = config('database.connections.mysql.prefix');
            $return = $this->alias('a')
                ->join($prefix . 'merchant' . ' s', 's.mer_id = a.mer_id')
                ->field($field)
                ->where($where)
                ->order($order)
                ->select()
                ->toArray();
            foreach ($return as $k=>$value){
                $count=(new NewRecruitJob())->getCount(['mer_id'=>$value['mer_id'],['status'=>1,'is_del=>0']]);
                if(!$count){
                    unset($return[$k]);
                }
            }
        $return=array_values($return);
        return $return;
    }
    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $limit
     * @return mixed
     * 查询商家数量
     */
    public function getCompanyByMerCount($where){
        $prefix = config('database.connections.mysql.prefix');
        $return = $this->alias('a')
            ->join($prefix . 'merchant' . ' s', 's.mer_id = a.mer_id')
            ->where($where)
            ->count();
        return $return;
    }
}