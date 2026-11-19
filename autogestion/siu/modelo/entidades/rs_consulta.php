<?php

namespace siu\modelo\entidades;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use \SIU\Chulupi\util\modelo;

/**
 * Un resultset que se puede guardar en sesion del usuario. Se guardan los datos
 * para regenerarla, por lo que no ocupa mucha memoria, pero ejecuta las consulta.
 * <<<<<<<
 * Usa modelo\catalogo
 * 
 * Las formas de usarla son 2:
 *  - Crearla con el constructor, para luego guardarla (persistir)
 *  - Recuperarla del metodo-fabrica recuperar_consulta que la recontruye a partir
 * de la sesion.
 */
class rs_consulta extends modelo\RsBase implements modelo\persistible
{

	protected $clase, $metodo, $parametros, $config = array();

	function __construct($clase, $metodo, $parametros)
	{
		parent::__construct();
		$this->clase = $clase;
		$this->metodo = $metodo;
		$this->parametros = $parametros;
	}

	/**
	 * !IMPORTANT - Para inyectar columnas, llamar asi 
	 * $datos = &$intancia->get_datos(). De esta forma
	 * se obtiene correctamente una referencia.
	 * @return array
	 */
	public function &get_datos()
	{
		if (!isset($this->datos)) {
			$this->datos = catalogo::consultar($this->clase, $this->metodo, $this->parametros, $this->config);
		}
		return $this->datos;
	}

	//--------------------------
	//Persistible
	//------------------------

	function get_id_unico($sal)
	{
		$claves = array(
			$this->clase,
			$this->metodo,
			serialize($this->parametros),
			$sal
		);

		$clave = sha1(implode('#', $claves));
		return $clave;
	}

	function get_parametros_para_guardar()
	{
		$params = array($this->clase,
			$this->metodo,
			$this->parametros,
			$this->columna_id,
			$this->entidades
		);
		return $params;
	}

	static function regenerar_instancia($objeto, $params)
	{
		$instancia = new rs_consulta($params[0], $params[1], $params[2]);
		$instancia->set_columna_id($params[3]);
		$instancia->set_entidades($params[4]);
		return $instancia;
	}
	
	
	protected function set_entidades($entidades)
	{
		//solo para inyectar al reconstruir!
		$this->entidades = $entidades;
	}

}

?>
