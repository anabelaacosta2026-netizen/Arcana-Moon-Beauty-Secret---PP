<?php
/**
 * Script de generación del dump SQL y de la base de datos completa para Arcana
 */

$passDefault = 'Arcana2026*';
$hashAdmin = password_hash('Admin1234*', PASSWORD_DEFAULT);
$hashSuperAdmin = password_hash('Anabela2026*', PASSWORD_DEFAULT);
$hashUser = password_hash('Cliente2026*', PASSWORD_DEFAULT);

// Categorías
$categorias = [
    1 => 'Manicura',
    2 => 'Uñas',
    3 => 'Pestañas',
    4 => 'Faciales',
    5 => 'Masajes',
    6 => 'Pedicura',
    7 => 'Podología y Reflexología'
];

// Roles
$roles = [
    1 => 'Super Admin',
    2 => 'Administrador',
    3 => 'Cliente'
];

// Espacios
$espacios = [
    1 => ['nombre' => 'Puesto Manicura 1', 'tipo' => 'Manicura', 'estado' => 'disponible'],
    2 => ['nombre' => 'Puesto Manicura & Uñas 2', 'tipo' => 'Uñas', 'estado' => 'disponible'],
    3 => ['nombre' => 'Cabina Pestañas & Cejas', 'tipo' => 'Pestañas', 'estado' => 'disponible'],
    4 => ['nombre' => 'Cabina Facial & Estética', 'tipo' => 'Facial', 'estado' => 'disponible'],
    5 => ['nombre' => 'Cabina Masajes & Relax', 'tipo' => 'Masajes', 'estado' => 'disponible'],
    6 => ['nombre' => 'Gabinete Podología & Pedicura', 'tipo' => 'Podología', 'estado' => 'disponible']
];

// Especialidades
$especialidades = [
    1 => 'Manicurista Profesional & Soft Gel',
    2 => 'Lashista & Especialista en Miradas',
    3 => 'Cosmetóloga & Cosmiatra Facial',
    4 => 'Masoterapeuta & Terapeuta Holística',
    5 => 'Podóloga Universitaria & Reflexóloga'
];

// Empleadas (5)
$empleadas = [
    1 => [
        'nombre' => 'Camila',
        'apellido' => 'Benítez',
        'email' => 'camila.benitez@arcana.com',
        'telefono' => '+54 387 455-1234',
        'imagenEmpleado' => 'assets/images/team-avatar.jpg',
        'modalidad' => 'Presencial',
        'activo' => 1,
        'especialidades' => [1]
    ],
    2 => [
        'nombre' => 'Luciana',
        'apellido' => 'Morales',
        'email' => 'luciana.morales@arcana.com',
        'telefono' => '+54 387 466-2345',
        'imagenEmpleado' => 'assets/images/team-avatar.jpg',
        'modalidad' => 'Mixto',
        'activo' => 1,
        'especialidades' => [2]
    ],
    3 => [
        'nombre' => 'Valeria',
        'apellido' => 'Ramos',
        'email' => 'valeria.ramos@arcana.com',
        'telefono' => '+54 387 477-3456',
        'imagenEmpleado' => 'assets/images/team-avatar.jpg',
        'modalidad' => 'Presencial',
        'activo' => 1,
        'especialidades' => [3]
    ],
    4 => [
        'nombre' => 'Sofía',
        'apellido' => 'Herrera',
        'email' => 'sofia.herrera@arcana.com',
        'telefono' => '+54 387 488-4567',
        'imagenEmpleado' => 'assets/images/team-avatar.jpg',
        'modalidad' => 'Domicilio',
        'activo' => 1,
        'especialidades' => [4]
    ],
    5 => [
        'nombre' => 'Mariana',
        'apellido' => 'Farfán',
        'email' => 'mariana.farfan@arcana.com',
        'telefono' => '+54 387 499-5678',
        'imagenEmpleado' => 'assets/images/team-avatar.jpg',
        'modalidad' => 'Presencial',
        'activo' => 1,
        'especialidades' => [5]
    ]
];

// Servicios (15)
$servicios = [
    1 => [
        'nombre_servicio' => 'Manicura Rusa Combinada',
        'descripcion' => 'Técnica perfeccionista con torno y micro fresas de diamante para una limpieza profunda de cutículas y esmaltado semipermanente bajo cutícula de máxima durabilidad.',
        'categoria' => 'Manicura',
        'id_categoria' => 1,
        'duracion_minutos' => 60,
        'precio' => 18500.00,
        'imagen_servicio' => 'assets/images/service-manicure.jpg',
        'cuidados_previos' => "Asistir con uñas limpias sin esmalte tradicional.\nNo cortar cutículas en seco 48 hs antes del turno.\nInformar si tienes uñas frágiles o alergias a polímeros.",
        'cuidados_posteriores' => "Aplicar aceite de cutículas todas las noches antes de dormir.\nUsar guantes para tareas domésticas o contacto con lavandina.\nNo raspar ni arrancar el semipermanente con herramientas metálicas."
    ],
    2 => [
        'nombre_servicio' => 'Kapping Gel Fortalecedor',
        'descripcion' => 'Fina capa de gel sobre la uña natural que aporta estructura, resistencia y protección contra quiebres sin añadir extensiones pesadas.',
        'categoria' => 'Manicura',
        'id_categoria' => 1,
        'duracion_minutos' => 75,
        'precio' => 22000.00,
        'imagen_servicio' => 'assets/images/service-manicure.jpg',
        'cuidados_previos' => "Evitar aplicar aceites o cremas pesadas en manos 2 horas antes.\nNo limar las uñas en exceso previamente.",
        'cuidados_posteriores' => "Realizar service de mantenimiento cada 21 días.\nNo limar el borde libre de la uña para no romper el sellado."
    ],
    3 => [
        'nombre_servicio' => 'Soft Gel & Esculpidas Tips',
        'descripcion' => 'Sistema express de extensiones completas de gel curadas con luz LED, flexibles, ultralivianas y con un acabado impecable.',
        'categoria' => 'Uñas',
        'id_categoria' => 2,
        'duracion_minutos' => 90,
        'precio' => 28000.00,
        'imagen_servicio' => 'assets/images/service-nailart.jpg',
        'cuidados_previos' => "Tener uñas naturales libres de hongos o lesiones activas.\nNo intentar retirar esculpidas anteriores arrancándolas.",
        'cuidados_posteriores' => "No utilizar las uñas como herramientas para abrir latas o paquetes.\nEvitar el contacto directo y prolongado con acetona pura."
    ],
    4 => [
        'nombre_servicio' => 'Nail Art Avanzado & Mano Alzada',
        'descripcion' => 'Diseños artísticos personalizados, cristalería Swarovski, encapsulados, efectos cromados y degradés franceses de alta precisión.',
        'categoria' => 'Uñas',
        'id_categoria' => 2,
        'duracion_minutos' => 90,
        'precio' => 26500.00,
        'imagen_servicio' => 'assets/images/service-nailart.jpg',
        'cuidados_previos' => "Traer imágenes de referencia del estilo o paleta de colores deseada.\nLlegar con tiempo para acordar el diseño con la profesional.",
        'cuidados_posteriores' => "Aplicar una capa de top coat protector si notas pérdida de brillo.\nEvitar golpes secos contra superficies duras."
    ],
    5 => [
        'nombre_servicio' => 'Extensiones Poligel Premium',
        'descripcion' => 'Fórmula híbrida acrílico-gel que brinda resistencia inigualable con flexibilidad natural, ideal para largos extremos.',
        'categoria' => 'Uñas',
        'id_categoria' => 2,
        'duracion_minutos' => 105,
        'precio' => 31000.00,
        'imagen_servicio' => 'assets/images/service-nailart.jpg',
        'cuidados_previos' => "No morderse las uñas ni colocar productos con formol 72 hs previas.\nAsistir con manos higienizadas.",
        'cuidados_posteriores' => "No forzar la uña en caso de enganche; comunicarse al centro.\nHidratar la piel circundante con sérum nutritivo."
    ],
    6 => [
        'nombre_servicio' => 'Lifting de Pestañas & Keratina',
        'descripcion' => 'Curvatura natural desde la raíz con nutrición profunda de queratina vegetal y tinte negro intenso que realza y abre la mirada.',
        'categoria' => 'Pestañas',
        'id_categoria' => 3,
        'duracion_minutos' => 60,
        'precio' => 20000.00,
        'imagen_servicio' => 'assets/images/service-eyelashes.jpg',
        'cuidados_previos' => "Asistir sin máscara de pestañas ni restos de delineador.\nRetirar lentes de contacto antes de iniciar la sesión.",
        'cuidados_posteriores' => "No mojar los ojos ni tomar baños de vapor durante las primeras 24 horas.\nCepillar las pestañas suavemente cada mañana."
    ],
    7 => [
        'nombre_servicio' => 'Extensiones Pestañas Pelo a Pelo',
        'descripcion' => 'Colocación de fibra sintética ultraligera sobre cada pestaña natural, logrando un efecto rímel natural y elegante.',
        'categoria' => 'Pestañas',
        'id_categoria' => 3,
        'duracion_minutos' => 90,
        'precio' => 27000.00,
        'imagen_servicio' => 'assets/images/service-eyelashes.jpg',
        'cuidados_previos' => "Ojos 100% desmaquillados y limpios de cualquier base oleosa.\nEvitar cafeína justo antes del turno para mantener párpados quietos.",
        'cuidados_posteriores' => "No utilizar desmaquillantes con base de aceite o bifásicos.\nDormir preferentemente boca arriba para no aplastar las fibras."
    ],
    8 => [
        'nombre_servicio' => 'Volumen Ruso & Efecto Foxy',
        'descripcion' => 'Abanicos artesanales de 3 a 6 fibras ultrafinas para máxima densidad, volumen seductor y mirada rasgada.',
        'categoria' => 'Pestañas',
        'id_categoria' => 3,
        'duracion_minutos' => 110,
        'precio' => 33500.00,
        'imagen_servicio' => 'assets/images/service-eyelashes.jpg',
        'cuidados_previos' => "No rizar las pestañas mecánicamente el día del turno.\nDisponer de tiempo suficiente para la colocación minuciosa.",
        'cuidados_posteriores' => "Lavar con espuma lash shampoo libre de sulfatos a partir de 48 hs.\nAgendar mantenimiento preventivo a los 15-20 días."
    ],
    9 => [
        'nombre_servicio' => 'Limpieza Facial con Punta de Diamante',
        'descripcion' => 'Microdermoabrasión que remueve células muertas, desobstruye poros, extrae impurezas e hidrata con máscara descongestiva.',
        'categoria' => 'Faciales',
        'id_categoria' => 4,
        'duracion_minutos' => 60,
        'precio' => 24000.00,
        'imagen_servicio' => 'assets/images/service-facial.jpg',
        'cuidados_previos' => "No realizar exfoliaciones físicas ni químicas 4 días antes.\nNo depilarse el rostro el mismo día de la sesión.",
        'cuidados_posteriores' => "Aplicar protector solar FPS 50+ cada 3 horas religiosamente.\nNo usar maquillaje pesado en el rostro durante las primeras 12 horas."
    ],
    10 => [
        'nombre_servicio' => 'Dermaplaning & Glow Therapy',
        'descripcion' => 'Exfoliación con bisturí quirúrgico que retira vello facial fino y estrato córneo, dejando la piel luminosa, ultra suave y receptiva a sérums.',
        'categoria' => 'Faciales',
        'id_categoria' => 4,
        'duracion_minutos' => 75,
        'precio' => 29000.00,
        'imagen_servicio' => 'assets/images/service-facial.jpg',
        'cuidados_previos' => "Suspender el uso de ácidos glicólicos o retinoides 4 días antes.\nNo exponerse a sol directo intenso las 48 hs previas.",
        'cuidados_posteriores' => "Evitar actividad física intensa y sudoración por 24 horas.\nHidratar con ácido hialurónico puro y emulsiones regeneradoras."
    ],
    11 => [
        'nombre_servicio' => 'Peeling Renovador Anti-Age',
        'descripcion' => 'Tratamiento con cóctel de alfahidroxiácidos y antioxidantes para atenuar manchas, líneas finas y unificar el tono cutáneo.',
        'categoria' => 'Faciales',
        'id_categoria' => 4,
        'duracion_minutos' => 50,
        'precio' => 26000.00,
        'imagen_servicio' => 'assets/images/service-facial.jpg',
        'cuidados_previos' => "Informar si tienes piel atópica, rosácea activa o estás embarazada.\nVenir con la piel limpia sin bases cosméticas.",
        'cuidados_posteriores' => "No retirar las pielecillas descamadas con las manos.\nUso riguroso de protector solar y bruma termal calmante."
    ],
    12 => [
        'nombre_servicio' => 'Masaje Descontracturante & Cervical',
        'descripcion' => 'Terapia manual profunda focalizada en espalda, cuello y hombros para disolver nudos musculares y aliviar tensiones crónicas.',
        'categoria' => 'Masajes',
        'id_categoria' => 5,
        'duracion_minutos' => 50,
        'precio' => 23000.00,
        'imagen_servicio' => 'assets/images/service-massage.jpg',
        'cuidados_previos' => "Evitar comidas copiosas o pesadas 1 hora antes de la sesión.\nInformar sobre cirugías recientes, hernias o contracturas agudas.",
        'cuidados_posteriores' => "Beber abundante agua durante las horas posteriores para drenar toxinas.\nEvitar entrenamientos de fuerza de alta exigencia el resto del día."
    ],
    13 => [
        'nombre_servicio' => 'Masaje con Piedras Calientes & Aromaterapia',
        'descripcion' => 'Ritual relajante con piedras volcánicas basálticas a temperatura ideal y aceites esenciales naturales que armonizan cuerpo y mente.',
        'categoria' => 'Masajes',
        'id_categoria' => 5,
        'duracion_minutos' => 60,
        'precio' => 28500.00,
        'imagen_servicio' => 'assets/images/service-massage.jpg',
        'cuidados_previos' => "Tomar una ducha tibia antes del turno para relajar los tejidos.\nAsistir con prendas holgadas y confortables.",
        'cuidados_posteriores' => "Mantener el cuerpo abrigado al retirarse del salón.\nConsumir una infusión relajante (manzanilla, tilo o lavanda)."
    ],
    14 => [
        'nombre_servicio' => 'Pedicura Spa & Belleza de Pies',
        'descripcion' => 'Baño de sales aromáticas, remoción de asperezas, exfoliación con microgránulos, masaje relajante y esmaltado semipermanente en pies.',
        'categoria' => 'Pedicura',
        'id_categoria' => 6,
        'duracion_minutos' => 60,
        'precio' => 21500.00,
        'imagen_servicio' => 'assets/images/service-pedicure.jpg',
        'cuidados_previos' => "No cortar las esquinas de las uñas al ras para evitar encarnamientos.\nAsistir con calzado cómodo.",
        'cuidados_posteriores' => "Hidratar los talones a diario con crema rica en urea.\nSecar minuciosamente los espacios interdigitales tras la ducha."
    ],
    15 => [
        'nombre_servicio' => 'Podología Integral & Reflexología Podal',
        'descripcion' => 'Atención clínica y terapéutica de uñas encarnadas, hiperqueratosis, callosidades y masaje reflexológico en puntos reflejos plantares.',
        'categoria' => 'Podología y Reflexología',
        'id_categoria' => 7,
        'duracion_minutos' => 70,
        'precio' => 27000.00,
        'imagen_servicio' => 'assets/images/service-podiatry.jpg',
        'cuidados_previos' => "No aplicar ácidos ni callicidas comerciales antes de la consulta.\nTraer calzado de uso habitual para evaluar la pisada.",
        'cuidados_posteriores' => "Utilizar calzado amplio y transpirable según las indicaciones.\nRealizar baños de inmersión tibios si fueron prescriptos."
    ]
];

// Relación servicio_empleado
// Emp 1: Camila (Servicios 1, 2, 3, 4, 5) - Manicura & Uñas
// Emp 2: Luciana (Servicios 6, 7, 8) - Pestañas (Mixto)
// Emp 3: Valeria (Servicios 9, 10, 11) - Faciales
// Emp 4: Sofía (Servicios 12, 13) - Masajes (Domicilio)
// Emp 5: Mariana (Servicios 14, 15) - Pedicura & Podología
$serviciosEmpleadas = [
    1 => ['id_servicio' => 1, 'id_empleado' => 1, 'modalidad' => 'Presencial', 'max' => 6],
    2 => ['id_servicio' => 2, 'id_empleado' => 1, 'modalidad' => 'Presencial', 'max' => 5],
    3 => ['id_servicio' => 3, 'id_empleado' => 1, 'modalidad' => 'Presencial', 'max' => 4],
    4 => ['id_servicio' => 4, 'id_empleado' => 1, 'modalidad' => 'Presencial', 'max' => 4],
    5 => ['id_servicio' => 5, 'id_empleado' => 1, 'modalidad' => 'Presencial', 'max' => 4],
    6 => ['id_servicio' => 6, 'id_empleado' => 2, 'modalidad' => 'Mixto', 'max' => 6],
    7 => ['id_servicio' => 7, 'id_empleado' => 2, 'modalidad' => 'Mixto', 'max' => 4],
    8 => ['id_servicio' => 8, 'id_empleado' => 2, 'modalidad' => 'Mixto', 'max' => 4],
    9 => ['id_servicio' => 9, 'id_empleado' => 3, 'modalidad' => 'Presencial', 'max' => 5],
    10 => ['id_servicio' => 10, 'id_empleado' => 3, 'modalidad' => 'Presencial', 'max' => 4],
    11 => ['id_servicio' => 11, 'id_empleado' => 3, 'modalidad' => 'Presencial', 'max' => 5],
    12 => ['id_servicio' => 12, 'id_empleado' => 4, 'modalidad' => 'Domicilio', 'max' => 5],
    13 => ['id_servicio' => 13, 'id_empleado' => 4, 'modalidad' => 'Domicilio', 'max' => 4],
    14 => ['id_servicio' => 14, 'id_empleado' => 5, 'modalidad' => 'Presencial', 'max' => 5],
    15 => ['id_servicio' => 15, 'id_empleado' => 5, 'modalidad' => 'Presencial', 'max' => 5]
];

// Usuarios (25)
$usuarios = [
    // 1 Super Admin
    1 => [
        'nombre' => 'Anabela',
        'apellido' => 'Acosta',
        'email' => 'anabela.acosta@arcana.com',
        'pass_plain' => 'Anabela2026*',
        'contrasena' => $hashSuperAdmin,
        'fecha_registro' => '2026-01-10',
        'activo' => 1,
        'telefono' => '+54 387 401-0001',
        'DNI' => '38123456',
        'id_rol' => 1 // Super Admin
    ],
    // 4 Admins
    2 => [
        'nombre' => 'Gonzalo',
        'apellido' => 'Navarro',
        'email' => 'gonzalo.navarro@arcana.com',
        'pass_plain' => 'Admin1234*',
        'contrasena' => $hashAdmin,
        'fecha_registro' => '2026-01-15',
        'activo' => 1,
        'telefono' => '+54 387 402-0002',
        'DNI' => '35789123',
        'id_rol' => 2 // Admin
    ],
    3 => [
        'nombre' => 'Florencia',
        'apellido' => 'Giménez',
        'email' => 'florencia.gimenez@arcana.com',
        'pass_plain' => 'Admin1234*',
        'contrasena' => $hashAdmin,
        'fecha_registro' => '2026-01-20',
        'activo' => 1,
        'telefono' => '+54 387 403-0003',
        'DNI' => '39456789',
        'id_rol' => 2 // Admin
    ],
    4 => [
        'nombre' => 'Mateo',
        'apellido' => 'Rodríguez',
        'email' => 'mateo.rodriguez@arcana.com',
        'pass_plain' => 'Admin1234*',
        'contrasena' => $hashAdmin,
        'fecha_registro' => '2026-02-01',
        'activo' => 1,
        'telefono' => '+54 387 404-0004',
        'DNI' => '36123987',
        'id_rol' => 2 // Admin
    ],
    5 => [
        'nombre' => 'Carla',
        'apellido' => 'Villalba',
        'email' => 'carla.villalba@arcana.com',
        'pass_plain' => 'Admin1234*',
        'contrasena' => $hashAdmin,
        'fecha_registro' => '2026-02-05',
        'activo' => 1,
        'telefono' => '+54 387 405-0005',
        'DNI' => '37987654',
        'id_rol' => 2 // Admin
    ],
    // 20 Clientes
    6 => ['nombre'=>'Julieta','apellido'=>'Romero','email'=>'julieta.romero@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-02-10','activo'=>1,'telefono'=>'+54 387 410-1006','DNI'=>'40112233','id_rol'=>3],
    7 => ['nombre'=>'Martina','apellido'=>'Silva','email'=>'martina.silva@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-02-14','activo'=>1,'telefono'=>'+54 387 411-1007','DNI'=>'41223344','id_rol'=>3],
    8 => ['nombre'=>'Valentina','apellido'=>'Díaz','email'=>'valentina.diaz@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-02-18','activo'=>1,'telefono'=>'+54 387 412-1008','DNI'=>'42334455','id_rol'=>3],
    9 => ['nombre'=>'Paula','apellido'=>'Medina','email'=>'paula.medina@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-02-22','activo'=>1,'telefono'=>'+54 387 413-1009','DNI'=>'43445566','id_rol'=>3],
    10 => ['nombre'=>'Agustina','apellido'=>'Castro','email'=>'agustina.castro@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-02-26','activo'=>1,'telefono'=>'+54 387 414-1010','DNI'=>'44556677','id_rol'=>3],
    11 => ['nombre'=>'Carolina','apellido'=>'Ortiz','email'=>'carolina.ortiz@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-03-01','activo'=>1,'telefono'=>'+54 387 415-1011','DNI'=>'40667788','id_rol'=>3],
    12 => ['nombre'=>'Micaela','apellido'=>'López','email'=>'micaela.lopez@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-03-05','activo'=>1,'telefono'=>'+54 387 416-1012','DNI'=>'41778899','id_rol'=>3],
    13 => ['nombre'=>'Daniela','apellido'=>'Ruiz','email'=>'daniela.ruiz@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-03-10','activo'=>1,'telefono'=>'+54 387 417-1013','DNI'=>'42889900','id_rol'=>3],
    14 => ['nombre'=>'Romina','apellido'=>'Vega','email'=>'romina.vega@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-03-15','activo'=>1,'telefono'=>'+54 387 418-1014','DNI'=>'43990011','id_rol'=>3],
    15 => ['nombre'=>'Belén','apellido'=>'Albornoz','email'=>'belen.albornoz@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-03-20','activo'=>1,'telefono'=>'+54 387 419-1015','DNI'=>'44001122','id_rol'=>3],
    16 => ['nombre'=>'Victoria','apellido'=>'Juárez','email'=>'victoria.juarez@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-04-01','activo'=>1,'telefono'=>'+54 387 420-1016','DNI'=>'40123987','id_rol'=>3],
    17 => ['nombre'=>'Antonella','apellido'=>'Pereyra','email'=>'antonella.pereyra@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-04-05','activo'=>1,'telefono'=>'+54 387 421-1017','DNI'=>'41234876','id_rol'=>3],
    18 => ['nombre'=>'Milagros','apellido'=>'Flores','email'=>'milagros.flores@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-04-10','activo'=>1,'telefono'=>'+54 387 422-1018','DNI'=>'42345765','id_rol'=>3],
    19 => ['nombre'=>'Rocío','apellido'=>'Guzmán','email'=>'rocio.guzman@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-04-15','activo'=>1,'telefono'=>'+54 387 423-1019','DNI'=>'43456654','id_rol'=>3],
    20 => ['nombre'=>'Florencia','apellido'=>'Toledo','email'=>'florencia.toledo@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-04-20','activo'=>1,'telefono'=>'+54 387 424-1020','DNI'=>'44567543','id_rol'=>3],
    21 => ['nombre'=>'Sabrina','apellido'=>'Castillo','email'=>'sabrina.castillo@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-05-02','activo'=>1,'telefono'=>'+54 387 425-1021','DNI'=>'40678432','id_rol'=>3],
    22 => ['nombre'=>'Solana','apellido'=>'Miranda','email'=>'solana.miranda@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-05-10','activo'=>1,'telefono'=>'+54 387 426-1022','DNI'=>'41789321','id_rol'=>3],
    23 => ['nombre'=>'Mariana','apellido'=>'Quiroga','email'=>'mariana.quiroga@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-05-15','activo'=>1,'telefono'=>'+54 387 427-1023','DNI'=>'42890210','id_rol'=>3],
    24 => ['nombre'=>'Lucía','apellido'=>'Córdoba','email'=>'lucia.cordoba@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-05-20','activo'=>1,'telefono'=>'+54 387 428-1024','DNI'=>'43901109','id_rol'=>3],
    25 => ['nombre'=>'Constanza','apellido'=>'Ibáñez','email'=>'constanza.ibanez@gmail.com','pass_plain'=>'Cliente2026*','contrasena'=>$hashUser,'fecha_registro'=>'2026-06-01','activo'=>1,'telefono'=>'+54 387 429-1025','DNI'=>'44012098','id_rol'=>3]
];

// Bloqueo de días (20)
$bloqueos = [
    1 => ['id_usuario' => 1, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => '2026-01-01', 'motivo' => 'Año Nuevo (Feriado Nacional)'],
    2 => ['id_usuario' => 1, 'fecha_desde' => '2026-02-16', 'fecha_hasta' => '2026-02-16', 'motivo' => 'Carnaval Lunes (Feriado Nacional)'],
    3 => ['id_usuario' => 1, 'fecha_desde' => '2026-02-17', 'fecha_hasta' => '2026-02-17', 'motivo' => 'Carnaval Martes (Feriado Nacional)'],
    4 => ['id_usuario' => 1, 'fecha_desde' => '2026-03-23', 'fecha_hasta' => '2026-03-23', 'motivo' => 'Feriado Puente Turístico Nacional'],
    5 => ['id_usuario' => 1, 'fecha_desde' => '2026-03-24', 'fecha_hasta' => '2026-03-24', 'motivo' => 'Día Nacional de la Memoria por la Verdad y la Justicia'],
    6 => ['id_usuario' => 1, 'fecha_desde' => '2026-04-02', 'fecha_hasta' => '2026-04-02', 'motivo' => 'Día del Veterano y de los Caídos en Malvinas'],
    7 => ['id_usuario' => 1, 'fecha_desde' => '2026-04-03', 'fecha_hasta' => '2026-04-03', 'motivo' => 'Viernes Santo (Feriado Nacional)'],
    8 => ['id_usuario' => 1, 'fecha_desde' => '2026-05-01', 'fecha_hasta' => '2026-05-01', 'motivo' => 'Día Internacional del Trabajador'],
    9 => ['id_usuario' => 1, 'fecha_desde' => '2026-05-25', 'fecha_hasta' => '2026-05-25', 'motivo' => 'Día de la Revolución de Mayo'],
    10 => ['id_usuario' => 1, 'fecha_desde' => '2026-06-17', 'fecha_hasta' => '2026-06-17', 'motivo' => 'Paso a la Inmortalidad del Gral. Don Martín Miguel de Güemes (Salta)'],
    11 => ['id_usuario' => 1, 'fecha_desde' => '2026-06-20', 'fecha_hasta' => '2026-06-20', 'motivo' => 'Día de la Bandera - Paso a la Inmortalidad del Gral. Manuel Belgrano'],
    12 => ['id_usuario' => 1, 'fecha_desde' => '2026-07-09', 'fecha_hasta' => '2026-07-09', 'motivo' => 'Día de la Independencia Argentina'],
    13 => ['id_usuario' => 1, 'fecha_desde' => '2026-08-17', 'fecha_hasta' => '2026-08-17', 'motivo' => 'Paso a la Inmortalidad del Gral. José de San Martín'],
    14 => ['id_usuario' => 1, 'fecha_desde' => '2026-09-13', 'fecha_hasta' => '2026-09-13', 'motivo' => 'Fiesta del Milagro de Salta - Triduo y Solemnidad'],
    15 => ['id_usuario' => 1, 'fecha_desde' => '2026-09-14', 'fecha_hasta' => '2026-09-14', 'motivo' => 'Exaltación de la Cruz - Festividad del Milagro'],
    16 => ['id_usuario' => 1, 'fecha_desde' => '2026-09-15', 'fecha_hasta' => '2026-09-15', 'motivo' => 'Solemne Procesión del Señor y Virgen del Milagro (Feriado Provincial)'],
    17 => ['id_usuario' => 1, 'fecha_desde' => '2026-10-12', 'fecha_hasta' => '2026-10-12', 'motivo' => 'Día del Respeto a la Diversidad Cultural'],
    18 => ['id_usuario' => 1, 'fecha_desde' => '2026-11-06', 'fecha_hasta' => '2026-11-06', 'motivo' => 'Cumpleaños de la Administradora (Anabela Acosta) - Salón Cerrado'],
    19 => ['id_usuario' => 1, 'fecha_desde' => '2026-11-23', 'fecha_hasta' => '2026-11-23', 'motivo' => 'Día de la Soberanía Nacional'],
    20 => ['id_usuario' => 1, 'fecha_desde' => '2026-12-08', 'fecha_hasta' => '2026-12-08', 'motivo' => 'Día de la Inmaculada Concepción de María']
];

// Citas / Turnos (35)
// Distribuidos entre usuarios clientes (6 a 25)
$turnos = [
    // Completados en el pasado (con señas y reseñas)
    1  => ['id_usuario' => 6,  'id_espacio' => 1, 'fecha_hora' => '2026-09-02 10:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $5.550 con Mercado Pago MP-8910. Servicio completado con éxito.', 'id_se' => 1],
    2  => ['id_usuario' => 7,  'id_espacio' => 3, 'fecha_hora' => '2026-09-03 11:30:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $6.000 vía transferencia. Lifting perfecto.', 'id_se' => 6],
    3  => ['id_usuario' => 8,  'id_espacio' => 4, 'fecha_hora' => '2026-09-04 15:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $7.200. Limpieza punta diamante realizada sin irritación.', 'id_se' => 9],
    4  => ['id_usuario' => 9,  'id_espacio' => 5, 'fecha_hora' => '2026-09-05 16:30:00', 'estado' => 'completado', 'modalidad' => 'Domicilio',  'observaciones' => 'Seña abonada $6.900. Atención domiciliaria en B° Tres Cerritos.', 'id_se' => 12],
    5  => ['id_usuario' => 10, 'id_espacio' => 6, 'fecha_hora' => '2026-09-08 17:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $6.450 con Ualá Bis. Tratamiento de pedicura spa finalizado.', 'id_se' => 14],
    6  => ['id_usuario' => 11, 'id_espacio' => 2, 'fecha_hora' => '2026-09-10 09:30:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.400. Esculpidas soft gel diseño baby boomer.', 'id_se' => 3],
    7  => ['id_usuario' => 12, 'id_espacio' => 3, 'fecha_hora' => '2026-09-11 14:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.100. Pestañas clásicas curvatura D.', 'id_se' => 7],
    8  => ['id_usuario' => 13, 'id_espacio' => 4, 'fecha_hora' => '2026-09-16 10:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.700. Dermaplaning facial con hidratación profunda.', 'id_se' => 10],
    9  => ['id_usuario' => 14, 'id_espacio' => 5, 'fecha_hora' => '2026-09-17 18:00:00', 'estado' => 'completado', 'modalidad' => 'Domicilio',  'observaciones' => 'Seña abonada $8.550. Masaje con piedras calientes en B° San Lorenzo.', 'id_se' => 13],
    10 => ['id_usuario' => 15, 'id_espacio' => 6, 'fecha_hora' => '2026-09-18 11:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.100. Podología integral y corte terapéutico.', 'id_se' => 15],
    11 => ['id_usuario' => 16, 'id_espacio' => 1, 'fecha_hora' => '2026-09-22 15:30:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $6.600. Kapping gel y esmaltado french.', 'id_se' => 2],
    12 => ['id_usuario' => 17, 'id_espacio' => 3, 'fecha_hora' => '2026-09-23 16:30:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $10.050. Volumen ruso 4D impecable.', 'id_se' => 8],
    13 => ['id_usuario' => 18, 'id_espacio' => 4, 'fecha_hora' => '2026-09-24 09:30:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $7.800. Peeling renovador primera sesión.', 'id_se' => 11],
    14 => ['id_usuario' => 19, 'id_espacio' => 2, 'fecha_hora' => '2026-09-25 11:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $7.950. Nail art flores mano alzada.', 'id_se' => 4],
    15 => ['id_usuario' => 20, 'id_espacio' => 1, 'fecha_hora' => '2026-09-28 17:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $9.300. Poligel con efecto encapsulado.', 'id_se' => 5],

    // Turnos recientes y confirmados (Octubre 2026)
    16 => ['id_usuario' => 21, 'id_espacio' => 1, 'fecha_hora' => '2026-10-01 10:00:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $5.550 vía Mercado Pago.', 'id_se' => 1],
    17 => ['id_usuario' => 22, 'id_espacio' => 4, 'fecha_hora' => '2026-10-02 11:30:00', 'estado' => 'completado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $7.200 con tarjeta de débito.', 'id_se' => 9],
    18 => ['id_usuario' => 23, 'id_espacio' => 5, 'fecha_hora' => '2026-10-03 16:00:00', 'estado' => 'completado', 'modalidad' => 'Domicilio',  'observaciones' => 'Seña abonada $6.900. Turno completado en domicilio.', 'id_se' => 12],
    19 => ['id_usuario' => 24, 'id_espacio' => 3, 'fecha_hora' => '2026-10-05 09:00:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $6.000 MP #987654. Turno confirmado para mañana.', 'id_se' => 6],
    20 => ['id_usuario' => 25, 'id_espacio' => 2, 'fecha_hora' => '2026-10-05 11:00:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.400. Lista para colocación soft gel.', 'id_se' => 3],
    21 => ['id_usuario' => 6,  'id_espacio' => 4, 'fecha_hora' => '2026-10-06 14:00:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.700. Glow therapy confirmada.', 'id_se' => 10],
    22 => ['id_usuario' => 7,  'id_espacio' => 6, 'fecha_hora' => '2026-10-06 16:30:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $8.100 vía transferencia bancaria.', 'id_se' => 15],
    23 => ['id_usuario' => 8,  'id_espacio' => 5, 'fecha_hora' => '2026-10-07 15:00:00', 'estado' => 'confirmado', 'modalidad' => 'Domicilio',  'observaciones' => 'Seña abonada $8.550. Atención a domicilio.', 'id_se' => 13],
    24 => ['id_usuario' => 9,  'id_espacio' => 1, 'fecha_hora' => '2026-10-08 10:00:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $6.600. Service de kapping programado.', 'id_se' => 2],
    25 => ['id_usuario' => 10, 'id_espacio' => 3, 'fecha_hora' => '2026-10-09 16:00:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $10.050. Mantenimiento volumen ruso.', 'id_se' => 8],

    // Turnos Pendientes (Próximos días)
    26 => ['id_usuario' => 11, 'id_espacio' => 1, 'fecha_hora' => '2026-10-13 11:00:00', 'estado' => 'pendiente',  'modalidad' => 'Presencial', 'observaciones' => 'Pendiente de acreditación de seña $5.550.', 'id_se' => 1],
    27 => ['id_usuario' => 12, 'id_espacio' => 4, 'fecha_hora' => '2026-10-14 15:00:00', 'estado' => 'pendiente',  'modalidad' => 'Presencial', 'observaciones' => 'Agendado online. Recordatorio de pago de seña enviado.', 'id_se' => 9],
    28 => ['id_usuario' => 13, 'id_espacio' => 2, 'fecha_hora' => '2026-10-15 17:30:00', 'estado' => 'pendiente',  'modalidad' => 'Presencial', 'observaciones' => 'Pendiente de comprobante de transferencia.', 'id_se' => 4],
    29 => ['id_usuario' => 14, 'id_espacio' => 3, 'fecha_hora' => '2026-10-16 10:30:00', 'estado' => 'pendiente',  'modalidad' => 'Presencial', 'observaciones' => 'Reserva web pendiente de confirmación.', 'id_se' => 7],
    30 => ['id_usuario' => 15, 'id_espacio' => 6, 'fecha_hora' => '2026-10-19 14:00:00', 'estado' => 'pendiente',  'modalidad' => 'Presencial', 'observaciones' => 'Pendiente pago seña podología.', 'id_se' => 14],

    // Turnos Futuros y Cancelados
    31 => ['id_usuario' => 16, 'id_espacio' => 5, 'fecha_hora' => '2026-10-20 16:00:00', 'estado' => 'confirmado', 'modalidad' => 'Domicilio',  'observaciones' => 'Seña abonada $6.900. Masaje descontracturante.', 'id_se' => 12],
    32 => ['id_usuario' => 17, 'id_espacio' => 4, 'fecha_hora' => '2026-10-21 11:30:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $7.800. Peeling anti-age sesión 2.', 'id_se' => 11],
    33 => ['id_usuario' => 18, 'id_espacio' => 1, 'fecha_hora' => '2026-09-12 18:00:00', 'estado' => 'cancelado',  'modalidad' => 'Presencial', 'observaciones' => 'Cancelado por el cliente con 48 hs de anticipación.', 'id_se' => 1],
    34 => ['id_usuario' => 19, 'id_espacio' => 3, 'fecha_hora' => '2026-09-20 15:00:00', 'estado' => 'cancelado',  'modalidad' => 'Presencial', 'observaciones' => 'Cancelado por motivos de fuerza mayor.', 'id_se' => 6],
    35 => ['id_usuario' => 20, 'id_espacio' => 2, 'fecha_hora' => '2026-10-22 17:00:00', 'estado' => 'confirmado', 'modalidad' => 'Presencial', 'observaciones' => 'Seña abonada $9.300 con Mercado Pago.', 'id_se' => 5]
];

// Reseñas de clientes para turnos completados
$resenas = [
    1 => ['id_servicio' => 1,  'id_usuario' => 6,  'id_cita' => 1,  'puntuacion' => 5, 'comentario' => '¡Increíble la manicura rusa! La atención de Camila fue impecable, muy prolija y ya llevo 3 semanas con el esmalte intacto.', 'fecha_publicacion' => '2026-09-03 14:20:00'],
    2 => ['id_servicio' => 6,  'id_usuario' => 7,  'id_cita' => 2,  'puntuacion' => 5, 'comentario' => 'El lifting de pestañas me cambió la mirada por completo. Muy suave el procedimiento y no me ardió nada.', 'fecha_publicacion' => '2026-09-04 18:10:00'],
    3 => ['id_servicio' => 9,  'id_usuario' => 8,  'id_cita' => 3,  'puntuacion' => 5, 'comentario' => 'La limpieza con punta de diamante me dejó la piel súper luminosa y limpia de puntos negros. Súper recomendado.', 'fecha_publicacion' => '2026-09-05 20:30:00'],
    4 => ['id_servicio' => 12, 'id_usuario' => 9,  'id_cita' => 4,  'puntuacion' => 5, 'comentario' => 'Excelente el servicio a domicilio de Sofía. Tenía una contractura fuerte en la espalda y salí renovada.', 'fecha_publicacion' => '2026-09-06 12:00:00'],
    5 => ['id_servicio' => 14, 'id_usuario' => 10, 'id_cita' => 5,  'puntuacion' => 5, 'comentario' => 'La pedicura spa es una maravilla. El masaje con sales aromáticas fue un mimo total.', 'fecha_publicacion' => '2026-09-09 10:15:00'],
    6 => ['id_servicio' => 3,  'id_usuario' => 11, 'id_cita' => 6,  'puntuacion' => 5, 'comentario' => 'El soft gel dura muchísimo y se siente liviano en las manos. Me encantó el color.', 'fecha_publicacion' => '2026-09-11 16:45:00'],
    7 => ['id_servicio' => 7,  'id_usuario' => 12, 'id_cita' => 7,  'puntuacion' => 4, 'comentario' => 'Muy lindas pestañas clásicas, bien naturales como yo quería. Muy puntual la atención.', 'fecha_publicacion' => '2026-09-12 19:00:00'],
    8 => ['id_servicio' => 10, 'id_usuario' => 13, 'id_cita' => 8,  'puntuacion' => 5, 'comentario' => 'El dermaplaning es lo mejor que me hice en el año. La base de maquillaje ahora me queda de revista.', 'fecha_publicacion' => '2026-09-17 11:30:00'],
    9 => ['id_servicio' => 13, 'id_usuario' => 14, 'id_cita' => 9,  'puntuacion' => 5, 'comentario' => 'El masaje con piedras calientes es una experiencia suprema. Hermoso ambiente y relax total.', 'fecha_publicacion' => '2026-09-18 15:40:00'],
    10 => ['id_servicio' => 15, 'id_usuario' => 15, 'id_cita' => 10, 'puntuacion' => 5, 'comentario' => 'Mariana es una profesional excelente. Me solucionó el problema de la uña encarnada sin nada de dolor.', 'fecha_publicacion' => '2026-09-19 13:20:00']
];

// Cuentas de cobro / Mercado Pago activas
$payment_accounts = [
    1 => [
        'id_cuenta' => 1,
        'titular' => 'Anabela Acosta',
        'cuit_cuil' => '27-38123456-4',
        'tipo_cuenta' => 'Mercado Pago',
        'alias' => 'arcana.estetica.mp',
        'cbu_cvu' => '0000003100089123456789',
        'banco' => 'Mercado Pago Wallet',
        'es_activa' => 1,
        'estado' => 'activo'
    ],
    2 => [
        'id_cuenta' => 2,
        'titular' => 'Arcana Beauty Secret S.R.L.',
        'cuit_cuil' => '30-71890123-8',
        'tipo_cuenta' => 'Cuenta Corriente',
        'alias' => 'ARCANA.SALTA.BBVA',
        'cbu_cvu' => '0170001520000012345678',
        'banco' => 'Banco BBVA Argentina',
        'es_activa' => 0,
        'estado' => 'inactivo'
    ]
];

// GENERAR ARCHIVO SQL DUMP
$sqlDump = "-- ========================================================\n";
$sqlDump .= "-- ARCANA BEAUTY SECRET — SQL DUMP EXPORTABLE PARA MYSQL / PHPMYADMIN\n";
$sqlDump .= "-- Base de datos: `arcanaestetica_db`\n";
$sqlDump .= "-- Servidor: 127.0.0.1:3306\n";
$sqlDump .= "-- ========================================================\n\n";

$sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sqlDump .= "START TRANSACTION;\n";
$sqlDump .= "SET time_zone = \"+00:00\";\n\n";

$sqlDump .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
$sqlDump .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
$sqlDump .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
$sqlDump .= "/*!40101 SET NAMES utf8mb4 */;\n\n";

$sqlDump .= "CREATE DATABASE IF NOT EXISTS `arcanaestetica_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sqlDump .= "USE `arcanaestetica_db`;\n\n";

// DROP TABLES IF EXIST
$tablesDrop = [
    'resenas', 'imagen_cita', 'cita_servicio', 'citas', 'servicio_empleado',
    'categoria_servicio', 'precio_servicio', 'servicio', 'empleado_especialidad',
    'especialidad', 'empleado', 'espacio', 'bloqueo_dia', 'payment_accounts',
    'cambios_dashboard', 'usuario', 'roles', 'categoria'
];
foreach ($tablesDrop as $t) {
    $sqlDump .= "DROP TABLE IF EXISTS `$t`;\n";
}
$sqlDump .= "\n-- --------------------------------------------------------\n\n";

// CREATE TABLES
$sqlDump .= "-- Estructura para la tabla `roles`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `roles` (\n  `id_rol` int NOT NULL AUTO_INCREMENT,\n  `nombre_rol` varchar(50) NOT NULL,\n  PRIMARY KEY (`id_rol`),\n  UNIQUE KEY `nombre_rol` (`nombre_rol`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `categoria`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `categoria` (\n  `id_categoria` int NOT NULL AUTO_INCREMENT,\n  `nombre_categoria` varchar(100) NOT NULL,\n  PRIMARY KEY (`id_categoria`),\n  UNIQUE KEY `nombre_categoria` (`nombre_categoria`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `usuario`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `usuario` (\n  `id_usuario` int NOT NULL AUTO_INCREMENT,\n  `nombre` varchar(100) NOT NULL,\n  `apellido` varchar(100) NOT NULL,\n  `email` varchar(150) NOT NULL,\n  `contrasena` varchar(255) NOT NULL,\n  `fecha_registro` date NOT NULL,\n  `activo` tinyint(1) NOT NULL DEFAULT '1',\n  `telefono` varchar(20) DEFAULT NULL,\n  `DNI` varchar(20) NOT NULL,\n  `id_rol` int NOT NULL,\n  PRIMARY KEY (`id_usuario`),\n  UNIQUE KEY `email` (`email`),\n  UNIQUE KEY `DNI` (`DNI`),\n  KEY `fk_usuario_rol` (`id_rol`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `espacio`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `espacio` (\n  `id_espacio` int NOT NULL AUTO_INCREMENT,\n  `nombre` varchar(100) NOT NULL,\n  `tipo` varchar(50) NOT NULL,\n  `estado` varchar(30) NOT NULL,\n  PRIMARY KEY (`id_espacio`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `especialidad`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `especialidad` (\n  `id_especialidad` int NOT NULL AUTO_INCREMENT,\n  `nombre_especialidad` varchar(100) NOT NULL,\n  PRIMARY KEY (`id_especialidad`),\n  UNIQUE KEY `nombre_especialidad` (`nombre_especialidad`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `empleado`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `empleado` (\n  `id_empleado` int NOT NULL AUTO_INCREMENT,\n  `nombre` varchar(100) NOT NULL,\n  `apellido` varchar(100) NOT NULL,\n  `telefono` varchar(20) DEFAULT NULL,\n  `email` varchar(150) NOT NULL,\n  `imagenEmpleado` varchar(255) DEFAULT NULL,\n  `modalidad` varchar(50) DEFAULT 'Presencial',\n  `activo` tinyint(1) NOT NULL DEFAULT '1',\n  PRIMARY KEY (`id_empleado`),\n  UNIQUE KEY `email` (`email`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `empleado_especialidad`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `empleado_especialidad` (\n  `id_empleado_especialidad` int NOT NULL AUTO_INCREMENT,\n  `id_empleado` int NOT NULL,\n  `id_especialidad` int NOT NULL,\n  PRIMARY KEY (`id_empleado_especialidad`),\n  UNIQUE KEY `uq_empleado_especialidad` (`id_empleado`,`id_especialidad`),\n  KEY `fk_empesp_especialidad` (`id_especialidad`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `servicio`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `servicio` (\n  `id_servicio` int NOT NULL AUTO_INCREMENT,\n  `nombre_servicio` varchar(150) NOT NULL,\n  `descripcion` text,\n  `categoria` varchar(100) DEFAULT NULL,\n  `disponibilidad` tinyint(1) NOT NULL DEFAULT '1',\n  `imagen_servicio` varchar(255) DEFAULT NULL,\n  `duracion_minutos` int NOT NULL,\n  `cuidados_previos` text DEFAULT NULL,\n  `cuidados_posteriores` text DEFAULT NULL,\n  PRIMARY KEY (`id_servicio`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `precio_servicio`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `precio_servicio` (\n  `id_precio_servicio` int NOT NULL AUTO_INCREMENT,\n  `id_servicio` int NOT NULL,\n  `precio` decimal(10,2) NOT NULL,\n  `fecha_desde` date NOT NULL,\n  `fecha_hasta` date DEFAULT NULL,\n  PRIMARY KEY (`id_precio_servicio`),\n  KEY `fk_precio_servicio` (`id_servicio`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `categoria_servicio`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `categoria_servicio` (\n  `id_categoria_servicio` int NOT NULL AUTO_INCREMENT,\n  `id_categoria` int NOT NULL,\n  `id_servicio` int NOT NULL,\n  PRIMARY KEY (`id_categoria_servicio`),\n  UNIQUE KEY `uq_categoria_servicio` (`id_categoria`,`id_servicio`),\n  KEY `fk_catserv_servicio` (`id_servicio`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `servicio_empleado`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `servicio_empleado` (\n  `id_servicio_empleado` int NOT NULL AUTO_INCREMENT,\n  `id_servicio` int NOT NULL,\n  `id_empleado` int NOT NULL,\n  `modalidad_disponib` varchar(50) DEFAULT NULL,\n  `max_por_dia` int DEFAULT NULL,\n  PRIMARY KEY (`id_servicio_empleado`),\n  UNIQUE KEY `uq_servicio_empleado` (`id_servicio`,`id_empleado`),\n  KEY `fk_servemp_empleado` (`id_empleado`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `bloqueo_dia`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `bloqueo_dia` (\n  `id_bloqueo_dia` int NOT NULL AUTO_INCREMENT,\n  `id_usuario` int NOT NULL,\n  `fecha_desde` date NOT NULL,\n  `fecha_hasta` date NOT NULL,\n  `motivo` varchar(255) DEFAULT NULL,\n  PRIMARY KEY (`id_bloqueo_dia`),\n  KEY `fk_bloqueo_usuario` (`id_usuario`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `citas`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `citas` (\n  `id_cita` int NOT NULL AUTO_INCREMENT,\n  `id_usuario` int NOT NULL,\n  `id_espacio` int NOT NULL,\n  `fecha_hora` datetime NOT NULL,\n  `estado` varchar(30) NOT NULL,\n  `modalidad` varchar(50) DEFAULT NULL,\n  `observaciones` text,\n  `activo` tinyint(1) NOT NULL DEFAULT '1',\n  `creada_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  `modificada_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n  PRIMARY KEY (`id_cita`),\n  KEY `fk_citas_usuario` (`id_usuario`),\n  KEY `fk_citas_espacio` (`id_espacio`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `cita_servicio`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `cita_servicio` (\n  `id_cita_servicio` int NOT NULL AUTO_INCREMENT,\n  `id_cita` int NOT NULL,\n  `id_servicio_empleado` int NOT NULL,\n  PRIMARY KEY (`id_cita_servicio`),\n  UNIQUE KEY `uq_cita_servemp` (`id_cita`,`id_servicio_empleado`),\n  KEY `fk_citaserv_servemp` (`id_servicio_empleado`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `imagen_cita`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `imagen_cita` (\n  `id_imagen_cita` int NOT NULL AUTO_INCREMENT,\n  `id_cita` int NOT NULL,\n  `tipo_imagen` varchar(50) DEFAULT NULL,\n  `imagenUrl` varchar(255) NOT NULL,\n  `fecha_carga` date NOT NULL,\n  PRIMARY KEY (`id_imagen_cita`),\n  KEY `fk_imgcita_cita` (`id_cita`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `resenas`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `resenas` (\n  `id_resena` int NOT NULL AUTO_INCREMENT,\n  `id_servicio` int NOT NULL,\n  `id_usuario` int NOT NULL,\n  `id_cita` int DEFAULT NULL,\n  `puntuacion` int NOT NULL,\n  `comentario` text NOT NULL,\n  `fecha_publicacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  PRIMARY KEY (`id_resena`),\n  KEY `fk_resena_servicio` (`id_servicio`),\n  KEY `fk_resena_usuario` (`id_usuario`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `payment_accounts`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `payment_accounts` (\n  `id_cuenta` int NOT NULL AUTO_INCREMENT,\n  `titular` varchar(150) NOT NULL,\n  `cuit_cuil` varchar(30) NOT NULL,\n  `tipo_cuenta` varchar(50) NOT NULL,\n  `alias` varchar(50) NOT NULL,\n  `cbu_cvu` varchar(50) NOT NULL,\n  `banco` varchar(100) NOT NULL,\n  `es_activa` tinyint(1) NOT NULL DEFAULT '1',\n  `estado` varchar(20) NOT NULL DEFAULT 'activo',\n  PRIMARY KEY (`id_cuenta`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

$sqlDump .= "-- Estructura para la tabla `cambios_dashboard`\n";
$sqlDump .= "CREATE TABLE IF NOT EXISTS `cambios_dashboard` (\n  `id_cambio` int NOT NULL AUTO_INCREMENT,\n  `modulo` varchar(50) NOT NULL,\n  `accion` varchar(50) NOT NULL,\n  `registro_id` int DEFAULT NULL,\n  `id_usuario` int NOT NULL,\n  `usuario_nombre` varchar(100) NOT NULL,\n  `usuario_rol` varchar(50) NOT NULL,\n  `detalles` text NOT NULL,\n  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n  PRIMARY KEY (`id_cambio`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

// INSERTS
$sqlDump .= "-- Volcado de datos para la tabla `roles`\n";
$sqlDump .= "INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES\n";
$roleInserts = [];
foreach ($roles as $idR => $nomR) {
    $roleInserts[] = "($idR, '$nomR')";
}
$sqlDump .= implode(",\n", $roleInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `categoria`\n";
$sqlDump .= "INSERT INTO `categoria` (`id_categoria`, `nombre_categoria`) VALUES\n";
$catInserts = [];
foreach ($categorias as $idC => $nomC) {
    $catInserts[] = "($idC, '$nomC')";
}
$sqlDump .= implode(",\n", $catInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `espacio`\n";
$sqlDump .= "INSERT INTO `espacio` (`id_espacio`, `nombre`, `tipo`, `estado`) VALUES\n";
$espInserts = [];
foreach ($espacios as $idE => $esp) {
    $espInserts[] = "($idE, '{$esp['nombre']}', '{$esp['tipo']}', '{$esp['estado']}')";
}
$sqlDump .= implode(",\n", $espInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `especialidad`\n";
$sqlDump .= "INSERT INTO `especialidad` (`id_especialidad`, `nombre_especialidad`) VALUES\n";
$espArr = [];
foreach ($especialidades as $idEsp => $nEsp) {
    $espArr[] = "($idEsp, '$nEsp')";
}
$sqlDump .= implode(",\n", $espArr) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `usuario` (25 Usuarios: 1 Super Admin, 4 Admins, 20 Clientes)\n";
$sqlDump .= "INSERT INTO `usuario` (`id_usuario`, `nombre`, `apellido`, `email`, `contrasena`, `fecha_registro`, `activo`, `telefono`, `DNI`, `id_rol`) VALUES\n";
$usrInserts = [];
foreach ($usuarios as $idU => $u) {
    $contrasenaSql = addslashes($u['contrasena']);
    $usrInserts[] = "($idU, '{$u['nombre']}', '{$u['apellido']}', '{$u['email']}', '$contrasenaSql', '{$u['fecha_registro']}', {$u['activo']}, '{$u['telefono']}', '{$u['DNI']}', {$u['id_rol']})";
}
$sqlDump .= implode(",\n", $usrInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `empleado` (5 Empleadas)\n";
$sqlDump .= "INSERT INTO `empleado` (`id_empleado`, `nombre`, `apellido`, `telefono`, `email`, `imagenEmpleado`, `modalidad`, `activo`) VALUES\n";
$empInserts = [];
foreach ($empleadas as $idEmp => $e) {
    $empInserts[] = "($idEmp, '{$e['nombre']}', '{$e['apellido']}', '{$e['telefono']}', '{$e['email']}', '{$e['imagenEmpleado']}', '{$e['modalidad']}', {$e['activo']})";
}
$sqlDump .= implode(",\n", $empInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `empleado_especialidad`\n";
$sqlDump .= "INSERT INTO `empleado_especialidad` (`id_empleado_especialidad`, `id_empleado`, `id_especialidad`) VALUES\n";
$empEspInserts = [];
$cEE = 1;
foreach ($empleadas as $idEmp => $e) {
    foreach ($e['especialidades'] as $idEsp) {
        $empEspInserts[] = "($cEE, $idEmp, $idEsp)";
        $cEE++;
    }
}
$sqlDump .= implode(",\n", $empEspInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `servicio` (15 Servicios con Cuidados Antes y Después)\n";
$sqlDump .= "INSERT INTO `servicio` (`id_servicio`, `nombre_servicio`, `descripcion`, `categoria`, `disponibilidad`, `imagen_servicio`, `duracion_minutos`, `cuidados_previos`, `cuidados_posteriores`) VALUES\n";
$srvInserts = [];
foreach ($servicios as $idS => $s) {
    $nom = addslashes($s['nombre_servicio']);
    $desc = addslashes($s['descripcion']);
    $cat = addslashes($s['categoria']);
    $prev = addslashes($s['cuidados_previos']);
    $post = addslashes($s['cuidados_posteriores']);
    $srvInserts[] = "($idS, '$nom', '$desc', '$cat', 1, '{$s['imagen_servicio']}', {$s['duracion_minutos']}, '$prev', '$post')";
}
$sqlDump .= implode(",\n", $srvInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `precio_servicio`\n";
$sqlDump .= "INSERT INTO `precio_servicio` (`id_precio_servicio`, `id_servicio`, `precio`, `fecha_desde`, `fecha_hasta`) VALUES\n";
$prcInserts = [];
foreach ($servicios as $idS => $s) {
    $prcInserts[] = "($idS, $idS, {$s['precio']}, '2026-01-01', NULL)";
}
$sqlDump .= implode(",\n", $prcInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `categoria_servicio`\n";
$sqlDump .= "INSERT INTO `categoria_servicio` (`id_categoria_servicio`, `id_categoria`, `id_servicio`) VALUES\n";
$catSrvInserts = [];
foreach ($servicios as $idS => $s) {
    $catSrvInserts[] = "($idS, {$s['id_categoria']}, $idS)";
}
$sqlDump .= implode(",\n", $catSrvInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `servicio_empleado`\n";
$sqlDump .= "INSERT INTO `servicio_empleado` (`id_servicio_empleado`, `id_servicio`, `id_empleado`, `modalidad_disponib`, `max_por_dia`) VALUES\n";
$seInserts = [];
foreach ($serviciosEmpleadas as $idSE => $se) {
    $seInserts[] = "($idSE, {$se['id_servicio']}, {$se['id_empleado']}, '{$se['modalidad']}', {$se['max']})";
}
$sqlDump .= implode(",\n", $seInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `bloqueo_dia` (20 Bloques: Feriados Nacionales, de Salta y Cumpleaños Administradora 06/11)\n";
$sqlDump .= "INSERT INTO `bloqueo_dia` (`id_bloqueo_dia`, `id_usuario`, `fecha_desde`, `fecha_hasta`, `motivo`) VALUES\n";
$blqInserts = [];
foreach ($bloqueos as $idB => $b) {
    $mot = addslashes($b['motivo']);
    $blqInserts[] = "($idB, {$b['id_usuario']}, '{$b['fecha_desde']}', '{$b['fecha_hasta']}', '$mot')";
}
$sqlDump .= implode(",\n", $blqInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `citas` (35 Turnos)\n";
$sqlDump .= "INSERT INTO `citas` (`id_cita`, `id_usuario`, `id_espacio`, `fecha_hora`, `estado`, `modalidad`, `observaciones`, `activo`, `creada_en`) VALUES\n";
$citasInserts = [];
foreach ($turnos as $idC => $c) {
    $obs = addslashes($c['observaciones']);
    $citasInserts[] = "($idC, {$c['id_usuario']}, {$c['id_espacio']}, '{$c['fecha_hora']}', '{$c['estado']}', '{$c['modalidad']}', '$obs', 1, '{$c['fecha_hora']}')";
}
$sqlDump .= implode(",\n", $citasInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `cita_servicio`\n";
$sqlDump .= "INSERT INTO `cita_servicio` (`id_cita_servicio`, `id_cita`, `id_servicio_empleado`) VALUES\n";
$csInserts = [];
foreach ($turnos as $idC => $c) {
    $csInserts[] = "($idC, $idC, {$c['id_se']})";
}
$sqlDump .= implode(",\n", $csInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `resenas` (10 Reseñas Verificadas)\n";
$sqlDump .= "INSERT INTO `resenas` (`id_resena`, `id_servicio`, `id_usuario`, `id_cita`, `puntuacion`, `comentario`, `fecha_publicacion`) VALUES\n";
$resInserts = [];
foreach ($resenas as $idR => $r) {
    $com = addslashes($r['comentario']);
    $resInserts[] = "($idR, {$r['id_servicio']}, {$r['id_usuario']}, {$r['id_cita']}, {$r['puntuacion']}, '$com', '{$r['fecha_publicacion']}')";
}
$sqlDump .= implode(",\n", $resInserts) . ";\n\n";

$sqlDump .= "-- Volcado de datos para la tabla `payment_accounts`\n";
$sqlDump .= "INSERT INTO `payment_accounts` (`id_cuenta`, `titular`, `cuit_cuil`, `tipo_cuenta`, `alias`, `cbu_cvu`, `banco`, `es_activa`, `estado`) VALUES\n";
$payInserts = [];
foreach ($payment_accounts as $idP => $p) {
    $payInserts[] = "({$p['id_cuenta']}, '{$p['titular']}', '{$p['cuit_cuil']}', '{$p['tipo_cuenta']}', '{$p['alias']}', '{$p['cbu_cvu']}', '{$p['banco']}', {$p['es_activa']}, '{$p['estado']}')";
}
$sqlDump .= implode(",\n", $payInserts) . ";\n\n";

// FOREIGN KEYS
$sqlDump .= "--\n-- Restricciones para tablas volcadas\n--\n\n";
$sqlDump .= "ALTER TABLE `bloqueo_dia`\n  ADD CONSTRAINT `fk_bloqueo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `categoria_servicio`\n  ADD CONSTRAINT `fk_catserv_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON DELETE CASCADE,\n  ADD CONSTRAINT `fk_catserv_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `citas`\n  ADD CONSTRAINT `fk_citas_espacio` FOREIGN KEY (`id_espacio`) REFERENCES `espacio` (`id_espacio`),\n  ADD CONSTRAINT `fk_citas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `cita_servicio`\n  ADD CONSTRAINT `fk_citaserv_cita` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`) ON DELETE CASCADE,\n  ADD CONSTRAINT `fk_citaserv_servemp` FOREIGN KEY (`id_servicio_empleado`) REFERENCES `servicio_empleado` (`id_servicio_empleado`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `empleado_especialidad`\n  ADD CONSTRAINT `fk_empesp_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE,\n  ADD CONSTRAINT `fk_empesp_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_especialidad`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `precio_servicio`\n  ADD CONSTRAINT `fk_precio_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `servicio_empleado`\n  ADD CONSTRAINT `fk_servemp_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleado` (`id_empleado`) ON DELETE CASCADE,\n  ADD CONSTRAINT `fk_servemp_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE;\n\n";
$sqlDump .= "ALTER TABLE `usuario`\n  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);\n\n";
$sqlDump .= "ALTER TABLE `resenas`\n  ADD CONSTRAINT `fk_resena_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicio` (`id_servicio`) ON DELETE CASCADE,\n  ADD CONSTRAINT `fk_resena_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;\n\n";

$sqlDump .= "COMMIT;\n\n";
$sqlDump .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
$sqlDump .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
$sqlDump .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

// Guardar archivo SQL
file_put_contents(__DIR__ . '/../database/arcanaestetica.sql', $sqlDump);
echo "SQL dump generado exitosamente en database/arcanaestetica.sql\n";

// TAMBIÉN GUARDAR EN JSON DB para compatibilidad total con JsonDB
$jsonDB = [
    'roles' => array_values(array_map(function($k, $v) { return ['id_rol' => $k, 'nombre_rol' => $v]; }, array_keys($roles), $roles)),
    'categoria' => array_values(array_map(function($k, $v) { return ['id_categoria' => $k, 'nombre_categoria' => $v]; }, array_keys($categorias), $categorias)),
    'espacio' => array_values(array_map(function($k, $v) { return array_merge(['id_espacio' => $k], $v); }, array_keys($espacios), $espacios)),
    'especialidad' => array_values(array_map(function($k, $v) { return ['id_especialidad' => $k, 'nombre_especialidad' => $v]; }, array_keys($especialidades), $especialidades)),
    'usuario' => array_values(array_map(function($k, $v) { return array_merge(['id_usuario' => $k], $v); }, array_keys($usuarios), $usuarios)),
    'empleado' => array_values(array_map(function($k, $v) { return array_merge(['id_empleado' => $k], $v); }, array_keys($empleadas), $empleadas)),
    'empleado_especialidad' => array_values(array_map(function($r) {
        return ['id_empleado_especialidad' => $r[0], 'id_empleado' => $r[1], 'id_especialidad' => $r[2]];
    }, array_map(function($s) {
        preg_match('/\(([0-9]+),\s*([0-9]+),\s*([0-9]+)\)/', $s, $m);
        return [(int)$m[1], (int)$m[2], (int)$m[3]];
    }, $empEspInserts))),
    'servicio' => array_values(array_map(function($k, $v) {
        return [
            'id_servicio' => $k,
            'nombre_servicio' => $v['nombre_servicio'],
            'descripcion' => $v['descripcion'],
            'categoria' => $v['categoria'],
            'disponibilidad' => 1,
            'imagen_servicio' => $v['imagen_servicio'],
            'duracion_minutos' => $v['duracion_minutos'],
            'cuidados_previos' => $v['cuidados_previos'],
            'cuidados_posteriores' => $v['cuidados_posteriores']
        ];
    }, array_keys($servicios), $servicios)),
    'precio_servicio' => array_values(array_map(function($k, $v) {
        return [
            'id_precio_servicio' => $k,
            'id_servicio' => $k,
            'precio' => $v['precio'],
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => null
        ];
    }, array_keys($servicios), $servicios)),
    'categoria_servicio' => array_values(array_map(function($k, $v) {
        return [
            'id_categoria_servicio' => $k,
            'id_categoria' => $v['id_categoria'],
            'id_servicio' => $k
        ];
    }, array_keys($servicios), $servicios)),
    'servicio_empleado' => array_values(array_map(function($k, $v) {
        return array_merge(['id_servicio_empleado' => $k], $v);
    }, array_keys($serviciosEmpleadas), $serviciosEmpleadas)),
    'bloqueo_dia' => array_values(array_map(function($k, $v) {
        return array_merge(['id_bloqueo_dia' => $k], $v);
    }, array_keys($bloqueos), $bloqueos)),
    'citas' => array_values(array_map(function($k, $v) {
        return [
            'id_cita' => $k,
            'id_usuario' => $v['id_usuario'],
            'id_espacio' => $v['id_espacio'],
            'fecha_hora' => $v['fecha_hora'],
            'estado' => $v['estado'],
            'modalidad' => $v['modalidad'],
            'observaciones' => $v['observaciones'],
            'activo' => 1,
            'creada_en' => $v['fecha_hora'],
            'modificada_en' => $v['fecha_hora']
        ];
    }, array_keys($turnos), $turnos)),
    'cita_servicio' => array_values(array_map(function($k, $v) {
        return [
            'id_cita_servicio' => $k,
            'id_cita' => $k,
            'id_servicio_empleado' => $v['id_se']
        ];
    }, array_keys($turnos), $turnos)),
    'resenas' => array_values(array_map(function($k, $v) {
        return array_merge(['id_resena' => $k], $v);
    }, array_keys($resenas), $resenas)),
    'payment_accounts' => array_values($payment_accounts),
    'cambios_dashboard' => [
        [
            'id_cambio' => 1,
            'modulo' => 'Inicialización',
            'accion' => 'Carga inicial de datos',
            'registro_id' => 1,
            'id_usuario' => 1,
            'usuario_nombre' => 'Anabela Acosta',
            'usuario_rol' => 'Super Admin',
            'detalles' => 'Configuración de categorías, 15 servicios, 5 empleadas, 25 usuarios y 20 bloqueos de calendario.',
            'fecha_hora' => '2026-10-04 18:00:00'
        ]
    ]
];

file_put_contents(__DIR__ . '/../database/arcana_db.json', json_encode($jsonDB, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "JSON DB actualizado exitosamente en database/arcana_db.json\n";
