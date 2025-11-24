<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\siswa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query=siswa::query();
        $query->select('siswa.*','nama_jurusan');
        $query->join('jurusan', 'siswa.kode_jurusan', '=', 'jurusan.kode_jurusan');
        $query->orderBy('nama_lengkap');

        if(!empty($request->nama_siswa)){
            $query->where('nama_lengkap','like','%'.$request->nama_siswa.'%');
        }

        if(!empty($request->kode_jurusan)){
        $query->where('siswa.kode_jurusan',$request->kode_jurusan);
        }

        $siswa=$query->paginate(10);

        $jurusan = DB::table('jurusan')->get();
            
        return view('siswa.index', compact('siswa','jurusan')); 
    }

public function store(Request $request){
        $nisn = $request->nisn;
        $nama_lengkap = $request->nama_lengkap;
        $kelas = $request->kelas;
        $no_hp = $request->no_hp;
        $kode_jurusan=$request->kode_jurusan;
        $password= Hash::make('12345');
        
        $foto = ""; 
        $disk_path = "uploads/foto_siswa"; 

        if ($request->hasFile('foto')) {
            $foto = $nisn.".".$request->file('foto')->getClientOriginalExtension();
        }

        try{
            $data = [
                'nisn' => $nisn,
                'nama_lengkap' => $nama_lengkap,
                'kelas' => $kelas,
                'no_hp' => $no_hp,
                'kode_jurusan' => $kode_jurusan,
                'foto' => $foto,
                'password' => $password
            ];
            
            $simpan=DB::table('siswa')->insert($data);
            
            if($simpan){
                if($request->hasFile('foto')){
                    $request->file('foto')->storeAs($disk_path, $foto, 'public');
                }

                return Redirect::back()->with(['success'=>'Data Tersimpan!']);
            }
        } catch (\Exception $e){
            Log::error("Error saat menyimpan siswa: " . $e->getMessage());
            return Redirect::back()->with(['warning'=>'Gagal menyimpan data!']);
        }
    }

    public function edit(Request $request) 
    {
        $nisn = $request->nisn;
        
        $siswa = DB::table('siswa')->where('nisn', $nisn)->first();

        if (!$siswa) {
            return response('Data siswa tidak ditemukan', 404); 
        }

        $jurusan = DB::table('jurusan')->get(); 

        return view('siswa.edit', compact('siswa', 'jurusan'));
    }

    public function update(Request $request, $nisn_param){
        $nisn = $request->nisn;
        $nama_lengkap = $request->nama_lengkap;
        $kelas = $request->kelas;
        $no_hp = $request->no_hp;
        $kode_jurusan = $request->kode_jurusan;
        $password = Hash::make('12345');

        $siswa = DB::table('siswa')->where('nisn', $nisn_param)->first();

        if (!$siswa) {
            return Redirect::back()->with(['warning' => 'Data siswa tidak ditemukan untuk diupdate.']);
        }

        $old_foto_name = $siswa->foto;
        $disk_path = 'uploads/foto_siswa'; 
        $foto = $old_foto_name;
        if ($request->hasFile('foto')) {
            $extension = $request->file('foto')->getClientOriginalExtension();
            $foto = $nisn . '.' . $extension;
        }

        try {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'kelas' => $kelas,
                'no_hp' => $no_hp,
                'kode_jurusan' => $kode_jurusan,
                'foto' => $foto,
                'password' => $password
            ];

            $update = DB::table('siswa')->where('nisn', $nisn_param)->update($data);

            if ($request->hasFile('foto')) {
                if (!empty($old_foto_name)) {
                    $old_path = $disk_path . '/' . $old_foto_name;
                    if (Storage::disk('public')->exists($old_path)) {
                        Storage::disk('public')->delete($old_path);
                    }
                }

                $request->file('foto')->storeAs($disk_path, $foto, 'public');
            }

            if ($update || $request->hasFile('foto')) {
                return Redirect::back()->with(['success' => 'Data Diupdate!']);
            }

            return Redirect::back()->with(['warning' => 'Tidak ada perubahan data yang disimpan.']);
        } catch (\Exception $e) {
            Log::error('Error saat mengupdate siswa: ' . $e->getMessage());
            return Redirect::back()->with(['warning' => 'Gagal mengupdate data!']);
        }
    }
    
     public function delete($nisn)
     {
        $delete=DB::table('siswa')->where('nisn',$nisn)->delete();
        if($delete){
            return Redirect::back()->with(['success'=>'Data Dihapus']);
        }else{
            return Redirect::back()->with(['warning'=>'Data gagal dihapus']); 
        }
     }
}