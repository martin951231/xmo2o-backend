require('../../common/vendor.js');import common from '../../../../utils/common.js'

let timer = null,
	is_timeOut = false,
	has_cityCenter = false;
// 定位
let getLocation = (_this, now_city, callback, timeOut = 5000, isGetLocationByNowCity = false) => {
	let getLocation_msg = (res) => {
		is_timeOut = true
		if ((res.lng && res.lat) || (res.latitude && res.longitude)) {
			if (res.latitude && res.longitude) {
				res.lng = res.longitude
				res.lat = res.latitude
			}
			callback && callback(res)
		} else {
			// #ifdef H5
			if (isGetLocationByNowCity) {
				getNowCityLocation(_this,now_city,callback)
			} else {
				callback && callback(res)
			}
			// #endif
			
			// #ifdef MP-WEIXIN || MP-TOUTIAO
			uni.getSetting({
				success: res => {
					let authSetting = res.authSetting || ''
					if (typeof authSetting['scope.userLocation'] != 'undefined' && authSetting['scope.userLocation'] == true) {
						_this.is_auth_location = true
					} else {
						_this.is_auth_location = false
					}
					console.log('group--common--定位授权--is_auth_location',_this.is_auth_location)
					if (!_this.is_auth_location) {
						_this.show_modal = true
					} else {
						_this.show_modal = false
						callback && callback({lng: '',lat: ''})
					}
				}
			})
			// #endif
		}
	}
	// #ifdef H5
	common.locationFun(getLocation_msg, _this, 'baidu', 'gcj02')
	
	timer = setTimeout(() => {
		timer = null
		clearTimeout(timer)
		if (!is_timeOut && isGetLocationByNowCity) {
			getNowCityLocation(_this,now_city,callback)
		}
	},timeOut)
	// #endif
	
	// #ifdef MP-WEIXIN || MP-TOUTIAO
	common.locationFun(getLocation_msg, _this, 'baidu',  0, 0, 'gcj02')
	// #endif
}

// 打开授权设置
let openSettingHandle = (e,_this,now_city,callback,timeOut) => {
	let authLocation = e.detail.authSetting['scope.userLocation'] || ''
	if ( authLocation ) {
		_this.show_modal = false
		getLocation(_this, now_city, callback, timeOut = 5000)
	}
}

// 获取定位失败，根据城市id获取当前城市中心坐标
let getNowCityLocation = (_this,now_city,callback) => {
	if (now_city == '') {
		common.getConfig((data) => {
			now_city = data && data.city && data.city.area_id?data.city.area_id:''
		})
	}
	
	let params = {
		now_city,
		noTip: true
	}
	let success = (res) => {
		if (callback && res && res.city_info) {
			res.city_info.area_name = res.city_info.city_name
			res.city_info.area_id = res.city_info.city_id
			callback(res.city_info)
			has_cityCenter = false
		}
	}
	let fail = (err) => {
		if (callback) {
			callback()
		}
		has_cityCenter = false
	}
	if (!has_cityCenter) {
		has_cityCenter = true
		_this.$request('/common/common.area/getCityCenter',params,success,fail)
	}
}

// 团购轮播 导航 广告 附近好店
let getIndex = (_this,params,callback, api="/group/api.group/getList") => {
	let success = (res) => {
		if (callback) {
			callback(res)
		}
	}
	_this.$request(api, params, success)
}

// 筛选条件
let filterDataHandle = (cat_fid, cat_id, res, callBack,cat_name="分类") => {
	let { nearby = [], category_list = [], sort = [], recommend = [] } = res
	let filterData = [
		{
			name: cat_name,
			type: 'hierarchy',
			key: 'category_list',
			submenu: []
		},
		{
			name: common.changeTxt('附近'),
			type: 'hierarchy',
			key: 'nearby',
			submenu: []
		},
		{
			name: common.changeTxt('智能排序'),
			type: 'hierarchy',
			key: 'sort',
			submenu: []
		}
	]
	let area_id,cat_value,sort_value;
	filterData.forEach(item => {
		if (item.key === 'nearby') {
			item.submenu = getNearby(nearby)
			area_id = item.submenu[0].submenu[0].area_id
		} else if (item.key === 'category_list') {
			item.submenu = getCategoryList(category_list,cat_fid,cat_id,cat_name)
			cat_value = cat_id?cat_id:item.submenu[0].cat_id
		} else if (item.key === 'sort') {
			item.submenu = getSort(sort)
			sort_value = item.submenu[0].sort_value
		}
	})
	if (callBack) {
		callBack({
			filterData,
			area_id,
			cat_value,
			sort_value
		})
	}
}

// 附近
let getNearby = (list) => {
	let defaultNearby = [
		{
			name: common.changeTxt('附近'),
			type: 'hierarchy',
			value: '',
			area_id: '',
			area_name: common.changeTxt('附近'),
			submenu: [
				{
					name: common.changeTxt('附近'),
					type: 'radio',
					value: '',
					area_id: '',
					area_name: common.changeTxt('附近')
				}
			]
		}
	]
	let nearby = []
	if (list.length) {
		nearby = list.filter(item => {
			item.name = item.area_name
			item.type = 'hierarchy'
			item.value = item.area_id
			item.submenu = []
			if (item.area_list.length) {
				item.submenu = item.area_list.filter(subItem => {
					subItem.name = subItem.area_name
					subItem.value = subItem.area_id
					subItem.type = 'radio'
					return subItem
				})
			}
			return item
		})
	}
	return defaultNearby.concat(nearby)
}
// 分类
let getCategoryList = (list,cat_fid,cat_id,cat_name) => {
	let defaultCategoryList = [
		{
			name: `全部${cat_name}`,
			type: 'radio',
			value: '',
			cat_name: `全部${cat_name}`,
			cat_id: ''
		}
	]
	let categoryList = []
	if (list.length) {
		categoryList = list.filter(item => {
			item.name = item.cat_name
			item.type = 'hierarchy'
			item.value = item.cat_id
			item.submenu = []
			if (item && item.category_list && item.category_list.length) {
				item.submenu = item.category_list.filter(subItem => {
					subItem.name = subItem.cat_name
					subItem.value = subItem.cat_id
					subItem.type = 'radio'
					return subItem
				})
			}
			return item
		})
	}
	return defaultCategoryList.concat(categoryList)
}
// 排序
let getSort = (list = []) => {
	let sort = []
	if (list.length) {
		sort = list.filter(item => {
			item.name = item.sort_name
			item.value = item.sort_value
			item.type = 'radio'
			return item
		})
	}
	return sort
}

module.exports = {
	getLocation,
	openSettingHandle,
	getIndex,
	filterDataHandle
};