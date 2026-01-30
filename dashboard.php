<?php
session_start();

if (!isset($_SESSION['cliente'])) {
    header('Location: portal.html');
    exit;
}

$clientSession = $_SESSION['cliente'];
$config = include __DIR__ . '/config.php';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
$clients = $services = $monthly = $kpis = [];
$error = null;

try {
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $kpis = $pdo->query("
        SELECT
          (SELECT COUNT(*) FROM clientes_registrados) AS total_clientes,
          (SELECT COUNT(*) FROM clientes_registrados WHERE status = 1) AS clientes_activos,
          (SELECT COUNT(*) FROM servicios_contratados WHERE status = 1) AS servicios_activos,
          (SELECT COALESCE(SUM(s.costo),0)
             FROM servicios_contratados sc
             JOIN servicios s ON s.id_servicio = sc.id_servicio
             WHERE sc.status = 1) AS valor_activo
    ")->fetch();

    $clients = $pdo->query("
        SELECT c.id_cliente_reg, c.nombre, c.mail, c.nivel, c.status,
               COUNT(sc.id_servicio_cont) AS servicios,
               COALESCE(SUM(CASE WHEN sc.status = 1 THEN s.costo ELSE 0 END), 0) AS valor_activo
        FROM clientes_registrados c
        LEFT JOIN servicios_contratados sc ON sc.id_cliente_reg = c.id_cliente_reg
        LEFT JOIN servicios s ON s.id_servicio = sc.id_servicio
        GROUP BY c.id_cliente_reg
        ORDER BY valor_activo DESC, servicios DESC;
    ")->fetchAll();

    $services = $pdo->query("
        SELECT s.nombre, COUNT(sc.id_servicio_cont) AS contratos,
               SUM(CASE WHEN sc.status = 1 THEN 1 ELSE 0 END) AS activos,
               COALESCE(SUM(CASE WHEN sc.status = 1 THEN s.costo ELSE 0 END),0) AS valor_activo
        FROM servicios s
        LEFT JOIN servicios_contratados sc ON sc.id_servicio = s.id_servicio
        GROUP BY s.id_servicio
        ORDER BY contratos DESC;
    ")->fetchAll();

    $monthly = $pdo->query("
        SELECT DATE_FORMAT(sc.fecha_inicio, '%Y-%m') AS mes,
               COALESCE(SUM(s.costo),0) AS total
        FROM servicios_contratados sc
        JOIN servicios s ON s.id_servicio = sc.id_servicio
        GROUP BY mes
        ORDER BY mes;
    ")->fetchAll();
} catch (PDOException $e) {
    $error = 'No se pudo conectar a la base de datos. Importa el script sql/Consulting.sql y revisa config.php. Detalle: ' . $e->getMessage();

    // Datos de demostración para que el dashboard se pueda visualizar.
    $kpis = [
        'total_clientes'   => 8,
        'clientes_activos' => 7,
        'servicios_activos'=> 9,
        'valor_activo'     => 215000,
    ];
    $clients = [
        ['nombre' => 'Grupo Nébula SA de CV', 'mail' => 'contacto@nebula.mx', 'nivel' => 'EMPRESA', 'status' => 1, 'servicios' => 2, 'valor_activo' => 53000],
        ['nombre' => 'Comercializadora Atlas', 'mail' => 'it@atlas.com.mx', 'nivel' => 'PRO', 'status' => 1, 'servicios' => 1, 'valor_activo' => 28000],
        ['nombre' => 'Innova Labs', 'mail' => 'admin@innovalabs.io', 'nivel' => 'EMPRESA', 'status' => 1, 'servicios' => 1, 'valor_activo' => 40000],
        ['nombre' => 'Café Aurora', 'mail' => 'dueño@cafeaurora.mx', 'nivel' => 'BASICO', 'status' => 1, 'servicios' => 1, 'valor_activo' => 30000],
        ['nombre' => 'Estudio Pixel', 'mail' => 'hola@estudiopixel.mx', 'nivel' => 'PRO', 'status' => 0, 'servicios' => 1, 'valor_activo' => 15000],
    ];
    $services = [
        ['nombre' => 'Desarrollo Web', 'contratos' => 2, 'activos' => 2, 'valor_activo' => 50000],
        ['nombre' => 'Implementación de RPA', 'contratos' => 2, 'activos' => 2, 'valor_activo' => 80000],
        ['nombre' => 'Control y gestión de Bases de Datos', 'contratos' => 2, 'activos' => 2, 'valor_activo' => 56000],
        ['nombre' => 'Asistencia App Moviles', 'contratos' => 1, 'activos' => 1, 'valor_activo' => 30000],
        ['nombre' => 'Fix Developer', 'contratos' => 1, 'activos' => 0, 'valor_activo' => 0],
    ];
    $monthly = [
        ['mes' => '2026-01', 'total' => 131000],
        ['mes' => '2026-02', 'total' => 84000],
    ];
}

$clientLabels   = array_map(fn($c) => $c['nombre'], $clients);
$clientServices = array_map(fn($c) => (int) $c['servicios'], $clients);
$serviceLabels  = array_map(fn($s) => $s['nombre'], $services);
$serviceCounts  = array_map(fn($s) => (int) $s['contratos'], $services);
$monthlyLabels  = array_map(fn($m) => $m['mes'], $monthly);
$monthlyTotals  = array_map(fn($m) => (float) $m['total'], $monthly);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard de Clientes | Consultoría TI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIa7WvE6lY1XGMXW50nD0Wf0U6JkP7+YlJJo3uE1x04wA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <nav>
      <div class="logo">
        <div class="logo-mark">Λ</div>
        <div>
          <div>Consultoría Estratégica TI</div>
          <small style="color: var(--muted); font-weight: 600;">Web | RPA | Apps | Datos | Fix</small>
        </div>
      </div>
      <div class="nav-links">
        <a href="index.html">Inicio</a>
        <a href="servicios.html">Servicios</a>
        <a href="sectores.html">Sectores</a>
        <a href="metodo.html">Enfoque</a>
        <a href="portal.html">Portal</a>
        <a href="agenda.html">Agenda</a>
        <a href="contacto.html">Contacto</a>
        <a href="dashboard.php">Dashboard</a>
        <a class="btn" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Salir</a>
      </div>
    </nav>
  </header>

  <main class="page">
    <section>
      <h2>Dashboard de clientes y servicios</h2>
      <p class="lead">Visibilidad de clientes registrados, servicios contratados y valor activo.</p>
      <div class="alert" style="margin-bottom:14px;">
        <i class="fa-solid fa-user-check"></i> Sesión activa: <?php echo htmlspecialchars($clientSession['nombre']); ?> (<?php echo htmlspecialchars($clientSession['nivel']); ?>)
      </div>
      <?php if ($error): ?>
        <div class="alert" style="margin-bottom:14px; border-color: rgba(255, 123, 123, 0.45); background: rgba(255, 123, 123, 0.12);">
          <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      <div class="metric-cards">
        <div class="kpi">
          <strong><?php echo (int)$kpis['total_clientes']; ?></strong>
          <span class="helper">Clientes registrados</span>
        </div>
        <div class="kpi">
          <strong><?php echo (int)$kpis['clientes_activos']; ?></strong>
          <span class="helper">Clientes activos</span>
        </div>
        <div class="kpi">
          <strong><?php echo (int)$kpis['servicios_activos']; ?></strong>
          <span class="helper">Servicios activos</span>
        </div>
        <div class="kpi">
          <strong>$<?php echo number_format((float)$kpis['valor_activo'], 0); ?></strong>
          <span class="helper">Valor mensual estimado</span>
        </div>
      </div>
    </section>

    <section>
      <h2>Clientes registrados</h2>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Nivel</th>
              <th>Servicios</th>
              <th>Valor activo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['nombre']); ?><br><span class="helper"><?php echo htmlspecialchars($c['mail'] ?? ''); ?></span></td>
                <td><?php echo htmlspecialchars($c['nivel']); ?></td>
                <td><?php echo (int)$c['servicios']; ?></td>
                <td>$<?php echo number_format((float)$c['valor_activo'], 0); ?></td>
                <td>
                  <?php if ((int)$c['status'] === 1): ?>
                    <span class="badge success">Activo</span>
                  <?php else: ?>
                    <span class="badge danger">Inactivo</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section>
      <h2>Servicios contratados</h2>
      <div class="chart-grid">
        <div class="chart-card">
          <h3>Servicios por cliente</h3>
          <canvas id="chartClients"></canvas>
        </div>
        <div class="chart-card">
          <h3>Distribución por tipo de servicio</h3>
          <canvas id="chartServices"></canvas>
        </div>
        <div class="chart-card">
          <h3>Valor contratado por mes</h3>
          <canvas id="chartMonthly"></canvas>
        </div>
      </div>
    </section>

    <section>
      <h2>Resumen por servicio</h2>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>Servicio</th>
              <th>Contratos</th>
              <th>Activos</th>
              <th>Valor activo</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($services as $s): ?>
              <tr>
                <td><?php echo htmlspecialchars($s['nombre']); ?></td>
                <td><?php echo (int)$s['contratos']; ?></td>
                <td><?php echo (int)$s['activos']; ?></td>
                <td>$<?php echo number_format((float)$s['valor_activo'], 0); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    const clientLabels  = <?php echo json_encode($clientLabels); ?>;
    const clientValues  = <?php echo json_encode($clientServices); ?>;
    const serviceLabels = <?php echo json_encode($serviceLabels); ?>;
    const serviceCounts = <?php echo json_encode($serviceCounts); ?>;
    const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
    const monthlyTotals = <?php echo json_encode($monthlyTotals); ?>;

    const colorPrimary = '#00c2ff';
    const colorAccent  = '#7df4c5';
    const colorAlt     = '#6f7cff';

    new Chart(document.getElementById('chartClients'), {
      type: 'bar',
      data: {
        labels: clientLabels,
        datasets: [{
          label: 'Servicios contratados',
          data: clientValues,
          backgroundColor: colorPrimary
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
      }
    });

    new Chart(document.getElementById('chartServices'), {
      type: 'doughnut',
      data: {
        labels: serviceLabels,
        datasets: [{
          data: serviceCounts,
          backgroundColor: [colorPrimary, colorAccent, colorAlt, '#ffb866', '#ff7b7b']
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });

    new Chart(document.getElementById('chartMonthly'), {
      type: 'line',
      data: {
        labels: monthlyLabels,
        datasets: [{
          label: 'Valor contratado',
          data: monthlyTotals,
          borderColor: colorAccent,
          backgroundColor: 'rgba(125, 244, 197, 0.2)',
          tension: 0.35,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true },
          x: { ticks: { autoSkip: true, maxTicksLimit: 6 } }
        }
      }
    });
  </script>
  <script src="app.js"></script>
</body>
</html>
