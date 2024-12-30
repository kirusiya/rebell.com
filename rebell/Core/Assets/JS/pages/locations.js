( function( $ ) {
  MicroModal.init( );

  $( '.Locations [data-id]' ).each( function( idx, loc ) {
    $( loc ).click( function( evt ) {
      const id = $( evt.target ).data( 'id' );
      MicroModal.show( id );
    } );
  } );
} )( jQuery )
