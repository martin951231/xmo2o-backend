<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear"></i>
				<a href="{pigcms{:U('Repair/index')}">报修管理</a>
			</li>
			<li class="active">添加报修</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post">
						<div class="tab-content">

							<div class="form-group">
                                <input type="hidden" id="cate1_value">
								<label class="col-sm-1"><label for="name">报修类型<span class="red">*&nbsp;&nbsp;必填</span></label></label>
								<select name="cate1" id="cate1" style="margin-right:10px;height:35px; width:120px" class="col-sm-2">
                                    <option value="0" <if condition="$_GET['cate1'] eq '0'">selected="selected"</if>>请选择</option>
                                    <volist name='classify_list' id='v'>
                                        <option data-id="{pigcms{$v.type}" value="{pigcms{$v.id}" <if condition="$_GET['cate1'] eq $v['id']">selected="selected"</if> >{pigcms{$v.cate_name}</option>
                                    </volist>
                                </select>
                            </div>

                                                                    <div class="form-group">
                                                                        <label class="col-sm-1"><label for="name">报修分类<span class="red">*&nbsp;&nbsp;必填</span></label></label>
                                                                        <select name="cate" id="cate" style="margin-right:10px;height:35px; width:120px" class="col-sm-2">
                                                                            <option value="0" <if condition="$_GET['cate'] eq '0'">selected="selected"</if>>请选择</option>

                                                                        </select>
                                                                        <select name="subcate" id="subcate" style="height:35px" class="col-sm-2">
                                                                            <option value="0" <if condition="$_GET['subcate'] eq '0'">selected="selected"</if>>请选择</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="col-sm-1"><label for="phone">报修人联系方式</label></label>
                                                                        <input class="col-sm-2" size="20" name="phone" id="phone" type="text" value=""/>
                                                                    </div>
                                                                    <div class="form-group" id="unit">
                                                                        <label class="col-sm-1"><label for="express_type">报修人姓名</label></label>

                                                                            <input class="col-sm-2" size="20" name="user_name" id="user_name" type="text" value=""/>
                                                                        <input class="col-sm-2" size="20" name="user_bind" id="user_bind" type="hidden" value=""/>
                                                                        <input class="col-sm-2" size="20" name="user_bind_id" id="user_bind_id" type="hidden" value=""/>
                                                                    </div>
                                                                    <div class="form-group" id="single_floor_vacancy">
                                                                        <label class="col-sm-1"><label for="floor_name">联系地址</label></label>

                                                                        <select class="col-sm-1"  name="single_id" id="single">
                                                                            <option value="">请选择楼栋</option>
                                        <!--									<volist name='single_list' id='single_data'>-->
<!--										<option value="{pigcms{$single_data.id}">{pigcms{$single_data.single_name}</option>-->
<!--									</volist>-->
								</select>

								<select class="col-sm-1"  name="floor_id" id="floor">
									<option value="">请选择单元</option>
								</select>
                                <select class="col-sm-1"  name="layer_id" id="layer">
                                    <option value="">请选择楼层</option>
                                </select>

								<select class="col-sm-1"  name="vacancy_id" id="vacancy">
									<option value="">请选择房间</option>
								</select>
								<input class="col-sm-2" size="20" name="bind_uid" id="bind_uid" type="hidden" value=""/>
							</div>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="id_card">报修内容<span class="red">*&nbsp;&nbsp;必填</span></label></label>
                                <textarea class="col-sm-2" size="20" style="width: 300px;height: 100px;" name="content" id="content" type="text"></textarea>

                            </div>


								<div class="form-group">
									<label class="col-sm-1">上传图片</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">上传图片</a>
									图片宽度建议为：640px，高度建议为：238px。最多上传9张
								</div>
								<div class="form-group">
									<label class="col-sm-1">图片预览</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul"></ul>
									</div>
								</div>
                            <div class="form-group">
								<label class="col-sm-1">分发给工作人员<span class="red">*&nbsp;&nbsp;必填</span></label>
								<select id="worker_id" name="worker_id">
									<option value="">请选择</option>
									<volist name="workers" id="worker">
										<option value="{pigcms{$worker['wid']}">{pigcms{$worker['name']}</option>
									</volist>
								</select>
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

<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>

<script type="text/javascript">
	$(function(){

		$('#phone').on('blur' , function(){
			if($(this).val().length > 6){
				$.post("{pigcms{:U('ajax_get_user_bind')}", {phone: $(this).val()}, function(data) {
                    console.log('12123231',data);
					if(data.status==1){
						$('#user_name').val(data.user_bind.name);
                        $('#user_bind').val(data.user_bind.uid);
                        $('#user_bind_id').val(data.user_bind.pigcms_id);
					}else{

						$('#user_name').val();
					}
				},"JSON");



				//控制楼栋
				$.post("{pigcms{:U('get_single')}", {phone: $(this).val()}, function(data) {

					var html = '<option value="0">请选择</option>';
					if(data['status'] == 1){
						var cat_list = data.single_list;
						for(var i in cat_list){
							if (cat_list[i]['id'] == '{pigcms{$_GET['subcate']}' ) {
								html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['single_name'] + '</option>';
							}else{
								html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['single_name'] + '</option>';
							}
						}
						$('#single').html(html);
					} else {
						var single_html = '<option value="0">该手机号暂未绑定楼栋</option>';
						$("#single").html(single_html);
						var floor_html = '<option value="0">该手机号暂未绑定单元</option>';
						$("#floor").html(floor_html);
						var vacancy_html = '<option value="0">该手机号暂未绑定房间</option>';
						$("#vacancy").html(vacancy_html);
						var layer_html = '<option value="0">该手机号暂未楼层房间</option>';
						$("#layer").html(layer_html);

						$('#bind_uid').val(0);
					}
				},"JSON");

			}


		});

        $('#find_value').keyup(function () {
            var find_value = $(this).val();
            var ajax_user_list_url = "{pigcms{:U('ajax_user_list')}";
            console.log(find_value)
            if (find_value) {
                $.post(ajax_user_list_url,{find_value: find_value},function (data) {
                    var shtml = '';
                    if (data.status) {
                        $("#showIndex").show().find(".nomore").hide().siblings().show();
                        $('.user_list').show();
                        for (var i in data['user_list']) {
                            shtml += '<li><a href="javascript:;" onclick="getInfoId('+ data['user_list'][i]['pigcms_id']+','+data['user_list'][i]['uid'] +','+data['user_list'][i]['phone'] +',\''+data['user_list'][i]['name']+'\')" title="手机号：'+data['user_list'][i]['phone']+'&nbsp;&nbsp;|&nbsp;&nbsp;姓名：' + data['user_list'][i]['name'] + '&nbsp;&nbsp;|&nbsp;&nbsp;地址：' + data['user_list'][i]['address']+ '">'+data['user_list'][i]['phone']+'&nbsp;&nbsp;|&nbsp;&nbsp;' + data['user_list'][i]['name'] +'('+ data['user_list'][i]['type_txt'] + ')&nbsp;&nbsp;|&nbsp;&nbsp;' + data['user_list'][i]['address'] + '</a></li>';
                        }
                        $("#wuye").html(shtml)
                        $('.user_list_content').html(shtml);
                    } else {
                        $("#showIndex").show().find("#wuye").html('').closest('.more').hide().siblings().show();
                        shtml += '暂无相关数据';

                        $('.user_list_content').html(shtml);
                    }
                },'json')
            }
        });
	});

	KindEditor.ready(function(K){
		var kind_editor = K.editor({
			allowFileManager : true
		});
		K('#J_selectImage').click(function(){
			if($('.upload_pic_li').size() >= 9){
				alert('最多上传9个图片！');
				return false;
			}

			kind_editor.uploadJson = "{pigcms{:U('ajax_upload_pic')}";
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
</script>
<script type="text/javascript">

    $(document).ready(function() {
        var cat_id = $("#cate1_value").val();
        if(cat_id == 1){
            $("#single_floor_vacancy").hide();
            $("#phone").val('');
            $("#user_name").val('');
            $("#user_bind").val('');

        }else if(cat_id == 2){
            $("#single_floor_vacancy").show();
        }
        if(!cat_id){
            return;
        }
        get_son_category1(cat_id);
    });
	// var cat_id = $('#cate').val();
	// get_son_category(cat_id);
    $('#cate').change();
	$('#cate').change(function(){

		var cat_id = $(this).val();
		console.log('个人报修分类', cat_id);
		if(cat_id == 1){
           $("#single_floor_vacancy").hide();
		}else if(cat_id == 2){
			$("#single_floor_vacancy").show();
		}
		if(!cat_id){
			return;
		}
		get_son_category(cat_id);

	});

	function get_son_category(cat_id){
		var url = "{pigcms{:U(ajax_get_category)}";
		$.post(url,{'cat_id':cat_id,'source':'add'},function(result){
			var html = '<option value="0">请选择</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					if (cat_list[i]['id'] == '{pigcms{$_GET['subcate']}' ) {
						html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['cate_name'] + '</option>';
					}else{
						html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['cate_name'] + '</option>';
					}
				}
				$('#subcate').html(html);
			} else {
				$("#subcate").html(html);
			}
		},'json');
	}

	$('#cate1').change(function(){

		var cat_id = $(this).val(),type=$("#cate1 option:selected").attr("data-id");
		$("#cate1_value").val(cat_id);
		$("#subcate").html('<option value="0">请选择</option>');
		if(type == 1){
			$("#single_floor_vacancy").hide();
			$("#phone").val('');
			$("#user_name").val('');
			$("#user_bind").val('');

		}
		// else if(type == 2){
		// 	$("#single_floor_vacancy").show();
		// }
		if(!cat_id){
			return;
		}
		get_son_category1(cat_id);

	});

	function get_son_category1(cat_id){
		var url = "{pigcms{:U(ajax_get_category1)}";
		$.post(url,{'cat_id':cat_id,'source':'add'},function(result){
			var html = '<option value="0">请选择</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					if (cat_list[i]['id'] == '{pigcms{$_GET['subcate']}' ) {
						html +='<option value="'+ cat_list[i]['id'] +'" selected=selected>' + cat_list[i]['cate_name'] + '</option>';
					}else{
						html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['cate_name'] + '</option>';
					}
				}
				$('#cate').html(html);
			} else {
				$("#cate").html(html);
			}
		},'json');
	}

	var single = $('#single').val();
	get_son_floor(single);

	$('#single').change(function(){
		var cat_id = $('#single').val();
		console.log(cat_id)
		if(!cat_id){
			return;
		}
		get_son_floor(cat_id);

	});
	function get_son_floor(cat_id){
		var url = "{pigcms{:U('ajax_get_floor')}";
		var phone = $('#phone').val()
		$.post(url,{'cat_id':cat_id,phone: phone},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择单元</option>';
			var htmls = '<option value="0">请选择房间</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['floor_id'] +'">' + cat_list[i]['floor_name'] + '</option>';
				}
				$('#floor').html(html);

				$('#vacancy').html(htmls);
			} else {

				$("#floor").html(html);
				$('#vacancy').html(htmls);
			}
		},'json');
	}
	$('#floor').change(function(){

		var cat_id = $('#floor').val();

		console.log(cat_id)
		if(!cat_id){
			return;
		}
//		get_vacancy(cat_id);
		get_new_layer(cat_id);

	});

	function get_vacancy(cat_id){
		var url = "{pigcms{:U('ajax_get_vacancy')}";
		var phone = $('#phone').val()
		$.post(url,{'cat_id':cat_id,phone: phone},function(result){
			console.log("result")
			console.log(result)
			var html = '<option value="0">请选择房间</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['pigcms_id'] +'">' + cat_list[i]['room'] + '</option>';
				}
				$('#vacancy').html(html);
			} else {
				$("#vacancy").html(html);
			}
		},'json');
	}

	$('#vacancy').change(function(){

		var vacancy_id = $('#vacancy').val();
		var phone = $('#phone').val();
		console.log(vacancy_id);
		if(vacancy_id){
			$.post("{pigcms{:U('ajax_get_user_vacancy')}", {vacancy_id: vacancy_id,phone:phone}, function(data) {
                console.log("============");
                console.log(data);
				if(data.status==1){

					//$('#user_bind').val(data.user_bind.uid);
				}else{

					//$('#user_name').val();
				}
			},"JSON");
		}

	});


	$('#layer').change(function(){

		var cat_id = $('#layer').val();

		console.log(cat_id)
		if(!cat_id){
			return;
		}
		get_vacancy(cat_id);

	});

	function get_new_layer(cat_id){
        var phone = $('#phone').val();
		var url = "{pigcms{:U('ajax_get_layer')}";
		$.post(url,{'cat_id':cat_id,'phone':phone},function(result){
			console.log("result")
			console.log(result)
			var shtml = '<option value="0">请选择房间</option>';
			var html = '<option value="0">请选择楼层</option>';
			if(result['status'] == 1){
				var cat_list = result.cat_list;
				for(var i in cat_list){
					html +='<option value="'+ cat_list[i]['id'] +'">' + cat_list[i]['layer_name'] + '</option>';
				}
				$('#layer').html(html);
                $('#vacancy').html(shtml);
                $('#vacancy').html(html);
			} else {
				$("#layer").html(html);
				$('#vacancy').html(shtml);
			}
		},'json');
	}

</script>

<include file="Public:footer"/>