<include file="Public:header"/>
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin: 0px 0px;">
            <volist name="status" id="vo">
				<tr>
					<th>
                        <p class="time">{pigcms{$vo.dateline|date="Y-m-d H:i",###}</p>
						<if condition="$vo['status'] eq 0">
							<h3>订单生成成功</h3> <p>订单编号：{pigcms{$order.real_orderid}</p>
						<elseif condition="$vo['status'] eq 1"/> 
							<h3>订单支付成功</h3> <p>订单编号：{pigcms{$order.real_orderid}</p>
						<elseif condition="$vo['status'] eq 2"/>
							<h3>店员接单</h3> 
							<p>店员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 正在为您准备商品</p>
						<elseif condition="$vo['status'] eq 3"/>
							<php>if ($vo['from_type'] == 0) { </php>
								<h3>配送员接单</h3>
								<p>配送员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 正在赶往店铺取货</p>
							<php>}else{</php>
								<h3>分配配送员</h3>
								<p>配送员:<if condition="$vo['name']">订单已分配给<strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>，等待配送员接单</p>
							<php>}</php>
						<elseif condition="$vo['status'] eq 4"/> 
							<h3>配送员到店</h3> 
							<p>配送员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>已到店，准备配送，请耐心等待</p>
						<elseif condition="$vo['status'] eq 5"/> 
							<h3>配送员配送中</h3> 
							<p>配送员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>正快速向您靠拢，请耐心等待！</p>
						<elseif condition="$vo['status'] eq 6"/>
							<h3>订单已完成</h3> 
							<p><php>if($order['is_pick_in_store'] < 2){</php>配送员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>已完成配送，欢迎下次光临！<php>}else{</php>订单编号：{pigcms{$order.real_orderid}<php>}</php></p>
						<elseif condition="$vo['status'] eq 7"/>
							<php>if ($order['is_pick_in_store'] == 3) { </php>
								<h3>店员已发货</h3> 
								<p>店员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 已发货给快递公司<strong style="color:red">【{pigcms{$order['express_name']}】</strong>，快递单号:<strong style="color:green">{pigcms{$order['express_number']}</strong></p>
							<php>}else{</php>
								<h3>店员验证消费</h3>
								<p>店员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>将订单改成已消费</p>
							<php>}</php>
						<elseif condition="$vo['status'] eq 8"/>
							<h3>完成评论</h3>
							<p>用户已完成评论，谢谢您提出宝贵意见！</p>
						<elseif condition="$vo['status'] eq 9"/>
                            <php>if ($order['pay_type'] != 'offline') { </php>
                            <h3>已完成退款</h3>
                            <p><php> if (empty($vo['note'])) { </php>用户已完成退款<php> } else { </php>{pigcms{$vo.note}<php>}</php></p>
                            <php>}else{</php>
                            <h3>已取消</h3>
                            <php>}</php>
						<elseif condition="$vo['status'] eq 10"/>
							<h3>已取消订单</h3>
							<p><php> if (empty($vo['note'])) { </php>用户已经取消订单<php> } else { </php>{pigcms{$vo.note}<php>}</php></p>
						<elseif condition="$vo['status'] eq 11"/>
							<h3>商家分配自提点</h3> 
							<p>店员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>给您分配</p>
						<elseif condition="$vo['status'] eq 12"/> 
							<h3>商家发货到自提点</h3> 
							<p>店员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a>已经给您发货到配送点</p>
						<elseif condition="$vo['status'] eq 13"/> 
							<h3>自提点已接货</h3> 
							<p>自提点<strong style="color:red">【{pigcms{$vo.name}】</strong> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 已经接到您的货物了</p>
						<elseif condition="$vo['status'] eq 14"/> 
							<h3>自提点已发货</h3> 
							<p>自提点<strong style="color:red">【{pigcms{$vo.name}】</strong> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 已经给您发货了</p>
						<elseif condition="$vo['status'] eq 15"/> 
							<h3>您在自提点取货</h3> 
							<p>用户在自提点<php>if($vo['name']){</php><strong style="color:red">【{pigcms{$vo.name}】</strong><php>}</php> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 已经把用户的货提走了！</p>
						<elseif condition="$vo['status'] eq 30"/> 
							<h3>店员为您修改了价格</h3>
							<p>店员<strong style="color:red">【{pigcms{$vo.name}】</strong> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 已将订单的总价修改成{pigcms{$vo.note}</p>
						<elseif condition="$vo['status'] eq 31"/> 
							<h3>配送员放弃配送</h3>
							<p>{pigcms{$vo.note}</p>
						<elseif condition="$vo['status'] eq 34"/> 
							<h3>订单已被取消</h3>
							<p>已为用户退款！</p>
						<elseif condition="$vo['status'] eq 35"/>
							<h3>配送员接单</h3>
							<p>配送员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 正在赶往店铺取货</p>
						<elseif condition="$vo['status'] eq 36"/> 
							<h3>更换配送员</h3>
							<p>配送员:<if condition="$vo['name']"><strong style="color:red">【{pigcms{$vo.name}】</strong></if> <a class="tel-btn" href="tel:{pigcms{$vo.phone}">{pigcms{$vo.phone}</a> 正快速向您靠拢，请耐心等待！
								<br/><span>{pigcms{$vo.note}</span>
							</p>
						</if>
					</th>
				</tr>
            </volist>
        </table>
<include file="Public:footer"/>