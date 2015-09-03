jQuery(document).ready(function($){
    $("#wpcc-clone-btn").click(function(){
        var post_id = parseInt($("#wpcc_select_post").val());

        if(post_id){
            $(this).val("Processing...").prop("disabled",true);
            alert('hey do!');
        }else{
            $('#wpcc_select_post').focus();
        }

    });
    $(document).on("change","#wpcc_select_post_type",function(){
        $(".spinner.wpcc-clone-spinner").addClass('is-active');
        $('#wpcc_select_post').html('<option value="0">Select Post</option>').prop("disabled",true);

        var data = {
            'action': 'get_posts_by_type',
            'post_type': $("#wpcc_select_post_type").val(),
            'nonce': $("#wpcc_mb_nonce_").val()
        };

        $.post(ajaxurl, data, function(obj) {
            if( obj.status ){
                $.each(obj.posts,function(i,item){
                    $('#wpcc_select_post')
                        .append($("<option></option>")
                        .attr("value",item.id)
                        .text(item.title)).prop("disabled",false);
                });
                $("#wpcc-clone-btn").prop("disabled",false);
            }
        }).done(function() {
            $(".spinner.wpcc-clone-spinner").removeClass('is-active');
        });
    });
});
