<include file="Public:header"/>
<form id="myform" method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">编号</th>
            <td>
                {pigcms{$employee_list.id}
            </td>
        </tr>
        <tr>
            <th width="80">昵称</th>
            <td>
                {pigcms{$employee_list.nickname}
            </td>
        </tr>
        <tr>
            <th width="80">从业者姓名</th>
            <td>
                {pigcms{$employee_list.true_name}
            </td>
        </tr>
        <tr>
            <th width="80">业务联系电话</th>
            <td>{pigcms{$employee_list.business_phone}</td>
        </tr>
		 <tr>
            <th width="80">注册手机号</th>
            <td>{pigcms{$employee_list.phone}</td>
        </tr>
        <tr>
            <th width="80">主页点击数</th>
            <td>
                {pigcms{$employee_list.click_num}
            </td>
        </tr>
        <tr>
            <th width="80">粉丝数</th>
            <td>
                {pigcms{$employee_list.fans_num}
            </td>
        </tr>
        <tr>
            <th width="80">收藏数</th>
            <td>
                {pigcms{$employee_list.collect_num}
            </td>
        </tr>
        <tr>
            <th width="80">点赞数</th>
            <td>
                {pigcms{$employee_list.thumb_num}
            </td>
        </tr>
        <tr>
            <th width="80">账户余额</th>
            <td>
                {pigcms{$employee_list.now_money}
            </td>
        </tr>


        <tr>
            <th width="80">是否关联店铺</th>
            <td>
                <if condition="$employee_list['is_relate_store'] eq 0">
                否
                <else />
                是
                </if>
            </td>
        </tr>
        <tr>
            <th width="80">是否启用</th>
            <td>
                <if condition="$employee_list['is_open'] eq 1">开启<else/>开启</if>
              </td>
        </tr>
         <tr>
            <th width="80">是否开启悬浮广告</th>
            <td>
                <if condition="$employee_list['is_open_adver'] eq 1">开启<else/>开启</if>
           </td>
        </tr>
        <tr>
            <th width="80">悬浮框背景色</th>
            <td>
                {pigcms{$employee_list['adver_color']}
            </td>
        </tr>
        <tr>
            <th width="80">悬浮框文字</th>
            <td>{pigcms{$employee_list.adver_info}</td>
        </tr>


        <if condition="is_array($employee_list['write_field'])">
            <tr>
                <th width="80" style="color: #0cc2c6;">从业者信息</th>
            </tr>
            <volist name="employee_list['write_field']" id="item">
                <tr>
                    <th width="80">{pigcms{$item.name}</th>
                    <if condition="$item.type eq 2">
                        <td>
                            {pigcms{$item.address}
                        </td>
                        <elseif condition="$item.type eq 10"/>
                        <td>
                            <volist name="item.value" id="vv">
                                <img src="{pigcms{:file_domain()}{pigcms{$vv}" style="width: 80px;height: 80px;margin-right:5px;"  class="view_msg">
                            </volist>
                        </td>

                        <elseif condition="$item.type eq 8"/>
                        <td>
                            {pigcms{$item.date_value}   {pigcms{$item.time_value}
                        </td>
                        <else/>
                        <td>
                            {pigcms{$item.value}
                        </td>
                    </if>
                </tr>
            </volist>
        </if>


        <tr>
            <th width="80">添加时间</th>
            <td>
                {pigcms{$employee_list.add_time|date='Y-m-d H:i:s',###}
            </td>
        </tr>


        <tr class="refuse_info_tip" style="display: none;">
            <th width="80">审核拒绝理由</th>
            <td>
                <textarea maxlength="100" class="input fl" name="refuse_info" id="refuse_info" rows="2" tips="用于描述审核拒绝理由,限制100字以内"></textarea>
            </td>
        </tr>

        <tr>
            <th width="80">审核状态</th>
            <td>
                <span class="cb-enable"><label class="cb-enable selected"><span onclick="check_info(1)">审核通过</span><input name="examine_status" type="radio" value="1"/></label></span>
                <span class="cb-disable"><label class="cb-disable"><span onclick="check_info(2)">审核拒绝</span><input name="examine_status" type="radio" value="2"/></label></span>
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
    $('#choose_color_box1').click(function(){
        window.top.showTopColorPanel('Openedit',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_color1')
    });

    var lockInfo = false;
    function check_info(status) {
        if (lockInfo) return;
        lockInfo = true;
        console.log('切换信息', status)
        if (status == 2) {
            $('.refuse_info_tip').show();
        } else {
            $('.refuse_info_tip').hide();
        }

    }



    // var a = $("input[name='is_open_adver']:checked").val();
</script>
<include file="Public:footer"/>