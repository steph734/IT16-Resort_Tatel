<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Audit_Log extends Model
{
    use HasFactory;

    // Table name (since it's not plural by Laravel convention)
    protected $table = 'audit_log';

    // Primary key
    protected $primaryKey = 'id';

    // Auto-incrementing ID
    public $incrementing = true;

    // Mass-assignable fields
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    // Casts
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Audit log belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
