<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectResponsibleAssignment extends Model { protected $fillable=['project_id','user_id','starts_at','ends_at','assigned_by']; protected $casts=['starts_at'=>'date','ends_at'=>'date']; public function project():BelongsTo{return $this->belongsTo(Project::class);} public function user():BelongsTo{return $this->belongsTo(User::class);} public function assigner():BelongsTo{return $this->belongsTo(User::class,'assigned_by');}}