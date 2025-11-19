import common from '../../../../utils/common.js'

let imgDomain = common.requestDomain
// 图片或视频拼接域名
let handleImgOrVideo = (url) => {
	if (url) {
		if (url.indexOf('http://') == -1 && url.indexOf('https://') == -1) {
			if(url.indexOf('/') == 0) {
				url = url.substring(1)
			}
			url = imgDomain + url
		}
	}
	return url
}

/* 
	获取微页面信息 || 主页信息
	params = {
		source: '', // 来源 platform merchant store
		source_id: '', // 来源id mer_id store_id
		pageId: '' // 页面id
		type: '1', // 2 预览 默认1  微页面信息需要
	}
	callBack 回调
	api 接口地址
	hasCustomModule 是否有自定义模块
 */

let customPageInfoApi = '/common/common.DecoratePage/getEditMicoPage'
let getCustomModuleInfo = (params) => {
	return new Promise((resolve,reject) => {
		let success = (res) => {
			resolve(res)
		}
		common.postV20(customPageInfoApi, params, success)
	})
}

let getPageInfo = (params = '', callBack = '',api = '') => {
	if (!params) return
	
	if (!api) {
		api = customPageInfoApi
	}
	
	let success = (res) => {
		if (res && JSON.stringify(res) != '[]' && JSON.stringify(res) != '{}') {
			if (res && res.custom && res.custom.length) {
				res.custom.forEach(async (item,index) => {
					// 如果有自定义模块 根据自定义模块id获取页面信息
					if (item && item.type == 'customModule' && item.content && item.content.id) {
						params.id = item.content.id
						params.type = 1
						let customModuleList = await getCustomModuleInfo(params)
						// 对应index插入自定义模块内容
						res.custom.splice(index, 1, ...customModuleList.custom);
					}
				})
				callBack(res)
				return
			}
			callBack(res)
		}
	}
	
	common.postV20(api, params, success)
}

module.exports = {
	handleImgOrVideo,
	getPageInfo,
	imgDomain
};