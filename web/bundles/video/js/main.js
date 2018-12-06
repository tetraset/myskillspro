// video section

var handPause = true;
var hardPause = false;
var cash = {};
var lastSelectedText = null;
var isMobile = false;
var isMouseEnter = false;
var activeCueText = null;
var oldWord = null;
var autoplay = false;
var notyBase;
var toolboxContainer;
var hoverTarget;
var player;
if($('#episode_video').length) {
	player = videojs('episode_video',
		{
			html5: {
				nativeTextTracks: $('body').width() < 450
			},
			plugins: {
				abLoopPlugin: {
					start:0    	//in seconds - defaults to 0
					,end:false    	//in seconds. Set to  false to loop to end of video. Defaults to false
					,enabled:false			//defaults to false
					,loopIfBeforeStart:false //allow video to play normally before the loop section? defaults to true
					,loopIfAfterEnd:true	// defaults to true
					,pauseAfterLooping: false     	//if true, after looping video will pause. Defaults to false
					,pauseBeforeLooping: false     	//if true, before looping video will pause. Defaults to false
					,createButtons: false		//defaults to true
				}
			}

		}, function() {
			this.on('loadeddata', function(){
				var tracks = this.textTracks();

				if(!tracks.length) {
					return;
				}

				var enTrack = tracks[0];
				enTrack.oncuechange = function () {
					var cue = this.activeCues[0]; // assuming there is only one active cue
					if (typeof cue != 'undefined' && typeof cue.text != 'undefined' && cue.text.toString().length && cue.text.indexOf('<i.word>') === -1) {
						activeCueText = cue.text;
						var words = activeCueText.split(' ');
						var newCueText = '';

						$.each(words, function(i,val) {
							newCueText += val.toString().replace("<br/>", "\n").replace(new RegExp("([a-z0-9\'\-]{2,})", "ig"), '<i.word>$1</i>') + ' ';
						});

						cue.text = newCueText.trim();

						if ($('body').width() < 350) {
							var div = WebVTT.convertCueToDOMTree(window, cue.text);
							$('#subtitles').html(div);
						}
					}
				};
				$('.vjs-playback-rate').attr('title', 'Play speed');
				if (autoplay) {
					this.play();
				}
			});
		}
	);
	notyBase = $('.video-js');
	toolboxContainer = '.video-js';
} else {
	notyBase = $('.navbar-fixed-top');
	toolboxContainer = '.bookContent';
}

function changeVideo(url) {
	handPause = true;
	player.src({"type":"video/mp4", "src":url});
	player.play();
	return false;
}
function changePoster(url) {
	player.poster(url);
}
function changeSubs(url) {
	var oldTracks = player.remoteTextTracks();
	var i = oldTracks.length;
	while (i--) {
		player.removeRemoteTextTrack(oldTracks[i]);
	}
	player.addRemoteTextTrack({kind:"subtitles", srclang:"en", label:"English", src: url, mode: 'showing'});
	player.remoteTextTracks()[0].mode = 'showing';
}
$(function() {
	isMobile = $('body').width() < 350;
	$('.vjs-text-track-display').addClass('subtitle-display');
	if ($('#episode_video').length) {
		player.on('play', function(){
			resetEnvironment();
		});
		player.on('pause', function() {
			if( !hardPause ) {
				handPause = true;
			}
		});
		player.on('error', function() {
			console.log('error, reinit: ' + player.currentSrc());
			changeVideo(player.currentSrc());
		});
		videojs('episode_video').ready(function() {
			this.hotkeys({
				volumeStep: 0.1,
				seekStep: 5,
				enableModifiersForNumbers: false
			});
		});
	}

	// hover on subtitles block
	$('body').on('mouseenter', '.subtitle-display div div div', function(e){
		if (!$('#episode_video').length) {
			return;
		}
		isMouseEnter = true;
		pauseVideo();
	});
	// leave subtitles & tooltip blocks
	$('body').on('mouseleave', '.subtitle-display div div div', function(e){
		if (!$('#episode_video').length) {
			return;
		}
		isMouseEnter = false;
		setTimeout(function() {
			if (!isMouseEnter && player.paused() && !handPause) {
				player.play();
				hardPause = false;
				$('.word').tooltip('destroy');
			}
		}, 300);
	});
	// click subtitles block (mobile)
	$('body').on('click', '#subtitles,.subtitle-display div div div', function(e){
		if ($('#episode_video').length) {
			player.pause();
		}
		hardPause = true;
	});
	// close tooltip
	$('body').on('click', '.close_tooltip', function(e){
		$('.word').tooltip('destroy');
		$('.tooltip').remove();
	});
	// play/pause video by clicking display
	$('.subtitle-display').click(function () {
		togglePause();
	});
	// translate word by hovering on one
	$('body').on('mouseenter', '.word', function(){
		var el = $(this);
		hoverTarget = el;
		if(el.hasClass('word')) {
			el.addClass('loading');
		}

		sleep(50);
		if (hoverTarget !== el) {
			if (hoverTarget) {
				hoverTarget.removeClass('loading');
			}
			el.removeClass('loading');
			return;
		}

		document.body.style.cursor='wait';

		translateWord(el, el.html(), false);
	});
	$('body').on('mouseleave', '.word', function(){
		hoverTarget = null;
	});
	// translate words or sentence by hovering on ones
	jQuery('body').on("textSelect", function (e) {
		e.preventDefault();
		e.stopPropagation();
		var string = $.event.special.textSelect.options.getSelectedText();
		if (string != "" && string != lastSelectedText) {
			var containsEn = string.match(/[a-z]/i);
			if( containsEn ) {
				document.body.style.cursor='wait';
				var el = $(e.target);

				if(el.hasClass('word')) {
					el.addClass('loading');
				}

				translateWord(el, string, true);
				lastSelectedText = string;
			}
		}
	});
	$('body').on('click', '.noty_text' ,function (e) {
		$.noty.closeAll();
	});
	$('body').on('click', '.add_word', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var isNoty = false;
		var el = $(this);
		var id_word = null, idVideo = null, timeOnVideo = null, subSearchText = null, hashVideoClip = null;
		if(el.hasClass('add_tooltip_noty')) {
			el.removeClass('add_word').addClass('add_tooltip_loading');
			isNoty = true;
		} else {
			el.removeClass('add_word').html('<img id="word_loader" src="/bundles/video/images/ajax-loader.gif" />');
			if ($('#episode_video').length) {
				idVideo = $('#episode_video').data('video_id');
				timeOnVideo = player.currentTime();
				var tracks = player.textTracks();
			}

			if(typeof hash_video_clip != 'undefined' && hash_video_clip) {
				hashVideoClip = hash_video_clip;
			} else if(activeCueText) {
				subSearchText = activeCueText;
			} else if($('#episode_video').length) {
				subSearchText = player.textTracks()[0].activeCues[0].text;
			} else if($('.word[data-original-title]').length) {
				subSearchText = $('.word[data-original-title]').closest('p').html().replace(/(<([^>]+)>)/ig,"");
			}
		}
		translation = el.parent().html();
		id_word = el.data('id_word');

		if (id_word == 'undefined') {
			notyBase.noty({
				text: 'No translations for this word',
				type: 'error',
				timeout: 60000,
				maxVisible: 1,
				force: true,
				layout: "bottomCenter",
				killer: true,
				dismissQueue: false
			});
			return;
		}

		$.post('/api/word/add', {id_word: id_word, id_video: idVideo, time_on_video: timeOnVideo, sub_search_text: subSearchText, hash_video_clip: hashVideoClip}, function(data) {
			if(data != 'ok') {
				if(!isNoty) {
					el.addClass('add_word').html('+');
				}
				notyBase.noty({
					text: data,
					type: 'error',
					timeout: 60000,
					maxVisible: 1,
					force: true,
					layout: "bottomCenter",
					killer: true,
					dismissQueue: false
				});
				return;
			}

			if(isNoty) {
				el.removeClass('add_tooltip_loading').addClass('add_tooltip_done');
			} else {
				el.html('✓');
			}
		});
	});
});
translateWord = function(el, w, isNoty) {
	var word = w;
	var s = word;
	if(!isNoty) {
		oldWord = word;
	}

	if(typeof cash[word] != 'undefined') {
		$('.word').tooltip('destroy');
		var id_word = cash[word].id_word;
		if(isNoty) {
			notyBase.noty({
				text: '<a href="#" data-id_word="'+id_word+'" title="сохранить себе" class="add_word add_tooltip_noty">+</a>'+cash[word].content,
				type: 'info',
				timeout: 60000,
				maxVisible: 1,
				force: true,
				layout: "bottomCenter",
				killer: true,
				dismissQueue: false,
				closeWith: ['button']
			});
		} else {
			el.tooltip(
				{
					'title': '<a href="#" data-id_word="'+id_word+'" title="сохранить себе"  class="add_word add_tooltip">+</a>'+cash[word].content + ' <a href="#" class="close_tooltip" data-dismiss="alert">×</a>',
					'html': true,
					'trigger': 'manual',
					'placement': 'top',
					'container': toolboxContainer,
					'template': '<div class="tooltip subtitle-display" role="tooltip"><div><div><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div></div></div>'
				}
			).on('shown.bs.popover', function (e) {
					var popover = jQuery(this);
					jQuery(this).parent().find('div.popover .close').on('click', function (e) {
						popover.popover('hide');
					});
				});
			el.tooltip('show');
		}
		document.body.style.cursor='default';
		el.removeClass('loading');
		return;
	}

	if(isNoty) {
		notyBase.noty({
			text: 'Перевожу... <img id="loader_translation" src="/bundles/video/images/ajax-loader.gif" />',
			type: 'info',
			maxVisible: 1,
			force: true,
			layout: "bottomCenter",
			killer: true,
			dismissQueue: false
		});
	}

	$.get('/api/entranslate', {word: s}, function(translations){
		var content = '';
		var translation_length = translations.length;
		var id_word;

		if( translation_length ) {
			translations.forEach(function(t) {
				if(t && typeof t == 'string') {
					content += "\n" + t;
				} else if(t.public_html_translation) {
					id_word = t.id_word;
					content = t.public_html_translation + "\n<span style='color: #888;'><i>" + t.public_source + "</i><span>"
				} else {
					content = "Нет переводов...";
				}
			});
		}
		content = (isNoty && word ? "["+word+"]\n" : "")+content;
		content = content.trim().replace(/\r?\n/g, '<br />');

		cash[word] = {'content': content, 'id_word': id_word};

		if(isNoty) {
			notyBase.noty({
				text: '<a href="#" data-id_word="'+id_word+'" title="сохранить себе"  class="add_word add_tooltip_noty">+</a>'+content,
				type: 'info',
				timeout: 60000,
				maxVisible: 1,
				force: true,
				layout: "bottomCenter",
				killer: true,
				dismissQueue: false,
				closeWith: ['button']
			});
		} else {
			if(oldWord != word) {
				return;
			}

			$('.word').tooltip('destroy');
			el.tooltip(
				{
					'title':'<a href="#" data-id_word="'+id_word+'" title="сохранить себе"  class="add_word add_tooltip">+</a>'+content + ' <a href="#" class="close_tooltip" data-dismiss="alert">×</a>',
					'html':true,
					'trigger':'manual',
					'placement':'top',
					'container': toolboxContainer,
					'template': '<div class="tooltip subtitle-display" role="tooltip"><div><div><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div></div></div>'
				}
			).on('shown.bs.popover', function(e){
					var popover = jQuery(this);
					jQuery(this).parent().find('div.popover .close').on('click', function(e){
						popover.popover('hide');
					});
				});
			el.tooltip('show');
		}
		document.body.style.cursor='default';
		el.removeClass('loading');
	}, 'json');
};
function sleep(ms) {
	ms += new Date().getTime();
	while (new Date() < ms){}
}
togglePause = function(){
	if (typeof player == 'undefined') {
		return false;
	}
	if (player.paused()) {
		player.play();
		handPause = true;
	} else {
		player.pause();
		handPause = false;
	}
};
resetEnvironment = function() {
	isMouseEnter = false;
	hardPause = false;
	handPause = false;
	$('.word').tooltip('destroy');
	$('.tooltip').remove();
	document.body.style.cursor='default';
	$('.loading').removeClass('loading');
	$.noty.closeAll();
};
pauseVideo = function() {
	if ($('#episode_video').length && !player.paused() && !handPause) {
		player.pause();
		hardPause = true;
	}
};

// store.js
/* Copyright (c) 2010-2016 Marcus Westin */
(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.store = f()}})(function(){var define,module,exports;return (function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
	(function (global){
		"use strict";module.exports=function(){function e(){try{return o in n&&n[o]}catch(e){return!1}}var t,r={},n="undefined"!=typeof window?window:global,i=n.document,o="localStorage",a="script";if(r.disabled=!1,r.version="1.3.20",r.set=function(e,t){},r.get=function(e,t){},r.has=function(e){return void 0!==r.get(e)},r.remove=function(e){},r.clear=function(){},r.transact=function(e,t,n){null==n&&(n=t,t=null),null==t&&(t={});var i=r.get(e,t);n(i),r.set(e,i)},r.getAll=function(){},r.forEach=function(){},r.serialize=function(e){return JSON.stringify(e)},r.deserialize=function(e){if("string"==typeof e)try{return JSON.parse(e)}catch(t){return e||void 0}},e())t=n[o],r.set=function(e,n){return void 0===n?r.remove(e):(t.setItem(e,r.serialize(n)),n)},r.get=function(e,n){var i=r.deserialize(t.getItem(e));return void 0===i?n:i},r.remove=function(e){t.removeItem(e)},r.clear=function(){t.clear()},r.getAll=function(){var e={};return r.forEach(function(t,r){e[t]=r}),e},r.forEach=function(e){for(var n=0;n<t.length;n++){var i=t.key(n);e(i,r.get(i))}};else if(i&&i.documentElement.addBehavior){var c,u;try{u=new ActiveXObject("htmlfile"),u.open(),u.write("<"+a+">document.w=window</"+a+'><iframe src="/favicon.ico"></iframe>'),u.close(),c=u.w.frames[0].document,t=c.createElement("div")}catch(l){t=i.createElement("div"),c=i.body}var f=function(e){return function(){var n=Array.prototype.slice.call(arguments,0);n.unshift(t),c.appendChild(t),t.addBehavior("#default#userData"),t.load(o);var i=e.apply(r,n);return c.removeChild(t),i}},d=new RegExp("[!\"#$%&'()*+,/\\\\:;<=>?@[\\]^`{|}~]","g"),s=function(e){return e.replace(/^d/,"___$&").replace(d,"___")};r.set=f(function(e,t,n){return t=s(t),void 0===n?r.remove(t):(e.setAttribute(t,r.serialize(n)),e.save(o),n)}),r.get=f(function(e,t,n){t=s(t);var i=r.deserialize(e.getAttribute(t));return void 0===i?n:i}),r.remove=f(function(e,t){t=s(t),e.removeAttribute(t),e.save(o)}),r.clear=f(function(e){var t=e.XMLDocument.documentElement.attributes;e.load(o);for(var r=t.length-1;r>=0;r--)e.removeAttribute(t[r].name);e.save(o)}),r.getAll=function(e){var t={};return r.forEach(function(e,r){t[e]=r}),t},r.forEach=f(function(e,t){for(var n,i=e.XMLDocument.documentElement.attributes,o=0;n=i[o];++o)t(n.name,r.deserialize(e.getAttribute(n.name)))})}try{var v="__storejs__";r.set(v,v),r.get(v)!=v&&(r.disabled=!0),r.remove(v)}catch(l){r.disabled=!0}return r.enabled=!r.disabled,r}();
	}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
},{}]},{},[1])(1)
});