<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Lang/data_import')}" class="on">多语言导入</a>
		</ul>
	</div>	
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<colgroup><col> <col> <col> <col><col><col><col><col><col width="240" align="center"> </colgroup>
				<thead>
					<tr>
						<th>模块</th>
						<th>下载Excel数据<span style="color:red;">（下载前请先添加好数据）</span></th>
						<th style="width: 500px;">导入Excel数据<span style="color:red;">（导入的表格必须是左边下载的表格）</span></th>
					</tr>
				</thead>
				<tbody>
                    <tr>
                        <td>商家信息</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'merchant'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="merchant_file" data-name="merchant" /></td>
                    </tr>
					<tr>
						<td>商家左侧菜单</td>
						<td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'new_merchant_menu'))}" class="download_excel">下载表格</a></td>
						<td><input type="file" id="new_merchant_menu_file" data-name="new_merchant_menu" /></td>
					</tr>
					<tr>
						<td>城市区域</td>
						<td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'area'))}" class="download_excel">下载表格</a></td>
						<td><input type="file" id="area_file" data-name="area" /></td>
					</tr>
                    <tr>
                        <td>手机网站导航</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'slider','category'=>'wap_slider'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="wap_slider_file" data-name="slider" /></td>
                    </tr>
                    <tr>
                        <td>外卖导航</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'slider','category'=>'waimai_slider'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="waimai_slider_file" data-name="slider" /></td>
                    </tr>
                    <tr>
                        <td>手机站{pigcms{$config.shop_alias_name}导航</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'slider','category'=>'wap_shop_slider'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="wap_shop_slider_file" data-name="slider" /></td>
                    </tr>
                    <tr>
                        <td>APP底部导航</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'home_menu','category'=>'app_footer'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="app_footer_file" data-name="home_menu" /></td>
                    </tr>
                    <tr>
                        <td>平台底部导航</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'home_menu','category'=>'plat_footer'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="plat_footer_file" data-name="home_menu" /></td>
                    </tr>
                    <tr>
                        <td>{pigcms{$config.shop_alias_name}底部导航</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'home_menu','category'=>'shop_footer'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="shop_footer_file" data-name="home_menu" /></td>
                    </tr>
					<tr>
						<td>关于我们</td>
						<td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'appintro'))}" class="download_excel">下载表格</a></td>
						<td><input type="file" id="appintro_file" data-name="appintro" /></td>
					</tr>

					<tr>
						<td>商家分类</td>
						<td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'merchant_category'))}" class="download_excel">下载表格</a></td>
						<td><input type="file" id="merchant_category_file" data-name="merchant_category" /></td>
					</tr>
					<tr>
						<td>等级管理</td>
						<td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'user_level'))}" class="download_excel">下载表格</a></td>
						<td><input type="file" id="user_level_file" data-name="user_level" /></td>
					</tr>
                    <tr>
                        <td>系统优惠券</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'system_coupon'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="system_coupon_file" data-name="system_coupon" /></td>
                    </tr>
                    <tr>
                        <td>系统配置项</td>
                        <td><a href="{pigcms{:U('Lang/config_import_download')}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="config_file" data-name="config" /></td>
                    </tr>
                    <tr>
                        <td>国际区号</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'national_phone'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="national_phone_file" data-name="national_phone" /></td>
                    </tr>
                    <tr>
                        <td>店铺分类</td>
                        <td><a href="{pigcms{:U('Lang/data_import_download',array('type'=>'shop_category'))}" class="download_excel">下载表格</a></td>
                        <td><input type="file" id="shop_category_file" data-name="shop_category" /></td>
                    </tr>
				</tbody>
			</table>
		</div>

</div>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/fileinput.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/locales/zh.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}bootstrap-fileinput-master/css/fileinput.css" />
<script type="text/javascript">
    initUpload("merchant_file","merchant");
	initUpload("new_merchant_menu_file","new_merchant_menu");
	initUpload("area_file","area");
    initUpload("wap_slider_file","slider");
    initUpload("waimai_slider_file","slider");
    initUpload("wap_shop_slider_file","slider");
    initUpload("app_footer_file","home_menu");
    initUpload("plat_footer_file","home_menu");
    initUpload("shop_footer_file","home_menu");
    initUpload("appintro_file","appintro");
    initUpload("merchant_category_file","merchant_category");
    initUpload("user_level_file","user_level");
    initUpload("system_coupon_file","system_coupon");
    initUpload("national_phone_file","national_phone");
    initUpload("shop_category_file","shop_category");
    initUpload_config("config_file");

	function initUpload_config(ctrlId){
		$("#"+ctrlId).fileinput({
			uploadUrl : '{pigcms{:U("config_import_upload")}',
			language: 'zh', //设置语言
			showBrowse : true,
			showPreview : true,
			showRemove : true,
			showUpload : true,
			showCancel : false,
			showClose : true,
			showCaption : true,
			dropZoneEnabled : false,
			allowedFileExtensions : ['xls']
		}).on("fileuploaded", function (event, data, previewId, index) {
	        if(data.response.status == 1)
	        {
	            layer.msg(data.files[index].name + data.response.info,{offset:'300px'});
	        	//关闭
	            $(".close").click();
	        }
	        else{
	            layer.msg(data.files[index].name + "{pigcms{:L_("导入失败")} " + data.response.info, {icon: 5,offset:'300px'});
		        //重置
		        $("#"+ctrlId).fileinput("clear");
		        $("#"+ctrlId).fileinput("reset");
		        $('#'+ctrlId).fileinput('refresh');
		        $('#'+ctrlId).fileinput('enable');
		        $(".close").click();
	        }
	    });
	}

	function initUpload(ctrlId, type){
		if(type == ''){
			return ;
		}
		$("#"+ctrlId).fileinput({
			uploadUrl : '{pigcms{:U("data_import_upload")}',
			language: 'zh', //设置语言
			showBrowse : true,
			showPreview : true,
			showRemove : true,
			showUpload : true,
			showCancel : false,
			showClose : true,
			showCaption : true,
			dropZoneEnabled : false,
			allowedFileExtensions : ['xls'],
			uploadExtraData: function (e) {
				var param = {
					"type" : type
				}
	            return param;
	        }
		}).on("fileuploaded", function (event, data, previewId, index) {
	        if(data.response.status == 1)
	        {
	            layer.msg(data.files[index].name + data.response.info,{offset:'300px'});
	        	//关闭
	            $(".close").click();
	        }
	        else{
	            layer.msg(data.files[index].name + "{pigcms{:L_("导入失败")} " + data.response.info, {icon: 5,offset:'300px'});
		        //重置
		        $("#"+ctrlId).fileinput("clear");
		        $("#"+ctrlId).fileinput("reset");
		        $('#'+ctrlId).fileinput('refresh');
		        $('#'+ctrlId).fileinput('enable');
		        $(".close").click();
	        }
	    });
	}

</script>
<include file="Public:footer"/>