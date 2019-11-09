<?php /** @noinspection ALL */

namespace app\admin\model;

use app\admin\exception\BaseException;
use think\Model;

/***
 * 讲座预告model
 * Class MeetingNotice
 * @package app\admin\model
 */
class MeetingNotice extends Model
{

    protected $name = 'meeting_notice';
    /**
     * 新增讲座预告
     * @param $post
     * @param name
     * @param department
     * @param campus
     * @param score
     * @param month
     */
    public function add($post)
    {
        $data = $post['data'];
        $res = self::save([
            'name' => $data['name'],
            'department' => $data['department'],
            'campus' => $data['campus'],
            'score' => $data['score'],
            'month' => $data['month']
        ]);
        if(!$res){
            throw new BaseException(['code' => 411, 'msg' => '服务器insert发生错误']);
        }
    }

    /**
     * 更新讲座预告
     * @param $post
     */
    public function myUpdate($post){
        $data = $post['data'];
        $temp_list['id'] = $data['id'];
        if($data['name'] != ''){
            $temp_list['name'] = $data['name'];
        }
        if($data['department'] != '') {
            $temp_list['department'] = $data['department'];
        }
        if($data['campus'] != ''){
            $temp_list['campus'] = $data['campus'];
        }
        if($data['score'] != ''){
            $temp_list['score'] = $data['score'];
        }
        if($data['month'] != ''){
            $temp_list['month'] = $data['month'];
        }
        $list = [
            $temp_list
        ];
        $res = self::saveAll($list);
        if(!$res){
            throw new BaseException(['code' => 411, 'msg' => '服务器update发生错误']);
        }
    }


}
