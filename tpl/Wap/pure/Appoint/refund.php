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
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/appoint_refund.css" />
<script type="text/javascript" src="{pigcms{$config['site_url']}/packapp/my/scripts/weui/weui.min.js"></script>
<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/ajaxfileupload.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>

<body>
    <div class="content" <if condition="$expire eq 1">style="display: none"</if>>
        <div class="foods">
            <div class="goods-img">
                <img src="{pigcms{$now_order['list_pic']}" />
            </div>
            <div class="item-right">
                <div class="text">
                    {pigcms{$now_order['appoint_name']}
                </div>
                <div class="rev_ul rev_input" data-price="{pigcms{$goods['pay_price']|floatval}" data-detail_id="{pigcms{$now_order['appoint_id']}" data-goods_id="{pigcms{$now_order['appoint_id']}">
                    <span>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['appoint_price']|floatval}</span>
                    <span class="num">x1</span>
                </div>
                
                  <div class="payment-money">
                    <if condition="$now_order['payment_status']">
                    <span>预付定金：</span><span class="cr-06C1AE">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['payment_money']|floatval}</span>
                      <else/>
                      <span class="tag-tips">一口价</span>
                    </if>
                  </div>
            </div>
            <php></php>
        </div>
        
        <div class="tui-money-wrap">
            <div class="tui-money border-bottom">
                <span>支付金额</span>
                <span>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['total_money']|floatval}</span>
            </div>

            <div class="tui-money">
                <span>退款金额</span>
                <span id="refundMoney" class="cr-F7AD1F">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.total_money}</span>
                <input type="hidden" name="order_id" value="{pigcms{$now_order['order_id']}" />
            </div>
        </div>
        
        <div class="explain">
            <div class="title"><span>退款说明</span></div>
            <div class="text-area">
                <textarea name="reason" id="reason" rows="6" cols="3" maxlength="200" placeholder="在这里输入内容"></textarea>
                <span class="reason-num">0/200</span>
            </div>
        </div>
        <div class="upload">
            <div class="title"><span>上传凭证(最多3张)</span></div>
            <ul class="imgsUp" id="upload_list">
                <li class="fail">
                    <div class="up_img">
                        <span>+</span>
                    </div>
                    <input type="file" accept="image/*" id="imgUploadFile" onchange="imgUpload()" name="imgFile" value="选择文件上传"/>
                </li>
            </ul>
        </div>
        <div class="bottom">提交</div>
    </div>
    <div class="exceed" <if condition="$expire eq 0">style="display: none"</if>>
        <h4>{pigcms{$expire_tips}</h4>
        <a href="{pigcms{:U('Shop/refundStatus', array('order_id' => $order['order_id']))}">查看退款进度</a>
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
                if (sum < 3) {
                    $('.fail').show();
                }
            });
            $('.bottom').click(function(){
                var images = [];
                $("input[class='images']").each(function(i, el) {
                    images.push($(this).val());
                });
                images = images.join();
                $.post("{pigcms{:U('Appoint/saveRefund')}", {'appoint_id':$(".rev_input").data('detail_id'), 'order_id':$('input[name="order_id"]').val(), 'images':images, 'reason':$('#reason').val()}, function(res){
                    if (res.errcode == 1) {
                        layer.open({content:res.msg, skin:'msg', time:2});
                    } else {
                        location.href="{pigcms{:U('Appoint/refundStatus')}&order_id=" + $('input[name="order_id"]').val();
                    }
                }, 'json');
            });
            
            $("#reason").on("input",function(e) {
                let valueLength = e.target.value.length || 0
                $('.reason-num').text(`${valueLength}/200`)
            })
            
        });


        function imgUpload(){
            $.ajaxFileUpload({
                url:"{pigcms{:U('Appoint/ajax_upload_file')}",
                secureuri:false,
                fileElementId:'imgUploadFile',
                dataType: 'json',
                success: function (data) {
                    if(data.error == 2){
                        $("#upload_list").prepend('<li class="a1"><img src="'+data.url+'"/><button class="del"></button><input type="hidden" class="images" name="img[]" value="'+data.url+'" /></li>');
                        let sum = $("#upload_list .a1").length;
                        if(sum >= 3){
                            $('.fail').hide();
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