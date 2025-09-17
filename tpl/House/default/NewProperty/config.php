<include file="NewPublic:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Index/index')}">社区管理</a>
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
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
                            <if condition="$type eq 0">
                                <li class="active">
                                    <a href="#basicinfo" data-toggle="tab">基本设置</a>
                                </li>
                            </if>
                            <li>
                                <a href="#modifyparam" data-toggle="tab">参数设置</a>
                            </li>
                            <if condition="$type eq 0">
                                <li>
                                    <a href="#modifypwd" data-toggle="tab">修改密码</a>
                                </li>
                            </if>
                            <if condition="$type eq 1">
                                <li class="active">
                                    <a href="#modifypwd" data-toggle="tab">修改密码</a>
                                </li>
                            </if>
						</ul>
					</div>
					<form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('NewProperty/config_edit')}" enctype="multipart/form-data">
						<div class="tab-content">
                            <div class="tab-pane" id="modifyparam">
                                <div class="form-group" style="width:100%;height: 100px;color: red">
                                    <p style="margin-top: 8px">&nbsp;&nbsp;1、四舍五入：在设置保留的小数位基础上进行四舍五入的方法统计费用。</p>
                                    <p style="margin-top: 8px">&nbsp;&nbsp;2、全舍：保留了设置的小数位后，其余的小数全舍。</p>
                                    <p style="margin-top: 8px">&nbsp;&nbsp;3、当后台设置保留四位小数、三位小数、两位小数时，用户端，只展示两个数;当后台设置保留一位小数时，按照设置的值展示一个数</p>
                                    <p style="margin-top: 8px">&nbsp;&nbsp;4、预缴账单支付30分钟不缴费自动作废，和始终不作废两种模式</p>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="plate_open">保留方式</label></label>
                                    <input type="radio" <if condition="$house_property['type'] eq 1">checked</if> name="type" value="1" /> <span>四舍五入</span>
                                    &nbsp;&nbsp;
                                    <input type="radio" <if condition="$house_property['type'] eq 2">checked</if> name="type" value="2" /> <span>全舍</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="plate_open">其他小数位数</label></label>
                                    <input class="col-sm-2" value="{pigcms{$house_property['other_digit']}" type="number" id="other_digit" name="other_digit"/>
                                    <label class="col-sm-3"><label>最多保留四位</label></label>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="village_name">水电燃小数位数</label></label>
                                    <input class="col-sm-2" value="{pigcms{$house_property['meter_digit']}" type="number" id="meter_digit" name="meter_digit"/>
                                    <label class="col-sm-3"><label>最多保留四位</label></label>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="village_name">预缴账单作废</label></label>
                                    <input type="radio" <if condition="$house_property['deleteBillMin'] eq 0">checked</if> name="deleteBillMin" value="0" /> <span>不作废</span>
                                    &nbsp;&nbsp;
                                    <input type="radio" <if condition="$house_property['deleteBillMin'] eq 30">checked</if> name="deleteBillMin" value="30" /> <span>30分钟作废</span>
                                </div>
                            </div>
                            <if condition="$type eq 0"><div class="tab-pane active" id="basicinfo"></if>
                            <if condition="$type eq 1"><div class="tab-pane" id="basicinfo"></if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">物业名称</label></label>
                                    <if condition="$house_property.set_name_time elt 0">
                                        <input class="col-sm-2" size="20" name="property_name" value="{pigcms{$house_property.property_name}" type="text"/><span class="form_tips">(&nbsp;仅可修改一次，后续有变化请联系平台进行修改&nbsp;)</span>
                                        <else/>
                                        <input class="col-sm-2" size="20" value="{pigcms{$house_property['property_name']}" type="text" style="border:none;background:white!important;" readonly/><span class="form_tips">(&nbsp;如果想再次修改物业公司名称,请联系平台修改&nbsp;)</span>
                                    </if>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="name">物业公司名称简称</label></label>
                                    <if condition="$house_property.set_short_name_time elt 0">
                                        <input class="col-sm-2" size="20" name="property_short_name" value="{pigcms{$house_property.property_short_name}" type="text"/><span class="form_tips">(&nbsp;仅可修改一次，后续有变化请联系平台进行修改&nbsp;)</span>
                                        <else/>
                                        <input class="col-sm-2" size="20" value="{pigcms{$house_property['property_short_name']|default='暂无'}" type="text" style="border:none;background:white!important;" readonly/><span class="form_tips">(&nbsp;如果想再次修改物业公司名称简称,请联系平台修改&nbsp;)</span>
                                    </if>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label>所在省市区</label></label>
                                    <fieldset id="choose_cityarea" province_id="{pigcms{$house_property.province_id}" city_id="{pigcms{$house_property.city_id}" area_id="{pigcms{$house_property.area_id}"></fieldset>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="village_address">物业地址</label></label>
									<input class="col-sm-3" name="property_address" id="adress" type="text" value="{pigcms{$house_property.property_address}"/>
									<span class="form_tips">地址不能带有上面所在地选择的省/市/区信息。</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="property_logo">物业logo</label></label>
                                    <input class="btn btn-primary" type="button" id="image3" value="选择图片" />
                                    <input type="hidden" name="property_logo" value="{pigcms{$house_property.property_logo}" id="property_logo" />

                                    <img src="{pigcms{:replace_file_domain($house_property['property_logo'])}" id="village_logo_view" style="padding: 15px; width: 80px; <if condition="!$house_property['property_logo']">display: none;</if>">

                                    <span class="form_tips">建议上传200*200的图片</span>
                                </div>
                                <if condition="$config['property_self_logo'] eq 1 ">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="house_property_login">物业登录地址</label></label>
                                        <input class="col-sm-4" id="house_property_login" value="{pigcms{$house_property_login}" type="text" readonly/>
                                        <span class="form_tips">
                                            <div style="line-height: 16px;" class="btn btn-success" onclick="copyContent();">点击复制</div>
                                        </span>
                                    </div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="long_lat">物业经纬度</label></label>
                                    <input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" readonly value="<if condition="$house_property['long']">{pigcms{$house_property.long},{pigcms{$house_property.lat}</if>"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="property_phone">物业联系方式</label></label>
									<input class="col-sm-2" size="20" name="property_phone" value="{pigcms{$house_property['property_phone']}" type="text" style=""/>
									<span class="form_tips" style="color: gray;margin-left:1px;">(电话号码以空格分开)</span>
								</div>
							</div>
                            <if condition="$type eq 0"><div class="tab-pane" id="modifypwd"></if>
                            <if condition="$type eq 1"><div class="tab-pane active" id="modifypwd"></if>
                            	<div class="form-group">
									<label class="col-sm-1"><label for="plate_open">原密码</label></label>
									<input class="col-sm-2" value="" type="password" id="old_pwd" name="old_pwd"/>
									<label class="col-sm-3"><label>不修改密码可不填写</label></label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="village_name">新密码</label></label>
									<input class="col-sm-2" value="" type="password" id="pwd" name="pwd"/>
									<label class="col-sm-3"><label>不修改密码请留空，最少6个字符</label></label>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>确认密码</label></label>
									<input class="col-sm-2" value="" type="password" id="sure_pwd" name="sure_pwd"/>
									<label class="col-sm-3"><label>请再输入一次上面的新密码，以便确认输对了</label></label>
								</div>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button type="submit" class="btn btn-info">
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
                    (用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。
                </div>
            </div>
            <div class="modal-body no-padding" style="width:100%;">
                <form id="map-search" style="margin:10px;">
                    <input id="map-keyword" type="textbox" style="width:500px;" placeholder="尽量填写城市、区域、街道名"/>
                    <input type="submit" value="搜索"/>
                </form>
                <div style="width:100%;height:600px;min-height:600px;" id="cmmap"></div>
            </div>
            <div class="modal-footer no-margin-top">
                <button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    关闭
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
</script>

<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>

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
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>    
<script>
    KindEditor.ready(function(K) {
        var editor = K.editor({
            allowFileManager : true
        });
        K('#image3').click(function() {
            editor.uploadJson = "{pigcms{:U('NewProperty/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_logo_view');
                        img.attr("src",url);
                        img.show();
                        K("#property_logo").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
        K('#weixin_qr_code_put').click(function() {
            editor.uploadJson = "{pigcms{:U('NewProperty/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#weixin_qr_code_view');
                        img.attr("src",url);
                        img.show();
                        K("#weixin_qr_code").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
    });
</script>
<script type="text/javascript">
var lock = 0, test;

	$('#edit_form').submit(function(){
		var old_pwd = $("#old_pwd").val();
		var new_pwd = $("#pwd").val();
		var sure_pwd = $("#sure_pwd").val();
		if (new_pwd) {
			if (!old_pwd) {
				alert('请输入原密码！');
				return false;
			}
			if (new_pwd.length < 6) {
				alert('密码最少6位！');
				return false;
			}
			if (new_pwd!=sure_pwd) {
				alert('确认密码与新密码不一样！');
				return false;
			}
		}
		
		$('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
		if (lock = 1) {
			return;
		}
		lock = 1;
		$.post("{pigcms{:U('NewProperty/config_edit')}",$('#edit_form').serialize(),function(result){
			lock = 0;
			if(result.status == 1){
				window.location.href = "{pigcms{:U('NewProperty/config')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				alert(result.info);
			}
		})
		return false;
	});


    function copyContent(){
        var copyobject=document.getElementById("house_property_login");
        copyobject.select();
        document.execCommand("Copy");
        layer.alert('已复制成功哦~');
    };

</script>

<include file="NewPublic:footer"/>