$(document).ready(function () {
    mui.init();
    // 分类 || 套餐id
    console.log('urlParam',urlParam)
    var id = urlParam.id;
    // cat 分类 package 套餐 renewOrder 续费
    var type = urlParam.type;
    if (type != 'renewOrder') {
        $('#selectNum').removeClass('hide');
    } else {
        $('#selectNum').addClass('hide');
    }
    // 续费订单
    var store_id = $.getUrlParam('store_id');

    // 导航title
    var title = store_id?L_('续费详情'):L_('购买详情');
    $('title').text(title);
    $('.mui-title').text(title);

    // 店铺图标
    var storeDetailImg = './images/newMarketing/catStoreImg.png'
    if (type == 'package') {
        storeDetailImg = './images/newMarketing/packageDefaultImg.png'
    }
    $('.img-wrap img').attr('src',storeDetailImg);

    // 支付方式
    var payTypeList = [];
    var payType = "";
    // 支付年限
    var years = "";
    // 数量
    var num = "1";
    // 总价格
    var totalMoney = 0;
    // 优惠金额
    var discountMoney = 0;
    // 支付金额
    var payMoney = 0;
    // 店铺总数
    var storeTotalNum = 0;
    // 商家余额
    var merchantMoney = 0;
    var currency = '￥';
    var timer = null;

    // 服务周期 || 支付方式渲染
    function listRender(id, container, list) {
        laytpl(document.getElementById(id).innerHTML).render(list, function(html){
            $(container).html(html);
        });
    }

    // 服务周期选中渲染
    function serviceCycleActive() {
        $('.service-list .item').removeClass('active');
        $('.service-list .item').each(function(){
            if ($(this).data('years') == years) {
                $(this).addClass('active')
            }
        })
    }

    // 支付方式选中渲染
    function payTypeActive() {
        $('.payType-list .item .select-icon').removeClass('active');
        $('.payType-list .item').each(function(){
            if ($(this).data('type') == payType) {
                $(this).find('.select-icon').addClass('active')
            }
        })
    }

    // 支付方式获取 过滤掉支付宝支付
    function getPayType() {
        var success = function(res) {
            if (res.length) {
                res = res.filter (item => item.value != 'alipay')
                res.forEach(item => {
                    item.icon = './images/newMarketing/' + item.value + '.png';
                })
                payTypeList = res;
                payType = payTypeList[0].value;
                listRender('payListList','.payType-list',payTypeList);
                payTypeActive();
            }
        }

        common.httpV20('/new_marketing/merchant.Order/pay_check', {}, success);
    }
    getPayType();

    // 订单信息渲染 && 商家余额判断
    function moneyRender() {
        $('#totalMoney .value').text(currency + totalMoney);
        $('#discountMoney .value').text('-' + currency + discountMoney);
        $('#payMoney .value').text(currency + payMoney);
        if (Number(merchantMoney) < Number(payMoney)) {
            // 商家余额不足
            $('.balance-no').show();
        } else {
            $('.balance-no').hide();
        }
    }

    // 分类店铺 || 套餐详情 获取各种价格
    function getDiscountPayPrice() {
        var params = {
            id: id,
            year: years,
            num: num,
            type: type == 'cat'?2:1,
            noTip: true,
        }

        var success = function(res) {
            discountMoney = res.discount || 0;
            payMoney = res.pay || 0;
            totalMoney = (Number(discountMoney) + Number(payMoney)).toFixed(2);
            moneyRender();
        }
        common.httpV20('/new_marketing/merchant.MarketingPackage/getDiscountPayPrice', params, success);
    }

    // 获取详情
    function getDetail(noTip = false) {
        var params = {
            noTip: noTip
        };

        var api = '';
        if (store_id && type == 'renewOrder') {
            // 续费订单详情
            api = '/new_marketing/merchant.order/getPayInfo';
            params['store_id'] = store_id;
            params['years'] = years;
        } else {
            if (type == 'cat') {
                // 分类详情
                api = '/new_marketing/merchant.MarketingPackage/getCatDetail';
                params['id'] = id;
            } else if (type == 'package') {
                // 套餐详情
                api = '/new_marketing/merchant.MarketingPackage/getpackageDetail';
                params['id'] = id;
            }
        }

        if (!api) return

        var success = function(res) {
            console.log('res',res);
            if (res) {
                // 服务周期
                var service_cycle = [];
                if (type == 'renewOrder' && res.price_detail && res.price_detail.service_cycle && res.price_detail.service_cycle.length) {
                    service_cycle = res.price_detail.service_cycle;
                } else if ((type == 'package' || type == 'cat') && res.data && res.data.service_cycle && res.data.service_cycle.length) {
                    service_cycle = res.data.service_cycle;
                }
                years = years?years:service_cycle.length?service_cycle[0].years:'';
                service_cycle.length && listRender('serviceList','.service-list',service_cycle);
                serviceCycleActive();

                var name = '';
                var packageDesc = '';
                var yearPrice = '0';
                if (type == 'renewOrder') {
                    if (res.store && res.store.name) {
                        name = res.store.name;
                    }
                    if (res.price_detail) {
                        var price_detail = res.price_detail
                        yearPrice = price_detail.year_price || 0;
                    }

                    if (res.pay) {
                        pay = res.pay;
                        discountMoney = pay.discount_money || 0;
                        payMoney = pay.pay_money || 0;
                        merchantMoney = pay.merchant_money || 0;
                        totalMoney = (Number(discountMoney) + Number(payMoney)).toFixed(2);
                    }
                    moneyRender();
                } else if (type == 'package' || type == 'cat') {
                    merchantMoney = res.money || 0;
                    currency = res.currency || '￥';
                    if (res.data) {
                        var data = res.data
                        name = data.name || '';
                        yearPrice = data.year_price || 0;
                        if (type == 'package' && data.store_detail && data.store_detail.length) {
                            packageDesc = data.store_detail.map(item => item.num + '个' + item.type_name).join('+')
                        }
                        if (type == 'package' && data.all_num) {
                            storeTotalNum = data.all_num
                        }
                    }
                    getDiscountPayPrice();
                    
                } 
                $('.detail-content .name').text(name);
                $('.detail-content .year_price').text(yearPrice);
                packageDesc && $('.detail-content .desc').text(L_(packageDesc));
                if (type == 'package') {
                    $('#storeTotalNum').show();
                    $('#storeTotalNum .value').text(storeTotalNum);
                }
                
            }
        }

        common.httpV20(api, params, success);
    }
    getDetail();

    // 服务周期点击
    mui('.mui-content').on('tap','.service-list .item',function(e){
        if (years == $(this).data('years')) {
            return
        } 
        years = $(this).data('years');
        serviceCycleActive();
        if (type != 'renewOrder') {
            getDiscountPayPrice();
        } else {
            getDetail(true);
        }
    });

    // 数量加减点击
    mui('.mui-content').on('tap','#plus',function(e){
        num++
        $('#num').text(num);
        if (num > 1) {
            $('#minus').addClass('cr-orange');
            $('#minus').removeClass('cr-gray');
        }
        if (type != 'renewOrder') {
            getDiscountPayPrice();
        } else {
            getDetail(true);
        }
    });
    mui('.mui-content').on('tap','#minus',function(e){
        if (num == 1) {
            $('#minus').removeClass('cr-orange');
            $('#minus').addClass('cr-gray');
            return
        }
        num--
        $('#num').text(num);
        if (type != 'renewOrder') {
            getDiscountPayPrice();
        } else {
            getDetail(true);
        }
    });

    // 支付方式点击
    mui('.mui-content').on('tap','.payType-list .item',function(e){
        if (payType == $(this).data('type')) {
            return
        } 
        payType = $(this).data('type');
        payTypeActive();
    });

    // 去支付
    mui('.mui-content').on('tap','.submit-btn',function(e){
        if (payType == 'balance' && Number(merchantMoney) < Number(payMoney)) {
            mui.toast(L_('商家余额不足，请选择其他支付方式！'))
            return
        }
        if (payType=='weixin' && !urlParam.openid) {
            layer.open({
                content: L_('微信支付需要先绑定微信？绑定后请重新点击确认支付！'),
                btn: [L_('前往'), L_('取消')],
                yes: function(index){
                    var backUrl = location.href;
                    location.href = window.location.protocol + '//' + requestDomain + '/wap.php?c=Packapp&a=bind&is_return=1&referer=' + encodeURI(backUrl);
                    layer.close(index);
                }
            });
        } else {
            if (type == 'renewOrder') {
                saveRenewOrder();
            } else {
                normalGoPay();
            }
        }
    });

    // 分类 || 套餐支付
    function normalGoPay() {
        var params = {
            pay_check: payType,
            type: type == 'cat'?2:type == 'package'?1:'',
            id: id,
            pay_money: payMoney,
            discount_money: discountMoney,
            year: years,
            store_num: storeTotalNum,
            num: num,
            openid: urlParam.openid,
            is_mobine: 1,
        }
        var success = function(res) {
            paySuccess(res);
        }
        common.httpV20('/new_marketing/merchant.Order/pay',params,success)
    }

    // 续费订单保存
    function saveRenewOrder() {
        var params = {
            store_id: store_id,
            years: years,
            pay_type: payType,
        }

        var success = function(res) {
            renewOrderGoPay(res.order_id,res.order_type)
        }
        common.httpV20('/new_marketing/merchant.order/savePayInfo',params,success)
    }

    // 续费订单去支付
    function renewOrderGoPay(order_id = '',order_type = '') {
        if (!order_id) return
        var params = {
            order_id,
            order_type,
            pay_type: payType,
            openid: urlParam.openid,
            is_mobine: 1,
        }
        var success = function(res) {
            paySuccess(res);
        }
        common.httpV20('/merchant/merchant.pay/goPay',params,success)
    }

    // 支付成功
    function paySuccess(data) {
        if (payType == 'balance') {
            // 商家余额支付
            mui.toast('支付成功');
            setTimeout(function () {
                redirect('back')
            }, 1000);
        } else if(payType == 'weixin' && data.weixin_param) {
            layer.closeAll();
            layer.open({
                type:1,
                content:'<div onclick="location.reload()" style="width:250px;top:50%;position:fixed;background:white;padding:20px 0;left:50%;margin-left:-125px;text-align:center;border-radius:10px;margin-top:-27px;">正在唤起微信支付，点击刷新</div>',
                shadeClose:false,
                success:function(){}
            });
            var weixinParam = data.weixin_param;
            WeixinJSBridge.invoke("getBrandWCPayRequest",weixinParam,function(res){
                WeixinJSBridge.log(res.err_msg);
                if(res.err_msg=="get_brand_wcpay_request:cancel"){
                    setTimeout(function(){
                        window.location.reload();
                    },200);
                    layer.open({type:2,content:'刷新页面中',shadeClose:false});
                }else if(res.err_msg=="get_brand_wcpay_request:ok"){
                    setTimeout("window.location.href = '"+data.redirctUrl+"'",200);
                    layer.open({type:2,content:'跳转加载中',shadeClose:false});
                }else{
                    $(".submit-btn").removeAttr("disabled");
                    $(".submit-btn").html("确认支付");
                }
                layer.closeAll();
                searchPayStatus(data.orderid);
            });
        } else if (payType == 'alipay') {
            res.info && openWebviewUrl(res.info)
        }
    }

    // 支付状态查询
    function searchPayStatus(orderid = '') {
        if (!orderid) return
        var params = {
            orderid: orderid
        }
        var success = function(res) {
            if (res.paid && res.paid == 1) {
                mui.toast('支付成功');
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
                setTimeout(function () {
                    redirect_self('newMarketing_storeMarket.html')
                }, 1000);
            } else {
                timer = setTimeout(function(){
                    searchPayStatus(orderid);
                },3000)
            }
        }
        common.httpV20('/new_marketing/merchant.Order/searchPayStatus',params,success)
    }
})