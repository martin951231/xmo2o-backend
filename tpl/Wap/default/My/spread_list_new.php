<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>推广列表</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name='apple-touch-fullscreen' content='yes'>
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <style>
        dl.list dd.dealcard {
            overflow: visible;
            -webkit-transition: -webkit-transform .2s;
            position: relative;
        }
        .dealcard.orders-del {
            -webkit-transform: translateX(1.05rem);
        }
        .dealcard-block-right {
            height: 1.68rem;
            position: relative;
        }
        .dealcard .dealcard-brand {
            margin-bottom: .18rem;
        }
        .dealcard small {
            font-size: .24rem;
            color: #9E9E9E;
        }
        .dealcard weak {
            font-size: .24rem;
            color: #999;
            position: absolute;
            bottom: 0;
            left: 0;
            display: block;
            width: 100%;
        }
        .dealcard weak b {
            color: #FDB338;
        }
        .dealcard weak a.btn{
            margin: -.15rem 0;
        }
        .dealcard weak b.dark {
            color: #fa7251;
        }
        .hotel-price {
            color: #ff8c00;
            font-size: .24rem;
            display: block;
        }
        .del-btn {
            display: block;
            width: .45rem;
            height: .45rem;
            text-align: center;
            line-height: .45rem;
            position: absolute;
            left: -.85rem;
            top: 50%;
            background-color: #EC5330;
            color: #fff;
            -webkit-transform: translateY(-50%);
            border-radius: 50%;
            font-size: .4rem;
        }
        .no-order {
            color: #D4D4D4;
            text-align: center;
            margin-top: 1rem;
            margin-bottom: 2.5rem;
        }
        .icon-line {
            font-size: 2rem;
            margin-bottom: .2rem;
        }
        .orderindex li {
            display: inline-block;
            width: 33%;
            text-align:center;
            position: relative;
        }
        .orderindex li .react {
            padding: .28rem 0;
        }
        .orderindex .text-icon {
            display: block;
            font-size: .4rem;
            margin-bottom: .18rem;
        }
        .orderindex .amount-icon {
            position: absolute;
            left: 50%;
            top: .16rem;
            color: white;
            background: #EC5330;
            border-radius: 50%;
            padding: .08rem .06rem;
            min-width: .28rem;
            font-size: .24rem;
            margin-left: .1rem;
            display: none;
        }
        .order-icon {
            display: inline-block;
            width: .5rem;
            height: .5rem;
            text-align: center;
            line-height: .5rem;
            border-radius: .06rem;
            color: white;
            margin-right: .25rem;
            margin-top: -.06rem;
            margin-bottom: -.06rem;
            background-color: #F5716E;
            vertical-align: initial;
            font-size: .3rem;
        }
        .order-all {
            background-color: #2bb2a3;
        }
        .order-zuo,.order-jiudian {
            background-color: #F5716E;
        }
        .order-fav {
            background-color: #0092DE;
        }
        .order-card {
            background-color: #EB2C00;
        }
        .order-lottery {
            background-color: #F5B345;
        }
        .color-gray{
            color:gray;
            border-color:gray;
        }
        .color-gray:active{
            background-color:gray;
        }
        .orderindex li .react.hover{
            color:#FF658E;
        }
        #load_more{
            padding: 10px;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body id="index">
<div id="tips" class="tips" style="display:block;">您只要将您挑选的商品分享给朋友，购买后您即可获得丰厚的{pigcms{$config.money_name}。</div>
<dl class="list" style="margin-top:0px;">
    <dd>
        <ul class="orderindex">
            <li><a href="{pigcms{:U('My/spread_list')}" class="react <if condition="empty($_GET['status'])">hover</if>">
                <i class="text-icon">⌺</i>
                <span>未结算</span>
                </a>
            </li><li><a href="{pigcms{:U('My/spread_list',array('status'=>-1))}" class="react <if condition="$_GET['status'] == '-1'">hover</if>">
                <i class="text-icon">⌸</i>
                <span>全部</span>
                </a>
            </li><li><a href="{pigcms{:U('My/spread_list',array('status'=>1))}" class="react <if condition="$_GET['status'] == '1'">hover</if>">
                <i class="text-icon">⌻</i>
                <span>已结算</span>
                </a>
            </li>
            <!--li><a href="{pigcms{:U('My/spread_list',array('status'=>2))}" class="react <if condition="$_GET['status'] == '2'">hover</if>">
                <i class="text-icon">⌹</i>
                <span>已退款</span>
            </a>
            </li-->
        </ul>
    </dd>
</dl>
<div style="margin-top:.2rem;">
    <dl class="list" id="orders">
        <dd>
            <dl id="content">
            </dl>
            <dl id="load_more">加载中</dl>
        </dd>
    </dl>
</div>
<script src="{pigcms{:C('JQUERY_FILE')}"></script>
<script src="{pigcms{$static_path}js/common_wap.js"></script>
<include file="Public:footer"/>
{pigcms{$hideScript}
<script>
    var page = 1;
    var money_name = '{pigcms{$config.money_name}';
    var open_extra_price = '{pigcms{$config.open_extra_price}';
    var currency_txt = '{pigcms{$config.Currency_txt}';
    var flag = true;  //放置重复请求

    function create_html(spread_lists) {
        var _html = '';
        for (i in spread_lists) {
            _html += '<dd class="dealcard dd-padding">';
            _html += '<div class="dealcard-block-right" style="margin-left:0px;">';
            _html += '<div class="dealcard-brand">' + spread_lists[i].desc.txt;
            if (spread_lists[i].desc.info != undefined) {
                _html += ' 《' + spread_lists[i].desc.info + '》</div>';
            }else{
                _html += ' 《》</div>';
            }
            _html += '<small>';
            if (spread_lists[i].money == 0) {
                _html += '<font style="color:#2bb8aa;">订单' + money_name + '过小未获得' + money_name + '</font>';
            } else {
                _html += money_name + '：<font style="color:#2bb8aa;">' + spread_lists[i].money + '</font> ';
                if (open_extra_price == 1) {
                    _html += '个';
                } else {
                    _html += currency_txt;
                }
                _html += '&nbsp;&nbsp;时间：' + spread_lists[i].add_date;
            }
            _html += '</small>';
            _html += '<weak style="color:black;">';
            if (spread_lists[i].status == 0 && (spread_lists[i].order_type == 'store' || spread_lists[i].order_type == 'cash')) {
                //空
            } else if (spread_lists[i].status == 0 && spread_lists[i].order_type != 'store') {
                if (spread_lists[i].order_info.status == 0 || spread_lists[i].order_info.status > 3) {
                    _html += '待消费';
                }
            } else if (spread_lists[i].status == 1 || spread_lists[i].order_type == 'store' || spread_lists[i].order_type == 'cash' || spread_lists[i].order_type == 'meal') {
                _html += '已结算';
            } else {
                _html += '订单已退款';
            }
            _html += '</weak>';
            _html += '</div>';
            _html += '</dd>';
        }
        return _html;
    }

    function ajax_spread_list() {
        $('#load_more').show();
        var url = '{pigcms{:U("My/spread_list")}' + '&page=' + page;

        var status = "{pigcms{$_GET['status']}"
        if(status){
            url += '&status=' + status
        }
        $.get(url, function (rs) {
            if (rs.spread_list.length < 1) {
                if (page == 1) {
                    $('#load_more').html('没有推广数据');
                } else {
                    $('#load_more').html('加载完成');
                    $('#load_more').show().fadeOut(1000);
                }
                return false;
            }
            var _html = create_html(rs.spread_list);
            $('#content').append(_html);
            $('#load_more').hide();
            flag = true;
            page++;
        })
    }
    ajax_spread_list();


    $(window).scroll(function () {
        var scrollTop = $(this).scrollTop();
        var scrollHeight = $(document).height();
        var windowHeight = $(this).height();
        if (scrollHeight - scrollTop - windowHeight < 30 && flag) {
            flag = false;
            ajax_spread_list();
        }
    });

</script>
</body>
</html>