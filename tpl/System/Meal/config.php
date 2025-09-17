<include file="Public:header"/>
		<div class="mainbox">
			<if condition="empty($_GET['galias'])">
				<div id="nav" class="mainnav_title">
					<ul>
						<volist name="group_list" id="vo">
							<a href="{pigcms{:U('Config/index',array('gid'=>$vo['gid']))}" <if condition="$gid eq $vo['gid']">class="on"</if>>{pigcms{$vo.gname}</a>|
						</volist>
					</ul>
				</div>
			<else/>
				<if condition="$header_file">
					<include file="$header_file"/>
				</if>
			</if>
			<if condition="$system_session['level'] eq 2 && in_array($gid,array(7,8,22,34,43,20))">
				<div class="page_tips">
					<ol>
						<if condition="$gid eq 7">
							<li>1. 微信支付 对接文档&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=5" target="_blank">【点击查看】</a></li>
							<li>2. 支付宝H5 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=15" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 43"/>
							<li>1. 支付宝H5 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=15" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 8"/>
							<li>1. 微信公众号 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=3" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 22"/>
							<li>1. 小区模块 使用文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=32" target="_blank">【点击查看】</a></li>
							<li>2. 车牌识别配置 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=34" target="_blank">【点击查看】</a></li>
							<li>3. A1人脸识别 使用文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=35" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 34"/>
							<li>1. 支付宝H5 对接文档&nbsp;&nbsp;&nbsp;<a href="https://o2o-doc.pigcms.com/web/#/1?page_id=15" target="_blank">【点击查看】</a></li>
						<elseif condition="$gid eq 20"/>
							<li>多个配送时间段时间不能重叠设置，例如 时间段一为8:00-10:00，时间段二为6:00-22:00 否则取最后一个时间段内的数据</li>
						</if>
					</ol>
				</div>
			</if>
			<form id="myform" method="post" action="{pigcms{:U('Config/amend')}" refresh="true">
				{pigcms{$config_tab_html}
				{pigcms{$config_html}
				<div class="btn" style="margin-top:20px;">
					<input TYPE="submit"  name="dosubmit" value="提交" class="button" />
					<input type="reset"  value="取消" class="button" />
				</div>
			</form>
		</div>
		<script>
			$(function(){
				$('.table_form:eq(0)').show();
				
				$('.tab_ul li a').click(function(){
					$(this).closest('li').addClass('active').siblings('li').removeClass('active');
					$($(this).attr('href')).show().siblings('.table_form').hide();
					return false;
				});
				$('#im_key').click(function(){
					window.top.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/im')}",function(data){
						if(data.error_code){
							window.top.msg(0,data.msg,true,3);
						}else{
							window.top.msg(1,data.msg,true,3);
						}
					},'json');
				});
				$('#live_service_key').click(function(){
					window.top.msg(2,'正在请求中,请稍等...',true,100);
					$.get("{pigcms{:U('Config/live_service')}",function(data){
						if(data.error_code){
							window.top.msg(0,data.msg,true,3);
						}else{
							window.top.msg(1,data.msg,true,3);
						}
					},'json');
				});
				
			});
		</script>
		<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
		<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
		<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
		<script type="text/javascript">
			KindEditor.ready(function(K){
				var site_url = "{pigcms{$config.site_url}";
				var editor = K.editor({
					allowFileManager : true
				});
				$('.config_upload_image_btn').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
					editor.loadPlugin('image', function(){
						editor.plugin.imageDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-image').val(site_url+url);
								editor.hideDialog();
							}
						});
					});
					setTimeout(function(){
						$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
					},200);
				});
				$('.config_upload_file_btn').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_file')}&name="+upload_file_btn.siblings('.input-file').attr('name');
					editor.loadPlugin('insertfile', function(){
						editor.plugin.fileDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-file').val(url);
								editor.hideDialog();
							}
						});
					});
					setTimeout(function(){
						$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
					},200);
				});
				
				window.editor = K.create('#config_register_agreement',{pasteType : 1});
				window.editor = K.create('#config_store_register_agreement',{pasteType : 1});
				window.editor = K.create('#config_village_group_rule',{pasteType : 1,resizeType : 1,width:"50%",height:"300px"});
			});
		</script>
		<style>
			.table_form{border:1px solid #ddd;}
			.tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
			.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
			.tab_ul>li>a {
				position: relative;
				display: block;
				padding: 10px 15px;
				margin-right: 2px;
				line-height: 1.42857143;
				border: 1px solid transparent;
				border-radius: 4px 4px 0 0;
				padding: 7px 12px 8px;
				min-width: 100px;
				text-align: center;
			}
			.tab_ul>li>a, .tab_ul>li>a:focus {
				border-radius: 0!important;
				border-color: #c5d0dc;
				background-color: #F9F9F9;
				color: #999;
				margin-right: -1px;
				line-height: 18px;
				position: relative;
			}
			.tab_ul>li>a:focus, .tab_ul>li>a:hover {
				text-decoration: none;
				background-color: #eee;
			}
			.tab_ul>li>a:hover {
				border-color: #eee #eee #ddd;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li>a:hover {
				background-color: #FFF;
				color: #4c8fbd;
				border-color: #c5d0dc;
			}
			.tab_ul>li:first-child>a {
				margin-left: 0;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #555;
				background-color: #fff;
				border: 1px solid #ddd;
				border-bottom-color: transparent;
				cursor: default;
			}
			.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
				color: #576373;
				border-color: #c5d0dc #c5d0dc transparent;
				border-top: 2px solid #4c8fbd;
				background-color: #FFF;
				z-index: 1;
				line-height: 18px;
				margin-top: -1px;
				box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
			}
			.tab_ul:before,.tab_ul:after{
				content: " ";
				display: table;
			}
			.tab_ul:after{
				clear: both;
			}
		</style>
<include file="Public:footer"/>