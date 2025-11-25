<!DOCTYPE html>
<html lang="zh-CN" >
    <head>
        <meta charset="utf-8"/>
        <title>我的小区消息</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}service/css/mobiscroll.2.13.2.css" rel="stylesheet"/>
        <script src="{pigcms{:C('JQUERY_FILE')}"></script>
        <style>
            body {
                padding: 0px;
                margin: 0px auto;
                font-size: 14px;
                background-color: #f5f4f9;
                color: #232323;
                position: relative;
                -webkit-tap-highlight-color: rgba(0,0,0,0);
                -webkit-user-select: none;
                overflow-x: hidden;
            }
            html, body, div, p, ul, li, dl, dt, dd, h1, h2, h3, h4, h5, h6, form, input, select, button, textarea, iframe, table, th, td {
                margin: 0;
                padding: 0;
                border-spacing: 0px !important;
                outline: medium;
                word-break: break-word;
                border: none;
                list-style: none;
            }
            .public {
                height: 44px;
                line-height: 44px;
                background: #06c1ae;
                color: #fff;
                position: fixed;
                width: 100%;
                top: 0px;
                left: 0px;
                z-index: 880;
            }
            .public .content {
                text-align: center;
                font-size: 16px;
            }
            .entry li {
                margin: 0px 10px 10px 10px;
                border-radius: 5px;
                background: #fff;
                border: #e6e6e6 1px solid;
            }
            .kd_entry li .top {
                border-bottom: #f1f1f1 1px solid;
                color: #999999;
            }
            .entry li .top {
                border-radius: 5px 5px 0px 0px;
                font-size: 13px;
                line-height: 22px;
                padding: 6px;
            }
            .clr:after {
                visibility: hidden;
                display: block;
                content: " ";
                clear: both;
                height: 0;
                line-height: 0;
            }
            .fl {
                float: left;
                display: inline;
            }
            .entry li .con {
                font-size: 12px;
                color: #666666;
                line-height: 24px;
                padding: 5px 12px;
            }
            .kd_entry li .con {
                padding: 0 12px;
            }
            .entry li .con_top {
                border-bottom: #f1f1f1 1px solid;
            }
            .kd_entry li .con p {
                overflow: hidden;
                word-break: keep-all;
                white-space: nowrap;
                text-overflow: ellipsis;
            }
            .entry li .con_top span.red {
                color: #e93e14;
            }
            .kd_entry li .remark {
                line-height: 20px;
                height: 20px;
                font-size: 13px;
                position: relative;
                color: #06c1ae;
            }
            .entry li .remark {
                line-height: 18px;
                padding: 10px 0;
            }
            .public .return {
                position: absolute;
                width: 50px;
                height: 100%;
                left: 0px;
                top: 0px;
            }
            .public .return:after {
                display: block;
                content: "";
                border-top: 2px solid #fff;
                border-left: 2px solid #fff;
                width: 10px;
                height: 10px;
                -webkit-transform: rotate(-45deg);
                background-color: transparent;
                position: absolute;
                left: 16px;
                top: 16px;
            }
            .h44 {
                height: 44px;
            }
            .tip_time{
                text-align: center;
                padding: 15px 0 10px;
                font-size: 12px;
                color: #999;
            }
        </style>
    </head>

    <body>
<!--        <section class="public pageSliderHide">-->
<!--         <a href="{pigcms{:U('My/index')}" ><div class="return"></div></a>-->
<!--            <div class="content">我的社区消息</div>-->
<!--        </section>-->

<!--        <div class="h44"></div>-->

        <section class="store" style="margin-top: 5px;">
            <div class="entry kd_entry">
                <ul id="publish_list">
                    <volist name="list" id="vo">
                        <div class="tip_time">
                            {pigcms{$vo['add_time']|date='Y-m-d H:i:s',###}
                        </div>
                        <li <if condition="$vo['url'] neq ''">class="link_url" data-url="{pigcms{$vo['url']}"</if>>
                            <if condition="$vo['title']">
                                <div class="top clr">
                                    <div class="fl">
                                            {pigcms{$vo['title']}
                                    </div>
                                </div>
                            </if>
                            <div class="con">
                                <if condition="$vo['content'] || $vo['pre_title'] neq '' || $vo['end_title'] neq ''">
                                    <div class="con_top">
                                        <if condition="$vo['pre_title'] neq ''">
                                            <p>
                                                {pigcms{$vo['pre_title']}
                                            </p>
                                        </if>
                                        <if condition="$vo['content']">
                                            <volist name="vo['content']" id="item">
                                                <p>
                                                    {pigcms{$item['title']}：
                                                    <span>{pigcms{$item['content']}</span>
                                                </p>
                                            </volist>
                                        </if>
                                        <if condition="$vo['end_title'] neq ''">
                                            <p>
                                                {pigcms{$vo['end_title']}
                                            </p>
                                        </if>
                                    </div>
                                </if>
                                <if condition="$vo['url'] neq ''">
                                    <div class="remark">
                                        <div>
                                            {pigcms{$vo['url_title']|default='查看详情'}
                                        </div>
                                    </div>
                                </if>
                            </div>
                        </li>
                    </volist>
                </ul>
            </div>
            <!-- 上拉刷新 -->
        <div class="pullup" style="display:none;">上拉加载更多...</div>
        <div class="loading" style="display:none;"><img src="{pigcms{$static_path}service/images/xubox_loading2.gif" alt="">正在加载</div>
            <div style="padding-bottom: 80px;"></div>
        </section>
        
        <style>
            .pullup,.loading{
                text-align: center;
                padding:0 0 10px 0;
                font-size: 14px;
                color: #999;
            }
            .loading img{
                height: 14px;
                vertical-align: middle;
                margin-right: 2px;
            }
            body{line-height: 1.5;}
           .fl { float: left; display: inline; }
        </style>
        <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
        <script>
            var flag = false;
            var page = 0;
            // 上拉加载和下拉刷新
            $(window).scroll(function(e) {
                e.stopPropagation();
                //上拉加载
                if(flag){
                    //数据加载中
                    return false;
                }
                if ($(document).scrollTop() >= $(document).height() - $(window).height()) {
                    $('.pullup').hide();
                    $('.loading').show();
                    flag = true;
                    page++;
                    changeAllshou();
                    $('.pullup').show();
                    $('.loading').hide();
                }
            });

            function changeAllshou(){
                var my_house_tip_ajax = "{pigcms{:U('my_house_tip_ajax')}";
                $.post(my_house_tip_ajax,{page:page},function(data){
                    if(data.error == 1){
                        flag = false;
                        $("#publish_list").append(data.publishListHtml);
                    }else{
                        flag = true;
                        $('.pullup').html(data.msg);
                    }
                },'json');
            }

            $('.link_url').click(function () {
               var url = $(this).data('url');
               console.log('url',url);
               window.location.href = url;
            });
        </script>
    </body>
</html>

