<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('SmartCharge/smart_add')}">智能充电桩管理</a>
			</li>
			<li class="active">添加智能充电桩</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()"  action="{pigcms{:U('smart_modify')}">
						<div class="tab-content">
							<input  type="hidden" name='id' value="{pigcms{$_GET['id']}"/>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">二维码名称</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$info['name']}"/>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-1">打开方式</label>
								<select class="col-sm-2" name="open_type" id="open_type" value="{pigcms{$info['open_type']}">
									<option value="0" <if condition="$info['open_type'] eq 0 || !$info">selected="selected"</if>>链接</option>
									<option value="1" <if condition="$info['open_type'] eq 1">selected="selected"</if>>小程序</option>
								</select>

							</div>
							<div class="form-group open_url" style="display:none;">
								<label class="col-sm-1"><label for="open_url">链接</label></label>
								<input class="col-sm-2" size="20" name="open_url" id="open_url" placeholder="请填写链接地址" type="text" value="{pigcms{$info['open_url']}"/>
							</div>
							<div class="form-group wxapp_id" style="display:none;">
								<label class="col-sm-1"><label for="name">打开其他小程序</label></label>
								<select class="col-sm-2"  name="wxapp_id" id="wxapp_id" value="{pigcms{$info['wxapp_id']}" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
									<option value="">选择小程序</option>
									<volist name='wxapp_list' id='vo'>
										<option value="{pigcms{$vo.appid}" <if condition="$info['wxapp_id'] eq $vo['appid']">selected="selected"</if>>{pigcms{$vo.name}</option>
									</volist>
								</select>
							</div>
							<div class="form-group wxapp_page" style="display:none;">
								<label class="col-sm-1"><label for="wxapp_page">小程序页面</label></label>
								<input type="text" class="col-sm-2" name="wxapp_page" id="wxapp_page" size="20" value="{pigcms{$info['wxapp_page']}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/>
							</div>
						</div>
						<div class="space"></div>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info" type="submit">
									<i class="ace-icon fa fa-check bigger-110"></i>
									保存
								</button>
							</div>
						</div>
				</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	function check_submit(){
		if($('#open_type').val() == 0 && $('#open_url').val()==''){
			alert('打开方式为链接，链接不可为空');
			return false;
		}
		if($('#open_type').val() == 1 && $('#wxapp_id').val()==''){
			alert('打开方式为小程序，请选择小陈程序');
			return false;
		}
	}
	$('#open_type').change(function(){
		var vl = $(this).val();
		console.log('vl',vl);
		if (0==vl) {
			$('.open_url').show();
			$('.wxapp_id').hide();
			$('.wxapp_page').hide();
		} else {
			$('.open_url').hide();
			$('.wxapp_id').show();
			$('.wxapp_page').hide();
		}
	});
	$('#open_type').change();

	$('#wxapp_id').change(function(){
		var vls = $(this).val();
		if (vls) {
			$('.wxapp_page').show();
		} else {
			$('.wxapp_page').hide();
		}
	});
	$('#wxapp_id').change();
</script>

<include file="Public:footer"/>