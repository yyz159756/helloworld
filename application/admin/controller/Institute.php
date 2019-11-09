<?php /** @noinspection ALL */

namespace app\admin\controller;

use app\admin\controller\MyController;
use app\admin\exception;
use app\admin\model;
use think\Cache;
use app\admin\extra;

/**
 * Class Institute
 * @package app\admin\controller
 * 学院查看控制器
 */
//指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
//响应类型
header('Access-Control-Allow-Methods:*');
//响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');

class Institute extends MyController
{
    public function index()
    {
        $post = input('post.');
        //获取type
        $type = $post['type'];
        //获取data
        $data = $post['data'];
        $tokenModel = new model\Token();
        $meetingModel = new model\Meeting();
        $InstituteModel = new model\Institute();

        /***
         * 学院查看
         * 表格包含信息：单位（学院），发布讲座数，出席人数（总出席人数），
         * 学时数（总学时数，即全部讲座所出席人员所获得的学时数总和）；
         * 可按学年查看（包含2018-2019学年、2019-2020学年）
         */
        if ($type == 'A201') {
            //获取所有的单位的学时信息
            $Info = $InstituteModel->getAllInstituteInfo($post);
            //返回JSON
            return $this->renderSuccessData('success', $Info);
        } /***
         * 测试用，无关
         */
        else if ($type == 'test') {
            //关联表测试
            $res = db('user')
                ->join('meeting_member', 'user.id = meeting_member.uid')
                ->where('number', '=', '20181003097')
                ->field('username, major, meeting_name, score')
                ->select();

            return $this->renderSuccessData('success', $res);

        }
    }
}
