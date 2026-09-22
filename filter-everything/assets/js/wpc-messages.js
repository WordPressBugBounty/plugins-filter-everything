/**
 * «Plugin notifications» messages (src/Admin/Messages.php): dismissal, the one-off
 * consent question and the countdown. No dependencies.
 */
( function () {
    'use strict';

    var cfg = window.wpcMessages || {};

    function post( action, data, done ) {
        var body = 'action=' + encodeURIComponent( action ) + '&nonce=' + encodeURIComponent( cfg.nonce || '' );
        Object.keys( data ).forEach( function ( key ) {
            body += '&' + encodeURIComponent( key ) + '=' + encodeURIComponent( data[ key ] );
        } );

        var xhr = new XMLHttpRequest();
        xhr.open( 'POST', cfg.ajaxUrl, true );
        xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
        xhr.onload = function () {
            if ( done ) {
                done( xhr.status >= 200 && xhr.status < 300 );
            }
        };
        xhr.send( body );
    }

    function pad( n ) {
        return n < 10 ? '0' + n : String( n );
    }

    function startCountdown( box ) {
        var end   = parseInt( box.getAttribute( 'data-wpc-end' ), 10 ) * 1000;
        var units = {};

        Array.prototype.forEach.call( box.querySelectorAll( '[data-u]' ), function ( el ) {
            units[ el.getAttribute( 'data-u' ) ] = el;
        } );

        function tick() {
            var left = Math.max( 0, Math.floor( ( end - Date.now() ) / 1000 ) );

            units.d.textContent = Math.floor( left / 86400 );
            units.h.textContent = pad( Math.floor( left % 86400 / 3600 ) );
            units.m.textContent = pad( Math.floor( left % 3600 / 60 ) );
            units.s.textContent = pad( left % 60 );

            if ( left <= 0 ) {
                window.clearInterval( timer );
                var msg = box.closest( '.wpc-msg' );
                if ( msg ) {
                    msg.style.display = 'none';
                }
            }
        }

        var timer = window.setInterval( tick, 1000 );
        tick();
    }

    function init() {
        Array.prototype.forEach.call( document.querySelectorAll( '.wpc-msg__count' ), startCountdown );

        document.addEventListener( 'click', function ( e ) {
            var target = e.target.closest ? e.target.closest( '[data-wpc-dismiss], [data-wpc-consent]' ) : null;
            if ( ! target ) {
                return;
            }

            var msg = target.closest( '.wpc-msg' );

            if ( target.hasAttribute( 'data-wpc-dismiss' ) ) {
                var id = target.getAttribute( 'data-wpc-dismiss' );
                post( 'flrt_message_dismiss', { id: id } );
                // The PRO benefits tab restates a running offer once the bar is gone.
                Array.prototype.forEach.call( document.querySelectorAll( '[data-wpc-sale-strip="' + id + '"]' ), function ( strip ) {
                    strip.removeAttribute( 'hidden' );
                } );
            } else {
                post( 'flrt_messages_consent', { answer: target.getAttribute( 'data-wpc-consent' ) } );
            }

            if ( msg ) {
                msg.style.display = 'none';
            }
        } );
    }

    if ( 'loading' === document.readyState ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
} )();
