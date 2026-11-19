<?php
/**
 *
 *
 * @package     Unidades de Gestión
 * @subpackage  Unidades de Gestión
 * 
 */
class ci_edi_dominio extends guarani_pers_ci
{
	protected $s__dominio;
	
	// Filtros
	protected $s__datos;
	protected $s__filtro = '';
	
	protected $s__seleccion;
	
	protected $elementos_seleccionados;
	
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ini()
	{
		if (empty($this->s__dominio)) {
			$datos = $this->cn()->entidad()->get_datos_unidades_gestion();
			if (!empty($datos['dominio'])) {
				$this->s__dominio = array('dominio' => $datos['dominio']);
			} else {
				$this->s__dominio = array('dominio' => unidades_gestion::dominio_institucion);
			}
		}
	}
	
	function get_dominio()
	{
		return $this->s__dominio['dominio'];
	}
	
	function conf__pant_dominio(toba_ei_pantalla $pantalla)
	{
		if (!empty($this->s__dominio['dominio'])) {
			// Configuración de las dependencias
			switch ($this->s__dominio['dominio']) {
				case unidades_gestion::dominio_institucion :
					$pantalla->eliminar_dep('filtro_ra');
					$pantalla->eliminar_dep('cuadro_ra');
					
					$pantalla->eliminar_dep('filtro_tipos_propuestas');
					$pantalla->eliminar_dep('cuadro_tipos_propuestas');
					
					$pantalla->eliminar_dep('filtro_propuestas');
					$pantalla->eliminar_dep('cuadro_propuestas');
					
					break;
				case unidades_gestion::dominio_ra :
					$pantalla->eliminar_dep('filtro_tipos_propuestas');
					$pantalla->eliminar_dep('cuadro_tipos_propuestas');
					
					$pantalla->eliminar_dep('filtro_propuestas');
					$pantalla->eliminar_dep('cuadro_propuestas');
					
					break;
				case unidades_gestion::dominio_tipo_prop :
					$pantalla->eliminar_dep('filtro_ra');
					$pantalla->eliminar_dep('cuadro_ra');
					
					$pantalla->eliminar_dep('filtro_propuestas');
					$pantalla->eliminar_dep('cuadro_propuestas');
					
					break;
				case unidades_gestion::dominio_prop :
					$pantalla->eliminar_dep('filtro_ra');
					$pantalla->eliminar_dep('cuadro_ra');
					
					$pantalla->eliminar_dep('filtro_tipos_propuestas');
					$pantalla->eliminar_dep('cuadro_tipos_propuestas');
					
					break;
			}
		} else {
			// Si no hay dominio seleccionado, se ocultan todas las dependencias
			$pantalla->eliminar_dep('filtro_ra');
			$pantalla->eliminar_dep('cuadro_ra');
			
			$pantalla->eliminar_dep('filtro_tipos_propuestas');
			$pantalla->eliminar_dep('cuadro_tipos_propuestas');
			
			$pantalla->eliminar_dep('filtro_propuestas');
			$pantalla->eliminar_dep('cuadro_propuestas');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_dominio -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_dominio(toba_ei_formulario $form)
	{
		$form->set_datos($this->s__dominio);
	}

	function evt__form_dominio__modificacion($datos)
	{
		if ($datos['dominio'] != $this->s__dominio['dominio']) {
			$this->resetear_pantalla();
			$this->cn()->entidad()->resetear_relacion();
		}
		$this->s__dominio = $datos;
		$this->cn()->entidad()->set_datos_unidades_gestion($this->s__dominio, false);
	}

	
	//-----------------------------------------------------------------------------------
	//---- RESPONSABLES ACADÉMICAS
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- filtro_ra --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_ra(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
	}

	function evt__filtro_ra__filtrar($datos)
	{
		$this->filtrar('filtro_ra', $datos);
	}

	function evt__filtro_ra__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_ra --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ra(cuadro_seleccion_multiple $cuadro)
	{
		$filtro = empty($this->s__filtro) ? 'true' : $this->s__filtro;
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);
		$cuadro->set_datos($datos);
		$this->s__seleccion = $this->cn()->entidad()->get_datos_responsables_academicas_ug();
		if (empty($datos)) {
			$cuadro->set_eof_mensaje($this->get_msg_eof(guarani_etiquetas::get_tabla('sga_responsables_academicas')));
		}
	}

	function evt__cuadro_ra__seleccion($datos)
	{
		$this->s__seleccion = $datos;
		$this->cn()->entidad()->agregar_responsables_academica($datos);
	}

	function conf_evt__cuadro_ra__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (isset($this->s__seleccion)) {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ra', $fila, 'responsable_academica'));
		}
	}


	//-----------------------------------------------------------------------------------
	//---- TIPOS DE PROPUESTAS
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro_tipos_propuestas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_tipos_propuestas(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
	}

	function evt__filtro_tipos_propuestas__filtrar($datos)
	{
		$this->filtrar('filtro_tipos_propuestas', $datos);
	}

	function evt__filtro_tipos_propuestas__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_tipos_propuestas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_tipos_propuestas(cuadro_seleccion_multiple $cuadro)
	{
		$datos = toba::consulta_php('co_propuestas')->get_propuestas_tipos_reporte($this->s__filtro);
		$cuadro->set_datos($datos);
		$this->s__seleccion = $this->cn()->entidad()->get_datos_tipos_propuestas();
		if (empty($datos)) {
			$cuadro->set_eof_mensaje($this->get_msg_eof(guarani_etiquetas::get_tabla('sga_propuestas_tipos')));
		}
	}

	function evt__cuadro_tipos_propuestas__seleccion($datos)
	{
		$this->s__seleccion = $datos;
		$this->cn()->entidad()->agregar_tipos_propuestas($datos);
	}

	function conf_evt__cuadro_tipos_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (isset($this->s__seleccion)) {
			$evento->set_check_activo($this->es_fila_seleccionada('cuadro_tipos_propuestas', $fila, 'propuesta_tipo'));
		}
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- PROPUESTAS
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		$this->filtrar('filtro_propuestas', $datos);
	}

	function evt__filtro_propuestas__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_propuestas')->get_propuestas_ofertas($this->s__filtro);
		$cuadro->set_datos($datos);
		$this->s__seleccion = $this->cn()->entidad()->get_datos_propuestas();
		if (empty($datos)) {
			$cuadro->set_eof_mensaje($this->get_msg_eof(guarani_etiquetas::get_tabla('sga_propuestas')));
		}
	}

	function evt__cuadro_propuestas__seleccion($datos)
	{
		$this->s__seleccion = $datos;
		$this->cn()->entidad()->agregar_propuestas($datos);
	}

	function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		if (isset($this->s__seleccion)) {
			$clave = $this->dep('cuadro_propuestas')->get_clave_fila_array($fila);
			$seleccionada = false;
			foreach ($this->s__seleccion as $seleccion) {
				if (isset($seleccion['propuesta']) && isset($seleccion['ubicacion'])) {
					if (($seleccion['propuesta'] == $clave['propuesta']) && (($seleccion['ubicacion'] == $clave['ubicacion']))) {
						$seleccionada = true;
						break;
					}	
				}
			}
			$evento->set_check_activo($seleccionada);
		}
	}


	//-----------------------------------------------------------------------------------
	//---- AUXILIARES
	//-----------------------------------------------------------------------------------
	
	function get_msg_eof($etiqueta = null)
	{
		$indx_msg = '800_eof_cuadro';
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg .= '_filtrado';
		}
		return guarani_mensajes::get($indx_msg, array($etiqueta->get_enf('plural')));
	}
	
	function es_fila_seleccionada($dep_cuadro, $fila, $campo_clave)
	{
		$clave = $this->dep($dep_cuadro)->get_clave_fila_array($fila);
		$clave_fila = $clave[$campo_clave];
		foreach ($this->s__seleccion as $seleccion) {
			if (isset($seleccion[$campo_clave])) {
				if ($seleccion[$campo_clave] == $clave_fila) {
					return true;
				}	
			}
		}
		return false;
	}
	
	function filtrar($nombre_filtro, $datos)
	{
		$this->s__datos = $datos;
		if (!empty($datos)) {
			$this->s__filtro = $this->dep($nombre_filtro)->get_sql_where();
		} else {
			$this->s__filtro = '';
		}
	}
	
	function resetear_pantalla()
	{
		unset($this->s__seleccion);
		
		unset($this->s__datos);
		$this->s__filtro = '';
	}
	
	function resetear()
	{
		$this->resetear_pantalla();
		unset($this->s__dominio);
	}

}
?>