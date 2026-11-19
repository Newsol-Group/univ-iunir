kernel.renderer.registrar_pagelet('examenes', function(info) {
    var id = '#' + info.id;

    return {
        onload: function() {
			$(id).on("click", "a.rechazado", function() {
				$('tr#'+$(this).attr("link")).slideToggle("slow");
			});
			
			
			$(id + ' #examenes_historicos').on("click", function () {
				var valor_check_exa = 'S';
				if ($(this).prop('checked'))
					valor_check_exa = 'T';
				kernel.ajax.load(info.url_examenes, id, {
					historia: false,
					type: 'GET',
					data: {'examenes_historicos': valor_check_exa},
					success: function() {
						kernel.renderer.pagelet('examenes').onload();
					}
				});
			});
        }
    }
})