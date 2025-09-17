<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
					<form id="add_info" class="form-horizontal" method="post" action="__SELF__">
						<div class="tab-content">
							<input type="hidden" value="{pigcms{$express['id']}" name="id">
							<div class="form-group">
								<label class="col-sm-2">类型:</label>
								<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$express['type'] eq 0">checked="checked"</if>  class="ace" value="0" name="type"><span style="z-index: 1" class="lbl">小号</span></label>
								<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$express['type'] eq 1">checked="checked"</if> class="ace" value="1" name="type"><span style="z-index: 1" class="lbl">中号</span></label>
								<label style="padding-left:0px;"><input type="radio" <if condition="$express['type'] eq 2">checked="checked"</if> class="ace" value="2" name="type"><span style="z-index: 1" class="lbl">大号</span></label>
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





$('#add_info').submit(function(){
    $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
        console.log('result', result);

        if (result && result.status==1) {
            layer.open({
                content: '添加成功',
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
