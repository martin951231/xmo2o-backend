<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tasks"></i>
				<a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
			</li>
			<li class="active">编辑</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form class="form-horizontal" method="post" onSubmit="return check_submit();" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$_GET['cate_id']}" name="cate_id">
							<input type="hidden" value="{pigcms{$cate.id}" name="id">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">序号：</label></label>
									<input class="col-sm-2" size="20" name="num_id" id="num_id" type="text" value="{pigcms{$cate['num_id']}"/>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="name">二维码名称：</label></label>
									<input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$cate['name']}"/>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">位置:</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" value="<if condition="$cate['long']">{pigcms{$cate.long},{pigcms{$cate.lat}</if>"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
								</div>
							</div>
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1">上传图片</label>
									<input class="btn btn-primary" type="button" id="image3" value="选择图片" />
									<input type="hidden" name="image_path" value="{pigcms{$cate['image_path']}" id="village_logo" />

									<img src="{pigcms{:replace_file_domain($cate['image_path'])}" id="village_logo_view" style="padding: 15px; width: 80px; <if condition="!$cate['image_path']">display: none;</if>">

									<span class="form_tips">建议上传700*350的图片</span>
								</div>
							</div>
                            <if condition="$dataList">
								<volist name="dataList" id="vo">
									<div id="basicinfo" class="tab-pane active">
										<div class="form-group">
											<label class="col-sm-1"><label for="name" style="font-weight:bold;">{pigcms{$vo.field_name}</label></label>
										</div>
									</div>
									<volist name="vo.field_cate_test" id="voo">
										<if condition="$voo['type'] eq 1">
                                        <!--文本-->
											<div id="basicinfo" class="tab-pane active">
												<div class="form-group">
													<label class="col-sm-1"><label for="name">{pigcms{$voo.title}：</label></label>
													<input class="col-sm-2" size="20" name="{pigcms{$voo.key}[value][]" id="" type="text" value="{pigcms{$cate['entication_field'][$voo['key']]['value'][0]}"/>
													<input type="hidden" name="{pigcms{$voo.key}[type][]" value="{pigcms{$voo.type}"/>
													<input type="hidden" name="{pigcms{$voo.key}[title][]" value="{pigcms{$voo.title}"/>

												</div>
											</div>
										<elseif condition="$voo['type'] eq 2"/>
										<!--图片-->
											<div id="basicinfo" class="tab-pane active">
												<div class="form-group">
													<label class="col-sm-1">{pigcms{$voo.title}</label>
													<input class="btn btn-primary click_image_{pigcms{$voo.acid} up_click_image" data-acid="{pigcms{$voo.acid}" type="button" id="image{pigcms{$voo.acid}" value="选择图片" />
													<input type="hidden" name="{pigcms{$voo.key}[value][]" value="{pigcms{$cate['entication_field'][$voo['key']]['value'][0]}" id="click_image_hidden_{pigcms{$voo.acid}" />
<!--													<img src="" id="village_logo_view_11" style="padding: 15px; width: 80px;">-->
													<img src="{pigcms{:replace_file_domain($cate['entication_field'][$voo['key']]['value'][0])}" id="village_logo_view_{pigcms{$voo.acid}" style="padding: 15px; width: 80px; <if condition="!($cate['entication_field'][$voo['key']]['value'][0])">display: none;</if>">
													<input type="hidden" name="{pigcms{$voo.key}[type][]" value="{pigcms{$voo.type}"/>
													<input type="hidden" name="{pigcms{$voo.key}[title][]" value="{pigcms{$voo.title}"/>
												</div>
											</div>
										<elseif condition="$voo['type'] eq 3"/>
										<!--日期-->
											<div id="basicinfo" class="tab-pane active">
												<div class="form-group">
													<label class="col-sm-1">{pigcms{$voo.title}：</label>
													<input class="col-sm-2 Wdate" type="text" readonly style="height:30px;" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd',vel:'stop_apply_time{pigcms{$voo.key}'})" value="{pigcms{$cate['entication_field'][$voo['key']]['value'][0]}"/>
													<input name="{pigcms{$voo.key}[value][]" id="stop_apply_time{pigcms{$voo.key}" type="hidden" value="{pigcms{$cate['entication_field'][$voo['key']]['value'][0]}"/>
													<input type="hidden" name="{pigcms{$voo.key}[type][]" value="{pigcms{$voo.type}"/>
													<input type="hidden" name="{pigcms{$voo.key}[title][]" value="{pigcms{$voo.title}"/>
												</div>
											</div>
										</if>

									</volist>
								</volist>
							</if>
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

	<div id="modal-table" class="modal fade map-modal" tabindex="-1" style="display:block;">
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
<if condition="$config['tianditu_web_key']">
    <script src="https://api.tianditu.gov.cn/api?v=4.0&tk={pigcms{$config.tianditu_web_key}" type="text/javascript"></script>
    <script src="/tpl/Static/js/coordinate.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/tianditu_map.js"></script>
<else />
	<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>
</if>

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
			editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
			editor.loadPlugin('image', function() {
				editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#url3').val(),
					clickFn : function(url, title, width, height, border, align) {
						var img = K('#village_logo_view');
						img.attr("src",url);
						img.show();
						K("#village_logo").val(url);
						editor.hideDialog();
					}
				});
			});
		});
        K('.up_click_image').click(function() {
			var acid= K(this).attr('data-acid');
			console.log('this',K(this));
			console.log('acid',acid);
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_logo_view_'+acid);
                        img.attr("src",url);
                        img.show();
                        K('#village_logo_view_'+acid).val(url);
                        K('#click_image_hidden_'+acid).val(url);
                        editor.hideDialog();
                    }
                });
            });
        });


	});

	KindEditor.ready(function(K) {
		var editor = K.editor({
			allowFileManager : true
		});
		K('.click_image_11').click(function() {
			editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
			editor.loadPlugin('image', function() {
				editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#url3').val(),
					clickFn : function(url, title, width, height, border, align) {
						var img = K('#village_logo_view_11');
						img.attr("src",url);
						img.show();
						K("#click_image_hidden_11").val(url);
						editor.hideDialog();
					}
				});
			});
		});

	});
function check_submit(){
	if($('#name').val()==''){
		alert('名称不能为空！');
		return false;
	}
	}
</script>




<include file="Public:footer"/>