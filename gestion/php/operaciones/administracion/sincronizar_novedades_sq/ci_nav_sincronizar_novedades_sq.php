<?php
/**
 * 	
 *
 * @package		Administración
 * @subpackage 	Sincronizar Novedades con SQ
 * 
 */
class ci_nav_sincronizar_novedades_sq extends ci_navegacion
{
	
	protected $s__filtro_propuestas;
	protected $s__datos_propuestas;
			
	function ini__operacion()
    {
		// Si la conexión a SQ falla no le permito ingresar a la operación
        if (!cobro::conexion_ok()) {
            $mje = guarani_mensajes::get('800ADM_error_conexion_SQ');
            throw guarani::error($mje);
        }
    }
	
	function get_msg_eof($dep = null)
    {
          $indx_msg = $this->indx_msg_eof;
          if (isset($this->s__filtro) && !empty($this->s__filtro)) {
              $indx_msg = $this->indx_msg_eof . '_filtrado';
          }
          return guarani_mensajes::get($indx_msg, array('novedades'));
    }
	
	//-----------------------------------------------------------------------------------
	//---- Filtro propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro_propuestas__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_propuestas = $datos;
			$this->s__filtro_propuestas = $this->dep('filtro_propuestas')->get_sql_where();
		} else {
			unset($this->s__datos_propuestas);
			$this->s__filtro_propuestas = '';
		}
	}

	function evt__filtro_propuestas__cancelar()
	{
		unset($this->s__datos_propuestas);
		unset($this->s__filtro_propuestas);
	}

	function conf__filtro_propuestas(toba_ei_filtro $filtro)
	{		
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos_propuestas)) {
			$filtro->set_datos($this->s__datos_propuestas);
		}	
	}
	
	//-----------------------------------------------------------------------------------
	//---- Cuadro propuestas ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_propuestas(toba_ei_cuadro $cuadro)
	{
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro) && !$this->con_listado_inicial()) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->cn()->get_listado_propuestas_sincronizar(isset($this->s__filtro_propuestas) ? $this->s__filtro_propuestas : null);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
	
	function evt__cuadro_propuestas__sincronizar($seleccion)
	{
		$this->cn()->sincronizar_propuesta_cobrable($seleccion['plan_id']);
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- Filtro inscripciones ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro_inscripciones__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro_inscripciones')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_inscripciones__cancelar()
	{
		parent::evt__filtro__cancelar();
	}

	function conf__filtro_inscripciones(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Cuadro inscripciones ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_inscripciones(toba_ei_cuadro $cuadro)
	{
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro) && !$this->con_listado_inicial()) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->cn()->get_listado_inscripciones_sincronizar($this->get_filtro_condicion());
			$cuadro->set_datos($datos);
		}
		$mensaje_cuadro_vacio = $this->get_msg_eof()."<br>".guarani_mensajes::get('800_sq_verificar_rest_hooks', null, false);
		$cuadro->set_eof_mensaje($mensaje_cuadro_vacio);
	}
	
	function evt__cuadro_inscripciones__sincronizar($seleccion)
	{
		$this->cn()->sincronizar_inscripcion($seleccion['propuesta_aspira'], $seleccion['corte']);
	}
}