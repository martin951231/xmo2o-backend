<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2022/2/16
 * Time: 13:53
 *======================================================
 */

namespace customization;

trait customization
{
    /**
     * 公安人脸数据  营山定制
     * User: zhanghan
     * Date: 2022/2/14
     * Time: 10:25
     * @param $domain
     * @return bool
     */
    public static function hasCloudIntercom(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('huizhisq.com'))){
            return true;
        }
        return false;
    }
    public static function hasAihorseWNet(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('aihorse.net'))){
            return true;
        }
        return false;
    }

    /**
     * 杂淘定制
     * User: zhanghan
     * Date: 2022/2/14
     * Time: 11:33
     * @return bool
     */
    public static function hasThirdZatao(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('zatao.cc'))){
            return true;
        }
        return false;
    }

    /**
     * 环球定制
     * User: zhanghan
     * Date: 2022/2/16
     * Time: 13:47
     * @return bool
     */
    public static function hasYiLvBao(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('yilvbao.cn'))){
            return true;
        }
        return false;
    }

    /**
     * 延华定制
     * User: zhanghan
     * Date: 2022/2/21
     * Time: 16:05
     * @return bool
     */
    public static function hasPmwlc(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('chinarg.cn'))){
            return true;
        }
        return false;
    }

    /**
     * 遂川定制
     * @author: liukezhu
     * @date : 2022/6/28
     * @return bool
     */
    public static function hasSuiChuan(){
        if (in_array(getRunTopDomain(true),MergeWhitDomain('sccssq.dazhongbanben.com'))){
            return true;
        }
        return false;
    }


    /**
     * hqby.shanxisendijingtong.com定制
     * @author: liukezhu
     * @date : 2022/7/25
     * @return bool
     */
    public static function hasHqby(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('shanxisendijingtong.com'))){
            return true;
        }
        return false;
    }

    public static function hasMeijuWuyeCustomized(){
        if (in_array(getRunTopDomain(),MergeWhitDomain('meijuwuye.com'))){
            return true;
        }
        return false;
    }
}