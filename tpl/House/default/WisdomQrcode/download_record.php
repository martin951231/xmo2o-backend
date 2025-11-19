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
<!--					<form id="add_info" class="form-horizontal" method="post" action="__SELF__">-->
						<div class="tab-content">

							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">选择二维码类别：</label></label>
                                <select name="cate_id" class="col-sm-1" id="cate_id">
									<volist name="wisdom_qrcode_cate" id="vo">
                                      <option value="{pigcms{$vo.id}">{pigcms{$vo.cate_name}</option>
									</volist>
								</select>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="property_starttime">选择记录时间:</label></label>
								<input type="text" name="start_time" class="col-sm-1" value="" id="start_time" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})" placeholder="开始时间"/>
								<input type="text" name="end_time" class="col-sm-1" value="" id="end_time"  onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})" placeholder="结束时间"/>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9" style="">
									<button class="btn btn-info" type="button" id="sub">
										<i class="ace-icon fa fa-check bigger-110"></i>
										选择
									</button>
								</div>
							</div>
						</div>
<!--					</form>-->
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
<script type="text/javascript">


	$("#sub").click(function(){

		var cate_id = $("#cate_id").val();
		var start_time = $("#start_time").val();
		var end_time = $("#end_time").val();
		console.log(cate_id)
		console.log(start_time)
		console.log(end_time)


		$.get("{pigcms{:U('qrcode_record_index')}",{cate_id:cate_id,start_time:start_time,end_time:end_time,type:1},function(res){
			var $a = $("<a>");
			$a.attr("href", res.file);
			$a.attr("download", res.filename);
			$("body").append($a);
			$a[0].click();
			$a.remove();
		},'json');
	})


</script>
<include file="Publics:footer"/>
