<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Audit_Log;

class PackageController extends Controller
{
    /**
     * Get all packages for the settings page
     */
    public function index()
    {
        // Eager-load amenities for list management
        $packages = Package::with('amenities')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'packages' => $packages
        ]);
    }

    /**
     * Get a single package
     */
    public function show($id)
    {
        try {
            $package = Package::where('PackageID', $id)->with('amenities')->firstOrFail();

            return response()->json([
                'success' => true,
                'package' => $package
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found.'
            ], 404);
        }
    }

    /**
     * Store a new package
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'amenities' => 'required|array|min:1',
            'amenities.*' => 'string',
        ], [
            'amenities.required' => 'Please select at least one amenity.',
            'amenities.min' => 'Please select at least one amenity.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Save new amenities to database if they don't exist
            foreach ($request->amenities as $amenityName) {
                Amenity::firstOrCreate([
                    'name' => $amenityName
                ], ['is_default' => false]);
            }

            // Convert amenities array to newline-separated string (legacy field kept for compatibility)
            $description = implode("\n", $request->amenities);

            $package = Package::create([
                'Name' => $request->name,
                'Description' => $description,
                'Price' => $request->price,
                'max_guests' => $request->max_guests,
                'excess_rate' => $request->excess_rate ?? 100,
            ]);

            // Attach amenities via pivot
            $amenityIds = Amenity::whereIn('name', $request->amenities)->pluck('id')->toArray();
            $package->amenities()->sync($amenityIds);

            // Audit log: package created
            try {
                Audit_Log::create([
                    'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                    'action' => 'Create Package',
                    'description' => 'Created package ' . ($package->PackageID ?? 'n/a') . ' name: ' . ($package->Name ?? 'n/a'),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'message' => 'Package created successfully!',
                'package' => $package
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create package. Please try again.'
            ], 500);
        }
    }

    /**
     * Update an existing package
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'amenities' => 'required|array|min:1',
            'amenities.*' => 'string',
        ], [
            'amenities.required' => 'Please select at least one amenity.',
            'amenities.min' => 'Please select at least one amenity.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $package = Package::where('PackageID', $id)->firstOrFail();

            // Save new amenities to database if they don't exist
            foreach ($request->amenities as $amenityName) {
                Amenity::firstOrCreate([
                    'name' => $amenityName
                ], ['is_default' => false]);
            }

            // Convert amenities array to newline-separated string (legacy field kept for compatibility)
            $description = implode("\n", $request->amenities);

            $package->update([
                'Name' => $request->name,
                'Description' => $description,
                'Price' => $request->price,
                'max_guests' => $request->max_guests,
                'excess_rate' => $request->excess_rate ?? 0,
            ]);

            // Sync amenities via pivot
            $amenityIds = Amenity::whereIn('name', $request->amenities)->pluck('id')->toArray();
            $package->amenities()->sync($amenityIds);

            // Audit log: package updated
            try {
                Audit_Log::create([
                    'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                    'action' => 'Update Package',
                    'description' => 'Updated package ' . ($package->PackageID ?? $id),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully!',
                'package' => $package
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update package. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete a package
     */
    public function destroy($id)
    {
        try {
            $package = Package::where('PackageID', $id)->firstOrFail();
            
            // Check if package is being used in any bookings
            if ($package->bookings()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete package. It is being used in existing bookings.'
                ], 400);
            }
            
            $package->delete();

            // Audit log: package deleted
            try {
                Audit_Log::create([
                    'user_id' => \Illuminate\Support\Facades\Auth::user()->user_id ?? null,
                    'action' => 'Delete Package',
                    'description' => 'Deleted package ' . ($package->PackageID ?? $id) . ' name: ' . ($package->Name ?? 'n/a'),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete package. Please try again.'
            ], 500);
        }
    }
}
