<?php /** @noinspection ALL */

namespace app\admin\model;
use app\admin\exception\BaseException;
use think\Model;
use app\admin\model\Token;
use app\admin\model\SavePhoto;
use think\Db;


/***
 * 会议管理员model
 * Class MeetingAdmin
 * @package app\admin\model
 */
class MeetingAdmin extends Model
{
    protected $name = 'meeting_admin';
    /**
     * 新增管理员（二级）
     * 将工号和meeting_id 插入到 meeting_admin表中
     * @param $post
     * @param meeting_id 会议id
     * @param number[] 新增管理员的工号 array
     */
    public function addAdmin($post)
    {
        $meeting_id = $post['data']['meeting_id'];
        $number = $post['data']['number'];
        //获取该会议的发布人id
        $applyId = db('meeting_pending_assess')
            ->where('id','=',$meeting_id)
            ->value('admin_id');

        if(!$applyId){
            throw new BaseException(['code' => 411, 'msg' => '会议id有误!']);
        }
        //检测不能管理员重复添加
        $res = db('meeting_admin') -> where('meeting_id','=',$meeting_id)
            ->select();
        //查重
        if($res){
            foreach ($res as $k => $v){
                //已经有的admin_id
                $has_admin_id = $v['admin_id'];
                //获取已经有的admin_id的学号
                $has_number = db('admin')->where('id','=',$has_admin_id)->select();
                if($has_number){
                    $has_number = $has_number[0]['number'];
                }
                //一一去比较
                foreach ($number as $k => $num){
                    if($num == $has_number){
                        throw new BaseException(['code' => 413, 'msg' => '学号' . $num . '已添加，请不要重复添加']);
                    }
                }
            }
        }

        //检测添加的管理不能超过四个
        $count_number = count($number);
        $count = self::where('meeting_id','=',$meeting_id)
            ->count();
        if($count+$count_number > 4){
            throw new BaseException(['code' => 412, 'msg' => '添加的管理员不能超过四个！']);
        }

        DB::startTrans();
        try{
            foreach ($number as $k => $num)
            {

                //如果是此活动申请者则跳过，非此活动的申请者 才能给2级权限
                if($applyId == $num){
                    continue;
                }
                //获取admin_id, 这里没必要用关联了
                $admin_id = db('admin')
                    ->where('number','=',$num)
                    ->value('id');
                if(!$admin_id){
                    //如果admin表中没获取到的话 就用默认密码注册一个
                    $admin_id = db('admin')
                        ->insertGetId([
                            'number' => $num,
                            'password' => md5(config('setting.SALT').config('setting.DEFAULT_PASSWORD')),
                            'level' => 2,
                        ]);
                }
                //插入到meeting_admin表中
                $res = self::insert([
                    'admin_id' => $admin_id,
                    'meeting_id' => $meeting_id,
                    'level' => 2,
                ]);

            }
            Db::commit();
        }
        catch (\Exception $e){
            Db::rollback();
            throw new BaseException(['code' => 500, 'msg' => '服务器内部错误！']);
        }

    }
}
