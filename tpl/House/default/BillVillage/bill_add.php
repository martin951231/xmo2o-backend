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

							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">费用名称:</label></label>
								<input class="col-sm-2" size="20" name="title" id="title" placeholder="费用名称" type="text" value=""/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
							</div>
							<div class="form-group" style="">
								<label class="col-sm-1">类型:</label>

								<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="checked" class="ace" value="2" name="bill_type"><span style="z-index: 1" class="lbl">收入</span></label>
								<label style="padding-left:0px;"><input type="radio" checked="checked" class="ace" value="1" name="bill_type"><span style="z-index: 1" class="lbl">支出</span></label>
							</div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="budgetCategoryId">所属分类：</label></label>
                                <select class="col-sm-2" name="budgetCategoryId" id="budgetCategoryId">
                                    <option value="0">请选择分类</option>
                                    <foreach name="categoryList" item="vo">
                                        <option value="{pigcms{$vo.id}">
                                        {pigcms{$vo.category_name}
                                        </option>
                                    </foreach>
                                </select>
                            </div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">单价:</label></label>
								<input class="col-sm-2" size="20" name="unit_price" id="unit_price" placeholder="" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">数量:</label></label>
								<input class="col-sm-2" size="20" name="num" id="num" placeholder="" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">金额:</label></label>
								<input class="col-sm-2" size="20" name="price" id="price" placeholder="" type="text" value=""/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">收款方:</label></label>
								<input class="col-sm-2" size="20" name="payee_name" id="payee_name" placeholder="收款方" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">付款方:</label></label>
								<input class="col-sm-2" size="20" name="payer_name" id="payer_name" placeholder="付款方" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="property_starttime">付款时间:</label></label>
								<input type="text" name="payment_time" class="col-sm-2" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})" placeholder="付款时间"/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">备注:</label></label>
								<input class="col-sm-2" size="20" name="desc" id="desc" placeholder="" type="text" value=""/>

							</div>
							<div class="form-group">
								<label class="col-sm-1">上传图片:</label>
								<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">上传图片</a>
								最多上传5张
							</div>
							<div class="form-group">
								<label class="col-sm-1">图片预览:</label>
								<div id="upload_pic_box">
									<ul id="upload_pic_ul"></ul>
								</div>
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
<script type="text/javascript">

	KindEditor.ready(function(K){
		console.log("{pigcms{$config.site_url}");
		console.log("{pigcms{:U('Bill/ajax_upload_pic')}");
		var kind_editor = K.editor({
			allowFileManager : true
		});

		K('#J_selectImage').click(function(){

			if($('.upload_pic_li').size() >= 5){
				alert('最多上传5个图片！');
				return false;
			}
			kind_editor.uploadJson ="{pigcms{$config.site_url}{pigcms{:U('BillVillage/ajax_upload_pic')}";
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
