<?php

/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class ci_nav_administrar_solicitudes_constancias extends ci_navegacion
{
	// Variable que guarda el par�metro solicitud si se invoca desde la operaci�n de Solicitar Constancias.
	protected $s__solicitud_inicial;
	protected $s__filtro;
	protected $s__selecciones = [];
	//protected $id_persona;

	function ini__operacion()
	{
		parent::ini__operacion();
		toba::memoria()->desactivar_reciclado();
		$this->s__solicitud_inicial = toba::memoria()->get_parametro('solicitud');

		if (isset($this->s__solicitud_inicial)) {
			$this->s__seleccion = array('solicitud' => $this->s__solicitud_inicial);
			$this->seleccionar_solicitud_a_administrar($this->s__seleccion);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_solicitud_seleccionada()
	{
		return current($this->s__seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial();
	}

	function conf__administrar_solicitud(toba_ei_pantalla $pantalla)
	{
		$this->evento('eliminar')->set_msg_confirmacion($this->get_msg_confirmar_eliminar());
	}

	//-----------------------------------------------------------------------------------
	//---- Dependencias -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//------ Cuadro ---------------------------------------------------------------------

	// Se redefine la selecci�n del cuadro ya que no existe un DR a cargar.
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->seleccionar_solicitud_a_administrar($seleccion);
	}

    function evt__cuadro__selecciones($selecciones)
    {
        $this->s__selecciones = $selecciones;
    }

	function seleccionar_solicitud_a_administrar($solicitud)
	{
		$this->set_pantalla('administrar_solicitud');
		// Se setea como pantalla actual del ci de edici�n la pantalla correspondiente seg�n el estado de la solicitud seleccionada.
		$datos = toba::consulta_php('co_constancias')->get_solicitud_estado($this->get_solicitud_seleccionada());
		switch ($datos['estado']) {
			case constancias::estado_solicitud_pendiente:
				$this->dependencia('editor')->set_pantalla('pant_imprimir_rechazar');
				break;
			case constancias::estado_solicitud_impreso:
				$this->dependencia('editor')->set_pantalla('pant_registrar_entrega');
				break;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		$this->dependencia('editor')->resetear_vars();

		if (isset($this->s__solicitud_inicial)) {
			//Limpio la memoria
			unset($this->s__solicitud_inicial);
			$this->cn()->resetear();
			// Vuelvo a la operacion de Solicitar Constancias
			try {
				echo toba_js::abrir();
				echo "document.location.href='" . toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '37000079', array()) . "'";
				echo toba_js::cerrar();
			} catch (guarani_error $e) {
				throw $e;
			}
		} else {
			parent::evt__cancelar();
		}
	}

	function evt__eliminar()
	{
		// Se realizan las actualizaciones correspondientes a la Eliminaci�n de la Solicitud.
		$solicitud = $this->get_solicitud_seleccionada();
		$this->cn()->eliminar_solicitud_constancia($solicitud);
		// Se informa el �xito de la eliminaci�n.
		$msje = guarani_mensajes::get('eliminar_ok');
		toba::notificacion()->agregar($msje, comunes::error_informacion);
		$this->controlador()->evt__cancelar();
	}

	function evt__filtro__filtrar($datos)
    {
		$where = $this->dep('filtro')->get_sql_where();
		$this->s__filtro = $where; 
	}
	
	/**
	 * 23/07/2025
	 * Newsol - Joaquin
	 * registrar_impresiones_multiples
	 * Registra la Impresión simultánea de varias Solicitudes de Constancias. 
	 */

	function evt__cuadro__procesar_pendientes_masivo()
	{
		$solicitudes = $this->get_solicitudes_array();
		
		if ($solicitudes === false) {
			return;
		}

		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$id_persona = $persona['persona'];

		toba::db()->abrir_transaccion();
		try {
			$this->cn()->registrar_impresiones_multiples($solicitudes, $id_persona, '');
			toba::db()->cerrar_transaccion();
			$cantidad = count($solicitudes);
			if ($cantidad > 0) {
				toba::notificacion()->agregar
				(
					"Se imprimieron correctamente {$cantidad} solicitudes pendientes.",
					comunes::error_informacion
				);
			}
		} catch (\Exception $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	function get_solicitudes_array() 
	{
		if (empty($this->s__filtro)) {
			toba::notificacion()->agregar
			(
				'Debe filtrar las solicitudes antes de procesar.',
				comunes::error_informacion
			);
			return false;
		}

		$raw_solicitudes_array = $this->cn()->get_procesar_pendientes_masivo($this->s__filtro);
		$solicitudes = array_map(fn($row) => $row['solicitud'], $raw_solicitudes_array);

		if (empty($solicitudes)) {
			toba::notificacion()->agregar
			(
				'No hay solicitudes pendientes para procesar.',
				comunes::error_informacion
			);
			return false;
		}
		
		unset($this->s__filtro);
		return $solicitudes;
	}

	function evt__cuadro__imprimir_multiples()
    {
        if (empty($this->s__selecciones)) {
            toba::notificacion()->agregar
			(
                'No hay solicitudes seleccionadas para aprobar.',
                comunes::error_informacion
            );
            return;
        }

		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$id_persona = $persona['persona'];

        toba::db()->abrir_transaccion();
        
		try {

			$solicitudes = array_map(fn($row) => $row['solicitud'], $this->s__selecciones);
			$this->cn()->registrar_impresiones_multiples($solicitudes, $id_persona, ''); 
			$cantidad = count($solicitudes);
    	        
			toba::db()->cerrar_transaccion();

            if ($cantidad > 0) { 
				toba::notificacion()->agregar
				(
					"Se imprimieron correctamente {$cantidad} solicitudes seleccionadas.", 
					comunes::error_informacion
				);
			}        
		} catch (\Exception $e) {
            toba::db()->abortar_transaccion();
            throw $e;
        }

		$this->s__selecciones = [];
    }

	/**
	 * 23/07/2025
	 * Newsol - Joaquin
	 * registrar_impresiones_multiples
	 * Registra la Impresión simultánea de varias Solicitudes de Constancias. 
	 */
	function extender_objeto_js()
	{
		echo 
		"
			//console.log('Extensión de ci_nav_administrar_solicitudes_constancias.js');

			const headerCb = obtenerCheckboxPrincipal();
			const { idx, tbl, checked } = obtenerIndiceYTabla(headerCb);

			(function(){
				const tableEl = tbl[0];
				const rows = tableEl.querySelectorAll(
					'tr.ei-cuadro-celda-par, tr.ei-cuadro-celda-impar'
				);
				rows.forEach(function(tr){
					const cells = tr.getElementsByTagName('td');
					// columna Estado = índice 1
					if (cells[1].textContent.trim() !== 'Pendiente') {
						const cb = cells[idx].querySelector('input[type=\"checkbox\"]');
						if (cb) {
							cb.disabled = true;
						}
					}
				});
			})();
			
			const constancias_select = jQuery('#ef_ei_37000553_filtroconstancia');
			const sel_total = jQuery('#js-sel_total');
			const btn_aprobar = jQuery('#cuadro_37000554_cuadro_imprimir_multiples');

			//cuadro_37000554_cuadro_imprimir_multiples

			function obtenerCheckboxPrincipal() {
				const headerCb = jQuery('td.ei-cuadro-col-tit input[type=\"checkbox\"][onclick*=\"seleccion_total\"]');
				if (!headerCb.length) {
					console.warn('No se encontró el checkbox de seleccionar_todos');
					return null;
				}
				return headerCb;
			}

			function obtenerIndiceYTabla(headerCb) {
				const headerTd = headerCb.closest('td');
				return {
					idx: headerTd.index(),
					tbl: headerTd.closest('table'),
					checked: headerCb.prop('checked')
				};
			}

			// Función reescrita sin jQuery dentro
			function cambiarEstadoCheckboxes(tbl, idx, prop, checked) {
				const tableEl = tbl[0];
				const rows = tableEl.querySelectorAll('tr.ei-cuadro-celda-par, tr.ei-cuadro-celda-impar');
				rows.forEach(function(tr) {
					const cells = tr.getElementsByTagName('td');
					const estado = cells[1].textContent.trim();
					if (estado === 'Pendiente') {
						const cell = cells[idx];
						const cb = cell.querySelector('input[type=\"checkbox\"]');
						if (cb) {
							cb[prop] = checked;
						}
					}
				});
			}

			function ejecutarSeleccionLogica(tbl, checked, evento) {
				const jsObjName = tbl.data('objeto-js');
				if (jsObjName && window[jsObjName]) {
					if (checked) {
						window[jsObjName].seleccionar_todos(evento);
					} else {
						window[jsObjName].deseleccionar_todos(evento);
					}
				}
			}
			
			window.seleccion_total = function(evento) {
				const headerCb = obtenerCheckboxPrincipal();
				if (!headerCb) return;
				const { idx, tbl, checked } = obtenerIndiceYTabla(headerCb);
				cambiarEstadoCheckboxes(tbl, idx, 'checked', checked);
				ejecutarSeleccionLogica(tbl, checked, evento);
			};

			function toggle_sel_total() { 
				if (constancias_select.val() == 'nopar') {
					sel_total.prop('disabled', true);
					sel_total.prop('checked', false);
					cambiarEstadoCheckboxes(tbl, idx, 'disabled', true);
					cambiarEstadoCheckboxes(tbl, idx, 'checked', false);
					btn_aprobar.prop('disabled', true);
				} else { 
					sel_total.prop('disabled', false);
					cambiarEstadoCheckboxes(tbl, idx, 'disabled', false);
					btn_aprobar.prop('disabled', false);
				}
			}

			constancias_select.on('change', toggle_sel_total);
			btn_aprobar.on('click', function(){
				let hayPendientes = false;
				const rows = tbl[0].querySelectorAll('tr.ei-cuadro-celda-par, tr.ei-cuadro-celda-impar');
				rows.forEach(function(tr){
					const cells = tr.getElementsByTagName('td');
					if (cells[1].textContent.trim() === 'Pendiente') {
						const cb = cells[idx].querySelector('input[type=\"checkbox\"]');
						if (cb && cb.checked) {
							hayPendientes = true;
						}
					}
				});
				if (!hayPendientes) {
					alert('No hay solicitudes pendientes para procesar.');
					return false;  // evita que continúe la acción
				}
        	});
			jQuery(document).ready(toggle_sel_total);
		";
	}
}
