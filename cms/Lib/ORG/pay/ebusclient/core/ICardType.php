<?php
interface ICardType
{
    /// <summary>卡类型：1:农行借记卡准贷记卡
    /// </summary>
    const PAY_TYPE_ABC = "1";
    
    /// <summary> 卡类型：3:农行贷记卡 
    /// </summary>
    const PAY_TYPE_CREDIT = "3";
    /// <summary> 卡类型：A:农行卡合并 
    /// </summary> 
    const PAY_TYPE_ALL ="A";
}

?>