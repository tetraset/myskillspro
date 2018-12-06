//асинхронная загрузка API Youtube
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var currentUrl = location.href;

function resizeVideo() {
    console.log('resize video');
    $('.video-js').each(function() {
        var w = $(this).width();
        var h = $(this).height();

        var newWidth = $(this).parent().width();
        var resize = newWidth / w;
        var newHeight = resize * h;
        $(this).css('width', '100%');
        if (newHeight > 0) {
            $(this).css('height', newHeight+'px');
        }
    });
}

function resizeImages() {
    $('.box img').each(function() {
        $(this).removeAttr('width');
        $(this).removeAttr('height');
    });
}

function resize() {
    resizeVideo();
    resizeImages();
}

$(document).ready(function(){
    resize();
    // навешиваем на событие поворота экрана
    window.addEventListener("orientationchange", function() {
        resize();
    }, false);
    // навешиваем на событие ресайза окна
    window.onresize = function() {
        resize();
    };
});