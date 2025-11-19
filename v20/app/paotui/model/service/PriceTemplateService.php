<?php

namespace app\paotui\model\service;

use app\paotui\model\db\PaotuiAddedFee;
use app\paotui\model\db\PaotuiPriceTemplate;
use app\paotui\model\db\PaotuiPriceTemplateMerchant;
use Exception;
use think\facade\Db;

/**
 * 跑腿价格模板
 *
 * @author: zt
 * @date: 2025/07/07
 */
class PriceTemplateService
{
	public $priceTemplateMod;

	public function __construct()
	{
		$this->priceTemplateMod = new PaotuiPriceTemplate();
	}

	public function savePriceTemplate($params)
	{
		$rule = [
			'name' => 'require',
			'type' => 'require|in:1,2',
			'meter' => 'require|gt:0',
			'start_meter' => 'require|gt:0',
			'start_price' => 'require|gt:0',
			'exceed_miles_rule' => 'require|array',
			//'max_weight_range_start' => 'require|egt:0',
			//'max_weight_range_end' => 'require|egt:0',
			'weight_range_add_price' => 'require|gt:0',
			'exceed_weight_rule' => 'require|array',
			'bind_business' => 'require|in:1,2,3',
			'special_delivery' => 'require|in:0,1',
			'special_delivery_count' => 'require|egt:0',
		];
		$message = [
			'name' => '请输入模板名称',
			'type' => '请选择计价类型',
			'meter' => '请选择计价类型',
			'start_meter' => '请输入里程规则起送距离',
			'start_price' => '请输入里程规则起步价格',
			'bind_business' => '请选择绑定业务类型',
			'special_delivery' => '请选择是否专送',
			'special_delivery_count' => '专送限制数量填写有误'
		];

		if (validate($rule, $message)->check($params)) {
			if ($params['bind_business'] != 3) {
				$params['special_delivery'] = 0;
				$params['special_delivery_count'] = 0;
			}

			//exceed_miles_rule检查距离不能是0，且只能越来越大
			$lastMeter = 0;
			foreach ($params['exceed_miles_rule'] as $item) {
				if ($item['meter'] == 0) {
					throw new Exception('里程规则阶梯距离不能为0');
				}
				if ($item['meter'] <= $lastMeter) {
					throw new Exception('里程规则阶梯距离不能小于等于上一层阶梯');
				}
				$lastMeter = $item['meter'];
			}

			$data = [
				'name' => $params['name'],
				'type' => $params['type'],
				'meter' => $params['meter'],
				'start_meter' => $params['start_meter'],
				'start_price' => $params['start_price'],
				'exceed_miles_rule' => json_encode($params['exceed_miles_rule'], JSON_UNESCAPED_UNICODE),
				'max_weight_range_start' => $params['max_weight_range_start'],
				'max_weight_range_end' => $params['max_weight_range_end'],
				'weight_range_add_price' => $params['weight_range_add_price'],
				'exceed_weight_rule' => json_encode($params['exceed_weight_rule'], JSON_UNESCAPED_UNICODE),
				'bind_business' => $params['bind_business'],
				'special_delivery' => $params['special_delivery'],
				'special_delivery_count' => $params['special_delivery'] ? $params['special_delivery_count'] : 0,
			];

			$id = $params['id'] ?? 0;
			if ($id > 0) {
				//编辑
				$old = $this->priceTemplateMod->getOne(['id' => $id]);
				if (empty($old)) {
					throw new \think\Exception('价格模板不存在');
				} else {
					$this->priceTemplateMod->updateThis(['id' => $id], $data);
					//当专送模板变更时，需要更新商家的模板
					if ($old->special_delivery != $data['special_delivery']) {
						if ($old->special_delivery == 1) {
							//从专送变成非专送
							Db::name('paotui_price_template_merchant')->where('special_delivery_template_id', $id)->update(['special_delivery_template_id' => 0]);
						} else {
							//非专送变成专送
							Db::name('paotui_price_template_merchant')->where('template_id', $id)->update(['template_id' => 0]);
						}
					}
					return true;
				}
			} else {
				//新增
				$data['status'] = 0;
				return $this->priceTemplateMod->add($data);
			}
		}
		return false;
	}

	//获取价格模板列表
	public function getLists($params)
	{
		$where = [];

		if (isset($params['bind_business']) && $params['bind_business'] > 0) {
			$where[] = ['bind_business', '=', $params['bind_business']];
		}
		if (isset($params['status']) && $params['status'] > -1) {
			$where[] = ['status', '=', $params['status']];
		}

		$fields = '*';
		$lists = $this->priceTemplateMod
			->where($where)
			->field($fields)
			->append(['status_text', 'bind_business_text', 'type_text'])
			->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
			->each(function ($item) {
				$item['exceed_miles_rule'] = json_decode($item['exceed_miles_rule'], true);
				$item['exceed_weight_rule'] = json_decode($item['exceed_weight_rule'], true);
				if ($item['bind_business'] == 3) {
					if ($item['special_delivery']) {
						$item['bind_merchant_count'] = Db::name('paotui_price_template_merchant')->where('special_delivery_template_id', $item['id'])->count();
					} else {
						$item['bind_merchant_count'] = Db::name('paotui_price_template_merchant')->where('template_id', $item['id'])->count();
					}
				} else {
					$item['bind_merchant_count'] = 0;
				}
			})
			->toArray();
		return $lists;
	}

	//商家列表
	public function merchant($params)
	{
		if (empty($params['template_id'])) {
			return [];
		}

		$where = [
			['m.status', '<>', 4]
		];
		if (!empty($params['name'])) {
			$where[] = ['m.name', 'like', '%' . $params['name'] . '%'];
		}
		if (!empty($params['phone'])) {
			$where[] = ['m.phone', '=', $params['phone']];
		}
		if (isset($params['status']) && $params['status'] > -1) {
			$where[] = ['m.status', '=', $params['status']];
		}

		$template = $this->priceTemplateMod->where('id', $params['template_id'])->find();
		if ($template['special_delivery'] == 1) {
			$where[] = ['pm.special_delivery_template_id', '=', $params['template_id']];
		} else {
			$where[] = ['pm.template_id', '=', $params['template_id']];
		}

		$lists = Db::name('paotui_price_template_merchant')->alias('pm')
			->join('merchant m', 'pm.mer_id = m.mer_id')
			->where($where)
			->field('m.mer_id,m.name,m.phone,m.status')
			->order('m.mer_id ASC')
			->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
			->toArray();
		return $lists;
	}

	public function getAllMerchant($params)
	{
		$where = [
			['m.status', '<>', 4]
		];
		if (!empty($params['name'])) {
			$where[] = ['m.name', 'like', '%' . $params['name'] . '%'];
		}
		if (!empty($params['phone'])) {
			$where[] = ['m.phone', '=', $params['phone']];
		}
		if (isset($params['status']) && $params['status'] > -1) {
			$where[] = ['m.status', '=', $params['status']];
		}
		$lists = Db::name('merchant')->alias('m')
			->leftJoin('paotui_price_template_merchant pm', 'pm.mer_id = m.mer_id')
			->where($where)
			->field('m.mer_id,m.name,m.phone,m.status,pm.template_id,pm.special_delivery_template_id')
			->order('pm.template_id DESC,m.mer_id ASC')
			->paginate(['list_rows' => $params['page_size'], 'page' => $params['page']])
			->each(function ($item) {
				$item['template_name'] = $item['template_id'] ? $this->priceTemplateMod->where('id', $item['template_id'])->value('name') : '--';
				$item['special_delivery_template_name'] = $item['special_delivery_template_id'] ? $this->priceTemplateMod->where('id', $item['special_delivery_template_id'])->value('name') : '--';
				return $item;
			})
			->toArray();
		return $lists;
	}

	//获取帮我送（商家）模板
	public function getTemplateSelect()
	{
		$rs = [
			'normal_template' => [],
			'special_delivery_template' => [],
		];

		$rs['normal_template'] = $this->priceTemplateMod
			->where('bind_business', 3)
			->where('special_delivery', 0)
			->field('id,name')
			->select()
			->toArray();
		$rs['special_delivery_template'] = $this->priceTemplateMod
			->where('bind_business', 3)
			->where('special_delivery', 1)
			->field('id,name')
			->select()
			->toArray();
		return $rs;
	}

	//单商家绑定跑腿模板
	public function bindTemplate($merId, $templateId, $specialDeliveryTemplateId)
	{
		if ($merId < 1) {
			throw new \think\Exception('参数错误');
		}

		$old = Db::name('paotui_price_template_merchant')->where('mer_id', $merId)->find();
		$data = [
			'mer_id' => $merId,
			'template_id' => $templateId,
			'special_delivery_template_id' => $specialDeliveryTemplateId
		];
		if ($old) {
			return Db::name('paotui_price_template_merchant')->where('id', $old['id'])->update($data);
		} else {
			return Db::name('paotui_price_template_merchant')->insert($data);
		}
	}

	//批量商家绑定跑腿模板
	public function batchBindTemplate($merIds, $templateId, $specialDeliveryTemplateId)
	{
		if (empty($merIds)) {
			throw new \think\Exception('请选择操作商家');
		}
		Db::transaction(function () use ($merIds, $templateId, $specialDeliveryTemplateId) {
			foreach ($merIds as $merId) {
				$this->bindTemplate($merId, $templateId, $specialDeliveryTemplateId);
			}
		});
		return true;
	}

	//copy from plugin/paotui/Lib/Model/Paotui_price_templateModel.class.php
	public function calculateFee($business, $distance, $weight, $specialDelivery = 0, $merId = 0): array
	{
		//*************匹配计费模板************* */
		//1、根据business找到对应的计费模板
		$templateId = 0;
		if ($business == 3) {
			//商家帮我送需要查询商家绑定的模板
			if (empty($merId)) {
				throw new Exception('跑腿计费模板获取失败');
			}
			$bindMerchant = PaotuiPriceTemplateMerchant::where(['mer_id' => $merId])->find();
			$templateId = $specialDelivery == 0 ? $bindMerchant['template_id'] : $bindMerchant['special_delivery_template_id'];
			if (empty($templateId)) {
				throw new Exception('未配置跑腿计费模板！');
			}
		}

		$tempWhere = [
			'bind_business' => $business,
			'status' => 1,
			'special_delivery' => $specialDelivery,
		];
		$templateId && $tempWhere['id'] = $templateId;
		$template = PaotuiPriceTemplate::where($tempWhere)->where('delete_time', null)->find();
		if (!$template) {
			throw new Exception('未配置跑腿计费模板！');
		}

		//2、计算里程费用
		$meter = $template['meter'];  //距离间隔
		$startMeter = $template['start_meter']; //起步距离
		$startPrice = $template['start_price']; //起步价格
		$exceedMilesRule = json_decode($template['exceed_miles_rule'], true); //超出起步距离的价格规则

		$distanceFee = $startPrice;  //默认里程费用为起步价
		$exceedFee = 0;
		//向上取
		if($distance % $meter){
			$distance = $distance - $distance % $meter + $meter;
		}
		if ($distance > $startMeter) {
			//大于起步距离
			$exceedDistance = $distance - $startMeter;
			foreach ($exceedMilesRule as $key => $item) {
				$exceedMeter = $item['meter'];
				$exceedMoney = $item['money'];
				$nextExceedMeter = $exceedMilesRule[$key + 1]['meter'] ?? PHP_INT_MAX;
				if ($exceedDistance >= $exceedMeter && $exceedDistance < $nextExceedMeter) {
					//向上取整 exceedDistance除以meter
					$exceedFee = round($exceedMoney * ceil($exceedDistance / $meter), 2);
					break;
				}
			}
		}
		$distanceFee += $exceedFee;

		//3、计算重量费用
		$weightFee = 0;
		$exceedWeight = 0;
		$exceedWeightRule = json_decode($template['exceed_weight_rule'], true);
		$addPrice = $template['weight_range_add_price'];

		foreach ($exceedWeightRule as $item) {
			$minWeight = $item['min'];
			$maxWeight = $item['max'];
			$money = $item['money'];

			if ($weight >= $minWeight) {
				$weightFee = $money;
				$exceedWeight = $weight > $maxWeight ? $weight - $maxWeight : 0;
			}
		}
		$exceedWeightFee = $exceedWeight * $addPrice;

		//*************匹配增值费用************* */
		//1、根据business找到对应的增值模板、计算增值费用
		$nowTime = date('H:i:s');
		$where = [
			['bind_business', '=', $business],
			['status', '=', 1],
			['begin_time', '<=', $nowTime],
			['end_time', '>=', $nowTime],
		];
		$addedFeeList = PaotuiAddedFee::where($where)->where('delete_time', null)->select()->toArray();
		$addFeeName = '';
        $addFee = 0;
        if ($addedFeeList) {
            $addFee = array_sum(array_column($addedFeeList, 'money'));
            $addFeeName = implode('、', array_column($addedFeeList, 'name'));
        }

		return [
			'basic_distance_price' => getFormatNumber($startPrice),  //基础配送费
			'extra_distance_price' => $distanceFee > $startPrice ? round($distanceFee - $startPrice, 2) : 0,
			'basic_weight_price' => getFormatNumber($weightFee),  //基础费用
			'extra_weight_price' => getFormatNumber($exceedWeightFee), //额外重量加价费用
			'added_fee_list' => $addedFeeList,  //增值费用列表
            'added_fee_name' => $addFeeName,
			'added_fee' => getFormatNumber($addFee),
			'price_template_id' => $template['id'],
			'exceed_weight' => $exceedWeight,
		];
	}
}
