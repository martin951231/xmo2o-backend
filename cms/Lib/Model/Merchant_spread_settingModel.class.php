<?php

/**
 * 推广商设置
 */
class  Merchant_spread_settingModel extends Model
{
    /**
     * 表单填写项类型
     * @author: 张涛
     * @date: 2021/01/13
     */
    public function cueFieldsType()
    {
        $types = [
            '0' => L_('单行文本'),
            '1' => L_('多行文本'),
           // '2' => L_('地图'),
            '3' => L_('下拉选择框'),
           // '4' => L_('数字格式'),
            '5' => L_('身份证正反面'),
            '6' => L_('身份证号'),
            '7' => L_('日期格式'),
            //'8' => L_('时间格式'),
            '9' => L_('上传图片')
        ];
        return $types;

    }

}