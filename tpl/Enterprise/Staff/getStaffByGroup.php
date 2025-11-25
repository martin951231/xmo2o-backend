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
                    <button class="btn btn-success" onclick="all_select(this)" data-value="1">{pigcms{:L_("全选")}</button>
                    <button class="btn btn-success" onclick="balance_operate_by_batch()">{pigcms{:L_("余额批量管理")}</button>
                    <button class="btn btn-success" onclick="balance_clear()">{pigcms{:L_("全部人员余额清零")}</button>
                    <button class="btn btn-success" onclick="window.history.go(-1)">{pigcms{:L_("返回上一级")}</button>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("选择")}</th>
                                <th>{pigcms{:L_("卡号/工号/ID")}</th>
                                <th>{pigcms{:L_("姓名")}</th>
                                <th>{pigcms{:L_("手机号码")}</th>
                                <th class="text-middle">{pigcms{:L_("预存余额")}</th>
                                <th class="text-middle">{pigcms{:L_("实体卡ID")}</th>
                                <th class="text-middle">{pigcms{:L_("员工组别")}</th>
                                <th class="text-middle">{pigcms{:L_("备注")}</th>
                                <th class="text-middle">{pigcms{:L_("录入时间")}</th>
                                <th class="text-middle">{pigcms{:L_("状态")}</th>
                                <th class="text-middle">{pigcms{:L_("操作")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <volist name="staffs" id="s">
                                <tr>
                                    <td><input type="checkbox" class="staff_checkbox" name="staff_ids[]" value="{pigcms{$s.staff_id}"/></td>
                                    <td>{pigcms{$s.job_number}</td>
                                    <td>{pigcms{$s.name}</td>
                                    <td>{pigcms{$s.phone}</td>
                                    <td class="text-middle">{pigcms{$s.money}</td>
                                    <td class="text-middle">{pigcms{$s.card_id}</td>
                                    <td class="text-middle">{pigcms{$s.group_name}</td>
                                    <td class="text-middle">{pigcms{$s.remark}</td>
                                    <td class="text-middle">
                                        <if condition="$s['create_time'] gt 0">{pigcms{$s.create_time|date='Y-m-d
                                            H:i:s',###}
                                        </if>
                                    </td>
                                    <td class="text-middle">
                                        <if condition="$s['status'] eq 1">正常
                                            <else/>
                                            禁用
                                        </if>
                                    </td>
                                    <td class="am-text-middle">
                                        <div class="tpl-table-black-operation">
                                            <a href="javascript:void(0)" onclick="edit_staff({pigcms{$s.staff_id})">{pigcms{:L_("查看详情")}</a>
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
    function all_select(obj) {
        var v = $(obj).attr('data-value');
        if (v == 1) {
            $('.staff_checkbox').attr('checked', 'checked');
            $(obj).attr('data-value', 0).html('全不选');
        } else {
            $('.staff_checkbox').attr('checked', false);
            $(obj).attr('data-value', 1).html('全选');
        }
    }

    function balance_clear(){
        var checked_staff = $('.staff_checkbox:checked');
        var len = checked_staff.length;
        if(len < 1){
            alert('请至少选择一个员工');
        }else{
            var ids = [];
            for (var i = 0; i < len; i++) {
                ids.push(checked_staff[i].value)
            }
            var content = '选择人数：'+len+'人<br/><br/>是否确认全部清零?<br/><br/><input type="password" id="password" value="" placeholder="请输入操作密码"/>';
            art.dialog({
                content: content,
                title: '全部人员余额清零',
                width: '300px',
                cancelVal: '关闭',
                ok: function () {
                    var passwd = $('#password').val();
                    if (passwd == '') {
                        $('#password').focus();
                        return false;
                    }
                    var data = {
                        'password': passwd,
                        'staff_id': ids
                    }
                    $.post("{pigcms{:U('Staff/clearBalance')}", data, function (rs) {
                        if (rs.error_no != 0) {
                            alert(rs.error_msg);
                        }
                    })
                    art.dialog.open.origin.location.reload();
                },
                cancel: true
            });
        }
    }

    function balance_operate_by_batch() {
        var checked_staff = $('.staff_checkbox:checked');
        var len = checked_staff.length;
        if (len < 1) {
            alert('请至少选择一个员工');
        } else {
            var ids = '';
            for (var i = 0; i < len; i++) {
                if (ids == '') {
                    ids = checked_staff[i].value;
                } else {
                    ids = ids + "," + checked_staff[i].value;
                }
            }
            art.dialog.open("{pigcms{:U('Staff/mBalanceByBatch')}" + "&ids=" + ids, {
                init: function () {
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle', iframe);
                },
                id: 'balance_operate_by_batch',
                title: '余额批量管理',
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
                        staff_ids: ids,
                        operate: win.$('#operate').val(),
                        money: win.$('#money').val(),
                        remark: win.$('#remark').val(),
                        password: win.$('#password').val()
                    }

                    if (data.operate == "0") {
                        alert('请选择操作')
                        win.$('#operate').focus();
                        return false;
                    }
                    if (data.money <= 0) {
                        alert('金额必须大于0')
                        win.$('#money').focus();
                        return false;
                    }
                    if (data.remark == '') {
                        alert('备注不能为空')
                        win.$('#remark').focus();
                        return false;
                    }
                    if (data.password == "") {
                        alert('请输入操作密码')
                        win.$('#password').focus();
                        return false;
                    }

                    var close_dialog = true;
                    $.ajaxSettings.async = false;
                    $.post("{pigcms{:U('Staff/applayMoneyBatch')}", data, function (rs) {
                        if (rs.error_no != 0) {
                            alert(rs.error_msg)
                            close_dialog = false;
                        } else {
                            art.dialog.open.origin.location.reload();
                        }
                    })
                    $.ajaxSettings.async = true;
                    return close_dialog;
                },
                cancel: true,
            });
        }
    }

    function add_staff() {
        art.dialog.open("{pigcms{:U('Staff/add')}", {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'add_staff',
            title: '添加会员',
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
                    job_number: win.$('#job_number').val(),
                    name: win.$('#name').val(),
                    phone: win.$('#phone').val(),
                    card_id: win.$('#card_id').val(),
                    group_id: win.$('#group_id option:selected').val(),
                    remark: win.$('#remark').val(),
                    status: win.$('#status option:selected').val()
                }
                if (data.job_number == '') {
                    alert('卡号/工号/ID不能为空')
                    win.$('#job_number').focus();
                    return false;
                }
                if (data.name == '') {
                    alert('姓名不能为空')
                    win.$('#name').focus();
                    return false;
                }
                $.post("{pigcms{:U('Staff/add')}", data, function (rs) {
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

    function edit_staff(staff_id) {
        art.dialog.open("{pigcms{:U('Staff/edit')}" + '&staff_id=' + staff_id, {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'edit_staff',
            title: '查看详情',
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
                    staff_id: staff_id,
                    job_number: win.$('#job_number').val(),
                    name: win.$('#name').val(),
                    card_id: win.$('#card_id').val(),
                    group_id: win.$('#group_id option:selected').val(),
                    remark: win.$('#remark').val(),
                    status: win.$('#status option:selected').val()
                }
                if (data.job_number == '') {
                    alert('卡号/工号/ID不能为空')
                    win.$('#job_number').focus();
                    return false;
                }
                if (data.name == '') {
                    alert('姓名不能为空')
                    win.$('#name').focus();
                    return false;
                }
                $.post("{pigcms{:U('Staff/edit')}", data, function (rs) {
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

    function staff_group(){
        window.location.href = "{pigcms{:U('Staff/group')}";
    }

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
