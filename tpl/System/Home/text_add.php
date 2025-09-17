<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Home/other')}">关键词回复列表</a>|
					<a href="{pigcms{:U('Home/other_add')}">添加图文关键词回复</a>
					<a href="{pigcms{:U('Home/text_add')}" class="on">添加文本+图片分离式关键词回复</a>
				</ul>
			</div>
			<form method="post" action="" refresh="true" enctype="multipart/form-data" >
				<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
					<tr>
						<th width="80">关键词</th>
						<td><input type="text" class="input-text" name="key" size="20" placeholder="关键词" validate="maxlength:20,required:true" value="{pigcms{$info.key}"/><span style="color: red;">相同的关键词使用最后一次添加的</span></td>
					</tr>
					<tr>
						<th width="80">回复内容</th>
						<td><textarea rows="6" cols="104"  name="content" id="content">{pigcms{$info.content}</textarea><img src="./static/images/exp1.png"></td></td>
					</tr>
					<tr>
						<th width="80">回复图片</th>
						<td>
							<input type="text"  style="width:200px;" name="pic" class="input input-image" value=""   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
							<input type="hidden"  style="width:200px;" name="media_id" id="media_id" value="">
						</td>
					</tr>
				</table>
				<div class="btn">
					<input type="submit"  name="dosubmit" value="提交" class="button" />
					<input type="reset"  value="取消" class="button" />
				</div>
			</form>
		</div>
		<style>
			.table_form{border:1px solid #ddd;}
			.tab_ul{margin-top:20px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
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
			.ke-dialog-default.ke-dialog{
				top:100px !important;
			}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">

<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
	KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.J_selectImage').click(function(){
				var upload_file_btn = $(this);
				console.log(editor);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_wx_media')}";
				editor.loadPlugin('image', function(){
					editor.plugin.imageDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							url = url.replace("\/","");
							upload_file_btn.siblings('.input-image').val(title);
							$('#media_id').val(url)
							editor.hideDialog();
						}
					});
				});
			});

		});
</script>
<include file="Public:footer"/>