# discordgo-owoselfbot-v1

Selfbot farming OwO yang ditulis ulang dalam **Go**. Protokol Discord Gateway
(WebSocket) dan REST diimplementasi langsung di kode ini — bukan wrapper, bukan
pakai library Discord. Fokusnya farming Otomatis OwO.

> ## Peringatan
>
> **Selfbot melanggar ToS Discord dan berisiko ban.** Token akun memberi akses
> penuh ke akun kamu. Jangan pernah membagikan atau meng-commit `config.json`
> maupun `.env`. Gunakan dengan risiko sendiri.

---

## Fitur

**Gateway**
- ✅ Handshake IDENTIFY, heartbeat + ACK, RESUME, reconnect dengan backoff
- ✅ Kompresi zlib-stream (opsional)
- ✅ Subscribe guild (opcode 37) agar event `MESSAGE_CREATE` mengalir
- ✅ Presence `online` dikirim saat IDENTIFY

**Farming**
- ✅ Start/stop otomatis lewat `channelId` (hot-reload, tanpa restart)
- ✅ Siklus `owoh` → `owob` → `owo` → jeda 16–18 detik → `owo<action>` tiap 5 menit
- ✅ Mode slash (`prefix` = `/`): `/hunt` + `/battle`, menunggu balasan OwO
      dengan `nonce` yang sama

> [!CAUTION]
> **Khusus akun yang terkena rate limit.** Mode slash (`prefix` = `/`) ditujukan
> hanya untuk akun yang **tidak bisa mengirim pesan teks** dan hanya bisa memakai
> slash command. Di mode ini pesan teks `owoh`/`owob`/`owo`, `pray`/`action`, dan
> **auto-gems tidak berfungsi** — hanya `/hunt` + `/battle`.
>
> **Risiko:** solve captcha **gambar TIDAK bekerja** di mode slash. Captcha
> hCaptcha (ber-button) tetap diproses.

**Interaksi OwO**
- ✅ Duel: klik tombol `battle_accept` otomatis
- ✅ Auto-gems: gem habis → `owoinv` → pilih set gem → `owouse`

**Captcha**
- ✅ hCaptcha via captopia + cookie OAuth OwO
- ✅ Captcha gambar: unduh attachment → base64 → imagetotext → kirim via DM
- ✅ Alur captopia dua langkah: buat job (`POST /api/solve`) lalu polling
      (`GET /api/solve/{job_id}`) sampai `status` bukan `proses`
- ✅ Fallback: tunggu solve manual 9 menit

**Token & konfigurasi**
- ✅ Token otomatis dari sesi aplikasi Discord desktop
- ✅ Token manual via `.env` (cocok VPS / Termux / hosting) tanpa aplikasi Discord
- ✅ Data sensitif dipisah ke `.env` (tidak ikut ter-commit)
- ✅ Hot-reload `config.json`

**Ketahanan**
- ✅ Single-instance lock (dua proses tidak bisa jalan bersamaan)
- ✅ Deteksi double-loop (hunt <10 detik → stop + cooldown)
- ✅ Panic per handler dipulihkan, proses tidak ikut mati
- ✅ Penanganan error Discord: 429 (retry sesuai `retry_after`), 401/403, 404

---

## Prasyarat

- **Go 1.22 atau lebih baru**
- **Git**
- Akun Discord
- Opsional: API key [captopia.xyz](https://captopia.xyz) untuk captcha otomatis

---

## Build

```bash
git clone https://github.com/Pangleran/discordgo-owoselfbot-v1.git
cd discordgo-owoselfbot-v1
make build
# hasil: bin/discordgo-selfbot
```

Tanpa `make`:

```bash
go build -trimpath -ldflags "-s -w" -o bin/discordgo-selfbot ./cmd/bot
```

---

## Instalasi per lingkungan

### Termux (Android)

```bash
pkg update && pkg upgrade -y
pkg install -y git golang
termux-setup-storage   # opsional, agar bisa akses penyimpanan
```

Klon dan build:

```bash
git clone https://github.com/Pangleran/discordgo-owoselfbot-v1.git
cd discordgo-owoselfbot-v1
make build
```

Buat `.env` dan isi token (wajib — Termux tidak punya aplikasi Discord desktop):

```bash
cp .env.example .env
nano .env
```

```env
DISCORD_TOKEN=token_akun_kamu
DISCORD_ACCOUNT_ID=
CAPTOPIA_API_KEY=
```

Buat `config.json` dan isi `channelId`:

```bash
cp config.example.json config.json
nano config.json
```

Jalankan di latar belakang:

```bash
setsid -f ./bin/discordgo-selfbot -config config.json -gateway \
  >> bot.log 2>&1 < /dev/null
```

Pantau log:

```bash
tail -f bot.log
```

### Linux (desktop, GUI)

Install Git dan Go:

```bash
sudo apt update && sudo apt install -y git golang-go
```

Klon, build, dan siapkan konfigurasi:

```bash
git clone https://github.com/Pangleran/discordgo-owoselfbot-v1.git
cd discordgo-owoselfbot-v1
make build
cp config.example.json config.json
cp .env.example .env
```

Token:

- **Otomatis:** biarkan `DISCORD_TOKEN` di `.env` kosong. Bot membaca token dari
  sesi aplikasi Discord desktop yang sedang login.
- **Manual:** isi `DISCORD_TOKEN` di `.env`.

Jalankan (Discord akan dibuka otomatis ke `channelId` bila token otomatis):

```bash
./bin/discordgo-selfbot -config config.json -gateway
```

### VPS (Linux headless)

Sama seperti Linux, tetapi token **wajib** diisi manual di `.env` karena tidak
ada aplikasi Discord desktop.

```bash
sudo apt update && sudo apt install -y git golang-go
git clone https://github.com/Pangleran/discordgo-owoselfbot-v1.git
cd discordgo-owoselfbot-v1
make build
cp config.example.json config.json
cp .env.example .env
nano .env   # isi DISCORD_TOKEN
```

Rekomendasi: jalankan sebagai layanan **systemd user** agar otomatis restart.

```bash
mkdir -p ~/.config/systemd/user
cp deploy/discordgo-selfbot.service ~/.config/systemd/user/
```

Sesuaikan `WorkingDirectory` di unit bila repo tidak di
`~/Documents/discordgo-owoselfbot-v1`:

```bash
nano ~/.config/systemd/user/discordgo-selfbot.service
```

Aktifkan:

```bash
loginctl enable-linger "$USER"
systemctl --user daemon-reload
systemctl --user enable --now discordgo-selfbot
journalctl --user -u discordgo-selfbot -f
```

Alternatif cepat tanpa systemd:

```bash
setsid -f ./bin/discordgo-selfbot -config config.json -gateway \
  >> bot.log 2>&1 < /dev/null
```

### Hosting panel (Pterodactyl, dll)

1. Build binary Linux amd64 (di mesin lokal atau lewat egg bila tersedia Go):

   ```bash
   GOOS=linux GOARCH=amd64 make build
   ```

2. Unggah `bin/discordgo-selfbot` dan `config.json` ke server panel.
   Bila panel menyediakan Go, cukup unggah source lalu jalankan `make build`.

3. Isi data sensitif lewat **Environment Variables** panel (lebih disarankan,
   tidak membuat file):

   ```
   DISCORD_TOKEN=token_akun_kamu
   DISCORD_ACCOUNT_ID=
   CAPTOPIA_API_KEY=
   ```

   Alternatif: buat file `.env` dengan isi yang sama. Environment dari panel
   selalu menang atas isi file.

4. Isi `channelId` di `config.json`.

5. Set **Startup Command**:

   ```
   ./bin/discordgo-selfbot -config config.json -gateway
   ```

6. Jalankan dari console panel.

---

## Konfigurasi

Salin template:

```bash
cp config.example.json config.json
cp .env.example .env
```

**`config.json`** (tanpa rahasia):

| Field | Keterangan |
|---|---|
| `channelId` | ID channel farming. Kosong/`"-"` = idle |
| `prefix` | default `owo`; isi `/` untuk mode slash (`/hunt` + `/battle`) |
| `action` | command berkala, default `pray` |
| `statusgems` | aktifkan auto-gems |
| `solver.apiBase` | base captopia (default `https://captopia.xyz`) |

**`.env`** (rahasia):

| Variabel | Keterangan |
|---|---|
| `DISCORD_TOKEN` | token akun. Kosong = otomatis dari sesi Discord desktop; terisi = pakai langsung (VPS/Termux/hosting) |
| `DISCORD_ACCOUNT_ID` | opsional; kunci bot ke satu akun Discord |
| `CAPTOPIA_API_KEY` | API key captopia; kosongkan untuk captcha manual |

`config.json` dan `.env` ada di `.gitignore` dan **tidak akan ikut ter-commit**.

---

## Menjalankan & menghentikan

Farming dikendalikan lewat `channelId` di `config.json` (hot-reload, tanpa
restart):

- isi `channelId` dengan ID channel target → farming mulai
- kosongkan `channelId` (atau isi `"-"`) → farming berhenti

Mode bantu:

```bash
./bin/discordgo-selfbot -token-status                # cek akun yang dipakai
./bin/discordgo-selfbot -me                          # info akun via REST
./bin/discordgo-selfbot -send "owo h" -channel 123   # kirim satu pesan
./bin/discordgo-selfbot -version
```
