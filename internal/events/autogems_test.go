package events

import (
	"strings"
	"testing"
)

// inventory membangun teks inventory palsu dari daftar kode gem.
func inventory(gems ...string) string {
	parts := make([]string, len(gems))
	for i, g := range gems {
		parts[i] = "`" + g + "`"
	}
	return "**tester** inventory\n" + strings.Join(parts, " ")
}

// TestPickGemsPilihTerbesar memastikan tiap bentuk memakai tingkat tertinggi.
// Regresi: versi lama berurutan dari terkecil (051 lebih dulu).
func TestPickGemsPilihTerbesar(t *testing.T) {
	got := pickGems(inventory("051", "052", "053", "054", "055", "056", "057",
		"065", "066", "067", "068", "069", "070", "071",
		"072", "073", "074", "075", "076", "077", "078"))
	want := []string{"057", "071", "078"}
	if strings.Join(got, " ") != strings.Join(want, " ") {
		t.Fatalf("harus pilih terbesar: got %v want %v", got, want)
	}
}

// TestPickGemsSubstitusiPerBentuk memastikan bila satu tingkat habis, tingkat
// di bawahnya dipakai untuk bentuk itu saja, tanpa memengaruhi bentuk lain.
func TestPickGemsSubstitusiPerBentuk(t *testing.T) {
	// 057 habis -> 056. 071 ada. 078 habis -> 077.
	got := pickGems(inventory("056", "071", "077"))
	want := []string{"056", "071", "077"}
	if strings.Join(got, " ") != strings.Join(want, " ") {
		t.Fatalf("substitusi salah: got %v want %v", got, want)
	}
}

// TestPickGemsContohPengguna adalah contoh dari permintaan: 065 habis tetapi
// 066 ada, sehingga pasangannya menjadi 51 66 72 bila hanya itu yang tersedia.
func TestPickGemsContohPengguna(t *testing.T) {
	got := pickGems(inventory("051", "066", "072"))
	want := []string{"051", "066", "072"}
	if strings.Join(got, " ") != strings.Join(want, " ") {
		t.Fatalf("got %v want %v", got, want)
	}
}

// TestPickGemsBentukHilang mengembalikan nil bila satu bentuk tidak punya gem.
func TestPickGemsBentukHilang(t *testing.T) {
	// Tanpa gem hati sama sekali.
	if got := pickGems(inventory("051", "065")); got != nil {
		t.Fatalf("harus nil bila satu bentuk kosong, dapat %v", got)
	}
}

// TestPickGemsTidakTertukar memastikan gem bentuk lain tidak dipakai sebagai
// pengganti (mis. 072 tidak boleh menggantikan 065).
func TestPickGemsTidakTertukar(t *testing.T) {
	// 065..071 kosong, tapi 072 ada. Tidak boleh memakai 072 sbg bulat.
	if got := pickGems(inventory("051", "072", "073")); got != nil {
		t.Fatalf("bentuk tidak boleh tertukar, dapat %v", got)
	}
}

// TestPickGemsKosong menangani inventory tanpa gem.
func TestPickGemsKosong(t *testing.T) {
	if got := pickGems("**tester** inventory\nkosong"); got != nil {
		t.Fatalf("harus nil, dapat %v", got)
	}
}

// realInventory adalah salinan inventory pengguna (dipotong ke bagian gem).
// Diamond hanya sampai 055, jadi tier tertinggi yang dipakai 055 — bukan 057.
const realInventory = "**====== また's Inventory ======**\n" +
	"`051`<:cgem1:492572122120585240>\u00b3\u2074\u2077    " +
	"`052`<:ugem1:492572122514980864>\u00b3\u2074\u2075    " +
	"`053`<:rgem1:492572122888011776>\u00b2\u2075\u2078    " +
	"`054`<:egem1:492572122477101056>\u00b9\u2070\u2078    " +
	"`055`<:mgem1:492572122590478356>\u2070\u2070\u2077    " +
	"`065`<:cgem3:510366792024195072>\u00b3\u2075\u2075    " +
	"`066`<:ugem3:510366792095367189>\u00b3\u2078\u2079    " +
	"`067`<:rgem3:510366792653340674>\u00b2\u2077\u2078    " +
	"`068`<:egem3:510366792800272394>\u00b9\u00b2\u2074    " +
	"`069`<:mgem3:510366792447819777>\u2070\u00b9\u2077    " +
	"`070`<a:lgem3:510366794729652224>\u2070\u2070\u2077    " +
	"`071`<a:fgem3:510366794792566785>\u2070\u2070\u00b2    " +
	"`072`<:cgem4:510366763255463936>\u00b3\u2074\u2077    " +
	"`073`<:ugem4:510366764249382922>\u2074\u2070\u2079    " +
	"`074`<:rgem4:510366763884478464>\u00b2\u2077\u2070    " +
	"`075`<:egem4:510366763972558848>\u2070\u2075\u2075    " +
	"`076`<:mgem4:510366763993661440>\u2070\u2070\u2076    " +
	"`077`<a:lgem4:510366765826572295>\u2070\u00b9\u2078    " +
	"`078`<a:fgem4:510366765340033025>\u2070\u2070\u00b3    " +
	"`079`<:cstar:1101731000721096744>\u2070\u2070\u2079    " +
	"`084`<a:lstar:1101731005473230880>\u2070\u2070\u00b2"

// TestPickGemsInventoryAsli mengunci pilihan pada inventory nyata: diamond
// tertinggi yang dimiliki 055, bulat 071, hati 078.
func TestPickGemsInventoryAsli(t *testing.T) {
	got := pickGems(realInventory)
	want := []string{"055", "071", "078"}
	if strings.Join(got, " ") != strings.Join(want, " ") {
		t.Fatalf("got %v want %v", got, want)
	}
}

// TestPickGemsStarDilewatkan memastikan star (079..084) tidak ikut dipasangkan,
// karena itu gem event yang dipasang manual.
func TestPickGemsStarDilewatkan(t *testing.T) {
	got := pickGems(realInventory)
	for _, g := range got {
		if g >= "079" {
			t.Fatalf("star tidak boleh dipakai: %v", got)
		}
	}
}

// huntLine membangun teks pesan hunt dengan bentuk gem yang aktif.
func huntLine(slots ...string) string {
	m := map[string]string{
		"1": "<a:lgem1:492572124251422720>`[3/100]`",
		"3": "<a:fgem3:510366794792566785>`[224/1000]`",
		"4": "<a:fgem4:510366765340033025>`[224/1000]`",
	}
	var parts []string
	for _, s := range []string{"1", "3", "4"} {
		for _, want := range slots {
			if s == want {
				parts = append(parts, m[s])
			}
		}
	}
	return "**tester**, hunt is empowered by " + strings.Join(parts, " ") + " !"
}

// TestActiveSlotsMembacaBentuk memastikan slot dibaca dari emoji gem1/gem3/gem4.
func TestActiveSlotsMembacaBentuk(t *testing.T) {
	cases := []struct {
		name string
		text string
		want string
	}{
		{"lengkap", huntLine("1", "3", "4"), "1,3,4"},
		{"diamond hilang", huntLine("3", "4"), "3,4"},
		{"hanya bulat", huntLine("3"), "3"},
		{"tanpa empowered", "You found: bee", ""},
	}
	for _, c := range cases {
		got := activeSlots(c.text)
		keys := make([]string, 0, len(got))
		for _, s := range []string{"1", "3", "4"} {
			if got[s] {
				keys = append(keys, s)
			}
		}
		if strings.Join(keys, ",") != c.want {
			t.Errorf("%s: got %q want %q", c.name, strings.Join(keys, ","), c.want)
		}
	}
}

// TestPickGemsForSebagian memastikan hanya bentuk yang diminta yang dipilih,
// sehingga perintah use bisa berisi satu atau dua gem.
func TestPickGemsForSebagian(t *testing.T) {
	inv := inventory("055", "071", "078")

	if got := pickGemsFor(inv, []string{"1"}); strings.Join(got, " ") != "055" {
		t.Errorf("satu slot: got %v want [055]", got)
	}
	if got := pickGemsFor(inv, []string{"1", "3"}); strings.Join(got, " ") != "055 071" {
		t.Errorf("dua slot: got %v want [055 071]", got)
	}
	if got := pickGemsFor(inv, []string{"1", "3", "4"}); strings.Join(got, " ") != "055 071 078" {
		t.Errorf("tiga slot: got %v want [055 071 078]", got)
	}
}

// TestDeadSlotsMendeteksiYangHilang memastikan bentuk yang hilang terdeteksi,
// dan pengamatan pertama hanya dijadikan patokan.
func TestDeadSlotsMendeteksiYangHilang(t *testing.T) {
	h := &gemsHandler{}

	// Pengamatan pertama dengan ketiganya aktif: tidak ada yang mati.
	if got := h.deadSlots(map[string]bool{"1": true, "3": true, "4": true}); len(got) != 0 {
		t.Fatalf("pengamatan pertama tidak boleh ada yang mati: %v", got)
	}
	// Diamond hilang.
	if got := h.deadSlots(map[string]bool{"3": true, "4": true}); strings.Join(got, ",") != "1" {
		t.Fatalf("harus mendeteksi diamond mati, dapat %v", got)
	}
	// Tidak berubah: tidak ada yang baru mati.
	if got := h.deadSlots(map[string]bool{"3": true, "4": true}); len(got) != 0 {
		t.Fatalf("tidak boleh berulang: %v", got)
	}
	// Bulat ikut hilang.
	if got := h.deadSlots(map[string]bool{"4": true}); strings.Join(got, ",") != "3" {
		t.Fatalf("harus mendeteksi bulat mati, dapat %v", got)
	}
}

// TestDeadSlotsPengamatanPertamaSudahMati memastikan gem yang mati SEBELUM bot
// start tetap terdeteksi. Tanpa ini, bot yang baru dijalankan saat diamond
// sudah habis tidak akan pernah mengisi diamond, karena tidak ada perubahan
// yang bisa dibandingkan.
func TestDeadSlotsPengamatanPertamaSudahMati(t *testing.T) {
	h := &gemsHandler{}
	got := h.deadSlots(map[string]bool{"3": true, "4": true})
	if strings.Join(got, ",") != "1" {
		t.Fatalf("diamond harus terdeteksi mati sejak awal, dapat %v", got)
	}
}

// TestIsHuntMessageTanpaGem memastikan hunt yang kehilangan SEMUA gem tetap
// terbaca sebagai pesan hunt, walau baris "empowered by" hilang.
func TestIsHuntMessageTanpaGem(t *testing.T) {
	cases := []struct {
		name string
		text string
		want bool
	}{
		{"dengan empowered", "**me**, hunt is empowered by <a:fgem3:510366794792566785>`[199/1000]` !", true},
		{"tanpa empowered sama sekali", "**me**, You found: :bug: :bee:\n <:blank:1> **|** gained **504xp**!", true},
		{"bukan hunt", "**me**, here's your daily", false},
	}
	for _, c := range cases {
		if got := isHuntMessage(strings.ToLower(c.text)); got != c.want {
			t.Errorf("%s: got %v want %v", c.name, got, c.want)
		}
	}
}

// TestMergeSlotsTanpaDuplikat memastikan akumulasi slot mati mempertahankan
// urutan dan tidak menggandakan.
func TestMergeSlotsTanpaDuplikat(t *testing.T) {
	got := mergeSlots([]string{"1"}, []string{"3", "1"})
	if strings.Join(got, ",") != "1,3" {
		t.Fatalf("got %v want [1 3]", got)
	}
	if got := mergeSlots(nil, nil); len(got) != 0 {
		t.Fatalf("nil+nil harus kosong, dapat %v", got)
	}
}
