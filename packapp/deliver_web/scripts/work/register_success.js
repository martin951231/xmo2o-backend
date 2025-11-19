setTitleStyle(L_('注册成功'), '#333333', '')
var app = new Vue({
    el: '#app',
    data: {
        phone: '',
        showBtn: false,
        btnUrl: '',
    },
    mounted() {
        if (common.checkApp()) {
            this.showBtn = false
        } else {
            this.showBtn = true
        }
        var config = common.getCache('config', true)
        if (config.site_phone && config.site_phone.length) {
            this.phone = config.site_phone[0]
        }
    },
    methods: {
        downLoad() {
            if (configData.app_download_url) {
                location.href = configData.app_download_url
            } else {
                getConfig(function (config) {
                    location.href = config.app_download_url
                })
            }
        }
    }
})