<?php

declare (strict_types=1);

namespace app\appoint\model\db;

use app\common\model\db\AppointCategory;
use app\common\model\db\Merchant;
use think\Model;

class Appoint extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;
	
	protected $pk = 'appoint_id';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'mer_id', 'mer_id');
    }
    
    public function searchAppointNameAttr($query, $value)
    {
        !empty($value) && $query->where('appoint_name','like', '%' . $value . '%'); 
    }
    
    public function searchIdsAttr($query, $value)
    {
        !empty($value) && is_array($value) && $query->where('appoint_id','IN', $value);
    }
    
    public function searchCheckStatusAttr($query, $value)
    {
        $value > -1 && $query->where('check_status', $value); 
    }

    public function searchCatFidAttr($query, $value)
    {
        $value && $query->where('cat_fid', $value);
    }
    
    public function searchMerIdAttr($query, $value)
    {
        is_array($value) && $query->where('mer_id','IN', $value);
    }
    
    public function appointProduct()
    {
        return $this->hasMany(AppointProduct::class,'appoint_id', 'appoint_id');
    }

    public function appointCategory()
    {
        return $this->belongsTo(AppointCategory::class,'cat_id', 'cat_id');
    }
    
    public function searchNotInNewsflashAppointAttr($query, $value)
    {
        if($value){
            $appointIds = AppointNewsflash::where('delete_time',0)->column('appoint_ids');
            $appointIds = array_filter(explode(',', implode(',',$appointIds)));
            $appointIds && $query->where('appoint_id', 'NOT IN', $appointIds);
        }
    }

    public function merchantGoodsList($merId, $pageSize = 15, $params = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        
        $result = $this->alias('s')
            ->join('merchant mt', 'mt.mer_id = s.mer_id')
            ->join('appoint_category ac', 'ac.cat_id = s.cat_id')
            ->field(['s.appoint_id AS goods_id', 's.appoint_name as goods_name', 's.payment_status','s.payment_money', 's.is_appoint_price','s.appoint_price','s.mer_id','s.pic'])
            ->where([
                's.mer_id' => $merId,
                's.check_status' => 1,
                's.appoint_status' => 0,
                'mt.status'=> 1,
                'ac.is_autotrophic'=> 0,
            ])
            ->where('s.appoint_id', 'NOT IN', function ($query)use($prefix) {
                $query->table($prefix.'wechat_live_goods')
                    ->where('goods_type', 'appoint')
                    ->field('goods_id');
            });
            if(!empty($params['goods_name'])){
                $result = $result->where('s.appoint_name', 'like', "%{$params['goods_name']}%");
            }
            
            $result = $result->paginate(min($pageSize, 100))
            ->each(function ($item) {
                $imgs = explode(';', $item['pic']);
                $item['cover_image'] = $imgs ? replace_file_domain($imgs[0]) : '';
                $item['store_name'] = '--';
                if ($item['payment_status'] == 1) {
                    $item['price'] = '定金（' . $item['payment_money'] . '元）';
                }else{
                    $item['price'] = $item['is_appoint_price'] == 0?'面议':$item['appoint_price'];
                }
            });
        return $result;
    }

}