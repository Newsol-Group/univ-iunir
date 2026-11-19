<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Mesas de Examen
 */
class cn_man_modificar_mesas_de_examen extends cn_man_mesas_de_examen
{	
    protected $s__actas;
    
    function get_actas($llamado_mesa = null)
    {        
        if (!isset($this->s__actas)) {
            $filtro = "sga_actas.llamado_mesa = $llamado_mesa";
            $this->s__actas = toba::consulta_php('co_actas')->get_listado_actas_examen($filtro);        
        }
        return $this->s__actas;
    }
    
    function resetear_actas() 
    {
        unset($this->s__actas);
    }
    
	function get_indice_instancia()
	{
	 	return 'modificacion_llamado_mesa';
	}
	
	function get_listado_llamados($filtro)
	{
		return toba::consulta_php('co_mesas_de_examen')->get_llamados_editables($filtro);
	}

	/** Utilizado al Guardar en la operación de Modificar Mesas de Examen.
	 *  La entidad cargada siempre será un Llamado Mesa, y la solapa Instancia debe
	 *  grabar datos asociados a la mesa y no al llamado. Se realiza manualmente, 
	 *	insertando las nuevas Instancias en la BD, previo al sincronizar del DR.
	 * @param type $instancias_a_agregar
	 */
	
	function guardar_mesa_modificada($instancias_a_agregar=array())
	{
		try {
			$datos_llamado = $this->entidad()->get_datos_llamado();
			$mesa = $datos_llamado['mesa_examen'];
			toba::db()->abrir_transaccion();
			if (!empty($instancias_a_agregar)) {
				foreach ($instancias_a_agregar as $instancia) {
					guarani::act('act_mesas_de_examen_nucleo')->insertar_instancia_mesa($mesa, $instancia['instancia'], $instancia['escala_nota']);
				}
			}
			parent::guardar();
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}
    
    /** 
     * Si hay cambio de fecha o cambios en los docentes entonces se actualiza 
     * la version
     */
    function hay_cambios()
    {
        $actas = $this->get_actas();
        if ($this->entidad()->hay_cambios() && !empty($actas)) {            
            //De las actas que estan asociadas tengo que incrementar la versión            
            foreach($actas as $acta) {
                //solo se incrementa la versión si la version impresa y version son iguales
                if ($acta['version'] == $acta['version_impresa']) {
                    guarani::act('act_actas')->actualizar_version_acta($acta['id_acta'], $acta['version']);
                }
                
            }
            
        }
    }
}
?>