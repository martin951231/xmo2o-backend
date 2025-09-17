<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("好友瓜分券")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加瓜分券")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="myform" method="post" >
								<div class="tab-content card_new" id="tab1" >
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分券名称")}:</label></label>
										<input type="text" name="name" id="name" class="px" value="{pigcms{$coupon.name}" style="width:210px;"/><span class="tip">({pigcms{:L_("要求9个汉字以内")})</span>
									</div>
									
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分券数量")}</label></label>
										<input type="text" name="num" id="num" class="px" value="" style="width:210px;"/>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("瓜分券活动时间")}:</label></label>
										<input type="text" class="input-text" name="start_time" id="start_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置开始时间")}" value="{pigcms{:date('Y-m-d H:i')}" onfocus="WdatePicker({isShowClear:false,readOnly:true,minDate:'{pigcms{:date('Y-m-d')}',dateFmt:'yyyy-MM-dd HH:mm'})"/>-
										<input type="text" class="input-text" name="end_time" id="end_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置结束时间")}" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',minDate:'#F{$dp.$D(\'start_time\')}'})" />
										<span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分券有效期")}:</label></label>
										<select name='day'>
											<for start='0' end='16'>
												<option value="{pigcms{$i}" <eq name='info.day' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
											</for>
										</select>
										<span>天</span>
										<select name='hour'>
											<for start='0' end='24'>
												<option value="{pigcms{$i}" <eq name='info.hour' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
											</for>
										</select>
										<span>时</span>
										<select name='minute'>
											<for start='0' end='60'>
												<option value="{pigcms{$i}" <eq name='info.minute' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
											</for>
										</select>
										<span>分</span>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分方式")}:</label></label>
											<label><input type="radio" name="type" value="0" checked="checked"/><span>好友均分（每人领取金额相同）</span></label>
											<label><input type="radio" name="type" value="1"/><span>拼手气（每人领取金额随机）</span></label>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分内容")}:</label></label>
										<input type="text" class="input fl" name="user_num" value="" validate="required:true,number:true,min:1" autocomplete="off" />名好友，瓜分<input type="text" class="input" name="money" value=""  validate="required:true,number:true,min:0.01" autocomplete="off" />元
									</div>
									
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("可用券时间")}:</label></label>

										<input type="radio" name="time_type" value="0" checked="checked" />
										<input type="text" class="input-text" name="use_start_time" id="use_start_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置开始时间")}" value="{pigcms{:date('Y-m-d H:i')}" onfocus="WdatePicker({isShowClear:false,readOnly:true,minDate:'{pigcms{:date('Y-m-d')}',dateFmt:'yyyy-MM-dd HH:mm'})"/>-
										<input type="text" class="input-text" name="use_end_time" id="use_end_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置结束时间")}" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',minDate:'#F{$dp.$D(\'use_start_time\')}'})" />

										<label><input type="radio" name="time_type" value="1"/>成功瓜分当日起<input type="text" class="input" name="use_time" value="" autocomplete="off" />天内可用</label>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("用券门槛")}:</label></label>
										<label><input type="radio" name="use_condition" value="0" checked="checked" />无使用门槛</label>
										<label><input type="radio" name="use_condition" value="1" />
										订单满<input type="text" class="input" name="order_money" value="" autocomplete="off"/>元</label>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("每人限瓜分次数")}:</label></label>
										<label>	<input type="radio" name="partition_times_type" value="0" checked="checked"/>不限次数</label>
										<label>	
											<input type="radio" name="partition_times_type" value="1" />
											<input type="text" class="input" name="partition_times" autocomplete="off"/>次
										</label>
									</div>
									<div class="form-group wx_coupon">
                                        <label class="tiplabel"><label>{pigcms{:L_("指定店铺")}：</label></label>
                                        {pigcms{:L_("全选")}: <input type="radio" onclick="seloptall(0)" name="sel" checked value="0">
                                        {pigcms{:L_("全不选")}: <input type="radio" onclick="seloptall(1)" name="sel" value="1">
                                    </div>
                                    <div class="form-group wx_coupon">
                                        <label class="tiplabel"><label>&nbsp;</label></label>
                                        <volist name="store_list" id="vo">
                                            <input type="checkbox" <if condition="in_array($vo['store_id'],$coupon['store_id'])">checked="checked"</if> name="store_id[]" class="px2" value="{pigcms{$vo.store_id}" >【{pigcms{$vo.name}】
                                        </volist>
                                    </div>

									<div class="form-group">
										<label class="tiplabel">{pigcms{:L_("上传图片")}</label>
										<label class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</label>
						                <input type="hidden" class="input-image" name="img_url" id="pic_list" value="{pigcms{$detail['img_url']}">
										<span class="tip">{pigcms{:L_("图片宽度建议为：500px，高度建议为：")}400px</span>
									</div>

					                <div class="form-group image_view" style="height: 250px;">
										<label class="tiplabel">{pigcms{:L_("图片预览")}</label>
										<label class="avatar_list" id="avatar_show">
										</label>
									</div>	
								</div>	
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit" onclick="confirm_coupon()">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="./static/js/cart/jscolor.js" type="text/javascript"></script>
<link rel="stylesheet" href="./static/kindeditor/themes/default/default.css"/>
<link rel="stylesheet" href="./static/kindeditor/plugins/code/prettify.css"/>
<style>
	.select_tab{
		width:124px;
		height:36px;
		color: #555;
		border: 1px solid #c5d0dc;
		font-size:16px;
		z-index:9;
		line-height: 36px;
    text-align: center;
		position: relative;
	}
	label .select_tab{
		display: inline-block;
		margin: 0 0 -1px;
		padding: 15px 25px;
		font-weight: 600;
		text-align: center;
		color: #bbb;
		border: 1px solid transparent;
	}
	.select{
		border-top: 1px solid orange;
		border-bottom: 1px solid #fff;
	}
	.card_new{
		margin-top:-6px;
	}
	.other label,table{
		color:#a0a0a0;
	}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<link rel="stylesheet" href="./static/validate/dist/css/bootstrapValidator.css"/>
<!-- Include the FontAwesome CSS if you want to use feedback icons provided by FontAwesome -->
<!--<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" />-->
<script type="text/javascript" src="./static/validate/vendor/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="./static/validate/vendor/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="./static/validate/dist/js/bootstrapValidator.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript">
</script>
<script type="text/javascript">
    function 
    seloptall(s) {
        if(s == 0){
            $('input[class="px2"]').each(function(){
                $(this).prop('checked',true);
            });
        }else{
            $('input[class="px2"]').each(function(){
                $(this).prop('checked',false);
            });
        }
    }
    seloptall(0);

		
</script>
<script type="text/javascript">
	KindEditor.ready(function(K){
		var editor = K.editor({
			allowFileManager : true
		});
        $('.J_selectImage').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=partition_coupon";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                            $('.image_view').show();
                            $('#avatar_show').show();
                            $('input[name=img_url]').val(url);
                            var html='<img src="'+url+'" style="width:250px;height:200px;margin-right:5px;">';
                            $('.avatar_list').html(html)
                            editor.hideDialog();
                            

                    }
                });
            });
        });
    })
	
</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css"/>
<style>
	#tab1 .tiplabel {
		margin-left: 20px;
		margin-right: 20px;
		min-width: 200px;!important
	}
</style>

<include file="Public:footer"/>