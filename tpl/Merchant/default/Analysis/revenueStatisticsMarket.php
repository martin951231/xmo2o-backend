<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="layuimini-container">
                    <ul class="ul_tab" id="ul_tab">
                        <if condition="$config['shop_alias_name']">
                        <li name="1"><a class="select_tab select" href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'shop'))}">{pigcms{:L_("外卖统计")}</a></li>
                        </if>
                        <if condition="$config['mall_alias_name']">
                        <li name="3"><a class="select_tab select" href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'mall'))}">{pigcms{:L_("商城统计")}</a></li>
                        </if>
                        <li name="2" class="act"> <a class="select_tab "href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'market'))}" class="on">{pigcms{:L_("营销统计")}</a></li>
                        <if condition="$config['meal_alias_name']">
                        <li name="4"><a class="select_tab" href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'dining'))}">{pigcms{:L_("餐饮统计")}</a></li>
                        </if>
                        <div class="clear"></div>
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
                                        <form action="" method="get" id="myform1" style="margin-left: 35px;">
                                            <input type="hidden" name="c" value="Analysis"/>
                                            <input type="hidden" name="a" value="revenueStatistics"/>
                                            <input type="hidden" name="business_type" value="market"/>
                                            <span class="fl" style="line-height: 36px;">业务类型：</span>
                                            <select name="order_type">
                                                <volist name="typeArr" id="vo">
                                                    <option value="{pigcms{$key}">{pigcms{$vo}</option>
                                                </volist>
                                            </select>
                                            <input type="submit" value="{pigcms{:L_("查询")}" class="btn-download" style="padding: 4px 10px;" />
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
                                                            <h5>活动总营业额 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中的所有活动订单总额；活动类型包含：')}" data-info="{pigcms{:L_('商家满减优惠活动')}?{pigcms{:L_('商家优惠券活动')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="today-market_total_money_0">
                                                                    0
                                                                </span>
                                                                <span class="today-market_total_money_1" style="font-size: 20px">
                                                                    .00
                                                                </span><img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                                <div class="discount_info_fr" style="display: none;">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>
                                                                                商家满减优惠活动：<label class="today-merchant_reduce_order_money"></label>
                                                                            </div>
                                                                            <div>
                                                                                商家优惠券活动：<label class="today-card_price_order_money"></label>
                                                                            </div>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </h1>
                                                            <div>
                                                                <small>
                                                                    占总营业额
                                                                    <span style="color: green" class="today-market_total_money_rate">
                                                                       0.00
                                                                    </span>
                                                                    <span style="color: green">
                                                                    %
                                                                    </span>
                                                                </small>
                                                            </div>
                                                            <small class="today-market_total_money_text">
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
                                                            <h5>活动总支出 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中的平台所有活动补贴总额；包含：')}"  data-info="{pigcms{:L_('商家满减优惠活动')}?{pigcms{:L_('商家优惠券活动')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="today-mer_total_money_0">
                                                                    0
                                                                </span>
                                                                <span class="today-mer_total_money_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                                <div class="discount_info_fr" style="display: none;">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>
                                                                                商家满减优惠活动：<label class="today-merchant_reduce"></label>
                                                                            </div>
                                                                            <div>
                                                                                商家优惠券活动：<label class="today-card_price"></label>
                                                                            </div>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </h1>
                                                            <small class="today-mer_total_money_text">比昨日同时段</small>
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
                                                            <h5>带动订单 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中的所有活动订单总数')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="today-market_count_0">
                                                                    0
                                                                </span>
                                                                <span style="font-size: 20px">
                                                                    {pigcms{:L_('单')}
                                                                </span>
                                                                <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                                <div class="discount_info_fr" style="display: none;">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>
                                                                                商家满减优惠活动：<label class="today-merchant_reduce_count"></label>
                                                                            </div>
                                                                            <div>
                                                                                商家优惠券活动：<label class="today-card_price_count"></label>
                                                                            </div>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </h1>
                                                             <div>
                                                                <small>
                                                                    占总订单
                                                                    <span style="color: green" class="today-market_count_rate">
                                                                       0.00
                                                                    </span>
                                                                    <span style="color: green">
                                                                    %
                                                                    </span>
                                                                </small>
                                                            </div>
                                                            <small class="today-market_count_text">比昨日同时段
                                                                
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
                                                            <h5>活动盈利 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中所有活动订单的商家净收入，包含自有支付')}" ></h5>
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
                                                            <h5>活动客单价 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中活动订单的平均单价，计算公式为：统计时间内所有有效订单中所有活动订单的商家净收入除以统计时间内所有有效订单中的所有活动订单总数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="today-unit_price_0">
                                                                    0
                                                                </span>
                                                                <span class="today-unit_price_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                            </h1>
                                                            <small class="today-unit_price_text">比昨日同时段</small>
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
                                                            <h5>活动老客数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中活动订单的老用户数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="today-regular_customer_0">
                                                                    0
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    人
                                                                </span>
                                                            </h1>
                                                            <small class="today-regular_customer_text">比昨日同时段</small>
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
                                                            <h5>活动新客数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中活动订单的新用户数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="today-new_customer_0">
                                                                    0
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    人
                                                                </span>
                                                            </h1>
                                                            <small class="today-new_customer_text">比昨日同时段</small>
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
                                                            <h5>投入产出比 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内活动盈利除以活动总支出，投入产出比越高效果越好，说明通过较少的投入，得到较多的盈利')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="today-input_output_rate_0">
                                                                    0
                                                                </span>
                                                                <span class="today-input_output_rate_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    %
                                                                </span>
                                                            </h1>
                                                            <small class="today-input_output_rate_text">比昨日同时段</small>
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
                                                            <h5>领券数量 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内用户领取商家优惠券的总数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="today-card_new_coupon_hadpull_0">
                                                                    0
                                                                </span>
                                                            </h1>
                                                            <small class="today-card_new_coupon_hadpull_text">比昨日同时段</small>
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
                                                            <h5>核销率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有已领取商家优惠券里的带动订单量除以领取数量')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="today-coupon_use_rate_0">
                                                                    0
                                                                </span>
                                                                <span class="today-coupon_use_rate_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    %
                                                                </span>
                                                            </h1>
                                                            <small class="today-coupon_use_rate_text">比昨日同时段</small>
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
                                    活动总营业额
                                </span>
                                <span
                                    id="SupendButton_2" 
                                    class="type-choice"
                                    data-data_type="1" 
                                    onclick="SupendButtonClick2()">
                                    带动订单
                                </span>
                            </div>
                            <div class="layui-input-inline" style="width: 100px;">
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
                            <div class="form-group" style="margin-bottom:0px;height: 40px;margin-top: 10px;">
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
                                    <input type="text" class="input-text fl" name="start_time" style="width:120px;" id="start_time"  value="" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd', maxDate: '{pigcms{$nowDate}'})"/>            
                                    <input type="text" class="input-text fl" name="end_time" style="width:120px;" id="end_time" value="" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd', maxDate: '{pigcms{$nowDate}'})"/>
                                    <input type="button" value="{pigcms{:L_("查询")}" class="btn-download time-sure"/>　　
                                </span>
                                <a href="javascript:void(0)" onclick="exports()" class="btn btn-download" style="margin-right: 10px;float: right;">{pigcms{:L_("下载报表")}</a>
                            </div>
                            <div style="margin: 10px 10px 10px 10px">
                                <div class="layuimini-main layui-top-box">
                                    <div class="layui-row layui-col-space10">
                                       <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                                        <div class="layui-col-md2">
                                            <div class="col-xs-4 col-md-3">
                                                <div class="panel">
                                                    <div class="panel-body">
                                                        <div class="panel-title">
                                                            <h5>活动总营业额 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中的所有活动订单总额；活动类型包含：')}" data-info="{pigcms{:L_('商家满减优惠活动')}?{pigcms{:L_('商家优惠券活动')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="history-market_total_money_0">
                                                                    0
                                                                </span>
                                                                <span class="history-market_total_money_1" style="font-size: 20px">
                                                                    .00
                                                                </span><img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                                <div class="discount_info_fr">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>
                                                                                平台满减优惠活动：<label class="history-merchant_reduce_order_money"></label>
                                                                            </div>
                                                                            <div>
                                                                                平台优惠券活动：<label class="history-card_price_order_money"></label>
                                                                            </div>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </h1>
                                                            <div>
                                                                <small>
                                                                    占总营业额
                                                                    <span style="color: green" class="history-market_total_money_rate">
                                                                       0.00
                                                                    </span>
                                                                    <span style="color: green">
                                                                    %
                                                                    </span>
                                                                </small>
                                                            </div>
                                                            <small class="history-market_total_money_text">
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
                                                            <h5>活动总支出 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中的平台所有活动补贴总额；包含：')}"  data-info="{pigcms{:L_('商家满减优惠活动')}?{pigcms{:L_('商家优惠券活动')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="history-mer_total_money_0">
                                                                    0
                                                                </span>
                                                                <span class="history-mer_total_money_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                                <div class="discount_info_fr">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>
                                                                                商家满减优惠活动：<label class="history-merchant_reduce"></label>
                                                                            </div>
                                                                            <div>
                                                                                商家优惠券活动：<label class="history-card_price"></label>
                                                                            </div>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </h1>
                                                            <small class="history-mer_total_money_text">比昨日同时段</small>
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
                                                            <h5>带动订单 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中的所有活动订单总数')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="history-market_count_0">
                                                                    0
                                                                </span>
                                                                <span style="font-size: 20px">
                                                                    {pigcms{:L_('单')}
                                                                </span>
                                                                <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                                <div class="discount_info_fr">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>
                                                                                商家满减优惠活动：<label class="history-merchant_reduce_count"></label>
                                                                            </div>
                                                                            <div>
                                                                                商家优惠券活动：<label class="history-card_price_count"></label>
                                                                            </div>
                                                                        </span>
                                                                    </span>
                                                                </div>
                                                            </h1>
                                                             <div>
                                                                <small>
                                                                    占总订单
                                                                    <span style="color: green" class="history-market_count_rate">
                                                                       0.00
                                                                    </span>
                                                                    <span style="color: green">
                                                                    %
                                                                    </span>
                                                                </small>
                                                            </div>
                                                            <small class="history-market_count_text">比昨日同时段
                                                                
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
                                                            <h5>活动盈利 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中所有活动订单的商家净收入，包含自有支付')}" ></h5>
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
                                                            <h5>活动客单价 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中活动订单的平均单价，计算公式为：统计时间内所有有效订单中所有活动订单的商家净收入除以统计时间内所有有效订单中的所有活动订单总数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="history-unit_price_0">
                                                                    0
                                                                </span>
                                                                <span class="history-unit_price_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                            </h1>
                                                            <small class="history-unit_price_text">比昨日同时段</small>
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
                                                            <h5>活动老客数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中活动订单的老用户数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="history-regular_customer_0">
                                                                    0
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    人
                                                                </span>
                                                            </h1>
                                                            <small class="history-regular_customer_text">比昨日同时段</small>
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
                                                            <h5>活动新客数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中活动订单的新用户数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="history-new_customer_0">
                                                                    0
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    人
                                                                </span>
                                                            </h1>
                                                            <small class="history-new_customer_text">比昨日同时段</small>
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
                                                            <h5>投入产出比 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内活动盈利除以活动总支出，投入产出比越高效果越好，说明通过较少的投入，得到较多的盈利')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="history-input_output_rate_0">
                                                                    0
                                                                </span>
                                                                <span class="history-input_output_rate_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    %
                                                                </span>
                                                            </h1>
                                                            <small class="history-input_output_rate_text">比昨日同时段</small>
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
                                                            <h5>领券数量 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内用户领取商家优惠券的总数')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="history-card_new_coupon_hadpull_0">
                                                                    0
                                                                </span>
                                                            </h1>
                                                            <small class="history-card_new_coupon_hadpull_text">比昨日同时段</small>
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
                                                            <h5>核销率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有已领取商家优惠券里的带动订单量除以领取数量')}" ></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="history-coupon_use_rate_0">
                                                                    0
                                                                </span>
                                                                <span class="history-coupon_use_rate_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <span style="font-size: 18px">
                                                                    %
                                                                </span>
                                                            </h1>
                                                            <small class="history-coupon_use_rate_text">比昨日同时段</small>
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
                    <div class="layuimini-main" style="height: 750px;">
                        <fieldset class="layui-elem-field layui-field-title">
                            <div style="margin: 10px 10px 10px 10px">
                                <div class="layuimini-main layui-top-box">
                                    <div class="layui-row layui-col-space10">
                                       <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                                        <div class="history-header">店铺统计明细</div>
                                        <div class="layui-col-md2">
                                                <div class="panel">
                                                    <iframe id="store_info" style="width: 100%;height: 750px;border: none;" src='{pigcms{:U("Analysis/statisticsHistoryByStore")}'></iframe>
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
    </div>
</div>

<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/revenueStatistics.css">
<style type="text/css">
    #SupendButton_1{
        width: 100px
    }
    #SupendButton_2{
        width: 80px;
        margin-right: 20px;
    }
        
    
</style>

<script type="text/javascript">
    var store_list = '{pigcms{$_GET["store_list"]}';
    var business_type = '{pigcms{$_GET["business_type"]}';
    var order_type = '{pigcms{$_GET["order_type"]}';
    var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Analysis/statistics_export')}";
    var export_url_source = "{pigcms{:U('Analysis/statistics_export')}"; 
    var statisticsTodayChartUrl = "{pigcms{:U('Analysis/statisticsTodayChart')}";
    var statisticsTodayUrl = "{pigcms{:U('Analysis/statisticsToday')}";
    var statisticsHistoryUrl = "{pigcms{:U('Analysis/statisticsHistory')}";
    var statisticsHistoryByStoreUrl = "{pigcms{:U('Analysis/statisticsHistoryByStore',array('store_list'=>$_GET['store_list']))}";
    var Currency_symbol = "{pigcms{$config['Currency_symbol']}";
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_path}js/echarts.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<script src="{pigcms{$static_path}js/revenueStatistics.js?T=1"></script>