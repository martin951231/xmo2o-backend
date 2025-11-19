<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{pigcms{:L_("微信公众平台源码,微信机器人源码,微信自动回复源码 PigCms多用户微信营销系统")}</title>
    <meta http-equiv="MSThemeCompatible" content="Yes" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/style_2_common.css" />
    <link href="{pigcms{$static_path}css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/cymain.css" />
    <script src="{pigcms{$static_path}js/common.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}js/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
    <style>
        body {
            line-height: 180%;
        }

        ul.modules li {
            padding: 4px 10px;
            margin: 4px;
            background: #efefef;
            float: left;
            width: 27%;
        }

        ul.modules li div.mleft {
            float: left;
            width: 40%
        }

        ul.modules li div.mright {
            float: right;
            width: 55%;
            text-align: right;
        }



        .button {
            margin-left: 15px;
            padding: 6px 8px;
            cursor: pointer;
            display: inline-block;
            text-align: center;
            line-height: 1;
            letter-spacing: 2px;
            font-family: Tahoma, Arial/9!important;
            width: auto;
            overflow: visible;
            color: #333;
            border: solid 1px #999;
            border-radius: 5px;
            background: #DDD;
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='#DDDDDD');
            background: linear-gradient(top, #FFF, #DDD);
            background: -moz-linear-gradient(top, #FFF, #DDD);
            background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#FFF), to(#DDD));
            /* text-shadow: 0px 1px 1px rgba(255, 255, 255, 1); */
            box-shadow: 0 1px 0 rgba(255, 255, 255, .7), 0 -1px 0 rgba(0, 0, 0, .09);
            -moz-transition: -moz-box-shadow linear .2s;
            -webkit-transition: -webkit-box-shadow linear .2s;
            transition: box-shadow linear .2s;
        }
        .input-text {
            border: 1px solid #A7A6AA;
            line-height: 18px;
            height: 18px;
            margin: 0 5px 0 0;
            padding: 2px 0 2px 5px;
            border: 1px solid #d0d0d0;
            font-family: Verdana, Geneva, sans-serif,"{pigcms{:L_("微软雅黑")}";
            font-size: 12px;
            width: 250px;
        }
        input, select, textarea, .textarea_style {
            border: 1px solid #dcdcdc;
            vertical-align: middle;
        }
    </style>
</head>
<body style="background: #fff; padding: 20px 20px;">
<table class="search_table" width="100%">
    <tr>
        <td>
            <form action="{pigcms{:U('Shop/miaomaiyunErpGoodsLists')}" method="get">
                <input type="hidden" name="c" value="Shop"/>
                <input type="hidden" name="a" value="miaomaiyunErpGoodsLists"/>
                <input type="hidden" name="store_id" value="{pigcms{$Think.get.store_id}"/>
                <input type="hidden" name="is_multi" value="{pigcms{$Think.get.is_multi}"/>
                {pigcms{:L_("搜索")}:

                <select name="search_type">
                    <option value="goods_title" <if condition="($_GET['search_type'] eq 'goods_title')">selected</if>>商品名称</option>
                    <option value="barcode"  <if condition="($_GET['search_type'] eq 'barcode')">selected</if>>商品条码</option>
                </select>
                <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder=""/>
                <input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　
                <if condition="$_GET['is_multi'] eq 1">
                <input type="button" value="{pigcms{:L_('批量选中')}" onclick="insertErpGoods()"  class="button"/>　
                </if>
            </form>
        </td>
    </tr>
</table>
<table class="ListProduct" width="100%">
    <thead>
    <tr>
        <if condition="$_GET['is_multi'] eq 1">
            <th><input data-select="0" type="checkbox" onclick="selectAll($(this))"/></th>
        </if>
        <th>{pigcms{:L_("商品编号")}</th>
        <th>{pigcms{:L_("商品名称")}</th>
        <th>{pigcms{:L_("商品条码")}</th>
        <th>{pigcms{:L_("存货类型")}</th>
        <th>{pigcms{:L_("所属分类")}</th>
        <th>{pigcms{:L_("销售单位")}</th>
        <th>{pigcms{:L_("实际零售价")}({pigcms{$config['Currency_txt']})</th>
        <if condition="$_GET['is_multi'] neq 1">
            <th>{pigcms{:L_("操作")} </th>
        </if>
    </tr>
    </thead>
    <tbody>
    <if condition="$erpGoods">
        <volist name="erpGoods" id="vo">
            <tr>
                <if condition="$_GET['is_multi'] eq 1">
                    <td><input type="checkbox" class="g_m_id" name="g_m_id[]" value="{pigcms{$vo.g_m_id}"/></td>
                </if>
                <td>{pigcms{$vo.g_m_id}</td>
                <td>{pigcms{$vo.goods_title}</td>
                <td>{pigcms{$vo.barcode}</td>
                <td>{pigcms{$vo.spec_name}</td>
                <td>{pigcms{$vo.category_name}</td>
                <td>{pigcms{$vo.unit_name}</td>
                <td>{pigcms{$vo.retail_price|getFormatNumber}</td>
                <if condition="$_GET['is_multi'] neq 1">
                <td class="textcenter">
                    <a href="javascript:void(0);" onclick="returnHomepage('{pigcms{$vo.g_m_id}')">{pigcms{:L_("选中")}</a>
                </td>
                </if>
            </tr>
        </volist>
        <else />
        <tr><td colspan="9" style="text-align: center">{pigcms{:L_("暂未搜索到您要的商品")}</td></tr>
    </if>
    </tbody>
</table>
<div class="footactions" style="padding-left: 10px">
    <div class="pages">{pigcms{$page}</div>
</div>
<script>
    var fun = art.dialog.data('erpFun');
    // "返回数据到主页面")}
    function returnHomepage(val){
        fun(val);
        setTimeout("art.dialog.close()", 100);
    }

    //"预览大图")}
    $('.view_msg').click(function () {
        window.top.art.preview({
            padding: 0,
            title: '大图',
            url: $(this).attr('src'),
            width: 600,
            height: 400,
            click: false,
            lock: true
        });
    });

    function selectAll(obj) {
        var is_select = obj.data('select');
        var after_select = is_select == 1 ? 0 : 1;

        if (after_select) {
            $('.g_m_id').attr('checked', true);
        } else {
            $('.g_m_id').attr('checked', false);
        }
        obj.data('select', after_select);
    }

    function insertErpGoods() {
        var g_m_id = [];
        var checked_obj = $('.g_m_id:checked');
        checked_obj.each(function (i) {
            g_m_id.push(checked_obj.eq(i).val());
        })
        if (g_m_id.length < 1) {
            alert('请选择一条记录');
        } else {
            fun(g_m_id);
            setTimeout("art.dialog.close()", 100);
        }
    }
</script>
</body>
</html>