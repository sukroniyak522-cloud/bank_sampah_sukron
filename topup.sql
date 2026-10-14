-- =====================================================
-- Tambahan: tabel topup (jalankan di database bank_sampah_sukron)
-- Aman dijalankan tanpa menghapus data yang sudah ada.
-- phpMyAdmin > pilih database > tab SQL > tempel > Kirim
-- =====================================================

CREATE TABLE IF NOT EXISTS `topup` (
  `id_topup`     int(11) NOT NULL AUTO_INCREMENT,
  `id_user`      int(11) NOT NULL,
  `nominal`      decimal(15,2) NOT NULL,
  `keterangan`   varchar(100) NOT NULL DEFAULT 'Top up saldo',
  `dicatat_oleh` int(11) DEFAULT NULL,
  `tanggal`      datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_topup`),
  KEY `fk_topup_user` (`id_user`),
  KEY `fk_topup_admin` (`dicatat_oleh`),
  CONSTRAINT `fk_topup_user`  FOREIGN KEY (`id_user`)      REFERENCES `users` (`id_user`) ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `fk_topup_admin` FOREIGN KEY (`dicatat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_topup_positif` CHECK (`nominal` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
