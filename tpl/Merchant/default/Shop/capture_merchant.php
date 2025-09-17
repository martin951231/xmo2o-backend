<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{pigcms{:L_("选择商家店铺")}</title>
    <meta http-equiv="MSThemeCompatible" content="Yes" />
    <link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/style_2_common.css" />
    <link href="{pigcms{$static_path}css/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="/tpl/Merchant/default/static/css/cymain.css" />

    <script src="{pigcms{$static_path}js/common.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}js/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
    <style>
        body{line-height:180%;}
        ul.modules li{padding:4px 10px;margin:4px;background:#efefef;float:left;width:27%;}
        ul.modules li div.mleft{float:left;width:40%}
        ul.modules li div.mright{float:right;width:55%;text-align:right;}

        .btn_msg {
            border: none;
            display: inline-block;
            padding: 4px 11px;
            margin-bottom: 0;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            border-radius: 2px;
            background: #009adb;
        }

        .btn_qx {
            border: none;
            display: inline-block;
            padding: 4px 11px;
            margin-bottom: 0;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            border-radius: 2px;
            background: #999999;
        }
        .btn_fixed {
            background-color: white;
            width: 94%;
            line-height: 60px;
            text-align: -webkit-right;
            padding: 0 3% 0 0;
            bottom: 0;
        }
        TABLE.ListProduct TBODY TD {
            font-size: 14px;
        }
    </style>
</head>
<body style="background:#fff;padding:20px 20px;">
    <form action="#" method="post">
        <input type="text" placeholder="{pigcms{:L_("请输入名称搜索词")}" value="{pigcms{$keyword}" class="px" name="name">
        <button class="btnGrayS" style="height: 29px;">{pigcms{:L_("搜索")}</button>
    </form>
    <div style="padding: 5px 5px 0;">
    <h4>{pigcms{:L_("商户列表")}</h4>
    </div>
    <table class="ListProduct" border="0" cellSpacing="0" cellPadding="0" width="100%">
        <thead>
        <tr>
            <th>{pigcms{:L_("商家名称")}</th>
            <th style=" width:80px;">
                {pigcms{:L_("操作")}
                <input type="checkbox" id='checkall'/>
            </th>
        </tr>
        </thead>
        <volist name="merchant_list" id="vo" key='k'>
            <tr>
                <td>{pigcms{$vo.name}</td>
                <td class="norightborder">
                    <label>
                        <input type="checkbox" name="mer_id" value="{pigcms{$vo.mer_id}" data-k="{pigcms{$first+$k}">{pigcms{:L_("选中")}
                    </label>
                </td>
            </tr>
        </volist>
    </table>
    <div class="footactions" style="padding-left:10px;margin-bottom: 60px;">
        <div class="pages">
            {pigcms{$page_bar}
        </div>
    </div>


    <div class="btn_fixed">
        <input type="button" class="btn btn-primary btn_msg" value="{pigcms{:L_('确认')}" onclick="fun()">
        &nbsp;&nbsp;&nbsp;&nbsp;
        <input type="reset" value="{pigcms{:L_('取消')}" class="button btn_qx" onclick="fun()"/>
    </div>
    <script>
        $('.modal-header .close').live('click',function(){
            setTimeout("art.dialog.close()", 100 );
        });
        $('.btn_qx').live('click',function(){
            setTimeout("art.dialog.close()", 100 );
        });
        var obj = art.dialog.data('dom');
        console.log('obj', obj);
        var dom=obj.parent().find('.tag_box');
        var mer_arr = [];
        if(dom.length>0){
            dom.find('.tag').each(function(){
                console.log('mer_id', $(this).data('mer_id')+'');
                mer_arr.push($(this).data('mer_id')+'');
            });
        }else{
            dom=$('<p>',{'class':'tag_box'});
            obj.after(dom);
        }
        var check_num=0;
        var count=$('input[name="mer_id"]').length;
        $('input[name="mer_id"]').each(function(index,val){
            console.log('val----', $(this).val());
            console.log('mer_arr----', mer_arr);
            if($.inArray($(this).val(), mer_arr)>-1){
                $(this).prop('checked',true);
                check_num++;
            }
        });
        /*{pigcms{:L_("是否全选中")}*/
        if(check_num==count){
            $("input#checkall").prop('checked',true);
        }
        // "单选")}
        $('input[name="mer_id"]').click(function(){
            var mer_id=$(this).val();
            if($(this).is(":checked")){
                var pobj=$(this).parents('tr');
                var text=pobj.find('td').eq(0).text();
                var k=$(this).data('k');
                dom.append($('<span>',{'class':'tag tag_'+mer_id,'data-mer_id':mer_id,'text':text,'data-k':k}).append($('<a>',
                    {'class':'del','text':'X'})));
                mer_arr.push($(this).val());
                check_num++;
            }else{
                dom.find('.tag_'+mer_id).remove();
                console.log(dom.find('.tag'+mer_id));
                mer_arr.splice($.inArray(mer_id, mer_arr), 1);
                check_num--;
            }
            if(check_num<count){
                $("input#checkall").prop('checked',false);
            }else{
                $("input#checkall").prop('checked',true);
            }
            var merchant_list = mer_arr.join(',');
            art.dialog.data('merchant_list',merchant_list);
        });
        /*{pigcms{:L_("批量选择")}*/
        $(document).on('click','#checkall',function(){
            var flag=this.checked;
            if(flag){
                check_num=count;
            }else{
                check_num=0;
            }
            console.log('mer_arr--', mer_arr);
            $('input[name="mer_id"]').each(function(){
                $(this).prop('checked',flag);
                var mer_id=$(this).val();
                var key=$.inArray(mer_id,mer_arr);
                if(key>=0){
                    if(!flag){
                        dom.find('.tag_'+mer_id).remove();
                        console.log(dom.find('.tag'+mer_id));
                        mer_arr.splice(key, 1);
                    }
                }else{
                    if(flag){
                        var pobj=$(this).parents('tr');
                        var text=pobj.find('td').eq(0).text();
                        var k=$(this).data('k');
                        dom.append($('<span>',{'class':'tag tag_'+mer_id,'data-mer_id':mer_id,'text':text,'data-k':k}).append($('<a>',
                            {'class':'del','text':'X'})));
                        mer_arr.push(mer_id);
                    }
                }
            });
        });
        // "确认后关闭弹窗")}
        function fun(){
            setTimeout("art.dialog.close()", 100 );
        }
    </script>
</body>
</html>