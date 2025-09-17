<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Home/first_by_time')}" >分时段关注自动回复</a>
			<a href="{pigcms{:U('Home/first')}" class="on">其他时段关注自动回复</a>
		</ul>
	</div>
	<form method="post" action="" refresh="true" enctype="multipart/form-data" >
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
			<tr>
				<th width="160">回复类型　</th>
				<td>
				<label><input type="radio" name="type" class="type" value="0" <if condition="$first['type'] eq 0">checked</if>>自定义文本</label>
				<!--label><input type="radio" name="type" class="type" value="1" <if condition="$first['type'] eq 1">checked</if>>自定义图文</label-->
				<label><input type="radio" name="type" class="type" value="4" <if condition="$first['type'] eq 4">checked</if>>单/多图文消息</label>
				<label><input type="radio" name="type" class="type" value="2" <if condition="$first['type'] eq 2">checked</if>>网站功能</label>
				<label><input type="radio" name="type" class="type" value="3" <if condition="$first['type'] eq 3">checked</if>>本站推荐的{pigcms{$config.group_alias_name}</label>
				<!--label><input type="radio" name="type" class="type" value="4" <if condition="$first['type'] eq 4">checked</if>>本站推荐的{pigcms{$config.waimai_alias_name}店铺</label-->
				<label><input type="radio" name="type" class="type" value="5" <if condition="$first['type'] eq 5">checked</if>>优惠券</label>
				</td>
			</tr>
			<tr class="class_0" <if condition="$first['type'] neq 0">style="display:none"</if>>
				<th width="160">回复内容　</th>
				<td><textarea rows="20" cols="45" name="content" id="content">{pigcms{$first.content}</textarea>
				<img src="./static/images/exp1.png"></td>
			</tr>
			<tr class="class_1" <if condition="$first['type'] neq 1">style="display:none"</if>>
				<th width="160">回复标题　</th>
				<td><input type="text" class="input-text" name="title" id="title" value="{pigcms{$first.title}"/></td>
			</tr>
			<tr class="class_1" <if condition="$first['type'] neq 1">style="display:none"</if>>
				<th width="160">内容介绍　</th>
				<td><textarea rows="4" cols="25" name="info" id="info">{pigcms{$first.info}</textarea></td>
			</tr>
			<tr class="class_1" <if condition="$first['type'] neq 1">style="display:none"</if>>
				<th width="160">外链URL　</th>
				<td>
					<input type="text" class="input-text" name="url" id="url" value="{pigcms{$first.url}" style="width:200px;" placeholder="外链接url如：http://www.baidu.com" validate="maxlength:200,url:true"/>
					<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="外链接url如：http://www.baidu.com">
					<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
			<tr class="class_1" <if condition="$first['type'] neq 1">style="display:none"</if>>
				<th width="160">回复图片　</th>
				<td><input type="file" class="input-text" name="pic" id="pic" value="{pigcms{$first.pic}"/></td>
			</tr>
			<if condition="$first['pic']">
				<tr class="class_1" <if condition="$first['type'] neq 1">style="display:none"</if>>
					<th width="160"></th>
					<td><img src="{pigcms{$first.pic}" width="280" height="180"></td>
				</tr>
			</if>
			
			<tr class="class_2" <if condition="$first['type'] neq 2">style="display:none"</if>>
				<th width="160">回复网站功能　</th>
				<td>
				<label><input type="radio" name="fromid" value="1" <if condition="$first['fromid'] eq 1">checked</if>>网站首页</label>
				<label><input type="radio" name="fromid" value="2" <if condition="$first['fromid'] eq 2">checked</if>>{pigcms{$config.group_alias_name}首页</label>
				<label><input type="radio" name="fromid" value="3" <if condition="$first['fromid'] eq 3">checked</if>>{pigcms{$config.meal_alias_name}首页</label>
				</td>
			</tr>
			
			<tr class="class_4" <if condition="$first['type'] neq 4">style="display:none"</if>>
				<th width="160">选择图文内容</th>
				<td>
				<label>
						<select name="image_text_id">
							<volist name="image_text_list" id="vo">
								<option value="{pigcms{$vo.pigcms_id}" <if condition="$vo['pigcms_id'] eq $first['image_text_id']">selected</if>>【{pigcms{$vo.list.0.title}】 - <if condition="count($vo['list']) gt 1">多图文<else />单图文</if></option>
							</volist>
						</select>
				</label>
				</td>
			</tr>
			
			<tr class="class_5" <if condition="$first['type'] neq 5">style="display:none"</if>>
				<th width="160">回复规则　</th>
				<td>
				<label><input type="radio" name="reply_rule" value="1" <if condition="$first['reply_rule'] eq 1">checked</if>>首次关注与再次关注统一回复</label>
				<label><input type="radio" name="reply_rule" value="2" <if condition="$first['reply_rule'] eq 2">checked</if>>首次关注与再次关注分别回复</label>
				</td>
			</tr>
			<tr class="class_5" <if condition="$first['type'] neq 5">style="display:none"</if>>
				<th></th>
				<td>
					<ul class="tab_ul"<if condition="$first['reply_rule'] eq 1"> style="display: none;"</if>>
						<li class="active"><a data-toggle="tab" href="#tab_0">首次关注回复</a></li>
						<li class=""><a data-toggle="tab" href="#tab_1">再次关注回复</a></li>
					</ul>
					<table class="table_form" width="100%" style="display: table;" id="tab_0">
						<tbody>
							<tr class="coupon">
								<th width="160">回复标题　</th>
								<td><input type="text" class="input-text" name="coupon_title" id="coupon_title" value="{pigcms{$first.coupon_title}" placeholder="请输入回复标题，建议20字以内"  size="30"/></td>
							</tr>
							<tr class="coupon">
								<th width="160">回复内容　</th>
								<td><textarea  name="coupon_content" rows="4" cols="25"  placeholder="请输入回复标题，建议200字以内">{pigcms{$first.coupon_content}</textarea></td>
							</tr>
							<tr class="coupon">
								<th width="160">回复图片  </th>
								<td class="info_coupon_img">
									<span class="upload_coupon_img">
										<input type="button" value="上传图片" class="button" style="margin-left:0px;margin-right:10px;">
									</span>
									<input type="text" class="input-text input-image valid" name="coupon_img" id="coupon_img" value="{pigcms{$first.coupon_img}" size="48">
									<span style="color: red;">建议尺寸 400*400</span>
								</td>
							</tr>
							<tr class="coupon">
								<th width="160">绑定优惠券</th>
								<td>
									<input type="button" value="添加优惠券" class="button" onclick="select_coupon()" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">
									<table style="width:800px;" class="table_form" id='goods_spec'>
										<thead>
											<tr >
												<th style="text-align: left;">ID</th>
												<th style="text-align: left;">满减条件</th>
												<th style="text-align: left;">有效时间</th>
												<th style="text-align: left;">使用平台</th>
												<th style="text-align: left;">使用类别</th>
												<th style="text-align: left;">使用分类</th>
												<th style="text-align: left;">操作</th>
											</tr>
										</thead>
										<tbody>
											<volist name="coupon_list" id="vo">
											<tr>
												<input type='hidden' name="coupon_ids[]" value="{pigcms{$vo.coupon_id}" />
												<td>{pigcms{$vo.coupon_id}</td>
												<td><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></td>
												<td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
												<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
												<td><if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if></td>
												<td><if condition="$vo.cate_id eq '0'">全部分类<else />{pigcms{$vo['cate_id']}</if></td>
												<td>
													<button type="button" onClick="delThis(this)">移除</button>
												</td>
											</tr>							
											</volist>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="table_form" width="100%" style="display: none;" id="tab_1">
						<tbody>
							<tr class="coupon">
								<th width="160">回复标题　</th>
								<td><input type="text" class="input-text" name="coupon_title_again" id="coupon_title_again" value="{pigcms{$first.coupon_title_again}" placeholder="请输入回复标题，建议20字以内" size="30" /></td>
							</tr>
							<tr class="coupon">
								<th width="160">回复内容　</th>
								<td><textarea  name="coupon_content_again" rows="4" cols="25"  placeholder="请输入回复标题，建议200字以内">{pigcms{$first.coupon_content_again}</textarea></td>
							</tr>
							<tr class="coupon">
								<th width="160">回复图片  </th>
								<td class="info_coupon_img_again">
									<span class="upload_coupon_img_again">
										<input type="button" value="上传图片" class="button" style="margin-left:0px;margin-right:10px;">
									</span>
									<input type="text" class="input-text input-image valid" name="coupon_img_again" id="coupon_img_again" value="{pigcms{$first.coupon_img_again}" size="48">
									<span style="color: red;">建议尺寸 400*400</span>
								</td>
							</tr>
							<tr class="coupon">
								<th width="160">绑定优惠券</th>
								<td>
									<input type="button" value="添加优惠券" class="button" onclick="select_coupon('_again')" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">
									<table style="width:800px;" class="table_form" id='goods_spec_again'>
										<thead>
											<tr >
												<th style="text-align: left;">ID</th>
												<th style="text-align: left;">满减条件</th>
												<th style="text-align: left;">有效时间</th>
												<th style="text-align: left;">使用平台</th>
												<th style="text-align: left;">使用类别</th>
												<th style="text-align: left;">使用分类</th>
												<th style="text-align: left;">操作</th>
											</tr>
										</thead>
										<tbody>
											<volist name="coupon_list_again" id="vo">
											<tr>
												<input type='hidden' name="coupon_ids_again[]" value="{pigcms{$vo.coupon_id}" />
												<td>{pigcms{$vo.coupon_id}</td>
												<td><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></td>
												<td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
												<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
												<td><if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if></td>
												<td><if condition="$vo.cate_id eq '0'">全部分类<else />{pigcms{$vo['cate_id']}</if></td>
												<td>
													<button type="button" onClick="delThis(this,'_again')">移除</button>
												</td>
											</tr>							
											</volist>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<div class="btn">
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

var _selected_coupon_ids = '{pigcms{$first["coupon_ids"]}';
var _selected_coupon_ids_again = '{pigcms{$first["coupon_ids_again"]}';
function select_coupon(type=''){
	var url = '';
	if (type) {
		url = '{pigcms{:U("select_coupon")}'+'&selected='+_selected_coupon_ids_again;
	}else{
		url = '{pigcms{:U("select_coupon")}'+'&selected='+_selected_coupon_ids;
	}
	layer.open({
		type: 2,
		area: ['75%', '700px'],
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
			$.post("{pigcms{:U('ajaxGetCoupon')}",{selected:selected},function(data){
				if(data.length > 0){
					var html = '';
					console.log(data)
					for(var i = 0; i< data.length; i++){
					console.log(i)
						html += '<tr>'+
									'<input type="hidden" name="coupon_ids'+type+'[]" value="'+data[i].coupon_id+'" />'+
									'<td>'+data[i].coupon_id+'</td>'+
									'<td>'+data[i].start_time+'至'+data[i].end_time+'</td>';

						if (data[i].is_discount) {
							html += '<td>满'+data[i].order_money+'打'+data[i].discount_value+'折</td>'; 
						}else{
							html += '<td>满'+data[i].order_money+'减'+data[i].discount+'</td>';
						}
						html += 	'<td>'+data[i].platform_str+'</td>'+
									'<td>'+data[i].cate_name+'</td>'+
									'<td>'+data[i].cate_id+'</td>'+
									'<td>'+
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
	if(confirm('{pigcms{:L_("你确定要移除此优惠券吗？")}')){
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