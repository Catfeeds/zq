<?php
/**
 * 视频专区
 */
use Think\Tool\Tool;

class HighlightsController extends CommonController
{

    //设置视频分类id数组
    public $vClass = [];
    protected function _initialize() {
        parent::_initialize();

        if(substr_count($_SERVER['PATH_INFO'],'/') > 1) $this->error("找不到相关页面！");
        $this->vClass = M('HighlightsClass')->getField('id,name');
    }

    public function index(){
        $class = I('class_id');
        preg_match('/[a-zA-Z]*/i', $class,$matches);
        if($matches[0])
        {
            $word = M('HotKeyword')->where(['url_name'=>$class])->getField('keyword');
            if($word)
                $class = $word;
            else
                $class = '';
        }
        $this->assign('keyWord',$class);
        A('Mobile/Nav')->navHead('video');
        $this->assign('titleHead','视频专区');
        $this->assign('urlHead',U('/video'));
        if($class) $this->assign('user',['name'=>$class]);

        $seo = [
            'seo_title' => '视频专区|英超直播|西甲直播|世界杯直播|赛事直播_体育赛事直播专题频道_全球体育手机网',
            'seo_keys'  => '英超直播,西甲直播,世界杯直播,赛事直播,中超直播,法甲直播,JRS直播,直播吧,意甲直播,德甲直播,亚冠直播,欧冠直播,足球直播,NBA直播,CBA直播',
            'seo_desc'  => '全球体育赛事直播专题频道，为您提供英超直播、西甲直播、2018世界杯直播、中超直播、篮球直播等赛事直播内容，欢迎关注！',
        ];
        $this->assign('seo',$seo);
        $this->display();
    }

    //ajax获取视频列表
    public function getVideoList()
    {
        $page = I('p',1,'int');
        $limit = 12;
        $where = [];
        $where['h.status'] = 1;
        $where['h.m_url'] = ['NEQ',''];
        $where['h.add_time'] = ['lt',I('time',time(),'int')];
        $where['h.user_id'] = ['NEQ','NULL'];
        if(I('key')) $where['h.label'] = ['like', '%'.I('key').'%'];
        $videoList = M('Highlights h')->join('INNER JOIN qc_front_user fu ON fu.id = h.user_id')->field('h.id,h.img,h.title,h.click_num as click,h.top_recommend as is_top,h.class_id,fu.head,fu.nick_name as name,h.m_ischain,h.m_url')->where($where)->order('add_time desc')->page($page . ',' . $limit)->select();
        foreach($videoList as $key=>$val)
        {
            //拼接url
            $videoList[$key]['url'] = $val['m_ischain'] == 1?$val['m_url']:U('/video/'.$val['id'].'@m');
            //处理封面
            if (!empty($val['img'])) {
                $videoList[$key]['img'] = Think\Tool\Tool::imagesReplace($val['img']);
            } else {
                $videoList[$key]['img'] = staticDomain('/Public/Images/defalut/newsimg.jpg');
            }
            //处理用户头像
            $videoList[$key]['head'] = frontUserFace($val['head']);
            $videoList[$key]['class'] = $this->vClass[$val['class_id']];
            if(strlen($val['click'])>4){
                $videoList[$key]['click'] = round($val['click']/10000,1).'万';
            }
            $videoList[$key]['click'] = addClickConfig(1, $val['class_id'],$val['click'], $val['id']);
            unset($videoList[$key]['class_id'],$videoList[$key]['m_ischain'],$videoList[$key]['m_url']);
        }
        if($videoList)
            $data = ['code'=>200,'data'=>$videoList];
        else
            $data = ['code'=>201,'msg'=>'暂无数据!!'];
        $this->ajaxReturn($data);
    }

    //视频播放详情页
    public function info()
    {
        $id = I('id');
        $res = M('Highlights h')->join('LEFT JOIN qc_front_user fu ON fu.id = h.user_id')->field('h.*,fu.head,fu.nick_name as name,h.user_id,fu.is_expert')->where(['h.id'=>$id])->find();
        if(!$res)
            $this->_empty();
        
        M('Highlights')->where(['id'=>$id])->setInc('click_num');
        if (!empty($res['img'])) {
            $res['img'] = Think\Tool\Tool::imagesReplace($res['img']);
        } else {
            $res['img'] = staticDomain('/Public/Images/defalut/newsimg.jpg');
        }
        $res['head'] = frontUserFace($res['head']);
        $label = explode(',',$res['label']);
        //优化点击量显示
        $clickNum = addClickConfig(1, $res['class_id'],$res['click_num'], $res['id']);
        if(strlen($res['click_num'])>4)
            $res['click_num'] = round($clickNum/10000,1).'万';
        else
            $res['click_num'] = $clickNum;
        $keyUrl = M('HotKeyword')->where(['keyword'=>['in',$res['label']]])->getField('keyword,url_name');
        foreach($label as $key=>$val)
        {
            if($keyUrl[$val])
                $label[$key] = [$val,$keyUrl[$val]];
            else
                $label[$key] = [$val,$val];
        }
        if($label)
        {
            foreach ($label as $key=>$val)
            {
                $label[$key][0] = strtoupper($val[0]);
            }
        }
        $res['label'] = $label;
        $this->assign('data',$res);
        $this->assign('titleHead','视频专区');
        $this->assign('urlHead',U('/video'));
        $this->assign('user',['name'=>'正文']);
        $this->assign('sConfig',['url'=>'video']);
        $classArr = getVideoClass(0)[$res['class_id']];
        $this->setSeo([
            'seo_title' => $res['seo_title'] ?: $res['title'].'_'.$classArr['name'].'视频集锦专区频道'.'_全球体育手机网',
            'seo_keys'  => $res['seo_keys']  ?: $classArr['seo_keys'],
            'seo_desc'  => $res['seo_desc']  ?: $classArr['seo_desc'],
        ]);
        $this->display();
    }

}