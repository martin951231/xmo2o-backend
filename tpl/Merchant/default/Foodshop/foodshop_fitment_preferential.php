<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("今日特惠")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
			table th{padding:17px 10px 19px;}
			table td{padding:17px 10px 19px 15px;}
			#good_list li{list-style: none;}
			#good_list li .del{color:blue;float:right;cursor:pointer;-webkit-user-select: none;-webkit-touch-callout: none;}
			#good_list li:hover{color:red;}
			#good_list li .del:hover{text-decoration:underline;color:red;}
		</style>
	</head>
	<body>
		<form id="myform" method="post" action="{pigcms{:U('foodshop_fitment_preferential_save')}" autocomplete="off">
			<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
			<table>
				<tr>
					<th width="20%">{pigcms{:L_("名称")}</th>
					<td width="80%" colspan="3">
						<input type="text" class="input" name="preferential_name" id="preferential_name" value="{pigcms{$subject_info.preferential_name|default=L_("今日特惠")}" style="width:200px;"/>
					</td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("关联商品")}</th>
					<td width="80%" colspan="3">
						<div>
							<ul id="good_list">
								<volist name="goodArr" id="vo">
									<li>
										<input class="good_id" type="hidden" name="good_id[]" value="{pigcms{$vo.goods_id}"/>
										<span>{pigcms{$vo.name}</span>
										<div class="del">[{pigcms{:L_("删除")}]</div>
									</li>
								</volist>
							</ul>
							<button type="button" id="choose_good_btn" style="margin-left:0px;">{pigcms{:L_("选择商品")}</button>
							<span style="margin-left:20px;">最多选择8件商品.</span>
						</div>
					</td>
				</tr>
				<tr>
				<th width="20%">{pigcms{:L_("是否显示")}</th>
				<td width="80%" colspan="3">
					<select name="preferential_status" id="preferential_status" style="width:150px;">
						<option value="1" <if condition="$subject_info['preferential_status'] eq '1'">selected="selected"</if>>{pigcms{:L_("显示")}</option>
						<option value="0" <if condition="$subject_info['preferential_status'] eq '0'">selected="selected"</if>>{pigcms{:L_("不显示")}</option>
					</select>
				</td>
			</tr>
			</table>
			<div class="btn">
				<button type="submit" id="submit" style="margin-bottom:30px;">{pigcms{:L_("保存")}</button>
			</div>
		</form>
		<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
		<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
		<script>
			if($('#good_list li').size() > 0){
				$('#good_list').css('margin-bottom','20px');
			}
			parent.subject_win_name = window.name;
			function build_good_save(goodData){
				$('#good_list').css('margin-bottom','20px');
				console.log(goodData);
				for(var i in goodData){
					$('#good_list').append('<li><input class="good_id" type="hidden" name="good_id[]" value="'+goodData[i].id+'"/><span>'+goodData[i].title+'</span><div class="del">[{pigcms{:L_("删除")}]</div></li>');
				}
			}
			$(function(){
				$('#preferential_name').keyup(function(){
					$('#fitment_showcase_name',parent.document).html($('#preferential_name').val());
				});
				$('#preferential_status').change(function(){
					if($('#preferential_status').val() == '1'){
						$('.fitment_showcase',parent.document).show();
					}else{
						$('.fitment_showcase',parent.document).hide();
					}
				});
				$('#good_list .del').live('click',function(){
					$(this).closest('li').remove();
					if($('#good_list li').size() == 0){
						$('#good_list').css('margin-bottom','0px');
					}
				});
				$('#choose_good_btn').click(function(){
					var selecteditemsArr = [];
					$.each($('#good_list .good_id'),function(i,item){
						selecteditemsArr.push($(item).val());
					});
					if(selecteditemsArr.length >= 8){
						parent.layer.msg('{pigcms{:L_("最多仅能添加8个商品。")}<br/>{pigcms{:L_("请先删除再进行操作。")}');
						return false;
					}
					parent.layer.open({
						title:false,
						closeBtn: 0,
						type:2,
						content:'{pigcms{$this->config['site_url']}/merchant.php?c=Diypage&a=foodshop_goods_library&store_id={pigcms{$_GET.store_id}&type=more&pageFrom=shop_fitment&max_num=8&number='+new Date().getTime()+'&selecteditems='+selecteditemsArr.join(','),
						area:['650px','576px'],
						shade: 0.2,
						cancel:function(){
							
						},
						move:false
					});
				});
				$('#myform').submit(function(){
					if($('#preferential_name').val() == ''){
						parent.layer.msg('{pigcms{:L_("请填写橱窗名称")}');
						return false;
					}
					if($('#preferential_status').val() == '1' && $('#good_list li').size() == 0){
						parent.layer.msg('{pigcms{:L_("请关联至少1个商品，或者关闭橱窗显示")}');
						return false;
					}
					if($('#good_list li').size() > 8){
						parent.layer.msg('{pigcms{:L_("最多仅能关联8个商品，现在关联了")} ' + $('#good_list li').size() + ' {pigcms{:L_("个，请先删除。")}');
						return false;
					}
					
					$('#submit').prop('disabled',true).html('{pigcms{:L_("保存中")}...');
					$.post($('#myform').attr('action'),$('#myform').serialize(),function(result){
						if(result.status == 1){
							parent.layer.alert(result.info,{
								end:function(){
									window.parent.art.dialog({id:'fitment'}).close();
								}
							});
						}else{
							parent.layer.alert(result.info);
							$('#submit').prop('disabled',false).html('{pigcms{:L_("保存")}');
						}
					});
					return false;
				});
			});
		</script>
	</body>
</html>