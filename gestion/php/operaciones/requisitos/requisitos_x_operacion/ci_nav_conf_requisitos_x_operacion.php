<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class ci_nav_conf_requisitos_x_operacion extends ci_nav_con_preseleccion
{
	protected $s__operacion_seleccionada;
	protected $s__mapeo_indice;

	const separador = '-%-';

	function ini()
	{
		$this->set_concepto_preseleccion('Operación');
		
		// Si se viene de la operación de Configurar Requisitos por Acción con una Acción preseleccionada, se
		// setea dicho filtro por defecto para que se muestren el cuadro las Operaciones de dicha Acción.
		$accion = toba::memoria()->get_parametro('accion');
		if (isset($accion)) {
			$datos_accion = toba::consulta_php('co_acciones')->get_accion_datos($accion);
			$this->dependencia('filtro_preseleccion')->columna('accion')->set_estado(array('condicion' => 'es_igual_a', 'valor' => $datos_accion['descripcion'], 'condicion_cs' => 'checked', 'condicion_as' => 'checked'));
			$this->s__datos_preseleccion = array('accion' => array('condicion' => 'es_igual_a', 'valor' => $datos_accion['descripcion'], 'condicion_cs' => 'checked', 'condicion_as' => 'checked'));
			$this->s__filtro_preseleccion = "sga_acciones.accion = $accion";
		} 
	}
	
	function set_preseleccion($seleccion)
	{
		$this->s__operacion_seleccionada = $this->cn()->get_info_operacion($seleccion);
	}
	
	/**
	 * Arma la clave como la concatenacion de todos los campos necesarios para determinar el requisito_x_accion 
	 * seleccionado, independientemente del grupo en el cual esté, para eso son necesarios los campos tipo, operador_not, 
	 * requisito-parametros o entidad-estado. Luego en la seleccion se vuelve a separar la clave del requisito seleccionado.
	 * Esto se hace ya que los campos parametros y estado, pueden ser nulos y toba no permite que un campo nulo sea 
	 * parte de la clave del cuadro.
	 */
	function armar_clave_para_seleccion($datos)
	{
		unset($this->s__mapeo_indice);
		foreach ($datos as $key => $dato){
			if ($key == 0) {
				foreach ($dato as $key_dato => $valor){
					$this->s__mapeo_indice[]=$key_dato;
				}
			}
			$datos[$key]['clave'] = implode(self::separador, $dato);
		}
		return $datos;
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje($this->get_msg_eof());
		$filtro = "sga_acciones_operacion.operacion = '{$this->s__operacion_seleccionada['operacion']}'";
		$datos = $this->cn()->get_listado($filtro);
		/**
		 * Se da a los requisitos el formato necesario para mostrarlos en el cuadro con sus atributos (entidad-estado / 
		 * requisito-parámetros) y que el usuario pueda distinguir unívocamente cuál se selecciona para su configuración
		 * a nivel de operación.
		 */
		foreach ($datos as $key=>$req_x_accion) {
			$op_not = '';
			if ($req_x_accion['operador_not']) {
				$op_not = 'NO ';
			}
			if ($req_x_accion['tipo'] == requisito::comp_requisito) {
				if (isset($req_x_accion['parametros'])) {
					$lista_params = guarani_texto::string_armar_lista($req_x_accion['parametros']);
				} else {
					$lista_params = '';
				}
				$datos[$key]['requisito_descr'] = $op_not . $req_x_accion['requisito_nombre'] . ' ' . $lista_params; 
			} elseif ($req_x_accion['tipo'] = requisito::comp_entidad) {
				$datos[$key]['requisito_descr'] = $op_not . $req_x_accion['requisito_nombre'] . ' - ' . $req_x_accion['estado_nombre']; 
			}
		}
		$datos = $this->armar_clave_para_seleccion($datos);
		$cuadro->set_datos($datos);
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('accion', 'operacion'));
			$this->ev_info->set_columnas_fila(array('accion'), 1);            
			$this->ev_info->set_columnas_fila(array('operacion'), 2);           
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('operacion', 'Operación');
			$this->ev_info->set_etiqueta('accion', 'Acción');
		}
		return $this->ev_info;
	}
	
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_datos_ev_operacion();
		$this->ev_info()->set_datos($datos);
	}
	
	function get_datos_ev_operacion()
	{
		$datos['operacion'] = $this->s__operacion_seleccionada['operacion_codigo_y_nombre'];
		$datos['accion']	= $this->s__operacion_seleccionada['accion_descripcion'];
		return $datos;
	}
	
	/**
	 * Obtiene la seleccion del cuadro, separando del string de todos los campos concatenados que fueron necesarios para 
	 * formar la clave. Esto se hizo de esta manera porque Toba no permite definir como clave campos que pueden ser nulos.
	 * 
	 */
	function obtener_seleccion_cuadro($seleccion)
	{
		$seleccion = explode(self::separador, $seleccion['clave']);
		foreach($seleccion as $key => $valor) {
			$seleccion_indices[$this->s__mapeo_indice[$key]] = $valor;
		}
		return 	$seleccion_indices;
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $this->obtener_seleccion_cuadro($seleccion);
		$parametros = $this->s__seleccion;
		$this->cn()->cargar($this->s__operacion_seleccionada['operacion'], $parametros);
		$this->set_pantalla('edicion');
	}
		
	function get_seleccion()
	{
		return $this->s__seleccion;
	}

	function evt__guardar()
	{
		parent::evt__guardar();		
		$this->dep('editor')->resetear();
	}
	
	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor')->resetear();
	}
}
?>