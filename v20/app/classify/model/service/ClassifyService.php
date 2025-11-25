<?php


namespace app\classify\model\service;


use app\classify\model\db\ClassifyUserInput;

class ClassifyService
{
    public function classifyUserInputList($fcid, $limit)
    {
        $data = ClassifyUserInput::where([
                'status' => 1,
                'fcid' => $fcid,
            ])
            ->with(['user' => function($query){
                $query->field('uid, nickname, avatar');
            }])
            ->field(
                'id, uid, cid, fcid, title, lxname, lxtel, description, address, addtime, 
            views, shares, imgs, content'
            )
            ->append(['release_time'])
            ->order('topsort DESC, updatetime DESC')
            ->limit($limit)
            ->select()
            ->toArray();
        foreach ($data as &$item){
            if(!empty($item['imgs'])){
                foreach ($item['imgs'] as &$img){
                    $img = replace_file_domain($img);
                }
            }
            $item['user']['avatar'] = replace_file_domain($item['user']['avatar']);

            $item['cate_url'] = cfg('site_url').'/wap.php?g=Wap&c=Classifynew&a=category&cat_id='.$item['cid'];
            $item['classify_user_url'] = cfg('site_url').'/wap.php?g=Wap&c=Classifynew&a=member&uid='.$item['uid'];
            $item['view_url'] = cfg('site_url').'/wap.php?c=Classifynew&a=view&id='.$item['id'];
        }        
        return $data;
    }
}