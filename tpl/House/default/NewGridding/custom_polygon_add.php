<include file="Publics:header"/>


<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
	.col-sm-2 {
		width: 15rem;
	}
	.col-sm-3 {
		min-width: 22rem;
	}
	.charge_avatar_box img {
		width: 100px;
		height: 100px;
	}
	.charge_avatar_box #input_file_view {
		padding: 10px;
	}
</style>
<div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
                    <form  id="add_info" class="form-horizontal" method="post" action="__SELF__" enctype="multipart/form-data">
						<input  name="id" type="hidden"  value="{pigcms{$_GET['id']}"/>
						<input  name="custom_id" type="hidden"  value="{pigcms{$_GET['custom_id']}"/>
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-2"><label for="polygon_name">区域名称：</label></label>
								<input validate="required:true" <if condition="$_GET['look'] eq 1">disabled='disabled'</if> class="col-sm-3" size="20" name="polygon_name" id="polygon_name" placeholder="请输入区域名称" type="text" value="{pigcms{$custom_polygon['polygon_name']}"/>
								<span class="form_tips">必填</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2"><label for="charge_name">姓名：</label></label>
								<input <if condition="$_GET['look'] eq 1">disabled='disabled'</if> class="col-sm-3" size="20" name="charge_name" id="charge_name" placeholder="请输入网格员姓名" type="text" value="{pigcms{$custom_polygon['charge_name']}"/>
								<span class="form_tips">必填</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2"><label for="layer_room">性别：</label></label>

								<select <if condition="$_GET['look'] eq 1">disabled='disabled'</if> class="col-sm-3" name="sex" id="sex" >
									<option value="1" <if condition="$custom_polygon['sex'] eq 1">selected='selected'</if>>男</option>
									<option value="2" <if condition="$custom_polygon['sex'] eq 2">selected='selected'</if>>女</option>
								</select>

							</div>
							<div class="form-group">
								<label class="col-sm-2"><label for="charge_phone">联系方式：</label></label>
								<input validate="required:true" <if condition="$_GET['look'] eq 1">disabled='disabled'</if> class="col-sm-3" size="20" name="charge_phone" id="charge_phone" placeholder="请输入网格员联系方式" type="text" value="{pigcms{$custom_polygon['charge_phone']}"/>
								<span class="form_tips">必填</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2"><label for="charge_address">住址：</label></label>
								<input validate="required:true" <if condition="$_GET['look'] eq 1">disabled='disabled'</if> class="col-sm-3" size="20" name="charge_address" id="charge_address" placeholder="请输入网格员住址" type="text" value="{pigcms{$custom_polygon['charge_address']}"/>
								<span class="form_tips">必填</span>
							</div>
							<div class="form-group">
								<label class="col-sm-2"><label for="charge_ID">身份证号：</label></label>
								<input validate="required:true" <if condition="$_GET['look'] eq 1">disabled='disabled'</if> class="col-sm-3" size="20" name="charge_ID" id="charge_ID" placeholder="请输入网格员身份证号" type="text" value="{pigcms{$custom_polygon['charge_ID']}"/>
								<span class="form_tips">必填</span>
							</div>
							<if condition="$custom_polygon['charge_avatar'] neq ''">
								<div class="form-group" style="display: flex;">
									<label class="col-sm-2"><label for="layer_room">头像现图：</label></label>
									<img src="{pigcms{$custom_polygon['charge_avatar']}" style="width:50px;height:50px;" class="view_msg"/>
								</div>
							</if>
							<div class="form-group charge_avatar_box" style="display: flex;">
								<label class="col-sm-2"><label for="layer_room">头像：</label></label>
								<if condition="$_GET['look'] neq 1">
									<img src="{pigcms{$static_path}area_street/img/add.png" id="input_file">
								</if>
								<img  src="{pigcms{$custom_polygon['charge_avatar']}" id="input_file_view" <if condition="$custom_polygon['charge_avatar'] eq ''">style="display:none;"</if>>
								<input type="hidden" name="charge_avatar" id="charge_avatar" value="{pigcms{$custom_polygon['charge_avatar']}" style="min-width: 20rem;height: 25px" placeholder="头像" tips="建议尺寸200*200">
							</div>

						</div>
						<if condition="$_GET['look'] neq 1">
							<div class="space"></div>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											提交
										</button>
									</div>
								</div>
							</div>
						</if>
					</form>
				</div>
    </div>


<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">

$(document).ready(function (){

	KindEditor.ready(function(K) {
		var editor = K.editor({
			allowFileManager : true
		});
		K('#input_file').click(function() {
			editor.uploadJson = "{pigcms{:U('ajax_upload_pic')}";
			editor.loadPlugin('image', function() {
				editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#url3').val(),
					clickFn : function(url, title, width, height, border, align) {
						var img = K('#input_file_view');
						img.attr("src",url);
						img.show();
						K("#charge_avatar").val(url);
						editor.hideDialog();
					}
				});
			});
		});
	});

    $('#add_info').submit(function(){
        $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
            console.log('result', result);
            if (result && result.status==1) {
				window.parent.close_all_page(result.data);
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
});


</script>
<include file="Publics:footer"/>
