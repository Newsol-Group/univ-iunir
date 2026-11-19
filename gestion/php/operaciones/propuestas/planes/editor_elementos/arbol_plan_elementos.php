<?php
class arbol_plan_elementos extends guarani_pers_ei_arbol
{
	protected $mostrar_barra = true;
	protected $mostrar_boton_orientaciones = false;
	protected $mostrar_boton_editar = true;
	protected $separador_accion_param = '%_%';

	function mostrar_barra($estado = true)
	{
		$this->mostrar_barra = $estado;
	}

	function mostrar_boton_orientaciones($estado = true)
	{
		$this->mostrar_boton_orientaciones = $estado;
	}

	function mostrar_boton_editar($estado = true)
	{
		$this->mostrar_boton_editar = $estado;
	}

	function generar_html_barra_especifica()
	{
		if ($this->mostrar_barra) {
			if ($this->mostrar_boton_editar) {
				echo "<div style='height:25px; background: rgb(220,220,220);text-align:right;'>";
				$imagen = toba_recurso::imagen_proyecto("propuesta/modo_edicion.png", true);
				echo "<button type='button' title='Editar la estructura del Plan de Estudios' style='float:left;' onclick='{$this->objeto_js}.modo_asociar()'>$imagen</input>";
			} else {
				echo "<div style='height:25px; background-color: #e7e7e7; text-align:right;'>";
				$imagen = toba_recurso::imagen_proyecto("propuesta/modo_edicion.png", true);
				echo "<button type='button' DISABLED style='float:left;' onclick='{$this->objeto_js}.modo_asociar()'>$imagen</button>";
			}
			if ($this->mostrar_boton_orientaciones) {
				$imagen = toba_recurso::imagen_proyecto("propuesta/g_contenedor_orientaciones.png", true);
				echo "<button type='button' title='Agregar Orientaciones al Plan' onclick='{$this->objeto_js}.agregar_orientaciones()'>$imagen</button>";
			}
			echo "</div>";
		}
	}

	function generar_html()
	{
		echo toba_form::hidden($this->_submit, '');
		echo toba_form::hidden($this->_submit . "__apertura_datos", '');
		echo toba_form::hidden($this->_submit . "__seleccion", '');
		$id = "id='{$this->objeto_js}_nodo_raiz'";
		echo "<div class='ei-base ei-arbol-base'>";
		echo $this->get_html_barra_editor();
		$this->generar_html_barra_sup(null, true, '');
		if ($this->_mostrar_filtro_rapido) {
			$this->generar_html_filtro_rapido();
		}
		$this->generar_html_barra_especifica();
		echo "<div style='width: 100%' id='cuerpo_{$this->objeto_js}'>";
		if (isset($this->_nodos_inicial)) {
			//--- Se incluye la barrita que contiene el path actual
			$barra = "";
			if (guarani_arreglos::count($this->_nodos_inicial) > 0) {
				$nodo = $this->_nodos_inicial[0];
				while ($nodo->get_padre() != null) {
					$nodo = $nodo->get_padre();
					$nodo_barra = "<a href='#' onclick='{$this->objeto_js}.ver_propiedades(\"";
					$nodo_barra .= $nodo->get_id() . "\");' ";
					$nodo_barra .= "class='ei-arbol-ver-prop'>" . $this->acortar_nombre($nodo->get_nombre_corto(), 20) . "</a>";
					$barra = $nodo_barra . " > " . $barra;
				}
				if ($barra != '') {
					$barra = "<div class='ei-arbol-barra-path'>$barra</div>";
				}
				echo $barra;
			}
			$id_div = '';
			if (guarani_arreglos::count($this->_nodos_inicial) > 1) {
				$id_div = $id;
				$id = '';
			}
			echo "<div class='ei-cuerpo ei-arbol-cuerpo' $id_div>\n";

			foreach ($this->_nodos_inicial as $nodo_inicial) {
				echo "\n<ul $id class='ei-arbol-raiz'>";
				echo $this->recorrer_recursivo($nodo_inicial, true);
				echo "</ul>";
				$id = null; //El id lo tiene s?lo el primer nodo
			}
			echo "</div>";
		}
		echo "</div>";
		echo "</div>";
	}

	function extender_objeto_js()
	{
		echo "
		//-----------------------------------------------------------
		//-- Generales ----------------------------------------------
		//-----------------------------------------------------------

		//--- Modo ASOCIAR ----------------------------
		{$this->objeto_js}.modo_asociar = function() {
			this.controlador.set_evento(new evento_ei('asociar',true,'',[]));
		};

		//--- Crear Orientacion ----------------------------
		{$this->objeto_js}.agregar_orientaciones = function() {
			var cantidad = '';
			var nombre = '';
			do {
				cantidad = prompt('Cantidad de Orientaciones');
				if ( cantidad == null ) return;
				// Se chequea que el string ingresado respete la expresion regular de un número entero
				if ( /^\d+$/.test(cantidad) ) {
					if ((parseInt(cantidad) > 0) && (parseInt(cantidad) < 100)) {
						// Si la cantidad es válida, pide el nombre de la orientación, poniendo como valor defecto el texto Orientaciones
						nombre = prompt('Nombre', 'Orientaciones');
						if(nombre.length == 0) {
							alert('La orientación debe tener un nombre.');
						} else if(nombre.length > 255) {
							alert('El nombre de la orientación no puede tener más de 255 caracteres.');
						} else {
							break;
						}
					} else {
						alert('Cantidad inválida. Debe ser un número entero mayor a 0 y menor a 100.');
					}
				} else {
					alert('Cantidad inválida. Debe ser un número entero mayor a 0 y menor a 100.');
				}
			} while (true);
			var parametros = cantidad + '{$this->separador_accion_param}' + nombre;
			this.controlador.set_evento(new evento_ei('orientaciones',true,'',[parametros]));
		};
		
		//-----------------------------------------------------------
		//-- Alteracion del arbol -----------------------------------
		//-----------------------------------------------------------
		
		//--- Seleccionar un MODULO ----------------------------
		{$this->objeto_js}.seleccionar_modulo = function(nodo) {
			this.seleccionar_elemento(nodo);
		};

		//--- Seleccionar una ACTIVIDAD -------------------------
		{$this->objeto_js}.seleccionar_actividad = function(nodo) {
			this.seleccionar_elemento(nodo);
		};

		//--- Seleccionar un ELEMENTO -------------------------
		{$this->objeto_js}.seleccionar_elemento = function(nodo) {
			var id_elemento = nodo.getAttribute('id_nodo');
			var id_elemento_padre = nodo.parentNode.parentNode.getAttribute('id_nodo');
			this.controlador.accion_nodo('seleccionar_elemento', id_elemento, id_elemento_padre);
		};

		//--- ELIMINAR un ELEMENTO ----------------------------
		{$this->objeto_js}.eliminar_elemento = function(id_elemento, id_elemento_padre, nombre) {
			var parametros = [];
		    parametros['id_elemento'] = id_elemento;
			parametros['id_elemento_padre'] = id_elemento_padre;
			parametros['nombre'] = nombre;
			this.controlador.ajax('es_correlativa_de_o_asociada_a_certificado', parametros, this, this.error_eliminar_elemento);
		};


		{$this->objeto_js}.error_eliminar_elemento = function(respuesta) {
			var mensaje = '';
			if (respuesta != undefined && (respuesta['mensaje_correlativas'] != '' || respuesta['mensaje_certificados'] != '')) {
				if (respuesta['mensaje_correlativas'] != '') {
					var mensaje_correlativas = respuesta['mensaje_correlativas'];
					// El elemento a eliminar es correlativa de otros elementos.
					mensaje = 'ATENCION!\\n\\n' + mensaje_correlativas + '\\n tienen como correlativa a ' + respuesta['nombre'] + ' \\n\\n';
				}
				if (respuesta['mensaje_certificados'] != '') {
					// El elemento está asociado a Certificados del Plan Versión.
					var mensaje_certificados = respuesta['mensaje_certificados'];
					if (mensaje != '') {
						mensaje = mensaje + 'Y \\n\\n' + respuesta['nombre'] + ' está asociada a los Certificados ' + '\\n' + mensaje_certificados + '. \\n\\n';
					} else {
						mensaje = 'ATENCION!\\n\\n' + respuesta['nombre'] + ' está asociada a los Certificados ' + '\\n' + mensaje_certificados + '. \\n\\n';
					}
				}
				mensaje = mensaje + '<br>' + 'No es posible eliminar ' + respuesta['nombre'] + ' sin desasociarla.';
				notificacion.limpiar();
				notificacion.agregar(mensaje,'info')
				notificacion.mostrar();
			} else {
				mensaje = '¿Desea eliminar: ' + respuesta['nombre'] + ' ?' ;
				if(confirm(mensaje)) {
					this.controlador.accion_nodo('eliminar_elemento', respuesta['id_elemento'], respuesta['id_elemento_padre']);
				}
			}
		}

		//--- Asociar ELEMENTOS a un MODULO ------------------------
		{$this->objeto_js}.modulo_asociar_elementos = function(nodo) {
			var elementos = [];
			var ci_disp = this.controlador.dep('disponibles');
			if ( ci_disp.dep('actividades') ) {
				var tipo = 'actividades';
			} else if( ci_disp.dep('materias_genericas') ) {
				var tipo = 'materias_genericas';
			} else if( ci_disp.dep('modulos') ) {
				var tipo = 'modulos';	
			} else if (ci_disp.dep('modulos_rtf')) {
				var tipo = 'modulos_rtf';
			} else {
				alert('Error buscando elementos');
			}
			elementos = elementos.concat( ci_disp.dep(tipo).get_ids_seleccionados('seleccion') );
			if (elementos.length > 0) {
				this.controlador.accion_nodo('modulo_asociar_elementos', nodo, null);
			} else {
				notificacion.limpiar();
				notificacion.agregar('No hay elementos seleccionados. Seleccione las ACTIVIDADES o MODULOS que desea asociar','info')
				notificacion.mostrar();
			}
		};

		//--- Crear un MODULO ---------------------------------------
		{$this->objeto_js}.modulo_agregar = function(nodo) {
			var nombre = '';
			do {
				nombre = prompt('Nombre del módulo a agregar.');
				if ( nombre == null ) return;
			} while (nombre == '' );
			this.controlador.accion_nodo('modulo_agregar', nodo, null, nombre );
		};

		//--- Crear una ORIENTACION -----------------------------------
		{$this->objeto_js}.modulo_orientacion_agregar = function(nodo) {
			var nombre = '';
			do {
				nombre = prompt('Nombre de la orientacion a agregar.');
				if ( nombre == null ) return;
			} while (nombre == '');
			this.controlador.accion_nodo('modulo_orientacion_agregar', nodo, null, nombre );
		};
		";
	}

}
?>