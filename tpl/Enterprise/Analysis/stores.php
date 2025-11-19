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
                    <div method="get" action="{pigcms{:U('Analysis/stores')}">
                        <input type="text" name="keyword" id="keyword" class="input-text"
                               value="{pigcms{$_GET['keyword']}" placeholder="请输入关键字筛选"/>
                        <button class="btn btn-success" id="search">{pigcms{:L_('查询')}</button>&nbsp;&nbsp;&nbsp;
                    </div>
                    <div id="shopList" class="grid-view">

                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("店铺ID")}</th>
                                <th>{pigcms{:L_("店铺名称")}</th>
                                <th class="text-middle">{pigcms{:L_("操作")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <volist name="stores" id="s">
                                <tr>
                                    <td>{pigcms{$s.store_id}</td>
                                    <td>{pigcms{$s.name}</td>
                                    <td class="am-text-middle">
                                       <input data-store-name="{pigcms{$s.name}" value="{pigcms{$s.store_id}" type="radio" name="store_id"/>
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
    $('#search').on('click',function(){
        location.href = "{pigcms{:U('Analysis/stores')}" + '&keyword='+$('#keyword').val();
    })
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
