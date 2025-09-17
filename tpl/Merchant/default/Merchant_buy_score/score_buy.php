<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <if condition="$config['site_favicon']">
        <link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
    </if>
    <title>{pigcms{$config.site_name} - {pigcms{:L_("商家中心")}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
    <script src="{pigcms{$static_public}js/layer/layer.js"></script>
</head>

<body class="no-skin" style="background-color: #FFFFFF;">
<div style="text-align: center;">
    <form enctype="multipart/form-data" class="form-horizontal" id="score_buy" method="get" action="{pigcms{:U(Merchant_buy_score/score_buy)}">
        <input type="hidden" name="c" value="Merchant_buy_score"/>
        <input type="hidden" name="a" value="score_buy"/>
        <div style="text-align: left; padding-left: 35%; margin-top: 50px; font-size: 16px;">
            <div style="margin-top: 150px;">
                <div>{pigcms{:L_("平台积分价格：")}</div>
                <input type="text" name="merchant_buy_points" value="{pigcms{$config.merchant_buy_points}" readonly="readonly" >&nbsp;{pigcms{:L_("积分")}&nbsp;( 1{pigcms{$config['Currency_txt']}{pigcms{:L_("购买积分数")} )
            </div>
            <div style="margin-top: 15px;">
                <div>{pigcms{:L_("购买金额：")}</div>
                <input type="text" name="payment_money" id="payment_money" onkeyup="value=value.replace(/[^\d]/g,'')" value="">&nbsp;&nbsp;{pigcms{$config['Currency_txt']}
            </div>
        </div>
        <div style="margin-top: 15px;">
            <button class="btn btn-info" type="submit"> <i class="ace-icon fa fa-check bigger-110"></i> {pigcms{:L_("购买")} </button>
        </div>
    </form>
</div>
<script>

    $("#score_buy").submit(function(){

        var payment_money = $("#payment_money").val();
        if(parseInt(payment_money) < 0){
            layer.msg('{pigcms{:L_("购买金额必须大于")}0');
            return false;
        }
    })

</script>
</body>
</html>