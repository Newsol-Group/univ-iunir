<?php
/**
 * Interface que debe implementar una extensión o redefinición de un complemento de guarani que se desea mostrar en la pantalla de inicio
 */
class guarani_complemento implements guarani_interface_complemento
{
	protected $id; //Indica el Id del complemento que maneja
	protected $datos_basicos; 
	static private $instancia;
	static private $ev;
	
	function __construct($id_complemento)
	{
		$this->id = $id_complemento;
		$this->set_datos_basicos();
		$clase_ev = $this->get_clase_implementacion();
		$this->ev = new $clase_ev($this);
	}
	
	static function instancia($id_complemento)
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new guarani_complemento($id_complemento);	
		}
		return self::$instancia;	
	}	

	/**
	 * Setea los Datos Basicos del complemento (los que surgen de la tabla mdp_complementos)
	 * 
	 */
	function set_datos_basicos()
	{
		$this->datos_basicos = toba::consulta_php('co_complementos')->get_datos_basicos($this->get_id());
		if (is_null($this->datos_basicos)) {
			throw new guarani_error("No existe un complemento con id = {$this->get_id()}");
		}
	}

	function get_id()
	{
		return $this->id;
	}
	
	function get_datos_basicos()
	{
		return $this->datos_basicos;
	}
	
	/**
	 *  @return string Ubicacion del Complemento en la pantalla
	 */
	function get_ubicacion()
	{
		return $this->datos_basicos['ubicacion'];
	}

	/**
	 * @return string Nombre de la clase que implementa el complemento
	 */
	function get_clase_implementacion()
	{
		return $this->datos_basicos['clase_implementacion'];		
	}

	//-- Interface Gráfica --------------------------------------------

	/**
	 * Devuelve el codigo HTML para mostrar el complemento en la Pantalla de Inicio
	 * @return string Codigo HTML que muestra el complemento 
	 */
	function generar_layout() 
	{
		$this->ev->generar_layout();
	}

}
?>