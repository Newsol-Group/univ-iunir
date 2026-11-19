<?php
/**
 * 
 * 
 * @package		Matricula
 * @subpackage	Regularidad
 * 
 */
class ci_edi_conf_regla_calc_regularidad extends guarani_pers_ci
{	
	protected $valor_defecto_disponible_para = 0;
	
	protected $ev_info;
	protected $s__causa_perdida;
	protected $s__regla_nombre;
	
	//Datos temporales de los Formularios y Cuadros de selección
	protected $propuestas_ubicacion_temp;
	protected $cuadro_propuestas_temp;
	
	//Datos del Filtro de Propuestas-Ubicacion.
	protected $s__datos_filtro_propuestas;
	protected $s__filtro_propuestas;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		$this->valor_defecto_disponible_para = 0;
		
		$this->s__causa_perdida = $this->controlador->get_seleccion();
		$this->s__regla_nombre = toba::consulta_php('co_readmisiones')->get_nombre_causa_perdida($this->s__causa_perdida['causa_perdida_reg']);
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__regla_nombre);
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 100%;');
			$this->ev_info->set_columnas_a_mostrar(array('nombre'));

			$this->ev_info->set_columnas_fila(array('nombre'), 1);
			
			$this->ev_info->set_etiqueta('nombre', 'Regla');
			$this->ev_info->set_td_etiqueta_width('60px');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- propuestas_ubicacion ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas_ubicacion(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->propuestas_ubicacion_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->propuestas_ubicacion_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_propuestas_ubicacion());
		}
	}

	function evt__propuestas_ubicacion__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_propuestas_ubicacion($datos);
		} catch (guarani_error $e) {
			$this->propuestas_ubicacion_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__propuestas_ubicacion__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_propuestas_ubicacion($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_propuestas(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_propuestas)) {
			$filtro->set_datos($this->s__datos_filtro_propuestas);
		}
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_propuestas = $datos;
			$this->s__filtro_propuestas = $this->dep('filtro_propuestas')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_propuestas);
			unset($this->s__filtro_propuestas);
		}
	}

	function evt__filtro_propuestas__cancelar()
	{
		unset($this->s__datos_filtro_propuestas);
		unset($this->s__filtro_propuestas);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_propuestas -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		$msg_eof = guarani_mensajes::get('800_eof_cuadro_filtrado', array('Propuestas'));
		//Sin tener filtro, se setea el msg de que debe filtrar.
		if ((!isset($this->s__filtro_propuestas))) {
			$msg_eof = guarani_mensajes::get('800_eof_cuadro_sinfiltro');
			$cuadro->evento('agregar')->anular();
		} else {
			$cuadro->set_datos($this->cn()->get_ubicaciones_propuestas($this->s__filtro_propuestas));
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		$this->cuadro_propuestas_temp = $seleccion;
	}

	function evt__cuadro_propuestas__agregar()
	{
		if(isset($this->cuadro_propuestas_temp)) {
			foreach ($this->cuadro_propuestas_temp as $propuesta) {
				$propuesta['causa_perdida_reg'] = $this->s__causa_perdida['causa_perdida_reg'];
				$propuesta['para_controlar'] = $this->valor_defecto_disponible_para;
				$this->cn()->entidad()->agregar_propuestas_ubicacion($propuesta);
			}
		} else {
			//Valido que se haya seleccionado alguna Responsable Académica		
			$mje_sin_ra = guarani_mensajes::get('800_no_seleccion', array('una Propuesta'));
			throw guarani::error($mje_sin_ra);
		}
	}
	
	function resetear_variables()
	{
		unset($this->s__datos_filtro_propuestas);
	}
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$cuadro = $this->dep('cuadro_propuestas')->get_objeto_js(); 
		echo "
			//---- Eventos ---------------------------------------------

			{$cuadro}.evt__agregar = function()
			{
				var seleccion = this.get_ids_seleccionados('seleccion');
				if (seleccion == '' || seleccion == null) {
					notificacion.agregar('Debe seleccionar al menos una Propuesta');
					return false;            
				}
				return true;
			} 
		";
	}

}
?>