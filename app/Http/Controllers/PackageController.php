<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::all();
        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Name' => 'required|string|max:50',
            'Description' => 'required|string|max:255',
            'Price' => 'required|numeric|min:0',
        ]);

        Package::create([
            'Name' => $request->input('Name'),
            'Description' => $request->input('Description'),
            'Price' => $request->input('Price'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function edit($id)
    {
        $package = Package::findOrFail($id);
        return view('packages.edit', compact('package'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'Name' => 'required|string|max:50',
            'Description' => 'required|string|max:255',
            'Price' => 'required|numeric|min:0',
        ]);

        $package = Package::findOrFail($id);
        $package->update([
            'Name' => $request->input('Name'),
            'Description' => $request->input('Description'),
            'Price' => $request->input('Price'),
            'updated_at' => now(),
        ]);

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy($id)
    {
        $package = Package::findOrFail($id);
        $package->delete();

        return redirect()->route('packages.index')->with('success', 'Package deleted successfully.');
    }
}
