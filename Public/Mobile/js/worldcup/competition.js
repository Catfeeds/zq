// table切换
//定义数组并获取数组内各个的节点
var buttonArr = document.getElementsByClassName("choice");
var divArr = document.getElementsByClassName("finals-box");
for (var i = 0; i < buttonArr.length; i++) {
    buttonArr[i].onclick = function () {
        //this
        // alert(this.innerHTML)
        //for循环遍历button数组长度
        for (var j = 0; j < buttonArr.length; j++) {
            //重置所有的button样式
            buttonArr[j].style.color = "#ffffff";
            //给当前的(点击的那个)那个button添加样式
            this.style.color = "#fff04e";
            //隐藏所有的div
            divArr[j].style.display = "none";
            //判断当前点击是按钮数组中的哪一个？
            if (this == buttonArr[j]) {
                // alert(j);
                //显示点击按钮对应的div
                divArr[j].style.display = "block";
            }
        }
    }
}
