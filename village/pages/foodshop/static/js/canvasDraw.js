function canvasDraw(id, obj) {
	this.ctx = uni.createCanvasContext(id, obj);
	this.ratio = getPixelRatio(this.ctx);
	this.ctx.mozImageSmoothingEnabled = false;
	this.ctx.webkitImageSmoothingEnabled = false;
	this.ctx.msImageSmoothingEnabled = false;
	this.ctx.imageSmoothingEnabled = false;
	this.winWidth = uni.getSystemInfoSync().windowWidth * 2;
}

function getPixelRatio (context) {
	return uni.getSystemInfoSync().pixelRatio
}

canvasDraw.prototype = {
	getPx(rpx) {
		return rpx / (750 / this.winWidth);
	},
	getRpx(px) {
		return px * (750 / this.winWidth);
	},
	judgeRpxOrPx(num) {
		if ((num + '').indexOf('rpx') === -1) {
			return this.getPx(parseInt(num));
		} else {
			return parseInt(num);
		}
	},
	/**
	 * 绘制圆角图片
	 * img, 
	 * x, rpx
	 * y, rpx
	 * width, rpx || px
	 * height rpx || px
	 *  同于drawImage的参数
	 * r 圆角半径 rpx
	 * bgColor canvas背景颜色
	 */
	drawFilletFillImg(img, sx, sy, swidth, sheight, x, y, width, height,r) {
		this.ctx.save();
		this.ctx.moveTo(x, y + r);
		this.ctx.beginPath();
		if (width === height && r >= width / 2) {
			this.ctx.arc(x + r, y + r, r, 0, 2 * Math.PI);
		} else {
			this.ctx.arc(x + r, y + r, r, Math.PI, 1.5 * Math.PI);
			this.ctx.arc(x + width - r, y + r, r, 1.5 * Math.PI, 2 * Math.PI);
			this.ctx.arc(x + width - r, y + height - r, r, 0, 0.5 * Math.PI);
			this.ctx.arc(x + r, y + height - r, r, 0.5 * Math.PI, Math.PI);
		}
		this.ctx.closePath();
		this.ctx.clip();
		this.ctx.drawImage(img, sx, sy, swidth, sheight, x, y, width, height)
		this.ctx.restore();
	},
	/**
	 * 绘制圆角矩形
	 * img, 
	 * x, rpx
	 * y, rpx
	 * width, rpx || px
	 * height rpx || px
	 *  同于drawImage的参数
	 * r 圆角半径 rpx
	 * isGrd 是否渐变
	 * bgColor canvas背景颜色
	 */
	drawFilletFillRect(x, y, width, height, r, isGrd, drawColor) {
		x = this.getPx(x);
		y = this.getPx(y);
		width = this.getPx(width);
		height = this.getPx(height);
		r = this.getPx(r);
		if (isGrd) {
			const grd = this.ctx.createLinearGradient(0, 0, width, 0)
			console.log(drawColor, 'drawColor')
			for (let index = 0; index < drawColor.length; index++) {
				if (index == 0) {
					grd.addColorStop(0, drawColor[index])
				} else if (index != drawColor.length - 1) {
					grd.addColorStop((index / 10) * (10 / drawColor.length), drawColor[index])
				} else {
					grd.addColorStop(1, drawColor[index])
				}
			}
			console.log(grd, 'grd')
			this.ctx.setFillStyle(grd); //将渐变色渲染入正方形
		} else {
			this.ctx.setFillStyle(drawColor[0]);
		}
		this.ctx.fillRect(x, y, width, height)

		this.roundRect(x, y, width, height, r || 0);
	},
	/**
	 * 绘制文本
	 * fontSize 字体大小
	 * color 字体颜色
	 * str 绘制的文本
	 * x, y 坐标 单位均为rpx
	 */
	drawText(str, x, y, fontSize = 24, color = '#000', align = 'left') {
		x = this.getPx(x);
		y = this.getPx(y + fontSize);
		fontSize = this.getPx(fontSize);
		this.ctx.save();
		this.ctx.setFontSize(fontSize)
		this.ctx.setTextAlign(align);
		this.ctx.fillStyle = color;
		this.ctx.fillText(str, x, y );
		this.ctx.restore();
		return this;
	},
	/**
	 * callback 绘制完成之后的回调
	 */
	drawFinally(callback) {
		let that = this;
		that.ctx.draw(true, function() {
			setTimeout(function() {
				callback && callback(that.ctx);
			}, 500)
		}, that);
	},
	/**
	 * destWidth 导出的图片目标宽度
	 * canvasHeight canvas高度 （单位px）
	 */
	canvasToPosterImg(canvasId, canvasWidth, canvasHeight, destWidth, destHeight, callback, is_showLoading = false) {
		let that = this;
		if (is_showLoading) {
			uni.showLoading({
				title: '正在生成图片...',
				mask: true
			})
		}

		// let destHeight = destWidth / that.winWidth * canvasHeight;
		uni.canvasToTempFilePath({
			x: 0,
			y: 0,
			width: canvasWidth,
			height: canvasHeight,
			destWidth: destWidth,
			destHeight: destHeight,
			canvasId: canvasId,
			quality: 1,
			fileType: 'jpg',
			success: (res) => {
				if (is_showLoading) {
					uni.hideLoading();
				}
				callback && callback(res);
			},
			fail: function(err) {
				console.log(err);
				callback && callback(err);
			}
		}, that)
	},
	roundRect: function(x, y, w, h, r) {
		var min_size = Math.min(w, h);
		if (r > min_size / 2) r = min_size / 2;
		// 开始绘制
		this.ctx.beginPath();
		this.ctx.moveTo(x + r, y);
		this.ctx.arcTo(x + w, y, x + w, y + h, r);
		this.ctx.arcTo(x + w, y + h, x, y + h, r);
		this.ctx.arcTo(x, y + h, x, y, r);
		this.ctx.arcTo(x, y, x + w, y, r);
		this.ctx.closePath();
	},
	/**
	 * 16进制转化为rgb
	 */
	colorRgb: function(colorHex, opacityVal = 1) {
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
	},
	/**
	 * rgb/rgba转化为16进制
	 */
	hexify: function(color) {
		let values = color
			.replace(/rgba?\(/, '')
			.replace(/\)/, '')
			.replace(/[\s+]/g, '')
			.split(',');
		let a = parseFloat(values[3] || 1),
			r = Math.floor(a * parseInt(values[0]) + (1 - a) * 255),
			g = Math.floor(a * parseInt(values[1]) + (1 - a) * 255),
			b = Math.floor(a * parseInt(values[2]) + (1 - a) * 255);
		return "#" +
			("0" + r.toString(16)).slice(-2) +
			("0" + g.toString(16)).slice(-2) +
			("0" + b.toString(16)).slice(-2);
	},
}

module.exports = {
	canvasDraw: canvasDraw
}
