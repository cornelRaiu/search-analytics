( function () {
	var settings = window.mwtsaDeactivationFeedback;
	var dialog = document.getElementById( 'mwtsa-df' );
	var row = settings && document.querySelector( 'tr[data-plugin="' + settings.plugin + '"]' );
	var link = row && row.querySelector( '.deactivate a' );

	// Without <dialog> support the link just deactivates, as it would without this script.
	if ( ! dialog || ! link || typeof dialog.showModal !== 'function' ) {
		return;
	}

	var form = dialog.querySelector( 'form' );
	var submit = dialog.querySelector( '.mwtsa-df-submit' );
	var snooze = dialog.querySelector( '.mwtsa-df-snooze' );
	var note = dialog.querySelector( '.mwtsa-df-note' );

	function selected() {
		var radio = form.querySelector( 'input[name="reason"]:checked' );
		return radio ? radio.value : '';
	}

	function details() {
		var input = form.querySelector( 'input[name="details"]:not([disabled])' );
		return input ? input.value.trim() : '';
	}

	// Nothing is sent for a temporary deactivation, or for "Other" without a reason typed in.
	function willSend() {
		var reason = selected();
		return reason !== '' && reason !== 'temporary' && ( reason !== 'other' || details() !== '' );
	}

	function update() {
		var reason = selected();

		form.querySelectorAll( '.mwtsa-df-reason' ).forEach( function ( item ) {
			var active = item.getAttribute( 'data-reason' ) === reason;
			var more = item.querySelector( '.mwtsa-df-more' );
			var input = item.querySelector( 'input[name="details"]' );

			more.hidden = ! active || ! more.children.length;
			if ( input ) {
				input.disabled = ! active;
			}
		} );

		snooze.hidden = reason !== 'temporary';
		note.hidden = reason === 'temporary';
		submit.textContent = willSend() ? settings.labels.send : ( reason ? settings.labels.plain : settings.labels.skip );
		submit.classList.toggle( 'button-primary', reason !== '' );
	}

	function deactivate() {
		window.location.href = link.href;
	}

	function send() {
		var body = new FormData();
		body.append( 'action', settings.action );
		body.append( '_ajax_nonce', settings.nonce );
		body.append( 'reason', selected() );
		body.append( 'details', details() );
		if ( form.elements.snooze.checked ) {
			body.append( 'snooze', '1' );
		}

		var controller = typeof AbortController === 'function' ? new AbortController() : null;
		if ( controller ) {
			setTimeout( function () {
				controller.abort();
			}, 5000 );
		}

		return fetch( settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
			signal: controller ? controller.signal : undefined,
		} );
	}

	link.addEventListener( 'click', function ( event ) {
		event.preventDefault();
		form.reset();
		update();
		dialog.showModal();
	} );

	form.addEventListener( 'change', update );
	form.addEventListener( 'input', update );

	// Cancel, Escape and a click on the dark area around the dialog all close it without deactivating.
	dialog.querySelector( '.mwtsa-df-cancel' ).addEventListener( 'click', function () {
		dialog.close();
	} );
	dialog.addEventListener( 'click', function ( event ) {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );

	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		var reason = selected();

		if ( ! willSend() && ! ( reason === 'temporary' && form.elements.snooze.checked ) ) {
			deactivate();
			return;
		}

		form.querySelectorAll( 'button' ).forEach( function ( item ) {
			item.disabled = true;
		} );
		submit.textContent = settings.labels.sending;
		send().catch( function () {} ).then( deactivate );
	} );
} )();
