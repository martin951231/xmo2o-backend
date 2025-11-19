<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('index')}">饭票充值模板列表</a>
			<a href="{pigcms{:U('add')}" class="on">添加饭票充值模板</a>

		</ul>
	</div>
	<form method="post" action="" refresh="true" enctype="multipart/form-data" >
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
			<tr class="class_5">
				<th></th>
				<td>
					<table class="table_form" width="100%" style="display: table;" id="tab_0">
						<tbody>
							<tr class="coupon">
								<th width="160">活动名称　</th>
								<td><input type="text" class="input-text" name="name" id="name" value="{pigcms{$info.name}" placeholder="请输入活动标题，建议20字以内"  size="50"/></td>
							</tr>
							<tr class="coupon">
								<th width="160">充值金额　</th>
								<td>
									<div class="recharge_content">
										<if condition="empty($info['id'])">
										<input type="text" class="input-text" name="recharge_money[]"  value=""  size="10"/>
										<input type="text" class="input-text" name="recharge_money[]"  value=""  size="10"/>
										<input type="text" class="input-text" name="recharge_money[]"  value=""  size="10"/>
										</if>
										<volist name="info.recharge_money_arr" id="m">
										<input type="text" class="input-text" name="recharge_money[]"  value="{pigcms{$m}"  size="10"/>
										</volist>
										<span style="color:red;">最少输入3个，最多输入6个自定义金额</span>
									</div>
									<input type="button" value="添加金额" class="button" onclick="add_recharge()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;margin-top:5px"/>
								</td>
							</tr>

							<tr class="coupon">
								<th width="160">绑定商家</th>
								<td>
									<input type="button" value="添加商家" class="button" onclick="select_coupon()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">
									<span style="color:red;">如果不选择商家，默认为所有商家</span>
									<table style="width:800px;" class="table_form" id='goods_spec'>
										<thead>
											<tr >
												<th style="text-align: left;">ID</th>
												<th style="text-align: left;">商家名称</th>
												<th style="text-align: left;">操作</th>
											</tr>
										</thead>
										<tbody>
											<volist name="info.merchant_arr" id="vo">
											<tr>
												<input type='hidden' name="coupon_ids[]" value="{pigcms{$vo.mer_id}" />
												<td>{pigcms{$vo.mer_id}</td>
												<td>{pigcms{$vo.name}</td>
												<td>
													<button type="button" onClick="delThis(this)">移除</button>
												</td>
											</tr>
											</volist>
										</tbody>
									</table>
								</td>
							</tr>
							<tr class="coupon">
								<th width="160">活动有效期　</th>
								<td><input type="text" class="input-text" name="effective_day" id="effective_day" value="{pigcms{$info.effective_day}" placeholder=""  size="10"/> 天
								<span style="color:red;">0或不填表示永久有效</span></td>
							</tr>
							<tr class="coupon">
								<th width="160">启用</th>
								<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$info['status'] eq 1">selected</if>" ><span>是</span>
								<input type="radio" name="status" value="1" <if condition="$info['status'] eq 1">checked="checked"</if>></label></span>
						<span class="cb-disable "><label class="cb-disable <if condition="$info['status'] neq 1">selected</if>"><span>否</span>
								<input type="radio" name="status" value="0" <if condition="$info['status'] neq 1">checked="checked"</if>></label></span>

								</td>
							</tr>
						</tbody>
					</table>

		<div class="btn">
			<input type="hidden"  name="id" value="{pigcms{$info.id}" />
			<input type="submit"  name="dosubmit" value="提交" class="button" />
			<input type="reset"  value="取消" class="button" />
		</div>
	</form>
</div>
<style>
	.table_form{border:1px solid #ddd;}
	.tab_ul{margin-top:20px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
	.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
	.tab_ul>li>a {
	position: relative;
	display: block;
	padding: 10px 15px;
	margin-right: 2px;
	line-height: 1.42857143;
	border: 1px solid transparent;
	border-radius: 4px 4px 0 0;
	padding: 7px 12px 8px;
	min-width: 100px;
	text-align: center;
	}
	.tab_ul>li>a, .tab_ul>li>a:focus {
	border-radius: 0!important;
	border-color: #c5d0dc;
	background-color: #F9F9F9;
	color: #999;
	margin-right: -1px;
	line-height: 18px;
	position: relative;
	}
	.tab_ul>li>a:focus, .tab_ul>li>a:hover {
	text-decoration: none;
	background-color: #eee;
	}
	.tab_ul>li>a:hover {
	border-color: #eee #eee #ddd;
	}
	.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
	color: #555;
	background-color: #fff;
	border: 1px solid #ddd;
	border-bottom-color: transparent;
	cursor: default;
	}
	.tab_ul>li>a:hover {
	background-color: #FFF;
	color: #4c8fbd;
	border-color: #c5d0dc;
	}
	.tab_ul>li:first-child>a {
	margin-left: 0;
	}
	.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
	color: #576373;
	border-color: #c5d0dc #c5d0dc transparent;
	border-top: 2px solid #4c8fbd;
	background-color: #FFF;
	z-index: 1;
	line-height: 18px;
	margin-top: -1px;
	box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
	}
	.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
	color: #555;
	background-color: #fff;
	border: 1px solid #ddd;
	border-bottom-color: transparent;
	cursor: default;
	}
	.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
	color: #576373;
	border-color: #c5d0dc #c5d0dc transparent;
	border-top: 2px solid #4c8fbd;
	background-color: #FFF;
	z-index: 1;
	line-height: 18px;
	margin-top: -1px;
	box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
	}
	.tab_ul:before,.tab_ul:after{
	content: " ";
	display: table;
	}
	.tab_ul:after{
	clear: both;
	}	
	.tags,.goods_tags{
		margin-top: 10px;
	}
	.tags .tag, .goods_tags .goods_tag{
	    display: inline-block;
	    padding: 2px 2px;
	    margin: 0px 5px 5px 0;
	    border: 1px solid #ccc;
	}
	.tags .tag .close, .goods_tags .goods_tag .goods_close {
	    display: inline-block;
	    padding: 0 4px;
	    color: #277fc1;
	    font-weight: bold;
	    cursor: pointer;
	}
	.a_choose{
		float: left; 
		border: 1px solid #ccc; 
		padding: 2px 3px; 
		cursor: pointer;
		display: block;
	}
</style>

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
function add_recharge(){
	var add_html = '<input type="text" class="input-text" name="recharge_money[]"  value=""  size="10"/>';
	$('.recharge_content').append(add_html);
	return false;
}
$(document).ready(function(){
	$(".type").click(function(){
		$(".class_0,.class_1,.class_2,.class_4,.class_5").hide();
		$(".class_" + $(this).val()).show();
	});
});
$(document).ready(function(){
	$("input[name=reply_rule]").click(function(){
		if ($(this).val()==1) {
			$('.tab_ul').hide();
			$('#tab_0').show();
			$('#tab_1').hide();
		}else{
			$('.tab_ul li a').closest('li').eq(0).addClass('active').siblings('li').removeClass('active');
			$('.tab_ul').show();
			$('#tab_0').show();
			$('#tab_1').hide();
		}
	});
});
$('.tab_ul li a').click(function(){
	$(this).closest('li').addClass('active').siblings('li').removeClass('active');
	$($(this).attr('href')).show().siblings('.table_form').hide();
	return false;
});

var _selected_coupon_ids = '{pigcms{$info["merchant"]}';
var _selected_coupon_ids_again = '{pigcms{$info["merchant"]}';
function select_coupon(type=''){
	var url = '';
	if (type) {
		url = '{pigcms{:U("Home/select_merchant")}'+'&selected='+_selected_coupon_ids_again;
	}else{
		url = '{pigcms{:U("Home/select_merchant")}'+'&selected='+_selected_coupon_ids;
	}
	layer.open({
		type: 2,
		area: ['30%', '700px'],
		fixed: false,
		offset:'100px',
		maxmin: true,
		content: url,
		btn: ['确定', '取消'],
		yes: function(index, layero){
			var frameId = $(layero).find("iframe").attr('id');
			var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
			if (type) {
				_selected_coupon_ids_again = selected;
			}else{
				_selected_coupon_ids = selected;
			}
			$("#goods_spec"+type+" tbody").empty();
			$.post("{pigcms{:U('ajaxGetMerchant')}",{selected:selected},function(data){
				if(data.length > 0){
					var html = '';
					console.log(data)
					for(var i = 0; i< data.length; i++){
					console.log(i)
						html += '<tr>'+
									'<input type="hidden" name="coupon_ids'+type+'[]" value="'+data[i].mer_id+'" />'+
									'<td>'+data[i].mer_id+'</td>'+
									'<td>'+data[i].name+'</td>';

						html += '<td>'+
											'<button type="button" onClick="delThis(this)">移除</button>'+
									'</td>'+
								'</tr>'
					}
					$("#goods_spec"+type+" tbody").append(html);
				}
			},'json');
		    layer.close(index);
		},
		btn2: function(index){
			layer.close(index);
		}
	});
}

function delThis(t,type=''){
	if(confirm('{pigcms{:L_("你确定要移除此商户吗？")}')){
		var coupon_id = $(t).parents("tr").find('[name="coupon_ids'+type+'[]"]').val();
		var _selected_coupon_ids_arr = (_selected_coupon_ids+type).split(',');
		var _selected_coupon_ids_arr_new = [];
		for(var i = 0;i< _selected_coupon_ids_arr.length;i++){
			if(_selected_coupon_ids_arr[i] != coupon_id){
				_selected_coupon_ids_arr_new.push(_selected_coupon_ids_arr[i]);
			}
		}
		if (type) {
			_selected_coupon_ids_again = _selected_coupon_ids_arr_new.join(",");
		}else{
			_selected_coupon_ids = _selected_coupon_ids_arr_new.join(",");
		}
		$(t).parents("tr").eq(0).remove();
	}
}

KindEditor.ready(function(K){
	var site_url = "{pigcms{$config.site_url}";
	var editor = K.editor({
		allowFileManager : true
	});
	$('.upload_coupon_img').click(function(){
		var upload_file_btn = $(this);
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				clickFn : function(url, title, width, height, border, align) {
					upload_file_btn.siblings('.input-image').val(site_url+url);
					editor.hideDialog();
				}
			});
		});
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},200);
	});
	$('.upload_coupon_img_again').click(function(){
		var upload_file_btn = $(this);
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				clickFn : function(url, title, width, height, border, align) {
					upload_file_btn.siblings('.input-image').val(site_url+url);
					editor.hideDialog();
				}
			});
		});
		setTimeout(function(){
			$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
		},200);
	});
});
function addLink(domid,iskeyword){
	art.dialog.data('domid', domid);
	art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
}
</script>
<include file="Public:footer"/>