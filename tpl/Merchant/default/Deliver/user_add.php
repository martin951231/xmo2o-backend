<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-desktop"></i>
				<a href="{pigcms{:U('Deliver/user')}">{pigcms{:L_("配送管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加配送员")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本设置")}</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">{pigcms{:L_("姓名")}</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text"/>
								</div>
								<if condition="$config.international_phone eq 1">
									<div class="form-group">
										<label class="col-sm-1"><label for="password">{pigcms{:L_("区号")}</label></label>
										
									
										<pigcms:national_phone id="phone_country_type" style="height:34px;float:left;margin-right:5px;">
										 	<option value="{pigcms{$vo.code}"  <if condition="$config.qcloud_sms_default_country eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
										</pigcms:national_phone>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label for="phone">{pigcms{:L_("联系电话")}</label></label>
									
									<input class="col-sm-2" size="20" name="phone" id="phone" type="text"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="qq">{pigcms{:L_("密码")}</label></label>
									<input class="col-sm-2" size="20" name="pwd" id="pwd" type="text"/>
								</div>
								<!--div class="form-group" style="display:none;">
									<label class="col-sm-1"><label for="qq">{pigcms{:L_("配送范围")}</label></label>
									<input class="col-sm-2" size="20" name="range" id="range" type="text" value="5"/>
									<span class="form_tips">{pigcms{:L_("（公里）")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">{pigcms{:L_("店铺经纬度")}</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" readonly="readonly"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">{pigcms{:L_("点击选取经纬度")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("配送员常驻地")}</label></label>
									<fieldset id="choose_cityarea"></fieldset>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="adress">{pigcms{:L_("店铺地址")}</label></label>
									<input class="col-sm-2" size="20" name="adress" id="adress" type="text"/>
									<span class="form_tips">{pigcms{:L_("地址不能带有上面所在地选择的省")}/区/{pigcms{:L_("商圈信息。")}</span>
								</div-->
								<div class="form-group">
									<label class="col-sm-1" for="have_meal">{pigcms{:L_("状态")}</label>
									<select name="status" id="status">
										<option value="1" selected="selected">{pigcms{:L_("正常")}</option>
										<option value="0">{pigcms{:L_("禁止")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="have_meal">{pigcms{:L_("交通工具")}</label>
									<select name="traffic">
										<option value="">{pigcms{:L_("未知")}</option>
										<volist name="traffic" id="vo">
										<option value="{pigcms{$key}">{pigcms{$vo.text}</option>
										</volist>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="have_meal">{pigcms{:L_("选择店铺")}</label>
                                    <div class="col-sm-2" style="padding-left:0px;width: 60%">
                                    <volist name="waimai_store" id='waimai'>
                                        <input name="store_ids[]" type="checkbox" value="{pigcms{$waimai.store_id}" style="margin-bottom: 15px" id="l_{pigcms{$waimai.store_id}">  <label for="l_{pigcms{$waimai.store_id}">{pigcms{$waimai.name} - {pigcms{$waimai.adress}</label><br/>
                                    </volist>
                                    </div>
								</div>

                                <div class="form-group">
                                    <label class="col-sm-1" for="have_meal">{pigcms{:L_("是否显示隐私号")}</label>
                                    <select name="show_phone_status" id="show_phone_status">
                                        <option value="1" selected="selected">{pigcms{:L_("显示")}</option>
                                        <option value="0">{pigcms{:L_("隐藏")}</option>
                                    </select>
                                </div>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$('#edit_form').submit(function(){
		$('#edit_form button[type="submit"]').prop('disabled',true).html('{pigcms{:L_("保存中")}...');
		$.post("{pigcms{:U('Deliver/user_add')}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Deliver/user')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>{pigcms{:L_("保存")}');
				alert(result.info);
			}
		})
		return false;
	}); 
</script>
<include file="Public:footer"/>
