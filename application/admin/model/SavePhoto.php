<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace app\admin\model;
use think\Model;
use app\admin\exception\BaseException;
use app\admin\model\Token;
use think\Db;
/**
 * email smtp （support php7）
 *
 * Modified by: Reson 2017/06
 * UPDATE:
 * 1、change ereg to preg_match；change ereg_replace to preg_replace.
 * 2、change var to public/private.
 *
 * More: http://www.daixiaorui.com
 *
 */


class SavePhoto extends Model
{
    public function save_()
    {
        if ($_FILES["file"]["error"]) {
            echo $_FILES["file"]["error"];
        }
        else
            {
                dump($_FILES["file"]["type"]);
                echo"</br>";
            //没有出错
            //加限制条件
            //判断上传文件类型为png或jpg且大小不超过10240000B（10M）
            if (($_FILES["file"]["type"] == "image/png" || $_FILES["file"]["type"] == "image/jpeg"
                    || $_FILES["file"]["type"] == "image/jpg") && $_FILES["file"]["size"] < 10240000) {
                switch ($_FILES["file"]["type"]) {
                    case 'image/jpeg':
                        $type = 'jpg';
                        break;
                    case 'image/jpg':
                        $type = 'jpg';
                        break;
                    case 'image/png':
                        $type = 'png';
                        break;
                    case 'image/gif':
                        $type = 'gif';
                        break;
                    default:
                        $ext = '';
                        break;
                }
                //防止文件名重复
                $filename = ROOT_PATH . 'public/static/img/' . time() . rand(0,10) . '.' . $type;
                //转码，把utf-8转成gb2312,返回转换后的字符串， 或者在失败时返回 FALSE。
//                $filename = iconv("UTF-8", "gb2312", $filename);
                //检查文件或目录是否存在
                if (file_exists($filename)) {
                    echo "该文件已存在";
                } else {
                    //保存文件,   move_uploaded_file 将上传的文件移动到新位置
                    move_uploaded_file($_FILES["file"]["tmp_name"], $filename);//将临时地址移动到指定地址
                    return $filename;
                }
            }
            else {
                throw new BaseException(['code' => 420, 'msg' => '文件类型不对']);

            }


        }
    }
}


