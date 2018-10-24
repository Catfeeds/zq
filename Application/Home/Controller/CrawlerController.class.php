<?php
set_time_limit(0);
@ini_set('implicit_flush',1);
ob_implicit_flush(1);
@ob_end_clean();
/**
 * @爬虫抓取程序
 */
use Think\Controller;
use Think\Tool\Tool;
vendor('QueryList.autoload');
use QL\QueryList;

class CrawlerController extends CommonController
{
    protected function _initialize() 
    {
        //$this->statement = '<p class="statement">'.C('news_statement').'</p>';
        $this->statement = '';
    }

    public function sitemap(){
        $sitemap = "<?xml version='1.0' encoding='utf-8'?>\r\n<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\r\n";
        $sitemap .= "<url>\r\n".
                        "<loc>https://www.qqty.com/</loc>\r\n".
                        "<priority>1.0</priority>\r\n".
                        "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                        "<changefreq>always</changefreq>\r\n".
                    "</url>\r\n";
        $sitemap .= "<url>\r\n".
                        "<loc>https://www.qqty.com/App/introduce.html</loc>\r\n".
                        "<priority>0.8</priority>\r\n".
                        "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                        "<changefreq>daily</changefreq>\r\n".
                    "</url>\r\n";
        $sitemap .= "<url>\r\n".
                        "<loc>https://www.qqty.com/Copyright/map.html</loc>\r\n".
                        "<priority>0.9</priority>\r\n".
                        "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                        "<changefreq>daily</changefreq>\r\n".
                    "</url>\r\n";
        $sitemap .= "<url>\r\n".
                        "<loc>https://bf.qqty.com/</loc>\r\n".
                        "<priority>0.9</priority>\r\n".
                        "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                        "<changefreq>daily</changefreq>\r\n".
                    "</url>\r\n";
        $sitemap .= "<url>\r\n".
                        "<loc>https://bf.qqty.com/lanqiu.html</loc>\r\n".
                        "<priority>0.9</priority>\r\n".
                        "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                        "<changefreq>daily</changefreq>\r\n".
                    "</url>\r\n";
        $sitemap .= "<url>\r\n".
                        "<loc>https://data.qqty.com/</loc>\r\n".
                        "<priority>0.9</priority>\r\n".
                        "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                        "<changefreq>daily</changefreq>\r\n".
                    "</url>\r\n";
        //资讯专题与栏目页
        $newsClass = getPublishClass(0);
        foreach ($newsClass as $k => $v) {
            if(empty($v['path']) && empty($v['domain'])){
                continue;
            }
            if($v['pid'] == 0 && $v['domain'] != ''){
                $url = U('@'.$v['domain']);
                $sitemap .= "<url>\r\n".
                                "<loc>".$url."</loc>\r\n".
                                "<priority>0.9</priority>\r\n".
                                "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                                "<changefreq>daily</changefreq>\r\n".
                            "</url>\r\n";
            }elseif ($v['pid'] != 0) {
                $url = newsClassUrl($v['id'],$newsClass);
                $sitemap .= "<url>\r\n".
                                "<loc>".$url."</loc>\r\n".
                                "<priority>0.9</priority>\r\n".
                                "<lastmod>".date('Y-m-d',time())."</lastmod>\r\n".
                                "<changefreq>daily</changefreq>\r\n".
                            "</url>\r\n";
            }
        }
        //当前一天资讯
        $add_time = strtotime(date(Ymd))-86400;
        $news = M('PublishList')->field("id,add_time,class_id")->where(['status'=>1,'add_time'=>['gt',$add_time]])->order('add_time desc')->select();
        $classArr = getPublishClass(0);
        foreach($news as $k=>$v)
        {
            $sitemap .= "<url>\r\n".
                            "<loc>".newsUrl($v['id'],$v['add_time'],$v['class_id'],$classArr)."</loc>\r\n".
                            "<priority>0.7</priority>\r\n".
                            "<lastmod>".date('Y-m-d',$v['add_time'])."</lastmod>\r\n".
                            "<changefreq>daily</changefreq>\r\n".
                        "</url>\r\n";
        }
       
        $sitemap .= '</urlset>';
        header("Content-type: text/xml");
        echo $sitemap;
        die;
    }

    //网易资讯抓取
    public function wangyi(){
        exit();
        $url = [
            '13' => 'http://sports.163.com/special/000587PO/newsdata_epl_index.js',   //英超
            '14' => 'http://sports.163.com/special/000587PP/newsdata_laliga_index.js',//西甲
            '17' => 'http://sports.163.com/special/000587PN/newsdata_world_yj.js',    //意甲
            '15' => 'http://sports.163.com/special/000587PN/newsdata_world_dj.js',    //德甲
            '4'  => 'http://sports.163.com/special/000587PK/newsdata_nba_index.js',   //NBA
            '3'  => 'http://sports.163.com/special/000587PL/newsdata_cba_index.js',   //CBA
            '27' => 'http://sports.163.com/gjb/',                                     //欧冠
            '18' => 'http://sports.163.com/zc/',                                      //中超
            '28' => 'http://sports.163.com/acl/',                                     //亚冠
        ];

        foreach ($url as $k => $v) {
            $urlCheck = [];
            if(in_array($k, [27,18,28])){
                //网页抓取url
                $urlCheck = QueryList::Query($v,array(
                    'url' => array('.news_item>h3>a','href')
                ))->getData(function($item){
                    return $item['url'];
                });
            }else{
                //js抓取url
                $contents = iconv("GBK", "UTF-8", Tool::url_get_contents($v)); //转码
                $contents = ltrim($contents, "data_callback(");
                $contents = rtrim($contents, ")");
                $urlArr= json_decode($contents,true);
                foreach ($urlArr as $kk => $vv) {
                    $urlCheck[] = $vv['docurl'];
                }
            }
            foreach ($urlCheck as $key => $value) {
                if(stripos($value, 'photoview')){
                    //去掉图库url
                    unset($urlCheck[$key]);
                }
            }

            //已抓取网址
            $capture_url = M('PublishList')->master(true)->where(['capture_url'=>['in',$urlCheck]])->getField('capture_url',true);

            //去掉已抓取网址
            foreach ($urlCheck as $kkk => $vvv) {
                if(in_array($vvv, $capture_url)){
                    unset($urlCheck[$kkk]);
                }
            }

            if(empty($urlCheck)) continue;

            $this->CrawlerWangYi($urlCheck,$k);
        }
        exit;
    }

    //网易
    public function CrawlerWangYi($urlCheck,$class_id)
    {
        exit();
        //多线程捉取
        QueryList::run('Multi',[
            //待采集链接集合
            'list' => $urlCheck,
            'curl' => [
                'opt' => array(
                    //这里根据自身需求设置curl参数
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
            'success' => function($res)use($class_id)
            {
                $docurl = $res['info']['url'];
                $html = iconv("GBK", "UTF-8", $res['content']); 
                $data = QueryList::Query($html,[
                    'title'        => array('#epContentLeft>h1:eq(0)','text'), //标题
                    'add_time'     => ['.post_time_source','text','-a'],       //时间
                    'label'        => ['meta[name="keywords"]','content'],     //标签
                    'seo_keys' => ['meta[name="description"]','content'],  //seo关键词
                    'content'      => array('.post_body','html','-.post_topshare_wrap -.nph_gallery -.post_btmshare -.gg200x300 a -.ep-source -.post_end_ad -script -style') //内容
                ])->getData(function($item)use($docurl,$class_id){
                    $item['short_title'] = $item['title']; //短标题
                    $item['source']      = '网易体育';         //来源
                    $add_time = preg_match_all('/\d+/',$item['add_time'],$arr);
                    $item['add_time']    = strtotime(implode('', $arr[0]));
                    $item['update_time'] = $item['add_time']; //时间
                    $item['app_time']    = $item['add_time']; //时间
                    $item['class_id']    = $class_id;      //分类
                    $item['capture_url'] = $docurl;        //来源地址
                    $item['author']      = 1;              //默认作者admin
                    $item['status']      = 0;              //默认待发布
                    $content             = $item['content'];
                    if(stripos($content, '版权声明')){
                        return [];
                    }
                    $item['content'] = $this->HandleContent($content);
                    return $item;
                });
                // echo ($data[0]['content']);
                // die; 
                if($data[0]['title']){
                    if(M('PublishList')->add($data[0])){
                        dump("采集成功：".$data[0]['title'].';分类id: '.$class_id);
                    }else{
                        dump("采集失败：".$docurl.';分类id: '.$class_id);
                    }
                }else{
                    dump("采集失败：".$docurl.';分类id: '.$class_id);
                }
                unset($data);
            }
        ]);
    }

    public function HandleContent($content)
    {
        $content = str_replace(array("\r\n", "\r", "\n"), "", $content);
        $content = preg_replace('#\s*<p>\s*</p>#is', '', $content);
        $content = htmlspecialchars($content).$this->statement;
        return $content;
    }

    //搜狐
    public function souhu()
    {
        exit();
        $url = [
            '13' => 'http://sports.sohu.com/yingchao_list.shtml',   //英超
            '14' => 'http://sports.sohu.com/xijia_a.shtml',         //西甲
            '17' => 'http://sports.sohu.com/yijia_list.shtml',      //意甲
            '15' => 'http://sports.sohu.com/dejia_list.shtml',      //德甲
            '27' => 'http://sports.sohu.com/uefachampionsleague_news/index.shtml',      //欧冠
            '4'  => 'http://sports.sohu.com/nba_a.shtml',           //NBA
            '3'  => 'http://cbachina.sports.sohu.com/s2011/cba/'    //CBA
        ];

        foreach ($url as $k => $v) {
            $urlCheck = [];
            //网页抓取url
            if(!in_array($k, [3])){
                $urlCheck = QueryList::Query($v,array(
                    'url' => array('.f14list>ul>li>a','href')
                ))->getData(function($item){
                    return $item['url'];
                });
            }else{
                $html = Tool::url_get_contents($v);
                $urlCheck = QueryList::Query($html,array(
                    'url' => array('.f14list>ul>li>a','href')
                ))->getData(function($item){
                    return $item['url'];
                });
            }
            
            foreach ($urlCheck as $key => $value) {
                if(stripos($value, 'pic') || stripos($value, 'index') || stripos($value, 'www.sohu.com') || stripos($value, 'rmwins1617cl') || stripos($value, 's2017') || stripos($value, 'cbachina')){
                    //去掉图库url
                    unset($urlCheck[$key]);
                }
            }

            //已抓取网址
            $capture_url = M('PublishList')->master(true)->where(['capture_url'=>['in',$urlCheck]])->getField('capture_url',true);

            //去掉已抓取网址
            foreach ($urlCheck as $kkk => $vvv) {
                if(in_array($vvv, $capture_url)){
                    unset($urlCheck[$kkk]);
                }
            }
            if(empty($urlCheck)) continue;

            $this->CrawlerSouHu($urlCheck,$k);
        }
        exit;
    }

    //抓取搜狐资讯
    public function CrawlerSouHu($urlCheck,$class_id)
    {
        exit();
        //多线程捉取
        QueryList::run('Multi',[
            //待采集链接集合
            'list' => $urlCheck,
            'curl' => [
                'opt' => array(
                    //这里根据自身需求设置curl参数
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
            'success' => function($res)use($class_id)
            {
                $docurl = $res['info']['url'];
                $html   = $res['content'];
                $rule = [
                        'title'        => array('h1[itemprop="headline"]','text'), //标题
                        'add_time'     => ['#pubtime_baidu','text'],               //时间
                        'label'        => ['meta[name="keywords"]','content'],     //标签
                        'seo_keys' => ['meta[name="description"]','content'],  //seo关键词
                        'content'      => array('#contentText','html','a -script -style -#isBasedOnUrl -#author -#sourceOrganization -#indexUrl -#url br -iframe')                //内容
                    ];
                $data = QueryList::Query($html,$rule)->getData(function($item)use($docurl,$class_id){
                    $item['title']       = iconv("GBK", "UTF-8", $item['title']);
                    $content             = iconv("GBK", "UTF-8", $item['content']);
                    $item['short_title'] = $item['title'];     //短标题
                    $item['source']      = '搜狐体育';         //来源
                    $item['add_time']    = strtotime($item['add_time']);
                    $item['update_time'] = $item['add_time'];  //时间
                    $item['app_time']    = $item['add_time'];
                    $item['class_id']    = $class_id;          //分类
                    $item['capture_url'] = $docurl;            //来源地址
                    $item['label']       = str_replace(' ', ',', $item['label']);
                    $item['author']      = 1;                  //默认作者admin
                    $item['status']      = 0;                  //默认待发布
                    if(stripos($content, '版权声明')){
                        return [];
                    }
                    $content             = preg_replace('/　/is','',$content);
                    $item['content']     = $this->HandleContent($content);
                    return $item;
                });
                // dump($data[0]);
                // die;
                if($data[0]['title']){
                    if(M('PublishList')->add($data[0])){
                        dump("采集成功：".$data[0]['title'].';分类id: '.$class_id);
                    }else{
                        dump("采集失败：".$docurl.';分类id: '.$class_id);
                    }
                }else{
                    dump("采集失败：".$docurl.';分类id: '.$class_id);
                }
                unset($data);
            }
        ]);
    }

    //QQ
    public function qq()
    {
        exit();
        $url = [
            '13' => 'http://sports.qq.com/l/isocce/yingc/list2012032675724.htm',   //英超
            '14' => 'http://sports.qq.com/l/isocce/xijia/laliganews.htm',          //西甲
            '17' => 'http://sports.qq.com/c/yijia2016_toutiao_1.htm',              //意甲
            '27' => 'http://sports.qq.com/l/isocce/chamleg/list20120327151726.htm',//欧冠
            '3'  => 'http://sports.qq.com/l/cba/CBAlist.htm',                      //CBA
            '18' => 'http://sports.qq.com/l/csocce/jiaa/list20120912114353.htm',   //中超
            '15' => 'http://i.match.qq.com/pac/dejia?p=1&limit=10',                //德甲
        ];

        foreach ($url as $k => $v) {
            $urlCheck = [];
            if(in_array($k, [13,3])){
                //网页抓取url
                $urlCheck = QueryList::Query($v,array(
                    'url' => array('.leftList>ul>li>a','href')
                ))->getData(function($item){
                    return $item['url'];
                });
            }else if($k == 14){
                $html = iconv('GBK', 'UTF-8', Tool::url_get_contents($v));
                $doc = phpQuery::newDocumentHTML($html);
                $jsData = pq("#articleLiInHidden")->text();
                preg_match_all("/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(htm)/",$jsData,$urlData);
                $urlCheck = $urlData[0];
            }else if($k == 17){
                $urlCheck = QueryList::Query($v,array(
                    'url' => array('h4>a','href')
                ))->getData(function($item){
                    return $item['url'];
                });
            }else if(in_array($k, [27,18])){
                $urlCheck = QueryList::Query($v,array(
                    'url' => array('.newslist>ul>li>a','href')
                ))->getData(function($item){
                    return $item['url'];
                });
            }elseif($k == 15){
                $html = Tool::url_get_contents($v,['Referer:http://sports.qq.com']);
                $data = json_decode($html,true);
                foreach ($data['data'] as $d => $dd) {
                    $urlCheck[] = $dd['url'];
                }
            }
            //拼上https链接，防止重复
            $urlCheck2 = [];
            foreach ($urlCheck as $kk => $vv) {
                $urlCheck2[] = str_replace('http://', 'https://', $vv);
            }
            $selectUrl = array_merge($urlCheck,$urlCheck2);

            //已抓取网址
            $capture_url = M('PublishList')->master(true)->where(['capture_url'=>['in',$selectUrl]])->getField('capture_url',true);

            //去掉已抓取网址
            foreach ($urlCheck as $kkk => $vvv) {
                $httpurl = str_replace('http://', 'https://', $vvv);
                $httpsurl = str_replace('https://', 'http://', $vvv);
                if(in_array($httpurl, $capture_url) || in_array($httpsurl, $capture_url)){
                    unset($urlCheck[$kkk]);
                }
            }
            
            if(empty($urlCheck)) continue;

            $this->CrawlerQQ($urlCheck,$k);
        }
        exit;
    }

    //抓取QQ资讯
    public function CrawlerQQ($urlCheck,$class_id)
    {
        exit();
        //多线程捉取
        QueryList::run('Multi',[
            //待采集链接集合
            'list' => $urlCheck,
            //'list' => ['http://sports.qq.com/a/20170628/008775.htm'],
            'curl' => [
                'opt' => array(
                    //这里根据自身需求设置curl参数
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
            'success' => function($res)use($class_id)
            {
                $docurl = $res['info']['url'];
                $html = iconv('GBK', 'UTF-8', $res['content']);
                // echo $html;
                // die;
                $data = QueryList::Query($html,[
                    'title'        => array('.qq_article>.hd>h1','text'), //标题
                    'add_time'     => ['.a_time','text'],               //时间
                    'label'        => ['meta[name="keywords"]','content'],     //标签
                    'seo_keys' => ['meta[name="Description"]','content'],  //seo关键词
                    'content'      => array('.qq_article','html','a -script -style -.rv-root-v2 -#sports_video_Vip -.hd -iframe')                //内容
                ])->getData(function($item)use($docurl,$class_id){
                    $item['short_title'] = $item['title'];     //短标题
                    $item['source']      = '腾讯体育';         //来源
                    $item['author']      = 1;                  //默认作者admin
                    $item['status']      = 0;                  //默认待发布
                    $item['add_time']    = strtotime($item['add_time']);
                    $item['update_time'] = $item['add_time'];  //时间
                    $item['app_time']    = $item['add_time'];
                    $item['class_id']    = $class_id;          //分类
                    $item['capture_url'] = $docurl;            //来源地址
                    $item['label']       = trim(str_replace($item['title'], '', $item['label']),',');
                    $content = preg_replace('/<!-- 相关视频 -->(.*?)<!-- \/相关视频 -->/is', '', $item['content']);
                    $content = strip_tags($content,'<p><img>');
                    $content = str_replace(array("\r\n", "\r", "\n",'正文已结束，您可以按alt+4进行评论'), "", $content);
                    if(stripos($content, '版权声明')){
                        return [];
                    }
                    $content = preg_replace('#\s*<p>\s*</p>#is', '', $content);
                    $item['content']     = htmlspecialchars($content).$this->statement;
                    if($item['title'] != ''){
                        return $item;
                    }
                });
                // dump($data);
                // die;
                if($data[0]['title']){
                    if(M('PublishList')->add($data[0])){
                        dump("采集成功：".$data[0]['title'].';分类id: '.$class_id);
                    }else{
                        dump("采集失败：".$docurl.';分类id: '.$class_id);
                    }
                }else{
                    dump("采集失败：".$docurl.';分类id: '.$class_id);
                }
                
                unset($data);
            }
        ]);
    }

    //NBA中国
    public function nba()
    {
        exit();
        $url = [
            '4' => 'http://china.nba.com/news/',   //NBA中国
        ];

        foreach ($url as $k => $v) {
            $urlCheck = [];
            //网页抓取url
            $urlCheck = QueryList::Query($v,array(
                'url' => array('#indexNewsWrap>.news-wrap>a','href')
            ))->getData(function($item){
                return $item['url'];
            });

            //已抓取网址
            $capture_url = M('PublishList')->master(true)->where(['capture_url'=>['in',$urlCheck]])->getField('capture_url',true);

            //去掉已抓取网址
            foreach ($urlCheck as $kk => $vv) {
                if(in_array($vv, $capture_url)){
                    unset($urlCheck[$kk]);
                }
            }

            if(empty($urlCheck)) continue;

            $this->CrawlerNBA($urlCheck,$k);
        }
        exit;
    }

    //抓取NBA中国资讯
    public function CrawlerNBA($urlCheck,$class_id)
    {
        exit();
        //多线程捉取
        QueryList::run('Multi',[
            //待采集链接集合
            'list' => $urlCheck,
            'curl' => [
                'opt' => array(
                    //这里根据自身需求设置curl参数
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
            'success' => function($res)use($class_id)
            {
                $docurl = $res['info']['url'];
                $html = iconv('GBK', 'UTF-8', $res['content']);
                // echo $html;
                // die;
                $data = QueryList::Query($html,[
                    'title'        => array('.hd>h1','text'), //标题
                    'add_time'     => ['.article-time','text'],               //时间
                    'seo_keys' => ['meta[name="Description"]','content'],  //seo关键词
                    'content'      => array('#Cnt-Main-Article-QQ','html','a -script -style')                //内容
                ])->getData(function($item)use($docurl,$class_id){
                    $item['short_title'] = $item['title'];     //短标题
                    $item['source']      = 'nba';              //来源
                    $item['author']      = 1;                  //默认作者admin
                    $item['status']      = 0;                  //默认待发布
                    $item['label']       = 'nba';
                    $item['add_time']    = strtotime($item['add_time']);
                    $item['update_time'] = $item['add_time'];  //时间
                    $item['app_time']    = $item['add_time'];
                    $item['class_id']    = $class_id;          //分类
                    $item['capture_url'] = $docurl;            //来源地址
                    $content = $item['content'];
                    if(stripos($content, '版权声明')){
                        return [];
                    }
                    $content = preg_replace('#\s*<p>\s*</p>#is', '', $content);
                    $item['content']     = htmlspecialchars($content).$this->statement;
                    if($item['title'] != ''){
                        return $item;
                    }
                });
                // dump($data);
                // die;
                if($data[0]['title']){
                    if(M('PublishList')->add($data[0])){
                        dump("采集成功：".$data[0]['title'].';分类id: '.$class_id);
                    }else{
                        dump("采集失败：".$docurl.';分类id: '.$class_id);
                    }
                }else{
                    dump("采集失败：".$docurl.';分类id: '.$class_id);
                }
                unset($data);
            }
        ]);
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
}