-- DEEPNEST — skema dan data rujukan awal (tanpa akun dan tanpa data demo).
-- Impor melalui phpMyAdmin ke database KOSONG (utf8mb4_unicode_ci). Setelah itu buat admin pertama
-- melalui https://domain-anda/setup (isi SETUP_TOKEN di .env) atau php artisan deepnest:create-admin.
-- Dibuat: 2026-11-09 dari migrasi Laravel.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agenda_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agenda_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_stage_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `session_type` varchar(40) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `date_label` varchar(100) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `speakers` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'planned',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agenda_items_program_stage_id_foreign` (`program_stage_id`),
  CONSTRAINT `agenda_items_program_stage_id_foreign` FOREIGN KEY (`program_stage_id`) REFERENCES `program_stages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `agenda_items` DISABLE KEYS */;
INSERT INTO `agenda_items` VALUES
(1,1,'FGD Challenge Discovery · Hari I: Pemetaan Masalah','diskusi','2026-11-01',NULL,'08:00:00',NULL,'Unhas Hotel & Convention, Makassar','Paparan program DEEPNEST dan peran mitra sebagai challenge owner, paparan permasalahan setiap mitra, serta diskusi kelompok tematik pangan dan kesehatan.',NULL,'completed',1,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,1,'FGD Challenge Discovery · Hari II: Perumusan dan Kesepakatan Tantangan','diskusi','2026-11-02',NULL,'08:00:00',NULL,'Unhas Hotel & Convention, Makassar','Perumusan challenge statement, fungsi minimum, dan indikator keberhasilan; prioritisasi tantangan; kesepakatan peran dan kontribusi mitra; persiapan Open Call.',NULL,'completed',1,2,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(3,1,'Validasi lapangan ke mitra prioritas','kunjungan',NULL,'November 2026',NULL,NULL,'Lokasi mitra','Observasi proses kerja, wawancara pengguna, pemeriksaan data, dan peninjauan calon lokasi uji coba.',NULL,'planned',0,3,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(4,1,'FGD 2: Finalisasi tantangan','diskusi',NULL,'November 2026',NULL,NULL,'Makassar','Penetapan tantangan final, kesepakatan kontribusi mitra, dan kriteria seleksi peserta.',NULL,'planned',0,4,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(5,2,'Penyerahan tantangan final ke Open Call','publikasi',NULL,'Akhir November 2026',NULL,NULL,'Universitas Hasanuddin','Tantangan final dipublikasikan melalui laman DEEPNEST sebagai dasar Open Call peserta.',NULL,'planned',0,5,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(6,3,'Bootcamp (Hari 1)','workshop',NULL,'Bulan ke-5 program · tanggal menyusul',NULL,NULL,'Makassar','Pemahaman masalah, validasi kebutuhan pengguna, serta perancangan solusi dan spesifikasi teknis.',NULL,'planned',0,6,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(7,4,'Mentoring Sprint (Hari 2)','mentoring',NULL,'Bulan ke-5 program · tanggal menyusul',NULL,NULL,'Makassar','Pendampingan oleh mentor ahli, pengembangan purwarupa, pengujian awal, dan iterasi.',NULL,'planned',0,7,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(8,6,'Demo Day & Technical Review (Hari 3)','demo',NULL,'Bulan ke-5 program · tanggal menyusul',NULL,NULL,'Makassar','Presentasi dan demonstrasi purwarupa, evaluasi teknis dan potensi hilirisasi, serta seleksi inovasi prioritas.',NULL,'planned',0,8,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `agenda_items` ENABLE KEYS */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `body` longtext NOT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'pengumuman',
  `audience` varchar(20) NOT NULL DEFAULT 'public',
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcements_slug_unique` (`slug`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `announcements_is_published_audience_published_at_index` (`is_published`,`audience`,`published_at`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES
(1,'FGD Challenge Discovery DEEPNEST telah dilaksanakan','fgd-challenge-discovery-deepnest','Mitra pemerintah daerah, industri, koperasi, asosiasi, dan fasilitas kesehatan memaparkan permasalahan prioritas sebagai dasar tantangan DEEPNEST.','FGD Challenge Discovery Hackathon Deeptech DEEPNEST dilaksanakan pada 1–2 November 2026 di Unhas Hotel & Convention, Makassar.\n\nPada forum ini, mitra memaparkan permasalahan prioritas di bidang ketahanan pangan dan kesehatan, lalu bersama tim pelaksana merumuskan challenge statement, fungsi minimum, dan indikator keberhasilan.\n\nTahap berikutnya adalah validasi lapangan dan FGD 2 untuk menetapkan tantangan final yang akan diumumkan bersama pembukaan Open Call.','berita','public',0,0,NULL,NULL,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL);
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
DROP TABLE IF EXISTS `challenge_owners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_owners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'lainnya',
  `theme_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `initial_focus` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_position` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(32) DEFAULT NULL,
  `participation_status` varchar(32) NOT NULL DEFAULT 'invited',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `internal_notes` text DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `challenge_owners_slug_unique` (`slug`),
  KEY `challenge_owners_theme_id_foreign` (`theme_id`),
  CONSTRAINT `challenge_owners_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `challenge_owners` DISABLE KEYS */;
INSERT INTO `challenge_owners` VALUES
(1,'Pemerintah Kabupaten Maros','pemkab-maros','Pemkab Maros','pemerintah_daerah',1,NULL,'Produktivitas komoditas unggulan, efisiensi pengelolaan kawasan produksi, ketertelusuran hasil, serta konektivitas pelaku usaha, pemerintah daerah, dan pasar.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',1,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(2,'Pemerintah Kabupaten Pangkajene dan Kepulauan','pemkab-pangkep','Pemkab Pangkep','pemerintah_daerah',1,NULL,'Produktivitas komoditas unggulan wilayah daratan dan kepulauan, efisiensi kawasan produksi, ketertelusuran, distribusi antarpulau, dan akses pasar.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',2,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(3,'Pemerintah Kabupaten Sidenreng Rappang','pemkab-sidrap','Pemkab Sidrap','pemerintah_daerah',1,NULL,'Usulan awal: produktivitas komoditas pertanian dan peternakan unggulan, pengolahan hasil, rantai pasok, dan dukungan pasokan program Makan Bergizi Gratis.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',3,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(4,'Dinas Peternakan dan Kesehatan Hewan Provinsi Sulawesi Selatan','disnakkeswan-sulsel','Disnakkeswan Sulsel','instansi_pemerintah',1,NULL,'Pemantauan produksi dan kesehatan ternak, pengendalian mutu pakan, peningkatan layanan peternakan, dan pengembangan genetik ternak.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',4,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(5,'PT JAPFA Comfeed Indonesia Tbk.','japfa','JAPFA','industri',1,NULL,'Mutu pakan dan bahan baku, pengujian cepat, genetik unggul, dan efisiensi produksi.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',5,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(6,'PT Enersky Innovation Indonesia','enersky','Enersky','industri',1,NULL,'Teknologi pendukung: sensor dan IoT, otomasi, serta integrasi perangkat dan sistem pemantauan produksi.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',6,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(7,'PT Hadin Metavisi Akademika','hadin-metavisi','Hadin Metavisi','industri',1,NULL,'Teknologi pendukung: analitika data, kecerdasan buatan, pengembangan perangkat lunak, serta integrasi dan interoperabilitas sistem.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',7,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(8,'Koperasi Serikat Pekerja Merdeka Indonesia (KOSPERMINDO)','kospermindo','KOSPERMINDO','koperasi',1,NULL,'Kebutuhan pengguna pada unit budidaya dan pengolahan, mutu produk, serta pemasaran dan distribusi bagi anggota koperasi.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',8,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(9,'GAKESLAB Indonesia Wilayah Sulawesi Selatan','gakeslab-sulsel','GAKESLAB Sulsel','asosiasi',2,NULL,'Kebutuhan pasar alat kesehatan dan laboratorium, standar teknis, perizinan edar, serta kesiapan manufaktur dan distribusi.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',9,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(10,'Rumah Sakit Pendidikan Universitas Hasanuddin','rsp-unhas','RSP Unhas','fasilitas_kesehatan',2,NULL,'Alat kesehatan untuk deteksi dini, pemeriksaan, pemantauan pasien, serta efisiensi dan keselamatan layanan.',NULL,NULL,NULL,NULL,NULL,NULL,'invited',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',10,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(11,'HIPELKI','hipelki','HIPELKI','asosiasi',NULL,NULL,'Bidang dan fokus tantangan menunggu konfirmasi.',NULL,NULL,NULL,NULL,NULL,NULL,'pending_confirmation',0,'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan \"Tampilkan publik\" setelah ada izin mitra.',11,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL);
/*!40000 ALTER TABLE `challenge_owners` ENABLE KEYS */;
DROP TABLE IF EXISTS `challenge_theme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenge_theme` (
  `challenge_id` bigint(20) unsigned NOT NULL,
  `theme_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`challenge_id`,`theme_id`),
  KEY `challenge_theme_theme_id_foreign` (`theme_id`),
  CONSTRAINT `challenge_theme_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `challenge_theme_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `challenge_theme` DISABLE KEYS */;
INSERT INTO `challenge_theme` VALUES
(1,1),
(1,3),
(2,2),
(2,3),
(3,2),
(3,3),
(4,1),
(4,3),
(5,1),
(5,3),
(6,1),
(6,3),
(7,1),
(7,3),
(8,1),
(8,2),
(8,3),
(9,2),
(9,3);
/*!40000 ALTER TABLE `challenge_theme` ENABLE KEYS */;
DROP TABLE IF EXISTS `challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `challenges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint(20) unsigned NOT NULL,
  `challenge_owner_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `challenge_statement` text DEFAULT NULL,
  `problem_statement` text DEFAULT NULL,
  `affected_users` text DEFAULT NULL,
  `current_baseline` text DEFAULT NULL,
  `desired_outcome` text DEFAULT NULL,
  `minimum_functions` text DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `constraints` text DEFAULT NULL,
  `available_data` text DEFAULT NULL,
  `data_public` tinyint(1) NOT NULL DEFAULT 0,
  `pilot_location` text DEFAULT NULL,
  `pilot_location_public` tinyint(1) NOT NULL DEFAULT 0,
  `contact_info` text DEFAULT NULL,
  `contact_public` tinyint(1) NOT NULL DEFAULT 0,
  `internal_notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `challenges_slug_unique` (`slug`),
  KEY `challenges_theme_id_foreign` (`theme_id`),
  KEY `challenges_challenge_owner_id_foreign` (`challenge_owner_id`),
  KEY `challenges_status_theme_id_index` (`status`,`theme_id`),
  CONSTRAINT `challenges_challenge_owner_id_foreign` FOREIGN KEY (`challenge_owner_id`) REFERENCES `challenge_owners` (`id`) ON DELETE SET NULL,
  CONSTRAINT `challenges_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `challenges` DISABLE KEYS */;
INSERT INTO `challenges` VALUES
(1,1,1,'Produksi pangan stabil di tengah penyusutan lahan produktif','produksi-pangan-stabil-di-tengah-penyusutan-lahan-produktif','Inovasi agar produksi pangan tetap stabil saat lahan pertanian produktif menyusut akibat alih fungsi lahan dan kerusakan kawasan hulu.',NULL,'Kedekatan dengan Kota Makassar memicu migrasi penduduk dan alih fungsi lahan pertanian menjadi perumahan, terutama di kecamatan yang berbatasan dengan Makassar. Di kawasan hulu/pegunungan, pembukaan lahan merusak unsur tanah sehingga memicu banjir saat musim hujan dan kekeringan saat kemarau, yang berdampak pada penurunan produksi.\n\nPemerintah daerah telah menetapkan perlindungan lahan pangan pertanian berkelanjutan, namun krisis lahan tetap menjadi ancaman jangka panjang.','Petani, kelompok tani, dan perangkat daerah terkait.',NULL,'Produksi pangan tetap stabil di tengah penyusutan lahan produktif, termasuk dukungan penerapan pertanian perkotaan (urban farming) dan transisi mata pencaharian warga dalam 5–10 tahun.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,1,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(2,2,1,'Pemantauan kesehatan ibu hamil dan pencegahan stunting','pemantauan-kesehatan-ibu-hamil-dan-pencegahan-stunting','Solusi untuk meningkatkan kepatuhan pemeriksaan kehamilan dan mendukung penurunan stunting serta kematian ibu dan bayi.',NULL,'Angka kematian ibu melahirkan dan bayi dilaporkan meningkat, dan stunting masih menjadi fokus penanganan. Masalah utama adalah rendahnya kesadaran ibu hamil untuk rutin berkonsultasi ke fasilitas kesehatan meskipun penyuluhan telah diberikan.\n\nFasilitas rumah sakit tipe C di wilayah pegunungan juga terkendala kekurangan tenaga medis dan alat kesehatan.','Ibu hamil, kader dan tenaga kesehatan, puskesmas, serta rumah sakit daerah.',NULL,'Peningkatan pemantauan dan tindak lanjut kesehatan ibu hamil serta balita berisiko stunting.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,2,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(3,2,2,'Akses layanan kesehatan dari wilayah kepulauan','akses-layanan-kesehatan-dari-wilayah-kepulauan','Teknologi untuk menjembatani keterbatasan distribusi dan aksesibilitas layanan kesehatan antara pulau dan daratan.',NULL,'Kendala utama berada pada distribusi dan aksesibilitas menuju fasilitas kesehatan, terutama dari wilayah kepulauan ke daratan yang sulit dijangkau. Sebaran tenaga medis dan fasilitas pendukung di pulau sangat terbatas, sejalan dengan tingginya angka stunting dan tuberkulosis (TBC).','Warga kepulauan, tenaga kesehatan di pulau, dan dinas kesehatan.',NULL,'Deteksi dini, pemantauan, dan rujukan yang lebih cepat bagi warga kepulauan.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,3,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(4,1,2,'Perlindungan lahan pertanian produktif berkelanjutan','perlindungan-lahan-pertanian-produktif-berkelanjutan','Solusi untuk melindungi dan memantau lahan pertanian produktif secara berkelanjutan.',NULL,'Pemerintah daerah membutuhkan solusi untuk perlindungan lahan produktif secara berkelanjutan. Isu air bersih juga dicatat sebagai kebutuhan daerah dalam FGD.','Perangkat daerah bidang pertanian dan tata ruang, serta petani.',NULL,'Data dan alat bantu keputusan untuk perlindungan lahan pertanian produktif.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,4,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(5,1,4,'Intensifikasi reproduksi dan peningkatan populasi sapi','intensifikasi-reproduksi-dan-peningkatan-populasi-sapi','Teknologi pendukung inseminasi buatan, layanan betina produktif, kesehatan, dan nutrisi ternak.',NULL,'Tantangan utama berfokus pada peningkatan produksi ternak, intensifikasi alat produksi, dan program peningkatan populasi sapi, meliputi reproduksi ternak (inseminasi buatan dan layanan betina produktif), kesehatan ternak, dan nutrisi ternak. Dinas menyampaikan tersedianya dana pendamping.','Peternak, inseminator, dan petugas kesehatan hewan.',NULL,'Peningkatan keberhasilan reproduksi dan populasi sapi di Sulawesi Selatan.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,5,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(6,1,8,'Ketertelusuran dan mutu rumput laut pascapanen','ketertelusuran-dan-mutu-rumput-laut-pascapanen','Solusi ketertelusuran dan peningkatan mutu rumput laut pada tahap penanganan pascapanen.',NULL,'Koperasi telah mengembangkan platform aplikasi untuk komoditas rumput laut dengan fokus pada tahap penanganan pascapanen, dan membutuhkan solusi ketertelusuran serta peningkatan mutu rumput laut.','Pembudidaya rumput laut anggota koperasi dan pengelola koperasi.',NULL,'Rumput laut anggota koperasi tertelusur dan memenuhi standar mutu pembeli.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,6,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(7,1,7,'Distribusi benih jagung unggul hingga ke petani','distribusi-benih-jagung-unggul-hingga-ke-petani','Model dan teknologi untuk menjamin ketersediaan dan distribusi benih jagung unggul serta konsistensi mutu bagi petani.',NULL,'Pengembangan varietas jagung unggul hasil inovasi Unhas menargetkan produktivitas 5–7 ton per hektare dengan mutu yang konsisten untuk kebutuhan industri pakan dan bioetanol. Tantangan mencakup keunggulan varietas, proses litbang, jaminan ketersediaan benih, regulasi sertifikasi, manajemen sektor hulu, serta model bisnis yang efisien untuk mendistribusikan benih hingga ke petani.','Petani jagung, penangkar benih, dan industri pakan.',NULL,'Benih unggul tersedia tepat waktu di tingkat petani dengan mutu terjaga.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,7,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(8,3,NULL,'Drone untuk pertanian, pemantauan laut, dan distribusi alat kesehatan','drone-untuk-pertanian-pemantauan-laut-dan-distribusi-alat-kesehatan','Pemanfaatan drone untuk penaburan benih, pemetaan/pemantauan bawah laut, dan pengantaran alat kesehatan ke wilayah terpencil.',NULL,'Mitra penyedia teknologi drone menawarkan perakitan dan operasional drone, termasuk drone penabur benih, pemetaan/pemantauan bawah laut, serta pengantaran alat kesehatan ke tempat terpencil.','Petani, nelayan, dan fasilitas kesehatan di wilayah terpencil.',NULL,'Purwarupa layanan drone yang tervalidasi untuk kebutuhan pangan dan kesehatan di wilayah kepulauan.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra. Konfirmasi nama mitra penyedia teknologi drone.','draft',NULL,8,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL),
(9,2,10,'AI berbasis data untuk kebutuhan medis dan operasional rumah sakit','ai-berbasis-data-untuk-kebutuhan-medis-dan-operasional-rumah-sakit','Purwarupa kecerdasan buatan berbasis data untuk menunjang layanan medis dan operasional rumah sakit.',NULL,'Rumah sakit membutuhkan purwarupa kecerdasan buatan (AI) berbasis data untuk menunjang kebutuhan medis dan operasional, dengan tetap memenuhi aspek etik, keselamatan, validasi, dan perlindungan data pasien.','Tenaga kesehatan, manajemen rumah sakit, dan pasien.',NULL,'Layanan yang lebih cepat, akurat, aman, dan efisien.',NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.','draft',NULL,9,'2026-11-09 14:29:44','2026-11-09 14:29:44',NULL);
/*!40000 ALTER TABLE `challenges` ENABLE KEYS */;
DROP TABLE IF EXISTS `conflict_declarations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conflict_declarations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `proposal_id` bigint(20) unsigned DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conflict_declarations_reviewer_id_foreign` (`reviewer_id`),
  KEY `conflict_declarations_team_id_foreign` (`team_id`),
  KEY `conflict_declarations_proposal_id_foreign` (`proposal_id`),
  CONSTRAINT `conflict_declarations_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conflict_declarations_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conflict_declarations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `conflict_declarations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conflict_declarations` ENABLE KEYS */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'panduan',
  `file_path` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `visibility` varchar(20) NOT NULL DEFAULT 'admins',
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documents_slug_unique` (`slug`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'Apa itu DEEPNEST?','DEEPNEST (Deeptech Ecosystem for Emerging Prototype and Sustainable Technologies) adalah hackathon deep technology Universitas Hasanuddin bertema Ketahanan Pangan dan Kesehatan. Program berlangsung 12 bulan, dari perumusan tantangan bersama mitra hingga pilot, inkubasi, dan hilirisasi.','Umum',1,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,'Siapa yang dapat mendaftar?','Tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan/atau masyarakat inovator dari berbagai wilayah Indonesia yang memiliki gagasan, hasil riset, atau teknologi awal di bidang ketahanan pangan dan kesehatan.','Pendaftaran',2,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(3,'Kapan Open Call dibuka?','Tantangan final sedang difinalisasi bersama mitra. Jadwal Open Call akan diumumkan di halaman Pengumuman dan Linimasa. Anda sudah dapat membuat akun dan menyiapkan tim.','Pendaftaran',3,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(4,'Tema apa saja yang tersedia?','Tiga tema: **Ketahanan Pangan**, **Kesehatan**, dan **Hybrid**. Hybrid hanya untuk solusi yang secara sengaja menjawab kedua bidang atau memadukan kapabilitas keduanya, bukan pilihan cadangan.','Proposal',4,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(5,'Apakah saya harus memilih tantangan dari mitra?','Ya. Proposal harus disesuaikan dengan kebutuhan mitra pemilik masalah. Setelah memilih tema, portal menampilkan tantangan yang relevan untuk dipilih.','Proposal',5,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(6,'Bisakah proposal diubah setelah dikirim?','Tidak. Setelah dikirim, proposal terkunci. Panitia dapat membukanya kembali untuk revisi; perubahan tersebut tercatat dalam riwayat.','Proposal',6,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(7,'Apakah proposal saya akan dipublikasikan?','Tidak. Proposal dan berkas bersifat rahasia. Jika produk disetujui, panitia hanya menampilkan ringkasan publik (nama, gambar, deskripsi singkat, tema, dan tantangan). PDF proposal tetap privat kecuali panitia secara eksplisit membukanya.','Kerahasiaan',7,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(8,'Bagaimana proposal dinilai?','Proposal dinilai oleh reviewer/juri yang ditugaskan panitia menggunakan rubrik tertimbang per fase (Seleksi Open Call dan Demo Day). Reviewer wajib menyatakan konflik kepentingan, dan juri tidak menilai tim yang dibimbingnya.','Penilaian',8,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(9,'Apa yang diperoleh tim terpilih?','Tim terpilih mengikuti Bootcamp, Mentoring Sprint, dan Demo Day dengan pendampingan mentor internal dan mentor mitra. Purwarupa prioritas diarahkan ke pengembangan lanjutan, pilot di lingkungan mitra, perlindungan KI, inkubasi, dan hilirisasi.','Program',9,1,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
DROP TABLE IF EXISTS `mentor_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mentor_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `person_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  `proposal_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mentor_assignments_person_id_team_id_unique` (`person_id`,`team_id`),
  KEY `mentor_assignments_team_id_foreign` (`team_id`),
  KEY `mentor_assignments_proposal_id_foreign` (`proposal_id`),
  KEY `mentor_assignments_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `mentor_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mentor_assignments_person_id_foreign` FOREIGN KEY (`person_id`) REFERENCES `people` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mentor_assignments_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mentor_assignments_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `mentor_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `mentor_assignments` ENABLE KEYS */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_11_08_000100_create_themes_table',1),
(5,'2026_11_08_000110_create_challenge_owners_table',1),
(6,'2026_11_08_000120_create_challenges_table',1),
(7,'2026_11_08_000130_create_program_stages_table',1),
(8,'2026_11_08_000140_create_teams_table',1),
(9,'2026_11_08_000150_create_review_phases_table',1),
(10,'2026_11_08_000160_create_proposals_table',1),
(11,'2026_11_08_000170_create_proposal_children_tables',1),
(12,'2026_11_08_000180_create_review_tables',1),
(13,'2026_11_08_000190_create_people_tables',1),
(14,'2026_11_08_000200_create_content_tables',1),
(15,'2026_11_08_000210_create_audit_logs_and_settings_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `body` longtext NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_updated_by_foreign` (`updated_by`),
  CONSTRAINT `pages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'tentang','Tentang DEEPNEST','Deeptech Ecosystem for Emerging Prototype and Sustainable Technologies: hackathon deep technology Universitas Hasanuddin bertema Ketahanan Pangan dan Kesehatan.','## Mengapa DEEPNEST\n\nPermasalahan ketahanan pangan dan kesehatan di Indonesia Timur semakin kompleks akibat kondisi geografis kepulauan, panjangnya rantai distribusi, ketimpangan kapasitas produksi dan layanan, serta terbatasnya pemanfaatan teknologi pada budidaya, pengolahan, pengendalian mutu, pemasaran, dan pelayanan kesehatan.\n\nBerbagai solusi telah diterapkan, namun masih cenderung sektoral, terutama pada aspek pemantauan waktu nyata, otomatisasi, pengujian cepat, ketertelusuran, interoperabilitas data, dan validasi lapangan. Kebutuhan ini sejalan dengan Program Makan Bergizi Gratis dan Program Cek Kesehatan Gratis.\n\n## Dari tantangan nyata menuju inovasi berdampak\n\nDEEPNEST dirancang sebagai akselerator yang mempertemukan pemilik masalah, inovator, perguruan tinggi, industri, pemerintah, fasilitas pelayanan kesehatan, dan calon pengguna. Setiap tantangan berasal dari kebutuhan nyata mitra sebagai pemilik masalah (*challenge owner*).\n\nDEEPNEST tidak berhenti pada kompetisi. Program 12 bulan ini membangun jalur berkelanjutan dari validasi masalah, pengembangan purwarupa, pilot, perlindungan kekayaan intelektual, inkubasi, *business matching*, hingga adopsi dan hilirisasi teknologi oleh mitra.\n\n## Tujuan\n\n- Memvalidasi permasalahan berdasarkan kebutuhan pengguna.\n- Membentuk kolaborasi lintas disiplin.\n- Mengembangkan solusi menjadi purwarupa dengan indikator kinerja terukur.\n- Menguji kesesuaian solusi terhadap kondisi operasional, standar teknis, regulasi, dan kemampuan pengguna.\n- Menyiapkan produk menuju pilot dan hilirisasi.\n\n## Target luaran\n\n1. Terselenggaranya satu kegiatan Hackathon Deeptech DEEPNEST yang mengintegrasikan Challenge Discovery, Bootcamp, Mentoring Sprint, Prototype Development Sprint, Demo Day & Technical Review, serta pendampingan menuju pilot project dan hilirisasi.\n2. Sekurang-kurangnya 15 tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan/atau masyarakat inovator.\n3. Sekurang-kurangnya 3 purwarupa fungsional yang tervalidasi awal, menjawab kebutuhan challenge owner, dan siap memasuki tahap pilot project serta hilirisasi teknologi.\n\n## Metode pembinaan\n\nBimbingan teknis berlangsung selama 38 jam pelajaran (1 JP setara 45 menit) dengan pendekatan *problem-driven*, *experiential*, dan *stage-gated learning*. Pendampingan memakai skema mentor ganda: mentor internal memastikan ketepatan ilmiah, desain teknologi, pengujian, dan dokumentasi; mentor mitra menilai kesesuaian solusi dengan kebutuhan operasional, standar, biaya penerapan, kemampuan pengguna, dan peluang adopsi.\n\nPengembangan purwarupa melalui empat *stage gate*: validasi masalah dan spesifikasi minimum, *proof of concept*, integrasi dan pengujian fungsi, serta demonstrasi dan penyempurnaan berdasarkan umpan balik pengguna.\n\n## Penyelenggara\n\nDEEPNEST diselenggarakan oleh Universitas Hasanuddin melalui Direktorat Inovasi dan Kekayaan Intelektual, dalam rangka Program Hilirisasi Riset – Bantuan Pendanaan Penyelenggaraan Hackathon Deeptech dari Direktorat Hilirisasi dan Kemitraan, Direktorat Jenderal Riset dan Pengembangan, Kementerian Pendidikan Tinggi, Sains, dan Teknologi.\n\nKeberlanjutan hasil dikawal oleh Direktorat Inovasi dan Kekayaan Intelektual, Direktorat Hilirisasi dan Komersialisasi, serta Science Techno Park Universitas Hasanuddin.',1,NULL,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,'ketentuan','Ketentuan dan Kelayakan','Siapa yang dapat mengikuti DEEPNEST, prinsip proposal, kerahasiaan, dan penilaian.','## Siapa yang dapat mengikuti\n\nTim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan/atau masyarakat inovator dari berbagai wilayah Indonesia yang memiliki gagasan, hasil riset, atau teknologi awal di bidang ketahanan pangan dan kesehatan.\n\n## Prinsip proposal\n\n- Proposal berorientasi pada penyelesaian masalah nyata dan disesuaikan dengan kebutuhan mitra (*challenge owner*) yang telah berkomitmen.\n- Setiap proposal memilih satu tema utama: **Ketahanan Pangan**, **Kesehatan**, atau **Hybrid**. Hybrid hanya untuk solusi yang secara sengaja menjawab kedua bidang, bukan kategori cadangan.\n- Alur pengisian: pilih tema → lihat tantangan terkait → pilih tantangan → lengkapi proposal.\n- Proposal diisi secara terstruktur (identitas produk, tim, latar riset, aspek teknis, pasar dan dampak, kesesuaian tantangan, serta kesiapan legal, keselamatan, etik, dan data) dan dilengkapi gambar produk serta proposal resmi dalam format PDF.\n- Proposal yang telah dikirim tidak dapat disunting kecuali panitia membukanya kembali untuk revisi. Tema tidak dapat diganti setelah pengiriman.\n\n## Seleksi\n\nSeleksi dilakukan melalui Open Call dengan target 15 tim terpilih. Penilaian dilakukan oleh reviewer/juri yang ditugaskan panitia menggunakan rubrik tertimbang. Juri tidak menilai tim yang dibimbingnya dan wajib menyatakan konflik kepentingan.\n\n## Kerahasiaan\n\nIsian proposal, berkas PDF, dokumen legal, dan bukti pendukung bersifat rahasia dan hanya dapat diakses oleh tim pengusul, reviewer yang ditugaskan, dan admin. Untuk produk yang disetujui, panitia hanya menampilkan ringkasan publik yang telah ditinjau. PDF proposal tidak ditampilkan kecuali panitia secara eksplisit membukanya.\n\n## Ketentuan yang akan diumumkan\n\nJumlah maksimal anggota tim, jadwal dan tenggat Open Call, batas kata, format berkas, serta rubrik final akan diumumkan bersama pembukaan Open Call.',1,NULL,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(3,'privasi','Kebijakan Privasi','Bagaimana DEEPNEST mengumpulkan, menggunakan, dan melindungi data Anda.','## Data yang dikumpulkan\n\nSaat mendaftar dan mengajukan proposal, kami mengumpulkan nama, alamat surel, nomor telepon, institusi, kota/provinsi, bidang keahlian, data tim, isian proposal, serta berkas yang Anda unggah.\n\n## Tujuan penggunaan\n\nData digunakan untuk administrasi pendaftaran, seleksi dan penilaian, pendampingan, pelaporan program kepada pemberi dana, dan komunikasi terkait DEEPNEST.\n\n## Akses dan kerahasiaan\n\n- Isian proposal dan berkas hanya dapat diakses oleh tim Anda, reviewer yang ditugaskan, dan admin.\n- Nomor telepon dan surel peserta tidak pernah ditampilkan publik.\n- Nilai dan catatan reviewer tidak ditampilkan kepada peserta kecuali panitia mengaktifkannya.\n- Setiap akses ke berkas sensitif dicatat dalam log audit.\n- Ringkasan publik hanya ditampilkan untuk produk yang disetujui dan telah ditinjau panitia.\n\n## Penyimpanan\n\nBerkas disimpan di luar direktori publik server dan hanya disajikan melalui pemeriksaan hak akses. Data disimpan selama program berjalan dan selama diperlukan untuk pelaporan.\n\n## Hak Anda\n\nAnda dapat memperbarui profil melalui portal dan menghubungi panitia untuk pertanyaan atau permintaan terkait data pribadi.',1,NULL,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(4,'syarat','Syarat Penggunaan','Ketentuan penggunaan portal DEEPNEST.','## Akun\n\nSetiap pengguna bertanggung jawab atas kerahasiaan kata sandi dan kebenaran data yang diisikan. Satu akun digunakan oleh satu orang.\n\n## Isi proposal\n\nPeserta menjamin bahwa isi proposal adalah karya tim, tidak melanggar hak pihak lain, dan menyebutkan dengan jujur status kekayaan intelektual, perizinan, serta ketergantungan pada pihak ketiga.\n\n## Penggunaan yang dilarang\n\nDilarang mengunggah berkas berbahaya, mencoba mengakses data pengguna lain, atau menggunakan portal untuk tujuan di luar program DEEPNEST.\n\n## Keputusan panitia\n\nKeputusan seleksi dan publikasi ditetapkan oleh panitia sesuai ketentuan program. Hal-hal yang belum diatur akan ditetapkan kemudian oleh Ketua Tim Pelaksana.',1,NULL,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
DROP TABLE IF EXISTS `people`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `people` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `category` varchar(20) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `expertise` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `theme_id` bigint(20) unsigned DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `people_slug_unique` (`slug`),
  KEY `people_user_id_foreign` (`user_id`),
  KEY `people_theme_id_foreign` (`theme_id`),
  KEY `people_type_is_public_index` (`type`,`is_public`),
  CONSTRAINT `people_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `people_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `people` DISABLE KEYS */;
/*!40000 ALTER TABLE `people` ENABLE KEYS */;
DROP TABLE IF EXISTS `program_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(48) NOT NULL,
  `phase_number` tinyint(3) unsigned DEFAULT NULL,
  `phase_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `name_id` varchar(255) DEFAULT NULL,
  `month_label` varchar(64) DEFAULT NULL,
  `start_month` tinyint(3) unsigned DEFAULT NULL,
  `end_month` tinyint(3) unsigned DEFAULT NULL,
  `starts_on` date DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `output` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'upcoming',
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_stages_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `program_stages` DISABLE KEYS */;
INSERT INTO `program_stages` VALUES
(1,'challenge_discovery',1,'Formulasi Tantangan','Challenge Discovery','Identifikasi Tantangan','Bulan 1–2',1,2,NULL,NULL,'Tantangan dirumuskan bersama mitra sebagai pemilik masalah (challenge owner) melalui FGD, validasi lapangan, dan penyusunan challenge brief.','Identifikasi masalah bersama challenge owner\nPemetaan kebutuhan pengguna\nPerumusan challenge statement','Daftar tantangan prioritas dan kebutuhan teknologi','ongoing',1,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,'open_call',2,'Penjaringan Inovator','Open Call & Team Formation','Open Call dan Pembentukan Tim','Bulan 2–4',2,4,NULL,NULL,'Sosialisasi, pendaftaran, dan seleksi tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan masyarakat inovator.','Sosialisasi dan pendaftaran\nSeleksi peserta\nPembentukan dan penyempurnaan tim lintas disiplin','15 tim terpilih','upcoming',1,2,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(3,'bootcamp',3,'Hackathon & Pengembangan Purwarupa','Bootcamp','Bootcamp','Bulan 5 · Hari 1',5,5,NULL,NULL,'Penyamaan pemahaman masalah, validasi kebutuhan pengguna, dan perancangan solusi bersama mentor.','Pemahaman masalah\nValidasi kebutuhan pengguna\nPerancangan solusi dan spesifikasi teknis','Desain solusi tervalidasi','upcoming',1,3,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(4,'mentoring_sprint',3,'Hackathon & Pengembangan Purwarupa','Mentoring Sprint','Mentoring Sprint','Bulan 5 · Hari 2',5,5,NULL,NULL,'Pendampingan skema mentor ganda: mentor internal untuk ketepatan ilmiah dan mentor mitra untuk kesesuaian operasional.','Pendampingan oleh mentor ahli\nPengembangan purwarupa\nPengujian awal dan iterasi','Purwarupa fungsional (awal)','upcoming',1,4,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(5,'prototype_development_sprint',3,'Hackathon & Pengembangan Purwarupa','Prototype Development Sprint','Sprint Pengembangan Purwarupa','Bulan 5',5,5,NULL,NULL,'Pembuatan proof of concept, integrasi komponen, dan pengujian awal kinerja, akurasi, keamanan, keterulangan, serta kemudahan penggunaan.','Proof of concept dan integrasi komponen\nPengujian awal dan peer review\nDokumentasi perubahan dan mitigasi risiko','Purwarupa fungsional dan data uji awal','upcoming',1,5,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(6,'demo_day',3,'Hackathon & Pengembangan Purwarupa','Demo Day & Technical Review','Demo Day dan Telaah Teknis','Bulan 5 · Hari 3',5,5,NULL,NULL,'Presentasi dan demonstrasi purwarupa di hadapan juri dan mitra, dilanjutkan seleksi inovasi prioritas.','Presentasi dan demonstrasi purwarupa\nEvaluasi teknis, pengguna, dan potensi hilirisasi\nSeleksi inovasi prioritas','3 purwarupa prioritas','upcoming',1,6,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(7,'prototype_funding',4,'Pilot, Hilirisasi & Dampak','Prototype Funding & Pilot Preparation','Pendanaan Purwarupa dan Persiapan Pilot','Bulan 6–7',6,7,NULL,NULL,'Bantuan pengembangan purwarupa terpilih serta penyusunan lokasi, protokol, indikator, dan pembagian tanggung jawab pilot bersama mitra.','Penyempurnaan purwarupa\nPendanaan pengembangan\nPenyusunan protokol pilot\nAnalisis risiko dan kesiapan','Purwarupa siap pilot','upcoming',1,7,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(8,'pilot_project',4,'Pilot, Hilirisasi & Dampak','Pilot Project','Uji Coba Lapangan','Bulan 7–10',7,10,NULL,NULL,'Uji coba di lingkungan operasional mitra untuk menilai kinerja teknis, penerimaan pengguna, dan kesiapan integrasi.','Implementasi di lingkungan nyata\nPengujian kinerja\nEvaluasi bersama mitra pengguna','Hasil uji pilot dan rekomendasi adopsi','upcoming',1,8,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(9,'incubation_downstreaming',4,'Pilot, Hilirisasi & Dampak','Incubation & Downstreaming','Inkubasi dan Hilirisasi','Bulan 6–12',6,12,NULL,NULL,'Pendampingan perlindungan KI, standardisasi, model bisnis, business matching, dan akses pendanaan lanjutan.','Inkubasi teknologi\nPerlindungan kekayaan intelektual\nBusiness matching dan investasi\nPersiapan komersialisasi','Model bisnis, kemitraan, dan rencana hilirisasi','upcoming',1,9,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(10,'adoption_impact',4,'Pilot, Hilirisasi & Dampak','Adoption & Impact','Adopsi dan Dampak','Bulan 12+',12,12,NULL,NULL,'Adopsi produk oleh mitra melalui penggunaan langsung, pengadaan, lisensi, atau produksi bersama.','Adopsi oleh industri dan pengguna\nLisensi atau produksi bersama\nSkalasi dan keberlanjutan','Pemanfaatan teknologi dan dampak nyata','upcoming',1,10,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(11,'monitoring_evaluation',NULL,'Lintas fase','Monitoring, Evaluation & Reporting','Monitoring, Evaluasi, dan Pelaporan','Bulan 1–12',1,12,NULL,NULL,'Berjalan di setiap tahap untuk memastikan kualitas, akuntabilitas, dan pencapaian luaran.','Pemantauan jadwal dan capaian\nEvaluasi per tahap\nPelaporan program','Laporan kemajuan dan evaluasi program','ongoing',1,11,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `program_stages` ENABLE KEYS */;
DROP TABLE IF EXISTS `proposal_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposal_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `theme_question_id` bigint(20) unsigned NOT NULL,
  `answer` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_answers_proposal_id_theme_question_id_unique` (`proposal_id`,`theme_question_id`),
  KEY `proposal_answers_theme_question_id_foreign` (`theme_question_id`),
  CONSTRAINT `proposal_answers_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proposal_answers_theme_question_id_foreign` FOREIGN KEY (`theme_question_id`) REFERENCES `theme_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `proposal_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposal_answers` ENABLE KEYS */;
DROP TABLE IF EXISTS `proposal_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposal_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `type` varchar(32) NOT NULL,
  `disk` varchar(32) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_files_uuid_unique` (`uuid`),
  KEY `proposal_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `proposal_files_proposal_id_type_index` (`proposal_id`,`type`),
  CONSTRAINT `proposal_files_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proposal_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `proposal_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposal_files` ENABLE KEYS */;
DROP TABLE IF EXISTS `proposal_research_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposal_research_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `year` varchar(20) NOT NULL,
  `title` text NOT NULL,
  `researchers` text DEFAULT NULL,
  `funding_amount` decimal(15,2) DEFAULT NULL,
  `funding_source` varchar(255) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proposal_research_histories_proposal_id_foreign` (`proposal_id`),
  CONSTRAINT `proposal_research_histories_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `proposal_research_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposal_research_histories` ENABLE KEYS */;
DROP TABLE IF EXISTS `proposal_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposal_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(32) DEFAULT NULL,
  `to_status` varchar(32) NOT NULL,
  `note` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proposal_status_histories_proposal_id_foreign` (`proposal_id`),
  KEY `proposal_status_histories_changed_by_foreign` (`changed_by`),
  CONSTRAINT `proposal_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposal_status_histories_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `proposal_status_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposal_status_histories` ENABLE KEYS */;
DROP TABLE IF EXISTS `proposal_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposal_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposal_versions_proposal_id_version_unique` (`proposal_id`,`version`),
  KEY `proposal_versions_submitted_by_foreign` (`submitted_by`),
  CONSTRAINT `proposal_versions_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proposal_versions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `proposal_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposal_versions` ENABLE KEYS */;
DROP TABLE IF EXISTS `proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned NOT NULL,
  `theme_id` bigint(20) unsigned DEFAULT NULL,
  `challenge_id` bigint(20) unsigned DEFAULT NULL,
  `program_stage_id` bigint(20) unsigned DEFAULT NULL,
  `review_phase_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `short_summary` varchar(300) DEFAULT NULL,
  `tkt_level` tinyint(3) unsigned DEFAULT NULL,
  `prototype_status` varchar(40) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(32) DEFAULT NULL,
  `inventors` text DEFAULT NULL,
  `previous_work` text DEFAULT NULL,
  `existing_results` text DEFAULT NULL,
  `ip_publications` text DEFAULT NULL,
  `technical_description` text DEFAULT NULL,
  `technical_validation` text DEFAULT NULL,
  `technical_limitations` text DEFAULT NULL,
  `target_users` text DEFAULT NULL,
  `market_description` text DEFAULT NULL,
  `competitors` text DEFAULT NULL,
  `value_proposition` text DEFAULT NULL,
  `market_validation` text DEFAULT NULL,
  `impact_description` text DEFAULT NULL,
  `adoption_plan` text DEFAULT NULL,
  `problem_statement` text DEFAULT NULL,
  `challenge_importance` text DEFAULT NULL,
  `solution_description` text DEFAULT NULL,
  `minimum_functions` text DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `data_facility_needs` text DEFAULT NULL,
  `pilot_location` text DEFAULT NULL,
  `ip_status` varchar(40) DEFAULT NULL,
  `ip_notes` text DEFAULT NULL,
  `regulatory_status` varchar(40) DEFAULT NULL,
  `regulatory_notes` text DEFAULT NULL,
  `third_party_dependencies` text DEFAULT NULL,
  `safety_risks` text DEFAULT NULL,
  `data_risk_status` varchar(40) DEFAULT NULL,
  `data_notes` text DEFAULT NULL,
  `ethics_status` varchar(40) DEFAULT NULL,
  `ethics_notes` text DEFAULT NULL,
  `environmental_risks` text DEFAULT NULL,
  `mitigation_plan` text DEFAULT NULL,
  `demo_video_url` varchar(500) DEFAULT NULL,
  `repository_url` varchar(500) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `version` int(10) unsigned NOT NULL DEFAULT 0,
  `first_submitted_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `decision_note` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `unpublished_at` timestamp NULL DEFAULT NULL,
  `hidden_reason` text DEFAULT NULL,
  `public_slug` varchar(255) DEFAULT NULL,
  `public_pdf_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `public_title` varchar(255) DEFAULT NULL,
  `public_short_description` varchar(500) DEFAULT NULL,
  `public_full_description` text DEFAULT NULL,
  `public_problem` text DEFAULT NULL,
  `public_solution` text DEFAULT NULL,
  `public_target_users` text DEFAULT NULL,
  `public_technology` text DEFAULT NULL,
  `public_impact` text DEFAULT NULL,
  `public_image_path` varchar(255) DEFAULT NULL,
  `public_team_label` varchar(255) DEFAULT NULL,
  `public_show_team` tinyint(1) NOT NULL DEFAULT 0,
  `public_show_tkt` tinyint(1) NOT NULL DEFAULT 0,
  `public_show_links` tinyint(1) NOT NULL DEFAULT 0,
  `public_badge` varchar(32) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proposals_slug_unique` (`slug`),
  UNIQUE KEY `proposals_public_slug_unique` (`public_slug`),
  KEY `proposals_team_id_foreign` (`team_id`),
  KEY `proposals_challenge_id_foreign` (`challenge_id`),
  KEY `proposals_program_stage_id_foreign` (`program_stage_id`),
  KEY `proposals_review_phase_id_foreign` (`review_phase_id`),
  KEY `proposals_created_by_foreign` (`created_by`),
  KEY `proposals_approved_by_foreign` (`approved_by`),
  KEY `proposals_theme_id_status_index` (`theme_id`,`status`),
  KEY `proposals_is_public_published_at_index` (`is_public`,`published_at`),
  KEY `proposals_status_index` (`status`),
  CONSTRAINT `proposals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposals_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposals_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `proposals_program_stage_id_foreign` FOREIGN KEY (`program_stage_id`) REFERENCES `program_stages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposals_review_phase_id_foreign` FOREIGN KEY (`review_phase_id`) REFERENCES `review_phases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proposals_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`),
  CONSTRAINT `proposals_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `proposals` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposals` ENABLE KEYS */;
DROP TABLE IF EXISTS `review_criteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_criteria` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_phase_id` bigint(20) unsigned NOT NULL,
  `theme_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `max_score` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `weight` decimal(5,2) NOT NULL,
  `requires_comment` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_criteria_review_phase_id_foreign` (`review_phase_id`),
  KEY `review_criteria_theme_id_foreign` (`theme_id`),
  CONSTRAINT `review_criteria_review_phase_id_foreign` FOREIGN KEY (`review_phase_id`) REFERENCES `review_phases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_criteria_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `review_criteria` DISABLE KEYS */;
INSERT INTO `review_criteria` VALUES
(1,1,NULL,'Relevansi terhadap masalah nyata challenge owner','Seberapa tepat proposal menjawab tantangan mitra yang dipilih dan prioritas daerah/nasional.',5,15.00,0,1,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,1,NULL,'Kejelasan masalah dan pengguna','Akar masalah, pengguna terdampak, dan kondisi saat ini diuraikan jelas dan didukung data.',5,10.00,0,2,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(3,1,NULL,'Kebaruan dan potensi deeptech','Tingkat kebaruan teknologi dan kedalaman ilmiah/rekayasa yang dibawa.',5,10.00,0,3,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(4,1,NULL,'Kesiapan tim lintas disiplin','Komposisi keahlian tim sesuai kebutuhan solusi dan pengalaman relevan.',5,10.00,0,4,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(5,1,NULL,'Kelayakan teknis','Solusi realistis untuk menghasilkan purwarupa fungsional dalam siklus program.',5,15.00,0,5,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(6,1,NULL,'Bukti atau kesiapan purwarupa awal','Hasil riset, data uji, atau purwarupa awal yang sudah ada (TKT).',5,10.00,0,6,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(7,1,NULL,'Potensi dampak dan adopsi','Manfaat terukur bagi pengguna dan peluang diadopsi oleh mitra.',5,15.00,0,7,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(8,1,NULL,'Kesiapan keselamatan, legal, etik, dan data','Identifikasi isu KI, perizinan, keselamatan, etik, dan perlindungan data beserta mitigasinya. Ini menilai kesiapan, bukan menyatakan kepatuhan hukum final.',5,10.00,0,8,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(9,1,NULL,'Kualitas dan kelengkapan proposal','Kelengkapan isian, lampiran, dan kejelasan penyajian.',5,5.00,0,9,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(10,2,NULL,'Relevansi masalah','Purwarupa tetap menjawab masalah dan kebutuhan pengguna challenge owner.',5,10.00,0,1,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(11,2,NULL,'Bukti fungsi','Fungsi utama terdemonstrasi secara nyata, bukan sekadar simulasi.',5,15.00,0,2,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(12,2,NULL,'Kinerja teknis','Akurasi, keandalan, keterulangan, dan kinerja terhadap indikator keberhasilan.',5,15.00,0,3,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(13,2,NULL,'Validasi pengguna','Umpan balik dan uji awal bersama calon pengguna.',5,10.00,0,4,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(14,2,NULL,'Kesiapan keselamatan dan regulasi','Aspek keselamatan, standar teknis, dan jalur regulasi yang realistis.',5,10.00,0,5,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(15,2,NULL,'Kelayakan operasional','Kemudahan operasi, biaya, pemeliharaan, dan kesesuaian dengan proses kerja mitra.',5,10.00,0,6,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(16,2,NULL,'Dampak dan skalabilitas','Potensi manfaat luas dan kemampuan direplikasi di wilayah lain.',5,10.00,0,7,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(17,2,NULL,'Rencana implementasi dan hilirisasi','Rencana pilot, KI, model bisnis, dan jalur adopsi yang jelas.',5,10.00,0,8,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(18,2,NULL,'Kualitas demonstrasi dan pitch','Kejelasan penyampaian, demonstrasi, dan jawaban atas pertanyaan juri.',5,10.00,0,9,1,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `review_criteria` ENABLE KEYS */;
DROP TABLE IF EXISTS `review_phases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_phases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(48) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_phases_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `review_phases` DISABLE KEYS */;
INSERT INTO `review_phases` VALUES
(1,'open_call','Seleksi Open Call','Penyaringan proposal: relevansi, kesesuaian tantangan, kesiapan tim, dan kelengkapan proposal.',1,NULL,NULL,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,'demo_day','Demo Day & Technical Review','Penilaian purwarupa: fungsi, kelayakan teknis, keselamatan, dampak, kesiapan implementasi, dan potensi hilirisasi.',1,NULL,NULL,2,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `review_phases` ENABLE KEYS */;
DROP TABLE IF EXISTS `review_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` bigint(20) unsigned NOT NULL,
  `review_criterion_id` bigint(20) unsigned NOT NULL,
  `score` tinyint(3) unsigned DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `review_scores_review_id_review_criterion_id_unique` (`review_id`,`review_criterion_id`),
  KEY `review_scores_review_criterion_id_foreign` (`review_criterion_id`),
  CONSTRAINT `review_scores_review_criterion_id_foreign` FOREIGN KEY (`review_criterion_id`) REFERENCES `review_criteria` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_scores_review_id_foreign` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `review_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `review_scores` ENABLE KEYS */;
DROP TABLE IF EXISTS `reviewer_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviewer_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `review_phase_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'assigned',
  `due_at` timestamp NULL DEFAULT NULL,
  `conflict_reason` text DEFAULT NULL,
  `conflict_declared_at` timestamp NULL DEFAULT NULL,
  `override_note` text DEFAULT NULL,
  `removed_at` timestamp NULL DEFAULT NULL,
  `removed_by` bigint(20) unsigned DEFAULT NULL,
  `removal_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviewer_assignments_review_phase_id_foreign` (`review_phase_id`),
  KEY `reviewer_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `reviewer_assignments_removed_by_foreign` (`removed_by`),
  KEY `reviewer_assignments_reviewer_id_status_index` (`reviewer_id`,`status`),
  KEY `reviewer_assignments_proposal_id_review_phase_id_index` (`proposal_id`,`review_phase_id`),
  CONSTRAINT `reviewer_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviewer_assignments_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviewer_assignments_removed_by_foreign` FOREIGN KEY (`removed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviewer_assignments_review_phase_id_foreign` FOREIGN KEY (`review_phase_id`) REFERENCES `review_phases` (`id`),
  CONSTRAINT `reviewer_assignments_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reviewer_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviewer_assignments` ENABLE KEYS */;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reviewer_assignment_id` bigint(20) unsigned NOT NULL,
  `proposal_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `review_phase_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `recommendation` varchar(40) DEFAULT NULL,
  `overall_comment` text DEFAULT NULL,
  `private_notes` text DEFAULT NULL,
  `weighted_score` decimal(6,2) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `reopened_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_reviewer_assignment_id_unique` (`reviewer_assignment_id`),
  KEY `reviews_reviewer_id_foreign` (`reviewer_id`),
  KEY `reviews_review_phase_id_foreign` (`review_phase_id`),
  KEY `reviews_reopened_by_foreign` (`reopened_by`),
  KEY `reviews_proposal_id_review_phase_id_status_index` (`proposal_id`,`review_phase_id`,`status`),
  CONSTRAINT `reviews_proposal_id_foreign` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_reopened_by_foreign` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_review_phase_id_foreign` FOREIGN KEY (`review_phase_id`) REFERENCES `review_phases` (`id`),
  CONSTRAINT `reviews_reviewer_assignment_id_foreign` FOREIGN KEY (`reviewer_assignment_id`) REFERENCES `reviewer_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
DROP TABLE IF EXISTS `team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `discipline` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'member',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `joined_at` timestamp NULL DEFAULT NULL,
  `added_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_members_team_id_user_id_unique` (`team_id`,`user_id`),
  KEY `team_members_added_by_foreign` (`added_by`),
  KEY `team_members_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `team_members_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `team_members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `team_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_members` ENABLE KEYS */;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `leader_id` bigint(20) unsigned NOT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `profile` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(32) DEFAULT NULL,
  `join_code` varchar(16) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teams_slug_unique` (`slug`),
  UNIQUE KEY `teams_join_code_unique` (`join_code`),
  KEY `teams_leader_id_foreign` (`leader_id`),
  CONSTRAINT `teams_leader_id_foreign` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
DROP TABLE IF EXISTS `theme_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `theme_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint(20) unsigned DEFAULT NULL,
  `question` text NOT NULL,
  `help_text` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `word_limit` smallint(5) unsigned DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `theme_questions_theme_id_foreign` (`theme_id`),
  CONSTRAINT `theme_questions_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `theme_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `theme_questions` ENABLE KEYS */;
DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(16) NOT NULL DEFAULT '#0E6B4F',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `themes_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `themes` DISABLE KEYS */;
INSERT INTO `themes` VALUES
(1,'food_security','Ketahanan Pangan','Food Security','Budidaya dan produksi cerdas, otomasi dan analitika produksi, mutu pakan dan bahan baku, genetik unggul, pengolahan hasil pangan, perikanan, dan pertanian, serta pemasaran, distribusi, dan ketertelusuran untuk produktivitas dan rantai pasok yang berkelanjutan.','#3D6E46',1,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(2,'health','Kesehatan','Health','Alat kesehatan berteknologi tinggi untuk deteksi dini, pemeriksaan, dan pemantauan pasien; pelayanan kesehatan yang lebih cepat, aman, akurat, dan terjangkau; efisiensi operasional rumah sakit; serta sistem berbasis AI/data untuk kebutuhan medis dan operasional.','#A8402F',2,1,'2026-11-09 14:29:44','2026-11-09 14:29:44'),
(3,'hybrid','Hybrid','Hybrid','Solusi yang secara sengaja menjawab ketahanan pangan dan kesehatan sekaligus, atau memadukan kapabilitas kedua bidang dalam satu solusi terintegrasi. Hybrid bukan kategori cadangan bagi proposal yang jelas termasuk satu tema.','#3D4A7A',3,1,'2026-11-09 14:29:44','2026-11-09 14:29:44');
/*!40000 ALTER TABLE `themes` ENABLE KEYS */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'participant',
  `phone` varchar(32) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `discipline` varchar(255) DEFAULT NULL,
  `participant_type` varchar(32) DEFAULT NULL,
  `expertise` text DEFAULT NULL,
  `consented_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

