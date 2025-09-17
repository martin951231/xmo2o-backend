$(document).ready(function () {
    mui.init();
    // cat 分类 package 套餐
    var tabType = 'cat'; 
    // 分类id
    var catId = '';

    // 分类是否显示
    var isShowCatList = false;

    // 关闭遮罩层
    function closeMask(isClickMask = false) {
        $('.cat-list').removeClass('active');
        $('.mui-icon-arrowdown').removeClass('cr-orange active');
        isShowCatList = false;
        $('html').removeClass('overHidden');
        if (!isClickMask) {
            mask.close();
        }
    }

    var mask = mui.createMask(function() {
        closeMask(true);
    });

    // 分类选中
    function catItemActive() {
        $('.cat-item').removeClass('active');
        $('.cat-item').each(function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            if (id == catId) {
                $(this).addClass('active');
                $('.cat-title').text(name);
            }
        })
    }

    // 分类列表
    function getCatList() {
        var success = function(data) {
            if (data && data.length) {
                catId = data[0].value;
                catItemActive();
                getCatStoreList();
                laytpl(document.getElementById('catlist').innerHTML).render(data, function(html){
                    $('.cat-list').append(html);
                });
            }
        }
        common.httpV20('/new_marketing/merchant.MarketingPackage/getCatList',{notip:true},success);
    }
    getCatList();
    
    // 分类店铺
    function getCatStoreList(){
        $('.loading1').show();
        var params = {
            id: catId
        }
        var success = function(data) {
            $('.loading1').hide();
            catItemActive();
            if (isShowCatList) {
                closeMask()
            }
            if (data && data.length) {
                data.forEach(item => {
                    item.cat_img = item.cat_img || './images/newMarketing/catStoreImg.png';
                })
                $('.catStore').show();
                laytpl(document.getElementById('cardlist').innerHTML).render(data, function(html){
                    $('.catStore-list').html(html);
                    $('.pullup1').show();
                });
            } else {
                $('.empty').show();
            }
        }
        common.httpV20('/new_marketing/merchant.MarketingPackage/getCatSearchList',params,success);
    }

    // 套餐店铺
    function getPackageStoreList() {
        $('.loading1').show();
        var success = function(data) {
            data = data.data
            $('.loading1').hide();
            if (data && data.length) {
                data.forEach(item => {
                    item.package_detail = ''
                    item.cat_img = item.cat_img || './images/newMarketing/packageDefaultImg.png';
                    if (item.store_detail && item.store_detail.length) {
                        item.package_detail = item.store_detail.map(subItem => subItem.num + '个' + subItem.type_name).join('+')
                    }
                })
                laytpl(document.getElementById('cardlist').innerHTML).render(data, function(html){
                    $('.package-list').html(html);
                    $('.pullup1').show();
                });
            } else {
                $('.empty').show();
            }
        }
        common.httpV20('/new_marketing/merchant.MarketingPackage/getSearchList',{},success);
    }

    getPackageStoreList();

    // 购买记录
    mui('.mui-bar').on('tap','.order-list',function(e){
        openWindow({
            url:'newMarketing_orderList.html',
            id:'newMarketing_orderList'
        });
    });

    // 分类 套餐切换
    mui('body').on('tap','.tab-wrap span',function(e){
        if ($(this).data('type') == tabType) return
        tabType = $(this).data('type');
        $('.tab-wrap span').removeClass('active');
        $(this).addClass('active');
        if (tabType == 'cat') {
            $('.catStore').show();
            $('.package-list').hide();
        } else {
            $('.catStore').hide();
            $('.package-list').show();
            closeMask();
        }
    });
    
    // 分类点击显隐
    mui('.mui-content').on('tap','.cat-title-wrap',function(e){
        isShowCatList = !isShowCatList
        if (isShowCatList) {
            $('.cat-list').addClass('active');
            $('.mui-icon-arrowdown').addClass('cr-orange active');
            $('html').addClass('overHidden');
            mask.show();
        } else {
            closeMask();
        }
    });

    // 分类点击
    mui('.mui-content').on('tap','.cat-item',function(e){
        if (catId == $(this).data('id')) {
            closeMask();
            return
        } 
        catId = $(this).data('id');
        catItemActive();
        getCatStoreList();
    });

    // 购买
    mui('.mui-content').on('tap','.btn',function(e){
        var id = $(this).data('id');
        openWindow({
            url: 'newMarketing_confirmOrder.html?id=' + id + '&type=' + tabType,
            id: 'newMarketing_confirmOrder'
        });
    });
})