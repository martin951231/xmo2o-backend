// 在 #app 标签下渲染一个按钮组件
new Vue({
    el: '#app',
    template: ``,
    data() {
        return {
            value: '',
            loading: false,
            finished: false,
            showPicker: false,
            params: {
                keywords: '',
                status: 0,
                page: 1,
                page_size: 10
            },
            dataList: [],
            searchTypeText: L_('全部'),
            columns: [{
                    text: L_('全部'),
                    value: 0,
                },
                {
                    text: L_('报名中'),
                    value: 1,
                },
                {
                    text: L_('活动开始'),
                    value: 2,
                },
                {
                    text: L_('报名结束'),
                    value: 3,
                },
            ],
        }
    },
    created() {

    },
    methods: {
        getDataList(type) {
            
            common.httpV20('/life_tools/storestaff.LifeToolsAppoint/getAppointList', this.params, data => {
                if(type == 'search'){
                    this.dataList = data.list;
                   
                }else{
                    this.dataList = this.dataList.concat(data.list)
                }
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
            if (common.checkApp()) {
                if (common.checkAndroidApp()) {
                    window.pigcmspackapp.closewebview(2);
                } else {
                    common.iosFunction('closewebview/2', { name: 'closewebview', type: '2' });
                }
            } else {
                window.history.go(-1);
            }
        },
        //搜索事件
        onSearch(e) {
            this.params.keywords = e;
            this.params.page = 1;
            this.getDataList('search');
        },
        //打开选择框
        openSecect() {
            this.showPicker = true
        },
        onConfirm(value) {
            this.searchTypeText = value.text
            this.params.status = value.value
            this.showPicker = false;
            this.params.page = 1;
            this.getDataList('search');
        },
        toMerChantDetail(item) {
            window.location.href = "merchantActivityDetails.html?appoint_id=" + item.appoint_id;
        },
        onLoad() {
            // 异步更新数据
            this.getDataList();
        },
    }
});