$(function(){
    $.get('/clear/session', {n:Math.random()}, function() {
        console.log('clear session');
    });
});
