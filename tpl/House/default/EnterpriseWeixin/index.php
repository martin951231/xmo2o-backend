<include file="NewPublic:header"/>

<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-wechat"></i>
				<a href="{pigcms{:U('EnterpriseWeixin/index')}">企业微信永久授权码</a>
			</li>
			<li class="active">企业微信永久授权码</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<if condition="empty($bind)">
						<if condition="$url">
							<div id="shopList" class="grid-view">
								<div class="span12" style="margin-top:10px;">
									<div class="form-actions">
										<php>if(1){</php>
											<a class="btn btn-success cert-setting-btn js-wxauth-btn" target="_blank" data-url="{pigcms{$url}" href="{pigcms{$url}"><i class="ace-icon fa fa-wechat"></i>我有企业微信，立即设置</a>
										<php>}else{</php>
										    暂无设置
										<php>}</php>
									</div>
								</div>
							</div>
                        <elseif condition="$is_new"/>
                            <php>if ($qy_err) {</php>
                                <div class="span12" style="margin-top:10px;">
                                    <div class="form-actions">
                                        <p>企业微信错误：</p>
                                        <p>{pigcms{$qy_err.errmsg}</p>
                                    </div>
                                </div>
                            <php>} else {</php>
                                <div id="shopList" class="grid-view">
                                    <div class="span12" style="margin-top:10px;">
                                        <div class="form-actions">
                                            <a class="btn btn-success cert-setting-btn js-set-btn"><i class="ace-icon fa fa-wechat"></i>立即设置</a>
                                        </div>
                                    </div>
                                </div>
                            <php>}</php>
						<else />
							<div id="shopList" class="grid-view">
								<div class="span12" style="margin-top:10px;">
									<div class="form-actions">
										<a class="btn btn-error cert-setting-btn">平台还没有开启网页授权，或管理员配置错误，请联系系统管理者！</a>
									</div>
								</div>
								<php>if($_SESSION['system'] && $api_component_error_result){</php>
									<div class="span12" style="margin-top:10px;">
										<div class="form-actions">
											<p>请求微信接口出现错误：</p>
											<p>{pigcms{$api_component_error_result.errmsg}</p>
											<p>{pigcms{$api_component_error_result.real_errmsg}</p>
										</div>
									</div>
								<php>} elseif ($qy_err) {</php>
                                <div class="span12" style="margin-top:10px;">
                                    <div class="form-actions">
                                        <p>企业微信错误：</p>
                                        <p>{pigcms{$qy_err.errmsg}</p>
                                    </div>
                                </div>
                                <php>}</php>
							</div>
						</if>
					<else />
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-2"><label>企业微信号</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$bind['corp_name']}" type="text" style="border:none;background:white!important;" readonly="readonly"/>
								</div>
								<div class="form-group">
									<label class="col-sm-2"><label>企业微信账号类型</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$bind['type_info']}" type="text" style="border:none;background:white!important;" readonly="readonly"/>
								</div>
								<div class="form-group">
									<label class="col-sm-2"><label>企业在微工作台二维码</label></label>
									<div id="upload_pic_box"><img style="width:158px;height:158px;" src="{pigcms{$bind['qrcode_url']}"/></div>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-2"><label>授权方企业微信类型</label></label>
                                    <input class="col-sm-2" size="20" value="{pigcms{$bind['type_info']}" type="text" style="border:none;background:white!important;"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-2"><label></label></label>
                                    <button type="button" class="btn btn-success page-refresh">刷新</button>
                                    <button style="margin-left: 10px;" type="button" class="btn btn-success page-jude">配置信息</button>
                                </div>
							</div>
						</div>
					</form>
					</if>
				</div>
			</div>
		</div>
	</div>
</div>

<if condition="empty($bind)">
<script type="text/javascript">
	$(document).ready(function(){
        var href = "{pigcms{:U('EnterpriseWeixin/qy_guide_about')}";
	    $('.js-set-btn').click(function () {
            layer.open({
                type: 2,
                title:'企业微信',
                content: href,
                area: ['950px', '650px'],
                maxmin: false,
            });
        })


		$('.js-wxauth-btn').click(function(){

// 			$(".modal").dialog({
// 			    autoOpen: false,
// 			    modal: true,
// 			    buttons: {
// 			        Ok: function () {
// 			            $(this).dialog("close");
// 			        }
// 			    }
// 			});
// 			return false;
			var url = $(this).attr('data-url');
			if(url == ''){
// 				layer_tips(1,'授权网址获取失败，请联系管理员！');
// 				return false;
			}
// 			window.open(url); 
			var html = '';
			html += '<div class="modal fade in" style="width: 400px;height:165px;margin-top:-5px; " aria-hidden="false"><div class="modal-header">';
			html += '<a class="close" data-dismiss="modal">×</a>提示</div>';
			html += '<div class="modal-body">';
			html += '<p>请在新窗口中完成企业微信授权&nbsp;&nbsp;<a href="" target="_blank"></a></p>';
			html += '</div>';
			html += '<div class="modal-footer">';
			html += '<div style="text-align: center;">';
			html += '<button type="button" class="btn btn-success js-refresh">已成功设置</button>';
			html += '<a class="btn btn-default js-retry" href="'+url+'" target="_blank" data-loading-text="地址读取中..">授权失败，重试</a>';
			html += '</div>';
			html += '</div></div>';//<div class="modal-backdrop fade in"></div>';
			$('body').append(html);
		});
		$('.close').live('click', function(){
			$(this).parents('.modal').remove();
			$('.modal-backdrop').remove();
		});
		$('.js-refresh').live('click', function(){
			// location.reload();
            window.top.GenerateRoutes();
		});
	});
</script>
    <else/>
<script type="text/javascript">
    $('.page-refresh').click(function(){
        // location.reload();
        console.log('111')
        window.top.GenerateRoutes();
    });
    $('.page-jude').click(function(){
        location.href="{pigcms{$addUrl}";
    });
    var is_add = "{pigcms{$is_add}";
    var addUrl = "{pigcms{$addUrl}";
    console.log('is_add', is_add);
    if (1==is_add) {
        layer.open({
            content: '配置信息不完善需要前去配置！',
            shadeClose: false,
            closeBtn: 0
            ,btn: ['确定']
            ,yes:function(index){
                location.href=addUrl;
            }
        })
    }
</script>
</if>

<link rel="stylesheet" href="{pigcms{$static_path}css/base.css">
<style>
.form-actions {
background-color: #fff;
}
.modal{
display:block;
}
</style>
<include file="NewPublic:footer"/>
