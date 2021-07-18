/* global subscribe */

document.addEventListener( 'DOMContentLoaded', function( event ) {

	const form = document.querySelectorAll( '.subscribe-form' );

	form.forEach( element => element.addEventListener( 'submit', function( e ) {

		e.preventDefault();

		const form = e.target,
			message = form.querySelector( '.subscribe-form-message' ),
			formData = new FormData( form );

		message.style.display = 'none';
		message.classList.remove( 'subscribe-form-message-success', 'subscribe-form-message-error' );

		formData.append( 'action', 'subscribe' );
		formData.append( '_ajax_nonce', subscribe.nonce );

		fetch( subscribe.adminUrl, {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				const messageClass = response.success ? 'subscribe-form-message-success' : 'subscribe-form-message-error';

				message.innerHTML = response.data;
				message.classList.add( messageClass );
				message.style.display = 'block';
			} );
	} ) );
} );
