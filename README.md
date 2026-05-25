# SLiMS SIBI Plugin
Plugin untuk mengintegrasikan metadata SIBI (Sistem Informasi Perbukuan Indonesia) dari Pusat Perbukuan Kementerian Pendidikan Dasar dan Menengah langsung ke dalam aplikasi SLiMS.

## Instalasi
1. Ekstrak file plugin
2. Letakkan folder `sibi` ke dalam folder `plugins` pada instalasi SLiMS Anda.
3. Buat folder `sibi_docs` di bawah direktori repository untuk menyimpan file e-books yang didownload dari SIBI.
4. Pastikan agar folder `sibi_docs` bisa ditulis (writable).
5. Login sebagai admin ke dalam aplikasi SLiMS dan masuk ke menu System -> Plugins.
6. Aktifkan plugin SIBI dengan menekan switch Enable.
7. Menu SIBI akan tersedia pada modul Biblografi pada bagian bawah.

## Cara Menggunakan
1. Masuk ke modul Bibliografi -> SIBI.
2. Klik tombol "Harvest Metadata SIBI" pada pojok kanan atas.
3. Setelah proses Harvest berhasil, sinkronisasi metadata SIBI yang sudah tersimpan ke SLiMS dengan menekan tombol "Sync Metadata SIBI ke SLiMS". 
4. Agar hasil sinkronisasi metadata bisa dicari, lakukan pengindeksan bibliografis melalui menu System -> Biblio Indexes. Klik pada tombol Update Index pada pojok kanan atas.
5. Setelah proses sinkronisasi selesai, selanjutnya file e-book setiap metadata yang diimpor bisa diunduh satu per satu dengan menekan tombol "Unduh" pada setiap baris. Proses download setiap koleksi digital dapat memakan waktu yang cukup lama.

