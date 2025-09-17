(function (doc, win) {
    var docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
        recalc = function () {
            var clientWidth = docEl.clientWidth;
            if (!clientWidth) return;
            docEl.style.fontSize = 100 * (clientWidth / 750) + 'px';
        };

    if (!doc.addEventListener) return;
    win.addEventListener(resizeEvt, recalc, false);
    doc.addEventListener('DOMContentLoaded', recalc, false);
})(document, window);

function ReturnLastPage(){
    history.back();
}

function getTicket () {
    var reg = /ticket=(.*?),/;
    var arr = reg.exec(navigator.userAgent);
    if(arr == null){
        return ''
    }else{
        return arr[1];
    }
}

function getV20Ticket () {
    var reg = /v20_ticket=(.*?),/;
    var arr = reg.exec(navigator.userAgent);
    if(arr == null){
        return ''
    }else{
        return arr[1];
    }
}

function getDeviceid () {
    var reg = /device-id=(.*?),/;
    var arr = reg.exec(navigator.userAgent);
    if(arr == null){
        return ''
    }else{
        return arr[1];
    }
}

// 安卓网页登录成功的回调方法
function androidRefresh(ticket, device){
    if(ticket && device){
        urlParam.ticket = ticket
        urlParam.device_id = device
        app.psdLogin('ticket')
    }
}

// IOS网页登录成功的回调方法
function refresh(ticket, device){
    if(ticket && device){
        urlParam.ticket = ticket
        urlParam.device_id = device
        app.psdLogin('ticket')
    }
}

var myCaptcha,CaptchaToken,CaptchaRandstr;
var app = new Vue({
    el: '#app',
    data: {
        code: '', // 验证码
        codeLength: 4, // 验证码长度
        focused: true, // 验证码输入框是否聚焦
        time: 60, // 验证码倒计时
        showMask: false, // 是否显示弹窗
        showAreacode: false, // 显示区号弹窗
        areaCodeList: [], // 区号列表
        areaCode: '', // 当前选择的区号
        showVcodeMask: false, // 显示验证码输入弹窗
        showVcode: true, // 显示验证码登录
        showPassword: false, // 显示密码登录
        showForgetMask: false, // 显示忘记密码弹窗
        phoneFocused: false, // 手机输入框聚焦
        phone: '', // 手机号
        psdFocused: false, // 密码输入框聚焦
        password: '', // 密码
        psdType: 'password', // 密码输入框显示类型 password text
        config: '', // 页面基本配置信息
        mainColor: '',
        vcodeErr: false,
        isShowPage: false,
        isThirdPhone: false,
        footerStyle: '',
        wxLoginId: '',
        timer: '',
        openMultilingual: false,
        deviceId: '',
        referer: '',
        openSchool: 0, // 是否支持校园登录
        accountPlaceholder: L_('请输入手机号'),
        showThirdLogin: true, // 是否展示第三方登录
        showAgreement: false,
        isAgree: false,
        privacyParams: {},
        checkedPrivacy: false,
    },
    beforeDestroy() {
        window.sessionStorage.setItem('currentPhone', null)
    },
    mounted () {
        var _this = this
		if(window.sessionStorage.getItem('currentPhone')){
            this.phone = window.sessionStorage.getItem('currentPhone')
        }
		var login_status = common.cookie.get('login_status')
		if (login_status && login_status == 'logout')  {
			console.log('清除ticket', login_status)			
            common.removeCache('ticket', true)
            common.removeCache('v20_ticket', true)
			common.removeCache('user', true)
			localStorage.removeItem('packapp_ticket')
			localStorage.removeItem('packapp_user')
			localStorage.removeItem('packapp_device')
		}

        common.getConfig({noTip:true, special_lang:visitWork}, this.getConfig)
        this.deviceId = urlParam.device_id ? urlParam.device_id : common.getDeviceId()
        this.referer = encodeURIComponent(location.href)
        let from_type = ''
        if(this.referer.indexOf('parkingFeetwo') != -1){
            from_type = 'park_new'
        }
        
        if (common.checkDouyin()) {
            this.showThirdLogin = false;
        }

        if (!urlParam.device_id) {
            if (common.checkWeixin() && !common.checkWeixinApp()) {
                // 微信浏览器中登录
                redirect_self((window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?c=Packapp&a=weixin&referer=' + _this.referer + '&from_type='+ from_type +'&device_id=' + _this.deviceId + (urlParam.thirdLogin && (urlParam.thirdLogin == true || urlParam.thirdLogin == 'true')? '&thirdLogin=true' : ''))
            } else if (common.checkAlipay()) {
				if (typeof my != 'undefined') {
					my.getEnv(function(res) {
						if (res.miniprogram) {
							// 支付宝小程序登录
							weui.alert(L_('正在打开登录页面'))
							_this.isAlipayApp = true
							_this.getLoginId()
						} else {
							// 支付宝浏览器中登录
							redirect_self( (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?c=Packapp&a=alipay&referer=' + _this.referer +'&device_id=' + _this.deviceId + (urlParam.thirdLogin && (urlParam.thirdLogin == true || urlParam.thirdLogin == 'true')? '&thirdLogin=true' : ''))
						}
					});
				} else {
					// 支付宝浏览器中登录
					redirect_self( (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?c=Packapp&a=alipay&referer=' + _this.referer +'&device_id=' + _this.deviceId  + (urlParam.thirdLogin  && (urlParam.thirdLogin == true || urlParam.thirdLogin == 'true')? '&thirdLogin=true' : ''))
				}
            } else if (common.checkIosApp()) {
                // IOS APP登录
                // v20_ticket
                if (getV20Ticket()) {
                    urlParam.v20_ticket = getV20Ticket()
                }

                if (!getTicket()) {
					common.iosFunction('login', {name:'login'});
                } else {
                    urlParam.ticket = getTicket()
                    urlParam.device_id = getDeviceid()
                    this.psdLogin('ticket')
                }

            } else if (common.checkAndroidApp()) {
                // 安卓APP登录
                // v20_ticket
                if (getV20Ticket()) {
                    urlParam.v20_ticket = getV20Ticket()
                }

                if (!getTicket()) {
                    window.lifepasslogin.startJsToJavaFunction()
                } else {
                    urlParam.ticket = getTicket()
                    urlParam.device_id = getDeviceid()
                    this.psdLogin('ticket')
                }
                
            } else if (common.checkWeixinApp()) {
                // 微信小程序登录
                weui.alert(L_('正在打开登录页面'))
                this.getLoginId()
            } else if (/(xkyApp)/.test(navigator.userAgent)) {
                // 星空云对接登录
				location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?c=Login&a=index&third_login_type=xykApp&referer=' + _this.referer;
            } else {
                // 普通浏览器登录
                this.isShowPage = true
                this.hashHandle()
                console.log('urlParam', urlParam)

                document.onkeydown = function(e) {
                    var key = window.event.keyCode;
                    if (key == 13) {
                        _this.psdLogin();
                    }
                }
            }
        } else {
            // 第三方登录绑定手机号
            if (urlParam.third_id) {
				//临时登录
				if(urlParam.thirdLogin && (urlParam.thirdLogin == true || urlParam.thirdLogin == 'true')){
					var param = {
						third_from: urlParam.third_from,
						third_id: urlParam.third_id,
						'Device-Id': urlParam.device_id
					};
					//获取用户临时信息
					common.http('Login&a=third_id_info', param, function (data) {
						localStorage.setItem('packapp_tmp_user',JSON.stringify(data.user));
						common.cookie.set('user_tmp_ticket', data.ticket);
						common.cookie.set('v20_tmp_ticket', data.v20_ticket);
						common.cookie.set('user_tmp_device_id', urlParam.device_id ? urlParam.device_id : 'packapp');
						
						//跳转返回
						if (urlParam.back) {
							if (urlParam.back == 'back') {
								openWebviewUrl('back')
							} else {
								redirect_self(urlParam.back)
							}
						} else {
							redirect_self((window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?g=Wap&c=My&a=index&device_id=' + _this.deviceId + '&ticket=' + data.ticket + '&v20_ticket=' + data.v20_ticket)
						}
				
					});
				}else{
					this.isThirdPhone = this.isShowPage = true
				}
            }
	    // 如果开启了校园登录并且从微信登录重定向过来 不显示第三方登录
            if (urlParam.third_from == 'weixin' && this.openSchool == '1') {
                this.showThirdLogin = false
                this.isThirdPhone = false // 不显示绑定手机号了
                this.showVcode = this.isShowPage = true
            }

            // 第三方登录成功 获得ticket换取用户信息
            if (urlParam.device_id && urlParam.ticket) {
                this.psdLogin('ticket')
            }

            // 第三方登录失败
            if (urlParam.errmsg) {
                weui.alert(decodeURIComponent(urlParam.errmsg))
            }
        }
        window.addEventListener('hashchange', this.hashHandle)

        var winHeight = document.documentElement.clientHeight  //获取当前页面高度
        var _this = this
        window.addEventListener('resize', function () {
            var thisHeight = document.documentElement.clientHeight
            if ( thisHeight < winHeight ) {
                //键盘弹出
                _this.footerStyle = 'position:static;margin-top: 50px'
            } else {
                //键盘收起
                _this.footerStyle = ''
            }
        })
    },
    methods: {
        getLoginId () {
            var _this = this
            common.http('Event_way&a=add', {noTip: true}, function (data) {
                console.log('登录获取ID', data)
                _this.checkLoginStatus(data)
                wx.miniProgram.navigateTo({url: '/pages/login/login?loginId=' + data})
            })
        },
        loginIdReport (id) {
            var _this = this
            common.http('Event_way&a=up', {id: id, msg:{desc: '小程序内嵌h5页面授权登录'}}, function (data) {
                wx.miniProgram.navigateTo({url: '/pages/login/login?loginId=' + id})
            })
        },
        checkLoginStatus (id) {
            var _this = this
            _this.timer = setInterval(function () {
                common.removeCache('ticket', true)
                common.http('Event_way&a=down', {id: id, noTip: true}, function (data) {
                    if (data) {
                        data = JSON.parse(data)
                        clearInterval(_this.timer)
                        if (data.ticket) {
                            common.setCache('ticket', data.ticket, true)
                            common.setCache('user', data.user, true)
                            common.cookie.set('login_status', 'login')
                            common.cookie.set('user_ticket', data.ticket)
                            common.cookie.set('user_device_id', data.device_id)
							localStorage.setItem('packapp_ticket',JSON.stringify(data.ticket))
							localStorage.setItem('packapp_user',JSON.stringify(data.user))
                            localStorage.setItem('packapp_device',JSON.stringify(data.device_id))
                            
                            if (data.v20_ticket) {
                                common.cookie.set('v20_ticket', encodeURIComponent(data.v20_ticket))
                            }

                            if (urlParam.back) {
                                if (urlParam.back == 'back') {
                                	openWebviewUrl('back')
                                } else {
                                	redirect_self(urlParam.back)
                                }
                            } else {
                                redirect_self((window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?g=Wap&c=My&a=index&device_id=' + data.device_id + '&ticket=' + data.ticket)
                            }
                        } else {
                            document.title = ''
                            redirect_self(urlParam.loginFail)
                        }
                    }

                }, function (err) {
                    clearInterval(_this.timer)
                    document.title = ''
                    redirect_self(urlParam.loginFail)
                })
            }, 200)

        },

        thirdLogin (type) {
            if (type == 'weixin') {
                weui.alert(L_('只能在微信环境中使用'))
            } else if (type == 'alipay') {
                weui.alert(L_('只能在支付宝环境中使用'))
            }

        },
        getConfig () {
			var _this = this;
            this.config = common.getCache('config', true)
            this.mainColor = this.config.config.mobile_head_color
            this.openSchool = this.config.config.open_school || 0
            // 开启校园登录 且不是微信登录 弹出校园登录输入页面 如果是微信登录回来还是进入手机号验证码输入页面
            if (this.openSchool == '1' && urlParam.third_from != 'weixin') {
                this.showThirdLogin = true
                this.showPassword = true
                this.showVcode = false
                this.accountPlaceholder = L_('手机号/学工号')
            }
            document.title = this.config.config.site_name ? this.config.config.site_name : '登录'
            if (this.config.config.international_phone) {
                this.getAreaCode()
				this.openMultilingual = true
            }
            if (this.config.config.special_lang_list) {
                replace_html_lang();
				setTimeout(function(){
					replace_html_lang();
				}, 300);
            }
			
			if (!common.checkWeixinApp()){
				//初始化验证码模块
				if(_this.config.qcloud_captcha_appid && _this.config.qcloud_captcha_appid != ''){
					$.getScript('https://turing.captcha.qcloud.com/TCaptcha.js', function(){
						myCaptcha = new TencentCaptcha(_this.config.qcloud_captcha_appid, function(res){
							console.log('qcloud_captcha_res', res);
							CaptchaToken = res.ticket;
							CaptchaRandstr = res.randstr;
							console.log('Captcha token:', res.ticket)
							myCaptcha.destroy();
							_this.sendVcode();
						}, {});
					});
				}else if(_this.config.dingxiang_captcha_appid && _this.config.dingxiang_captcha_appid != ''){
					$.getScript('https://cdn.dingxiang-inc.com/ctu-group/captcha-ui/index.js', function(){
						myCaptcha = _dx.Captcha(document.getElementById('captchaSendCode'), {
							appId: _this.config.dingxiang_captcha_appid,
							style: 'popup',
							success: function (token) {
								CaptchaToken = token;
								console.log('Captcha token:', token)
								myCaptcha.hide();
								_this.sendVcode();
							}
						});
					});
				}
			}
        },
        hashHandle () {
            var hash = window.location.hash.slice(1)
            console.log('hash改变了', hash)
			var hashObj = {showVcode: true, showVcodeMask: true, showAreacode: true, showForgetMask: true}
			// hash的值 
			if (hash && !hashObj[hash]) {
				return
			}
            if (hash) {
                this.showMask = true
                this[hash] = true
                if (hash == 'showVcode') {
                    this.$nextTick(function () {
                        document.getElementById("vcode").focus()
                    })
                }else if (hash == 'showVcodeMask') {
					console.log('this.phone', this.phone);
					if(this.phone == ''){
						history.go(-1);
						return ;
					}
                }
            } else {
                this.showMask = false
                this.showForgetMask = this.showAreacode = this.showVcodeMask = false
                document.title = L_('登录')
            }
        },
        // 倒计时
        countDown () {
            var _this = this
            setTimeout(function () {
                _this.time--
                if (_this.time > 0) {
                    _this.countDown()
                }
            }, 1000)
        },

        closeMask () {
            console.log('3333333')
            this.showMask = false
        },

        // 显示弹窗
        openMask (type) {

            location.hash = type
            if (type == 'showForgetMask') {
                document.title = L_('修改登录密码流程')
            }
        },

        // 选中区号
        selectAreaCode (value) {
            this.showAreacode = false
            this.areaCode = value
            history.go(-1)
        },

        // 验证码登录
        showVcodeBox (type) {
            if (type == 'mask') {
                history.go(-1)
            }
            this.showVcode = true
            this.showPassword = false
            this.password = ''
            this.accountPlaceholder = L_('请输入手机号')
        },

        // 密码登录
        showPasswordBox () {
            this.showVcode = false
            this.showPassword = true
            if (this.openSchool == '1') {
                this.accountPlaceholder = L_('手机号/学工号')
            }
        },
        // 显示密码
        showPsd (type) {
            this.psdType = type
        },
        clearInput (type) {
            this[type] = ''
        },
        inputBlur (type) {
            var _this = this
            setTimeout(function () {
                _this[type] = false
            }, 300)

        },
        // 点击登录
        psdLogin (type) {
            var _this = this
            if (_this.isShowPage && ((this.isThirdPhone && this.config.register_agreement_url) || (this.showThirdLogin && (this.config.register_agreement_url || this.config.privacy_policy_url))) && !this.checkedPrivacy) {
                weui.alert(L_("请先阅读并勾选页面底部协议"));
                return;
            }
            if (!this.client) {
                this.client = common.checkAndroid ? 2 : 1
            }
            var param = {
                client: this.client
            }
            if (type == 'ticket') {
                param.ticket = urlParam.ticket
                param['Device-Id'] = urlParam.device_id
            }else if (type == 'vcode') {
                param.code = this.code
                param.phone = this.phone
            } else {
                param.phone = this.phone
                param.passwd = this.password
                if (!param.passwd) {
                    return false
                }
            }
            if (this.openMultilingual) {
                param.phone_country_type = this.areaCode
            }
            
			common.removeCache('ticket', true)
            common.http('Login&a=login', param, function (data) {
                _this.loginSuccess(data,type)
            }, function (err) {
                if (type == 'vcode') {
                    weui.alert(err.errorMsg)
                    _this.code = ''
                    _this.vcodeErr = true
                } else if (common.checkApp()) {
                    _this.loginSuccess({ticket: urlParam.ticket, v20_ticket: urlParam.v20_ticket },type)
                } else {
                    weui.alert(err.errorMsg)
                }
            })
        },
        // 获取ticket和用户信息成功接口
        loginSuccess (data,type) {
            if (data) {
                common.setCache('ticket', data.ticket, true)
				localStorage.setItem('packapp_ticket',JSON.stringify(data.ticket))
                if (data.user) {
                    common.setCache('user', data.user, true)
					localStorage.setItem('packapp_user',JSON.stringify(data.user))
                }
                common.cookie.set('login_status', 'login')
                common.cookie.set('user_ticket', data.ticket)

                // v20_ticket
                if (data.v20_ticket) {
                    common.cookie.set('v20_ticket', encodeURIComponent(data.v20_ticket))
                }

                urlParam.device_id ? common.cookie.set('user_device_id', urlParam.device_id) : common.cookie.set('user_device_id', 'packapp')
                urlParam.device_id && localStorage.setItem('packapp_device',JSON.stringify(urlParam.device_id))
				if (urlParam.back) {
                    if (urlParam.back == 'back') {
                    	openWebviewUrl('back')
                    } else {
                    	redirect_self(urlParam.back)
                    }
                } else {
                    redirect_self((window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?g=Wap&c=My&a=index&device_id=' + _this.deviceId + '&ticket=' + data.ticket + '&v20_ticket=' + data.v20_ticket)
                }
            }

        },
        // 绑定手机号登录
        bindPhoneLogin () {
            var _this = this
            var param = {
                phone: this.phone,
                code: this.code,
                third_from: urlParam.third_from,
                third_id: urlParam.third_id
            }
            if (this.openMultilingual) {
                param.phone_country_type = this.areaCode
            }
            common.http('Login&a=third_id_login', param, function (data) {

                _this.loginSuccess(data)
            }, function (err) {
                weui.alert(err.errorMsg)
                _this.code = ''
                _this.vcodeErr = true
            })
        },
        // 点击获取验证码
        getVcode () {
            this.getPrivacy()
        },

        //隐私协议详情
        goAgree(){
            document.getElementById("agreementContent").style.display = ''
            // window.sessionStorage.setItem('currentPhone', this.phone)
            // window.open((window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/packapp/village/pages/my/privacyContent')
        },

        // 是否统一隐私协议政策的按钮操作
        operate(flag){
            document.getElementById("privacyAgreement").style.display = 'none'
            if(flag){
                this.isAgree = true
                this.checkedPrivacy = true
                this.getVcode()
            }
        },

        closeModal(){
            document.getElementById("agreementContent").style.display = 'none'
        },

        getPrivacy(){
            let that = this
            common.http('HouseApi&a=get_house_user_register_protocol', {noTip: true, phone: that.phone}, function(result) {
                that.privacyParams = result
                if(!that.isAgree && that.privacyParams.is_intercept_handle && that.privacyParams.is_intercept_handle*1==1){
                    document.getElementById("privacyAgreement").style.display = ''
                    return
                }
                this.vcodeErr = false
                if(that.config.qcloud_captcha_appid && that.config.qcloud_captcha_appid != ''){
                    if(!CaptchaToken || CaptchaToken == ''){
                        myCaptcha.show();
                    }else{
                        myCaptcha.destroy();
                        myCaptcha.show();
                    }
                }else if(that.config.dingxiang_captcha_appid && that.config.dingxiang_captcha_appid != ''){
                    if(!CaptchaToken || CaptchaToken == ''){
                        myCaptcha.show();
                    }else{
                        myCaptcha.reload();
                        myCaptcha.show();
                    }
                }else{
                    that.sendVcode();
                }
            });
        },

		sendVcode () {
			var _this = this
            if (_this.isShowPage && ((this.isThirdPhone && this.config.register_agreement_url) || (this.showThirdLogin && (this.config.register_agreement_url || this.config.privacy_policy_url))) && !this.checkedPrivacy) {
                weui.alert(L_("请先阅读并勾选页面底部协议"));
                return;
            }
            var param = {
                phone: this.phone,
                type: 4,
                CaptchaToken: CaptchaToken,
				CaptchaRandstr: CaptchaRandstr,
            }
            if (this.openMultilingual) {
                param.phone_country_type = this.areaCode
            }
            common.http('Login&a=sendCode', param, function (data) {
                weui.toast(L_('验证码已发送'))
                _this.time = 60
                location.hash = 'showVcodeMask'
                _this.countDown()
            })
		},
        // 请求获取国际区号列表
        getAreaCode () {
            var _this = this
            common.http('Config&a=getNationalPhone', {}, function (data) {
                if (data) {
                    _this.areaCodeList = data
                    _this.areaCode = data[0].code
                }
            })
        }
    },
    computed: {
        codeArr() {
            if (this.code) {
                this.vcodeErr = false
            }
            return this.code.split('')
        },
        cursorIndex() {
            return this.code.length
        }
    },
    watch: {
        code(newVal) {
            this.code = newVal.replace(/[^\d]/g,'')
            if (newVal.length >= 4) {
				window.scroll(0, 0)
                if (urlParam.third_from) {
                    this.bindPhoneLogin()
                } else {
                    this.psdLogin('vcode')
                }
            }
        }
    }
})