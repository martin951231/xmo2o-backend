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
                        <li name="3"> <a class="select_tab "href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'mall'))}" class="on">{pigcms{:L_("商城统计")}</a></li>
                        </if>
                        <li name="2"> <a class="select_tab "href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'market'))}" class="on">{pigcms{:L_("营销统计")}</a></li>
                        <if condition="$config['meal_alias_name']">
                        <li  class="act" name="4"><a class="select_tab" href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'dining'))}">{pigcms{:L_("餐饮统计")}</a></li>
                        </if>
                        <div class="clear"></div>
                   </ul> 
                </div>
                 <div class="layuimini-container">
                    <!-- 实时数据 -->

                    <div class="layuimini-main">
                        <fieldset class="layui-elem-field layui-field-title">
                            <div style="margin-bottom: 20px;">
                                <span style="    font-size: 21px;font-weight: 600;margin-right: 70px;">营业统计(统计时间内实时数据)</span>
                                <span style="float: right;"><span class="auto_refresh"></span>&nbsp;后自动刷新</span>
                            </div>
                            <div class="form-group" style="margin-bottom:0px;">
                                <form action="{pigcms{:U('Appoint/order_list')}" method="get" id="myform1" style="margin-left: -22px;">
                                    <input type="hidden" name="c" value="Analysis"/>
                                    <input type="hidden" name="a" value="revenueStatistics"/>     
                                    <input type="hidden" name="business_type" value="dining"/>                          
                                    <span class="fl" style="margin-left:60px;line-height: 36px;">{pigcms{:L_("选择店铺")}</span>
                                   
                                    <input type="hidden" id="part_store" data-type="part_store" name="store_list" <if condition="$_GET['store_list'] neq -1 AND !empty($_GET['store_list'])">checked value="{pigcms{$_GET['store_list']}" <else />value="0"</if>>
                            
                                    <span class="fl select-store" href="{pigcms{:U('Analysis/store_list')}" id="select_store"  data-type="all_store">
                                        <span>
                                            <if condition="$_GET['store_list'] neq -1 AND !empty($_GET['store_list'])">
                                                {pigcms{:L_("指定店铺")}
                                            <else/>
                                                {pigcms{:L_("全部店铺")}
                                            </if>

                                        </span>
                                        <img src="{pigcms{$static_path}images/icons/add-2.png">
                                    </span>
                                    <input type="submit" value="{pigcms{:L_("查询")}" class="btn-download" style="padding: 4px 10px;" />
                                </form>
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
                                                            <h5>营业额 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内流水总额，包含：')}" data-info="{pigcms{:L_('商品原价')}"></h5>
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
                                                            <h5>订单收入 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内已完成订单的净收入，包含自有支付金额；扣除：')}" data-info="{pigcms{:L_('平台抽成')}?{pigcms{:L_('平台满减补贴')}?{pigcms{:L_('商家满减补贴')}?{pigcms{:L_('退款金额')}"></h5>
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
                                                            <h5>有效订单 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内未被取消、未全部商品退款的订单数；取消包括平台、店员、用户操作的取消')}"></h5>
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
                                                            <h5>活动补贴 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内平台/商家满减优惠与平台/商家优惠券补贴总额，包含：')}" data-info="{pigcms{:L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')}?{pigcms{:L_('平台满减优惠商家补贴金额部分，含满减优惠的所有类型')}?{pigcms{:L_('商家自有满减优惠商家补贴金额部分')}?{pigcms{:L_('平台优惠券平台补贴金额部分')}?{pigcms{:L_('商家优惠券商家补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins" style="position: relative;">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="today-discount_money_0">
                                                                    0
                                                                </span>
                                                                <span class="today-discount_money_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="tips_img discount_info_show">
                                                                <div class="discount_info">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>{pigcms{:L_('商家补贴总额')}：<label class="today-merchant_total"></label></div>
                                                                            <div>{pigcms{:L_('平台补贴总额')}：<label class="today-plat_total"></label></div>
                                                                        </span>
                                                                    </span>
                                                                    </div>
                                                                
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
                                                            <h5>无效订单 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与全部商品退款的订单。')}"></h5>
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
                                                            <h5>损失 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与全部商品退款的退款金额；')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="today-loss_money_0">
                                                                    0
                                                                </span>
                                                                <span class="today-loss_money_1" style="font-size: 20px">
                                                                </span>
                                                            </h1>
                                                            <small class="today-loss_money_text">比昨日同时段
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
                                                            <h5>客单价 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内有效订单的平均单价，计算公式为：当前时段所有有效订单的订单收入除以有效单数')}"></h5>
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
                                                            <h5>下单用户数 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内下单用户数。')}"></h5>
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
                                    有效订单
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
                        <div class="layui-inline">
                          
                            <div class="layui-input-inline yesterdayTotal" style="margin-right: 100px;float: right;color: #666">
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
                                <a href="javascript:void(0)" onclick="exports()" class="btn-download" style="margin-right: 10px;float: right;">{pigcms{:L_("下载报表")}</a>
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
                                                            <h5>营业额 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内流水总额，包含：')}" data-info="{pigcms{:L_('商品原价')}"></h5>
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
                                                            <h5>订单收入 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内已完成订单的净收入，包含自有支付金额；扣除：')}" data-info="{pigcms{:L_('平台抽成')}?{pigcms{:L_('平台满减补贴')}?{pigcms{:L_('商家满减补贴')}?{pigcms{:L_('退款金额')}"></h5>
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
                                                            <h5>有效订单 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内未被取消、未全部商品退款的订单数；取消包括平台、店员、用户操作的取消')}"></h5>
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
                                                            <h5>活动补贴 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内平台/商家满减优惠与平台/商家优惠券补贴总额，包含：')}" data-info="{pigcms{:L_('平台满减优惠平台补贴金额部分，含满减优惠的所有类型')}?{pigcms{:L_('平台满减优惠商家补贴金额部分，含满减优惠的所有类型')}?{pigcms{:L_('商家自有满减优惠商家补贴金额部分')}?{pigcms{:L_('平台优惠券平台补贴金额部分')}?{pigcms{:L_('商家优惠券商家补贴金额部分')}?{pigcms{:L_('平台积分抵扣平台补贴部分')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins" style="position: relative;">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="history-discount_money_0">
                                                                    0
                                                                </span>
                                                                <span class="history-discount_money_1" style="font-size: 20px">
                                                                    .00
                                                                </span>
                                                                <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="tips_img discount_info_show">
                                                                <div class="discount_info">
                                                                    <span>
                                                                        <b></b>
                                                                        <em></em>
                                                                        <span>
                                                                            <div>{pigcms{:L_('商家补贴总额')}：<label class="history-merchant_total"></label></div>
                                                                            <div>{pigcms{:L_('平台补贴总额')}：<label class="history-plat_total"></label></div>
                                                                        </span>
                                                                    </span>
                                                                    </div>
                                                                
                                                            </h1>
                                                            <small class="history-invalid_count_text">比昨日同时段
                                                               
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
                                                            <h5>客单价 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内有效订单的平均单价，计算公式为：当前时段所有有效订单的订单收入除以有效单数。')}"></h5>
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
                                                            <h5>下单用户数 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内下单用户数。')}"></h5>
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
                                                            <h5>无效订单 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与全部商品退款的订单。')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="history-invalid_count_0">
                                                                    0
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
                                                            <h5>损失 <img src="./tpl/System/Static/images/help1.gif" class="tips_img" title="{pigcms{:L_('统计时间内包括用户主动及平台、店员主动取消与全部商品退款的退款金额；')}"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span style="font-size: 18px">
                                                                    {pigcms{$config['Currency_symbol']}
                                                                </span>
                                                                <span class="history-loss_money_0">
                                                                    0
                                                                </span>
                                                                <span class="history-loss_money_1" style="font-size: 20px">
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
                    <div class="layuimini-main" style="height: 750px;">
                        <fieldset class="layui-elem-field layui-field-title">
                            <div style="margin: 10px 10px 10px 10px">
                                <div class="layuimini-main layui-top-box">
                                    <div class="layui-row layui-col-space10">
                                       <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                                        <div class="history-header">店铺统计明细</div>
                                        <div class="layui-col-md2">
                                                <div class="panel">
                                                    <iframe id="store_info" style="width: 100%;height: 550px;border: none;" src='{pigcms{:U("Analysis/statisticsHistoryByStore")}'></iframe>
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
</style>
<script type="text/javascript">
    var store_list = '{pigcms{$_GET["store_list"]}';
    var url = "{pigcms{$config.site_url}"
    var business_type = '{pigcms{$_GET["business_type"]}';
    var order_type = '{pigcms{$_GET["order_type"]}';
    var export_url = "{pigcms{:U('Analysis/statistics_export')}";
    var export_url_source = "{pigcms{:U('Analysis/statistics_export')}";
    var statisticsTodayChartUrl = "{pigcms{:U('Analysis/statisticsTodayChart')}";
    var statisticsTodayUrl = "{pigcms{:U('Analysis/statisticsToday')}";
    var statisticsHistoryUrl = "{pigcms{:U('Analysis/statisticsHistory')}";
    var statisticsHistoryByStoreUrl = "{pigcms{:U('Analysis/statisticsHistoryByStore',array('store_list'=>$_GET['store_list'],'business_type'=>$_GET['business_type']))}";
    var Currency_symbol = "{pigcms{$config['Currency_symbol']}";
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_path}js/echarts.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<script src="{pigcms{$static_path}js/revenueStatistics.js?T=1"></script>
<include file="Public:footer"/>