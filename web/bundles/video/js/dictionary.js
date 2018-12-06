function show_more_words(p) {
	$('.next').remove();
	$('div.dictionary-word:last').after('<p id="loader"><img src="/bundles/video/images/ajax-loader.gif" /></p>');
	$.get('/dictionary',{ajax:1,p:p},function(data){
		$('#loader').remove();
		$('div.dictionary-word:last').after(data);
	});
}
function readMoreInit() {
	$('.translation_html').readmore({
		moreLink: '<a class="more_less" href="#">Показать еще</a>',
		lessLink: '<a class="more_less" href="#">Скрыть</a>',
		speed: 50,
	});
}
$(function(){
	$('#accordion').on('shown.bs.collapse', function () {
		readMoreInit();
	})
});