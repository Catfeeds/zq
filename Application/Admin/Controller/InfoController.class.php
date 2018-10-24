<?php
use Think\Tool\Tool;
class InfoController extends CommonController
{
    public function HotLeague(){
        $map = $this->_search('HotLeague');

        $list = $this->_list(CM('HotLeague'),$map,'sort',true);
        foreach ($list  as $k => $v)
        {
            $list[$k]['logo']  = Tool::imagesReplace($v['logo']);
        }
        $this->assign('list',$list);
        $this->display('HotLeague');
    }

    public function add(){
        if(IS_POST){
            $id = I('id');
            $model = D('HotLeague');
            $validate = $model->create();

            if($validate['union_id'] == ''){
                $this->error('请选择联赛');
            }

            if(empty($id)){
                $id = $model->add();
                if(!$id)
                    $this->error('保存失败');
            }else{
                M("HotLeague")->where(['id' => $id])->data($validate)->save();
            }

            //上传logo
            if (!empty($_FILES['fileInput']['tmp_name']) && $id) {
                $return = D('Uploads')->uploadImg("fileInput", "hot_league", $id);
                if($return['status'] != 1)
                    $this->error('上传失败 !');

                M("HotLeague")->where(['id' => $id])->save(['logo'=>$return['url']]);
            }

            $this->success('保存成功!',cookie('_currentUrl_'));
        }else{

            $mService = mongoService();
            $continent = $mService->select('fb_continent',[], ['continent_id','continent_name']);
            //顶级洲际赛事分类
            $topConIdArrs = $topCouIdArrs = $countryMap = $countryIds = $countryIds2 = [];

            $mService->index = ['s_name' => 1];
            foreach($continent as $coKey => $coVal){
                //洲际关联country_id
                $country1 = $mService->fetchRow('fb_country',
                    ['s_name' => $coVal['continent_name'][0]],
                    ['country_id', 'images']
                );

                $continent[$coKey]['name']          = $coVal['continent_name'][0];
                $continent[$coKey]['t_name']        = $coVal['continent_name'][1];
                $continent[$coKey]['country_id']    = (int)$country1['country_id'];

                $countryNameMap[]   = $continent[$coKey]['name'];
                $topConIdArrs[]        = (string)$coVal['continent_id'];
                $topCouIdArrs[]        = (int)$country1['country_id'];

                unset($continent[$coKey]['continent_name']);
            }

            //洲际赛事下所有国家
            $mService->index = ['continent_id' => 1];
            $country = $mService->select(
                'fb_country',
                ['continent_id' => ['$in' => $topConIdArrs]],
                ['country_id','s_name','t_name','continent_id']
            );


            foreach($country as $couKey => $couVal){

                unset($country[$couKey]['_id']);

                if(is_int($couVal['country_id'])){
                    $countryMap[$couVal['continent_id']][] = $country[$couKey];
                    if(!in_array($couVal['country_id'], $topCouIdArrs)){
                        $countryIds2[] = $couVal['country_id'];
                    }
                }
            }

            //洲际所有赛事
            $mService->index = ['country_id' => 1];
            $unions = $mService->select(
                'fb_union',
                ['country_id' => ['$in' => $topCouIdArrs]],
                ['country_id','union_id','union_name'],
                ['union_id' => 1]
            );

            foreach($unions as $uk => $uv){
                $unions[$uk]['union_name'] = $uv['union_name'][0];
                unset($unions[$uk]['_id']);
            }

            //国家所有赛事
            $mService->index = ['country_id' => 1];
            $country_unions = $mService->select(
                'fb_union',
                ['country_id' => ['$in' => $countryIds2]],
                ['country_id','union_id','union_name']
            );

            foreach($country_unions as $couKey2 => $couVal2){
                $country_unions[$couKey2]['union_name'] = $couVal2['union_name'][0];
                unset($country_unions[$couKey2]['_id']);
            }

            $this->assign('continent', $continent);
            $this->assign('unions', json_encode($unions));
            $this->assign('countryMap', json_encode($countryMap));
            $this->assign('country_unions', json_encode($country_unions));

            if(!empty(I('id'))){
                $list = M('HotLeague')->where(['id' => I('id')])->find();
                $list['logo']  = Tool::imagesReplace($list['logo']);
                $this->assign('vo', $list);
            }
            $this->display();
        }

    }
}