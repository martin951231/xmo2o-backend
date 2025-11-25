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
                    <button class="btn btn-success" onclick="add_staff_group()">{pigcms{:L_("新建分组")}</button>
                    <button class="btn btn-success" onclick="window.history.go(-1)">{pigcms{:L_("返回上一级")}</button>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th class="text-middle">{pigcms{:L_("组别ID")}</th>
                                <th class="text-middle">{pigcms{:L_("组别名称")}</th>
                                <th class="text-middle">{pigcms{:L_("组别备注")}</th>
                                <th class="text-middle">{pigcms{:L_("组员数量")}</th>
                                <th class="text-middle">{pigcms{:L_("操作")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <volist name="groups" id="g">
                                <tr>
                                    <td class="text-middle">{pigcms{$g.group_id}</td>
                                    <td class="text-middle">{pigcms{$g.name}</td>
                                    <td class="text-middle">{pigcms{$g.remark}</td>
                                    <td class="text-middle">{pigcms{$g.staff_num|intval}</td>
                                    <td class="am-text-middle">
                                        <div class="tpl-table-black-operation">
                                            <a href="javascript:void(0)" onclick="edit_staff_group({pigcms{$g.group_id})">{pigcms{:L_("编辑分组")}</a>
                                            <a href="{pigcms{:U('Staff/getStaffByGroup',array('group_id'=>$g['group_id']))}">{pigcms{:L_("组员管理")}</a>
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
    function add_staff_group() {
        art.dialog.open("{pigcms{:U('Staff/addGroup')}", {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'add_staff_group',
            title: '新建分组',
            padding: 0,
            width: '800px',
            lock: true,
            resize: false,
            button: null,
            fixed: false,
            close: null,
            left: '50%',
            top: '38.2%',
            ok: function () {
                var win = this.iframe.contentWindow;
                var data = {
                    name: win.$('#name').val(),
                    remark: win.$('#remark').val()
                }
                if (data.name == '') {
                    alert('组别名称不能为空')
                    win.$('#name').focus();
                    return false;
                }
                if (data.remark == '') {
                    alert('组别备注不能为空')
                    win.$('#remark').focus();
                    return false;
                }
                $.post("{pigcms{:U('Staff/addGroup')}", data, function (rs) {
                    if (rs.error_no != 0) {
                        alert(rs.error_msg)
                        return false;
                    } else {
                        art.dialog.open.origin.location.reload();
                    }
                })
            },
            cancel: true,
        });
    }

    function edit_staff_group(group_id) {
        art.dialog.open("{pigcms{:U('Staff/editGroup')}" + '&group_id=' + group_id, {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'edit_staff_group',
            title: '编辑分组',
            padding: 0,
            width: '800px',
            lock: true,
            resize: false,
            button: null,
            fixed: false,
            close: null,
            left: '50%',
            top: '38.2%',
            ok: function () {
                var win = this.iframe.contentWindow;
                var data = {
                    group_id: group_id,
                    name: win.$('#name').val(),
                    remark: win.$('#remark').val()
                }
                if (data.name == '') {
                    alert('组别名称不能为空')
                    win.$('#name').focus();
                    return false;
                }
                if (data.remark == '') {
                    alert('组别备注不能为空')
                    win.$('#remark').focus();
                    return false;
                }
                $.post("{pigcms{:U('Staff/editGroup')}", data, function (rs) {
                    if (rs.error_no != 0) {
                        alert(rs.error_msg)
                        return false;
                    } else {
                        art.dialog.open.origin.location.reload();
                    }
                })
            },
            cancel: true,
        });
    }

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
