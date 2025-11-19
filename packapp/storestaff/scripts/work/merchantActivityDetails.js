// 在 #app 标签下渲染一个按钮组件
new Vue({
    el: '#app',
    template: ``,
    data() {
        return {
            value: '',
            detailIndex: 0,
            dataList: [],
            is_show: false,
            loading: false,
            finished: false,
            showPicker: false,
            show: false,
            sum: [],
            details: {},
            message: '',
            params: {
                keywords: '',
                appoint_id: '',
                search_type: 0,
                page: 1,
                page_size: 10
            },
            searchTypeText: L_('订单号'),
            columns: [
                {
                    text: L_('订单号'),
                    value: 1,
                },
                {
                    text: L_('昵称'),
                    value: 2,
                },
                {
                    text: L_('手机号'),
                    value: 3,
                },
            ],
            detail: null
        }
    },
    created() {
        this.params.appoint_id = this.getQueryVariable("appoint_id")
    },
    methods: {
        //核销
        writeOff(e,i) {
            vant.Dialog.alert({
                title: '提示',
                message: '确认核销吗',
                showCancelButton: true,
                confirmButtonColor: '#2ecc71'
              }).then(() => {
                common.httpV20('/life_tools/storestaff.LifeToolsAppoint/verification', {code: e.verify_code}, data => {
                    vant.Toast.success('核销成功！');
                    this.$set(this.dataList[i],'is_verify_btn',0)
                },err=>{
                    vant.Toast.fail(err.msg);
                })
              })
        },
        //退款
        refund(e,i) {
            this.show = true;
            this.detail = e;
            this.detailIndex = i;
            this.message = ''
        },
        //确认退款弹窗
        refunds(){
            vant.Dialog.alert({
                title: '提示',
                message: '确认退款吗',
                showCancelButton: true,
                confirmButtonColor: '#2ecc71'
              }).then(() => {
                common.httpV20('/life_tools/storestaff.LifeToolsAppoint/refund', {order_id:this.detail.order_id,reason:this.message}, data => {
                    this.show = false;
                    vant.Toast.success('退款成功！');
                    this.$set(this.dataList[this.detailIndex],'is_refund_btn',0)
                },err=>{
                    vant.Toast.fail(err.msg);
                })
              })
        },
        getQueryVariable(variable) {
            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split("=");
                if (pair[0] == variable) {
                    return pair[1];
                }
            }
            return (false);
        },
        getDataList(type) {
            common.httpV20('/life_tools/storestaff.LifeToolsAppoint/getAppointOrderList', this.params, data => {
                if (type == 'search') {
                    this.dataList = data.list;

                } else {
                    this.dataList = this.dataList.concat(data.list)
                    data.list.forEach(v => {
                        this.sum.push(false)
                    })

                }
                this.details = data
                // 加载状态结束
                this.loading = false;
                // 数据全部加载完成
                if (data.list.length == 0) {
                    //没有更多数据了
                    this.finished = true;
                }
                this.params.page++;
            });
        },
        //返回上一页
        toBack() {
            window.history.back();
        },
        viewImg(e, i) {
            vant.ImagePreview({
                images: e.show_value,
                startPosition: i,
            });
        },
        //搜索事件
        onSearch(e) {
            this.params.keywords = e;
            this.params.page = 1;
            this.getDataList('search');
        },
        //列表底部显示更多事件
        open_lower(i) {
            if (this.sum[i]) {
                this.$set(this.sum, i, false)
                return
            }
            this.$set(this.sum, i, true)
        },
        //打开选择框
        openSecect() {
            this.showPicker = true
        },
        onConfirm(value) {
            this.searchTypeText = value.text
            this.params.search_type = value.value
            this.showPicker = false;
            this.params.page = 1;
            this.getDataList('search');
        },
        onLoad() {
            // 异步更新数据
            this.getDataList();
        },
    }
});