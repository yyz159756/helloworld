<?php /** @noinspection ALL */

namespace app\admin\model;

use think\Model;

/**
 * Class Admin
 * @package app\admin\model
 * 学院查看model
 */
class Institute extends Model
{


    /**
     * 获取单个学院的学时信息
     * 返回data：单位（学院），发布讲座数，出席人数（总出席人数），
     * 学时数（总学时数，即全部讲座所出席人员所获得的学时数总和）；
     * @param $institute 单位名称
     * @param $term 学年（2018-2019 则填20182019， 2018-2019第一学期 填201820191）
     */
    public function InstituteInfo($institute, $term)
    {
        //如果term填写了20182019 那么长度就是8，要去获取2018-2019第一和第二学期两个学期的会议
        if(strlen($term) == 8){
            //去meeting_pending_assess表中查看该单位开了哪些会议 获取它们的meeting_id
            $meeting_id = db('meeting_pending_assess')
                ->where('department','=',$institute)
                ->where('status','=',1)
                ->where('term','like',"$term%")
                ->field('id')
                ->select();
        }
        //举个例子：如果term=201820191 那么长度为9，where直接：term = 201820191 去meeting_pending_assess表中select
        else if(strlen($term) == 9){
            //去meeting_pending_assess表中查看该单位开了哪些会议 获取它们的meeting_id
            $meeting_id = db('meeting_pending_assess')
                ->where('department','=',$institute)
                ->where('status','=',1)
                ->where('term','=',$term)
                ->field('id')
                ->select();
        }
        if(!$meeting_id){
            $data['institute'] = $institute;
            $data['meeting_number'] = 0;//讲座数为0
            $data['attend'] = 0;//出席数为0
            $data['score'] = 0;//总学时数为0
            return $data;
        }
        else
        {
            $meeting_number = 0;
            $attend = 0;
            $score = 0;
            //遍历处理每个meeting_id
            foreach ($meeting_id as $k => $v)
            {
                $meeting_id = $v['id'];
                //会议数+1
                $meeting_number = $meeting_number + 1;
                //去meeting_member表中统计学时和出席数
                $res = db('meeting_member')
                    ->where('meeting_id','=',$meeting_id)
                    ->select();
                if($res){
                    foreach ($res as $p => $q)
                    {
                        //统计出席数
                        if($q['checkin'] == 1 && $q['checkout'] == 1)
                        {
                            //出席数+1
                            $attend = $attend + 1;
                        }
                        //统计总学时，有扣分的不算 只算正分
                        if($q['score'] > 0){
                            $score = $score + $q['score'];
                        }

                    }//foreach
                }//if
            }//foreach
            $data['institute'] = $institute;//单位名称
            $data['meeting_number'] = $meeting_number;//讲座数
            $data['attend'] = $attend;//出席数
            $data['score'] = $score;//总学时数
            return $data;
        }//else
    }
    /**
     * 学院查看 获取所有学院的学时信息
     * 表格包含信息：单位（学院），发布讲座数，出席人数（总出席人数），
     * 学时数（总学时数，即全部讲座所出席人员所获得的学时数总和）；
     * 可按学年查看（包含2018-2019学年、2019-2020学年）
     * @param $post
     * @param term 学年 （2018-2019 则填20182019）
     */
    public function getAllInstituteInfo($post)
    {
        /**
         * @param array $AllInstitute 所有学院的数组
        */
        $AllInstitute = config('setting.ALL_INSTITUTE');
        $term = $post['data']['term'];
        //获取所有学院的信息
        foreach ($AllInstitute as $k => $v) {
            $data[$k] = Institute::InstituteInfo($v, $term);
        }
        return $data;
    }
}
