<?php
declare(strict_types=1);

final class Barang
{
    public function __construct(private PDO $connection)
    {
    }

    public function cariKode(string $kodeBarang): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_barang, kode_barang, nama_barang, kategori, harga, stok
             FROM barang
             WHERE kode_barang = :kode_barang
             LIMIT 1'
        );
        $statement->execute([':kode_barang' => $kodeBarang]);
        $barang = $statement->fetch();

        return $barang ?: null;
    }

    public function ambilKeranjang(array $isiKeranjang): array
    {
        if ($isiKeranjang === []) {
            return [];
        }

        $idBarang = array_map('intval', array_keys($isiKeranjang));
        $parameter = implode(', ', array_fill(0, count($idBarang), '?'));
        $statement = $this->connection->prepare(
            "SELECT id_barang, kode_barang, nama_barang, kategori, harga, stok
             FROM barang
             WHERE id_barang IN ({$parameter})
             ORDER BY nama_barang"
        );
        $statement->execute($idBarang);
        $barang = $statement->fetchAll();

        foreach ($barang as &$item) {
            $item['jumlah'] = (int) ($isiKeranjang[$item['id_barang']] ?? 0);
        }
        unset($item);

        return $barang;
    }

    public function selesaikanPenjualan(array $isiKeranjang): void
    {
        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare(
                'UPDATE barang
                 SET stok = stok - ?
                 WHERE id_barang = ? AND stok >= ?'
            );

            foreach ($isiKeranjang as $idBarang => $jumlah) {
                $statement->execute([$jumlah, $idBarang, $jumlah]);

                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Stok berubah atau tidak cukup. Periksa kembali keranjang.'
                    );
                }
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }
}
