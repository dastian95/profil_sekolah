<?php
// Partial bersama: fungsi JS printBukti(r) untuk cetak Bukti Tanda Daftar SPMB.
// Di-include DI DALAM blok <script> pada admin/pendaftar.php & admin/antrian.php.
// Butuh $sch_nama & $sch_alamat di scope pemanggil (dari site_settings).

// URL absolut aset — wajib utk gambar kop di popup cetak (about:blank tak punya base relatif)
$_bukti_scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$_bukti_dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$_bukti_asset  = $_bukti_scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_bukti_dir;
?>
function printBukti(r) {
    const jk   = r.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    const tgl  = r.tanggal_lahir ? new Date(r.tanggal_lahir).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-';
    const tglKk= r.tgl_kk ? new Date(r.tgl_kk).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-';
    const daft = r.tanggal_daftar ? new Date(r.tanggal_daftar).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    const sistemLabel = r.sistem_pendidikan === 'pkbm' ? 'PKBM (70% Raport)' : r.sistem_pendidikan === 'khusus' ? 'Daftar Khusus (70% Raport)' : 'Reguler (SMP)';
    // Info antrian/loket (dari meja yang mengisi data) — opsional
    const antri = r._antrian || null;
    // Auto-centang berkas berdasarkan data yang sudah terisi
    const kkOk    = r.tgl_kk && r.tgl_kk <= '2025-06-15';
    const tkaOk   = parseFloat(r.nilai_tka) > 0;
    const boxOn   = '<div class="berkas-box on">&#10003;</div>';
    const boxOff  = '<div class="berkas-box"></div>';
    const nilaiTkaTxt = tkaOk ? Number(r.nilai_tka).toFixed(2) : '';
    // Identitas sekolah ikut Konten Website (CMS)
    const SCH_NAMA   = <?= json_encode($sch_nama) ?>;
    const SCH_ALAMAT = <?= json_encode($sch_alamat) ?>;
    const SCH_LOGO   = <?= json_encode($_bukti_asset . '/assets/img/kop-surat.png') ?>;
    const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const yr = new Date().getFullYear();
    const tahunAjaran = yr + '/' + (yr + 1);

    // Baris data (label, value-html)
    const rows = [
        ['No. Pendaftaran', `<strong>${esc(r.no_pendaftaran) || '-'}</strong>`],
        ['Nama Lengkap',    `<strong>${esc(r.nama)}</strong>`],
        ['NISN',            esc(r.nisn) || '-'],
        ['Tanggal Lahir',   tgl],
        ['Jenis Kelamin',   jk],
        ['Jurusan Pilihan', `<strong>${esc(r.jurusan)}</strong>`],
        ['Gelombang',       'Gelombang ' + esc(r.gelombang)],
        ['Asal Sekolah',    esc(r.asal_sekolah) || '-'],
    ];
    if (r.alamat_sekolah) rows.push(['Alamat Sekolah', esc(r.alamat_sekolah)]);
    rows.push(['Sistem Penilaian', esc(sistemLabel)]);
    rows.push(['Tanggal KK',       tglKk]);
    rows.push(['Tanggal Daftar',   daft]);

    // Kelengkapan berkas
    const isNoTka = r.sistem_pendidikan === 'pkbm' || r.sistem_pendidikan === 'khusus';
    const ketKK = !r.tgl_kk
        ? '<small style="color:#c0392b;">Kartu Keluarga belum diserahkan kepada panitia</small>'
        : (kkOk
            ? '<span class="status-ok">&#10003; Memenuhi syarat</span><br><small>Tgl. KK: ' + tglKk + '</small>'
            : '<span class="status-fail">&#10007; Melampaui batas cut-off 15 Juni 2025</span><br><small>Tgl. KK: ' + tglKk + '</small>');
    const ketTka = isNoTka
        ? '<small style="color:#6c757d;">Tidak dipersyaratkan untuk jalur pendaftaran ini</small>'
        : (tkaOk
            ? '<span class="status-ok">&#10003; Telah diserahkan kepada panitia</span><br><small>Nilai TKA: ' + nilaiTkaTxt + '</small>'
            : '<small style="color:#c0392b;">Hasil TKA belum diserahkan kepada panitia</small>');
    const bwVal  = r.buta_warna || 'belum';
    const bwDone = bwVal !== 'belum';
    const ketBw  = !bwDone
        ? '<small style="color:#c0392b;">Hasil tes buta warna belum diserahkan kepada panitia</small>'
        : (bwVal === 'normal'
            ? '<span class="status-ok">&#10003; Normal (Tidak Buta Warna)</span>'
            : (bwVal === 'buta_warna_parsial'
                ? '<span class="status-fail">&#10007; Positif Buta Warna Parsial</span>'
                : '<span class="status-fail">&#10007; Positif Buta Warna Total</span>'));
    const berkas = [
        { label:'Kartu Keluarga (KK) DKI Jakarta', sub:'Asli + fotokopi', on:kkOk, ket:ketKK },
        { label:'Hasil TKA', sub:'Fotokopi (jalur reguler)', on:(!isNoTka && tkaOk), ket:ketTka },
        { label:'Akta Kelahiran', sub:'Fotokopi', on:false, ket:'<small style="color:#6c757d;">Diserahkan dan diverifikasi oleh petugas pada saat pendaftaran</small>' },
        { label:'Tes Buta Warna', sub:'Hasil pemeriksaan dokter', on:bwDone, ket:ketBw },
    ];

    // ── Gaya 1: Klasik ─────────────────────────────────────────────
    const lembarKlasik = `
      <div class="header">
        ${antri ? `<div class="antri-box"><div class="lbl">Loket</div><div class="num">${antri.nomor}</div>${antri.meja ? `<div class="loket">${antri.meja}</div>` : ''}</div>` : ''}
        <img class="kop-img" src="${SCH_LOGO}" alt="Kop ${esc(SCH_NAMA)}">
        <h2 style="margin-top:8px;font-size:15px;">BUKTI TANDA DAFTAR SPMB</h2>
        <p style="font-size:11px;">Tahun Pelajaran ${tahunAjaran}</p>
      </div>
      <table class="info">
        ${rows.map(([k, v]) => `<tr><td>${k}</td><td>: ${v}</td></tr>`).join('')}
      </table>
      <div class="section-title">&#9745; Kelengkapan Berkas — Diisi Petugas</div>
      <table class="berkas-table">
        <thead><tr><th style="width:36px;text-align:center;">&#10003;</th><th>Berkas</th><th>Keterangan</th></tr></thead>
        <tbody>
          ${berkas.map(b => `<tr><td class="centang">${b.on ? boxOn : boxOff}</td><td><strong>${b.label}</strong>${b.sub ? '<br><small>' + b.sub + '</small>' : ''}</td><td>${b.ket}</td></tr>`).join('')}
        </tbody>
      </table>
      <div class="daftar-ulang"><strong>&#9888; Penting:</strong> Jika siswa/siswi <strong>lulus seleksi</strong>, formulir ini wajib dibawa kembali saat <strong>daftar ulang</strong>. Formulir tanpa tanda tangan panitia tidak berlaku.</div>
      <p class="note">Bukti ini hanya sah sebagai tanda daftar dan bukan merupakan jaminan penerimaan.</p>
      <div class="footer">
        <div class="ttd"><div class="name-line">Orang Tua / Wali</div></div>
        <div class="ttd"><div class="name-line">Panitia SPMB</div></div>
      </div>`;

    // ── Gaya 2: Modern ─────────────────────────────────────────────
    const lembarModern = `
      <div class="tag">Gaya 2 — Modern</div>
      <div class="m-head">
        <div>
          <h2>${esc(SCH_NAMA)}</h2>
          <div class="m-addr">${esc(SCH_ALAMAT)}</div>
        </div>
        ${antri ? `<div class="m-antri"><div class="lbl">Loket</div><div class="num">${antri.nomor}</div>${antri.meja ? `<div class="loket">${antri.meja}</div>` : ''}</div>` : ''}
      </div>
      <div class="m-title">BUKTI TANDA DAFTAR SPMB &middot; T.P. ${tahunAjaran}</div>
      <div class="m-card">
        <div class="m-grid">
          ${rows.map(([k, v]) => `<div class="m-row"><span class="m-k">${k}</span><span class="m-v">${v}</span></div>`).join('')}
        </div>
      </div>
      <div class="m-sec">Kelengkapan Berkas <span style="font-weight:normal;opacity:.85;">— diisi petugas</span></div>
      <div class="m-berkas">
        ${berkas.map(b => `<div class="m-bitem"><div class="m-box ${b.on ? 'on' : ''}">${b.on ? '&#10003;' : ''}</div><div class="m-bmain"><strong>${b.label}</strong>${b.sub ? '<span class="m-bsub">' + b.sub + '</span>' : ''}</div><div class="m-bket">${b.ket}</div></div>`).join('')}
      </div>
      <div class="m-warn"><strong>&#9888; Penting:</strong> Jika lulus seleksi, formulir ini wajib dibawa saat <strong>daftar ulang</strong>. Tanpa tanda tangan panitia tidak berlaku.</div>
      <div class="m-foot">
        <div class="m-ttd"><div class="m-line"></div>Orang Tua / Wali</div>
        <div class="m-ttd"><div class="m-line"></div>Panitia SPMB</div>
      </div>`;

    const html = `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<title>Bukti Daftar SPMB</title>
<style>
  body{font-family:Arial,sans-serif;font-size:13px;margin:0;padding:24px;color:#111;}
  .tag{font-size:9px;color:#c4c4c4;letter-spacing:.5px;margin-bottom:6px;}
  /* Gaya Klasik */
  .header{text-align:center;border-bottom:3px double #333;padding-bottom:12px;margin-bottom:16px;position:relative;}
  .header h2{margin:4px 0;font-size:16px;text-transform:uppercase;letter-spacing:.5px;}
  .header p{margin:2px 0;font-size:12px;}
  .header .kop-img{width:100%;max-width:100%;display:block;margin:0 auto 4px;}
  .antri-box{position:absolute;top:0;right:0;background:#fff;border:2px solid #111;border-radius:6px;padding:6px 12px;text-align:center;min-width:96px;z-index:2;}
  .antri-box .lbl{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:#444;}
  .antri-box .num{font-size:20px;font-weight:800;line-height:1.1;}
  .antri-box .loket{font-size:10px;font-weight:bold;margin-top:2px;}
  table.info{width:100%;border-collapse:collapse;margin-bottom:16px;}
  table.info td{padding:4px 8px;vertical-align:top;}
  table.info td:first-child{width:170px;font-weight:bold;color:#333;}
  .section-title{font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px;
    background:#f0f0f0;padding:5px 8px;margin:16px 0 8px;border-left:3px solid #333;}
  .berkas-table{width:100%;border-collapse:collapse;margin-bottom:10px;font-size:12px;}
  .berkas-table th{background:#f0f0f0;padding:5px 8px;text-align:left;font-size:11px;border:1px solid #ccc;}
  .berkas-table td{padding:5px 8px;border:1px solid #ddd;vertical-align:middle;}
  .berkas-table td.centang{text-align:center;width:36px;}
  .berkas-box{display:inline-block;width:16px;height:16px;border:1.5px solid #333;text-align:center;line-height:15px;font-size:12px;font-weight:bold;}
  .berkas-box.on{background:#198754;border-color:#198754;color:#fff;}
  .status-ok{color:#198754;font-weight:700;}
  .status-fail{color:#dc3545;font-weight:700;}
  .daftar-ulang{border:1.5px solid #333;border-radius:4px;padding:7px 10px;font-size:11.5px;margin-bottom:14px;background:#fffbea;}
  .footer{margin-top:24px;display:flex;justify-content:space-between;}
  .ttd{text-align:center;width:200px;}
  .ttd .name-line{margin-top:56px;border-top:1px solid #333;padding-top:4px;font-size:11px;}
  .note{font-size:11px;color:#666;margin-bottom:12px;padding:6px 8px;border:1px dashed #bbb;border-radius:4px;}
  /* Gaya Modern */
  .m-head{background:linear-gradient(135deg,#1a3c34,#2f8258);color:#fff;padding:16px 18px;border-radius:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;}
  .m-head h2{margin:0;font-size:16px;text-transform:uppercase;letter-spacing:.4px;}
  .m-head .m-addr{font-size:11px;opacity:.9;margin-top:3px;max-width:380px;}
  .m-antri{background:#fff;color:#1a3c34;border-radius:8px;padding:6px 12px;text-align:center;min-width:90px;}
  .m-antri .lbl{font-size:8px;text-transform:uppercase;letter-spacing:.5px;color:#555;}
  .m-antri .num{font-size:18px;font-weight:800;line-height:1.1;}
  .m-antri .loket{font-size:10px;font-weight:bold;}
  .m-title{text-align:center;font-weight:bold;font-size:12px;letter-spacing:1px;color:#2f8258;margin:12px 0;}
  .m-card{border:1px solid #d9e2dd;border-radius:12px;padding:14px 16px;}
  .m-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;}
  .m-row{display:flex;flex-direction:column;border-bottom:1px dotted #e3e8e6;padding-bottom:4px;}
  .m-k{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:#8a978f;}
  .m-v{font-size:12px;font-weight:600;}
  .m-sec{background:#2f8258;color:#fff;font-weight:bold;font-size:12px;padding:6px 12px;border-radius:8px;margin:14px 0 8px;text-transform:uppercase;letter-spacing:.5px;}
  .m-berkas{display:flex;flex-direction:column;gap:6px;}
  .m-bitem{display:flex;align-items:center;gap:10px;border:1px solid #e3e8e6;border-radius:8px;padding:7px 10px;font-size:12px;}
  .m-box{width:18px;height:18px;border:1.5px solid #2f8258;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;flex-shrink:0;font-size:12px;}
  .m-box.on{background:#2f8258;}
  .m-bmain{flex:1;}
  .m-bsub{display:block;font-size:10px;color:#777;}
  .m-bket{text-align:right;min-width:150px;}
  .m-warn{margin-top:12px;background:#fff8e6;border:1px solid #f4d774;border-radius:8px;padding:8px 12px;font-size:11px;}
  .m-foot{display:flex;justify-content:space-between;margin-top:30px;}
  .m-ttd{text-align:center;width:200px;font-size:11px;}
  .m-ttd .m-line{border-top:1px solid #333;margin-top:48px;margin-bottom:4px;}
  .lembar.first{page-break-after:always;}
  .rangkap-label{font-size:9px;color:#888;letter-spacing:.5px;text-align:right;margin-bottom:4px;text-transform:uppercase;}
  @page{size:215mm 330mm;margin:0;}
  @media print{body{padding:14mm;}.rangkap-label{color:#aaa;}}
</style></head>
<body>
  <div class="lembar first"><div class="rangkap-label">Lembar 1 &mdash; Untuk Pendaftar</div>${lembarKlasik}</div>
  <div class="lembar"><div class="rangkap-label">Lembar 2 &mdash; Arsip Sekolah</div>${lembarKlasik}</div>
</body></html>`;
    // Cetak lewat iframe tersembunyi — tanpa popup "about:blank", auto-bersih setelah cetak
    const old = document.getElementById('printFrame');
    if (old) old.remove();
    const frame = document.createElement('iframe');
    frame.id = 'printFrame';
    frame.style.position = 'fixed';
    frame.style.right = '0';
    frame.style.bottom = '0';
    frame.style.width = '0';
    frame.style.height = '0';
    frame.style.border = '0';
    document.body.appendChild(frame);
    const fdoc = frame.contentWindow.document;
    fdoc.open();
    fdoc.write(html);
    fdoc.close();
    const doPrint = () => {
        frame.contentWindow.focus();
        frame.contentWindow.print();
        // Bersihkan iframe setelah dialog cetak selesai
        frame.contentWindow.onafterprint = () => frame.remove();
        setTimeout(() => { if (document.getElementById('printFrame')) frame.remove(); }, 60000);
    };
    if (frame.contentWindow.document.readyState === 'complete') {
        setTimeout(doPrint, 150);
    } else {
        frame.onload = () => setTimeout(doPrint, 150);
    }
}
