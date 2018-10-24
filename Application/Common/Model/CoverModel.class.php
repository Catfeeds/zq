<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

use Think\Model;

class CoverModel extends Model
{
    public $tmp_path = '';

    public function __construct()
    {
        //判断操作系统,选择临时文件生成目录
        $os_name = PHP_OS;
        if (strpos($os_name, "Linux") !== false) {
            $this->tmp_path = "/tmp/";
        } else if (strpos($os_name, "WIN") !== false) {
            $this->tmp_path = SITE_PATH . "/Runtime/Temp/";
        }
    }

    //自动生成专家推荐封面图片
    public function cover($id, $gid, $gtype)
    {
        //获取底图列表,随机抽取一张
        $dr = 'Public/Home/images/expert/backdrop';
        $dir = opendir($dr);
        while (false != ($file = readdir($dir))) {
            if (($file != ".") and ($file != "..")) {
                $file_list[] = $file;
            }
        }
        closedir($dir);

        $file_list = array_values($file_list);
        $rn = array_rand($file_list);
        //底图
        $dst_path = $dr . '/' . $file_list[$rn];

        //创建图片的实例
        $dst = imagecreatefromstring(file_get_contents($dst_path));

        if ($gtype == 1) {
            $m = M("GameBkinfo");
            $gtype = 2;
        } else {
            $m = M("GameFbinfo");
            $gtype = 1;
        }
        //查询赛事数据
        $game = $m->field('home_team_id,home_team_name,away_team_id,away_team_name')->where(['game_id' => $gid])->select();
        setTeamLogo($game, $gtype);
        $game = $game[0];
        //替换为http
//        $game['homeTeamLogo'] = str_replace('https', 'http', $game['homeTeamLogo']);
//        $game['awayTeamLogo'] = str_replace('https', 'http', $game['awayTeamLogo']);

        $game['home_team_name'] = explode(',', $game['home_team_name'])[0];
        $game['away_team_name'] = explode(',', $game['away_team_name'])[0];

        $logData['oldlogo'] = $game;
        //判读是否能访问
        $httpUrl = SITE_URL . $_SERVER['HTTP_HOST'];
        $home_res = get_headers($game['homeTeamLogo'], 1);
        $away_res = get_headers($game['awayTeamLogo'], 1);
        $logData['getHome'] = $home_res;
        $logData['getAway'] = $away_res;
        if (!preg_match('/200/', $home_res[0])) $game['homeTeamLogo'] = $httpUrl . '/Public/Home/images/common/home_def.png?1';
        if (!preg_match('/200/', $away_res[0])) $game['awayTeamLogo'] = $httpUrl . '/Public/Home/images/common/away_def.png?1';
        $logData['newLogo'] = $game;
        logRecord('--數據:' . json_encode($logData), 'cover.txt');
        //创建图片的实例
        $cover_c = $this->cover_config($game['home_team_name'], $game['away_team_name'], $gtype);//获取模板配置
        $temp_path = $cover_c['temp_path'];
        //模板
        if ($cover_c['bokeh'] == 1) {
            $src_path_b = $this->imagesize($game['homeTeamLogo'], $id, 'home', 2);
            $src_path2_b = $this->imagesize($game['awayTeamLogo'], $id, 'away', 2);
            //主队
            $src_b = imagecreatefromstring(file_get_contents($src_path_b));
            list($src_w, $src_h) = getimagesize($src_path_b);
            $this->imagecopymerge_alpha($dst, $src_b, -57, -50, 0, 0, $src_w, $src_h, 25);
            //客队
            $src2_b = imagecreatefromstring(file_get_contents($src_path2_b));
            list($src_w2, $src_h2) = getimagesize($src_path2_b);
            $this->imagecopymerge_alpha($dst, $src2_b, 272, 120, 0, 0, $src_w2, $src_h2, 25);
        }
        //主队
        $temp = imagecreatefromstring(file_get_contents($temp_path));
        list($temp_w, $temp_h) = getimagesize($temp_path);
        imagecopy($dst, $temp, 0, 0, 0, 0, $temp_w, $temp_h);

        $src_path = $this->imagesize($game['homeTeamLogo'], $id, 'home');
        $src_path2 = $this->imagesize($game['awayTeamLogo'], $id, 'away');
        //主队
        $src = imagecreatefromstring(file_get_contents($src_path));
        list($src_w, $src_h) = getimagesize($src_path);
        imagecopy($dst, $src, $cover_c['home_logo_x'], $cover_c['home_logo_y'], 0, 0, $src_w, $src_h);
        //客队
        $src2 = imagecreatefromstring(file_get_contents($src_path2));
        list($src_w2, $src_h2) = getimagesize($src_path2);
        imagecopy($dst, $src2, $cover_c['away_logo_x'], $cover_c['away_logo_y'], 0, 0, $src_w2, $src_h2);

        if ($cover_c['font']) {
            //打上文字
            $font = 'Public/Home/font/wryh.ttf';//字体路径

            $black = imagecolorallocate($dst, $cover_c['font_r'], $cover_c['font_g'], $cover_c['font_b']);//字体颜色
            imagefttext($dst, $cover_c['font_size'], 0, $cover_c['home_font_x'], $cover_c['home_font_y'], $black, $font, $game['home_team_name']);

            imagefttext($dst, $cover_c['font_size'], 0, $cover_c['away_font_x'], $cover_c['away_font_y'], $black, $font, $game['away_team_name']);
        }

        imagedestroy($src);
        imagedestroy($src2);
        imagedestroy($src_b);
        imagedestroy($src2_b);

        //输出图片
        list($dst_w, $dst_h, $dst_type) = getimagesize($dst_path);
        switch ($dst_type) {
            case 1://GIF
                header('Content-Type: image/gif');
                $tmp = $this->tmp_path . $id . ".gif";
                $type = 'image/gif';
                $filename = $id . '.gif';
                imagegif($dst, $tmp);
                break;
            case 2://JPG
                header('Content-Type: image/jpeg');
                $tmp = $this->tmp_path . $id . ".jpg";
                $type = 'image/jpeg';
                $filename = $id . '.jpg';
                imagejpeg($dst, $tmp);
                break;
            case 3://PNG
                header('Content-Type: image/png');
                $tmp = $this->tmp_path . $id . ".png";
                $type = 'image/png';
                $filename = $id . '.png';
                imagepng($dst, $tmp);
                break;
            default:
                break;
        }
        imagedestroy($dst);
        return ['name' => $filename, 'type' => $type, 'tmp_name' => $tmp, 'error' => 0, 'size' => filesize($tmp)];
    }

    //获取不同模板配置
    public function cover_config($home, $away, $type)
    {
        //获取模板配置
        $cover_config = C("cover");
        $key = array_keys($cover_config);
        $rn = array_rand($key);
        $id = $key[$rn];
        $home_n = mb_strlen($home, 'UTF-8');
        $away_n = mb_strlen($away, 'UTF-8');

        //是否显示队名
        $data['font'] = 1;

        //当主客队字数超过6个时,使用不显示文字模板
        if ($home_n > 5 || $away_n > 5) {
            $id = 'temp2';
        }
        if ($home_n > 6 || $away_n > 6) {
            $data['font'] = 0;
            $id = 'temp7';
        }
        $config = $cover_config[$id];
        //模板1时计算客队名称长度,用于计算客队x坐标
        $away_font_x = 0;//初始化为0
        $home_font_x = 0;//初始化为0
        //模板1,模板3时计算客队坐标
        if ($id == 'temp1' || $id == 'temp3') $away_font_x = (34 + 2 * $away_n) * $away_n;
        //当主客队字数大于5时不可用
        if ($id == 'temp2') {
            $home_font_x = (32 + 2 * $home_n) * $home_n / 2;
            $away_font_x = (32 + 2 * $away_n) * $away_n / 2;
        }
        if ($id == 'temp5') $data['bokeh'] = 1;

        if ($type == 2) {
            $data['temp_path'] = $config['temp_path_b'] ? $config['temp_path_b'] : $config['temp_path'];
        } else {
            $data['temp_path'] = $config['temp_path'];
        }
        $data['home_logo_x'] = $config['home_logo_x'];
        $data['home_logo_y'] = $config['home_logo_y'];
        $data['away_logo_x'] = $config['away_logo_x'];
        $data['away_logo_y'] = $config['away_logo_y'];
        $data['font_r'] = $config['font_r'];
        $data['font_g'] = $config['font_g'];
        $data['font_b'] = $config['font_b'];
        $data['font_size'] = $config['font_size'];
        $data['home_font_x'] = $config['home_font_x'] - $home_font_x;
        $data['home_font_y'] = $config['home_font_y'];
        $data['away_font_x'] = $config['away_font_x'] - $away_font_x;
        $data['away_font_y'] = $config['away_font_y'];
        return $data;
    }


    //对球队图标进行拉伸
    public function imagesize($filename, $id, $type, $mult = 1)
    {
        //因为PHP只能对资源进行操作，所以要对需要进行缩放的图片进行拷贝，创建为新的资源
        $src = imagecreatefrompng($filename);
        imagesavealpha($src, true);

        //取得源图片的宽度和高度
        $size_src = getimagesize($filename);
        $w = $size_src['0'];
        $h = $size_src['1'];

        //指定缩放出来的最大的宽度（也有可能是高度）
        $max = 190 * $mult;

        //根据最大值为300，算出另一个边的长度，得到缩放后的图片宽度和高度
        if ($w > $h) {
            $w = $max;
            $h = $h * ($max / $size_src['0']);
        } else {
            $h = $max;
            $w = $w * ($max / $size_src['1']);
        }

        //声明一个$w宽，$h高的真彩图片资源
        $image = imagecreatetruecolor($w, $h);
        imagealphablending($image, false);
        imagesavealpha($image, true);


        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($image, $src, 0, 0, 0, 0, $w, $h, $size_src['0'], $size_src['1']);

        //告诉浏览器以图片形式解析
        header('content-type:image/png');
        $tmp = $this->tmp_path . $id . '_' . $type . ".png";
        imagepng($image, $tmp);

        //销毁资源
        imagedestroy($image);
        return $tmp;
    }

    public function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        $opacity = $pct;
        // getting the watermark width
        $w = imagesx($src_im);
        // getting the watermark height
        $h = imagesy($src_im);

        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);
        // copying that section of the background to the cut
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        // inverting the opacity
        //$opacity = 100 - $opacity;

        // placing the watermark now
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
    }

    //专家发布推荐获取赛事列表
    /**
     * 弹窗查找赛事
     */
    public function findGameData($game_type)
    {
        if ($game_type == 1) {
            list($_ya) = D('GambleHall')->matchList(1,'',1);
            list($_ji) = D('GambleHall')->matchList(2,'',1);
        } else {
            list($_ya) = D('GambleHall')->basketballList();
        }

        $ya = array();
        foreach ($_ya as $k => $v) {
            if (!($v['game_state'] != 0 or time() > $v['gtime'])) {
                $ya[$v['game_id']] = $v;
            }
        }
        foreach ($_ji as $k => $v) {
            if ($v['game_state'] != 0 or time() > $v['gtime']) {
                unset($_ji[$k]);
            } else {
                if ($ya[$v['game_id']]) {
                    $_ji[$k]['fsw_exp_home'] = $ya[$v['game_id']]['fsw_exp_home'];
                    $_ji[$k]['fsw_exp'] = $ya[$v['game_id']]['fsw_exp'];
                    $_ji[$k]['fsw_exp_away'] = $ya[$v['game_id']]['fsw_exp_away'];
                    $_ji[$k]['fsw_ball_home'] = $ya[$v['game_id']]['fsw_ball_home'];
                    $_ji[$k]['fsw_ball'] = $ya[$v['game_id']]['fsw_ball'];
                    $_ji[$k]['fsw_ball_away'] = $ya[$v['game_id']]['fsw_ball_away'];
                    unset($ya[$v['game_id']]);
                }
            }
        }
        $game = array_values(array_merge((array)$ya, (array)$_ji));
        $httpUrl = SITE_URL . $_SERVER['HTTP_HOST'];
        $union = [];
        foreach ($game as $k => $v) {
            //判断赛事亚盘竞猜是否缺少相关数据,是的话删除该赛事
            $v_tmp = false;
            if ($v['draw_odds'] === '' && $v['draw_letodds'] === '' && $v['fsw_exp'] === '' && $v['fsw_ball'] === '') $v_tmp = true;
            if ($v_tmp || $v['fsw_exp'] === null) {
                unset($game[$k]);
                continue;
            }
            if (array_key_exists($v['union_id'], $union)) {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num'] + 1);
            } else {
                $union[$v['union_id']] = ['union_id' => $v['union_id'], 'union_name' => $v['union_name'], 'union_num' => '1', 'union_color' => $v['union_color']];
            }
            if (empty($v['homeTeamLogo'])) $game[$k]['homeTeamLogo'] = $httpUrl . '/Public/Home/images/common/home_def.png';
            if (empty($v['awayTeamLogo'])) $game[$k]['awayTeamLogo'] = $httpUrl . '/Public/Home/images/common/away_def.png';
        }
        $game = $this->arraySequence($game, 'gtime', 'SORT_ASC');
        $union = array_values($union);
        $res = ['game' => $game, 'union' => $union];
        return $res;
    }

    public function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
    
    //通过文章内容获取关键字
    public function contGetKey($content, $type = 1)
    {
        if (!$keyword = S('keyword_' . $type)) {
            $keyword = M('HotKeyword')->getField('keyword', true);
            //默认为1,关键字包含两张表数据
            if ($type == 1) {
                $tmp = M('PublishKey')->where(['status' => 1])->getField('name', true);
                $keyword = array_merge((array)$tmp, (array)$keyword);
            }
            S('keyword_' . $type, $keyword, 600);
        }
        $tmp = [];
        foreach ($keyword as $val) {
            if (strpos($content, $val))
                $tmp[] = $val;
        }
        $tmp = array_unique($tmp);
        if (empty($tmp)) {
            $str = '';
        } elseif (count($tmp) < 4) {
            $str = implode(',', $tmp);
        } else {
            $arr = array_rand($tmp, 3);
            $str = $tmp[$arr[0]] . ',' . $tmp[$arr[1]] . ',' . $tmp[$arr[2]];
        }
        return $str;
    }
}