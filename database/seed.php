<?php
/**
 * ARCANA — Database Seeder
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

function seedDatabase(PDO $pdo): void {
    // Check if data already exists
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM servicios");
    if ((int)$stmt->fetchColumn() > 0) {
        return; // Already seeded
    }

    // 1. Seed Servicios (18 items)
    $servicios = [
        ['Manicura spa', 'Manicura profesional con disenos delicados, cuidado personalizado y acabados elegantes.', 'Manicura', 'Sabrina Fernandez', 1, 'assets/images/service-manicure.jpg', 90, 25000],
        ['Pedicura relajante', 'Tratamiento delicado y profesional para aliviar molestias y corregir unas encarnadas.', 'Pedicura', 'Anabela Acosta', 1, 'assets/images/service-pedicure.jpg', 90, 25000],
        ['Pestanas voluminosas', 'Pestanas voluminosas y definidas, con aplicacion profesional y acabados delicados.', 'Pestañas', 'Agustina Cabrera', 1, 'assets/images/service-eyelashes.jpg', 90, 25000],
        ['Tratamiento de talones', 'Cuidado intensivo para talones agrietados con hidratacion profunda y suavizado.', 'Pedicura', 'Anabela Acosta', 1, 'assets/images/service-heels.jpg', 60, 22000],
        ['Unas artisticas', 'Disenos creativos y personalizados en unas esculpidas con tecnicas de ultima tendencia.', 'Uñas', 'Sabrina Fernandez', 1, 'assets/images/service-nailart.jpg', 120, 35000],
        ['Trenzas profesionales', 'Trenzas de todo tipo con tecnicas especializadas y cuidado del cuero cabelludo.', 'Trenzas', 'Macarena Aguilar', 1, 'assets/images/service-braids.jpg', 180, 45000],
        ['Facial hidratante', 'Tratamiento facial completo con limpieza profunda, exfoliacion e hidratacion intensiva.', 'Faciales', 'Rocio Lopez', 1, 'assets/images/service-facial.jpg', 75, 30000],
        ['Masaje descontracturante', 'Masaje terapeutico para aliviar tension muscular y mejorar la circulacion.', 'Masajes', 'Nahuel Benitez', 1, 'assets/images/service-massage.jpg', 60, 28000],
        ['Perfilado de cejas', 'Diseño y perfilado de cejas con henna para un look definido y natural.', 'Cejas', 'Agustina Cabrera', 1, 'assets/images/service-eyebrows.jpg', 45, 18000],
        ['Depilacion corporal', 'Depilacion con cera caliente para una piel suave y libre de vello.', 'Depilación', 'Valeria Castillo', 1, 'assets/images/service-waxing.jpg', 60, 22000],
        ['Podologia clinica', 'Tratamiento especializado para unas encarnadas, callosidades y problemas podales.', 'Podología', 'Anabela Acosta', 1, 'assets/images/service-podiatry.jpg', 60, 32000],
        ['Esmaltado semipermanente', 'Esmaltado de larga duracion con gran variedad de colores y acabado perfecto.', 'Manicura', 'Sabrina Fernandez', 1, 'assets/images/service-manicure.jpg', 60, 20000],
        ['Masaje con piedras', 'Terapia de piedras calientes para una relajacion profunda y equilibrio energetico.', 'Masajes', 'Nahuel Benitez', 1, 'assets/images/service-massage.jpg', 90, 35000],
        ['Lifting de pestanas', 'Rizado y tinte natural de pestanas para una mirada abierta y radiante.', 'Pestañas', 'Agustina Cabrera', 0, 'assets/images/service-eyelashes.jpg', 50, 22000],
        ['Facial antiedad', 'Tratamiento reafirmante con activos antiedad para prevenir lineas de expresion.', 'Faciales', 'Rocio Lopez', 1, 'assets/images/service-facial.jpg', 90, 42000],
        ['Depilacion facial', 'Depilacion delicada de rostro con hilo o cera segun tipo de piel.', 'Depilación', 'Valeria Castillo', 1, 'assets/images/service-waxing.jpg', 30, 15000],
        ['Unas esculpidas', 'Extension de unas en acrilico o gel con la forma y longitud deseada.', 'Uñas', 'Sabrina Fernandez', 1, 'assets/images/service-nailart.jpg', 150, 40000],
        ['Cejas microblading', 'Tecnica de pigmentacion semipermanente para cejas perfectas todo el dia.', 'Cejas', 'Agustina Cabrera', 0, 'assets/images/service-eyebrows.jpg', 120, 55000]
    ];

    $stmtServ = $pdo->prepare("INSERT INTO servicios (nombre, descripcion, categoria, especialista, disponible, imagen, duracion, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($servicios as $s) {
        $stmtServ->execute($s);
    }

    // 2. Seed Empleados (6 items)
    $empleados = [
        ['Sabrina', 'Lopez', 'sabrina.l@arcana.com', '3795-678909', 'assets/images/team-avatar.jpg', json_encode(['Manicura', 'Uñas']), 'Presencial'],
        ['Anabela', 'Acosta', 'anabela.a@arcana.com', '3794-678009', 'assets/images/team-avatar.jpg', json_encode(['Podología', 'Pedicura']), 'Mixto'],
        ['Agustina', 'Cabrera', 'agustina.c@arcana.com', '3795-557709', 'assets/images/team-avatar.jpg', json_encode(['Pestañas', 'Cejas']), 'Presencial'],
        ['Macarena', 'Aguilar', 'macarena.a@arcana.com', '3795-670039', 'assets/images/team-avatar.jpg', json_encode(['Trenzas', 'Masajes']), 'Presencial'],
        ['Rocio', 'Lopez', 'rocio.l@arcana.com', '3794-564312', 'assets/images/team-avatar.jpg', json_encode(['Faciales']), 'Domicilio'],
        ['Nahuel', 'Benitez', 'nahuel.b@arcana.com', '3794-928407', 'assets/images/team-avatar.jpg', json_encode(['Masajes']), 'Presencial']
    ];

    $stmtEmp = $pdo->prepare("INSERT INTO empleados (nombre, apellido, email, telefono, foto, especialidades, modalidad) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($empleados as $e) {
        $stmtEmp->execute($e);
    }

    // 3. Seed Usuarios (10 items)
    $adminPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
    $userPasswordHash = password_hash('user123', PASSWORD_BCRYPT);

    $usuarios = [
        ['Fernando', 'Cabral', 'ferbralka@gmail.com', '3794-758013', '38456123', $userPasswordHash, 'usuario', 'inactivo', '2026-06-25 10:00:00'],
        ['Anabela', 'Acosta', 'anaacostra00@gmail.com', '3794-678009', '35123456', $adminPasswordHash, 'admin', 'activo', '2026-06-25 10:00:00'],
        ['Virginia', 'Garcia', 'garcia1996@gmail.com', '3795-778807', '36987123', $userPasswordHash, 'usuario', 'activo', '2026-07-01 10:00:00'],
        ['Rocio', 'Lopez', 'rociolopez@gmail.com', '3794-564312', '37456987', $userPasswordHash, 'usuario', 'activo', '2026-07-05 10:00:00'],
        ['Valeria', 'Castillo', 'valcastillo@gmail.com', '3795-223344', '38521476', $userPasswordHash, 'usuario', 'activo', '2026-06-28 10:00:00'],
        ['Nahuel', 'Benitez', 'nahuelb@gmail.com', '3794-667788', '37234561', $userPasswordHash, 'usuario', 'activo', '2026-06-26 10:00:00'],
        ['Sabrina', 'Fernandez', 'sabrifern@gmail.com', '3795-990011', '38123456', $adminPasswordHash, 'admin', 'activo', '2026-06-20 10:00:00'],
        ['Agustina', 'Cabrera', 'aguscabr45@gmail.com', '3795-557709', '38901234', $userPasswordHash, 'usuario', 'activo', '2026-06-22 10:00:00'],
        ['Macarena', 'Aguilar', 'macaaguilar@gmail.com', '3795-670039', '36547890', $userPasswordHash, 'usuario', 'inactivo', '2026-07-02 10:00:00'],
        ['Laura', 'Martinez', 'lauramtz@gmail.com', '3794-112233', '39012345', $userPasswordHash, 'usuario', 'activo', '2026-07-03 10:00:00']
    ];

    $stmtUsr = $pdo->prepare("INSERT INTO usuarios (nombre, apellido, email, telefono, dni, password_hash, rol, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($usuarios as $u) {
        $stmtUsr->execute($u);
    }

    // 4. Seed Turnos (12 items)
    $turnos = [
        ['2026-07-07 16:30:00', 'Nahuel Benitez', 'Sabrina Lopez', 'Pedicura', 'A', 'Presencial', 'completado', '-'],
        ['2026-07-10 14:30:00', 'Fernando Cabral', 'Anabela Acosta', 'Podología', 'A', 'Domicilio', 'completado', '-'],
        ['2026-07-11 13:00:00', 'Valeria Castillo', 'Macarena Aguilar', 'Manicura', 'B', 'Presencial', 'cancelado', '-'],
        ['2026-07-13 12:30:00', 'Rocio Fernandez', 'Macarena Aguilar', 'Manicura', 'A', 'Presencial', 'pendiente', 'Diabetes'],
        ['2026-07-14 10:00:00', 'Virginia Garcia', 'Agustina Cabrera', 'Pestañas voluminosas', 'B', 'Presencial', 'confirmado', '-'],
        ['2026-07-15 15:00:00', 'Laura Martinez', 'Sabrina Fernandez', 'Unas artisticas', 'A', 'Presencial', 'confirmado', '-'],
        ['2026-07-16 11:00:00', 'Carlos Mendez', 'Nahuel Benitez', 'Masaje descontracturante', 'C', 'Presencial', 'pendiente', 'Embarazo'],
        ['2026-07-17 09:30:00', 'Maria Sanchez', 'Anabela Acosta', 'Podologia clinica', 'A', 'Presencial', 'completado', '-'],
        ['2026-07-18 14:00:00', 'Pedro Gonzalez', 'Rocio Lopez', 'Facial hidratante', 'B', 'Domicilio', 'confirmado', '-'],
        ['2026-07-19 16:00:00', 'Lucia Ramirez', 'Agustina Cabrera', 'Perfilado de cejas', 'A', 'Presencial', 'completado', '-'],
        ['2026-07-20 13:30:00', 'Ana Torres', 'Valeria Castillo', 'Depilacion corporal', 'B', 'Presencial', 'cancelado', 'Alergia a cera'],
        ['2026-07-21 10:30:00', 'Diego Ruiz', 'Sabrina Fernandez', 'Esmaltado semipermanente', 'A', 'Presencial', 'pendiente', '-']
    ];

    $stmtTur = $pdo->prepare("INSERT INTO turnos (fecha_hora, cliente, empleado, servicio, espacio, modalidad, estado, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($turnos as $t) {
        $stmtTur->execute($t);
    }
}

// If run directly from CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    echo "Inicializando base de datos...\n";
    $pdo = Database::getConnection();
    seedDatabase($pdo);
    echo "¡Base de datos inicializada y poblada con éxito!\n";
}
