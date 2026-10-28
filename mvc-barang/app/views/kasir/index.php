<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(string|float|int $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Kasir Toko</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <main class="page-shell">
        <header class="page-header">
            <a class="brand" href="./">Kasir toko</a>
        </header>

        <div id="ajax-status" class="visually-hidden" role="status" aria-live="polite"></div>
        <section class="workspace" aria-labelledby="page-title">
            <div class="intro">
                <p class="eyebrow">TRANSAKSI / BARANG</p>
                <h1 id="page-title">Kasir</h1>
                <p class="intro-copy">Cari barang dengan kode, lalu tambahkan ke keranjang.</p>
            </div>

            <?php if ($pesan !== null): ?>
                <div class="notice notice-<?= e($pesan['jenis']) ?>" role="<?= $pesan['jenis'] === 'error' ? 'alert' : 'status' ?>" aria-live="polite" tabindex="-1">
                    <?= e($pesan['teks']) ?>
                </div>
            <?php endif; ?>

            <?php if ($struk !== null): ?>
                <section class="receipt-panel" aria-labelledby="receipt-title">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">TRANSAKSI SELESAI</p>
                            <h2 id="receipt-title">Ringkasan pembayaran</h2>
                        </div>
                    </div>
                    <div class="receipt-lines">
                        <?php foreach ($struk['barang'] as $item): ?>
                            <div class="receipt-line">
                                <span><?= e($item['nama_barang']) ?> × <?= (int) $item['jumlah'] ?></span>
                                <strong><?= rupiah((float) $item['harga'] * (int) $item['jumlah']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="receipt-total">
                        <span>Total</span>
                        <strong><?= rupiah($struk['total']) ?></strong>
                    </div>
                </section>
            <?php endif; ?>

            <div class="cashier-layout">
                <section class="search-panel" aria-labelledby="search-title">
                    <div class="section-heading">
                        <div>
                            <p class="eyebrow">LANGKAH 1</p>
                            <h2 id="search-title">Cari barang</h2>
                        </div>
                    </div>

                    <form class="search-form" action="./" method="get" data-ajax>
                        <label for="kode_barang">Kode barang</label>
                        <div class="search-controls">
                            <input
                                id="kode_barang"
                                name="kode_barang"
                                type="search"
                                value="<?= e($kodeCari) ?>"
                                placeholder="Contoh: BRG001"
                                autocomplete="off"
                                required
                            >
                            <button class="button button-primary" type="submit">Cari</button>
                        </div>
                    </form>

                    <?php if ($kodeCari !== ''): ?>
                        <?php if ($hasilCari === null): ?>
                            <div class="search-result search-empty" role="status">
                                <h3>Barang tidak ditemukan</h3>
                                <p>Periksa kembali kode <strong><?= e($kodeCari) ?></strong>.</p>
                            </div>
                        <?php else:
                            $idHasil = (int) $hasilCari['id_barang'];
                            $jumlahDalamKeranjang = (int) ($_SESSION['keranjang'][$idHasil] ?? 0);
                            $sisaSetelahKeranjang = (int) $hasilCari['stok'] - $jumlahDalamKeranjang;
                        ?>
                            <div class="search-result">
                                <div class="product-code"><?= e($hasilCari['kode_barang']) ?></div>
                                <h3><?= e($hasilCari['nama_barang']) ?></h3>
                                <p class="product-category"><?= e($hasilCari['kategori'] ?? 'Tanpa kategori') ?></p>
                                <div class="product-meta">
                                    <span>Harga</span>
                                    <strong><?= rupiah($hasilCari['harga']) ?></strong>
                                </div>
                                <div class="product-meta stock-meta">
                                    <span>Stok tersedia</span>
                                    <strong><?= (int) $hasilCari['stok'] ?> unit</strong>
                                </div>
                                <?php if ($jumlahDalamKeranjang > 0): ?>
                                    <p class="stock-note">
                                        <?= $jumlahDalamKeranjang ?> unit di keranjang, <?= $sisaSetelahKeranjang ?> unit tersisa setelah transaksi.
                                    </p>
                                <?php endif; ?>

                                <?php if ($sisaSetelahKeranjang > 0): ?>
                                    <form class="add-form" action="./" method="post" data-ajax>
                                        <input type="hidden" name="aksi" value="tambah">
                                        <input type="hidden" name="kode_barang" value="<?= e($hasilCari['kode_barang']) ?>">
                                        <button class="button button-add" id="add-to-cart" type="submit">Tambah ke keranjang</button>
                                    </form>
                                <?php else: ?>
                                    <p class="stock-empty">Semua stok barang ini sudah ada di keranjang.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="search-hint">
                            <p>Masukkan kode barang untuk melihat nama, harga, dan stoknya.</p>
                            <span>Contoh kode: BRG001, BRG002, BRG003</span>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="cart-panel" id="keranjang" aria-labelledby="cart-title">
                    <div class="section-heading cart-heading">
                        <div>
                            <p class="eyebrow">LANGKAH 2</p>
                            <h2 id="cart-title" tabindex="-1">Isi keranjang</h2>
                        </div>
                        <span class="cart-count"><?= (int) $jumlahBarangKeranjang ?> barang</span>
                    </div>

                    <?php if ($keranjang === []): ?>
                        <div class="cart-empty">
                            <h3>Keranjang masih kosong</h3>
                            <p>Cari barang memakai kode, lalu tekan tombol tambah ke keranjang.</p>
                        </div>
                    <?php else: ?>
                        <div class="cart-items">
                            <?php foreach ($keranjang as $item): ?>
                                <article class="cart-item">
                                    <div class="cart-item-main">
                                        <div>
                                            <span class="cart-code"><?= e($item['kode_barang']) ?></span>
                                            <h3><?= e($item['nama_barang']) ?></h3>
                                            <p><?= rupiah($item['harga']) ?> per unit</p>
                                        </div>
                                        <strong class="item-subtotal">
                                            <?= rupiah((float) $item['harga'] * (int) $item['jumlah']) ?>
                                        </strong>
                                    </div>
                                    <div class="cart-item-controls">
                                        <form class="quantity-form" action="./" method="post" data-ajax>
                                            <input type="hidden" name="id_barang" value="<?= (int) $item['id_barang'] ?>">
                                            <button
                                                class="quantity-step"
                                                id="kurangi-<?= (int) $item['id_barang'] ?>"
                                                type="submit"
                                                name="aksi"
                                                value="kurangi"
                                                aria-label="Kurangi satu <?= e($item['nama_barang']) ?>"
                                            >−</button>
                                            <output class="quantity-value" aria-label="Jumlah barang">
                                                <?= (int) $item['jumlah'] ?>
                                            </output>
                                            <button
                                                class="quantity-step"
                                                id="tambah-<?= (int) $item['id_barang'] ?>"
                                                type="submit"
                                                name="aksi"
                                                value="tambah_satu"
                                                aria-label="Tambah satu <?= e($item['nama_barang']) ?>"
                                                <?= (int) $item['jumlah'] >= (int) $item['stok'] ? 'disabled' : '' ?>
                                            >+</button>
                                        </form>
                                        <span class="available-stock">Stok: <?= (int) $item['stok'] ?></span>
                                        <form action="./" method="post" data-ajax>
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id_barang" value="<?= (int) $item['id_barang'] ?>">
                                            <button class="remove-button" id="hapus-<?= (int) $item['id_barang'] ?>" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-total">
                            <span>Total belanja</span>
                            <strong><?= rupiah($totalKeranjang) ?></strong>
                        </div>

                        <form action="./" method="post" data-ajax>
                            <input type="hidden" name="aksi" value="selesai">
                            <button class="button button-checkout" id="checkout-button" type="submit">Selesaikan transaksi</button>
                        </form>
                        <p class="checkout-note">Stok di database berkurang setelah transaksi diselesaikan.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
    <script src="assets/kasir.js" defer></script>
</body>
</html>
