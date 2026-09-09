<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Divisi;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\PenanggungJawab;
use Illuminate\Database\Seeder;

class MasterKodeAsetSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Divisi (1 - 6)
        $divisiList = [
            ['kode_divisi' => '1', 'nama_divisi' => 'Direksi', 'keterangan' => 'Aset Tetap'],
            ['kode_divisi' => '2', 'nama_divisi' => 'Kelembagaan', 'keterangan' => 'Aset Tetap'],
            ['kode_divisi' => '3', 'nama_divisi' => 'Fundraising', 'keterangan' => 'Aset Tetap'],
            ['kode_divisi' => '4', 'nama_divisi' => 'Keuangan', 'keterangan' => 'Aset Tetap'],
            ['kode_divisi' => '5', 'nama_divisi' => 'Program', 'keterangan' => 'Aset dalam Kelolaan'],
            ['kode_divisi' => '6', 'nama_divisi' => 'Wakaf', 'keterangan' => 'Aset'],
        ];

        foreach ($divisiList as $div) {
            Divisi::updateOrCreate(
                ['kode_divisi' => $div['kode_divisi']],
                $div
            );
        }

        // 2. Seed Kategori (EL, FN, KD)
        $kategoriEL = Kategori::updateOrCreate(
            ['kode_kategori' => 'EL'],
            ['nama_kategori' => 'Elektronik', 'keterangan' => 'Peralatan elektronik, komputer, dan IT']
        );

        $kategoriFN = Kategori::updateOrCreate(
            ['kode_kategori' => 'FN'],
            ['nama_kategori' => 'Furniture', 'keterangan' => 'Perabotan kantor, meja, kursi, dan perlengkapan']
        );

        $kategoriKD = Kategori::updateOrCreate(
            ['kode_kategori' => 'KD'],
            ['nama_kategori' => 'Kendaraan', 'keterangan' => 'Kendaraan dinas dan operasional']
        );

        // 3. Seed Master Barang - Elektronik (EL01 - EL55)
        $elektronikItems = [
            ['kode' => '01', 'nama' => 'AC'],
            ['kode' => '02', 'nama' => 'Mic'],
            ['kode' => '03', 'nama' => 'Dispenser'],
            ['kode' => '04', 'nama' => 'Kipas Angin'],
            ['kode' => '05', 'nama' => 'Hardisk eksternal'],
            ['kode' => '06', 'nama' => 'Headset'],
            ['kode' => '07', 'nama' => 'Mesin Hidrolik'],
            ['kode' => '08', 'nama' => 'Kabel Roll Gulung'],
            ['kode' => '09', 'nama' => 'Proyektor'],
            ['kode' => '10', 'nama' => 'Kompor Gas'],
            ['kode' => '11', 'nama' => 'Komputer PC'],
            ['kode' => '12', 'nama' => 'Kulkas'],
            ['kode' => '13', 'nama' => 'Perkakas'],
            ['kode' => '14', 'nama' => 'Laptop'],
            ['kode' => '15', 'nama' => 'Mesin Absen'],
            ['kode' => '16', 'nama' => 'Mesin Fax'],
            ['kode' => '17', 'nama' => 'Mesin Jilid'],
            ['kode' => '18', 'nama' => 'Printer'],
            ['kode' => '19', 'nama' => 'Stabilizer'],
            ['kode' => '20', 'nama' => 'Router Wireless'],
            ['kode' => '21', 'nama' => 'Smartphone'],
            ['kode' => '22', 'nama' => 'Speaker komputer'],
            ['kode' => '23', 'nama' => 'Smartphone Tab'],
            ['kode' => '24', 'nama' => 'Pesawat Telepon'],
            ['kode' => '25', 'nama' => 'TV'],
            ['kode' => '26', 'nama' => 'UPS'],
            ['kode' => '27', 'nama' => 'Mesin hitung uang'],
            ['kode' => '28', 'nama' => 'Vacuum Cleaner'],
            ['kode' => '29', 'nama' => 'Kamera SLR/DSLR'],
            ['kode' => '30', 'nama' => 'Handycam'],
            ['kode' => '31', 'nama' => 'Mixer Sound'],
            ['kode' => '32', 'nama' => 'Lampu Flash'],
            ['kode' => '33', 'nama' => 'Genset'],
            ['kode' => '34', 'nama' => 'Digitizer'],
            ['kode' => '35', 'nama' => 'Handy Talky'],
            ['kode' => '36', 'nama' => 'Scanner'],
            ['kode' => '37', 'nama' => 'CCTV'],
            ['kode' => '38', 'nama' => 'Mesin Jahit/obras'],
            ['kode' => '39', 'nama' => 'Portable Speaker'],
            ['kode' => '40', 'nama' => 'Voice Recorder'],
            ['kode' => '41', 'nama' => 'Vertical Grip'],
            ['kode' => '42', 'nama' => 'Video'],
            ['kode' => '43', 'nama' => 'Mesin Pemotong KER'],
            ['kode' => '44', 'nama' => 'Mesin Label'],
            ['kode' => '45', 'nama' => 'Lensa Kamera'],
            ['kode' => '46', 'nama' => 'LED'],
            ['kode' => '47', 'nama' => 'Penangkal Petir'],
            ['kode' => '48', 'nama' => 'Server'],
            ['kode' => '49', 'nama' => 'Apple IMAC'],
            ['kode' => '50', 'nama' => 'Amplifier'],
            ['kode' => '51', 'nama' => 'Drone'],
            ['kode' => '52', 'nama' => 'Printer Thermal'],
            ['kode' => '53', 'nama' => 'SSD Portable'],
            ['kode' => '54', 'nama' => 'Megaphone'],
            ['kode' => '55', 'nama' => 'HT'],
        ];

        foreach ($elektronikItems as $item) {
            Barang::updateOrCreate(
                ['kategori_id' => $kategoriEL->id, 'kode_barang' => $item['kode']],
                ['nama_barang' => $item['nama']]
            );
        }

        // 4. Seed Master Barang - Furniture (FN01 - FN22)
        $furnitureItems = [
            ['kode' => '01', 'nama' => 'Brankas'],
            ['kode' => '02', 'nama' => 'Kursi Kerja'],
            ['kode' => '03', 'nama' => 'Lemari'],
            ['kode' => '04', 'nama' => 'Meja kerja'],
            ['kode' => '05', 'nama' => 'Meja Rapat'],
            ['kode' => '06', 'nama' => 'Peti'],
            ['kode' => '07', 'nama' => 'Rak Sepatu'],
            ['kode' => '08', 'nama' => 'Mesin Pengharum'],
            ['kode' => '09', 'nama' => 'Kursi tamu'],
            ['kode' => '10', 'nama' => 'Screen Proyektor'],
            ['kode' => '11', 'nama' => 'Tangga'],
            ['kode' => '12', 'nama' => 'Tenda'],
            ['kode' => '13', 'nama' => 'Tower'],
            ['kode' => '14', 'nama' => 'Tripod'],
            ['kode' => '15', 'nama' => 'Trolly'],
            ['kode' => '16', 'nama' => 'Papan'],
            ['kode' => '17', 'nama' => 'Tiang Listrik'],
            ['kode' => '18', 'nama' => 'Meja Tamu'],
            ['kode' => '19', 'nama' => 'Karpet'],
            ['kode' => '20', 'nama' => 'Jam Dinding'],
            ['kode' => '21', 'nama' => 'Tong Sampah'],
            ['kode' => '22', 'nama' => 'Apar'],
        ];

        foreach ($furnitureItems as $item) {
            Barang::updateOrCreate(
                ['kategori_id' => $kategoriFN->id, 'kode_barang' => $item['kode']],
                ['nama_barang' => $item['nama']]
            );
        }

        // 5. Seed Master Barang - Kendaraan (KD01 - KD04)
        $kendaraanItems = [
            ['kode' => '01', 'nama' => 'Mobil'],
            ['kode' => '02', 'nama' => 'Motor'],
            ['kode' => '03', 'nama' => 'Perahu'],
            ['kode' => '04', 'nama' => 'Sepeda'],
        ];

        foreach ($kendaraanItems as $item) {
            Barang::updateOrCreate(
                ['kategori_id' => $kategoriKD->id, 'kode_barang' => $item['kode']],
                ['nama_barang' => $item['nama']]
            );
        }

        // 6. Seed Master PIC / Amil (dengan Kode NIA 3 Digit, Divisi, Jabatan, dan Kontak)
        $divMap = Divisi::pluck('id', 'kode_divisi')->toArray();

        $picList = [
            ['kode' => '008', 'nama' => 'Iwan Rahmat', 'divisi' => '1', 'jabatan' => 'Direktur Utama', 'telepon' => '081289008008', 'email' => 'iwan.rahmat@alazhar.org'],
            ['kode' => '009', 'nama' => 'Subakti', 'divisi' => '1', 'jabatan' => 'Direktur Eksekutif', 'telepon' => '081289009009', 'email' => 'subakti@alazhar.org'],
            ['kode' => '010', 'nama' => 'Nurli Laelasari', 'divisi' => '2', 'jabatan' => 'Kepala Divisi Kelembagaan', 'telepon' => '081289010010', 'email' => 'nurli.laelasari@alazhar.org'],
            ['kode' => '012', 'nama' => 'Suparman', 'divisi' => '5', 'jabatan' => 'General Affairs & Logistik', 'telepon' => '081289012012', 'email' => 'suparman@alazhar.org'],
            ['kode' => '013', 'nama' => 'Rahmatullah Sidik', 'divisi' => '5', 'jabatan' => 'Koordinator RGI Sawangan', 'telepon' => '081289013013', 'email' => 'rahmatullah.sidik@alazhar.org'],
            ['kode' => '014', 'nama' => 'Rochadi Kohar', 'divisi' => '5', 'jabatan' => 'Fasilitator Otomotif RGI', 'telepon' => '081289014014', 'email' => 'rochadi.kohar@alazhar.org'],
            ['kode' => '017', 'nama' => 'Lias', 'divisi' => '4', 'jabatan' => 'Staf Keuangan & Pajak', 'telepon' => '081289017017', 'email' => 'lias@alazhar.org'],
            ['kode' => '019', 'nama' => 'Jamaludin', 'divisi' => '5', 'jabatan' => 'Instruktur Teknik Komputer RGI', 'telepon' => '081289019019', 'email' => 'jamaludin@alazhar.org'],
            ['kode' => '023', 'nama' => 'Rusdi', 'divisi' => '5', 'jabatan' => 'Staff Operasional RGI', 'telepon' => '081289023023', 'email' => 'rusdi@alazhar.org'],
            ['kode' => '025', 'nama' => 'Matnur', 'divisi' => '5', 'jabatan' => 'Koordinator Keamanan & Driver', 'telepon' => '081289025025', 'email' => 'matnur@alazhar.org'],
            ['kode' => '026', 'nama' => 'Sigit Nugroho', 'divisi' => '3', 'jabatan' => 'Supervisor Digital Fundraising', 'telepon' => '081289026026', 'email' => 'sigit.nugroho@alazhar.org'],
            ['kode' => '029', 'nama' => 'Andi', 'divisi' => '3', 'jabatan' => 'Account Manager Fundraising', 'telepon' => '081289029029', 'email' => 'andi@alazhar.org'],
            ['kode' => '030', 'nama' => 'Maulana Soheh', 'divisi' => '5', 'jabatan' => 'Fasilitator Tata Busana RGI', 'telepon' => '081289030030', 'email' => 'maulana.soheh@alazhar.org'],
            ['kode' => '031', 'nama' => 'Faridun Nidhom', 'divisi' => '5', 'jabatan' => 'Instruktur Desain Grafis RGI', 'telepon' => '081289031031', 'email' => 'faridun.nidhom@alazhar.org'],
            ['kode' => '032', 'nama' => 'Deden Nurdin Salim', 'divisi' => '5', 'jabatan' => 'Kepala Divisi Program Pendayagunaan', 'telepon' => '081289032032', 'email' => 'deden.nurdin@alazhar.org'],
            ['kode' => '035', 'nama' => 'Soleh', 'divisi' => '5', 'jabatan' => 'Fasilitator Fotografi & Studio RGI', 'telepon' => '081289035035', 'email' => 'soleh@alazhar.org'],
            ['kode' => '037', 'nama' => 'Arini Susanti', 'divisi' => '4', 'jabatan' => 'Kepala Divisi Keuangan & Akuntansi', 'telepon' => '081289037037', 'email' => 'arini.susanti@alazhar.org'],
            ['kode' => '042', 'nama' => 'Ratih Puspitasari', 'divisi' => '3', 'jabatan' => 'Relationship Officer CSR', 'telepon' => '081289042042', 'email' => 'ratih.puspitasari@alazhar.org'],
            ['kode' => '043', 'nama' => 'Eri Sukeri', 'divisi' => '5', 'jabatan' => 'Staf Logistik & Gudang', 'telepon' => '081289043043', 'email' => 'eri.sukeri@alazhar.org'],
            ['kode' => '044', 'nama' => 'Syarifudin', 'divisi' => '2', 'jabatan' => 'Staf Legal & Kelembagaan', 'telepon' => '081289044044', 'email' => 'syarifudin@alazhar.org'],
            ['kode' => '045', 'nama' => 'Mad Soleh', 'divisi' => '5', 'jabatan' => 'Staf Maintenance & Teknisi', 'telepon' => '081289045045', 'email' => 'mad.soleh@alazhar.org'],
            ['kode' => '046', 'nama' => 'Eko Mustakim', 'divisi' => '3', 'jabatan' => 'Koordinator Event & Mitra', 'telepon' => '081289046046', 'email' => 'eko.mustakim@alazhar.org'],
            ['kode' => '049', 'nama' => 'Benny Abdullah', 'divisi' => '6', 'jabatan' => 'Koordinator Pengelolaan Wakaf', 'telepon' => '081289049049', 'email' => 'benny.abdullah@alazhar.org'],
            ['kode' => '050', 'nama' => 'Suryamin', 'divisi' => '2', 'jabatan' => 'Staf Pengelolaan Aset & CRM', 'telepon' => '081289050050', 'email' => 'suryamin@alazhar.org'],
            ['kode' => '052', 'nama' => 'Ridwan', 'divisi' => '3', 'jabatan' => 'Fundraiser Retail', 'telepon' => '081289052052', 'email' => 'ridwan@alazhar.org'],
            ['kode' => '053', 'nama' => 'Yeny Herliana', 'divisi' => '4', 'jabatan' => 'Staf Kasir & Pembayaran', 'telepon' => '081289053053', 'email' => 'yeny.herliana@alazhar.org'],
            ['kode' => '055', 'nama' => 'Eko Sugiyanto', 'divisi' => '5', 'jabatan' => 'Koordinator Santri Career Center', 'telepon' => '081289055055', 'email' => 'eko.sugiyanto@alazhar.org'],
            ['kode' => '057', 'nama' => 'Ridho Fitriansyah Mursalaat', 'divisi' => '2', 'jabatan' => 'Staf IT & Infrastruktur Jaringan', 'telepon' => '081289057057', 'email' => 'ridho.fitriansyah@alazhar.org'],
            ['kode' => '060', 'nama' => 'Suci Putriani', 'divisi' => '3', 'jabatan' => 'Customer Service & Donatur Care', 'telepon' => '081289060060', 'email' => 'suci.putriani@alazhar.org'],
            ['kode' => '063', 'nama' => 'Edy Tri Susanto', 'divisi' => '5', 'jabatan' => 'Staf Program Pemberdayaan Ekonomi', 'telepon' => '081289063063', 'email' => 'edy.tri@alazhar.org'],
            ['kode' => '066', 'nama' => 'Sigit Tripuruca', 'divisi' => '2', 'jabatan' => 'Staf HRD & Pengembangan SDM', 'telepon' => '081289066066', 'email' => 'sigit.tripuruca@alazhar.org'],
            ['kode' => '067', 'nama' => 'Ahmad Priyanto', 'divisi' => '5', 'jabatan' => 'Fasilitator Administrasi Perkantoran RGI', 'telepon' => '081289067067', 'email' => 'ahmad.priyanto@alazhar.org'],
            ['kode' => '069', 'nama' => 'Irsan Haikal', 'divisi' => '3', 'jabatan' => 'Creative Designer & Video Editor', 'telepon' => '081289069069', 'email' => 'irsan.haikal@alazhar.org'],
            ['kode' => '071', 'nama' => 'Setiyadi', 'divisi' => '5', 'jabatan' => 'Instruktur Bengkel RGI', 'telepon' => '081289071071', 'email' => 'setiyadi@alazhar.org'],
            ['kode' => '072', 'nama' => 'Rudiansah', 'divisi' => '5', 'jabatan' => 'Staf Distribusi & Kebencanaan', 'telepon' => '081289072072', 'email' => 'rudiansah@alazhar.org'],
            ['kode' => '073', 'nama' => 'Nurmala', 'divisi' => '4', 'jabatan' => 'Staf Akuntansi & Jurnal', 'telepon' => '081289073073', 'email' => 'nurmala@alazhar.org'],
            ['kode' => '074', 'nama' => 'Aditya Kusuma', 'divisi' => '3', 'jabatan' => 'Digital Marketer & Ads Specialist', 'telepon' => '081289074074', 'email' => 'aditya.kusuma@alazhar.org'],
            ['kode' => '077', 'nama' => 'Teguh Widada', 'divisi' => '5', 'jabatan' => 'Kepala Asrama Santri Putra RGI', 'telepon' => '081289077077', 'email' => 'teguh.widada@alazhar.org'],
            ['kode' => '081', 'nama' => 'Ulil Ansor', 'divisi' => '5', 'jabatan' => 'Instruktur Rekaman Audio RGI', 'telepon' => '081289081081', 'email' => 'ulil.ansor@alazhar.org'],
            ['kode' => '082', 'nama' => 'Rayan Asa Luminaries', 'divisi' => '3', 'jabatan' => 'Staf Media Kreatif', 'telepon' => '081289082082', 'email' => 'rayan.asa@alazhar.org'],
            ['kode' => '084', 'nama' => 'Dwi Nursyamsi', 'divisi' => '4', 'jabatan' => 'Staf Verifikasi Dokumen Keuangan', 'telepon' => '081289084084', 'email' => 'dwi.nursyamsi@alazhar.org'],
            ['kode' => '086', 'nama' => 'Eka Nur Prihantari', 'divisi' => '2', 'jabatan' => 'Sekretaris Eksekutif', 'telepon' => '081289086086', 'email' => 'eka.nur@alazhar.org'],
            ['kode' => '088', 'nama' => 'Mohammad Mahrus', 'divisi' => '5', 'jabatan' => 'Staf Program Dakwah & Advokasi', 'telepon' => '081289088088', 'email' => 'mohammad.mahrus@alazhar.org'],
            ['kode' => '089', 'nama' => 'Fadhillah Mustikaningrum', 'divisi' => '5', 'jabatan' => 'Kepala Asrama Santri Putri RGI', 'telepon' => '081289089089', 'email' => 'fadhillah.mustika@alazhar.org'],
            ['kode' => '090', 'nama' => 'Agus Rodiansyah', 'divisi' => '5', 'jabatan' => 'Staf Lapangan Siaga Bencana', 'telepon' => '081289090090', 'email' => 'agus.rodiansyah@alazhar.org'],
            ['kode' => '092', 'nama' => 'Oktorian Yolinda', 'divisi' => '3', 'jabatan' => 'Koordinator Fundraising Komunitas', 'telepon' => '081289092092', 'email' => 'oktorian.yolinda@alazhar.org'],
            ['kode' => '093', 'nama' => 'Hapipul Umam', 'divisi' => '5', 'jabatan' => 'Staf Program Kesehatan & Ambulans', 'telepon' => '081289093093', 'email' => 'hapipul.umam@alazhar.org'],
            ['kode' => '095', 'nama' => 'Norma Widya Rachamawatie', 'divisi' => '4', 'jabatan' => 'Staf Payroll & Remunerasi', 'telepon' => '081289095095', 'email' => 'norma.widya@alazhar.org'],
            ['kode' => '101', 'nama' => 'Ridwan Sanusi', 'divisi' => '5', 'jabatan' => 'Staf Program Beasiswa', 'telepon' => '081289101101', 'email' => 'ridwan.sanusi@alazhar.org'],
            ['kode' => '102', 'nama' => 'Fakhri Hamdi', 'divisi' => '3', 'jabatan' => 'Content Creator & Copywriter', 'telepon' => '081289102102', 'email' => 'fakhri.hamdi@alazhar.org'],
            ['kode' => '109', 'nama' => 'Muhammad Lutfhi Nurizaman', 'divisi' => '2', 'jabatan' => 'Staf Database & Analis Sistem', 'telepon' => '081289109109', 'email' => 'lutfhi.nurizaman@alazhar.org'],
            ['kode' => '113', 'nama' => 'Kholis Fatahillah', 'divisi' => '5', 'jabatan' => 'Fasilitator Kewirausahaan RGI', 'telepon' => '081289113113', 'email' => 'kholis.fatahillah@alazhar.org'],
            ['kode' => '114', 'nama' => 'Mohlas Madani', 'divisi' => '6', 'jabatan' => 'Staf Pengembangan Aset Wakaf', 'telepon' => '081289114114', 'email' => 'mohlas.madani@alazhar.org'],
            ['kode' => '116', 'nama' => 'Andi Suryadi', 'divisi' => '5', 'jabatan' => 'Koordinator Lapangan Program Desa', 'telepon' => '081289116116', 'email' => 'andi.suryadi@alazhar.org'],
            ['kode' => '117', 'nama' => 'Fahmi', 'divisi' => '3', 'jabatan' => 'Staf Telefundraising', 'telepon' => '081289117117', 'email' => 'fahmi@alazhar.org'],
            ['kode' => '118', 'nama' => 'Feni Lestari', 'divisi' => '4', 'jabatan' => 'Kasir Kantor Cirendeu', 'telepon' => '081289118118', 'email' => 'feni.lestari@alazhar.org'],
            ['kode' => '119', 'nama' => 'Herfandro Fajar', 'divisi' => '2', 'jabatan' => 'Staf Pengadaan & Inventaris', 'telepon' => '081289119119', 'email' => 'herfandro.fajar@alazhar.org'],
            ['kode' => '123', 'nama' => 'Agus Bangun Prabowo', 'divisi' => '5', 'jabatan' => 'Staf Pelaksana RGI', 'telepon' => '081289123123', 'email' => 'agus.bangun@alazhar.org'],
            ['kode' => '124', 'nama' => 'Ulfa Mutia', 'divisi' => '3', 'jabatan' => 'Staf Pelayanan Donatur KLB', 'telepon' => '081289124124', 'email' => 'ulfa.mutia@alazhar.org'],
            ['kode' => '129', 'nama' => 'Dofi Ridofillah', 'divisi' => '5', 'jabatan' => 'Staf Program Rumah Sehat', 'telepon' => '081289129129', 'email' => 'dofi.ridofillah@alazhar.org'],
            ['kode' => '143', 'nama' => 'Ade Anisa Oktaviani', 'divisi' => '4', 'jabatan' => 'Staf Anggaran & Perbendaharaan', 'telepon' => '081289143143', 'email' => 'ade.anisa@alazhar.org'],
            ['kode' => '146', 'nama' => 'Salman Abdun Nashiir', 'divisi' => '3', 'jabatan' => 'Campaigner ZISWAF Online', 'telepon' => '081289146146', 'email' => 'salman.abdun@alazhar.org'],
            ['kode' => '147', 'nama' => 'Muammaliyah Muhammad Amin', 'divisi' => '2', 'jabatan' => 'Staf Hubungan Kelembagaan', 'telepon' => '081289147147', 'email' => 'muammaliyah.amin@alazhar.org'],
            ['kode' => '149', 'nama' => 'Anas Priyogi', 'divisi' => '5', 'jabatan' => 'Staf Monitoring Evaluasi Program', 'telepon' => '081289149149', 'email' => 'anas.priyogi@alazhar.org'],
            ['kode' => '150', 'nama' => 'Alam Maptullah', 'divisi' => '3', 'jabatan' => 'Account Executive Korporat', 'telepon' => '081289150150', 'email' => 'alam.maptullah@alazhar.org'],
            ['kode' => '152', 'nama' => 'Siti Sarah', 'divisi' => '4', 'jabatan' => 'Staf Pelaporan Keuangan', 'telepon' => '081289152152', 'email' => 'siti.sarah@alazhar.org'],
            ['kode' => '153', 'nama' => 'Faisal Ahmad', 'divisi' => '5', 'jabatan' => 'Staf Logistik Tanggap Bencana', 'telepon' => '081289153153', 'email' => 'faisal.ahmad@alazhar.org'],
            ['kode' => '154', 'nama' => 'Bayu Setiawan', 'divisi' => '3', 'jabatan' => 'Fotografer & Desainer Lapangan', 'telepon' => '081289154154', 'email' => 'bayu.setiawan@alazhar.org'],
            ['kode' => '155', 'nama' => 'Siti Adidah', 'divisi' => '5', 'jabatan' => 'Instruktur Kuliner & Tata Boga RGI', 'telepon' => '081289155155', 'email' => 'siti.adidah@alazhar.org'],
            ['kode' => '157', 'nama' => 'Herlinda Novita Wardani', 'divisi' => '3', 'jabatan' => 'Staf Partnership Retail', 'telepon' => '081289157157', 'email' => 'herlinda.novita@alazhar.org'],
            ['kode' => '158', 'nama' => 'Wahyudi', 'divisi' => '5', 'jabatan' => 'Staf Sarana Prasarana Kampus Sawangan', 'telepon' => '081289158158', 'email' => 'wahyudi@alazhar.org'],
            ['kode' => '159', 'nama' => 'Muhammad Sudrajat', 'divisi' => '5', 'jabatan' => 'Staf Pendistribusian Logistik', 'telepon' => '081289159159', 'email' => 'muhammad.sudrajat@alazhar.org'],
            ['kode' => '160', 'nama' => 'Ahmad Zaki Zamany', 'divisi' => '3', 'jabatan' => 'Staf Social Media Specialist', 'telepon' => '081289160160', 'email' => 'ahmad.zaki@alazhar.org'],
            ['kode' => '162', 'nama' => 'Abbas', 'divisi' => '5', 'jabatan' => 'Staf Operasional Program', 'telepon' => '081289162162', 'email' => 'abbas@alazhar.org'],
            ['kode' => '165', 'nama' => 'Lia Umro Safitri', 'divisi' => '4', 'jabatan' => 'Staf Rekonsiliasi Bank', 'telepon' => '081289165165', 'email' => 'lia.umro@alazhar.org'],
            ['kode' => '166', 'nama' => 'Sujarwo Putra', 'divisi' => '5', 'jabatan' => 'Fasilitator Lapangan Program Pertanian', 'telepon' => '081289166166', 'email' => 'sujarwo.putra@alazhar.org'],
            ['kode' => '169', 'nama' => 'Fauzi Arif Suhada', 'divisi' => '3', 'jabatan' => 'Koordinator Layanan Konter Zakat', 'telepon' => '081289169169', 'email' => 'fauzi.arif@alazhar.org'],
            ['kode' => '171', 'nama' => 'Rosyadi', 'divisi' => '5', 'jabatan' => 'Staf Pelaksana Lapangan', 'telepon' => '081289171171', 'email' => 'rosyadi@alazhar.org'],
            ['kode' => '172', 'nama' => 'Nopen Setiawan', 'divisi' => '5', 'jabatan' => 'Staf Program Rumah Qur\'an', 'telepon' => '081289172172', 'email' => 'nopen.setiawan@alazhar.org'],
            ['kode' => '173', 'nama' => 'Agus Setiawan', 'divisi' => '2', 'jabatan' => 'Staf IT Hardware & Jaringan', 'telepon' => '081289173173', 'email' => 'agus.setiawan@alazhar.org'],
            ['kode' => '174', 'nama' => 'Hadi Nur Cahyo', 'divisi' => '3', 'jabatan' => 'Officer Fundraising Cabang', 'telepon' => '081289174174', 'email' => 'hadi.nur@alazhar.org'],
            ['kode' => '179', 'nama' => 'Nadhilah Amalia Sifa', 'divisi' => '4', 'jabatan' => 'Staf Administrasi Keuangan', 'telepon' => '081289179179', 'email' => 'nadhilah.amalia@alazhar.org'],
            ['kode' => '186', 'nama' => 'Dedy Irwansyah', 'divisi' => '5', 'jabatan' => 'Staf Operasional Program Sawangan', 'telepon' => '081289186186', 'email' => 'dedy.irwansyah@alazhar.org'],
            ['kode' => '187', 'nama' => 'Dian Ameliawati', 'divisi' => '2', 'jabatan' => 'Staf Sekretariat & Kearsipan', 'telepon' => '081289187187', 'email' => 'dian.amelia@alazhar.org'],
            ['kode' => '189', 'nama' => 'Deta Aga Arif Rahman', 'divisi' => '3', 'jabatan' => 'Staf Digital Media & Broadcast', 'telepon' => '081289189189', 'email' => 'deta.aga@alazhar.org'],
            ['kode' => '191', 'nama' => 'Naila Novita', 'divisi' => '3', 'jabatan' => 'Staf Layanan Donatur Online', 'telepon' => '081289191191', 'email' => 'naila.novita@alazhar.org'],
            ['kode' => '194', 'nama' => 'Rizky Ramadhanti', 'divisi' => '4', 'jabatan' => 'Staf Akuntansi & Pajak', 'telepon' => '081289194194', 'email' => 'rizky.ramadhanti@alazhar.org'],
            ['kode' => '198', 'nama' => 'Halimatu Sa\'diah', 'divisi' => '5', 'jabatan' => 'Staf Program Pendidikan', 'telepon' => '081289198198', 'email' => 'halimatu.sadiah@alazhar.org'],
            ['kode' => '199', 'nama' => 'Putri Amelia', 'divisi' => '3', 'jabatan' => 'Staf Layanan Muzakki', 'telepon' => '081289199199', 'email' => 'putri.amelia@alazhar.org'],
            ['kode' => '200', 'nama' => 'Annisa Syafariah', 'divisi' => '2', 'jabatan' => 'Staf Legalitas & Kerjasama', 'telepon' => '081289200200', 'email' => 'annisa.syafariah@alazhar.org'],
            ['kode' => '202', 'nama' => 'Rifka Hartono', 'divisi' => '5', 'jabatan' => 'Staf Program Dakwah & Bina Santri', 'telepon' => '081289202202', 'email' => 'rifka.hartono@alazhar.org'],
            ['kode' => '203', 'nama' => 'Muhammad Ridwan', 'divisi' => '3', 'jabatan' => 'Staf Fundraising Retail', 'telepon' => '081289203203', 'email' => 'm.ridwan@alazhar.org'],
            ['kode' => '205', 'nama' => 'Ahmad Yasir', 'divisi' => '5', 'jabatan' => 'Koordinator Lapangan RGI Sawangan', 'telepon' => '081289205205', 'email' => 'ahmad.yasir@alazhar.org'],
            ['kode' => '206', 'nama' => 'Hijriani', 'divisi' => '4', 'jabatan' => 'Staf Keuangan Program', 'telepon' => '081289206206', 'email' => 'hijriani@alazhar.org'],
            ['kode' => '207', 'nama' => 'Wilda', 'divisi' => '3', 'jabatan' => 'Staf CRM & Retensi Donatur', 'telepon' => '081289207207', 'email' => 'wilda@alazhar.org'],
            ['kode' => '208', 'nama' => 'Nurlia', 'divisi' => '5', 'jabatan' => 'Staf Administrasi RGI', 'telepon' => '081289208208', 'email' => 'nurlia@alazhar.org'],
            ['kode' => '211', 'nama' => 'Hana Nurhasanah', 'divisi' => '4', 'jabatan' => 'Kasir Layanan Zakat', 'telepon' => '081289211211', 'email' => 'hana.nurhasanah@alazhar.org'],
            ['kode' => '213', 'nama' => 'Imam Nur Hamid', 'divisi' => '5', 'jabatan' => 'Fasilitator Pembinaan Karakter Santri', 'telepon' => '081289213213', 'email' => 'imam.nur@alazhar.org'],
            ['kode' => '214', 'nama' => 'Mohamad Hasan', 'divisi' => '6', 'jabatan' => 'Staf Inventarisasi Tanah Wakaf', 'telepon' => '081289214214', 'email' => 'mohamad.hasan@alazhar.org'],
            ['kode' => '215', 'nama' => 'Ahmad Rizal', 'divisi' => '3', 'jabatan' => 'Staf Media Kreatif & Infografis', 'telepon' => '081289215215', 'email' => 'ahmad.rizal@alazhar.org'],
            ['kode' => '217', 'nama' => 'Zaenal Mustofa', 'divisi' => '5', 'jabatan' => 'Staf Logistik Program Tanggap Bencana', 'telepon' => '081289217217', 'email' => 'zaenal.mustofa@alazhar.org'],
            ['kode' => '218', 'nama' => 'Eliyah', 'divisi' => '4', 'jabatan' => 'Staf Pembukuan & Kas Kecil', 'telepon' => '081289218218', 'email' => 'eliyah@alazhar.org'],
            ['kode' => '220', 'nama' => 'Irpan Hardiansah', 'divisi' => '2', 'jabatan' => 'Staf IT Developer & Web', 'telepon' => '081289220220', 'email' => 'irpan.hardiansah@alazhar.org'],
            ['kode' => '221', 'nama' => 'Nabilah Rusydah', 'divisi' => '3', 'jabatan' => 'Staf Partnership Lembaga & Komunitas', 'telepon' => '081289221221', 'email' => 'nabilah.rusydah@alazhar.org'],
            ['kode' => '222', 'nama' => 'JIhan Sahra', 'divisi' => '5', 'jabatan' => 'Staf Dokumentasi Program', 'telepon' => '081289222222', 'email' => 'jihan.sahra@alazhar.org'],
            ['kode' => '223', 'nama' => 'Fitri Ana Wulandari', 'divisi' => '4', 'jabatan' => 'Staf Verifikasi Transaksi Masuk', 'telepon' => '081289223233', 'email' => 'fitri.ana@alazhar.org'],
            ['kode' => '224', 'nama' => 'Abu Hurairah', 'divisi' => '5', 'jabatan' => 'Koordinator Relawan & Dakwah', 'telepon' => '081289224224', 'email' => 'abu.hurairah@alazhar.org'],
            ['kode' => '225', 'nama' => 'Adha Apriani', 'divisi' => '3', 'jabatan' => 'Staf Layanan Donasi Online', 'telepon' => '081289225225', 'email' => 'adha.apriani@alazhar.org'],
            ['kode' => '226', 'nama' => 'Intan Juliani Hidayat', 'divisi' => '2', 'jabatan' => 'Staf Administrasi Umum', 'telepon' => '081289226226', 'email' => 'intan.juliani@alazhar.org'],
            ['kode' => '227', 'nama' => 'Rif’at Sauqi', 'divisi' => '5', 'jabatan' => 'Staf Siaga Tanggap Bencana (Sigab)', 'telepon' => '081289227227', 'email' => 'rifat.sauqi@alazhar.org'],
            ['kode' => '228', 'nama' => 'Nurman Fauzan Hidayana', 'divisi' => '2', 'jabatan' => 'Staf Kelembagaan & Pengawasan Internal', 'telepon' => '081289228228', 'email' => 'nurman.fauzan@alazhar.org'],
        ];

        foreach ($picList as $pic) {
            $divId = isset($pic['divisi']) && isset($divMap[$pic['divisi']]) ? $divMap[$pic['divisi']] : null;
            PenanggungJawab::updateOrCreate(
                ['kode_pic' => $pic['kode']],
                [
                    'nama' => $pic['nama'],
                    'divisi_id' => $divId,
                    'jabatan' => $pic['jabatan'] ?? null,
                    'telepon' => $pic['telepon'] ?? null,
                    'email' => $pic['email'] ?? null,
                    'status' => 'aktif',
                ]
            );
        }

        // 7. Seed Master Lokasi (Kode 3 Digit, Alamat Real, Koordinat GPS, dan Gedung)
        $lokasiList = [
            // KANTOR PUSAT CIRENDEU (Kode 100 - 140)
            ['kode' => '100', 'nama' => 'Kantor Cirendeu (Gedung Utama)', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Cirendeu, Kec. Ciputat Timur, Kota Tangerang Selatan, Banten 15419', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '110', 'nama' => 'Lantai 1 - Gedung Cirendeu', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Lantai 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '111', 'nama' => 'Lobi Utama Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Lobi Depan Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '112', 'nama' => 'Ruang Rapat Utama Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Ruang Rapat Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '113', 'nama' => 'Ruang Fundraising Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Area Fundraising Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '114', 'nama' => 'Ruang Direktur Utama Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Ruang Direksi Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '115', 'nama' => 'Pantry & Dapur Staf Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Area Pantry Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '116', 'nama' => 'Toilet Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Area Servis Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '117', 'nama' => 'Gudang Logistik Lt. 1', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Gudang Arsip & Logistik Lt. 1, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '120', 'nama' => 'Lantai 2 - Gedung Cirendeu', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Lantai 2, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '121', 'nama' => 'Ruang Keuangan & Akuntansi Lt. 2', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Divisi Keuangan Lt. 2, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '122', 'nama' => 'Ruang Kerja Divisi Program Lt. 2', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Divisi Program Lt. 2, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '123', 'nama' => 'Toilet Lt. 2', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Area Toilet Lt. 2, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '130', 'nama' => 'Lantai 3 - Gedung Cirendeu', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Lantai 3, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '131', 'nama' => 'Ruang Kerja HRD & SDM Lt. 3', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Ruang HRD Lt. 3, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '132', 'nama' => 'Ruang CRM & Digital Media Lt. 3', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Ruang CRM Lt. 3, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '133', 'nama' => 'Ruang Kerja Divisi Kelembagaan Lt. 3', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Ruang Kelembagaan Lt. 3, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '134', 'nama' => 'Toilet Lt. 3', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Area Toilet Lt. 3, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],
            ['kode' => '140', 'nama' => 'Rooftop & Area Panel Surya', 'gedung' => 'Kantor Cirendeu', 'alamat' => 'Jl. Cirendeu Raya No. 1, Rooftop Gedung Cirendeu, Ciputat Timur, Tangerang Selatan', 'lat' => -6.309315, 'lng' => 106.772520],

            // KLB PUSAT KEBAYORAN BARU (Kode 200 - 213)
            ['kode' => '200', 'nama' => 'KLB Pusat Kebayoran Baru', 'gedung' => 'KLB Pusat Kebayoran Baru', 'alamat' => 'Kompleks Masjid Agung Al Azhar, Jl. Sisingamangaraja No. 1, Selong, Kebayoran Baru, Jakarta Selatan 12110', 'lat' => -6.238210, 'lng' => 106.801530],
            ['kode' => '210', 'nama' => 'Lobi Layanan KLB Pusat', 'gedung' => 'KLB Pusat Kebayoran Baru', 'alamat' => 'Jl. Sisingamangaraja No. 1, Lobi Pelayanan ZISWAF, Kebayoran Baru, Jakarta Selatan', 'lat' => -6.238210, 'lng' => 106.801530],
            ['kode' => '211', 'nama' => 'Ruang Rapat KLB Pusat', 'gedung' => 'KLB Pusat Kebayoran Baru', 'alamat' => 'Jl. Sisingamangaraja No. 1, Ruang Pertemuan KLB, Kebayoran Baru, Jakarta Selatan', 'lat' => -6.238210, 'lng' => 106.801530],
            ['kode' => '212', 'nama' => 'Ruang Kerja Staf KLB Pusat', 'gedung' => 'KLB Pusat Kebayoran Baru', 'alamat' => 'Jl. Sisingamangaraja No. 1, Ruang Staf Operasional, Kebayoran Baru, Jakarta Selatan', 'lat' => -6.238210, 'lng' => 106.801530],
            ['kode' => '213', 'nama' => 'Ruang Keuangan KLB Pusat', 'gedung' => 'KLB Pusat Kebayoran Baru', 'alamat' => 'Jl. Sisingamangaraja No. 1, Ruang Keuangan & Kasir, Kebayoran Baru, Jakarta Selatan', 'lat' => -6.238210, 'lng' => 106.801530],

            // KAMPUS RGI SAWANGAN (Kode 300 - 339)
            ['kode' => '300', 'nama' => 'Kampus RGI Sawangan (Utama)', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Sawangan Baru, Kec. Sawangan, Kota Depok, Jawa Barat 16511', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '301', 'nama' => 'Pos / Ruang Jaga Sekuriti RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Gerbang Utama RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '302', 'nama' => 'Bengkel Pelatihan Otomotif RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Workshop Otomotif, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '303', 'nama' => 'Lobi Utama Kampus RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Gedung Administrasi RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '304', 'nama' => 'Ruang Rapat Instruktur RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, R. Rapat Guru/Instruktur, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '305', 'nama' => 'Ruang Kerja Staf RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Ruang Kantor Staf RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '306', 'nama' => 'Ruang Kerja Unit LJG', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Kantor Layanan Jasa Gemilang, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '307', 'nama' => 'Area Parkir Kendaraan RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Parkir Timur Kampus RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '308', 'nama' => 'Ruang Keuangan Kampus RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Ruang Kasir & Akuntansi RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '309', 'nama' => 'Ruang Guru & Instruktur', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Ruang Pendidik RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '310', 'nama' => 'Ruang Server & IT RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Data Center & IT Support, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '311', 'nama' => 'Ruang Aula Serbaguna Nurhayati', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Auditorium Nurhayati, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '312', 'nama' => 'Ruang SCC (Santri Career Center)', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Gedung Pelatihan Karir, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '313', 'nama' => 'Ruang SCC Belakang', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Ruang Konseling & Karir, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '314', 'nama' => 'Pantry & Dapur Utama RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Dapur Sentral Santri, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '315', 'nama' => 'Toilet Tamu Pria RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Area Toilet Tamu, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '316', 'nama' => 'Toilet Tamu Wanita RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Area Toilet Tamu, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '317', 'nama' => 'Lab Komputer & Kelas TKJ', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Laboratorium Jaringan Komputer, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '318', 'nama' => 'Ruang Praktek Tata Busana (Tabus)', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Workshop Menjahit & Busana, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '319', 'nama' => 'Ruang Teori Tata Busana', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Kelas Teori Desain Pola, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '320', 'nama' => 'Lab Desain Grafis & Multimedia', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Lab Komputer Desain Grafis, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '321', 'nama' => 'Ruang Shooting & Podcast', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Studio Konten Kreator RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '322', 'nama' => 'Studio & Kelas Fotografi', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Studio Lighting & Foto Produk, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '323', 'nama' => 'Ruang Kelas Administrasi Perkantoran (AP)', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Kelas Teori & Praktek AP, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '324', 'nama' => 'Ruang Kepala Asrama Putri', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Asrama Putri Blok A, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '325', 'nama' => 'Balkon Asrama Putri Lt. 2', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Lantai 2 Asrama Putri, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '326', 'nama' => 'Studio Rekaman Musik & Vokal', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Audio Recording Studio, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '327', 'nama' => 'Kamar Asrama 3 Santri Putri', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Unit Asrama 3 Putri, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '328', 'nama' => 'Kamar Asrama 2 Santri Putri', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Unit Asrama 2 Putri, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '329', 'nama' => 'Kamar Asrama 1 Santri Putri', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Unit Asrama 1 Putri, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '330', 'nama' => 'Ruang Teori Otomotif & Mesin', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Kelas Teori Mesin Kendaraan, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '331', 'nama' => 'Gudang Barang Logistik & Perkakas', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Gudang Barat Kampus, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '332', 'nama' => 'Gudang File & Arsip Keuangan', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Ruang Arsip Dokumen RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '333', 'nama' => 'Dapur Santri Putra', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Area Belakang Dapur Putra, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '334', 'nama' => 'Ruang Jemur & Cuci Santri Putri', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Area Laundry Asrama Putri, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '335', 'nama' => 'Kamar Asrama 3 Santri Putra', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Unit Asrama 3 Putra, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '336', 'nama' => 'Kamar Asrama 2 Santri Putra', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Unit Asrama 2 Putra, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '337', 'nama' => 'Kamar Asrama 1 Santri Putra', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Unit Asrama 1 Putra, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '338', 'nama' => 'Ruang Kepala Asrama Putra', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Asrama Putra Blok A, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],
            ['kode' => '339', 'nama' => 'Mushola Kampus RGI', 'gedung' => 'Kampus RGI Sawangan', 'alamat' => 'Jl. Raya Sawangan KM. 2 No. 100, Mushola Sentral RGI, Sawangan, Depok', 'lat' => -6.398540, 'lng' => 106.764510],

            // DASAMAS CENTER (Kode 400 - 423)
            ['kode' => '400', 'nama' => 'Dasamas Center (Gedung Utama)', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Cirendeu, Ciputat Timur, Kota Tangerang Selatan, Banten 15419', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '410', 'nama' => 'Toilet Lt. 1 Dasamas', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Area Toilet Lt. 1, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '411', 'nama' => 'Ruang Kamar Relawan 1', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Kamar Penginapan 1, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '412', 'nama' => 'Ruang Kamar Relawan 2', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Kamar Penginapan 2, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '413', 'nama' => 'Pantry Dasamas Center', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Dapur Relawan, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '421', 'nama' => 'Ruang Tunggu Tamu Dasamas', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Ruang Tamu Depan, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '422', 'nama' => 'Ruang Aula Pertemuan Dasamas', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Aula Utama Dasamas, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],
            ['kode' => '423', 'nama' => 'Toilet Lantai Atas Dasamas', 'gedung' => 'Dasamas Center', 'alamat' => 'Jl. Masjid Al Azhar No. 45, Area Toilet Lt. 2, Tangerang Selatan', 'lat' => -6.305020, 'lng' => 106.780010],

            // KANTOR LAYANAN (KL) (Kode 500 - 520)
            ['kode' => '500', 'nama' => 'KL Sentra Primer Jakarta Timur', 'gedung' => 'Kantor Layanan (KL)', 'alamat' => 'Kantor Layanan Sentra Primer Al Azhar, Jl. Dr. Sumarno, Pulo Gebang, Kec. Cakung, Kota Jakarta Timur, DKI Jakarta 13950', 'lat' => -6.215530, 'lng' => 106.945020],
            ['kode' => '510', 'nama' => 'KL Bintaro Tangerang Selatan', 'gedung' => 'Kantor Layanan (KL)', 'alamat' => 'Kantor Layanan Al Azhar Bintaro, Ruko Kebayoran Arcade 1 Blok C3 No. 58, Bintaro Jaya Sektor 7, Pondok Aren, Kota Tangerang Selatan, Banten 15224', 'lat' => -6.282540, 'lng' => 106.717050],
            ['kode' => '520', 'nama' => 'KL Cikarang Bekasi', 'gedung' => 'Kantor Layanan (KL)', 'alamat' => 'Kantor Layanan Al Azhar Cikarang, Jl. Dr. Satrio No. 22, Simpangan, Kec. Cikarang Utara, Kabupaten Bekasi, Jawa Barat 17530', 'lat' => -6.315020, 'lng' => 107.165030],

            // KANTOR PERWAKILAN WILAYAH (KPW) (Kode 600 - 640)
            ['kode' => '600', 'nama' => 'KPW Jawa Timur (Surabaya)', 'gedung' => 'Kantor Perwakilan Wilayah (KPW)', 'alamat' => 'KPW LAZ Al Azhar Jawa Timur, Jl. Gayungsari Barat No. 35, Gayungan, Kec. Gayungan, Kota Surabaya, Jawa Timur 60235', 'lat' => -7.331200, 'lng' => 112.721500],
            ['kode' => '610', 'nama' => 'KPW Jawa Tengah (Semarang)', 'gedung' => 'Kantor Perwakilan Wilayah (KPW)', 'alamat' => 'KPW LAZ Al Azhar Jawa Tengah, Jl. Kyai Saleh No. 12, Mugassari, Kec. Semarang Selatan, Kota Semarang, Jawa Tengah 50249', 'lat' => -6.993240, 'lng' => 110.420310],
            ['kode' => '620', 'nama' => 'KPW D.I. Yogyakarta', 'gedung' => 'Kantor Perwakilan Wilayah (KPW)', 'alamat' => 'KPW LAZ Al Azhar D.I. Yogyakarta, Jl. Ring Road Utara No. 88, Mlati, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55284', 'lat' => -7.755600, 'lng' => 110.369500],
            ['kode' => '630', 'nama' => 'KPW Sumatera Barat (Padang)', 'gedung' => 'Kantor Perwakilan Wilayah (KPW)', 'alamat' => 'KPW LAZ Al Azhar Sumatera Barat, Jl. Khatib Sulaiman No. 48, Lolong Belanti, Kec. Padang Utara, Kota Padang, Sumatera Barat 25136', 'lat' => -0.927100, 'lng' => 100.361200],
            ['kode' => '640', 'nama' => 'KPW Sulawesi Selatan (Makassar)', 'gedung' => 'Kantor Perwakilan Wilayah (KPW)', 'alamat' => 'KPW LAZ Al Azhar Sulawesi Selatan, Jl. Boulevard Ruko Ruby Blok F No. 12, Masale, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231', 'lat' => -5.147720, 'lng' => 119.432740],
        ];

        foreach ($lokasiList as $lok) {
            Lokasi::updateOrCreate(
                ['kode_lokasi' => $lok['kode']],
                [
                    'nama_lokasi' => $lok['nama'],
                    'gedung' => $lok['gedung'] ?? null,
                    'alamat_lengkap' => $lok['alamat'] ?? null,
                    'latitude' => $lok['lat'] ?? null,
                    'longitude' => $lok['lng'] ?? null,
                ]
            );
        }
    }
}
