<?php
return [
    'id' => 'Docente',
    'parametros' => [
        'index' => 'inicio_docente'
    ],
    'operaciones' => [
        'acceso' => [
            'activa' => true
        ],
        'configuracion' => [
            'activa' => true
        ],

        'notas_cursada_comision' => [
            'activa' => true,
            'url' => 'cursada',
            'derechos' => [
                'edicion' => [
                    'comision_cargar_notas' => 'comision',
                ],
                'guardar' => [
                    'comision_cargar_notas' => 'comision',
                ],
            ]
        ],

        'cierre_cursadas' => [
            'activa' => true,
            'derechos' => [
                'cerrar_acta' => [
                    'comision_acta_cerrar' => 'comision',
                ],
                'cerrar_acta_libro' => [
                    'comision_acta_cerrar' => 'comision',
                ],
                'imprimir_comprobante' => [
                    'comision_acta_imprimir' => 'comision',
                ],
            ]
        ],


        'zona_comisiones' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
            ]
        ],
        'zona_clases' => [
	        'activa' => true,
	        'menu' => [
		        'visible' => true
	        ]
        ],
        'zona_examenes' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
            ]
        ],

        'asistencias' => [
            'activa' => true,
			'derechos' => [
                'index' => [
                    'asistencia_editar' => 'comision',
                ]
            ]
        ],
        
        'asistencias_planilla' => [
            'activa' => true
        ],
        
        'temas_dictados' => [
            'activa' => true
        ],
        // REPORTES
        'agenda_examenes' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'reportes'
            ]
        ],

        'ficha_alumno' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'reportes'
            ]
        ],

        'inscriptos_cursadas' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'texto' => 'inscriptos_cursada',
                'submenu' => 'reportes'
            ]
        ],

        'inscriptos_examenes' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'reportes'
            ]
        ],

        'inscriptos_rechazados' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'texto' => 'inscriptos_rechazados_cursada',
                'submenu' => 'reportes'
            ]
        ],

        'oferta_comisiones' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'reportes'
            ]
        ],


        'reporte_actas' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'reportes'
            ]
        ],

        'resumen_cursada' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'reportes'
            ]
        ],
		
		'encuestas_kolla' => [
			'activa' => true,
			'menu' => [
				'visible' => true,
				'submenu' => 'encuestas'
			]
		],
		
		'encuestas_respondidas' => [
            'activa' => true,
            'menu' => [
				'visible' => true,
                'submenu' => 'encuestas'
            ]
        ],
		
		'encuestas_resultados' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'encuestas'
            ]
        ],

        'encuestas_resultados_docente' => [
            'activa' => true,
            'menu' => [
                'visible' => true,
                'submenu' => 'encuestas'
            ]
        ],
		
		// FIN REPORTES

        'inscriptos_examen' => [
            'activa' => true,
        ],

        'notas_mesa_examen' => [
            'activa' => true,
            'derechos' => [
                'edicion' => [
                    'mesa_cargar_notas' => 'llamado_mesa'
                ],
                'guardar' => [
                    'mesa_cargar_notas' => 'llamado_mesa'
                ]
            ],
        ],

        'impresion_actas_examen' => [
            'activa' => true,
			'url' => 'actas_examen',
            'derechos' => [
                'imprimir_acta' => [
                    'acta_examen_imprimir' => 'llamado_mesa'
                ],
                'cerrar_acta' => [
                    'acta_examen_cerrar' => 'llamado_mesa'
                ]
            ]
        ],

        'mensajes' => [
            'activa' => true,
        ],

        'admin_actas_examen_fc' => [
            'activa' => true,
            'derechos' => [
                'imprimir_acta' => [
                    'acta_examen_imprimir' => 'llamado_mesa'
                ],
                'editar' => [
                    'acta_examen_editar' => 'llamado_mesa'
                ],
                'modificar_datos_acta' => [
                    'acta_examen_editar' => 'llamado_mesa'
                ],
            ]
        ],


        'evaluaciones' => [
            'activa' => true,
            'derechos' => [
                'listar_notas' => [
                    'evaluacion_consultar' => 'comision',
                ],
                'listar_notas_pdf' => [
                    'evaluacion_imprimir' => 'comision',
                ],
                'listar_notas_xls' => [
                    'evaluacion_imprimir' => 'comision',
                ],
                'borrar_evaluacion' => [
                    'evaluacion_anular' => 'comision',
                ],
                'nueva_evaluacion' => [
                    'evaluacion_crear' => 'comision',
                ],
                'crear_evaluacion' => [
                    'evaluacion_crear' => 'comision',
                ],
                'editar_evaluacion' => [
                    'evaluacion_editar' => 'comision',
                ],
                'actualizar_evaluacion' => [
                    'evaluacion_editar' => 'comision',
                ],
                'editar_notas' => [
                    'evaluacion_editar' => 'comision',
                ],
            ]
        ],

        'admin_derechos_comision' => [
            'activa' => true,
            'url' => 'derechos_comision',
            'derechos' => [
                'edicion' => [
                    'otorgar_derechos_comision' => 'comision'
                ],
                'guardar' => [
                    'otorgar_derechos_comision' => 'comision'
                ],
                'info_docentes' => [
                    'otorgar_derechos_comision' => 'comision'
                ],
            ],
            'menu' => [
                'visible' => true,
                'submenu' => 'derechos'
            ]
        ],

        'admin_derechos_mesa' => [
            'activa' => true,
            'url' => 'derechos_mesa',
            'derechos' => [
                'edicion' => [
                    'otorgar_derechos_mesa_examen' => 'llamado_mesa',
                ],
                'guardar' => [
                    'otorgar_derechos_mesa_examen' => 'llamado_mesa',
                ],
                'info_docentes' => [
                    'otorgar_derechos_mesa_examen' => 'llamado_mesa',
                ],
            ],
            'menu' => [
                'visible' => true,
                'submenu' => 'derechos'
            ]
        ],
        
        'notificador' => [ 'activa' => true, ],
		
		'administrar_cursos_virtuales' => [
            'activa' => true,
			'url' => 'moodle',
            'derechos' => [
                'index' => [
                    'comision_admin_cur_virtuales' => 'comision',
                ],
            ]
        ],
        'inicio_docente' => [ 'activa' => true, ],
        
    ]
];
