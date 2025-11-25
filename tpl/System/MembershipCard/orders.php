<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('MembershipCard/config')}">平台会员卡</a>|
            <a href="{pigcms{:U('MembershipCard/report')}">效果数据</a>|
            <a href="{pigcms{:U('MembershipCard/orders')}" class="on">订单列表</a>|
        </ul>
    </div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('MembershipCard/orders')}" method="get">
						<input type="hidden" name="c" value="MembershipCard"/>
						<input type="hidden" name="a" value="orders"/>
						
						搜索: 
                        <select name="searchtype" id="searchtype">
							<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>用户手机号</option>
							<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>用户昵称</option>
						</select>
                        <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<font color="#000">下单时间:</font>
						<input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						订单状态:
						<select id="paid" name="paid">
							<volist name="paidLists" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $_GET['paid']">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>&nbsp;&nbsp;&nbsp;&nbsp;
						支付方式: 
						<select id="pay_type" name="pay_type">
							<option value="" <if condition="'' eq $_GET['pay_type']">selected="selected"</if>>全部支付方式</option>
							<volist name="payMethods" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $_GET['pay_type']">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
						<input type="submit" value="查询" class="button"/>　
                        <input type="button" value="重置" class="button" onclick="reset_search()"/>　

                        <input type="button" value="导出订单" class="button" onclick="export_list()" style="float: right"/>　
					</form>
				</td>
				
				<td style="width:100px;">
				</td>
			</tr>
			<tr>
                <td>
                    <b>总订单金额：{pigcms{$total_price|getFormatNumber=###}</b> &nbsp;&nbsp;&nbsp;
                    <b>总订单数：{pigcms{$count|getFormatNumber=###}</b>
                </td>
	        </tr>         
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>订单编号</th>
							<th>下单人</th>
							<th>手机号</th>
							<th>会员卡名称</th>
							<th>价格</th>
							<th>下单时间</th>
							<th>支付时间</th>
							<th>过期时间</th>
							<th>下单来源</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($lists)">
							<volist name="lists" id="vo">
							<tr>
								<td>{pigcms{$vo.order_id}</td>
								<td>{pigcms{$vo.nickname}</td>
								<td>{pigcms{$vo.phone}</td>
								<td>{pigcms{$vo.order_name}</td>
								<td>{pigcms{$vo.price}</td>
								<td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>
								<td><gt name='vo.pay_time' value='0'>{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}<else/> -- </gt></td>
								<td><gt name='vo.expire_time' value='0'>{pigcms{$vo.expire_time|date='Y-m-d H:i:s',###}<else/> -- </gt></td>
								<td>{pigcms{$vo.source}</td>
							</tr>
							</volist>

							<tr><td class="textcenter pagebar" colspan="8">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
<script>
var url = "{pigcms{$config.site_url}"
var export_url = "{pigcms{:U('Shop/export')}"

function reset_search() {
    $('#searchtype').val('phone')
    $('input[name=keyword]').val('');
    $('input[name=begin_time]').val('');
    $('input[name=end_time]').val('');
    $('#paid').val(-1);
    $('#pay_type').val("");
}

function export_list() {
    var searchtype = $('#searchtype').val();
    var keyword = $('input[name=keyword]').val();
    var begin_time = $('input[name=begin_time]').val();
    var end_time = $('input[name=end_time]').val();
    var paid = $('#paid').val();
    var pay_type = $('#pay_type').val();

    var url = "{pigcms{:U('MembershipCard/exportOrders')}"
    url += "&searchtype=" + searchtype;
    url += "&keyword=" + keyword;
    url += "&begin_time=" + begin_time;
    url += "&end_time=" + end_time;
    url += "&paid=" + paid;
    url += "&pay_type=" + pay_type
    window.open(url);
}
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>