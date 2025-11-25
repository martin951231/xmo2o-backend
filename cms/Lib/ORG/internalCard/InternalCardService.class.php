<?php


namespace internalCard;

import("@.ORG.service.BaseService");

class InternalCardService extends \service\BaseService
{
    /**
     * author:Luo
     * 生成导入批次号
     * @return string
     */
    public static function genImportBatchNo()
    {
        $rand_str = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $batch_no = 'YKTI';
            for ($i = 0; $i < 8; $i++) {
                $_rand = rand(0, strlen($rand_str) - 1);
                $batch_no .= $rand_str[$_rand];
            }
        } while (M('internal_membership_card_import')->where(['batch_no' => $batch_no])->find());

        return $batch_no;
    }

    /**
     * 批量充值批次号
     * @return string
     */
    public static function genRechargeBatchNo()
    {
        $rand_str = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $batch_no = 'YKTR';
            for ($i = 0; $i < 8; $i++) {
                $_rand = rand(0, strlen($rand_str) - 1);
                $batch_no .= $rand_str[$_rand];
            }
        } while (M('internal_membership_card_recharge')->where(['batch_no' => $batch_no])->find());

        return $batch_no;
    }
}