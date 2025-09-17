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
 
			<notempty name="is_refund">
            <a type="button" href="{pigcms{:U('Shop/refundStatus', array('order_id' => $order['order_id']))}">退款进度</a> 
			</notempty>
        </div>
        <div class="selectAll">
            <p class="do <if condition='$yuanjino2o_customized_func neq 1'>active</if>">
                <i></i>全选
            </p>
            <div>实际支付:<span>{pigcms{$config['Currency_symbol']}{pigcms{$order['price'] - $order['coupon_price'] - $order['card_price']|floatval}</span></div>
        </div>
        <div class="foods">
            <php> $refundPrice = 0; </php>
            <volist name="goodsList" id="goods">
            <div class="foodItem">
                <div class="itemLeft" style="flex:1;">
                    <div class="img do <if condition='$yuanjino2o_customized_func neq 1'>active</if>" style="white-space: nowrap;align-self:flex-start;" data-price="{pigcms{:floatval($goods['pay_price'])}" data-jd="{pigcms{$goods['jd_sku_id']}" data-num="{pigcms{$goods['num']}">
                        <i></i>
                        <img src="{pigcms{$goods['image']}" />
                    </div>
                    <div class="text" style="flex:1;">
                        <div style="white-space: break-spaces;">{pigcms{$goods['name']}</div>
                        <!-- <div>{pigcms{$config['Currency_symbol']}{pigcms{$goods['pay_price']|floatval}</div> -->
                        <p style="font-size: 12px;color: #666;padding-top: 5px;">{pigcms{$goods['spec']}</p>
                    </div>
                </div>
                <div class="itemRight" style="white-space: nowrap;align-self:flex-start;min-height: 40px;display: flex;align-items: center;">
                    <div class="zi rev_ul" style="display: flex;">
                        <span class="jian">-</span>
                        <input class="rev_input" type="tel" disabled value="{pigcms{$goods['num']}" data-price="{pigcms{$goods['pay_price']|floatval}" data-detail_id="{pigcms{$goods['id']}" data-goods_id="{pigcms{$goods['goods_id']}" data-sku_id="{pigcms{$goods['jd_sku_id']}" style="background-color: transparent;">
                        <span class="jia" data-price="{pigcms{$goods['pay_price']|floatval}" data-num="{pigcms{$goods['num']}">+</span>
                    </div>
                </div>
                <php> $refundPrice += $goods['num'] * $goods['pay_price']; </php>
            </div>
            </volist>
        </div>
        <div class="jdRefund" style="background: white;margin: 10px auto;padding: 10px;display: none;">
            <span style="font-size: 12px;color: #979798;">*仅对京东商品生效：</span>
            <div class="expect" style="margin-bottom: 10px;margin-top: 5px;"></div>
            <div class="back" style="margin-bottom: 10px;"></div>
            <div style="margin-bottom: 10px;">
                包装状态：<input type="radio" name="isHasPackage" value="0">没有包装&nbsp;&nbsp;
                <input type="radio" name="isHasPackage" value="1">有包装
            </div>
            <div>
                包装详情：<input type="radio" name="packageDesc" value="0">没有包装&nbsp;&nbsp;
                <input type="radio" name="packageDesc" value="10">包装完整&nbsp;&nbsp;
                <input type="radio" name="packageDesc" value="20">包装破损
            </div>
        </div>
        <div class="tuiMoney">退款金额:
            <span id="refundMoney">{pigcms{$config['Currency_symbol']}<if condition='$yuanjino2o_customized_func neq 1'>{pigcms{$order.customer_money}<else/>0</if></span>
            <input type="hidden" name="order_id" value="{pigcms{$order['order_id']}" />
        </div>
        <div class="cost">
            1. 不含配送费、打包费、优惠金额<br/>
            2. 您退货后，需扣除该商品所获得的平台积分，如账户已使用平台积分，则自商品退款金额中扣除相应平台积分
        </div>
        <div class="explain" style="align-items: flex-start;">
            <span>退款说明:</span>
            <textarea style="margin-left: 15px;flex: 1;" name="reason" id="reason" rows="3" cols="3" maxlength="30" placeholder="选填，最多支持30个字<if condition="$config['is_open_jd_goods'] eq 1">，京东商品必填</if>"></textarea>
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
        <a href="{pigcms{:U('Shop/refundStatus', array('order_id' => $order['order_id']))}">查看退款进度</a>
    </div>
 
<script type="text/javascript"> 
var pay_real = "{pigcms{$order['price']}";
    var customer_money = {pigcms{$order.customer_money};
function totalMoney()
{
    var money = 0;
    var loop = 0;
    var _sel = 0;
    $('.itemLeft .do').each(function(){
        loop++;
        if (($(this).hasClass('active'))) {
            var price = parseFloat($(this).data('price')) || 0
            var num = parseInt($(this).parent().next().find('.rev_input').val()) || 0
            var buy_num = parseInt($(this).data('num')) || 0
            if (buy_num == num) {
                _sel++;
            }
            var goods_total_price = price * num
            money += goods_total_price;
        }
    });
    money = parseFloat(money.toFixed(2));
    if(loop == _sel){
        money = customer_money;
    }
    if(money>pay_real)money = parseFloat(pay_real);
    return money;
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

    if(/(android)/.test(navigator.userAgent.toLowerCase())){
          var winHeight = $(window).height();   //获取当前页面高度
        $(window).resize(function(){
           var thisHeight=$(this).height();
            if(winHeight - thisHeight >50){
                 //当软键盘弹出，在这里面操作
            $(".bottom").css("position","static")
            }else{
                //当软键盘收起，在此处操作
           $(".bottom").css("position","fixed")
            }
        });
    }

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
           //判断京东商品
            if($('.itemLeft .do').length ==1){
                $('.itemLeft .do').each(function(){
                    if (($(this).data('jd')>0)) {
                        $(this).trigger('click');
                    }
                });
            }else{
                var flag = true;
                $('.itemLeft .do').each(function() {
                    if (($(this).data('jd') > 0)) {
                        layer.open({content:'京东商品请单个退货！', skin:'msg', time:2})
                        flag = false;
                        return false;
                    }
                });
                if(flag==false){
                    return false;
                }
            }

            $(this).addClass('active');
            $('.itemLeft .do').addClass('active');
        }
        $('#refundMoney').html('{pigcms{$config['Currency_symbol']}' + totalMoney());
    });
    $('.itemLeft .do').click(function(){
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            if($(this).data('jd')>0){
                $('.jdRefund').hide();
            }
            $('.selectAll .do').removeClass('active');
        } else {
            $(this).addClass('active');
            //京东只可退单个商品
            if($(this).data('jd')!=0){
                $('.itemLeft .do').not(this).removeClass('active');
                //请求京东接口
                var this_ = $(this);
                $.ajax({
                    type: "POST",
                    url: "{pigcms{:U('Shop/jdRefund')}",
                    data: {order_id:$("input[name='order_id']").val(), skuId:$(this).data('jd')},
                    dataType: "json",
                    success: function(data){
                        if(data.error==1){
                            this_.removeClass('active');
                            layer.open({content:data.err_msg, skin:'msg', time:2});
                        }else{
                            $('.jdRefund').show();
                            var expect_str = "处理方式：";
                            var return_str = "取件方式：";
                            $.each(data.expect, function(i,val){
                               expect_str += "<input type='radio' name='expect' checked value="+ val.code +">"+ val.name;
                            });
                            $('.expect').html(expect_str+"<br>");
                            $.each(data.return, function(i,val){
                                return_str += "<input type='radio' name='back' value="+ val.code +">"+ val.name+"&nbsp;&nbsp;&nbsp;";
                            });
                            $('.back').html(return_str);
                        }
                    }
                });
            }
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
            var goods_num = $(this).data('num')
            var number = parseInt($(this).prev().val())+1;
            if (number > goods_num) {
                return
            }
            $(this).prev().attr('value',number);
            $('#refundMoney').html('{pigcms{$config['Currency_symbol']}' + totalMoney());
        } else {
            var number = parseInt($(this).next().val());
            if (number>1) {
                number--;
            }
            $(this).next().attr('value',number);
            $('#refundMoney').html('{pigcms{$config['Currency_symbol']}' + totalMoney());
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
            goods.push({'num':$(this).parents('.foodItem').find('.rev_input').val(), 'detail_id':$(this).parents('.foodItem').find('.rev_input').data('detail_id'),'jd_sku_id':$(this).parents('.foodItem').find('.rev_input').data('sku_id')});
        });

        var images = [];
        $("input[class='images']").each(function(i, el) {
            images.push($(this).val());
            console.log($(this).val());
        });
        images = images.join();
        //京东选项
        var expect = $("input[name='expect']:checked").val();
        var back = $("input[name='back']:checked").val();
        var isHasPackage = $("input[name='isHasPackage']:checked").val();
        var packageDesc = $("input[name='packageDesc']:checked").val();
        if((isHasPackage==0 && packageDesc!=0)||(isHasPackage!=0 && packageDesc==0)){
            layer.open({content:'包装信息填写有误！', skin:'msg', time:2});
            return false;
        }
        if($('.jdRefund').is(":hidden")){
        }else{
            if(!expect){layer.open({content:'请选择退货信息！', skin:'msg', time:2});return false;}
            if(!back){layer.open({content:'请选择取件方式！', skin:'msg', time:2});return false;}
            if(!isHasPackage){layer.open({content:'请选择包装！', skin:'msg', time:2});return false;}
            if(!packageDesc){layer.open({content:'请选择包装详情！', skin:'msg', time:2});return false;}
            if($('#reason').val()==""){layer.open({content:'京东商品需要填写原因！', skin:'msg', time:2});return false;}
        }
        if(goods.length == 0){
            layer.open({content:'没有选择商品！', skin:'msg', time:2});
            return false;
        }
        $.post("{pigcms{:U('Shop/saveRefund')}", {'goods':goods, 'order_id':$('input[name="order_id"]').val(), 'images':images, 'reason':$('#reason').val(),'expect':expect,'back':back,'isHasPackage':isHasPackage,'packageDesc':packageDesc}, function(res){
            if (res.errcode == 1) {
                layer.open({content:res.msg, skin:'msg', time:2});
            } else {
                location.replace("{pigcms{:U('Shop/refundStatus')}&order_id=" + $('input[name="order_id"]').val()) 
            }
        }, 'json');
    });
});


function imgUpload(){
    $.ajaxFileUpload({
        url:"{pigcms{:U('Shop/ajax_upload_file')}",
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
