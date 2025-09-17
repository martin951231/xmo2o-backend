// 在 #app 标签下渲染一个按钮组件
new Vue({
    el: '#msgListPage',
    template: ``,
    data() {
        return {
            value: '',
            loading: false,
            finished: false,
            params: {
                page: 1,
                pageSize: 10
            },
            dataList: [],
            activeNames: []
        }
    },
    created() {},
    methods: {
        onClickLeft() {
            redirect('back')
        },
        onClickRight() {
            if(this.dataList.length) {
                this.handleRead('',1)
            }
        },
        getDataList() {
            let success = (res) => {
                // is_read 1-已读，0-未读
                if(this.params.page == 1) {
                    this.dataList = res.data
                } else {
                    this.dataList = this.dataList.concat(res.data)
                }
                
                // 加载状态结束
                this.loading = false;
                // 数据全部加载完成
               if (res.data.length == 0) {
                   //没有更多数据了
                   this.finished = true;
               }
               if(this.dataList.length) {
                    $('.box-list-collapse').show()
               }
               this.params.page++;
            }
            common.httpV20('/warn/merchant.Notice/getNoticeList', this.params, success);
        },
        onLoad() {
            // 异步更新数据
            this.getDataList();
        },
        // 是否全部已读，1：是，0：否
        handleRead(id = '',is_all = 0) {
            let params = {
                id,
                is_all
            }
            let success = (res) => {
                if(is_all == 1) {
                    this.params.page = 1
                    this.getDataList();
                } else {
                    this.dataList = this.dataList.map(item => {
                        if(item.pigcms_id == id) {
                            item.is_read = 1
                        }
                        return item
                    }) 
                }
            }
            common.httpV20('/warn/merchant.Notice/read', params, success);
        },
        msgChange(activeNames) {
            if (activeNames.length) {
                this.dataList.forEach((item) => {
                    if (item.is_read != 1 && activeNames.findIndex((keyItem) => keyItem == item.pigcms_id) != -1) {
                        this.handleRead(item.pigcms_id)
                    }
                })
            }
        }
    }
});