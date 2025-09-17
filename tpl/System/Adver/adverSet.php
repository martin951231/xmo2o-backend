<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Adver/adverSet')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
      <tr>
        <th width="90">上传背景图片：</th>
        <td>
          <if condition="$index_background_pic">
            <img style="width: 100px;float: left;" src="{pigcms{$index_background_pic}" alt="">
          </if>
          <input type="file" class="input fl" name="index_background_pic" style="width:200px;" placeholder="请上传背景图片"  <if condition="empty($index_background_pic)"> validate="required:true" </if>/>
        </td>
      </tr>
      <tr>
        <th width="90">上传广告图片：</th>
        <td>
          <if condition="$index_adver_pic">
            <img style="width: 100px;float: left;" src="{pigcms{$index_adver_pic}" alt="">
          </if>
          <input type="file" class="input fl" name="index_adver_pic" style="width:200px;" placeholder="请上传广告图片" <if condition="empty($index_background_pic)"> validate="required:true" </if> />
            <a class="btn btn-sm btn-success" style="text-decoration: none;">尺寸：398 * 110</a>
        </td>
      </tr>
      <tr>
        <th width="90">广告图跳转链接：</th>
        <td>
          <input type="text" class="input fl" name="index_adver_pic_jump_url" id="url" value="{pigcms{$index_adver_pic_jump_url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:800,required:true"/>
          <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
        </td>
      </tr>
      <tr>
        <th width="90">轮播图广告位状态：</th>
        <td>
          <span>显示 <input type="radio" name="index_view_pic_status" value="1" <if condition="$index_view_pic_status eq 1">checked="checked" </if> /></span>
          <span style="margin-left: 10px;">隐藏 <input type="radio" name="index_view_pic_status" value="0" <if condition="$index_view_pic_status eq 0">checked="checked" </if> /></span>
          <span style="margin-left: 10px;">隐藏占位 <input type="radio" name="index_view_pic_status" value="2" <if condition="$index_view_pic_status eq 2">checked="checked" </if> /></span>
        </td>
      </tr>
			<tr>
				<th width="90">广告图状态：</th>
				<td>
          <span>显示 <input type="radio" name="index_adver_status" value="1" <if condition="$index_adver_status eq 1">checked="checked" </if> /></span> 
          <span style="margin-left: 10px;">隐藏 <input type="radio" name="index_adver_status" value="0" <if condition="$index_adver_status eq 0">checked="checked" </if> /></span>
          <span style="margin-left: 10px;">隐藏占位 <input type="radio" name="index_adver_status" value="2" <if condition="$index_adver_status eq 2">checked="checked" </if> /></span>
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
<script>
  function addLink(domid, iskeyword, type) {
    art.dialog.data('domid', domid);
    if (type == 1) {
      art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword=' + iskeyword, {
        lock: true,
        title: '插入链接或关键词',
        width: 800,
        height: 500,
        yesText: '关闭',
        background: '#000',
        opacity: 0.45
      });
    } else {
      art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword=' + iskeyword, {
        lock: true,
        title: '插入链接或关键词',
        width: 800,
        height: 500,
        yesText: '关闭',
        background: '#000',
        opacity: 0.45
      });
    }
  }
</script>
<include file="Public:footer"/>