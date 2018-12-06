var countries = [], genres = [], types = [], levels = [], lengths = [], loadIterations = 0;

function show_more(addr, p, s, target) {
    $('.next').remove();
    var loader = '<p id="loader"><img src="/bundles/video/images/ajax-loader.gif" /></p>';
    if(p == 1) {
        $('#results').html(loader);
        loadIterations = 0;
    } else {
        $('div.series:last').after(loader);
    }
    $.get(addr,{ajax:1,p:p, s:s, target:target, countries: countries, genres: genres, types: types, levels: levels, lengths: lengths},function(data){
        if(p == 1) {
            $('#results').html(data);
        } else {
            $('#loader').remove();
            $('div.series:last').after(data);
        }
        loadIterations++;
    });
}

function initNextButton() {
    $('.next').waypoint(function() {
        if(loadIterations < 3) {
            $('.next a').trigger('click');
        }
    },
    {
        offset: '80%'
    });
}

function formatSeries (item) {
    if (item.loading) return item.text;

    var markup = "<div class='select2-result-repository clearfix'>" +
        "<div class='select2-result-repository__avatar' style='float: left; margin-right: 5px;'><img style='width:50px' src='" + item.poster_url + "' /></div>" +
        "<div class='select2-result-repository__meta'>" +
        "<div class='select2-result-repository__title'>" + item.en_title + "</div>" +
        "</div></div>";

    return markup;
}

function formatSeriesSelection (item) {
    return item.en_title;
}

$(function () {
    initNextButton();
});