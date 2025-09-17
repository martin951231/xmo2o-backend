<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
	.page-content .btn-info { border: none;}
	.page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white; width: 755px;">
	<div class="row" style="background-color: white;">
		<div class="col-xs-12">
			<form id="add_info" class="form-horizontal" method="post" action="__SELF__">
				<div class="tab-content">
                    <input type="hidden" value="{pigcms{$detail['id']}" name="id">
                    <div class="form-group">
                        <label class="col-sm-1"><label for="park_type">车牌类型：</label></label>
                        <select name="park_type" style="margin-left: -2px;width: 15%">
                            <option value='0'>请选择</option>
                            <option value='1' <if condition='$detail["park_type"] eq 1'>selected="selected"</if>>军车</option>
                            <option value='2' <if condition='$detail["park_type"] eq 2'>selected="selected"</if>>警车</option>
                            <option value='3' <if condition='$detail["park_type"] eq 3'>selected="selected"</if>>消防车</option>

                        </select>
                    </div>
					<div class="form-group">
						<label class="col-sm-1"><label for="">车牌开头包含：</label></label>
						<input class="col-sm-2" size="20" name="first_name" id="first_name" placeholder="请输入" type="text" value="{pigcms{$detail['first_name']}"/>

					</div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="">车牌结尾包含：</label></label>
                        <input class="col-sm-2" size="20" name="last_name" id="last_name" placeholder="请输入" type="text" value="{pigcms{$detail['last_name']}"/>

                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="">完成车牌：</label></label>
                        <input class="col-sm-2" size="20" name="free_park" id="free_park" placeholder="请输入" type="text" value="{pigcms{$detail['free_park']}"/>
                    </div>
				</div>
				<div class="space"></div>
				<div class="clearfix form-actions">
					<div class="col-md-offset-3 col-md-9">
						<button class="btn btn-info" type="submit">
							<i class="ace-icon fa fa-check bigger-110"></i>
							编辑
						</button>
					</div>
				</div>
		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>
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




	$('#add_info').submit(function(){
		$.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
			console.log('result', result);

			if (result && result.status==1) {
				layer.open({
					content: '编辑成功',
					shadeClose: false,
					closeBtn: 0
					,btn: ['确定']
					,yes:function(index){
						window.parent.close_all_page(result,'free');
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
				layer.open({content: '编辑失败'});
			}
		});
		return false;
	});


</script>
<include file="Publics:footer"/>
