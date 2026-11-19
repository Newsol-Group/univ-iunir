<?php 

class ci_edi_plan_certificados extends ci_edi_plan_condiciones
{

	protected $s__filtro;
	protected $s__datos;
	protected $s__seleccion;

	//------ Filtro ----------------------

	function evt__filtro_certificados__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro_certificados')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}

	function evt__filtro_certificados__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = "";
	}

	function conf__filtro_certificados(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}

	//-------------------------------------
	

	function conf__plan_certificados(toba_ei_cuadro $cuadro) 
	{
		$certificados = $this->cn()->get_certificados_plan_version();
		$cuadro->set_datos($certificados);
		$etiqueta = guarani_etiquetas::get_tabla('sga_planes_certificados')->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array($etiqueta)));
	}	

	function evt__plan_certificados__seleccion($seleccion)
	{
		$this->cn()->seleccionar_certificado($seleccion['certificado'], $seleccion['entidad']);
		$this->set_pantalla('edicion');
	}
	
	function conf__certificados(toba_ei_cuadro $cuadro) 
	{
		$certificados = $this->cn()->get_certificados_disponibles($this->s__filtro);
		$cuadro->set_datos($certificados);
	}	
	
	function evt__certificados__seleccion($seleccion)
	{
		$this->cn()->asociar_certificado($seleccion['certificado'], $seleccion['entidad']);
		$this->set_pantalla('edicion');
	}
	
	
	function evt__cancelar()
	{
		$this->evt__filtro_certificados__cancelar();
		$this->dep('certificados_plan')->evt__cancelar();
		parent::evt__cancelar();
	}
	
	function evt__guardar()
	{
		try {
			$this->cn()->guardar();
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'),'info');
		} catch (guarani_error $e) {
			$titulo_ventana = $e->get_titulo_ventana();
			if (isset($titulo_ventana)) {
				toba::notificacion()->set_titulo($this->get_mensaje('guardar_error') . "<br>". $titulo_ventana);
			} else {
				toba::notificacion()->agregar($e->get_mensaje());
			}
			toba::logger()->error($e->getMessage());
		} catch (toba_error $e) {
			toba::notificacion()->agregar($this->get_mensaje('guardar_error'),'error');
		}
	}
	
}

?>