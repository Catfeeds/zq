<?php
set_time_limit(0);
@ini_set('implicit_flush',1);
ob_implicit_flush(1);
@ob_end_clean();
/**
 * @帖子数据抓取程序
 * @author chenzj dengwj
 */
vendor('QueryList/autoload');
use QL\QueryList;

use Think\Controller;
use Think\Tool\Tool;

class PostCaptureController extends CommonController
{
    protected function _initialize() 
    {

    }
    //虎扑帖子数据采集
    public function hupu()
    {
        $url = [
                    ['cid' => '2' ,'url' => 'manutd'],      //曼联圈
                    ['cid' => '1' ,'url' => 'realmadrid'],  //皇马圈
                    ['cid' => '7' ,'url' => 'barcelona'],   //巴萨圈
                    ['cid' => '8' ,'url' => 'bayern'],      //拜仁圈
                    ['cid' => '6' ,'url' => 'hengda'],      //恒大圈
                    ['cid' => '14','url' => 'china-soccer'],//中国队圈
                    ['cid' => '11','url' => 'topic'],       //足球话题
                    ['cid' => '9' ,'url' => 'zucai'],       //足彩圈
               ];

        //获取机器人
        $user = M('FrontUser')->field('id')->where(['status'=>1,'is_robot'=>1])->select();

        foreach ($url as $key => $value) 
        {
            //获取页面并转码
            //$contents = mb_convert_encoding(Tool::url_get_contents('https://bbs.hupu.com/'.$value['url']), "UTF-8", "GBK");
            $contents = Tool::url_get_contents('https://bbs.hupu.com/'.$value['url']);  

            preg_match_all('/<tr mid="(.*?)"><td class="p_chkbox">/i', $contents, $match);

            if(!in_array($value['url'], ['zucai','hengda'])) unset($match[1][0]); //去掉第一篇置顶贴

            foreach ($match[1] as $k => $v) 
            {
                echo '|------------------------第'.$k.'---------------------------|<br/>';
                $post_url = 'https://bbs.hupu.com/'.$v.'.html';
                $content  = Tool::url_get_contents($post_url); 

                //获取标题
                preg_match('/<div class="subhead"><span>(.*?)<\/span>/i', $content, $post_title);

                //获取内容
                preg_match('/<div class="quote-content">(.*?)<\/tr><\/table>/is', $content, $post_content);
                
                //过滤字符
                $post_content = preg_replace('/<small.*?>(.*?)<\/small>|虎扑|虎扑体育/is', '', $post_content[1]);
                $img          = Tool::getTextImgUrl($post_content,fasle);
                $post_content = Tool::html2text($post_content);

                //标题不能有[]，文章不能有图片,标题和内容不能为空
                if(preg_match("/\[*\]/is", $post_title[1]) || !empty($img) || empty($post_title[1]) || empty($post_content)) 
                {
                    dump("【{$post_title[1]}】该标题不复合要求或文章存在图片,已放弃采集:".$v);
                    continue; 
                }

                //抓取评论
                preg_match_all('/<div id="(\d+)" class="floor">(.*?)<\/tr><\/table>/is', $content, $post_comments); 
                $commentTemp = [];
                foreach ($post_comments[0] as $a => $aa) 
                {
                    preg_match('/<div id="(\d+)" class="floor">/is', $aa, $comment_id); //评论id
                    //评论内容
                    preg_match('/<table class="case" border="0" cellspacing="0" cellpadding="0"><tr><td>(.*?)<\/tr><\/table>/is', $aa, $comment_n);
                    $commentTemp[] = [
                        'id'      => $comment_id[1],
                        'comment' => $comment_n[1],
                    ]; 
                }

                foreach ($commentTemp as $kk => $vv) 
                {
                    if(preg_match('/<div class="subhead">|<blockquote>/is', $vv['comment']) || empty($vv['comment'])) //去掉不符合要求的评论
                    {
                       unset($commentTemp[$kk]); 
                    }
                    else
                    {
                        $comment = preg_replace('/<small.*?>(.*?)<\/small>|虎扑|虎扑体育/is', '', $vv['comment']); //过滤
                        $commentTemp[$kk]['comment'] = Tool::html2text($comment); //去掉html标签
                    }
                }
                //是否已采集帖子
                $CommunityPosts = M('CommunityPosts')->field('id,capture_id')->where(['is_capture'=>1,'capture_id'=>$v])->find();

                if(!$CommunityPosts)  //采集帖子和评论
                {
                    //帖子
                    $randTime = rand(NOW_TIME-1800,NOW_TIME-3600);
                    $post['cid']            = $value['cid'];
                    $post['user_id']        = $user[array_rand($user)]['id'];
                    $post['base64_title']   = base64_encode(Tool::html2text($post_title[1]));
                    $post['base64_content'] = base64_encode($post_content);
                    $post['create_time']    = $randTime;
                    $post['lastreply_time'] = $randTime;
                    $post['is_capture']     = 1;
                    $post['capture_id']     = $v;

                    //帖子入库
                    $post_id = M('CommunityPosts')->add($post);

                    if($post_id){
                        M('community')->where(['id'=>$value['cid']])->setInc('post_num');  //圈子发帖数加1

                        //添加评论
                        $this->hupu_comments($post_id,$commentTemp,$user);

                        dump("【{$post_title[1]}】采集帖子和评论成功:".$v);
                    }
                    else
                    {
                        dump("【{$post_title[1]}】采集帖子和评论失败:".$v);
                    }
                }
                else //只采集评论
                {
                    $post_id = $CommunityPosts['id'];
                    
                    //添加评论
                    $this->hupu_comments($post_id,$commentTemp,$user);

                    dump("【{$post_title[1]}】采集评论成功:".$v);
                }
                
            }
        }

    }

    //虎扑评论入库
    public function hupu_comments($post_id,$commentTemp,$user)
    {
        if($post_id && !empty($commentTemp)) //添加评论
        {
            $commentArr = [];
            $timeArr    = [];
            foreach ($commentTemp as $kkk => $vvv) 
            {
                //是否已采集评论
                $CommunityComment = M('CommunityComment')->field("id,capture_id")->where(['is_capture'=>1,'capture_id'=>$vvv['id']])->find();
                if(!$CommunityComment) 
                {
                    $create_time  = rand(time()-1800,time());
                    $commentArr[] = [
                        'post_id'        => $post_id,
                        'user_id'        => $user[array_rand($user)]['id'],
                        'content'        => base64_encode($vvv['comment']),
                        'filter_content' => base64_encode($vvv['comment']),
                        'is_capture'     => 1,
                        'capture_id'     => $vvv['id'],
                        'platform'       => rand(2,3),
                        'create_time'    => $create_time,
                    ];
                    $timeArr[] = $create_time;
                }
            }

            if(M('CommunityComment')->addAll($commentArr)) //统一添加评论，更新帖子最新评论时间，被评论数
            {
                M('CommunityPosts')->where(['id'=>$post_id])->save(['lastreply_time'=>max($timeArr),'comment_num'=>['exp','comment_num+'.count($commentArr)]]);
            }
        }
    }

    //直播8帖子评论抓取
    public function zhibo8() 
    {
        //数据库圈子id对应直播8圈子id
        $fid = [
            1  => '233', //皇马圈
            2  => '236', //曼联圈
            7  => '234', //巴萨圈
            8  => '235', //拜仁圈
            6  => '261', //恒大圈
            14 => '232', //中国队圈
            11 => '8'    //足球话题
        ];
        $robots = M('FrontUser')->field('id')->where(['status'=>1,'is_robot'=>1])->select();
        
        foreach ($fid as $kk => $vv) 
        {
            $url = 'http://bbs.zhibo8.cc/forum/forum_thread_list?fid=' . $vv . '&page=1&per_page_num=50&device=pc';
            $rsl = Tool::url_get_contents($url);
            if ($rsl) 
            {
                $rsl = json_decode($rsl, true);
                if ($rsl['status'] == 1) 
                {
                    $data = $rsl['data'];
                    foreach ($data['list'] as $v) 
                    {
                        if (!empty($v['img_list'])) {
                            dump('跳过----' . $v['subject'] . '-------');
                            continue;
                        }
                        $u = 'http://bbs.zhibo8.cc/forum/post_list?tid=' . $v['tid'] . '&device=pc&page=1&per_page_num=30NaN';
                        $content = Tool::url_get_contents($u);
                        if ($content) 
                        {
                            $content = json_decode($content, true);
                            if ($content['status'] == 1) 
                            {
                                $list         = $content['data']['list'];
                                $add_comments = [];
                                $timeArr      = [];
                                $post_id      = 0;
                                foreach ($list as $key => $val) 
                                {
                                    $rand = rand(0, count($robots) - 1);
                                    $user_id = $robots[$rand];
                                    if ($key == 0) //帖子
                                    {
                                        $img = Tool::getTextImgUrl($val['message'],fasle);
                                        //帖子不能有图片,标题和内容不能为空
                                        if(!empty($img) || empty($val['subject']) || empty($val['message'])) 
                                        {
                                            dump('跳过----' . $val['subject'] . '-------');
                                            continue;
                                        }
                                        //是否已采集帖子
                                        $CommunityPosts = M('CommunityPosts')->field('id,capture_id')->where(['is_capture'=>2,'capture_id'=>$val['tid']])->find();

                                        if (!$CommunityPosts) //新增帖子
                                        {
                                            $post_id = $this->doposts($val['subject'], $val['message'], $kk, $user_id['id'], $val['tid']);
                                        } 
                                        else //新增评论
                                        {
                                            $post_id = $CommunityPosts['id'];
                                        }
                                    }
                                    else //评论
                                    {
                                        if (!empty($val['img_list']) || empty($val['message'])) {
                                            dump('跳过评论----' . $val['message'] . '-------');
                                            continue;
                                        }
                                        //是否已采集评论
                                        $CommunityComment = M('CommunityComment')->field("id,capture_id")->where(['is_capture'=>2,'capture_id'=>$val['pid']])->find();

                                        if (!$CommunityComment) 
                                        {
                                            if ($post_id) {
                                                $create_time = rand(time(), time() - 1800);
                                                $message     = preg_replace('/直播吧|直播8/is', '', $val['message']); //过滤
                                                $add_comments[] = [
                                                    'post_id'        => $post_id,
                                                    'user_id'        => $user_id['id'],
                                                    'content'        => base64_encode(Tool::html2text($message)),
                                                    'filter_content' => base64_encode(Tool::html2text($message)),
                                                    'is_capture'     => 2,
                                                    'capture_id'     => $val['pid'],
                                                    'platform'       => rand(2, 3),
                                                    'create_time'    => $create_time,
                                                ];
                                                $timeArr[] = $create_time;
                                            }
                                        }
                                    }
                                }

                                if (M('CommunityComment')->addAll($add_comments)) {
                                    M('CommunityPosts')->where(['id'=>$post_id])->save(['lastreply_time'=>max($timeArr),'comment_num'=>['exp','comment_num+'.count($add_comments)]]);
                                }

                            }
                        }
                    }
                }
            }
        }
        
    }

    private function doposts($subject, $message, $cid, $user_id, $tid) {
        $randTime = rand(time()-1800,time()-3600);
        $subject = preg_replace('/直播吧|直播8/is', '', $subject); //过滤
        $message = preg_replace('/直播吧|直播8/is', '', $message); //过滤
        $rsl = M('CommunityPosts')->add([
            'cid'            => $cid,
            'user_id'        => $user_id,
            'base64_title'   => base64_encode(Tool::html2text($subject)),
            'base64_content' => base64_encode(Tool::html2text($message)),
            'is_capture'     => 2,
            'capture_id'     => $tid,
            'create_time'    => $randTime,
            'lastreply_time' => $randTime,
        ]);
        
        if($rsl) M('community')->where(['id'=>$cid])->setInc('post_num');  //圈子发帖数加1
        
        return $rsl;
    }

    //抓取espn资讯
    public function espn()
    {
        $Model = M('PublishList','qc_','mysql://root:qwer1234@192.168.1.226:3306/cms'); 
        //$Model = M('PublishList');
        $url = 'http://www.espn.com/fantasy/basketball/';
        $contents = Tool::url_get_contents($url);
        preg_match_all('/<article data-id="(\d+)" (.*?)>(.*?)<\/article>/is', $contents, $match);
        $page = [];
        foreach ($match[0] as $k => $v) {
            preg_match('/data-id="(\d+)"/is', $v, $id); //id
            preg_match('/href="(.*?)"/is', $v, $url);   //url
            if(!strpos($url[1],'video')){
                $page[$k]['id'] = $id[1];
                $page[$k]['url'] = 'http://www.espn.com'.$url[1];
            }
        }
        $page_id = array_map("array_shift", $page);
        //获取已抓取资讯
        $is_page = $Model->where(['capture_id'=>['in',$page_id]])->getField('capture_id',true);
        foreach ($page as $k => $v) {
            if(in_array($v['id'],$is_page)){
                unset($page[$k]);
            }
        }
        if(empty($page)) exit("empty！");

        foreach ($page as $k => $v) 
        {
            $newsData = [];
            $news = Tool::url_get_contents($v['url']);
            //标题
            preg_match('/<header class="article-header"><h1>(.*?)<\/h1><\/header>/is', $news, $title); 
            //摘要
            //preg_match('/<figcaption class="photoCaption">(.*?)<cite>/is',   $news, $remark); 
            //内容
            preg_match('/<\/a><\/li><\/ul><p>(.*?)<footer class="article-footer">/is', $news, $content);
            //图片
            preg_match('/<div class="img-wrap">(.*?)<\/picture>/is', $news, $imgStr);
            preg_match('/<source srcset="(.*?)"/is', $imgStr[1], $img);
            
            $title_en = html_entity_decode(strip_tags($title[1]),ENT_QUOTES);
            //$remark_en = html_entity_decode(strip_tags($remark[1]),ENT_QUOTES);iframe
            $en_content = $this->strip_html_tags(['aside','blockquote'],$content[1],1);
            $content_en = html_entity_decode(strip_tags($en_content),ENT_QUOTES);

            //连在一起请求翻译
            $str = $title_en . "\n\n\n\n\n" . $content_en;
            $ec_text = $this->translate($str);
            $data = explode("nnnnn", $ec_text);
            if(!empty($img)){
                $img  = Think\Tool\Tool::url_get_contents($img[1]);
                $return = D('Uploads')->uploadFileBase64(base64_encode($img), "newsimg");
                $imgUrl = $return['status'] == 1 ? "<img src='".C('IMG_SERVER').$return['url']."' />" : '';
            }
            $newsData['class_id']= 63;
            $newsData['capture_id']= $v['id'];
            $newsData['status']  = 0;
            $newsData['add_time']= NOW_TIME;
            $newsData['title']   = $data[0];
            $newsData['content'] = htmlspecialchars($imgUrl.$data[1]);
            $newsData['en_content'] = htmlspecialchars($en_content);
            $rs = $Model->add($newsData);
            if($rs){
                dump("【{$data[0]}】采集资讯成功:".$v['id']);
            }
        }
    }

    /**
     * 删除指定的标签和内容
     * @param array $tags 需要删除的标签数组
     * @param string $str 数据源
     * @param string $content 是否删除标签内的内容 默认为0保留内容    1不保留内容
     * @return string
     */
    public function strip_html_tags($tags,$str,$content=0){
        if($content){
            $html=array();
            foreach ($tags as $tag) {
                $html[]='/(<'.$tag.'.*?>[\s|\S]*?<\/'.$tag.'>)/';
            }
            $data=preg_replace($html,'',$str);
        }else{
            $html=array();
            foreach ($tags as $tag) {
                $html[]="/(<(?:\/".$tag."|".$tag.")[^>]*>)/i";
            }
            $data=preg_replace($html, '', $str);
        }
        return $data;
    } 

    public function foxsport()
    {
        $url = "http://www.foxsportsasia.com/football/premier-league/news/detail/item636759/arsenal-fans-are-at-it-again-after-palace-mauling/";
        $contents = Tool::url_get_contents($url);

        //$contents = file_get_contents('a.html');
        preg_match('/<p><strong>(.*?)<\/section>/is', $contents, $match);
        $img      = Tool::getTextImgUrl($match[1],false);
        foreach ($img as $k => $v) {
            if(preg_match('/http/is', $v)){
                $imgStr .= "<img src='".$v."' />";
            }
        }
        //去掉html标签
        $text = html_entity_decode(strip_tags($match[1]),ENT_QUOTES);
        $text = str_replace([PHP_EOL,"\r\n", "\r", "\n"], '', $text);
        echo $text;
        echo '<br/>';
        $text = translate($text);
        echo ($imgStr.'<br/>'.$text);
        // if($imgStr != ''){
        //     $text = $imgStr.'<br/>'.$text;
        // }
    }

    /**
     * 执行文本翻译（支持的语种）'auto'=>'自动检测'
     * 'ara'=>'阿拉伯语', 'de'=>'德语', 'ru' =>'俄语','spa'=>'西班牙语','fra'=>'法语','kor'=>'韩语',
     * 'pt' =>'葡萄牙语','jp' => '日语','th' =>'泰语','wyw'=> '文言文', 'el' => '希腊语',
     * 'it' =>'意大利语','en' => '英语','yue'=>'粤语','zh' => '中文' ,  'nl' =>'荷兰语'.
     * @param string $text 要翻译的文本
     * @param string $from 原语言语种 默认:英文
     * @param string $to 目标语种 默认:中文
     * @return boolean string 翻译失败:false 翻译成功:翻译结果
     */
    public function execTranslate($text, $from = 'en', $to = 'zh') {
        $url = "http://fanyi.baidu.com/v2transapi";
        $data = http_build_query ( array('from' => $from,'to' => $to,'query' => $text) );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_REFERER, "http://fanyi.baidu.com" );
        curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:37.0) Gecko/20100101 Firefox/37.0' );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 10 );
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        $result = json_decode ( $result, true );
        // 出错状态码 999
        if ($result ['error']) return false;
        $text = '';
        foreach ($result['trans_result']['data'] as $k => $v) {
            $text .= $v['dst'].PHP_EOL;
        }
        return $text;
    }

    public function translate($text=""){
        $url = 'http://www.tastemylife.com/gtr.php';
        $postdata = array(
                'p'   =>  '1',
                'q'   =>  $text,
                'sl'  =>  'en',
                'tl'  =>  'zh-CN',
        );
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_TIMEOUT,60);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postdata);
        $data = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($data,true);
        return ($result['result']);
    }

    /**
     * 抓取美女图片
     */
    public function getBeautyPic1(){
       //性感美女（对应泳装女神）,清纯美女，校花美女，车模美女，美女特辑，明星风采
       /*
        $url = [
            ['class_id' => 17, 'url' => ['http://www.mm131.com/xinggan/', 'http://www.zbjuran.com/mei/xinggan/', 'https://www.4493.com/xingganmote/']],
            ['class_id' => 21, 'url' => ['http://www.mm131.com/qingchun/', 'http://www.zbjuran.com/mei/qingchun/', 'https://www.4493.com/gaoqingmeinv/']],
            ['class_id' => 21, 'url' => ['http://www.mm131.com/xiaohua/', 'http://www.zbjuran.com/mei/xiaohua/', 'https://www.4493.com/weimeixiezhen/']],
            ['class_id' => 5, 'url' => ['http://www.mm131.com/chemo/', '', 'https://www.4493.com/motemeinv/']],
            ['class_id' => 18, 'url' => ['http://www.mm131.com/qipao/', '', 'https://www.4493.com/siwameitui/']],
            ['class_id' => 4, 'url' => ['http://www.mm131.com/mingxing/', 'http://www.zbjuran.com/mei/mingxing/', 'https://www.4493.com/mingxingxiezhen/']],
        ];

        $param = [
            [['list_6_', '_'], ['list_13_', '_'], ['index-', '']],
            [['list_1_', '_'], ['list_14_', '_'], ['index-', '']],
            [['list_2_', '_'], ['list_15_', '_'], ['index-', '']],
            [['list_3_', '_'], [], ['index-', '']],
            [['list_4_', '_'], [], ['index-', '']],
            [['list_5_', '_'], ['list_16_', '_'], ['index-', '']],
        ];
*/

        $url = [
            ['class_id' => 17, 'url' => 'http://www.mm131.com/xinggan/',  'class_name' => 'list_6_'],
            ['class_id' => 21, 'url' => 'http://www.mm131.com/qingchun/', 'class_name' => 'list_1_'],
            ['class_id' => 21, 'url' => 'http://www.mm131.com/xiaohua/',  'class_name' => 'list_2_'],
            ['class_id' => 5,  'url' => 'http://www.mm131.com/chemo/',    'class_name' => 'list_3_'],
            ['class_id' => 18, 'url' => 'http://www.mm131.com/qipao/',    'class_name' => 'list_4_'],
            ['class_id' => 4,  'url' => 'http://www.mm131.com/mingxing/', 'class_name' => 'list_5_'],
        ];

        $urlArr = file_get_contents('./Public/Home/data/urlArr1.php');
        if($urlArr){
            $urlArr = json_decode($urlArr, true);
        }else{
            $urlArr = [];
            foreach($url as $k => $v){
                $urlArr[$k] = $this->getAllUrl($v['url'], $v['class_name']);
            }

            file_put_contents('./Public/Home/data/urlArr1.php', json_encode($urlArr));
        }

//        $urlArr = [
//            [ "http://www.mm131.com/xinggan/list_6_5.html", "http://www.mm131.com/xinggan/list_6_6.html"],
//            ["http://www.mm131.com/qingchun/list_1_2.html", "http://www.mm131.com/qingchun/list_1_3.html"],
//        ];
//        var_dump($urlArr);die;

        if(empty($urlArr))
            die('no page');

        $model = D('Gallery');
        $usedUrl = file_get_contents('./Public/Home/data/usedUrl.php');//已使用的url
        if($usedUrl)
            $usedUrl = json_decode($usedUrl, true);

        foreach($urlArr as $k => $v) {
            $num = 1;
            foreach ($v as $k1 => $v1) {
                if(in_array($v1, $usedUrl[1]))
                    continue;

                if($num == 2)
                    continue;

//                var_dump($v1);
                $contents = Tool::url_get_contents($v1);
                if ($contents) {
                    //转码
                    $contents = mb_convert_encoding($contents, "UTF-8", "GBK");
                    //截取主体
                    preg_match_all('/<dl class="list-left public-box">(.*?)<\/dl>/is', $contents, $content);
                    //截取图集链接
                    preg_match_all('/<a target="_blank" href="(.*?)">/i', $content[0][0], $arr);
                    //获取标题
                    preg_match_all('/(alt)=("[^"]*")/i', $content[0][0], $title);
                    //得到每页的图集，分别到图集获取图片
                    if ($arr[1]) {
                        foreach ($arr[1] as $k2 => $v2) {
                            $data = [];
                            $data['class_id']    = $url[$k]['class_id'];
                            $data['editor']      = 1;
                            $data['title']       = htmlspecialchars_decode(trim($title[2][$k2], '"'));
                            $data['short_title'] = htmlspecialchars_decode(trim($title[2][$k2], '"'));
                            $data['remark']      = htmlspecialchars_decode(trim($title[2][$k2], '"'));
                            $data['add_time']    = NOW_TIME;
                            $data['status']      = 0;

                            if(stripos($v2, 'www.mm131.com/xinggan') && $k2 < 5)
                                $data['home_recommend'] = 1;

                            $rs_id = $model->add($data);

                            $picName = explode('.', end(explode('/', $v2)))[0];

                            $picArr = [];
                            for ($i = 1; $i < 31; $i++) {
                                $picUrl = "http://img1.mm131.com/pic/{$picName}/{$i}.jpg";

                                $img = Tool::url_get_contents($picUrl);
                                //直接请求，判断404
                                if(stripos($img, 'Not Found')){
                                    break;
                                } else {
                                    $return = D('Uploads')->uploadFileBase64(base64_encode($img), "gallery", '', $i, $rs_id);
                                    if($return['status'] == 1) {
                                        if ($i == 1) {
                                            $size = getimagesize($picUrl);
                                            $return['url'] .= '&size=' . $size[0] . 'X' . $size[1];
                                        }

                                        $picArr[$i] = $return['url'];
                                    }else{
                                        continue;
                                    }
                                }
                            }

                            if ($picArr) {
                                $model->where(['id' => $rs_id])->save(['img_array' => json_encode($picArr),  'update_time' => NOW_TIME, 'status' => 1]);
                            }
//                            var_dump($v2, $picArr);die;
                        }

                    } else {
                        break;
                    }
                } else {
                    break;
                }

//                $all[] = $v1;
                if($usedUrl){
                    $usedUrl[1][] = $v1;
                    file_put_contents('./Public/Home/data/usedUrl.php', json_encode($usedUrl));
                }else{
                    $usedUrl = [];
                    $usedUrl[1][] = $v1;
                    file_put_contents('./Public/Home/data/usedUrl.php', json_encode($usedUrl));
                }
                $num++;
            }
        }
//        var_dump($all);
        die('ok');
    }

    public function getBeautyPic2(){
        //性感美女（对应泳装女神）,清纯美女，校花美女，车模美女，美女特辑，明星风采
        $url = [
            ['class_id' => 17, 'url' => 'http://www.zbjuran.com/mei/xinggan/',  'class_name' => 'list_13_'],
            ['class_id' => 21, 'url' => 'http://www.zbjuran.com/mei/qingchun/', 'class_name' => 'list_14_'],
            ['class_id' => 21, 'url' => 'http://www.zbjuran.com/mei/xiaohua/',  'class_name' => 'list_15_'],
            ['class_id' => 4,  'url' => 'http://www.zbjuran.com/mei/mingxing/', 'class_name' => 'list_16_'],
        ];

        $urlArr = file_get_contents('./Public/Home/data/urlArr2.php');
        if($urlArr){
            $urlArr = json_decode($urlArr, true);
        }else{
            $urlArr = [];
            foreach($url as $k => $v){
                $urlArr[$k] = $this->getAllUrl($v['url'], $v['class_name']);
            }

            file_put_contents('./Public/Home/data/urlArr2.php', json_encode($urlArr));
        }

//        var_dump($urlArr);die;
//        $urlArr = [
//            ["http://www.zbjuran.com/mei/xinggan/", "http://www.zbjuran.com/mei/xinggan/list_13_2.html"],
//            ["http://www.zbjuran.com/mei/qingchun/", "http://www.zbjuran.com/mei/qingchun/list_14_2.html"],
//        ];

        if(empty($urlArr))
            die('no page');

        $model = D('Gallery');
        $web = 'http://www.zbjuran.com';
        $usedUrl = file_get_contents('./Public/Home/data/usedUrl.php');//已使用的url
        if($usedUrl)
            $usedUrl = json_decode($usedUrl, true);

        foreach($urlArr as $k => $v) {
            $num = 1;
            foreach ($v as $k1 => $v1) {
                if(in_array($v1, $usedUrl[2]))
                    continue;

                if($num == 2)
                    continue;

//                var_dump($v1);

                $contents = Tool::url_get_contents($v1);
                if ($contents) {
                    //转码
                    $contents = mb_convert_encoding($contents, "UTF-8", "GBK");
                    //截取图集链接
                    preg_match_all('/ <div class="name"><a target="_blank" href="(.*?)"/is', $contents, $arr);
                    //获取标题
                    preg_match_all('/(title)=("[^"]*")/i', $contents, $title);
                    //得到每页的图集，分别到图集获取图片
                    if ($arr[1]) {
                        foreach ($arr[1] as $k2 => $v2) {
                            $v2 = $web.$v2;
                            $picContent = Tool::url_get_contents($v2);
                            $picContent = mb_convert_encoding($picContent, "UTF-8", "GBK");
                            //获取图片链接
                            preg_match_all('/(src)=("[^"]*")/i', $picContent, $picMain);

                            $picArr = [];
                            //有图片的话
                            if(isset($picMain[2][3]) && end(explode('.', trim($picMain[2][3], '"'))) == 'jpg'){
                                $data = [];
                                $data['class_id']    = $url[$k]['class_id'];
                                $data['editor']      = 1;
                                $data['title']       = htmlspecialchars_decode(trim($title[2][$k2+1], '"'));
                                $data['short_title'] = htmlspecialchars_decode(trim($title[2][$k2+1], '"'));
                                $data['remark']      = htmlspecialchars_decode(trim($title[2][$k2+1], '"'));
                                $data['add_time']    = NOW_TIME;
                                $data['status']      = 0;
                                $rs_id = $model->add($data);

                                $picOne = Tool::url_get_contents($web.trim($picMain[2][3], '"'));
                                $return = D('Uploads')->uploadFileBase64(base64_encode($picOne), "gallery", '', 1, $rs_id);
                                $size = getimagesize($web.trim($picMain[2][3], '"'));
                                $picArr[1] = $return['url'].'&size='.$size[0].'X'.$size[1];

                                for ($i = 2; $i < 31; $i++) {
                                    $picUrl = str_replace(".html", '_'.$i.'.html', $v2);

                                    $img = Tool::url_get_contents($picUrl);
                                    preg_match_all('/(src)=("[^"]*")/i', $img, $imgMain);

                                    if($imgMain[2][3]){
                                        $picAll = Tool::url_get_contents($web.trim($imgMain[2][3], '"'));
                                        $return = D('Uploads')->uploadFileBase64(base64_encode($picAll), "gallery", '', $i, $rs_id);

                                        if($return['status'] == 1) {
                                            $picArr[] = $return['url'];
                                        }else{
                                            continue;
                                        }
                                    } else {
                                        break;
                                    }
                                }

                                if ($picArr) {
                                    $model->where(['id' => $rs_id])->save(['img_array' => json_encode($picArr),  'update_time' => NOW_TIME, 'status' => 1]);
                                }
//                                var_dump($v2, $picArr);die;
                            }else{
                                break;
                            }
                        }
                    } else {
                        break;
                    }
                } else {
                    break;
                }

//                $all[] = $v1;

                if($usedUrl){
                    $usedUrl[2][] = $v1;
                    file_put_contents('./Public/Home/data/usedUrl.php', json_encode($usedUrl));
                }else{
                    $usedUrl = [];
                    $usedUrl[2][] = $v1;
                    file_put_contents('./Public/Home/data/usedUrl.php', json_encode($usedUrl));
                }
                $num++;
            }
        }
//        var_dump($all);
        die('ok');
    }

    public function getBeautyPic3(){
        //性感美女（对应泳装女神）,清纯美女，校花美女，车模美女，美女特辑，明星风采
        $url = [
            ['class_id' => 17, 'url' => 'https://www.4493.com/xingganmote/',   'class_name' => 'index-'],
            ['class_id' => 21, 'url' => 'https://www.4493.com/gaoqingmeinv/',  'class_name' => 'index-'],
            ['class_id' => 21, 'url' => 'https://www.4493.com/weimeixiezhen/',  'class_name' => 'index-'],
            ['class_id' => 5,  'url' => 'https://www.4493.com/motemeinv/',      'class_name' => 'index-'],
            ['class_id' => 18, 'url' => 'https://www.4493.com/siwameitui/',     'class_name' => 'index-'],
            ['class_id' => 4,  'url' => 'https://www.4493.com/mingxingxiezhen/','class_name' => 'index-'],
        ];

        $urlArr = file_get_contents('./Public/Home/data/urlArr3.php');
        if($urlArr){
            $urlArr = json_decode($urlArr, true);
        }else{
            $urlArr = [];
            foreach($url as $k => $v){
                $urlArr[$k] = $this->getAllUrl($v['url'], $v['class_name'], '.htm', true);
            }

            file_put_contents('./Public/Home/data/urlArr3.php', json_encode($urlArr));
        }

//        var_dump($urlArr);die;
//        $urlArr = [
//            ["http://www.zbjuran.com/mei/xinggan/", "http://www.zbjuran.com/mei/xinggan/list_13_2.html"],
//            ["http://www.zbjuran.com/mei/qingchun/", "http://www.zbjuran.com/mei/qingchun/list_14_2.html"],
//        ];

        if(empty($urlArr))
            die('no page');

        $model = D('Gallery');
        $web = 'https://www.4493.com';
        $usedUrl = file_get_contents('./Public/Home/data/usedUrl.php');//已使用的url
        if($usedUrl)
            $usedUrl = json_decode($usedUrl, true);

        foreach($urlArr as $k => $v) {
            $num = 1;
            foreach ($v as $k1 => $v1) {
                if(in_array($v1, $usedUrl[3]))
                    continue;

                if($num == 2)
                    continue;

//                var_dump($v1);

                $contents = Tool::url_get_contents($v1);
                if ($contents) {
                    //转码
                    $contents = mb_convert_encoding($contents, "UTF-8", "GBK");
                    //主体
                    preg_match_all('/<ul class="clearfix">(.*?)<\/ul>/is', $contents, $content);
                    //截取图集链接，首页和后面的页面结构不同
                    if(stripos($v1, 'htm')){
                        preg_match_all('/<a target="_blank" href="(.*?)">/i', $content[0][0], $arr);
                    }else{//首页有间隔
                        preg_match_all('/<a target="_blank" href="(.*?)" >/i', $content[0][0], $arr);
                    }

                    //获取标题
                    preg_match_all('/<span>(.*?)<\/span>/i', $content[0][0], $title);

                    $picArr = [];
                    //得到每页的图集，分别到图集获取图片
                    if ($arr[1]) {
                        foreach ($arr[1] as $k2 => $v2) {
                            $v2 = $web.$v2;
                            $picContent = Tool::url_get_contents($v2);
                            $picContent = mb_convert_encoding($picContent, "UTF-8", "GBK");
                            //获取图片链接
                            preg_match_all('/<p><img src="(.*?)"/is', $picContent, $picMain);
                            //获取图片的最大页码
                            preg_match_all('/<span id="allnum">(.*?)<\/span>/is', $picContent, $maxNum);
                            //有图片的话
                            if(isset($picMain[1][0])){
                                $data = [];
                                $data['class_id']    = $url[$k]['class_id'];
                                $data['editor']      = 1;
                                $data['title']       = htmlspecialchars_decode(trim($title[1][$k2], '"'));
                                $data['short_title'] = htmlspecialchars_decode(trim($title[1][$k2], '"'));
                                $data['remark']      = htmlspecialchars_decode(trim($title[1][$k2], '"'));
                                $data['add_time']    = NOW_TIME;
                                $data['status']      = 0;
                                $rs_id = $model->add($data);

                                $picOne = Tool::url_get_contents(trim($picMain[1][0], '"'));
                                $return = D('Uploads')->uploadFileBase64(base64_encode($picOne), "gallery", '', 1, $rs_id);
                                $size = getimagesize(trim($picMain[1][0], '"'));
                                $picArr[1] = $return['url'].'&size='.$size[0].'X'.$size[1];

                                $picUrl = '';
                                for ($i = 2; $i <= (int)$maxNum[1][0]; $i++) {
                                    if($i == 2){
                                        $picUrl = $v2;
                                    }else{
                                        $picUrl = $picUrl;
                                    }
                                    $last = $i-1;
                                    $picUrl = str_replace($last.'.htm', $i.'.htm', $picUrl);

                                    $img = Tool::url_get_contents($picUrl);
                                    preg_match('/<p><img src="(.*?)"/is', $img, $imgMain);

                                    if($imgMain[1]){
                                        $picAll = Tool::url_get_contents(trim($imgMain[1], '"'));
                                        $return = D('Uploads')->uploadFileBase64(base64_encode($picAll), "gallery", '', $i, $rs_id);

                                        if($return['status'] == 1) {
                                            $picArr[] = $return['url'];
                                        }else{
                                            continue;
                                        }
                                    }else{
                                        break;
                                    }
                                }

                                if ($picArr) {
                                    $model->where(['id' => $rs_id])->save(['img_array' => json_encode($picArr), 'update_time' => NOW_TIME, 'status' => 1]);
                                }
//                                var_dump($v2, $picArr);die;
                            }else{
                                break;
                            }
                        }
                    } else {
                        break;
                    }
                } else {
                    break;
                }

                if($usedUrl){
                    $usedUrl[3][] = $v1;
                    file_put_contents('./Public/Home/data/usedUrl.php', json_encode($usedUrl));
                }else{
                    $usedUrl = [];
                    $usedUrl[3][] = $v1;
                    file_put_contents('./Public/Home/data/usedUrl.php', json_encode($usedUrl));
                }
                $num++;
//                $all[] = $v1;
            }
        }
//        var_dump($all);
        die('ok');
    }

    /**
     * 检查远程图片或文件或url是否存在
     * @param $url
     * @return bool
     */
    function remote_file_exists($url, $isHttps=false)
    {
        $curl = curl_init($url);
        // 不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        // 发送请求
        if($isHttps){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $result = curl_exec($curl);
        $found = false;
        // 如果请求没有发送失败
        if ($result !== false) {
            // 再检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                $found = true;
            }
        }

        curl_close($curl);
        return $found;
    }

    /**
     * 获取网站分类所有图集链接
     */
    public function getAllUrl($url, $class, $suffix='.html', $isHttps=false){
        $res[0] = $url;
        for($i=2; $i<100; $i++){
            $urlPage = $url.$class.$i.$suffix;
            if($this->remote_file_exists($urlPage, $isHttps)){
                $res[$i-1] = $urlPage;
            }else{
                break;
            }
        }

        return $res;
    }

    /**
     * 获取网站分类所有图集链接
     */
    public function getAllUrl2($url, $className, $lastPage=99, $suffix='.html'){
        $res[0] = $url;
        for($i=2; $i<=$lastPage; $i++){
            $urlPage = $url.$className.$i.$suffix;
            $res[$i-1] = $urlPage;
        }

        return $res;
    }

    /**
     * hzl
     * 美女图片采集
     * http://www.mm131.com/
     */
    function getPic(){
        $queryPage = I('page') <= 1 ? 1 : I('page');//默认采集所有分类下的第一页，传page可自定义采集某一页
        $queryNum = I('num') <= 1 ? 1 : I('num');//默认采集当前类页的num个图集
        $startTime = date('Y-m-d H:i:s');
        $web = 'http://www.mm131.com/';

        //默认只采集第一页
        $urlArr = [
            ['class_id' => 17, 'url' => $queryPage == 1 ? $web . 'xinggan/' : $web . 'xinggan/list_6_' . $queryPage . '.html'],
            ['class_id' => 21, 'url' => $queryPage == 1 ? $web . 'qingchun/' : $web . 'qingchun/list_1_' . $queryPage . '.html'],
            ['class_id' => 21, 'url' => $queryPage == 1 ? $web . 'xiaohua/' : $web . 'xiaohua/list_2_' . $queryPage . '.html'],
            ['class_id' => 5, 'url' => $queryPage == 1 ? $web . 'chemo/' : $web . 'chemo/list_3_' . $queryPage . '.html'],
            ['class_id' => 18, 'url' => $queryPage == 1 ? $web . 'qipao/' : $web . 'qipao/list_4_' . $queryPage . '.html'],
            ['class_id' => 4, 'url' => $queryPage == 1 ? $web . 'mingxing/' : $web . 'mingxing/list_5_' . $queryPage . '.html'],
        ];

        //原站图片分类、和每页图片
        $model = D('Gallery');

        //已抓取的图集
        $hisArr = $model->master(true)
            ->where(['class_id' => ['in', [4, 5, 17, 18, 21]], 'status' => 1, 'capture_url' => ['like', $web.'%']])
            ->group('capture_url')
            ->getField('capture_url', true);

        $dlImgArr = [];

        //开始采集内容
        foreach($urlArr as $k => $v) {
            $rules = [
                'link' => ['a', 'href'],
                'title'=> ['a>img', 'alt'],
                'img'  => ['a>img', 'src'],
            ];
            $range = '.list-left>dd:lt(19)';

            $classQueryNum[$v['url']] = 0;

            $data = QueryList::Query($v['url'], $rules, $range)->getData(function ($item) {
                return $item;
            });

            //过滤已经采集过的图集，并且只采集指定数量的图集
            foreach($data as $imgk => $imgv){
                if(!in_array($imgv['link'], $hisArr) && $classQueryNum[$v['url']] < $queryNum){
                    $imgv['class_id'] = $v['class_id'];
                    $dlImgArr[] = $imgv;
                    $hisArr[] = $imgv['link'];
                    $classQueryNum[$v['url']] += 1;
                }
            }
        }

        if(I('mode') == 'test'){
              dump($dlImgArr);exit;
        }

        //开始下载图片,（可以考虑用任务队列下载）
        foreach($dlImgArr as $dlk => $dlv){
            if(!in_array($dlv['link'], $hisArr))
                continue;

            $logStr = '当前要采集的图集：' . $dlv['link'] . '<br/>';
            //入库
            $title = htmlspecialchars_decode($dlv['title']);
            $data['class_id']   = $dlv['class_id'];
            $data['title']    = $title;
            $data['short_title'] = $title;
            $data['remark']   = $title;
            $data['add_time'] = time();
            $data['editor']   = 1;
            $data['status']   = 0;
            $data['capture_url'] = trim($dlv['link']);
            $rs_id = $model->add($data);
            $picName = substr($dlv['img'], 0, strripos($dlv['img'], '/') + 1);

            //图集遍历，手动设定抓取30张
            $picArr = [];
            for ($i = 1; $i < 31; $i++) {
                $picUrl = "{$picName}{$i}.jpg";
                $img = Tool::url_get_contents($picUrl, ['Referer: http://www.mm131.com/']);

                //直接请求，判断404
                if (stripos($img, 'Not Found') || stripos($img, 'html')) {
                    break;
                } else {
                    $thumb = $i == 1 ? "[[240,240,\"1_240\"]]" : null;
                    $return = D('Uploads')->uploadFileBase64(base64_encode($img), "gallery", '', $i, $rs_id, $thumb);

                    $logStr .= "<br/>采集第{$i}张图片状态：". json_encode($return) . '<br/>';
                    if ($return['status'] == 1) {
                        if ($i == 1) {
                            $serverImg = $head = Think\Tool\Tool::imagesReplace($return['url']);
                            $size = getimagesize($serverImg);
                            $return['url'] .= '&size=' . $size[0] . 'X' . $size[1];
                        }
                        $picArr[$i] = (string)$return['url'];
                    }
                }
            }

            echo $logStr;

            if ($picArr) {
                $up = [
                    'img_array' => json_encode($picArr),
                    'update_time' => time(),
                    'status' => 1
                ];
                $model->where(['id' => $rs_id])->save($up);
            }else{
                $model->where(['id' => $rs_id])->delete();
            }
        }

        $ids = $model
            ->master(true)
            ->where(['class_id' => 17, 'add_time' => ['BETWEEN', [strtotime('00:00:00'), strtotime('23:59:59')]], 'status' => 1])
            ->order('id asc')
            ->limit(5)
            ->getField('id', true);

        $model->where(['id' => ['in', $ids]])->save(['home_recommend' => 1]);

        die('ok！开始时间：'.$startTime.'， 结束时间：'.date('Y-m-d H:i:s'));
    }

    /**
     * http://www.zbjuran.com/
     */
    function getPic2(){
        $startTime = date('Y-m-d H:i:s');
        $url = [
            ['class_id' => 17, 'url' => 'http://www.zbjuran.com/mei/xinggan/',  'class_name' => 'list_13_'],
            ['class_id' => 21, 'url' => 'http://www.zbjuran.com/mei/qingchun/', 'class_name' => 'list_14_'],
            ['class_id' => 21, 'url' => 'http://www.zbjuran.com/mei/xiaohua/',  'class_name' => 'list_15_'],
            ['class_id' => 4,  'url' => 'http://www.zbjuran.com/mei/mingxing/', 'class_name' => 'list_16_'],
        ];

        $urlArr = getWebConfig('urlArr2');
        if(empty($urlArr)){
            $urlArr = [];
            $rules = [
                'link' => array('.pages a', 'href'),
            ];

            //list_13_71.html
            foreach($url as $k => $v){
                if($v['class_id'] == 4) {
                    $last = 24;
                }else{
                    $data = QueryList::Query($v['url'], $rules)->data;
                    $last = explode('_', explode('.', $data[count($data)-1]['link'])[0])[2];
                    unset($data);
                }

                $urlArr[$k] = $this->getAllUrl2($v['url'], $v['class_name'], $last);
            }

            M('Config')->where(['sign' => 'urlArr2'])->save(['config' => json_encode($urlArr)]);
        }

//var_dump($urlArr);die;
        $usedPage = getWebConfig('usedPage2');
/*
        $urlArr = [
            ["http://www.zbjuran.com/mei/xinggan/", "http://www.zbjuran.com/mei/xinggan/list_13_2.html"],
            ["http://www.zbjuran.com/mei/qingchun/", "http://www.zbjuran.com/mei/qingchun/list_14_2.html"],
        ];
*/
        $model = D('Gallery');
        $rules = [
            'link' => ['a', 'href'],
            'title'=> ['a', 'title'],
        ];
        $range = '.name';
        //入库成功的page
        $newUsedPage = [];
        //数据库已有的链接
        $web = 'http://www.zbjuran.com';
        $dataArr = $model->master(true)->where(['class_id' => ['in', [4,5,17,18,21]], 'status' => 1, 'capture_url' => ['like', $web.'%']])->group('capture_url')->getField('capture_url', true);

        //采集某页面所有的图片
        foreach($urlArr as $k => $v) {
            $class_id = $url[$k]['class_id'];
            //多线程，待用
//            $this->multiRun($v, $class_id, $model);

            $num = 1;
            foreach ($v as $k1 => $v1) {
                //跳过已经入库
                if(in_array($v1, $usedPage)) continue;

                //只运行一页
                if($num == 2) continue;

                $data = QueryList::Query($v1, $rules, $range)
                    ->getData(function ($item) use ($class_id, $model, $dataArr, $web) {
                        //避免相同不同页数引起的链接一致
                        if(!in_array($web.$item['link'], $dataArr)) {
                            $data = [];
                            $data['class_id']    = $class_id;
                            $data['editor']      = 1;
                            $data['title']       = htmlspecialchars_decode($item['title']);
                            $data['short_title'] = htmlspecialchars_decode($item['title']);
                            $data['remark']      = htmlspecialchars_decode($item['title']);
                            $data['add_time']    = time();
                            $data['status']      = 0;
                            $data['capture_url'] = $web.$item['link'];
                            $rs_id = $model->add($data);

                            $picArr = [];
                            $picUrl = $web.$item['link'];
                            for ($i = 1; $i < 31; $i++) {
                                if($i == 1){
                                    $picUrl = $picUrl;
                                }else if($i == 2){
                                    $picUrl = str_replace(".html", '_'.$i.'.html', $picUrl);
                                }else{
                                    $picUrl = explode('_', $picUrl)[0].'_'.$i.'.html';
                                }

                                $imgMain = QueryList::Query($picUrl, ['img' => ['img', 'src']], '.picbox')->data;
                                //没有就跳出
                                if($imgMain[0]['img']){
                                    $img = Tool::url_get_contents($web.$imgMain[0]['img']);
                                    $thumb = $i == 1 ? "[[240,240,\"1_240\"]]" : null;
                                    $return = D('Uploads')->uploadFileBase64(base64_encode($img), "gallery", '', $i, $rs_id, $thumb);

                                    if($return['status'] == 1) {
                                        if($i == 1){
                                            $size = getimagesize($web.$imgMain[0]['img']);
                                            $return['url'] = $return['url'].'&size='.$size[0].'X'.$size[1];
                                        }

                                        $picArr[$i] = $return['url'];
                                    }else {
                                        continue;
                                    }
                                }else{
                                    break;
                                }
                            }

                            if ($picArr) {
                                $model->where(['id' => $rs_id])->save(['img_array' => json_encode($picArr),  'update_time' => time(), 'status' => 1]);
                            }

                            $item['id'] = $rs_id;
                        }else{
                            $item['id'] = 0;
                        }

                        return $item;
                    });

                print_r($data);
                $newUsedPage[] = $v1;
                $num++;
            }
        }

        print_r($newUsedPage);
        if($usedPage){
            $usedPage = array_merge($usedPage, $newUsedPage);
        }else{
            $usedPage = $newUsedPage;
        }

        M('Config')->where(['sign' => 'usedPage2'])->save(['config' => json_encode($usedPage)]);

        $ids = $model->where(['class_id' => 17, 'add_time' => ['BETWEEN', [strtotime('00:00:00'), strtotime('23:59:59')]], 'status' => 1])->order('id asc')->limit(5)->getField('id', true);
        $model->where(['id' => ['in', $ids]])->save(['home_recommend' => 1]);
        unset($urlArr, $dataArr, $usedPage);

        die('ok！开始时间：'.$startTime.'， 结束时间：'.date('Y-m-d H:i:s'));
    }

    /**
     * https://www.4493.com/
     */
    function getPic3(){
        $startTime = date('Y-m-d H:i:s');
        //性感美女（对应泳装女神）,清纯美女，校花美女，车模美女，美女特辑，明星风采
        $url = [
            ['class_id' => 17, 'url' => 'https://www.4493.com/xingganmote/',   'class_name' => 'index-',  'lastPage' => 400],
            ['class_id' => 21, 'url' => 'https://www.4493.com/gaoqingmeinv/',  'class_name' => 'index-',  'lastPage' => 282],
            ['class_id' => 21, 'url' => 'https://www.4493.com/weimeixiezhen/',  'class_name' => 'index-', 'lastPage' => 330],
            ['class_id' => 5,  'url' => 'https://www.4493.com/motemeinv/',      'class_name' => 'index-', 'lastPage' => 230],
            ['class_id' => 18, 'url' => 'https://www.4493.com/siwameitui/',     'class_name' => 'index-', 'lastPage' => 280],
            ['class_id' => 4,  'url' => 'https://www.4493.com/mingxingxiezhen/','class_name' => 'index-', 'lastPage' => 390],
        ];

        $urlArr = getWebConfig('urlArr3');
        if(empty($urlArr)){
            $urlArr = [];
            //https://www.4493.com/xingganmote/index-2.htm
            foreach($url as $k => $v){
                $urlArr[$k] = $this->getAllUrl2($v['url'], $v['class_name'], $v['lastPage'], '.htm');
            }

            M('Config')->where(['sign' => 'urlArr3'])->save(['config' => json_encode($urlArr)]);
        }

//var_dump($urlArr);die;
        $usedPage = getWebConfig('usedPage3');
/*
        $urlArr = [
            ["https://www.4493.com/xingganmote/", "https://www.4493.com/xingganmote/index-2.htm"],
            ["https://www.4493.com/gaoqingmeinv/", "https://www.4493.com/gaoqingmeinv/index-2.htm"],
        ];
*/
        $model = D('Gallery');
        $rules = [
            'link' => ['.piclist>ul>li>a', 'href'],
            'title'=> ['.piclist>ul>li>a>span', 'text'],
        ];
        $range = '';
        //入库成功的page
        $newUsedPage = [];
        //数据库已有的链接
        $web = 'https://www.4493.com';
        $dataArr = $model->master(true)->where(['class_id' => ['in', [4,5,17,18,21]], 'status' => 1, 'capture_url' => ['like', $web.'%']])->group('capture_url')->getField('capture_url', true);

        //采集某页面所有的图片
        foreach($urlArr as $k => $v) {
            $class_id = $url[$k]['class_id'];
            //多线程，待用
//            $this->multiRun($v, $class_id, $model);

            $num = 1;
            foreach ($v as $k1 => $v1) {
                //跳过已经入库
                if(in_array($v1, $usedPage)) continue;

                //只运行一页
                if($num == 2) continue;

                $data = QueryList::Query($v1, $rules, $range, 'UTF-8')
                        ->getData(function ($item) use ($class_id, $model, $dataArr, $web) {
                        //避免相同不同页数引起的链接一致
                        if(!in_array($web.$item['link'], $dataArr)) {
                            $data = [];
                            $data['class_id']    = $class_id;
                            $data['editor']      = 1;
                            $data['title']       = htmlspecialchars_decode($item['title']);
                            $data['short_title'] = htmlspecialchars_decode($item['title']);
                            $data['remark']      = htmlspecialchars_decode($item['title']);
                            $data['add_time']    = time();
                            $data['status']      = 0;
                            $data['capture_url'] = $web.$item['link'];
                            $rs_id = $model->add($data);

                            $picArr = [];
                            $picUrl = $web.$item['link'];
                            for ($i = 1; $i < 31; $i++) {
                                if($i == 1){
                                    $picUrl = $picUrl;
                                }else{
                                    $last = $i-1;
                                    $picUrl = str_replace($last.'.htm', $i.'.htm', $picUrl);
                                }

                                $img = Tool::url_get_contents($picUrl);
                                preg_match('/<p><img src="(.*?)"/is', $img, $imgMain);
                                //没有就跳出
                                if($imgMain[1]){
                                    $img = Tool::url_get_contents($imgMain[1]);
                                    $thumb = $i == 1 ? "[[240,240,\"1_240\"]]" : null;
                                    $return = D('Uploads')->uploadFileBase64(base64_encode($img), "gallery", '', $i, $rs_id, $thumb);

                                    if($return['status'] == 1) {
                                        if($i == 1){
                                            $size = getimagesize($imgMain[1]);
                                            $return['url'] = $return['url'].'&size='.$size[0].'X'.$size[1];
                                        }

                                        $picArr[$i] = $return['url'];
                                    }else {
                                        continue;
                                    }
                                }else{
                                    break;
                                }
                            }

                            if ($picArr) {
                                $model->where(['id' => $rs_id])->save(['img_array' => json_encode($picArr),  'update_time' => time(), 'status' => 1]);
                            }

                            $item['id'] = $rs_id;
                        }else{
                            $item['id'] = 0;
                        }

                        return $item;
                    });

                print_r($data);
                $newUsedPage[] = $v1;
                $num++;
            }
        }

        print_r($newUsedPage);
        if($usedPage){
            $usedPage = array_merge($usedPage, $newUsedPage);
        }else{
            $usedPage = $newUsedPage;
        }

        M('Config')->where(['sign' => 'usedPage3'])->save(['config' => json_encode($usedPage)]);

        $ids = $model->where(['class_id' => 17, 'add_time' => ['BETWEEN', [strtotime('00:00:00'), strtotime('23:59:59')]], 'status' => 1])->order('id asc')->limit(5)->getField('id', true);
        $model->where(['id' => ['in', $ids]])->save(['home_recommend' => 1]);
        unset($urlArr, $dataArr, $usedPage);

        die('ok！开始时间：'.$startTime.'， 结束时间：'.date('Y-m-d H:i:s'));
    }
    /**
     * 多线程采集图片
     * @param $urls
     * @param $class_id
     * @param $model
     */
    function multiRun($urls, $class_id, $model){
        QueryList::run('Multi',[
            'list' => $urls,
            'curl' => [
                'opt' => array(
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_AUTOREFERER => true,
                ),
                //设置线程数
                'maxThread' => 100,
                //设置最大尝试数
                'maxTry' => 3
            ],
            'success' => function($html)use ($class_id, $model){
                //采集规则
                $reg = [
                    //采集图片链接
                    'link' => array('a', 'href'),
                    //采集图片标题
                    'title' => array('a>img', 'alt'),
                ];
                //采集范围
                $range = '.list-left>dd:lt(19)';

                $data = QueryList::Query($html['content'], $reg, $range)
                        ->getData(function ($item) use ($class_id, $model){
                            $data = [];
                            $data['class_id']    = $class_id;
                            $data['editor']      = 1;
                            $data['title']       = htmlspecialchars_decode($item['title']);
                            $data['short_title'] = htmlspecialchars_decode($item['title']);
                            $data['remark']      = htmlspecialchars_decode($item['title']);
                            $data['add_time']    = NOW_TIME;
                            $data['status']      = 0;
                            $data['capture_url'] = $item['link'];

                            $rs_id = $model->add($data);
                            $picName = explode('.', end(explode('/', $item['link'])))[0];
                            $picArr = [];
                            for ($i = 1; $i < 31; $i++) {
                                $picUrl = "http://img1.mm131.com/pic/{$picName}/{$i}.jpg";

                                $img = Tool::url_get_contents($picUrl);
                                //直接请求，判断404
                                if(stripos($img, 'Not Found')){
                                    break;
                                } else {
                                    $return = D('Uploads')->uploadFileBase64(base64_encode($img), "gallery", '', $i, $rs_id);
                                    if($return['status'] == 1) {
                                        if ($i == 1) {
                                            $size = getimagesize($picUrl);
                                            $return['url'] .= '&size=' . $size[0] . 'X' . $size[1];
                                        }

                                        $picArr[$i] = $return['url'];
                                    }else{
                                        continue;
                                    }
                                }
                            }

                            if ($picArr) {
                                $model->where(['id' => $rs_id])->save(['img_array' => json_encode($picArr),  'update_time' => NOW_TIME, 'status' => 1]);
                                $item['rs'] = 'ok';
                            }else{
                                $item['rs'] = 'fail';
                            }

                            $item['id'] = $rs_id;
                            return $item;
                });
                //打印结果
                print_r($data);
            }
        ]);
    }

    //采集球探今日推荐赛事，不限联盟级别
    public function win007Game(){
        $url = 'http://guess.win007.com/';
        $rules = [
            'game_id' => ['.popupGuessTD .info', 'scheduleid'],
        ];
        $data = QueryList::Query($url, $rules)->getData(function ($item) {
            return !empty($item['game_id']) ? $item['game_id'] : '';
        });
        $gameIdArr = array_filter($data);
        if(!empty($gameIdArr)){
            $rs = M('GameFbinfo')->where(['game_id'=>['in',$gameIdArr]])->save(['is_show'=>1]);
            echo $rs;
        }else{
            echo '无赛事推荐';
        }
        
    }

}