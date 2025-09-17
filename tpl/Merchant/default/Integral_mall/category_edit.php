<include file="Public:header"/>
<div class="main-content">
    <style type="text/css">
        *{list-style: none;}
        #upload_pic_box{overflow: auto;}
        #upload_pic_box .upload_pic_li,.wap_upload_pic_li{width:130px;float:left;list-style:none;display: inline-block;}
        #upload_pic_box img{width:100px;height:70px;}
    </style>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-cubes"></i>
                <a href="{pigcms{:U('category_list')}">{pigcms{:L_("积分商城分类列表")}</a>
            </li>
            <li class="active">{pigcms{:L_("积分商城分类添加")}/{pigcms{:L_("编辑")}</li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
                        <input type="hidden" name='cat_fid' value="{pigcms{$now_category.cat_fid}"/>
                        <input type="hidden" name='cat_id' value="{pigcms{$_GET.cat_id}"/>
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("分类名称")}</label>
                                    <input class="col-sm-2" size="20" maxlength="20" name="cat_name" type="text" value="{pigcms{$now_category.cat_name}" <if condition="$_GET['frame_show'] eq 1">readonly</if>/>
                                    <span class="form_tips"></span>
                                </div>
                                <if condition="$_GET['frame_show'] neq 1">
                                <div class="form-group" >
                                    <label class="col-sm-1">{pigcms{:L_("分类图片")}</label>
                                    <div style="display:inline-block;" id="J_wap_selectImage">
                                        <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;">{pigcms{:L_("上传图片")}</div>
                                    </div>
                                    <span class="form_tips red">{pigcms{:L_("分类图片，建议尺寸：")}298*198</span>
                                </div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("分类图片预览")}</label>
                                    <div id="upload_pic_box">
                                        <ul id="cat_pic">
                                            <if condition="$now_category['cat_pic']">
                                                <li class="upload_pic_li"><img src="{pigcms{:file_domain()}{pigcms{$now_category.cat_pic}"/><input type="hidden" name="cat_pic" value="{pigcms{$now_category.cat_pic}"/></li>
                                            </if>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("分类排序")}</label>
                                    <input class="col-sm-1" size="10" maxlength="20" name="cat_sort" type="text" value="{pigcms{$now_category.cat_sort}"  <if condition="$_GET['frame_show'] eq 1">readonly</if>/>
                                    <span class="form_tips red">{pigcms{:L_("排序值越大，越往前显示！")}</span>
                                </div>


                                <if condition='!empty($now_category["cat_fid"])'>
                                    <div class="form-group">
                                        <label class="col-sm-1" for="is_weekshow">{pigcms{:L_("是否热门")}</label>
                                        <span><label><input <if condition="$now_category['cat_status'] eq 1">checked="checked"</if> name="is_hot" value="1" type="radio" >&nbsp;<span>{pigcms{:L_("是")}</span></label></span>
                                        <span><label><input <if condition="$now_category['cat_status'] eq 0">checked="checked"</if> name="is_hot" value="0" type="radio">&nbsp;<span>{pigcms{:L_("否")}</span>&nbsp;</label></span>
                                        <span class="form_tips" style="color:red">{pigcms{:L_("如果选择热门，颜色会有变化")}</span>
                                    </div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("分类状态")}</label></label>

                                    <if condition="$_GET['frame_show'] neq 1">
                                        <span><label><input name="cat_status" <if condition="$now_category['cat_status'] eq 1">checked="checked"</if> value="1" type="radio" >&nbsp;<span>{pigcms{:L_("启用")}</span></label></span>
                                        <span><label><input name="cat_status" <if condition="$now_category['cat_status'] eq 0">checked="checked"</if> value="0" type="radio">&nbsp;<span>{pigcms{:L_("关闭")}</span>&nbsp;</label></span>
                                    <else/>
                                        <php>if ($now_category['cat_status']==1) { echo '启用';} else { echo '关闭'; }</php>
                                    </if>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1">{pigcms{:L_("描述")}</label>
                                    <textarea name="desc" cols="35" rows="4" <if condition="$_GET['frame_show'] eq 1">readonly</if>>{pigcms{$now_category.desc}</textarea>
                                    <span class="form_tips" style="color:red">{pigcms{:L_("微信回复、电脑网站介绍时需要用到，换行符表示换行。最多支持12个字。")}</span>
                                </div>
                            </div>
                            <if condition="$_GET['frame_show'] neq 1">
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info" type="submit">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        {pigcms{:L_("保存")}
                                    </button>
                                </div>
                            </div>
                            </if>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<script type="text/javascript">
    KindEditor.ready(function(K) {
        var editor = K.editor({
            allowFileManager : true
        });

        K('#J_wap_selectImage').click(function(){
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic', array('path' => 'goods'))}";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        $('#cat_pic').html('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="cat_pic" value="'+url+'"/></li>');
                        editor.hideDialog();
                    }
                });
                setTimeout(function(){
                    $('.ke-dialog').css('top',$(window).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
                },200);
            });
        });
    });
</script>
<include file="Public:footer"/>