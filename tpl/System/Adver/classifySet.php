<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Adver/classifySet')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
      <tr>
        <th width="80">{pigcms{$config.classify_name}</th>
        <td>
          <span class="cb-enable"><label class="cb-enable <if condition="$wap_home_show_classify eq 1">selected</if>"><span>启用</span><input type="radio" name="wap_home_show_classify" value="1" <if condition="$wap_home_show_classify eq 1">checked="checked"</if>/></label></span>
          <span class="cb-disable"><label class="cb-disable <if condition="$wap_home_show_classify eq 0">selected</if>"><span>关闭</span><input type="radio" name="wap_home_show_classify" value="0" <if condition="$wap_home_show_classify eq 0">checked="checked"</if>/></label></span>
        </td>
      </tr>
      <tr>
        <th width="90">{pigcms{$config.classify_name}描述</th>
        <td>
          <input type="text" class="input fl" name="classify_name_desc" value="{pigcms{$classify_name_desc}" style="width:200px;" placeholder="请填写首页{pigcms{$config.classify_name}描述"/>
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