<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Market/order')}" class="on">订单列表</a>
			</ul>
		</div>
        <table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Market/order')}" method="get">
						<input type="hidden" name="c" value="Market"/>
						<input type="hidden" name="a" value="order"/>
                        <if condition="$admin_area neq 3">
                            <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline;margin-bottom: 10px;"></div>
                            　　　
                        </if>
                        <select name="search_type">
                            <option value="order_id" <if condition="$_GET['search_type'] eq 'order_id'">selected='selected'</if> >{pigcms{:L_("订单ID")}</option>
                            <option value="orderid" <if condition="$_GET['search_type'] eq 'orderid'">selected='selected'</if>>{pigcms{:L_("订单编号")}</option>
                            <option value="third_id" <if condition="$_GET['search_type'] eq 'third_id'">selected='selected'</if>>{pigcms{:L_("微信支付流水号")}</option>
                            <option value="merchant_name" <if condition="$_GET['search_type'] eq 'merchant_name'">selected='selected'</if>>{pigcms{:L_("商家名称")}</option>
                            <option value="username" <if condition="$_GET['search_type'] eq 'username'">selected='selected'</if>>{pigcms{:L_("联系人")}</option>
                            <option value="phone" <if condition="$_GET['search_type'] eq 'phone'">selected='selected'</if>>{pigcms{:L_("联系电话")}</option>
                        </select>&nbsp;&nbsp;
                        <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入搜索信息")}'/>
                        批发分类筛选:
                        <select id="fid" name="fid">
                            <option value="0" selected="selected">请选择</option>
                            <volist name="fid_list" id="vo">
                                <option value="{pigcms{$vo['id']}" <if condition="$fid eq $vo['id']">selected="selected"</if>>{pigcms{$vo['name']}</option>
                            </volist>
                        </select>
                        <select id="cid" name="cid" style="display: none;">
                        </select>
                        {pigcms{:L_("配送方式：")}
                        <select name='is_pick_in_store'>
                            <option value="-1">{pigcms{:L_("全部")}</option>
                            <option value="-2" <if condition="$_GET['is_pick_in_store'] eq -2">selected='selected'</if> >{pigcms{:L_("到店自提")}</option>
                            <option value="1" <if condition="$_GET['is_pick_in_store'] eq 1">selected='selected'</if> >{pigcms{:L_("快递发货")}</option>
                            <option value="2" <if condition="$_GET['is_pick_in_store'] eq 2">selected='selected'</if>>{pigcms{:L_("物流发货")}</option>
                        </select>
                        {pigcms{:L_("订单状态：")}
                        <select name='status' id="status">
                            <option value="-1">{pigcms{:L_("全部")}</option>
                            <option value="-2" <if condition="$_GET['status'] eq -2">selected='selected'</if> >{pigcms{:L_("下单成功待支付")}</option>
                            <option value="1" <if condition="$_GET['status'] eq 1">selected='selected'</if> >{pigcms{:L_("支付成功待发货")}</option>
                            <option value="2" <if condition="$_GET['status'] eq 2">selected='selected'</if>>{pigcms{:L_("发货成功")}</option>
                            <option value="3" <if condition="$_GET['status'] eq 3">selected='selected'</if>>{pigcms{:L_("收货成功")}</option>
                        </select>
                        {pigcms{:L_("支付方式：")}
                        <select name='pay_type' id="pay_type">
                            <option value="-1">{pigcms{:L_("全部")}</option>
                            <option value="merchant_money" <if condition="$_GET['pay_type'] eq 'merchant_money'">selected='selected'</if> >{pigcms{:L_("商家余额")}</option>
                            <volist name="pay_method" id="item">
                                <option value="{pigcms{$key}" <if condition="$_GET['pay_type'] eq $key">selected='selected'</if> >{pigcms{$item['name']}</option>
                            </volist>
                        </select>
                        <font color="#000">{pigcms{:L_("日期筛选：")}</font>
                        <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                        <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>


                        <input type="submit" value="查询" class="button"/>　
					</form>
                    <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 50px;">导出订单</a>
				</td>
			</tr>
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>订单号</th>
                            <th>订单编号</th>
							<th>商品图片</th>
							<th>商品名称</th>
							<th>单价 ({pigcms{$config['Currency_txt']})</th>
							<th>批发数量</th>
							<th>总价 ({pigcms{$config['Currency_txt']})</th>
							<th>优惠明细</th>
							<th>卖家信息</th>
							<th>买家信息</th>
							<th>订单状态</th>
                            <if condition="$config['merchant_integral_mall_control'] eq 1">
                                <th>{pigcms{:L_("订单类型")}</th>
                            </if>
							<th>操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($orders)">
							<volist name="orders" id="vo">
								<tr>
									<td>{pigcms{$vo.order_id}</td>

                                    <php>if ($vo['orderid'] > 0) {</php>
                                    <td>{pigcms{$vo.orderid}</td>
                                    <php>}else{</php>
                                    <td>-</td>
                                    <php>}</php>
									<td><img src="{pigcms{$vo.pic}" width="50" height="50" class="view_msg"></td>
									<td>{pigcms{$vo.name}</td>
                                    <php>if ($vo['spec_price'] != '') {</php>
                                    <td>{pigcms{$vo.spec_price}</td>
                                    <php>}else{</php>
                                    <td>{pigcms{$vo.price|floatval}</td>
                                    <php>}</php>
									<td>{pigcms{$vo.num}({pigcms{$vo.unit})</td>
									<td>{pigcms{$vo.money|floatval}</td>
									<td>{pigcms{$vo.discount_info_txt}</td>
									<td>
									                 商家名:{pigcms{$vo.merchant_name}<br/>
									                 商家电话:<b>{pigcms{$vo.merchant_phone}</b><br/>
									                 店铺名:{pigcms{$vo.store_name}<br/>
									                 店铺电话:<b>{pigcms{$vo.merchant_phone}</b><br/>
									</td>
									<td>
									                 商家名:{pigcms{$vo.buy_merchant_name}<br/>
									                 商家电话:<b>{pigcms{$vo.buy_merchant_phone}</b><br/>
									                 联系人:{pigcms{$vo.username}<br/>
									                 联系电话:<b>{pigcms{$vo.userphone}</b><br/>
									                 收货地址:{pigcms{$vo.address}<br/>
									</td>
									<td class="status">{pigcms{$vo.status_txt}</td>
                                    <if condition="$config['merchant_integral_mall_control'] eq 1">
                                        <td>
                                            <if condition="$vo['type'] eq 1">
                                                {pigcms{:L_("虚拟批发")}
                                                <else/>
                                                {pigcms{:L_("真实批发")}
                                            </if>
                                        </td>
                                    </if>
									<td>
									    <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Market/order_detail',array('order_id'=>$vo['order_id'],'frame_show'=>true))}','查看订单详情',720,520,true,false,false,false,'detail',true);">查看</a> 
									    <if condition="$vo['status'] eq 2">
									    <span>|</span> <a href="javascript:void(0);" data-href="{pigcms{:U('Market/pull', array('order_id'=>$vo['order_id']))}" class="refund">确认收货</a>
									    </if>
									</td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="15">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="15">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
	<script>
$(function(){
    var ajax_cid_url = "{pigcms{:U('Market/ajax_cid')}";
    var se_fid = "{pigcms{$fid}";
    var se_cid = "{pigcms{$cid}";
	$('.refund').click(function(){
		var get_url = $(this).data('href'), obj = $(this);
		window.o2oTop.art.dialog({
			content: '您确定要将此单改成已收货吗',
			lock: true,
			ok: function () {
				if(typeof this.close == 'function'){
					this.close();
				}
				$.get(get_url,function(response){
					console.log(response);
					if (response.error_code == false) {
						obj.parents('tr').find('.status').html('<span style="color:green">已收货</span>');
						obj.prev('span').remove();
						obj.remove();
					} else {
						window.o2oTop.msg('',response.msg);
					}
				},'json');
				return false;
			},
			cancelVal: '取消',
			cancel: true
		});
	});

	$('#fid').change(function(){
	    if($('#fid').val()==0){
	        $('#cid').hide();
	    }
        $.get(ajax_cid_url,{fid:$('#fid').val()},function(response){
            var str = '<option value="-1">请选择<\/script>';
            if (response.length > 0) {
                for( var i in response){
                    str += '<option value="'+response[i]['id']+'"';
                    if(se_cid==response[i]['id']){
                     str +=  'selected="selected";'
                    }
                    str += '>'+ response[i]['name'] +'</option>';
                }
                $('#cid').show();
                $('#cid').html(str);
            }else{
                $('#cid').hide();
                $('#cid').html(str);
            }
        },'json');
    });
    if(se_fid > 0){
        $("#fid").trigger("change");
    }
});
var url = "{pigcms{$config.site_url}";
var export_url = "{pigcms{:U('Market/export')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<include file="Public:footer"/>
