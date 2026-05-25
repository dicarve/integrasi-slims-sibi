* SLiMS SIBI Plugin
Plugin untuk mengintegrasikan metadata SIBI (Sistem Informasi Perbukuan Indonesia) dari Pusat Perbukuan Kementerian Pendidikan Dasar dan Menengah langsung ke dalam aplikasi SLiMS.

** Instalasi
1. Ekstrak file plugin
2. Letakkan folder `sibi` ke dalam folder `plugins` pada instalasi SLiMS Anda.
3. Buat folder `sibi_docs` di bawah direktori repository untuk menyimpan file e-books yang didownload dari SIBI
4. Login sebagai admin ke dalam aplikasi SLiMS dan masuk ke menu System -> Plugins
5. Aktifkan plugin SIBI dengan menekan switch Enable
6. Menu SIBI akan tersedia pada modul Biblografi pada bagian bawah

** Cara Menggunakan
1. Masuk ke modul Bibliografi -> SIBI.
2. Klik tombol "Harvest Metadata SIBI" pada pojok kanan atas.
3. Setelah proses Harvest berhasil, sinkronisasi metadata SIBI yang sudah tersimpan ke SLiMS dengan menekan tombol "Sync Metadata SIBI ke SLiMS". 
4. Agar hasil sinkronisasi metadata bisa dicari, lakukan pengindeksan bibliografis melalui menu System -> Biblio Indexes. Klik pada tombol Update Index pada pojok kanan atas.
