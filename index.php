<?php
include "config/db.php";

/* Ambil data */
$kegiatan_all = [];
$prestasi_all = [];
$struktur = [];

$kq = mysqli_query($conn, "SELECT id, judul, deskripsi, tanggal, foto, foto_type FROM kegiatan ORDER BY tanggal DESC, id DESC");
while($r = mysqli_fetch_assoc($kq)) $kegiatan_all[] = $r;

$pq = mysqli_query($conn, "SELECT id, judul, keterangan, tanggal, foto, foto_type FROM prestasi ORDER BY tanggal DESC, id DESC");
while($r = mysqli_fetch_assoc($pq)) $prestasi_all[] = $r;

$sq = mysqli_query($conn, "SELECT * FROM struktur_desa ORDER BY id ASC");
while($r = mysqli_fetch_assoc($sq)) $struktur[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>E-DESLAY | Desa Banjardowo</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="header">
  <div class="logo-container">
    <img src="assets/images/logo-nganjuk.png" alt="Logo Kabupaten Nganjuk" class="logo-kabupaten">
    <div class="desa-info">
      <h1>Desa Banjardowo</h1>
      <p>Kecamatan Lengkong, Kabupaten Nganjuk</p>
    </div>
  </div>

  <nav class="nav-menu">
    <ul>
      <li><a href="#visi-misi">Visi-Misi</a></li>
      <li><a href="#kegiatan">Kegiatan</a></li>
      <li><a href="#prestasi">Prestasi</a></li>
      <li><a href="#struktur">Struktur</a></li>
      <li><a href="login.php" class="btn primary">Login Admin</a></li>
    </ul>
  </nav>
</header>

<main class="page-wrapper">

  <section class="hero reveal">
    <div class="hero-logo">
      <img src="assets/images/logo-big.png" alt="E-Deslay">
    </div>
    <div class="hero-text">
      <h1>Selamat datang di E-Deslay, Layanan Digital 
        Desa yang Lebih Mudah dan Cepat.</h1>
      <p>
        E-Deslay hadir sebagai wujud inovasi pelayanan masyarakat di desa. 
        Melalui platform ini, warga dapat memperoleh panduan pembuatan surat, 
        menyampaikan saran, serta mengakses informasi terkini mengenai kegiatan 
        desa. Mari bersama mewujudkan pelayanan publik yang transparan, efisien,
        dan berorientasi pada kemudahan masyarakat.
      </p>
    </div>
  </section>

  <section id="visi-misi" class="visi-misi reveal">
    <h2>Visi & Misi</h2>
    <p>
      Pemerintah Desa Banjardowo berkomitmen untuk mewujudkan pelayanan publik yang
      transparan, cepat, dan berbasis digital, dengan menjunjung partisipasi masyarakat,
      serta meningkatkan kesejahteraan melalui keterbukaan informasi dan inovasi pelayanan.
    </p>
  </section>

<section id="kegiatan" class="section">
  <h2 style="text-align:center;">Kegiatan Desa</h2>

  <div class="slider-wrapper" id="kegiatan-slider">
    <div class="slider-track">
      <?php
      $total = count($kegiatan_all);
      for ($i = 0; $i < $total; $i += 2): ?>
        <div class="slide-page">
          <?php for ($j = $i; $j < min($i + 2, $total); $j++): $k = $kegiatan_all[$j]; ?>
            <div class="card-item">
              <?php if($k['foto']): ?>
                <img src="data:<?= $k['foto_type']; ?>;base64,<?= base64_encode($k['foto']); ?>" alt="">
              <?php endif; ?>
              <div class="card-content">
                <h3>
                  <a href="kegiatan_detail.php?id=<?= $k['id']; ?>" style="text-decoration:none;color:#1e3a8a;">
                    <?= htmlspecialchars($k['judul']); ?>
                  </a>
                </h3>
                <p><?= htmlspecialchars(substr($k['deskripsi'], 0, 100)); ?>...</p>
                <small><?= htmlspecialchars($k['tanggal']); ?></small>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="slider-nav">
    <button id="keg-prev">&lt; Sebelumnya</button>
    <button id="keg-next">Berikutnya &gt;</button>
  </div>
</section>

<section id="prestasi" class="section">
  <h2 style="text-align:center;">Prestasi</h2>

  <div class="slider-wrapper" id="prestasi-slider">
    <div class="slider-track">
      <?php
      $totalp = count($prestasi_all);
      for ($i = 0; $i < $totalp; $i += 2): ?>
        <div class="slide-page">
          <?php for ($j = $i; $j < min($i + 2, $totalp); $j++): $p = $prestasi_all[$j]; ?>
            <div class="card-item">
              <?php if($p['foto']): ?>
                <img src="data:<?= $p['foto_type']; ?>;base64,<?= base64_encode($p['foto']); ?>" alt="">
              <?php endif; ?>
              <div class="card-content">
                <h3>
                  <a href="prestasi_detail.php?id=<?= $p['id']; ?>" style="text-decoration:none;color:#1e3a8a;">
                    <?= htmlspecialchars($p['judul']); ?>
                  </a>
                </h3>
                <p><?= htmlspecialchars(substr($p['keterangan'], 0, 100)); ?>...</p>
                <small><?= htmlspecialchars($p['tanggal']); ?></small>
              </div>
            </div>
          <?php endfor; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="slider-nav">
    <button id="pre-prev">&lt; Sebelumnya</button>
    <button id="pre-next">Berikutnya &gt;</button>
  </div>
</section>

<section id="struktur" class="struktur reveal">
<h2>Struktur Perangkat Desa</h2>
  <div class="struktur-container">
    <?php
    $kepala = null;
    $sekretaris = null;
    $bawahan = [];
    $terakhir = null;

    foreach ($struktur as $s) {
      $jab = strtolower(trim($s['jabatan'])); // normalize

      if (strpos($jab, 'kepala desa') !== false) {
        $kepala = $s;
      } elseif (strpos($jab, 'jerukwangi') !== false) {
        $terakhir = $s;
      } else {
        $bawahan[] = $s; // sisanya masuk sini (termasuk Kepala Dusun Banjardowo)
      }
    }
?>


    <div class="struktur-grid">
    <div class="struktur-top">
      <?php if ($kepala): ?>
        <div class="card-struct utama">
          <h3><?= htmlspecialchars($kepala['jabatan']) ?></h3>
          <p><?= htmlspecialchars($kepala['nama']) ?></p>
        </div>
      <?php endif; 
      ?>
    </div>
    <?php 
    $chunks = array_chunk($bawahan, 2);
    foreach ($chunks as $pair): ?>
      <div class="row-pair">
        <?php foreach ($pair as $p): ?>
          <div class="card-struct">
            <h4><?= htmlspecialchars($p['jabatan']) ?></h4>
            <p><?= htmlspecialchars($p['nama']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="struktur-bottom">
    <?php if ($terakhir): ?>
      <div class="card-struct">
        <h4><?= htmlspecialchars($terakhir['jabatan']) ?></h4>
        <p><?= htmlspecialchars($terakhir['nama']) ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>

  <section class="download reveal">
    <h3>Download Aplikasi E-Deslay</h3>
    <p>Nikmati kemudahan layanan desa melalui aplikasi Android resmi kami.</p>
    <a href="#" class="btn primary">Download APK</a>
  </section>

</main>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-left">
      <img src="assets/images/logo-big.png" alt="Logo Team">
      <div class="desa-info">
        <h3>Desa Banjardowo</h3>
        <p>Kecamatan Lengkong, Kabupaten Nganjuk</p>
      </div>
    </div>

    <div class="footer-right">
      <h4>Kontak Kami</h4>
      <p>📍 JL. Gondang Timur No.41A Banjardowo, Kec. Lengkong, Kab. Nganjuk</p>
      <p>📧 <a href="mailto:banjardowolengkong@gmail.com" style="color:#fff;text-decoration:none;">banjardowolengkong@gmail.com</a></p>
      <p>📞 0857-4664-1970</p>
    </div>
  </div>

  <div class="footer-bottom">
    © <?= date("Y"); ?> Desa Banjardowo. All Rights Reserved.  
    | Website Resmi E-DESLAY — Layanan Digital Desa.
  </div>
</footer>


<script>
// Scroll animation
const reveals = document.querySelectorAll(".reveal");
window.addEventListener("scroll", () => {
  for (const r of reveals) {
    const top = r.getBoundingClientRect().top;
    if (top < window.innerHeight - 100) r.classList.add("active");
  }
});
</script>
<script>
function initSlider(wrapperId, prevId, nextId) {
  const wrapper = document.getElementById(wrapperId);
  if (!wrapper) return;

  const track = wrapper.querySelector('.slider-track');
  const slides = wrapper.querySelectorAll('.slide-page');
  const prev = document.getElementById(prevId);
  const next = document.getElementById(nextId);

  let index = 0;

  function update() {
    const offset = -index * 100;
    track.style.transform = `translateX(${offset}%)`;
  }

  prev.addEventListener('click', () => {
    index = (index - 1 + slides.length) % slides.length;
    update();
  });

  next.addEventListener('click', () => {
    index = (index + 1) % slides.length;
    update();
  });

  update();
}

initSlider('kegiatan-slider', 'keg-prev', 'keg-next');
initSlider('prestasi-slider', 'pre-prev', 'pre-next');
</script>
</body>
</html>
