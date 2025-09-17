<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <div id="v20_store_fitment" class="tab-pane">
                <div class="store_fitment_div">
                    <div class="form-group">
                        <div class="group-title">{pigcms{:L_("轮播图")}
                            <button type="button" class="btn fitment banner" data-type="shop_fitment_banner"
                                    data-title="{pigcms{:L_(" 门店专题
                            ")}">{pigcms{:L_("装修")}</button>
                        </div>
                        <div class="group-content">{pigcms{:L_("通过运营位推荐新品、打造爆款商品，更可设置多个不同时间生效的海报。")}</div>
                    </div>
                    <div class="form-group">
                        <div class="group-title">{pigcms{:L_("今日特惠")}
                            <button type="button" class="btn fitment preferential"
                                    data-type="foodshop_fitment_preferential" data-title="{pigcms{:L_(" 今日特惠
                            ")}">{pigcms{:L_("装修")}</button>
                        </div>
                        <div class="group-content">{pigcms{:L_("将您的商品以大图橱窗展现，充分利用店内流量，从而提升下单率。")}</div>
                    </div>
                    <div class="form-group cat-group">
                        <div class="group-title">{pigcms{:L_("特色分类")}</div>
                        <div class="group-content cat">
                            <div class="cat-row clearfix">
                                <div class="cat-name">{pigcms{:L_("热销")}</div>
                                <div class="cat-desc">
                                    <div class="cat-item">{pigcms{:L_("状态：")}<span id="fitment_cat_hot_status"><if
                                                    condition="$subject_info['cat_hot_status']">{pigcms{:L_("展示")}<else/>{pigcms{:L_("隐藏")}</if></span>
                                    </div>
                                    <div class="cat-item">{pigcms{:L_("排序方式：")}<span id="fitment_cat_hot_sort">
                                                                            <if condition="$subject_info['cat_hot_sort'] eq 1">
                                                                                {pigcms{:L_("价格从高到低排序")}
                                                                            <elseif condition="$subject_info['cat_hot_sort'] eq 2"/>
                                                                                {pigcms{:L_("价格从低到高排序")}
                                                                            <elseif condition="$subject_info['cat_hot_sort'] eq 3"/>
                                                                                {pigcms{:L_("销量从高到低排序")}
                                                                            <elseif condition="$subject_info['cat_hot_sort'] eq 4"/>
                                                                                {pigcms{:L_("销量从低到高排序")}
                                                                            <else/>{pigcms{:L_("默认排序")}</if></span>
                                    </div>
                                    <div class="cat-item">{pigcms{:L_("描述：")}<span id="fitment_cat_hot_desc">{pigcms{$subject_info.cat_hot_desc|default=L_("大家喜欢吃，才叫真好吃")}</span>
                                    </div>
                                    <div class="cat-edit">
                                        <a title="{pigcms{:L_(" 修改")}" class="green fitment" style="padding-right:8px;"
                                        data-type="foodshop_fitment_category_hot" data-title="{pigcms{:L_("热销分类")}">
                                        <i class="ace-icon fa fa-pencil bigger-130"></i>{pigcms{:L_("编辑")}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="cat-row clearfix">
                                <div class="cat-name">{pigcms{:L_("优惠")}</div>
                                <div class="cat-desc">
                                    <div class="cat-item">{pigcms{:L_("状态：")}<span id="fitment_cat_discount_status"><if
                                                    condition="$subject_info['cat_discount_status']">{pigcms{:L_("展示")}<else/>{pigcms{:L_("隐藏")}</if></span>
                                    </div>
                                    <div class="cat-item">{pigcms{:L_("排序方式：")}<span id="fitment_cat_discount_sort">
                                                                            <if condition="$subject_info['cat_discount_sort'] eq 1">
                                                                                {pigcms{:L_("价格从高到低排序")}
                                                                            <elseif condition="$subject_info['cat_discount_sort'] eq 2"/>
                                                                                {pigcms{:L_("价格从低到高排序")}
                                                                            <elseif condition="$subject_info['cat_discount_sort'] eq 3"/>
                                                                                {pigcms{:L_("销量从高到低排序")}
                                                                            <elseif condition="$subject_info['cat_discount_sort'] eq 4"/>
                                                                                {pigcms{:L_("销量从低到高排序")}
                                                                            <else/>{pigcms{:L_("默认排序")}</if></span>
                                    </div>
                                    <div class="cat-item">{pigcms{:L_("描述：")}<span id="fitment_cat_discount_desc">{pigcms{$subject_info.cat_discount_desc|default=L_("美味又实惠，大家快来抢")}</span>
                                    </div>
                                    <div class="cat-edit">
                                        <a title="{pigcms{:L_(" 修改")}" class="green fitment" style="padding-right:8px;"
                                        data-type="foodshop_fitment_category_discount"
                                        data-title="{pigcms{:L_("优惠分类")}">
                                        <i class="ace-icon fa fa-pencil bigger-130"></i>{pigcms{:L_("编辑")}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<!--                <div id="fitment_preview">-->
<!--                    <iframe src="/packapp/foodshop/#/menu?store_id={pigcms{$store_shop['store_id']}" width="380"-->
<!--                            height="680" frameborder="0" id="iframe_preview">-->
<!--                        <p>Your browser does not support iframes.</p>-->
<!--                    </iframe>-->
<!--                </div>-->
            </div>
        </div>
    </div>
</div>
<style>
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF !important;
        font-family: "sans serif", tahoma, verdana, helvetica;
        font-size: 12px;
        line-height: 24px;
        height: 24px;
        padding: 2px 4px;
        border-color: #848484 #E0E0E0 #E0E0E0 #848484;
        border-style: solid;
        border-width: 1px;
        display: -moz-inline-stack;
        display: inline-block;
        vertical-align: middle;
        zoom: 1;
    }

    .form-group > label {
        font-size: 12px;
        line-height: 24px;
    }

    #upload_pic_box {
        margin-top: 20px;
        height: 150px;
    }

    #upload_pic_box .upload_pic_li {
        width: 130px;
        float: left;
        list-style: none;
    }

    #upload_pic_box .upload_image_li {
        width: 130px;
        float: left;
        list-style: none;
    }

    #upload_pic_box img {
        width: 100px;
        height: 70px;
    }

    .webuploader-element-invisible {
        position: absolute !important;
        clip: rect(1px 1px 1px 1px);
        clip: rect(1px, 1px, 1px, 1px);
    }

    .webuploader-pick-hover .btn {
        background-color: #629b58 !important;
        border-color: #87b87f;
    }

    #store_fitment {
        margin-left: 30px;
        position: relative;
    }

    #v20_store_fitment {
        margin-left: 30px;
        position: relative;
    }

    .store_fitment_div {
        width: 500px;
    }

    #store_fitment .form-group {
        border-bottom: 1px solid #EEE;
        padding: 15px 0;
    }

    #store_fitment .form-group.cat-group {
        border-bottom: 0;
    }

    #fitment_preview {
        position: absolute;
        left: 650px;
        top: 0;
        width: 315px;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }

    #fitment_preview img {
        width: 100%;
    }

    .fitment_subject img:hover, .fitment_showcase img:hover {
        cursor: pointer;
        border: 2px solid blue;
    }

    .group-title {
        position: relative;
        font-size: 18px;
        color: black;
        margin-bottom: 10px;
    }

    .group-content {
        width: 320px;
        line-height: 1.6;
        font-size: 14px;
        color: #999;
    }

    .group-title .btn.fitment {
        background-color: #438eb9 !important;
        border-radius: 3px;
        border: none;
        position: absolute;
        right: 0px;
        top: 0px;
    }

    .group-content.cat {
        width: 100%;
    }

    .group-content .cat-row {
        margin-top: 20px;
        position: relative;
    }

    .group-content .cat-name {
        font-size: 16px;
        color: black;
        float: left;
        width: 30%;
    }

    .group-content .cat-desc {
        font-size: 14px;
        float: left;
        width: 70%;
        border-bottom: 1px solid #EEE;
        padding-bottom: 20px;
    }

    .group-content .cat-edit {
        position: absolute;
        right: 0px;
        top: 0px;
    }

    .weigh_table td {
        padding-left: 12px;
    }

    #goods_spec {
        width: 600px;
        border-color: #ccc;
        border-style: solid;
        border-width: 1px 0 0 1px;
        margin-bottom: 10px;
    }

    #goods_spec tr:first-child {
        background: #eee;
    }

    #goods_spec th {
        height: 32px;
    }

    #goods_spec th, #goods_spec td {
        border-color: #ccc;
        border-style: solid;
        border-width: 0 1px 1px 0;
        text-align: center;
        height: 32px;
    }

    #goods_spec input {
        text-indent: 3px;
        margin: 5px 0;
        height: 26px;
    }

    .reduce_recharge {
        background: url(/tpl/Merchant/default/static/css/img/reduce_nc.png) no-repeat;
        width: 32px;
        height: 32px;
        border: none;
        position: absolute;
    }

    .plus_recharge {
        background: url(/tpl/Merchant/default/static/css/img/plus_nc.png) no-repeat;
        width: 32px;
        height: 32px;
        border: none;
        position: absolute;
        margin-left: 38px;
    }

    .plus_re {
        margin-right: 50px;
    }

    .activity_image_li {
        width: 130px;
        float: left;
        list-style: none;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>
<if condition="!$_GET['store_id']">
	<script type="text/javascript">
		var href = parent.location.href;
		console.log(href);
		if(href.indexOf('store_id') > 0){
			var paramArr = href.split('?');
			if(paramArr[1]){
				location.href = "{pigcms{:U('foodshop_fitment')}&" + paramArr[1];
			}
		}
	</script>
</if>
<script type="text/javascript">
    $('.fitment').click(function () {
        var store_id = window.parent.location.href.split('store_id=')[1].split('&')[0];
        var _this = $(this);
        art.dialog.open(
        //     '{pigcms{$this->config['
        // site_url
        // ']}/merchant.php?c=Foodshop&a=' + _this.data('type') + '&store_id={pigcms{$_GET.store_id}',
            '{pigcms{$this->config['
        site_url
        ']}/merchant.php?c=Foodshop&a=' + _this.data('type') + '&store_id=' + store_id,
            {
                lock: true,
                title: '{pigcms{:L_("页面装修")} -> ' + _this.data('title'),
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45,
                id: 'fitment',
                close: function () {/*关闭窗口时的回调函数*/
                    var _iframe1 = window.top.getTopIframe();
                    _iframe1.location.reload(true);
                }
            }
    )
        ;
    });

    $('.fitment_subject img').click(function () {
        $('.fitment.subject').trigger('click');
    });
    $('.fitment_showcase img').click(function () {
        $('.fitment.showcase').trigger('click');
    });

    console.log($('.page-content').width());
    $('#fitment_preview').css('left', (500 + ($('.page-content').width() - 500 - 350) / 2) + 'px');


    // 标签添加
    $(document).on('click', '.plus_recharge', function () {
        var item = $(this).parents('.form-group').find('.plus_re:last');
        var newitem = $(item).clone(true);

        $(this).css('visibility', 'hidden');
        $(this).siblings('.reduce_recharge').css('visibility', 'visible');

        newitem.find('.tip').css('visibility', 'hidden');

        $(item).after(newitem);
        newitem.find('input').attr('value', '');
    })

    // 标签减少
    $(".reduce_recharge").click(function () {
        if ($(this).parents('.form-group').find('.plus_re').length <= 1) {
            $('.plus_re input').val('');
            $('.plus_re:first').find('.tip').css('visibility', 'visible');

        } else {

            $(this).parents('.plus_re').remove();
            // $('.plus_re:last').find('.reduce_recharge').css('visibility','hidden');
            $('.plus_re:last').find('.plus_recharge').css('visibility', 'visible');

            $.each($(this).parents('.form-group').find('.plus_re'), function (index, val) {
                var No = index + 1;
                if (No == 1) {
                    $(val).find('.tip').css('visibility', 'visible')
                }
                $(val).data('id', No);

            });
        }
    })

</script>
<script>

    $(document).ready(function () {
        console.log(window.parent.location.href);

        var store_id = window.parent.location.href.split('store_id=')[1].split('&')[0];
        var uploader = WebUploader.create({
            auto: true,
            swf: '{pigcms{$static_public}js/Uploader.swf',
            server: "{pigcms{:U('Foodshop/ajax_store_pic')}",
            pick: {
                id: '#J_selectImage',
                multiple: false
            },
            accept: {
                title: 'Images',
                extensions: 'gif,jpg,jpeg,png',
                mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
            }
        });
        uploader.on('fileQueued', function (file) {
            if ($('.upload_pic_li').size() >= 5) {
                uploader.cancelFile(file);
                alert('{pigcms{:L_("最多上传5个图片！")}');
                return false;
            }
        });
        uploader.on('uploadSuccess', function (file, response) {
            if (response.error == 0) {
                $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="' + response.url + '"/><input type="hidden" name="pic[]" value="' + response.title + '"/><br/><a href="#" onclick="deleteImage(\'' + response.title + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
            } else {
                alert(response.info);
            }
        });
        uploader.on('uploadError', function (file, reason) {
            $('.loading' + file.id).remove();
            alert('{pigcms{:L_("上传失败！请重试。")}');
        });


        var uploaderBackGround = WebUploader.create({
            auto: true,
            swf: '{pigcms{$static_public}js/Uploader.swf',
            server: "{pigcms{:U('Foodshop/ajax_store_pic')}",
            pick: {
                id: '#background',
                multiple: false
            },
            accept: {
                title: 'Images',
                extensions: 'gif,jpg,jpeg,png',
                mimeTypes: 'image/gif,image/jpeg,image/jpg,image/png'
            }
        });
        uploaderBackGround.on('fileQueued', function (file) {
            if ($('.upload_backgroud_li').size() >= 1) {
                uploader.cancelFile(file);
                alert('{pigcms{:L_("最多上传1个图片！")}');
                return false;
            }
        });
        uploaderBackGround.on('uploadSuccess', function (file, response) {
            if (response.error == 0) {
                $('#upload_backgroud_li').append('<li class="upload_backgroud_li"><img src="' + response.url + '"/><input type="hidden" name="background[]" value="' + response.title + '"/><br/><a href="#" onclick="deleteImage(\'' + response.title + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
            } else {
                alert(response.info);
            }
        });
        uploaderBackGround.on('uploadError', function (file, reason) {
            $('.loading' + file.id).remove();
            alert('{pigcms{:L_("上传失败！请重试。")}');
        });


    });




    // 活动图片
    KindEditor.ready(function (K) {
        var editor = K.editor({
            allowFileManager: true,
            langType: "{pigcms{$config.system_lang_alias}"
        });
        K('#activityImage').click(function () {
            if ($('.activity_image_li').size() >= 3) {
                alert('{pigcms{:L_("最多上传3个图片！")}');
                return false;
            }
            editor.uploadJson = "{pigcms{:U('Foodshop/ajax_store_activity_pic')}";
            editor.loadPlugin('image', function () {
                editor.plugin.imageDialog({
                    showRemote: false,
                    imageUrl: K('#course_pic').val(),
                    clickFn: function (url, title, width, height, border, align) {
                        $('#activity_image_ul').append('<li class="activity_image_li"><img src="' + url + '"/><input type="hidden" name="activity_image[]" value="' + title + '"/><br/><a href="#" onclick="deleteImage(\'' + title + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
                        editor.hideDialog();
                    }
                });
                setTimeout(function () {
                    $('.ke-dialog').css('top', (($(window).height() - $('.ke-dialog').height()) / 2) - 5 + 'px');
                }, 200);
            });
        });
    });

    function deleteImage(path, obj) {
        $.post("{pigcms{:U('Foodshop/ajax_delstore_pic')}", {path: path});
        $(obj).closest('li').remove();
    }

    function check(obj) {
        var length = $('.paycheck:checked').length;
        if (length == 0) {
            $(obj).attr('checked', 'checked');
            bootbox.alert('{pigcms{:L_("最少要选择一种支付方式")}');
        }
    }

    $(function ($) {
        $('input[name=is_book]').click(function () {
            if ($(this).val() == 1) {
                $('.book').css('display', 'block');
            } else {
                $('.book').css('display', 'none');
            }
        });
        $('input[name=is_invoice]').click(function () {
            if ($(this).val() == 1) {
                $('.invoice').css('display', 'block');
            } else {
                $('.invoice').css('display', 'none');
            }
        });

        $('#book_start').timepicker($.extend($.datepicker.regional['zh-cn'], {
            'timeFormat': 'hh:mm:ss',
            'hour': '00',
            'minute': '00'
        }));
        $('#book_stop').timepicker($.extend($.datepicker.regional['zh-cn'], {
            'timeFormat': 'hh:mm:ss',
            'hour': '23',
            'minute': '59'
        }));

        var is_submit = false;
        $('#edit_form').submit(function () {
            if (is_submit) return false;
            is_submit = true;
            $.post("{pigcms{:U('Foodshop/shop_edit',array('store_id'=>$_GET['store_id']))}", $('#edit_form').serialize(), function (result) {
                if (result.status == 1) {
                    alert(result.info);
                    window.location.href = "{pigcms{:U('Foodshop/index')}";
                } else {
                    is_submit = false;
                    alert(result.info);
                }
            })
            return false;
        });
    });

    function addLink(domid, iskeyword, type) {
        art.dialog.data('domid', domid);
        art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword=' + iskeyword, {
            lock: true,
            title: '{pigcms{:L_("插入链接或关键词',
            width: 800,
            height: 500,
            yesText: '关闭")}',
            background: '#000',
            opacity: 0.45
        });
    }


</script>
<include file="Public:footer"/>
