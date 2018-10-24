<?php
/**
 * 帮助中心管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-1-21
 */
use Think\Tool\Tool;
class HelpController extends CommonController {
    protected function _initialize()
    {
        parent::_initialize();
        //获取导航分类
        $helpClass = M('HelpClass')->where(['pid'=>0,'status'=>1])->order("sort asc")->select();
        foreach ($helpClass as $k => $v) {
            $helpClass[$k]['childClass'] = M('HelpClass')->where(['pid'=>$v['id'],'status'=>1])->order("sort asc")->select();
        }
        $this->assign('helpClass',$helpClass);
    }
    //帮助中心主页
    public function index()
    {
        //获取常见问题分类
        $problem = M('HelpClass')->where(['pid'=>1,'status'=>1])->order("sort asc")->limit(4)->select();
        foreach ($problem as $k => $v) {
            //获取问题文章
            $problem[$k]['article'] = M('HelpArticle')->where(['class_id'=>$v['id'],'is_recommend'=>1,'status'=>1])->limit(6)->order("add_time desc")->field('id,class_id,title')->select();
        }
        //客服系统
        $liveChatUrl = '//kf.qqty.com/chat.php?a=95249&amp;el=emgtY24_';

        if($id = is_login()){
            $this->assign('id',$id);
            $UserData = M('frontUser')->where(array('id'=>$id))->field("username,nick_name")->find();
            $liveChatUrl .= '&name='.$UserData['nick_name'].'&tel='.$UserData['username'];
        }
        $this->assign('liveChatUrl',$liveChatUrl);

        $this->assign('problem',$problem);
        $this->assign('position','帮助中心');
        $this->display();
    }

    //问题列表
    public function help_list()
    {
        $class_id  = I('get.class_id');
        $classId   = I('get.classId');
        //查询条件
        $map = $this->_search('HelpArticle');
        $map['class_id'] = $class_id;
        $map['status'] = 1;
        //获取问题文章
        $list = $this->_list(D('HelpArticle'),$map,10,"id desc","id,class_id,title",'',"/help_list/{$classId}/{$class_id}/%5BPAGE%5D.html");
        $this->assign('list',$list);
        //分类名称
        $className = M('HelpClass')->where(['id'=>$class_id,'status'=>1])->getField('name');
        $this->assign('className',$className);
        $this->assign('position','帮助中心');
        $this->display();
    }

    //问题内容
    public function help_detail()
    {
        $articleId = I('get.articleId');
        //获取问题文章
        $where['id'] = $articleId;
        $is_show = I('get.is_show');
        if(empty($is_show)){
            $where['status'] = 1;
        }
        $article = M('HelpArticle')->where($where)->find();
        $this->assign('article',$article);
        //上一篇
        $nextArticle = M('HelpArticle')->where(['class_id'=>$article['class_id'],'status'=>1,'id'=>['lt',$article['id']]])->order("id desc")->limit(1)->field('id,class_id,title')->select();
        //下一篇
        $prevArticle = M('HelpArticle')->where(['class_id'=>$article['class_id'],'status'=>1,'id'=>['gt',$article['id']]])->order("id asc")->limit(1)->field('id,class_id,title')->select();
        $this->assign('nextArticle',$nextArticle);
        
        $this->assign('prevArticle',$prevArticle);
        $this->assign('position','帮助中心');
        $this->display();
    }

    //查询列表
    public function help_search()
    {
        $keyword = urldecode(I('get.keyword'));
        $this->assign('keyword',$keyword);
        $map = $this->_search('HelpArticle');
        //关键字查询
        $map['status'] = 1;
        $map['title'] = ['like',"%{$keyword}%"];
        $list = $this->_list(D('HelpArticle'),$map,10,'add_time desc','','',"/help_search/{$keyword}/%5BPAGE%5D.html");
        $this->assign('list',$list);
        $this->assign('position','帮助中心');
        $this->display();
    }

}