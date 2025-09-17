
setTitleStyle(L_('历史账单'), '#333333', '#ffffff')
const dt = L_('全部日期');
const nowDate = new Date();
var _this = null;

var app = new Vue({
    el: '#app',
    data: {
        // 类型筛选
        type: "",
        typeList: [],
        // 日期筛选
        dateTitle: dateToString(nowDate),
        date: dateToString(nowDate),
        selectedDate: nowDate,
        selectAllDate: false,
        startDate: new Date('2020/01/01'),
        endDate: nowDate,
        // 明细
        expend: 0,
        income: 0,
        noData: false,
        recordList: [],
        // 分页
        loadTips: '',
        page: 1,
        pageSize: 20,
        isLoading: false,
        hasMore: true, //没有更多数据
        config: {},
        currency: '￥'
    },
    created() {
        _this = this;
        this.config = common.getCache('config', true)
        if (this.config) {
            const {currency = '￥'} = this.config
            this.currency = currency
        }
        if (this.config.income_types && this.config.income_types.length) {
            // 转化成固定格式
            for (var i = 0; i < this.config.income_types.length; i++) {
                var item = this.config.income_types[i]
                if (i == 0) {
                    this.type = item.value
                    this.typeTitle = item.name
                }
                this.typeList.push({
                    text: item.name,
                    value: item.value
                })
            }

        }
        this.getRecordList()
    },
    mounted() {
        window.onscroll = function () {
            if (parseInt(getScrollTop() + getWindowHeight()) >= getScrollHeight()) {
                //此时滚动条已经下拉到底部啦
                if (this.isLoading || !_this.hasMore) {
                    return false;
                }
                _this.page++;
                _this.getRecordList()
            }
        }
    },
    methods: {
        onDateSelect(value) {
            console.log(value)
            this.selectAllDate = false
            this.selectedDate = value
            this.dateTitle = dateToString(value)
            this.$refs.datePicker.toggle()
            this.date = this.dateTitle
            this.page = 1
            this.goTop()
            this.getRecordList()
        },
        allDateClick() {
            this.selectAllDate = true
            this.selectedDate = null
            this.dateTitle = dt
            this.$refs.datePicker.toggle()
            this.date = ''
            this.page = 1
            this.goTop()
            this.getRecordList()
        },
        formatterDate(day) {
            if (nowDate.setHours(0, 0, 0, 0) == day.date.setHours(0, 0, 0, 0)) {
                day.text = L_('今天')
            }
            return day;
        },
        onTypeChange(val) {
            this.type = val;
            this.page = 1;
            this.goTop()
            this.getRecordList()
        },
        goTop() {
            document.body.scrollIntoView()
        },
        getRecordList() {
            this.isLoading = true
            this.loadTips = L_('加载中...')
            common.http('/deliver/User/incomeDetail', {
                date: this.date,
                page: this.page,
                pageSize: this.pageSize,
                type: this.type,
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