(function( $ ){
	var go_live_update_urls = {
		init : function(){
			$( '[data-js="go-live-update-urls/checkboxes/check-all"]' ).click( this._un_check_tables );
		},

		_un_check_tables : function(){
			var el = $( this );
			if( el.prop( 'checked' ) ){
				$( '[data-list="' + el.data( 'list' ) +'"] input' ).prop( 'checked', true );
			} else {
				$( '[data-list="' + el.data( 'list' ) +'"] input' ).prop( 'checked', false );
			}
		}
	};

	$( function(){
		go_live_update_urls.init();
	} );


})( jQuery );
