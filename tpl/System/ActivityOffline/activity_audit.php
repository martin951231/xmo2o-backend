
<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<form id="myform" method="post" action="" enctype="multipart/form-data" frame="true" refresh="true">
    <input type="hidden" name="activity_id" value="{pigcms{$activity_detail.activity_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">编号</th>
            <td>
                {pigcms{$activity_detail.activity_id}
            </td>
        </tr>
        
        <tr>
            <th width="80">活动主题</th>
            <td>
                {pigcms{$activity_detail.title}
            </td>
        </tr>

        <tr>
            <th width="80">状态</th>
            <td>
                <select  name="examine_status" id="examine_status" validate="maxlength:20,required:true">
                    <option value="1" <if condition="$activity_detail['examine_status'] eq 1">selected</if>>待审核</option>
                    <option value="2" <if condition="$activity_detail['examine_status'] eq 2">selected</if>>审核通过</option>
                    <option value="3" <if condition="$activity_detail['examine_status'] eq 3">selected</if>>审核不通过</option>
                </select>
            </td>
        </tr>


        <tr class="reject_message" style="display: none;">
            <th width="80">不通过原因</th>
            <td>
               <textarea name="reject_message" style="width: 200px;height: 100px;">{pigcms{$activity_detail.reject_message}</textarea>
            </td>
        </tr>

        <tr>
            <th width="80">添加时间</th>
            <td>
                {pigcms{$activity_detail.add_time|date='Y-m-d H:i:s',###}
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script type="text/javascript">
    $('#examine_status').change(function(argument) {
        if ($('#examine_status').val()==3) {
            $('.reject_message').show();
        }else{
            $('.reject_message').hide();
        }
    })
     $('#examine_status').trigger('change');
</script>
<include file="Public:footer"/>