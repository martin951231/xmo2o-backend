<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear"></i>
				<a href="{pigcms{:U('Repair/village_suggest')}">投诉建议管理</a>
			</li>
			<li class="active">添加投诉建议</li>
		</ul>
	</div>
    <style>
        #wuye li{
            height:40px;line-height:40px;cursor:pointer;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden
        }
        #showIndex .more p{
            height:40px;line-height:40px;font-size:12px;padding-left:10px
        }
        #showIndex{
            display:none;
            position:absolute;
            margin-top: 40px;
            margin-left: 132px;
            background:#ddd;
            z-index: 99;
        }
    </style>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post">
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="name">投诉建议分类</label></label>
								<select name="cate" id="cate" style="margin-right:10px;height:35px; width:120px" class="col-sm-1">
									<option value="0" <if condition="$_GET['cate'] eq '0'">selected="selected"</if>>请选择</option>
									<volist name="cate_list" id="vo">
										<option value="{pigcms{$key}" <if condition="$_GET['cate'] eq $key">selected="selected"</if>>{pigcms{$vo}</option>
									</volist>
								</select>
								<select name="subcate" id="subcate" style="height:35px" class="col-sm-1">
									<option value="0" <if condition="$_GET['subcate'] eq '0'">selected="selected"</if>>请选择</option>
								</select>
							</div>


							<div class="form-group">
								<label class="col-sm-1"><label for="phone">联系方式</label></label>
<!--								<input class="col-sm-2" size="20" name="phone" id="phone" type="text" value=""/>-->
                                <input class="col-sm-2" name="phone" id="find_value" type="text" style="margin-right:10px;font-size:14px;width: 330px;" autocomplete="off" placeholder="请输入联系方式"/>
                                <div id="showIndex">
                                    <div class="more">
                                        <ul style="padding-left:20px" id="wuye">
                                        </ul>
                                    </div>
                                </div>
							</div>


							<div class="form-group" id="unit">
								<label class="col-sm-1"><label for="express_type">姓名</label></label>

								<input class="col-sm-2" size="20" name="user_name" id="user_name" type="text" value=""/>
								<input class="col-sm-2" size="20" name="user_bind" id="user_bind" type="hidden" value=""/>
                                <input class="col-sm-2" size="20" name="user_bind_id" id="user_bind_id" type="hidden" value=""/>

							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="id_card">投诉建议内容</label></label>
								<textarea class="col-sm-2" size="20" style="width: 300px;height: 100px;" name="content" id="content" type="text"></textarea>

							</div>


							<div class="form-group">
								<label class="col-sm-1">上传图片</label>
								<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">上传图片</a>
								图片宽度建议为：640px，高度建议为：238px。最多上传9张
							</div>
							<div class="form-group" id="upload_show">
								<label class="col-sm-1">图片预览</label>
								<div id="upload_pic_box">
									<ul id="upload_pic_ul"></ul>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-1">分发给工作人员</label>
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

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>

<script type="text/javascript">
    $(document).bind("click",function(e){
        var target=$(e.target);
        console.log(target)
        if(target.closest("#find_value").length==0){
            $("#showIndex").hide().find(".nomore").hide().siblings().hide().find("#wuye").html("")
        }

    })
	$(function(){
		if($('.upload_pic_li').size() == 0){
		}else{
			$("#upload_show").show();
		}
		$('#phone').on('blur' , function(){
			if($(this).val().length > 6){
				$.post("{pigcms{:U('ajax_get_user_bind')}", {phone: $(this).val()}, function(data) {

					if(data.status==1){
						$('#user_name').val(data.user_bind.name);
						$('#user_bind').val(data.user_bind.uid);
					}else{

						$('#user_name').val();
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
                        $('.more').show();
                        for (var i in data['user_list']) {
                            shtml += '<li><a href="javascript:;" onclick="getInfoId('+ data['user_list'][i]['pigcms_id']+','+data['user_list'][i]['uid'] +','+data['user_list'][i]['phone'] +',\''+data['user_list'][i]['name']+'\')" title="手机号：'+data['user_list'][i]['phone']+'&nbsp;&nbsp;|&nbsp;&nbsp;姓名：' + data['user_list'][i]['name'] + '&nbsp;&nbsp;|&nbsp;&nbsp;地址：' + data['user_list'][i]['address']+ '">'+data['user_list'][i]['phone']+'&nbsp;&nbsp;|&nbsp;&nbsp;' + data['user_list'][i]['name'] + '&nbsp;&nbsp;|&nbsp;&nbsp;' + data['user_list'][i]['address'] + '</a></li>';
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
    function getInfoId(id,uid,phone,name){
        $('#user_name').val(name);
        $('#user_bind').val(uid);
        $('#user_bind_id').val(id);
        $('#find_value').val(phone);
    }
	KindEditor.ready(function(K){
		var kind_editor = K.editor({
			allowFileManager : true
		});
		K('#J_selectImage').click(function(){
			if($('.upload_pic_li').size() >= 9){
				alert('最多上传9个图片！');
				return false;
			}
			if($('.upload_pic_li').size() > 0){
				$("#upload_show").show();
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


	var cat_id = $('#cate').val();
	get_son_category(cat_id);

	$('#cate').change(function(){

		var cat_id = $(this).val();
		if(!cat_id){
			return;
		}
		get_son_category(cat_id);

	});

	function get_son_category(cat_id){
		var url = "{pigcms{:U(ajax_get_category)}";
		$.post(url,{'cat_id':cat_id},function(result){
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









</script>

<include file="Public:footer"/>