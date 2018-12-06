var game = {
    hash: null,
    total_clips: 0,
    current_clip_index: 0,
    hint: null,
    time_limit: 0,
    start_game: false,
    stats: null,
    status: null,
    page: 1,
    total: 0,
    show_hint_btn: false,
    start: 0,
    finish: 0,

    startGame: function() {
        $(".vjs-text-track-display").css("display","none !important");
        var that = this;
        autoplay = false;
        that.showLoader();
        var processPercent = Math.round((that.current_clip_index/that.total_clips)*100);
        $('#proccess_game').show();
        $('#proccess_game .progress-bar').attr('aria-valuenow', processPercent).css('width', processPercent + '%').html(processPercent + '% Complete');
        $('#game_elements blockquote').html(that.hint);
        $('#game_elements textarea').val('');
        var hint_btn = $('#show_hint_btn');
        if (that.show_hint_btn) {
            hint_btn.show();
        } else {
            hint_btn.hide();
        }
        that.hideLoader();
        if (that.finish) {
            that.loopVideo(that.start, that.finish);
        }
        autoplay = $('body').width() > 450;
    },
    loopVideo: function(start, finish) {
        player.abLoopPlugin.setStart(start).setEnd(finish).enable().playLoop();
    },
    checkResult: function () {
        var that = this;
        var textarea = $('#game_elements textarea');
        that.showLoader();

        var result = textarea.val();
        $.post('/game/' + that.hash + '/' + that.current_clip_index + '/result', {result: result}, function(data) {
            if(!data || !data.correct_text) {
                $('#player_video').noty({
                    text: data ? data.error : 'Unknown error, try later...',
                    type: 'error',
                    timeout: 60000,
                    maxVisible: 1,
                    force: true,
                    layout: "bottomCenter",
                    killer: true,
                    dismissQueue: false
                });
                that.hideLoader();
                return;
            }

            var percent = $('#percent');
            percent.removeClass('bg-success');
            percent.removeClass('bg-danger');
            if(data.percent >= 60) {
                percent.addClass('bg-success');
            } else {
                percent.addClass('bg-danger');
            }

            var error = $('#errors');
            error.removeClass('bg-success');
            error.removeClass('bg-danger');
            if(data.errors >= 1) {
                error.addClass('bg-danger');
            } else {
                error.addClass('bg-success');
            }

            that.current_clip_index++;

            error.find('code').html(data.errors);
            percent.find('code').html(data.percent + '%');
            $('#correct_text').html(data.correct_text + (data.translate ? '<p><i>('+data.translate+')</i></p>' : ''));
            $('#result').html(textarea.val());

            $('#game_loader').hide();
            $('#game_result').show();
        });
    },
    showHint: function() {
        var that = this;

        if (!that.show_hint_btn) {
            return;
        }

        var hint_btn = $('#show_hint_btn');
        hint_btn.hide();
        that.show_hint_btn = false;
        that.showLoader();
        $.post('/game/' + that.hash + '/' + that.current_clip_index + '/hint', {game: that.hash}, function(data) {
            that.hideLoader();
            if(!data || !data.hint) {
                $('#player_video').noty({
                    text: data ? data.error : 'Unknown error, try later...',
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
            $('#game_elements blockquote').html(data.hint);
        });
    },
    showLoader: function() {
        $('.start_game').hide();
        $('#game_elements').hide();
        $('#game_result').hide();
        $('#game_loader').show();
    },
    hideLoader: function() {
        $('#game_elements').show();
        $('#game_loader').hide();
    },
    initTimer: function() {
        var i = 100;
        var that = this;

        var counterBack = setInterval(function(){
            i--;
            if (i > 0){
                $('#timer_game .progress-bar').css('width', i+'%');
            } else {
                clearInterval(counterBack);
                if(!that.start_game) {
                    that.playNextClip();
                }
            }

        }, Math.round(that.time_limit / 100) * 1000);
    },
    
    getStats: function(user_id) {
        var that = this;

        $.get('/api/games/stats', {user_id: user_id}, function(data) {
            if(!data || !data.finished) {
                $('#games').noty({
                    text: data ? data.error : 'Unknown error, try later...',
                    type: 'error',
                    timeout: 60000,
                    maxVisible: 1,
                    force: true,
                    layout: "bottomCenter",
                    killer: true,
                    dismissQueue: false
                });
                that.hideLoader();
                return;
            }

            that.stats = data;
            $('#progress').html(data.progress);
            $('#finished').html(data.finished);
            $('#abandoned').html(data.abandoned);

            that.showGames(user_id);
        });
    },
    showGames: function(user_id) {
        var that = this;
        that.showLoader();
        $('.next').remove();

        if (that.stats === null) {
            that.getStats(user_id);
            return;
        }

        var status = $('.game_status li.active').data('status');

        if(status !== that.status) {
            that.page = 1;
            that.status = status;
            that.total = 0;
            $('.game').remove();
        } else {
            that.page++;
        }

        var gamesItem = '<div class="col-md-4 img-portfolio game">' +
            '<a target="_blank" href="{link}">' +
            '<img class="img-responsive img-hover" src="{thumb}" alt="">' +
            '</a>' +
            '<h4>' +
            '<a target="_blank" href="{link}">{title}</a>' +
        '</h4>' +
        '<p>{description}</p>' +
        '</div>';

        var gamesHtml = '';

        $.get('/api/games/' + status, {user_id: user_id, page: that.page}, function(data) {
            if(!data) {
                $('#games').noty({
                    text: data ? data.error : 'Unknown error, try later...',
                    type: 'error',
                    timeout: 60000,
                    maxVisible: 1,
                    force: true,
                    layout: "bottomCenter",
                    killer: true,
                    dismissQueue: false
                });
                that.hideLoader();
                return;
            }

            data.forEach(function(item) {
                var link = '/game/' + item.hash;
                var thumb = item.video_clip.thumb ? item.video_clip.thumb : item.video_clip.series.poster_url;
                var title = item.video_clip.title;
                var dateStart = new Date(item.game_start);
                var options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric'
                };

                var description = 'Attempt: ' + item.attempt_number + '. Started at ' + dateStart.toLocaleString("en-US", options);

                gamesHtml += gamesItem
                    .replace('{link}', link)
                    .replace('{link}', link)
                    .replace('{thumb}', thumb)
                    .replace('{title}', title)
                    .replace('{description}', description);

                that.total++;
            });

            if (that.total < Number.parseInt($('#' + status).html())) {
                gamesHtml += '<div class="next">' +
                    '<a href="#" class="button" onclick="game.showGames('+user_id+');return false;">Показать еще →</a>' +
                '</div>'
            }

            $('#game_loader').before(gamesHtml);
            that.hideLoader();
        });
    },
    playNextClip: function() {
        var that = this;

        var timer = $('#timer_game');

        if (timer.length) {
            timer.hide();
            $('#timer_game2').hide();
        }
        that.start_game = true;

        if (that.current_clip_index >= that.total_clips) {
            location.href = '/game/' + that.hash + '/finish';
            return;
        }

        that.showLoader();
        $.post('/game/' + that.hash + '/' + that.current_clip_index, {game: that.hash}, function(data) {
            if(!data || !data.hash) {
                $('#player_video').noty({
                    text: data ? data.error : 'Unknown error, try later...',
                    type: 'error',
                    timeout: 60000,
                    maxVisible: 1,
                    force: true,
                    layout: "bottomCenter",
                    killer: true,
                    dismissQueue: false
                });
                that.hideLoader();
                return;
            }

            if(data.penalty) {
                location.href = data.link;
                return;
            }

            that.translation = data.translation;
            that.hint = data.hint;
            hash_video_clip = data.hash;
            that.start = data.start;
            that.finish = data.finish;
            that.show_hint_btn = data.hints > 0;

            that.startGame();
        });
    }
};
