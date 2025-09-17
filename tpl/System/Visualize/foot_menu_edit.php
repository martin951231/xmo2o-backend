<include file="Public:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<style>
    .frame_form .input{
        height:30px;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('Visualize/foot_menu_edit')}" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$foot_menu_info.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="100">菜单名称</th>
            <td><input type="text" class="input fl" name="name" value="{pigcms{$foot_menu_info.name}" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
        </tr>
        <if condition="$foot_menu_info['pic_path']">
            <tr>
                <th width="100">菜单图片展示</th>
                <td><img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$foot_menu_info.pic_path}" style="width:25px;height:25px;" class="view_msg"/></td>
            </tr>
        </if>
        <tr>
            <th width="100">菜单图片</th>
            <td><input type="file" class="input fl" name="pic_path" style="width:200px;" placeholder="请上传图片" tips="可不上传"/></td>
        </tr>

        <if condition="$foot_menu_info['hover_pic_path']">
            <tr>
                <th width="100">菜单选中图片展示</th>
                <td><img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$foot_menu_info.hover_pic_path}" style="width:25px;height:25px;" class="view_msg"/></td>
            </tr>
        </if>


        <tr>
            <th width="100">菜单选中图片</th>
            <td><input type="file" class="input fl" name="hover_pic_path" style="width:200px;" placeholder="请上传图片" tips="可不上传"/></td>
        </tr>
        <tr>
            <th width="100">链接地址</th>
            <td>
                <input type="text" class="input fl" name="url" id="url" value="{pigcms{$foot_menu_info.url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,required:true,url:true"/>
                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink2('url',0)" data-toggle="modal">从功能库选择</a>
            </td>
        </tr>
         <tr class="wxapp_open_wxapp">
            <th width="80">打开其他小程序</th>
            <td>
                <select name="wxapp_id" id="wxapp_id" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
                    <option value="">选择小程序</option>
                    <volist name='wxapp_list' id='vo'>
                        <option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $foot_menu_info['wxapp_id']">selected</if> >{pigcms{$vo.name}</option>
                    </volist>
               </select>
               <span style="position: relative;top: 2px;">选填</span>
            </td>
        </tr>
        <tr  class="wxapp_open_wxapp wxapp_page"  <if condition="empty($foot_menu_info['wxapp_id'])">style="display: none;"</if> >
            <th width="80">小程序页面</th>
            <td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$foot_menu_info.wxapp_page}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
        </tr>

        <tr>
            <th width="100">菜单排序</th>
            <td><input type="text" class="input fl" name="sort" style="width:100px;" value="{pigcms{$foot_menu_info.sort}" validate="maxlength:10,required:true,number:true"/></td>
        </tr>
        <tr>
            <th width="100">菜单状态</th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$foot_menu_info['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$foot_menu_info['status'] eq 1">checked="checked"</if>/></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$foot_menu_info['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$foot_menu_info['status'] eq 0">checked="checked"</if>/></label></span>
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
    function addLink2(domid, iskeyword, type) {
        art.dialog.data('domid', domid);
        if (type == 1) {
            art.dialog.open('?g=Admin&c=LinkPC&a=visualize&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        } else {
            art.dialog.open('?g=Admin&c=Link&a=visualize&iskeyword=' + iskeyword, {
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
    $("#wxapp_id").change(function(){
        if ($("#wxapp_id").val()) {
            $(".wxapp_page").show();
        }else{
            $("#wxapp_page").val('');
            $(".wxapp_page").hide();
        }
    })
    $("#wxapp_id").trigger('change');
</script>
<include file="Public:footer"/>