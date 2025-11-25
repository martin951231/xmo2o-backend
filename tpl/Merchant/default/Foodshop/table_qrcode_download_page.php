<include file="Public:header"/>
<div class="main-content">
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
                                <input type="hidden" name="store_id" value="{pigcms{$store_id}">
                                <div class="form-group">
                                    <label class="col-sm-1"><label>     </label></label>
                                    <label><input type="radio" name="is_common" value="1" <if condition="$now_goods['is_must'] eq 0">checked="checked"</if>>&nbsp;&nbsp;已装修的桌台码</label>&nbsp;&nbsp;&nbsp;
                                    <label><input type="radio" name="is_common" value="2" <if condition="$now_goods['is_must'] eq 1">checked="checked"</if>>&nbsp;&nbsp;普通桌台码</label>&nbsp;&nbsp;&nbsp;
                                </div>

                                <div class="clearfix form-actions">
                                    <div class="col-md-offset-3 col-md-9">
                                        <button class="btn btn-info" type="submit">
<!--                                            <i class="ace-icon fa fa-check bigger-110"></i>-->
                                            {pigcms{:L_("批量下载")}
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