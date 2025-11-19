<?php
declare (strict_types = 1);

namespace app\wechatLive\model\db;


use app\common\model\db\Merchant;
use app\common\model\db\MerchantStore;
use think\Model;
use think\model\concern\SoftDelete;

class WechatLiveGoods extends Model
{
    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';
    
    const GOODS_UP_SHELVES = 1;//上架
    const GOODS_DOWN_SHELVES = 2;//下架
    
    public function merchantStore()
    {
        return $this->belongsTo(MerchantStore::class,'store_id','store_id');
    } 
    
    public function merchant()
    {
        return $this->belongsTo(Merchant::class,'mer_id','mer_id');
    }

    public function searchMerIdAttr($query, $value)
    {
        $value && $query->where('mer_id', $value);
    }
    
    public function searchGoodsNameAttr($query, $value)
    {
        $value && $query->where('goods_name', 'like',"%$value%");
    }
    
    public function searchCheckStatusAttr($query, $value)
    {
        $value > -1 && $query->where('check_status', $value);
    } 
    
    public function searchGoodsTypeAttr($query, $value)
    {
        $value && $query->where('goods_type', $value);
    }

    public function searchKeyWordsAttr($query, $value, $data)
    {
        $type = $value ? $data['search_type'] : 0;
        if($type == 1){
            $value && $query->where('goods_name', 'like', "%{$value}%");
        }else if($type == 2){
            $value && $query->where('mer_id', 'IN', function ($query)use($value) {
                $query->table(config('database.connections.mysql.prefix').'merchant')
                    ->where('name','like', "%{$value}%")
                    ->field('mer_id');
            });
        }
    }
        
    
}