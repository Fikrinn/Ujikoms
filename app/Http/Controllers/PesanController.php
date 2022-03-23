<?php

namespace App\Http\Controllers;

use Alert;
use App\Models\buku;
use App\Models\kategori;
use App\Models\penjualan;
use App\Models\pesanan_detail;
use App\Models\pesan;
use App\Models\user;
use Carbon\Carbon;
use Auth;
use Illuminate\Http\Request;

class PesanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index($id)
    {
        $buku = buku::where('id', $id)->first();

        return view('pesan.index', compact('buku'));
    }
    public function pesan(Request $request, $id)
    {
        $buku = buku::where('id', $id)->first();
        $tanggal = Carbon::now();

        //validasi apakah melebihi stok
        if ($request->jumlah_pesan > $buku->stok) {
            return redirect('pesan/' . $id);
        }

        //cek validasi
        $cek_pesanan = pesan::where('user_id', Auth::user()->id)->where('status', 0)->first();
        //simpan ke database pesanan
        if (empty($cek_pesanan)) {
            $pesanan = new pesan;
            $pesanan->user_id = Auth::user()->id;
            $pesanan->tanggal = $tanggal;
            $pesanan->status = 0;
            $pesanan->jumlah_harga = 0;
            $pesanan->kode = mt_rand(100, 999);
            $pesanan->save();
        }

        //simpan ke database pesanan detail
        $pesanan_baru = pesan::where('user_id', Auth::user()->id)->where('status', 0)->first();

        //cek pesanan detail
        $cek_pesanan_detail = pesanan_detail::where('buku_id', $buku->id)->where('pesan_id', $pesanan_baru->id)->first();
        if (empty($cek_pesanan_detail)) {
            $pesanan_detail = new pesanan_detail;
            $pesanan_detail->buku_id = $buku->id;
            $pesanan_detail->pesan_id = $pesanan_baru->id;
            $pesanan_detail->jumlah = $request->jumlah_pesan;
            $pesanan_detail->jumlah_harga = $buku->harga * $request->jumlah_pesan;
            $pesanan_detail->save();
        } else {
            $pesanan_detail = pesanan_detail::where('buku_id', $buku->id)->where('pesan_id', $pesanan_baru->id)->first();

            $pesanan_detail->jumlah = $pesanan_detail->jumlah + $request->jumlah_pesan;

            //harga sekarang
            $harga_pesanan_detail_baru = $buku->harga * $request->jumlah_pesan;
            $pesanan_detail->jumlah_harga = $pesanan_detail->jumlah_harga + $harga_pesanan_detail_baru;
            $pesanan_detail->update();
        }

        //jumlah total
        $pesanan = pesan::where('user_id', Auth::user()->id)->where('status', 0)->first();
        $pesanan->jumlah_harga = $pesanan->jumlah_harga + $buku->harga * $request->jumlah_pesan;
        $pesanan->update();

        Alert::success('pesan masuk keranjang', 'Success');
        return redirect('check-out');

    }

    public function check_out()
    {
        $pesanan = pesan::where('user_id', Auth::user()->id)->where('status', 0)->first();
        if (!empty($pesanan)) {
            $pesanan_details = pesanan_detail::where('pesan_id', $pesanan->id)->get();

        }
        return view('pesan.check_out', compact('pesanan', 'pesanan_details'));
    }

    public function delete($id)
    {
        $pesanan_detail = pesanan_detail::where('id', $id)->first();

        $pesanan = pesan::where('id', $pesanan_detail->pesan_id)->first();
        $pesanan->jumlah_harga = $pesanan->jumlah_harga - $pesanan_detail->jumlah_harga;
        $pesanan->update();

        $pesanan_detail->delete();

        Alert::error('pesan Sukses Dihapus', 'Hapus');
        return redirect('check-out');
    }

    public function konfirmasi()
    {
        $user = User::where('id', Auth::user()->id)->first();

        $pesanan = pesan::where('user_id', Auth::user()->id)->where('status', 0)->first();
        $pesan_id = $pesanan->id;
        $pesanan->status = 1;
        $pesanan->update();

        $pesanan_details = pesanan_detail::where('pesan_id', $pesan_id)->get();
        foreach ($pesanan_details as $pesanan_detail) {
            $buku = buku::where('id', $pesanan_detail->buku_id)->first();
            $buku->stok = $buku->stok - $pesanan_detail->jumlah;
            $buku->update();
        }

        Alert::success('pesan telah di checkout', 'Success');
        return redirect('history/' . $pesan_id);

    }


}
