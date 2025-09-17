<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Adver/sliderOpacitySet')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
      <tr>
        <th width="90">首页导航透明度：</th>
        <td>
          <input type="number" min="0" max="1" step="0.1" class="input fl" name="index_slider_opacity" value="{pigcms{$index_slider_opacity}" style="width:200px;" placeholder="请填写首页导航透明度" validate="required:true"/>
        </td>
      </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<include file="Public:footer"/>