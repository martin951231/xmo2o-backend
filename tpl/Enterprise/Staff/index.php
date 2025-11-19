<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {
                    display: none;
                }
                .btn-success{
                    margin: 8px 10px;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">

                        <form action="{pigcms{:U('Staff/index')}" method="get" style="float:left;margin-left:10px;" id="staff_search">
                            <input type="hidden" name="c" value="Staff"/>
                            <input type="hidden" name="a" value="index"/>
                            {pigcms{:L_("筛选")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                            <select name="searchtype">
                                <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("手机号码")}</option>
                                <option value="job_number" <if condition="$_GET['searchtype'] eq 'job_number'">selected="selected"</if>>{pigcms{:L_("卡号/工号/ID")}</option>
                                <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("姓名")}</option>
                                <option value="remark" <if condition="$_GET['searchtype'] eq 'remark'">selected="selected"</if>>{pigcms{:L_("备注")}</option>
                                <option value="card_id" <if condition="$_GET['searchtype'] eq 'card_id'">selected="selected"</if>>{pigcms{:L_("实体卡ID")}</option>
                                <option value="group_name" <if condition="$_GET['searchtype'] eq 'group_name'">selected="selected"</if>>{pigcms{:L_("员工组别")}</option>
                            </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <button class="" >{pigcms{:L_("查询")}</button>
                        </form>
                    <div style="display: inline-block;float: right">
                        <button class="btn btn-success" onclick="add_staff()">{pigcms{:L_("新增会员")}</button>
                        <button class="btn btn-success" onclick="import_staff()">{pigcms{:L_("批量新增")}</button>
                        <button class="btn btn-success" onclick="staff_group()">{pigcms{:L_("分组管理")}</button>
                        <button class="btn btn-success" onclick="go_balance()">{pigcms{:L_("余额操作记录")}</button>
                        <button class="btn btn-success" onclick="export_excel()">{pigcms{:L_("导出会员")}</button>
                    </div>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("卡号/工号/ID")}</th>
                                <th>{pigcms{:L_("姓名")}</th>
                                <th>{pigcms{:L_("UID")}</th>
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
                                    <td>{pigcms{$s.staff_number}</td>
                                    <td>{pigcms{$s.name}</td>
                                    <td>
                                        <if condition="$s['job_number'] EQ 0">
                                            <a title="点击扫码绑定平台用户"
                                               href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=qiyeStaff&id={pigcms{$s['staff_id']}&img=1"
                                               class="see_qrcode" data-name="{pigcms{$s.name}">扫码绑定用户</a>
                                            <else/>
                                            {pigcms{$s.job_number}
                                        </if>
                                    </td>
                                    <td>{pigcms{$s.phone}</td>
                                    <td class="text-middle">{pigcms{$s.money} <gt name="s.unpaid_money" value="0"><font color="red">({pigcms{$s.unpaid_money}未到账)</font></gt></td>
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
                                            <a href="javascript:void(0)" onclick="show_staff_money({pigcms{$s.staff_id})">{pigcms{:L_("余额管理")}</a>
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
    $('.see_qrcode').click(function(){
        layer.open({
            title: '{pigcms{:L_("扫码绑定员工账户")}<font style="color: red;">（姓名：' + $(this).attr('data-name') + '）</font>',
            area:['430px','483px'],
            type: 2,
            shade: 0.4,
            content:$(this).attr('href'),
            cancel: function () {
                art.dialog.open.origin.location.reload();
            }
        });
        return false;
    });

    function import_staff(){
        art.dialog.open(
            "{pigcms{:U('Staff/importStaff')}",
            {
                lock:true,
                title:'{pigcms{:L_("批量新增")}',
                width:800,
                height:500,
                yesText:'关闭',
                background: '#000',
                opacity: 0.45,
                id:'fitment',
                close: function(){
                    location.reload(true);
                }
            });
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
                    staff_number: win.$('#staff_number').val(),
                    name: win.$('#name').val(),
                    phone: win.$('#phone').val(),
                    card_id: win.$('#card_id').val(),
                    group_id: win.$('#group_id option:selected').val(),
                    remark: win.$('#remark').val(),
                    status: win.$('#status option:selected').val()
                }
                if (data.staff_number == '') {
                    alert('卡号/工号/ID不能为空')
                    win.$('#staff_number').focus();
                    return false;
                }
                if (data.name == '') {
                    alert('姓名不能为空')
                    win.$('#name').focus();
                    return false;
                }
                var close_dialog = true;
                $.ajaxSettings.async = false;
                $.post("{pigcms{:U('Staff/add')}", data, function (rs) {
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
                    staff_number: win.$('#staff_number').val(),
                    name: win.$('#name').val(),
                    card_id: win.$('#card_id').val(),
                    group_id: win.$('#group_id option:selected').val(),
                    remark: win.$('#remark').val(),
                    status: win.$('#status option:selected').val()
                }
                if (data.staff_number == '') {
                    alert('卡号/工号/ID不能为空')
                    win.$('#staff_number').focus();
                    return false;
                }
                if (data.name == '') {
                    alert('姓名不能为空')
                    win.$('#name').focus();
                    return false;
                }
                var close_dialog = true;
                $.ajaxSettings.async = false;
                $.post("{pigcms{:U('Staff/edit')}", data, function (rs) {
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

    function staff_group(){
        window.location.href = "{pigcms{:U('Staff/group')}";
    }

    function go_balance(){
        window.location.href = "{pigcms{:U('Analysis/balance')}";
    }

    function export_excel(){
        var url_params = $('#staff_search').serialize();
        url_params = url_params.replace('c=Staff&a=index','');
        window.open("{pigcms{:U('Staff/export')}" + url_params);
    }

    function show_staff_money(staff_id){
        art.dialog.open("{pigcms{:U('Staff/showMoney')}" + '&staff_id=' + staff_id, {
            init: function () {
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle', iframe);
            },
            id: 'show_staff_money',
            title: '余额管理',
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
                $.post("{pigcms{:U('Staff/applayMoneyBatch')}", data, function (rs) {
                    if (rs.error_no != 0) {
                        alert(rs.error_msg)
                    } else {
                        art.dialog.open.origin.location.reload();
                    }
                })
                return false;
            },
            cancel: true,
        });
    }

</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
