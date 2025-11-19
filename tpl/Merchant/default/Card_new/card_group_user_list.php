<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("会员卡分组用户列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<a class="btn btn-success" onclick="edit_money()" >{pigcms{:L_("余额批量管理")}</a>
					<a class="btn btn-success "  onclick="clear_money()">{pigcms{:L_("全部人员非自充余额清零")}</a>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
                                	<th width="80" style="text-align:center"><input type="checkbox" class="checkbox_all" style="wdith:20px; height:20px;"></th>
									<th width="120">{pigcms{:L_("会员卡号")}</th>
									<th width="120">{pigcms{:L_("微信卡号")}</th>
									<th width="120">{pigcms{:L_("微信昵称")}</th>
									<th width="120">{pigcms{:L_("姓名")}</th>
									<th width="80">{pigcms{:L_("性别")}</th>
									<th width="120">{pigcms{:L_("用户生日")}</th>
									<th width="120">{pigcms{:L_("用户手机")}</th>
									<th width="240" class="am-text-middle">{pigcms{:L_("会员卡余额")}</th>
									<th width="120" class="am-text-middle">{pigcms{:L_("会员卡")}{pigcms{$config['score_name']}</th>
									<th width="120">{pigcms{:L_("实体卡号")}</th>
									<th width="120">{pigcms{:L_(用户分组)}</th>
									<th width="160" class="am-text-middle">{pigcms{:L_("领卡时间")}</th>
									<th width="120">{pigcms{:L_("会员卡状态")}</th>
									<th id="shopList_c1" width="180">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$user_list">
									<volist name="user_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td align="center"><input type="checkbox" name="checkbox_id[]" style="wdith:20px; height:20px;" value="{pigcms{$vo.id}" onclick="return Dcheckbox($(this));"></td>
											<td>{pigcms{$vo.id}</td>
											<td><if condition="$vo.wx_card_code neq ''">{pigcms{$vo.wx_card_code}<else /></if></td>
											<td><if condition="$vo['uid'] neq ''">{pigcms{$vo.nickname}<else /><a href="{pigcms{:U('Card_new/see_qrcode',array('id'=>$vo['id']))}" data-id="{pigcms{$vo.id}" class="see_qrcode">{pigcms{:L_("查看二维码(用户微信扫描后绑定")})</a></if></td>
											<td>{pigcms{$vo.name}</td>
											<td><if condition="$vo['sex'] eq 1">男<elseif condition="$vo['sex'] eq 2"/>女<else/>未知</if></td>
											<td><if condition="$vo['birthday'] neq '0000-00-00'">{pigcms{$vo.birthday}</if></td>
											<td>{pigcms{$vo.phone}</td>
											<td class="am-text-middle">
												<div>{pigcms{$vo['card_money']+$vo['card_money_give']}</div>
												<span style="color: red;">{pigcms{:L_("（非自充余额：")}{pigcms{$vo['card_money_give']}{pigcms{:L_("）")}</span>
												</td>
											<td class="am-text-middle">{pigcms{$vo.card_score}</td>
											<td>{pigcms{$vo.physical_id}</td>
											<td>{pigcms{$card_group[$vo['gid']]}</td>
											<td class="am-text-middle"><if condition="$vo.add_time neq 0">{pigcms{$vo['add_time']|date="Y-m-d H:i:s",###}</if></td>
											<td><if condition="$vo.status eq '1'"><font color="green">{pigcms{:L_("正常")}</font><else /><font color="red">{pigcms{:L_("禁止")}</font></if></td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a class="layer-iframe" href="{pigcms{:U('Card_new/card_detail',array('id'=>$vo['id'],'uid'=>$vo['uid']))}">{pigcms{:L_("查看详情")}</a>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
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
$(".checkbox_all").on('click',function(){
	if($(this).is(':checked')){
		$("input[name='checkbox_id[]']").prop("checked",true);  
	}else{
		$("input[name='checkbox_id[]']").prop("checked",false);  	
	}	
		
});

function Dcheckbox(e){
	var n=0;
	var len = $("input[name='checkbox_id[]']").length;
	for(var i=0;i<len;i++){
		if($("input[name='checkbox_id[]']")[i].checked) n++;		
	}
	if(n==len){
		$(".checkbox_all").prop("checked",true); 
	}else{
		$(".checkbox_all").prop("checked",false); 
	}
		
}

function edit_money(e){
	var ids=[];
	$.each($("input[name='checkbox_id[]']"),function(){
		if($(this).is(':checked')){
			ids.push($(this).val())
		}
	})
	if(ids.length<=0){
		alert('请选择要操作的会员');
		return false;
	}

	var href = "{pigcms{:U('Card_new/money_edit')}"+"&ids="+ids.join(',')+"&gid={pigcms{$_GET['gid']}";
	art.dialog.open(href,{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle',iframe);
		},
		id: 'handle',
		title:'{pigcms{:L_("余额批量管理")}',
		padding: 0,
		width: '55%',
		height: '55%',
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
		
}
function clear_money(e){
	var href = "{pigcms{:U('Card_new/money_clear',array('gid'=>$_GET['gid']))}";
	art.dialog.open(href,{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle',iframe);
		},
		id: 'handle',
		title:'{pigcms{:L_("全部人员非自充余额清零")}',
		padding: 0,
		width: '30%',
		height: '30%',
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
		
}


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
