kernel.renderer.registrar_pagelet('home', function (info)
{
	var id = '#' + info.id;

	return {
		onload: function ()
		{
			
			$( 'body' ).on( "click", ".js-link-clase", function(e) {
				
				// Si el link esta deshabilitado por los derechos salgo
				if($(this).hasClass('disabled')) return false;
				
				var url_clase = $(this).data('urlComisionClase');
				window.location.href = url_clase;
				e.preventDefault();
				return false;
				
			});
		}
	}
});