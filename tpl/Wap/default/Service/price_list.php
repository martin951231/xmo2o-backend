<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="yes" name="apple-touch-fullscreen">
    <meta content="telephone=no" name="format-detection">
    <meta content="black" name="apple-mobile-web-app-status-bar-style">
    <meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1.0, minimum-scale=1.0,user-scalable=no">
    <meta name="baidu-site-verification" content="Rp99zZhcYy">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <link href="{pigcms{$static_path}service/css/basic.css" rel="stylesheet" type="text/css">
    <script src="{pigcms{$static_path}service/js/jquery-2.1.4.js"></script>
    <script src="{pigcms{$static_path}service/js/json2.js"></script>
    <script src="{pigcms{$static_path}service/js/basic.js"></script>
    <script src="{pigcms{$static_path}service/js/jquery.validate.min.js"></script>
    <script src="{pigcms{$static_path}service/js/md5.min.js"></script>
    <script src="{pigcms{$static_path}service/js/newcode-src.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
    <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key={pigcms{$config.google_map_ak}"></script>
    <script type="text/javascript">var is_google_map = '{pigcms{$config['google_map_ak']}';</script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
    </if>
    <title>需求详情</title>
    <link href="{pigcms{$static_path}service/css/quote.css" rel="stylesheet" type="text/css"/>
    <style>
        .orders_list li{ margin-top: 12px; padding: 0 10px; }
        .orders_list li .time{ color: 999999; font-size: 12px; padding-left: 18px; background: url({pigcms{$static_path}service/images/2ddzt_03.png) left center no-repeat; background-size: 11px; line-height: 1; margin-bottom: 8px; }
        .orders_list li .p18{ padding: 5px 0px 5px 22px; position: relative;  }
        .orders_list li .p18:after{ content: ''; height: 100%; width: 1px; background: #dcdcdc; position: absolute; left: 5px; top: 0px; z-index: 1 }

        .orders_list li .p18 .con{ background: #fff; padding: 13px; border-radius: 5px; box-shadow: 0px 0px 8px 2px #e3f0ec; position: relative; }
        .orders_list li .p18 .con:after{    content: "";width: 0;height: 0;display: block;border-top: 10px solid #ffffff;border-left: 8px solid transparent;border-right: 8px solid transparent;border-bottom: 0;position: absolute;top: 50%; left: -9px; margin-top: -5px; -webkit-transform: rotate(90deg);  }

        .orders_list li .p18:before{content: "";width: 8px;height: 8px;display: block; border-radius: 100%; background: #06c1ae; position: absolute; left: 2px; top: 50%; margin-top: -4px; z-index: 2}
        .orders_list li .p18 .con h2{ color: #06c1ae; font-size: 14px; font-weight: bold; padding-bottom: 8px; }

        .public {
            height: 44px;
            line-height: 44px;
            background: #06c1ae;
            color: #fff;
            width: 100%;
            top: 0px;
            left: 0px;
            z-index: 880;
        }
        .public .content {
            text-align: center;
            font-size: 16px;
        }
        .public .return {
            position: absolute;
            width: 50px;
            height: 44px;
            left: 0px;
            top: 0px;
        }
        .layermbtn span {
            display: inline-grid;
        }
        .refresh {
            position: absolute;
            right: 30px;
            bottom: 68px;
            width: 40px;
            height: 40px;
            background: url({pigcms{$static_path}/images/sxt.png) center no-repeat;
            background-size: 40px;
        }
        .refresh_buy {
            position: absolute;
            right: 30px;
            bottom: 30px;
            width: 40px;
            height: 40px;
            background: url({pigcms{$static_path}/images/sxt.png) center no-repeat;
            background-size: 40px;
        }
        .map_tip_view {
            position: absolute;
            left: 25%;
            top: 24px;
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 8px solid white;
        }
        .BMapLabel {
            left: -30px !important;
            top: -30px !important;
        }

        .js_quote_box{
            display: -webkit-box; /* 老版本语法: Safari, iOS, Android browser, older WebKit browsers. */
            display: -moz-box; /* 老版本语法: Firefox (buggy) */
            display: -ms-flexbox; /* 混合版本语法: IE 10 */
            display: -webkit-flex; /* 新版本语法: Chrome 21+ */
            display: flex; /* 新版本语法: Opera 12.1, Firefox 22+ */
            -webkit-box-pack: justify;
            -moz-justify-content: space-between;
            -webkit-justify-content: space-between;
            justify-content: space-between;
            padding-right: 0;
        }
        .js_quote_box .l-flex{
            -webkit-box-flex: 1;
            -moz-flex-grow: 1;
            -webkit-flex-grow: 1;
            flex-grow: 1;
            padding-right: 20px;
            width: 50%;
        }
    </style>
</head>
<body class="bg-gray android">
	<if condition="!$is_weixin_browser">
		<section class="public pageSliderHide">
			<a href=""><div class="return"></div></a>
			<div class="content">需求详情</div>
		</section>
	</if>

<div class="pagewrap" id="mainpage">
    <div class="clear"></div>
    <div class="main bg-gray padd-wrap1 quote-page-c">
        <div class="has-quote-service-box" style="margin-top: 5px;">

            <if condition="$publishInfo.catgory_type eq 1">
                <if condition="is_array($offer_list)">
                    <div class="tab-s1">
                        <div class="title "> 当前收到 <span class="count-num">{pigcms{$offer_count}</span> 个报价 </div>
                    </div>
                    <volist name="offer_list" id="vo">
                        <div class="quote-service">
                            <span class="num-s1 ">{pigcms{$key+1}</span>
                            <div class="ebox-1">
                                <div class="info-basic display-box check-realname">
                                    <div class="user_head_box"> <a href="{pigcms{:U('Service/provider_info',array('puid'=>$vo['p_uid']))}" class="user_head"> <img src="{pigcms{:replace_file_domain($vo['avatar'])}" class="preview_head_img"/> </a> </div>
                                    <div class="info">
                                        <div class="li-1"><span class="name">{pigcms{$vo.name}</span></div>
                                        <div class="contact">
                                            <p class="tel-info"><a href="tel:{pigcms{$vo.phone}" class="a-tel"> <i class="ico ico-tel-2"></i> {pigcms{$vo.phone} </a></p>
                                            <p class="tips-txt"><span class="tips-diary">我是{pigcms{$config.site_name}服务商</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="c-price-info">
                                    <div class="li-2">
                                        <div class="normal-info ">
                                            <div class="normal-price">给您参考报价：<div class="price-cell">{pigcms{$config['Currency_symbol']}<span class="price">{pigcms{$vo.price}</span>{pigcms{$config['Currency_txt']}</div></div>
                                            <div class="price-range-tips">价格可能上下浮动</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="botttom-oper">
                                <div class="tab-bar-s1">
                                    <if condition="$vo.status eq 1">
                                        <a href="javascript:;" onclick='offerPay("{pigcms{$vo.offer_id}")'>去付款</a>
                                    <elseif condition="$vo.status eq 2"/>
                                        <a href="javascript:;" onclick='offerRefund("{pigcms{$vo.offer_id}")'>退款</a>
                                    <elseif condition="$vo.status eq 3"/>
                                        <a href="javascript:;" onclick='offerConfirm("{pigcms{$vo.offer_id}")'>确认服务</a>
                                    <elseif condition="$vo.status eq 4"/>
                                        <a href="{pigcms{:U('Service/service_evaluate',array('offer_id'=>$vo['offer_id']))}">评价</a>
                                    <elseif condition="$vo.status eq 5"/>
                                        <a href="javascript:;">退款中</a>
                                    <elseif condition="$vo.status eq 6"/>
                                        <a href="javascript:;">退款成功已关闭</a>
                                    <elseif condition="$vo.status eq 7"/>
                                        <a href="javascript:;">已完成</a>
                                    </if>
                                    <a href="javascript:;" onclick="msgShow({pigcms{$vo.offer_id})"><i class="ico ico-msg"></i>留言<em class="total"><if condition="$vo.msg_count gt 0">（{pigcms{$vo.msg_count}）</if></em></a>
                                </div>

                                <div class="content-box add-message-box hidden" id="msg_list_{pigcms{$vo.offer_id}">
                                    <div class="message_add">
                                        <div class="form-list1">
                                            <div class="li textarea-box " checksuccess="false">
                                                <textarea class="form-control js_validate message_content" id="service_first_content_{pigcms{$vo.offer_id}" placeholder="请输入留言内容" name="content"></textarea>
                                            </div>
                                            <div class="btn-message-wrap"><button class="btn btn-blue2 js_btn_message_add" onclick='send_msg("{pigcms{$vo.offer_id}","{pigcms{$vo.p_uid}","{pigcms{:replace_file_domain($vo['avatar'])}","{pigcms{$vo.name}")' style="font-size:0.86rem;">发 送</button></div>
                                        </div>
                                    </div>
                                    <ul class="ul-list5" id="msg_list_content_{pigcms{$vo.offer_id}">
                                        <volist name="vo['msg_list']" id="msg_vo">
                                            <li class="li cell_li not-self">
                                                <if condition="$msg_vo.type eq 1">
                                                    <div class="ulli-3">
                                                        <div class="user_head_box">
                                                            <a href="javascript:;" class="user_head">
                                                                <img src="{pigcms{:replace_file_domain($user_session['avatar'])}">
                                                                <span class="radiushaed"></span>
                                                            </a>
                                                        </div>
                                                        <div class="cell-s1">
                                                            <div class="time">（{pigcms{$msg_vo.add_time|date="Y-m-d H:i:s",###}）</div>
                                                            <span class="name fc-orange">您</span>
                                                            给{pigcms{$vo.name}留言:
                                                        </div>
                                                    </div>
                                                <else/>
                                                    <div class="ulli-3">
                                                        <div class="user_head_box">
                                                            <a href="javascript:;" class="user_head">
                                                                <img src="{pigcms{:replace_file_domain($vo['avatar'])}"/>
                                                                <span class="radiushaed"></span>
                                                            </a>
                                                        </div>
                                                        <div class="cell-s1">
                                                            <div class="time">（{pigcms{$msg_vo.add_time|date="Y-m-d H:i:s",###}）</div>
                                                            <span class="name fc-orange">{pigcms{$vo.name}</span>
                                                            给您留言:
                                                        </div>
                                                    </div>

                                                </if>

                                                <div class="ulli-5">
                                                    {pigcms{$msg_vo.message}
                                                </div>
                                            </li>
                                        </volist>
                                        
                                    </ul>
                                </div>

                                <div class="content-box add-message-box hidden" id="refund_{pigcms{$vo.offer_id}">
                                    <div class="message_add">
                                        <div class="form-list1">
                                            <div class="li textarea-box " checksuccess="false">
                                                <textarea class="form-control js_validate message_content" id="refund_content_{pigcms{$vo.offer_id}" placeholder="请输入退款理由" name="content"></textarea>
                                            </div>
                                            <div class="btn-message-wrap"><a class="btn btn-blue2 js_btn_message_add" onclick='refunds("{pigcms{$vo.offer_id}")' style="font-size:0.86rem;">提交申请</a></div>
                                        </div>
                                    </div>
                                </div>

                            </div>


                        </div>
                    </volist>
                <else/>
                    <div class="no_record">
                        <div class="no_record_con">当前暂未收到报价……</div>
                    </div>
                </if>
            <else/>
                


                <if condition="is_array($offer_info) && $offer_info['phone']">
                    <div class="quote-service">
                        <div class="ebox-1">
                            <?php if($publishInfo['deliver_type'] == 1){ ?>
                                <?php if($show_deliver_user == 1){ ?>
                                <div class="info-basic display-box check-realname">
                                    <div class="user_head_box"> <a href="javascript:void(0)" class="user_head"> <img src="{pigcms{:replace_file_domain($offer_info['avatar'])}" class=" preview_head_img"> </a> </div>

                                    <div class="info">
                                        <div class="li-1"><span class="name">{pigcms{$offer_info.name}</span></div>
                                        <div class="contact">
                                            <p class="tel-info"><a href="tel:{pigcms{$offer_info.phone}" class="a-tel"> <i class="ico ico-tel-2"></i> {pigcms{$offer_info.phone} </a></p>
                                            <p class="tips-txt"><span class="tips-diary">我是 {pigcms{$config.site_name} 配送员</span></p>
                                            <if condition="$imUrl"><p class="tips-txt"><a href="{pigcms{$imUrl}">在线沟通</a></p></if>
                                        </div>
                                    </div>
                                </div>
                                <?php  } ?>
                            <?php } else{ ?>

                                <div class="info-basic display-box check-realname">
                                    <div class="user_head_box"> <a href="{pigcms{:U('Service/provider_info',array('puid'=>$offer_info['p_uid']))}" class="user_head"> <img src="{pigcms{:replace_file_domain($offer_info['avatar'])}" class=" preview_head_img"> </a> </div>

                                    <div class="info">
                                        <div class="li-1"><span class="name">{pigcms{$offer_info.nickname}</span></div>
                                        <div class="contact">
                                            <p class="tel-info"><a href="tel:{pigcms{$offer_info.phone}" class="a-tel"> <i class="ico ico-tel-2"></i> {pigcms{$offer_info.phone} </a></p>
                                            <p class="tips-txt"><span class="tips-diary">我是{pigcms{$config.site_name}服务商</span></p>
                                        </div>
                                    </div>
                                </div>
                                 
                            <?php  } ?>
                            
                            <div class="c-price-info">
                                <div class="li-2">

                                    <section class="g_details">
                                        <div class="orders_list">
                                            <ul>
                                                <volist name="offer_record_list" id="vo">
                                                    <li>
                                                        <div class="time">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div>
                                                        <div class="p18">
                                                            <div class="con">
                                                                <h2>{pigcms{$vo.remarks}</h2>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </volist>
                                            </ul>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </if>

            </if>
            

            <script>
                function offerRefund(offer_id){
                    if($("#refund_"+offer_id).is('.hidden')){
                        $("#refund_"+offer_id).removeClass('hidden');
                    }else{
                        $("#refund_"+offer_id).addClass('hidden');
                    }
                }


                function refunds(offer_id){
                    var refund_url = "{pigcms{:U('Service/offer_refund')}";
                    var reason = $("#refund_content_"+offer_id).val();
                    $.post(refund_url,{'offer_id':offer_id,'reason':reason},function(data){
                        if(data.error == 1){
                            layer.open({
                                content: data.msg
                                ,btn: ['确定']
                                ,yes: function(index){
                                    layer.close(index);
                                    window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                                }
                            });
                        }else{
                            layer.open({
                                content: data.msg
                                ,btn: ['确定']
                                ,yes: function(index){
                                    layer.close(index);
                                }
                            });
                        }
                    },'json');
                }

                function refund(offer_id){
                    var refund_url = "{pigcms{:U('Service/offer_refund')}";
                    var reason = $("#refund_content_"+offer_id).val();
                    $.post(refund_url,{'offer_id':offer_id,'reason':reason},function(data){
                        if(data.error == 1){
                            layer.open({
                                content: data.msg
                                ,btn: ['确定']
                                ,yes: function(index){
                                    layer.close(index);
                                    window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                                }
                            });
                        }else{
                            layer.open({
                                content: data.msg
                                ,btn: ['确定']
                                ,yes: function(index){
                                    layer.close(index);
                                }
                            });
                        }
                    },'json');
                }

                function offerConfirm(offer_id){

                    layer.open({
                        content: '确认服务后，平台将打款给服务商，确定服务商已服务？'
                        ,btn: ['确定', '取消']
                        ,yes: function(index){
                            var user_confirm_service_url = "{pigcms{:U('Service/user_confirm_service')}";
                            $.post(user_confirm_service_url,{'offer_id':offer_id},function(data){
                                if(data.error == 1){
                                    layer.open({
                                        content: data.msg
                                        ,btn: ['确定']
                                        ,yes: function(index){
                                            layer.close(index);
                                            window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                                        }
                                    });
                                }else{
                                    layer.open({
                                        content: data.msg
                                        ,btn: ['确定']
                                        ,yes: function(index){
                                            layer.close(index);
                                        }
                                    });
                                }
                            },'json');
                        }
                    });
                    
                }

                function offerPay(offer_id){
                    var offer_pay_url = "{pigcms{:U('Service/offer_pay')}";
                    layer.open({
                        content: '您确定要支付这个报价吗？'
                        ,btn: ['确定', '取消']
                        ,yes: function(index){
                            $.post(offer_pay_url,{'offer_id':offer_id},function(data){
                                if(data.error == 3){
                                    layer.open({
                                        content: data.msg
                                        ,btn: ['确定']
                                        ,yes: function(index){
                                            layer.close(index);
                                            location.href = "{pigcms{:U('My/recharge',array('label'=>'wap_service_'))}{pigcms{$_GET['publish_id']}";
                                        }
                                    });

                                }else if(data.error == 1){
                                    layer.open({
                                        content: data.msg
                                        ,btn: ['确定']
                                        ,yes: function(index){
                                            layer.close(index);
                                            window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                                        }
                                    });
                                }else if(data.error == 4){
                                    layer.open({
                                        content: data.msg
                                        ,btn: ['确定']
                                        ,yes: function(index){
                                            layer.close(index);
                                            window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                                        }
                                    });
                                }else{
                                    layer.open({
                                        content: data.msg
                                        ,btn: ['确定']
                                        ,yes: function(index){
                                            layer.close(index);
                                        }
                                    });
                                }
                            },'json');
                        }
                    });

                }

                function msgShow(offer_id){
                    if($("#msg_list_"+offer_id).is('.hidden')){
                        $("#msg_list_"+offer_id).removeClass('hidden');
                    }else{
                        $("#msg_list_"+offer_id).addClass('hidden');
                    }
                }

                function send_msg(offer_id,p_uid,avatar,nickname){
                    var message = $("#service_first_content_"+offer_id).val();
                    if(!message){
                        layer.open({
                            content: '留言内容不可以为空'
                            ,btn: ['确定']
                            ,yes: function(index){
                                layer.close(index);
                            }
                        });
                        return false;
                    }
                    var send_msg_url = "{pigcms{:U('Service/send_msg')}";
                    var offer_id = offer_id;
                    var publish_id = "{pigcms{$publishInfo['publish_id']}";
                    var uid = "{pigcms{$user_session['uid']}";
                    var p_uid = p_uid;
                    var type = 1;

                    $.post(send_msg_url,{'offer_id':offer_id,'publish_id':publish_id,'uid':uid,'p_uid':p_uid,'type':type,'message':message},function(data){
                        if(data.error == 1){
                            layer.open({
                                content: data.msg
                                ,btn: ['确定']
                                ,yes: function(index){
                                    layer.close(index);
                                }
                            });
                            $("#msg_list_content_"+offer_id).prepend('<li class="li cell_li not-self"> <div class="ulli-3"> <div class="user_head_box"> <a href="javascript:;" class="user_head"> <img src="'+"{pigcms{$user_session['avatar']}"+'"> <span class="radiushaed"></span> </a> </div> <div class="cell-s1"> <div class="time">（刚刚）</div> <span class="name fc-orange">您</span> 给'+nickname+'留言: </div> </div> <div class="ulli-5">'+message+'</div> </li>');
                            $("#service_first_content_"+offer_id).val('');
                        }else{
                            layer.open({
                                content: data.msg
                                ,btn: ['确定']
                                ,yes: function(index){
                                    layer.close(index);
                                }
                            });
                        }
                    },'json');
                }

            </script>

            <div class="onekey-offer"></div>
        </div>

        <!--标题 -->
        <div class="tab-s2 tab-s3">
            <div class="title js_demandtplSit"> 您的 <span class="fc-orange">{pigcms{$publishInfo.cat_name}</span> 需求详情 </div>
        </div>

        <!-- 需求详情 -->

        <if condition="$publishInfo.catgory_type eq 1">
            <div class="ebox-1 demandtpl-wrap demand-info-c s3 demandtpl-wrap-level" id="needStatus">
                <i class="ico ico-state">已结束</i>
                <div class="content-box">
                    <ul class="ul-list1 ul-list3 js_demandtpl_list_Inner" needid="326621">
                        <volist name="publishInfo['cat_field']" id="vo">
                            <if condition="$vo['type'] eq 6">
                                <li class="li">
                                    <div class="ulli ulli-6">{pigcms{$vo.alias_name}：</div>
                                    <div class="ulli ulli-flex1">{pigcms{$vo.value.address}</div>
                                </li>
                            <elseif condition="$vo['type'] eq 3"/>
                                <li class="li" style="width: 100%;">
                                    <div class="ulli ulli-6">{pigcms{$vo.alias_name}：</div>
                                    <div class="ulli ulli-flex1">
                                        <volist name="vo.value" id="vvo">
                                            <span style="display:inline; padding-right: 15px;" class="">{pigcms{$vvo}</span>
                                        </volist>
                                    </div>
                                    
                                </li>
                            <elseif condition="$vo['type'] eq 2"/>
                                <li class="li">
                                    <div class="ulli ulli-6">{pigcms{$vo.alias_name}：</div>
                                    <if condition="$vo['value'] eq 'inputdesc'">
                                        <div class="ulli ulli-flex1">{pigcms{$vo.desc}</div>
                                    <elseif condition="$vo['value'] eq 'time'"/>
                                        <div class="ulli ulli-flex1">{pigcms{$vo.date}{pigcms{$vo.minute}</div>
                                    <else/>
                                        {pigcms{$vo.value}
                                    </if>
                                </li>
                            <elseif condition="$vo['type'] eq 4"/>
                                <li class="li">
                                    <div class="ulli ulli-6">{pigcms{$vo.alias_name}：</div>
                                    <div class="ulli ulli-flex1">{pigcms{$vo.value.time_start} {pigcms{$vo.value.time_end}</div>
                                </li>
                            <elseif condition="$vo['type'] eq 7"/>
                                <li class="li">
                                    <div class="ulli ulli-6">{pigcms{$vo.alias_name}：</div>
                                    <div class="ulli ulli-flex1">
                                        起点：<span class="orange js_need_address address_start">{pigcms{$vo['value']['address_start']}</span>
                                        <br/>到达点：<span class="orange js_need_address address_end">{pigcms{$vo['value']['address_end']}</span>
                                        <div class="drive-distance">
                                            <p>
                                                <i class="ico ico-drive"></i> 参考行车距离：
                                                <span id="service_need_distance"></span> <br> (平台距离按<if condition="$config['service_is_riding_distance']">骑行距离<else/>直线距离</if>计算)
                                            </p>
                                        </div>
                                    </div>
                                </li>
                                <li class="js_coordinate_ele">
                                    <div style="width: 100%;height: 200px;overflow: hidden;margin:0;font-family:"微软雅黑";" id="allmap"></div>
                                </li>
                            <else/>
                                <li class="li">
                                    <div class="ulli ulli-6">{pigcms{$vo.alias_name}：</div>
                                    <div class="ulli ulli-flex1">{pigcms{$vo.value}</div>
                                </li>
                            </if>
                        </volist>
                        <li class="li">
                            <div class="ulli ulli-6">状态：</div>
                            <div class="ulli ulli-flex1">
                                <if condition="$publishInfo['status'] eq 1">已发布
                                    <elseif condition="$publishInfo['status'] eq 2"/>已付款待服务
                                    <elseif condition="$publishInfo['status'] eq 3"/>已服务待确认
                                    <elseif condition="$publishInfo['status'] eq 4"/>订单完成
                                    <elseif condition="$publishInfo['status'] eq 5"/>申请退款
                                    <elseif condition="$publishInfo['status'] eq 6"/>退款成功
                                    <elseif condition="$publishInfo['status'] eq 7"/>评价成功
                                    <elseif condition="$publishInfo['status'] eq 8"/>等待取货
                                    <elseif condition="$publishInfo['status'] eq 9"/>配送中
                                    <elseif condition="$publishInfo['status'] eq 10"/>已暂停报价
                                    <elseif condition="$publishInfo['status'] eq 11"/>已经过期
                                    <elseif condition="$publishInfo['status'] eq 12"/>过期退款
                                    <elseif condition="$publishInfo['status'] eq 13"/>退款失败
                                </if>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <if condition="$publishInfo['status'] eq 1">
                <div class="quote-price-oper ebox-1 js-goto js_quote_box">
                    <div class="l-flex">
                        <a  style=" background: #06c1ae; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange" onclick="cancel_publish()" href="javascript:;">关闭需求</a> 
                    </div>
                </div>
            </if>

            <script>
                function cancel_publish(){
                    var publish_id = "{pigcms{$_GET['publish_id']}";
                    var cancel_publish_url = "{pigcms{:U('Service/cancel_publish')}";
                    //询问框
                    layer.open({
                        content: '您确定要关闭需求么？关闭后您将不再会接收到报价。'
                        ,btn: ['确定', '取消']
                        ,yes: function(index){
                            layer.close(index);
                            location.reload();
                            $.post(cancel_publish_url,{'publish_id':publish_id},function(data){
                                // if(data.error == 1){
                                //     layer.open({
                                //         content: data.msg
                                //         ,btn: ['确定']
                                //         ,yes: function(index){
                                //             location.href = location.href;
                                //         }
                                //     });
                                // }else{
                                //     layer.open({
                                //         content: data.msg
                                //         ,skin: 'msg'
                                //         ,time: 2
                                //     });
                                // }
                                window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                            },'json');
                        }
                    });
                }
            </script>

            <script type="text/javascript">
                if(typeof(is_google_map) != "undefined"){
                    var p1 = {lng:parseFloat("{pigcms{$publishInfo['address_start_lng']}"), lat:parseFloat("{pigcms{$publishInfo['address_start_lat']}")};
                    var p2 ={lng:parseFloat("{pigcms{$publishInfo['address_end_lng']}"), lat:parseFloat("{pigcms{$publishInfo['address_end_lat']}")};
                    var directionsService = new google.maps.DirectionsService();
                    var directionsDisplay = new google.maps.DirectionsRenderer();
                    var map = new google.maps.Map(document.getElementById('allmap'),{
                        center: p1,
                        zoom:16,
                        streetViewControl:false,
                        mapTypeControl:false
                    });
                    directionsDisplay.setMap(map);
                    calcRoute();
                    function calcRoute() {
                        var request = {
                            origin: p1,
                            destination: p2,
                            travelMode: 'DRIVING'
                        };
                        directionsService.route(request, function (result, status) {
                            if (status == 'OK') {
                                directionsDisplay.setDirections(result);
                            }
                        });
                    }
                }else{
                    var map = new BMap.Map("allmap");
                    var p1 = new BMap.Point("{pigcms{$publishInfo['address_start_lng']}", "{pigcms{$publishInfo['address_start_lat']}");
                    var p2 = new BMap.Point("{pigcms{$publishInfo['address_end_lng']}", "{pigcms{$publishInfo['address_end_lat']}");
                    var output = "";
                    var searchComplete = function (results) {
                        var plan = results.getPlan(0);
                        output = plan.getDistance(true);
                    }
                    var transit = new BMap.DrivingRoute(map, {
                        renderOptions: {map: map}, onSearchComplete: searchComplete, onPolylinesSet: function () {
                            $("#service_need_distance").html(output);
                        }
                    });
                    transit.search(p1, p2);
                }
            </script>

        <elseif condition="$publishInfo.catgory_type eq 2"/>
            <div class="ebox-1 demandtpl-wrap demand-info-c s3 demandtpl-wrap-level" id="needStatus">
                <div class="content-box">
                    <ul class="ul-list1 ul-list3 js_demandtpl_list_Inner" needid="326621">
                        <li class="li">
                            <div class="ulli ulli-6">商品要求：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.goods_remarks}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">购买类型：</div>
                            <div class="ulli ulli-flex1"><if condition="$cat_field_info.buy_type eq 1">就近购买<else/>指定地址</if></div>
                        </li>
                        <if condition="$cat_field_info.buy_type eq 2">
                            <li class="li">
                                <div class="ulli ulli-6">指定地址：</div>
                                <div class="ulli ulli-flex1">{pigcms{$cat_field_info.address}</div>
                            </li>
                        </if>

                        <li class="li">
                            <div class="ulli ulli-6">送达地址：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.end_adress_name}</div>
                        </li>
                      <if condition="$cat_field_info['arrival_time_info']">
                        <li class="li">
                            <div class="ulli ulli-6">送达时间：</div>
                                <div class="ulli ulli-flex1">预计在 {pigcms{$cat_field_info.arrival_time_info} 送达</div>
                        </li>
                      </if>
                        <li class="li">
                            <div class="ulli ulli-6">预估商品费用：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.estimate_goods_price}</div>
                        </li>

                        <if condition="$publishInfo.weight_tip neq ''">
                            <li class="li">
                                <div class="ulli ulli-6">物品重量：</div>
                                <div class="ulli ulli-flex1">{pigcms{$publishInfo.weight_tip}</div>
                            </li>
                            <else/>
                            <li class="li">
                                <div class="ulli ulli-6">物品重量：</div>
                                <div class="ulli ulli-flex1">{pigcms{$cat_field_info.weight|default='0.00'}<if condition="$cat_field_info.weight_info eq ''">（KG）</if></div>
                            </li>
                        </if>
                        <li class="li">
                            <div class="ulli ulli-6">重量费用：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.weight_price}</div>
                        </li>


                        <li class="li">
                            <div class="ulli ulli-6">基础配送费：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.basic_distance_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">超出基础配送距离费用：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.distance_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">优惠券抵扣:</div>
                            <div class="ulli ulli-flex1">{pigcms{$publishInfo.system_coupon_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">小费：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.tip_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">总价：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.total_price}</div>
                        </li>
                        <if condition="$cat_field_info['img']">
                            <li class="li">
                                <div class="ulli ulli-6">商品图片：</div>
                                <div class="ulli ulli-flex1">
                                    <volist name="cat_field_info['img']" id="vo">
                                        <a href="javascript:void(0);" onclick='showImg("{pigcms{$vo}")'><img src="{pigcms{$vo}" style="float:left; padding-left: 2px; width: 70px; height: 70px;margin-bottom:7px;margin-left: 7px;" alt=""></a>
                                    </volist>
                                </div>
                            </li>
                        </if>

                        <li class="li">
                            <div class="ulli ulli-6">状态：</div>
                            <div class="ulli ulli-flex1">
                                <if condition="$publishInfo['status'] eq 1">待支付
                                    <elseif condition="$publishInfo['status'] eq 2"/>待接单
                                    <elseif condition="$publishInfo['status'] eq 3"/>已服务
                                    <elseif condition="$publishInfo['status'] eq 4"/>订单完成
                                    <elseif condition="$publishInfo['status'] eq 5"/>申请退款
                                    <elseif condition="$publishInfo['status'] eq 6"/>退款成功
                                    <elseif condition="$publishInfo['status'] eq 7"/>评价成功
                                    <elseif condition="$publishInfo['status'] eq 8"/>等待取货
                                    <elseif condition="$publishInfo['status'] eq 9"/>配送中
                                    <elseif condition="$publishInfo['status'] eq 10"/>订单已取消
                                    <elseif condition="$publishInfo['status'] eq 11"/>已经过期
                                    <elseif condition="$publishInfo['status'] eq 12"/>过期退款
                                    <elseif condition="$publishInfo['status'] eq 13"/>退款失败
                                </if>
                            </div>
                        </li>
                        <php>if ($cat_field_info['buy_type'] == 2 && $publishInfo['status'] != 8 && $publishInfo['status'] != 9) {</php>
                            <li class="li">
                                <div class="ulli ulli-6">参考行车：</div>
                                <div class="ulli ulli-flex1">
                                    <p>
                                        <i class="ico ico-drive"></i> 参考行车距离：
                                        <span id="service_need_distance"></span>
                                        <br> <span id="service_distance_tips">(平台距离按<if condition="$config['service_is_riding_distance']">骑行距离<else/>直线距离</if>计算)</span>
                                    </p>
                                </div>
                            </li>
                            <li class="js_coordinate_ele">
                                <div style="width: 100%;height: 200px;overflow: hidden;margin:0;font-family:"微软雅黑";" id="allmap"></div>
                            </li>
                        <php>} else if($cat_field_info['buy_type'] == 2) {</php>
                            <li class="li">
                                <div class="ulli ulli-6">实时定位：</div>
                                <div class="ulli ulli-flex1">
                                    <p>
                                        <i class="ico ico-drive"></i> 距离：
                                        <span id="service_need_distance"></span>
                                        <br> <span id="service_distance_tips">(平台距离按<if condition="$config['service_is_riding_distance']">骑行距离<else/>直线距离</if>计算)</span>
                                    </p>
                                </div>
                            </li>

                            <li class="li">
                                <p id="map_tip"></p>
                            </li>
                            <li class="js_coordinate_ele">
                                <div style="width: 100%;height: 230px;overflow: hidden;margin:0;font-family:"微软雅黑";" id="allmap"></div>
                                <div class="refresh_buy fr"></div>
                            </li>
                        <php>}</php>
                    </ul>
                </div>
            </div>

            <if condition="$publishInfo['status'] eq 1">
                <if condition="!$hidePay">
                <div class="quote-price-oper ebox-1 js-goto js_quote_box">
                    <div class="l-flex">
                        <a  style=" background: #06c1ae; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange" data-id="{pigcms{$publishInfo['publish_id']}"  onclick="payMoney(this)" href="javascript:void(0);">去支付</a>
                    </div>
                </div>
                </if>
                <elseif condition="$publishInfo['status'] eq 2"/>
                <div class="quote-price-oper ebox-1 js-goto js_quote_box">
                    <div class="l-flex">
                        <a  style=" background: #999c9e; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange" data-id="{pigcms{$publishInfo['publish_id']}"  onclick="order_refund(this)" href="javascript:void(0);">取消订单</a>
                    </div>
                </div>
            </if>

            <php>if ($cat_field_info['buy_type'] == 2 && ($publishInfo['status'] == 8 || $publishInfo['status'] == 9)){</php>
                <script type="text/javascript">
                    if(typeof(is_google_map) != "undefined" && "{pigcms{$cat_field_info['buy_type']}"== 2){

                        $(document).ready(function(){
                            line();
                            $('.refresh_buy').click(function(){
                                line();
                            });
                            setInterval("line()", 20000);//1000为1秒钟
                        });

                        function line()
                        {
                            $.get("{pigcms{:U('Service/line', array('order_id' => $publishInfo['publish_id']))}", function(response){
                                if (response.err_code == false) {
                                    // 显示距离
                                    if (response.destance_sum) {
                                        $('#service_need_distance').html(response.destance_sum);
                                    }
                                    // 显示信息
                                    if (response.tip) {
                                        $('#map_tip').html(response.tip);
                                    }
                                    var lines = response.lines, center = response.center, points = response.points;
                                    var map = new google.maps.Map(document.getElementById('allmap'), {
                                        mapTypeControl:false,
                                        zoom: 13,
                                        center: {lng:parseFloat(center.lng),lat:parseFloat(center.lat)}
                                    });
                                    var point = {lng:parseFloat(center.lng), lat:parseFloat(center.lat)};
                                    var from_point = {lng:parseFloat(points.from_site.lng), lat:parseFloat(points.from_site.lat)};
                                    var aim_point ={lng:parseFloat(points.aim_site.lng), lat:parseFloat(points.aim_site.lat)};

                                    var deliverIcon = new google.maps.Marker({
                                        position: point,
                                        map:map,
                                        icon:  {url:'{pigcms{$static_path}/images/map/deliver_pos.png', anchor: new google.maps.Point(16, 16)}
                                    });
                                    if (response.destance_sum) {
                                        var label_txt = response.destance_sum;
                                        var infowindow = new google.maps.InfoWindow({content: label_txt}); //创建一个InfoWindow
                                        infowindow.open(map, deliverIcon); //把这个infoWindow绑定在选定的marker上面
                                        //使用谷歌地图定义的事件，给这个marker添加点击事件
                                        google.maps.event.addListener(deliverIcon, "click", function(){
                                            infowindow.open(map,deliverIcon);
                                        });
                                    }

                                    var storeIcon = new google.maps.Marker({
                                        position: from_point,
                                        map:map,
                                        icon:  {url:'{pigcms{$static_path}/images/map/store_pos.png', anchor: new google.maps.Point(16, 16)}
                                    });

                                    var userIcon =  new google.maps.Marker({
                                        position: aim_point,
                                        map:map,
                                        icon:   {url:'{pigcms{$static_path}/images/map/my_pos.png', anchor: new google.maps.Point(32, 32)}
                                    });

                                    if (lines != null) {
                                        var polygonArr = [];
                                        for (var i in lines) {
                                            polygonArr.push({lng:lines[i].lng, lat:lines[i].lat});
                                        }
                                        var flightPath = new google.maps.Polyline({
                                            path: polygonArr,
                                            geodesic: true,
                                            strokeColor: 'green',
                                            strokeOpacity: 0.5,
                                            strokeWeight: 2
                                        });

                                        flightPath.setMap(map);

                                        if (response.status < 4) {
                                            var flightPath = new google.maps.Polyline({
                                                path:[point, from_point],
                                                geodesic: true,
                                                strokeColor: 'red',
                                                strokeOpacity: 0.5,
                                                strokeWeight: 2
                                            });
                                            flightPath.setMap(map);
                                        } else {
                                            var flightPath = new google.maps.Polyline({
                                                path:[point, aim_point],
                                                geodesic: true,
                                                strokeColor: 'red',
                                                strokeOpacity: 0.5,
                                                strokeWeight: 2
                                            });
                                            flightPath.setMap(map);

                                        }
                                    } else if (response.status < 4) {
                                        var flightPath = new google.maps.Polyline({
                                            path:[point, from_point, aim_point],
                                            geodesic: true,
                                            strokeColor: 'red',
                                            strokeOpacity: 0.5,
                                            strokeWeight: 2
                                        });
                                        flightPath.setMap(map);
                                    } else {
                                        var flightPath = new google.maps.Polyline({
                                            path:[from_point, point, aim_point],
                                            geodesic: true,
                                            strokeColor: 'red',
                                            strokeOpacity: 0.5,
                                            strokeWeight: 2
                                        });
                                        flightPath.setMap(map);
                                    }
                                }
                            }, 'json');
                        }
                    }else if("{pigcms{$cat_field_info['buy_type']}"== 2){
                        $(document).ready(function(){
                            line();
                            $('.refresh_buy').click(function(){
                                line();
                            });
                            setInterval("line()", 20000);//1000为1秒钟
                        });

                        function line()
                        {
                            $.get("{pigcms{:U('Service/line', array('order_id' => $publishInfo['publish_id']))}", function(response){
                                if (response.err_code == false) {
                                    // 显示距离
                                    if (response.destance_sum) {
                                        $('#service_need_distance').html(response.destance_sum);
                                    }
                                    // 显示信息
                                    if (response.tip) {
                                        $('#map_tip').html(response.tip);
                                    }
                                    var lines = response.lines, center = response.center, points = response.points;
                                    var map = new BMap.Map("allmap", {"enableMapClick":false});
                                    var point = new BMap.Point(center.lng, center.lat);
                                    var from_point = new BMap.Point(points.from_site.lng, points.from_site.lat);
                                    var aim_point = new BMap.Point(points.aim_site.lng, points.aim_site.lat);
                                    map.centerAndZoom(point, 13);
                                    map.enableScrollWheelZoom();

                                    var deliverIcon = new BMap.Icon("{pigcms{$static_path}/images/map/deliver_pos.png", new BMap.Size(22,60));
                                    var deliver_marker = new BMap.Marker(point, {icon:deliverIcon});
                                    map.addOverlay(deliver_marker);
                                    if (response.destance_sum) {
                                        var label_txt = response.destance_sum;
                                        var label = new BMap.Label(label_txt,{offset:new BMap.Size(20,-10)});
                                        label.setStyle({
                                            border: "none",
                                            boxShadow: "0px 1px 10px #FFC1B9",
                                            borderRadius: "15px",
                                            color : "black",
                                            fontSize : "12px",
                                            height : "24px",
                                            padding: "2px 8px",
                                            lineHeight : "20px",
                                            fontFamily:"微软雅黑"
                                        });
                                        deliver_marker.setLabel(label);
                                        setTimeout(function () {
                                            $('.BMapLabel').append("<div class='map_tip_view'></div>");
                                        }, 200);
                                    }

                                    var storeIcon = new BMap.Icon("{pigcms{$static_path}/images/map/store_pos.png", new BMap.Size(22,60));
                                    map.addOverlay(new BMap.Marker(from_point, {icon:storeIcon}));
                                    var userIcon = new BMap.Icon("{pigcms{$static_path}/images/map/my_pos.png", new BMap.Size(60,60));
                                    map.addOverlay(new BMap.Marker(aim_point, {icon:userIcon}));

                                    if (lines != null) {
                                        var polygonArr = [];
                                        for (var i in lines) {
                                            polygonArr.push(new BMap.Point(lines[i].lng, lines[i].lat));
                                        }
                                        var polyline = new BMap.Polyline(polygonArr, {strokeColor:"green", strokeWeight:2, strokeOpacity:0.5}); //创建折线
                                        map.addOverlay(polyline); //增加折线

                                        if (response.status < 4) {
                                            var polyline = new BMap.Polyline([point, from_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                            map.addOverlay(polyline);
                                        } else {
                                            var polyline = new BMap.Polyline([point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                            map.addOverlay(polyline);
                                        }
                                    } else if (response.status < 4) {
                                        var polyline = new BMap.Polyline([point, from_point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                        map.addOverlay(polyline);
                                    } else {
                                        var polyline = new BMap.Polyline([from_point, point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                        map.addOverlay(polyline);
                                    }
                                }
                            }, 'json');
                        }
                    }
                </script>
            <php>}else if($cat_field_info['buy_type'] == 2){</php>
                <script type="text/javascript">
                    if(typeof(is_google_map) != "undefined" && "{pigcms{$cat_field_info['buy_type']}"== 2){
                        var p1 = {lng:parseFloat("{pigcms{$cat_field_info['address_lng']}"), lat:parseFloat("{pigcms{$cat_field_info['address_lat']}")};
                        var p2 ={lng:parseFloat("{pigcms{$cat_field_info['end_adress_lng']}"), lat:parseFloat("{pigcms{$cat_field_info['end_adress_lat']}")};
                        var directionsService = new google.maps.DirectionsService();
                        var directionsDisplay = new google.maps.DirectionsRenderer();
                        var map = new google.maps.Map(document.getElementById('allmap'),{
                            center: p1,
                            zoom:16,
                            streetViewControl:false,
                            mapTypeControl:false
                        });
                        directionsDisplay.setMap(map);
                        calcRoute();
                        function calcRoute() {
                            var request = {
                                origin: p1,
                                destination: p2,
                                travelMode: 'DRIVING'
                            };
                            directionsService.route(request, function (result, status) {
                                if (status == 'OK') {
                                    directionsDisplay.setDirections(result);
                                    $('#service_distance_tips').html('');
                                    $('#service_need_distance').html(result.routes[0].legs[0].distance.text+','+result.routes[0].legs[0].duration.text);
                                }
                            });
                        }
                    }else if("{pigcms{$cat_field_info['buy_type']}"== 2){
                        var map = new BMap.Map("allmap");
                        var p1 = new BMap.Point("{pigcms{$cat_field_info['address_lng']}", "{pigcms{$cat_field_info['address_lat']}");
                        var p2 = new BMap.Point("{pigcms{$cat_field_info['end_adress_lng']}", "{pigcms{$cat_field_info['end_adress_lat']}");
                        var output = "";
                        var searchComplete = function (results) {
                            var plan = results.getPlan(0);
                            output = plan.getDistance(true);
                        }
                        var transit = new BMap.DrivingRoute(map, {
                            renderOptions: {map: map},
                            onSearchComplete: searchComplete,
                            onPolylinesSet: function () {
                                $("#service_need_distance").html(output);
                            }
                        });
                        transit.search(p1, p2);
                    }
                </script>
            <php>}</php>
        <elseif condition="$publishInfo.catgory_type eq 3"/>
            <div class="ebox-1 demandtpl-wrap demand-info-c s3 demandtpl-wrap-level" id="needStatus">
                <div class="content-box">
                    <ul class="ul-list1 ul-list3 js_demandtpl_list_Inner" needid="326621">
                        <if condition="$cat_field_info['goods_catgory']">
                        <li class="li">
                            <div class="ulli ulli-6">商品分类：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.goods_catgory}</div>
                        </li>
                        </if>
                        <if condition="$publishInfo.weight_tip neq ''">
                            <li class="li">
                                <div class="ulli ulli-6">物品重量：</div>
                                <div class="ulli ulli-flex1">{pigcms{$publishInfo.weight_tip}</div>
                            </li>
                            <else/>
                            <li class="li">
                                <div class="ulli ulli-6">物品重量：</div>
                                <div class="ulli ulli-flex1">{pigcms{$cat_field_info.weight|default='0.00'}<if condition="$cat_field_info.weight_info eq ''">（KG）</if></div>
                            </li>
                        </if>
                        <if condition="$cat_field_info.price neq ''">
                            <li class="li">
                                <div class="ulli ulli-6">物品价值：</div>
                                <div class="ulli ulli-flex1">{pigcms{$cat_field_info.price}</div>
                            </li>
                        </if>
                        <li class="li">
                            <div class="ulli ulli-6">取件地址：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.start_adress_name}</div>
                        </li>
                        <if condition="$cat_field_info['end_adress_name']">
                        <li class="li">
                            <div class="ulli ulli-6">收货地址：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.end_adress_name}</div>
                        </li>
                        </if>
                        <li class="li">
                            <div class="ulli ulli-6">预计送达时间：</div>
                            <div class="ulli ulli-flex1"><if condition="$cat_field_info['fetch_time']">{pigcms{$cat_field_info.fetch_time}<else/>立即取件</if></div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">备注：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.remarks|default='无'}</div>
                        </li>
                        
                        <li class="li">
                            <div class="ulli ulli-6">重量费用：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.weight_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">基础配送费：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.basic_distance_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">超出基础配送费：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.distance_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">优惠券抵扣:</div>
                            <div class="ulli ulli-flex1">{pigcms{$publishInfo.system_coupon_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">小费：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.tip_price}</div>
                        </li>
                        <li class="li">
                            <div class="ulli ulli-6">总价：</div>
                            <div class="ulli ulli-flex1">{pigcms{$cat_field_info.total_price}</div>
                        </li>
                        <if condition="$cat_field_info['img']">
                            <li class="li">
                                <div class="ulli ulli-6">商品图片：</div>
                                <div class="ulli ulli-flex1">
                                    <volist name="cat_field_info['img']" id="vo">
                                        <a href="javascript:void(0);" onclick='showImg("{pigcms{$vo}")'><img src="{pigcms{$vo}" style="float:left; padding-left: 2px; border-radius:0px; width: 70px; height: 70px;margin-bottom:7px;margin-left: 7px;" alt=""></a>
                                    </volist>
                                </div>
                            </li>
                        </if>
                        <li class="li">
                            <div class="ulli ulli-6">状态：</div>
                            <div class="ulli ulli-flex1">
                                <if condition="$publishInfo['status'] eq 1" >待支付
                                <elseif condition="$publishInfo['status'] eq 2"/>
                                    <if condition="$publishInfo['is_voice'] AND $publishInfo['voice_status'] eq '10'">
                                        基础费用已支付，
                                    </if>
                                    待接单
                                <elseif condition="$publishInfo['status'] eq 3"/>已服务
                                <elseif condition="$publishInfo['status'] eq 4"/>订单完成
                                <elseif condition="$publishInfo['status'] eq 5"/>申请退款
                                <elseif condition="$publishInfo['status'] eq 6"/>退款成功
                                <elseif condition="$publishInfo['status'] eq 7"/>评价成功
                                <elseif condition="$publishInfo['status'] eq 8"/>等待取货
                                    <if condition="$publishInfo['is_voice']  AND $publishInfo['voice_status'] eq 1">
                                        ，尾款待支付
                                    </if>
                                <elseif condition="$publishInfo['status'] eq 9"/>配送中
                                <elseif condition="$publishInfo['status'] eq 10"/>订单已取消
                                <elseif condition="$publishInfo['status'] eq 11"/>已经过期
                                <elseif condition="$publishInfo['status'] eq 12"/>过期退款
                                <elseif condition="$publishInfo['status'] eq 13"/>退款失败
                                </if>
                            </div>
                        </li>


                        <php>if ($publishInfo['status'] != 8 && $publishInfo['status'] != 9) {</php>
                            <if condition="$cat_field_info['end_adress_lng']">
                            <li class="li">
                                <div class="ulli ulli-6">参考行车：</div>
                                <div class="ulli ulli-flex1">
                                    <p>
                                        <i class="ico ico-drive"></i> 参考行车距离：
                                        <span id="service_need_distance"></span>
                                        <br> <span id="service_distance_tips">(平台距离按<if condition="$config['service_is_riding_distance']">骑行距离<else/>直线距离</if>计算)</span>
                                    </p>
                                </div>
                            </li>
                            <li class="js_coordinate_ele">
                                <div style="width: 100%;height: 200px;overflow: hidden;margin:0;font-family:"微软雅黑";" id="allmap"></div>
                            </li>
                            </if>
                        <php>} else {</php>
                            <li class="li">
                                <div class="ulli ulli-6">实时定位：</div>
                                <div class="ulli ulli-flex1">
                                    <p>
                                        <i class="ico ico-drive"></i> 距离：
                                        <span id="service_need_distance"></span>
                                        <br> <span id="service_distance_tips">(平台距离按<if condition="$config['service_is_riding_distance']">骑行距离<else/>直线距离</if>计算)</span>
                                    </p>
                                </div>
                            </li>

                            <li class="li">
                                <p id="map_tip"></p>
                            </li>
                            <li class="js_coordinate_ele">
                                <div style="width: 100%;height: 230px;overflow: hidden;margin:0;font-family:"微软雅黑";" id="allmap"></div>
                                <div class="refresh fr"></div>
                            </li>
                        <php>}</php>
                        <php>if ($publishInfo['status'] == 8 || $publishInfo['status'] == 9){</php>
                            <script type="text/javascript">
                            if(typeof(is_google_map) != "undefined"){

                                $(document).ready(function(){
                                    line();
                                    $('.refresh').click(function(){
                                        line();
                                    });
                                    setInterval("line()", 20000);//1000为1秒钟
                                });

                                function line()
                                {
                                    $.get("{pigcms{:U('Service/line', array('order_id' => $publishInfo['publish_id']))}", function(response){
                                        if (response.err_code == false) {
                                            // 显示距离
                                            if (response.destance_sum) {
                                                $('#service_need_distance').html(response.destance_sum);
                                            }
                                            // 显示信息
                                            if (response.tip) {
                                                $('#map_tip').html(response.tip);
                                            }
                                            var lines = response.lines, center = response.center, points = response.points;
                                            var map = new google.maps.Map(document.getElementById('allmap'), {
                                                mapTypeControl:false,
                                                zoom: 13,
                                                center: {lng:parseFloat(center.lng),lat:parseFloat(center.lat)}
                                            });
                                            var point = {lng:parseFloat(center.lng), lat:parseFloat(center.lat)};
                                            var from_point = {lng:parseFloat(points.from_site.lng), lat:parseFloat(points.from_site.lat)};
                                            var aim_point ={lng:parseFloat(points.aim_site.lng), lat:parseFloat(points.aim_site.lat)};

                                            var deliverIcon = new google.maps.Marker({
                                                position: point,
                                                map:map,
                                                icon:  {url:'{pigcms{$static_path}/images/map/deliver_pos.png', anchor: new google.maps.Point(16, 16)}
                                            });
                                            if (response.destance_sum) {
                                                var label_txt = response.destance_sum;
                                                var infowindow = new google.maps.InfoWindow({content: label_txt}); //创建一个InfoWindow
                                                infowindow.open(map, deliverIcon); //把这个infoWindow绑定在选定的marker上面
                                                //使用谷歌地图定义的事件，给这个marker添加点击事件
                                                google.maps.event.addListener(deliverIcon, "click", function(){
                                                    infowindow.open(map,deliverIcon);
                                                });
                                            }

                                            var storeIcon = new google.maps.Marker({
                                                position: from_point,
                                                map:map,
                                                icon:  {url:'{pigcms{$static_path}/images/map/store_pos.png', anchor: new google.maps.Point(16, 16)}
                                            });

                                            var userIcon =  new google.maps.Marker({
                                                position: aim_point,
                                                map:map,
                                                icon:   {url:'{pigcms{$static_path}/images/map/my_pos.png', anchor: new google.maps.Point(32, 32)}
                                            });

                                            if (lines != null) {
                                                var polygonArr = [];
                                                for (var i in lines) {
                                                    polygonArr.push({lng:lines[i].lng, lat:lines[i].lat});
                                                }
                                                var flightPath = new google.maps.Polyline({
                                                    path: polygonArr,
                                                    geodesic: true,
                                                    strokeColor: 'green',
                                                    strokeOpacity: 0.5,
                                                    strokeWeight: 2
                                                });

                                                flightPath.setMap(map);

                                                if (response.status < 4) {
                                                    var flightPath = new google.maps.Polyline({
                                                        path:[point, from_point],
                                                        geodesic: true,
                                                        strokeColor: 'red',
                                                        strokeOpacity: 0.5,
                                                        strokeWeight: 2
                                                    });
                                                    flightPath.setMap(map);
                                                } else {
                                                    var flightPath = new google.maps.Polyline({
                                                        path:[point, aim_point],
                                                        geodesic: true,
                                                        strokeColor: 'red',
                                                        strokeOpacity: 0.5,
                                                        strokeWeight: 2
                                                    });
                                                    flightPath.setMap(map);

                                                }
                                            } else if (response.status < 4) {
                                                var flightPath = new google.maps.Polyline({
                                                    path:[point, from_point, aim_point],
                                                    geodesic: true,
                                                    strokeColor: 'red',
                                                    strokeOpacity: 0.5,
                                                    strokeWeight: 2
                                                });
                                                flightPath.setMap(map);
                                            } else {
                                                var flightPath = new google.maps.Polyline({
                                                    path:[from_point, point, aim_point],
                                                    geodesic: true,
                                                    strokeColor: 'red',
                                                    strokeOpacity: 0.5,
                                                    strokeWeight: 2
                                                });
                                                flightPath.setMap(map);
                                            }
                                        }
                                    }, 'json');
                                }
                            }else{
                                $(document).ready(function(){
                                    line();
                                    $('.refresh').click(function(){
                                        line();
                                    });
                                    setInterval("line()", 20000);//1000为1秒钟
                                });

                                function line()
                                {
                                    $.get("{pigcms{:U('Service/line', array('order_id' => $publishInfo['publish_id']))}", function(response){
                                        if (response.err_code == false) {
                                            // 显示距离
                                            if (response.destance_sum) {
                                                $('#service_need_distance').html(response.destance_sum);
                                            }
                                            // 显示信息
                                            if (response.tip) {
                                                $('#map_tip').html(response.tip);
                                            }
                                            var lines = response.lines, center = response.center, points = response.points;
                                            var map = new BMap.Map("allmap", {"enableMapClick":false});
                                            var point = new BMap.Point(center.lng, center.lat);
                                            var from_point = new BMap.Point(points.from_site.lng, points.from_site.lat);
                                            var aim_point = new BMap.Point(points.aim_site.lng, points.aim_site.lat);
                                            map.centerAndZoom(point, 13);
                                            map.enableScrollWheelZoom();

                                            var deliverIcon = new BMap.Icon("{pigcms{$static_path}/images/map/deliver_pos.png", new BMap.Size(22,60));
                                            var deliver_marker = new BMap.Marker(point, {icon:deliverIcon});
                                            map.addOverlay(deliver_marker);
                                            if (response.destance_sum) {
                                                var label_txt = response.destance_sum;
                                                var label = new BMap.Label(label_txt,{offset:new BMap.Size(20,-10)});
                                                label.setStyle({
                                                    border: "none",
                                                    boxShadow: "0px 1px 10px #FFC1B9",
                                                    borderRadius: "15px",
                                                    color : "black",
                                                    fontSize : "12px",
                                                    height : "24px",
                                                    padding: "2px 8px",
                                                    lineHeight : "20px",
                                                    fontFamily:"微软雅黑"
                                                });
                                                deliver_marker.setLabel(label);
                                                setTimeout(function () {
                                                    $('.BMapLabel').append("<div class='map_tip_view'></div>");
                                                }, 200);
                                            }

                                            var storeIcon = new BMap.Icon("{pigcms{$static_path}/images/map/store_pos.png", new BMap.Size(22,60));
                                            map.addOverlay(new BMap.Marker(from_point, {icon:storeIcon}));
                                            var userIcon = new BMap.Icon("{pigcms{$static_path}/images/map/my_pos.png", new BMap.Size(60,60));
                                            map.addOverlay(new BMap.Marker(aim_point, {icon:userIcon}));

                                            if (lines != null) {
                                                var polygonArr = [];
                                                for (var i in lines) {
                                                    polygonArr.push(new BMap.Point(lines[i].lng, lines[i].lat));
                                                }
                                                var polyline = new BMap.Polyline(polygonArr, {strokeColor:"green", strokeWeight:2, strokeOpacity:0.5}); //创建折线
                                                map.addOverlay(polyline); //增加折线

                                                if (response.status < 4) {
                                                    var polyline = new BMap.Polyline([point, from_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                                    map.addOverlay(polyline);
                                                } else {
                                                    var polyline = new BMap.Polyline([point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                                    map.addOverlay(polyline);
                                                }
                                            } else if (response.status < 4) {
                                                var polyline = new BMap.Polyline([point, from_point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                                map.addOverlay(polyline);
                                            } else {
                                                var polyline = new BMap.Polyline([from_point, point, aim_point], {strokeColor:"red", strokeWeight:2, strokeOpacity:0.5, strokeStyle: 'dashed'}); //创建折线
                                                map.addOverlay(polyline);
                                            }
                                        }
                                    }, 'json');
                                }
                            }
                        </script>
                        <php>}else{</php>
                            <script type="text/javascript">
                            if(typeof(is_google_map) != "undefined"){
                                var p1 = {lng:parseFloat("{pigcms{$cat_field_info['start_adress_lng']}"), lat:parseFloat("{pigcms{$cat_field_info['start_adress_lat']}")};
                                var p2 ={lng:parseFloat("{pigcms{$cat_field_info['end_adress_lng']}"), lat:parseFloat("{pigcms{$cat_field_info['end_adress_lat']}")};
                               
                                var directionsService = new google.maps.DirectionsService();
                                var directionsDisplay = new google.maps.DirectionsRenderer();
                                var map = new google.maps.Map(document.getElementById('allmap'),{
                                    center: p1,
                                    zoom:16,
                                    streetViewControl:false,
                                    mapTypeControl:false
                                });
                                directionsDisplay.setMap(map);
                                calcRoute();
                                function calcRoute() {
                                    var request = {
                                        origin: p1,
                                        destination: p2,
                                        travelMode: 'DRIVING'
                                    };
                                    directionsService.route(request, function (result, status) {
                                        if (status == 'OK') {
                                            directionsDisplay.setDirections(result);
                                            $('#service_distance_tips').html('');
                                            $('#service_need_distance').html(result.routes[0].legs[0].distance.text+','+result.routes[0].legs[0].duration.text);
                                        }
                                    });
                                }
                            }else{
                                var map = new BMap.Map("allmap");
                                var p1 = new BMap.Point("{pigcms{$cat_field_info['start_adress_lng']}", "{pigcms{$cat_field_info['start_adress_lat']}");
                                var p2 = new BMap.Point("{pigcms{$cat_field_info['end_adress_lng']}", "{pigcms{$cat_field_info['end_adress_lat']}");
                                var output = "";
                                var searchComplete = function (results) {
                                    var plan = results.getPlan(0);
                                    output = plan.getDistance(true);
                                }
                                var transit = new BMap.DrivingRoute(map, {
                                    renderOptions: {map: map},
                                    onSearchComplete: searchComplete,
                                    onPolylinesSet: function () {
                                        $("#service_need_distance").html(output);
                                    }
                                });
                                transit.search(p1, p2);
                            }
                        </script>
                        <php>}</php>

                    </ul>
                </div>
            </div>
            <if condition="$publishInfo['status'] eq 1">
                <if condition="!$hidePay">
                <div class="quote-price-oper ebox-1 js-goto js_quote_box">
                    <div class="l-flex">
                        <a  style=" background: #06c1ae; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange"  data-id="{pigcms{$publishInfo['publish_id']}"  onclick="payMoney(this)" href="javascript:void(0);">
                        去支付
                        </a>
                    </div>
                </div>
                </if>
            <elseif condition="$publishInfo['status'] eq 8 AND $publishInfo['is_voice'] AND $publishInfo['voice_status'] eq '1'"/>
                <div class="quote-price-oper ebox-1 js-goto js_quote_box">
                    <div class="l-flex">
                        <a  style=" background: #06c1ae; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange"  data-id="{pigcms{$publishInfo['publish_id']}"  onclick="payMoney(this)" href="javascript:void(0);">
                        去支付尾款
                        </a>
                    </div>
                    <div class="l-flex">
                        <a  style=" background: #999c9e; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange" data-id="{pigcms{$publishInfo['publish_id']}"  onclick="order_refund(this)" href="javascript:void(0);">取消订单</a>
                    </div>
                </div>
            <elseif condition="$publishInfo['status'] eq 2"/>
                <div class="quote-price-oper ebox-1 js-goto js_quote_box">
                    <div class="l-flex">
                        <a  style=" background: #999c9e; display: inline-block; width: 100%; text-align: center; height: 2.862338362rem; line-height: 2.862338362rem; color: #fff; border-radius: 0.394652478rem; font-size: 1.05233028rem;" class=" l-offer-btn l-offer-orange" data-id="{pigcms{$publishInfo['publish_id']}"  onclick="order_refund(this)" href="javascript:void(0);">取消订单</a>
                    </div>
                </div>
            </if>
            
        </if>
    </div>
</div>
{pigcms{$hideScript}
<script src="{pigcms{$static_path}service/js/hammer.min.js"></script>
<script src="{pigcms{$static_path}service/js/play.js"></script>
<script>
    // 取消订单
    var refund = false;
    function order_refund(obj) {
        if (refund) return false;
        refund = true;
        var id = $(obj).data('id');
        console.log('支付的服务id:  ', id);
        var service_order_refund = "{pigcms{:U('Service/service_order_refund')}";
        var locked = false;
        layer.open({
            content: '您确定要取消订单吗？取消订单后，金额将原路退还。'
            ,btn: ['确定', '取消']
            ,skin: 'footer'
            ,yes: function(index){
                if (locked) {
                    return
                }
                locked = true;
                $.post(service_order_refund,{'publish_id':id},function(data){
                    refund = false;
                    if(data.error == 1){
                        // $("#catList"+cid).remove();
                        // layer.open({content: data.msg});
                        layer.open({
                            content: data.msg
                            ,btn: ['确定']
                            ,yes: function(index){
                                layer.close(index);
                                window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                            }
                        });
                    } else if (data.error == 2) {
                        layer.open({
                            content: data.msg
                            ,btn: ['确定']
                            ,yes: function(index){
                                layer.close(index);
                                window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                            }
                        });
                    }  else if (data.error == 3) {
                        layer.open({
                            content: data.msg
                            ,btn: ['确定']
                            ,yes: function(index){
                                layer.close(index);
                                window.location.href = data.url;
                            }
                        });
                    } else if (data.errorCode) {
                        layer.open({
                            content: data.errorMsg
                            ,btn: ['确定']
                            ,yes: function(index){
                                layer.close(index);
                                window.location.href = "{pigcms{:U('Service/price_list',array('publish_id'=>$_GET['publish_id']))}&t={pigcms{:time()}";
                            }
                        });
                    }
                    locked = false;
                },'json')
            }
            ,no: function (index) {
                refund = false;
                locked = false;
            }
        });
    }

    function showImg(img_url){
        $('.mask').show();
        $('.img_show').prop('src',img_url);
        $('.content_img').show();
        var pageii = layer.open({
          type: 1
          ,content: '<div class="playli" style=""><img src="'+img_url+'" alt="" style="width: 100%;"></div>'
          ,anim: 'up'
          ,style: 'border: none; -webkit-animation-duration: .5s; animation-duration: .5s;'
        });
        $('.playli img').click(function(e){
           $('.laymshade').hide();
           $('.layermmain').hide();
            
        });
    }
	var user_from = '{pigcms{$user_session.from_device_id}';
    var pay = false;
    // 支付
    function payMoney(obj) {
        if (pay) return false;
        pay = true;
        var id = $(obj).data('id');
        var order_url = "{pigcms{:U('Service/service_plat_order')}";
        console.log('支付的服务id:  ', id);
        $.post(order_url,{publish_id:id},function(data){
            pay = false;
            console.log('订单消息', data)
            if (data && data.order_id) {
                var order_id = data.order_id;
                if(user_from != 'wxapp_paotui'){
					var pay_url = "{pigcms{:U('Pay/check',array('type'=>'plat'))}" + '&order_id=' + order_id;
					console.log(pay_url);
					window.location.href = pay_url;
				}else{
					wx.miniProgram.navigateTo({
						url:'/pages/pay/index?type=plat&order_id='+order_id
					});
				}
            }
        },'json');
    }
</script>
    
</body>
</html>