
<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<form id="myform" method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$list.join_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">编号</th>
            <td>
                {pigcms{$list.join_id}
            </td>
        </tr>
        
        <tr>
            <th width="80">活动主题</th>
            <td>
                {pigcms{$list.title}
            </td>
        </tr>
        <tr>
            <th width="80">订单编号</th>
            <td>
                {pigcms{$list.order_id}
            </td>
        </tr>
		<tr>
            <th width="80">姓名</th>
            <td><input type="text" class="input fl" name="name" id="name" value="{pigcms{$list.name}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">手机号</th>
            <td><input type="text" class="input fl" name="phone" id="phone" value="{pigcms{$list.phone}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">报名费用</th>
            <td><input type="text" class="input fl" name="money" id="money" value="{pigcms{$list.money}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">状态</th>
            <td>
                <select  name="status" id="status" value="{pigcms{$list.status}" validate="maxlength:20,required:true">
                    <option value="0" <if condition="$list['status'] eq 0">selected</if>>报名失败</option>
                    <option value="1" <if condition="$list['status'] eq 1">selected</if>>报名成功</option>
                    <option value="2" <if condition="$list['status'] eq 2">selected</if>>已完成</option>
                    <option value="3" <if condition="$list['status'] eq 3">selected</if>>待付款</option>
                    <option value="4" <if condition="$list['status'] eq 4">selected</if>>已退款</option>
                    <option value="5" <if condition="$list['status'] eq 5">selected</if>>已签到(已参加)</option>
                    <option value="6" <if condition="$list['status'] eq 6">selected</if>>已取消</option>
                </select>
            </td>
        </tr>

        <tr>
            <th width="80">添加时间</th>
            <td>
                {pigcms{$list.add_time|date='Y-m-d H:i:s',###}
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>