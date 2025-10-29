<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login_admin.php');
    exit;
}

// Initialize variables
 $where_clauses = [];
 $params = [];
 $query = "SELECT p.*, pl.nama_pelanggan, pl.no_pelanggan 
          FROM pengaduan p 
          JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan";

// Handle filtering
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    if (!empty($_POST['start_date'])) {
        $where_clauses[] = "p.tanggal_pengaduan >= ?";
        $params[] = $_POST['start_date'];
    }
    if (!empty($_POST['end_date'])) {
        $where_clauses[] = "p.tanggal_pengaduan <= ?";
        $params[] = $_POST['end_date'];
    }
    if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
        $where_clauses[] = "p.status = ?";
        $params[] = $_POST['status'];
    }
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}
 $query .= " ORDER BY p.tanggal_pengaduan DESC";

// Prepare and execute the statement
 $stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

// Handle CSV Export
if (isset($_POST['export']) && $_POST['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_pengaduan_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['ID Pengaduan', 'No. Pelanggan', 'Nama Pelanggan', 'Tanggal', 'Isi Pengaduan', 'Status', 'Tanggapan Admin']);
    
    // Data CSV
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['id_pengaduan'],
            $row['no_pelanggan'],
            $row['nama_pelanggan'],
            $row['tanggal_pengaduan'],
            $row['isi_pengaduan'],
            $row['status'],
            $row['tanggapan_admin']
        ]);
    }
    
    fclose($output);
    exit();
}

// Re-execute query for display if it was used for export
if (isset($_POST['export'])) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - PDAM Tirta Musi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="assets/img/logo.jpg" alt="PDAM Tirta Musi Logo">
                    <h1>PDAM Tirta Musi - Seberang Ulu 2</h1>
                </div>
                <div class="header-info">
                    <p>Selamat datang, <?php echo $_SESSION['nama_admin']; ?> | <a href="logout.php" style="color: var(--light-text);">Logout</a></p>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="dashboard-container">
                <aside class="sidebar">
                    <h3>Menu Admin</h3>
                    <ul class="sidebar-menu">
                        <li><a href="dashboard_admin.php">Dashboard</a></li>
                        <li><a href="data_tunggakan.php">Data Tunggakan</a></li>
                        <li><a href="laporan.php" class="active">Laporan</a></li>
                        <li><a href="pengaturan.php">Pengaturan</a></li>
                    </ul>
                </aside>
                
                <div class="main-content">
                    <div class="content-header">
                        <h2>Laporan Pengaduan</h2>
                    </div>
                    
                    <div class="form-container">
                        <form action="laporan.php" method="post">
                            <div class="form-row">
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="start_date">Tanggal Mulai</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="end_date">Tanggal Selesai</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="form-col">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="all" <?php echo (isset($_POST['status']) && $_POST['status'] == 'all') ? 'selected' : ''; ?>>Semua</option>
                                            <option value="menunggu" <?php echo (isset($_POST['status']) && $_POST['status'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                            <option value="diproses" <?php echo (isset($_POST['status']) && $_POST['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                            <option value="selesai" <?php echo (isset($_POST['status']) && $_POST['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="filter" class="btn">Terapkan Filter</button>
                            <button type="submit" name="export" value="csv" class="btn btn-success" style="margin-left: 10px;">Export ke CSV</button>
                        </form>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>No. Pelanggan</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Isi Pengaduan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $row['id_pengaduan']; ?></td>
                                            <td><?php echo $row['no_pelanggan']; ?></td>
                                            <td><?php echo $row['nama_pelanggan']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengaduan'])); ?></td>
                                            <td><?php echo substr($row['isi_pengaduan'], 0, 50) . '...'; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Tidak ada data pengaduan yang ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>