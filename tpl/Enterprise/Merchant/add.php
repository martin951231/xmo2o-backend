<include file="Public:lite_header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {
                    display: none;
                }

                .add_btn {
                    padding: 5px 10px;
                    background: #0a8ddf;
                    color: #ffffff
                }

                .cancel_btn {
                    padding: 5px 10px;
                    background: #9f9f9f;
                    color: #ffffff;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
                   <form action="{pigcms{:U('Merchant/add')}" method="get">
						<input type="hidden" name="c" value="Merchant"/>
						<input type="hidden" name="a" value="add"/>
						<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                        <input type="text" name="keyword" id="keyword" class="input-text"
                               value="{pigcms{$_GET['keyword']}" placeholder="请输入关键字筛选"/>
                        <button type="submit" class="btn btn-success" id="search">{pigcms{:L_('查询')}</button>&nbsp;&nbsp;&nbsp;
                    </form>
                    <div id="shopList" class="grid-view">

                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("商家ID")}</th>
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
                                    <td>{pigcms{$s.mer_id}</td>
                                    <td>{pigcms{$s.name}</td>
                                    <td>{pigcms{$s.phone}</td>
                                    <td>{pigcms{$s.address}</td>
                                    <td class="text-middle">{pigcms{$s.store_num}</td>
                                    <td class="am-text-middle">
                                        <if condition="$s['has_choose'] eq '1'">
                                            <span class="cancel_btn" data_mer_id="{pigcms{$s.mer_id}">取消</span>
                                            <else/>
                                            <span class="add_btn" data_mer_id="{pigcms{$s.mer_id}">添加</span>
                                        </if>

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
	var choose_provincess="/admin.php?g=System&c=Area&a=ajax_province";choose_cityss="/admin.php?g=System&c=Area&a=ajax_city";choose_area="/admin.php?g=System&c=Area&a=ajax_area";
    $('.add_btn').live('click', function () {
        var obj = $(this);
        var mer_id = obj.attr('data_mer_id');
        $.post("{pigcms{:U('Merchant/modify')}", {mer_id: mer_id, operate: "add"}, function (rs) {
            if (rs.error_no != 0) {
                alert(rs.error_msg)
                return false;
            } else {
                obj.removeClass('add_btn');
                obj.addClass('cancel_btn');
                obj.html('取消');
            }
        })
    })

    $('#search').on('click',function(){
        location.href = "{pigcms{:U('Merchant/add')}" + '&keyword='+$('#keyword').val();
    })

    $('.cancel_btn').live('click', function () {
        var obj = $(this);
        var mer_id = obj.attr('data_mer_id');
        $.post("{pigcms{:U('Merchant/modify')}", {mer_id: mer_id, operate: "del"}, function (rs) {
            if (rs.error_no != 0) {
                alert(rs.error_msg)
                return false;
            } else {
                obj.removeClass('cancel_btn');
                obj.addClass('add_btn');
                obj.html('添加');
            }
        })
    })
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
