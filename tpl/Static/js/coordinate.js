// Coordinate conversion
// 天地图: Uses CGCS2000 coordinate system (very close to WGS-84, can be used interchangeably in most cases)
// 百度地图: Uses BD-09 coordinate system (secondary encryption based on GCJ-02)
// 高德地图: Uses GCJ-02 coordinate system (encrypted version of WGS-84 for Chinese maps)
// 腾讯地图: Uses GCJ-02 coordinate system (same as Gaode)

class Coordinate {
    // WGS-84 (Tianditu) to BD-09 (Baidu)
    static wgs84ToBd09(wgs84_lng, wgs84_lat) {
        const gcj = this.wgs84ToGcj02(wgs84_lng, wgs84_lat);
        return this.gcj02ToBd09(gcj[0], gcj[1]);
    }

    // WGS-84 (Tianditu) to GCJ-02 (Gaode/Tencent)
    static wgs84ToGcj02(wgs84_lng, wgs84_lat) {
        if (this.outOfChina(wgs84_lng, wgs84_lat)) {
            return [wgs84_lng, wgs84_lat];
        }
        const dLat = this.transformLat(wgs84_lng - 105.0, wgs84_lat - 35.0);
        const dLng = this.transformLng(wgs84_lng - 105.0, wgs84_lat - 35.0);
        const radLat = wgs84_lat / 180.0 * Math.PI;
        let magic = Math.sin(radLat);
        magic = 1 - 0.00669342162296594323 * magic * magic;
        const sqrtMagic = Math.sqrt(magic);
        const new_dLat = (dLat * 180.0) / ((6378245.0 * (1 - 0.00669342162296594323)) / (magic * sqrtMagic) * Math.PI);
        const new_dLng = (dLng * 180.0) / (6378245.0 / sqrtMagic * Math.cos(radLat) * Math.PI);
        const mgLat = wgs84_lat + new_dLat;
        const mgLng = wgs84_lng + new_dLng;
        return [mgLng, mgLat];
    }

    // GCJ-02 (Gaode/Tencent) to BD-09 (Baidu)
    static gcj02ToBd09(gcj_lng, gcj_lat) {
        const x = gcj_lng;
        const y = gcj_lat;
        const z = Math.sqrt(x * x + y * y) + 0.00002 * Math.sin(y * Math.PI * 3000.0 / 180.0);
        const theta = Math.atan2(y, x) + 0.000003 * Math.cos(x * Math.PI * 3000.0 / 180.0);
        const bd_lng = z * Math.cos(theta) + 0.0065;
        const bd_lat = z * Math.sin(theta) + 0.006;
        return [bd_lng, bd_lat];
    }

    // GCJ-02 (Gaode/Tencent) to WGS-84 (Tianditu)
    static gcj02ToWgs84(gcj_lng, gcj_lat) {
        const d = this.transformLat(gcj_lng - 105.0, gcj_lat - 35.0);
        const e = this.transformLng(gcj_lng - 105.0, gcj_lat - 35.0);
        const radLat = gcj_lat / 180.0 * Math.PI;
        let magic = Math.sin(radLat);
        magic = 1 - 0.00669342162296594323 * magic * magic;
        const sqrtMagic = Math.sqrt(magic);
        const new_dLat = (d * 180.0) / ((6378245.0 * (1 - 0.00669342162296594323)) / (magic * sqrtMagic) * Math.PI);
        const new_dLng = (e * 180.0) / (6378245.0 / sqrtMagic * Math.cos(radLat) * Math.PI);
        const mgLat = gcj_lat + new_dLat;
        const mgLng = gcj_lng + new_dLng;
        return [gcj_lng * 2 - mgLng, gcj_lat * 2 - mgLat];
    }

    // BD-09 (Baidu) to GCJ-02 (Gaode/Tencent)
    static bd09ToGcj02(bd_lng, bd_lat) {
        const x = bd_lng - 0.0065;
        const y = bd_lat - 0.006;
        const z = Math.sqrt(x * x + y * y) - 0.00002 * Math.sin(y * Math.PI * 3000.0 / 180.0);
        const theta = Math.atan2(y, x) - 0.000003 * Math.cos(x * Math.PI * 3000.0 / 180.0);
        const gg_lng = z * Math.cos(theta);
        const gg_lat = z * Math.sin(theta);
        return [gg_lng, gg_lat];
    }

    // BD-09 (Baidu) to WGS-84 (Tianditu)
    static bd09ToWgs84(bd_lng, bd_lat) {
        const gcj = this.bd09ToGcj02(bd_lng, bd_lat);
        return this.gcj02ToWgs84(gcj[0], gcj[1]);
    }

    // Check if coordinates are outside China
    static outOfChina(lng, lat) {
        return (lng < 72.004 || lng > 137.8347) || (lat < 0.8293 || lat > 55.8271);
    }

    static transformLat(x, y) {
        let ret = -100.0 + 2.0 * x + 3.0 * y + 0.2 * y * y + 0.1 * x * y + 0.2 * Math.sqrt(Math.abs(x));
        ret += (20.0 * Math.sin(6.0 * x * Math.PI) + 20.0 * Math.sin(2.0 * x * Math.PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(y * Math.PI) + 40.0 * Math.sin(y / 3.0 * Math.PI)) * 2.0 / 3.0;
        ret += (160.0 * Math.sin(y / 12.0 * Math.PI) + 320 * Math.sin(y * Math.PI / 30.0)) * 2.0 / 3.0;
        return ret;
    }

    static transformLng(x, y) {
        let ret = 300.0 + x + 2.0 * y + 0.1 * x * x + 0.1 * x * y + 0.1 * Math.sqrt(Math.abs(x));
        ret += (20.0 * Math.sin(6.0 * x * Math.PI) + 20.0 * Math.sin(2.0 * x * Math.PI)) * 2.0 / 3.0;
        ret += (20.0 * Math.sin(x * Math.PI) + 40.0 * Math.sin(x / 3.0 * Math.PI)) * 2.0 / 3.0;
        ret += (150.0 * Math.sin(x / 12.0 * Math.PI) + 300.0 * Math.sin(x / 30.0 * Math.PI)) * 2.0 / 3.0;
        return ret;
    }
}

// Export for Node.js or browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Coordinate;
} else {
    window.Coordinate = Coordinate;
}