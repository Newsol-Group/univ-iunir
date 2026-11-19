<?php
/**
 * Manejador de Entidades Generico
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */
class cn_manejador_entidad_nucleo extends guarani_pers_cn
{
	protected $s__entidad = array();
	protected $s__cursor;
	protected $s__id_nuevo = 0;
	protected $s__mapeo_cargadas = array(); //Mapea la clave de la DB con el ID interno
	protected $s__desactivar_transacciones_anidadas = false;
	
	static protected $co;

	function ini()
	{
		foreach ($this->s__entidad as $entidad) {
			$this->agregar_dependencia_por_indice($entidad['rol'], $entidad['indice']);
			$this->dep($entidad['rol']); // El consumo es el que instancia realmente el objeto...
			$this->dep($entidad['rol'])->set_controlador($this);
		}
		toba::logger()->info('El manejador de entidades: Inicializacion. Entidades cargadas: [' . guarani_arreglos::count($this->s__entidad) . ']');
	}

	/**
	 * Retorna la clase de consulta de la entidad instanciada
	 * @return toba::consulta_php
	 */
	function co()
	{
		if (!isset(self::$co)) {
			self::$co = toba::consulta_php($this->get_clase_consulta());
		}
		return self::$co;
	}

	/**
	 * Retorna un listado de las entidades presentes
	 * @return recordset
	 */
	function get_listado($filtro)
	{
		return self::$co()->get_listado($filtro);
	}

	/**
	 * Indica el INDICE del CN que implementa la entidad manejada
	 * @return string
	 */
	function get_indice_instancia()
	{
		
	}

	/**
	 * Indica la clase  de consulta de la entidad manejada
	 * @return string
	 */
	function get_clase_consulta()
	{
		
	}

	/**
	 * Construye una nueva instancia de la entidad manejada
	 * @return cn_entidad
	 */
	function nueva()
	{
		$indice_componente = $this->get_indice_instancia();
		$rol = 'rol_' . $this->s__id_nuevo;
		$this->agregar_dependencia_por_indice($rol, $indice_componente);
		$this->dep($rol)->resetear();
		$this->dep($rol)->inicializar_estado();
		$this->dep($rol)->set_controlador($this);
		$this->s__entidad[$this->s__id_nuevo]['indice'] = $indice_componente;
		$this->s__entidad[$this->s__id_nuevo]['rol'] = $rol;
		$this->s__entidad[$this->s__id_nuevo]['nueva'] = true;
		$this->s__cursor = $this->s__id_nuevo;
		if ($this->s__desactivar_transacciones_anidadas) {
			$this->dep($rol)->desactivar_transacciones_anidadas();
		}
		$this->s__id_nuevo++;
		return $this->entidad();
	}

	function etiquetas($dep = null)
	{
		return toba::cn($this->get_indice_instancia())->etiquetas($dep);
	}

	/**
	 * Carga una instancia de la entidad manejada.
	 * Invoca al metodo 'cargar' de la entidad
	 * @return cn_entidad
	 */
	function cargar($id, $parametros = null)
	{
		$indice = $this->get_indice_db_convertido($id);
		if (!isset($this->s__mapeo_cargadas[$indice])) {
			$indice_componente = $this->get_indice_instancia();
			$rol = 'rol_' . $this->s__id_nuevo;
			$this->agregar_dependencia_por_indice($rol, $indice_componente);
			$this->dep($rol)->set_controlador($this);
			$this->dep($rol)->cargar($id, $parametros);
			$this->s__entidad[$this->s__id_nuevo]['indice'] = $indice_componente;
			$this->s__entidad[$this->s__id_nuevo]['rol'] = $rol;
			$this->s__entidad[$this->s__id_nuevo]['nueva'] = false;
			$this->s__cursor = $this->s__id_nuevo;
			$this->s__mapeo_cargadas[$indice] = $this->s__id_nuevo;
			$cursor = $this->s__entidad[$this->s__id_nuevo];
			if ($this->s__desactivar_transacciones_anidadas) {
				$this->dep($rol)->desactivar_transacciones_anidadas();
			}
			$this->s__id_nuevo++;
			return $this->entidad();
		} else {
			throw new guarani_error('La entidad seleccionada para cargar ya esta instanciada en el manejador');
		}
	}

	/**
	 * Indica que la entidad no existia en la base de datos
	 * @return boolean
	 */
	function entidad_es_nueva($id = null)
	{
		if (!$id && !isset($this->s__cursor)) {
			throw new guarani_error('La entidad seleccionada no existe en memoria');
		}
		$indice = isset($id) ? $id : $this->s__cursor;
		return $this->s__entidad[$indice]['nueva'];
	}

	function entidad_cargada()
	{
		return guarani_arreglos::count($this->s__entidad) > 0;
	}

	/**
	 * Retorna una instancia de la entidad cargada por ID
	 * @return cn_entidad
	 */
	function entidad($id = null)
	{
		if (!$id && !isset($this->s__cursor)) {
			throw new guarani_error('ENTIDAD: No existe una entidad seleccionada');
		}
		if ($id) {
			if (in_array($id, $this->s__entidad)) {
				return $this->dep($this->s__entidad[$id]['rol']);
			}
			return null;
		}
		return $this->dep($this->s__entidad[$this->s__cursor]['rol']);
	}

	/**
	 * Selecciona una entidad
	 */
	function seleccionar($id)
	{
		$this->s__cursor = $id;
	}

	/**
	 * Dispara la eliminacion de la entidad cargada
	 */
	function eliminar($id = null)
	{
		toba::logger()->info('El manejador de entidades: ELIMINAR');
		if (!$id && !isset($this->s__cursor)) {
			throw new guarani_error('ELIMINAR ENTIDAD: No existe una entidad seleccionada');
		}
		$indice = isset($id) ? $id : $this->s__cursor;
		$this->entidad($indice)->eliminar();
		$this->resetear();
	}

	/**
	 * Dispara la sincronizacion de las entidades cargadas
	 */
	function guardar()
	{
		toba::logger()->info('El manejador de entidades: GUARDAR');
		$claves = array();
		foreach ($this->_dependencias as $clave => $dep) {
			$omitir = guarani_texto::contiene_subcadena($clave, 'entidad');
			if (!$omitir) {
				$claves[] = $dep->sincronizar();
			}
		}
		foreach (array_keys($this->s__entidad) as $entidad) {
			$this->s__entidad[$entidad]['nueva'] = false;
		}
		return $claves;
	}

	/**
	 * Resetea el estado interno del manejador
	 */
	function resetear()
	{
		toba::logger()->info('El manejador de entidades: RESETEAR');
		foreach ($this->_dependencias as $id => $dep) {
			$dep->resetear();
			$this->descargar_dependencia($id);
		}
		$this->s__cursor = null;
		$this->s__id_nuevo = 0;
		$this->s__entidad = array();
		$this->s__mapeo_cargadas = array();
	}

	private function get_indice_db_convertido($id)
	{
		if (is_array($id)) {
			return implode('%-%', $id);
		} else {
			return '%-%' . $id;
		}
	}

	function desactivar_transacciones_anidadas()
	{
		$this->s__desactivar_transacciones_anidadas = true;
		foreach ($this->_dependencias as $dep) {
			$dep->desactivar_transacciones_anidadas();
		}
	}

	function activar_transacciones_anidadas()
	{
		$this->s__desactivar_transacciones_anidadas = false;
		foreach ($this->_dependencias as $dep) {
			$dep->activar_transacciones_anidadas();
		}
	}

}
?>