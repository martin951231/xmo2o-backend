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
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'mall'))}">{pigcms{$config['mall_alias_name']}</a>
            </if>
            <a href="{pigcms{:U('Analysis/revenueStatistics',array('business_type'=>'deliver'))}" class="on">配送</a>
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
                                <input type="hidden" name="business_type" value="{pigcms{$Think.get.business_type|default='deliver'}"/>
                                
                                <if condition="$now_area['area_type'] neq 3">
                                    <span class="fl">地区筛选：</span>
                                    <div class="fl" id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_id="{pigcms{$_GET.street_id}" style="display:inline"></div>
                                </if>
                                <input type="submit" value="查询" class="button" style="margin-top: 2px;"/>
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
                                                <h5>平台总配送费 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效配送订单中平台配送费的总额；包含外卖、跑腿、商城、第三方订单（美团与饿了么）、社区拼团业务中的配送费。')}"></h5>
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
                                                    </span><img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <div>
                                                                    {pigcms{$config['shop_alias_name']}：<label class="today-deliver_money_shop"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{$config['mall_alias_name']}：<label class="today-deliver_money_mall"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('跑腿')}：<label class="today-deliver_money_third"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('第三方')}：<label class="today-deliver_money_paotui"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('社区拼团')}：<label class="today-vip_level_reduce_money"></label>
                                                                </div>
                                                            </span>
                                                        </span>
                                                    </div>
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
                                                <h5>平台配送费抽成 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效配送订单中平台配送费的抽成总额；如果订单未设置平台配送费抽成比例则直接按照订单的原价配送费来统计。')}" ></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-plat_freight_charge_0">
                                                        0
                                                    </span>
                                                    <span class="today-plat_freight_charge_1" style="font-size: 20px">
                                                        .00
                                                    </span>

                                                    </span>
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <div>
                                                                    {pigcms{$config['shop_alias_name']}：<label class="today-plat_freight_charge_shop"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{$config['mall_alias_name']}：<label class="today-plat_freight_charge_mall"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('跑腿')}：<label class="today-plat_freight_charge_paotui"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('第三方')}：<label class="today-plat_freight_charge_third"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('社区拼团')}：<label class="today-plat_freight_charge_village_group"></label>
                                                                </div>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="today-plat_freight_charge_text">比昨日同时段</small>
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
                                                <h5>有效配送单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('未被取消的订单数；取消包括平台、店员操作的取消。')}"></h5>
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
                                                <h5>无效配送单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('被取消的订单数；取消包括平台、店员操作的取消。')}"></h5>
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
                                                <h5>活动补贴 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效配送订单的补贴配送费总额；只包含外卖业务下，平台满减的配送优惠类型下的平台与商家配送费的补贴金额，平台配送的达到免配送费条件的配送费补贴金额。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-discount_money_0">
                                                        00
                                                    </span>
                                                    <span class="today-discount_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <div>
                                                                    {pigcms{:L_('平台补贴')}：<label class="today-plat_total_money"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('商家补贴')}：<label class="today-mer_total_money"></label>
                                                                </div>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="today-discount_money_text">比昨日同时段
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
                                                <h5>损失 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('平台、店员主动取消订单的退款配送费金额。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="today-loss_money_0">
                                                        00
                                                    </span>
                                                    <span class="today-loss_money_1" style="font-size: 20px">
                                                        .00
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
                                                <h5>配送超时订单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中超过预计送达时间后完成配送的订单。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-overtime_count_0">
                                                        00
                                                    </span>
                                                    <span class="today-overtime_count_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="today-overtime_count_text">比昨日同时段
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
                                                <h5>配送超时超时率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内配送超时订单量除以有效配送单量。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-overtime_rate_0">
                                                        00
                                                    </span>
                                                    <span class="today-overtime_rate_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <span style="font-size: 18px">
                                                        %
                                                    </span>
                                                </h1>
                                                <small class="today-overtime_rate_text">比昨日同时段
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
                                                <h5>催单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中平台配送员被催单订单数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-reminder_count_0">
                                                        0
                                                    </span>
                                                </h1>
                                                <small class="today-user_count_text">比昨日同时段
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
                                                <h5>催单率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内催单量除以有效配送单量。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-reminder_rate_0">
                                                        00
                                                    </span>
                                                    <span class="today-reminder_rate_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <span style="font-size: 18px">
                                                        %
                                                    </span>
                                                </h1>
                                                <small class="today-reminder_rate_text">比昨日同时段
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
                                                <h5>差评订单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中配送员评价<=2分的订单数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-bad_count_0">
                                                        0
                                                    </span>
                                                </h1>
                                                <small class="today-user_count_text">比昨日同时段
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
                                                <h5>差评率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内配送员差评单量除以配送员评价总单量。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="today-bad_rate_0">
                                                        00
                                                    </span>
                                                    <span class="today-bad_rate_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <span style="font-size: 18px">
                                                        %
                                                    </span>
                                                </h1>
                                                <small class="today-bad_rate_text">比昨日同时段
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
                        平台总配送费
                    </span>
                    <span
                        id="SupendButton_2" 
                        class="type-choice"
                        data-data_type="1" 
                        onclick="SupendButtonClick2()">
                        有效配送单数
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
                        <input type="button" value="{pigcms{:L_("查询")}" class="btn-download time-sure"/>　　
                    </span>
                    <a href="javascript:void(0)" onclick="exports()" class="btn btn-download" style="margin-right: 10px;float: right;">{pigcms{:L_("下载报表")}</a>
                </div>
                <div style="margin: 10px 10px 10px 10px">
                    <div class="layuimini-main layui-top-box">
                       
                    <div class="layuimini-main layui-top-box">
                        <div class="layui-row layui-col-space10">
                           <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                            <div class="layui-col-md2">
                                <div class="col-xs-4 col-md-3">
                                    <div class="panel">
                                        <div class="panel-body">
                                            <div class="panel-title">
                                                <h5>平台总配送费 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效配送订单中平台配送费的总额；包含外卖、跑腿、商城、第三方订单（美团与饿了么）、社区拼团业务中的配送费。')}"></h5>
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
                                                    </span><img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <div>
                                                                    {pigcms{$config['shop_alias_name']}：<label class="history-deliver_money_shop"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{$config['mall_alias_name']}：<label class="history-deliver_money_mall"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('跑腿')}：<label class="history-deliver_money_third"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('第三方')}：<label class="history-deliver_money_paotui"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('社区拼团')}：<label class="history-vip_level_reduce_money"></label>
                                                                </div>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="history-total_money_text">比昨日同时段
                                                   
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
                                                <h5>平台配送费抽成 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效配送订单中平台配送费的抽成总额；如果订单未设置平台配送费抽成比例则直接按照订单的原价配送费来统计。')}" ></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-plat_freight_charge_0">
                                                        0
                                                    </span>
                                                    <span class="history-plat_freight_charge_1" style="font-size: 20px">
                                                        .00
                                                    </span>

                                                    </span>
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <div>
                                                                    {pigcms{$config['shop_alias_name']}：<label class="history-plat_freight_charge_shop"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{$config['mall_alias_name']}：<label class="history-plat_freight_charge_mall"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('跑腿')}：<label class="history-plat_freight_charge_paotui"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('第三方')}：<label class="history-plat_freight_charge_third"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('社区拼团')}：<label class="history-plat_freight_charge_village_group"></label>
                                                                </div>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </h1>
                                                <small class="history-plat_freight_charge_text">比昨日同时段</small>
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
                                                <h5>有效配送单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('未被取消的订单数；取消包括平台、店员操作的取消。')}"></h5>
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
                                                <h5>活动补贴 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效配送订单的补贴配送费总额；只包含外卖业务下，平台满减的配送优惠类型下的平台与商家配送费的补贴金额，平台配送的达到免配送费条件的配送费补贴金额。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-discount_money_0">
                                                        00
                                                    </span>
                                                    <span class="history-discount_money_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    
                                                    <img src="{pigcms{$static_path}/images/icons/xiangxiajiantou.png" class="discount_info_show">
                                                    <div class="discount_info">
                                                        <span>
                                                            <b></b>
                                                            <em></em>
                                                            <span>
                                                                <div>
                                                                    {pigcms{:L_('平台补贴')}：<label class="history-plat_total_money"></label>
                                                                </div>
                                                                <div>
                                                                    {pigcms{:L_('商家补贴')}：<label class="history-mer_total_money"></label>
                                                                </div>
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
                                                <h5>配送超时订单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中超过预计送达时间后完成配送的订单。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-overtime_count_0">
                                                        00
                                                    </span>
                                                    <span class="history-overtime_count_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                </h1>
                                                <small class="history-overtime_count_text">比昨日同时段
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
                                                <h5>配送超时超时率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内配送超时订单量除以有效配送单量。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-overtime_rate_0">
                                                        00
                                                    </span>
                                                    <span class="history-overtime_rate_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <span style="font-size: 18px">
                                                        %
                                                    </span>
                                                </h1>
                                                <small class="history-overtime_rate_text">比昨日同时段
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
                                                <h5>催单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中平台配送员被催单订单数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-reminder_count_0">
                                                        0
                                                    </span>
                                                </h1>
                                                <small class="history-user_count_text">比昨日同时段
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
                                                <h5>催单率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内催单量除以有效配送单量。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-reminder_rate_0">
                                                        00
                                                    </span>
                                                    <span class="history-reminder_rate_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <span style="font-size: 18px">
                                                        %
                                                    </span>
                                                </h1>
                                                <small class="history-reminder_rate_text">比昨日同时段
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
                                                <h5>差评订单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内所有有效订单中配送员评价<=2分的订单数。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-bad_count_0">
                                                        0
                                                    </span>
                                                </h1>
                                                <small class="history-user_count_text">比昨日同时段
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
                                                <h5>差评率 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('统计时间内配送员差评单量除以配送员评价总单量。')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="history-bad_rate_0">
                                                        00
                                                    </span>
                                                    <span class="history-bad_rate_1" style="font-size: 20px">
                                                        .00
                                                    </span>
                                                    <span style="font-size: 18px">
                                                        %
                                                    </span>
                                                </h1>
                                                <small class="history-bad_rate_text">比昨日同时段
                                                </small>
                                            </div>
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
                                                <h5>无效配送单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。')}"></h5>
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
                                                <h5>损失 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{:L_('包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额；')}"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="history-loss_money_0">
                                                        00
                                                    </span>
                                                    <span class="history-loss_money_1" style="font-size: 20px">
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
                                        <iframe id="merchant_info" style="width: 100%;height: 750px;border: none;" src='{pigcms{:U("Analysis/statisticsHistoryByMer")}'></iframe>
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
    #SupendButton_2 {
        width: 110px;
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
<script src="{pigcms{$static_path}js/revenueStatistics.js"></script>
<include file="Public:footer"/>