jQuery(document).ready(function($){

    $('a.bbpvotes-post-vote-link').click(function(e){
        e.preventDefault();
        
        var link = $(this);
        var admin_links = link.parents('.bbp-admin-links');
        var ajax_data = {};

        if(link.hasClass('loading')) return false;
        
        ajax_data._wpnonce=getURLParameter(link.attr('href'),'_wpnonce');
        ajax_data.post_id=getURLParameter(link.attr('href'),'post_id');
        ajax_data.action=getURLParameter(link.attr('href'),'action');

        $.ajax({
    
            type: "post",
            url: ajaxurl,
            data:ajax_data,
            dataType: 'json',
            beforeSend: function() {
                link.removeClass('.loading .success .error');
                link.addClass('loading');
            },
            success: function(data){
                if (data.success == false) {
                    link.addClass('error');
                    console.log(data.message);
                }else if (data.success == true) {
                    link.addClass('success');
                    
                    var scoreLink = admin_links.find('.bbpvotes-post-score-link');
                    if (data.score_text) scoreLink.text(data.score_text);
                    if (data.score_title) scoreLink.attr('title',data.score_title);
                    
                    if(link.hasClass('bbpvotes-post-voteup-link')){
                        link.text(bbpvotesL10n.you_voted_up);
                        var voteDownLink = admin_links.find('.bbpvotes-post-votedown-link');
                        voteDownLink.text(bbpvotesL10n.vote_down);
                    }else if(link.hasClass('bbpvotes-post-votedown-link')){
                        link.text(bbpvotesL10n.you_voted_down);
                        var voteUpLink = admin_links.find('.bbpvotes-post-voteup-link');
                        voteUpLink.text(bbpvotesL10n.vote_up);
                    }
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                link.addClass('error');
                console.log(xhr.status);
                console.log(thrownError);
            },
            complete: function() {
                link.removeClass('loading');
            }
        });
        
        
        
        return false;

    });

});

function getURLParameter(url, name) {
    return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
}