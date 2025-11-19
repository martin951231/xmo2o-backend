<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>充值记录</title>
		<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
		<meta http-equiv="pragma" content="no-cache"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" href="{pigcms{$static_path}company_campus/css/reset.css"> 
		<link rel="stylesheet" href="{pigcms{$static_path}company_campus/js/weui/weui.min.css">
		<link rel="stylesheet" href="{pigcms{$static_path}company_campus/css/recharge.css" /> 
		<script src="{pigcms{$static_path}company_campus/js/jquery-1.8.3.min.js"></script>  
		<script src="{pigcms{$static_path}company_campus/js/weui/weui.min.js"></script>
        <style type="text/css">            
            .hidden{display: none !important;}
            .bottom-txt{font-size: 12px;color: #999;line-height: 40px;height: 40px;text-align: center;}
        </style>
	</head>
	<body>
        <div id="app" class="list-wrapper">
        </div>
        <div class="bottom-txt">
            <span class="loading hidden">加载更多数据</span>
            <span class="complete hidden">没有更多数据了</span>
        </div>
        <script type="text/javascript">
            var is_load = true ; // 监听列表数据是否请求完毕，避免同时请求多次
            var page = 1;
            var complete = false;
            var keyword = '';
            getList('');
            $(document).on('scroll', window, function(e){
                var distance = $('.bottom-txt').offset().top-$(document).scrollTop() +$('.bottom-txt').height() - $(window).height()
                if (!complete && distance <= 40 && !is_load) {
                    is_load = true;
                    getList(keyword);
                }
                var scrollTop = $(window).scrollTop();
            })

            // 获取“猜你喜欢”店铺列表
            function getList (keyword) {
                var param = {};
                param.page = page
                param.keyword = keyword
                $.post('/wap.php?c=Company_campus&a=ajaxOrderList', param, function(data){
                            console.log(data)
                    is_load = false
                    if (data.list.length > 0) {
                        for (var i=0; i<data.list.length; i++) {
                            var item = data.list[i];
                            var item_html = '';
                            item_html = '<div class="weui-media-box weui-media-box_text">\
                                            <h4 class="weui-media-box__title">卡号信息:'+item.card_info+'</h4>\
                                            <h4 class="weui-media-box__title">充值金额: '+item.money+'{pigcms{$config["Currency_txt"]}</h4>\
                                            <h4 class="weui-media-box__title">充值时间: '+item.pay_time+'</h4> \
                                        </div>';
                            $('.list-wrapper').append(item_html)
                        }
                    }

                    if (data.list.length == 10) {
                        page++;
                        $('.bottom-txt .loading').removeClass('hidden');
                    } else {
                        complete = true;
                        $('.bottom-txt .loading').addClass('hidden');
                        $('.bottom-txt .complete').removeClass('hidden');
                    }
                },'json')
            }
        </script>
	</body>
</html>
