<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Library/set_send_price_config')}">代送基本设置</a>
			</li>
			<li class="active">基本信息设置</li>
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
					<form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Library/set_send_price_config')}" enctype="multipart/form-data">
						<div class="tab-content">
							<div class="form-group express_send_price">
								<label class="col-sm-1"><label for="express_send_price">代发价格</label></label>
								<input class="col-sm-1" size="10" name="express_send_price" id="express_send_price" type="text" placeholder="请设置代发价格" value="{pigcms{$village_info.express_send_price}" />
								<span class="form_tips" style="line-height: 30px;">请设置代发价格</span>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<if condition="in_array(3,$house_session['menus'])">
									<button type="submit" class="btn btn-info">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									<else/>
									<button type="button" class="btn btn-info disabled" disabled="disabled" style="cursor: not-allowed;">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
									</if>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			</div>
		</div>
	</div>
</div>
<script>

	$('#edit_form').submit(function(){

		var express_send_price = $("#express_send_price").val();
		if(express_send_price<0){
			alert('请设置不小于0的代发价格！');
			return false;
		}

		$('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
		if (lock = 1) {
			return;
		}
		lock = 1;


		$.post("{pigcms{:U('Library/set_send_price_config')}",$('#edit_form').serialize(),function(result){
			lock = 0;
			if(result.status == 1){
				window.location.href = "{pigcms{:U('Library/set_send_price_config')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				alert(result.info);
			}
		})
		return false;
	});
</script>

<include file="Public:footer"/>