<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ConferenceRegistration
 *
 * @property int $id
 * @property int $member_id
 * @property string $conference
 * @property string $status
 * @property \Carbon\Carbon $updated_at
 *
 */
class ConferenceRegistration extends Model
{
	protected $table = 'conference_registrations';
	public $timestamps = true;

	protected $casts = [
		'user_id' => 'int',
	];

	protected $fillable = [
		'user_id',
		'conference',
        'status'
	];

	public function registrant()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function getTotalPaidAttribute()
    {
        return '$1038.50';
    }

    public function getBriefSummary()
    {
        return 'Opening Ceremony; Full Tutorials & Exhibits; Student Party; Board Dinner';
    }

	public function __toString()
    {
        return "Registration: {$this->member}::{$this->conference}::{$this->status}";
    }
}
