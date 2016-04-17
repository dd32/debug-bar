(function() {
	var count, menuCount, list, dbjsError, isReady
		rawCount = 0,
		errors = [];


	window.onerror = function( errorMsg, url, lineNumber ) {
		if ( ! document.getElementById( 'debug-bar-js-error-count' ) )
			errors[ errors.length ] = [errorMsg, url, lineNumber];
		else
			dbjsError(errorMsg, url, lineNumber);
	}


	if ( typeof jQuery != 'undefined' ) {
		jQuery(document).ready( function(){
			for ( err in errors ) {
				dbjsError( errors[err][0], errors[err][1], errors[err][2] );
			}
		});
	} else {
		// @see http://gomakethings.com/a-native-javascript-equivalent-of-jquerys-ready-method/
		isReady = function ( fn ) {
			// Sanity check
			if ( typeof fn !== 'function' ) return;

			// If document is already loaded, run method
			if ( document.readyState === 'interactive' || document.readyState === 'complete' ) {
				return fn();
			}

			// Otherwise, wait until document is loaded
			// The document has finished loading and the document has been parsed but sub-resources such as images, stylesheets and frames are still loading. The state indicates that the DOMContentLoaded event has been fired.
			//document.addEventListener( 'interactive', fn, false );
			document.addEventListener( 'DOMContentLoaded', fn, false );
		};

		isReady( function(){
			for ( err in errors ) {
				dbjsError( errors[err][0], errors[err][1], errors[err][2] );
			}
		});
	}



	dbjsError = function( errorMsg, url, lineNumber ) {

		var errorLine, place, button, tab;

		rawCount++;

		if ( ! menuCount ) {
			menuCount = document.getElementById( 'debug-bar-js-issue-count' );
		}
		if ( ! count ) {
			count = document.getElementById( 'debug-bar-js-error-count' );
		}
		if ( ! list ) {
			list = document.getElementById( 'debug-bar-js-errors' );
		}

		if ( ! count || !list ) {
			return; // threw way too early... @todo cache these?
		}

		if ( 1 == rawCount ) {
			button = document.getElementById( 'wp-admin-bar-debug-bar' );
			if ( ! button ) {
				return; // how did this happen?
			}
			if ( button.className.indexOf( 'debug-bar-warning-summary' ) === -1 ) {
				button.className = button.className + ' debug-bar-warning-summary';
			}

			tab = document.getElementById( 'debug-menu-link-Debug_Bar_JS' );
			if ( tab ) {
				tab.style.display = 'block';
			}

			if ( menuCount.className.indexOf( 'debug-bar-issue-count' ) === -1 ) {
				menuCount.className = menuCount.className + ' debug-bar-issue-count';
			}
			if ( menuCount.className.indexOf( 'debug-bar-issue-warnings' ) === -1 ) {
				menuCount.className = menuCount.className + ' debug-bar-issue-warnings';
			}
		}

		count.textContent = rawCount;
		menuCount.textContent = rawCount;
		errorLine = document.createElement( 'li' );
		errorLine.className = 'debug-bar-js-error';
		errorLine.textContent = errorMsg;
		place = document.createElement( 'span' );
		place.textContent = url + ' line ' + lineNumber;
		errorLine.appendChild( place );
		list.appendChild( errorLine );

	};

})();
