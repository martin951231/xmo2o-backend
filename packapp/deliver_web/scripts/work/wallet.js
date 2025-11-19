var _this = null;
setTitleStyle('', '#333333', '#ffffff')
var app = new Vue({
    el: '#app',
    data: {
        total: 0, //总收入
        fee: 0, //小费
        expend: 0, // 今日支出
        income: 0, // 今日收入
        recordList: [],
        noData: false,
        hasMore: true,
        page: 1,
        pageSize: 10,
        isLoading: false,
        loadTips: '',
        currencyTxt: L_('元'),
        currency: '￥'
    },
    created() {
        _this = this;
        getConfig(function (config) {
            if (config) {
                const {currency_txt = L_('元'),currency = '￥'} = config
                _this.currencyTxt = currency_txt
                _this.currency = currency
            }
        })    
        this.getWalletData()
        this.getRecordList()
    },
    actived() {
        this.getWalletData()
        this.getRecordList()
    },
    mounted() {
        window.onscroll = function () {
            if (parseInt(getScrollTop() + getWindowHeight()) >= getScrollHeight()) {
                //此时滚动条已经下拉到底部啦
                if (_this.isLoading || !_this.hasMore) {
                    return false;
                }
                _this.page++;
                console.log('onscroll to bottom')
                _this.getRecordList()
            }
        }
    },
    methods: {
        getWalletData() {
            common.http('/deliver/User/wallet', { noTip: true }, function (data) {
                _this.total = (data.money * 1).toFixed(2)
                _this.fee = (data.tip_money * 1).toFixed(2)
            })
        },
        getRecordList() {
            var today = dateToString(new Date())
            this.isLoading = true
            _this.loadTips = L_('加载中...')
            common.http('/deliver/User/incomeDetail', {
                date: today,
                page: this.page,
                pageSize: this.pageSize,
                type: 0,//0表示全部收支
            }, function (data) {
                _this.expend = (data.total_expend * 1).toFixed(2)
                _this.income = (data.total_income * 1).toFixed(2)
                if (_this.page == 1) {
                    _this.noData = data.lists.length ? false : true;
                    _this.recordList = data.lists
                } else {
                    _this.recordList = _this.recordList.concat(data.lists)
                }
                if (data.lists.length < _this.pageSize) {
                    _this.hasMore = false
                    _this.loadTips = L_('没有更多数据了')
                } else {
                    _this.hasMore = true
                    _this.loadTips = L_('上拉加载更多数据')
                }
                _this.isLoading = false
            })
        }
    }
})