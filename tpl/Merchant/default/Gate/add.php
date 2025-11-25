<include file="Public:header"/>
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-cloud"></i>
                <a href="{pigcms{:U('Hardware/index')}">{pigcms{:L_("微硬件")}</a>
            </li>
            <li class="active"><if condition="$_GET['id']">{pigcms{:L_("修改")}<else/>{pigcms{:L_("增加")}</if>{pigcms{:L_("闸机设备")}</li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div class="tab-content">
                        <div class="grid-view">
                            <form enctype="multipart/form-data" class="form-horizontal" method="post">
                                <input type="hidden" name="id" value="{pigcms{$device['id']}"/>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("闸机名称")}</label></label>
                                    <input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$device.name}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("闸机设备ID")}</label></label>
                                    <input class="col-sm-2" size="20" name="gid" id="gid" type="text" value="{pigcms{$device.gid}"/>
                                </div>

<!--                                <div class="form-group yilianyun_box">-->
<!--                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("密钥")}</label></label>-->
<!--                                    <input class="col-sm-2" size="20" name="mkey" id="mkey" type="text" value="{pigcms{$device.mkey}"/>-->
<!--                                </div>-->

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
<include file="Public:footer"/>