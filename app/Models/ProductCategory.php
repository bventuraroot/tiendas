<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * Productos que tienen esta categoría por nombre (products.category es string).
     * No es relación Eloquent directa; útil para contar.
     */
    public function productsCount(): int
    {
        return \DB::table('products')->where('category', $this->name)->count();
    }
}
