<?php
declare(strict_types=1);

final class KasirController
{
    public function __construct(private Barang $barang)
    {
    }

    public function tampilkan(bool $ajax = false): void
    {
        $kodeCari = trim((string) ($_GET['kode_barang'] ?? $_SESSION['kode_terakhir'] ?? ''));
        if ($kodeCari !== '') {
            $_SESSION['kode_terakhir'] = $kodeCari;
        }

        $hasilCari = $kodeCari !== ''
            ? $this->barang->cariKode($kodeCari)
            : null;
        $isiKeranjang = $_SESSION['keranjang'] ?? [];
        $keranjang = $this->barang->ambilKeranjang($isiKeranjang);
        $jumlahBarangKeranjang = array_sum($isiKeranjang);
        $totalKeranjang = $this->hitungTotal($keranjang);
        $pesan = $_SESSION['pesan'] ?? null;
        $struk = $_SESSION['struk'] ?? null;
        unset($_SESSION['pesan'], $_SESSION['struk']);

        if ($ajax) {
            ob_start();
            require __DIR__ . '/../views/kasir/index.php';
            $html = ob_get_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['html' => $html], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        require __DIR__ . '/../views/kasir/index.php';
    }

    public function proses(array $input, bool $ajax = false): void
    {
        $aksi = (string) ($input['aksi'] ?? '');
        $keranjang = $_SESSION['keranjang'] ?? [];

        if ($aksi === 'tambah') {
            $kode = trim((string) ($input['kode_barang'] ?? ''));
            $jumlah = filter_var($input['jumlah'] ?? 1, FILTER_VALIDATE_INT);
            $barang = $this->barang->cariKode($kode);

            if ($barang === null) {
                $this->beriPesan('Barang tidak ditemukan. Cari kode barang terlebih dahulu.', 'error');
            } elseif ($jumlah === false || $jumlah < 1) {
                $this->beriPesan('Jumlah barang minimal 1.', 'error');
            } else {
                $idBarang = (int) $barang['id_barang'];
                $jumlahDiKeranjang = (int) ($keranjang[$idBarang] ?? 0);
                $jumlahBaru = $jumlahDiKeranjang + $jumlah;

                if ($jumlahBaru > (int) $barang['stok']) {
                    $this->beriPesan('Jumlah melebihi stok yang tersedia.', 'error');
                } else {
                    $keranjang[$idBarang] = $jumlahBaru;
                    $_SESSION['keranjang'] = $keranjang;
                    $this->beriPesan('Barang ditambahkan ke keranjang.', 'success');
                }
            }
        } elseif ($aksi === 'tambah_satu' || $aksi === 'kurangi') {
            $idBarang = filter_var($input['id_barang'] ?? null, FILTER_VALIDATE_INT);

            if ($idBarang === false) {
                $this->beriPesan('Barang tidak ditemukan di keranjang.', 'error');
            } else {
                $baris = $this->cariDiKeranjang($idBarang);
                if ($baris === null) {
                    $this->beriPesan('Barang tidak ada di keranjang.', 'error');
                } elseif ($aksi === 'tambah_satu') {
                    if ((int) $baris['jumlah'] >= (int) $baris['stok']) {
                        $this->beriPesan('Jumlah sudah mencapai stok yang tersedia.', 'error');
                    } else {
                        $_SESSION['keranjang'][$idBarang] = (int) $baris['jumlah'] + 1;
                        $this->beriPesan('Jumlah barang ditambah satu.', 'success');
                    }
                } elseif ((int) $baris['jumlah'] <= 1) {
                    unset($_SESSION['keranjang'][$idBarang]);
                    $this->beriPesan('Barang dihapus dari keranjang.', 'success');
                } else {
                    $_SESSION['keranjang'][$idBarang] = (int) $baris['jumlah'] - 1;
                    $this->beriPesan('Jumlah barang dikurangi satu.', 'success');
                }
            }
        } elseif ($aksi === 'hapus') {
            $idBarang = filter_var($input['id_barang'] ?? null, FILTER_VALIDATE_INT);
            if ($idBarang !== false) {
                unset($_SESSION['keranjang'][$idBarang]);
            }
            $this->beriPesan('Barang dihapus dari keranjang.', 'success');
        } elseif ($aksi === 'selesai') {
            $keranjang = $_SESSION['keranjang'] ?? [];

            if ($keranjang === []) {
                $this->beriPesan('Keranjang masih kosong.', 'error');
            } else {
                try {
                    $barisKeranjang = $this->barang->ambilKeranjang($keranjang);
                    $total = $this->hitungTotal($barisKeranjang);
                    $this->barang->selesaikanPenjualan($keranjang);
                    $_SESSION['struk'] = [
                        'barang' => $barisKeranjang,
                        'total' => $total,
                    ];
                    $_SESSION['keranjang'] = [];
                    $this->beriPesan('Transaksi berhasil. Stok barang sudah diperbarui.', 'success');
                } catch (RuntimeException $exception) {
                    $this->beriPesan($exception->getMessage(), 'error');
                } catch (PDOException $exception) {
                    $this->beriPesan('Transaksi gagal disimpan. Periksa koneksi database.', 'error');
                }
            }
        } else {
            $this->beriPesan('Aksi tidak dikenal.', 'error');
        }

        if ($ajax) {
            $this->tampilkan(true);
            return;
        }

        $kodeTerakhir = trim((string) ($_SESSION['kode_terakhir'] ?? ''));
        $tujuan = $kodeTerakhir !== ''
            ? './?kode_barang=' . rawurlencode($kodeTerakhir)
            : './';
        header('Location: ' . $tujuan, true, 303);
    }

    private function cariDiKeranjang(int $idBarang): ?array
    {
        $baris = $this->barang->ambilKeranjang($_SESSION['keranjang'] ?? []);
        foreach ($baris as $item) {
            if ((int) $item['id_barang'] === $idBarang) {
                return $item;
            }
        }

        return null;
    }

    private function hitungTotal(array $baris): float
    {
        $total = 0.0;
        foreach ($baris as $item) {
            $total += (float) $item['harga'] * (int) $item['jumlah'];
        }

        return $total;
    }

    private function beriPesan(string $teks, string $jenis): void
    {
        $_SESSION['pesan'] = [
            'teks' => $teks,
            'jenis' => $jenis,
        ];
    }
}
