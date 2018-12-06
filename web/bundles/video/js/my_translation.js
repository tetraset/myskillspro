var wordForOwnTranslation;
var refreshAfterAddingOwnTranslation = false;
$(function() {
	$('#addUpdateModalButton').on('click', function (event) {
		saveUserTranslation(wordForOwnTranslation, $('#translation-text').val());
	});
});
function addOwnTranslation(word, isRefreshPage) {
	if(typeof player != 'undefined') {
		player.pause();
	}
	handPause = true;
	wordForOwnTranslation = word;
	$('#translation-text').val('');
	$('#addUpdateModal').modal('show');
	availableTranslationForm();
	refreshAfterAddingOwnTranslation = typeof isRefreshPage != 'undefined' && isRefreshPage;
}
function removeTranslation(id_translation) {
	if(!id_translation || !confirm('Вы действительно намерены удалить данный перевод?')) {
		return false;
	}
	$('.translation-panel-' + id_translation + ' btn-remove').attr('disabled', 'disabled');
	$.ajax({
		url: '/api/entranslate/remove',
		method: "DELETE",
		data: {id_translation: id_translation},
		dataType: "json"
	}).done(
		function(data) {
			$('.translation-panel-' + id_translation).fadeOut();
		})
		.fail(function(data){
			$('.translation-panel-' + id_translation).eq(0).noty({
				text: data.responseJSON.error,
				type: 'error',
				timeout: 60000,
				maxVisible: 1,
				force: true,
				layout: "topCenter",
				killer: true,
				dismissQueue: false
			});
			$('.translation-panel-' + id_translation + ' btn-remove').removeAttr('disabled');
		});
}
function saveUserTranslation(word, translation, isRefreshPage) {
	if(!word || !translation) {
		return false;
	}
	disableTranslationForm();
	$.ajax({
		url: '/api/entranslate/add',
		method: "POST",
		data: {word: word, translation: translation},
		dataType: "json"
	}).done(
		function(data) {
			var target = null;
			if($('.video-js').length) {
				target = $('.video-js');
			} else if($('.navbar-fixed-top').length) {
				target = $('.navbar-fixed-top');
			} else {
				target = $('body');
			}
			target.noty({
				text: '<strong><i class="glyphicon glyphicon-ok"></i> Перевод успешно добавлен. Скоро он будет доступен в вашем и общем словарях</strong>',
				type: 'success',
				timeout: 10000,
				maxVisible: 1,
				force: true,
				layout: "bottomCenter",
				killer: true,
				dismissQueue: false
			});
			$('#addUpdateModal').modal('hide');
			availableTranslationForm();
			if(refreshAfterAddingOwnTranslation) {
				window.location.reload();
			}
	}).fail(function(data){
		$('#addUpdateModal .modal-body').noty({
			text: data.responseJSON.error,
			type: 'error',
			timeout: 60000,
			maxVisible: 1,
			force: true,
			layout: "topCenter",
			killer: true,
			dismissQueue: false
		});
		availableTranslationForm();
	});
}
function disableTranslationForm() {
	$('#addUpdateModalButton').html('<i class="fa fa-cog fa-spin fa-1x fa-fw"></i>');
	$('#addUpdateModal textarea').attr('disabled', 'disabled');
	$('#addUpdateModal button').attr('disabled', 'disabled');
	$('.btn-remove').attr('disabled', 'disabled');
}
function availableTranslationForm() {
	$('#addUpdateModalButton').html('<i class="fa fa-plus" aria-hidden="true"></i> Добавить');
	$('#addUpdateModal textarea').removeAttr('disabled');
	$('#addUpdateModal button').removeAttr('disabled');
	$('.btn-remove').removeAttr('disabled');
}