<?php

class test_abm_actividad_materia extends test_guarani_abm
{
	protected static $descripcion = "ABM de Actividad de tipo MATERIA";
	protected $manejador = 'manejador_materias';
	protected $api_entidad = array(	'datos_elemento',
									'datos_atrib');
	protected $columna_clave_db = 'elemento';
	protected $datos_alta = array(	
		'datos_elemento' => array(
								'codigo' => '1005',
								'nombre' => 'Materia incorporada a travez de un caso de TEST',
								'nombre_abreviado' => 'Materia de Prueba',
								'entidad_subtipo' => 50, //  TODO poner constantes
								'estado' => 'A',
								'entidad' => NULL
							),
		'datos_atrib' => array(
								'aprobacion_x_resolucion' => 'S',
								'cantidad_de_aplazos' => NULL,
								'creditos' => NULL,
								'creditos_max' => NULL,
								'creditos_min' => NULL,
								'coeficiente' => NULL,
								'escala_nota_cursada' => '1',
								'escala_nota_equivalencia' => '3',
								'escala_nota_examen' => '2',
								'horas_semanales' => NULL,
								'horas_totales' => NULL,
								'permite_promocion' => 'S',
								'permite_rendir_libre' => 'S',
								'promediable' => 'S',
								'requiere_cursada' => 'S',
								'requiere_examen' => 'S',
								'sale_listado' => 'S',
								'vigencia' => '1',
								'nivel' => 'B',
								'contenido' => 'Bla Bla bla',
								'disciplina' => '1',
							)
						);
	protected $datos_modificacion = array(	
		'datos_elemento' => array (
								'nombre' => 'Materia de TEST',
								'nombre_abreviado' => 'Materia de Prueba: usada en casos de test',
							),
		'datos_atrib' => array (
								'requiere_cursada' => 'N',
								'requiere_examen' => 'N',
								'contenido' => 'Bli Bli bli',
							)
						);
}

?>