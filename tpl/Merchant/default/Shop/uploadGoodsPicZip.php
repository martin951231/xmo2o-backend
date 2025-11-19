<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <title>{pigcms{$config.site_name}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
    <style>
        a:hover,a:visited{color:#666;}
    </style>
</head>
<body>
<form id="myform" method="post" action="{pigcms{:U('Shop/uploadGoodsPicZip')}" enctype="multipart/form-data">
    <input type="hidden" name="store_id" value="{pigcms{$storeId}"/>
    <input type="hidden" name="act" value="{pigcms{$act}"/>
    <table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
        <tr>
            <th width="80">压缩包</th>
            <td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="点击上传图片压缩包" accept="application/zip"/></td>
        </tr>
        <tr>
            <th width="80">上传要求</th>
            <td>
                <span style="color: green;">
                    1.图片要求：480*480以上，正方形图片，大小不得超过5M,格式为.jpg/.png<br/>
                    2.请以商品名称命名图片，同商品名称将使用同一张图片，以最后上传的为准<br/>
                    3.请将所有图片放入一个文件夹，并压缩为.zip，最多不能超过200张，最大不超过20M<br/>
                    4.以上规则有任意一项错误，图片将上传失败
                </span>
            </td>
        </tr>
    </table>
    <div class="clearfix form-actions">
        <div class="col-md-offset-3 col-md-9">
            <button class="btn btn-info" type="submit">
                <i class="ace-icon fa fa-check bigger-110"></i>
                提交
            </button>
        </div>
    </div>
</form>
</body>
</html>