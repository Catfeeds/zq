<?php
/**
 * app版本管理
 * @author huangjiezhen <418832673@qq.com> 2016.09.26
 */

class ApiRequestController extends CommonController
{
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $dwz_db_name=$dwz_db_name ? $dwz_db_name : $this->getActionName();
        $model = CM($dwz_db_name);
        $map = $this->_search($dwz_db_name);

        //时间请求
        if (!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime']))
        {
            $map['request_time'] = array('BETWEEN',array(strtotime($_REQUEST ['startTime']),strtotime($_REQUEST ['endTime'])));
        }
        elseif (!empty($_REQUEST ['startTime']))
        {
            $map['request_time'] = array('EGT',strtotime($_REQUEST ['startTime']));
        }
        else if (!empty($_REQUEST ['endTime']))
        {
            $map['request_time'] = array('ELT',strtotime($_REQUEST ['endTime']));
        }

        $this->assign("map",$map);
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        if (!empty($model)) {
            $this->_list($model, $map);
        }

        $this->display();
    }

    //配置页面
    public function logConf()
    {
        $this->vo = getWebConfig('appRequest');
        $this->display();
    }

    //保存配置
    public function saveLogConf()
    {
        $config = json_encode(['appLogOn'=>I('appLogOn'),'appLogList'=>I('appLogList')]);

        if (M('Config')->where(['sign'=>'appRequest'])->save(['config'=>$config]) !== false)
        {
            $this->success('保存成功');
        }

        $this->error('保存失败');
    }
}