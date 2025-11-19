<include file="Public/header"/>
        <section class="search">
            <div class="w1200 clr">
                <div class="fl">
                    <div class="logo fl">
                        <a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}" width=160 height=43></a>
                    </div>
                </div>
                <div class="fr">
                    <ul class="arrow clr">
                        <li class="clr on">
                            <i>1</i>
                            <span>选择向导</span>
                        </li>
                        <li class="clr on">
                            <i>2</i>
                            <span>填写订单</span>
                        </li>
                        <li class="clr ou">
                            <i>3</i>
                            <span>在线支付</span>
                        </li>
                        <li class="clr">
                            <i>4</i>
                            <span>支付成功</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="order">
            <div class="w1200">
                <div class="infor">
                    <h2 class="title">订单信息</h2>
                    <div class="infor_top">
                        <div class="clr name_zf">
                            <div class="fl">
                                <div class="name_top">
                                    <span>{pigcms{$now_guide.guide_name}</span><if condition="$now_guide['guide_sex'] eq 2"><i></i><else/><i class="on"></i></if>
                                </div>
                                <div class="name_txt">
                                    使用日期：<span class="Arial">{pigcms{$order_detail.start_time}</span> 至 <span class="Arial">{pigcms{$order_detail.end_time}</span> <span class="m25">共<span class="Arial">{pigcms{$order_detail.day}</span>天</span>
                                </div>
                            </div>
                            <div class="infor_moy fr">
                                <span class="stay">待支付</span>
                                <span class="price">￥<em>{pigcms{$scenic_order.guide_price}</em></span>
                            </div>
                        </div>
                    </div>
                    <div class="infor_end">
                        <h2>
                            <span>出行人信息</span>
                        </h2>
                        <ul class="clr">
							<volist name="family" id="vo">
								<li>
									<h5>出行人1</h5>
									<div class="end_list clr">
										<span class="fl">姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名： </span>
										<p class="p75">{pigcms{$vo.family_name}</p>
									</div>
									<div class="end_list clr">
										<span class="fl">身份证号：</span>
										<p class="p75">{pigcms{$vo.certificates}</p>
									</div>
								</li>
							</volist>

                        </ul>
                    </div>
                    <div class="infor_end infor_take">
                        <h2>
                            <span>联系人信息</span>
                        </h2>
                        <ul class="clr">
                            <li>
                                <h5>联系人</h5>
                                <div class="end_list clr">
                                    <span class="fl">姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名： </span>
                                    <p class="p75">{pigcms{$scenic_order.name}</p>
                                </div>
                                <div class="end_list clr">
                                    <span class="fl">电话号码：</span>
                                    <p class="p75">{pigcms{$scenic_order.phone}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <if condition="$order_detail['explain']">
	                    <div class="infor_end infor_take">
	                        <h2>
	                            <span>备注信息</span>
	                        </h2>
	                        <ul class="clr">
	                            <li>
	                                <h5>补充说明</h5>
	                                <div class="end_list" style="color: #333333; line-height: 1.4">{pigcms{$order_detail['explain']}</div>
	                            </li>
	                        </ul>
	                    </div>
                    </if>
                </div>
                <div class="tim"><i></i>请您在提交订单后<em id="minute_show">0分钟</em><em id="second_show">0</em>内完成支付，逾期订单将自动取消。</div>


				<form action="{pigcms{:U('Scenic_pay/go_pay')}" method="post" id="deal-buy-form" class="common-form J-wwwtracker-form">

                <div class="bank clr" >
                    <div class="clr bank_top">
                        <h2 class="fl">余额支付</h2>
                        <div class="fr">
                            <input type="checkbox" id="use_balance" name="use_balance" class="balye" style="display:none">
                            <label for="balbox" class="balye">使用余额支付</label>
                            <span class="fr">￥<em>{pigcms{$now_user.now_money}</em></span>
                        </div>

                    </div>
                </div>
                <div class="bank clr" id="paymethod">
                    <div class="clr bank_top">
                        <h2 class="fl">支付平台</h2>
                    </div>
                    <div class="method treasure">
                        <dl class="clr">
							<volist name="pay_method" id="vo">
								<dd data-name="{pigcms{$key}">
									<input type="radio" name="pay_type" value="{pigcms{$key}" style="display:none">
									<img src="{pigcms{$static_public}images/pay/{pigcms{$key}.gif" width="112" height="32" alt="{pigcms{$vv.name}" title="{pigcms{$vv.name}"/>
								</dd>
							</volist>
                        </dl>
                    </div>
                </div>


                <div class="stays clr">
                    <div class="fl">
                        <span class="stay">待支付</span>
                        <span class="price">￥<em>{pigcms{$scenic_order.guide_price}</em></span>
                    </div>
					<input type="hidden" name="order_id" value="{pigcms{$scenic_order['order_id']}">
					<input type="hidden" name="type" value="1">

					<a href="javascript:$('#deal-buy-form').submit();" class="fr">确认支付</a>
                </div>
				</form>
            </div>
        </section>
<script type="text/javascript">
    /*银行*/

    $(".click_bank").click(function(){

        if($(".bank_dis").is(":hidden")){
            $(".bank_dis").slideDown();
            $(this).parents(".bank").addClass("on");
        }else{
            $(".bank_dis").slideUp();
            $(this).parents(".bank").removeClass("on");
        }
	});

  $(".method li").click(function(){
        $(this).addClass("on").siblings().removeClass("on");
        var index=$(this).index();
        $(".method dl").eq(index).show().siblings("dl").hide();
    });

  $(".method dd").click(function(){
    $(this).addClass("on").siblings("dd").removeClass("on");
    $(this).parents(".method dl").siblings("dl").find("dd").removeClass("on");
    $(this).parents(".bank").css("border","#fe6903 1px solid");
  })


</script>

<script type="text/javascript">
var intDiff = parseInt({pigcms{$count['down']});//倒计时总秒数量
function timer2(intDiff){
    window.setInterval(function(){
		var day=0,
			hour=0,
			minute=0,
			second=0;//时间默认值
		if(intDiff > 0){
			minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
			second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
		}

		if (minute <= 9) minute = '0' + minute;
		if (second <= 9) second = '0' + second;
		$('#minute_show').html('<s></s>'+minute+'分钟');
		$('#second_show').html('<s></s>'+second+'秒');
		intDiff--;
    },1000);
}

$(function(){
  timer2(intDiff);
	$(".method dd").click(function(){
		$(this).addClass("on").siblings("dd").removeClass("on");
		$(this).parents(".bank").css("border","#fe6903 1px solid");
		$(this).find('input[name="pay_type"]').attr('checked','checked');
	})


	var order_total = Number("{pigcms{$scenic_order.order_total}");
	var now_money = Number("{pigcms{$now_user.now_money}");


	if($("#use_balance").is(':checked')==true){

		if(now_money>=order_total){
			//$('#need_pay').html(0);
			$('#paymethod').css('display','none');
			$(".method dd").removeClass('on')
			$('input:radio:checked').removeAttr('checked');
		}else{
			//$('#need_pay').html((order_total-now_money).toFixed(2));
			$('#paymethod').css('display','block');
			$(".method dd:first").addClass('on')
			$('input[type="radio"]:first').attr('checked','checked');
		}
	}else{
		//$('#need_pay').html(order_total);
		$('#paymethod').css('display','block');
		$(".method dd:first").addClass('on')
		$('input[type="radio"]:first').attr('checked','checked');
	}

	//支付平台
	$("label.balye").click(function(){
		if($(this).siblings("input.balye").is(":checked")){
			$(this).removeClass("on");
			$(this).parents(".bank").css("border","#ebebeb  1px solid")
			$('input.balye').removeAttr('checked');
		}else{
			$(this).addClass("on");
			$(this).parents(".bank").css("border","#fe6903 1px solid")
			$('input.balye').attr('checked','checked');
		}

		if($("#use_balance").is(':checked')==true){
			$('input[name="use_balance"]').val(1);
			if(now_money>=order_total){
				//$('#need_pay').html(0);
				$('#paymethod').css('display','none');
				$('input:radio:checked').removeAttr('checked');
			}else{
				//$('#need_pay').html((order_total-now_money).toFixed(2));
				$('#paymethod').css('display','block');
				$('input[type="radio"]:first').attr('checked','checked');
			}
		}else{
			$('input[name="use_balance"]').val(0);
			//$('#need_pay').html(order_total);
			$('#paymethod').css('display','block');
			$('input[type="radio"]:first').attr('checked','checked');
		}
	});

	$('#deal-buy-form').submit(function(){
		if($('input[name="pay_type"]:checked').val() == 'weixin'){
			art.dialog({
				title: '提示信息',
				id: 'weixin_pay_tip',
				opacity:'0.4',
				lock:true,
				fixed: true,
				resize: false,
				content: '正在获取微信支付相关信息，请稍等...'
			});
			$.post($('#deal-buy-form').attr('action'),$('#deal-buy-form').serialize(),function(result){
				art.dialog.list['weixin_pay_tip'].close();
				if(result.status == 1){
					orderid = result.orderid;
					art.dialog({
						title: '请使用微信扫码支付',
						id: 'weixin_pay_qrcode',
						width:'350px',
						opacity:'0.4',
						lock:true,
						fixed: true,
						resize: false,
						content: '<p style="margin-top:20px;margin-bottom:20px;text-align:center;font-size:16px;color:black;">请使用微信扫描二维码进行支付</p><p style="text-align:center;"><img src="{pigcms{:U('Recognition/get_own_qrcode')}&qrCon='+result.info+'" style="width:240px;height:240px;"></p><p style="text-align:center;margin-top:20px;margin-bottom:20px;"><input id="J-order-weixin-button" type="button" class="btn btn-large btn-pay" value="已支付完成"/></p>',
						cancel: function(){
							$("#J-order-pay-button").val("去付款");
							$("#J-order-pay-button").removeAttr("disabled");
						},
					});
				}else{

					art.dialog({
						title: '错误提示：',
						id: 'weixin_pay_error',
						opacity:'0.4',
						lock:true,
						fixed: true,
						resize: false,
						content: result.info
					});

				}
			});
			return false;
		}
	});
	$('#J-order-weixin-button').live('click',function(){
		window.location.href="{pigcms{:U('Scenic_pay/weixin_back',array('order_type'=>'guide'))}&order_id="+orderid;
	});


});
</script>
<include file="Public/footer"/>