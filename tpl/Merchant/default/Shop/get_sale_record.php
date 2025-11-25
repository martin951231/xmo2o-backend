<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="MSThemeCompatible" content="Yes" />
    <link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/style_2_common.css" />
    <link href="/tpl/Merchant/default/static/css/style.css" rel="stylesheet" type="text/css" />
    <style>
        body{line-height:180%;}
        ul.modules li{padding:4px 10px;margin:4px;background:#efefef;float:left;width:27%;}
        ul.modules li div.mleft{float:left;width:40%}
        ul.modules li div.mright{float:right;width:55%;text-align:right;}

        .logo{
            display:inline-block;
            width:10px;
            height:10px;
        }

        .pagebar{
            float: right;
            margin-top: 10px;
        }

        .pagebar a.active {
            background-color: #6faed9;
            border-color: #6faed9;
            color: #fff;
            text-shadow: 0 -1px 0 rgba(0,0,0,.25);
            z-index: 2;
        }

        .pagebar a {
            border-radius: 0!important;
            color: #2283c5;
            background-color: #fafafa;
            margin: 0 -1px 0 0;
            position: relative;
            z-index: auto;
            border: 1px solid #ddd;
            padding: 5px 14px;
        }
        .pagebar a.active {
            background-color: #6faed9;
            border-color: #6faed9;
            color: #fff;
            text-shadow: 0 -1px 0 rgba(0,0,0,.25);
            z-index: 2;
        }
        .pagebar a.fetch_page:hover {
            background-color: #EFEFEF;
        }
        .pagebar input{
            border: 1px solid #ddd;
            height: 26px;
        }
    </style>
</head>
<body style="background:#fff;padding:20px 20px;">
<table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
    <thead>
    <tr>
        <th>{pigcms{:L_("日期")}</th>
        <th>{pigcms{:L_("活动本日销量")}</th>
        <th>{pigcms{:L_("活动本日带动净收入")}</th>
    </tr>
    </thead>
    <volist name="date_lists" id="vo">
        <tr>
            <td>{pigcms{$vo.datetime}</td>
            <td>{pigcms{$vo.sale_num}</td>
            <td>{pigcms{$vo.sale_price}</td>
        </tr>
    </volist>
</table>
</body>
</html>