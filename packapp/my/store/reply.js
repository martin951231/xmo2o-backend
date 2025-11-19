
var app = new Vue({
    el: '#app',
    data: {
        reply_list: [],
        showBigImgBox: false,
        store_id: 342,
        imgs: [],
        page: 1,
        pagesize: 20,
        isLoading: false,
        isComplete: false,
        isPostComplete: true,
        totalNum: 0
    },
    mounted: function() {
        var _this = this
        if (urlParam.store_id) {
            _this.store_id = urlParam.store_id
            _this.totalNum = urlParam.total
            this.getReplyList()
            $(document).on('scroll', window, function(e){
                var distance = $('.bottom-txt').offset().top - $(document).scrollTop() + $('.bottom-txt').height() - $(window).height()
                if (!_this.isComplete && distance <= 40 && !_this.isPostComplete) {
                    _this.isPostComplete = true
                    _this.getReplyList()
                }

            })
        }

    },
    methods: {
        // 查看大图
        checkBigImg: function(index, i) {
            var _this = this
            this.imgs = this.reply_list[index].pic
            this.showBigImgBox = true
            this.$nextTick(function () {
                var imgSwiper = new Swiper('.bigImgBox .swiper-container', {
                    pagination: _this.imgs.length > 1 ? '.bigImgBox .swiper-pagination' : '',
                    initialSlide: i,
                    zoom: true,
                });
            })
        },
        // 关闭查看大图
        closeBigImg: function() {
            this.showBigImgBox = false
        },
        // 打开链接
        goUrl: function(url) {
            openWebviewUrl(url)
        },
        // 获取页面信息
        getReplyList: function(e) {
            var _this = this
            var param = {
                store_id: _this.store_id,
                page: _this.page,
                pagesize: _this.pagesize
            }
            common.http('Merchant&a=store_reply', param, function(data) {
                _this.isPostComplete = false
                if (data && data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        if (data[i].score) {
                            data[i].scoreArr = _this.scoreHandle(data[i].score)
                        }
                    }
                    _this.reply_list = _this.reply_list.concat(data)
                }
                if (data.length < _this.pagesize) {
                    _this.isComplete = true
                    _this.isLoading = false
                } else {
                    _this.isLoading = true
                    _this.page++
                }
            })
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