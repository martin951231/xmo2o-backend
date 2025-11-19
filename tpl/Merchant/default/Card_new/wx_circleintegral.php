<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("微信商圈积分记录")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row" style="margin-top: 10px">
				<div class="col-xs-12">
				<form action="{pigcms{:U('Card_new/wx_circleintegral')}" method="get" style="float:left;margin-left:10px">
				
						<input type="hidden" name="c" value="Card_new"/>
						<input type="hidden" name="a" value="wx_circleintegral"/>
					店铺：
                    <input type="text" name="shop_name" style="height: 30px;width: 100px;" value="{pigcms{$_GET['shop_name']}"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<!--					<select name="searchtype" >-->
<!--							<option value="card_id" <if condition="$_GET['searchtype'] eq 'card_id'">selected="selected"</if>>{pigcms{:L_("会员卡号")}</option>-->
<!--							<option value="shop_name" <if condition="$_GET['searchtype'] eq 'shop_name'">selected="selected"</if>>{pigcms{:L_("店铺名称")}</option>-->
<!--						</select>-->
                    会员卡号/手机号：
						<input type="text" name="keywords" style="height: 30px;width: 150px;" value="{pigcms{$_GET['keywords']}"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

						{pigcms{:L_("添加时间")}:
					<input type="text" class="input fl" style="height: 30px" name="begin_time" id="d4311"  value="{pigcms{$_GET['begin_time']}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
					<input type="text" class="input fl" style="height: 30px" name="end_time" id="d4312" value="{pigcms{$_GET['end_time']}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;

					<button class="btn btn-success on_search" style="padding: 0 20px">{pigcms{:L_("搜索")}</button>
					 
				</form>

					<div id="shopList" class="grid-view" style="margin-top: 30px">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="80">ID</th>
									<th width="120">{pigcms{:L_("店铺名称")}</th>
									<th width="100">{pigcms{:L_("会员ID")}</th>
									<th width="100">{pigcms{:L_("会员卡号")}</th>
									<th width="100">{pigcms{:L_("会员手机号")}</th>
									<th width="80">{pigcms{:L_("付款金额")}</th>
									<th width="80">{pigcms{:L_("退款金额")}</th>
									<th width="80">{pigcms{:L_("兑换积分")}</th>
									<th width="100">{pigcms{:L_("订单类型")}</th>
									<th width="100">{pigcms{:L_("同步状态")}</th>
									<th width="100">{pigcms{:L_("同步方式")}</th>
									<th width="150">{pigcms{:L_("同步时间")}</th>
									<th width="150">{pigcms{:L_("添加时间")}</th>
									<th width="80">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$wxSyncList">
									<volist name="wxSyncList" id="vo">
                                    <tr>
                                        <td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.shop_name}</td>
                                        <td>{pigcms{$vo.uid}</td>
                                        <td>{pigcms{$vo.card_id}</td>
                                        <td>{pigcms{$vo.phone}</td>
                                        <td>{pigcms{$vo.amount}</td>
                                        <td>{pigcms{$vo.refund_amount}</td>
                                        <td>{pigcms{$vo.score}</td>
                                        <td>{pigcms{$vo.event_type_txt}</td>
                                        <td>{pigcms{$vo.status_txt}</td>
                                        <td>{pigcms{$vo.sync_type_txt}</td>
                                        <td>{pigcms{$vo.sync_time_txt}</td>
                                        <td>{pigcms{$vo.add_time_txt}</td>
                                        <php>if($vo['status'] == 0){</php>
                                            <td><a href="javascript:void(0)" class="syncBtn" data-id="{pigcms{$vo.id}"> {pigcms{:L_("同步")}</a></td>
                                        <php>}</php>
                                    </tr>

									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="15" >{pigcms{:L_("无内容")}</td></tr>
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
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
 
function drop_confirm(msg, url)
{
	if (confirm(msg)) {
		window.location.href = url;
	}
}
</script>

<script>
	$(function(){

        $('.on_search').click(function(){
            $('form').submit();
        });
		
	
	});
	
	
</script>
<include file="Public:footer"/>
