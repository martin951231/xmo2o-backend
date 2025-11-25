<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/pick')}">{pigcms{:L_("自提点管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("自提点地址新增")}</li>
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
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("添加自提点")}</a>
							</li>
						
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="long_lat">{pigcms{:L_("自提点经纬度")}</label></label>
								<input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" readonly="readonly"/>
								&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">{pigcms{:L_("点击选取经纬度")}</a>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label>{pigcms{:L_("自提点")}</label></label>
								<fieldset id="choose_cityarea"></fieldset>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label>{pigcms{:L_("详细地址")}</label></label>
								<input type="text" name="pick_addr" value="" />
							</div>
              <div class="form-group">
                <label class="col-sm-1"><label>{pigcms{:L_("自提点图片")}<font color="red">*</font></label></label>
                <div style="display:inline-block;" id="J_selectImage">
                  <div class="btn btn-sm btn-success" style="position:relative;">{pigcms{:L_("选择本地图片")}</div>
                </div>
                <span class="form_tips">{pigcms{:L_("图片尺寸支持600*600px")}</span>
              </div>
              <div class="form-group">
                <label class="col-sm-1"><label>{pigcms{:L_("图片预览")}</label></label>
                <div id="upload_pic_box">
                  <ul id="upload_pic_ul">
                  </ul>
                </div>
              </div>
							<div class="form-group">
								<label class="col-sm-1"><label>{pigcms{:L_("电话号码")}</label></label>
								<input type="text" name="phone" value="" />
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label>{pigcms{:L_("是否开启")}</label></label>
                                <span><label><input name="is_open" value="0" type="radio">&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</label></span>
                                <span><label><input name="is_open" checked="checked" value="1" type="radio" >&nbsp;<span>{pigcms{:L_("开启")}</span></label></span>
							</div>
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
					({pigcms{:L_("用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。")}
				</div>
			</div>
			<div class="modal-body no-padding" style="width:100%;">
				<form id="map-search" style="margin:10px;">
					<input id="map-keyword" type="textbox" style="width:500px;" placeholder="{pigcms{:L_("尽量填写城市、区域、街道名")}"/>
					<input type="submit" value="{pigcms{:L_("搜索")}"/>
				</form>
				<div style="margin-left:10px;margin-bottom:10px;" id="map_tips">({pigcms{:L_("用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。")}</div>
				<div style="width:100%;height:600px;min-height:600px;" id="cmmap"></div>
			</div>
			<div class="modal-footer no-margin-top">
				<button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
					<i class="ace-icon fa fa-times"></i>
					{pigcms{:L_("关闭")}
				</button>
			</div>
		</div>
	</div>
</div>
<style>
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
#upload_pic_box{margin-top:20px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}

.small_btn{
margin-left: 10px;
padding: 6px 8px;
cursor: pointer;
display: inline-block;
text-align: center;
line-height: 1;
letter-spacing: 2px;
font-family: Tahoma, Arial/9!important;
width: auto;
overflow: visible;
color: #333;
border: solid 1px #999;
-moz-border-radius: 5px;
-webkit-border-radius: 5px;
border-radius: 5px;
background: #DDD;
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='#DDDDDD');
background: linear-gradient(top, #FFF, #DDD);
background: -moz-linear-gradient(top, #FFF, #DDD);
background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#FFF), to(#DDD));
text-shadow: 0px 1px 1px rgba(255, 255, 255, 1);
box-shadow: 0 1px 0 rgba(255, 255, 255, .7), 0 -1px 0 rgba(0, 0, 0, .09);
-moz-transition: -moz-box-shadow linear .2s;
-webkit-transition: -webkit-box-shadow linear .2s;
transition: box-shadow linear .2s;
outline: 0;
}
.small_btn:active{
border-color: #1c6a9e;
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#33bbee', endColorstr='#2288cc');
background: linear-gradient(top, #33bbee, #2288cc);
background: -moz-linear-gradient(top, #33bbee, #2288cc);
background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#33bbee), to(#2288cc));
}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}
.webuploader-element-invisible {
    position: absolute !important;
    clip: rect(1px 1px 1px 1px);
    clip: rect(1px,1px,1px,1px);
}
.webuploader-pick-hover .btn{
    background-color: #629b58!important;
    border-color: #87b87f;
}
</style>
<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>

<if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
    <script  src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&key={pigcms{$config.google_map_ak}&libraries=places"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/google_map.js"></script>
<else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=1"></script>
</if>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript">
  // 商品图片
  setTimeout(function () {
    var uploader = WebUploader.create({
      auto: true,
      swf: '{pigcms{$static_public}js/Uploader.swf',
      server: "{pigcms{:U('Config/pickAddressUploadPic')}",
      pick: {
        id: '#J_selectImage',
        multiple: false
      },
      accept: {
        title: 'Images',
        extensions: 'gif,jpg,jpeg,png',
        mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
      }
    });
    uploader.on('fileQueued', function (file) {
      if ($('.upload_pic_li').size() >= 2) {
        uploader.cancelFile(file);
        alert('{pigcms{:L_("最多上传1张图片！")}');
        return false;
      }
    });
    uploader.on('uploadSuccess', function (file, response) {
      if (response.error == 0) {
        $('#upload_pic_ul').html('<li class="upload_pic_li"><img src="' + response.long_url + '"/><input type="hidden" name="image" value="' + response.url + '"/><br/></li>');
      } else {
        alert(response.info);
      }
    });
    uploader.on('uploadError', function (file, reason) {
      $('.loading' + file.id).remove();
      alert('{pigcms{:L_("上传失败！请重试。")}');
    });

  }, 20);
  uploaderHas = true;

$(document).ready(function(){
	$('#edit_form').submit(function(){
		$.post("{pigcms{:U('Config/pick_address_add')}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Config/pick')}";
			}else{
				alert(result.info);
			}
		})
		return false;
	});
});

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>