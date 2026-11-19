<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class cn_man_matrices_equivalencia extends cn_manejador_entidad
{
	protected $s__indice_entidad_actual = 'matriz_equivalencia';
	
	function get_indice_instancia()
	{
	 	return $this->s__indice_entidad_actual;
	}

	function usar_matrices()
	{
	 	$this->s__indice_entidad_actual = 'matriz_equivalencia';
	}
	
	function usar_grupos()
	{
	 	$this->s__indice_entidad_actual = 'grupo_equivalencia';
	}
	
	function get_clase_consulta()
	{
		return 'co_matrices_de_equivalencia';
	}
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_matrices_de_equivalencia')->get_matrices_equivalencia_con_grupos($filtro);
	}
	
	function cargar_matriz($id_matriz)
	{
		$this->usar_matrices();
		$this->cargar(array('matriz' => $id_matriz));
	}

	function cargar_grupo($id_grupo)
	{
		$this->usar_grupos();
		$this->cargar(array('grupo_equivalencia' => $id_grupo));
	}
	
	function cargar_grupo_matriz($id_matriz)
	{
		$this->usar_matrices();
		$this->entidad()->cargar_grupos($id_matriz);
	}

	function agregar_matriz_a_plan_version($id_plan_version_destino)
	{
		$this->usar_matrices();
		$this->nueva();
		$datos_iniciales['plan_version_destino'] = $id_plan_version_destino;
		$this->entidad()->set_datos_iniciales($datos_iniciales);
	}

	function agregar_grupo_a_matriz($id_matriz)
	{
		$this->usar_grupos();
		$this->nueva();
		$datos_iniciales['matriz'] = $id_matriz;
		$this->entidad()->set_datos_iniciales($datos_iniciales);
	}

	//Eliminación en cascada. Cuando se elimina una matriz se eliminan todos sus grupos asociados.
	function eliminar_matriz($id_matriz)
	{
		$this->eliminar_grupos_matriz($id_matriz);
		$this->cargar_matriz($id_matriz);
		$this->eliminar();
	}

	//Elimina todos los grupos asociados a la matriz dada. Para esto primero los recupera, y luego desactiva las
	//transacciones anidadas, para que se eliminen o bien todos o bien ninguno.
	function eliminar_grupos_matriz($id_matriz)
	{
		$grupos = $this->co()->get_grupos_matriz_equivalencia($id_matriz);
		$this->desactivar_transacciones_anidadas();
		foreach ($grupos as $grupo) {
			$this->eliminar_grupo($grupo['grupo_equivalencia']);
		}
	}

	/**
	 * Elimina el grupo dado de una matriz determinada. Consecuentemente, se eliminan todas las actividades origen y 
	 * destino asociadas al mismo. No es necesario realizar la eliminación en cascada de las mismas ya que en este 
	 * caso no se cargan como entidades independientes, si no que están dentro del mismo Datos Relación y se eliminarán 
	 * al eliminar el Grupo padre de las mismas.
	 *
	 */
	function eliminar_grupo($id_grupo)
	{
		$this->cargar_grupo($id_grupo);
		$this->eliminar();
	}
	
}

?>