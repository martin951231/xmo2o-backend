<include file="Public:header"/>
	<form id="myform" method="post" frame="true" refresh="true">
		<input type="hidden" name="change_location" value="0"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th>导入表格</th>
				<td><input type="file" id="translate_file" /></td>
			</tr>
			<tr>
				<td colspan="2">只支持上传扩展名为<span style="color:red;">xls</span>的表格文件,<a href="/static/file/privileged_user.xls">下载示例文件</a>；另外，上传成功后，请等待服务器<span style="color:red;">处理完成后再关闭页面。</span></td>
			</tr>
			<tr id="confirmimport" style="display: none;">
				<td colspan="2" id="tipsbtn">为您检索到 <span id="countdata">0</span> 条数据<button onclick="return ajax_import(0,this);">确定执行导入</button></td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="0" class="frame_form" id="ajaxform" width="100%">
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/fileinput.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/locales/zh.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}bootstrap-fileinput-master/css/fileinput.css" />
	<script type="text/javascript">
		initUpload_config("translate_file");
		var filename = "";


		function ajax_import(start,_this){
			$.post("{pigcms{:U('ajaxPrivileged')}",{'filename':filename,'start':start},function(data){
				if(data.status == 1){
					$("#ajaxform").append('<tr><th>'+data.data.start+'-'+data.data.end+'条</th><td style="color:red">成功处理 '+data.data.complete+' 条</td></tr>');
					if(data.data.yes == 1){
						//结束了
						$("#ajaxform").append('<tr><th>导入结果</th><td>导入完成...</td></tr>');	
						$("#tipsbtn").html("导入完成...");
						return ;						
					}
					else{
						ajax_import(data.data.end+1);
					}
				}
				else{
					$("#ajaxform").append('<tr><th>导入结果</th><td>导入出错...</td></tr>');	
					$("#tipsbtn").html("导入终止...");
				}
			});
			if(typeof _this != 'undefined'){
				$(_this).parent().append("。正在导入中...请勿关闭窗口");
				$(_this).remove();
			}
			return false;
		}
	function initUpload_config(ctrlId){
		$("#"+ctrlId).fileinput({
			uploadUrl : '{pigcms{:U("privileged_upload")}',
			language: 'zh', //设置语言
			showBrowse : true,
			showPreview : false,
			showRemove : false,
			showUpload : true,
			showCancel : false,
			showClose : true,
			showCaption : true,
			dropZoneEnabled : false,
			allowedFileExtensions : ['xls']
		}).on("fileuploaded", function (event, data, previewId, index) {
	        if(data.response.status == 1)
	        {
	            layer.msg(data.files[index].name + data.response.info,{offset:'300px'});
	        	//关闭
	        	if(data.response.data.count > 0){
		        	$("#countdata").text(data.response.data.count);
		        	$("#confirmimport").show();
		        	filename = data.response.data.filename;
		        }
	        }
	        else{
	            layer.msg(data.files[index].name + "导入失败 " + data.response.info, {icon: 5,offset:'300px'});
		        //重置
		        $("#"+ctrlId).fileinput("clear");
		        $("#"+ctrlId).fileinput("reset");
		        $('#'+ctrlId).fileinput('refresh');
		        $('#'+ctrlId).fileinput('enable');
		        $(".close").click();
	        }
	    });
	}
	</script>
<include file="Public:footer"/>