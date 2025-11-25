<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Market/buy_order')}">{pigcms{:L_("进销存")}</a>
			</li>
			<li class="active">{pigcms{:L_("进货订单")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <div class="form-group">
                        <a class="btn btn-success"  href="javascript:void(0)" onclick="exports()"  style="float:right;">{pigcms{:L_("导出订单")}</a> 
                    </div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50" class="button-column">{pigcms{:L_("订单号")}</th>
									<th width="50" class="button-column">{pigcms{:L_("商品图片")}</th>
									<th class="button-column">{pigcms{:L_("商品名称")}</th>
									<th width="100" class="button-column">{pigcms{:L_("单价")} ({pigcms{$config['Currency_txt']})</th>
									<th width="100" class="button-column">{pigcms{:L_("批发数量")}</th>
                                    <th width="80" class="button-column">{pigcms{:L_("总价")} ({pigcms{$config['Currency_txt']})</th>
                                    <th width="80" class="button-column">{pigcms{:L_("运费")} ({pigcms{$config['Currency_txt']})</th>
									<th width="100" class="button-column">{pigcms{:L_("优惠明细")}</th>
									<th width="100" class="button-column">{pigcms{:L_("卖家信息")}</th>
									<th width="80" class="button-column">{pigcms{:L_("订单状态")}</th>
									<th width="200" class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$orders">
									<volist name="orders" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td class="button-column">{pigcms{$vo.order_id}</td>
											<td class="button-column"><img src="{pigcms{$vo.pic}" width="50" height="50" class="view_img"></td>
											<td class="button-column">{pigcms{$vo.name}</td>
											<td class="button-column">{pigcms{$vo.price|floatval}</td>
											<td class="button-column">{pigcms{$vo.num}({pigcms{$vo.unit})</td>
                                            <td class="button-column">
                                                <if condition="$vo['actual_money']">
                                                    {pigcms{$vo.actual_money|floatval}
                                                    <else/>
                                                    {pigcms{$vo.money|floatval}
                                                </if>
                                            </td>
                                            <td class="button-column">
                                                <if condition="$vo['total_express_fee']">
                                                    {pigcms{$vo.total_express_fee|floatval}
                                                    <else/>
                                                    -
                                                </if>
                                            </td>
                                            <td>
                                                <php>if ($vo['discount_info'] != '' || ($vo['system_discount_info']>0 && $vo['system_discount_info'] < 100)){</php>
                                                    <if condition="$vo.discount_info neq ''">
                                                        {pigcms{$vo.discount_info_txt}
                                                    </if>
                                                    <if condition="$vo.system_discount_info gt 0">
                                                        {pigcms{:L_("享受")}<span>{pigcms{$vo['system_discount_info']/10|floatval}</span>{pigcms{:L_("折优惠，实际以")}<span>{pigcms{$vo['money']|floatval}</span>{pigcms{:L_("元购买")}
                                                    </if>
                                                <php>} else {</php>
                                                    {pigcms{$vo.discount_info_txt}
                                                <php>}</php>
                                            </td>
											<td>
											                 {pigcms{:L_("商家名")}:{pigcms{$vo.merchant_name}<br/>
											                 {pigcms{:L_("商家电话")}:<span style="color:green">{pigcms{$vo.merchant_phone}</span><br/>
											                 {pigcms{:L_("店铺名")}:{pigcms{$vo.store_name}<br/>
											                 {pigcms{:L_("店铺电话")}:<span style="color:green">{pigcms{$vo.merchant_phone}</span><br/>
											</td>
											<td class="button-column">{pigcms{$vo.status_txt}</td>
											<td class="">
											    <if condition="$vo['status'] eq 10">
	                                                <php>if ($vo['fid']) {</php>
	                                                <a title="{pigcms{:L_("去支付")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Market/cart',array('fid'=>$vo['fid'],'group_id'=>$vo['group_id'],'team_id'=>$vo['team_id']))}">{pigcms{:L_("支付订单")}</a>|
	                                                <php> } else { </php>
													<a title="{pigcms{:L_("去支付")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Market/buy',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id'],'group_id'=>$vo['group_id'],'team_id'=>$vo['team_id']))}">{pigcms{:L_("支付订单")}</a>|
	                                                <php>} </php>
													<a title="{pigcms{:L_("删除")}" class="green" onclick="artconcancel({pigcms{$vo.order_id});" href="javascript:void(0);">{pigcms{:L_("删除订单")}</a>|
											    <elseif condition="$vo['status'] eq 1" />
                                                    <php>if ($vo['status'] == 1 && $vo['is_pick_in_store']==0 && $vo['pick_id']) {</php>
                                                        <a class="true_offline" data-link_url="{pigcms{:U('Market/market_pick',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id']))}" href="javascript:void(0);">{pigcms{:L_("自提")}</a>
                                                    <php>}</php>

											    	<eq name='vo.group_id' value='0'>
                                                		<a title="{pigcms{:L_("删除")}" class="green" onclick="artconcancel({pigcms{$vo.order_id});" href="javascript:void(0);">{pigcms{:L_("删除订单")}</a>|
                                                	<else/>
                                                		<a href="javascript:void(0);" class="green group_btn" data-clipboard-text="{pigcms{$config.site_url}{pigcms{:U('Market/market',array('goods_id'=>$vo['goods_id'],'group_id'=>$vo['group_id'],'team_id'=>$vo['team_id'],'type'=>'group'))}" >{pigcms{:L_("拷贝参团地址")}</a>|
                                                	</eq>
											    <elseif condition="$vo['status'] eq 2" />
											    	<a class="btn btn-success" onclick="artconfirms({pigcms{$vo.order_id});">{pigcms{:L_("收货")}</a>  
											    <elseif condition="$vo['status'] eq 3" />
                                                    <php>if ($is_market_goods_upper_store!=1){</php>
                                                    <if condition="$vo['store_count'] eq 1">
                                                        <a title="{pigcms{:L_("上架")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Market/add_to_store',array('order_id' => $vo['order_id'], 'store_id' => $vo['to_store_id']))}">{pigcms{:L_("上架")}</a>
                                                    <elseif condition="$vo['store_count'] gt 1" />
                                                        <a title="{pigcms{:L_("上架")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Market/select_store', array('order_id' => $vo['order_id']))}">{pigcms{:L_("上架")}</a>
                                                    </if>
                                                    <php>}</php>

                                                    <php>if ($is_publish_integral_mall==1 && !$vo['integral_mall_goods_id']){</php>
                                                        <a title="{pigcms{:L_("发布到商家积分商城")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Integral_mall/goods_save',array('order_id' => $vo['order_id'], 'type' => 'market'))}">{pigcms{:L_("发布到商家积分商城")}</a>
                                                    <php>}</php>
											    <elseif condition="$vo['status'] eq 4" />
	                                                <a title="{pigcms{:L_("已退款")}" class="green" style="padding-right:8px;" href="javascript:;">{pigcms{:L_("已退款")}</a>|
													
											    </if>
											    <a title="{pigcms{:L_("操作订单")}" class="green handle_btn" href="{pigcms{:U('Market/order_detail', array('order_id' => $vo['order_id'], 'type' => 'buy'))}">{pigcms{:L_("查看详情")}</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="10" >{pigcms{:L_("暂无订单信息")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
$(function(){
    var load = false;
    $('.true_offline').click(function () {
        if (load) {
            return false;
        }
        load = true;
        var obj = $(this);
        var url = obj.data('link_url');
        layer.open({
            content:'{pigcms{:L_("你确定这样操作吗？操作后可能不能恢复！")}',
            btn:['{pigcms{:L_("确定','取消")}'],
            yes:function(index){
                $.post(url,function(res){
                    layer.close(index);
                    layer.open({
                        content:res.info,
                        btn:['{pigcms{:L_("确定")}'],
                        yes:function(index2){
                            if(res.status){
                                layer.close(index2);
                                window.location.reload();
                            }else{
                                layer.close(index2);
                                window.location.reload();
                            }
                            load = false;
                        }
                    });
                });
            },
            cancel:function(index){
                load = false;
                layer.close(index);
            }
        });
    });


	$('.handle_btn').live('click',function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("操作订单")}',
			padding: 0,
			width: 1500,
			height: 520,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});
    $('.view_img').click(function(){
        var obj = $(this);
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            skin: 'layui-layer-nobg', //"没有背景色")}
            shadeClose: true,
            content: '<img src="' + obj.attr('src') + '" style="height:100%;width:100%"/>'
        });
    });
});
function artconfirms(order_id) {
	artDialog({	
			content:'{pigcms{:L_("您确认您已经收到货了，确认后您的钱就付给对方，不能返回了！")}',
			lock:true,
			style:'succeed noClose'
		},
		function(){
			$.post("{pigcms{:U('pull')}", {'order_id':order_id}, function(response){
			     if (response.error_code) {
			    	 artDialog({content:response.msg});
			     } else {
			    	 window.location.reload();
			     }
			}, 'json');
		}
	);
}
function artconcancel(order_id) {
    artDialog({ 
            content:'{pigcms{:L_("您确定要删除该订单吗？")}',
            lock:true,
            style:'succeed noClose'
        },
        function(){
            $.post("{pigcms{:U('cancel')}", {'order_id':order_id}, function(response){
                 if (response.status == 0) {
                     artDialog({content:response.info});
                 } else {
                     window.location.reload();
                 }
            }, 'json');
        }
    );
}
</script>
<script type="text/javascript">
$(function(){	
var clipboard = new Clipboard('.group_btn');

clipboard.on('success', function(e) {
    layer.alert('{pigcms{:L_("拷贝成功，请分享给需要参团的商家使用！")}',{icon:1});
});

clipboard.on('error', function(e) {
    layer.alert('{pigcms{:L_("抱歉，该浏览器不支持该插件，请更换浏览器操作！")}',{icon:2});
});
    
});
var url = "{pigcms{$config.site_url}";
var export_url = "{pigcms{:U('Market/buy_order_export')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>