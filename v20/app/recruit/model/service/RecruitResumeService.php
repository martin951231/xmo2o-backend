<?php
namespace app\recruit\model\service;

use app\recruit\model\db\NewRecruitResume;
use app\recruit\model\db\NewRecruitJob;
use app\common\model\db\User;
use app\common\model\db\Area;

class RecruitResumeService
{
    public $resumeMod = null;

    public function __construct()
    {
        $this->resumeMod = new NewRecruitResume();
    }

    /**
     * 浏览量增加
     */
    public function recruitResumeInc($id)
    {
        $where = ['id'=>$id];
        $return=(new NewRecruitResume())->where($where)->inc('view_nums')->update();
        return $return;
    }

    /**
     * 城市ID
     */
    function getNameCityId($city_name){
        $city = (new Area())->where([['area_name', 'like', $city_name.'%'], ['area_pid', '>', 0]])->order('area_id ASC')->find();
        return $city;
    }

    //获取城市下的所有区域
    public function getAreaAndOneCircles($area_id,$fields){
        $now_city = (new Area())->where(['area_pid'=>$area_id])->field($fields)->select();
        return $now_city;
    }

    /**
     * 简历基本信息
     */
    public function recruitResumeBasic($where, $fields='*')
    {
        $return=(new NewRecruitResume())->where($where)->field($fields)->find();
        if(empty($return)){
            return [];
        }else{
            $return = $return->toArray();
        }
        if($return['portrait']){
            $return['portrait'] = [
                'path'=>$return['portrait'],
                'url'=>replace_file_domain($return['portrait']),
            ];
        }else{
            $return['portrait'] = [
                'path'=>'/static/avatar.jpg',
                'url'=>replace_file_domain('/static/avatar.jpg'),
            ];
        }
        return $return;
    }

    /**
     * 简历基本信息保存
     */
    public function recruitResumeBasicCreate($params, $id)
    {
        //年
        $years = 0;
        if($params['birthday']<time() && $params['birthday']>0){
            $remainder_seconds = abs(time() - $params['birthday']);
            if ($remainder_seconds - 31536000 > 0) {
                $years = intval($remainder_seconds / (31536000));
            }
        }
        $data = array(
            'uid' => $params['uid'],
            'name' => $params['name'],
            'sex' => $params['sex'],
            'phone' => $params['phone'],
            'portrait' => $params['portrait'],
            'on_job' => $params['on_job'],
            'birthday' => $params['birthday'],
            'work_time' => $params['work_time'],
            'update_time' => time(),
            'age'=>$years
        );
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            $return=(new NewRecruitResume())->where($where)->update($data);
        }else{
            // 新增
            $return=(new NewRecruitResume())->add($data);
        }
        return $return;
    }

    /**
     * 简历自我评价
     */
    public function recruitResumeEvaluate($where, $fields='*')
    {
        $return=(new NewRecruitResume())->where($where)->field($fields)->order(['id desc'])->find();
        return $return;
    }

    /**
     * 简历自我评价保存
     */
    public function recruitResumeEvaluateCreate($params, $id)
    {
        $data = array(
            'uid' => $params['uid'],
            'evaluate' => $params['evaluate'],
            'update_time' => time(),
        );
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            $return=(new NewRecruitResume())->where($where)->update($data);
        }else{
            // 新增
            $return=(new NewRecruitResume())->add($data);
        }
        $this->updateRecruitResumeTime($data['uid']);
        return $return;
    }

    /**
     * 简历预览
     */
    public function recruitResumePreview($uid)
    {
        $where = [['uid','=',$uid], ['is_del','=',0]];
        $return = (new NewRecruitResume())->where($where)->order(['id desc'])->find();
        if (empty($return)) {
            return [];
        }else{
            $return = $return->toArray();
        }
        $return['portrait'] = $return['portrait'] ? replace_file_domain($return['portrait']) : replace_file_domain('/static/avatar.jpg');
        // 位置
        $user = (new User())->where(['uid'=>$return['uid']])->order('uid,city_id,area_id')->find();
        $city = (new Area())->where(['area_id'=>$user['city_id']])->order('area_id,area_name')->find();
        $area = (new Area())->where(['area_id'=>$user['area_id']])->order('area_id,area_name')->find();
        $area_city = $city['area_name'].$area['area_name'];
        $return['area_city'] = $area_city;
        return $return;
    }

    /**
     * 简历详情
     */
    public function recruitResumeDetails($id)
    {
        $where = [['id','=',$id], ['is_del','=',0]];
        $return = (new NewRecruitResume())->where($where)->find();
        if(empty($return)){
            return [];
        }
        $return['portrait'] = $return['portrait'] ? replace_file_domain($return['portrait']) : replace_file_domain('/static/avatar.jpg');
        // 位置
        $user = (new User())->where(['uid'=>$return['uid']])->order('uid,city_id,area_id')->find();
        $city = (new Area())->where(['area_id'=>$user['city_id']])->order('area_id,area_name')->find();
        $area = (new Area())->where(['area_id'=>$user['area_id']])->order('area_id,area_name')->find();
        $area_city = $city['area_name'].$area['area_name'];
        $return['area_city'] = $area_city;
        return $return;
    }

    /**
     * 找人才
     */
    public function RecruitJobPersonnelList($params, $page, $pageSize)
    {
        $where[] = ['g.is_del','=',0];

        // 职位
        if($params['position_id'] > 0){
            $third = (new NewRecruitJob())->where(['job_id'=>$params['position_id']])->field('third_cate')->find();
            $where[] = ['a.job_id','=',$third['third_cate']];
        }

        // 区域
        if($params['area_id'] > 0){
            $params['area_id'][] = 0;
            $where[] = ['a.area_id','in',$params['area_id']];
        }

        // 年龄
        if(!empty($params['age'])){
            $where[] = ['g.age','between',[$params['age'][0], $params['age'][1]]];
        }

        // 是否面议
        if(!empty($params['is_face'])){
            $where[] = ['a.salary','=', '0,0'];
        }else{
            // 薪资
            if(!empty($params['salary'])){
                $where[] = ['a.salary','between',[$params['salary'][0], $params['salary'][1]]];
            }
        }

        // 年限
        if(!empty($params['years'])){
            foreach($params['years'] as $vs){
                if($vs==0){
                    $where[] = ['g.work_time','between',[$this->years(0), $this->years(1)]];
                }elseif($vs==1){
                    $where[] = ['g.work_time','between',[$this->years(1), $this->years(3)]];
                }elseif($vs==2){
                    $where[] = ['g.work_time','between',[$this->years(3), $this->years(5)]];
                }elseif($vs==3){
                    $where[] = ['g.work_time','between',[$this->years(5), $this->years(10)]];
                }elseif($vs==4){
                    $where[] = ['g.work_time','<', $this->years(10)];
                }
            }
        }

        print_r($where);
        // 学历
        if(!empty($params['education'])){
            $where[] = ['b.education','in',$params['education']];
        }

        // 搜索条件
        if(!empty($params['search'])){
            $where[] = [['e.cat_title','like','%'.$params['search'].'%'], ['f.company_name','like','%'.$params['search'].'%'], ['b.school_name','like','%'.$params['search'].'%'], 'or'];
        }

        $order = 'u.last_time desc, g.view_nums desc';

        $fields = 'g.id, g.name, g.sex, g.birthday, g.work_time, g.portrait as user_logo, a.city_id, a.area_id, b.education, c.area_name, d.area_name as city_name, e.cat_title, u.last_time';

        $return = (new NewRecruitResume())->RecruitJobPersonnelList($where, $order, $fields, $page, $pageSize);

        foreach($return as $k=>$v){
            $return[$k]['education'] = $this->education($v['education']);
            $return[$k]['area_city'] = $v['city_name'].$v['area_name'];
            $times = $this->calcTime($v['last_time'], time());
            if($times[0] > 0){
                if($times[0] < 12){
                    $return[$k]['active'] = $this->month($times[0]);
                }else{
                    $return[$k]['active'] = '很久未活跃';
                }
            }else{
                if($times[1] > 0){
                    if($times[1] < 7){
                        $return[$k]['active'] = $this->day($times[1]);
                    }else{
                        $return[$k]['active'] = '一月内活跃';
                    }
                }else{
                    if($times[2] > 0){
                        if($times[2] < 7){
                            $return[$k]['active'] = $this->time($times[2]);
                        }else{
                            $return[$k]['active'] = '一天内活跃';
                        }
                    }else{
                        $return[$k]['active'] = '一天内活跃';
                    }
                }
            }
            if($v['sex'] == 1){
                $return[$k]['sex'] = '男';
            }elseif($v['sex'] == 2){
                $return[$k]['sex'] = '女';
            }else{
                $return[$k]['sex'] = '未知';                
            }
            $return[$k]['last_time'] = $v['last_time'] ? date('Y-m',$v['last_time']) : '';
            // 年限
            if($v['work_time']){
                $work = date('Y',$v['work_time']);
                $time = date('Y',time());
                $work_time = $time - $work + 1;
                if($work){
                    if($work_time==1){
                        $work_time = $work_time.'年以内';
                    }else{
                        $work_time = $work_time.'年';
                    }
                }else{
                    $work_time = '无工作经验';
                }
            }else{
                $work_time = '无工作经验';
            }
            $return[$k]['work_time'] = $work_time;
            if($v['user_logo']){
                $return[$k]['user_logo'] = replace_file_domain($v['user_logo']);
            }else{
                $return[$k]['user_logo'] = cfg('site_url').'/static/avatar.jpg';
            }
            // 年龄
            $age = date('Y',time()) - date('Y',$v['birthday']);
            $return[$k]['age'] = $age.'岁';
        }
        return $return;
    }

    // 学历
    public function education($id)
    {
        $data = array(
            '0' => '',
            '1' => '初中及以下',
            '2' => '中专/中技',
            '3' => '高中',
            '4' => '大专',
            '5' => '本科',
            '6' => '硕士',
            '7' => '博士',
        );
        return $data[$id];
    }

    // 日期
    public function month($id)
    {
        $data = array(
            '1' => '二个月内活跃',
            '2' => '三个月内活跃',
            '3' => '四个月内活跃',
            '4' => '五个月内活跃',
            '5' => '六个月内活跃',
            '6' => '七个月内活跃',
            '7' => '八个月内活跃',
            '8' => '九个月内活跃',
            '9' => '十个月内活跃',
            '10' => '十一个月内活跃',
            '11' => '一年内活跃',
        );
        return $data[$id];
    }

    // 日期
    public function day($id)
    {
        $data = array(
            '1' => '二天内活跃',
            '2' => '三天内活跃',
            '3' => '四天内活跃',
            '4' => '五天内活跃',
            '5' => '六天内活跃',
            '6' => '一周内活跃',
        );
        return $data[$id];
    }

    // 时间
    public function time($id)
    {
        $data = array(
            '1' => '一小时内活跃',
            '2' => '二小时内活跃',
            '3' => '三小时内活跃',
            '4' => '四小时内活跃',
            '5' => '五小时内活跃',
            '6' => '六小时内活跃',
        );
        return $data[$id];
    }

    /**
     * 计算两个时间戳之差
     * @param $begin_time
     * @param $end_time
     * @return array
     */
    public function calcTime($fromTime, $toTime){
        //计算时间差
        $newTime = $toTime - $fromTime;
        $data = array(
            round($newTime % (86400*30*12) / (86400*30)),
            round($newTime % (86400*30) / 86400),
            round($newTime % 86400 / 3600),
            round($newTime % 86400 % 3600 / 60),
        );
        return $data;
    }

    /**
     * 计算年限
     * @return array
     */
    public function years($number){
        $year = date('Y',time()) - $number;
        return strtotime($year.'-01-01 00:00:00');
    }

    /**
     * 更新简历最后修改时间
     * @date: 2021/07/15
     */
    public function updateRecruitResumeTime($uid)
    {
        if ($uid < 0) {
            return false;
        }
        $this->resumeMod->where('uid', $uid)->where('is_del', 0)->update(['update_time' => time()]);
        return true;
    }

    /**
     * 获取简历id
     */
    public function recruiTresumeId($where){
        $return = (new NewRecruitResume())->where($where)->find()->toArray();
        return $return;
    }
}