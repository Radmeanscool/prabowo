# Daftar Uji (QA) DEEPNEST

Uji otomatis: `php artisan test` (87 uji). Kolom **Uji otomatis** menunjukkan kelas/metode yang mencakup butir.
Lakukan uji manual berikut di server *staging* sebelum Open Call dibuka, di desktop **dan** ponsel.

## A. Kriteria penerimaan (spesifikasi)

| # | Kriteria | Uji otomatis | Manual |
| --- | --- | --- | :---: |
| 1 | Pengunjung memahami DEEPNEST dan menjelajah tantangan publik | `PublicSiteTest::test_visitor_can_understand_deepnest`, `test_draft_challenges_are_not_public_until_published` | ☐ |
| 2 | Pengunjung hanya melihat produk yang dipublikasikan | `PublicSiteTest::test_only_published_products_are_listed` | ☐ |
| 3 | Peserta mendaftar dan membuat/bergabung tim | `AuthAndRoleTest::test_participant_can_register…`, `TeamTest::*` | ☐ |
| 4 | Tim memilih tantangan dan menyimpan draf | `ProposalSubmissionTest::test_theme_then_challenge_flow_creates_draft`, `test_participant_saves_structured_draft` | ☐ |
| 5 | Tim mengunggah gambar produk dan PDF | `FileSecurityTest::test_valid_image_and_pdf_are_stored_privately…` | ☐ |
| 6 | Formulir mendukung isian teknis, pasar, kesesuaian tantangan, dan kesiapan | `ProposalSubmissionTest::test_participant_saves_structured_draft` | ☐ |
| 7 | Proposal terkirim tidak publik | `DecisionAndPublicationTest::test_submitted_proposal_is_not_public` | ☐ |
| 8 | Reviewer hanya melihat proposal yang ditugaskan | `ReviewerFlowTest::test_reviewer_sees_only_assigned_proposals` | ☐ |
| 9 | Reviewer dapat menyatakan konflik kepentingan | `ReviewerFlowTest::test_conflict_declaration_revokes_access…` | ☐ |
| 10 | Reviewer menyimpan dan mengirim penilaian per fase | `ReviewerFlowTest::test_reviewer_saves_draft_then_submits…` | ☐ |
| 11 | Skor tertimbang dihitung di server | `ReviewerFlowTest::test_weighted_score_uses_criterion_weights`, `Unit\ScoreCalculatorTest` | ☐ |
| 12 | Admin dapat minta revisi, daftar pendek, setujui, tolak, pilih finalis | `DecisionAndPublicationTest::test_admin_decisions_follow_allowed_transitions`, `test_rejection_records_reason…` | ☐ |
| 13 | Persetujuan otomatis menampilkan ringkasan publik tanpa langkah kedua | `DecisionAndPublicationTest::test_approval_automatically_publishes…` | ☐ |
| 14 | Ringkasan publik otomatis tidak membuka PDF privat | `test_approval_automatically_publishes_safe_summary_without_pdf` | ☐ |
| 15 | Admin dapat membuka akses PDF publik secara terpisah | `test_admin_can_enable_public_pdf_separately` | ☐ |
| 16 | Admin dapat menyembunyikan produk tanpa menghapus proposal/penilaian | `test_unpublish_hides_product_without_deleting_history` | ☐ |
| 17 | Produk tersembunyi tidak dapat diakses dari URL publik | `test_unpublish_hides_product…`, `test_rejecting_approved_product_unpublishes_it` | ☐ |
| 18 | Berkas privat dilindungi kebijakan otorisasi | `FileSecurityTest::test_private_file_access_rules_per_role`, `test_internal_admin_files_are_hidden…` | ☐ |
| 19 | Catatan dan skor reviewer tidak terlihat peserta secara bawaan | `ReviewerFlowTest::test_reviewer_notes_and_scores_hidden_from_participants_by_default` | ☐ |
| 20 | Perubahan status dan publikasi tercatat di audit | assersi `AuditLog` di `ProposalSubmissionTest`, `DecisionAndPublicationTest`, `ReviewerFlowTest` | ☐ |
| 21 | Situs berfungsi di layar ponsel | Uji tangkapan layar 390 px tanpa gulir horizontal (manual) | ☐ |
| 22 | Dapat dipasang di Biz Corporate tanpa Supabase | `deepnest:check`, paket rilis, impor `deepnest-install.sql` | ☐ |

## B. Uji keamanan tambahan

| Uji | Uji otomatis | Manual |
| --- | --- | :---: |
| Berkas PHP/EXE/SVG dan PDF palsu ditolak | `FileSecurityTest::test_executable_and_disguised_uploads_are_rejected` | ☐ |
| Berkas melebihi batas ukuran ditolak | `FileSecurityTest::test_oversized_upload_is_rejected` | ☐ |
| URL berkas tanpa hak → 403/404/redirect login | `FileSecurityTest::test_private_file_access_rules_per_role`, `test_unknown_file_identifier_returns_404` | ☐ |
| Pengiriman ganda diblokir; proposal terkirim terkunci | `ProposalSubmissionTest::test_submitted_proposal_is_locked…` | ☐ |
| Eskalasi peran lewat pendaftaran/profil gagal | `AuthAndRoleTest::test_registration_cannot_escalate_role`, `test_participant_cannot_change_role_via_profile` | ☐ |
| Peserta tidak dapat menyisipkan status/publikasi lewat formulir | `ProposalSubmissionTest::test_status_and_publication_fields_cannot_be_injected_by_participant` | ☐ |
| Peran tidak dapat mengakses dashboard peran lain | `AuthAndRoleTest::test_each_role_only_reaches_its_own_dashboard` | ☐ |
| Reviewer tidak dapat mengubah status/publikasi | `ReviewerFlowTest::test_reviewer_cannot_change_status_or_publication` | ☐ |
| Konflik kepentingan memblokir penugasan | `AssignmentTest::*` | ☐ |
| Markdown tidak dapat menyisipkan skrip | `PublicSiteTest::test_markdown_content_is_sanitized` | ☐ |
| Header keamanan terpasang | `PublicSiteTest::test_security_headers_are_present` | ☐ |
| `/setup` hanya tersedia dengan token dan sebelum ada admin | `AuthAndRoleTest::test_setup_wizard_only_available…` | ☐ |
| Setiap halaman setiap peran dapat dirender | `SmokeTest::*` | ☐ |
| Beranda hanya menampilkan informasi terverifikasi (status dari data, target diberi label, tanpa bagian kosong) | `PublicSiteTest::test_homepage_shows_only_verifiable_information` | ☐ |
| Agenda rencana tidak tampil sebelum ditayangkan admin | `PublicSiteTest::test_planned_agenda_items_stay_private_until_admin_publishes_them` | ☐ |
| Bagian dan tautan Produk muncul setelah ada produk disetujui | `PublicSiteTest::test_product_section_and_navigation_appear_once_a_product_is_approved` | ☐ |

## C. Uji manual per peran (staging)

**Pengunjung:** beranda tampil benar di ponsel; menu ponsel terbuka/tertutup; tautan Tantangan, Tahapan,
Linimasa, Agenda, Pengumuman, FAQ berfungsi; produk tersembunyi memberi 404; sebelum ada produk disetujui
tidak ada bagian maupun tautan "Produk"; tidak ada angka atau nama lembaga yang belum terkonfirmasi.

**Peserta:** daftar → buat tim → bagikan kode → anggota lain bergabung → ketua menyetujui → pilih tema
(tantangan berubah sesuai tema) → pilih tantangan → isi semua bagian (penghitung kata) → simpan draf →
unggah gambar & PDF → layar konfirmasi → kirim (anggota non-ketua tidak bisa) → status & versi tampil →
coba ubah setelah kirim (harus terkunci).

**Reviewer:** masuk → hanya proposal ditugaskan → buka rubrik → simpan draf → kirim final → coba ubah
(terkunci) → nyatakan konflik pada penugasan lain (akses hilang).

**Admin:** atur Open Call di Pengaturan → tugaskan reviewer (tunggal dan massal; cek peringatan institusi)
→ minta revisi (peserta melihat catatan) → setujui (produk langsung tampil, PDF tidak) → buka PDF publik
→ cabut publikasi (URL 404) → terbitkan ulang → ekspor CSV (buka di Excel dengan pemisah titik koma) →
periksa Log Audit untuk semua aksi di atas.

**Hosting:** HTTPS aktif; `APP_DEBUG=false`; akses `https://domain/.env` dan `https://domain/storage/...`
harus 403/404; unggah PDF 10 MB berhasil; `SETUP_TOKEN` sudah dikosongkan.
