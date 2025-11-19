<include file="Public:header"/>
<link rel="stylesheet" type="text/css" href="./tpl/Merchant/default/static/css/bootstrap.min.css" />
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('MembershipCard/config')}">平台会员卡</a>|
            <a href="{pigcms{:U('MembershipCard/report')}" class="on">效果数据</a>|
            <a href="{pigcms{:U('MembershipCard/orders')}">订单列表</a>|
        </ul>
    </div>
    <div class="layuimini-container">
        <div style="padding: 15px;margin:15px;background: #fff">
            <h3 style="margin-bottom: 20px">活动详情</h3>
            <div style="margin-left: 30px">
                <p style="font-size: 1.6rem">会员卡名称：{pigcms{$config.card_name}  <a style="margin-left:30px" href="javacript::void(0)" id="operate_log">操作记录></a></p>
                <p style="font-size: 1.6rem">活动状态：<php>echo $config['status'] == 1?'进行中':'已关闭';</php></php>
                <p style="font-size: 1.6rem">优惠信息：
                    <php>
                        $discount = [];
                        $symbol = C('config.Currency_symbol');
                        if($config['discount_type']&1){
                            $discount[]=sprintf('赠送%s*%d张优惠券',$symbol.getFormatNumber($config['coupon_price']),$config['coupon_num']);
                        }
                        if($config['discount_type']&2){
                            $discount[]=sprintf('赠送%s*%d次平台配送费立减机会',$symbol.getFormatNumber($config['deliver_price']),$config['deliver_num']);
                        }
                        echo implode('；',$discount);
                    </php>
<!--                    赠送5元*6张优惠券；赠送2元*5次平台配送费立减机会-->
                </php>
            </div>
        </div>
        <!-- 分割线 -->
        <hr class="layui-bg-gray">

        <div class="layuimini-main">
            <fieldset class="layui-elem-field layui-field-title">
                <div class="form-group" style="margin-bottom:0px;height: 40px;margin-left: 30px;margin-top: 10px;">
                    <span style="    font-size: 21px;float: left;font-weight: 600;    margin-right: 70px;">活动效果</span>
                    <div class="fl time-choice-wrap cf stat-time-choice" style="float: left;">
                        <span class="time-btn time-choice" days="1">
                        {pigcms{:L_("昨日")}</span>
                        <span class="time-btn time-choice time-current" days="7" >
                        {pigcms{:L_("近7日")}</span>
                        <span class="time-btn time-choice" days="30" >
                        {pigcms{:L_("近30日")}</span>
                        <!--<span class="time-btn time-choice" id="choice-custome" timetype="4" >
                        {pigcms{:L_("自定义")}</span>
                        <input type="hidden" id="date_type" value="2">-->
                    </div>
                    <span class="time_select" style="display: none;">

                        <span class="fl select-item">{pigcms{:L_("日期筛选：")}</span>
                        <input type="text" class="layui-input input-text fl" name="start_time" style="width:120px;" id="start_time"  value="" autocomplete="off" readonly/>
                        <input type="text" class="layui-input input-text fl" name="end_time" style="width:120px;" id="end_time" value=""  autocomplete="off" readonly />
                        <a class="btn-download time-sure"/>{pigcms{:L_("查询")}</a>　
                    </span>
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
                                                <h5>购卡订单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有购卡订单总数"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="order_count">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('单')}
                                                    </span>
                                                </h1>
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
                                                <h5>购卡顾客支付金额 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有购卡顾客实付总金额"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="order_price">
                                                        0
                                                    </span>
                                                </h1>
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
                                                <h5>购卡新客人数(人) <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内开通会员卡总人数"></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span class="user_count">
                                                        0
                                                    </span>
                                                    <span style="font-size: 20px">
                                                        {pigcms{:L_('人')}
                                                    </span>
                                                </h1>
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
                                                <h5>购卡平均客单价 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有购卡平均客单价，计算公式为： 统计时间内购卡顾客支付总金额 /  统计时间内购卡订单总数" ></h5>
                                            </div>
                                            <div class="panel-content">
                                                <h1 class="no-margins">
                                                    <span style="font-size: 18px">
                                                        {pigcms{$config['Currency_symbol']}
                                                    </span>
                                                    <span class="per_price">
                                                        0
                                                    </span>
                                                </h1>
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
                            <div class="history-header">每日活动详情</div>
                            <div class="layui-col-md2" style="height: 750px;">
                                <div class="panel">
                                    <iframe id="sale_record_per_day" style="width: 100%;border: none;height: 750px;" src='{pigcms{:U("MembershipCard/form")}'></iframe>
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
    var form_url = '{pigcms{:U("MembershipCard/form")}';
    var sale_data_url = '{pigcms{:U("MembershipCard/saleData")}';

    $('#operate_log').on('click',function(){
        layer.open({
            title: false,
            closeBtn: 1,
            type: 2,
            content: '{pigcms{:C("config.site_url")}' + '/admin.php?c=MembershipCard&a=logs',
            area: ['850px', '650px'],
            offset: '50px',
            shade: 0.2,
            cancel: function () {
            },
            move: false
        });
    })

    $('.time-btn').on('click',function(){
        $(this).addClass('time-current').siblings('.time-btn').removeClass('time-current')
        var days = $(this).attr('days');
        update_sale_data(days);
        update_day_report(days);
    })

    function update_sale_data(days) {
        $.get(sale_data_url+'&days='+days,function(rs){
            $('.order_count').html(rs.order_count);
            $('.order_price').html('<span style="font-size:18px">' + rs.currency_symbol + '</span>' + rs.order_price);
            $('.user_count').html(rs.user_count);
            $('.per_price').html('<span style="font-size:18px">' + rs.currency_symbol + '</span>' + rs.per_price);
        })
    }

    function update_day_report(days) {
        $('#sale_record_per_day').attr('src', form_url + '&days=' + days);
    }

    update_sale_data(7);
    update_day_report(7);
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/laydate/laydate.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/laydate/laydate.css">
<include file="Public:footer"/>