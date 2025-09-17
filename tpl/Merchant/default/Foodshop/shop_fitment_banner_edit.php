<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("轮播图")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
			table th{padding:17px 10px 19px;}
			table td{padding:17px 10px 19px 15px;}
			#good_list li{list-style: none;}
			#good_list li .del{color:blue;float:right;cursor:pointer;-webkit-user-select: none;-webkit-touch-callout: none;}
			#good_list li:hover{color:red;}
			#good_list li .del:hover{text-decoration:underline;color:red;}
			.btn-success, .btn-success:focus{
				position: relative;
				width: 78px;
				height: 34px;
				background-color: #87b87f!important;
				border-color: #87b87f;
				text-align: center;
				line-height: 34px;
				color: white;
				cursor: pointer;
			}
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
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
	</head>
	<body>
		<form id="myform" method="post" action="{pigcms{:U('shop_fitment_banner_save')}" autocomplete="off">
			<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
            <input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
			<input type="hidden" name="pic" id="pic" value="{pigcms{$subject_info['banner'][$_GET['id']]['pic']}"/>
			<table>
				<tr>
					<th width="20%">{pigcms{:L_("banner图")}</th>
					<td width="80%" colspan="3">
						<span id="picStatus" style="color:green;margin-right:10px;display:none;"></span>
						<div style="display:inline-block;" id="J_selectImage">
							<div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;    background-color:#87b87f!important;border-color:#87b87f;">{pigcms{:L_("上传图片")}</div>
						</div>
						<span style="margin-left:20px;">{pigcms{:L_("图片建议尺寸")}700*180</span>
					</td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("名称")}</th>
					<td width="80%" colspan="3"><input type="text" class="input" name="name" id="name" value="{pigcms{$subject_info['banner'][$_GET['id']]['name']}" style="width:200px;"/>&nbsp;&nbsp;&nbsp;<span>{pigcms{:L_("20个字以内")}</span></td>
				</tr>
				<tr>
					<th width="20%">{pigcms{:L_("链接")}</th>
					<td width="80%" colspan="3">
						<div>
                            <input type="text" name="url" id="url" value="{pigcms{$subject_info['banner'][$_GET['id']]['url']}">
                            <button type="button" id="url" onclick="addLink('url',0)" style="margin-left:0px;" data-toggle="modal">{pigcms{:L_("功能库")}</button>
						</div>
					</td>
				</tr>
				<tr>
				<th width="20%">{pigcms{:L_("是否显示")}</th>
				<td width="80%" colspan="3">
					<select name="status" id="status" style="width:150px;">
						<option value="1" <if condition="$subject_info['banner'][$_GET['id']]['status'] eq '1'">selected="selected"</if>>{pigcms{:L_("显示")}</option>
						<option value="0" <if condition="$subject_info['banner'][$_GET['id']]['status'] eq '0'">selected="selected"</if>>{pigcms{:L_("不显示")}</option>
					</select>
				</td>
			</tr>
			</table>
			<div class="btn">
				<button type="submit" id="submit" style="margin-bottom:30px;">{pigcms{:L_("保存")}</button>
			</div>
		</form>
		<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
        <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
        <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
        <script type="text/javascript">
            function addLink(domid,iskeyword){
                art.dialog.data('domid', domid);
                art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'{pigcms{:L_("插入链接或关键词")}',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
            }
            
            function build_image_save(imagePath) {
                $('#pic').val(imagePath);
                $('.fitment_subject img', parent.document).attr('src', imagePath + '?t=' + new Date().getTime());
                $('#picStatus').html('{pigcms{:L_("已上传")}').show();
            }
            if ($('#pic').val() != '') {
                $('#picStatus').html('{pigcms{:L_("已上传")}').show();
            }

            if ($('#good_list li').size() > 0) {
                $('#good_list').css('margin-bottom', '20px');
            }

            parent.subject_win_name = window.name;

     

            function build_good_save(goodData) {
                $('#good_list').css('margin-bottom', '20px');
                console.log(goodData);
                for (var i in goodData) {
                    $('#good_list').append('<li><input class="good_id" type="hidden" name="good_id[]" value="' + goodData[i].id + '"/><span>' + goodData[i].title + '</span><div class="del">[{pigcms{:L_("删除")}]</div></li>');
                }
            }

            $(function () {
                var uploader = WebUploader.create({
                    auto: true,
                    swf: '{pigcms{$static_public}js/Uploader.swf',
                    server: "{pigcms{:U('Foodshop/ajax_store_pic')}",
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
                    upload_layer_tip = layer.load(1, {
                        shade: [0.1, '#000']
                    })
                });

                uploader.on('uploadSuccess', function (file, response) {
                    console.log(response);
                    layer.close(upload_layer_tip);
                    if (response.error == 0) {
                        build_image_save(response.url);
                    } else {
                        alert(response.message);
                    }
                });

                uploader.on('uploadError', function (file, reason) {
                    $('.loading' + file.id).remove();
                    alert('{pigcms{:L_("上传失败！请重试。")}');
                });
 
                $('#myform').submit(function () {
                    if ($('#pic').val() == '') {
                        parent.layer.msg('{pigcms{:L_("请先上传图片")}');
                        return false;
                    }
                    if ($('#name').val() == '') {
                        parent.layer.msg('{pigcms{:L_("请填写名称")}');
                        return false;
                    }
                    // if ($('#url').val() == '') {
                    //     parent.layer.msg('{pigcms{:L_("请选择连接")}');
                    //     return false;
                    // }

                    $('#submit').prop('disabled', true).html('{pigcms{:L_("保存中")}...');
                    $.post($('#myform').attr('action'), $('#myform').serialize(), function (result) {
                        if (result.status == 1) {
                            layer.alert(result.info,function(){ 
                                art.dialog.close();
                                window.location.href="{pigcms{:U('shop_fitment_banner',array('store_id'=>$_GET['store_id']))}"
                            });
                        } else {
                            layer.alert(result.info);
                            $('#submit').prop('disabled', false).html('{pigcms{:L_("保存")}');
                        }
                    });

                    return false;
                });
            });
        </script>
	</body>
</html>