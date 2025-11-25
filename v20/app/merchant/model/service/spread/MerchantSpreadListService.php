<?php
/**
 * 商家推广佣金service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/09/18 14:22
 */

namespace app\merchant\model\service\spread;
use app\merchant\model\db\MerchantSpreadList;
class MerchantSpreadListService {
    public $merchantSpreadListModel = null;
    public function __construct()
    {
        $this->merchantSpreadListModel = new MerchantSpreadList();
    }

    /**
     * 获取多条记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getSome($where)
    {
        $row = $this->merchantSpreadListModel->getSome($where);
        return $row ? $row->toArray() : [];
    }
    /**
     * 获取记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function getOne($where)
    {
        $row = $this->merchantSpreadListModel->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function add($data)
    {
        $id = $this->merchantSpreadListModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }
    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->merchantSpreadListModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

}