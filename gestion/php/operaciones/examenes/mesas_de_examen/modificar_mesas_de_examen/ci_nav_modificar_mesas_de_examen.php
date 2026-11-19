<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Mesas de Examen
 */
class ci_nav_modificar_mesas_de_examen extends ci_navegacion
{
	protected $s__navegar = false;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN LLAMADO ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_llamado_mesa()
	{
		return $this->s__seleccion['llamado_mesa'];
	}

	function get_ubicaciones()
	{
		$datos = guarani::co('co_ubicaciones')->get_ubicaciones();
		if (guarani_arreglos::count($datos) == 1) {
			return $datos;
		} else {
			$no_seleccion = array(array('valor' => apex_ef_no_seteado, 'descr' => '-- Seleccione --'));
			return array_merge($no_seleccion, $datos);
		}
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = $this->cn()->get_listado_llamados($this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;
		$this->cn()->cargar_llamado($seleccion['llamado_mesa']);
		$this->set_pantalla('edicion');
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('nro_acta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_nro_acta = '';
			if (isset($datos['nro_acta'])) {
				$filtro_nro_acta = " AND EXISTS (	SELECT	*
													FROM	sga_actas
													WHERE	sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa AND
															sga_actas.estado <> '".acta::estado_anulada."' AND
															sga_actas.nro_acta = '{$datos['nro_acta']['valor']}')";
				$this->dep('filtro')->columna('nro_acta')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_nro_acta";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		try {
            
            $this->cn()->hay_cambios();
			$instancias = $this->dep('editor_llamado')->get_instancias_agregadas();
			$this->cn()->guardar_mesa_modificada($instancias);
			$this->s__navegar = true;
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	function evt__cancelar()
	{
		$this->dep('editor_llamado')->resetear_variables();
        $this->cn()->resetear_actas();
		parent::evt__cancelar();
	}
	
	// Se define este evento para poder volver a la pantalla inicial luego de la confirmación Javascript
	// cuando no se navega a "Imprimir Actas de Examen", evitando el control de cambios del evento 'cancelar'.
	function evt__volver_a_seleccion()
	{
		$this->dep('editor_llamado')->resetear_variables();
		parent::evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();

		if ($this->get_id_pantalla() == 'edicion') {
			if ($this->s__navegar) {
				$msjs_alertas = '';
				if ($this->cn()->entidad()->hay_notificaciones()) {
					$msjs_alertas = $this->cn()->entidad()->get_mensajes_notificaciones();
					$this->cn()->entidad()->resetear_notificaciones();
					$volver = 'var volver = false;';
				} else {
					$volver = 'var volver = true';
				}
				$msj_reimprimir = $msjs_alertas . "\\n" . guarani_mensajes::get('800EXA_confirma_modif_llamado');
				$datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($this->s__seleccion['llamado_mesa']);
				$parametros = array('anio_academico' => $datos_llamado_mesa['anio_academico'],
									'turno_examen' => $datos_llamado_mesa['turno_examen'],
									'actividad' => utf8_encode($datos_llamado_mesa['actividad_codigo']),
									'mesa_examen' => utf8_encode($datos_llamado_mesa['mesa_examen_nombre']),
									'fecha' => $datos_llamado_mesa['fecha']);
				$vinculo_reimprimir = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '38000085', $parametros);
				$this->s__navegar = false;
				echo "
					resp = confirm('$msj_reimprimir');
					if (resp) {
						document.location.href='$vinculo_reimprimir';
					} else {
						$volver;
						if (volver) {
							{$this->objeto_js}.set_evento(new evento_ei('volver_a_seleccion'), true);
						}
					}
				";
			}
		}
	}
}
?>
