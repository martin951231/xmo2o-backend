<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>{pigcms{$config.site_name} - {pigcms{:L_("会员卡编辑")}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
    <style>
        a:hover,a:visited{color:#666;}
        table th{padding:17px 10px 19px;}
        table td{padding:17px 10px 19px 15px;}
        #good_list li{list-style: none;}
        #good_list li .del{color:blue;float:right;cursor:pointer;-webkit-user-select: none;-webkit-touch-callout: none;}
        #good_list li:hover{color:red;}
        #good_list li .del:hover{text-decoration:underline;color:red;}
        .btn-success, .btn-success:focus{
            position: relative;
            width: 78px;
            height: 34px;
            background-color: #87b87f!important;
            border-color: #87b87f;
            text-align: center;
            line-height: 34px;
            color: white;
            cursor: pointer;
        }
        .webuploader-element-invisible {
            position: absolute !important;
            clip: rect(1px 1px 1px 1px);
            clip: rect(1px,1px,1px,1px);
        }
        .webuploader-pick-hover .btn{
            background-color: #629b58!important;
            border-color: #87b87f;
        }
    </style>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
</head>
<body>
<form id="myform" method="post" action="{pigcms{:U('banner_ticket_entrance')}" autocomplete="off">
    <input type="hidden" name="img" id="subject_pic" value="{pigcms{$data.img}"/>
    <table>
        <tr>
            <th width="20%">{pigcms{:L_("专题图")}</th>
            <td width="80%" colspan="3">
                <span id="picStatus" style="color:green;margin-right:10px;display:none;"></span>
                <div style="display:inline-block;" id="J_selectImage">
                    <div class="btn btn-sm btn-success" style="position:relative;width:78px;height:34px;    background-color:#87b87f!important;border-color:#87b87f;">{pigcms{:L_("上传图片")}</div>
                </div>
                <span style="margin-left:20px;">{pigcms{:L_("图片建议尺寸")}50*50</span>
                <notempty name="data.img">
					<img id="img" src="{pigcms{:replace_file_domain($data['img'])}" width="50px" height="50px;"/>
                </notempty>
            </td>
        </tr>
        <tr>
            <th width="20%">{pigcms{:L_("按钮名称")}</th>
            <td width="80%" colspan="3"><input type="text" class="input" name="name" id="subject_name" value="{pigcms{$data.name}" style="width:200px;"/>&nbsp;&nbsp;&nbsp;<span>{pigcms{:L_("4个字以内")}</span></td>
        </tr>
        <tr>
            <th width="20%">{pigcms{:L_("横幅描述")}</th>
            <td width="80%" colspan="3"><input type="text" class="input" name="des" id="" value="{pigcms{$data.des}" style="width:200px;"/>&nbsp;&nbsp;&nbsp;<span>{pigcms{:L_("20个字以内")}</span></td>
        </tr>
        <tr>
            <th width="20%">{pigcms{:L_("链接地址")}</th>
            <td width="80%" colspan="3"><input type="text" class="input" name="url" id="url" value="{pigcms{$data.url}" style="width:200px;"/>
                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
            </td>

        </tr>
        <tr>
            <th width="20%">{pigcms{:L_("是否显示")}</th>
            <td width="80%" colspan="3">
                <select name="dispaly" id="subject_show" style="width:150px;">
                    <option value="1" <if condition="$data['dispaly'] eq '1'">selected="selected"</if>>{pigcms{:L_("显示")}</option>
                    <option value="0" <if condition="$data['dispaly'] eq '0'">selected="selected"</if>>{pigcms{:L_("不显示")}</option>
                </select>
            </td>
        </tr>
    </table>
    <div class="btn">
        <button type="submit" id="submit" style="margin-bottom:30px;">{pigcms{:L_("保存")}</button>
    </div>
</form>
<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    var pageDomId = ''
    function addLink(domid, iskeyword, type){
        pageDomId = domid;
        art.dialog.data('domid', domid);
        parent.setLinkBases(window, 'platform', 'h5');
    }
    //链接库回调方法
    function getLinkBasesUrl(url){
        console.log('url', url);
        $('#' + pageDomId).val(url);
    }
</script>
<script type="text/javascript">
    var site_url = "{pigcms{:C('config.site_url')}"
    if ($('#subject_pic').val() != '') {
        $('#picStatus').html('{pigcms{:L_("已上传")}').show();
    }

    if ($('#good_list li').size() > 0) {
        $('#good_list').css('margin-bottom', '20px');
    }

    parent.subject_win_name = window.name;

    function build_image_save(imagePath) {
        console.log(imagePath);
        var image_url = site_url+imagePath;
        $('#subject_pic').val(imagePath);
        $("#img").attr("src",image_url);
        $('#picStatus').html('{pigcms{:L_("已上传")}').show();
    }

    function build_good_save(goodData) {
        $('#good_list').css('margin-bottom', '20px');
        console.log(goodData);
        for (var i in goodData) {
            $('#good_list').append('<li><input class="good_id" type="hidden" name="good_id[]" value="' + goodData[i].id + '"/><span>' + goodData[i].title + '</span><div class="del">[{pigcms{:L_("删除")}]</div></li>');
        }
    }

    $(function () {
        var uploader = WebUploader.create({
            auto: true,
            swf: './static/js/Uploader.swf',
            server: "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=shop/content",
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
            upload_layer_tip = layer.load(1, {
                shade: [0.1, '#000']
            })
        });

        uploader.on('uploadSuccess', function (file, response) {
            console.log(response);
            layer.close(upload_layer_tip);
            if (response.error == 0) {
                build_image_save(response.url);
            } else {
                alert(response.message);
            }
        });

        uploader.on('uploadError', function (file, reason) {
            $('.loading' + file.id).remove();
            alert('{pigcms{:L_("上传失败！请重试。")}');
        });

        $('#build_subject_pic').click(function () {
            parent.layer.open({
                title: '{pigcms{:L_("在线制作专题图")}',
                type: 2,
                content: '{pigcms{$this->config['
                site_url']}/merchant.php?c=Shop&a=shop_fitment_build_subject_pic&store_id={pigcms{$_GET.store_id}',
                area: ['90%', '90%'],
                shade: 0.2,
                cancel: function () {

                },
                move: false
            });
        });

        $('#subject_show').change(function () {
            if ($('#subject_show').val() == '1') {
                $('.fitment_subject', parent.document).show();
            } else {
                $('.fitment_subject', parent.document).hide();
            }
        });

        $('#good_list .del').live('click', function () {
            $(this).closest('li').remove();
            if ($('#good_list li').size() == 0) {
                $('#good_list').css('margin-bottom', '0px');
            }
        });

        $('#choose_good_btn').click(function () {
            var selecteditemsArr = [];
            $.each($('#good_list .good_id'), function (i, item) {
                selecteditemsArr.push($(item).val());
            });
            if (selecteditemsArr.length >= 20) {
                parent.layer.msg('{pigcms{:L_("最多仅能添加20个商品。")}<br/>{pigcms{:L_("请先删除再进行操作。")}');
                return false;
            }
            parent.layer.open({
                title: false,
                closeBtn: 0,
                type: 2,
                content: '{pigcms{$this->config['
                site_url']}/merchant.php?c=Diypage&a=good&store_id={pigcms{$_GET.store_id}&type=more&pageFrom=shop_fitment&max_num=20&number=' + new Date().getTime() + '&selecteditems=' + selecteditemsArr.join(','),
                area: ['650px', '576px'],
                shade: 0.2,
                cancel: function () {

                },
                move: false
            });
        });

        $('#shop_fitment_color').change(function () {
            $('#color_preview').css('background', '#' + $(this).val());
            console.log($('#fitment_preview .fitment_header', parent.document));
            $('#fitment_preview .fitment_header', parent.document).css('background-color', '#' + $(this).val());
        });

        $('#myform').submit(function () {
            if ($('#subject_pic').val() == '') {
                parent.layer.msg('{pigcms{:L_("请先上传专题图或在线制作")}');
                return false;
            }
            if ($('#subject_name').val() == '') {
                parent.layer.msg('{pigcms{:L_("请填写专题名称")}');
                return false;
            }
            if ($('#subject_show').val() == '1' && $('#good_list li').size() == 0) {
                parent.layer.msg('{pigcms{:L_("请关联至少1个商品，或者关闭专题显示")}');
                return false;
            }
            if ($('#good_list li').size() > 20) {
                parent.layer.msg('{pigcms{:L_("最多仅能关联20个商品，现在关联了")} ' + $('#good_list li').size() + ' {pigcms{:L_("个，请先删除。")}');
                return false;
            }

            $('#submit').prop('disabled', true).html('{pigcms{:L_("保存中")}...');
            $.post($('#myform').attr('action'), $('#myform').serialize(), function (result) {
                if (result.status == 1) {
                    layer.alert(result.info, {
                        end: function () {
                            $('#submit').prop('disabled', false).html('{pigcms{:L_("保存")}');
                            layer.closeAll();
                        }
                    });
                } else {
                    layer.alert(result.info);
                    $('#submit').prop('disabled', false).html('{pigcms{:L_("保存")}');
                }
            });

            return false;
        });
    });
</script>
</body>
</html>