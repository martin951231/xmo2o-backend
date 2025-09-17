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
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单号")}</th>
									<th>{pigcms{:L_("商品图片")}</th>
									<th>{pigcms{:L_("商品名称")}</th>
									<th>{pigcms{:L_("积分")} </th>
									<th>{pigcms{:L_("兑换数量")}</th>
									<th>{pigcms{:L_("总计积分")}</th>
									<th>{pigcms{:L_("运费")}</th>
									<th>{pigcms{:L_("信息")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
									<th class="am-text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$orders">
									<volist name="orders" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.order_id}</td>
											<td><img src="{pigcms{$vo.pic_image}" width="50" height="50" class="view_img"></td>
											<td>{pigcms{$vo.goods_name}</td>
											<td>{pigcms{$vo.payment_pure_integral|floatval}</td>
                                            <td>{pigcms{$vo.num}</td>
                                            <td>{pigcms{$vo.total_integral|floatval}</td>

                                            <td class="button-column">
                                                <if condition="$vo['is_pick_in_store'] eq 1">
                                                    {pigcms{$config['Currency_symbol']}{pigcms{$vo.freight_charge|floatval}
                                                    <span style="font-size: 12px;color: red;"><php>if ($vo['freight_charges'] == 1 && $vo['freight_charge'] > 0) {echo '(商户承担)';}</php></span>
                                                    <else/>
                                                    -
                                                </if>
                                            </td>
											<td>
												 {pigcms{:L_("联系人：")}{pigcms{$vo.username}<br/>
												 {pigcms{:L_("联系电话：")}<span style="color:green">{pigcms{$vo.userphone}</span><br/>

                                                <if condition="$vo['is_pick_in_store'] eq 1">
                                                    {pigcms{:L_("收货地址：")}{pigcms{$vo.address}<br/>
                                                    <else/>
                                                    {pigcms{:L_("自提地址：")}{pigcms{$vo.address}<br/>
                                                </if>
											</td>
											<td>{pigcms{$vo.status_txt}</td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
                                                    <if condition="$vo['is_pick_in_store'] eq 1 && ($vo['status'] eq 1 || $vo['status'] eq 2) && ($vo['goods_source_type'] neq 3 || $vo['look_mer_id'] eq $vo['virtual_wholesale_mer_id'])">
                                                        <a href="{pigcms{:U('Integral_mall/push',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id']))}">{pigcms{:L_("发货")}</a>
                                                    </if>
                                                    <if condition="$vo['is_pick_in_store'] eq 1 && ($vo['status'] eq 1 || $vo['status'] eq 2) && $vo['goods_source_type'] eq 3 && $vo['look_mer_id'] neq $vo['virtual_wholesale_mer_id']">
                                                        <a class="virtual_wholesale_mer" data-merchant_name="{pigcms{$vo['virtual_merchant']['name']}">{pigcms{:L_("虚拟批发")}</a>
                                                    </if>
                                                    <if condition="$vo['is_pick_in_store'] eq 0 && $vo['status'] eq 1">
                                                        <a href="{pigcms{:U('Integral_mall/push',array('goods_id'=>$vo['goods_id'], 'order_id'=>$vo['order_id']))}">{pigcms{:L_("用户自提")}</a>
                                                    </if>
													<a class="tpl-table-black-operation-green layer-iframe" href="{pigcms{:U('Integral_mall/order_detail',array('order_id'=>$vo['order_id']))}">{pigcms{:L_("订单详情")}</a>
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
<script>
$(function(){
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

    $('.virtual_wholesale_mer').click(function () {
        var name = $(this).data('merchant_name');
        layer.open({
            content: '请联系商家【' + name + '】进行发货！'
            ,btn: ['确定']
            ,yes: function(index){
                layer.close(index);
            }
        });
    });

    var order_arr = [];
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
</script>
<include file="Public:footer"/>