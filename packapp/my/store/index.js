var pageLoading = weui.loading('loading')
function getBaiduLocationAfter (result) {
	console.log('转码为百度坐标', result, app)
	app.getPageInfo({status: 'successs', lng: result.result[0].x, lat: result.result[0].y})
}
var app = new Vue({
    el: '#app',
    data: {
        store_more_style: '',
        store_more_txt_style: '',
        food_more_style: '',
        food_more_txt_style: '',
        reply_list: [1, 2],
        pageInfo: '',
        store_score: [],
        showBigImgBox: false,
        storeNameHeight: 37,
        showStoreNameFixed: false,
        tabTitleScrollTop: '',
        showTabTitleFixed: false,
        tabItemTopArr: [],
        tabAcitiveIndex: 0,
        store_id: '',
        showTimeBox: false,
        phoneActionSheet: [],
        lng: 0,
        lat: 0,
        showPhoneBox: false,
		tabTitle: [],
    },
    mounted: function() {
      console.log('店铺链接参数', urlParam)
        var _this = this
        if (urlParam.store_id) {
            _this.store_id = urlParam.store_id

            getUserLocation({historyTime: 0, errorAction:1, okFunction: this.getBaiduLocation, errorFunction: this.getBaiduLocation});
            $(window).on('scroll', function () {
                _this.scrollHandle()
            })
        } else {
            pageLoading.hide()
            weui.alert(L_('当前店铺不存在'), {
                buttons: [{
                    label: L_('确定'),
                    type: 'primary'
                }]
            });
        }
    },
    methods: {
		goAllReply: function() {
			var pagePath = location.href.split('index.html')[0] + 'reply.html?store_id=' + this.store_id + '&total=' + this.pageInfo.reply_count;
			this.goUrl(pagePath);
		},
		getBaiduLocation: function(e) {
			console.log('获取到定位', e)
			if (e && e.status == 'successs') {
                this.getPageInfo(e)
				geoconv('getBaiduLocationAfter', e.lng, e.lat)
			} 
		},
        // 点击汽车图标
        checkMap: function() {
            openMapBefore({
                lat: this.pageInfo.now_store.lat,
                lng: this.pageInfo.now_store.long,
                name: this.pageInfo.now_store.name,
                address: this.pageInfo.now_store.adress,
				type: 'baidu'
            })
        },
        // 点击电话图标
        checkPhone: function() {
            var _this = this

          if (this.pageInfo.now_store.phone.length == 1 && this.pageInfo.now_store.phone[0]) {
              this.callPhone(this.pageInfo.now_store.phone[0])
          } else if (this.pageInfo.now_store.phone.length > 1) {
              this.showPhoneBox = true
          } else {
              weui.alert(L_('当前店铺暂未设置电话'), {
                  buttons: [{
                      label: L_('确定'),
                      type: 'primary'
                  }]
              });
          }
        },
        callPhone: function(phone) {
            window.location.href = 'tel:' + phone
        },
        // 点击tab标题
        pageScrollTo: function(index) {
			var _this = this
            $('body,html').animate({ scrollTop: this.tabItemTopArr[index] }, 500)
			setTimeout(function(){
				_this.tabAcitiveIndex = index
			}, 550)
        },
        // 收藏店铺
        collectStore: function() {
            var _this = this
            if ((common.cookie.get('login_status') && common.cookie.get('login_status') == 'login') || urlParam.ticket) {
                common.http('Merchant&a=store_collection', {store_id: this.store_id, noTip: true}, function (data) {
                    if (data) {
                        _this.pageInfo.now_store.is_collect = !_this.pageInfo.now_store.is_collect
                        if (_this.pageInfo.now_store.is_collect) {
                            weui.toast(data, 1000)
                        }

                    }
                })
            } else {
                var url = 'https://' + document.domain + '/packapp/my/login.html?back=back'
                openWebviewUrl(url)
            }
        },
        // 滚动处理函数
        scrollHandle: function() {
            var scrollTop = $(window).scrollTop()
            var _this = this
            // 判断店铺名称固定
            if ( scrollTop >= this.storeNameHeight) {
                if (!this.showStoreNameFixed) {
                    this.showStoreNameFixed = true
                }
            } else {
                if (this.showStoreNameFixed) {
                    this.showStoreNameFixed = false
                }
            }

            

            // 判断当前滚动位置处于哪个标题
			if (_this.tabTitle.length > 0) {
				// 判断中间tab标题固定
				if ( scrollTop >= this.tabTitleScrollTop) {
				    if (!this.showTabTitleFixed) {
				        this.showTabTitleFixed = true
				
				    }
				} else {
				    if (this.showTabTitleFixed) {
				        this.showTabTitleFixed = false
				    }
				}
				if ( _this.tabAcitiveIndex < _this.tabItemTopArr.length - 1 && scrollTop >= _this.tabItemTopArr[_this.tabAcitiveIndex + 1]) {
					_this.tabAcitiveIndex = _this.tabAcitiveIndex + 1
					console.log('111', _this.tabAcitiveIndex)
				}
				if ( _this.tabAcitiveIndex > 0 && scrollTop < _this.tabItemTopArr[_this.tabAcitiveIndex]) {
					_this.tabAcitiveIndex = _this.tabAcitiveIndex - 1
					console.log('111', _this.tabAcitiveIndex)
				}
			}
        },
        // 查看大图
        checkBigImg: function(index) {
            var _this = this
            this.showBigImgBox = true
            this.$nextTick(function () {
                var imgSwiper = new Swiper('.bigImgBox .swiper-container', {
                    zoom: true,
                    pagination: _this.pageInfo.now_store.all_pic.length > 1 ? '.bigImgBox .swiper-pagination' : '',
                    initialSlide: index,
                    observer: true
                });
            })
        },
        // 关闭查看大图
        closeBigImg: function() {
            this.showBigImgBox = false
        },
        openTimeBox: function() {
            this.showTimeBox = true
        },
        closeMask: function(name) {
            this[name] = false
        },
        // 打开链接
        goUrl: function(url, id) {
			if (!id) {
				if (url.indexOf('c=Shop&a=index&shop-id=') >= 0) {
				  var params = GetRequest(url)
				  id = params['shop-id']
				}else if (url.indexOf('c=Shop&a=index&shop-') >= 0 || url.indexOf('c=Shop&a=index#shop-') >= 0) {
					var tmpArr = url.split('-');
				}else if (url.indexOf('c=Shop&a=classic_shop&shop_id=') >= 0) {
				  var params = GetRequest(url)
				}
			}
            if ( id && (url.indexOf('c=Shop&a=index') != -1  || url.indexOf('c=Shop&a=classic_shop') != -1 )) {
                if (common.checkWeixinApp()) {
					
                    wx.miniProgram.navigateTo({url:'/pages/shop_new/shopDetail/shopDetail?store_id=' + id})
                } else {
					
                    location.href = url
                }
            } else {
                openWebviewUrl(url)
            }
        },
        // 获取页面信息
        getPageInfo: function(e) {
            var _this = this
            var param = {
                store_id: _this.store_id
            }
            if (e.status == 'successs') {
                _this.lng = param.lng = e.lng
                _this.lat = param.lat = e.lat
            }
            pageLoading.hide()
            common.http('Merchant&a=shop', param, function(data) {

                if (data) {
                    _this.store_score = _this.scoreHandle(data.now_store.store_score)
                    if (data.reply_list && data.reply_list.length > 0) {
                        for (var i = 0; i < data.reply_list.length; i++) {
                            if (data.reply_list[i].score) {
                                data.reply_list[i].scoreArr = _this.scoreHandle(data.reply_list[i].score)
                            }
                        }
                    }
                    data.now_store.phone = data.now_store.phone.split(' ')
					
					var tabTitle = []
					if (data.store_group_list.length > 0  || data.now_store.have_card) {
						tabTitle.push(data.store_group_list_name)
					}
					if (data.store_meal_recommend_list.length > 0) {
						tabTitle.push('招牌菜')
					}
					if (data.reply_count > 0) {
						tabTitle.push('评价')
					}
					if (data.mer_store_count > 1) {
						tabTitle.push('商家信息')
					}
					_this.tabTitle = tabTitle
					
                    _this.pageInfo = data
                    console.log('店铺数据', data)
                    if (_this.pageInfo.guess_list.length > 0) {
                        for (var i = 0; i < _this.pageInfo.guess_list.length; i++ ) {
                            var item = _this.pageInfo.guess_list[i]
                            if (item.business_label != '') {
                                var arr = []
                                if (item.re_type == 'store') {
                                    arr[0] = item.business_label.indexOf('团') != -1
                                    arr[1] = item.business_label.indexOf('餐') != -1
                                    arr[2] = item.business_label.indexOf('外') != -1
                                } else if (item.re_type == 'meal') {
                                    arr = item.business_label.split(',')
                                }

                                item.business_label_arr = arr
                            }
                            if (item.re_type == 'hotel' && item.label != '') {
                                item.label_arr = item.label.split(',')
                            }
                            if (item.re_type == 'group') {
                                item.discount = (item.price / item.old_price).toFixed(2)
                                item.discount = item.discount * 100
                                item.discount = ((item.discount > 0 && item.discount <= 50) || (item.price == 0 && item.old_price > 0)) ? true : false
                                item.isShowDiscount = (item.discount > 0 && item.discount <= 50) || (item.price == 0 && item.old_price > 0)
                            }
                        }
                    }
                    _this.initial()
                }
            })
        },
        // 初始化页面swiper
        initial: function() {
            var _this = this
            this.$nextTick(function () {
                var swiper = new Swiper('.special-food .swiper-container', {
                    slidesPerView: 'auto',
                    onTouchMove: function(swiper, e) {
                        _this.swiperTouch( _this.pageInfo.store_meal_recommend_list.length, swiper, e, 'food')
                    },
                    onTouchEnd: function (swiper, e) {
                        _this.swiperTouchEnd(_this.pageInfo.store_meal_recommend_list.length, swiper, e, 'food')
                    }
                });
                var top_swiper = new Swiper('.header .swiper-container', {
                    slidesPerView: 'auto'
                });
                var swiper_pintuan = new Swiper('.shop-item-pintuan .swiper-container', {
                    direction: 'vertical',
                    initialSlide :0,
                    autoplay: 2000,
                    loop:true,
                    observer:true,
                    observeParents:true
                });

                _this.tabTitleScrollTop = _this.$refs.tabTitle.offsetTop - 42
                _this.getTabItemArr()
            })

        },
        getTabItemArr: function() { 
            var _this = this
            // 标题固定之后获取的top值才是准确的
            this.$nextTick(function(){
				var arr = document.getElementsByClassName('tabItem')
                for (var i = 0; i < arr.length; i++) {
                    var top = arr[i].offsetTop - 88
                    _this.tabItemTopArr.push(top)
                }
            })

        },

        // 店铺图片、招牌菜左滑查看更多
        swiperTouch: function(length, swiper, e, tag) {
            if (swiper.translate < 0 && swiper.isEnd) {
                var width = 150 * length - swiper.width
                var moreWidth = -swiper.translate - width - 10
                if (moreWidth/4 - 6 <= 20) {
                    this[tag + '_more_style'] = 'width:' + moreWidth + 'px;right:-' + moreWidth/2 + 'px;'
                    this[tag + '_more_txt_style'] = 'width:12px;padding-left:' + (moreWidth/4 ) + 'px;'
                } else if (moreWidth/4 - 6 > 30) {
                    this.goUrl(this.pageInfo.store_meal_recommend_url)
                }

            }
        },

        swiperTouchEnd: function(length, swiper, e, tag) {
            var _this = this
            if (swiper.translate < 0 && swiper.isEnd) {
                var width = 150 * length - swiper.width
                var moreWidth = -swiper.getWrapperTranslate() - width - 10
                if (moreWidth > 0){
                    var timer = setInterval(function () {
                        moreWidth = -swiper.getWrapperTranslate() - width - 10
                        if (moreWidth <= 0) {
                            clearInterval(timer)
                            return
                        }
                        _this[tag + '_more_style'] = 'width:' + moreWidth + 'px;right:-' + moreWidth/2 + 'px;'
                        _this[tag + '_more_txt_style'] = 'width:12px;padding-left:' + (moreWidth/4 ) + 'px;'
                    }, 10)
                }

            }
        },

        // 评分处理
        scoreHandle: function(score) {
            var arr = []
            arr[0] = Math.floor(score)
            if (score > arr[0] && score < 5) {
                arr[1] = 1
                arr[2] = 4 - arr[0]
            }
            return arr
        },
        // 阻止点击默认事件
        prevent: function() {
            return false
        }
    }
})