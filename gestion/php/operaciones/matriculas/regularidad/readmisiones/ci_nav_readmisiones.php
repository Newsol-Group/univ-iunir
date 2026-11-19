<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Regularidad
 */
class ci_nav_readmisiones extends ci_nav_con_preseleccion
{
	//Selecciones
	protected $s__alumno;
	protected $s__datos_alumno;
	protected $s__perdida_regularidad;
	protected $s__datos_perdidas_reg;
	protected $s__datos_readmisiones;
	protected $s__readmision;

	//Pantalla actual
	protected $s__pantalla;
	
	//Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		parent::set_concepto_preseleccion('Alumno');
	}

	//-----------------------------------------------------------------------------------
	//---- Selecciones ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function set_preseleccion($seleccion)
	{
		$this->s__alumno = $seleccion['alumno'];
	}

	function get_preseleccion()
	{
		return $this->s__alumno;
	}

	function get_seleccion()
	{
		return $this->s__perdida_regularidad;
	}
	
	function get_seleccion_datos()
	{
		return $this->s__datos_perdidas_reg;
	}

	function get_datos_readmision()
	{
		return $this->s__readmision;
	}
        
	function hay_readmision_vigente()
	{
		$vig = false;
		foreach ($this->s__datos_readmisiones as $readm) {
                    if (guarani_fecha::es_mayor_igual($readm['fecha_vencimiento'], guarani_fecha::get_hoy())) {
                        $vig = true;
                        break;
                    }
		}
		return $vig;
	}

	function get_fecha_perdida_regularidad_seleccionada()
	{
		return $this->s__datos_perdidas_reg['fecha'];
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
		$this->s__pantalla = $this->get_id_pantalla();
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$filtro = 'sga_alumnos.alumno = '.$this->s__alumno;
		$this->s__datos_alumno = current($this->cn()->get_listado_preseleccion($filtro));
		$this->ev_info()->set_datos($this->s__datos_alumno);
	}

	//---- cuadro_cuadro_perdidas_regularidades -----------------------------------------
	
	function conf__cuadro_perdidas_regularidades(toba_ei_cuadro $cuadro)
	{
		$where = 'sga_perdida_regularidad.alumno = '.$this->s__alumno;
		$datos = $this->cn()->get_listado($where);
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800MAT_alumno_sin_perdida_regularidad'));
	}

	function conf_evt__cuadro_perdidas_regularidades__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Sólo se podrá editar el último registro.
		$datos = $this->dep('cuadro_perdidas_regularidades')->get_datos();
		$ultima_perdida_regularidad = current($datos);
		if ($datos[$fila] != $ultima_perdida_regularidad) {
			$evento->ocultar();
		}
	}
	
	function evt__cuadro_perdidas_regularidades__seleccion($seleccion)
	{
		$this->s__perdida_regularidad = $seleccion['perdida_regularidad'];
		$this->set_pantalla('seleccion_readmision');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION READMISION ------------------------------------------------
	//-----------------------------------------------------------------------------------    
	
	function conf__seleccion_readmision(toba_ei_pantalla $pantalla)
	{
		$where = 'sga_perdida_regularidad.alumno = '.$this->s__alumno;
		$this->s__datos_perdidas_reg = current($this->cn()->get_listado($where));
		$formateo = new toba_formateo('html');
		$this->s__datos_perdidas_reg['fecha_format'] = $formateo->formato_fecha($this->s__datos_perdidas_reg['fecha']);
		$this->s__datos_perdidas_reg['datos_perdida_reg_titulo'] = '';
		$this->ev_info()->set_datos(array_merge($this->s__datos_alumno, $this->s__datos_perdidas_reg));

		// Cargo el listado de readmisiones
		$where = 'sga_readmisiones.perdida_regularidad = '.$this->s__perdida_regularidad;
		$this->s__datos_readmisiones = $this->cn()->get_listado_readmisiones($where);

                if ($this->hay_readmision_vigente()) {
                    $pantalla->eliminar_evento('agregar');
		}
		
	}
	
	//---- cuadro_readmisiones ----------------------------------------------------------
	
	function conf__cuadro_readmisiones(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_readmisiones');
		$cuadro->set_datos($this->s__datos_readmisiones);
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}
	
	function evt__cuadro_readmisiones__seleccion($seleccion)
	{
		$this->s__readmision = $seleccion['readmision'];
		$this->cn()->cargar($seleccion);
		$this->set_pantalla('edicion');
	}
	
	function conf_evt__cuadro_readmisiones__seleccion(toba_evento_usuario $evento, $fila)
	{
		/**
		 * Sólo se puede editar la última readmisión de cada pérdida de regularidad.
		 * Como los registros vienen ordenados por la fecha de vencimiento, se supone
		 * al primero de ellos como el mas reciente y al único que se puede editar.
		 */
		if ($fila != 0) {
                    $evento->ocultar();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION -------------------------------------------------------------
	//-----------------------------------------------------------------------------------    

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_alumno);
		parent::conf__edicion($pantalla);
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'propuesta_descr', 'datos_perdida_reg_titulo', 'anio_academico', 'fecha_format'));
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_descr'), 2);
			$this->ev_info->set_columnas_fila(array('datos_perdida_reg_titulo'), 3);
			$this->ev_info->set_columnas_fila(array('anio_academico', 'fecha_format'), 4);
			
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('propuesta_descr', 'Propuesta');
			$this->ev_info->set_etiqueta('datos_perdida_reg_titulo', 'Datos de la Pérdida de Regularidad:');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('fecha_format', 'Fecha');
			$this->ev_info->set_td_etiqueta_width('70px');
		}
		
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__guardar()
	{
		$hay_solicitud_vigente = toba::consulta_php('co_readmisiones')->get_solicitud_readmision_vigente($this->s__perdida_regularidad);
		if (!empty($hay_solicitud_vigente)){
			$mensaje= "Ya existe una solicitud de readmisión para el alumno";
			$this->informar_msg($mensaje, 'error');
			$this->set_pantalla('preseleccion');
		}else{
			//Se desactivan las transacciones para genererar una única que contenga los dos pasos: actualizar alumno y la readmisión.
			$this->cn()->desactivar_transacciones_anidadas();
			try {
			toba::db()->abrir_transaccion();
			
				//Actualizo el alumno
				$contabiliza = $this->dep('editor')->get_contabiliza();
				$cant_readmisiones = toba::consulta_php('co_alumnos')->get_cant_readmisiones($this->get_preseleccion());
				$cant_readmisiones = ($contabiliza == comunes::si) ? ($cant_readmisiones + 1) : $cant_readmisiones;
				guarani::act('act_alumnos')->actualizar_alumno($this->get_preseleccion(), $cant_readmisiones, comunes::si);
				
				//Guardo las modificaciones
				$this->cn()->guardar();
				
				//Reseteo y redirijo la pantalla
				if ($this->volver_al_guardar) {
					$this->cn()->resetear();
					$this->set_pantalla('seleccion_readmision');
					unset($this->s__readmision);
				}
				
				toba::notificacion()->agregar($this->get_mensaje('800MAT_readmision_alumno'), 'info');
				toba::db()->cerrar_transaccion();
			} catch (guarani_error $e) {
				toba::db()->abortar_transaccion();
				toba::notificacion()->agregar($e->getMessage(), 'error');
				toba::logger()->error($e->getMessage());
			}
		}
	}
	
	function evt__cancelar()
	{
		if ($this->s__pantalla == 'edicion') {
			$this->cn()->resetear();
			$this->set_pantalla('seleccion_readmision');
			unset($this->s__readmision);
		} else {
			$this->set_pantalla('seleccion');
			unset($this->s__perdida_regularidad);
			unset($this->s__datos_perdidas_reg);
		}
	}
	
	function evt__cancelar_preseleccion()
	{
		parent::evt__cancelar_preseleccion();
		unset($this->s__alumno);
		unset($this->s__datos_alumno);
	}
	
}
?>
