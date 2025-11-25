<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Village_group/send_list')}&goods_id={pigcms{$_GET['goods_id']}&cat_fid={pigcms{$_GET['cat_fid']}" class="on">{pigcms{:L_("发货列表")}</a>
			</li>
		</ul>
	</div>

	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="clearfix">
						<table class="search_table" width="100%">
							<tr>
								<td>
									<form  method="get" id="form">
										<input type="hidden" name="c" value="Village_group"/>
										<input type="hidden" name="a" value="send_list"/>
										<input type="hidden" name="goods_id" id="goods_id" value="{pigcms{$_GET.goods_id}"/>
									
										<select name="searchtimetype" id="searchtimetype">
											<option value="pay_time" <if condition="$_GET['searchtimetype'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
											<option value="arrive_cutting_time" <if condition="$_GET['searchtimetype'] eq 'arrive_cutting_time'">selected="selected"</if>>截单时间</option>
											<option value="arrive_time" <if condition="$_GET['searchtimetype'] eq 'arrive_time'">selected="selected"</if>>到达时间</option>
										</select>
										<input type="text" class="input-text" name="begin_time" style="width:120px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>			   
										<input type="text" class="input-text" name="end_time" style="width:120px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
										{pigcms{:L_("筛选")}: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
										<select name="searchtype" id="searchtype">
											<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("商品标题")}</option>
											<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单")}id</option>
											<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>{pigcms{:L_("商品编号")}</option>
											<option value="village_name" <if condition="$_GET['searchtype'] eq 'village_name'">selected="selected"</if>>{pigcms{:L_("社区名称")}</option>
										</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
										
										<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>
										<!-- <a href="{pigcms{:U('Village_group/send_list')}&time=1&begin_time={pigcms{:date('Y-m-d')}&end_time={pigcms{:date('Y-m-d')}&goods_id={pigcms{$_GET['goods_id']}" class="nativea <if condition="$_GET['time'] eq 1">on</if>">{pigcms{:L_("今天发货单")}</a>
										
										<a href="{pigcms{:U('Village_group/send_list')}&time=2&begin_time={pigcms{:date("Y-m-d",strtotime("+1 day"))}&end_time={pigcms{:date("Y-m-d",strtotime("+1 day"))}&goods_id={pigcms{$_GET['goods_id']}" class="nativea <if condition="$_GET['time'] eq 2">on</if>">{pigcms{:L_("明天发货单")}</a> -->
										
										<a  href="javascript:void(0)" class="button send_goods" url="{pigcms{:U('send_selected')}" parameter="send_all=1">{pigcms{:L_("一键发货")}</a>
										<a  href="javascript:void(0)" class="button send_goods" url="{pigcms{:U('get_selected')}" parameter="get_all=1">{pigcms{:L_("一键到货")}</a>
										<a  href="javascript:void(0)" class="button" onclick="PrePrint();">{pigcms{:L_("打印当前发货单")}</a>
										<a  href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">{pigcms{:L_("导出发货单")}</a>
									</form>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<form name="myform" id="myform" action="" method="post">
					<div class="table-list">
						<style>
						.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
						</style>
						<table class="table table-striped table-bordered table-hover">
							<colgroup>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
							
							</colgroup>
							<thead>
								<tr>
									<th><input type="checkbox" name="checkAll" value='1' />全选</th>
									<if condition="!$_GET['goods_id']">
										<th>{pigcms{:L_("商品名称")}</th>
									</if>
									<th>{pigcms{:L_("商家")}</th>
									<th>{pigcms{:L_("订单")}ID</th>
									<th>{pigcms{:L_("团长信息")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
									<th>{pigcms{:L_("自提地址")}</th>
									<th>{pigcms{:L_("进货总金额")}</th>
									<th>{pigcms{:L_("总金额")}</th>
									<th>{pigcms{:L_("购买数量")}</th>
									<th>{pigcms{:L_("支付时间")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
                                    <th>{pigcms{:L_("详细信息")}</th>
                                    <th>{pigcms{:L_("备注")}</th>
									<th>{pigcms{:L_("配送状态")}</th>
							
								</tr>
							</thead>
							<tbody>
                            <php>if (is_array($start_list)) {</php>
                                <volist name="start_list" id="vo">
                                    <tr>
                                    	<td><if condition="in_array($vo['deliver_status'],[0,1]) AND $vo['status'] egt 1 AND $vo['status'] neq 3 AND $vo['status'] lt 10"><input type="checkbox" name="checkThis" value='{pigcms{$vo.order_id}={pigcms{$vo.goods_id}' /></if></td>
                                        <if condition="!$_GET['goods_id']">
                                            <td>{pigcms{$vo.name}</td>
                                        </if>
                                        <td class="order">{pigcms{$vo.merchant_name}(ID:{pigcms{$vo.mer_id})</td>
                                        <td class="order">{pigcms{$vo.order_id}</td>
                                        <td>
                                            {pigcms{:L_("团长")}:{pigcms{$vo.header_info.name}<br>
                                            {pigcms{:L_("手机")}:{pigcms{$vo.header_info.phone}<br>
                                        </td>
                                        <td>
                                            {pigcms{:L_("用户")}id:{pigcms{$vo.uid}<br>
                                            {pigcms{:L_("用户手机")}:{pigcms{$vo.phone}<br>
                                            {pigcms{:L_("用户昵称")}:{pigcms{$vo.nickname}<br>
                                        </td>
                                        <if condition="$vo['pick_addr'] neq ''">
                                            <td>{pigcms{$vo.pick_addr}</td>
                                            <else/>
                                            <td>{pigcms{$vo.header_info.pick_address.pick_addr}</td>
                                        </if>
                                        <td>{pigcms{$vo.all_purchase_price|floatval}</td>
                                        <td>{pigcms{$vo.all_price|floatval}</td>
                                        <td>{pigcms{$vo.all_num|floatval}</td>
                                        <td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
                                        <td>
                                            <if condition="$vo.status eq 2 ">
                                                <font color="green">{pigcms{:L_("已完成")}</font>
                                                <elseif condition="$vo['status'] egt 1 AND $vo['status'] lt 10"/>
                                                <font color="green">{pigcms{:L_("已支付")}</font>
                                                <elseif condition="$vo['status'] eq 11 "/>
                                                <font color="red">{pigcms{:L_("已取消")}</font>
                                                <elseif condition="$vo['status'] eq 10 "/>
                                                <font color="red">{pigcms{:L_("超时未支付")}</font>
                                                <elseif condition="$vo['status'] eq 0 "/>
                                                <font color="red">{pigcms{:L_("未支付")}</font>
                                            </if>
                                        </td>
                                        <td>
                                            <if condition="$vo['village_group_type'] eq 1">
                                                {pigcms{:L_("类型:周期购")}<br>
                                                <if condition="$vo['d_send_time'] gt 0">
                                                    {pigcms{:L_("期望派送")}:{pigcms{$vo['d_send_time']|date="Y-m-d",###}<br>
                                                </if>
                                                <if condition="$vo['real_send_time'] gt 0">
                                                    {pigcms{:L_("实际派送")}:{pigcms{$vo['real_send_time']|date="Y-m-d",###}<br>
                                                </if>
                                                <if condition="$vo['service_time'] gt 0">
                                                    {pigcms{:L_("实际送达")}:{pigcms{$vo['service_time']|date="Y-m-d",###}<br>
                                                </if>
                                                <if condition="$vo['date_num'] gt 0">
                                                    {pigcms{:L_("当前期数")}:{pigcms{$vo['date_num']}<br>
                                                </if>
                                                <if condition="$vo['delivery_periods'] gt 1">
                                                    {pigcms{:L_("已派送期数")}:
                                                    {pigcms{$vo['date_num']==$vo['delivery_periods'] && $vo['deliver_status'] >3 ? $vo['date_num'] : ($vo['date_num']-1)}/{pigcms{$vo['delivery_periods']}<br>
                                                </if>
                                                <else/>
                                                {pigcms{:L_("类型:普通")}<br>
                                                <!-- {pigcms{:L_("派送时间:")}{pigcms{$vo['send_time']|date="Y-m-d",###}<br> -->
                                                截单时间:{pigcms{$vo['arrive_cutting_time']|date="Y-m-d H:i",###}<br>
                                                到达时间:{pigcms{$vo['arrive_time']|date="Y-m-d H:i",###}<br>
                                            </if>
                                        </td>
                                        <td>{pigcms{:$vo['note']}</td>
                                        <td>
                                            <if condition="$vo.deliver_status eq 0 AND $vo['status'] egt 1 AND $vo['status'] lt 10 AND $vo['is_delay'] neq 1">
                                                <a href="javascript:void(0);" class="send_goods button" data-id="{pigcms{$vo['order_id']}={pigcms{$vo['goods_id']}" parameter=""
                                                   url="{pigcms{:U('send_selected')}">
                                                    {pigcms{:L_("去发货")}
                                                </a>
                                                <elseif condition="$vo.deliver_status eq 1 AND $vo['is_delay'] neq 1"/>
                                                <font color="green">{pigcms{:L_("已发货")}</font>
                                                <a href="javascript:void(0);" class="send_goods button" data-id="{pigcms{$vo['order_id']}={pigcms{$vo['goods_id']}" parameter=""
                                                   url="{pigcms{:U('get_selected')}">
                                                    {pigcms{:L_("已到货")}
                                                </a>
                                                <elseif condition="$vo.deliver_status eq 2 AND $vo['is_delay'] neq 1"/>
                                                <font color="green">{pigcms{:L_("团长收货 待自提")}</font>
                                                <elseif condition="$vo.deliver_status eq 3 || $vo.deliver_status eq 4"/>
                                                <font color="green">{pigcms{:L_("已取货")}</font>
                                                <elseif condition="$vo.deliver_status eq 6 || $vo['is_delay'] eq 1" />
                                                <font color="gray">{pigcms{:L_("已延期")}-{pigcms{$vo['delay_time']|date="Y-m-d",###}</font>
                                            </if>
                                        </td>
                                    </tr>
                                </volist>
                                <tr>
                                    <td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td>
                                </tr>
                            <php>}else{</php>
                                <tr>
                                    <td class="textcenter red" colspan="13">{pigcms{:L_("列表为空！")}</td>
                                </tr>
                            <php>}</php>
							</tbody>
						</table>
					</div>
				</form>
			</div>
		</div>
		<style>
			.nativea{
				color: #004499;
				margin: 0 5px;
				padding: 4px 7px;
				background: #EFEFEF;
			}
			.nativea.on{
				background: #498CD0;
				color: #FFF;
				text-decoration: none;
			}
			
		</style>
	</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
	var url = "{pigcms{$config.site_url}";
	var export_url = "{pigcms{:U('send_export')}";
    var choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}";
    var check_all = 0;//是否选择全部（筛选的全部）
	
	$('[name="checkAll"]').bind("click", function(){
		if($(this)[0].checked){
			$('[name="checkThis"]').each(function(){
				this.checked = true
			})
            check_all = 1;
            alert('请注意：您已选择了筛选范围内的所有订单，请勿翻页操作，否则会取消您的选择。')
		}
		else{
			$('[name="checkThis"]').each(function(){
				this.checked = false
			})
            check_all = 0;
		}
	})

   $('.send_goods').click(function(){
       	var now_dom = $(this);
	   	var now_dom = $(this);
	   var other = [];
	   if(now_dom.attr('parameter')=='send_all=1' || now_dom.attr('parameter')=='get_all=1'){
		   $('[name="checkThis"]').each(function(i,val){
		   	if(this.checked) other.push($(this).val());			   
		   })
			   
	   }
	   else{
	   	other.push(now_dom.data('id'));
	   }
	   if(other.length == 0 && check_all==0){
	   	alert("您还没有选中任何数据！");
	   	return false;
	   }
		other = '&send_list='+other.join(",");
       other += '&checkAll='+check_all;
       
       get_str = $('form').serialize();
       get_str = get_str.replace(/c=\w+&/,'',get_str)
       get_str = get_str.replace(/a=\w+&/,'',get_str)
       other += '&'+get_str;
       
		art.dialog({
           	icon: 'question',
			id: 'handle',
			title:'{pigcms{:L_("请确认")}',
			padding: 0,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4', 
			content: '{pigcms{:L_("你确定这样操作吗？操作后可能不能恢复！")}' + (now_dom.attr('tip') ? '<br/><br/><div style="color:red;">'+now_dom.attr('tip')+'</div>' : ''),
			ok:function (){

               $.post(now_dom.attr('url'),now_dom.attr('parameter')+other,function(result){
                   if(result.status == 1){
                       layer.alert(result.info);
                       window.location.reload();
                   }else{
                       layer.alert(result.info);
                   }
               });
           },
		});
       return false;
   });
   

	
	function PrePrint(){
		art.dialog.open('{pigcms{:U('pre_print')}&province_id='+$('#choose_province').val()+'&city_id='+$('#choose_city').val()+'&area_id='+$('#choose_area').val()+'&goods_id='+$('#goods_id').val()+'&searchtimetype='+$('#searchtimetype').find(":selected").val()+'&begin_time='+$('#begin_time').val()+'&end_time='+$('#end_time').val()+'&searchtype='+$('#searchtype').val()+'&keyword='+$('#keyword').val()+"&page={pigcms{$_GET['page']}",{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("打印预览")}',
			padding: 0,
			lock: true,
			resize: false,
			width:'80%',
			height:'80%',
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});

		// window.top.artiframe('{pigcms{:U('pre_print')}&goods_id='+$('#goods_id').val()+'&begin_time='+$('#begin_time').val()+'&end_time='+$('#end_time').val()+'&searchtype='+$('#searchtype').val()+'&keyword='+$('#keyword').val()+"&page={pigcms{$_GET['page']}",'{pigcms{:L_("打印预览")}','80%','80%',true,false,false,'','add',true);
	}



</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>