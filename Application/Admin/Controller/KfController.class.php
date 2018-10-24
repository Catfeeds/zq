<?php

/**
 * 客服
 *
 * @author
 *
 * @since
 */
use Think\Tool\Tool;
use Think\Controller;
class KfController extends CommonController {

    public function index()
    {
        $pageNum     = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $currentPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')]:1;
        $rows = ($currentPage - 1) * $pageNum;

        if($_REQUEST['phone'] != ''){
            $where = "where m.phone like '%".$_REQUEST['phone']."%'";
        }

        if($_REQUEST['fullname'] != ''){
            $where .= $where?" and m.fullname like '%".$_REQUEST['fullname']."%'":"where m.fullname like '%".$_REQUEST['fullname']."%'";
        }

        $sql = "SELECT
                    m.ticket_id,
                    m.fullname,
                    m.phone,
                    m.text,
                    m.created,
                  t.user_id,
                    a.res_id,
                    s.`value`,
                    s.`title`
                FROM
                    lz_ticket_messages m
                LEFT JOIN lz_tickets t ON m.ticket_id = t.id
                LEFT JOIN lz_ticket_attachments a ON a.parent_id = t.id
                LEFT JOIN lz_resources s ON s.id = a.res_id
                $where 
                ORDER BY m.id DESC 
                LIMIT $rows ".",".$pageNum;
        $sql2 = "SELECT count(*) as c  FROM
                    lz_ticket_messages m
                LEFT JOIN lz_tickets t ON m.ticket_id = t.id
                LEFT JOIN lz_ticket_attachments a ON a.parent_id = t.id
                LEFT JOIN lz_resources s ON s.id = a.res_id
                $where 
                ";

        $db =  M()->db(1,C('KF_DB'));
        $list = $db->query($sql);

        $count = $db->query($sql2);
        $this->assign('list',$list);
        $this->assign ( 'totalCount', $count[0]['c'] );
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', $currentPage);
        $this->display();
    }


}