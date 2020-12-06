window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'UA-128181978-1');

$(function(){
  $('.links').on('inview', function(event, isInView, visiblePartX, visiblePartY) {
    if(isInView){
      $(this).stop().removeClass('f_before').addClass('f_after');
    }
  });
});
