<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <title>{pigcms{$config.site_name} - {pigcms{:L_("批量新增员工")}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
    <style>
        a:hover,a:visited{color:#666;}
    </style>
</head>
<body>
<form id="myform" method="post" action="{pigcms{:U('Staff/importStaff')}" enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
        <tr>
            <th width="80">Excel导入</th>
            <td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" accept="application/vnd.ms-excel"/></td>
        </tr>
        <tr>
            <th width="80">上传要求</th>
            <td>
                <span style="color: green;">
                    请下载批量新增商品模板并严格按照批量新增填写指南要求填写
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