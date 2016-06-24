/* Copyright 2013-2016 Zachary Doll */
$(function () {
    // Set variables from configs
    var efx = gdn.definition('LatestPostListEffects');
    var frequency = gdn.definition('LatestPostListFrequency');
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
                    updateList(data);
                }
                setTimeout(getLatestPosts, frequency * 1000);
            }
        });
    }

    function updateList(data) {
        lastDate = data.date;
        switch (efx) {
            case '1':
                var newListItems = $('<ul id="LPLNUl" />').html(data.list).contents();
                $("#LPLUl li").each(function (index) {
                    $(this).delay(200 * index).hide('slow', function () {
                        $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                        $(this).show('slow');
                    });
                });
                break;
            case '2':
                $("#LPLUl").fadeOut('slow', function () {
                    $(this).html(data.list);
                    $("#LPLUl").fadeIn('slow');
                });
                break;
            case '3':
                var newListItems = $('<ul id="LPLNUl" />').html(data.list).contents();
                $("#LPLUl li").each(function (index) {
                    $(this).delay(200 * index).fadeOut('slow', function () {
                        $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                        $(this).fadeIn(350);
                    });
                });
                break;
            case '4':
                var newListItems = $('<ul id="LPLNUl" />').html(data.list).contents();
                $("#LPLUl li").each(function (index) {
                    $(this).delay(200 * index).slideToggle('slow', function () {
                        $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                        $(this).slideToggle('slow');
                    });
                });
                break;
            case '5':
                var newListItems = $('<ul id="LPLNUl" />').html(data.list).contents();
                $("#LPLUl li").each(function (index) {
                    var oldHeight = $(this).height();
                    $(this).delay(200 * index).animate({opacity: 'toggle', width: 'toggle', height: oldHeight}, 'slow', function () {
                        $(this).html(newListItems.filter(':nth-child(' + (index + 1) + ')').html());
                        $(this).animate({opacity: 'toggle', width: 'toggle', height: oldHeight}, 'slow', function () {
                            $(this).css({height: ''});
                        });

                    });
                });
                break;
            default:
            case 'none':
                $("#LPLUl").html(data.list);
                break;
        }
    }

    if (frequency > 0) {
        setTimeout(getLatestPosts, frequency * 1000);
    }
});