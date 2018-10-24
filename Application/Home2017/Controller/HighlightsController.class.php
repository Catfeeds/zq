<?php
/**
 * 集锦控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-3-30
 */
class HighlightsController extends CommonController {
    public $prefix;
    protected function _initialize(){
        parent::_initialize();
        $this->prefix = C('IMG_SERVER');
    }
    //集锦主页
    public function index()
    {
        $high = M('Highlights');
        //获取轮播推荐集锦
        $carousel = $high->where(['is_recommend'=>2,'status'=>1])->order("sort desc,add_time desc")->field("id,game_id,game_type,title,concat('$this->prefix',img) img,web_url,web_ischain")->limit(5)->select();
        $this->assign('carousel',$carousel);
        //获取推荐集锦
        $recommend = $high->where(['is_recommend'=>1,'status'=>1])->order("sort desc,add_time desc")->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->limit(6)->select();
        $this->assign('recommend',$recommend);
        //获取最新视频
        $new = $high->where(['status'=>1])->order("add_time desc")->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->limit(10)->select();
        $this->assign('new',$new);
        //分类列表数据,后面的查询必须跟该数组内容一样,调换数组内内容位置即可实现排序
        $union_list = array(
            array("caiJing","全球彩经"),//全球彩经是自定义的
            array("1","NBA"),
            array("5","CBA"),
            array("36","英超"),
            array("31","西甲"),
            array("192","亚冠"),
            array("8","德甲"),
            array("60","中超"),
            array("34","意甲"),
            array("103","欧冠"),
            
        );
        $gallery = array();
        /**************************篮球**************************/
        $union_b[] = 1;//获取NBA集锦
        $union_b[] = 5;//获取CBA集锦
        foreach ($union_b as $val)
        {
            $gallery[$val]['union_id'] = $val;
            $gallery[$val]['game_type'] = 2;
            $gallery[$val]['list'] = $high->where(['union_id'=>$val,'game_type'=>2,'status'=>1])->order("add_time desc")->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->limit(15)->select();
        }
		//全球彩经
		$gallery["caiJing"]['union_id'] = "caiJing";
		$gallery["caiJing"]['game_type'] = 0;
		$gallery["caiJing"]['list'] = $high->where(['is_best'=>1,'status'=>1])->order("add_time desc")->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->limit(15)->select();
		
		/**************************足球**************************/
        $union[] = 36;//获取英超集锦
        $union[] = 31;//获取西甲集锦
        $union[] = 192;//获取亚冠集锦
        $union[] = 103;//获取欧冠集锦
        $union[] = 8;//获取德甲集锦
        $union[] = 60;//获取中超集锦
        $union[] = 34;//获取意甲集锦
        foreach($union as $val)
        {
            $gallery[$val]['union_id'] = $val;
            $gallery[$val]['game_type'] = 1;
            $gallery[$val]['list'] = $high->where(['union_id'=>$val,'game_type'=>1,'status'=>1])->order("add_time desc")->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->limit(15)->select();
        }
        $i = $gallery[$union_list[0][0]];
        unset($gallery[$union_list[0][0]]);
        array_unshift($gallery,$i);
        $anchorName = urldecode(I('anchorName','caiJing'));
        $this->assign('anchorName',$anchorName);
        $this->assign('union_list',$union_list);
        //dump($anchorName);
        //die;
        $this->assign('gallery',$gallery);
        $this->display();
    }

    //更多集锦页
    public function more(){
        $union_id   = I('union_id');
        $game_type  = I('game_type')?:1;
        $where['union_id'] = $union_id;
        if($union_id == 'caiJing'){
            $where['is_best'] = 1;
            unset($where['union_id']);
            $this->assign('unionName','全球彩经');
        }else{
            //获取联盟名称
            $unionModel = $game_type == 1 ? M('union') : M('bkUnion');
            $unionName  = $unionModel->where(['union_id'=>$union_id])->getField('union_name');
            $this->assign('unionName',switchName(0,$unionName));
        }
        
        //获取相关集锦
        $highlights = M('Highlights')->where($where)->where(['game_type'=>$game_type,'status'=>1])->order("add_time desc")->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->limit(15)->select();

        $this->assign('highlights',$highlights);

        $this->display();
    }

    //ajax获取更多集锦
    public function sendMore(){
        $p = isset($_POST['k'])?intval(trim($_POST['k'])):0;
        $union_id   = I('union_id');
        $game_type  = I('game_type') ? : 1;
        $where['union_id']   = $union_id;
        $where['game_type']  = $game_type;
        $where['status']     = 1;
        if($union_id == 'caiJing'){
            $where['is_best'] = 1;
            unset($where['union_id']);
        }
        $total = M('Highlights')->where($where)->count();//数据记录总数

        $num = 15;//每页记录数
        $totalpage = ceil($total/$num);//总计页数
        $limitpage = ($p-1)*$num;//每次查询取记录

        if($p>$totalpage){
            //超过最大页数，退出
            $this->error("没有了");
        }
        $list = M('Highlights')->where($where)->field("id,game_id,game_type,title,click_num,add_time,concat('$this->prefix',img) img,web_url,web_ischain")->order('add_time desc')->limit($limitpage,$num)->select();
        foreach ($list as $key => $value) {
            $list[$key]['add_time'] = format_date($value['add_time']);
        }
        if(count($list)>0){
            $this->success($list);
        }else{
            $this->error("没有了");
        }
    }
}