<?php
/**
 * ARCANA — Advanced Metrics & Analytics API Endpoint (api/metrics.php)
 * Calculates comprehensive analytics: periods, top services, revenues, cancellation rate, employee KPIs.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/JsonDB.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/response.php';

$db = JsonDB::getInstance();

$servicios = $db->getTable('servicio');
$preciosList = $db->getTable('precio_servicio');
$empleados = $db->getTable('empleado');
$citas = $db->getTable('citas');
$citaServicios = $db->getTable('cita_servicio');
$servicioEmpleados = $db->getTable('servicio_empleado');
$usuarios = $db->getTable('usuario');
$bloqueos = $db->getTable('bloqueo_dia');

// Helper para obtener precio de un servicio
$preciosMap = [];
foreach ($preciosList as $p) {
    $idSrv = (int)$p['id_servicio'];
    $preciosMap[$idSrv] = (float)($p['precio'] ?? 25000);
}

// Mapeo de cita -> servicio y empleado
$citaDetails = [];
foreach ($citas as $c) {
    $idCita = (int)$c['id_cita'];
    $css = array_values(array_filter($citaServicios, function($cs) use ($idCita) {
        return (int)$cs['id_cita'] === $idCita;
    }));

    $idServicio = 1;
    $idEmpleado = 1;

    if (!empty($css)) {
        $idSE = (int)$css[0]['id_servicio_empleado'];
        $se = array_values(array_filter($servicioEmpleados, function($item) use ($idSE) {
            return (int)$item['id_servicio_empleado'] === $idSE;
        }));
        if (!empty($se)) {
            $idServicio = (int)$se[0]['id_servicio'];
            $idEmpleado = (int)$se[0]['id_empleado'];
        }
    }

    $precio = $preciosMap[$idServicio] ?? 25000.0;
    $citaDetails[$idCita] = [
        'id_servicio' => $idServicio,
        'id_empleado' => $idEmpleado,
        'precio' => $precio,
        'estado' => strtolower($c['estado'] ?? 'pendiente'),
        'fecha' => substr($c['fecha_hora'] ?? '', 0, 10),
        'fecha_hora' => $c['fecha_hora'] ?? ''
    ];
}

$hoy = date('Y-m-d');
$hace7Dias = date('Y-m-d', strtotime('-6 days'));
$inicioMes = date('Y-m-01');

// 1. Métricas de Turnos por Período
$turnosHoy = 0;
$turnosSemana = 0;
$turnosMes = 0;
$ingresosTotales = 0.0;
$ingresosMes = 0.0;
$ingresosProyectados = 0.0;
$totalCompletados = 0;
$totalCancelados = 0;
$totalPendientes = 0;

$diasSemanaGrafico = [];
for ($i = 6; $i >= 0; $i--) {
    $f = date('Y-m-d', strtotime("-$i days"));
    $diasSemanaGrafico[$f] = [
        'fecha' => $f,
        'dia_nombre' => ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][date('w', strtotime($f))],
        'total' => 0,
        'completados' => 0,
        'ingresos' => 0
    ];
}

foreach ($citas as $c) {
    $idC = (int)$c['id_cita'];
    $det = $citaDetails[$idC] ?? null;
    if (!$det) continue;

    $f = $det['fecha'];
    $st = $det['estado'];
    $pr = $det['precio'];

    if ($f === $hoy) $turnosHoy++;
    if ($f >= $hace7Dias && $f <= $hoy) $turnosSemana++;
    if ($f >= $inicioMes) $turnosMes++;

    if (isset($diasSemanaGrafico[$f])) {
        $diasSemanaGrafico[$f]['total']++;
        if ($st === 'completado') {
            $diasSemanaGrafico[$f]['completados']++;
            $diasSemanaGrafico[$f]['ingresos'] += $pr;
        }
    }

    if ($st === 'completado') {
        $totalCompletados++;
        $ingresosTotales += $pr;
        if ($f >= $inicioMes) $ingresosMes += $pr;
    } elseif ($st === 'cancelado') {
        $totalCancelados++;
    } elseif ($st === 'pendiente' || $st === 'confirmado') {
        $totalPendientes++;
        $ingresosProyectados += $pr;
    }
}

$totalTurnos = count($citas);
$tasaCancelacion = ($totalTurnos > 0) ? round(($totalCancelados / $totalTurnos) * 100, 1) : 0.0;
$tasaEfectividad = ($totalTurnos > 0) ? round(($totalCompletados / $totalTurnos) * 100, 1) : 0.0;

// 2. Ranking de Servicios Más Solicitados
$serviciosStats = [];
foreach ($servicios as $s) {
    $idS = (int)$s['id_servicio'];
    $serviciosStats[$idS] = [
        'id_servicio' => $idS,
        'nombre' => $s['nombre_servicio'],
        'categoria' => $s['categoria'] ?? 'Estética',
        'precio' => $preciosMap[$idS] ?? 25000,
        'turnos_total' => 0,
        'turnos_completados' => 0,
        'ingresos_generados' => 0.0
    ];
}

foreach ($citaDetails as $det) {
    $idS = $det['id_servicio'];
    if (isset($serviciosStats[$idS])) {
        $serviciosStats[$idS]['turnos_total']++;
        if ($det['estado'] === 'completado') {
            $serviciosStats[$idS]['turnos_completados']++;
            $serviciosStats[$idS]['ingresos_generados'] += $det['precio'];
        }
    }
}

usort($serviciosStats, function($a, $b) {
    return $b['turnos_total'] <=> $a['turnos_total'];
});
$topServicios = array_slice(array_values($serviciosStats), 0, 5);

// 3. Desempeño por Empleado
$empleadosStats = [];
foreach ($empleados as $e) {
    $idE = (int)$e['id_empleado'];
    $empleadosStats[$idE] = [
        'id_empleado' => $idE,
        'nombre' => $e['nombre'] . ' ' . $e['apellido'],
        'foto' => $e['foto'] ?? 'assets/images/team-avatar.jpg',
        'turnos_asignados' => 0,
        'turnos_completados' => 0,
        'turnos_cancelados' => 0,
        'turnos_pendientes' => 0,
        'ingresos_generados' => 0.0,
        'efectividad' => 0.0
    ];
}

foreach ($citaDetails as $det) {
    $idE = $det['id_empleado'];
    if (isset($empleadosStats[$idE])) {
        $empleadosStats[$idE]['turnos_asignados']++;
        if ($det['estado'] === 'completado') {
            $empleadosStats[$idE]['turnos_completados']++;
            $empleadosStats[$idE]['ingresos_generados'] += $det['precio'];
        } elseif ($det['estado'] === 'cancelado') {
            $empleadosStats[$idE]['turnos_cancelados']++;
        } else {
            $empleadosStats[$idE]['turnos_pendientes']++;
        }
    }
}

foreach ($empleadosStats as &$empKPI) {
    $tot = $empKPI['turnos_asignados'];
    $empKPI['efectividad'] = ($tot > 0) ? round(($empKPI['turnos_completados'] / $tot) * 100, 1) : 100.0;
}
unset($empKPI);
$empleadosKPI = array_values($empleadosStats);

jsonResponse(true, [
    'resumen_general' => [
        'total_turnos' => $totalTurnos,
        'turnos_hoy' => $turnosHoy,
        'turnos_semana' => $turnosSemana,
        'turnos_mes' => $turnosMes,
        'completados' => $totalCompletados,
        'cancelados' => $totalCancelados,
        'pendientes' => $totalPendientes,
        'tasa_cancelacion' => $tasaCancelacion . '%',
        'tasa_efectividad' => $tasaEfectividad . '%',
        'ingresos_totales' => $ingresosTotales,
        'ingresos_mes' => $ingresosMes,
        'ingresos_proyectados' => $ingresosProyectados,
        'total_servicios' => count($servicios),
        'total_empleados' => count($empleados),
        'total_usuarios' => count($usuarios)
    ],
    'grafico_7_dias' => array_values($diasSemanaGrafico),
    'top_servicios' => $topServicios,
    'desempeno_empleados' => $empleadosKPI
]);
