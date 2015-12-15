jQuery(document).ready(function($){

    $('a.bbpvotes-post-vote-link').click(function(e){
        e.preventDefault();
        
        var link = $(this);
        var admin_links = link.parents('.bbp-admin-links');
        var reply_container = admin_links.parents('li');
        
        var ajax_data = {};
        

        if(link.hasClass('loading')) return false;
        
        var post_id = getURLParameter(link.attr('href'),'post_id');
        
        ajax_data._wpnonce=getURLParameter(link.attr('href'),'_wpnonce');
        ajax_data.post_id=post_id;
        ajax_data.action=getURLParameter(link.attr('href'),'action');

        $.ajax({
    
            type: "post",
            url: bbpvotesL10n.ajaxurl,
            data:ajax_data,
            dataType: 'json',
            beforeSend: function() {
                link.removeClass('.bbpvotes-db-loading .bbpvotes-db-success .bbpvotes-db-error');
                link.addClass('bbpvotes-db-loading');
            },
            success: function(data){

                if (data.success === false) {
                    link.addClass('bbpvotes-db-error');
                    console.log(data.message);
                }else if (data.success === true) {
                    link.addClass('bbpvotes-db-success');
                    
                    var scoreLink = admin_links.find('.bbpvotes-post-score-link');
                    if (data.score_text) scoreLink.text(data.score_text);
                    if (data.score_title) scoreLink.attr('title',data.score_title);

                    if(link.hasClass('bbpvotes-post-voteup-link')){

                        if(link.hasClass('bbpvotes-post-voted')){
                            link.text(bbpvotesL10n.vote_up);
                            link.removeClass('bbpvotes-post-voted');
                        }else{
                            link.text(bbpvotesL10n.you_voted_up);
                            link.addClass('bbpvotes-post-voted');
                        }

                        var voteDownLink = admin_links.find('.bbpvotes-post-votedown-link');
                        voteDownLink.removeClass('bbpvotes-post-voted');
                        voteDownLink.text(bbpvotesL10n.vote_down);
                        
                    }else if(link.hasClass('bbpvotes-post-votedown-link')){
                        
                        if(link.hasClass('bbpvotes-post-voted')){
                            link.text(bbpvotesL10n.vote_down);
                            link.removeClass('bbpvotes-post-voted');
                        }else{
                            link.text(bbpvotesL10n.you_voted_down);
                            link.addClass('bbpvotes-post-voted');
                        }
                        
                        var voteUpLink = admin_links.find('.bbpvotes-post-voteup-link');
                        voteUpLink.removeClass('bbpvotes-post-voted');
                        voteUpLink.text(bbpvotesL10n.vote_up);
                    }
                    
                    //append vote log
                    var reply_content = reply_container.find('.bbp-reply-content');
                    var current_votes_log = reply_content.find('.bbpvotes-post-votes-log');
                    
                    var ajax_data_log = {
                        'post_id'   :   post_id,
                        'action'    :   'bbpvotes_get_votes_log'
                    };
                    
                    $.ajax({

                        type: "post",
                        url: bbpvotesL10n.ajaxurl,
                        data:ajax_data_log,
                        dataType: 'html',
                        beforeSend: function() {
                            link.removeClass('.bbpvotes-db-loading');
                            link.addClass('bbpvotes-db-loading');
                        },
                        success: function(data){
                            
                            var votes_log = $(data);
                            
                            
                            if (current_votes_log.length){ //replace old log
                                current_votes_log.replaceWith(votes_log);
                            }else{
                                votes_log.hide();
                                reply_content.append(votes_log);
                                votes_log.slideDown();
                            }
                            
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            console.log(xhr.status);
                            console.log(thrownError);
                        },
                        complete: function() {
                            link.removeClass('bbpvotes-db-loading');
                        }
                    });
                    
                    
                    
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                link.addClass('bbpvotes-db-error');
                console.log(xhr.status);
                console.log(thrownError);
            },
            complete: function() {
                link.removeClass('bbpvotes-db-loading');
            }
        });

        return false;

    });

});

function getURLParameter(url, name) {
    return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
}