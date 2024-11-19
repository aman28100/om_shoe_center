<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\product;
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


    public function categories()
    {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.categories', compact('categories'));
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

    public function category_add()
    {
        return view('admin.category-add');
    }

    public function Category_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_extension = $image->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extension;
        $this->generateCategoryThumbnailsImage($image, $file_name);
        // $image->storeAs('public/brands', $file_name);

        $category->image = $file_name;
        $category->save();

        return redirect()->route('admin.categories')->with('status', 'Categories has been added successfully');
    }


    public function generateCategoryThumbnailsImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/categories');

        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }

    public function products()
    {
        $products = product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin\products', compact('products'));
    }

    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();

        return view('admin.product-add', compact('categories', 'brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = Carbon::now()->timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName);
            $product->image = $imageName;
        }
        $gallary_arr = array();
        $gallary_image = "";
        $counter = 1;

        if ($request->hasFile('images')) {
            $allowedfileExtion = ['jpg', 'jpeg', 'png'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtion);
                if ($gcheck) {
                    $gfileName = Carbon::now()->timestamp . "-" . $counter . "." . $gextension;
                    $this->GenerateProductThumbnailImage($file, $gfileName);
                    array_push($gallary_arr, $gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallary_image = implode(',', $gallary_arr);
        }
        $product->images =  $gallary_image;
        $product->save();
        return redirect()->route('admin.products')->with('status', "Product has been added Successfully");
    }

    public function GenerateProductThumbnailImage($image, $imageName)
    {
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');

        // Use Image::make() instead of Image::read()
        $img = Image::make($image->path());

        // Resize and crop the image to fit within specified dimensions
        $img->fit(540, 689, function ($constraint) {
            $constraint->upsize(); // Ensures the image won't be upscaled
        })->save($destinationPath . '/' . $imageName);

        // Create a smaller thumbnail version of the image
        $img->fit(104, 104, function ($constraint) {
            $constraint->upsize(); // Ensures the image won't be upscaled
        })->save($destinationPathThumbnail . '/' . $imageName);
    }



    //edit product

    public function product_edit($id)
    {
        $product = product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.product-edit', compact('product', 'categories', 'brands'));
    }

    public function product_update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $request->id,

            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required',
            'sale_price' => 'required',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required',
            'quantity' => 'required',
            'image' => 'mimes:png,jpg,jpeg|max:2048',
            'category_id' => 'required',
            'brand_id' => 'required',
        ]);

        $product = product::find($request->id);
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;


        if ($request->hasFile('image')) {
            if (file::exists(public_path('uploads/products') . "/" . $product->image)) {
                file::delete(public_path('uploads/products') . "/" . $product->image);
            }

            if (file::exists(public_path('uploads/products/thumbnails') . "/" . $product->image)) {
                file::delete(public_path('uploads/products/thumbnails') . "/" . $product->image);
            }


            $image = $request->file('image');
            $imageName = Carbon::now()->timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName);
            $product->image = $imageName;
        }
        $gallary_arr = array();
        $gallary_image = "";
        $counter = 1;

        if ($request->hasFile('images')) {

            foreach (explode(',', $product->images) as $ofile) {
                if (file::exists(public_path('uploads/products') . "/" . $ofile)) {
                    file::delete(public_path('uploads/products') . "/" . $ofile);
                }

                if (file::exists(public_path('uploads/products/thumbnails') . "/" . $ofile)) {
                    file::delete(public_path('uploads/products/thumbnails') . "/" . $ofile);
                }
            }

            $allowedfileExtion = ['jpg', 'jpeg', 'png'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtion);
                if ($gcheck) {
                    $gfileName = Carbon::now()->timestamp . "-" . $counter . "." . $gextension;
                    $this->GenerateProductThumbnailImage($file, $gfileName);
                    array_push($gallary_arr, $gfileName);
                    $counter = $counter + 1;
                }
            }
            $gallary_image = implode(',', $gallary_arr);
            $product->images =  $gallary_image;
        }

        $product->save();
        return redirect()->route('admin.products')->with('status', 'product has been Updated successfully');
    }


    public function product_delete($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return redirect()->route('admin.products')->with('error', 'Product not found.');
        }

        // Delete main product image if it exists
        if (File::exists(public_path('uploads/products/' . $product->image))) {
            File::delete(public_path('uploads/products/' . $product->image));
        }

        // Delete main product thumbnail if it exists
        if (File::exists(public_path('uploads/products/thumbnails/' . $product->image))) {
            File::delete(public_path('uploads/products/thumbnails/' . $product->image));
        }

        // Delete gallery images if they exist
        foreach (explode(',', $product->images) as $ofile) {
            if (File::exists(public_path('uploads/products/' . trim($ofile)))) {
                File::delete(public_path('uploads/products/' . trim($ofile)));
            }
            if (File::exists(public_path('uploads/products/thumbnails/' . trim($ofile)))) {
                File::delete(public_path('uploads/products/thumbnails/' . trim($ofile)));
            }
        }

        // Finally delete the product record from the database
        $product->delete();

        return redirect()->route('admin.products')->with('status', 'Product has been deleted successfully!');
    }
}
