<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index()
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        $appName = config('backup.backup.name');
        
        $files = $disk->files($appName);
        
        $backups = collect($files)->filter(function ($file) {
            return str_ends_with($file, '.zip');
        })->map(function ($file) use ($disk) {
            return [
                'path' => $file,
                'name' => basename($file),
                'size' => round($disk->size($file) / 1048576, 2) . ' MB',
                'date' => Carbon::createFromTimestamp($disk->lastModified($file))->format('M d, Y H:i:s'),
            ];
        })->sortByDesc('date')->values();

        return view('admin.backups.index', compact('backups'));
    }

    public function download(string $fileName)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $appName = config('backup.backup.name');
        $path = $appName . '/' . $fileName;

        if (!Storage::disk($diskName)->exists($path)) {
            abort(404, "Backup file not found.");
        }

        return Storage::disk($diskName)->download($path);
    }
}