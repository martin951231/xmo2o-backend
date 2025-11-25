<?php
/**
 * 系统后台餐饮店铺model
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/26 15:11
 */

namespace app\foodshop\model\db;
use think\Model;
class MerchantStoreFoodshop extends Model {

    protected $autoWriteTimestamp = false;

    use \app\common\model\db\db_trait\CommonFunc;
    
    /**
     * 根据店铺ID获取店铺
     * @return array|bool|Model|null
     */
    public function getStoreByStoreId($storeId) {
        if(!$storeId){
            return null;
        }

        $where = [
            'store_id' => $storeId
        ];

        $this->name = _view($this->name);
        $result = $this->where($where)->find();
        return $result;
    }


    public function setInc_shop_reply($store_id, $score=null, $dscore=null){
        $store_shop =$this->getStoreByStoreId($store_id);
        if ($store_shop) {
            $data=array();
            if(is_numeric($score)){
                $data['reply_count']=$store_shop['reply_count']+1;
                $data['score_all']=$store_shop['score_all']+$score;
                $data['score_mean'] = $data['score_all'] / $data['reply_count'];

            }
            if(is_numeric($dscore)){
                $data['reply_deliver_count']=$store_shop['reply_deliver_count']+1;
                $data['reply_deliver_score'] = ($store_shop['reply_deliver_count'] * $store_shop['reply_deliver_score'] + $dscore) / $data['reply_deliver_count'];
            }

            if ($this->where(array('store_id' => $store_id))->update($data)) {
                return true;
            } else {
                return false;
            }
        } else {
            $data=array();
            if(is_numeric($score)){
                $data['reply_count']=1;
                $data['score_all']=$score;
                $data['score_mean'] = $data['score_all'] / $data['reply_count'];
            }
            if(is_numeric($dscore)){
                $data['reply_deliver_count']=1;
                $data['reply_deliver_score']=$dscore;
            }
            $data['store_id'] = $store_id;
            if ($this->save($data)) {
                return true;
            } else {
                return false;
            }
        }
    }
}