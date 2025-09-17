var idTips = '* ' + L_("为保证配送服务安全监督，请如实填写身份证号");
var _this = null;
setTitleStyle(L_('注册'), '#ffffff', '#27a3f7')

window.handleFaceImg = (imgUrl) => {

}

window['handleFaceImg'] = function (imgUrl) {
    console.log('handleFaceImg success!!!!')
    _this.$set(_this.form, 'face_img', imgUrl)
}

// 注意不能用es6的语法，否则安卓低版本的手机无法运行、、
var app = new Vue({
    el: '#app',
    data: {
        form: {
            phone: '',
            name: '',
            idCard: '',
            code: '',
            face_img: ''//https://img01.yzcdn.cn/vant/leaf.jpg
        },
        agreeCheck: false,
        idPattern: /^[1-9]\d{5}(18|19|20|(3\d))\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/,
        idMessage: idTips,
        idCorrect: false,
        codeTimer: null,
        codeTime: 60,
        showCodeBtn: true,
        titleHeight: titleHeight,
        config: {},
        showFaceIn: false, // 是否显示人脸认证入口
        popupShow: false,
        popupActionType: '',
        pickerColumns: [],
        addressValue: '',
        addressList: [],
        main_color: '#29a4f8',
    },
    created() {
        console.log('-------进入页面')
        _this = this;
    },
    mounted() {
        getConfig(function (config) {
            _this.config = config
            // config.open_deliver_face_recognition == 1 是开启人像认证
            if(common.checkApp() && config && config.open_deliver_face_recognition ==1){
                _this.showFaceIn = true
            }
        })     
    },
    methods: {
        // 校验电话号码
        telFormatter(val) {
            return val.replace(/[^\d]/g, '')
        },
        // 校验身份证号
        idFormatter(val) {
            this.idMessage = idTips
            this.idCorrect = true
            return val
        },
        // 人像认证
        addFaceRecognition() {
            if(this.form.face_img) {
                return
            }
            if (common.checkIos()) {
                window.webkit.messageHandlers.PackAppJSBridgeMethod.postMessage({name: 'addFaceRecognition'});
            } else {
                window.pigcmspackapp.addFaceRecognition();
            }
        },
        // 获取验证码
        getCode() {
            if (this.form.phone) {
                if (!this.showCodeBtn) {
                    return;
                }
                // 请求发送验证码
                common.http('/deliver/User/sendCode', { phone: this.form.phone, from: 'register', noTip: true }, function (data) {
                    _this.$toast(L_('验证码发送成功')+'~')
                    _this.showCodeBtn = false
                    _this.codeTime--
                    _this.codeTimer = setInterval(function () {
                        _this.codeTime--
                        if (_this.codeTime == 0) {
                            clearInterval(_this.codeTimer)
                            _this.codeTime = 60
                            _this.codeTimer = null
                            _this.showCodeBtn = true
                        }
                    }, 1000)
                })
            } else {
                this.$toast(L_('请输入手机号码~'));
            }
        },
        goUrl(type) {
            console.log(type)
            let url = ''
            if (type == 'agreement') {
                url = this.config.register_agreement_url
            }
            if (type == 'policy') {
                url = this.config.privacy_policy_url
            }
            openWebviewUrl(url, type)
        },
        // 注册
        onSubmit(values) {
            if (this.idCorrect) {
                if (!this.agreeCheck) {
                    this.$toast(L_('同意用户协议和隐私政策才能注册哦')+'~');
                    return;
                }
                let params = {
                    phone: values.phone,
                    truename: values.name,
                    card_number: values.idCard,
                    code: values.code,
                    province_id: this.form.province_id,//省份
                    city_id: this.form.city_id,//城市
                    lat: this.form.lat,//经度
                    lng: this.form.lng,//纬度
                    adress: this.form.adress,//地址
                    noTip: true,
                }
                common.http('/deliver/User/register',params,function (data) {
                    _this.$toast(L_('注册成功')+'~')
                    openWebviewUrl('register_success.html')
                })
            } else {
                this.$toast(L_('请输入正确的身份证号码')+'~');
            }
        },
        //打开意向城市选择/去意向工作地址
        openPopup(type){
            this.popupActionType = type;
            if(this.popupActionType == 'intended_cities'){
                if(this.pickerColumns.length == 0){
                    common.http('/deliver/User/registerArea',{noTip: false},res=>{
                        this.pickerColumns = res;
                    })
                }
                this.popupShow = true;
            }else{
                if(!this.form.province_id || !this.form.city_id){
                    vant.Dialog.confirm({
                        title: L_('提示'),
                        message: L_('请选择意向工作城市后再选择意向工作地址'),
                        confirmButtonText: L_('选择意向工作城市'),
                        confirmButtonColor:this.main_color,
                      }).then(() => {
                        this.openPopup('intended_cities');
                      }).catch(() => {});
                    return
                }
                this.popupShow = true;
            }
        },
        pickerConfirm(e,i){
            //意向工作城市
            if(this.popupActionType == 'intended_cities'){
                let item1 = this.pickerColumns[i[0]]
                let item2 = ''
                if(item1 && item1.children.length){
                    item2 = item1.children[i[1]]
                }
                if(this.form.province_id != item1.value || this.form.city_id != item2.value){
                    //重置
                    this.form.adress = ''
                    this.form.lat = ''
                    this.form.lng = ''
                    this.addressList = [];
                    this.addressValue = '';
                }
                this.form.province_id = item1.value
                this.form.city_id = item2.value
                this.form.intended_cities_name = item1.text +' '+ item2.text

            }
            this.popupShow = false;
        },
        //地址搜索事件
        addressSearch(){
            if(!this.addressValue){
                this.addressList = [];
                return
            }
            $.get(`${location.origin}/index.php?g=Index&c=Map&a=suggestion&city_id=${this.form.city_id}&query=${this.addressValue}&city_limit=1`, {}, res=>{
                if(res.status == 1){
                    this.addressList = res.result;
                }else{
                    this.addressList = [];
                    vant.Toast({
                        message: res.result,
                        duration: 1000, // 持续展示 toast
                    });
                }
			}, 'json');
        },
        selectAddress(item){
            this.form.adress = item.name
            this.form.lat = item.lat
            this.form.lng = item.lng
            this.popupShow = false;
        },
    }
})