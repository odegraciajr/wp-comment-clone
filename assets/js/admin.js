jQuery(document).ready(function($){
    $("#wpcc-clone-btn").click(function(){
        var post_id_to_clone = parseInt($("#wpcc_select_post").val());
        var post_id = parseInt($(this).data('post-id'));

        if(post_id_to_clone){
            $(this).val("Processing...").prop("disabled",true);
            var r = confirm("Are you sure you want to clone all the comments?");

            if (r === true) {
                $(".spinner.wpcc-clone-spinner").addClass('is-active');

                var data = {
                    'action': 'clone_comments',
                    'post_id': post_id,
                    'post_id_to_clone': post_id_to_clone,
                    'nonce': $("#wpcc_mb_nonce_").val()
                };
                $.post(ajaxurl, data, function(obj) {
                    if( obj.status ){
                        alert(obj.count + " Comments cloned!");
                        location.reload();
                    }
                }).done(function() {
                    $(".spinner.wpcc-clone-spinner").removeClass('is-active');
                });

            }else{
                $(this).val("Clone Comments").prop("disabled",false);
            }
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

    $(".wpcc_advance_settings").click(function(e){
        e.preventDefault();
        $("#wpcc_advance_settings_wrap").toggle();
    });

    $("#wpcc-delete-comments-btn").click(function(){
        var $btn = $(this);
        var post_id = parseInt($(this).data('post-id'));

        $(this).val("Processing...").prop("disabled",true);
        $(".spinner.wpcc-delete-spinner").addClass('is-active');
        var r = confirm("Are you sure you want to delete all the comments?");
        if (r === true) {
            var data = {
                'action': 'wpcc_delete_comments',
                'post_id': post_id,
                'nonce': $("#wpcc_delete_mb_nonce_").val()
            };

            $.post(ajaxurl, data, function(obj) {
                if( obj.status ){
                    alert(obj.delete_count + " Comment(s) deleted!");
                    location.reload();
                }
            }).done(function() {
                $(".spinner.wpcc-delete-spinner").removeClass('is-active');
                $btn.val("Delete All Comments").prop("disabled",false);
            });
        }else{
            $btn.val("Delete All Comments").prop("disabled",false);
        }

    });

});
