<?php
include 'config.php';

if (isset($_GET['id'])) {
    $id_pengaduan = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Get complaint details
    $query = "SELECT p.*, pl.nama_pelanggan, pl.no_pelanggan, pl.alamat 
              FROM pengaduan p 
              JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan 
              WHERE p.id_pengaduan = $id_pengaduan";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $complaint = mysqli_fetch_assoc($result);
        
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h3>Detail Pengaduan #' . $complaint['id_pengaduan'] . '</h3>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<div class="form-row">';
        echo '<div class="form-col">';
        echo '<p><strong>No. Pelanggan:</strong> ' . $complaint['no_pelanggan'] . '</p>';
        echo '<p><strong>Nama Pelanggan:</strong> ' . $complaint['nama_pelanggan'] . '</p>';
        echo '<p><strong>Alamat:</strong> ' . $complaint['alamat'] . '</p>';
        echo '</div>';
        echo '<div class="form-col">';
        echo '<p><strong>Tanggal Pengaduan:</strong> ' . date('d/m/Y', strtotime($complaint['tanggal_pengaduan'])) . '</p>';
        echo '<p><strong>Status:</strong> <span class="status-badge status-' . $complaint['status'] . '">' . ucfirst($complaint['status']) . '</span></p>';
        echo '</div>';
        echo '</div>';
        echo '<hr style="margin: 20px 0;">';
        echo '<p><strong>Isi Pengaduan:</strong></p>';
        echo '<p>' . $complaint['isi_pengaduan'] . '</p>';
        
        if (!empty($complaint['foto_bukti'])) {
            echo '<p><strong>Foto Bukti:</strong></p>';
            echo '<img src="uploads/' . $complaint['foto_bukti'] . '" alt="Foto Bukti" style="max-width: 100%; max-height: 300px; border-radius: 4px;">';
        }
        
        echo '<hr style="margin: 20px 0;">';
        echo '<h4>Update Status</h4>';
        echo '<form action="dashboard_admin.php" method="post">';
        echo '<input type="hidden" name="action" value="update_status">';
        echo '<input type="hidden" name="id_pengaduan" value="' . $complaint['id_pengaduan'] . '">';
        echo '<div class="form-group">';
        echo '<select name="status" class="form-control">';
        echo '<option value="menunggu" ' . ($complaint['status'] == 'menunggu' ? 'selected' : '') . '>Menunggu</option>';
        echo '<option value="diproses" ' . ($complaint['status'] == 'diproses' ? 'selected' : '') . '>Diproses</option>';
        echo '<option value="selesai" ' . ($complaint['status'] == 'selesai' ? 'selected' : '') . '>Selesai</option>';
        echo '</select>';
        echo '</div>';
        echo '<button type="submit" class="btn">Update Status</button>';
        echo '</form>';
        
        echo '<hr style="margin: 20px 0;">';
        echo '<h4>Tanggapan Admin</h4>';
        echo '<form action="dashboard_admin.php" method="post">';
        echo '<input type="hidden" name="action" value="submit_response">';
        echo '<input type="hidden" name="id_pengaduan" value="' . $complaint['id_pengaduan'] . '">';
        echo '<div class="form-group">';
        echo '<textarea name="tanggapan_admin" class="form-control" rows="4" placeholder="Berikan tanggapan...">' . (isset($complaint['tanggapan_admin']) ? $complaint['tanggapan_admin'] : '') . '</textarea>';
        echo '</div>';
        echo '<button type="submit" class="btn">Kirim Tanggapan</button>';
        echo '</form>';
        
        if (!empty($complaint['tanggapan_admin'])) {
            echo '<div style="margin-top: 20px;">';
            echo '<p><strong>Tanggapan Sebelumnya:</strong></p>';
            echo '<p>' . $complaint['tanggapan_admin'] . '</p>';
            echo '<small>Dikirim pada: ' . date('d/m/Y H:i', strtotime($complaint['tanggal_tanggapan'])) . '</small>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">Pengaduan tidak ditemukan</div>';
    }
} else {
    echo '<div class="alert alert-danger">ID pengaduan tidak valid</div>';
}
?>