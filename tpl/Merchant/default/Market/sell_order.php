<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Market/sell_order')}">{pigcms{:L_("进销存")}</a>
			</li>
			<li class="active">{pigcms{:L_("销售订单")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <div class="form-group">
                        <input type="hidden" name="order_list" id="order_list_arr" value=""/>
                        <form action="{pigcms{:U('Market/sell_order')}" method="get">
                            <input type="hidden" name="c" value="Market"/>
                            <input type="hidden" name="a" value="sell_order"/>
                            <fieldset id="choose_cityarea" province_id="{pigcms{$_GET.province_id}" city_id="{pigcms{$_GET.city_id}" area_id="{pigcms{$_GET.area_id}">
                            <select name="search_type">
                                <option value="order_id" <if condition="$_GET['search_type'] eq 'order_id'">selected='selected'</if> >{pigcms{:L_("订单ID")}</option>
                                <option value="orderid" <if condition="$_GET['search_type'] eq 'orderid'">selected='selected'</if>>{pigcms{:L_("订单编号")}</option>
                                <option value="third_id" <if condition="$_GET['search_type'] eq 'third_id'">selected='selected'</if>>{pigcms{:L_("微信支付流水号")}</option>
                                <option value="merchant_name" <if condition="$_GET['search_type'] eq 'merchant_name'">selected='selected'</if>>{pigcms{:L_("商家名称")}</option>
                                <option value="username" <if condition="$_GET['search_type'] eq 'username'">selected='selected'</if>>{pigcms{:L_("联系人")}</option>
                                <option value="phone" <if condition="$_GET['search_type'] eq 'phone'">selected='selected'</if>>{pigcms{:L_("联系电话")}</option>
                            </select>&nbsp;&nbsp;
                            <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入搜索信息")}'/>
                            {pigcms{:L_("配送方式：")}
                            <select name='is_pick_in_store'>
                                <option value="-1">{pigcms{:L_("全部")}</option>
                                <option value="-2" <if condition="$_GET['search_type'] eq -2">selected='selected'</if> >{pigcms{:L_("到店自提")}</option>
                                <option value="1" <if condition="$_GET['search_type'] eq 1">selected='selected'</if> >{pigcms{:L_("快递发货")}</option>
                                <option value="2" <if condition="$_GET['search_type'] eq 2">selected='selected'</if>>{pigcms{:L_("物流发货")}</option>
                            </select>
                                <br/>
                            {pigcms{:L_("订单状态：")}
                            <select name='status' id="status">
                                <option value="-1">{pigcms{:L_("全部")}</option>
                                <option value="-2" <if condition="$_GET['status'] eq -2">selected='selected'</if> >{pigcms{:L_("下单成功待支付")}</option>
                                <option value="1" <if condition="$_GET['status'] eq 1">selected='selected'</if> >{pigcms{:L_("支付成功")}</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected='selected'</if>>{pigcms{:L_("发货成功")}</option>
                                <option value="3" <if condition="$_GET['status'] eq 3">selected='selected'</if>>{pigcms{:L_("收货成功")}</option>
                            </select>
                            {pigcms{:L_("支付方式：")}
                            <select name='pay_type' id="pay_type">
                                <option value="-1">{pigcms{:L_("全部")}</option>
                                <option value="merchant_money" <eq name="_GET['pay_type']" value='1'>selected='selected'</eq> >{pigcms{:L_("商家余额")}</option>
                                <volist name="pay_method" id="item">
                                    <option value="{pigcms{$key}" <eq name="_GET['pay_type']" value='{pigcms{$key}'>selected='selected'</eq> >{pigcms{$item['name']}</option>
                                </volist>
                            </select>
                            <font color="#000">{pigcms{:L_("日期筛选：")}</font>
                            <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>


                            <button class="btn btn-success" >{pigcms{:L_("查询")}</button>
                        </form>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <a class="btn btn-success"  href="javascript:void(0)" onclick="exports()"  style="float:right;">{pigcms{:L_("导出订单")}</a>
                        <br/>
                        <input type="submit"  style="text-align: left;clear: both;" value="{pigcms{:L_('发货')}" class="btn btn-success" onclick="push_goods_list()"/>
                    </div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
                                    <th style=" width:80px;">
                                        <input type="checkbox" id='checkall'/>{pigcms{:L_("全选")}
                                    </th>
									<th>{pigcms{:L_("订单号")}</th>
									<th>{pigcms{:L_("商品图片")}</th>
									<th>{pigcms{:L_("商品名称")}</th>
									<th>{pigcms{:L_("单价")} ({pigcms{$config['Currency_txt']})</th>
									<th>{pigcms{:L_("批发数量")}</th>
                                    <th>{pigcms{:L_("总价")}({pigcms{$config['Currency_txt']})</th>
                                    <th>{pigcms{:L_("运费")}({pigcms{$config['Currency_txt']})</th>
									<th>{pigcms{:L_("优惠明细")}</th>
									<th>{pigcms{:L_("买家信息")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
									<th class="am-text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$orders">
									<volist name="orders" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td class="norightborder">
                                                <if condition="$vo['status'] eq 1 OR $vo['status'] eq 2">
                                                    <label>
                                                        <input type="checkbox" name="order_id" value="{pigcms{$vo.order_id}" data-k="{pigcms{$first+$i}">
                                                    </label>
                                                </if>
                                            </td>
											<td>{pigcms{$vo.order_id}</td>
											<td><img src="{pigcms{$vo.pic}" width="50" height="50" class="view_img"></td>
											<td>{pigcms{$vo.name}</td>
                                            <php>if ($vo['spec_price'] != '') {</php>
											    <td>{pigcms{$vo.spec_price}</td>
                                            <php>}else{</php>
                                                <td>{pigcms{$vo.price|floatval}</td>
                                            <php>}</php>
											<td>{pigcms{$vo.num}({pigcms{$vo.unit})</td>
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
												 {pigcms{:L_("商家名：")}{pigcms{$vo.merchant_name}<br/>
												 {pigcms{:L_("商家电话：")}<span style="color:green">{pigcms{$vo.merchant_phone}</span><br/>
												 {pigcms{:L_("联系人：")}{pigcms{$vo.username}<br/>
												 {pigcms{:L_("联系电话：")}<span style="color:green">{pigcms{$vo.userphone}</span><br/>
												 {pigcms{:L_("收货地址：")}{pigcms{$vo.address}<br/>
											</td>
											<td>{pigcms{$vo.status_txt}</td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
                                                    <if condition="($vo['status'] eq 1 OR $vo['status'] eq 2) && ($vo['offline_status'] eq 1 || $vo['pay_type'] neq 'offline')">
                                                        <php>if ($vo['status'] == 1 && $vo['is_pick_in_store']==0 && $vo['pick_id']) {</php>
                                                        <a class="true_offline" data-link_url="{pigcms{:U('Market/market_pick',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id']))}" href="javascript:void(0);">{pigcms{:L_("自提")}</a>
                                                        <php>} else {</php>
                                                        <a href="{pigcms{:U('Market/push',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id']))}">
                                                            {pigcms{:L_("发货")}
                                                        </a>
                                                        <php>}</php>
                                                    </if>
                                                    <if condition="$vo['offline_status'] eq 0 && $vo['pay_type'] eq 'offline'">
                                                        <a class="true_offline" data-link_url="{pigcms{:U('Market/offline_get_money',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id']))}" href="javascript:void(0);">{pigcms{:L_("确认线下收钱")}</a>
                                                    </if>
													<a class="tpl-table-black-operation-green layer-iframe" href="{pigcms{:U('Market/order_detail',array('order_id'=>$vo['order_id'], 'type' => 'sell'))}">{pigcms{:L_("操作订单")}</a>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td colspan="10" >{pigcms{:L_("暂无订单信息")}</td></tr>
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
<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/market_area.js"></script>
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
    var order_arr = [];
    var info =  sessionStorage.getItem("sell_order_check_info");
    if (info) {
        $('#order_list_arr').val(info);
        order_arr = info.split(",");
        console.log('info----', order_arr);
    }
    var check_num=0;
    var merchant_list = '';
    var count=$('input[name="order_id"]').length;
    $('input[name="order_id"]').each(function(index,val){
        console.log('val----', $(this).val());
        console.log('order_arr----', order_arr);
        if($.inArray($(this).val(), order_arr)>-1){
            $(this).prop('checked',true);
            check_num++;
        }
    });
    /*{pigcms{:L_("是否全选中")}*/
    if(check_num==count){
        $("input#checkall").prop('checked',true);
    }

    // "单选")}
    $('input[name="order_id"]').click(function(){
        var order_id=$(this).val();
        if($(this).is(":checked")){
            order_arr.push(order_id);
            check_num++;
        }else{
            order_arr.splice($.inArray(order_id, order_arr), 1);
            check_num--;
        }
        console.log('check_num1----', check_num);
        console.log('count1----', count);
        console.log('order_arr1----', order_arr);
        if(check_num<count){
            $("input#checkall").prop('checked',false);
        }else{
            $("input#checkall").prop('checked',true);
        }
        merchant_list = order_arr.join(',');
        $('#order_list_arr').val(merchant_list);
        console.log('merchant_list1----', merchant_list);
        sessionStorage.setItem("sell_order_check_info", merchant_list);
    });
    /*{pigcms{:L_("批量选择")}*/
    $(document).on('click','#checkall',function(){
        var flag=this.checked;
        if(flag){
            check_num=count;
        }else{
            check_num=0;
        }
        console.log('order_arr2-1--', order_arr);
        $('input[name="order_id"]').each(function(){
            $(this).prop('checked',flag);
            var order_id=$(this).val();
            var key=$.inArray(order_id,order_arr);
            if(key>=0){
                if(!flag){
                    order_arr.splice(key, 1);
                }
            }else{
                if(flag){
                    order_arr.push(order_id);
                }
            }
        });
        merchant_list = order_arr.join(',');
        $('#order_list_arr').val(merchant_list);
        sessionStorage.setItem("sell_order_check_info", merchant_list);
        console.log('check_num2----', check_num);
        console.log('count2----', count);
        console.log('order_arr2-2----', order_arr);
        console.log('merchant_list2----', merchant_list);
    });
});
function push_goods_list() {
    if (!$('#order_list_arr').val()) {
        layer.alert('{pigcms{:L_("请选择可发货的单号！")}');
        return false;
    }
    var url_info =  "{pigcms{:U('Market/batch_push')}&order_list="+$('#order_list_arr').val();
    parent.layer.open({
        title:false,
        closeBtn: 0,
        type:2,
        content:url_info,
        area:['600px','500px'],
        shade: 0.2,
        cancel:function(){

        },
        move:false
    });
}
var url = "{pigcms{$config.site_url}";
var export_url = "{pigcms{:U('Market/export')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>