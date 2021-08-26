jQuery(document).ready(function() {

	jQuery('.infast-syncall-btn').on('click', function() {

		jQuery.ajax ({
			url: ajaxurl,  
			type:'POST',
			data:'action=infast_synchronise_all',

			beforeSend:function() {
				jQuery('.infast-syncall-btn').append('<span> [...]</span>');
				jQuery(this).prop('disabled', true);
			},
			success:function(results) {
				jQuery('.infast-syncall-btn span').remove();
				jQuery(this).prop('disabled', false);
			},
			error:function(request, status, error) {
				console.log(error);
				jQuery('.infast-syncall-btn span').remove();
				jQuery(this).prop('disabled', false);
			}
		});

	});

});