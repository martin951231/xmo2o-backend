<include file="Public:header"/>
<style>
    .span_msg{
        margin-top: 5px;display: block;
    }
    .fl{
        width: 75%;
    }
    #img{
        display: none;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('System/Forum/forum_add')}" enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">标题</th>
            <td>
                <div>
                    <div>
                        <input type="text" class="input fl" name="title" size="20" placeholder="请输入标题" validate="maxlength:20,required:true" maxlength="20"/>
                    </div>
                    <div>
                        <span class="span_msg">用于APP消息推送的标题显示，最多20字</span>
                    </div>
                </div>

            </td>
        </tr>

        <tr>
            <th width="80">描述</th>
            <td>
                <div >
                    <input type="text" class="input fl" name="content"  placeholder="请输入描述内容" validate="maxlength:50" maxlength="50"/>
                </div>
                <div style="width: 30%">
                    <span class="span_msg">用于APP消息推送的描述显示，最多50字</span>
                </div>
            </td>
        </tr>
<!---->
<!--        <tr>-->
<!--            <th width="80">图标</th>-->
<!--            <td>-->
<!--                <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>-->
<!--                <div>-->
<!--                    <img scr="" id="img" style="width:18%;">-->
<!--                </div>-->
<!--            </td>-->
<!--        </tr>-->

        <input type="hidden" name="img" value="">
        <tr>
            <th width="80">链接</th>
            <td>
                <div>
                    <div>
                        <input type="text" class="input fl" name="url" id="url"  placeholder="请输入链接" validate="maxlength:200,required:true,url:true"/>
                    </div>
                    <div>
                        <span class="span_msg">点击APP消息推送的跳转链接</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script>
    KindEditor.ready(function(K){
        var site_url = "{pigcms{$config.site_url}";
        var editor = K.editor({
            allowFileManager : true
        });
        $('.J_selectImage').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        $('input[name=img]').val(url);
                        $('#img').show();
                        $('#img').attr('src',site_url+url);
                        editor.hideDialog();
                    }
                });
            });
        });
        $('.J_selectFile').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('House/ajax_upload_file')}";
            editor.loadPlugin('insertfile', function(){
                editor.plugin.fileDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        upload_file_btn.siblings('.input-image').val(url);
                        editor.hideDialog();
                    }
                });
            });
        });

    });

</script>
<include file="Public:footer"/>