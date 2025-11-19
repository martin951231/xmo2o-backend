<include file="NewPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">小区管理</a>
            </li>
            <li class="active">添加小区</li>
        </ul>
    </div>
    <if condition="!$href">
        <div class="alert alert-info" style="margin:10px;">
            若您想在平台上开店，您可以先注册一个商家，并在此页面绑定，后续可方便从此页面跳转到商家后台。<br/>之后旗下各小区也可以从功能库链接中选择商家功能的对应页面链接，快速显示在业主使用的界面中。<br/>
            <a href="{pigcms{$config.site_url}/merchant.php" target="_blank" style="margin-top:8px;border:1px solid #2681f3;color: #2681f3;padding: 3px 6px;display: inline-block;">前往商家注册</a>
        </div>
        <else/>
        <div class="alert alert-info" style="margin:10px;">
            您已经绑定了商家，此处可以跳转到商家后台。<br/>对应旗下各小区也可以从功能库链接中选择商家功能的对应页面链接，快速显示在业主使用的界面中。<br/>
            <a href="javascript:void(0);" class="jumpUrl" style="margin-top:8px;border:1px solid #2681f3;color: #2681f3;padding: 3px 6px;display: inline-block;">前往商家</a>
        </div>
    </if>

    <!-- 内容头部 -->
    <style>
		.line-span { line-height:34px;}
		.none { display:none}
	</style>
    <if condition="!$href">
    <div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							绑定商家
						</div>
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="email">{pigcms{:L_("商家手机号")}</label></label>

								<input class="col-sm-2" size="20" name="new_phone" type="tel"/>
								<span class="form_tips">{pigcms{:L_("不修改手机留空")}</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="email">{pigcms{:L_("验证码")}</label></label>
								<input class="col-sm-2" size="20" name="SmsCode2" type="text" value=""/>
								&nbsp;&nbsp;&nbsp;&nbsp;
								<a href="javascript:void(0)" onclick="sendsms2(this)" class="btn btn-sm btn-success">{pigcms{:L_("发送验证码")}</a>
							</div>

						</div>

						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
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
    </div>
</if>
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script src="{pigcms{$static_path}propery_group/js/jquery-2.1.0.min.js"></script>
<script src="{pigcms{$static_path}propery_group/js/bootstrap-treeview.js"></script>
<script src="{pigcms{$static_path}layer/skin/layui.js"></script>
<script type="text/javascript">
    var href = "{pigcms{$href}";
    $('.jumpUrl').click(function () {
        window.parent.open(href);
    })

	var countdown2 = 60;
	function sendsms2(val){

		var phone = $("input[name='new_phone']").val()
		console.log(phone);
		if(phone==''){
			alert('{pigcms{:L_("手机号码不能为空！")}');
		}else{
			if(countdown2==60){
				$.ajax({
					url: '{pigcms{:U('sendsms')}',
					type: 'POST',
					dataType: 'json',
					data: {newphone:phone},
					success:function(date){
						if(date.status==0){
							alert(date.info)
							val.removeAttribute("disabled");
							$(val).html("{pigcms{:L_("验证短信")}");
							countdown2 = 60;
						}else{
							if (countdown2 == 0) {
								val.removeAttribute("disabled");
								$(val).html("{pigcms{:L_("验证短信")}");
								countdown2 = 60;
								//clearTimeout(t);
							} else {
								val.setAttribute("disabled", true);
								$(val).html("{pigcms{:L_("重新发送")}(" + countdown2 + ")");
								countdown2--;
								setTimeout(function() {
									sendsms2(val);
								},1000)
							}
						}
					}
				});
			} else {
                if (countdown2 == 0) {
                    val.removeAttribute("disabled");
                    $(val).html("{pigcms{:L_("验证短信")}");
                    countdown2 = 60;
                    //clearTimeout(t);
                } else {
                    val.setAttribute("disabled", true);
                    $(val).html("{pigcms{:L_("重新发送")}(" + countdown2 + ")");
                    countdown2--;
                    setTimeout(function() {
                        sendsms2(val);
                    },1000)
                }
            }
		}
	}





function check_submit(){

}
</script>
<include file="NewPublic:footer"/>
