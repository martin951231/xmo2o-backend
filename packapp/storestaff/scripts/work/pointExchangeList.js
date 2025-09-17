// 在 #app 标签下渲染一个按钮组件
new Vue({
    el: '#app',
    template: ``,
    data() {
        return {
            loading: false,
            finished: false,
            params: {
                key: '',
                page: 1,
                pageSize: 10
            },
            dataList: [],
        }
    },
    created() {
    },
    methods: {
        getDataList(type) {
            common.httpV20('/merchant/storestaff.CardGoods/exchangeStaffList', this.params, data => {
                if (type == 'search') {
                    this.dataList = data.data;

                } else {
                    this.dataList = this.dataList.concat(data.data)
                }
                // 加载状态结束
                this.loading = false;
                // 数据全部加载完成
                if (data.data.length == 0) {
                    //没有更多数据了
                    this.finished = true;
                }
                this.params.page++;
            },err=>{
                this.finished = true;
            });
        },
        //返回上一页
        toBack() {
            if (common.checkApp()) {
                if (common.checkAndroidApp()) {
                    window.pigcmspackapp.closewebview(2);
                } else {
                    common.iosFunction('closewebview/2', {
                        name: 'closewebview',
                        type: '2'
                    });
                }
            } else {
                window.history.go(-1);
            }

        },
        //确定兑换
        confirmExchange(item) {
            vant.Dialog.confirm({
                    title: '提示',
                    message: '确认兑换吗？',
                    showCancelButton: true,
                    confirmButtonColor: '#2ecc71'
                })
                .then(() => {
                    common.httpV20('/merchant/storestaff.CardGoods/useOrder', {id: item.id} , data => {
                        vant.Toast.success(L_('兑换成功!'));
                        this.dataList = [];
                        this.params.page = 1;
                        this.getDataList();
                    });
                })
                .catch(() => {
                    // on cancel
                });
        },
        //搜索事件
        onSearch(e) {
            this.params.page = 1;
            this.getDataList('search');
        },
        onListSearch(){
            this.onSearch();
        },
        onLoad() {
            // 异步更新数据
            this.getDataList();
        },
    }
});