<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use app\common\model\db\AppointOrder;
use app\common\model\db\MerchantStore;
use app\common\model\db\User;
use think\Model;

class AppointSupply extends Model
{
    const WAIT_RECEIVE = 1;//待接单
    const RECEIVE = 2;//已接单
    const COMPLETE = 3;//已完成
    const CANCEL = 4;//已取消
    const REFUND = 5;//已退款
    protected $pk = 'supply_id';

    public function appointOrder()
    {
        return $this->belongsTo(AppointOrder::class, 'order_id', 'order_id');
    }

    public function appoint()
    {
        return $this->belongsTo(Appoint::class, 'appoint_id', 'appoint_id');
    }

    public function worker()
    {
        return $this->belongsTo(MerchantWorkers::class, 'worker_id', 'merchant_worker_id');
    }

    public function merchantStore()
    {
        return $this->belongsTo(MerchantStore::class, 'store_id', 'store_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
    
    public function getOrderTimeAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        return date('Y-m-d H:i:s', $value);
    }

	public function setPushPositionInfoAttr($value)
	{
		if (empty($value)) {
			return '';
		}
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	public function getPushPositionInfoAttr($value)
	{
		if (empty($value)) {
			return '';
		}
		return json_decode($value, true);
	}

	public function setServiceRecordsAttr($value)
	{
		if (empty($value)) {
			return '';
		}
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	public function getServiceRecordsAttr($value)
	{
		if (empty($value)) {
			return '';
		}
		return json_decode($value, true);
	}

    public function searchStatusAttr($query, $value)
    {
        if($value) {
            if(is_array($value)) {
                $query->where('status','IN', $value);
            }else{
                $query->where('status', $value);
            }
        }
    }

    public function searchWorkerIdAttr($query, $value)
    {
        $value && $query->where('worker_id', $value);
    }
    
    public function searchSupplyIdAttr($query, $value)
    {
        $value && $query->where('supply_id', $value);
    }

    public function searchOrderIdAttr($query, $value)
    {
        $value && $query->where('order_id', $value);
    }

    public function searchUidAttr($query, $value)
    {
        $value && $query->where(['uid' => $value]);
    }
    
    public function searchTimeStartAttr($query, $value)
    {
        $value && $query->where('start_time','>', strtotime($value));
    }
    
    public function searchTimeEndAttr($query, $value)
    {
        $value && $query->where('start_time','<', strtotime(date('Y-m-d', strtotime($value)).'  23:59:59'));
    }

    public function searchStartTimeAttr($query, $value)
    {
        $value && $query->where('end_time','>', strtotime($value));
    }
    
    public function searchEndTimeAttr($query, $value)
    {
        $value && $query->where('end_time','<', strtotime(date('Y-m-d', strtotime($value)).'  23:59:59'));
    }

    public function getStatusTextAttr($value, $data): string
    {
        $text = [
            self::WAIT_RECEIVE => '待接单',
            self::RECEIVE => '未服务',
            self::COMPLETE => '已服务',
            self::CANCEL => '已取消',
            self::REFUND => '已退款',
        ];
        if(isset($data['status'])){
            return $text[$data['status']];
        }
        return '';
    }
}