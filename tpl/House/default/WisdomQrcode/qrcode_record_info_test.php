<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
        <script type="text/javascript" src="{pigcms{$static_path}layer/layer.js"></script>
        <script type="text/javascript" src="/static/js/artdialog/jquery.artDialog.js"></script>
		<title>{pigcms{$config.site_name} - 小区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
        <link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
        <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css">
        <script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
        <script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
        <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
        <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	</head>
    <style type="text/css">
    .green{ color:green}
	    .upload_pic_ul .upload_pic_li{width:150px;float:left;list-style:none;}
	    .upload_pic_ul img{width:140px;max-height:200px;border:1px solid #ccc;cursor: pointer;}
        .upload_pic_ul .upload_pic_li a{float: left;}
    </style>
	<body>
			<table>
				<tr>
				</tr>
                <php>$key1=$key2=$key3=0;</php>
				<volist name="record_log['entication_field']" id="vo" >
				<tr>
					<th width="20%">{pigcms{$vo.record_name}:</th>

					<td width="80%">

					</td>

				</tr>
                <php>$key2=0;</php>
				<volist name="vo['record_cate_field_config']" id="voo" >
					<tr>

						<th width="15%">{pigcms{$voo.title}</th>

						<td width="85%">
							<if condition="$voo.type eq 2">
                                <div class="form-group" >
                                    <ul class="upload_pic_ul">
                                    <php>$key3=0;</php>
                                    <volist name="voo['image_value']" id="vooo">
                                    <li class="upload_pic_li">
                                        <img style="width:140px" src="{pigcms{$vooo}" onclick="viewBigImg('{pigcms{$vooo}','')"/>
                                        <a href="#" onclick="deleteImg('{pigcms{$vooo}',this,'{pigcms{$key1}_{pigcms{$key2}_{pigcms{$key3}');return false;">[ 删除 ]</a>
                                    </li>
                                    <php>$key3++;</php>
                                    </volist>
                                    </ul>
                                </div>
                                 <div class="form-group j_selectImage">
                                    <input type="hidden" class="img_key" value="{pigcms{$key1}_{pigcms{$key2}">
									<a href="javascript:void(0)" class="btn btn-sm btn-success jp_select_image">上传图片</a>
								 </div>

								<else/>
								{pigcms{$voo.value}
							</if>
						</td>
					</tr>
                    <php>$key2++;</php>
				</volist>
                    <php>$key1++;</php>
				</volist>

			    <if condition="$robot_record AND $record_log['from_type'] eq 1">
                
                    <tr> 
                        <td width="10%">机器人编码</td>
                        <td width="90%">
                                {pigcms{$record_log.robot_code}
					    </td>
                    </tr>
                     <tr> 
                        <td width="10%">识别类型</td>
                        <td width="90%">
                                {pigcms{$robot_record.title}
					    </td>
                    </tr>
                    <tr> 
                        <td width="10%">业务点名称</td>
                        <td width="90%">
                                {pigcms{$robot_record.point_name}
					    </td>
                    </tr>
                    <tr> 
                        <td width="10%">开始时间</td>
                        <td width="90%">
                                {pigcms{$robot_record.start_time_str}
					    </td>
                    </tr>
                    <tr> 
                        <td width="10%">结束时间</td>
                        <td width="90%">
                                {pigcms{$robot_record.end_time_str}
					    </td>
                    </tr>
                     <tr> 
                        <td width="10%">上报图片</td>
                        <td width="90%">
                            <div>
                                <volist name="robot_record['image_urls']" id="imgV">
                                    <img src="{pigcms{$imgV}" style="width: 140px;margin-right: 10px;cursor: pointer;" class="img_node" onclick="viewBigImg('{pigcms{$imgV}','')">
                                </volist>
                            </div>
					    </td>
                    </tr>
                     <tr> 
                        <td width="10%">上报视频</td>
                        <td width="90%">
                            <div>
                                <volist name="robot_record['video_urls']" id="vsrc">
                                    <video src="{pigcms{$vsrc}" autoplay controls class="video_node" style="width: 400px;margin-right: 10px;"></video>
                                </volist>
                            </div>
					    </td>
                    </tr>
                      <tr> 
                        <td width="10%">备注</td>
                        <td width="90%">
                                {pigcms{$robot_record.remark}
					    </td>
                    </tr>
                </if>
			</table>
            <img alt="" style="display:none" id="displayimg" src="" />
	</body>
<script>
    function viewBigImg(url,name) {
                $("#displayimg").attr("src", url);
                var height = $("#displayimg").height();
                var width = $("#displayimg").width();
                layer.open({
                    type: 1,
                    title: false,
                    closeBtn: 0,
                    shadeClose: true,
                    area: ['1100px','700px'], //宽高
                    content: "<div style='text-align: center;vertical-align: middle;height: 100%;'><img alt=" + name + " title=" + name + " src=" + url + " /></div>"
                });
            }

        KindEditor.ready(function(K){
		var kind_editor = K.editor({
			allowFileManager : true
		});
		K('.jp_select_image').click(function(){
            var thsiTd=$(this).parent('.j_selectImage').parent('td');
			if(thsiTd.find('.upload_pic_li').size() >= 5){
				alert('最多上传5个图片！');
				return false;
			}
            var img_key=$(this).siblings('.img_key').val();
			kind_editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
			kind_editor.loadPlugin('image', function(){
				kind_editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#course_pic').val(),
					clickFn : function(url, title, width, height, border, align) {
                        $.post("{pigcms{:U('ajax_add_record_pic')}",{record_log_id:{pigcms{$record_log['id']},'surl':url,img_key:img_key},function(ret){
                                if(ret.err_code<1){
                                    thsiTd.find('.upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'" style="width: 140px;" onclick="viewBigImg(\''+url+'\',\'\')" /><a href="#" onclick="deleteImg(\''+url+'\',this,'+img_key+');return false;">[ 删除 ]</a></li>');
                                    if(thsiTd.find('.upload_pic_li').size() >= 5){
                                        thsiTd.find('.j_selectImage').hide();
                                    }
                                    kind_editor.hideDialog();
                                }else{
                                    kind_editor.hideDialog();
                                    layer.alert(ret.msg);
                                }
                            },'json');
						
					}
				});
			});
		});
	});

	function deleteImg(path,obj,img_key=''){
		$.post("{pigcms{:U('ajax_del_record_pic')}",{record_log_id:{pigcms{$record_log['id']},'surl':path,img_key:img_key},function(ret){
            if(ret.err_code<1){
                $(obj).closest('.upload_pic_li').remove();
                $(obj).closest('.upload_pic_ul');
                 if($(obj).closest('.upload_pic_ul').find('.upload_pic_li').size() <5){ 
                    $(obj).closest('td').show();
                }
            }else{
                layer.alert(ret.msg);
            }
        },'json');

	}
</script>
</html>