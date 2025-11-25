<include file="Public:header"/>
<div class="main-content">
    <div style="padding: 15px;margin:15px;background: #fff">
        <h3 style="margin-bottom: 20px">活动详情</h3>
        <div style="margin-left: 30px">
            <p style="font-size: 1.6rem">创建时间：{pigcms{$detail.create_time|date='Y-m-d H:i:s',###}  <a style="margin-left:30px" href="javacript::void(0)" id="operate_log">操作记录></a></p>
            <p style="font-size: 1.6rem">活动状态：{pigcms{$detail.status_str}<egt name="days_end" value="0">（{pigcms{$days_end}天后结束）</egt></p>
            <p style="font-size: 1.6rem">活动时间：{pigcms{$detail.start_date} 至 {pigcms{$detail.end_date}</p>
            <p style="font-size: 1.6rem">活动周期：{pigcms{$detail.week_str}的 {pigcms{$detail.start_time}-{pigcms{$detail.end_time}</p>
            <p style="font-size: 1.6rem">优惠信息：{pigcms{$detail.discount_str}</p>
        </div>
    </div>

    <script type="text/javascript">
        $('#operate_log').on('click',function(){
            parent.layer.open({
                title: false,
                closeBtn: 0,
                type: 2,
                content: '{pigcms{$this->config['
                site_url']}/merchant.php?c=Shop&a=time_limited_logs&limit_id={pigcms{$_GET.id}',
                area: ['950px', '650px'],
                shade: 0.2,
                cancel: function () {
                },
                move: false
            });
        })
    </script>

    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="layuimini-container">
                    <!-- 实时数据 -->

                    <div class="layuimini-main">
                        <fieldset class="layui-elem-field layui-field-title">
                            <div style="margin-bottom: 20px;">
                                <h3>活动效果</h3>
                            </div>
                            <div class="form-group" style="margin-bottom:0px;">
                                <span class="date_tab date_active" data-days="1">昨日</span>
                                <span class="date_tab" data-days="7">近7天</span>
                                <span class="date_tab" data-days="30">近30天</span>
                            </div>
                            <div style="margin: 10px 10px 10px 10px">
                                <p style="padding-right:10%;padding-bottom:15px;text-align: right;font-size:15px;color: #999;">展示活动下所有商品的总和数据</p>
                                <div class="layuimini-main layui-top-box">
                                    <div class="layui-row layui-col-space10">
                                        <!--  layui-col-md2 每行分成12 一共6列 所以12/6=2 使用layui-col-md2 -->
                                        <div class="layui-col-md2">
                                            <div class="col-xs-4 col-md-3">
                                                <div class="panel">
                                                    <div class="panel-body">
                                                        <div class="panel-title">
                                                            <h5>商品销量 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有有效订单中的商品销量"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="statistics_sale_num">
                                                                    0
                                                                </span>
                                                            </h1>
                                                            <small class="today-total_money_text">销售总量
                                                                <span class="statistics_total_sale_num">125</span>
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
                                                            <h5>活动有效订单数 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有有效订单中活动订单总数"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="statistics_order_num">
                                                                    0
                                                                </span>
                                                            </h1>
                                                            <small>订单总量
                                                                <span class="statistics_total_order_num">125</span>
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
                                                            <h5>活动订单净收入 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有有效活动订单中的商家净收入，包含自有支付"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="statistics_order_price">
                                                                    0
                                                                </span>
                                                            </h1>
                                                            <small class="today-total_money_text">订单总净收入
                                                                <span class="statistics_total_order_price">125</span>
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
                                                            <h5>活动订单补贴支出 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title=" 统计时间内所有有效活动订单中的活动补贴总额"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="statistics_order_subsidy_price">
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
                                                            <h5>平均客单价 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内所有有效订单中活动订单的平均单价，计算公式为： 统计时间内有效活动订单净收入  /  统计时间内有效活动订单总数"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="statistics_average_price">
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
                                                            <h5>平均投入产出比 <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="统计时间内活动订单净收入  /  活动订单补贴总支出，投入产出比越高效果越好，说明通过较少的投入，得到较多的盈利"></h5>
                                                        </div>
                                                        <div class="panel-content">
                                                            <h1 class="no-margins">
                                                                <span class="statistics_input_output_ratio">
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


                        </fieldset>
                    </div>
                    <div>
                        <h3>每日活动详情</h3>
                        <iframe id="sale_record_per_day" style="width: 100%;border: none;height: 500px" src="">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/revenueStatistics.css">
<style type="text/css">
    .date_tab{
        height: 50px;
        line-height:50px;
        padding: 7px 20px;
        border: 1px solid #ddd
    }
    .date_tab:hover{
        cursor:pointer
    }
    .date_active {
        color: #067ADE;
        border: #067ADE 1px solid;
    }
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
    var statisticsHistoryByStoreUrl = "{pigcms{:U('Analysis/statisticsHistoryByStore',array('store_list'=>$_GET['store_list']))}";
    var Currency_symbol = "{pigcms{$config['Currency_symbol']}";

    var limit_id = "{pigcms{$data_id.limit_id|default=0}";
    var limit_spec_id = "{pigcms{$data_id.limit_spec_id|default=0}";

    update_sale_statistics(1);
    update_sale_record(1);

    $('.date_tab').on('click',function(){
        $(this).addClass('date_active');
        $(this).siblings('span').removeClass('date_active');
        var days = $(this).attr('data-days');
        update_sale_statistics(days)
        update_sale_record(days)
    })

    function update_sale_statistics(days) {
        $.get("{pigcms{:U('Shop/get_sale_statistics')}"+ '&limit_id=' + limit_id + '&limit_spec_id=' + limit_spec_id + '&days=' + days,function (rs) {
            for (i in rs) {
                $('.statistics_' + i).html(rs[i]);
            }
        })
    }

    function update_sale_record(days) {
        var url="{pigcms{:U('Shop/get_sale_record')}";
        url += '&limit_id=' + limit_id + '&limit_spec_id=' + limit_spec_id + '&days=' + days
        console.log(url)
        $('#sale_record_per_day').attr('src',url);
    }
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>