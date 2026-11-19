<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_mesas_actas extends ci_nav_mesas
{
	// Acta seleccionada
	protected $s__acta_seleccionada;

	protected $s__datos_acta;
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf()
	{
		parent::conf();
		//Se setea el aviso de la deshabilitación del evento de selección de Actas en la pantalla correspondiente.
		if ($this->s__pantalla == 'seleccion_mesa') {
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse Mesas con Actas creadas');
		}
		//Se cambia el título de la pantalla de selección de Mesas a selección de Actas.
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->set_titulo('Selección de Actas');	
		}
	}
	
	function conf__seleccion_acta(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
		$datos = $this->get_datos_ev_mesa();
		$this->ev_info()->set_datos($datos);
	}
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$datos = $this->get_datos_ev_acta();
		// Agrega Datos del acta al los datos de la mesa que se agregaron al ev_info en el parent::conf__edicion
		$this->ev_info()->set_datos($datos,false);
	}

	function get_datos_ev_acta()
	{
		if (!isset($this->s__datos_acta)){
			$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($this->get_acta_seleccionada());
		}
		return $this->s__datos_acta;
	}
	
	//---- cuadro(mesas) ----------------------------------------------------------------

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		/**
		 * Si la mesa no tiene actas, entonces no tiene sentido que el usuario pueda seleccionarla, ya que en la siguiente 
		 * pantalla no habrá actas disponibles.
		 */
		$datos = $this->dependencia('cuadro')->get_datos();
		if ($datos[$fila]['cant_actas_sin_anular'] == 0) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->setear_datos_mesa($seleccion);
		$this->set_pantalla('seleccion_acta');
	}
	
	//---- cuadro_actas -----------------------------------------------------------------

	// Método que recupera las actas a mostrar, para ser Redefinido en la subclase que sea necesario.
	protected function get_listado_actas()
	{
		$mesa 	= toba::db()->quote($this->get_mesa_examen());
		$filtro = " sga_mesas_examen.mesa_examen = $mesa ";
		return toba::consulta_php('co_actas')->get_listado_actas_examen($filtro);
	}

	// Setea la representación del Estado del Acta, según si son abiertas o cerradas (puede redifinirse para otras clasificaciones).
	protected function formatear_semaforo($datos)
	{
		foreach ($datos as $clave => $valor) {
			if ($datos[$clave]['estado'] == acta::estado_abierta) {
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
			} elseif ($datos[$clave]['estado'] == acta::estado_cerrada){
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Acta Cerrada', null);
			} elseif ($datos[$clave]['estado'] == acta::estado_anulada){
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/azul.png', true, null, null, 'Acta Anulada', null);
			}
		}
		return $datos;
	}
	
	function conf__cuadro_actas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_actas');
		$nombre = $etiqueta->get_enf('plural');
		$msj = guarani_mensajes::get($this->indx_msg_eof, array($nombre));
		$cuadro->set_eof_mensaje($msj);
		//Obtengo las Actas de la Mesa actualmente seleccionada
		$datos = $this->get_listado_actas();
		//Se setea la imagen con el color correspondiente a si el acta está abierta o cerrada
		$datos = $this->formatear_semaforo($datos);
		//Seteo el cuadro
		$cuadro->set_datos($datos);
	}
	
	function evt__cuadro_actas__seleccion_acta($seleccion)
	{
		$this->seleccionar_acta($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function seleccionar_acta($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		// La selección y carga de evaluaciones se hace en este lugar ya que si se modifica el DR y se vuelve para seleccionar otra acta, esta DT queda modificado
		$this->seleccionar_mesa('edicion');
	}

	function get_acta_seleccionada()
	{
		return $this->s__acta_seleccionada['id_acta'];
	}
	
	function get_mesa_acta_seleccionada()
	{
		return isset($this->s__acta_seleccionada['mesa_examen']) ? $this->s__acta_seleccionada['mesa_examen'] : $this->s__mesa_examen;
	}
	
	function get_llamado_mesa()
	{
		return isset($this->s__acta_seleccionada['llamado_mesa_entidad']) ? $this->s__acta_seleccionada['llamado_mesa_entidad'] : $this->s__llamado_mesa;
	}
	
	function get_llamado()
	{
		return $this->s__acta_seleccionada['llamado_mesa'];
	}
	
	function get_evaluacion()
	{
		return $this->s__acta_seleccionada['evaluacion'];
	}
	
	//---- Eventos ----------------------------------------------------------------------
	
	/**
	 * En este caso, al cancelar, deben resetearse las variables locales, el cn y las variables de edición, y volver la 
	 * pantalla de selección de actas.
	 */
	function evt__cancelar()
	{
		$this->dependencia('editor')->resetear_variables();
		$this->resetear_variables();
		$this->cn()->resetear();
		$this->set_pantalla('seleccion_acta');
	}
	
	function resetear_variables()
	{
		unset($this->s__acta_seleccionada);
		unset($this->s__datos_acta);
	}
	
	function resetear_variables_llamado_mesa()
	{
		parent::resetear_variables();
	}
}
?>