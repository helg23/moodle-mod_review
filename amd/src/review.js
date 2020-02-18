define(['jquery', 'core/ajax', 'jqueryui'], function($,ajax) {
    return {
        init: function() {
            var save_rate=function(e) {
                e.preventDefault();
                var reviewid=0;
                var rate=0;
                var star = $(this);
				var classList = star.attr('class').split(/\s+/);
				/*get params of selected star from its css class.
				we can't use data-... fields here, cause course page ignores them*/
				$.each(classList, function(index, classname) {
					if(classname.startsWith('yfdr')){
						var classparts=classname.split(/_/);
						reviewid=classparts[1];
						rate=classparts[2];
					}
				});
                star.css('opacity', '0.3');
				/*send request to update rating*/
                var requests = ajax.call([{
                    methodname : 'mod_review_save_rate',
                    args: {'reviewid':reviewid,'rate':rate}}
                ]);
                requests[0]
                    .done(function(result){
                        if (result.result===1){
                            /*change rating display in a main form*/
                            $(".your_rate_stars a.star").each(function() {
								var current_rate=0;
								$.each($(this).attr('class').split(/\s+/), function(index, classname) {									
									if(classname.startsWith('yfdr')){
										var classparts=classname.split(/_/);
										current_rate=classparts[2];
									}
								});
								$(this).toggleClass('star_notempty',current_rate<=rate);
                            });
                            /*сhange rating display in review list*/
                            $("#review_rate"+result.userreview_id+" span.star").each(function() {
								$(this).toggleClass('star_notempty',$(this).data('rate')<=rate);
                            });
                            /*update the statistics block*/
                            $("#rates_stat_container").html(result.stat);
                            star.css('opacity', '1').slow(3000);
                        }
                    })
                    .fail(function(){
                        alert('save rate error');
                        star.css('opacity', '1');
                    });
            };

            /*widget to change review status*/
            var switcher_settings={
                containment: "parent",
                grid: [ 20, 50 ],
                stop: function(event,ui) {
                    var switcher=$(event.target);
                    var reviewid=switcher.data('reviewid');
                    var new_status=Math.round(ui.position.left/20)+1;
                    var requests = ajax.call([{
                        methodname : 'mod_review_save_status',
                        args: {'user_reviewid':reviewid,'status':new_status}}
                    ]);
                    requests[0]
                        .done(function(result){
                            if (result.result===1){
                                $("#status_container"+reviewid).html(result.switcher);
                                $('.status_switcher .status.draggable').draggable(switcher_settings);
                            }
                        })
                        .fail(function(){alert('save status error');});
                }
            };

            var save_status=function(e) {
                e.preventDefault();
                var link=$(this);
                var reviewid=link.data('reviewid');
                var status=link.data('status');
                /*request to change status*/
                var requests = ajax.call([{
                    methodname : 'mod_review_save_status',
                    args: {'user_reviewid':reviewid,'status':status}}
                ]);
                requests[0]
                    .done(function(result){
                        /*update switcher widget*/
                        if (result.result===1){
                            $("#status_container"+reviewid).html(result.switcher);
                            $('.status_switcher .status.draggable').draggable(switcher_settings);
                        }
                    })
                    .fail(function(){alert('save status error');});
            };

            /*set event handlers*/
            $('.your_rate_stars .star').click(save_rate);
            $(document).on('click', '.status_switcher .status',save_status);
            $('.status_switcher .status.draggable').draggable(switcher_settings);
        }
    };
});