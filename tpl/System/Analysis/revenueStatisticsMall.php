<!-- <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>layui</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
</head>
<body> -->
<include file="Public:header"/>
<link rel="stylesheet" type="text/css" href="./tpl/Merchant/default/static/css/bootstrap.min.css" />
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <if condition="$config['shop_alias_name']">
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'shop'))}">{pigcms{$config['shop_alias_name']}</a>
            </if>
            <if condition="$config['mall_alias_name']">
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'mall'))}" class="on">{pigcms{$config['mall_alias_name']}</a>
            </if>
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'deliver'))}">配送</a>
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'market'))}">营销</a>
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'dining'))}">餐饮</a>
        </ul>
    </div>
    <div class="layuimini-container">
        <!-- 实时数据 -->
        <div class="layuimini-main">
            <fieldset class="layui-elem-field layui-field-title">
                <div style="margin-bottom: 20px;">
                    <span style="    font-size: 21px;font-weight: 600;margin-right: 70px;">营业统计(今日实时数据)</span>
                    <span style="float: right;"><span class="auto_refresh"></span>&nbsp;后自动刷新</span>
                </div>
                
                <table class="search_table" width="100%">
                    <tr>
                        <td>
                            <form action="" method="get" id="myform1">
                                <input type="hidden" name="c" value="Analysis"/>
                                <input type="hidden" name="a" value="revenueStatistics"/>
                                <input type="hidden" name="business_type" value="mall"/>
                                
                                <if condition="$now_area['area_type'] neq 3">
                                    <span class="fl">地区筛选：</span>
                                    <div class="fl" id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_id="{pigcms{$_GET.street_id}" style="display:inline"></div>
                                </if>
                                <input type="submit" value="查询" class="btn btn-download" style="line-height: 2px;"/>
                            </form>
                        </td>
                    
                    </tr>
                </table>
                <div style="margin: 10px 10px 10px 10px">
                    <div class="layuimini-main layui-top-box">
                        <div class="layui-row layui-col-space10">
                           <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                        <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>营业额 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内流水总额，包含：')}" data-info="{pigcms{:L_('商品原价')}?{pigcms{:L_('打包费')}?{pigcms{:L_('配送费/快递费')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-total_money_0">
                                                        0
                                                    </span>
                                                    <span class="today-total_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-total_money_text">比昨日同时段
                                                    
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>商家收入 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内商家净收入，包含自有支付；扣除：')}" data-info="{pigcms{:L_('平台抽成')}&{pigcms{:L_('平台配送费')}?{pigcms{:L_('平台满减补贴')}?{pigcms{:L_('商家满减补贴')}?{pigcms{:L_('申请售后退款金额')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-mer_income_0">
                                                        0
                                                    </span>
                                                    <span class="today-mer_income_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-mer_income_text">比昨日同时段</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>有效订单 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-effective_count_0">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('单')}
                                                    </span>
                                                </h1>
                                                <small class="today-effective_count_text">比昨日同时段
                                                    
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                             <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>无效订单 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-invalid_count_0">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('单')}
                                                    </span>
                                                </h1>
                                                <small class="today-invalid_count_text">比昨日同时段
                                                   
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台盈利 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中平台佣金总额减去平台补贴总额加上平台配送费，可能会出现负数；平台补贴总额包含：')}" data-info="{pigcms{:L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')}&{pigcms{:L_('平台优惠券平台补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}?{pigcms{:L_('平台会员等级优惠补贴部分')}?{pigcms{:L_('平台送出积分部分')}?{pigcms{:L_('平台送出平台佣金部分')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-plat_income_0">
                                                        00
                                                    </span>
                                                    <span class="today-plat_income_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-plat_income_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台抽成 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('当前时段下所有有效订单中平台抽取商家佣金的总额。')}" ></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-plat_get_commission_0">
                                                        00
                                                    </span>
                                                    <span class="today-plat_get_commission_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-plat_get_commission_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台活动补贴 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单的平台补贴总额，包含：')}" data-info="{pigcms{:L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')}&{pigcms{:L_('平台优惠券平台补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}?{pigcms{:L_('平台会员等级优惠补贴部分')}?{pigcms{:L_('平台送出积分部分')}?{pigcms{:L_('平台送出平台佣金部分')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-plat_total_money_0">
                                                        00
                                                    </span>
                                                    <span class="today-plat_total_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info" style="display: none;">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <label>{pigcms{:L_('平台补贴总额')}：<label class="today-plat_total_money"></label></label><br/>
                                                                <label>{pigcms{:L_('平台满减补贴总额')}：<label class="today-platform_plat"></label></label><br/>
                                                                <label>{pigcms{:L_('平台优惠券补贴总额')}：<label class="today-coupon_price"></label></label><br/>
                                                                <label>{pigcms{:L_('平台积分抵扣补贴总额')}：<label class="today-score_deducte"></label></label><br/>
                                                                <label>{pigcms{:L_('平台会员等级优惠补贴总额')}：<label class="today-vip_level_reduce_money"></label></label><br/>
                                                                <label>{pigcms{:L_('平台送出积分补贴总额')}：<label class="today-send_score"></label>（积分数换算金额约等于<label class="today-scoreSendMoney"></label>）</label><br/>
                                                                <label>{pigcms{:L_('平台送出平台佣金总额')}：<label class="today-send_commission"></label></label>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="today-plat_total_money_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>商家活动补贴 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单的商家补贴总额，包含：')}" data-info="{pigcms{:L_('平台满减优惠商家补贴金额部分，含满减优惠的所有类型')}&{pigcms{:L_('商家自有满减优惠商家补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}?{pigcms{:L_('商家优惠券商家补贴金额部分')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-mer_total_money_0">
                                                        00
                                                    </span>
                                                    <span class="today-mer_total_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info" style="display: none;">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <label>{pigcms{:L_('商家补贴总额')}：<label class="today-mer_total_money"></label></label><br/>
                                                                <label>{pigcms{:L_('商家满减补贴总额')}：<label class="today-merchant_reduce"></label></label><br/>
                                                                <label>{pigcms{:L_('商家优惠券补贴总额')}：<label class="today-card_price"></label></label>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="today-mer_total_money_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台配送费 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中平台配送费的总额。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-plat_freight_charge_0">
                                                        00
                                                    </span>
                                                    <span class="today-plat_freight_charge_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-plat_freight_charge_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>客单价 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内有效订单的平均单价，计算公式为：当前时段所有有效订单的订单收入除以有效单数')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-unit_price_0">
                                                        00
                                                    </span>
                                                    <span class="today-unit_price_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-unit_price_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>下单用户数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内下单用户数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-user_count_0">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('人')}
                                                    </span>
                                                </h1>
                                                <small class="today-user_count_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>  
                </div>      
            </fieldset> 
        </div>
        <!-- 分割线 -->
        <!-- <hr class="layui-bg-gray"> -->
        <!-- 按钮以及下拉 --> 
        <form class="layui-form" action="" style="margin-left: 30px;">
            <div class="layui-form-item">
            <div class="layui-inline">
              <div class="layui-input-inline flex" style="width: 140px">
                <div class="flex data_type" style="margin-left: 10px;">
                    <span 
                        id="SupendButton_1" 
                        class="type-choice active" 
                        data-data_type="0" 
                        onclick="SupendButtonClick1()">
                        营业额
                    </span>
                    <span
                        id="SupendButton_2" 
                        class="type-choice"
                        data-data_type="1" 
                        onclick="SupendButtonClick2()">
                        平台盈利
                    </span>
                </div>
                <div class="layui-input-inline select-type" style="width: 100px;">
                    <select name="is_add_up" lay-verify="required" lay-search="" onchange="statisticsTodayChart()">
                      <option value="1" style="height: 45px">累计</option>
                      <option value="0" style="height: 45px">分时</option>
                    </select>
                </div>
              </div>
            </div>
          </div>
        </form>
       
        <!-- 图表展示-->
        <div style="width:100%;height:300px;">
            <div id="container" style="height: 100%;width: 100%"></div>
        </div>
                    

        <!-- 分割线 -->
        <hr class="layui-bg-gray">
        
        <div class="layuimini-main">
            <fieldset class="layui-elem-field layui-field-title">
                <div class="form-group" style="margin-bottom:0px;height: 40px;margin-left: 30px;margin-top: 10px;">
                    <span style="    font-size: 21px;float: left;font-weight: 600;    margin-right: 70px;">历史统计分析</span>
                    <div class="fl time-choice-wrap cf stat-time-choice" style="float: left;">
                        <span class="time-btn time-choice" timetype="1" id="choice-today">
                        {pigcms{:L_("昨日")}</span>
                        <span class="time-btn time-choice bor-gray time-current" id="choice-week" timetype="2" >
                        {pigcms{:L_("近7日")}</span>
                        <span class="time-btn time-choice" id="choice-month" timetype="3" >
                        {pigcms{:L_("近30日")}</span>
                        <span class="time-btn time-choice" id="choice-custome" timetype="4" >
                        {pigcms{:L_("自定义")}</span>
                        <input type="hidden" id="date_type" value="2">
                    </div>
                    <span class="time_select" style="display: none;">
                        
                        <span class="fl select-item">{pigcms{:L_("日期筛选：")}</span>
                        <input type="text" class="layui-input input-text fl" name="start_time" style="width:120px;" id="start_time"  value="" autocomplete="off" readonly/>            
                        <input type="text" class="layui-input input-text fl" name="end_time" style="width:120px;" id="end_time" value=""  autocomplete="off" readonly />
                        <a class="btn-download time-sure"/>{pigcms{:L_("查询")}</a>　
                    </span>
                    <a href="javascript:void(0)" onclick="exports()" class="btn btn-download" style="margin-right: 10px;float: right;">{pigcms{:L_("下载报表")}</a>
                </div>
                <div style="margin: 10px 10px 10px 10px">
                    <div class="layuimini-main layui-top-box">
                        <div class="layui-row layui-col-space10">
                           <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                            <div class="layui-col-md2">
                                <div class="history-header">有效订单统计</div>
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                        <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>营业额 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内流水总额，包含：')}" data-info="{pigcms{:L_('商品原价')}?{pigcms{:L_('打包费')}?{pigcms{:L_('配送费/快递费')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-total_money_0">
                                                        0
                                                    </span>
                                                    <span class="history-total_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-total_money_text">比昨日同时段
                                                    <!-- 引入image中的上升图标  -->
                                                    <embed src="{pigcms{$static_path}/images/up.svg" class="logo">
                                                        <span style="color: red">
                                                           0.00元
                                                        </span>
                                                    </embed>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>商家收入 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内商家净收入，包含自有支付；扣除：')}" data-info="{pigcms{:L_('平台抽成')}&{pigcms{:L_('平台配送费')}?{pigcms{:L_('平台满减补贴')}?{pigcms{:L_('商家满减补贴')}?{pigcms{:L_('申请售后退款金额')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-mer_income_0">
                                                        0
                                                    </span>
                                                    <span class="history-mer_income_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-mer_income_text">比昨日同时段</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>有效订单 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-effective_count_0">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('单')}
                                                    </span>
                                                </h1>
                                                <small class="history-effective_count_text">比昨日同时段
                                                    
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台活动补贴 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单的平台补贴总额，包含：')}" data-info="{pigcms{:L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')}&{pigcms{:L_('平台优惠券平台补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}?{pigcms{:L_('平台会员等级优惠补贴部分')}?{pigcms{:L_('平台送出积分部分')}?{pigcms{:L_('平台送出平台佣金部分')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-plat_total_money_0">
                                                        00
                                                    </span>
                                                    <span class="history-plat_total_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info" style="display: none;">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <label>{pigcms{:L_('平台补贴总额')}：<label class="history-plat_total_money"></label></label><br/>
                                                                <label>{pigcms{:L_('平台满减补贴总额')}：<label class="history-platform_plat"></label></label><br/>
                                                                <label>{pigcms{:L_('平台优惠券补贴总额')}：<label class="history-coupon_price"></label></label><br/>
                                                                <label>{pigcms{:L_('平台积分抵扣补贴总额')}：<label class="history-score_deducte"></label></label><br/>
                                                                <label>{pigcms{:L_('平台会员等级优惠补贴总额')}：<label class="history-vip_level_reduce_money"></label></label><br/>
                                                                <label>{pigcms{:L_('平台送出积分补贴总额')}：<label class="history-send_score"></label>（积分数换算金额约等于<label class="history-scoreSendMoney"></label>）</label><br/>
                                                                <label>{pigcms{:L_('平台送出平台佣金总额')}：<label class="history-send_commission"></label></label>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="history-plat_total_money_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>商家活动补贴 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单的商家补贴总额，包含：')}" data-info="{pigcms{:L_('平台满减优惠商家补贴金额部分，含满减优惠的所有类型')}&{pigcms{:L_('商家自有满减优惠商家补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}?{pigcms{:L_('商家优惠券商家补贴金额部分')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-mer_total_money_0">
                                                        00
                                                    </span>
                                                    <span class="history-mer_total_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info" style="display: none;">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <label>{pigcms{:L_('商家补贴总额')}：<label class="history-mer_total_money"></label></label><br/>
                                                                <label>{pigcms{:L_('商家满减补贴总额')}：<label class="history-merchant_reduce"></label></label><br/>
                                                                <label>{pigcms{:L_('商家优惠券补贴总额')}：<label class="history-card_price"></label></label>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="history-mer_total_money_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台盈利 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中平台佣金总额减去平台补贴总额加上平台配送费，可能会出现负数；平台补贴总额包含：')}" data-info="{pigcms{:L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')}&{pigcms{:L_('平台优惠券平台补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}?{pigcms{:L_('平台会员等级优惠补贴部分')}?{pigcms{:L_('平台送出积分部分')}?{pigcms{:L_('平台送出平台佣金部分')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-plat_income_0">
                                                        00
                                                    </span>
                                                    <span class="history-plat_income_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-plat_income_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台抽成 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('当前时段下所有有效订单中平台抽取商家佣金的总额。')}" ></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-plat_get_commission_0">
                                                        00
                                                    </span>
                                                    <span class="history-plat_get_commission_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-plat_get_commission_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台配送费 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中平台配送费的总额。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-plat_freight_charge_0">
                                                        00
                                                    </span>
                                                    <span class="history-plat_freight_charge_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-plat_freight_charge_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>客单价 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内有效订单的平均单价，计算公式为：当前时段所有有效订单的订单收入除以有效单数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-unit_price_0">
                                                        00
                                                    </span>
                                                    <span class="history-unit_price_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-unit_price_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>下单用户数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内下单用户数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-user_count_0">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('人')}
                                                    </span>
                                                </h1>
                                                <small class="history-user_count_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>  
                </div>     
            </fieldset> 
        </div>
        <div class="layuimini-main">
            <fieldset class="layui-elem-field layui-field-title">
                <div style="margin: 10px 10px 10px 10px">
                    <div class="layuimini-main layui-top-box">
                        <div class="layui-row layui-col-space10">
                           <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                            <div class="layui-col-md2">
                                <div class="history-header">无效订单统计</div>
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                        <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>无效订单 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-invalid_count_0">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('单')}
                                                    </span>
                                                </h1>
                                                <small class="history-invalid_count_text">比昨日同时段
                                                    <!-- 引入image中的上升图标  -->
                                                    <embed src="{pigcms{$static_path}/images/up.svg" class="logo">
                                                        <span style="color: red">
                                                           0.00元
                                                        </span>
                                                    </embed>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                          <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>损失 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额；')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-loss_money_0">
                                                        00
                                                    </span>
                                                    <span class="history-loss_money_1" style="font-size:20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-loss_money_text">比昨日同时段
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>  
                </div>    
            </fieldset> 
        </div>
        <div class="layuimini-main">
            <fieldset class="layui-elem-field layui-field-title">
                <div style="margin: 10px 10px 10px 10px">
                    <div class="layuimini-main layui-top-box">
                        <div class="layui-row layui-col-space10">
                           <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                            <div class="history-header">商家统计明细</div>
                            <div class="layui-col-md2" style="height: 750px;">
                                    <div class="panel">
                                        <iframe id="merchant_info" style="width: 100%;border: none;height: 750px;" src='{pigcms{:U("Analysis/statisticsHistoryByMer")}'></iframe>
                                    </div>
                            </div>
                        </div>
                    </div>  
                </div>    
            </fieldset> 
        </div>
    </div>
</div>
</div>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/revenueStatistics.css">
<style type="text/css">
    .discount_info{
        display: none;
    }
    #SupendButton_1{
        width: 76px
    }
    #SupendButton_2{
        width: 80px
    }
</style>
<script type="text/javascript">
    var business_type = '{pigcms{$_GET["business_type"]}';
    var order_type = '';
    var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Analysis/statistics_export')}";
    var export_url_source = "{pigcms{:U('Analysis/statistics_export')}";
    var province = '{pigcms{$_GET["province_idss"]}';
    if (!province) {
        province = $('#choose_provincess').val();
    }
    var city = '{pigcms{$_GET["city_idss"]}';
    if (!city) {
        city = $('#choose_cityss').val();
    }
    var area = '{pigcms{$_GET["area_id"]}';
    if (!area) {
        area = $('#choose_areass').val();
    }
    var statisticsTodayChartUrl = "{pigcms{:U('Analysis/statisticsTodayChart')}";
    var statisticsTodayUrl = "{pigcms{:U('Analysis/statisticsToday')}";
    var statisticsHistoryUrl = "{pigcms{:U('Analysis/statisticsHistory')}";
    var statisticsHistoryByMerUrl = "{pigcms{:U('Analysis/statisticsHistoryByMer')}";
    var Currency_symbol = "{pigcms{$config['Currency_symbol']}";
    var nowDate = '{pigcms{$nowDate}';

</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_path}js/echarts.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<script type="text/javascript" src="{pigcms{$static_public}js/laydate/laydate.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/laydate/laydate.css">
<script src="{pigcms{$static_path}js/revenueStatistics.js?t=2"></script>
<include file="Public:footer"/>