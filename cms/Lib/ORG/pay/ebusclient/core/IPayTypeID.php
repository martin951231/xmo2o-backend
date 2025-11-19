<?php
interface IPayTypeID
{
				/// <summary>交易种类:公众号支付
        /// </summary>
        const PAY_TYPE_JSAPI = "JSAPI";
    
        /// <summary>交易种类:原生扫码支付
        /// </summary>
        const PAY_TYPE_NATIVE = "NATIVE";
    
        /// <summary>交易种类:app支付
        /// </summary>
        const PAY_TYPE_APP = "APP";
				/// <summary>交易种类:直接支付
        /// </summary>
        const PAY_TYPE_DIRECTPAY = "ImmediatePay";

        /// <summary>交易种类:预授权支付
        /// </summary>
        const PAY_TYPE_PREAUTH = "PreAuthPay";

        /// <summary>交易种类:分期支付
        /// </summary>
        const PAY_TYPE_INSTALLMENTPAY = "DividedPay";
        /// <summary>交易种类:授权支付
        /// </summary>
        const PAY_TYPE_AGENTPAY = "AgentPay";
        /// <summary>交易种类:退款
        /// </summary>
        const PAY_TYPE_REFUND = "Refund";
        /// <summary>交易种类:付款
        /// </summary>
        const PAY_TYPE_DEFRAYPAY = "DefrayPay";
        /// <summary>交易种类：预授权确认
        /// </summary>
        const PAY_TYPE_PREAUTHED = "PreAuthed";
        /// <summary>交易种类：预授权取消
        /// </summary>
        const PAY_TYPE_PREAUTHCANCEL = "PreAuthCancel";
        
        /// <summary>交易种类:PC支付
        /// </summary>
        const PAY_TYPE_ALI_PAGE = "ALI_PC";

        /// <summary>交易种类:WAP支付
        /// </summary>
        const PAY_TYPE_ALI_WAP = "ALI_WAP";
        
        /// <summary>交易种类:APP支付
        /// </summary>
        const PAY_TYPE_ALI_APP = "ALI_APP";
        
        /// <summary>交易种类:预下单
        /// </summary>
        const PAY_TYPE_ALI_PRECREATE = "ALI_PRECREATE";
        
        /// <summary>交易种类:线下支付
        /// </summary>
        const PAY_TYPE_ALI_CREATE = "ALI_CREATE";
        /// <summary>
        /// 交易种类：线下刷卡支付
        /// </summary>
        const PAY_TYPE_ALI_PAY = "ALI_PAY";
        
        /// <summary>交易种类:微信刷卡支付 20170704 wangyutong
        /// </summary>
        const PAY_TYPE_MICROPAY = "MICROPAY";    
        
        /// <summary>
        /// 交易种类：微信H5支付
        /// </summary>
        const PAY_TYPE_MWEB = "MWEB";
        
}

?>