<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Symfony\Contracts\Service\Attribute\Required;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Intervention\Image\Facades\Image;


class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function add_brand()
    {
        return view('admin.brand-add');
    }

    public function brand_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extension = $image->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extension;
        $this->generateBrandThumbnailsImage($image, $file_name);
        $image->storeAs('public/brands', $file_name);

        $brand->image = $file_name;
        $brand->save();

        return redirect()->route('admin.brands')->with('status', 'Brand has been added successfully');
    }


    public function brand_edit($id)
    {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brand_update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);


        $brand = Brand::find($id);
        $brand->name = $request->name;
        $brand->slug = $request->slug;

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if (File::exists(public_path('uploads/brands/' . $brand->image))) {
                File::delete(public_path('uploads/brands/' . $brand->image));
            }
            $image = $request->file('image');
            $file_extension = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extension;
            $this->generateBrandThumbnailsImage($image, $file_name);
            $brand->image = $file_name;
        }
        // Upload the new image


        $brand->save();

        return redirect()->route('admin.brands')->with('status', 'Brand has been updated successfully.');
    }


    public function generateBrandThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands');

        // Create an image instance using Intervention Image
        $img = Image::make($image->path());

        // Crop or resize as needed
        $img->fit(124, 124, function ($constraint) {
            $constraint->upsize(); // Prevent image from being upsized
        });

        // Save the image to the desired location
        $img->save($destinationPath . '/' . $imageName);
    }
}
