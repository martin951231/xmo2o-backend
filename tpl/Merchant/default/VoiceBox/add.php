<include file="Public:header"/>
<div class="main-content">
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
                                <input type="hidden" name="id" value="{pigcms{$box.id|default=0}"/>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("设备名称")}</label></label>
                                    <input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$box.name}"/>
                                </div>

                                <div class="form-group yilianyun_box">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("设备IMEI")}</label></label>
                                    <input class="col-sm-2" size="20" name="imei" id="imei" type="text" value="{pigcms{$box.imei}"/>
                                </div>
								
								<div class="form-group yilianyun_box">
                                    <label class="col-sm-1"><label for="sort_name">{pigcms{:L_("通知次数")}</label></label>
                                    <input class="col-sm-1" size="10" name="voice_number" id="voice_number" type="text" value="{pigcms{$box.voice_number}"/>
									<span class="form_tips">{pigcms{:L_("来单时，重复播放提醒的次数。只能填数字，不填写或填写0则不播放，最多6次。")}</span>
                                </div>

                                <if condition="C('customization.bailihua_loudspeaker') eq 1">
                                <div class="form-group">
                                    <label class="col-sm-1" for="brand_id">{pigcms{:L_("云音响品牌")}</label>
                                    <select name="brand" id="brand">
                                        <option value="0" <if condition="$box['brand'] eq 0">selected="selected"</if>>{pigcms{:L_("默认")}</option>
                                        <option value="2" <if condition="$box['brand'] eq 2">selected="selected"</if>>{pigcms{:L_("百励华")}</option>
                                    </select>
                                </div>
                                </if>

                                <div class="form-group">
                                    <label class="col-sm-1" for="store_id">{pigcms{:L_("选择店面")}</label>
                                    <select name="store_id" id="store_id">
                                        <volist name="stores" id="store">
                                            <option value="{pigcms{$store['store_id']}" <if condition="$store['store_id'] eq $box['store_id']">selected="selected"</if>>{pigcms{$store['name']}</option>
                                        </volist>
                                    </select>
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
<include file="Public:footer"/>