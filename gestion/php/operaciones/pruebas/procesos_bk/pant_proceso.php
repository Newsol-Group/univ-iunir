<?php
class pant_proceso extends guarani_pers_ei_pantalla
{
	function extender_objeto_js()
	{
		echo "
			{$this->objeto_js}.set_progreso = function(count)
			{
				var image = 360;
				var width = -1 * ((image/200)*(100-count));
				var img = document.getElementById('barra_progreso');
				img.style.backgroundPosition = + width + 'px 0pt';
				img.alt   = count + '%';
				img.title = count + '%';

				var txt = document.getElementById('texto_progreso');
				txt.innerHTML = count + '%';
			}

			var finalizo_proceso = false;

			{$this->objeto_js}.consultar_estado_proceso = function()
			{
				this.ajax('estado_proceso',[], this, this.set_estado_proceso);

				if (!finalizo_proceso) {
					setTimeout(\"{$this->objeto_js}.consultar_estado_proceso()\",100);
				} else {
					this.ajax('resultados_proceso',[], this, this.set_resultados_proceso);
				}
			}

			{$this->objeto_js}.set_estado_proceso = function(datos)
			{
				if (datos) {
					if (datos['progreso'] == 100) {
						finalizo_proceso = true;
						this.set_progreso(100);
					}
					if (datos['mensaje'] != false) {
						//-- Eliminar el estado actual

						
						//-- Se crea el log de mensajes.
						var txt;

						if (typeof eUL == 'undefined')
							eUL = document.createElement('ul');
						
						for(i=0; i < datos['mensajes'].length; i++)
						{
							txt = '[' + datos['mensajes'][i]['TIEMPO'] + '] [' + datos['mensajes'][i]['NIVEL'] + ']: ' + datos['mensajes'][i]['MENSAJE'];
							eLI = document.createElement('li');
							eLI.appendChild(document.createTextNode(txt));
							eUL.appendChild(eLI);
						}

						document.getElementById('mensajes_proceso').appendChild(eUL);

						//-- Actualizo el porcentaje completado
						this.set_progreso(parseInt(datos['progreso'], 10));
					}

					if (finalizo_proceso && datos['progreso'] == 100) {
						{$this->objeto_js}.activar_boton('reporte1');
						{$this->objeto_js}.activar_boton('reporte2');
					}
				}
			}
						
			{$this->objeto_js}.set_resultados_proceso = function(datos)
			{
			
				//Configuro los efs
				this.dep('resultados').ef('procesados').set_estado(datos['cantidad_procesados']);
				this.dep('resultados').ef('com_copiadas').set_estado(datos['comisiones_copiadas']);
				this.dep('resultados').ef('com_sin_copia').set_estado(datos['comisiones_no_copiadas']);
				this.dep('resultados').ef('errores').set_estado(datos['errores']);
			}

			
			{$this->objeto_js}.set_progreso(0);
			{$this->objeto_js}.consultar_estado_proceso();
			{$this->objeto_js}.desactivar_boton('reporte1');
			{$this->objeto_js}.desactivar_boton('reporte2');
		";
			
	}

	function generar_layout()
	{
		$barra = toba_recurso::imagen_proyecto('porcentaje-imagen.png');

		echo "
			<div id='detalle_proceso'>
				<p>Se inicia el proceso... en instantes comenzará a ver el estado actual.<p>
				<div>
					<div style='float:left;'>
						<img id='barra_progreso' src='$barra' alt='0%' class='barra-progreso' />
					</div>
					<div id='texto_progreso' style='margin:5px 5px; float:left;'>
						30%
					</div>
					<div style='clear:both;'></div>
				</div>
				<p>Proceso iniciado...</p>
				<div id='mensajes_proceso' class='etiqueta'>
				</div>
			</div>
		";
		$this->dep('resultados')->generar_html();
	}

}

?>