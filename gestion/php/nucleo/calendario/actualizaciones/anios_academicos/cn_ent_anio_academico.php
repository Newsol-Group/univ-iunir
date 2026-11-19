<?php
/**
 *	Representa un Año Académico
 *
 * @package Calendario
 * @subpackage Años Académicos
 */
class cn_ent_anio_academico extends cn_entidad
{
	function cargar($id)
	{
		$this->tabla('fechas')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_anio_academico($datos['anio_academico']);
		$this->tabla('anios_academicos')->set($datos);
	}
    
    function set_datos_fechas($datos) 
    {
        $this->validar_fechas($datos);
		$this->tabla('fechas')->set($datos);
        $this->tabla('ra')->eliminar_filas();        
        $aux = array();
        foreach ($datos['responsables'] as $resposanble) {
            $aux['responsable_academica'] = $resposanble;
            $this->tabla('ra')->nueva_fila($aux);
        }  
    }
    
    function eliminar_fecha()
    {
        $this->tabla('fechas')->eliminar_fila($this->tabla('fechas')->get_cursor());        
    }
    
    function set_cursor($cursor)
    {
        $this->tabla('fechas')->set_cursor($cursor);
    }
    
    function hay_cursor()
	{
		return $this->tabla('fechas')->hay_cursor();
	}

	function unset_cursor()
	{
		$this->tabla('fechas')->resetear_cursor();
	}
    
    function get_desc_responsables_academicas()
    {
        $ra = implode(',', aplanar_matriz($this->get_responsables_academicas(), 'responsable_academica'));
        return toba::consulta_php('co_responsables_academicas')->get_responsables_academicas(" responsable_academica IN ($ra)");
    }
    
    function get_responsables_academicas()
    {
        return $this->tabla('ra')->get_filas();
    }
    
    function get_responsables_academicas_lista()
    {   
        $where = '';        
        $ras = aplanar_matriz($this->tabla('ra')->get_filas(null, false, false), 'responsable_academica');
        if ($this->hay_cursor()) {
            $propias = aplanar_matriz($this->tabla('ra')->get_filas(), 'responsable_academica');
            $quitar = array_intersect($ras, $propias);
            $cant = guarani_arreglos::count($ras);
            for ($i = 0; $i <= $cant; $i ++) {
                //Tengo que quitar las que tengo seleccionadas para este registro
                if (isset($quitar[$i]) && $ras[$i] == $quitar[$i]) {
                    unset($ras[$i]);
                }
            }
        }
        foreach($ras as $ra) {
            $aux[] = $ra;
        }
        
        if (!empty($aux)) {
            $where = ' sga_responsables_academicas.responsable_academica NOT IN ('.implode(',', $aux).')';
        }
        return toba::consulta_php('co_responsables_academicas')->get_ras_institucion_instalacion($where);        
    }

    function seleccionar_fecha($id_fecha)
    {
        $fechas = $this->tabla('fechas')->get_filas();
        foreach($fechas as $fecha) {
            if ($fecha['fecha_inicio'].','.$fecha['fecha_fin'] == $id_fecha) {
                $this->set_cursor($fecha['x_dbr_clave']);
                break;
            }
        }       
    }

	function get_datos($tabla=null)
	{
        if ($this->tabla('anios_academicos')->hay_cursor()) {
            $anios = $this->tabla('anios_academicos')->get();
            $fechas = $this->tabla('fechas')->get_filas();
            $ra = $this->tabla('ra')->get_filas();        
            return array_merge($anios, $fechas, $ra);
        }
	}

    function get_fecha_seleccionada()
    {        
        return $this->tabla('fechas')->get();
    }
	
	function validar_fechas($datos)
	{   
        if ($datos['fecha_inicio'] > $datos['fecha_fin']) {
            $this->set_error('800_control_fechas', array(' Hasta', 'menor o igual', ' Desde'));
        }
        //Valido si cada responsable academica se solapa con otra fecha
        foreach($datos['responsables'] as $ra) {
            $id_fecha = null;
            //Si estoy modificando no debo tener en cuenta está fecha para comparar con los demás            
            if ($this->hay_cursor()) {
                $fecha = $this->get_fecha_seleccionada();
                $id_fecha = $fecha['id_fecha'];
            }
            if (isset($datos['id_fecha'])) {
                $id_fecha = $datos['id_fecha'];
            }
            $result = toba::consulta_php('co_anios_academicos')->solapa_fecha_responsable_academica($ra, $datos['fecha_inicio'], $datos['fecha_fin'], $id_fecha);
            if ($result) {
                $responsable = current(toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($ra));                
                $this->set_error('800CAL_anio_academico_superpuesto', array($responsable['nombre']));
            }
        }        
        if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error('<br>'));
		}
	}
	
	function validar_anio_academico($anio)
	{
		$es_numero_entero = $anio == round($anio);
		$esta_en_rango = ($anio >= 0) && ($anio <= 9999);
		if (!$es_numero_entero || !$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('Año Académico','debe ser un entero entre 0 y 9999'));
		}
        if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}	
}
?>