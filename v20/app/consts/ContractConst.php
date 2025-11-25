<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      合同常量值
 */

namespace app\consts;

class ContractConst
{
    /**
     * @var string 合同待生效
     */
    const CONTRACT_NOT_EFFECTIVE = 'todo';

    /**
     * @var string 合同生效
     */
    const CONTRACT_IN_EFFECTIVE = 'doing';

    /**
     * @var string 合同失效
     */
    const CONTRACT_LOSE_EFFICACY = 'end';

    /**
     * @var string 全部正常合同
     */
    const CONTRACT_ALl = 'all';

    /**
     * @var int 合同附件数:最多单个合同上传附件最多件数
     */
    const CONTRACT_APPENDIX_MAX = 3;
}