<?php
/**
 * 店员客服service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/12/09 13:26
 */

namespace app\merchant\model\service\store;
use app\merchant\model\db\MerchantStoreKefu;
class MerchantStoreKefuService {
    public $merchantStoreKefuModel = null;
    public function __construct()
    {
        $this->merchantStoreKefuModel = new MerchantStoreKefu();
    }

    /**
     * 获取店员客服跳转
     * @throws Exception
     */
    public function getImUrl($staff)
    {
        $return = [
            'url' => ''
        ];
        if (empty($staff)) {
            return '';
        }

        $staffid = $staff['id'] ?? 0;
        if ($staffid <= 0) {
            return $return;
        }

        if (empty($staff['openid'])) {
            return $return;
        }

        // 查询店员绑定的客服
        $where = [
            'kf.store_id' => $staff['store_id'],
            'u.openid' => $staff['openid']
        ];
        $field = 'kf.store_id,kf.username';
        $kf = $this->getKefuByStaff($where,$field);
        if (empty($kf)) {
            return $return;
        }

        $im = $this->buildStoreImConversationUrl($kf['username'], 'store2user');
        $return['url'] = $im;
        return $return;
    }

    /**
     * 生成会话列表客服页面地址
     * @param $fromUser
     * @param array $params
     */
    function buildStoreImConversationUrl($fromUser, $relation, $params = [])
    {
        if (empty($fromUser) || empty($relation)) {
            return '';
        }
        $url = build_store_im_conversation_url($fromUser, $relation, $params);
        return $url;
    }

    /**
     * 根据条件返回绑定的客服
     * @param $where array 条件
     * @return array
     */
    public function getKefuByStaff($where,$field=true){
        $detail = $this->merchantStoreKefuModel->getKefuByStaff($where,$field);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件获取其数量
     * @param $where array $where
     * @return array
     */
    public function getCount($where) {
        if(empty($where)){
            return false;
        }

        $count = $this->merchantStoreKefuModel->getCount($where);
        if(!$count) {
            return 0;
        }

        return $count;
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getOne($where){
        $detail = $this->merchantStoreKefuModel->getOne($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 根据条件返回多条数据
     * @param $where array 条件
     * @return array
     */
    public function getSome($where){
        $detail = $this->merchantStoreKefuModel->getSome($where);
        if(!$detail) {
            return [];
        }
        return $detail->toArray();
    }

    /**
     * 添加数据
     * @param $where array 条件
     * @return array
     */
    public function add($data) {
        if( empty($data)){
            return false;
        }

        $result = $this->merchantStoreKefuModel->save($data);
        if(!$result) {
            return false;
        }

        return $this->merchantStoreKefuModel->id;
    }

}