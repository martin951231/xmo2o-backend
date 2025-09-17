<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Merchant/contract')}">已签署合同商家</a>|
            <a href="{pigcms{:U('Merchant/notContract')}" class="on">未签署合同商家</a>|
            <a href="{pigcms{:U('Merchant/contractSetting')}">合同配置</a>|
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <table class="search_table" width="100%">
                    <tr>
                        <td style="width:60%;">
                            <form action="{pigcms{:U('Merchant/notContract')}" method="get">
                                <input type="hidden" name="c" value="Merchant"/>
                                <input type="hidden" name="a" value="notContract"/>
                                筛选:
                                <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                                <select name="searchtype">
                                    <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商家名称</option>
                                    <option value="account" <if condition="$_GET['searchtype'] eq 'account'">selected="selected"</if>>商家帐号</option>
                                    <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
                                    <option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
                                </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="submit" value="查询" class="button"/>
                            </form>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>商家名称</th>
                    <th>联系电话</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="$merchant_list">
                    <volist name="merchant_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.name}</td>
                            <td>{pigcms{$vo.phone}</td>
                            <td class="textcenter">
                                <if condition="$vo.is_his eq 1">
                                <a href="javascript:void(0);" onclick="window.top.iframeDrawer({
                                    url: '/v20/public/platform/#/merchant/platform/HistoricalContracts?mer_id={pigcms{$vo['mer_id']}',
                                    title: '历史合同',
                                    id: '1',
                                    placement: 'right',
                                    visible: true,
                                    width: '25%',
                                    zIndex: 999,
                                    drawerIframeStyle:{//iframe样式
                                        width: '100%',
                                        height: '88vh'
                                    }
                                });">历史合同</a>
                                </if>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<include file="Public:footer"/>