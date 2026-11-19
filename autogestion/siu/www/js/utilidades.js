var no_seleccion = 'null';

/*
 * Ordena las opciones de un combo. Necesario cuando se carga vía AJAX.
 */
function ordenar_combo(idSelect)
{
	var opcion_no_seleccion = $('#' + idSelect + " option[value='" + no_seleccion +"']");
	var ops = $('#' + idSelect + " option:not(option[value='null'])");
	
	// Se comparan todos los elementos del array
	ops.sort(function (a,b) {
		return ( $(a).html().toUpperCase() < $(b).html().toUpperCase() ) ? -1 : ( $(a).html().toUpperCase() > $(b).html().toUpperCase() ) ? 1 : 0;
	});
	
	// Primero se agrega la opción de no selección
	var html;
	if (opcion_no_seleccion.length > 0) {
		html = "<option value='" + $(opcion_no_seleccion[0]).val() + "'>" + $(opcion_no_seleccion[0]).html() + "</option>";
	} else {
		html = '';
	}
	
	// Se agregan las opciones ordenadas por descripción.
	for (var i = 0; i < ops.length; i++) {
		html += "<option value='" + $(ops[i]).val() + "'>" + $(ops[i]).html() + "</option>";
	}
	
	// Se insertan las opciones en el select
	$('#' + idSelect).html(html);
}

function cloneObject(source_) {
	for (var item in source_) {
		if (typeof source_[item] == 'object') {
			this[item] = new cloneObject(source_[item]);
		} else{
			this[item] = source_[item];
		}
	}
}

function toggle(idElem) {
	$('#'+idElem).slideToggle(200);
}

function ocultar(elemId, resetear) {
	$('#'+elemId).hide();
	if (resetear) {
		resetDiv(elemId);
	}
}

function mostrar(elemId) {
	$('#'+elemId).show();
}

function set_solo_lectura(elemId, solo_lectura) {
	if (solo_lectura) {
		$('#'+elemId).attr('readonly', 'readonly');
	} else {
		$('#'+elemId).prop("readonly", false);
	}
}

function resetDiv(divId) {
	// Se recorren todos los campos del formulario contenidos en el div
	$(':input', '#'+divId).each (function() {
		var type = this.type;
		var tag = this.tagName.toLowerCase();
		// Se limpian los valores de los campos...
		if (type == 'text' || type == 'password' || tag == 'textarea') 
			this.value = '';
		else if (type == 'checkbox' || type == 'radio') 
			// Para checkboxes y radios se le quita el checked
			this.checked = false;
		else if (tag == 'select')
			// Para los selects -> no_seleccion
			this.value = no_seleccion;
	});
}

//------------------------------------------------------------------------------
//---- Control de cambios en formularios ---------------------------------------
//------------------------------------------------------------------------------

function formulario_hubo_cambios() 
{
	var hubo_cambio = false;
	$('.js-editable').each(function() {
		var valor_actual;

		if (this.tagName.toLowerCase() === 'select') {
			// Se examinan los selects
			valor_actual = $(this).val();
			if ((valor_actual === 'null') || (valor_actual === null)) {
				valor_actual = '';
			} 
		} else {
			// Se examina el resto de los inputs
			switch ($(this).prop('type')) {
				case 'checkbox':
				   valor_actual = ($(this).is(':checked')) ? 'S' : 'N';
				   break;
				case 'radio':
				   valor_actual = $('[name="'+$(this).prop('name')+'"]:checked').val();
				   break;
				default:
				   valor_actual = $(this).val();
			}
		}
		
		if ($(this).data('prev-value') != valor_actual) {
			hubo_cambio = true;
		}

	});

	return hubo_cambio;
}

function handle_combo_pais_localidad(campo_pais, campo_id) 
{
	if (($('#'+campo_id).val() == 'null') || ($('#'+campo_id).val() == '')) {
		$('#'+campo_pais).show();
		$('#'+campo_pais).val('54');
	} else {
		$('#'+campo_pais).hide();
	}
}

//-------------------------------------------------------------------------------------------------------------------------
//---- Manejo de Cookies (http://stackoverflow.com/questions/14573223/set-cookie-and-get-cookie-with-javascript) ----------
//-------------------------------------------------------------------------------------------------------------------------

function createCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires="+date.toGMTString();
    }
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}

function limpiarAcentos(texto){
	var r = texto.toLowerCase();
	non_asciis = {'a': '[àáâãäå]', 'ae': 'æ', 'c': 'ç', 'e': '[èéêë]', 'i': '[ìíîï]', 'n': 'ñ', 'o': '[òóôõö]', 'u': '[ùúû?ü]', 'y': '[ýÿ]'};
	for (i in non_asciis) {
		r = r.replace(new RegExp(non_asciis[i], 'g'), i);
	}
	return r;
};