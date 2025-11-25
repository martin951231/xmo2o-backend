<?php
/**
 * Created by PhpStorm.
 * Author: lihongshun
 * Date Time: 2022/4/15 9:20
 */

namespace app\community\model\service;
use app\community\model\db\HousePropertyDigit;
use app\community\model\db\HouseNewChargeRule;
use think\facade\Db;

class HousePropertyDigitService
{

    /**
     * 获取信息
     * 物业小数处理信息
     */
    public function get_one_digit($where,$field=true)
    {
        // 初始化 数据层
        $housePropertyDb = new HousePropertyDigit();
        $info = $housePropertyDb->getOne($where,$field);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        }else{
            $info = array();
        }
        return $info;
    }

	public function updateDigit( $data)
	{
		return (new HousePropertyDigit())::update($data);
	}

	public function addDigit($data)
	{
		return (new HousePropertyDigit())->save($data);
	}

    /**
     * 获取信息
     * 规则小数
     */
    public function get_onerule_digit($where,$field=true)
    {
        // 初始化 数据层
        $housePropertyDb = new HouseNewChargeRule();
        $info = $housePropertyDb->getOne($where,$field);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        }else{
            $info = array();
        }
        return $info;
    }
}
