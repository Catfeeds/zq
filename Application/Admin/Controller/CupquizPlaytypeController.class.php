<?php

/**
 * 玩法管理
 * @since
 */
use Think\Controller;

class CupquizPlaytypeController extends CommonController
{
    public function index()
    {
        $map = $this->_search('CupquizPlaytype');

        $list = $this->_list(CM('CupquizPlaytype'), $map, 'id', true);

        foreach ($list as $k => $v){
            $list[$k]['options'] = json_decode($v['options']);
        }

        $this->assign('list', $list);
        $this->display();
    }

    //编辑
    public function edit()
    {
        $id = I('id');
        $model = M('CupquizPlaytype');
        $vo = $model->where(['id'=>$id])->find();
        if(!$vo){
            $this->error('参数错误!');
        }

        $vo['options'] = json_decode($vo['options']);


        $this->assign('vo',$vo);
        $this->display();
    }

    public function update()
    {
        $model =  M('CupquizPlaytype');
        $option_1 = I('options');
        $option_2 = I('options2');

        $options = [];

        if(!$option_1 && !$option_2)
            $this->error('所填选项错误!');

        foreach($option_1 as $k => $v){
            if($v === '' && $option_2[$k] === '')
                break;

            if($option_2[$k]==='' || $v==='')
                $this->error('所填选项错误!');

            if($v)
                $options[] = [$v, $option_2[$k]];
        }

        if (false === $model->create()) {
            $this->error($model->getError());
        }

        $model->options = json_encode($options);

        if(I('id')){
            // 更新数据
            $list = $model->save();
            if (false !== $list) {
                //成功提示
                $this->success('编辑成功!',cookie('_currentUrl_'));
            } else {
                //错误提示
                $this->error('编辑失败!');
            }
        }else{
            $result = $model->add();
            if ($result !== false) {
                $this->success('添加活动成功！', cookie('_currentUrl_'));
            } else {
                $this->error('添加活动失败！');
            }
        }
    }

    public function forbidAll()
    {
        $model =  M('CupquizPlaytype');

        // 更新数据
        $list = $model->where(['id' => ['IN', I('id')]])->save(['status' => 0]);
        if (false !== $list) {
            //成功提示
            $this->success('编辑成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
    }


}