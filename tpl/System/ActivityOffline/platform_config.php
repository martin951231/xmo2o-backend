<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('ActivityOffline/manage')}">分类列表</a>|
					<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('ActivityOffline/add')}','添加分类',800,560,true,false,false,addbtn,'add',true);">添加分类</a>
					<a href="{pigcms{:U('ActivityOffline/activity_list')}">线下活动列表</a>|
					<a href="{pigcms{:U('ActivityOffline/examine_refundList')}">退款审核列表</a>|
					<a href="{pigcms{:U('ActivityOffline/ActivityOffline_config')}"  class="on">配置项</a>|
				</ul>
			</div>
				<div>
					<form action="{pigcms{:U('ActivityOffline/ActivityOffline_config')}" method="post">
						<table width="100%" cellspacing="0" class="table_form" cellspacing="0">
							<tbody>
								<volist name="agreement" id="vol">
                                    <tr class="{pigcms{$vol.alias_info}" <if condition="$vol['alias_info'] eq 'ActivityOffline_activity_notice_time' && $config_info['ActivityOffline_activity_notice_switch'][content] eq 0">style="display:none;"</if>>
                                        <th width="160px;">{pigcms{$vol.name}</th>
                                        <td>
                                            <if condition="$vol.type eq 'add_input'">
                                                <input type="text" class="input-text" name="{pigcms{$vol.alias_info}" value="{pigcms{$vol.content}">分
                                            <elseif condition="$vol.type eq 'text'" />
                                                <textarea class="editor_id" id="{pigcms{$vol.alias_info}" name="{pigcms{$vol.alias_info}" rows="20" cols="100">{pigcms{$vol.content}</textarea>
                                            <elseif condition="$vol.type eq 'rate_input'" />
                                                <input type="text" class="input-text" name="{pigcms{$vol.alias_info}" value="{pigcms{$vol.content}">%
                                                <elseif condition="$vol.type eq 'radio'" />
                                                <span class="cb-enable"><label class="cb-enable <if condition="$vol.content eq 1">selected</if>"><span>开启</span><input type="radio" name="{pigcms{$vol.alias_info}" value="1" <if condition="$vol.content eq 1">checked="checked"</if>/></label></span>
                                                <span class="cb-disable"><label class="cb-disable <if condition="$vol.content eq 0">selected</if>"><span>关闭</span><input type="radio" name="{pigcms{$vol.alias_info}" value="0" <if condition="$vol.content eq 0">checked="checked"</if>/></label></span>
                                                <if condition="$vol.desc neq ''">
                                                    <img title="{pigcms{$vol.desc}" class="tips_img" src="./tpl/System/Static/images/help.gif">
                                                </if>
                                            <elseif condition="$vol.type eq 'number'" />
                                                    <input type="number" class="input-text" name="{pigcms{$vol.alias_info}" value="{pigcms{$vol.content}">小时
                                                    <if condition="$vol.desc neq ''">
                                                        <img title="{pigcms{$vol.desc}" class="tips_img" src="./tpl/System/Static/images/help.gif">
                                                    </if>
                                            </if>
                                        </td>
                                    </tr>
								</volist>
									<tr>
										<th width="160px;"></th>
										<td><input type="submit" value="保存" class="button"/></td>
									</tr>
							</tbody>	
						</table>
					</form>
				</div>
		</div>
	<link rel="stylesheet" href="./static/kindeditor/themes/default/default.css">
	<script src="./static/kindeditor/kindeditor.js"></script>
	<script src="./static/kindeditor/lang/zh_CN.js"></script>
	<script>
		KindEditor.ready(function(K){
			// 	var site_url = "{pigcms{$config.site_url}";
			// 	var editor = K.editor({
			// 		allowFileManager : true
			// 	});

			// 	window.editor = K.create('#signup_agreement',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
			// 	window.editor = K.create('#config_store_register_agreement',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
			// 	window.editor = K.create('#config_village_group_rule',{pasteType : 1,resizeType : 1,width:"50%",height:"300px",uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"});
			// });

		// KindEditor.ready(function(K) {
			

			// window.editor = K.create('.editor_id',{
			// 	items:[
			// 			'source', '|', 'undo', 'redo', '|',  'cut', 'copy', 'paste',
			// 			'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
			// 			'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
			// 			'superscript', 'clearhtml', 'quickformat', 'selectall', '/', '|', 
			// 			'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
			// 			'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat'
			// 	],
			// 	 afterBlur: function(){ 
			// 		var html='';
			// 		html = editor.html(); 
			// 		console.log('-------------------:'+editor.html())
			// 		artDialog.data("html", html);  
			// 		if(html==''){
			// 			art.dialog.data("html_empty", 1);  
			// 		}else{
			// 			art.dialog.data("html_empty", 2);  
			// 		}
			// 		console.log(html)
			// 	 }
			// });
        // });

        $("input[name='ActivityOffline_activity_notice_switch']").click(function () {
            console.log('ActivityOffline_activity_notice_time-----', $(this).val());
            if ($(this).val() == 1) {
                $('.ActivityOffline_activity_notice_time').css('display', 'table-row');
            } else {
                $('.ActivityOffline_activity_notice_time').css('display', 'none');
            }
        });
</script>
<include file="Public:footer"/>