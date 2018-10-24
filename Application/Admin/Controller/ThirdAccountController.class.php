<?php

class ThirdAccountController extends CommonController
{
    /**
 * 默认排序操作
 * @access
 * @return
 * @throws
 */

    public function index ()
    {
        //$map = $this->_search('thirdAccount');
        $site_name = I('site_name');
        if(! empty($site_name))
        {
            $map['site_name'] = ['LIKE','%'.$site_name.'%'];
        }
        $account = I('account');
        if(! empty($account))
        {
            $map['account'] = ['LIKE','%'.$account.'%'];
        }
        $list = M("ThirdAccount")->where($map)->select();
        $this->assign('list',$list);
        $this->display();

    }

    public function save()
    {
        if(IS_POST)
        {//添加平台
            $id = I('id');

            $model = M("ThirdAccount");
            if(false === $model->create())
            {
                $this->error($model->getError());
            }
            if(!empty($id))
            {//编辑平台
                $result = $model->where(['id'=>$id])->save();
                if(false !== $result){
                    $this->success('编辑成功');
                }
            }
            else
            {
                $map['site_name'] = I('site_name');
                $map['url'] = I('url');
                $map['account'] = I('account');
                $map['pwd'] = I('pwd');
                $map['nickname'] = I('nickname');
                $map['remark'] = I('remark');
                $map['_logic'] = 'or';
                $siteAccount = $model->field('site_name,account')->where($map)->find();
                $re = $model->add($map);
                if(false !== $re)
                {
                    $this->success('添加成功');
                }
                else
                {
                    $this->success('添加失败');
                }
            }
        }
        else
        {
            $id = I('id');
            if(! empty($id))
            {
                $vo = M("ThirdAccount")->where(['id' => $id])->find();
                if(!$vo) $thsi->error('参数错误');
            }
            $this->assign('vo',$vo);
            $this->display();
        }
    }
}