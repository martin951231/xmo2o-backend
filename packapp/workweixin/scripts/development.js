(function(doc, win) {
	var docEl = doc.documentElement,
		resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
		recalc = function() {
			var clientWidth = docEl.clientWidth;
			if (!clientWidth) return;
			docEl.style.fontSize = 100 * (clientWidth / 750) + 'px';
		};

	if (!doc.addEventListener) return;
	win.addEventListener(resizeEvt, recalc, false);
	doc.addEventListener('DOMContentLoaded', recalc, false);

})(document, window);

var wxSdkLoad = false
var app = new Vue({
	el: '#app',
	data: {
		isShowPage: true,
		tabitem: [],
		MenuList: [],
		listdata: [],
		currentTopNav: 0,
		navScroll: '',
		num: -1,
		current: 0,
		show: false,
		from_id: '',
		from_type: '',
		ticket: '',
		pageIndex: 1,
		searchwords: '',
		no_data: false,
		pageLoading: false,
		pageLoadings: false,
		gid: '',
		type: 1,
		title: '',
		content: '',
		isAchiveBottom: false, // 滚动条是否到底部标志
		noMore: false,
		sendMsgs: {},
		userId: '',
		entry: '',
		from: '',
		menu_label: '所有',
	},
	created() {
		let _this = this
	},
	mounted() {
		let _this = this
		document.title = '内容引擎'

		_this.from_id = urlParam.from_id
		_this.from_type = urlParam.from_type
		_this.from = urlParam.from
		console.log("_this.from_type", _this.from_type)
		var login_status = common.cookie.get('workweixin_login_status')
		console.log("111111d", urlParam, login_status)

		if (!login_status) {
			_this.reloadData()
		} else if (urlParam.ticket) {
			_this.getinfo()
		} else {
			_this.getMsg()
		}
	},
	methods: {
		previewImage(e, url) {
			e.stopPropagation();
			let imgs = []
			imgs.push(url)
			console.log('imgs', imgs, wx, url)
			wx.previewImage({
				current: url,
				urls: imgs
			});
		},
		reloadData() {
			let _this = this
			common.removeCache('ticket', true)
			common.removeCache('from_id', true)
			common.removeCache('from_type', true)
			localStorage.removeItem('packapp_ticket')
			localStorage.removeItem('packapp_from_id')
			localStorage.removeItem('packapp_from_type')
			console.log("22222222")
			common.cookie.set('workweixin_login_status', 'login')
			_this.showpage()
		},
		getinfo() {
			common.logReport({
				'urlParam': 'urlParam',
				'data': urlParam
			});
			let _this = this
			console.log("4444444getinfo")
			common.setCache('from_id', urlParam.from_id, true)
			common.setCache('from_type', urlParam.from_type, true)
			common.setCache('ticket', urlParam.ticket, true)
			common.cookie.set('workweixin_login_status', 'login')
			common.cookie.set('user_ticket', urlParam.ticket)
			common.cookie.set('user_from_id', urlParam.from_id)
			common.cookie.set('user_from_type', urlParam.from_type)
			localStorage.setItem('packapp_ticket', JSON.stringify(urlParam.ticket))
			localStorage.setItem('packapp_from_id', JSON.stringify(urlParam.from_id))
			localStorage.setItem('packapp_from_type', JSON.stringify(urlParam.from_type))
			_this.getHeadType()
			_this.getSelectMenuList()
			_this.getContentList()
			window.addEventListener('scroll', _this.scrollEvent, true);
			_this.$nextTick(function() {
				var param = {};
				if (_this.from && _this.from == 'qylogin') {
					param['from'] = _this.from;
				}
				common.logReport({
					'regWxJs': '1regWxJs',
					'param': param
				});
				_this.regWxJs(param, function(data) {
					common.logReport({
						'getInfoRegWxJs': 'getInfoRegWxJs',
						'data': data
					});
					console.log("fdsfdsf", data)
				});
			})
		},
		getMsg() {
			let _this = this
			console.log("444444444getMsg")
			common.setCache('from_id', urlParam.from_id, true)
			common.setCache('from_type', urlParam.from_type, true)
			common.cookie.set('workweixin_login_status', 'login')
			common.cookie.set('user_from_id', urlParam.from_id)
			common.cookie.set('user_from_type', urlParam.from_type)
			localStorage.setItem('packapp_from_id', JSON.stringify(urlParam.from_id))
			localStorage.setItem('packapp_from_type', JSON.stringify(urlParam.from_type))
			_this.getHeadType()
			_this.getSelectMenuList()
			_this.getContentList()
			window.addEventListener('scroll', _this.scrollEvent, true);
			_this.$nextTick(function() {
				var param = {};
				if (_this.from && _this.from == 'qylogin') {
					param['from'] = _this.from;
				}
				common.logReport({
					'regWxJs': '2regWxJs',
					'param': param
				});
				_this.regWxJs(param, function(data) {
					common.logReport({
						'getMsgRegWxJs': 'getMsgRegWxJs',
						'data': data
					});
					console.log("fdsfdsf", data)
				});
			})
		},
		regWxJs: function(param, funs) {
			let _this = this
			if (common.checkWeixin()) {
				console.log("from_id", common.getCache('from_id', true), common.getCache('from_type', true))
				var regWxJsParam = {};
				regWxJsParam['noTip'] = true;
				regWxJsParam['from_id'] = common.getCache('from_id', true);
				regWxJsParam['ticket'] = common.getCache('ticket', true);
				regWxJsParam['from_type'] = common.getCache('from_type', true);
				regWxJsParam['url'] = location.href.split('#')[0];
				regWxJsParam['wx_api_type'] = 1;

				common.logReport({
					'regWxJs': 'regWxJs1',
					'regWxJsParam': regWxJsParam
				});
				common.httpV20('/community/manage_api.v1.ContentEngine/getQyWxConfig', regWxJsParam,
					function(data) {
						if (data.appId == undefined || data.appId == '') {
							return false;
						}
						common.logReport({
							'getQyWxConfig': 'getQyWxConfig',
							'data': data
						});
						console.log("data.appId", data)
						console.log("location.href", location.href)
						wx.config({
							beta: true,
							debug: false,
							appId: data.appId,
							timestamp: data.timestamp,
							nonceStr: data.noncestr,
							signature: data.signature,
							jsApiList: [
								'invoke',
								'checkJsApi',
								'previewImage',
							]
						});

						wx.ready(function(res) {
							console.log('api22接口', res)
							common.logReport({
								'wx': 'wx.ready',
								'res': res
							});
							_this.regWxworkJs(funs, data.timestamp, data.noncestr, param);
						});

						// wx.error(function(res) {
						// 	alert(res.errMsg);
						// });
					}
				);
			}
		},
		regWxworkJs: function(funs, timestamp, noncestr, param = {}) {
			let _this = this
			console.log("nonceStr", noncestr)
			if (common.checkWeixin()) {
				var regWxworkJsParam = {};
				regWxworkJsParam['noTip'] = true;
				regWxworkJsParam['from_id'] = common.getCache('from_id', true);
				regWxworkJsParam['from_type'] = common.getCache('from_type', true);
				regWxworkJsParam['wx_api_type'] = 2;
				regWxworkJsParam['timestamp'] = timestamp;
				regWxworkJsParam['nonceStr'] = noncestr;
				regWxworkJsParam['url'] = location.href.split('#')[0];
				common.logReport({
					'regWxworkJs': 'regWxworkJs1',
					'regWxworkJsParam': regWxworkJsParam,
					'param': param
				});
				common.httpV20('/community/manage_api.v1.ContentEngine/getQyWxConfig', regWxworkJsParam,
					function(data) {
						common.logReport({
							'getQyWxConfig1': 'getQyWxConfig1',
							'data': data,
						});
						if (data.appId == undefined || data.appId == '') {
							return false;
						}
						wx.agentConfig({
							debug: true,
							corpid: data.appId, // 必填，企业微信的corpid，必须与当前登录的企业一致
							agentid: data.agentid, // 必填，企业微信的应用id （e.g. 1000247）
							timestamp: data.timestamp,
							nonceStr: data.noncestr,
							signature: data.signature,
							jsApiList: [
								'invoke',
								'sendChatMessage',
								'getContext',
								'getCurExternalContact',
							],
							success: function(res) {
								common.logReport({
									'wx': 'wx.agentConfig',
									'res': res,
								});
								wx.checkJsApi({
									jsApiList: ['invoke', 'getContext', 'sendChatMessage', 'getCurExternalContact'], // 需要检测的JS接口列表，所有JS接口列表见附录2,
									success: function(res) {
										// 以键值对的形式返回，可用的api值true，不可用为false
										// 如：{"checkResult":{"chooseImage":true},"errMsg":"checkJsApi:ok"}
										console.log('api接口', res)
										common.logReport({
											'wx': 'wx.checkJsApi',
											'res': res,
										});

										console.log("_this.entry未调用", )
										wx.invoke('getContext', {}, function(res) {
											common.logReport({
												'wx': 'wx.invoke.getContext',
												'res': res,
											});
											console.log("_this.entry就解决", res)
											if (res.err_msg == "getContext:ok") {
												var entry = res.entry; //返回进入H5页面的入口类型，目前有normal、contact_profile、single_chat_tools、group_chat_tools
												if (funs) {
													funs(entry);
												}
												console.log("_this.entry", res)
											} else {
												//错误处理
											}
										});
										wx.invoke('sendChatMessage', {}, function(res) {
											common.logReport({
												'wx': 'wx.invoke.sendChatMessage',
												'res': res,
											});
										});
										wx.invoke('getCurExternalContact', {}, function(res) {
											common.logReport({
												'wx': 'wx.invoke.getCurExternalContact',
												'res': res,
											});
											if (res.err_msg == "getCurExternalContact:ok") {
												_this.userId = res.userId; //返回当前外部联系人userId
												console.log("_this.userId", res)
											} else {
												//错误处理
											}
										});
										if (param.from && param.from == 'qylogin') {
											setTimeout(function() {
												common.logReport({
													'wx': 'reloadPage',
													'regWxworkJsParam': regWxworkJsParam,
												});
												_this.reloadPage(regWxworkJsParam['from_id'], regWxworkJsParam['from_type'])
											}, 800)
										}
									}
								});
							},
							fail: function(res) {
								common.logReport({
									'err': 'err',
									'msg': '注册失败2',
									'res': res,
								});
								console.log("注册失败2", res)
							}
						});
					});
			}
		},
		reloadPage(from_id, from_type) {
			var reloadUrl = (window.location.protocol == 'http:' ? 'http:' : 'https:') + '//' + requestDomain + "/packapp/workweixin/DevelopmentStation.html?from_id=" + from_id + "&from_type=" + from_type;
			common.logReport({
				'reloadPage': 'reloadPage',
				'reloadUrl': reloadUrl,
			});
			window.location.href = reloadUrl;
		},

		searchClick() {
			let _this = this
			_this.pageIndex = 1
			common.logReport({
				'searchClick': 'searchClick',
			});
			_this.getContentList()
		},
		onSubmit() {
			let _this = this
			_this.pageIndex = 1
			common.logReport({
				'onSubmit': 'onSubmit',
			});
			_this.getContentList()
		},
		scrollEvent() {
			// 变量 scrollTop 是滚动条滚动时, 距离顶部的距离
			var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
			// 变量 windowHeight 是可视区的高度
			var windowHeight = document.documentElement.clientHeight || document.body.clientHeight;
			// 变量 scrollHeight 是滚动条的总高度
			var scrollHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
			// 滚动条到底部的条件 (距底部 80px 时触发加载)
			console.log("scrollTop", scrollTop, windowHeight, scrollHeight)
			if (
				scrollTop + windowHeight >= scrollHeight - 80 &&
				!this.isAchiveBottom && !this.noMore
			) {
				this.isAchiveBottom = true;
				setTimeout(() => {
					this.getContentList();
				}, 500);
			}
		},
		getHeadType() {
			let _this = this
			let param = {}
			common.httpV20('/community/manage_api.v1.ContentEngine/getHeadType', param, function(data) {
				console.log("data++++", data)
				_this.tabitem = data.data

				console.log("this.tabitem", _this.tabitem)
				
				common.logReport({
					'getHeadType': 'getHeadType',
					'tabitem': _this.tabitem
				});
			}, function(err) {
				weui.alert(err.errorMsg)
			})
		},
		async getSelectMenuList() {
			let _this = this
			let param = {
				from_id: common.getCache('from_id', true),
				from_type: common.getCache('from_type', true),
			}
			await common.httpV20('/community/manage_api.v1.ContentEngine/getSelectMenuList', param,
				function(data) {
					console.log("data_getSelectMenuList", data)
					_this.MenuList = data.map(item => {
						return {
							label: item.title,
							value: item.id
						}
					})
					console.log("this.tabitem")
					common.logReport({
						'getSelectMenuList': 'getSelectMenuList',
						'MenuList': _this.MenuList
					});
				},
				function(err) {
					weui.alert(err.errorMsg)
				})
		},
		async getContentList() {
			let _this = this
			let param = {
				page: _this.pageIndex,
				title: _this.searchwords,
				gid: _this.gid,
				type: _this.type,
				from_id: common.getCache('from_id', true),
				from_type: common.getCache('from_type', true),
			}
			await common.httpV20('/community/manage_api.v1.ContentEngine/getContentList', param, function(data) {
				console.log("data_getContentList", data)
				common.logReport({
					'getContentList': 'getContentList',
					'data': data
				});
				if (_this.searchwords == '') {
					if (data[0]) {
						_this.no_data = false;
						if (_this.pageIndex == 1) {
							_this.listdata = data;
						} else {
							_this.listdata = _this.listdata.concat(data);
						}
						_this.pageIndex++
					} else {
						if (_this.pageIndex == 1) {
							_this.$nextTick(function() {
								_this.pageLoadings = false;
								_this.no_data = true;
							})
						} else {
							_this.pageLoadings = true;
						}
					}
				} else {
					if (data[0]) {
						_this.no_data = false;
						if (_this.pageIndex == 1) {
							_this.listdata = data;
						} else {
							_this.listdata = _this.listdata.concat(data);
						}
						_this.pageIndex++
					} else {
						if (_this.pageIndex == 1) {
							_this.$nextTick(function() {
								_this.pageLoadings = false;
								_this.no_data = true;
							})
						} else {
							_this.pageLoadings = true;
						}
					}
				}

				this.isAchiveBottom = false;

			}, function(err) {
				weui.alert(err.errorMsg)
			})
		},
		sendtext() {
			let _this = this
			common.logReport({
				'sendtext': 'sendtext',
			});
			wx.invoke('sendChatMessage', {
				msgtype: 'text', //消息类型，必填
				text: {
					content: _this.sendMsgs.content, //文本内容
				}
			}, function(res) {
				common.logReport({
					'wx': 'wx.invoke.sendChatMessage',
					'res': res,
				});
				if (res.err_msg == 'sendChatMessage:ok') {
					//发送成功
				}
			});
			_this.isclose()
		},
		showpage() {
			window.location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:') + '//' + requestDomain + '/v20/public/index.php/community/manage_api.v1.ContentEngine/qyWxLogin?from_id=' + this.from_id + '&from_type=' + this.from_type
		},
		isShow() {
			this.show = !this.show
		},
		isclose() {
			this.show = false
		},
		selectitem(index, item) {
			let _this = this
			_this.sendMsgs = item
			_this.num = index
			common.logReport({
				'selectitem': 'selectitem',
				'sendMsgs': _this.sendMsgs,
			});
			console.log("_this.sendMsgs", _this.sendMsgs)
		},
		seedetails(e, item) {
			let _this = this
			e.stopPropagation();
			_this.title = item.title
			_this.content = item.content
			_this.isShow()
		},
		sendMsg() {
			let _this = this
			if (_this.current == 1 || _this.current == 2) {
				_this.upload()
			} else {
				let H5_link = _this.sendMsgs.content;
				if (_this.sendMsgs.content) {
					if (_this.userId) {
						H5_link += (H5_link.indexOf('?') != -1 ? '&' :'?') + 'userId=' + _this.userId
					}
				} else {
					H5_link = (window.location.protocol == 'http:' ? 'http:' : 'https:') + '//' + requestDomain + _this.sendMsgs.link
				}
				console.log("_this.sendMsgs", _this.sendMsgs)
				common.logReport({
					'sendMsg': 'sendMsg',
					'sendMsgs': _this.sendMsgs,
					'current': _this.current,
					'H5_link': H5_link,
				});
				if (_this.current == 0) {
					wx.invoke('sendChatMessage', {
						msgtype: 'text', //消息类型，必填
						text: {
							content: _this.sendMsgs.content, //文本内容
						}
					}, function(res) {
						common.logReport({
							'sewxndMsg': '3wx.invoke.sendChatMessage',
							'res': res,
							'sendMsgs': _this.sendMsgs,
							'current': _this.current,
						});
						if (res.err_msg == 'sendChatMessage:ok') {
							//发送成功
						}
					});
				} else {
					if (_this.sendMsgs.appid == '') {
						wx.invoke('sendChatMessage', {
							msgtype: 'news', //消息类型，必填
							news: {
								link: H5_link, //H5消息页面url 必填
								title: _this.sendMsgs.title, //H5消息标题
								desc: _this.sendMsgs.content ? _this.sendMsgs.content : '', //H5消息摘要
								imgUrl: _this.sendMsgs.share_img ? _this.sendMsgs.share_img :
								'', //H5消息封面图片URL
							}
						}, function(res) {
							common.logReport({
								'sewxndMsg': '4wx.invoke.sendChatMessage',
								'res': res,
								'sendMsgs': _this.sendMsgs,
								'current': _this.current,
							});
							if (res.err_msg == 'sendChatMessage:ok') {
								//发送成功

							}
						});
					} else {
						wx.invoke('sendChatMessage', {
							msgtype: 'miniprogram', //消息类型，必填
							miniprogram: {
								appid: _this.sendMsgs.appid, //小程序的appid
								title: _this.sendMsgs.title, //小程序消息的title
								imgUrl: _this.sendMsgs.share_img ? _this.sendMsgs.share_img :
								'', //小程序消息的封面图
								page: H5_link, //小程序消息打开后的路径，注意要以.html作为后缀，否则在微信端打开会提示找不到页面
							}
						}, function(res) {
							common.logReport({
								'sewxndMsg': '5wx.invoke.sendChatMessage',
								'res': res,
								'sendMsgs': _this.sendMsgs,
								'current': _this.current,
							});
							if (res.err_msg == 'sendChatMessage:ok') {
								//发送成功

							}
						});
					}
				}
			}
		},
		upload() {
			let _this = this
			let param = {
				id: _this.sendMsgs.id,
			}
			common.logReport({
				'upload': 'upload',
				'param': param,
			});
			common.httpV20('/community/manage_api.v1.ContentEngine/sendSetOut', param, function(data) {
				common.logReport({
					'sendSetOut': 'sendSetOut',
					'data': data,
					'current': _this.current,
				});
				console.log("data_sendSetOut", data)
				let H5_link = (window.location.protocol == 'http:' ? 'http:' : 'https:') + '//' + requestDomain + _this.sendMsgs.link
				console.log("_this.sendMsgs", _this.sendMsgs)
				if (_this.current == 1) {
					wx.invoke('sendChatMessage', {
						msgtype: 'image', //消息类型，必填
						image: {
							mediaid: data.media_id, //图片的素材id
						},
						success: function(res) {
							common.logReport({
								'wx': '1wx.invoke.sendChatMessage',
								'res': res,
							});
							if (res.err_msg == 'sendChatMessage:ok') {
								//发送成功
							}
						}
					});
				} else {
					wx.invoke('sendChatMessage', {
						msgtype: 'file', //消息类型，必填
						file: {
							mediaid: data.media_id, //图片的素材id
						},
						success: function(res) {
							common.logReport({
								'wx': '2wx.invoke.sendChatMessage',
								'res': res,
							});
							if (res.err_msg == 'sendChatMessage:ok') {
								//发送成功
							}
						}
					});
				}
			}, function(err) {
				weui.alert(err.errorMsg)
			})
		},
		showPicker() {
			let _this = this
			weui.picker(_this.MenuList, {
				onChange: function(result) {
					console.log(result);
				},
				onConfirm: function(result) {
					console.log(result);
					_this.menu_label = result[0].label
					_this.gid = result[0].value
					console.log('_this.gid', _this.gid);
					_this.pageIndex = 1
					_this.getContentList()
				},
				title: '单列选择器'
			});
		},
		goCenter(guid, index, e) {
			let _this = this
			if (_this.current !== index) {
				_this.current = index
				_this.num = -1
				let destination = index - 2;
				destination = destination < 0 ? 1 : destination;
				_this.currentTopNav = index;
				_this.type = guid
				_this.$nextTick(function() {
					_this.pageIndex = 1
					_this.searchwords = ''
					_this.getContentList()
				})
				if (_this.navScroll) {
					_this.navScroll.scrollToElement(
						document.querySelector('#topNav li:nth-child(' + destination + ')')
					);
				}
				// this.getdata();

				// 横向滑动居中
				let ul = document.querySelector('#topNavUl');
				let nav = document.getElementById("topNav");
				let window_offsetWidth = window.innerWidth; //屏幕宽度;
				let dom = e.target;
				if (dom) {
					let domoffsetWidth = dom.offsetLeft,
						//中间值 =( 屏幕宽度 - li宽度 ) / 2;
						diffWidth = (window_offsetWidth - dom.offsetWidth) / 2,
						//目标值 = offset - 中间值
						targetOffset = -(domoffsetWidth - diffWidth);
					let ul_width = ul.getBoundingClientRect().width; //开始

					// 未超出中间值
					if (-targetOffset < 0) {
						nav.scrollLeft = 0;
						return;
					}
					//末尾
					if (targetOffset < window_offsetWidth - ul_width) {
						nav.scrollLeft = -(window_offsetWidth - ul_width);
						return;
					}
					//正常
					nav.scrollLeft = -targetOffset
				}
			}
		},
	},
	computed: {

	},
	watch: {

	}
})
