<?php
// ARSIP — cabang rank_sort() untuk jalur G2 yang dihapus dari admin/pendaftar.php's
// sibling admin/ranking.php (dulu di dalam fungsi rank_sort, sesudah cabang 'g1').
// Dead code: rank_sort hanya pernah dipanggil dengan key 'g1'. Disimpan bila suatu
// saat sistem jalur G2 dihidupkan kembali.

        if ($key === 'g2_jarak') {
            $ja = isset($a['jarak_km']) && $a['jarak_km'] !== null ? (float)$a['jarak_km'] : 9999;
            $jb = isset($b['jarak_km']) && $b['jarak_km'] !== null ? (float)$b['jarak_km'] : 9999;
            if ($ja != $jb) return $ja <=> $jb;
            return $b['usia'] <=> $a['usia'];
        }
        if ($key === 'g2_abk') {
            $nka = (float)($a['nilai_khusus'] ?? 0);
            $nkb = (float)($b['nilai_khusus'] ?? 0);
            if ($nka != $nkb) return $nkb <=> $nka;
            return $b['usia'] <=> $a['usia'];
        }
        if ($key === 'g2_reguler') {
            $kel_ds = array_keys(KELURAHAN_ZONASI['Duren Sawit'] ?? []);
            $za = in_array($a['kelurahan'] ?? '', $kel_ds, true) ? 1 : 0;
            $zb = in_array($b['kelurahan'] ?? '', $kel_ds, true) ? 1 : 0;
            if ($za != $zb) return $zb <=> $za;
            if ((float)$a['nilai_akhir'] != (float)$b['nilai_akhir'])
                return (float)$b['nilai_akhir'] <=> (float)$a['nilai_akhir'];
            return $b['usia'] <=> $a['usia'];
        }
