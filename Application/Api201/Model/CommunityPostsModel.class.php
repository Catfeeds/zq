<?php

/**
 * 帖子模型
 */
class CommunityPostsModel extends \Think\Model
{
    private $_oCurl;

    /**
     * @param $post_id
     * @return array
     */
    public function uploadPostImg($post_id)
    {
        $retData = array('status' => true, 'data' => [], 'info' => '上传成功');
        $fileList = $_FILES;

        $this->_oCurl = new \Think\Tool\Curl();
        $this->_oCurl->maxSize = '5242880';
        $this->_oCurl->allowTypes = array('image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'application/x-shockwave-flash');
        $this->_oCurl->allowExts = array('jpg', 'jpeg', 'gif', 'png', 'swf');
        $this->_oCurl->savePath = "posts,{$post_id}";
        $this->_oCurl->isAutoCreateYMDir = false;
        $this->_oCurl->saveRule = " ";
        $this->_oCurl->formFileName = 'pics';
        $this->_oCurl->water  = true;

        $count = count($fileList['pics']['tmp_name']);

        for ($i = 0; $i < $count; $i++) {
			
            $fileName = $i + 1;
            $this->_oCurl->customName = $fileName;//自定义文件名，不含文件扩展名
            $this->_oCurl->thumbJson = "[[200,200," . $fileName . C('thumbImgSize') . "]]";
            $_FILES['pics']['name'] = $fileList['pics']['name'][$i];
            $_FILES['pics']['type'] = $fileList['pics']['type'][$i];
            $_FILES['pics']['tmp_name'] = $fileList['pics']['tmp_name'][$i];
            $_FILES['pics']['error'] = $fileList['pics']['error'][$i];
            $_FILES['pics']['size'] = $fileList['pics']['size'][$i];

            $imageArr = $this->_oCurl->upload();

            if ($imageArr['status'] != '1') {
                $retData['status'] = false;
                $retData['info'] = $imageArr['info'];
            }

            $retData['data'][$i] = $imageArr['data']['imgDir'];
        }
        return $retData;
    }

    /**
     * @param $userid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getMyPosts($userid, $page = 1, $limit = 20)
    {
        $page = $page == 0 ? $page = 1 : $page;
        $startRow = ($page - 1) * $limit;
        $data = array();

        $sql = "SELECT
	              u.nick_name AS `user_nick_name`,
	              u.head AS `user_head_img`,
	              c. NAME AS `quan_name`,
	              c.head_img AS `quan_head_img`,
	              p.id AS `post_id`,
	              p.base64_title AS `post_title`,
	              p.base64_content AS `post_content`,
	              p.img AS `post_img`,
                  count(cc.post_id) AS `post_comment_num`,
	              p.like_num AS `post_like_num`,
	              p.create_time AS `post_create_time`
                FROM
	              qc_community_posts p FORCE INDEX(inx_u_c)
                LEFT JOIN qc_community c ON p.cid = c.id
                LEFT JOIN qc_front_user u ON p.user_id = u.id
                LEFT JOIN qc_community_comment cc on p.id = cc.post_id
                WHERE
	              (p.user_id = {$userid})
	              GROUP BY p.id
                ORDER BY
	              post_create_time DESC limit $startRow,$limit";

        $posts = M()->query($sql);
        foreach ($posts as $key => $row) {
            $posts[$key]['post_title'] = (string)base64_decode($row['post_title']);
            $posts[$key]['post_content'] = (string)base64_decode($row['post_content']);
            $posts[$key]['user_nick_name'] = (string)$row['user_nick_name'];
            $posts[$key]['quan_name'] = (string)$row['quan_name'];
            $posts[$key]['user_head_img'] = frontUserFace($row['user_head_img']);
            $posts[$key]['quan_head_img'] = (string)\Think\Tool\Tool::imagesReplace($row['quan_head_img']);
            $post_imgs = empty($row['post_img']) ? array() : json_decode($row['post_img'], 1);

            foreach ($post_imgs as $k => $sourceImg) {
                $thumbImg = implode(C('thumbImgSize') . '.', explode('.', $sourceImg));
                $post_imgs[$k] = [
                    'normal' => C('IMG_SERVER') . $sourceImg,
                    'thumb'  => C('IMG_SERVER') . $thumbImg
                ];
            }
            $posts[$key]['post_img'] = $post_imgs;
        }

        return $posts;
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
        $page = $page == 0 ? $page = 1 : $page;
        $startRow = ($page - 1) * $limit;
        $data = array();
        $id_str = implode(',', $followId);
        $sql = "SELECT
	              u.id AS `user_id`,
	              u.nick_name AS `user_nick_name`,
	              u.head AS `user_head_img`,
	              c. NAME AS `quan_name`,
	              c.head_img AS `quan_head_img`,
	              p.id AS `post_id`,
	              p.base64_title AS `post_title`,
	              p.base64_content AS `post_content`,
	              p.img AS `post_img`,
	              count(cc.post_id) AS `post_comment_num`,
	              p.like_num AS `post_like_num`,
	              p.create_time AS `post_create_time`
                FROM
	              qc_community_posts p FORCE INDEX(inx_u_c)
                LEFT JOIN qc_front_user u ON p.user_id = u.id
                LEFT JOIN qc_community c ON p.cid = c.id
                 LEFT JOIN qc_community_comment cc on p.id = cc.post_id
                WHERE
	              (p.user_id IN ($id_str))
	              GROUP BY p.id
                ORDER BY
	              post_create_time DESC LIMIT $startRow,$limit";

        $posts = M()->query($sql);
        foreach ($posts as $key => $row) {
            $posts[$key]['post_title'] = $row['post_title'] == '' ? '' : base64_decode($row['post_title']);
            $posts[$key]['post_content'] = $row['post_title'] == '' ? '' : base64_decode($row['post_content']);
            $posts[$key]['user_nick_name'] = (string)$row['user_nick_name'];
            $posts[$key]['quan_name'] = (string)$row['quan_name'];
            $posts[$key]['user_head_img'] = frontUserFace($row['user_head_img']);
            $posts[$key]['quan_head_img'] = (string)\Think\Tool\Tool::imagesReplace($row['quan_head_img']);
            $post_imgs = empty($row['post_img']) ? array() : json_decode($row['post_img'], 1);
            foreach ($post_imgs as $k => $sourceImg) {
                $thumbImg = implode(C('thumbImgSize') . '.', explode('.', $sourceImg));
                $post_imgs[$k] = [
                    'normal' => C('IMG_SERVER') . $sourceImg,
                    'thumb'  => C('IMG_SERVER') . $thumbImg
                ];
            }
            $posts[$key]['post_img'] = $post_imgs;
        }

        return $posts;
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
                    if (strpos($vv, 'http://') === false)
                        $imgs[$kk] = C('IMG_SERVER') . $vv;
                }

                $results[$k]['news_img'] = $imgs;
            } else {
                if ($results[$k]['news_img']) {
                    $results[$k]['news_img'] = [C('IMG_SERVER') . $results[$k]['news_img']];
                } else {
                    if (count($imgs) >= 1) {
                        if (strpos($imgs[0], 'http://') === false)
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
}


?>