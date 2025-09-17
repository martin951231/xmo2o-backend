<?php
/**
 * 公共controller
 * Author: 衡婷妹
 * Date Time: 2020/09/14
 */

namespace app\common\controller\common;
use app\common\controller\CommonBaseController;
use app\common\model\service\asr\AsrService;
use app\common\model\service\CacheSqlService;
use app\common\model\service\FdumpSqlService;
class IndexController extends CommonBaseController
{
    /**
     * 清除缓存
     * Author: hengtingmei
     * @return array
     */
    public function cleanCache()
    {
        try {
            (new CacheSqlService())->clearCache();
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

        return api_output(0, ['']);
    }
    public function delFdumpSql()
    {
        try {
            $fdumpSqlService=new FdumpSqlService();
            $ret=$fdumpSqlService->delFdumpSqlData();
            /*
            $whereArr=array(['id','>',0]);
            $retObj=$fdumpSqlService->getListData($whereArr,'id','id ASC',1,300);
            if($retObj && !$retObj->isEmpty()){
                $retTmp=$retObj->toArray();
                foreach ($retTmp as $vv){
                    $tmpWhere=array(['id','=',$vv['id']]);
                    $fdumpSqlService->del_data($tmpWhere);
                }
            }
            */
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $ret);
    }

    /**
     * 删除缓存
     * Author: hengtingmei
     * @return array
     */
    public function deleteCache()
    {
        $param = request()->param();
        (new CacheSqlService())->deleteCache($param);
        return api_output(0, ['']);
    }
	
	public function tinit()
	{
		$client = new AsrService();
		
		//创建热词表
		$params = [
			"Name"        => "中海原山",
			"Description" => "一个小区名称",
			"WordWeights" => [
				[
					"Word" => "中海",
					"Weight" => 2
				]
			]
		];
//		$result =  $client->createAsrVocab($params);
//		var_export($result); 
		//正确反馈： '{"VocabId":"6e36f07d42e111edbd76446a2eb5fd98","RequestId":"50195bd1-7793-4ff5-974f-186e222e01a6"}'
		//重复执行的时候，错误反馈：  'name duplicate'
		
		
		//获取热词列表
		$result = $client->getAsrVocab('0e1580a73d7611edbd76446a2eb5fd98');
		dump($result);//"{"Name":"物业费","Description":"","VocabId":"0e1580a73d7611edbd76446a2eb5fd98","WordWeights":[{"Word":"智聆","Weight":1},{"Word":"滨海大厦","Weight":6},{"Word":"高价大厦","W ▶"
	}

	public function stat(){
        return api_output(0, []);
    }

    public function captcha()
    {
        return captcha();
    }
}
