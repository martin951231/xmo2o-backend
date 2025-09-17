<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("会员卡消费记录")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <form action="{pigcms{:U('Card_new/recharge_list')}" method="get" style="float:left;margin-left:10px;margin-bottom: 10px">
                        <input type="hidden" name="c" value="Card_new"/>
                        <input type="hidden" name="a" value="recharge_list"/>
                        {pigcms{:L_("筛选")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                        <select name="searchtype">
                            <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("手机号码")}</option>
                            <option value="card_id" <if condition="$_GET['searchtype'] eq 'card_id'">selected="selected"</if>>{pigcms{:L_("会员卡号")}</option>
                            <option value="physical_id" <if condition="$_GET['searchtype'] eq 'physical_id'">selected="selected"</if>>{pigcms{:L_("实体卡号")}</option>
                            <option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>{pigcms{:L_("用户姓名")}</option>
                        </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <font color="#000">{pigcms{:L_("时间：")}</font>
                        <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                        <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                        <button class="btn btn-success" >{pigcms{:L_("查询")}</button>&nbsp;&nbsp;&nbsp;&nbsp;
                        <button class="btn btn-success" onclick="exportRechargeList()">{pigcms{:L_("导出")}</button>&nbsp;&nbsp;&nbsp;&nbsp;
                    </form>

					<div id="shopList" class="grid-view">

                        <div class="alert alert-info" style="margin-top: 4rem">
                            {pigcms{:L_("金额增加（")}{pigcms{$config['Currency_txt']}）：{pigcms{$report.total_money_add|getFormatNumber}
                            &nbsp;&nbsp;{pigcms{:L_("金额减少（")}{pigcms{$config['Currency_txt']}）：{pigcms{$report.total_money_use|getFormatNumber}
                            &nbsp;&nbsp;{pigcms{$config['score_name']}{pigcms{:L_("增加（分）")}：{pigcms{$report.total_score_add|getFormatNumber}
                            &nbsp;&nbsp;{pigcms{$config['score_name']}{pigcms{:L_("减少（分）")}：{pigcms{$report.total_score_use|getFormatNumber}
                            &nbsp;&nbsp;{pigcms{:L_("优惠券增加（")}{pigcms{$config['Currency_txt']}）：{pigcms{$report.total_coupon_add|getFormatNumber}
                            &nbsp;&nbsp;{pigcms{:L_("优惠券减少（")}{pigcms{$config['Currency_txt']}）：{pigcms{$report.total_coupon_use|getFormatNumber}
                            &nbsp;&nbsp;{pigcms{:L_("在线充值总额（")}{pigcms{$config['Currency_txt']}）：{pigcms{$report.total_online_money|getFormatNumber}
                        </div>

						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("会员卡")}ID</th>
									<th width="50">{pigcms{:L_("会员名称")}</th>
									<th width="50">{pigcms{:L_("会员手机")}</th>
									<th width="50">{pigcms{:L_("时间")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("详情")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("金额增加（")}{pigcms{$config['Currency_txt']}）</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("金额减少（")}{pigcms{$config['Currency_txt']}）</th>
									<th id="shopList_c1" width="100">{pigcms{$config['score_name']}{pigcms{:L_("增加（分）")}</th>
									<th id="shopList_c1" width="100">{pigcms{$config['score_name']}{pigcms{:L_("减少（分）")}</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("优惠券增加（")}{pigcms{$config['Currency_txt']}）</th>
									<th id="shopList_c1" width="100">{pigcms{:L_("优惠券减少（")}{pigcms{$config['Currency_txt']}）</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$record">
									<volist name="record" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											
											<td width="50">{pigcms{$vo.card_id}</td>
											<td width="50">{pigcms{$vo.nickname}</td>
											<td width="50">{pigcms{$vo.phone}</td>
											<td width="50">{pigcms{$vo.time|date='Y-m-d H:i:s',###}</td>
											<td>{pigcms{$vo['desc']}</td>
											<td><font color="#2bb8aa"><if condition="$vo.money_add neq 0">+</if>{pigcms{$vo.money_add}</font></td>
											<td><font color="#f76120"><if condition="$vo.money_use neq 0">-</if>{pigcms{$vo.money_use}</font></td>
											<td><font color="#2bb8aa"><if condition="$vo.score_add neq 0">+</if>{pigcms{$vo.score_add}</font></td>
											<td><font color="#f76120"><if condition="$vo.score_use neq 0">-</if>{pigcms{$vo.score_use}</font></td>
											<td><font color="#2bb8aa"><if condition="$vo.coupon_add neq 0">+</if>{pigcms{$vo.coupon_add}</font></td>
											<td><font color="#f76120"><if condition="$vo.coupon_use neq 0">-</if>{pigcms{$vo.coupon_use}</font></td>
										</tr>
										
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("无内容")}</td></tr>
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

function exportRechargeList() {
    var url = '{pigcms{:U("Card_new/exportRechargeList")}';
    url += '&keyword=' + $('input[name=keyword]').val();
    url += '&searchtype=' + $('select[name=searchtype] ').val();
    url += '&begin_time=' + $('input[name=begin_time] ').val();
    url += '&end_time=' + $('input[name=end_time] ').val();
    window.open(url);
}
</script>

<script>
	$(function(){
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:$(this).data('title'),
				padding: 0,
				width: 720,
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
		
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 430,
				height: 433,
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
		
		$('#group_id').change(function(){
			$('#frmselect').submit();
		});
	});
</script>
<include file="Public:footer"/>
