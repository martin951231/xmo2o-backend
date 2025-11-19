<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Classify/reply_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="reply_id" value="{pigcms{$reply_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">评论内容</th>
				<td>
                    {pigcms{$detail.content}
                </td>
			</tr>
            <tr>
                <th width="80">评论者ID</th>
                <td>
                    {pigcms{$detail.reply_uid}
                </td>
            </tr>
            <tr>
                <th width="80">评论者昵称</th>
                <td>
                    {pigcms{$detail.reply_nickname}
                </td>
            </tr>
            <tr>
                <th width="80">被评论者ID</th>
                <td>
                    {pigcms{$detail.uid}
                </td>
            </tr>
            <tr>
                <th width="80">被评论者昵称</th>
                <td>
                    {pigcms{$detail.nickname}
                </td>
            </tr>
            <if condition="$detail.avatar neq ''">
                <tr>
                    <th width="80">被评论者头像</th>
                    <td><img src="{pigcms{$detail.avatar}" style="width:50px;height:50px;" class="view_msg"/></td>
                </tr>
            </if>
            <tr>
                <th width="80">评论时间</th>
                <td>
                    {pigcms{$detail.add_time|date='Y-m-d H:i:s',###}
                </td>
            </tr>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
<script type="text/javascript">
    function TextSEO(vv){
       vv=$.trim(vv);
       if(vv){
         var seo_title=$.trim($('#seo_title').val());
         var seo_keywords=$.trim($('#seo_keywords').val());
         var seo_description=$.trim($('#seo_description').val());
         if(!seo_title) $('#seo_title').val(vv);
         if(!seo_keywords) $('#seo_keywords').val(vv);
         if(!seo_description) $('#seo_description').val(vv);
       }
    }
    init_cat_edit();

    function init_cat_edit() {
        var reward_type = $('#reward_type').attr('type');
        if (reward_type == '') {
            return;
        }
        $('#reward_type').val(reward_type);
    }

    function select_reward_type(obj) {
        var val = $(obj).val();
        console.log('切换的值为： ', val);

        if (1 == val) {
            console.log('切换的值为1： ', val);
            $('#reward_publish_tr').css('display', 'none');
            $('#reward_look_tr').css('display', 'none');
        } else if (2 == val) {
            console.log('切换的值为2： ', val);
            $('#reward_publish_tr').css("display", "table-row");
            $('#reward_look_tr').css('display', 'none');
        } else if (3 == val) {
            console.log('切换的值为3： ', val);
            $('#reward_publish_tr').css('display', 'none');
            $('#reward_look_tr').css("display", "table-row");
        } else if (4 == val) {
            console.log('切换的值为4： ', val);
            $('#reward_publish_tr').css("display", "table-row");
            $('#reward_look_tr').css("display", "table-row");
        }
    }

    // 数字保留两位小数
    function moneyChange(obj) {
        var val = $(obj).val();
        if(val === "" || val ==null){
            alert('请输入正确的打赏金额数字！');
            return false;
        }
        if(isNaN(val)){
            alert('请输入正确的打赏金额数字！');
            return false;
        }
        console.log('数字保留两位小数', val);
        if (val) {
            val = Math.abs(Math.round(val * 100)/100);
        }
        $(obj).val(val);
    }
</script>