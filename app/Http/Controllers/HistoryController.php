<?php

namespace App\Http\Controllers;

use App\Models\pesan;
use App\Models\pesanan_detail;
use App\Models\User;
use Auth;

class HistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $pesanans = pesan::where('user_id', Auth::user()->id)->where('status', '!=', 0)->get();

        return view('history.index', compact('pesanans'));
    }

    public function detail($id)
    {
        $pesanan = pesan::where('id', $id)->first();
        $pesanan_details = pesanan_detail::where('pesan_id', $pesanan->id)->get();

        return view('history.detail', compact('pesanan', 'pesanan_details'));
    }
    public function laporan()
    {
        $laporan = history::all();
        return view('history.laporan', compact('laporan'));
    }
}