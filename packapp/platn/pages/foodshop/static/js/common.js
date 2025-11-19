import common from '../../../../utils/common.js'
/**
 * 获取胶囊按钮位置
 */
let getMenuPosition = (_this, systemInfo) => {
  let top = 4
  let right = 7
  let width = 87
  let height = 32
  
  if (systemInfo.platform === 'devtools' && systemInfo.system.indexOf('Android') === -1) {
    top = 6
    right = 10
  } else if (systemInfo.platform === 'devtools' && systemInfo.system.indexOf('Android') != -1) {
    top = 8
    right = 10
  } else if (systemInfo.system.indexOf('Android') != -1) {
    top = 8
    right = 10
    width = 95
  }
  
  // #ifdef H5
  top = 6
  width = 0
  // #endif
  
  _this.navigationBarTop = top
  _this.capsuleInfo = { top, right, width, height}
  _this.navigationH = height
  return {
    top: systemInfo.statusBarHeight + top,
    left: systemInfo.windowWidth - width - right,
    width: width,
    height: height
  }
}
/**
 * 获取状态栏样式
 */
let getStatusBarStyle = (_this, systemInfo) => {
  let statusBarPosition = {
    top: 0,
    left: 0,
    width: systemInfo.windowWidth,
    height: systemInfo.statusBarHeight
  }
  return formatStyle(statusBarPosition)
}
/**
 * 获取导航栏样式
 */
let getNavigationBarStyle = (_this, systemInfo) => {
  let menuPosition = getMenuPosition(_this, systemInfo)
  let height = 0
  // #ifdef MP-WEIXIN
  height = (menuPosition.top - systemInfo.statusBarHeight) * 2 + menuPosition.height
  // #endif
  
  // #ifdef H5
  height = (4 - systemInfo.statusBarHeight) * 2 + menuPosition.height
  // #endif
  
  let navigationBarPosition = {
    top: systemInfo.statusBarHeight,
    left: 0,
    width: systemInfo.windowWidth,
    height: height,
  }
  // IOS真机在navigationBar下面多加4个像素，保持和原生一致
  if (systemInfo.platform !== 'devtools' && systemInfo.system.indexOf('Android') === -1) {
    navigationBarPosition.height = navigationBarPosition.height + 4
  }
  
    _this.navigationBarH= navigationBarPosition.height
    _this.statusBarH= systemInfo.statusBarHeight
    _this.totalH= navigationBarPosition.height + systemInfo.statusBarHeight
  return navigationBarPosition
}
/**
 * 获取导航样式
 */
let getNavigationStyle = (_this, systemInfo) => {
  let menuPosition = getMenuPosition(_this, systemInfo)
  let padding = systemInfo.windowWidth - menuPosition.left - menuPosition.width
  let width = systemInfo.windowWidth - padding * 2
  // #ifdef MP-WEIXIN
  width = systemInfo.windowWidth - padding * 3 - menuPosition.width
  // #endif
  let navigationPosition = {
    top: menuPosition.top,
    left: padding,
    width: width,
    height: menuPosition.height
  }
  return formatStyle(navigationPosition)
}
/**
 * 获取胶囊按钮样式
 */
let getMenuStyle = (_this, systemInfo) => {
  return formatStyle(getMenuPosition(_this, systemInfo))
}
/**
 * 格式化Style
 */
const formatStyle = (position) => {
  let styles = []
  for (let key in position) {
    styles.push(`${key}: ${position[key]}px;`)
  }
  return styles.join(' ')
}

let requestFail = (_this,err,is_avoid_login = true) => {
	console.log(_this,'_this')
	_this.user_options = {}
	if (err.status == 1002 || err.status == 1006) {
		_this.$nextTick(() =>{
			_this.user_options = {
				toLogin: true,
				content: common.changeTxt('未获取到您的头像、昵称等信息，当前页面无法展示'),
				url: `/pages/store/homePage?store_id=${_this.store_id}`,
				goType: 'back'
			}
		})
		_this.$forceUpdate()
	} else {
		let contents = err.msg;
		if (!contents) {
			return
		}
		uni.showModal({
			title: common.app ? common.changeTxt('提示') : '提示',
			content: contents,
			confirmText:common.changeTxt('确定'),
			showCancel: false,
			success: function() {}
		});
	}
}

let showToastOpt = (msg,lang='',duration=2000,icon='none', successFun = '', failFun = '') => {
	console.log(lang,'lang')
	if (msg == '') return
	uni.showToast({
		title: common.changeTxt(msg,lang),
		duration: duration,
		icon: icon,
		success: () => {
			if (successFun) {
				successFun()
			}
		},
		fail: () => {
			if (failFun) {
				failFun()
			}
		}
	})
}

// 颜色转化为rgba
let colorRgb = (colorHex, opacityVal = 1) => {
	// 16进制颜色值的正则
	let reg = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/
	// 把颜色值变成小写
	let color = colorHex.toLowerCase()
	if (reg.test(color)) {
		// 如果只有三位的值，需变成六位，如：#fff => #ffffff
		if (color.length === 4) {
			let colorNew = "#";
			for (let i = 1; i < 4; i += 1) {
				colorNew += color.slice(i, i + 1).concat(color.slice(i, i + 1))
			}
			color = colorNew
		}
		// 处理六位的颜色值，转为RGB
		let colorChange = []
		for (let i = 1; i < 7; i += 2) {
			colorChange.push(parseInt("0x" + color.slice(i, i + 2)))
		}
		return "rgba(" + colorChange.join(",") + ',' + opacityVal + ")"
	} else {
		return color;
	}
}

// 判断是否为空为空返回''
let emptyData = data =>{
	if(!data|| data instanceof Array && data.length == 0 || data == 'null' || data == 'undefined') {
		// 空字符串、undefined、 空数组
		return ''
	}else if( data instanceof Object && JSON.stringify(data) == "{}") {
		// 空对象
		return ''
	}else {
		return data
	}
}

// 商家小程序 存储用户下单的店铺id及对应的就餐方式
let storageUserSaveOrderByMerchant = (store_id) => {
	let is_merchant_wxapp = common.$platConfig.is_merchant_wxapp || false
	if (is_merchant_wxapp) {
		let dining_type = uni.getStorageSync('foodshop_dining_type') || ''
		if (dining_type == 'selftake') {
			let selftake_merFoodshop = {
				dining_type: dining_type,
				store_id: store_id
			}
			uni.setStorageSync('selftake_merFoodshop', JSON.stringify(selftake_merFoodshop))
		} else if (dining_type == 'inhouse') {
			let inhouse_merFoodshop = {
				dining_type: dining_type,
				store_id: store_id
			}
			uni.setStorageSync('inhouse_merFoodshop', JSON.stringify(inhouse_merFoodshop))
		}
	}
}

module.exports = {
	getMenuPosition,
	getStatusBarStyle,
	getNavigationBarStyle,
	requestFail,
	showToastOpt,
	colorRgb,
	emptyData,
	storageUserSaveOrderByMerchant
};