<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>

<form id="myform" method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">编号</th>
            <td>
                {pigcms{$info.id}
            </td>
        </tr>
        <tr>
            <th width="80">评论者昵称</th>
            <td>
                 <input type="text" class="input fl" name="nickname" id="nickname" value="{pigcms{$info.nickname}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/>
            </td>
        </tr>
        <tr>
            <th width="80">从业者姓名</th>
            <td>
                 <input type="text" class="input fl" name="employee_name" id="employee_name" value="{pigcms{$info.employee_name}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/>
            </td>
        </tr>
        <tr>
            <th width="80">评论内容</th>
            <td><input type="text" class="input fl" name="employee_name" id="employee_name" value="{pigcms{$info.employee_name}" size="25" tips=""/></td>
        </tr>
		 <tr>
            <th width="80">评论图片</th>
            <td>
                <if condition="is_array($info['image'])">
                    <volist name="info['image']" id="vv">
                        <img src="{pigcms{:file_domain()}{pigcms{$vv}" style="width: 80px;height: 80px;margin-right:5px;"  class="view_msg">
                    </volist>
                </if>
            </td>
        </tr>

        <tr>
            <th width="80">评论标签</th>
            <td>
                <if condition="is_array($reply_memory)">
                    <volist name="reply_memory" id="vv">
                        <button style="margin-right: 5px;border-radius: 0.5rem;background-color: #38CCBD;color: white;">{pigcms{$vv.memory_name}<span>&nbsp;&nbsp;{pigcms{$vv.num}</span></button>
                    </volist>
                </if>
            </td>
        </tr>
        <tr>
            <th width="80">评论时间</th>
            <td>
                {pigcms{$info.add_time|date='Y-m-d H:i:s',###}
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
        window.o2oTop.showTopColorPanel('Openedit',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_color1')
    });
    // var a = $("input[name='is_open_adver']:checked").val();
</script>
<include file="Public:footer"/>