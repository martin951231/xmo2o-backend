<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Portal/tieba')}">贴吧板块</a>|
					<a href="{pigcms{:U('Portal/tieba_config')}"  class="on">配置项</a>|
				</ul>
			</div>
				<div>
					<form action="{pigcms{:U('Portal/tieba_config')}" method="post">
						<table width="100%" cellspacing="0" class="table_form" cellspacing="0">
							<tbody>
									<tr>
										<th width="160px;">{pigcms{:C('platform_config.platform_fabu_agreement')['name']}</th>
										<td>
											<textarea class="editor_id" name="{pigcms{:C('platform_config.platform_fabu_agreement')['alias_info']}" rows="20" cols="100">{pigcms{:C('platform_config.platform_fabu_agreement')['content']}</textarea>
										</td>
									</tr>
									<tr>
										<th width="160px;">{pigcms{:C('platform_config.platform_signup_agreement')['name']}</th>
										<td>
											<textarea class="editor_id" name="{pigcms{:C('platform_config.platform_signup_agreement')['alias_info']}" rows="20" cols="100">{pigcms{:C('platform_config.platform_signup_agreement')['content']}</textarea>
										</td>
									</tr>
									<tr>
										<th width="160px;"></th>
										<td><input type="submit" value="保存" class="button"/></td>
									</tr>
							</tbody>	
						</table>
					</form>
				</div>
		</div>
		<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
		<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
		<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script>
		KindEditor.ready(function(K) {
			window.editor = K.create('.editor_id',{
				items:[
						'source', '|', 'undo', 'redo', '|',  'cut', 'copy', 'paste',
						'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
						'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
						'superscript', 'clearhtml', 'quickformat', 'selectall', '/', '|', 
						'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
						'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat'
				],
				 afterBlur: function(){ 
					var html='';
					html = editor.html(); 
					console.log('-------------------:'+editor.html())
					artDialog.data("html", html);  
					if(html==''){
						art.dialog.data("html_empty", 1);  
					}else{
						art.dialog.data("html_empty", 2);  
					}
					console.log(html)
						
					 //this.sync('#editor_id');
				 }
			});
        });
</script>
<include file="Public:footer"/>