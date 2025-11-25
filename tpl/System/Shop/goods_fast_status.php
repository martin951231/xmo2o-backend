<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Shop/goods_fast_status')}">
        <input type="hidden" value="{pigcms{$ids}" name="id">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">审核状态</th>
                <td>审核通过：<input class="input" type="radio" id="status1" name="status" value="1" checked>&nbsp;&nbsp;&nbsp;&nbsp;审核失败：<input id="status2" class="input" type="radio" name="status" value="2"></td>
            </tr>
            <tr>
                <th width="80">备注</th>
                <td><textarea id="status_content" validate="minlength:1,required:true" class="input" name="status_content" style="min-height: 50px;min-width: 70%;">审核成功</textarea ></td>
            </tr>
        </table>
        <div class="btn">
            <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
            <input type="reset" value="取消" class="button" />
        </div>
	</form>
<script>
    $(document).ready(function (){
        $('#status1').click(function (){
            $("#status_content").html("审核成功");
        })
        $('#status2').click(function (){
            $("#status_content").html("");
        })
    })
</script>
<include file="Public:footer"/>