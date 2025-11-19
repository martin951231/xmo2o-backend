var pic_lens = 1;
var ticket = common.getCache('ticket');
var client = common.checkAndroidApp() ? 2 : (common.checkIosApp() ? 1 : 0); 
var store_id = urlParam.store_id; 
function imgUpload(id) {
    var index = layer.open({type: 2, shade: false, shadeClose: false});
    var fullPath = window.document.location.href;
    var pathName = window.document.location.pathname;
    var pos = fullPath.indexOf(pathName);
    var localhostPath = fullPath.substring(0, pos);
    var post_url = requestUrl + 'WapMerchant&a=uploadStoreImage&path=authfile';
    $.ajaxFileUpload({
        url: post_url,
        secureuri: false,
        fileElementId: id,
        dataType: 'json',
        type: 'post',
        success: function (data, status) {
            var str = '<div class="mui-pull-left delate_photo" ><img src=' + data.result.url.s_image + ' data-url=' + data.result.title + ' data-preview-src="" data-preview-group="1"/><i></i></div>';
            $('.showImg').append(str);
            layer.close(index);

        },
        error: function (data, status, e) {
            layer.close(index);
            mui.toast('上传失败');
        }
    });
}

if (common.checkWeixin()) {
    $('#fileImage').on('click', function (e) {
        event.preventDefault();
        if (pic_lens < 10) {
            wxUpload();
        } else {
            mui.toast('最多只能上传10张图片');
        }

    })
} else {
    // 上传图片
    $('.up_img').off('change', 'input').on('change', 'input', function (e) {
        var id = $(this).attr('id');
        var length = $('.showImg .delate_photo').length;
        if (pic_lens < 10) {
            pic_lens++;
            imgUpload(id);
            $('.showImg').width(105 * (pic_lens + 1) + 150);
        } else {
            mui.toast('最多只能上传10张图片');
        }
    });
}

function wxUpload() {
    wx.chooseImage({
        count: 1, // 默认9
        success: function (res) {
            var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
            wx.uploadImage({
                localId: localIds[0], // 需要上传的图片的本地ID，由chooseImage接口获得
                isShowProgressTips: 1, // 默认为1，显示进度提示
                success: function (res) {
                    var serverId = res.serverId; // 返回图片的服务器端ID
                    common.http('WapMerchant&a=ajaxGetWxImg', {'serverId': serverId, 'picType': 'authfile'}, function (data) {
                        var str = '<div class="mui-pull-left delate_photo" ><img src=' + data.url + ' data-url=' + data.title + ' data-preview-src="" data-preview-group="1"/><i></i></div>';
                        $('.showImg').append(str);
                        pic_lens++;
                    });
                },
                fail: function (res) {
                    mui.toast('微信上传组件返回：' + res.errMsg);
                }
            });
        },
        fail: function (res) {
            mui.toast('微信选择图片组件返回：' + res.errMsg);
        }
    });
}

//图片删除点击
mui('.mui-content').on('tap','.delate_photo i',function(e){
    $(this).parent('.delate_photo').remove();
    pic_lens--;
    $('.showImg').width(105*(pic_lens+1)+150);
});

//获取资质店铺资质审核数据
common.http('WapMerchant&a=getStoreAuthInfo', {
    'ticket': ticket,
    'client': client,
    'store_id': store_id
}, function (data) {
    if (data != null && data.length != 0) {
        var sum_pic = '';
        var pic_len = data.pics.length;
        pic_lens = pic_len;
        $.each(data.pics, function (i, val) {
            sum_pic += '<div class="mui-pull-left delate_photo" ><img src=' + val.url + ' data-url=' + val.title + ' data-preview-src="" data-preview-group="1"/><i></i></div>';
        });
        $('.showImg').append(sum_pic);
        $('.showImg').width(105 * (pic_len + 1) + 150);
        $('#addressText2').html(data.tip);
        $('#authStatus').html(data.auth_status);
        $('#authReason').html(data.reason);
        if (data.reason == '') {
            $('.authReason').hide();
        }
    } else {
        mui.toast(L_('获取店铺资质失败'));
    }
});

mui('.mui-content').on('tap', '.store_auth_save', function (e) {
    var pic = [];
    $.each($('.showImg .delate_photo'), function (i, val) {
        pic.push($(this).find('img').data('url'));
    });

    common.http('WapMerchant&a=saveStoreAuth', {'pic': pic, 'store_id': store_id}, function (data) {
        if (data.length == 0) {
            mui.toast("保存成功");
            if (common.checkApp()) {
                setTimeout(function () {
                    if (common.checkAndroidApp()) {
                        window.pigcmspackapp.closewebview(2);
                    } else {
                        common.iosFunction('closewebview/2', {name: 'closewebview', type: '2'});
                    }
                }, 2000);
            } else {
                setTimeout(function () {
                    history.go(-1);
                    document.execCommand('Refresh');
                }, 2000);
            }
        }
    });

});