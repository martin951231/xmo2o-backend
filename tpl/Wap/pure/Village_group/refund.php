<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>申请售后</title>
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
    <span>请选择退款商品</span>
<!--    <a type="button" href="{pigcms{:U('Shop/refundStatus', array('order_id' => $order['order_id']))}">退款进度</a>-->
</div>
<div class="selectAll">
    <p class="do active">
        <i></i>全选
    </p>
    <div>实际支付:<span>{pigcms{$config['Currency_symbol']}{pigcms{$order['pay_prices']|floatval}</span></div>
</div>
<div class="foods">
    <php> $refundPrice = 0; </php>
    <volist name="goodsList" id="goods">
        <div class="foodItem">
            <div class="itemLeft">
                <div class="img do active" data-price="{pigcms{:floatval($goods['totle_price'])}">
                    <i></i>
                    <img src="{pigcms{$goods['image']}" />
                </div>
                <div class="text">
                    <dl>
                        <dt>{pigcms{$goods['name']}</dt>
                        <!-- <dd>{pigcms{$config['Currency_symbol']}{pigcms{$goods['pay_price']|floatval}</dd> -->
                        <p style="font-size: 12px;color: #666;padding-top: 5px;">{pigcms{$goods['spec']}</p>
                    </dl>
                </div>
            </div>
            <div class="itemRight">
                <div class="zi rev_ul">
                    <!--                         <span class="fl jian">-</span> -->
                    <input class="fl rev_input" type="tel" disabled value="{pigcms{$goods['num']}" data-price="{pigcms{$goods['pay_price']|floatval}"  data-detail_id="{pigcms{$goods['id']}" data-goods_id="{pigcms{$goods['goods_id']}">
                    <!--                         <span class="fl jia">+</span> -->
                </div>
            </div>
            <php> $refundPrice += $goods['num'] * $goods['pay_price']; </php>
        </div>
    </volist>
</div>

<div class="tuiMoney">退款金额:
    <span id="refundMoney">{pigcms{$config['Currency_symbol']}{pigcms{$order.totle_prices}</span>
    <input type="hidden" name="order_id" value="{pigcms{$order['order_id']}" />
</div>
<!--<div class="cost">不含配送费、打包费、优惠金额</div>-->
<if condition="$village_group_consumed_cancel_order eq 1">
<div class="cost">{pigcms{$village_group_consumed_cancel_order_desc}</div>
</if>
<div class="explain">
    <span>退款说明:</span>
    <textarea name="reason" id="reason" rows="3" cols="3" maxlength="30" placeholder="选填，最多支持30个字"></textarea>
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
<!--<a href="{pigcms{:U('Village_group/refundStatus', array('order_id' => $order['order_id']))}">查看退款进度</a>-->
<a href="{pigcms{:U('Village_group/refundStatusList', array('order_id' => $order['order_id']))}">查看退款订单列表</a>
</div>
<script type="text/javascript">
    var pay_real = "{pigcms{$order['price']}";
    var discount_price = "{pigcms{$discount_price}";
    var customer_money = "{pigcms{$order['totle_prices']}";
    function totalMoney()
    {
        var money = 0;
        var loop = 0;
        var _sel = 0;
        $('.itemLeft .do').each(function(){
            loop++;
            if (($(this).hasClass('active'))) {
                _sel++;
                console.log($(this).data('price'))
                money += parseFloat($(this).data('price'));

                console.log(".itemLeft .do")
                console.log(money)
            }
        });
        money = parseFloat(money);
        if(money - discount_price <0){
            money = 0;
        }else{
        	console.log("discount_price");
            money -= parseFloat(discount_price);
        }
        if(loop == _sel){
            money = parseFloat(customer_money);
            console.log("loop == _sel")
            console.log(money)
        }
        if(money>pay_real){
            money = parseFloat(pay_real);
            console.log("money>pay_real")
            console.log(money)
        }
        console.log("总退款")
        console.log(money.toFixed(2))
        return money.toFixed(2);
    }
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

        $('#reason').on('blur', function(){
            setTimeout(function(){
                window.scroll(0,0)
            }, 500)
        })

        $('.selectAll .do').click(function(){
            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
                $('.itemLeft .do').removeClass('active');
                $('.jdRefund').hide();
            } else {


                $(this).addClass('active');
                $('.itemLeft .do').addClass('active');
            }
            $('#refundMoney').html('{pigcms{$config['Currency_symbol']}' + totalMoney());
        });
        $('.itemLeft .do').click(function(){
            // debugger
            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
                $('.selectAll .do').removeClass('active');
            } else {
                $(this).addClass('active');
                var flag = true;
                $('.itemLeft .do').each(function(){
                    if (!($(this).hasClass('active'))) {
                        flag = false;
                    }
                });
                if (flag) {
                    $('.selectAll .do').addClass('active');
                }
            }
            $('#refundMoney').html('{pigcms{$config['Currency_symbol']}' + totalMoney());
        });

        $('.rev_ul span').click(function(){
            if ($(this).hasClass("jia")) {
                var number = parseInt($('.rev_input').val())+1;
                $(this).prev().val(number);
            } else {
                var number = parseInt($(this).next().val());
                if (number>1) {
                    number--;
                }
                $(this).next().val(number);
            };
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


            var goods = [];
            $('.foods .active').each(function(){
                goods.push({'num':$(this).parents('.foodItem').find('.rev_input').val(), 'detail_id':$(this).parents('.foodItem').find('.rev_input').data('detail_id')});
            });

            var images = [];
            $("input[class='images']").each(function(i, el) {
                images.push($(this).val());
                console.log($(this).val());
            });
            images = images.join();
            //京东选项

            if($('.jdRefund').is(":hidden")){


            }else{


            }
            if(goods.length == 0){
                layer.open({content:'您还未选择任何商品！', skin:'msg', time:2});
                return false;
            }

            $.post("{pigcms{:U('Village_group/saveRefund')}", {'goods':goods, 'order_id':$('input[name="order_id"]').val(), 'images':images, 'reason':$('#reason').val()}, function(res){
                if (res.errcode == 1) {
                    layer.open({content:res.msg, skin:'msg', time:2});
                } else {
                    location.href="{pigcms{:U('Village_group/refundStatusList')}&order_id=" + $('input[name="order_id"]').val();
                }
            }, 'json');
        });
    });


    function imgUpload(){
        $.ajaxFileUpload({
            url:"{pigcms{:U('Village_group/ajax_upload_file')}",
            secureuri:false,
            fileElementId:'imgUploadFile',
            dataType: 'json',
            success: function (data) {
                if(data.error == 2){
                    $("#upload_list").append('<li class="a1"><img src="'+data.url+'"/><i class="del"></i><input type="hidden" class="images" name="img[]" value="'+data.url+'" /></li>');
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