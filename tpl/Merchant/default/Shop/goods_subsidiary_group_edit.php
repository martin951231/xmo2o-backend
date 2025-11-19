<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul class="nav nav-tabs" id="myTab">
                            <li>
                                <a class="btn btn-success"
                                   href="{pigcms{:U('Shop/goods_subsidiary_group',array('store_id' => $_GET['store_id']))}">{pigcms{:L_("返回分组列表")}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="grid-view">
                            <form enctype="multipart/form-data" class="form-horizontal" method="post">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("分组名称")}</label></label>
                                    <input name="id" type="hidden" value="{pigcms{$subsidiary_group.id}"/>
                                    <input name="store_id" type="hidden" value="{pigcms{$subsidiary_group.store_id}"/>
                                    <input class="col-sm-2" size="20" name="name" id="sort_name" type="text" value="{pigcms{$subsidiary_group.name}"/>
                                    <span class="form_tips">{pigcms{:L_("必填")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort">{pigcms{:L_("用户端分组名称")}</label></label>
                                    <input class="col-sm-1" size="10" name="t_name" id="sort" type="text" value="{pigcms{$subsidiary_group.t_name}"/>
                                    <span class="form_tips">{pigcms{:L_("必填 用户看到的分组名称")}</span>
                                </div>
                                <div class="clearfix form-actions">
                                    <div class="col-md-offset-3 col-md-9">
                                        <button class="btn btn-info" type="submit">
                                            <i class="ace-icon fa fa-check bigger-110"></i>
                                            {pigcms{:L_("保存")}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script>
    $(function () {
        $('form.form-horizontal').submit(function () {
            $(this).find('button[type="submit"]').html('{pigcms{:L_("保存中")}...').prop('disabled', true);
        });
    }
</script>
<include file="Public:footer"/>
