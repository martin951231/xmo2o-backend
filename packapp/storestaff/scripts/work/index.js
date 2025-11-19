var androidUrl = '', iosUrl = '', iosBao = '', androidBao = '', code = '', mkey = '', load = 1;
var doubble = false;
var device_type = 1;
var tooth = false;
var interval = null, setIn = null, print_mobile_code = '';
var downloads = true;
var print_mcode = '';
var print_paper = '';
var print_image = '';
var print_mkey = '';
var lanyaList = [];
var config = null;
var paramsData = null
var client = common.checkAndroidApp() ? 2 : (common.checkIosApp() ? 1 : 0);
$('.kf_link').hide();
$('.kf_linkOld').hide();
var print_bluetooth_code = '';
var print_bluetooth_connect = false;

//模拟扫码调试
// scanResult('mercoupon_15627_123456814')

if (urlParam.device_id && urlParam.ticket && !common.checkWeixinApp()) {
    common.setCache('device_id', urlParam.device_id, true);
    common.setCache('ticket', urlParam.ticket, true);
}

if (urlParam.now_lang) {
    common.setCache('now_lang', urlParam.now_lang, true);
    now_lang = urlParam.now_lang;
}

if (common.checkIosApp()) {
    downloads = false;
    common.iosFunction('iOS_Device_UUID', { name: 'iOS_Device_UUID' });
}
function back_device_uuid(res) {
    print_mobile_code = res;
}

$(document).ready(function () {

    if (common.checkIphoneXApp()) {
        $('.indexCashierFooter').css('padding-bottom', '34px');
    }

    var indexDatas = common.getCache('indexData', true);

    if (common.getCache('isStarted', true)) {
        if (indexDatas && indexDatas.pay_in_store == '1') {
            if ($(window).height() > 526) {
                $('.indexCashierTop .fl').css('padding', (($(window).height() * 0.3 - 80) / 2) + 'px 0px');
            }
            if (common.checkIosApp()) {
                $('.indexCashierTop .fl').css('padding-top', parseInt($('.indexCashierTop .fl').css('padding-top').replace('px', '')) + 20 + 'px');
            }

            $('#cashierPage').removeClass('hide');
        } else {
            $('#mainPage').removeClass('hide');
            $(".group_list a").each(function () {
                $(this).height($(window).width() * 0.5 * 0.94);
            });
        }
        $('#startBg').addClass('hide');
    } else {
        common.setCache('isStarted', 'true', true);
        $('#startBg img').css({ height: $(window).height(), width: $(window).width() });
        $('#startBg').removeClass('hide');
        $('#mainPage').addClass('hide');
        $('#cashierPage').addClass('hide');
    }
    if (urlParam.from == 'merchant' && urlParam.ticket) {
        common.removeAllCache(false);
        common.removeAllCache(true);
        common.setCache('isStarted', 'true', true);
        common.setCache('ticket', urlParam.ticket, true);
    }
    if (common.checkLogin() == false) {
        return false;
    }

    if (common.getVersion() >= 1300) {
        common.appFunction({ name: "closeStartupPage" });
    }

    setTimeout(function () {
        if (common.checkIosApp()) {
            common.iosFunction('changecolor/#2ECC71', { name: 'changecolor', color_value: '#2ECC71' });
        } else if (common.checkAndroidApp()) {
            window.pigcmspackapp.changecolor('#2ECC71');
        }
    }, 1000);

    var staffArr = common.getCache('store_staff', true);
    if (staffArr && !common.checkApp()) {
        indexDataObj(staffArr);
    } else {
        common.http('Storestaff&a=login', { noTip: true }, function (data) {
            common.setCache('ticket', data.ticket, true);
            common.setCache('ticket', data.ticket);
            if (data.v20_ticket) {
                localStorage.setItem('storestaff_v20_ticket', data.v20_ticket);
            }
            common.setCache('store_staff', data.user, true);
            indexDataObj(data.user);
        }, function (data) {
            common.removeCache('ticket', true);
            common.removeCache('ticket');

            redirect_self('login.html');
        });
    }

    $('.group .group_list').height($(window).height() - (common.checkIosApp() ? 95 : 75) - 40);
    common.onlyScroll($('.group .group_list'));
    if (!common.checkWeixin() && !common.checkApp()) {
        $('#scanQrcodeBox').remove();
        $('#noScanShow').height($('#noScanShow').width()).removeClass('hide');
    }
    $('#scanQrcode,#cashierScanCode').click(function () {
        common.scan('scanResult');
    });

    $('.printer').click(function () {
        var content = '<div style="letter-spacing:2px;font-size:16px;">';
        content += ' <div>终&nbsp;端&nbsp;号：' + print_mcode + '</div>';
        content += ' <div>密&nbsp;&nbsp;&nbsp;&nbsp;钥：' + print_mkey + '</div>';
        content += ' <div>纸张类型：' + print_paper + 'mm</div>';
        content += ' <div>支持图片：' + (print_image == '1' ? '支持' : '不支持') + '</div>';
        content += ' <div>&nbsp;</div>';
        content += ' <div style="font-size:12px;">添加打印机后，请重新启动本软件。</div>';
        content += '</div>';
        layer.open({
            title: '打印机参数',
            content: content,
            btn: []
        });
    });

    $('.offline_shop').click(function () {
        common.http('Storestaff&a=check_merchant', { noTip: true, type: 'shop' }, function (data) {
            var href = $(this).data('url');
            openWebviewUrl("retail.html");
        });

    })
    $('.storeBox,.store_qrcode').click(function () {
        var href = $(this).data('url');
        common.http('Storestaff&a=check_merchant', { noTip: true, type: 'store' }, function (data) {
			openWebviewUrl(href);
        });

    })
    $('.store,.cashBox').click(function () {
        var href = $(this).data('url');
        common.http('Storestaff&a=check_merchant', { noTip: true, type: 'store' }, function (data) {
            openWebviewUrl(href);
        });

    })

    config = common.getCache('config', true);
    if (!config || common.checkApp()) {
        common.http('Storestaff&a=config', { noTip: true, 'client': client }, function (data) {
            config = data;
            common.setCache('config', data, true);
            config_loaded();
        });
    } else {
        config_loaded();
    }

    laytpl.mobileCode = function (res) {
        if (res == print_mobile_code) {
            return true
        } else {
            return false
        }
    };
    if (common.checkApp()) {
        if (client == 2) {
            device_type = 1;
        } else {

            device_type = 2;
        }
        setTimeout(function () {
            common.http('Storestaff&a=blueteeth_hardware', {
                'device_type': device_type,
                'noTip': true
            }, function (data) {
                //获取蓝牙打印机数据
                if (data.print_list && data.print_list.length >= 2) {
                    if (device_type == 2) {
                        var lens = 0;
                        for (var i = 0; i < data.print_list.length; i++) {
                            if (data.print_list[i].print_mobile_code == print_mobile_code) {
                                lens++
                            }
                        }
                        if (lens >= 2) {
                            laytpl(document.getElementById('cardlist').innerHTML).render(data.print_list, function (html) {
                                $('#kd_entry').html(html);
                                doubble = true;
                                $(".printerAdd").show();
                            });
                            if (device_type == 2) {
                                common.iosFunction('get_printer/get_printer', { name: 'get_printer', callback: 'get_printer' });
                            } else {
                                window.pigcmspackapp.get_printer('get_printer');
                            }
                        } else {
                            lanyaList = data.print_list;
                            doubble = false;
                            $(".printerAdd").hide();
                            if (device_type == 2) {
                                common.iosFunction('get_printer/get_printer', { name: 'get_printer', callback: 'get_printer' });
                            } else {
                                window.pigcmspackapp.get_printer('get_printer');
                            }
                        }
                    } else {
                        laytpl(document.getElementById('cardlist').innerHTML).render(data.print_list, function (html) {
                            $('#kd_entry').html(html);
                            doubble = true;
                            $(".printerAdd").show();
                        });
                        if (device_type == 2) {
                            common.iosFunction('get_printer/get_printer', { name: 'get_printer', callback: 'get_printer' });
                        } else {
                            window.pigcmspackapp.get_printer('get_printer');
                        }
                    }
                } else {
                    if (data.print_list && data.print_list.length > 0) {
                        lanyaList = data.print_list;
                    }
                    doubble = false;
                    $(".printerAdd").hide();
                    if (device_type == 2) {
                        common.iosFunction('get_printer/get_printer', { name: 'get_printer', callback: 'get_printer' });
                    } else {
                        window.pigcmspackapp.get_printer('get_printer');
                    }
                }
            });
        }, 1000)
    }
    /*启动监听APP退出事件*/
    if (common.checkApp()) {
        setInterval(function () {
            var isLogout = common.getCache('isLogout', true);
            if (isLogout) {
                redirect_self('login.html');
            }

            var changeLanguageOk = common.getCache('changeLanguageOk', true);
            if (changeLanguageOk) {
                common.removeCache('changeLanguageOk', true);
                location.reload();
            }
        }, 300);
    }

    $('#loginout').click(function () {
        layer.open({
            content: L_('您确定要退出吗？')
            , btn: [L_('确定'), L_('取消')]
            , yes: function (index) {
                common.http('Storestaff&a=logout', {}, function (data) {
                    common.removeCache('ticket', true);
                    common.removeCache('ticket');

                    redirect_self('login.html');

                    layer.close(index);
                });
            }
        });
    });
    $('.openShopApp').click(function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (common.checkApp()) {
            if (common.checkAndroidApp()) {
                window.pigcmspackapp.judgeappexist(androidBao, 'downApp');
            } else {
                common.iosFunction('judgeappexist/' + iosBao + '/downApp', { name: 'judgeappexist', url_schemes: iosBao, callback: 'downApp' });
            }
        }
    });


    // 获取客服链接 
    var ticket = common.getCache('ticket', true);
    var device_id = common.getCache('device_id', true);

    common.http('Storestaff&a=getImUrl', { noTip: true }, function (data) {
        if (!data.url) {
            return
        }
        $('.kf_link').show();
        $('.kf_linkOld').show();
        $('.kf_link').attr('data-url', data.url);
        $('.kf_linkOld').attr('href', data.url);

    }, function (res) {
        console.log(res)
    });
});

function config_loaded() {
    console.log(config);
    androidUrl = config.mer_android_download_url;
    iosUrl = config.mer_ios_download_url;
    androidBao = config.mer_android_package_name;//安卓包名
    iosBao = config.mer_ios_package_name;//ios包名

    if (config.open_multilingual == 1 && config.lang_config && config.lang_config.lang_list && config.lang_config.lang_list.length > 0) {
        if (!all_lang || !now_lang || common.checkApp()) {
            common.setCache('lang_list', config.lang_config.lang_list, true);
            common.setCache('open_multilingual', config.open_multilingual, true);
            get_lang_list(config.lang_config.now_lang);
        } else {
            replace_html_lang();
        }
    }
    // 开启堂扫电子菜单模式
    if (config.open_meal_electronic_menu == 1) {
        $('.mealBox').attr('data-url', 'foodshop_order_list_new.html');
        $('.mealBox').find('a').prop('href', 'foodshop_order_list_new.html');
    }

    //一卡通定制【ID1017628】
    if(config.internal_membership_card == 1){
        $('.daily_report').show();
    }

    get_currency_symbol();

    if (common.checkAndroidApp() && common.getVersion() >= 1500) {
        if ((typeof window.pigcmspackapp == 'object' || typeof window.pigcmspackapp == 'function') && (typeof window.pigcmspackapp.isOpenFloatWindow == 'object' || typeof window.pigcmspackapp.isOpenFloatWindow == 'function')) {
            if (window.pigcmspackapp.isOpenFloatWindow() === false || window.pigcmspackapp.isOpenFloatWindow() == 'false') {
                window.pigcmspackapp.openFloatWindow();
            }
        }
    }
}

function downApp(status) {
    var ticket1 = common.getCache('ticket');
    var ios_device = common.getDeviceId();
    if (status == 1) {
        if (common.checkAndroidApp()) {
            //打开商家APP
            window.pigcmspackapp.openapp(androidBao, '', ticket1 + '&store_device_id=' + ios_device);
        } else {
            common.iosFunction('openapp/' + iosBao + '//' + 'from=storestaff&ios_device=' + ios_device + '&ticket=' + ticket1,
                { name: 'openapp', url_schemes: iosBao, from: 'storestaff', ios_device: ios_device, ticket: ticket1 });
        }
    } else {
        layer.open({
            content: L_('暂时未检测到您的商家中心app，系统默认给您下载，点击确定按钮后开始下载。'),
            btn: [L_('确定'), L_('取消')],
            yes: function (index) {
                if (!downloads) {

                    var iosHref = window.btoa(iosUrl);
                    iosHref = iosHref.replace(/\//g, "&");
                    common.iosFunction('downLoadApp/' + iosBao + '/' + iosHref, { name: 'downLoadApp', download_url: iosHref, bundleId: iosBao });
                    layer.close(index);
                } else {

                    window.pigcmspackapp.downLoadApp(androidUrl, androidBao);
                    layer.close(index);
                }

            }
        });
    }
}

function getParam(url, name) {
    var reg = new RegExp("[&|?]" + name + "=([^&$]*)", "gi");
    var a = reg.test(url);
    return a ? RegExp.$1 : "";
}

function scanResult(value) {
    var result = value;
    if (result.indexOf('subcard') != -1 && result.indexOf('http') !== 0) {
        result = value.split('_');
        pass = result[1];
        common.http('Storestaff&a=sub_card_verify', { 'pass': pass, 'noTip': false }, function (data) {
            if (!data.errorCode) {
                motify.log(L_('验证消费成功！'));
                setTimeout(openWebviewUrl("sub_card_order_detail.html?from_scan=1&pass=" + pass), 5000);
            } else {
                layer.open({
                    title: [L_('错误提示') + '：', 'background-color:#2ecc71;color:#fff;'],
                    content: L_('您扫描的内容') + ' “ <font color="red">' + result + '</font> ” ' + data.errorMsg,
                    btn: [L_('确定')],
                    end: function () {
                    }
                });
            }
        });
    } else if (result.indexOf('shop') != -1 && result.indexOf('http') !== 0) { // 店员扫用户支付成功页面进来
        result = value.split('_');
        var store_id = result[1];
        if (common.isNewShop()) {
            webToMiniApp('pages/shop/order/orderSearch', { order_id: store_id })
        } else {
            openWebviewUrl("shop_detail.html?order_id=" + store_id);
        }
    } else if (result.indexOf('cardgoods') != -1 && result.indexOf('http') !== 0) { // 店员扫用户端兑换商品详情页进来
        var index = layer.open({
            title: [L_('提示'), 'background-color:#2ecc71;color:#fff;'],
            content: L_('是否核销该积分商品'),
            btn: [L_('确定'),L_('取消')],
            yes: function () {
                common.httpV20('/storestaff/storestaff.scan/index', { 'code': result, 'noTip': false }, function (data) {
                    motify.log('核销成功！');
                    setTimeout(()=>{
                        openWebviewUrl("pointExchangeList.html")
                    },1500)
                });
                layer.close(index);
            }
        });
    }else if (result.indexOf('scenic') != -1 && result.indexOf('http') !== 0) { // 店员扫景区订单
        result = value.split('_');
        var scenic_order_id = result[1];
        openWebviewUrl("ticket_order_details.html?order_id=" + scenic_order_id);
    } else if(result.indexOf('deposit') != -1){
        //寄存核销
        common.httpV20('/storestaff/storestaff.scan/index', { 'code': result, 'noTip': false }, function (data) {
            laytpl(document.getElementById('showDepositGoods').innerHTML).render(data.data, function (html) {
                $("#showDepositGoodsModal").html(html);
                $("#showDepositGoodsModal").show();
            });
        });
    }else if (result.length == 14) {
        common.http('Storestaff&a=scan_payid_check', { 'payid': result }, function (data) {
            if (data.uid > 0) {
                openWebviewUrl("cashier_set.html?uid=" + data.uid + "&from_scan=1&payid=" + data.payid);
            }
        });
    } else if (result.length == 21 || result.length == 22 || result.length == 24) {   //扫码核销商城自提订单
        // var type_key = result.substr(0, 1);
        // if (type_key == 1) {
        //     var order_id = result.substr(1);
        //     openWebviewUrl("mall_detail.html?order_id=" + order_id);
        // }
        common.httpV20('/storestaff/storestaff.scan/index', { 'code': result, 'noTip': false }, function (data) {
            if(data.operate_type == 'url' && data.url){
                window.location.href = data.url;
            }
        });
    } else if (result.length == 28) {   // 城投定制-员工卡消费券积分核销
        common.httpV20('/storestaff/storestaff.scan/index', { 'code': result, 'noTip': false }, function (data) {
            if(data.operate_type == 'success'){
                motify.log(data.msg);
            }else if(data.operate_type == 'url'){
                openWebviewUrl("cashier_set.html?code=" + result + "&from_scan=1");
            }
        });
    }  else if (result.length == 16) {   // 城投定制-首页扫码预约弹框核销
        common.httpV20('/storestaff/storestaff.scan/index', { 'code': result, 'noTip': false }, function (data) {
            if(data.operate_type == 'lifetools_appoint_popup'){
                laytpl(document.getElementById('lifetools_appoint_popup').innerHTML).render(data, function (html) {
                    $("#lifetools_appoint_popupBox").html(html);
                    $("#lifetools_appoint_popupBox").show();
                });
            }
        });
    }else if (result.length == 12) {   // 景区、体育、健身
        common.httpV20('/storestaff/storestaff.scan/index', { 'code': result, 'noTip': false }, function (data) {
            motify.log('核销成功！');
        });
    }else if (result.indexOf('mercoupon_') != -1) {   // 店员H5支持核销商家优惠券
        common.httpV20('/storestaff/storestaff.Scan/index', { 'code': result, 'noTip': false }, function (data) {
            paramsData = data
            // 商家优惠券
            if(data.operate_type == 'mercoupon_verify'){
                laytpl(document.getElementById('mercoupon_verify').innerHTML).render(data, function (html) {
                    $("#mercoupon_verifyBox").html(html);
                    $("#mercoupon_verifyBox").show();
                });
            }
        });
    }else {
        if (result.indexOf('http://') !== 0 && result.indexOf('https://') !== 0) {
            var strArr = result.split(',');
            if (strArr.length == 2) {
                var barCode = strArr[1];
            } else {
                barCode = result;
            }
            if (barCode.length == 13) {
                common.setCache('shopBarCode', barCode, true);
                openWebviewUrl("retail.html");
            } else {
                layer.open({
                    title: [L_('错误提示') + '：', 'background-color:#2ecc71;color:#fff;'],
                    content: L_('您扫描的内容') + ' “ <font color="red">' + result + '</font> ” ' + L_('暂时无法识别'),
                    btn: [L_('确定')],
                    end: function () {
                    }
                });
            }
        } else {
            var ctype = getParam(result, 'a'), id = getParam(result, 'id'), c = getParam(result, 'c'), is_group_combine = getParam(result, 'is_group_combine'), order_id = getParam(result, 'order_id');
            if (ctype != 'group_qrcode' || id == '' || c != 'Storestaff') {
                var indexData = common.getCache('indexData', true);
                layer.open({
                    title: [L_('错误提示') + '：', 'background-color:#2ecc71;color:#fff;'],
                    content: '您扫描的内容不是有效的' + indexData.have_group_name + '验证二维码',
                    btn: [L_('确定')],
                    end: function () {
                    }
                });
            } else {
                if (is_group_combine == 1) {
                    // 团购优惠组合
                    // 请求接口 获得是否显示弹窗以及内容
                    var params = {
                        group_pass: id,
                        order_id: order_id
                    }
                    common.http('Storestaff&a=group_combine_find', params, function (data) {
                        if (data.row_count && data.row_count == 1 && data.data) {
                            laytpl($('#groupCombine').html()).render(data.data, function (html) {
                                $('#groupCombineModal').html(html);
                                $('#groupCombineModal').addClass('show');
                            });
                            return
                        }
                        if (data && data.row_count == 0) {
                            motify.log(L_('未查询到相关订单'));
                            return
                        }
                    });
                } else if (ctype == 'group_qrcode') {
                    openWebviewUrl("group_detail.html?order_id=" + getParam(result, 'order_id') + '&from=scan');
                }
            }
        }
    }
}

// 团购优惠组合验证
$('#groupCombineModal').on('click', '#groupCombineDialog', function (event) {
    event.stopPropagation();
});
$('#groupCombineModal').click(function (event) {
    $(this).removeClass('show');
});
$('#groupCombineModal').on('click', '#groupCombineBtn', function (e) {
    var order_id = $(this).data('id');
    common.http('Storestaff&a=group_combine_verify', {
        order_id: order_id
    }, function (data) {
        console.log(data, 'data-group_combine_verify')
        openWebviewUrl("group_detail.html?order_id=" + data.order_id + '&from=scan');
        $('#groupCombineModal').removeClass('show');
    })
});



function scanCardResult(str) {
    var code = str
    common.http('Storestaff&a=scan_payid_check', { 'payid': str }, function (data) {
        if (data.uid > 0) {
            openWebviewUrl("cashier_set.html?uid=" + data.uid + "&from_scan=1&payid=" + data.payid);
        }
    });
}

function indexDataObj(staffArr) {
    if (staffArr) {
        $('#staff_name,#footer_staff_name').html(staffArr.name);
        $('#footer_store_name').html(staffArr.store_name);
    }
    var indexData = common.getCache('indexData', true);

    if (staffArr.type == 2) {
        if (common.checkApp()) {
            $('.openShopApp').show();
        } else {
            $('.openShopApp').remove();
        }
    } else {
        $('.openShopApp').remove();
    }
    if (indexData && !common.checkApp()) {
        editData(indexData);
    } else {
        common.http('Storestaff&a=index', { noTip: true }, function (data) {
            common.setCache('indexData', data, true);
            editData(data);
        });
    }

    setTimeout(function () {
        if (common.checkApp() && common.getVersion() >= 1410) {
            if (common.checkAndroidApp()) {
                window.pigcmspackapp.getCurrentVolume('getCurrentVolume');
            } else {
                common.iosFunction('getCurrentVolume/' + getCurrentVolume, { name: 'getCurrentVolume', callback: 'getCurrentVolume' });
            }
        }
    }, 500);

    checkNotice();

    if (urlParam.redirect_url) {
        openWebviewUrl(urlParam.redirect_url);
    }
}

function editData(data) {
    // console.log(data)
    var countArr = [];
    if (data.have_group != '1') {
        $('.groupBox').remove();
    } else {
        $('.groupName').html(data.have_group_name);
    }
    //景区
    if (data.have_scenic != '1') {
        $('.scenicBox').remove();
    } else {
        $('.scenicName').html(data.have_scenic_name);
    }

    if (data.have_shop != '1') {
        $('.shopBox').remove();
        $('.shopBoxNew').remove();
    } else {
        if (common.isNewShop()) {
            $('.shopBox').remove();
        } else {
            $('.shopBoxNew').remove();
        }
        $('.shopName').html(data.have_shop_name);
    }

    //商城
    if (data.have_mall != '1') {
        $('.mallBox').remove();
    } else {
        $('.mallName').html(data.have_mall_name);
    }

    //积分兑换
    if (data.has_point_exchange != '1') {
        $('#pointExchangeList').remove();
    } else {
        $('#pointExchangeList').html(data.point_exchange_name);
    }

    if (data.have_meal != '1') {
        $('.mealBox').remove();
    } else {
        $('.mealName').html(data.have_meal_name);
    }

    if (data.have_meal2 != '1') {
        $('.mealBox, .meal2Link').remove();
    } else {
        $('.meal2Name').html(data.have_meal2_name);
    }
    //店员商城
    if (data.have_mall2 != '1') {
        $('.mallBox,.mall2Link').remove();
    } else {
        $('.mall2Name').html(data.have_mall2_name);

    }

    if (data.have_appoint != '1') {
        $('.appointBox').remove();
    } else {
        $('.appointName').html(data.have_appoint_name);
    }

    if (data.have_store != '1') {
        $('.storeBox').remove();
    } else {
        $('.storeName').html(data.have_store_name);
    }

    if (data.pay_in_store != '1') {
        $('.cashBox').remove();
        $('.store_qrcode').remove();
    } else {
        $('.cashName').html(data.have_cash_name);
    }

    if (data.open_sub_card != '0') {
        $('.sub_card').show();
    } else {
        $('.sub_card').remove();
    }

    if (data.have_deposita == '1') {
        $('.memberCard').show();
    } else {
        $('.memberCard').remove();
    }

    // 多语言
    if (data.have_store != '1') {
        $('.couponList,.store_qrcode').remove();
    }

    if (data.show_new_scenic) {
        $('.lifeScenic').show();
    }
    if (data.show_worker_card) {
        $('.workerCard').show();
    }
    if (data.show_sport) {
        $('.lifeSport').show();
    }
    if (data.show_merchant_activity) {
        $('.lifeMerchantActivity').show();
    }

    //页面布局
    if (data.pay_in_store == '1') {
        if ($(window).height() > 526) {
            $('.indexCashierTop .fl').css('padding', (($(window).height() * 0.3 - 80) / 2) + 'px 0px');
        }
        if (common.checkIosApp()) {
            $('.indexCashierTop .fl').css('padding-top', parseInt($('.indexCashierTop .fl').css('padding-top').replace('px', '')) + 20 + 'px');
        }

        $('#mainPage').addClass('hide');
        $('#cashierPage').removeClass('hide');
    } else {
        $('#cashierPage').addClass('hide');
        $('#mainPage').removeClass('hide');
        $(".group_list a").each(function () {
            $(this).height($(".group_list a").eq(0).width() * 0.94);
        });
    }
    $('#startBg').addClass('hide');

    //请求完页面参数判断是否需要直接跳转，例如页面列表，或订单详情。
    if (urlParam.gopage && !common.getCache('isGoOtherPage', true)) {
        var href = location.protocol + '//' + location.host + '/packapp/' + visitWork + '/' + urlParam.gopage + '.html' + (urlParam.goparam ? '?' + urlParam.goparam : '');
        if (common.isNewShop()) {
            //新版外卖店员
            switch (urlParam.gopage) {
                case 'shop':
                    webToMiniApp('pages/shop/order/order')
                    //href = location.protocol + '//' + location.host + '/packapp/staff/#/pages/shop/order/order';
                    break;
            }
        }

        if (common.checkApp()) {
            if (common.checkAndroidApp()) {
                window.pigcmspackapp.createwebview(href);
            } else {
                var iosHref = window.btoa(href);
                iosHref = iosHref.replace(/\//g, "&");
                common.iosFunction('createwebview/' + iosHref, { name: 'createwebview', url: iosHref });
            }
        } else {
            common.setCache('isGoOtherPage', 'true', true);
            location.href = href;
            return false;
        }
    }

    //声音提醒
    $('body').append('<video id="playMp3Tip" controls="true" loop="loop" src="source/new_order.mp3" style="display:none;" webkit-playsinline playsinline></video>');
    HTMLVideoElement.prototype.stop = function () {
        this.pause();
        this.currentTime = 0.0;
    }
    if (countArr.length > 0) {
        var nowIndex = 0;
        var timeArr = {};
        var playMp3Tip = null;
        var newOrderTip = null;
        setInterval(function () {
            var nowType = countArr[nowIndex];
            $('.loader').removeClass('on');
            $('.' + nowType + '_loader').addClass('on').show();
            common.http('Storestaff&a=' + nowType + '_count', { noTip: true, time: timeArr[nowType] }, function (data) {
                timeArr[nowType] = data.time;
                $('.' + nowType + '_loader em').html(data.count);
                $('.loader').removeClass('on');

                if (data.count > 0) {
                    if (newOrderTip != null) {
                        $('#playMp3Tip').trigger('stop');
                        layer.close(newOrderTip);
                    }

                    if (playMp3Tip == null) {
                        $('#playMp3Tip').trigger('play');
                    }

                    /*音乐播放5分钟*/
                    playMp3Tip = setTimeout(function () {
                        $('#playMp3Tip').trigger('stop');
                    }, 300000);
                    newOrderTip = layer.open({
                        title: '新订单提示'
                        , content: '您有新的订单需要处理。'
                        , btn: [L_('确定')]
                        , end: function (index) {
                            $('#playMp3Tip').trigger('stop');
                            clearTimeout(playMp3Tip);
                            playMp3Tip = null;
                            newOrderTip = null;
                        }
                    });
                }
            });
            if (nowIndex + 1 == countArr.length) {
                nowIndex = 0;
            } else {
                nowIndex++;
            }
        }, 5000);
    }
}

var exitLayer = -1;

function appbackmonitor() {
    if (/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())) {
        window.lifepasslogin.closeWebView();
    } else {
        if (common.getVersion() >= 1300) {
            window.pigcmspackapp.returnToHomePage();
            return false
        }
        if (exitLayer != -1) {
            window.pigcmspackapp.closewebview(2);
        } else {
            layer.closeAll();
            exitLayer = layer.open({
                content: '您确定要退出程序吗？再次按返回键将退出。'
                , btn: [L_('确定'), L_('取消')]
                , yes: function (index) {
                    window.pigcmspackapp.closewebview(2);
                    layer.close(index);
                }
                , end: function (index) {
                    exitLayer = -1;
                }
            });
        }
    }
}

function lanya(data) {
    var mkey1 = data.mkey;

    print_bluetooth_code = data.print_bluetooth_code;

    if (common.checkAndroidApp()) {
        window.pigcmspackapp.connectDevice(data.print_bluetooth_code);
    } else {
        common.iosFunction('connectDevice/' + data.print_bluetooth_code, { name: 'connectDevice', device_uuid: data.print_bluetooth_code });
    }
    motify.log(L_("打印模块开始每3秒自动请求打印"));
    setInterval(function () {
        common.http('Storestaff&a=own_print_work', { noTip: true, mkey: mkey1 }, function (data) {
            if (data.info != '') {
                if (common.checkAndroidApp()) {
                    window.pigcmspackapp.printer_work(data.info, '');
                } else {
                    var iosHref = window.btoa(unescape(encodeURIComponent(data.info)));

                    iosHref = iosHref.replace(/\//g, "&");

                    common.iosFunction('printer_work/' + iosHref + '/', { name: 'printer_work', print_img: '', print_text: iosHref });
                }
            }
        }, function (data) {
        });
    }, 3000);
};

function get_printer(arg1, arg2, arg3) {
    if (common.checkApp()) {
        if (load > 2) {
            clearInterval(setIn);
            clearInterval(interval);
        }
        if (arg1 != '') {
            $('.printer').show();
            $(".printerAdd").hide();
            print_mcode = arg1;
            print_paper = arg2;
            print_image = arg3;
            print_mkey = common.getDeviceId();
            common.http('Storestaff&a=get_print_has', { noTip: true, mkey: print_mkey, mcode: print_mcode }, function (data) {
                motify.log(L_("打印模块开始每3秒自动请求打印"));
                if (!doubble) {
                    setInterval(function () {
                        common.http('Storestaff&a=own_print_work', { noTip: true, mkey: print_mkey }, function (data) {
                            if (data.info != '') {
                                if (common.checkAndroidApp()) {
                                    window.pigcmspackapp.printer_work(data.info, '');
                                } else {
                                    var iosHref = window.btoa(unescape(encodeURIComponent(data.info)));

                                    iosHref = iosHref.replace(/\//g, "&");

                                    common.iosFunction('printer_work/' + iosHref + '/', { name: 'printer_work', print_img: '', print_text: iosHref });
                                }
                            }
                        }, function (data) {

                        });
                    }, 3000);
                } else {
                    setIn = setInterval(function () {
                        common.http('Storestaff&a=own_print_work', { noTip: true, mkey: print_mkey }, function (data) {
                            if (data.info != '') {
                                if (common.checkAndroidApp()) {
                                    window.pigcmspackapp.printer_work(data.info, '');
                                } else {
                                    var iosHref = window.btoa(unescape(encodeURIComponent(data.info)));

                                    iosHref = iosHref.replace(/\//g, "&");

                                    common.iosFunction('printer_work/' + iosHref + '/', { name: 'printer_work', print_img: '', print_text: iosHref });
                                }
                            }
                        }, function (data) {

                        });
                    }, 3000);
                }
            }, function (res) {
                motify.log(res.errorMsg)
            })
        } else {
            if (!doubble) {
                if (lanyaList.length > 0) {
                    if (client == 2) {
                        lanya(lanyaList[0])
                    } else {
                        for (var i = 0; i < lanyaList.length; i++) {
                            if (lanyaList[i].print_mobile_code == print_mobile_code) {
                                lanya(lanyaList[i])
                            }
                        }
                    }
                }
            } else {
                if (tooth) {
                    tooth = false;
                    if (common.checkAndroidApp()) {
                        window.pigcmspackapp.connectDevice(code);
                    } else {
                        common.iosFunction('connectDevice/' + code, { name: 'connectDevice', device_uuid: code });
                    }
                    print_bluetooth_code = code;

                    motify.log(L_("打印模块开始每3秒自动请求打印"));
                    interval = setInterval(function () {
                        common.http('Storestaff&a=own_print_work', { noTip: true, mkey: mkey }, function (data) {
                            if (data.info != '') {
                                if (common.checkAndroidApp()) {
                                    window.pigcmspackapp.printer_work(data.info, '');
                                } else {

                                    var iosHref = window.btoa(unescape(encodeURIComponent(data.info)));

                                    iosHref = iosHref.replace(/\//g, "&");

                                    common.iosFunction('printer_work/' + iosHref + '/', { name: 'printer_work', print_img: '', print_text: iosHref });
                                }
                            }
                        }, function (data) {

                        });
                    }, 3000);
                }

            }

        }
    }

}

function getDeviceStatus(type) {
    if (type == 1) {
        if (print_bluetooth_connect === false) {
            motify.log("蓝牙打印机正在连接：" + print_bluetooth_code);
        }
    } else if (type == 2) {
        print_bluetooth_connect = false;
        motify.log('蓝牙打印机未连接成功');
        $('.tishi').show();
        $('.tishi').addClass('active');
        $('.indexCashierTop').addClass('active');
        $('.tishi i').click(function (e) {
            $('.tishi').hide();
            $('.indexCashierTop').removeClass('active');
        });
    } else {
        $('.tishi').hide();
        if (print_bluetooth_connect === false) {
            motify.log('蓝牙打印机连接成功');
        }
        print_bluetooth_connect = true;
    }
}

$(".printerAdd").on("click", function () {
    $("#printerAdd").show()
});

$("#printerAdd").on("click", ".laymshade", function () {
    $("#printerAdd").hide()
});

$("#printerAdd").on("click", ".bluetooth", function () {
    load++;
    $(".bluetooth").removeClass('active');
    $(".noBluetooth").removeClass('active');
    $(this).addClass("active");
    code = $(this).attr('data-code');
    mkey = $(this).attr("data-mkey");
    tooth = false;
    $("#printerAdd").hide();
    if (common.checkAndroidApp()) {
        tooth = true;
        window.pigcmspackapp.get_printer('get_printer');
    } else {
        tooth = true;
        common.iosFunction('get_printer/get_printer', { name: 'get_printer', callback: 'get_printer' });
    }
});

$("#printerAdd").on("click", ".noBluetooth", function () {
    $(this).addClass("active");
    $(".bluetooth").removeClass('active');
    setTimeout(function () {
        if (load != 1) {
            clearInterval(setIn);
            clearInterval(interval);
        }
        $("#printerAdd").hide()
    }, 100)
})


// 判断密码是123456 弹出提示
var is_Simple_Password = common.getCache('is_Simple_Password', true);
var user = common.getCache('merchant_info', true);
if (is_Simple_Password == 1) {
    $('.alap_box').addClass('show');
    $('.alap_con').addClass('show');
    $('html, body').addClass('lock-back');
    function commonAlap() {
        $('.alap_box').hide();
        $('.alap_con').hide();
        common.setCache('is_Simple_Password', '0', true);
        $('html, body').removeClass('lock-back');
    }

    $('.btn_ok').click(function () {
        var password = $('.news_pwd1').val();
        var password2 = $('.news_pwd2').val();

        if (password.length < 6) {
            motify.log(L_('新密码不能少于6位！'));
            return false
        }
        if (password == '') {
            motify.log(L_('新密码不能为空！'));
            return false
        }
        if (password2 == '') {
            motify.log(L_('再次输入新密码不能为空！'));
            return false
        }
        if (password != password2) {
            motify.log(L_('两次输入密码不相同！'));
            $('.news_pwd2').val('')
            return false
        }

        common.http('Storestaff&a=changePwd', { 'password': password }, function (data) {
            console.log(data);
            motify.log(L_('修改成功！'));
            commonAlap();
        });
    })

    $('.btn_pass').click(function () {
        commonAlap();
    })

}


// 隐私协议
var lastAgreeTime = '';
var privacy_policy_md5 = '';

if (common.checkApp()) {
    common.http('Storestaff&a=get_privacy_policy', { noTip: true }, function (data) {
        privacy_policy_md5 = data.privacy_policy_md5;
        if (data.privacy_policy) {
            $('.fixedAgreement').show();
            if (common.checkIosApp()) {
                common.iosFunction('getAgreement/getAgreementOk', { name: 'getAgreement', callback: 'getAgreementOk' });
            } else {
                window.pigcmspackapp.getAgreement('getAgreementOk');
            }
        }
    })
}
function getAgreementOk(option) {
    if (!option || privacy_policy_md5.trim() != option.trim()) {
        agreementShow();
    }
}
function agreementShow() {
    weui.confirm(L_('<div class="agreementCon"><p>感谢您信任并使用我们的软件！</p> <p>我们依据最新的法律要求更新了用户协议和隐私政策，特向您推送本提示。请您仔细阅读并充分理解相关条款，其中的重点条款已为您标注，方便您了解自己的权利。</p> <p>我们将通过《隐私政策》帮助您了解我们收集、使用、存储和共享个人信息的情况，以及您所享有的相关权利。并向您介绍了我们公司主体的基本信息以及告知了我们的联系通道与方式。</p><p>您点击"同意"，即表示您已阅读并同意上述协议条款，我们将尽全力保障您的合法权益并继续为您提供优质的产品和服务。如您点击"不同意"，将可能导致无法继续使用我们的产品和服务。</p><p style="color:#999">您可通过阅读完整版<a href="userAgreement.html" class="agreementLink">《用户协议》</a>和<a href="agreement.html" class="agreementLink">《隐私政策》</a>了解详尽的条款内容</p></div>'), {
        title: L_('温馨提示'),
        buttons: [{
            label: L_('不同意'),
            type: 'gray',
            onClick: function () {
                notice();
            }
        }, {
            label: L_('同意'),
            type: 'primary',
            onClick: function () {
                if (common.checkIosApp()) {

                    common.iosFunction('saveAgreement/' + privacy_policy_md5, { name: 'saveAgreement', privacyPolicyMd5: privacy_policy_md5 });
                } else {
                    window.pigcmspackapp.saveAgreement(privacy_policy_md5);
                }
            }
        }]
    });
}
function notice() {
    setTimeout(function () {
        weui.alert('<p class="textP"><img src="images/notice.png" style="width:50px"  /><br>' + L_('若不同意则无法使用本软件') + '！</p>', {
            buttons: [{
                label: L_('知道了'),
                type: 'primary',
                onClick: function () {
                    setTimeout(function () {
                        agreementShow();
                    }, 350)
                }
            }]
        });
    }, 350);
}


//音量
var volume_timer = null;
function getCurrentVolume(volume) {
    if (volume_timer) {
        if (volume >= 0.8) {
            volume_timer = null;
            layer.close(volume_timer);
        }
        return true;
    }
    if (volume < 0.8) {
        volume_timer = layer.open({
            title: L_('音量过小提示')
            , content: L_('设备的音量过小，可能会听不到新订单推送音，建议进行调整。') + '<br/><br/><span style="color:#999;font-size:12px;">' + L_('以后也可在设置页进行手动调整音量。') + '</span>'
            , btn: [L_('设置为最大'), L_('手动设置')]
            , shadeClose: false
            , yes: function (index) {
                if (common.checkAndroidApp()) {
                    window.pigcmspackapp.setVolume('1');
                } else {
                    common.iosFunction('setVolume/1', { name: 'setVolume', volume_value: '1' });
                }
                motify.log(L_('已设置为最大'));
                volume_timer = null;
                layer.close(index);
            }
            , no: function (index) {
                openWebviewUrl("setting.html");
                volume_timer = null;
                layer.close(index);
            }
            , end: function (index) {
                volume_timer = null;
                layer.close(index);
            }
        });
    }
}

//心跳且检测是否退出
var customLoginTipLayer = null;
var checkLoginTimer = setInterval(function () {
    common.http('Storestaff&a=checkLogin', { noTip: true }, function (data) {

    }, function (data) {
        if (data && data.errorCode == '20044014') {
            clearInterval(checkLoginTimer);

            common.setCache('otherLoginAccount', '1', true);

            common.removeCache('ticket', true);
            common.removeCache('ticket');
            layer.open({
                title: L_('设备退出提示')
                , content: L_('您的账号在其他设备中登录了，此设备已自动退出。')
                , btn: [L_('重新登录')]
                , shadeClose: false
                , end: function (index) {

                    if (common.checkApp()) {
                        if (common.checkAndroidApp()) {
                            window.pigcmspackapp.voicetip_stop();
                        } else {
                            common.iosFunction('voicetip_stop', { name: 'voicetip_stop' });
                        }
                    }

                    common.removeCache('otherLoginAccount', true);
                    redirect_self('login.html');

                    layer.close(index);
                }
            });
            $('body').append('<style>.layermbtn:before{content:"";position:static;}</style>');
        } else if (data && data.errorCode == '20044015' && common.checkAndroidApp()) {
            clearInterval(checkLoginTimer);

            common.setCache('mustUpdateAppVar', '1', true);

            mustUpdateApp();
        } else if (data && data.errorCode == '20044016') {
            clearInterval(checkLoginTimer);
            location.reload();
        } else if (data && data.errorCode == '20044017') {
            var msgArr = JSON.parse(data.errorMsg);
            if (customLoginTipLayer) {
                layer.close(customLoginTipLayer);
            }
            customLoginTipLayer = layer.open({
                title: msgArr.title
                , content: msgArr.content
                , btn: msgArr.noClose ? '' : [msgArr.btn]
                , shadeClose: false
                , yes: function (index) {
                    layer.close(index);
                }
            });
            if (msgArr.noClose) {
                clearInterval(checkLoginTimer);
                $('.layermanim .layermend').remove();
            }
            $('body').append('<style>.layermbtn:before{content:"";position:static;}</style>');
        }

        //定时调用一下打印连接
        if (print_bluetooth_code != '') {
            if (common.checkAndroidApp()) {
                window.pigcmspackapp.connectDevice(print_bluetooth_code);
            } else {
                common.iosFunction('connectDevice/' + print_bluetooth_code, { name: 'connectDevice', device_uuid: print_bluetooth_code });
            }
        }

    });
}, 10000);

//亮屏
if (common.getVersion() >= 1441) {
    common.appFunction({ name: "setKeepScreenOn" });
}

function checkNotice() {
    var staffUser = common.getCache('store_staff', true);
    if (staffUser && staffUser.is_notice == 1) {
        if ($('.index_notice_tip_box').size() == 0) {
            $('body').prepend('<div class="index_notice_tip_box">' + L_('请注意，您关闭了接收新订单通知') + '<div class="link-url" data-url="setting.html">' + L_('设置') + '</div></div>');
        }
    } else {
        $('.index_notice_tip_box').remove();
    }
}

function pageShowFunc() {
    //亮屏
    if (common.checkApp() && common.getVersion() >= 1441) {
        common.appFunction({ name: "setKeepScreenOn" });
    }

    checkNotice();
}

/*页面点击事件*/
$(document).on('click', '.meal2Link', function () {
    webToMiniApp('pages/foodshop/index/index')
});
$(document).on('click', '.mall2Link, #mallBoxNewLink', function () {
    webToMiniApp('pages/mall/index/index')
});
$(document).on('click', '.shopBoxNew, #shopBoxNewLink', function () {
    webToMiniApp('pages/shop/order/order')
});
$(document).on('click', '.memberCard, #memberCard', function () {
    webToMiniApp('pages/deposit/member/memberCardList')
});


$(document).on('click', '.Addsub a', function(){
    change_deposit_goods_num($(this));
});

$('#showDepositGoodsModal').on('click','#verifyDepositGoods',function(e){
    var id = $('#showDepositGoodsModal').find('.deposit_goods_id').val();
    var num =  $('#showDepositGoodsModal').find('.deposit_goods_num').val();
    common.httpV20('/storestaff/storestaff.index/doCardDeposit', { 'id': id, 'num': num, 'noTip': false }, function (data) {
        $('#showDepositGoodsModal').hide();
        $('.depositGoodsSuccess').show();
    },function(){
        $('#showDepositGoodsModal').hide();
        $('.depositGoodsFail').show();
    });
})
// 活动预约核销
function writeAppointment(){
    let code = $('#writeAppointment')[0]?$('#writeAppointment')[0].dataset.code: ''
    common.httpV20('/life_tools/storestaff.LifeToolsAppoint/verification', { code: code, 'noTip': false }, function (data) {
        $("#lifetools_appoint_popupBox").hide();
        $('.depositGoodsSuccess').show();
    },function(){
        $("#lifetools_appoint_popupBox").hide();
        $('.depositGoodsFail').show();
    });
}
// 商家优惠券核销
function mercoupon_verify(){
    let params = {
        hadpull_id: paramsData.hadpull_id,
        uid: paramsData.uid,
    }
    common.httpV20('/storestaff/storestaff.Scan/verifyMerCoupon',params, function (data) {
        $("#mercoupon_verifyBox").hide();
        // motify.log('核销成功！',2000);
        motify.log(data.msg?data.msg:'核销成功！');
    },function(err){
        motify.log(err.msg?err.msg:'核销失败！');
    });
}
//关闭预约扫码弹框
function boxEnd(){
    $("#lifetools_appoint_popupBox").hide();
}
//关闭商家优惠券核销弹框
function mercouponClose(){
    $("#mercoupon_verifyBox").hide();
}
$(document).on('click','#depositGoodsSuccessClose',function(){
    $('.depositGoodsSuccess').hide();
})

$(document).on('click','#depositGoodsFailClose',function(){
    $('.depositGoodsFail').hide();
})

$(document).on('click','.laymshade',function(){
    $('#showDepositGoodsModal').hide();
    $('.depositGoodsSuccess').hide();
    $('.depositGoodsFail').hide();
    $("#lifetools_appoint_popupBox").hide();
})

function change_deposit_goods_num(that) {
    var emdom = $('.deposit_goods_num');
    var emval = emdom.val();
    var max = emdom.data('max-num');
    if (that.hasClass("jia")) {
        var emdom = $('.deposit_goods_num');
        if (emval > 0) {
            emdom.val(parseInt(emval) + 1 > max ? max : parseInt(emval) + 1);
        } else {
            emdom.val(1);
        }
    } else {
        if (emval > 1) {
            emdom.val(parseInt(emval) - 1);
        } else {
            emdom.val(1);
        }
    }
}
