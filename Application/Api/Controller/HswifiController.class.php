<?php
use Think\Controller;

class HswifiController extends Controller
{

    /**
     * 资讯列表
     */
    public function getNews()
    {
        $data = M("PublishList")
            ->field("id, title, add_time, img, class_id")
            ->where(["hs_recommend" => 1])
            ->order('update_time DESC')
            ->limit(50)
            ->select();

        $classArr = getPublishClass(0);

        foreach ($data as $key => $val) {
            $data[$key]['img'] = \Think\Tool\Tool::imagesReplace($val['img']);
            $data[$key]['jump_url'] = mNewsUrl($val['id'], $val['class_id'], $classArr);
        }

        $this->ajaxReturn(['status' => 1, 'data' => $data ?: []]);
    }

    /**
     * 比赛列表
     */
    public function matchList()
    {
        $live = D("Mobile/NewBase")->getLiveGame();
        foreach ($live as $k => $v) {
            $live[$k]['jump_url'] = "https://m.qqty.com/Details/data/scheid/{$v['gameId']}.html";
        }

        $this->ajaxReturn(['status' => 1, 'data' => $live ?: []]);
    }

    /**
     * 专题列表
     */
    public function specialList()
    {
        $list = M('Nav')
            ->field('name, ui_type_value as jump_url, icon, type')
            ->where(['status' => 1, 'type' => 36])
            ->order('sort asc')
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['icon'] = \Think\Tool\Tool::imagesReplace($v['icon']);
        }

        $this->ajaxReturn(['status' => 1, 'data' => $list ?: []]);
    }
}


