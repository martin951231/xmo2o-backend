<?php

class Merchant_register_fieldsModel extends Model
{
    public function cueFieldsType()
    {
        $types = [
            '0' => L_('单行文本'),
            '1' => L_('多行文本'),
            '2' => L_('上传图片'),
            '3' => L_('下拉选择框'),
            '4' => L_('身份证正反面'),
            '5' => L_('日期格式'),
            '6' => L_('时间格式'),
            '7' => L_('日期时间格式'),
        ];
        return $types;

    }
}