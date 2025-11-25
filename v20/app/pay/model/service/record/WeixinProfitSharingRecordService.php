<?php
/**
 * 微信分账记录service
 * Author: hengtingmei
 * Date Time: 2021/09/25 14:48
 */

namespace app\pay\model\service\record;
use app\pay\model\db\WeixinProfitSharingRecord;
class WeixinProfitSharingRecordService {
    public $weixinProfitSharingRecordModel = null;
    public function __construct()
    {
        $this->weixinProfitSharingRecordModel = new WeixinProfitSharingRecord();
    }

    /**
     * 获取多条记录
     * @author: 衡婷妹
     * Date Time: 2021/09/25
     */
    public function getSome($where)
    {
        $row = $this->weixinProfitSharingRecordModel->getSome($where);
        return $row ? $row->toArray() : [];
    }
    /**
     * 获取记录
     * @author: 衡婷妹
     * Date Time: 2021/09/25
     */
    public function getOne($where)
    {
        $row = $this->weixinProfitSharingRecordModel->where($where)->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 添加多条记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function addAll($data)
    {
        $id = $this->weixinProfitSharingRecordModel->addAll($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 添加记录
     * @author: 衡婷妹
     * @date: 2020/09/18
     */
    public function add($data)
    {
        $id = $this->weixinProfitSharingRecordModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }


    public function setCountInc($where)
    {
        $id = $this->weixinProfitSharingRecordModel->where($where)->inc('count',1)->update();
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return string|bool
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->weixinProfitSharingRecordModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

}