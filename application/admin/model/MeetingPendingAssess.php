<?php

namespace app\admin\model;

use think\Cache;
use think\Exception;
use think\Model;
use think\Db;
use app\admin\exception\BaseException;
use app\admin\model\Token;
use app\admin\model\SavePhoto;
use think\Paginator;

class MeetingPendingAssess extends Model
{
    protected $name = 'meeting_pending_assess';

    public function extraInfo()
    {
        return $this->hasOne('meeting', 'id', 'id');

    }

    public function majorInfo()
    {
        return $this->hasMany('meeting_major', 'meeting_id', 'id')
            ->group('major');

    }

    /**
     * 会议申请
     * 将数据插入到meeting_pending_assess（会议待审核表）
     * 上线测试的时候记得改SERVER_IP
     * @param $post
     * @return meeting_id 返回结果是meeting_id
     * @throws BaseException
     */
    public function applyMeeting($post)
    {
        $data = $post['data'];
        $token = $post['token'];
        $photoModel = new SavePhoto();
        //获取发布者的id
        $value = (new Token())->getContent($token);
        $admin_id = $value['id'];

//        // TODO 上线时要更改ip
//        //如果photo没有传就用默认的图片
//        if (!$_FILES) {
//            $data['photo'] = config('setting.SERVER_IP') . '/ppms2.9/public/static/img/default_photo.png';
//        } else {
//            //存入服务器并获取文件名
//            $filename = $photoModel->save_();
//            //处理url
//            $data['photo'] = config('setting.SERVER_IP') . '/ppms2.9' . substr($filename, 2);
//        }

        $data['photo'] = config('setting.SERVER_IP') . '/ppms2.9/public/static/img/default_photo.png';
        Db::startTrans();
        try {
            //插入meeting_pending_assess表中
            $res = $this->insertGetId([
                'name' => $data['name'],
                'position' => $data['position'],
                'date' => $data['date'],
                'begin_time' => $data['begin_time'],
                'end_time' => $data['end_time'],
                'term' => $data['term'],
                'signup_start_time' => $data['signup_start_time'],
                'signup_end_time' => $data['signup_end_time'],
                'type' => $data['type'],
                'department' => $data['department'],
                'score' => $data['score'],
                'description' => $data['description'],
                'capacity' => $data['capacity'],
                'admin_id' => $admin_id,
                'photo' => $data['photo'],
                'is_top' => $data['is_top'],
            ]);

            if (!$res) {
                throw new BaseException(['msg' => '数据插入服务器失败']);
            }
            $meeing_id = $res;
            //将major插入meeting_major表中
            foreach ($data['major'] as $k => $v) {
                foreach ($v['year'] as $p => $q) {
                    $res = (new MeetingMajor())->insert([
                        'meeting_id' => $meeing_id,
                        'major' => $v['major'],
                        'year' => $q,
                    ]);
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            echo $e;
            throw new BaseException(['code' => 500, 'msg' => '服务器内部错误！' . $e]);
        }

        return $meeing_id;
    }

    /**
     * 二级权限查找自己所发布过的会议
     * @param $post
     */
    public function findMeeting($post)
    {
        $token = $post['token'];
        //获取admin_id
        $value = (new Token())->getContent($token);
        $admin_id = $value['id'];

        $res = self::where('admin_id', '=', $admin_id)
            ->select();


        if (!$res) {
            throw new BaseException(['code' => 411, 'msg' => '无结果！']);
        }
        //有数据就装会议返回json
        $count = 0;//count $data[]计数器
        $return_data = [];
        foreach ($res as $k => $v) {

            $meeting_id = $v['id'];
            //dump($meeting_id);
            $res = db('meeting') -> where('id', '=', $meeting_id)
                ->select();
            //转成数组
            $res = collection($res)->toarray();
            //dump($res);

            if (!$res) {
                continue;
            }

            //会议信息存入main_info
            $data['main_info'] = $res[0];

            //获取会议的学院
            $res_major = db('meeting_major')
                ->where('meeting_id', '=', $meeting_id)
                ->select();

            //获取参加人数
            $res_member_num = db('meeting_member')
                ->where('meeting_id', '=', $meeting_id)
                ->where('ask_leave', '=', 0)
                ->count();

            //添加已报名人数
            $data['signup_num'] = $res_member_num;


            if (!$res_major) {
                $data['major_info'] = null;
            } else {
                $temp_major = '';
                //major数组ptr
                $j = 0;
                foreach ($res_major as $p => $q) {
                    $temp_major2 = $q['major'];
                    if ($temp_major != $temp_major2) {
                        if (!isset($res['major_info'][$j]['major'])) {
                            $data['major_info'][$j] = ['major' => '', 'year' => []];
                        }
                        $data['major_info'][$j]['major'] = $q['major'];
                        array_push($data['major_info'][$j]['year'], $q['year']);
                        $j = $j + 1;
                        $temp_major = $q['major'];
                    } else {
                        array_push($data['major_info'][$j - 1]['year'], $q['year']);
                        $temp_major = $q['major'];
                    }
                }//foreach
            }//else

            $return_data[$count] = $data;

            $count = $count + 1;
        }//foreach
        //dump($count);
        if($count == 0){
            throw new BaseException(['code' => 401, 'msg' => '查无会议']);
        }
        return $return_data;
        return $res;
    }

    /**
     * 二级权限申请置顶
     * @param $post
     */
    public function applyTop($post)
    {
        self::where('id', '=', $post['data']['meeting_id'])
            ->update([
                'is_top' => 1,
            ]);
    }

    /**
     * 会议通过审核
     * 修改审核表中的状态（status改为 1）并将该会议信息存入meeting表中
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function approve($meeting_id)
    {
        //修改审核表中的状态 status改为1
        $res = self::where('id', '=', $meeting_id)
            ->update([
                'status' => 1
            ]);
        if (!$res) {
            //确认信息是否插入meeting表中, 防止未成功插入meeting却修改了状态, 并防止多次插入meeting表
            $is_insert = db('meeting')->where('id', '=', $meeting_id)->value('id');
            if ($is_insert) {
                throw new BaseException(['code' => 411, 'msg' => '该会议已审核通过！']);
            }
        }

        $meeting = self::where('id', '=', $meeting_id)->find()->toArray();
        $meeting_insert = [
            'id' => $meeting_id,
            'name' => $meeting['name'],
            'position' => $meeting['position'],
            'date' => $meeting['date'],
            'begin_time' => $meeting['begin_time'],
            'end_time' => $meeting['end_time'],
            'signup_start_time' => $meeting['signup_start_time'],
            'signup_end_time' => $meeting['signup_end_time'],
            'score' => $meeting['score'],
            'term' => $meeting['term'],
            'type' => $meeting['type'],
            'department' => $meeting['department'],
            'description' => $meeting['description'],
            'photo' => $meeting['photo'],
            'capacity' => $meeting['capacity'],
            'signed' => 0,
            'admin_id' => $meeting['admin_id'],
            'is_top' => $meeting['is_top'],
            'status' => 0
        ];

        $res = db('meeting')->insert($meeting_insert);
        if (!$res) {
            throw new BaseException(['msg' => '插入失败']);
        }

    }

    public function approve_now($meeting_id)
    {
        $this->approve($meeting_id);
        $url = Cache::get("pic_" . $meeting_id);
        var_dump($url);
        $res = db('meeting')->where(['id' => $meeting_id])->update(['photo' => $url]);
        if (!$res) {
            throw new BaseException(['code' => 400, 'msg' => '图片修改错误']);
        }
    }

    /**
     * 不批准会议
     * 修改审核表中的状态（status改为 -1）
     * @param $post
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function disapprove($post)
    {
        $data = $post['data'];
        $token = $post['token'];
        $meeting_id = $post['data']['meeting_id'];
        //验证器
        $val = (new \app\admin\validate\Admin())->scene('content')
            ->check(['content' => $post['data']['reason']]);
        if (!$val) {
            throw new BaseException(['code' => 401, 'msg' => '内容含有非法字符']);
        }
        //修改审核表中的状态 status改为1 并 修改理由
        $res = self::where('id', '=', $meeting_id)
            ->update([
                'status' => -1,
                'reason' => $post['data']['reason']
            ]);

    }

    /**
     * 搜索会议，获取会议信息
     * @param $post
     * @param meeting_id
     * @param meeting_name
     */
    public function getInfo($post)
    {
        $data = $post['data'];
        if ($data['meeting_id'] != '' && $data['meeting_name'] == '') {   //根据meeting_id进行查找 只可能找出一条
            $res = self::with('extraInfo')
                ->where('id', '=', $data['meeting_id'])
                ->select();
            if ($res) {
                $res = collection($res)->toArray();
            }


            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '未查找到该会议']);
            }

            foreach ($res as $k => $v) {

                $meeting_id = $v['id'];
                $res_major = db('meeting_major')
                    ->where('meeting_id', '=', $meeting_id)
                    ->select();
                $res_member_num = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('ask_leave', '=', 0)
                    ->count();
                //添加已报名人数
                $res[$k]['signup_num'] = $res_member_num;
                if (!$res_major) {
                    $res[$k]['major_info'] = null;
                } else {
                    $temp_major = '';
                    //major数组ptr
                    $j = 0;
                    //major['year']数组ptr
                    $l = 0;
                    foreach ($res_major as $p => $q) {
                        $temp_major2 = $q['major'];
                        if ($temp_major != $temp_major2) {
                            if (!isset($res[$k]['major_info'][$j]['major'])) {
                                $res[$k]['major_info'][$j] = ['major' => '', 'year' => []];
                            }
                            $res[$k]['major_info'][$j]['major'] = $q['major'];
                            array_push($res[$k]['major_info'][$j]['year'], $q['year']);
                            $j = $j + 1;
                            $temp_major = $q['major'];
                        } else {
                            array_push($res[$k]['major_info'][$j - 1]['year'], $q['year']);
                            $temp_major = $q['major'];
                        }
                    }//foreach
                }//else
            }//foreach
        }//if
        else if ($data['meeting_id'] == '' && $data['meeting_name'] != '') {
            //根据meeting_name进行查找 用模糊查找
            $res = self::with('extraInfo')
                ->where('name', 'like', "%$data[meeting_name]%")
                ->select();
            if ($res) {
                $res = collection($res)->toArray();
            }
            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '未查找到会议']);
            }
            foreach ($res as $k => $v) {

                $meeting_id = $v['id'];
                $res_major = db('meeting_major')
                    ->where('meeting_id', '=', $meeting_id)
                    ->select();
                $res_member_num = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('ask_leave', '=', 0)
                    ->count();
                //添加已报名人数
                $res[$k]['signup_num'] = $res_member_num;
                if (!$res_major) {
                    $res[$k]['major_info'] = null;
                } else {
                    $temp_major = '';
                    //major数组ptr
                    $j = 0;
                    //major['year']数组ptr
                    $l = 0;
                    foreach ($res_major as $p => $q) {
                        $temp_major2 = $q['major'];
                        if ($temp_major != $temp_major2) {
                            if (!isset($res[$k]['major_info'][$j]['major'])) {
                                $res[$k]['major_info'][$j] = ['major' => '', 'year' => []];
                            }
                            $res[$k]['major_info'][$j]['major'] = $q['major'];
                            array_push($res[$k]['major_info'][$j]['year'], $q['year']);
                            $j = $j + 1;
                            $temp_major = $q['major'];
                        } else {
                            array_push($res[$k]['major_info'][$j - 1]['year'], $q['year']);
                            $temp_major = $q['major'];
                        }

                    }//foreach
                }//else

            }//foreach

        }//else if
        else if ($data['term'] != '' && $data['department'] == '') {
            //根据term进行查找
            $res = self::with('extraInfo')
                ->where('term', '=', $data['term'])
                ->select();
            if ($res) {
                $res = collection($res)->toArray();
            }
            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '未查找到会议']);
            }
            foreach ($res as $k => $v) {

                $meeting_id = $v['id'];


                //major处理
                $res_major = db('meeting_major')
                    ->where('meeting_id', '=', $meeting_id)
                    ->select();
                $res_member_num = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('ask_leave', '=', 0)
                    ->count();
                //添加已报名人数
                $res[$k]['signup_num'] = $res_member_num;

                if (!$res_major) {
                    $res[$k]['major_info'] = null;
                } else {
                    $temp_major = '';
                    //major数组ptr
                    $j = 0;
                    //major['year']数组ptr
                    $l = 0;
                    foreach ($res_major as $p => $q) {
                        $temp_major2 = $q['major'];
                        if ($temp_major != $temp_major2) {
                            if (!isset($res[$k]['major_info'][$j]['major'])) {
                                $res[$k]['major_info'][$j] = ['major' => '', 'year' => []];
                            }
                            $res[$k]['major_info'][$j]['major'] = $q['major'];
                            array_push($res[$k]['major_info'][$j]['year'], $q['year']);
                            $j = $j + 1;
                            $temp_major = $q['major'];
                        } else {
                            array_push($res[$k]['major_info'][$j - 1]['year'], $q['year']);
                            $temp_major = $q['major'];
                        }

                    }//foreach
                }//else

            }//foreach

        }//else if
        else if ($data['term'] == '' && $data['department'] != '') {
            //根据department进行查找
            $res = self::with('extraInfo')
                ->where('department', '=', $data['department'])
                ->select();
            if ($res) {
                $res = collection($res)->toArray();
            }
            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '未查找到会议']);
            }
            foreach ($res as $k => $v) {

                $meeting_id = $v['id'];
                $res_major = db('meeting_major')
                    ->where('meeting_id', '=', $meeting_id)
                    ->select();

                $res_member_num = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('ask_leave', '=', 0)
                    ->count();
                //添加已报名人数
                $res[$k]['signup_num'] = $res_member_num;

                if (!$res_major) {
                    $res[$k]['major_info'] = null;
                } else {
                    $temp_major = '';
                    //major数组ptr
                    $j = 0;
                    //major['year']数组ptr
                    $l = 0;
                    foreach ($res_major as $p => $q) {
                        $temp_major2 = $q['major'];
                        if ($temp_major != $temp_major2) {
                            if (!isset($res[$k]['major_info'][$j]['major'])) {
                                $res[$k]['major_info'][$j] = ['major' => '', 'year' => []];
                            }
                            $res[$k]['major_info'][$j]['major'] = $q['major'];
                            array_push($res[$k]['major_info'][$j]['year'], $q['year']);
                            $j = $j + 1;
                            $temp_major = $q['major'];
                        } else {
                            array_push($res[$k]['major_info'][$j - 1]['year'], $q['year']);
                            $temp_major = $q['major'];
                        }

                    }//foreach
                }//else

            }//foreach

        }//else if
        else if ($data['term'] != '' && $data['department'] != '') {
            //根据department和term进行查找
            $res = self::with('extraInfo')
                ->where('department', '=', $data['department'])
                ->where('term', '=', $data['term'])
                ->select();
            if ($res) {
                $res = collection($res)->toArray();
            }
            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '未查找到会议']);
            }

            foreach ($res as $k => $v) {

                $meeting_id = $v['id'];
                $res_major = db('meeting_major')
                    ->where('meeting_id', '=', $meeting_id)
                    ->select();

                $res_member_num = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('ask_leave', '=', 0)
                    ->count();
                //添加已报名人数
                $res[$k]['signup_num'] = $res_member_num;

                if (!$res_major) {
                    $res[$k]['major_info'] = null;
                } else {
                    $temp_major = '';
                    //major数组ptr
                    $j = 0;
                    //major['year']数组ptr
                    $l = 0;
                    foreach ($res_major as $p => $q) {
                        $temp_major2 = $q['major'];
                        if ($temp_major != $temp_major2) {
                            if (!isset($res[$k]['major_info'][$j]['major'])) {
                                $res[$k]['major_info'][$j] = ['major' => '', 'year' => []];
                            }
                            $res[$k]['major_info'][$j]['major'] = $q['major'];
                            array_push($res[$k]['major_info'][$j]['year'], $q['year']);
                            $j = $j + 1;
                            $temp_major = $q['major'];
                        } else {
                            array_push($res[$k]['major_info'][$j - 1]['year'], $q['year']);
                            $temp_major = $q['major'];
                        }

                    }//foreach
                }//else

            }//foreach
        }//else if

        //全部填''，返回所有信息
        else if ($data['term'] == '' && $data['department'] == '') {
            $res = self::with('extraInfo')
                ->select();
            if ($res) {
                $res = collection($res)->toArray();
            }

            if (!$res) {
                throw new BaseException(['code' => 401, 'msg' => '未查找到会议']);
            }

            foreach ($res as $k => $v) {

                $meeting_id = $v['id'];

                $res_member_num = db('meeting_member')
                    ->where('meeting_id', '=', $meeting_id)
                    ->where('ask_leave', '=', 0)
                    ->count();
                //添加已报名人数
                $res[$k]['signup_num'] = $res_member_num;

                $res_major = db('meeting_major')
                    ->where('meeting_id', '=', $meeting_id)
                    ->select();

                if (!$res_major) {
                    $res[$k]['major_info'] = null;
                } else {
                    $temp_major = '*';
                    //major数组ptr
                    $j = 0;
                    //major['year']数组ptr
                    $l = 0;
                    foreach ($res_major as $p => $q) {
                        $temp_major2 = $q['major'];
                        if ($temp_major != $temp_major2) {
                            if (!isset($res[$k]['major_info'][$j]['major'])) {
                                $res[$k]['major_info'][$j] = ['major' => '', 'year' => []];
                            }
                            $res[$k]['major_info'][$j]['major'] = $q['major'];
                            array_push($res[$k]['major_info'][$j]['year'], $q['year']);
                            $j = $j + 1;
                            $temp_major = $q['major'];
                        } else {
                            array_push($res[$k]['major_info'][$j - 1]['year'], $q['year']);
                            $temp_major = $q['major'];
                        }

                    }//foreach
                }//else

            }//foreach
        }//else if

        return $res;
    }

    /**
     * 会议信息修改
     * @param $post
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function MeetingUpdate($post)
    {
        $data = $post['data'];
        $meeting_id = $post['data']['meeting_id'];
        //修改会议名称
        if ($data['name'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'name' => $data['name'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议地点
        if ($data['position'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'position' => $data['position'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议活动日期
        if ($data['date'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'date' => $data['date'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议开始时间
        if ($data['begin_time'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'begin_time' => $data['begin_time'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议结束时间
        if ($data['end_time'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'end_time' => $data['end_time'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议所属学期
        if ($data['term'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'term' => $data['term'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议开始报名时间
        if ($data['signup_start_time'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'signup_start_time' => $data['signup_start_time'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议结束报名时间
        if ($data['signup_end_time'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'signup_end_time' => $data['signup_end_time'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改讲座类型
        if ($data['type'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'type' => $data['type'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改开会部门
        if ($data['department'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'department' => $data['department'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议学时
        if ($data['score'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'score' => $data['score'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        //修改会议描述（简介）
        if ($data['description'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'description' => $data['description'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }
        // TODO 此处修改图片有问题 暂时不能用
//        //修改会议图片
//        if ($_FILES) {
//            //存入服务器并获取文件名
//            $filename = (new SavePhoto())->save_();
//            //处理图片url
//            $url = config('setting.SERVER_IP') . '/ppms3.0' . substr($filename, 2);
//            //修改会议图片url
//            $res = self::where('id', '=', $meeting_id)
//                ->update([
//                    'photo' => $url,
//                ]);
//            if (!$res) {
//                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
//            }
//        }
        //修改会议可报名人数
        if ($data['capacity'] != '') {
            $res = self::where('id', '=', $meeting_id)
                ->update([
                    'capacity' => $data['capacity'],
                ]);
            if (!$res) {
                throw new BaseException(['code' => 411, 'msg' => '修改失败']);
            }
        }

    }

    public function updatePhoto($post)
    {
        $meeting_id = $post['meeting_id'];
        DB::startTrans();
        try {
            $file = request()->file('image');
            if ($file) {
                $info = $file->validate(['size' => 10240000, 'ext' => 'jpg,png,gif'])->move(ROOT_PATH . 'public' . DS . 'img');
                if ($info) {
                    $filename = $info->getSaveName();
                } else {
                    throw new BaseException(['code' => 401, 'msg' => $info->getError()]);
                }
                $res = self::where('id', '=', $meeting_id)
                    ->update([
                        'photo' => ROOT_PATH . 'public' . DS . 'img' . DS . $filename
                    ]);
                if (!$res) {
                    throw new BaseException(['code' => 402, 'msg' => '写入失败']);
                }
                db::commit();
            }
        } catch (Exception $e) {
            db::rollback();
            throw new BaseException(['code' => 401, 'msg' => '上传失败']);
        }
//        try {
//            //修改会议图片
//            if ($_FILES) {
////                //存入服务器并获取文件名
////                $filename = (new SavePhoto())->save_();
//                //处理图片url
//                $url = config('setting.SERVER_IP') . '/ppms2.9' . substr($filename, 2);
//                var_dump($url);
//                //修改会议图片url
//                $res = self::where('id', '=', $meeting_id)
//                    ->update([
//                        'photo' => $url,
//                    ]);
//                if (!$res) {
//                    throw new BaseException(['code' => 411, 'msg' => '上传失败']);
//                }
//                db::commit();
//            }
//        } catch (\Exception $e) {
//            db::rollback();
//            echo $e;
//            throw new BaseException(['code' => 411, 'msg' => '上传失败']);
//        }
    }

    /**
     * 一级权限设置别人二级权限，此二级权限的人会能查到一些额外其他学院的会议
     * @param $post
     */
    public function extraMeetingInfo($post)
    {
        $token = $post['token'];
        $var = (new Token())->getContent($token);
        $admin_id = $var['id'];
        $level = $var['level'];
        $admin_major = $var['major'];

        //去meeting_admin中找会议id meeting_admin是一级添加的管理员表
        $res_meeting_admin = db('meeting_admin')
            ->where('admin_id', '=', $admin_id)
            ->select();

        //这个admin没有被添加到其他会议管理中 直接返回无
        if (!$res_meeting_admin) {
            throw new BaseException(['code' => 210, 'msg' => '查无结果']);
        }

        //有数据就装会议返回json
        $count = 0;//count $data[]计数器
        $return_data = [];
        foreach ($res_meeting_admin as $k => $v) {

            $meeting_id = $v['meeting_id'];
            //dump($meeting_id);
            $res = db('meeting') -> where('id', '=', $meeting_id)
                ->select();
            //转成数组
            $res = collection($res)->toarray();
            //dump($res);



            if (!$res) {
                continue;
            }

            //会议信息存入main_info
            $data['main_info'] = $res[0];

            //获取会议的学院
            $res_major = db('meeting_major')
                ->where('meeting_id', '=', $meeting_id)
                ->select();

            //获取参加人数
            $res_member_num = db('meeting_member')
                ->where('meeting_id', '=', $meeting_id)
                ->where('ask_leave', '=', 0)
                ->count();

            //添加已报名人数
            $data['signup_num'] = $res_member_num;


            if (!$res_major) {
                $data['major_info'] = null;
            } else {
                $temp_major = '';
                //major数组ptr
                $j = 0;
                foreach ($res_major as $p => $q) {
                    $temp_major2 = $q['major'];
                    if ($temp_major != $temp_major2) {
                        if (!isset($res['major_info'][$j]['major'])) {
                            $data['major_info'][$j] = ['major' => '', 'year' => []];
                        }
                        $data['major_info'][$j]['major'] = $q['major'];
                        array_push($data['major_info'][$j]['year'], $q['year']);
                        $j = $j + 1;
                        $temp_major = $q['major'];
                    } else {
                        array_push($data['major_info'][$j - 1]['year'], $q['year']);
                        $temp_major = $q['major'];
                    }
                }//foreach
            }//else

            $return_data[$count] = $data;

            $count = $count + 1;
        }//foreach
        //dump($count);
        if($count == 0){
            throw new BaseException(['code' => 401, 'msg' => '查无会议']);
        }
        return $return_data;
    }//function

    public function getMeetingInfo($meeting_id)
    {
        return self::get(['id' => $meeting_id]);
    }

    public function distop_pending_meeting($post)
    {

        $res = self::where(['id' => $post['data']['meeting_id']])->select();
        $res = collection($res)->toArray();
        if(!$res){
            throw new BaseException(['code' => 410, 'msg' => '找不到该会议']);
        }
        if($res[0]['is_top'] == '0'){
            throw new BaseException(['code' => 411, 'msg' => '已经是不置顶了']);
        }


        $res = self::where('id', '=', $post['data']['meeting_id'])->update(['is_top' => 0]);
        if(!$res){
            throw new BaseException(['code' => 501, 'msg' => '服务器错误']);
        }

    }


}
