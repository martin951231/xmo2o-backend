<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("瓜分券")}</a>
			</li>
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
								<input type="hidden" name="pigcms_id" value="{pigcms{$coupon.pigcms_id}">
								<div class="tab-content card_new" id="tab1" >
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分券名称")}:</label></label>
										{pigcms{$coupon.name}
									</div>
									
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分券数量")}</label></label>
										{pigcms{$coupon.num}
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("瓜分券活动时间")}:</label></label>
										{pigcms{$coupon.start_time|date="Y-m-d H:i",###}-
										<input type="text" class="input-text" name="end_time" id="end_time" style="width:160px;" placeholder="{pigcms{:L_("点击设置结束时间")}" value="{pigcms{$coupon.end_time|date='Y-m-d H:i',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" />
										<span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分券有效期")}:</label></label>
											{pigcms{$coupon.day}
											<span>天</span>
											{pigcms{$coupon.hour}
											{pigcms{$coupon.minute}
											<span>时</span>
											<span>分</span>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分方式")}:</label></label>
										<if condition="$coupon['type'] eq 0">
										好友均分（每人领取金额相同）
										<else/>
										拼手气（每人领取金额随机）
										</if>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("瓜分内容")}:</label></label>
										{pigcms{$coupon.user_num}名好友，瓜分{pigcms{$coupon.money}元
									</div>
									
									<div class="form-group">
										<label class="tiplabel"><label><font color="red">*</font>{pigcms{:L_("可用券时间")}:</label></label>
										<if condition="$coupon['time_type'] eq 0">
											{pigcms{$coupon.use_start_time|date="Y-m-d H:i",###}-{pigcms{$coupon.use_end_time|date="Y-m-d H:i",###}
										<else/>
											成功瓜分当日起{pigcms{$coupon.use_time}天内可用
										</if>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("用券门槛")}:</label></label>
										<if condition="$coupon['use_condition'] eq 0">
										无使用门槛
										<else/>
										订单满{pigcms{$coupon.order_money}元
										</if>
									</div>
									<div class="form-group">
										<label class="tiplabel"><label>{pigcms{:L_("每人限瓜分次数")}:</label></label>
										<if condition="$coupon['partition_times'] eq 0">
										不限次数
										<else/>
										{pigcms{$coupon.partition_times}次
										</if>
									</div>
                                    <div class="form-group wx_coupon">
                                        <label class="tiplabel"><label>指定店铺</label></label>
                                        <volist name="store_list" id="vo">
                                           【{pigcms{$vo.name}】
                                        </volist>
                                    </div>	
                                    <div class="form-group wx_coupon">
                                        <label class="tiplabel"><label>分享图片</label></label>
                                        <img src="{pigcms{$coupon.img_url}" style="width:250px;height:200px;margin-right:5px;">
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
        seloptall(0)
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