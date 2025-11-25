<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
	.page-content .btn-info { border: none;}
	.page-content .clearfix.form-actions {text-align: -webkit-center;}
    .col-sm-1 { width: 14.333%;}
</style>
<div class="page-content" style="background-color: white;">
	<div class="row" style="background-color: white;">
		<div class="col-xs-12">
			<form id="add_info" class="form-horizontal" method="post" action="__SELF__">
				<div class="tab-content">
					<input type="hidden" value="{pigcms{$express['id']}" name="id">
					<div class="form-group">
						<label class="col-sm-1"><label for="housesize">设备号ID：</label></label>
						<input class="col-sm-2" size="20" name="equipment_id" id="equipment_id" placeholder="设备号ID" type="text" value="{pigcms{$express.equipment_id}"/>

					</div>
					<div class="form-group">
						<label class="col-sm-1"><label for="housesize">设备名称：</label></label>
						<input class="col-sm-2" size="20" name="equipment_name" id="equipment_name" placeholder="设备名称" type="text" value="{pigcms{$express.equipment_name}"/>

					</div>
					<if condition="$device_type_info">
						<div class="form-group">
							<label class="col-sm-1"><label for="housesize">设备类型：</label></label>
							&nbsp;&nbsp;&nbsp;<select class=" col-sm-2" name="screen_size_type" id="screen_size_type" style="height: 33px;width: 162px;">
								<volist name="device_type_info" id="vo">
									<option <if condition="$vo['value'] eq $express['screen_size_type']">selected="selected"</if> value="{pigcms{$vo.value}">{pigcms{$vo['name']}</option>
								</volist>
							</select>

						</div>
					</if>
					<div class="form-group">
						<label class="col-sm-1"><label for="housesize">柜子数量：</label></label>
						<input class="col-sm-2" size="20" name="num" id="num" placeholder="柜子数量" type="text" value="{pigcms{$express.num}"/>
						<span class="form_tips" style="color:red">&nbsp;&nbsp;&nbsp;如修改柜子数量,将导致所有的柜子数据清空</span>
					</div>
				</div>
				<div class="space"></div>
				<div class="clearfix form-actions">
					<div class="col-md-offset-3 col-md-9" style="margin-left:50%">
						<button class="btn btn-info" type="submit">
							<i class="ace-icon fa fa-check bigger-110"></i>
							添加
						</button>
					</div>
				</div>
		</div>
		</form>
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
	#upload_ico_pic_box,#upload_hello_pic_box{margin-top:20px;height:100px;}
	#upload_ico_pic_box .upload_pic_li,#upload_hello_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
	#upload_ico_pic_box img,#upload_hello_pic_box img{width:100px;height:70px;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>
<script src="./static/kindeditor/kindeditor.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>
<script type="text/javascript">
	KindEditor.ready(function(K){
		console.log("{pigcms{$config.site_url}");
		console.log("{pigcms{:U('Bill/ajax_upload_pic')}");
		var kind_editor = K.editor({
			allowFileManager : true
		});

		K('#J_selectImage').click(function(){

			if($('.upload_pic_li').size() >= 5){
				alert('最多上传5个图片！');
				return false;
			}
			kind_editor.uploadJson ="{pigcms{$config.site_url}{pigcms{:U('Bill/ajax_upload_pic')}";
			kind_editor.loadPlugin('image', function(){
				kind_editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#course_pic').val(),
					clickFn : function(url, title, width, height, border, align) {
						$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImg(\''+title+'\',this);return false;">[ 删除 ]</a></li>');
						kind_editor.hideDialog();
					}
				});
			});
		});
	});

	function deleteImg(path,obj){
		$.post("{pigcms{:U('ajax_del_pic')}",{path:path});
		$(obj).closest('.upload_pic_li').remove();
	}


	$('#add_info').submit(function(){
		$.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
			console.log('result', result);

			if (result && result.status==1) {
				layer.open({
					content: '修改成功',
					shadeClose: false,
					closeBtn: 0
					,btn: ['确定']
					,yes:function(index){
						window.parent.close_all_page(result);
					}
				})


			} else if (result.info) {
				layer.open({
					content: result.info,
					shadeClose: false,
					closeBtn: 0
					,btn: ['知道了']
				})
			} else {
				layer.open({content: '添加失败'});
			}
		});
		return false;
	});


</script>
<include file="Publics:footer"/>
