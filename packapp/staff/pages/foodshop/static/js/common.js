import common from '../../../../utils/common.js'

// uni.showToast
let showToastOpt = (msg,lang = {},duration=2000,icon='none') => {
	if (msg == '') return
	uni.showToast({
		title: common.changeTxt(msg,lang),
		duration: duration,
		icon: icon
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


module.exports = {
	showToastOpt,
	colorRgb
};