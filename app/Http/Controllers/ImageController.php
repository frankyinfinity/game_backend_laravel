<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function index()
    {
        return view('images.index');
    }

    public function listDataTable(Request $request)
    {
        // Get the latest version (by version desc, then created_at desc) for each docker_image_name + docker_tag pair
        $subQuery = Image::selectRaw('MAX(id) as id')
            ->groupBy('docker_image_name', 'docker_tag');

        $query = Image::query()
            ->whereIn('id', $subQuery)
            ->select('id', 'name', 'docker_image_name', 'docker_tag', 'version', 'is_active', 'created_at')
            ->orderBy('docker_image_name')
            ->orderBy('docker_tag')
            ->get();

        return datatables($query)->toJson();
    }

    public function show(Image $image)
    {
        // Get all versions of this docker image (including current)
        $allVersions = Image::where('docker_image_name', $image->docker_image_name)
            ->where('docker_tag', $image->docker_tag)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get previous inactive versions
        $previousVersions = $allVersions->where('id', '!=', $image->id);

        // Get containers using this image
        $containers = $image->containers()->with('parent')->get();

        return view('images.show', compact('image', 'previousVersions', 'containers'));
    }

    public function activate(Image $image)
    {
        // Deactivate all other versions of this docker image
        Image::where('docker_image_name', $image->docker_image_name)
            ->where('docker_tag', $image->docker_tag)
            ->update(['is_active' => false]);

        // Activate this version
        $image->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('images.index')
            ->with('success', 'Immagine attivata con successo.');
    }

    public function deactivate(Image $image)
    {
        $image->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('images.index')
            ->with('success', 'Immagine disattivata con successo.');
    }

    public function downloadBuildInput(Image $image)
    {
        if (!$image->build_input_path) {
            return redirect()->back()
                ->with('error', 'Nessun build input disponibile per questa immagine.');
        }

        if (!Storage::disk('local')->exists($image->build_input_path)) {
            return redirect()->back()
                ->with('error', 'File build input non trovato.');
        }

        return Storage::disk('local')->download($image->build_input_path);
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $image = Image::find($id);
            if ($image == null) continue;

            // Delete build input file from storage
            if ($image->build_input_path && Storage::disk('local')->exists($image->build_input_path)) {
                Storage::disk('local')->delete($image->build_input_path);
            }

            $image->delete();
        }
        return response()->json(['success' => true]);
    }
}
