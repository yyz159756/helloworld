<?php

namespace app\admin\controller;

use app\admin\exception\BaseException;
use think\Cache;
use think\Db;
use think\exception\DbException;

//指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
//响应类型
header('Access-Control-Allow-Methods:*');
//响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');
class Upload extends MyController
{
    public function uploadPhoto()
    {
        $post = input('post.');
        $meeting_id = $post['meeting_id'];
        $is_immediate = $post['is_immediate'];
//        var_dump($is_immediate);
        DB::startTrans();
        try {
            $file = request()->file('file');
            if (!$file) {
                throw new BaseException(['code' => 401, 'msg' => '没有接收到图片']);
            }
            if ($file) {
                $info = $file->validate(['size' => 10240000, 'ext' => 'jpg,png,gif'])->move(ROOT_PATH . 'public/static' . DS . 'img');
                if ($info) {
                    $filename = $info->getSaveName();
                } else {
                    throw new BaseException(['code' => 401, 'msg' => $info->getError()]);
                }
                $url = config('setting.SERVER_IP') . DS . 'ppms2.9/public/static' . DS . 'img' . DS . $filename;
                $res = Db::table('meeting_pending_assess')
                    ->where('id', '=', $meeting_id)
                    ->update(['photo' => $url]);
                if ($is_immediate == '1') {
                    //如果是立即发布那就要更新meeting表
                    $res1 = Db::table('meeting')
                        ->where('id', '=', $meeting_id)
                        ->update(['photo' => $url]);
                }
                if (!$res or (isset($res1) and !$res1)) {
                    throw new BaseException(['code' => 402, 'msg' => '写入失败']);
                }
                db::commit();
            }
        } catch (DbException $e) {
            db::rollback();
        }
        return $this->renderSuccessData('上传成功', ['url' => $url]);
    }
}