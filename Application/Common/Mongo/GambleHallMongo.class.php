<?php
namespace Common\Mongo;


class GambleHallMongo extends BaseMongo
{
    public function __construct($dbName=NULL){
        parent::__construct($dbName);
    }
    
    //判断动画关联
    public function getFbLinkbet($gids){
        $this->setCollection('fb_game_365'.C('TableSuffix'));
        
        if(!is_array($gids)){
            $_map = ['$or' => [
                ['jbh_id' => (int)$gids],
                ['jb_id'  => (int)$gids],
            ]];
            $betRes = $this->findOne($_map, ['projection'=>[
                'jbh_id' => 1,
                'jb_id' => 1,
                'is_icon' => 1
            ]]);
            
            return !empty($betRes['is_icon']) ? $betRes['is_icon'] : 0;
        }
        
        $_map = ['$or' => [
            ['jbh_id' => ['$in' => $gids]],
            ['jb_id'  => ['$in' => $gids]],
        ]];
        
        $betRes = $this->findAll($_map, [
            'projection' => [
                'jbh_id' => 1, 
                'jb_id' => 1, 
                'is_icon' => 1 
            ] 
        ]);
       
        $linksArr = [];
        if(!empty($betRes))
        {
            foreach($betRes as $k=> $v)
            {
                $gid = $v['jbh_id'] ? $v['jbh_id'] : $v['jb_id'];
                if($v['is_icon'] == 1) $linksArr[$gid] = 1;
            }
        }
        return $linksArr;
    }
    
}