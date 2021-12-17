jQuery(document).ready(function() {

	jQuery('.infast-test-btn').on('click', function() {

		var clientId = jQuery('#infast-client-id').val();
		var clientSecret = jQuery('#infast-client-id').val();

		if(!clientId || !clientSecret) {
			alert('Veuillez renseigner le ClientId et le ClientSecret.');
			return;
		}

		jQuery.ajax ({
			url: ajaxurl,  
			type:'GET',
			data:'action=infast_test_authentication',

			beforeSend:function() {
				jQuery('.infast-test-btn').append('<span> [...]</span>');
				jQuery(this).prop('disabled', true);
			},
			success:function(results) {
				jQuery('.infast-test-btn span').remove();
				jQuery(this).prop('disabled', false);
				alert('La connexion à INFast fonctionne correctement. Vous êtes connecté avec le compte ' + results.data);
			},
			error:function(request, status, error) {
				console.log(error);
				jQuery('.infast-test-btn span').remove();
				jQuery(this).prop('disabled', false);
				alert('Le test à échoué, veuillez vérifier votre ClientId et votre ClientSecret.');	
			}
		});

	});

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