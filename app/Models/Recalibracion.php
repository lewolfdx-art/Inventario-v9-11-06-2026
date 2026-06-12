<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recalibracion extends Model
{
    use HasFactory;

    protected $table = 'recalibraciones';

    protected $fillable = [
        'producto_id',
        'fecha_recalibracion',
        'proxima_recalibracion',
        'observaciones',
        'realizada_por_nombre',
    ];

    protected $casts = [
        'fecha_recalibracion' => 'date',
        'proxima_recalibracion' => 'date',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}