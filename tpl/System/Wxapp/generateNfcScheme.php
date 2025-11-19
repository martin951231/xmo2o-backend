<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Wxapp/generateNfcScheme')}"   frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">类型</td>
				<td>优惠买单</td>
			</tr>
			<tr>
                <td width="80">店铺名称：</td>
                <td>
                    <select class="fl" name="store_id" id="" style="width:200px; margin-right: 15px;"  validate="required:true">
                        <option value="">--请选择店铺--</option>
                        <volist name="stores" id='s'>
                            <option class="name_opt" value="{pigcms{$s['store_id']}">{pigcms{$s['name']}</option>
                        </volist>
                    </select>
                    <input type="text" class="input" id="name_search" onkeyup="filter_name()" placeholder="关键词搜索"/>
                </td>
            </tr>
			<tr>
				<td width="80">设备model_id</td>
				<td><input type="text" class="input fl" name="model_id" size="52" value="" validate="required:true"/></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type='text/javascript'>
	function filter_name(){
		var s = $('#name_search').val();
		$('.name_opt').each(function(){
			var unit_name = $(this).html().trim();
			if(unit_name.indexOf(s) === -1){
				$(this).hide();
			}else{
				$(this).show();
			}
		})
	}
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>