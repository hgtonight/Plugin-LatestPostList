/* Copyright 2013-2016 Zachary Doll */
$(function () {
    // Set variables from configs
    var inSettings = gdn.definition('LatestPostListSettings', false);
    var efx = gdn.definition('LatestPostListEffects', 'none');
    var frequency = gdn.definition('LatestPostListFrequency', 120);
    var lastDate = gdn.definition('LatestPostListLastDate');

    function getLatestPosts() {
        var url = gdn.url('/plugin/latestpostlist/getnewlist');

        $.ajax({
            url: url,
            global: false,
            type: "GET",
            data: null,
            dataType: "json",
            success: function (data) {
                if (data.date !== lastDate) {
                    lastDate = data.date;
                    updateList(efx, data);
                }
                setTimeout(getLatestPosts, frequency * 1000);
            }
        });
    }

    function updateList(effect, data) {
        var newListItems = $('<ul id="LPLNUl" />').html(data.list).contents();
        $("#LPLUl li").each(function (index) {
            switch (effect) {
            case '1':
                $(this).delay(200 * index).hide('slow', function () {
                    $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                    $(this).show('slow');
                });
                break;
            case '2':
                $(this).fadeOut('slow', function () {
                    $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                    $(this).fadeIn('slow');
                });
                break;
            case '3':
                $(this).delay(200 * index).fadeOut('slow', function () {
                    $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                    $(this).fadeIn(350);
                });
                break;
            case '4':
                $(this).delay(200 * index).slideToggle('slow', function () {
                    $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                    $(this).slideToggle('slow');
                });
                break;
            case '5':
                var oldHeight = $(this).height();
                $(this).delay(200 * index).animate({opacity: 'toggle', width: 'toggle', height: oldHeight}, 'slow', function () {
                    $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                    $(this).animate({opacity: 'toggle', width: 'toggle', height: oldHeight}, 'slow', function () {
                        $(this).css({height: ''});
                    });
                });                
                break;
            default:
            case 'none':
                    $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                break;
            }
        });
    }

    if (frequency > 0 && !inSettings) {
        setTimeout(getLatestPosts, frequency * 1000);
    }
    
    if(inSettings) {
        var newItems = {list: $('#LPLNewItems li').html()};
        $('#Form_LatestPostList-dot-Effects').change(function () {
            updateList($(this).find(':selected').val(), newItems);
        });
    }
});