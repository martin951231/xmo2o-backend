<?php

/**
 * 商家会员寄存-商品
 */
namespace app\merchant\model\db;
use think\Model;

class CardNewDepositGoods extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    protected $append = [
        'image_text'
    ];
    /**
     * 自减
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setDec($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->dec($field, $num)->update();
        return $result;
    }

    /**
     * 自增
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
    }

    public function sort()
    {
        return $this->belongsTo('CardNewDepositGoodsSort', 'sort_id', 'sort_id');
    }


    public function getImageAttr($value)
    {
        // return cfg('site_url') . $value;
        return replace_file_domain($value);
    }

    public function getImageTextAttr($value,$data)
    {
        return $data['image'];
    }

    /**
     * @param string $where
     * @param mixed $field
     * @return mixed
     * 获取字段值
     */
    public function getColumnValue($where,$field)
    {
        $ret=$this->where($where)->value($field);
        return $ret;
    }
}