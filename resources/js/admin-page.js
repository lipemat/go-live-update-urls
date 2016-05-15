(function( $ ){
	var go_live_update_urls = {
		init : function(){
			$( '.gluu-tables-button' ).click( this._un_check_tables );
		},

		_un_check_tables : function(){
			var el = $( this );
			if( el.hasClass( 'checked' ) ){
				el.removeClass( 'checked' );
				el.val( el.data( 'un-checked' ) );
				$( '[data-list="' + el.data( 'list' ) +'"] .gluu-wp-core-table' ).attr( 'checked', false );
			} else {
				el.addClass( 'checked' );
				el.val( el.data( 'checked' ) );
				$( '[data-list="' + el.data( 'list' ) +'"] .gluu-wp-core-table' ).attr( 'checked', true );
			}
		}
	};

	$( function(){
		go_live_update_urls.init();
	} );


})( jQuery );