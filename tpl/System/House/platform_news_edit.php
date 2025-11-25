<include file="Public:header"/>
<style>
    .btn {
        padding: 2px 6px;
        border-radius: 5px;
        outline: none;
    }
    .btn-success, .btn-success:focus {
        background-color: #87b87f!important;
        border-color: #87b87f;
    }
    .btn {
        display: inline-block;
        color: #FFF!important;
        text-shadow: 0 -1px 0 rgba(0,0,0,.25)!important;
        background-image: none!important;
        border: 0px solid #FFF;
        border-radius: 0;
        box-shadow: none!important;
        -webkit-transition: all ease .15s;
        -o-transition: all ease .15s;
        transition: all ease .15s;
        cursor: pointer;
        vertical-align: middle;
        margin: 0;
        position: relative;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('House/platform_news_edit')}" frame="true" refresh="true">

    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <input  name="news_id" type="hidden" value="{pigcms{$news_info['news_id']}"/>
        <tr>
            <th width="100">标题</th>
            <td><input type="text" class="input fl" name="title" size="100" placeholder="请输入新闻标题" validate="maxlength:100,required:true" value="{pigcms{$news_info['title']}"/></td>
        </tr>
        <tr>
            <th width="100">发布内容</th>
            <td>

                <textarea id="description" name="description"  placeholder="写上一些想要发布的内容">{pigcms{:replace_file_domain_content($news_info['content'])}</textarea>
            </td>
        </tr>
        <tr>
            <th width="100">是否热门</th>
            <td>
                <label><input value="1" name="is_hot" type="radio" <if condition="$news_info['is_hot'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
                &nbsp;&nbsp;&nbsp;
                <label><input value="0" name="is_hot" type="radio" <if condition="$news_info['is_hot'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>
                <span class="form_tips">若设置为热门，则会靠前显示</span>
            </td>
        </tr>
        <tr>
            <th width="100">是否重要通知</th>
            <td>
                <label><input  value="1" name="is_important_notice" type="radio" <if condition="$news_info['is_important_notice'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;是</label>
                &nbsp;&nbsp;&nbsp;
                <label><input  value="0" name="is_important_notice" type="radio" <if condition="$news_info['is_important_notice'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;否</label>
                <span class="form_tips">若设置为重要通知，则会在弹窗显示</span>
            </td>
        </tr>
        <tr>
            <th width="100">发布<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?></th>
            <td>
                <button class="btn btn-success" type="button"  onclick="chooseFloor1()">
                    点击选择发布<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>
                </button>
            </td>
        </tr>
        <tr>
            <th width="100">状态</th>
            <td>
                <label><input  value="1" name="status" type="radio" <if condition="$news_info['status'] eq 1"> checked="checked" </if> />&nbsp;&nbsp;显示</label>
                &nbsp;&nbsp;&nbsp;
                <label><input  value="0" name="status" type="radio" <if condition="$news_info['status'] neq 1"> checked="checked" </if> />&nbsp;&nbsp;关闭</label>
            </td>
        </tr>
    </table>
    <div class=" hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script src="./static/kindeditor/kindeditor.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    $(".ant-modal-root",parent.document).css("pointer-events","none")
</script>
<script type="text/javascript">
    KindEditor.ready(function(K){
        kind_editor = K.create("#description",{
            width:'100%',
            height:'400px',
            resizeType : 1,
            pasteType : 1,
            allowPreviewEmoticons:false,
            allowImageUpload : true,
            filterMode: true,
            items : [
                'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
                'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
                'insertunorderedlist', '|', 'emoticons', 'image', 'wordpaste'
            ],
            emoticonsPath : './static/emoticons/',
            uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/news"
        });
    });

    var news_id = "{pigcms{$_GET['news_id']}";
    function chooseFloor1() {
        var url = "{pigcms{:U('House/choice_village')}";
        var href = url + '&news_id='+news_id;
        layer.open({
            type: 2,
            title:'选择发布'+"<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>",
            content: href,
            area: ['85%', '90%'],
            maxmin: false
        });
    }

    function reset_() {
        window.location.href = "{pigcms{:U('House/platform_news')}";
    }


    function close_all_page(info) {
        layer.closeAll();
    }
</script>

<include file="Public:footer"/>