//显示选择状态
function showState(){ 
   //进球提示 - 声音
   if (localStorage.getItem("scoreSound") == 1){
      document.getElementById("scoreSound").checked = true;
   }
   //进球提示 - 弹窗
   if (localStorage.getItem("scoreTan") == 1){
      document.getElementById("scoreTan").checked = true;
   } 

   //红牌提示 - 声音
   if (localStorage.getItem("redSound") == 1){
      document.getElementById("redSound").checked = true;
   } 
   //红牌提示 - 弹窗
   if (localStorage.getItem("redTan") == 1){
      document.getElementById("redTan").checked = true;
   } 

   //黄牌显示
   if (localStorage.getItem("yellowShow") == 1){
      document.getElementById("yellowShow").checked = true;
   } 
   //排名显示
   if (localStorage.getItem("rankShow") == 1){
      document.getElementById("rankShow").checked = true;
   } 
   
    //语言选择
    var lang = document.getElementsByName("lang");
    if(localStorage.getItem("language") == 0){
       lang[0].checked =true;
    }
    if(localStorage.getItem("language") == 1){
       lang[1].checked =true;
    }

    //页面刷新间隔
    $('#refreshTime').val(localStorage.getItem("refreshTime"));
}

//进球提示 - 声音
function clickScoreSound(obj){
  if(obj.checked){
     localStorage.setItem("scoreSound",1);
  }else{
     localStorage.setItem("scoreSound",0)
  }
}
//进球提示 - 弹窗
function clickScoreTan(obj){
  if(obj.checked){
     localStorage.setItem("scoreTan",1);
  }else{
     localStorage.setItem("scoreTan",0)
  }
}


//红牌提示 - 声音
function clickRedSound(obj){
  if(obj.checked){
     localStorage.setItem("redSound",1);
  }else{
     localStorage.setItem("redSound",0)
  }
}
//红牌提示 - 弹窗
function clickRedTan(obj){
  if(obj.checked){
     localStorage.setItem("redTan",1);
  }else{
     localStorage.setItem("redTan",0)
  }
}

//黄牌显示 
function clickYellowShow(obj){
  if(obj.checked){
     localStorage.setItem("yellowShow",1);
  }else{
     localStorage.setItem("yellowShow",0)
  }
}
//排名显示 
function clickRankShow(obj){
  if(obj.checked){
     localStorage.setItem("rankShow",1);
  }else{
     localStorage.setItem("rankShow",0)
  }
}

//语言选择
function clickLanguage(obj){
  Cookie.setCookie('language', obj.value);
}

//页面刷新间隔
function selectRefresh(obj){
  localStorage.setItem("refreshTime",obj.options[obj.selectedIndex].value);
}














