<include file="Public:header"/>
<div class="main-content">
	<style type="text/css">
		fieldset:disabled select{
			background: #ddd;
		}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("编辑店铺")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
								<input type="hidden" name="id" value="{pigcms{$now_area.id}"/>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">{pigcms{:L_("经纬度")}</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" <gt name="now_area.id" value="0">value="{pigcms{$now_area.lng},{pigcms{$now_area.lat}"</gt> type="text" readonly="readonly" data-lat ="{pigcms{$now_area.lat}" data-long = "{pigcms{$now_area.lng}" />
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">{pigcms{:L_("点击选取经纬度")}</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>{pigcms{:L_("店铺所在地")}</label></label>
									<if condition="empty($_SESSION['system']) and isset($config['merchant_edit_circle']) and $config['merchant_edit_circle'] eq 0">
										<fieldset id="choose_cityarea" province_id="{pigcms{$now_area.province_id}" city_id="{pigcms{$now_area.city_id}" area_id="{pigcms{$now_area.area_id}" circle_id="{pigcms{$now_area.circle_id}" market_id="{pigcms{$now_area.market_id}" disabled='disabled'></fieldset>
									<else/>
										<fieldset id="choose_cityarea" province_id="{pigcms{$now_area.province_id}" city_id="{pigcms{$now_area.city_id}" area_id="{pigcms{$now_area.area_id}" circle_id="{pigcms{$now_area.circle_id}" market_id="{pigcms{$now_area.market_id}"></fieldset>
									</if>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="address">{pigcms{:L_("位置名称")}</label></label>
									<input class="col-sm-2" size="20" name="address" id="address" value="{pigcms{$now_area.address}" type="text"/>
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
<div id="modal-table" class="modal fade" tabindex="-1" style="display:block;">
	<div class="modal-dialog" style="width:80%;">
		<div class="modal-content" style="width:100%;">
			<div class="modal-header no-padding" style="width:100%;">
				<div class="table-header">
					<button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
						<span class="white">&times;</span>
					</button>
					{pigcms{:L_("选择经纬度")}
				</div>
			</div>
			<div class="modal-body no-padding" style="width:100%;">
				<form id="map-search" style="margin:10px;">
					<input id="map-keyword" type="textbox" style="width:500px;" placeholder="{pigcms{:L_("尽量填写城市、区域、街道名")}"/>
					<input type="submit" value="{pigcms{:L_("搜索")}"/>
				</form>
				<div style="margin-left:10px;margin-bottom:10px;" id="map_tips">({pigcms{:L_("用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。")}</div>
				<div style="width:100%;height:560px;min-height:560px;" id="cmmap"></div>
			</div>

			<div class="modal-footer no-margin-top">
				<button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
					<i class="ace-icon fa fa-times"></i>
					{pigcms{:L_("关闭")}
				</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- PAGE CONTENT ENDS -->

<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}";

var default_lng = '{pigcms{$default_city.area_lng|default=116.331398}';
var default_lat = '{pigcms{$default_city.area_lat|default=39.897445}';

</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script  src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&key={pigcms{$config.google_map_ak}&libraries=places"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/google_map.js"></script>
    <else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=1"></script>
</if>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<style>
.BMap_cpyCtrl{display:none;}
input.ke-input-text {
background-color: #FFFFFF;
background-color: #FFFFFF!important;
font-family: "sans serif",tahoma,verdana,helvetica;
font-size: 12px;
line-height: 24px;
height: 24px;
padding: 2px 4px;
border-color: #848484 #E0E0E0 #E0E0E0 #848484;
border-style: solid;
border-width: 1px;
display: -moz-inline-stack;
display: inline-block;
vertical-align: middle;
zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box{margin-top:20px;height:150px;}
#upload_pic_box .upload_pic_li,.upload_pic_li_logo{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>
<include file="Public:footer"/>
