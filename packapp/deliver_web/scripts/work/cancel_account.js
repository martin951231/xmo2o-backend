setTitleStyle("注销申请", "#333333", "#ffffff");
var app = new Vue({
    el: '#app',
    data: {
        detail: null,
        flag: true
    },
    created() {
        this.getDetail()
    },
    methods: {
        getDetail() {
            common.http('/deliver/user/logoffCheck', {
                // noTip: true
            }, data => {
                this.detail = data
                if (data.list.length > 0) {
                    let arr = [];
                    data.list.forEach(v => {
                        if (v.status == 1) {
                            arr.push(v)
                        }
                    })
                    if (data.list.length == arr.length) {
                        this.flag = false;
                    }
                }
            })
        },
        cancellation() {
            if (this.detail && this.detail.check) {
                vant.Dialog.confirm({
                        title: '提示',
                        message: '是否注销此账号？',
                        confirmButtonColor: '#2D85FF',
                    })
                    .then(() => {
                        common.http('/deliver/user/logoffUser', {
                            noTip: true
                        }, data => {
                            if (data.status == 1) {
                                if (common.checkIos()) {
                                    window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage({
                                        name: 'backToLogin'
                                    });
                                } else {
                                    window.pigcmspackapp.backToLogin();
                                }
                            } else {
                                vant.Toast('注销失败');
                            }

                        })
                    })
                    .catch(() => {
                        // on cancel
                    });
            } else {
                vant.Toast('此账号不符合注销要求');
            }

        }
    }
})