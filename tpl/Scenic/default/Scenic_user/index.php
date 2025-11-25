<include file="Public/header"/>
<include file="Public/header_user"/>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl nav">
				<ul>
					<li class="gr on">
						<a href="{pigcms{:U('index')}">个人信息</a>
					</li>
					<li class="mp">
						<a href="{pigcms{:U('ticket_order_list')}">门票订单</a>
					</li>
					<li class="dy">
						<a href="{pigcms{:U('aguide_order_list')}">导游订单</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('add_guide')}">申请向导</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('guide_order')}">我是向导订单</a>
					</li>
					<li class="yb">
						<a href="{pigcms{:U('mate')}">我的游伴</a>
					</li>
					<li class="cx">
						<a href="{pigcms{:U('travel_person')}">出行人信息</a>
					</li>
					<li class="xc">
						<a href="{pigcms{:U('manager_travel')}">我的行程</a>
					</li>
					<li class="sc">
						<a href="{pigcms{:U('collection')}">我的收藏</a>
					</li>
				</ul>
			</div>
			<div class="fr navright">
				<div class="nav_top clr">
					<div class="fl clr">
						<div class="fl img">
							<img src="{pigcms{$static_path}images/user_avatar.jpg" width="142" height="142">
						</div>
						<div class="p168">
							<h2><i>Hi,</i> {pigcms{$now_user['nickname']}</h2>
							<div class="public">
								<if condition="$now_user['phone']"><span class="pho">{pigcms{$now_user['phone']}</span></if>
								<span class="chat"><if condition="$now_user['openid']">已绑定微信<else/>未绑定微信</if></span>
							</div>
						</div>
					</div>
					<div class="fr balance">
						<div class="account clr">
							<i class="fl">账户余额</i><span>{pigcms{$now_user['now_money']}</span>
						</div>
						<div class="clr recharge">
							<a href="#recharge" class="cz">充值</a>
							<a href="#withdrawals" class="tx">提现</a>
						</div>
					</div>
				</div>
				<div class="transaction">
					<div class="tran_top">余额记录</div>
					<dl>
						<dt class="clr">
							<div class="w200 fl">时间</div>
							<div class="w160 fl">交易类型</div>
							<div class="w420 fl">详情</div>
							<div class="w100 fr">金额 (元)</div>
						</dt>
						<volist name="money_list" id="vv">
							<dd class="clr">
								<div class="w200 fl">{pigcms{$vv['time']}</div>
								<div class="w160 fl"><if condition="$vv['type'] eq 2">消费<elseif condition="$vv['type'] eq 1"/>充值</if></div>
								<div class="w420 fl">{pigcms{$vv['desc']}</div>
								<div class="w100 fr"><if condition="$vv['type'] eq 2">-{pigcms{$vv['money']}<elseif condition="$vv['type'] eq 1"/><span style="color:green;">+{pigcms{$vv['money']}</span></if></div>
							</dd>
						</volist>
					</dl>
					<style type="text/css">
						.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
					</style>
					<div class="pages">
						<span class="num">{pigcms{$pagebar}</span>
					</div>
				</div>
                <div class="order" id="recharge">
					<div class="tran_top">充值管理</div>
					<div class="order_nr clr">
						<div class="into">转入金额：
							<input type="text" placeholder="请输入金额" name="recharge" value="" id="recharge_money"><span>元</span>
						</div>
						<!--<div class="bank clr">
							<div class="clr bank_top click_bank">
								<h2 class="fl">银行卡支付 </h2>
							</div>
							<div class="bank_dis">
								<div class="bank_end">
									<div class="cardnumbe clr">
										<span class="fl">银行卡号</span>
										<div class="p75">
											<input type="text">
											<a href="javascript:void(0)">确定</a>
										</div>
									</div>
								</div>
								<div class="method">
									<ul class="clr">
										<li class="on">快捷支付</li>
										<li>网银支付</li>
									</ul>
									<dl class="clr">
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd><dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
									</dl>
									<dl class="clr" style="display: none;">
										<dd>
											<img src="{pigcms{$static_path}images/yht_03.jpg" width="185" height="38">
										</dd>
									</dl>
								</div>
							</div>
						</div>
						<div class="bank clr">
							<div class="clr bank_top">
								<h2 class="fl">支付平台</h2>
							</div>
							<div class="method treasure">
								<dl class="clr">
									<dd>
										<img src="{pigcms{$static_path}images/zfbt1.png" width="185" height="38">
									</dd>
									<dd>
										<img src="{pigcms{$static_path}images/zfbt.jpg" width="185" height="38">
									</dd>
								</dl>
							</div>
						</div>-->
						<a href="javascript:void(0)" class="next_step">下一步</a>
					</div>
				</div>
				<div class="rawals" id="withdrawals">
					<div class="tran_top">提现管理</div>
					<div class="rawals_end">
						<div class="kwithd clr">
							<i class="fl">可提现余额</i>
							<span>{pigcms{$now_user['now_money']}</span>元
						</div>
						<div class="into">真实姓名
							<input type="text" name="truename" id="truename" placeholder="请输入姓名">
						</div>
						<div class="into">转出金额
							<input type="text" name="withdraw" id="withdraw" placeholder="请输入金额" ><span>元</span>
						</div>
						<a href="javascript:void(0)" class="Withdrawals">提现</a>
					</div>
				</div>
			</div>
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
	})
	$(".method li").click(function(){
		$(this).addClass("on").siblings().removeClass("on");
		var index=$(this).index();
		$(".method dl").eq(index).show().siblings("dl").hide();
	});
	$(".method dd").click(function(){
		$(this).addClass("on").siblings("dd").removeClass("on");
		$(this).parents(".method dl").siblings("dl").find("dd").removeClass("on");
	})

	$('.next_step').click(function(){
		var money = $('#recharge_money').val();
		if(isNaN(money)){
			alert('请输入合法的金额！');
			return false;
		}else if(money > 10000){
			alert('单次充值金额最高不能超过1万元');
			e.preventDefault();
			return false;
		}else if(money < 0.1){
			alert('单次充值金额最低不能低于 0.1 元');
			return false;
		}

		var url="{pigcms{:U('Scenic_user/recharge_save_order')}";
		$.post(url, {money: money}, function(data, textStatus, xhr) {

			if(data.error_code){
				alert(data.msg);
			}else{
				window.location.href =data.msg;
			}
		});
	});

	$('.Withdrawals').click(function(){
		var money = $('#withdraw').val();
		var now_money = Number('{pigcms{$now_user.now_money}');
		var truename  = $('#truename').val();
		if(isNaN(money)){
			alert('请输入合法的金额！');
			return false;
		}else if(money > now_money){
			alert('提现超出范围');
			e.preventDefault();
			return false;
		}else if(money < 0.1){
			alert('单次提现金额最低不能低于 0.1 元');
			return false;
		}

		var url="{pigcms{:U('Scenic_user/withdraw')}";
		$.post(url, {money: money,truename:truename}, function(data, textStatus, xhr) {

			if(data.error_code){
				alert(data.msg);
			}else{
				alert(data.info);
				window.location.reload();
			}
		});
	});
</script>
<include file="Public/footer"/>