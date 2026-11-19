<?php
class form extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__regla__procesar = function(es_inicial)
		{
			//Oculto los parámetros de contexto
			this.ef('fecha').ocultar();
			this.ef('alumno').ocultar();
			this.ef('modulo').ocultar();
			this.ef('persona').ocultar();
			this.ef('actividad').ocultar();
			this.ef('elemento').ocultar();
			this.ef('plan_version').ocultar();
			this.ef('anio_academico').ocultar();
			this.ef('comision').ocultar();
			this.ef('subcomision').ocultar();
			this.ef('llamado_mesa').ocultar();
			this.ef('valor_parametro').ocultar();
			this.ef('propuesta').ocultar();
			this.ef('requisito').ocultar();
			this.ef('id_acta').ocultar();
			this.ef('tipo_acta').ocultar();
			this.ef('resultado').ocultar();
			this.ef('id_acta_original').ocultar();
			this.ef('comision_origen').ocultar();
			this.ef('entidad').ocultar();
			
			//Oculto los parámetros de la regla
			this.ef('puntaje').ocultar();
			this.ef('actividades').ocultar();
			this.ef('creditos').ocultar();
			this.ef('porcentaje').ocultar();
			this.ef('cantidad').ocultar();
			this.ef('anio').ocultar();
			this.ef('cantidad_regulares').ocultar();
			this.ef('cantidad_aprobadas').ocultar();
			this.ef('porcentaje_regulares').ocultar();
			this.ef('porcentaje_aprobadas').ocultar();
			this.ef('fecha_desde').ocultar();
			this.ef('fecha_hasta').ocultar();
			this.ef('anio_desde').ocultar();
			this.ef('anio_hasta').ocultar();
			this.ef('horas').ocultar();
			
			//Quito la obligatoriedad de los parámetros de contexto
			this.ef('fecha').set_obligatorio(false);
			this.ef('alumno').set_obligatorio(false);
			this.ef('modulo').set_obligatorio(false);
			this.ef('persona').set_obligatorio(false);
			this.ef('actividad').set_obligatorio(false);
			this.ef('elemento').set_obligatorio(false);
			this.ef('plan_version').set_obligatorio(false);
			this.ef('anio_academico').set_obligatorio(false);
			this.ef('comision').set_obligatorio(false);
			this.ef('subcomision').set_obligatorio(false);
			this.ef('llamado_mesa').set_obligatorio(false);
			this.ef('propuesta').set_obligatorio(false);
			this.ef('requisito').set_obligatorio(false);
			this.ef('id_acta').set_obligatorio(false);
			this.ef('tipo_acta').set_obligatorio(false);
			this.ef('resultado').set_obligatorio(false);
			this.ef('id_acta_original').set_obligatorio(false);
			this.ef('comision_origen').set_obligatorio(false);
			this.ef('entidad').set_obligatorio(false);
			
			//Quito la obligatoriedad de los parámetros de la regla
			this.ef('puntaje').set_obligatorio(false);
			this.ef('actividades').set_obligatorio(false);
			this.ef('creditos').set_obligatorio(false);
			this.ef('porcentaje').set_obligatorio(false);
			this.ef('cantidad').set_obligatorio(false);
			this.ef('anio').set_obligatorio(false);
			this.ef('cantidad_regulares').set_obligatorio(false);
			this.ef('cantidad_aprobadas').set_obligatorio(false);
			this.ef('porcentaje_regulares').set_obligatorio(false);
			this.ef('porcentaje_aprobadas').set_obligatorio(false);
			this.ef('valor_parametro').set_obligatorio(false);
			this.ef('fecha_desde').set_obligatorio(false);
			this.ef('fecha_hasta').set_obligatorio(false);
			this.ef('anio_desde').set_obligatorio(false);
			this.ef('anio_hasta').set_obligatorio(false);
			this.ef('horas').set_obligatorio(false);
			
			if (this.ef('regla').tiene_estado()) {
				var regla = this.ef('regla').get_estado();
				var parametros = [];
				parametros['regla'] = regla;
				
				this.controlador.ajax('get_parametros', parametros, this, this.set_campos);
				return false;
			}
		}
		
		{$this->objeto_js}.set_campos = function(datos)
		{
			for (id_dato in datos) {
				parametro = datos[id_dato]['parametro'];
				this.ef(parametro).mostrar();
				this.ef(parametro).set_obligatorio(true);
			}
		}
		";
	}

}
?>