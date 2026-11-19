var util = util || {};

util.filtro = function() {
    function get_id_filtro(filtro, valor) {
        return filtro+ ':' +valor;
    }
    
    function aplicar_filtro(id_filtro, elems) {
        elems.each(function(i, e) {
            var $e = $(e);
            var no_tiene_filtros = (! $e.data('filtros'));
            if (no_tiene_filtros) {
                $e.data('filtros', []);
            } 
            
            $e.data('filtros').push(id_filtro); // Agregamos el filtro que lo afecta
            $e.hide(); // Por último escondemos el elemento
//            $e.fadeTo('fast', 0.33); // Por último escondemos el elemento
        });
    }
    
    function reset_filtro(id_filtro, elems) {
        elems.each(function(i, e) {
            var $e = $(e);
            var filtros = $e.data('filtros');

            if (!filtros) {
                return; // no tiene ningún filtro aplicado
            }

            var index_filtro = jQuery.inArray(id_filtro, filtros);
            
            if (index_filtro >= 0) {
                filtros.splice(index_filtro, 1);    
            }

            $e.data('filtros', filtros);

            if (filtros.length == 0) {
                $e.show(); // se muestra si no se le está aplicando algún otro filtro
//                $e.fadeTo('fast', 1); // se muestra si no se le está aplicando algún otro filtro
            }
        });
    }
        
    function filtrar(filtro, valor) {
        var id_filtro = get_id_filtro(filtro, valor);
        
        if (! filtros[filtro]) {
            throw new 'No existe el filtro seleccionado';
        }
        if (filtros[filtro].evt__pre_filtrar) {
            filtros[filtro].evt__pre_filtrar();
        }
        
        var elems = filtros[filtro].elems_eliminar(valor);
        aplicar_filtro(id_filtro, elems);
    }
    
    function reset(filtro, valor) {
        var id_filtro = get_id_filtro(filtro, valor);
        
        if (! filtros[filtro]) {
            throw new 'No existe el filtro seleccionado';
        }
        
        var elems = filtros[filtro].elems();
        
        reset_filtro(id_filtro, elems);
    }
    
    var filtros = {};
    
    return {
        agregar: function(nombre, obj) {
            filtros[nombre] = obj;
        },
        /**
         * Se acciona el filtro
         * @param $elem el elemento sobre el que se acciona el filtro
         * @param filtro el filtro que se aplica
         * @param valor a filtrar
         */
        accionar: function ($elem, filtro, valor) {
            if (! $elem.hasClass('seleccionado')) {
                filtrar(filtro, valor);
                $elem.addClass('seleccionado');
            } else {
                // Cuando se clickea sobre un filtro seleccionado este se desactiva
                reset(filtro, valor);
                $elem.removeClass('seleccionado');
            }
        },
		accionar_combo: function(filtro, valor) {
			filtrar(filtro, valor);
		},
		reset_combo: function(filtro, valor) {
			reset(filtro, valor);
		}
    }
};
