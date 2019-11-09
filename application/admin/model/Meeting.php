<?php /** @noinspection ALL */

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Cache;
use think\Exception;
use think\Model;
use app\admin\model\Token;
use app\admin\model\SavePhoto;
use think\Db;

class Meeting extends Model
{
    protected $name = 'meeting';


    public function insert(array $data = [], $replace = false, $getLastInsID = false, $sequence = null)
    {
        return parent::insert($data, $replace, $getLastInsID, $sequence); // TODO: Change the autogenerated stub
    }


    /**
     * 开始会议
     * 把meeting表里的status改成1
     * @param $post
     * @param meeting_id
     */
    public function Start($post)
    {
        //开始会议 把meeting表里的status改成1就好
        $meeting_id = $post['data']['meeting_id'];
        //校验是否已经开始
        $is_start = Db::table('meeting')->where(['id' => $meeting_id, 'status' => 1])->select();

        if ($is_start) {
            throw new BaseException(['code' => 401, 'msg' => '会议已经开始！']);
        }
        $members = Db::table('meeting_member')
            ->where(['meeting_id' => $post['data']['meeting_id']])
            ->select();
        if (!$members) {
            throw new BaseException(['code' => 401, 'msg' => '还没有人报名']);
        }
        $res = $this->where(['id' => $meeting_id])
            ->update(['status' => 1]);
        if (!$res) {
            throw new BaseException(['code' => 401, 'msg' => '操作错误']);
        }
//        if (!$res) {
//            //确认信息成功update
//            $is_update = db('meeting_pending_assess')
//                ->where('id', '=', $meeting_id)
//                ->where('status', '=', 1)
//                ->value('id');
//            if ($is_update) {
//                throw new BaseException(['code' => 411, 'msg' => '该会议已开始，请不要重复操作。']);
//            }
//        }
    }

    public function cache_meeting($post)
    {
        //将会议详情放入缓存，参会学生信息放入缓存
//        $meeting = self::where('id', '=', $post['data']['meeting_id'])
//            ->find();
//        //指定缓存方式为redis
//        Cache::store('redis')->tag('meeting')->set($meeting['id'], $meeting);

        //缓存参会学生信息
        $members = Db::table('meeting_member')
            ->where(['meeting_id' => $post['data']['meeting_id']])
            ->select();
        $uids = []; //uid数组
        foreach ($members as $m) {
            //打了标签
            //标签为meeting_member_(MEETING_ID)
            // TODO 标签新增字段头
            //缓存中学生ID为meeting(MEETING_ID)_(uid)
            Cache::store('redis')->tag('meeting_member_' . $post['data']['meeting_id'])->set('meeting'.$post['data']['meeting_id'].'_'.$m['uid'], $m);
            array_push($uids, $m['uid']);
        }
        Cache::store('redis')->set('meeting_' . $post['data']['meeting_id'] . '_uids', $uids);
        return 0;
    }

    /**
     * 结束会议
     * 把meeting表里的status改成 -1
     * @param $post
     * @param meeting_id
     * @throws BaseException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function End($post)
    {
        //结束会议 把meeting表里的status改成-1就好
        $meeting_id = $post['data']['meeting_id'];
        //校验是否已经结束
        $is_end = Db::table('meeting')->where(['id' => $meeting_id, 'status' => -1])->select();

        if ($is_end) {
            throw new BaseException(['code' => 401, 'msg' => '会议已经结束！']);
        }
        $res = $this->where(['id' => $meeting_id])
            ->update(['status' => -1]);
        if (!$res) {
            throw new BaseException(['code' => 401, 'msg' => '操作错误']);
        }
    }

    public function dump_meeting_cache($post)
    {
        //先从缓存中解出uid数组
//        Cache::connect(['type' => 'Redis']);
        try{
            $uids = Cache::store('redis')->get('meeting_' . $post['data']['meeting_id'] . '_uids');
//        var_dump($uids);
            //将数据从redis读出存进数据库
            foreach ($uids as $u) {
                $m = Cache::tag('meeting_member_' . $post['data']['meeting_id'])->get('meeting'.$meeting_id.'_'.$u);
                Db::table('meeting_member')
                    ->where(['uid' => $m['uid']])
                    ->update(['checkin' => $m['checkin'], 'checkout' => $m['checkout']]);
            }
        } catch (Exception $e) {
            throw new BaseException(['code' => 401, 'msg' => '缓存错误']);
        }
        //清掉缓存
        Cache::clear('meeting_member_' . $post['data']['meeting_id']);
    }


    /**
     * 活动置顶
     * 将meeting表中的top置为 1
     * @param $post
     * @param meeting_id
     */
    public function Top($post)
    {
        $meeting_id = $post['data']['meeting_id'];
        $res = self::where('id', '=', $meeting_id)
            ->update([
                'is_top' => '1'
            ]);
        if (!$res) {
            //确认是否真实的修改了
            $value = db('meeting')
                ->where('id', '=', $meeting_id)
                ->where('is_top', '=', 1)
                ->value('id');
            if ($value) {
                throw new BaseException(['code' => 411, 'msg' => '已置顶，请不要重复操作！']);
            }
        }
    }

    /**
     * 取消活动置顶
     * 将meeting表中的top置为 0
     * @param $post
     */
    public function disTop($post)
    {
        $meeting_id = $post['data']['meeting_id'];
        $res = self::where('id', '=', $meeting_id)
            ->update([
                'is_top' => '0',
            ]);

    }


    /**
     * 生成签到二维码 会有一个key做code_id cache存的值是 1 '1'表示签到
     * @param $post
     * @param token
     * @param meeting_id
     *
     */
    public function createCode($post)
    {
        $data = $post['data'];
        //生成key作code_id
        $key = get_key(); //获取随机字符串已经写成公共函数

        vendor('phpqrcode.phpqrcode'); //引入phpqrcode

        $url = json_encode([
            'meeting_id' => $data['meeting_id'],
            'code_id' => $key
        ]);

        //存起来 60秒有效期
        //value 1为签到 2为签退
        // TODO 上线时把有效期改回60s
        cache($key, 1, 60 * 60);
        //容错级别
        $errorCorrectionLevel = 'L';
        //生成图片大小
        $matrixPointSize = 6;
        $new_image = ROOT_PATH . 'public/static/' . $data['meeting_id'] . '_' . time() .'.png';
        //生成二维码图片
        \QRcode::png($url, $new_image, $errorCorrectionLevel, $matrixPointSize, 2);
        //输出图片
        header("Content-type: image/png");
        return json([
            'code' => 200,
            // TODO 上线时修改SERVER_IP
            'msg' => config('setting.SERVER_IP'). DS . 'ppms2.9/public/static/' . $data['meeting_id'] . '_' .time() . '.png'
        ]);
    }

    /***
     * 生成签退二维码 cache存的值是2表示签退
     * @param $post
     * @param token
     * @param meeting_id
     * @return code和msg的json数据 msg是二维码的url
     */
    public function createSignOutCode($post)
    {
        $data = $post['data'];
        //生成key作code_id
        $key = get_key();

        vendor('phpqrcode.phpqrcode');

        $url = json_encode([
            'meeting_id' => $data['meeting_id'],
            'code_id' => $key
        ]);

        //存起来 60秒有效期
        cache($key, 2, 60);
        //容错级别
        $errorCorrectionLevel = 'L';
        //生成图片大小
        $matrixPointSize = 6;
        $new_image = ROOT_PATH . 'public/static/' . $data['meeting_id'] .'.png';
        //生成二维码图片
        \QRcode::png($url, $new_image, $errorCorrectionLevel, $matrixPointSize, 2);
        //输出图片
        header("Content-type: image/png");
        return json([
            'code' => 200,
            // TODO 上线时修改SERVER_IP
            'msg' => config('setting.SERVER_IP'). DS . 'ppms2.9/public/static/' . $data['meeting_id'] . '.png'
        ]);
    }
    /***
     * 返回会议的管理员工号
     * @param $post
     * @param token
     * @param meeting_id
     * @return msg 会议的管理员
     */
    public function getMeetingManager($post)
    {
        $data = $post['data'];
        $meeting_id = $data['meeting_id'];

        $res = db('meeting_admin') -> where('meeting_id','=',$meeting_id)
            ->select();
        $res = collection($res) -> toArray();

        if(!$res){
            throw new BaseException(['code' => 410, 'msg' => '查无结果']);
        }

        $t_data = [];
        //添加工号
        foreach ($res as $k => $v) {
            $admin_res = db('admin') -> where('id','=',$v['admin_id'])->select();
            $admin_res = collection($admin_res) -> toArray();
            if($admin_res){
                $number = $admin_res[0]['number'];
            }
            array_push($t_data,$number);

        }
        $return_data['number'] = $t_data;
        return $return_data;

    }
    public function getMeetingInfo($meeting_id)
    {
        $meeting = Db::table('meeting')
            ->where('id', '=', $meeting_id)
            ->find();
        return $meeting;
    }


}