<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <div class="form-group">
                <div id="upload_pic_box">
                    <volist name="msg['appendix_file']" id="pic">
                        <li class="upload_pic_li">
                            <img src="{pigcms{:replace_file_domain($pic)}" style="width:100px;height:100px;" onclick="view_file_img('{pigcms{:replace_file_domain($pic)}')">
                        </li>
                    </volist>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    //另开窗口查看图片
    function view_file_img(url) {
        window.open(url,"_blank");
    }
</script>
<include file="Publics:footer"/>
