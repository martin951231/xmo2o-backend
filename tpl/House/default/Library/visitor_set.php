<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="x-page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="">
						<div class="tab-content">
							<div id="bind_protocol" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1" style=""><label for="title" style="font-weight: 900">字段名称</label></label>
                                    <label class="col-sm-1" style=""><label for="title" style="font-weight: 900">是否必填</label></label>
                                </div>

								<div class="form-group">
									<label class="col-sm-1">访客姓名</label>
                                    <label><input  value="1" name="name" type="radio" <if condition="$visitor_set['name'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
                                    &nbsp;&nbsp;&nbsp;
									<label><input  value="0" name="name" type="radio" <if condition="$visitor_set['name'] eq 0"> checked="checked" </if> />&nbsp;&nbsp;否</label>
								</div>
								<div class="form-group">
									<label class="col-sm-1">身份证号码</label>
                                    <label><input  value="1" name="id_card" type="radio" <if condition="$visitor_set['id_card'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
                                    &nbsp;&nbsp;&nbsp;
									<label><input  value="0" name="id_card" type="radio" <if condition="$visitor_set['id_card'] eq 0"> checked="checked" </if> />&nbsp;&nbsp;否</label>
								</div>
								<div class="space"></div>
								<div class="clearfix">
									<div class="col-md-offset-3">
										<button class="btn btn-info" type="button" id="aaa">
											<i class="ace-icon fa fa-check bigger-110"></i>
											保存
										</button>
									</div>
								</div>
							</div>
						</div>
					</form>
			</div>
		</div>
	 </div>
   </div>
</div>
<style type="text/css">
    #bind_protocol .col-sm-1{width:110px;}
    .ke-dialog-body .ke-input-text{height: 30px;}
    .no-skin .ke-dialog{
        left: 30% !important;top:18% !important;
    }
</style>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
<script type="text/javascript">
    $(function(){
        var options = {
            type: 'POST',
            url: "{pigcms{:U('Library/visitor_set')}",
            success:showResponse,
            dataType: 'json',
            error : function(xhr, status, err) {
                alert("操作失败");
            }
        };
        $("#edit_form").submit(function(){
            $(this).ajaxSubmit(options);
            return false;  //防止表单自动提交 
        });
    });
    $('#aaa').click(function(){
        $("#edit_form").submit();
    });
    function showResponse(responseText, statusText, xhr, $form){
        if(responseText.status==1){
            layer.alert(responseText.message,function(){
                var index = parent.layer.getFrameIndex(window.name); // 获取窗口索引
                parent.layer.close(index);
            });
        }else{
            layer.alert(responseText.message);
        }
    }
KindEditor.ready(function(K){
			kind_editor = K.create("#pcontent",{
				width:'85%',
				height:'400px',
				resizeType : 1,
				pasteType : 1,
				allowPreviewEmoticons:false,
				allowImageUpload : true,
				filterMode: true,
				items : [
					'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
					'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
					'insertunorderedlist', '|', 'emoticons', 'image', 'wordpaste'
				],
				emoticonsPath : './static/emoticons/',
				uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"
			});
		});
</script>

<include file="Public:footer"/>
