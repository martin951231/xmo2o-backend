<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {
                    display: none;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
				
					<div class="col-xs-10">
                    <form action="{pigcms{:U('Merchant/index')}" method="get">
						<input type="hidden" name="c" value="Merchant"/>
						<input type="hidden" name="a" value="index"/>
						<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
					<input type="submit" value="查询" class="button"/>　
					</form>
					</div>
					<button class="btn btn-success" onclick="add_merchant()">{pigcms{:L_("增加可消费商家")}</button>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("商家名称")}</th>
                                <th>{pigcms{:L_("联系电话")}</th>
                                <th>{pigcms{:L_("所在区域")}</th>
                                <th class="text-middle">{pigcms{:L_("店铺数量")}</th>
                                <th class="text-middle">{pigcms{:L_("操作")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <volist name="merchants" id="s">
                                <tr>
                                    <td>{pigcms{$s.name}</td>
                                    <td>{pigcms{$s.phone}</td>
                                    <td>{pigcms{$s.address}</td>
                                    <td class="text-middle">{pigcms{$s.store_num}</td>
                                    <td class="am-text-middle">
                                        <div class="tpl-table-black-operation">
                                            <a title="{pigcms{:L_(' 删除')}" class="item-delete tpl-table-black-operation-del"
                                            href="{pigcms{:U('Merchant/del',array('id'=>$s['id']))}">{pigcms{:L_("删除")}</a>
                                        </div>
                                    </td>
                                </tr>
                            </volist>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    choose_provincess="/admin.php?g=System&c=Area&a=ajax_province";choose_cityss="/admin.php?g=System&c=Area&a=ajax_city";choose_area="/admin.php?g=System&c=Area&a=ajax_area";
    function add_merchant() {
        art.dialog.open("{pigcms{:U('Merchant/add')}", {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'add_merchant',
            title: '增加可消费商家',
            padding: 0,
            width: '900px',
            height: '560px',
            lock: true,
            resize: false,
            button: null,
            fixed: false,
            close: null,
            left: '50%',
            top: '38.2%',
            cancel: function(){
                art.dialog.open.origin.location.reload();
            }
        });
    }

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
