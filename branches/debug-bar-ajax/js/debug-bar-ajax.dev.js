jQuery( function($) {
	$(document).ajaxSend( function( event, xhr, ajaxOptions ) {
		xhr.setRequestHeader( 'X-Request-Debug', 'true' );
	} );

	$(document).ajaxComplete( function( event, xhr, ajaxOptions ) {
		var html = decodeURIComponent( xhr.getResponseHeader( 'X-Response-Debug' ) ),
		    span = $( '<span />' ),
		    head = '<h1>' + span.text( ajaxOptions.type ).html() + ' ' + span.text( ajaxOptions.url ).html() + '</h1><dl>',
		    data = ajaxOptions.data.split( '&' ),
		    i, datum;

		for ( i in data ) {
			datum = data[i].split( '=' );
			head += '<dt>' + span.text( decodeURIComponent( datum[0] ) ).html() + '</dt><dd>' + span.text( decodeURIComponent( datum[1] ) ).html() + '</dd>';
		}

		$( '#debug-menu-target-Debug_Bar_Queries' ).append( head + '</dl>' ).append( html );
	} );
} );
