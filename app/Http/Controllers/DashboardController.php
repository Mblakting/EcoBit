<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;  

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::guard('siswa')->check()) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $hariini = date('Y-m-d');
        $bulanini = date('m');
        $tahunini = date('Y');
        
        $nisn = Auth::guard('siswa')->user()->nisn; 

        $presensihariini = DB::table('presensi')
            ->where('nisn', $nisn)
            ->where('tgl_presensi', $hariini)
            ->first();

        $historibulanini = DB::table('presensi')
            ->where('nisn', $nisn)
            ->whereRaw('MONTH(tgl_presensi) = ?', [$bulanini])
            ->whereRaw('YEAR(tgl_presensi) = ?', [$tahunini])
            ->orderBy('tgl_presensi', 'DESC')
            ->get();
            
        $rekappresensi = DB::table('presensi')
            ->selectRaw('COUNT(nisn) as jmlhadir, SUM(IF(jam_in > "07:00:00", 1, 0)) as jmlterlambat')
            ->where('nisn', $nisn)
            ->whereRaw('MONTH(tgl_presensi) = ?', [$bulanini]) 
            ->whereRaw('YEAR(tgl_presensi) = ?', [$tahunini])
            ->first();

        $leaderboard = DB::table('presensi')
            ->join('siswa', 'presensi.nisn', '=', 'siswa.nisn') 
            ->where('tgl_presensi', $hariini)
            ->orderBy('jam_in')
            ->get();
            
        $namabulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $rekapizin = DB::table('pengajuan_izin')
            ->selectRaw('SUM(IF(status = "i", 1, 0)) as jmlizin, SUM(IF(status = "s", 1, 0)) as jmlsakit')
            ->where('nisn', $nisn)
            ->whereRaw('MONTH(tgl_izin) = ?', [$bulanini])
            ->whereRaw('YEAR(tgl_izin) = ?', [$tahunini])
            ->where('status_approved',1) 
            ->first();
        
        return view('dashboard.dashboard', compact('presensihariini', 'historibulanini', 'namabulan', 'bulanini', 'tahunini', 'rekappresensi', 'leaderboard', 'rekapizin'));
    }

    public function dashboardadmin()
    {
        $hariini = date('Y-m-d');
        $rekappresensi = DB::table('presensi')
            ->selectRaw('COUNT(nisn) as jmlhadir, SUM(IF(jam_in > "07:00:00", 1, 0)) as jmlterlambat')
            ->where('tgl_presensi', $hariini)   
            ->first();

        $rekapizin = DB::table('pengajuan_izin')
            ->selectRaw('SUM(IF(status = "i", 1, 0)) as jmlizin, SUM(IF(status = "s", 1, 0)) as jmlsakit')
            ->where('tgl_izin', $hariini)   
            ->where('status_approved',1) 
            ->first();

        return view('dashboard.dashboardadmin', compact('rekappresensi', 'rekapizin'));
    }
}