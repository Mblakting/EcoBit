<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Redirect; 
use Illuminate\Support\Facades\Log; 

class PresensiController extends Controller
{
    public function generateHash($password = '123455')
    {
        $hash = Hash::make($password);
        return "Password Asli: " . $password . " <br>Hash Bcrypt yang Valid: <b>" . $hash . "</b><br><br>Salin string hash di atas dan paste di kolom 'password' database.";
    }
    
    public function create()
    {
        $hariini = date('Y-m-d');
        $nisn = Auth::guard('siswa')->user()->nisn;
        $cek = \DB::table('presensi')
            ->where('nisn', $nisn)
            ->where('tgl_presensi', $hariini)
            ->count();
        return view('presensi.create', compact('cek'));
    }

public function store(Request $request)
    {
        $nisn = Auth::guard('siswa')->user()->nisn;
        $tgl_presensi = date('Y-m-d');
        $jam = date('H:i:s');
        $latitudesekolah = -7.671325;
        $longitudesekolah = 110.589607;
        $lokasi = $request->lokasi;
        
        $image = $request->image;
        
        if(!$lokasi || !$image) {
            return response()->json(['status' => 'gagal', 'message' => 'Data tidak lengkap (lokasi atau gambar hilang)'], 422);
        }

        $lokasiuser = explode(",",$lokasi);
        $latitudeuser = $lokasiuser[0];
        $longitudeuser = $lokasiuser[1];
        $jarak = $this->distance($latitudesekolah, $longitudesekolah, $latitudeuser, $longitudeuser);
        $radius = round($jarak['meters']);

        if($radius > 200){ 
            return response()->json([
                'status' => 'error',
                'message' => 'Anda berada di luar area sekolah ' . $radius . ' meter!',
                'type' => 'out'
            ]);
        }
        
        $cek = \DB::table('presensi')
            ->where('nisn', $nisn)
            ->where('tgl_presensi', $tgl_presensi)
            ->first(); 

        if($cek) {
            $ket = "out";
        } else {
            $ket = "in";
        }

        $folderPath = "uploads/absensi/";
        $formatName = $nisn . "-" . $tgl_presensi . "-" . $ket;
        $image_parts = explode(';base64,', $image);
        
        if(count($image_parts) === 2) {
            $image_base64 = base64_decode($image_parts[1]);
            $fileName = $formatName . '.png';
            $file = $folderPath . $fileName;

            if($cek){
                if(!empty($cek->jam_out)){
                    return response()->json(['status' => 'gagal', 'message' => 'Anda sudah melakukan presensi masuk dan pulang hari ini'], 409);
                }

                $data_pulang = [
                    'jam_out' => $jam,
                    'foto_out' => $fileName,
                    'lokasi_out' => $lokasi,
                ];

                $saved = Storage::disk('public')->put($file, $image_base64);

                if($saved){
                    $update = DB::table('presensi')
                        ->where('tgl_presensi', $tgl_presensi)
                        ->where('nisn', $nisn)
                        ->update($data_pulang);

                    if($update){
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Hati-Hati Di Jalan!',
                            'type' => 'out'
                        ]);
                    } else {
                        return response()->json(['status' => 'gagal', 'message' => 'Gagal update database untuk presensi pulang']);
                    }
                } else {
                    return response()->json(['status' => 'gagal', 'message' => 'Gagal upload file untuk presensi pulang']);
                }

            } else {
                $dataDb = [
                    'nisn' => $nisn,
                    'tgl_presensi' => $tgl_presensi,
                    'jam_in' => $jam,
                    'foto_in' => $fileName,
                    'lokasi_in' => $lokasi,
                ];

                $saved = Storage::disk('public')->put($file, $image_base64);

                if($saved) {
                    $simpan = \DB::table('presensi')->insert($dataDb);
                    
                    if($simpan){
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Selamat Belajar!',
                            'type' => 'in'
                        ]);
                    } else {
                        return response()->json(['status' => 'gagal', 'message' => 'Gagal simpan database untuk presensi masuk']);
                    }
                } else {
                    return response()->json(['status' => 'gagal', 'message' => 'Gagal upload file untuk presensi masuk']);
                }
            }
        } else {
            return response()->json(['status' => 'gagal', 'message' => 'Format gambar tidak valid'], 400);
        }
    }

    function distance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        return compact('meters');     
    }

    public function editprofile()
    {
        $nisn = Auth::guard('siswa')->user()->nisn;
        $siswa = DB::table('siswa')->where('nisn', $nisn)->first();
        return view('presensi.editprofile', compact('siswa'));
    } 
    
    public function updateprofile(Request $request)
    {
        $nisn = Auth::guard('siswa')->user()->nisn;
        $siswa = DB::table('siswa')->where('nisn', $nisn)->first();
        
        $data = [
            'nama_lengkap' => $request->input('nama_lengkap'),
            'no_hp' => $request->input('no_hp'),
        ];
        
        $passwordChanged = false;
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $passwordChanged = true;
        }
        
        $foto_lama = $siswa->foto;
        $foto_baru = $foto_lama;
        $file_disimpan = false; 

        if ($request->hasFile('foto')) {
            $foto_baru = $nisn.".".$request->file('foto')->getClientOriginalExtension();
            $data['foto'] = $foto_baru;
            $file_disimpan = true; 

        } elseif ($request->filled('foto_webcam')) {
            $foto_webcam_data = $request->input('foto_webcam');
            
            $image_parts = explode(";base64,", $foto_webcam_data);
            if (count($image_parts) > 1) {
                $image_base64 = base64_decode($image_parts[1]); 
            } else {
                $image_base64 = base64_decode($foto_webcam_data);
            }

            $foto_baru = $nisn . '_' . time() . '.jpg';
            $data['foto'] = $foto_baru;

            $folderPath = 'uploads/foto_siswa';

            try {
                Storage::disk('public')->put($folderPath . '/' . $foto_baru, $image_base64);
                $file_disimpan = true;
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal menyimpan foto dari webcam.');
            }

        } else {
            $data['foto'] = $foto_lama; 
        }

        $update = DB::table('siswa')->where('nisn', $nisn)->update($data);
        if ($update || $file_disimpan) { 
            
            if($request->hasFile('foto')) {
                
                if ($foto_lama && $foto_lama !== 'default.jpg') {
                    Storage::disk('public')->delete('uploads/foto_siswa/' . $foto_lama);
                }


                $folderPath = 'uploads/foto_siswa';

                $request->file('foto')->storeAs($folderPath, $foto_baru, 'public');
            }
            
            if ($file_disimpan && !$request->hasFile('foto') && $foto_lama && $foto_lama !== 'default.jpg') {

                Storage::disk('public')->delete('uploads/foto_siswa/' . $foto_lama);
            }
            
            return redirect()->back()->with('success', 'Berhasil mengupdate data profile.'); 
            
        } else {
            return redirect()->back()->with('warning', 'Tidak ada data yang diubah.');
        }

        return redirect()->back()->with('error', 'Gagal mengupdate data profile.');
    }

    public function histori()
    {
        $namabulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        return view('presensi.histori', compact('namabulan'));
    }

    public function gethistori(Request $request)
    {
        $nisn = Auth::guard('siswa')->user()->nisn;
        $bulan = intval($request->input('bulan'));
        $tahun = intval($request->input('tahun'));

        $histori = DB::table('presensi')
            ->when($bulan, function ($query, $bulan) {
                return $query->whereMonth('tgl_presensi', $bulan);
            })
            ->when($tahun, function ($query, $tahun) {
                return $query->whereYear('tgl_presensi', $tahun);
            })
            ->where('nisn', $nisn)
            ->orderBy('tgl_presensi', 'desc')
            ->get();

        return view('presensi.gethistori', compact('histori'));
    }

    public function izin()
    {
        $dataizin = DB::table('pengajuan_izin')
            ->where('nisn', Auth::guard('siswa')->user()->nisn)
            ->orderBy('tgl_izin')
            ->get();
        return view('presensi.izin', compact('dataizin'));
    }
    
    public function buatizin()
    {
        return view('presensi.buatizin');
    }

    public function storeizin(Request $request)
    {
        $nisn = Auth::guard('siswa')->user()->nisn;
        $tgl_izin = $request->input('tgl_izin');
        $status = $request->input('status');
        $alasan = $request->input('keterangan');

        if (!$tgl_izin || !$status || !$alasan) {
            return response()->json(['status' => 'error', 'message' => 'Semua form harus diisi!'], 400);
        }

        if (!in_array($status, ['i', 's'])) {
            return response()->json(['status' => 'error', 'message' => 'Status tidak valid. Hanya "i" (Izin) atau "s" (Sakit).'], 400);
        }

        $cekIzin = DB::table('pengajuan_izin')
            ->where('nisn', $nisn)
            ->where('tgl_izin', $tgl_izin)
            ->count();
            
        if ($cekIzin > 0) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah mengajukan izin pada tanggal ' . date('d-m-Y', strtotime($tgl_izin)) . '.'], 409);
        }
        try {
            $insert = DB::table('pengajuan_izin')->insert([
                'nisn' => $nisn,
                'tgl_izin' => $tgl_izin,
                'status' => $status,
                'keterangan' => $alasan,
                'status_approved' => 0, 
            ]);

            if ($insert) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Izin berhasil diajukan.',
                    'redirect' => route('presensi.izin')
                ]);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Gagal simpan database saat mengajukan izin. Silakan coba lagi.']);
            }
        } catch (\Exception $e) {
            Log::error('Storeizin Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal mengajukan izin karena masalah server.'], 500);
        }
    }

    public function monitoring(){
        return view('presensi.monitoring');
    }

    public function getPresensi(Request $request)
    {
    $tanggal = $request->tanggal;
    $presensi = DB::table('presensi')
        ->select('presensi.*','nama_lengkap', 'nama_jurusan')
        ->join('siswa','presensi.nisn','=','siswa.nisn')
        ->join('jurusan','siswa.kode_jurusan','=','jurusan.kode_jurusan')
        ->where('tgl_presensi', $tanggal) 
        ->get();
    return view('presensi.getpresensi', compact('presensi'));
    }

    public function tampilkanpeta(Request $request)
    {
        $id=$request->id;
        $presensi=DB::table('presensi')->where('id',$id)
        ->join('siswa','presensi.nisn','=','siswa.nisn')
        ->first();
        return view('presensi.showmap', compact('presensi'));
    }

    public function izinSakit()
    {
        $izin_sakit = DB::table('pengajuan_izin')
            ->join('siswa', 'pengajuan_izin.nisn', '=', 'siswa.nisn')
            ->orderBy('tgl_izin', 'desc')
            ->get();

        return view('presensi.izin_sakit', compact('izin_sakit'));
    }

    public function approveIzinSakit(Request $request)
    {
        $status_approve = $request->status_approve;
        $id_izin_sakit_form = $request->id_izin_sakit_form; 
        try {
            DB::table('pengajuan_izin')
                ->where('id', $id_izin_sakit_form)
                ->update([
                    'status_approved' => $status_approve,
                ]);
            return redirect('/presensi/izin-sakit')->with(['success' => 'Data berhasil di-update.']);

        } catch (\Exception $e) {
            return redirect('/presensi/izin-sakit')->with(['warning' => 'Data gagal di-update.']);
        }
    }

    public function batalkanIzinSakit($id)
    {
        try {
            DB::table('pengajuan_izin')
                ->where('id', $id)
                ->update([
                    'status_approved' => 0, 
                ]);
            return redirect('/presensi/izin-sakit')->with(['success' => 'Status berhasil dibatalkan (Pending).']);

        } catch (\Exception $e) {
            return redirect('/presensi/izin-sakit')->with(['warning' => 'Gagal membatalkan status.']);
        }
    }
}