<?php

/**
 * 帖子模型
 */
class CommunityPostsModel extends \Think\Model
{

    /**
     * @param $post_id
     * @return array
     */
    public function uploadPostImg($post_id)
    {
        $retData  = ['status' => true, 'data' => [], 'info' => '上传成功'];
        $fileList = $_FILES;

        $curlObj                = new \Think\Tool\Curl();
        $curlObj->maxSize       = '5242880';
        $curlObj->allowTypes    = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'application/x-shockwave-flash'];
        $curlObj->allowExts     = ['jpg', 'jpeg', 'gif', 'png', 'swf'];
        $curlObj->savePath      = "posts,{$post_id}";
        $curlObj->isAutoCreateYMDir = false;
        $curlObj->saveRule      = " ";
        $curlObj->formFileName  = 'pics';
        $curlObj->water         = true;

        $count = count($fileList['pics']['tmp_name']);

        for ($i = 0; $i < $count; $i++) {
            $fileName = $i + 1;
            $curlObj->customName        = $fileName;//自定义文件名，不含文件扩展名
            $curlObj->thumbJson         = "[[200,200," . $fileName . C('thumbImgSize') . "]]";
            $_FILES['pics']['name']     = $fileList['pics']['name'][$i];
            $_FILES['pics']['type']     = $fileList['pics']['type'][$i];
            $_FILES['pics']['tmp_name'] = $fileList['pics']['tmp_name'][$i];
            $_FILES['pics']['error']    = $fileList['pics']['error'][$i];
            $_FILES['pics']['size']     = $fileList['pics']['size'][$i];

            $imageArr = $curlObj->upload();
            if ($imageArr['status'] != '1') {
                $retData['status'] = false;
                $retData['info'] = $imageArr['info'];
            }

            $retData['data'][$i] = $imageArr['data']['imgDir'];
        }

        return $retData;
    }

    /**
     * 我的发帖
     * @param $userid
     * @param int $page
     * @param int $limit
     * @param int $type
     * @return array
     */
    public function getMyPosts($userid, $page = 1, $limit = 20,$type = 0)
    {
        $page       = $page == 0 ? $page = 1 : $page;
        $startRow   = ($page - 1) * $limit;

        $fields = [
            'u.nick_name'       => 'user_nick_name',
            'u.head'            => 'user_head_img',
            'u.status'          => 'u_status',
            'c.name'            => 'quan_name',
            'c.head_img'        => 'quan_head_img',
            'p.id'              => 'post_id',
            'p.base64_title'    => 'post_title',
            'p.base64_content'  => 'post_content',
            'p.img'             => 'post_img',
            'p.like_num'        => 'post_like_num',
            'p.comment_num'     => 'post_comment_num',
            'p.create_time'     => 'post_create_time',
            'p.status'          => 'post_status',
        ];

        if(!$type){
            $posts = M('communityPosts p')
                ->field($fields)
                ->join('LEFT JOIN qc_community c ON p.cid = c.id')
                ->join('LEFT JOIN qc_front_user u ON p.user_id = u.id')
                ->where(['p.user_id' => $userid, 'p.status' => 1, 'u.status' => 1 , 'p.cid' => ['neq', 0]])
                ->order('post_create_time DESC')
                ->limit($startRow , $limit)
                ->select();
        }else{
            $posts = M('communityPosts p')
                ->field($fields)
                ->join('LEFT JOIN qc_community c ON p.cid = c.id')
                ->join('LEFT JOIN qc_front_user u ON p.user_id = u.id')
                ->where(['p.user_id' => $userid, 'p.status' => 1, 'u.status' => 1 , 'p.cid' => ['eq', 0]])
                ->order('post_create_time DESC')
                ->limit($startRow , $limit)
                ->select();
        }

	    $comment = $this->getDataComment($posts, 'post_id');
	
	    foreach ($posts as $key => $row) {
            $posts[$key]['post_title']      = (string)base64_decode($row['post_title']);
            $posts[$key]['post_content']    = (string)base64_decode($row['post_content']);
            $posts[$key]['user_nick_name']  = (string)$row['user_nick_name'];
            $posts[$key]['quan_name']       = (string)$row['quan_name'];
            $posts[$key]['user_head_img']   = frontUserFace($row['user_head_img']);
            $posts[$key]['quan_head_img']   = (string)\Think\Tool\Tool::imagesReplace($row['quan_head_img']);

            $post_imgs = !$row['post_img'] ? [] : json_decode($row['post_img'], 1);
            foreach ($post_imgs as $k => $sourceImg) {
                $thumbImg = implode(C('thumbImgSize') . '.', explode('.', $sourceImg));
                $post_imgs[$k] = [
                    'normal' => C('IMG_SERVER') . $sourceImg,
                    'thumb'  => C('IMG_SERVER') . $thumbImg
                ];
            }
            $posts[$key]['post_img'] = $post_imgs;
		    isset($comment[$row['post_id']]) ? $posts[$key]['post_comment'] = array_slice($comment[$row['post_id']], 0, 2) : $posts[$key]['post_comment'] = [];
        }

        return $posts ?: [];
    }


    /**
     * 好友帖子列表
     * @param $followId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getMyFllowPosts($followId, $page = 0, $limit = 20)
    {
        $page       = $page == 0 ? $page = 1 : $page;
        $startRow   = ($page - 1) * $limit;
        $id_str     = implode(',', $followId);

        $fields = [
            'u.id'              => 'user_id',
            'u.nick_name'       => 'user_nick_name',
            'u.head'            => 'user_head_img',
            'u.status'          => 'u_status',
            'c.name'            => 'quan_name',
            'c.head_img'        => 'quan_head_img',
            'p.id'              => 'post_id',
            'p.base64_title'    => 'post_title',
            'p.base64_content'  => 'post_content',
            'p.img'             => 'post_img',
            'p.like_num'        => 'post_like_num',
            'p.comment_num'     => 'post_comment_num',
            'p.create_time'     => 'post_create_time',
            'p.status'           => 'post_status',
        ];

        $posts = M('communityPosts p')
            ->field($fields)
            ->join('LEFT JOIN qc_community c ON p.cid = c.id')
            ->join('LEFT JOIN qc_front_user u ON p.user_id = u.id')
            ->where(['p.user_id' => ['IN', $id_str],'u.status' => 1, 'p.status' => 1])
            ->order('post_create_time DESC')
            ->limit($startRow , $limit)
            ->select();

        foreach ($posts as $key => $row) {
            $posts[$key]['post_title']      = $row['post_title'] == '' ? '' : base64_decode($row['post_title']);
            $posts[$key]['post_content']    = $row['post_title'] == '' ? '' : base64_decode($row['post_content']);
            $posts[$key]['user_nick_name']  = (string)$row['user_nick_name'];
            $posts[$key]['quan_name']       = (string)$row['quan_name'];
            $posts[$key]['user_head_img']   = frontUserFace($row['user_head_img']);
            $posts[$key]['quan_head_img']   = (string)\Think\Tool\Tool::imagesReplace($row['quan_head_img']);

            $post_imgs = !$row['post_img'] ? [] : json_decode($row['post_img'], 1);
            foreach ($post_imgs as $k => $sourceImg) {
                $thumbImg = implode(C('thumbImgSize') . '.', explode('.', $sourceImg));
                $post_imgs[$k] = [
                    'normal' => C('IMG_SERVER') . $sourceImg,
                    'thumb'  => C('IMG_SERVER') . $thumbImg
                ];
            }

            $posts[$key]['post_img'] = $post_imgs;
        }

        return $posts ?: [];
    }

    /**
     * @param $results
     * 处理资讯图片展示
     */
    public function getNewsLists($results)
    {
        foreach ($results as $k => $v) {

            $imgs = \Think\Tool\Tool::getTextImgUrl(htmlspecialchars_decode($v['content']), 0);
            foreach ($imgs as $kkk => $vvv) {
                if (strtoupper(substr(strrchr($vvv, '.'), 1)) == 'GIF')
                    unset($imgs[$kkk]);
            }

            if (count($imgs) >= 3) {
                $imgs = array_slice($imgs, 0, 3);
                foreach ($imgs as $kk => $vv) {
                    if (strpos($vv, SITE_URL) === false)
                        $imgs[$kk] = C('IMG_SERVER') . $vv;
                }

                $results[$k]['news_img'] = $imgs;
            } else {

                if ($results[$k]['news_img']) {
                    $results[$k]['news_img'] = [C('IMG_SERVER') . $results[$k]['news_img']];
                } else {

                    if (count($imgs) >= 1) {

                        if (strpos($imgs[0], SITE_URL) === false)
                            $results[$k]['news_img'] = [C('IMG_SERVER') . $imgs[0]];
                        else
                            $results[$k]['news_img'] = [$imgs[0]];
                    } else {
                        $results[$k]['news_img'] = [];
                    }
                }
            }
            $results[$k]['comment_num'] = M('Comment')->where(['publish_id' => $v['id']])->count(); //获取评论数
        }
        return $results;
    }
	
	/**
	 * 对应帖子评论
	 * @param $data
	 * @param $field
	 * @return array
	 */
    public function getDataComment($data, $field)
    {
	    $communityIds = array_column($data, $field);
	    $comment_where['c.post_id'] = ['in', $communityIds];
	    $comment_where['c.status'] = 1;
	    $comment_where['c.pid'] = 0;
	    $comment_temp = M('CommunityComment c')
		    ->field('c.id, c.post_id, c.user_id, c.content, c.create_time, u.nick_name')
		    ->join(' LEFT JOIN qc_front_user u on c.user_id = u.id ')
		    ->where($comment_where)
		    ->order('c.create_time desc')
		    ->select();
	    $comment = [];
	    $sort = [];
	    foreach ($comment_temp as $k => $v) {
		    $comment[$v['post_id']][] = $v;
		    $sort[] = $v['create_time'];
	    }
	    array_multisort($sort, SORT_DESC, $comment);
	
	    foreach ($comment as $ck => $cv) {
		    foreach ($cv as $k => $v) {
			    $comment[$ck][$k]['content'] = (string)base64_decode($v['content']);
			    unset($comment[$ck][$k]['id'], $comment[$ck][$k]['user_id'], $comment[$ck][$k]['post_id'], $comment[$ck][$k]['create_time']);
		    }
	    }
	    return$comment;
    }
    
    
    
}


?>