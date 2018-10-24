<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>跳转提示</title>
<style type="text/css">
	body {
	  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	  font-size: 14px;
	  line-height: 1.42857143;
	  color: #333;
	  background-color: #ffffff;
	}
	h2{
	  font-family: inherit;
	  line-height: 1.1;
	  color: #000;
	  font-size: 28px;
	  margin-bottom: 10px;
	  }
	*{ margin: 0; padding: 0;}
	.clearfix:after { content: ""; display: block; height: 0; clear: both; visibility: hidden; }
	.clearfix { display: inline-table; }
	*html .clearfix { height: 1%; }
	.clearfix { display: block; }
	*+html .clearfix { min-height: 1%; }
	.pull-left{ float: left;}
	.pull-right{ float: right;}
	.con{ width: auto; position: fixed; left: 50%; top: 50%; margin-left: -115px; margin-top: -32px;}
	.con .right{ margin-left: 20px;}
	.con .right p{ color: #8a8a8a; letter-spacing: 2px;}
	.con .right .text-orange{ color: #ff7e00;}
</style>
</head>
<body>
<div class="con clearfix">
	<div class="pull-left">
    	<img src="__PUBLIC__/Home/images/login/return-success.png" alt="笑脸" width="64" height="64">
    </div>
	<div class="pull-left right">
	<present name="message">
    	<h2><?php echo($message); ?></h2>
    <else/>
    	<h2><?php echo($error); ?></h2>
    </present>
		<p>
		页面自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait" class="text-orange"><?php echo($waitSecond); ?></b>
		</p>
    </div>
</div>
<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time <= 0) {
		location.href = href;
		clearInterval(interval);
	};
}, 1000);
})();
</script>
</body>
</html>
