<?php
use Think\Model;
class TestModel extends Model
{
    public function test(){
        echo time();
    }
}