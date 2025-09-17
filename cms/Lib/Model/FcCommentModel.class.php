<?php

class FcCommentModel extends Model
{
    function getFcCommentList($where){
        $commentList = M('fc_comment as c')
            ->join(C('DB_PREFIX').'user u ON c.uid = u.uid')
           // ->field('b.*')
            ->where($where)
            ->select();

        foreach($commentList as &$list){
            $userLevel = D(_view('User_level'))->field('lname,icon')->where(array('uid'=>$list['uid']))->find();
            $articleCount = D('Fc_article')->where(array('uid'=>$list['uid']))->count('id');

            //用户等级
            $list['lname'] = $userLevel['$userLevel'];
            $list['icon'] = $userLevel['icon'];

            //用户发帖数
            $list['articleCount'] = $articleCount;
        }
        return $commentList;
    }
}