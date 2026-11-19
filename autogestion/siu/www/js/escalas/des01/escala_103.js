guarani.escalas().registrar('103', function() {
	return {
		get_tipo: function() {
			return 'discreta';
		},
		validar: function(nota) {
			var escalast		= '**10=>A||**9=>A||**8=>A||**7=>A||**6=>A||**5=>R||**4=>R||**3=>R||**2=>R||**1=>R||**0=>R||**';
			var string_buscar	= '**' + nota + '=>';
			var posicion_ini	= escalast.indexOf(string_buscar);
			return posicion_ini != -1;
		},
		get_resultado: function(nota) {
			var escalast		= '**10=>A||**9=>A||**8=>A||**7=>A||**6=>A||**5=>R||**4=>R||**3=>R||**2=>R||**1=>R||**0=>R||**';
			var nota_bucar		= '**' + nota + '=>';
			var posicion_ini	= escalast.indexOf(nota_bucar);
			if (posicion_ini == -1) {
				return '';
			} else {
				posicion_ini = posicion_ini + nota_bucar.length;
				return escalast.substr(posicion_ini,1);
			}
		},
		get_resultado_descr: function(nota) {
			return _resultados[guarani.escalas().recuperar('103').get_resultado(nota)].descr;
		}
	}
});