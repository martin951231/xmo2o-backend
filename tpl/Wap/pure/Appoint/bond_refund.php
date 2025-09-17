<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>申请退款</title>
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width">
<meta http-equiv="pragma" content="no-cache">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-touch-fullscreen" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="format-detection" content="telephone=no">
<meta name="format-detection" content="address=no">
<link rel="stylesheet" type="text/css" href="{pigcms{$config['site_url']}/packapp/my/scripts/weui/weui.min.css" />
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}shop/css/customer.css" />
<script type="text/javascript" src="{pigcms{$config['site_url']}/packapp/my/scripts/weui/weui.min.js"></script>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/ajaxfileupload.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
<body>
    <div class="content" <if condition="$expire eq 1">style="display: none"</if>>
        <div class="returnGood">
            <span>退款服务:</span>
 
			<notempty name="is_refund">
<!--            <a type="button" href="{pigcms{:U('Shop/refundStatus', array('order_id' => $order['order_id']))}">退款进度</a> -->
			</notempty>
        </div>
        <div class="selectAll">
            <p class="do active">

            </p>
            <if condition = "$now_order['pay_type'] == ''">
                <div>
                    支付定金:<span>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['payment_money']|floatval}</span></div>
            <else/>
                <div>
                    支付定金:<span>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['payment_money']|floatval}</span></div>
            </if>

        </div>
        <div class="foods">
            <div class="foodItem">
                <div class="itemLeft">
                    <div class="">
                        <img src="{pigcms{$now_order['list_pic']}" />
                    </div>
                    <div class="text">
                        <dl>
                            <dt>{pigcms{$now_order['appoint_name']}</dt>
                        </dl>
                    </div>
                </div>
                <div class="itemRight">
                    <div class="zi rev_ul">
                        <input class="fl rev_input" type="tel" disabled value="1" data-price="{pigcms{$goods['pay_price']|floatval}" data-detail_id="{pigcms{$now_order['appoint_id']}" data-goods_id="{pigcms{$now_order['appoint_id']}">
                    </div>
                </div>
                <php></php>
            </div>

        </div>

        <div class="tuiMoney">退款金额:
            <span id="refundMoney">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.total_money}</span>
            <input type="hidden" name="order_id" value="{pigcms{$now_order['order_id']}" />
        </div>
        <div class="cost"></div>
        <div class="explain">
            <span>退款说明:</span>
            <textarea style="margin-left: 15px;width: -webkit-calc(100% - 100px);width: calc(100% - 100px)" name="reason" id="reason" rows="3" cols="3" maxlength="30" placeholder="选填，最多支持30个字"></textarea>
        </div>
        <div class="upload">
            <h4>上传凭证</h4>
            <ul class="imgsUp" id="upload_list">
                <li class="fail">
                    <div class="up_img">
                        <i></i>
                        <span>上传凭证</span>
                        <p>(最多3张)</p>
                    </div>
                    <input type="file" accept="image/*" id="imgUploadFile" onchange="imgUpload()" name="imgFile" value="选择文件上传"/>
                </li>
            </ul>
        </div>
        <div style="padding-bottom: 60px;"></div>
        <div class="bottom">提交</div>
    </div>
    <div class="exceed" <if condition="$expire eq 0">style="display: none"</if>>
        <h4>{pigcms{$expire_tips}</h4>
<!--        <a href="{pigcms{:U('Shop/refundStatus', array('order_id' => $order['order_id']))}">查看退款进度</a>-->
    </div>

    <script type="text/javascript">


        $(document).ready(function(){

            $(document).on('click','.imgsUp img',function(){
                var gallery = weui.gallery($('.imgsUp').find('img').attr('src'), {
                    className: '',
                    onDelete: function(){
                        if(confirm( L_('确定删除该图片？') )){
                            console.log('删除');
                        }
                        gallery.hide(function(){
                            console.log('`gallery` has been hidden');
                        });
                    }
                });
                $('.weui-gallery__del').remove();
            });

            $(document).on('click', '.del', function() {
                $(this).parents('.a1').remove();
                let sum = $("#upload_list .a1").length;
                let svm = $('.fail').length;
                if (sum < 3 && svm <= 0) {
                    $("#upload_list").prepend('<li class="fail"><div class="up_img"><i></i><span>上传凭证</span><p>(最多3张)</p></div><input type="file" accept="image/*" id="imgUploadFile" onchange="imgUpload()" name="imgFile" value="选择文件上传"/></li>');
                }
            });
            $('.bottom').click(function(){
                var images = [];
                $("input[class='images']").each(function(i, el) {
                    images.push($(this).val());
                    console.log($(this).val());
                });
                images = images.join();
                console.log($('input[name="order_id"]').val());
                console.log(images);
                console.log($('#reason').val());
                $.post("{pigcms{:U('Appoint/saveRefund')}", {'appoint_id':$(".rev_input").data('detail_id'), 'order_id':$('input[name="order_id"]').val(), 'images':images, 'reason':$('#reason').val()}, function(res){
                    if (res.errcode == 1) {
                        layer.open({content:res.msg, skin:'msg', time:2});
                    } else {
                        location.href="{pigcms{:U('Appoint/refundStatus')}&order_id=" + $('input[name="order_id"]').val();
                    }
                }, 'json');
            });
        });


        function imgUpload(){
            $.ajaxFileUpload({
                url:"{pigcms{:U('Appoint/ajax_upload_file')}",
                secureuri:false,
                fileElementId:'imgUploadFile',
                dataType: 'json',
                success: function (data) {
                    if(data.error == 2){
                        $("#upload_list").append('<li class="a1"><img src="'+data.url+'"/><button class="del"></button><input type="hidden" class="images" name="img[]" value="'+data.url+'" /></li>');
                        let sum = $("#upload_list .a1").length;
                        if(sum >= 3){
                            $('.fail').remove();
                            return false;
                        }
                    } else {
                        layer.open({content: data.msg ,btn: ['确定']});
                    }
                }
            });
        }

    </script>
</body>
</html>