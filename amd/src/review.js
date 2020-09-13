define(['jquery', 'core/ajax', 'jqueryui'], function($, ajax) {
    return {
        init: function() {
            var save_rate = function(e) {
                e.preventDefault();
                var reviewid = 0;
                var rate = 0;
                var star = $(this);
                var classList = star.attr('class').split(/\s+/);
                /* Get params of selected star from its css class.*/
                /* We can't use data- fields here, cause course page ignores them*/
                $.each(classList, function(index, classname) {
                    if (classname.startsWith('yfdr')) {
                        var classparts = classname.split(/_/);
                        reviewid = classparts[1];
                        rate = classparts[2];
                    }
                });
                star.css('opacity', '0.3');
                /* Send request to update rating*/
                var requests = ajax.call([{
                    methodname: 'mod_review_save_rate',
                    args: {'reviewid': reviewid, 'rate': rate}}
                ]);
                requests[0]
                    .done(function(result) {
                        if (result.result === 1) {
                            /* Change rating display in a main form*/
                            $(".your_rate_stars a.star").each(function() {
                                var currentRate = 0;
                                $.each($(this).attr('class').split(/\s+/), function(index, classname) {
                                    if (classname.startsWith('yfdr')) {
                                        var classparts = classname.split(/_/);
                                        currentRate = classparts[2];
                                    }
                                });
                                $(this).toggleClass('star_notempty', currentRate <= rate);
                            });
                            /* Change rating display in review list*/
                            $("#review_rate" + result.userreview_id + " span.star").each(function() {
                                $(this).toggleClass('star_notempty', $(this).data('rate') <= rate);
                            });
                            /*update the statistics block*/
                            $("#rates_stat_container").html(result.stat);
                            star.css('opacity', '1').slow(3000);
                        }
                    })
                    .fail(function() {
                        console.log('save rate error');
                        star.css('opacity', '1');
                    });
            };

            /* Widget to change review status*/
            var switcher_settings = {
                containment: "parent",
                grid: [ 20, 50 ],
                stop: function(event, ui) {
                    var switcher = $(event.target);
                    var reviewId = switcher.data('reviewid'); 
                    var newStatus = Math.round(ui.position.left / 20) + 1;
                    var requests = ajax.call([{
                        methodname: 'mod_review_save_status',
                        args: {'user_reviewid': reviewId,'status': newStatus}
                    }]);
                    requests[0]
                        .done(function(result) {
                            if (result.result===1) {
                                $("#status_container" + reviewId).html(result.switcher);
                                $('.status_switcher .status.draggable').draggable(switcher_settings);
                            }
                        })
                        .fail(function() {
                            console.log('save status error');
                        });
                }
            };

            var saveStatus = function(e) {
                e.preventDefault();
                var link = $(this);
                var reviewId = link.data('reviewid');
                var status = link.data('status');
                /* Request to change status*/
                var requests = ajax.call([{
                    methodname: 'mod_review_save_status',
                    args: {'user_reviewid': reviewId, 'status': status}
                }]);
                requests[0]
                    .done(function(result) {
                        /* Update switcher widget*/
                        if (result.result===1) {
                            $("#status_container" + reviewId).html(result.switcher);
                            $('.status_switcher .status.draggable').draggable(switcher_settings);
                        }
                    })
                    .fail(function() {
                        console.log('save status error');
                    });
            };

            /* Set event handlers*/
            $('.your_rate_stars .star').click(save_rate);
            $(document).on('click', '.status_switcher .status', saveStatus);
            $('.status_switcher .status.draggable').draggable(switcher_settings);
        }
    };
});