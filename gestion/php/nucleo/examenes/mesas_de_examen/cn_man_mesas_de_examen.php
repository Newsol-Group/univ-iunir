<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class cn_man_mesas_de_examen extends cn_manejador_entidad
{
	protected $s__indice_entidad_actual = 'mesa_examen';
	protected $s__turno_examen;

	/**
	 * Se extiende el ini del cn_manejador_entidad para poder crear el arreglo de nombres default de mesas de examen.
	 */
	function ini()
	{
		parent::ini();
		// Se establecen los nombres para las mesas de examen (personalizable)
		mesa_de_examen::set_nombres_default_mesas();
	}

	function get_indice_instancia()
	{
		return $this->s__indice_entidad_actual;
	}

	function usar_mesas()
	{
		$this->s__indice_entidad_actual = 'mesa_examen';
	}

	function usar_llamados()
	{
		$this->s__indice_entidad_actual = 'llamado_mesa';
	}

	function get_clase_consulta()
	{
		return 'co_mesas_de_examen';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_mesas_de_examen')->get_mesas_con_llamados($filtro);
	}

	/**
	 * Se setea el dato del Turno de Exámen seleccionado en la pantalla inicial por el usuario para mantenerlo en las clases
	 * de negocio, ya que se utiliza para diferentes validaciones y búsqueda de datos, y el mismo no se guarda en ninguna de 
	 * de los Drs manipulados.
	 */
	function set_turno_examen($turno)
	{
		$this->s__turno_examen = $turno;
	}

	function cargar_mesa($id_mesa)
	{
		$this->usar_mesas();
		$this->cargar(array('mesa_examen' => $id_mesa));
		$this->entidad()->set_turno_asociado($this->s__turno_examen);
	}

	/**
	 * Se distingue la carga común del llamado de la carga para eliminarlo, ya que 
	 * esta última no deben cargar las tablas con el campo requerido para los forms ml, 
	 * y en los otros casos sí.
	 */
	function cargar_llamado($id_llamado, $para_eliminar = false)
	{
		$this->usar_llamados();
		$this->cargar(array('llamado_mesa' => $id_llamado));
	}

	function agregar_mesa_a_actividad($id_activ, $id_turno)
	{
		$this->usar_mesas();
		$this->nueva();
		$cant_mesas = toba::consulta_php('co_mesas_de_examen')->get_cant_mesas_de_actividad_en_turno_examen($id_activ, $id_turno);
		$nombre = mesa_de_examen::get_nombre_default_mesa($cant_mesas);

		$datos_iniciales['nombre'] = $nombre;
		$datos_iniciales['elemento'] = $id_activ;
		$this->entidad()->set_datos_generales($datos_iniciales);
		$this->entidad()->set_turno_asociado($this->s__turno_examen);
	}

	function agregar_llamado_a_mesa($id_mesa)
	{
		$this->usar_llamados();
		$this->nueva();
		$datos_iniciales['mesa_examen'] = $id_mesa;
		$this->entidad()->set_datos_generales($datos_iniciales);
	}

	/**
	 * Eliminación en cascada. Cuando se elimina una mesa se eliminan todos sus llamados asociados.
	 */
	function eliminar_mesa($id_mesa)
	{
		$this->eliminar_llamados_mesa($id_mesa);
		$this->cargar_mesa($id_mesa);
		$this->eliminar();
	}

	/**
	 * Elimina todos los llamados asociados a la mesa dada. Para esto primero los recupera, y 
	 * luego desactiva las transacciones anidadas, para que se eliminen o bien todos o ninguno.
	 */
	function eliminar_llamados_mesa($id_mesa)
	{
		$id_mesa = toba::db()->quote($id_mesa);
		$filtro = "sga_mesas_examen.mesa_examen = $id_mesa";
		$llamados = $this->co()->get_mesas_con_llamados($filtro);
		$this->desactivar_transacciones_anidadas();
		foreach ($llamados as $llamado) {
			$this->eliminar_llamado_mesa($llamado['llamado_mesa']);
		}
	}

	function eliminar_llamado_mesa($id_llamado)
	{
		$this->usar_llamados();
		$this->cargar_llamado($id_llamado);
		$this->eliminar();
	}

}
?>